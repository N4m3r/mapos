package br.com.jjferreiras.mapos;

import java.io.ByteArrayOutputStream;
import java.io.IOException;
import java.io.InputStream;
import java.io.OutputStream;
import java.net.HttpURLConnection;
import java.net.URL;
import java.nio.charset.StandardCharsets;
import java.security.MessageDigest;
import java.security.NoSuchAlgorithmException;
import java.util.LinkedHashMap;
import java.util.Locale;
import java.util.Map;
import java.util.Random;

/**
 * Cliente HTTP mínimo para o ISAPI dos equipamentos Hikvision.
 *
 * O Capacitor Android não traz cliente HTTP próprio e uma biblioteca externa só
 * por causa de duas requisições XML inflaria o APK. Aqui está o necessário:
 * conexão com timeout curto (no campo a rede cai), autenticação Digest
 * (MD5/MD5-sess, qop=auth) e leitura do corpo como texto.
 *
 * Os equipamentos não usam TLS: o tráfego é HTTP na rede local. Por isso o
 * AndroidManifest libera cleartext — e somente para isso.
 */
final class HikvisionHttp {

    static final class Resposta {
        final int codigo;
        final String corpo;

        Resposta(int codigo, String corpo) {
            this.codigo = codigo;
            this.corpo = corpo == null ? "" : corpo;
        }
    }

    private static final int TIMEOUT_CONEXAO = 4000;
    private static final int TIMEOUT_LEITURA = 8000;
    private static final int MAX_CORPO = 512 * 1024;

    private HikvisionHttp() {}

    static Resposta get(String url, String usuario, String senha) {
        return requisitar("GET", url, null, usuario, senha);
    }

    static Resposta put(String url, String xml, String usuario, String senha) {
        return requisitar("PUT", url, xml, usuario, senha);
    }

    /**
     * Executa a requisição e, se o equipamento pedir Digest, repete com a
     * resposta calculada. Hikvision sempre responde 401 com
     * `WWW-Authenticate: Digest realm=..., nonce=..., qop="auth"`.
     */
    static Resposta requisitar(String metodo, String url, String corpo, String usuario, String senha) {
        try {
            Resposta primeira = executar(metodo, url, corpo, null);
            if (primeira.codigo != 401) {
                return primeira;
            }

            // A segunda tentativa precisa do desafio; executar() guarda o header.
            String desafio = ultimoDesafio;
            if (desafio == null || !desafio.toLowerCase(Locale.US).startsWith("digest")) {
                return primeira;
            }

            Map<String, String> campos = camposDesafio(desafio);
            String autorizacao = montarAutorizacao(metodo, urlPath(url), campos, usuario, senha, corpo);
            if (autorizacao == null) {
                return primeira;
            }
            Map<String, String> extra = new LinkedHashMap<>();
            extra.put("Authorization", autorizacao);

            return executar(metodo, url, corpo, extra);
        } catch (IOException e) {
            return new Resposta(0, "");
        }
    }

    /** Header WWW-Authenticate da última resposta 401 (a classe é usada em uma thread só). */
    private static String ultimoDesafio;

    private static Resposta executar(String metodo, String url, String corpo, Map<String, String> headers)
        throws IOException {
        HttpURLConnection conn = null;
        try {
            conn = (HttpURLConnection) new URL(url).openConnection();
            conn.setRequestMethod(metodo);
            conn.setConnectTimeout(TIMEOUT_CONEXAO);
            conn.setReadTimeout(TIMEOUT_LEITURA);
            conn.setInstanceFollowRedirects(false);
            conn.setRequestProperty("Accept", "application/xml, text/xml, */*");

            if (headers != null) {
                for (Map.Entry<String, String> h : headers.entrySet()) {
                    conn.setRequestProperty(h.getKey(), h.getValue());
                }
            }

            byte[] bytes = corpo == null ? null : corpo.getBytes(StandardCharsets.UTF_8);
            if (bytes != null) {
                conn.setDoOutput(true);
                conn.setRequestProperty("Content-Type", "application/xml; charset=utf-8");
                conn.setFixedLengthStreamingMode(bytes.length);
                try (OutputStream out = conn.getOutputStream()) {
                    out.write(bytes);
                }
            }

            int codigo = conn.getResponseCode();
            ultimoDesafio = conn.getHeaderField("WWW-Authenticate");

            InputStream in = codigo >= 400 ? conn.getErrorStream() : conn.getInputStream();
            String texto = in == null ? "" : ler(in);
            return new Resposta(codigo, texto);
        } finally {
            if (conn != null) {
                conn.disconnect();
            }
        }
    }

