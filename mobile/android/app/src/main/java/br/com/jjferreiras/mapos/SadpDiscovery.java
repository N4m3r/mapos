package br.com.jjferreiras.mapos;

import android.content.Context;
import android.net.wifi.WifiManager;
import java.net.DatagramPacket;
import java.net.Inet4Address;
import java.net.InetAddress;
import java.net.MulticastSocket;
import java.net.NetworkInterface;
import java.net.SocketTimeoutException;
import java.nio.charset.StandardCharsets;
import java.util.ArrayList;
import java.util.Collections;
import java.util.LinkedHashMap;
import java.util.List;
import java.util.Locale;
import java.util.Map;
import java.util.UUID;

/**
 * SADP (Hikvision Service Auto-Discovery Protocol) sobre UDP/37020.
 *
 * O que vai e volta é XML — não existe pacote binário no UDP (o formato binário
 * só existe na variante L2/raw Ethernet, que exigiria root e não é usada aqui).
 *
 * A vantagem sobre varrer portas: a resposta traz MAC, série, modelo,
 * firmware, IP/máscara/gateway, DHCP e — o mais importante para o projeto —
 * se o equipamento já foi ativado. Sem isso não dá para casar a câmera
 * descoberta com o ponto que o cadastro do projeto previu.
 */
final class SadpDiscovery {

    private static final String GRUPO = "239.255.255.250";
    private static final int PORTA = 37020;

    private SadpDiscovery() {}

    /** Probe: XML puro. O mesmo UUID vai em todos os reenvios da varredura. */
    private static byte[] probe(String uuid) {
        String xml = "<?xml version=\"1.0\" encoding=\"utf-8\"?>"
            + "<Probe>"
            + "<Uuid>" + uuid + "</Uuid>"
            + "<Types>inquiry</Types>"
            + "<ResponseMode>2</ResponseMode>"
            + "</Probe>";
        return xml.getBytes(StandardCharsets.UTF_8);
    }

    /**
     * Envia o probe por multicast e por broadcast e junta as respostas.
     *
     * Respostas duplicadas (o equipamento costuma responder às duas rotas) são
     * removidas pelo MAC normalizado.
     */
    static List<HikvisionDiscovery.Info> descobrir(Context contexto, int duracaoMs) {
        Map<String, HikvisionDiscovery.Info> porMac = new LinkedHashMap<>();
        MulticastSocket socket = null;
        WifiManager.MulticastLock lock = null;

        try {
            // Sem o lock o Android descarta multicast vindo de interfaces
            // que não a rota padrão (é o caso do adaptador USB/Ethernet).
            WifiManager wifi = (WifiManager) contexto.getApplicationContext()
                .getSystemService(Context.WIFI_SERVICE);
            if (wifi != null) {
                lock = wifi.createMulticastLock("sadp-discovery");
                lock.setReferenceCounted(false);
                lock.acquire();
            }

            socket = new MulticastSocket(PORTA);
            socket.setReuseAddress(true);
            socket.setBroadcast(true);
            socket.setLoopbackMode(false);
            socket.setTimeToLive(4);
            socket.setSoTimeout(400);

            InetAddress grupo = InetAddress.getByName(GRUPO);
            List<InetAddress> destinos = new ArrayList<>();
            destinos.add(grupo);
            destinos.add(InetAddress.getByName("255.255.255.255"));

            for (NetworkInterface nif : Collections.list(NetworkInterface.getNetworkInterfaces())) {
                if (!nif.isUp() || nif.isLoopback()) continue;
                try {
                    socket.joinGroup(new java.net.InetSocketAddress(grupo, PORTA), nif);
                } catch (Exception ignorada) {
                    // interface sem suporte a multicast: segue com as demais
                }
                for (java.net.InterfaceAddress endereco : nif.getInterfaceAddresses()) {
                    if (endereco.getAddress() instanceof Inet4Address && endereco.getBroadcast() != null) {
                        destinos.add(endereco.getBroadcast());
                    }
                }
            }

            byte[] pacote = probe(UUID.randomUUID().toString());
            int duracao = Math.max(1500, duracaoMs);
            long fim = System.currentTimeMillis() + duracao;
            long proximoEnvio = 0;
            byte[] buffer = new byte[64 * 1024];

            while (System.currentTimeMillis() < fim) {
                long agora = System.currentTimeMillis();
                // Reenvia a cada 2 s: UDP não é confiável e o device pode estar
                // subindo quando o técnico chega.
                if (agora >= proximoEnvio) {
                    for (InetAddress destino : destinos) {
                        try {
                            socket.send(new DatagramPacket(pacote, pacote.length, destino, PORTA));
                        } catch (Exception ignorada) {
                            // alguns aparelhos recusam o envio a certos endereços
                        }
                    }
                    proximoEnvio = agora + 2000;
                }

                try {
                    DatagramPacket resposta = new DatagramPacket(buffer, buffer.length);
                    socket.receive(resposta);
                    HikvisionDiscovery.Info info = interpretar(resposta.getData(), resposta.getLength());
                    if (info != null) {
                        String chave = info.mac.isEmpty() ? info.ip + "|" + info.serie : info.mac.toUpperCase(Locale.US);
                        porMac.put(chave, info);
                    }
                } catch (SocketTimeoutException esperado) {
                    // sem resposta neste ciclo: continua até o prazo
                }
            }
        } catch (Exception e) {
            // Socket indisponível (sem rede, porta ocupada): devolve o que houver.
        } finally {
            if (socket != null) {
                try {
                    socket.close();
                } catch (Exception ignorada) {
                    // ignora
                }
            }
            if (lock != null && lock.isHeld()) {
                lock.release();
            }
        }

        List<HikvisionDiscovery.Info> lista = new ArrayList<>(porMac.values());
        lista.sort((a, b) -> HikvisionDiscovery.compararIp(a.ip, b.ip));
        return lista;
    }

