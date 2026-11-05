package br.com.jjferreiras.mapos;

import java.io.ByteArrayInputStream;
import java.nio.charset.StandardCharsets;
import java.util.ArrayList;
import java.util.List;
import javax.xml.parsers.DocumentBuilder;
import javax.xml.parsers.DocumentBuilderFactory;
import org.w3c.dom.Document;
import org.w3c.dom.Element;
import org.w3c.dom.Node;
import org.w3c.dom.NodeList;

/**
 * Operações ISAPI dos equipamentos Hikvision (HTTP + Digest).
 *
 * Cobre o que a configuração de um CFTV precisa: identificar o equipamento, ler
 * o NVR e seus canais, ligar uma câmera IP a um canal livre e ajustar o IP de
 * fábrica (192.168.1.64) para o IP do projeto.
 */
final class HikvisionIsapi {

    private HikvisionIsapi() {}

    static final class Info {
        String modelo = "";
        String serie = "";
        String firmware = "";
        String nome = "";
        String mac = "";
        boolean ativada;
    }

    static final class Canal {
        int id;
        String nome = "";
        String ip = "";
        String protocolo = "";
        String modelo = "";
        boolean online;
        boolean habilitado = true;
    }

    static final class Resultado {
        final boolean ok;
        final String mensagem;

        Resultado(boolean ok, String mensagem) {
            this.ok = ok;
            this.mensagem = mensagem;
        }
    }

    /** URL http://ip:porta/caminho (porta 80 omitida). */
    static String url(String ip, int porta, String caminho) {
        int p = porta <= 0 ? 80 : porta;
        return "http://" + ip + (p == 80 ? "" : ":" + p) + caminho;
    }

    // ------------------------------------------------------------------
    // Identificação
    // ------------------------------------------------------------------

    /**
     * Identificação do equipamento.
     *
     * 401 = ativado e exigindo senha; 200 = ativado e aberto; 403 = ativado com
     * acesso negado; outros códigos = inacessível ou não é Hikvision.
     */
    static Info info(String ip, int porta, String usuario, String senha) {
        Info info = new Info();
        HikvisionHttp.Resposta r = HikvisionHttp.get(url(ip, porta, "/ISAPI/System/deviceInfo"), usuario, senha);

        if (r.codigo == 401) {
            info.ativada = true;
            return info;
        }
        if (r.codigo == 403) {
            info.ativada = true;
            return info;
        }
        if (r.codigo != 200) {
            return null;
        }

        // Camera de fabrica responde 200 sem senha, com <DeviceNotActivated>
        // (ou subStatusCode "notActivated"): ainda nao foi ativada.
        String corpo = r.corpo.toLowerCase(java.util.Locale.US);
        info.ativada = !(corpo.contains("notactivated") || corpo.contains("unactivated"));
        Element raiz = xml(r.corpo);
        if (raiz == null) {
            return info;
        }
        info.nome = texto(raiz, "deviceName");
        info.modelo = texto(raiz, "model");
        info.serie = texto(raiz, "serialNumber");
        info.firmware = texto(raiz, "firmwareVersion");

        // O MAC vem de outro endpoint; sem ele, o SADP é a única fonte.
        HikvisionHttp.Resposta rede = HikvisionHttp.get(
            url(ip, porta, "/ISAPI/System/Network/interfaces"), usuario, senha);
        if (rede.codigo == 200) {
            Element ifs = xml(rede.corpo);
            if (ifs != null) {
                info.mac = texto(ifs, "MACAddress");
            }
        }

        return info;
    }

    /** O equipamento está ativado? (câmera nova de fábrica responde 200 vazio em /ISAPI). */
    static boolean ativada(String ip, int porta) {
        HikvisionHttp.Resposta r = HikvisionHttp.get(url(ip, porta, "/ISAPI/System/deviceInfo"), null, null);
        if (r.codigo == 401 || r.codigo == 403) {
            return true;
        }
        if (r.codigo != 200) {
            return false;
        }
        String corpo = r.corpo.toLowerCase(java.util.Locale.US);
        return !(corpo.contains("notactivated") || corpo.contains("unactivated"));
    }

    // ------------------------------------------------------------------
    // Canais do NVR
    // ------------------------------------------------------------------

