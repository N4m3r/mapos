package br.com.jjferreiras.mapos;

import android.content.ActivityNotFoundException;
import android.content.Intent;
import android.net.Uri;
import android.util.Base64;
import androidx.core.content.FileProvider;
import com.getcapacitor.Plugin;
import com.getcapacitor.PluginCall;
import com.getcapacitor.PluginMethod;
import com.getcapacitor.annotation.CapacitorPlugin;
import java.io.File;
import java.io.FileOutputStream;

/**
 * Abre um arquivo gerado pelo app (PDF da OS, do projeto, holerite) no
 * aplicativo do sistema. O WebView do Capacitor nao trata download de blob:
 * sem isto o botao de PDF nao fazia nada no APK. O visualizador de PDF do
 * Android ja oferece imprimir, salvar e compartilhar.
 */
@CapacitorPlugin(name = "Arquivo")
public class ArquivoPlugin extends Plugin {

    @PluginMethod
    public void abrir(PluginCall call) {
        String base64 = call.getString("base64");
        String nome = call.getString("nome", "arquivo.pdf");
        String mime = call.getString("mime", "application/pdf");
        boolean compartilhar = Boolean.TRUE.equals(call.getBoolean("compartilhar", false));
        if (base64 == null || base64.isEmpty()) {
            call.reject("Arquivo vazio.");
            return;
        }

        try {
            // So o nome do arquivo: nada de caminho vindo do JavaScript.
            String seguro = nome.replaceAll("[^A-Za-z0-9._-]", "_");
            File pasta = new File(getContext().getCacheDir(), "documentos");
            if (!pasta.exists() && !pasta.mkdirs()) {
                call.reject("Não foi possível preparar a pasta do arquivo.");
                return;
            }
            File arquivo = new File(pasta, seguro);
            try (FileOutputStream out = new FileOutputStream(arquivo)) {
                out.write(Base64.decode(base64, Base64.DEFAULT));
            }

            Uri uri = FileProvider.getUriForFile(getContext(), getContext().getPackageName() + ".fileprovider", arquivo);
            Intent intent;
            if (compartilhar) {
                intent = new Intent(Intent.ACTION_SEND);
                intent.setType(mime);
                intent.putExtra(Intent.EXTRA_STREAM, uri);
                intent = Intent.createChooser(intent, "Compartilhar");
            } else {
                intent = new Intent(Intent.ACTION_VIEW);
                intent.setDataAndType(uri, mime);
            }
            intent.addFlags(Intent.FLAG_GRANT_READ_URI_PERMISSION | Intent.FLAG_ACTIVITY_NEW_TASK);
            getActivity().startActivity(intent);
            call.resolve();
        } catch (ActivityNotFoundException e) {
            call.reject("Nenhum aplicativo neste aparelho abre este tipo de arquivo.", "SEM_APP", e);
        } catch (Exception e) {
            call.reject("Não foi possível abrir o arquivo: " + e.getMessage(), "ERRO", e);
        }
    }
}