    /**
     * Interpreta uma resposta ProbeMatch/Hello.
     *
     * O equipamento pode prefixar lixo, enviar NUL à direita ou misturar
     * acentos em UTF-8/latin-1; por isso o recorte é defensivo.
     */
    static HikvisionDiscovery.Info interpretar(byte[] dados, int tamanho) {
        String texto = recortarXml(dados, tamanho);
        if (texto.isEmpty()) {
            return null;
        }
        String raiz = texto.toLowerCase(Locale.US);
        if (!raiz.contains("<probematch") && !raiz.contains("<hello")) {
            return null;
        }

        String ip = campo(texto, "IPv4Address");
        if (ip.isEmpty()) {
            return null;
        }

        HikvisionDiscovery.Info info = new HikvisionDiscovery.Info();
        info.ip = ip;
        info.mac = campo(texto, "MAC").replace('-', ':').toUpperCase(Locale.US);
        info.serie = campo(texto, "DeviceSN");
        if (info.serie.isEmpty()) {
            info.serie = campo(texto, "SerialNumber");
        }
        info.sadpId = info.serie;
        info.modelo = campo(texto, "DeviceDescription");
        info.fabricante = "Hikvision";
        info.firmware = campo(texto, "SoftwareVersion");
        info.ativado = "true".equalsIgnoreCase(campo(texto, "Activated"));
        info.mascara = campo(texto, "IPv4SubnetMask");
        info.gateway = campo(texto, "IPv4Gateway");
        info.dhcp = "true".equalsIgnoreCase(campo(texto, "DHCP"));

        String http = campo(texto, "HttpPort");
        if (!http.isEmpty()) {
            try {
                info.porta = Integer.parseInt(http);
            } catch (NumberFormatException ignorada) {
                info.porta = 80;
            }
        }

        return info;
    }

    /** Recorta o XML do datagrama: do primeiro `<` até o último `>`, sem NUL. */
    static String recortarXml(byte[] dados, int tamanho) {
        if (dados == null || tamanho <= 0) {
            return "";
        }
        int inicio = -1;
        for (int i = 0; i < tamanho; i++) {
            if (dados[i] == '<') {
                inicio = i;
                break;
            }
        }
        if (inicio < 0) {
            return "";
        }
        int fim = -1;
        for (int i = tamanho - 1; i >= inicio; i--) {
            if (dados[i] == '>') {
                fim = i;
                break;
            }
        }
        if (fim <= inicio) {
            return "";
        }

        String texto = new String(dados, inicio, fim - inicio + 1, StandardCharsets.UTF_8);
        if (texto.indexOf('�') >= 0) {
            // Firmware antigo responde latin-1: refaz a leitura.
            texto = new String(dados, inicio, fim - inicio + 1, StandardCharsets.ISO_8859_1);
        }
        return texto;
    }

    /** Primeiro valor de `<Tag>` (aceita prefixo de namespace). */
    static String campo(String xml, String tag) {
        java.util.regex.Matcher m = java.util.regex.Pattern
            .compile("<(?:[\\w.-]+:)?" + java.util.regex.Pattern.quote(tag) + "(?:\\s[^>]*)?>([^<]*)</",
                java.util.regex.Pattern.CASE_INSENSITIVE)
            .matcher(xml);
        return m.find() ? m.group(1).trim() : "";
    }
}