    /**
     * Canais do NVR. "StreamingProxy" é o que o NVR expõe; "InputProxy" é a
     * configuração de entrada das câmeras IP. Lemos os dois e cruzamos por id.
     */
    static List<Canal> canais(String ip, int porta, String usuario, String senha) {
        List<Canal> canais = new ArrayList<>();

        HikvisionHttp.Resposta cfg = HikvisionHttp.get(
            url(ip, porta, "/ISAPI/ContentMgmt/InputProxy/channels"), usuario, senha);
        if (cfg.codigo == 200) {
            Element raiz = xml(cfg.corpo);
            if (raiz != null) {
                for (Element c : filhos(raiz, "InputProxyChannel")) {
                    Canal canal = new Canal();
                    canal.id = inteiro(texto(c, "id"));
                    canal.nome = texto(c, "name");
                    canal.protocolo = texto(c, "protocol");
                    Element desc = primeiro(c, "sourceInputPortDescriptor");
                    if (desc != null) {
                        canal.ip = texto(desc, "ipAddress");
                    }
                    if (canal.id > 0) {
                        canais.add(canal);
                    }
                }
            }
        }

        HikvisionHttp.Resposta st = HikvisionHttp.get(
            url(ip, porta, "/ISAPI/ContentMgmt/StreamingProxy/channels"), usuario, senha);
        if (st.codigo == 200) {
            Element raiz = xml(st.corpo);
            if (raiz != null) {
                for (Element c : filhos(raiz, "StreamingProxyChannel")) {
                    int id = inteiro(texto(c, "id"));
                    canalCom(canais, id).online = "true".equalsIgnoreCase(texto(c, "online"));
                }
            }
        }

        // Status por canal (firmware antigo separa a informação).
        for (Canal canal : canais) {
            HikvisionHttp.Resposta s = HikvisionHttp.get(
                url(ip, porta, "/ISAPI/ContentMgmt/InputProxy/channels/" + canal.id + "/status"), usuario, senha);
            if (s.codigo == 200) {
                canal.online = "true".equalsIgnoreCase(extrair(s.corpo, "online"))
                    || "true".equalsIgnoreCase(extrair(s.corpo, "isOnline"));
            }
        }

        return canais;
    }

    private static Canal canalCom(List<Canal> lista, int id) {
        for (Canal c : lista) {
            if (c.id == id) return c;
        }
        Canal novo = new Canal();
        novo.id = id;
        lista.add(novo);
        return novo;
    }

    /**
     * Liga a câmera IP a um canal do NVR.
     *
     * Firmware novo aceita PUT em /channels/{id}; o antigo só aceita POST no
     * mesmo caminho. Tentamos PUT e, se recusar, POST.
     */
    static Resultado adicionarCanal(
        String nvrIp,
        int nvrPorta,
        String nvrUsuario,
        String nvrSenha,
        int canal,
        String cameraIp,
        String cameraUsuario,
        String cameraSenha,
        String protocolo
    ) {
        if (canal < 1) {
            return new Resultado(false, "Canal inválido.");
        }

        String xml = "<InputProxyChannel xmlns=\"http://www.hikvision.com/ver20/XMLSchema\">"
            + "<id>" + canal + "</id>"
            + "<name>" + esc("CAM " + canal) + "</name>"
            + "<sourceInputPortDescriptor>"
            + "<ipAddress>" + esc(cameraIp) + "</ipAddress>"
            + "<managePortNo>8000</managePortNo>"
            + "<srcInputPort>1</srcInputPort>"
            + "<userName>" + esc(cameraUsuario) + "</userName>"
            + "<streamType>auto</streamType>"
            + "</sourceInputPortDescriptor>"
            + "<protocol>" + esc(protocolo == null || protocolo.isEmpty() ? "HIKVISION" : protocolo) + "</protocol>"
            + "</InputProxyChannel>";

        String caminho = "/ISAPI/ContentMgmt/InputProxy/channels/" + canal;
        HikvisionHttp.Resposta r = HikvisionHttp.put(
            url(nvrIp, nvrPorta, caminho), xml, nvrUsuario, nvrSenha);

        if (r.codigo == 200 || r.codigo == 201) {
            return new Resultado(true, "Canal " + canal + " configurado no NVR.");
        }

        // POST com o mesmo XML (firmware que não aceita PUT).
        HikvisionHttp.Resposta r2 = HikvisionHttp.requisitar(
            "POST", url(nvrIp, nvrPorta, "/ISAPI/ContentMgmt/InputProxy/channels"),
            xml, nvrUsuario, nvrSenha);
        if (r2.codigo == 200 || r2.codigo == 201) {
            return new Resultado(true, "Canal " + canal + " configurado no NVR.");
        }

        String detalhe = mensagemErro(r.corpo);
        return new Resultado(false, detalhe.isEmpty() ? "O NVR recusou o canal " + canal + " (HTTP " + r.codigo + ")." : detalhe);
    }

    // ------------------------------------------------------------------
    // Câmera
    // ------------------------------------------------------------------

