package br.com.jjferreiras.mapos;

import android.content.Context;
import android.net.ConnectivityManager;
import android.net.DhcpInfo;
import android.net.LinkAddress;
import android.net.LinkProperties;
import android.net.Network;
import android.net.wifi.WifiManager;
import java.net.Inet4Address;
import java.net.InetAddress;
import java.net.NetworkInterface;
import java.util.ArrayList;
import java.util.Collections;
import java.util.LinkedHashMap;
import java.util.List;
import java.util.Map;
import java.util.concurrent.ExecutorService;
import java.util.concurrent.Executors;
import java.util.concurrent.TimeUnit;
import java.util.concurrent.atomic.AtomicInteger;

/**
 * Descoberta de equipamentos Hikvision na rede local.
 *
 * Não há SADP "de biblioteca": o protocolo é binário UDP e varia por firmware.
 * O caminho que funciona em qualquer versão é a varredura do segmento com as
 * portas que todo Hikvision escuta de fábrica:
 *
 *  - 80  (ISAPI/HTTP) — identifica modelo, série, firmware;
 *  - 554 (RTSP)       — confirma que é câmera sem exigir senha.
 *
 * A varredura é paralela e com timeout curto (rede de campo tem latência
 * baixa). Endereços que já respondem param a varredura: o caso comum é achar
 * um punhado de câmeras num /24.
 */
final class HikvisionDiscovery {

    interface Progresso {
        void aoEncontrar(Info dispositivo, int encontrados, int feitos, int total);
    }

    /** Um dispositivo encontrado (o que dá para saber sem credenciais). */
    static final class Info {
        String ip = "";
        String mac = "";
        String serie = "";
        String sadpId = "";
        String modelo = "";
        String fabricante = "Hikvision";
        String firmware = "";
        boolean ativado;
        int porta = 80;
        String mascara;
        String gateway;
        boolean dhcp;
    }

    private HikvisionDiscovery() {}

    /** Faixa /24 onde o aparelho está (o adaptador USB quando é o caso). */
    static String prefixoRede(Context contexto) {
        ConnectivityManager cm = (ConnectivityManager) contexto.getSystemService(Context.CONNECTIVITY_SERVICE);
        Network rede = cm == null ? null : cm.getActiveNetwork();
        if (rede != null && cm != null) {
            LinkProperties lp = cm.getLinkProperties(rede);
            if (lp != null) {
                for (LinkAddress endereco : lp.getLinkAddresses()) {
                    if (endereco.getAddress() instanceof Inet4Address
                        && !endereco.getAddress().isLinkLocalAddress()) {
                        String ip = endereco.getAddress().getHostAddress();
                        int p = ip.lastIndexOf('.');
                        if (p > 0) {
                            return ip.substring(0, p);
                        }
                    }
                }
            }
        }

        // Celular em Wi-Fi sem permissão de link properties: tenta a interface
        // ethernet/wlan diretamente.
        try {
            for (NetworkInterface nif : Collections.list(NetworkInterface.getNetworkInterfaces())) {
                if (!nif.isUp() || nif.isLoopback()) continue;
                for (InetAddress endereco : Collections.list(nif.getInetAddresses())) {
                    if (endereco instanceof Inet4Address && !endereco.isLinkLocalAddress()) {
                        String ip = endereco.getHostAddress();
                        int p = ip.lastIndexOf('.');
                        if (p > 0) return ip.substring(0, p);
                    }
                }
            }
        } catch (Exception ignorada) {
            // sem rede
        }

        return null;
    }

    /** Gateway DHCP (para gravar na câmera quando o técnico escolher DHCP). */
    static String gateway(Context contexto) {
        WifiManager wifi = (WifiManager) contexto.getApplicationContext().getSystemService(Context.WIFI_SERVICE);
        if (wifi != null) {
            DhcpInfo dhcp = wifi.getDhcpInfo();
            if (dhcp != null && dhcp.gateway != 0) {
                return ipDeInt(dhcp.gateway);
            }
        }
        return null;
    }

