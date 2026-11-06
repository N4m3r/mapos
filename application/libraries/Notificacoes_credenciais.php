<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Credenciais e ajustes de envio da Central de Notificacoes.
 *
 * Concentra a leitura e a gravacao do .env (SMTP e Evolution API), o intervalo
 * do disparo automatico e os avisos legados de OS. O controller fica so com a
 * tela, as permissoes e a mensagem de retorno.
 *
 * Regras importantes:
 * - senha e API key em branco mantem o valor atual (nao apagam credencial);
 * - nunca cria um .env novo, para nao derrubar a instalacao com arquivo parcial.
 */
class Notificacoes_credenciais
{
    /** @var CI_Controller */
    private $ci;

    public function __construct()
    {
        $this->ci = &get_instance();
        $this->ci->load->helper(['notificacoes', 'env']);
    }

    /** O .env existe e pode ser gravado? */
    public function envDisponivel()
    {
        return is_file(env_arquivo());
    }

    /** @return array<string, string> chaves de SMTP com os valores atuais */
    public function smtp()
    {
        return $this->valores(notif_env_chaves_smtp());
    }

    /** @return array<string, string> chaves do WhatsApp com os valores atuais */
    public function whatsapp()
    {
        return $this->valores(notif_env_chaves_whatsapp());
    }

    public function whatsappLigado()
    {
        return notif_env_booleano(env_valor_linha('WHATSAPP_EVOLUTION_ENABLED', 'false'));
    }

    public function intervalo()
    {
        $valor = $this->ci->data['configuration']['notif_intervalo_disparo'] ?? 120;

        return max(30, (int) $valor);
    }

    /**
     * @return array{ok: bool, mensagem: string}
     */
    public function salvarEmail(array $post)
    {
        $valores = $this->doPost(notif_env_chaves_smtp(), $post);

        $valores['EMAIL_PROTOCOL'] = $valores['EMAIL_PROTOCOL'] !== '' ? $valores['EMAIL_PROTOCOL'] : 'smtp';
        $valores['EMAIL_SMTP_CRYPTO'] = in_array($valores['EMAIL_SMTP_CRYPTO'], ['tls', 'ssl', ''], true)
            ? $valores['EMAIL_SMTP_CRYPTO'] : 'tls';

        $atual = env_valor_linha('EMAIL_SMTP_PASS', '');
        if ($valores['EMAIL_SMTP_PASS'] === '' && $atual !== '') {
            $valores['EMAIL_SMTP_PASS'] = $atual;
        }

        return $this->gravar($valores, 'Configurações de e-mail salvas.');
    }

    /**
     * @return array{ok: bool, mensagem: string}
     */
    public function salvarWhatsapp(array $post)
    {
        $valores = $this->doPost(notif_env_chaves_whatsapp(), $post);

        $valores['WHATSAPP_EVOLUTION_ENABLED'] = notif_env_booleano($valores['WHATSAPP_EVOLUTION_ENABLED']) ? 'true' : 'false';
        $valores['WHATSAPP_EVOLUTION_VERIFY_SSL'] = notif_env_booleano($valores['WHATSAPP_EVOLUTION_VERIFY_SSL']) ? 'true' : 'false';
        $valores['WHATSAPP_EVOLUTION_URL'] = rtrim($valores['WHATSAPP_EVOLUTION_URL'], '/');

        $atual = env_valor_linha('WHATSAPP_EVOLUTION_APIKEY', '');
        if ($valores['WHATSAPP_EVOLUTION_APIKEY'] === '' && $atual !== '') {
            $valores['WHATSAPP_EVOLUTION_APIKEY'] = $atual;
        }

        return $this->gravar($valores, 'Configurações de WhatsApp salvas.');
    }

    /**
     * @return array{ok: bool, mensagem: string}
     */
    public function salvarIntervalo($segundos)
    {
        $this->ci->load->model('mapos_model');

        $segundos = max(30, (int) $segundos);
        $ok = $this->ci->mapos_model->saveConfiguracao(['notif_intervalo_disparo' => $segundos]);

        return [
            'ok' => (bool) $ok,
            'mensagem' => $ok
                ? 'Intervalo de disparo salvo (' . $segundos . 's).'
                : 'Não foi possível salvar o intervalo.',
        ];
    }

    /**
     * Avisos de OS que viviam na aba Notificacoes de Configuracoes.
     *
     * @return array{ok: bool, mensagem: string}
     */
    public function salvarAvisosOs(array $post)
    {
        $this->ci->load->model('mapos_model');

        $destino = (string) ($post['os_notification'] ?? '');
        if (! in_array($destino, ['todos', 'cliente', 'tecnico', 'emitente', 'nenhum'], true)) {
            $destino = 'cliente';
        }

        $ok = $this->ci->mapos_model->saveConfiguracao([
            'os_notification' => $destino,
            'email_automatico' => ! empty($post['email_automatico']) ? 1 : 0,
            'notifica_whats' => (string) ($post['notifica_whats'] ?? ''),
        ]);

        return [
            'ok' => (bool) $ok,
            'mensagem' => $ok
                ? 'Avisos automáticos de OS salvos.'
                : 'Não foi possível salvar os avisos automáticos.',
        ];
    }

    /**
     * @param array<string, string> $chaves padrao => valor
     *
     * @return array<string, string>
     */
    private function valores(array $chaves)
    {
        $valores = [];
        foreach ($chaves as $chave => $padrao) {
            $valores[$chave] = (string) env_valor_linha($chave, $padrao);
        }

        return $valores;
    }

    /**
     * @param array<string, string> $chaves padrao => valor
     *
     * @return array<string, string>
     */
    private function doPost(array $chaves, array $post)
    {
        $valores = [];
        foreach ($chaves as $chave => $padrao) {
            $valores[$chave] = trim((string) ($post[$chave] ?? ''));
        }

        return $valores;
    }

    /**
     * @return array{ok: bool, mensagem: string}
     */
    private function gravar(array $valores, $mensagemOk)
    {
        if (! $this->envDisponivel()) {
            return [
                'ok' => false,
                'mensagem' => 'O arquivo application/.env não existe nesta instalação, então as credenciais não podem ser gravadas por aqui. Conclua a instalação (ou crie o .env) e tente de novo.',
            ];
        }

        if (! env_salvar($valores)) {
            return [
                'ok' => false,
                'mensagem' => 'Não foi possível gravar o .env. Verifique as permissões do arquivo application/.env.',
            ];
        }

        return [
            'ok' => true,
            'mensagem' => $mensagemOk . ' Salve as credenciais antes de testar.',
        ];
    }
}