    /** Troca IP/máscara/gateway/DHCP da câmera (o padrão de fábrica é 192.168.1.64). */
    static Resultado definirRedeCamera(
        String ipAtual,
        int porta,
        String usuario,
        String senha,
        String novoIp,
        String mascara,
        String gateway,
        boolean dhcp
    ) {
        HikvisionHttp.Resposta atual = HikvisionHttp.get(
            url(ipAtual, porta, "/ISAPI/System/Network/interfaces/1"), usuario, senha);
        if (atual.codigo != 200) {
            return new Resultado(false, "Não foi possível ler a rede da câmera (HTTP " + atual.codigo + ").");
        }

        String xml = "<NetworkInterface xmlns=\"http://www.hikvision.com/ver20/XMLSchema\">"
            + "<id>1</id>"
            + "<IPAddress>"
            + "<ipVersion>v4</ipVersion>"
            + "<addressingType>" + (dhcp ? "dhcp" : "static") + "</addressingType>"
            + "<ipAddress>" + esc(novoIp) + "</ipAddress>"
            + (mascara != null && !mascara.isEmpty()
                ? "<subnetMask>" + esc(mascara) + "</subnetMask>" : "")
            + (gateway != null && !gateway.isEmpty()
                ? "<DefaultGateway><ipAddress>" + esc(gateway) + "</ipAddress></DefaultGateway>" : "")
            + "</IPAddress>"
            + "</NetworkInterface>";

        HikvisionHttp.Resposta r = HikvisionHttp.put(
            url(ipAtual, porta, "/ISAPI/System/Network/interfaces/1"), xml, usuario, senha);
        if (r.codigo == 200) {
            return new Resultado(true, "IP da câmera alterado para " + novoIp + ".");
        }

        return new Resultado(false, "A câmera recusou a troca de IP (HTTP " + r.codigo + ").");
    }

    /** Ativa uma câmera nova de fábrica definindo a senha inicial. */
    static Resultado ativarCamera(String ip, int porta, String senha) {
        String xml = "<ActivateInfo xmlns=\"http://www.hikvision.com/ver20/XMLSchema\">"
            + "<password>" + esc(senha) + "</password>"
            + "</ActivateInfo>";

        HikvisionHttp.Resposta r = HikvisionHttp.requisitar(
            "PUT", url(ip, porta, "/ISAPI/System/activate"), xml, null, null);
        if (r.codigo == 200 || r.codigo == 201) {
            return new Resultado(true, "Câmera ativada.");
        }

        String detalhe = mensagemErro(r.corpo);
        return new Resultado(false, detalhe.isEmpty() ? "A câmera recusou a ativação (HTTP " + r.codigo + ")." : detalhe);
    }

    // ------------------------------------------------------------------
    // XML
    // ------------------------------------------------------------------

    static Element xml(String corpo) {
        if (corpo == null || corpo.isEmpty()) {
            return null;
        }
        try {
            DocumentBuilderFactory fabrica = DocumentBuilderFactory.newInstance();
            fabrica.setNamespaceAware(false);
            // Não há DTD em ISAPI; desligar evita entidade externa.
            fabrica.setFeature("http://apache.org/xml/features/disallow-doctype-decl", true);
            DocumentBuilder builder = fabrica.newDocumentBuilder();
            Document doc = builder.parse(new ByteArrayInputStream(corpo.getBytes(StandardCharsets.UTF_8)));
            return doc.getDocumentElement();
        } catch (Exception e) {
            return null;
        }
    }

    private static List<Element> filhos(Element pai, String nome) {
        List<Element> lista = new ArrayList<>();
        NodeList nos = pai.getChildNodes();
        for (int i = 0; i < nos.getLength(); i++) {
            Node n = nos.item(i);
            if (n.getNodeType() == Node.ELEMENT_NODE && nomeIgual(n.getNodeName(), nome)) {
                lista.add((Element) n);
            }
        }
        // Alguns firmwares aninham em outro elemento: busca profunda como reserva.
        if (lista.isEmpty()) {
            NodeList todos = pai.getElementsByTagName(nome);
            for (int i = 0; i < todos.getLength(); i++) {
                lista.add((Element) todos.item(i));
            }
        }
        return lista;
    }

    private static Element primeiro(Element pai, String nome) {
        List<Element> lista = filhos(pai, nome);
        return lista.isEmpty() ? null : lista.get(0);
    }

    private static boolean nomeIgual(String no, String esperado) {
        return no.equals(esperado) || no.endsWith(":" + esperado);
    }

    static String texto(Element pai, String nome) {
        Element e = primeiro(pai, nome);
        return e == null ? "" : e.getTextContent().trim();
    }

    private static String extrair(String xml, String tag) {
        if (xml == null) return "";
        java.util.regex.Matcher m = java.util.regex.Pattern
            .compile("<" + tag + ">([^<]*)</" + tag + ">", java.util.regex.Pattern.CASE_INSENSITIVE)
            .matcher(xml);
        return m.find() ? m.group(1).trim() : "";
    }

    private static String mensagemErro(String corpo) {
        String sub = extrair(corpo, "subStatusCode");
        if (sub.isEmpty()) {
            sub = extrair(corpo, "statusString");
        }
        return sub;
    }

    private static int inteiro(String valor) {
        try {
            return Integer.parseInt(valor.trim());
        } catch (Exception e) {
            return 0;
        }
    }

    private static String esc(String valor) {
        if (valor == null) return "";
        return valor.replace("&", "&amp;").replace("<", "&lt;").replace(">", "&gt;")
            .replace("\"", "&quot;").replace("'", "&apos;");
    }
}