    /** Faixa /24 do aparelho, varrendo 1..254 com concorrência limitada. */
    static List<Info> varrer(Context contexto, String prefixo, int timeoutMs, Progresso progresso) {
        List<Info> encontrados = Collections.synchronizedList(new ArrayList<Info>());
        if (prefixo == null || prefixo.isEmpty()) {
            return encontrados;
        }

        int total = 254;
        AtomicInteger feitos = new AtomicInteger();
        ExecutorService pool = Executors.newFixedThreadPool(48);

        for (int n = 1; n <= 254; n++) {
            final String ip = prefixo + "." + n;
            pool.submit(() -> {
                Info info = sondar(ip, Math.max(250, timeoutMs));
                if (info != null) {
                    encontrados.add(info);
                    if (progresso != null) {
                        progresso.aoEncontrar(info, encontrados.size(), feitos.incrementAndGet(), total);
                    }
                } else {
                    feitos.incrementAndGet();
                }
            });
        }

        pool.shutdown();
        try {
            // A varredura de um /24 em 48 threads leva poucos segundos.
            pool.awaitTermination(45, TimeUnit.SECONDS);
        } catch (InterruptedException e) {
            Thread.currentThread().interrupt();
        }

        // Ordem por IP para a tela não "embaralhar" a cada busca.
        List<Info> ordenada = new ArrayList<>(encontrados);
        ordenada.sort((a, b) -> compararIp(a.ip, b.ip));
        return ordenada;
    }

    /**
     * Um endereço é Hikvision se responde em alguma das portas de fábrica.
     * Sem senha só dá para saber que existe; modelo e série vêm depois (ISAPI).
     */
    static Info sondar(String ip, int timeoutMs) {
        boolean porta80 = portaAberta(ip, 80, timeoutMs);
        boolean porta554 = portaAberta(ip, 554, timeoutMs);
        if (!porta80 && !porta554) {
            return null;
        }

        Info info = new Info();
        info.ip = ip;
        info.porta = 80;
        if (porta80) {
            HikvisionHttp.Resposta r = HikvisionHttp.get(
                HikvisionIsapi.url(ip, 80, "/ISAPI/System/deviceInfo"), null, null);

            // 401 é a assinatura do Hikvision ativado; 200 = aberto (não ativado
            // ou sem senha configurada); 404 em /ISAPI não é Hikvision.
            if (r.codigo == 401 || r.codigo == 403) {
                info.ativado = true;
            } else if (r.codigo == 200) {
                // 200 so conta como Hikvision se o corpo e o deviceInfo dele.
                String corpo = r.corpo.toLowerCase(java.util.Locale.US);
                if (!corpo.contains("deviceinfo")) {
                    return null;
                }
                info.ativado = !(corpo.contains("notactivated") || corpo.contains("unactivated"));
            } else if (!porta554) {
                // Porta 80 aberta mas não é Hikvision e nem RTSP: não mostramos
                // roteador/impressora como câmera.
                return null;
            } else {
                info.ativado = true;
            }
        } else {
            // Só RTSP: câmera sem ISAPI exposto; o técnico precisa informar a senha.
            info.ativado = true;
        }

        return info;
    }

    private static boolean portaAberta(String ip, int porta, int timeoutMs) {
        try (java.net.Socket socket = new java.net.Socket()) {
            socket.connect(new java.net.InetSocketAddress(ip, porta), timeoutMs);
            return true;
        } catch (Exception e) {
            return false;
        }
    }

    static String ipDeInt(int valor) {
        return String.format(
            java.util.Locale.US, "%d.%d.%d.%d",
            (valor & 0xff), (valor >> 8) & 0xff, (valor >> 16) & 0xff, (valor >> 24) & 0xff);
    }

    static int compararIp(String a, String b) {
        String[] pa = a.split("\\.");
        String[] pb = b.split("\\.");
        for (int i = 0; i < 4; i++) {
            int va = pa.length > i ? Integer.parseInt(pa[i]) : 0;
            int vb = pb.length > i ? Integer.parseInt(pb[i]) : 0;
            if (va != vb) return Integer.compare(va, vb);
        }
        return 0;
    }

    /** MAC a partir do ARP do sistema (só funciona para vizinhos já resolvidos). */
    static String macDeArp(String ip) {
        Map<String, String> arp = new LinkedHashMap<>();
        try {
            for (String linha : lerArp()) {
                String[] partes = linha.trim().split("\\s+");
                if (partes.length >= 2 && partes[0].matches("\\d+\\.\\d+\\.\\d+\\.\\d+")) {
                    arp.put(partes[0], partes[1].toUpperCase());
                }
            }
        } catch (Exception ignorada) {
            return "";
        }
        return arp.getOrDefault(ip, "");
    }

    private static List<String> lerArp() {
        List<String> linhas = new ArrayList<>();
        try (java.io.BufferedReader reader = new java.io.BufferedReader(
            new java.io.FileReader("/proc/net/arp"))) {
            String linha;
            while ((linha = reader.readLine()) != null) {
                // ip hw_address flags mask device
                String[] partes = linha.trim().split("\\s+");
                if (partes.length >= 4 && !"HW".equalsIgnoreCase(partes[1])) {
                    linhas.add(partes[0] + " " + partes[3]);
                }
            }
        } catch (Exception ignorada) {
            // Sem /proc/net/arp (Android restrito): o MAC vem do ISAPI.
        }
        return linhas;
    }
}
