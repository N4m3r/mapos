package br.com.jjferreiras.mapos;

import com.getcapacitor.JSArray;
import com.getcapacitor.JSObject;
import com.getcapacitor.Plugin;
import com.getcapacitor.PluginCall;
import com.getcapacitor.PluginMethod;
import com.getcapacitor.annotation.CapacitorPlugin;
import java.util.List;
import java.util.concurrent.ExecutorService;
import java.util.concurrent.Executors;

/**
 * Plugin do Capacitor que expõe a rede local e o ISAPI dos equipamentos
 * Hikvision ao app.
 *
 * Tudo aqui roda fora da thread da interface: varredura de 254 endereços e
 * leitura de canais levam segundos e travariam a WebView.
 */
@CapacitorPlugin(name = "HikvisionCamera")
public class HikvisionCameraPlugin extends Plugin {

    /** Uma fila só: o técnico configura uma câmera por vez e as chamadas não competem. */
    private final ExecutorService fila = Executors.newSingleThreadExecutor();

    private void async(Runnable tarefa) {
        fila.execute(tarefa);
    }

    @PluginMethod
    public void disponivel(PluginCall call) {
        JSObject saida = new JSObject();
        saida.put("disponivel", true);
        call.resolve(saida);
    }

    @PluginMethod
    public void redeAtual(PluginCall call) {
        async(() -> {
            try {
                call.resolve(NetworkInfoUtil.redeAtual(getContext()));
            } catch (Exception e) {
                call.reject(e.getMessage(), "REDE", e);
            }
        });
    }

    /**
     * descubrir({ duracaoMs })
     * Varre o /24 do aparelho e devolve os equipamentos que respondem.
     */
    @PluginMethod
    public void descobrir(PluginCall call) {
        Integer duracao = call.getInt("duracaoMs", 12000);
        async(() -> {
            try {
                String prefixo = HikvisionDiscovery.prefixoRede(getContext());
                if (prefixo == null) {
                    call.reject("O aparelho não está conectado a nenhuma rede local.", "SEM_REDE");
                    return;
                }

                // SADP primeiro: além de achar a câmera de fábrica (IP 192.168.1.64),
                // é a única fonte de MAC, série e status de ativação sem senha —
                // sem isso não dá para casar o equipamento com o ponto previsto.
                List<HikvisionDiscovery.Info> achados = SadpDiscovery.descobrir(getContext(), duracao != null ? duracao : 12000);

                // Reserva: SADP pode estar bloqueado na rede do cliente (VLAN,
                // isolamento de porta). A varredura acha o que responde HTTP.
                if (achados.isEmpty()) {
                    int timeout = duracao != null && duracao > 0 ? Math.max(250, duracao / 40) : 300;
                    achados = HikvisionDiscovery.varrer(getContext(), prefixo, timeout, null);
                }

                JSArray dispositivos = new JSArray();
                for (HikvisionDiscovery.Info d : achados) {
                    JSObject item = new JSObject();
                    item.put("ip", d.ip);
                    item.put("mac", d.mac);
                    item.put("serie", d.serie);
                    item.put("sadpId", d.sadpId);
                    item.put("modelo", d.modelo);
                    item.put("fabricante", d.fabricante);
                    item.put("firmware", d.firmware);
                    item.put("ativado", d.ativado);
                    item.put("porta", d.porta);
                    item.put("mascara", d.mascara);
                    item.put("gateway", d.gateway);
                    item.put("dhcp", d.dhcp);
                    dispositivos.put(item);
                }

                JSObject saida = new JSObject();
                saida.put("dispositivos", dispositivos);
                saida.put("prefixo", prefixo);
                call.resolve(saida);
            } catch (Exception e) {
                call.reject(e.getMessage(), "DESCOBERTA", e);
            }
        });
    }

    /**
     * lerNvr({ ip, usuario, senha, porta })
     * Identificação do NVR e todos os canais, marcando os já ocupados.
     */
    @PluginMethod
    public void lerNvr(PluginCall call) {
        String ip = call.getString("ip");
        String usuario = call.getString("usuario", "admin");
        String senha = call.getString("senha");
        Integer porta = call.getInt("porta", 80);

        if (ip == null || ip.isEmpty() || senha == null) {
            call.reject("Informe o IP e a senha do NVR.", "ENTRADA");
            return;
        }

        async(() -> {
            try {
                int p = porta != null ? porta : 80;
                HikvisionIsapi.Info info = HikvisionIsapi.info(ip, p, usuario, senha);
                if (info == null) {
                    call.reject("Não foi possível falar com o NVR em " + ip + ". Confira o cabo e o IP.", "SEM_CONTATO");
                    return;
                }

                List<HikvisionIsapi.Canal> canais = HikvisionIsapi.canais(ip, p, usuario, senha);

                JSArray lista = new JSArray();
                for (HikvisionIsapi.Canal c : canais) {
                    JSObject item = new JSObject();
                    item.put("id", c.id);
                    item.put("nome", c.nome);
                    item.put("ip", c.ip);
                    item.put("online", c.online);
                    item.put("habilitado", c.habilitado);
                    item.put("ocupado", c.ip != null && !c.ip.isEmpty());
                    item.put("protocolo", c.protocolo);
                    item.put("porta", 8000);
                    item.put("modelo", c.modelo);
                    lista.put(item);
                }

                JSObject saida = new JSObject();
                saida.put("ip", ip);
                saida.put("nome", info.nome);
                saida.put("modelo", info.modelo);
                saida.put("serie", info.serie);
                saida.put("firmware", info.firmware);
                saida.put("quantosCanais", canais.size());
                saida.put("canais", lista);
                call.resolve(saida);
            } catch (Exception e) {
                call.reject(e.getMessage(), "NVR", e);
            }
        });
    }