    private static String ler(InputStream in) throws IOException {
        try (InputStream entrada = in; ByteArrayOutputStream saida = new ByteArrayOutputStream()) {
            byte[] buffer = new byte[8192];
            int total = 0;
            int lido;
            while ((lido = entrada.read(buffer)) != -1) {
                total += lido;
                if (total > MAX_CORPO) {
                    break;
                }
                saida.write(buffer, 0, lido);
            }
            return saida.toString(StandardCharsets.UTF_8.name());
        }
    }

    /** Caminho + query, do jeito que o Digest assina (path absoluto é o que a Hikvision usa). */
    static String urlPath(String url) {
        try {
            URL u = new URL(url);
            String caminho = u.getPath() == null || u.getPath().isEmpty() ? "/" : u.getPath();
            return u.getQuery() == null ? caminho : caminho + "?" + u.getQuery();
        } catch (IOException e) {
            return "/";
        }
    }

    private static Map<String, String> camposDesafio(String header) {
        Map<String, String> campos = new LinkedHashMap<>();
        String semEsquema = header.substring(header.indexOf(' ') + 1);
        // realm="IP Camera", qop="auth", nonce="...", opaque="..."
        int i = 0;
        while (i < semEsquema.length()) {
            int igual = semEsquema.indexOf('=', i);
            if (igual < 0) break;
            String chave = semEsquema.substring(i, igual).trim().toLowerCase(Locale.US).replace(",", "").trim();
            int inicio = igual + 1;
            String valor;
            if (inicio < semEsquema.length() && semEsquema.charAt(inicio) == '"') {
                int fim = semEsquema.indexOf('"', inicio + 1);
                if (fim < 0) break;
                valor = semEsquema.substring(inicio + 1, fim);
                i = fim + 1;
            } else {
                int virgula = semEsquema.indexOf(',', inicio);
                int fim = virgula < 0 ? semEsquema.length() : virgula;
                valor = semEsquema.substring(inicio, fim).trim();
                i = fim;
            }
            if (!chave.isEmpty()) {
                campos.put(chave, valor);
            }
            int proxima = semEsquema.indexOf(',', i);
            if (proxima < 0) break;
            i = proxima + 1;
            while (i < semEsquema.length() && Character.isWhitespace(semEsquema.charAt(i))) i++;
        }
        return campos;
    }

    private static String montarAutorizacao(
        String metodo,
        String uri,
        Map<String, String> campos,
        String usuario,
        String senha,
        String corpo
    ) {
        if (usuario == null || senha == null) {
            return null;
        }
        String realm = campos.get("realm");
        String nonce = campos.get("nonce");
        if (realm == null || nonce == null) {
            return null;
        }
        String algoritmo = campos.get("algorithm") == null ? "MD5" : campos.get("algorithm");
        String qop = campos.get("qop");
        String opaque = campos.get("opaque");

        String ha1 = md5(usuario + ":" + realm + ":" + senha);
        if ("MD5-sess".equalsIgnoreCase(algoritmo)) {
            String cnonceSess = cnonce();
            ha1 = md5(ha1 + ":" + nonce + ":" + cnonceSess);
        }
        String ha2 = md5(metodo + ":" + uri);

        StringBuilder sb = new StringBuilder("Digest ");
        sb.append("username=\"").append(usuario).append("\", ");
        sb.append("realm=\"").append(realm).append("\", ");
        sb.append("nonce=\"").append(nonce).append("\", ");
        sb.append("uri=\"").append(uri).append("\", ");
        sb.append("algorithm=").append(algoritmo).append(", ");

        if (qop != null && qop.toLowerCase(Locale.US).contains("auth")) {
            String nc = "00000001";
            String cn = cnonce();
            String resposta = md5(ha1 + ":" + nonce + ":" + nc + ":" + cn + ":auth:" + ha2);
            sb.append("qop=auth, ");
            sb.append("nc=").append(nc).append(", ");
            sb.append("cnonce=\"").append(cn).append("\", ");
            sb.append("response=\"").append(resposta).append("\"");
        } else {
            sb.append("response=\"").append(md5(ha1 + ":" + nonce + ":" + ha2)).append("\"");
        }
        if (opaque != null && !opaque.isEmpty()) {
            sb.append(", opaque=\"").append(opaque).append("\"");
        }

        return sb.toString();
    }

    private static String cnonce() {
        Random random = new Random();
        return String.format(Locale.US, "%08x", random.nextInt());
    }

    static String md5(String texto) {
        try {
            MessageDigest digest = MessageDigest.getInstance("MD5");
            byte[] bytes = digest.digest(texto.getBytes(StandardCharsets.UTF_8));
            StringBuilder sb = new StringBuilder(bytes.length * 2);
            for (byte b : bytes) {
                sb.append(String.format(Locale.US, "%02x", b & 0xff));
            }
            return sb.toString();
        } catch (NoSuchAlgorithmException e) {
            return "";
        }
    }
}
