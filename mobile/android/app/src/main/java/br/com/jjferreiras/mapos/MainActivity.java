package br.com.jjferreiras.mapos;

import android.os.Bundle;
import com.getcapacitor.BridgeActivity;

public class MainActivity extends BridgeActivity {

    @Override
    public void onCreate(Bundle savedInstanceState) {
        // Plugins próprios do app (não publicados como pacote npm): o Capacitor
        // só carrega automaticamente os listados em capacitor.plugins.json, então
        // os locais entram aqui antes do bridge criar a WebView.
        registerPlugin(HikvisionCameraPlugin.class);
        registerPlugin(ArquivoPlugin.class);
        super.onCreate(savedInstanceState);
    }
}