    /**
     * adicionarCanal({ nvr, canal, cameraIp, cameraUsuario, cameraSenha, protocolo })
     * Liga a câmera IP ao canal informado.
     */
    @PluginMethod
    public void adicionarCanal(PluginCall call) {
        JSObject nvr = call.getObject("nvr");
        Integer canal = call.getInt("canal");
        String cameraIp = call.getString("cameraIp");
        String cameraUsuario = call.getString("cameraUsuario", "admin");
        String cameraSenha = call.getString("cameraSenha");
        String protocolo = call.getString("protocolo", "HIKVISION");

        if (nvr == null || canal == null || cameraIp == null || cameraSenha == null) {
            call.reject("Informe o NVR, o canal e os dados da câmera.", "ENTRADA");
            return;
        }

        String nvrIp = nvr.getString("ip");
        String nvrUsuario = nvr.getString("usuario", "admin");
        String nvrSenha = nvr.getString("senha");
        Integer nvrPorta = nvr.getInteger("porta", 80);

        if (nvrIp == null || nvrSenha == null) {
            call.reject("Informe o IP e a senha do NVR.", "ENTRADA");
            return;
        }

        async(() -> {
            try {
                HikvisionIsapi.Resultado r = HikvisionIsapi.adicionarCanal(
                    nvrIp,
                    nvrPorta != null ? nvrPorta : 80,
                    nvrUsuario,
                    nvrSenha,
                    canal,
                    cameraIp,
                    cameraUsuario,
                    cameraSenha,
                    protocolo
                );
                if (!r.ok) {
                    call.reject(r.mensagem, "CANAL");
                    return;
                }

                JSObject saida = new JSObject();
                saida.put("ok", true);
                saida.put("canal", canal);
                saida.put("mensagem", r.mensagem);
                call.resolve(saida);
            } catch (Exception e) {
                call.reject(e.getMessage(), "CANAL", e);
            }
        });
    }

    /** lerCamera({ ip, usuario, senha, porta }) — modelo, série, firmware e ativação. */
    @PluginMethod
    public void lerCamera(PluginCall call) {
        String ip = call.getString("ip");
        String usuario = call.getString("usuario", "admin");
        String senha = call.getString("senha");
        Integer porta = call.getInt("porta", 80);

        if (ip == null || ip.isEmpty()) {
            call.reject("Informe o IP da câmera.", "ENTRADA");
            return;
        }

        async(() -> {
            try {
                int p = porta != null ? porta : 80;
                HikvisionIsapi.Info info = HikvisionIsapi.info(ip, p, usuario, senha);
                if (info == null) {
                    call.reject("Não foi possível falar com a câmera em " + ip + ".", "SEM_CONTATO");
                    return;
                }

                JSObject saida = new JSObject();
                saida.put("ip", ip);
                saida.put("mac", info.mac);
                saida.put("serie", info.serie);
                saida.put("modelo", info.modelo);
                saida.put("fabricante", "Hikvision");
                saida.put("firmware", info.firmware);
                saida.put("ativada", info.ativada);
                call.resolve(saida);
            } catch (Exception e) {
                call.reject(e.getMessage(), "CAMERA", e);
            }
        });
    }

    /** definirRedeCamera({ ip, usuario, senha, novoIp, mascara, gateway, dhcp }) */
    @PluginMethod
    public void definirRedeCamera(PluginCall call) {
        String ip = call.getString("ip");
        String usuario = call.getString("usuario", "admin");
        String senha = call.getString("senha");
        Integer porta = call.getInt("porta", 80);
        String novoIp = call.getString("novoIp");
        String mascara = call.getString("mascara", "255.255.255.0");
        String gateway = call.getString("gateway");
        Boolean dhcp = call.getBoolean("dhcp", false);

        if (ip == null || senha == null || novoIp == null) {
            call.reject("Informe a câmera (IP e senha) e o novo IP.", "ENTRADA");
            return;
        }

        async(() -> {
            try {
                HikvisionIsapi.Resultado r = HikvisionIsapi.definirRedeCamera(
                    ip, porta != null ? porta : 80, usuario, senha, novoIp, mascara, gateway,
                    dhcp != null && dhcp);
                if (!r.ok) {
                    call.reject(r.mensagem, "REDE_CAMERA");
                    return;
                }
                JSObject saida = new JSObject();
                saida.put("ok", true);
                saida.put("mensagem", r.mensagem);
                call.resolve(saida);
            } catch (Exception e) {
                call.reject(e.getMessage(), "REDE_CAMERA", e);
            }
        });
    }

    /** ativarCamera({ ip, senha, porta }) — câmera nova de fábrica. */
    @PluginMethod
    public void ativarCamera(PluginCall call) {
        String ip = call.getString("ip");
        String senha = call.getString("senha");
        Integer porta = call.getInt("porta", 80);

        if (ip == null || senha == null) {
            call.reject("Informe o IP e a senha inicial da câmera.", "ENTRADA");
            return;
        }

        async(() -> {
            try {
                HikvisionIsapi.Resultado r = HikvisionIsapi.ativarCamera(
                    ip, porta != null ? porta : 80, senha);
                if (!r.ok) {
                    call.reject(r.mensagem, "ATIVACAO");
                    return;
                }
                JSObject saida = new JSObject();
                saida.put("ok", true);
                saida.put("mensagem", r.mensagem);
                call.resolve(saida);
            } catch (Exception e) {
                call.reject(e.getMessage(), "ATIVACAO", e);
            }
        });
    }

    @Override
    protected void handleOnDestroy() {
        fila.shutdownNow();
        super.handleOnDestroy();
    }
}
