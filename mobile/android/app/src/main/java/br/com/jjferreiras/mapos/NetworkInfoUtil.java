package br.com.jjferreiras.mapos;

import android.content.Context;
import android.net.ConnectivityManager;
import android.net.LinkAddress;
import android.net.LinkProperties;
import android.net.Network;
import android.net.NetworkCapabilities;
import com.getcapacitor.JSObject;
import java.net.Inet4Address;
import java.net.InetAddress;
import java.net.NetworkInterface;
import java.util.Collections;

/**
 * Estado da rede local do aparelho.
 *
 * O técnico conecta um adaptador USB/Ethernet no celular para chegar à rede das
 * câmeras. O Android apresenta esse adaptador como transporte ETHERNET, então
 * esta classe extrai IP, máscara e gateway tanto do adaptador quanto do Wi-Fi.
 */
final class NetworkInfoUtil {

    private NetworkInfoUtil() {}

    static JSObject redeAtual(Context contexto) {
        JSObject saida = new JSObject();
        String tipo = "offline";
        String ip = null;
        String mascara = null;
        String gateway = null;
        String nomeInterface = null;
        boolean conectado = false;

        ConnectivityManager cm = (ConnectivityManager) contexto.getSystemService(Context.CONNECTIVITY_SERVICE);
        Network rede = cm == null ? null : cm.getActiveNetwork();
        NetworkCapabilities caps = rede == null ? null : cm.getNetworkCapabilities(rede);

        if (rede != null && caps != null) {
            conectado = true;
            if (caps.hasTransport(NetworkCapabilities.TRANSPORT_ETHERNET)) {
                tipo = "ethernet";
            } else if (caps.hasTransport(NetworkCapabilities.TRANSPORT_WIFI)) {
                tipo = "wifi";
            } else if (caps.hasTransport(NetworkCapabilities.TRANSPORT_CELLULAR)) {
                tipo = "celular";
            } else {
                tipo = "rede";
            }

            LinkProperties lp = cm.getLinkProperties(rede);
            if (lp != null) {
                nomeInterface = lp.getInterfaceName();
                gateway = lp.getDhcpServerAddress() != null ? lp.getDhcpServerAddress().getHostAddress() : null;
                for (LinkAddress endereco : lp.getLinkAddresses()) {
                    InetAddress inet = endereco.getAddress();
                    if (inet instanceof Inet4Address) {
                        ip = inet.getHostAddress();
                        mascara = prefixoParaMascara(endereco.getPrefixLength());
                        break;
                    }
                }
                if (gateway == null && lp.getRoutes() != null) {
                    for (android.net.RouteInfo rota : lp.getRoutes()) {
                        if (rota.isDefaultRoute() && rota.getGateway() instanceof Inet4Address) {
                            gateway = rota.getGateway().getHostAddress();
                            break;
                        }
                    }
                }
            }
        }

        // Alguns adaptadores USB não aparecem como "rede ativa" enquanto não
        // recebem DHCP. Nesse caso, procuramos uma interface com IPv4.
        if (!conectado) {
            try {
                for (NetworkInterface nif : Collections.list(NetworkInterface.getNetworkInterfaces())) {
                    if (!nif.isUp() || nif.isLoopback()) continue;
                    for (InetAddress endereco : Collections.list(nif.getInetAddresses())) {
                        if (endereco instanceof Inet4Address && !endereco.isLinkLocalAddress()) {
                            conectado = true;
                            tipo = "ethernet";
                            ip = endereco.getHostAddress();
                            nomeInterface = nif.getName();
                            break;
                        }
                    }
                    if (conectado) break;
                }
            } catch (Exception ignorada) {
                // sem rede: a tela mostra "Sem rede local"
            }
        }

        saida.put("conectado", conectado);
        saida.put("tipo", tipo);
        saida.put("ip", ip);
        saida.put("mascara", mascara);
        saida.put("gateway", gateway);
        saida.put("interface", nomeInterface);

        return saida;
    }

    /** Prefixo (/24) no formato de máscara (255.255.255.0). */
    static String prefixoParaMascara(int prefixo) {
        if (prefixo < 0 || prefixo > 32) return null;
        long valor = prefixo == 0 ? 0 : (0xffffffffL << (32 - prefixo)) & 0xffffffffL;

        return String.format(
            java.util.Locale.US,
            "%d.%d.%d.%d",
            (valor >> 24) & 0xff,
            (valor >> 16) & 0xff,
            (valor >> 8) & 0xff,
            valor & 0xff
        );
    }
}
