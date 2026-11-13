<?php

use PHPUnit\Framework\TestCase;

if (! defined('BASEPATH')) {
    define('BASEPATH', __DIR__);
}

if (! function_exists('base_url')) {
    function base_url($uri = '')
    {
        return 'http://localhost/' . ltrim((string) $uri, '/');
    }
}

if (! function_exists('site_url')) {
    function site_url($uri = '')
    {
        return 'http://localhost/index.php/' . ltrim((string) $uri, '/');
    }
}

if (! function_exists('csp_nonce')) {
    function csp_nonce()
    {
        return 'nonce-teste';
    }
}

if (! function_exists('asset_versao')) {
    function asset_versao($caminho, $padrao = '1')
    {
        return $padrao;
    }
}

if (! function_exists('html_escape')) {
    function html_escape($var)
    {
        return htmlspecialchars((string) $var, ENT_QUOTES, 'UTF-8');
    }
}

if (! function_exists('notif_lista')) {
    require_once APPPATH . 'helpers/notificacoes_helper.php';
}

require_once APPPATH . 'helpers/env_helper.php';

if (! class_exists('CI_Model')) {
    class CI_Model
    {
    }
}

require_once APPPATH . 'models/Notification_triggers_model.php';

/**
 * Renderiza as views da Central de Notificacoes com um CodeIgniter minimo.
 *
 * O PHPUnit deste projeto trata warning/notice como falha (failOnWarning), entao
 * uma variavel ou indice indefinido nas views quebra o teste antes do navegador.
 */
class NotificacoesViewsTest extends TestCase
{
    public $security;

    public $load;

    protected function setUp(): void
    {
        $this->security = new class {
            public function get_csrf_token_name()
            {
                return 'MAPOS_TOKEN';
            }

            public function get_csrf_hash()
            {
                return 'hash-teste';
            }
        };

        $this->load = new class {
            public $vars = [];

            public $security;

            public $load;

            public function __construct()
            {
                $this->load = $this;
            }

            public function view($nome, $vars = null, $retorno = false)
            {
                $arquivo = APPPATH . 'views/' . $nome . '.php';
                if (! is_file($arquivo)) {
                    throw new RuntimeException('View ausente: ' . $nome);
                }

                $ctx = $this->vars;
                if (is_array($vars)) {
                    $ctx = array_merge($ctx, $vars);
                }
                extract($ctx);

                ob_start();
                try {
                    include $arquivo;
                } catch (Throwable $e) {
                    ob_end_clean();
                    throw $e;
                }
                $html = ob_get_clean();

                // Como o Loader do CodeIgniter: sem $retorno, a view e ecoada.
                if ($retorno) {
                    return $html;
                }
                echo $html;

                return $this;
            }
        };
        $this->load->security = $this->security;
    }

    private function gatilho(array $extra = [])
    {
        return (object) array_merge([
            'id' => 1,
            'evento' => 'os_aberta',
            'nome' => 'OS aberta',
            'descricao' => 'Ao criar uma OS.',
            'grupo' => 'Ordem de Serviço',
            'ativo' => 1,
            'canais' => 'email,whatsapp',
            'destinatarios' => 'cliente',
            'blocos' => 'dados,valores',
            'anexos' => 'boleto',
            'template_slug' => 'os',
            'whatsapp_template' => 'os',
            'whatsapp_grupos' => null,
            'whatsapp_clientes' => '3',
            'projetos_id' => null,
            'email_destinatarios' => 'resp@empresa.com',
            'email_conversa' => 0,
        ], $extra);
    }

    private function contextoBase(): array
    {
        return [
            'porEvento' => [
                'os_aberta' => [
                    'evento' => ['nome' => 'OS aberta', 'grupo' => 'Ordem de Serviço'],
                    'gatilhos' => [$this->gatilho()],
                ],
            ],
            'eventosSemGatilho' => [
                'rdo_registrado' => ['nome' => 'Registro de execução', 'grupo' => 'Projetos'],
            ],
            'ordemGrupos' => ['Ordem de Serviço', 'Atendimento em campo', 'Projetos', 'Cobrança', 'Fiscal', 'Cliente'],
            'tabelaGatilhos' => true,
            'intervalo' => 120,
            'smtp' => [
                'EMAIL_PROTOCOL' => 'smtp',
                'EMAIL_SMTP_HOST' => 'smtp.exemplo.com',
                'EMAIL_SMTP_CRYPTO' => 'tls',
                'EMAIL_SMTP_PORT' => '587',
                'EMAIL_SMTP_USER' => 'conta@exemplo.com',
                'EMAIL_SMTP_PASS' => '',
            ],
            'whatsapp' => [
                'WHATSAPP_EVOLUTION_ENABLED' => 'true',
                'WHATSAPP_EVOLUTION_URL' => 'https://evo.exemplo.com',
                'WHATSAPP_EVOLUTION_APIKEY' => 'chave',
                'WHATSAPP_EVOLUTION_INSTANCE' => 'instancia',
                'WHATSAPP_EVOLUTION_VERIFY_SSL' => 'true',
                'WHATSAPP_EVOLUTION_AUTO_STATUS' => 'Finalizado',
            ],
            'whatsappAtivo' => true,
            'osNotification' => 'cliente',
            'emailAutomatico' => '1',
            'notificaWhats' => 'Olá {CLIENTE_NOME}',
            'podeSistema' => true,
            'envDisponivel' => true,
            'podeEmail' => true,
            'modelosEmail' => [(object) ['id' => 1, 'slug' => 'os', 'nome' => 'OS', 'descricao' => 'OS', 'ativo' => 1]],
            'modelosWhatsapp' => [(object) ['id' => 1, 'slug' => 'os', 'nome' => 'OS', 'descricao' => 'OS', 'conteudo' => 'Olá {CLIENTE_NOME}', 'ativo' => 1]],
            'whatsappSlugsCore' => ['os', 'cobranca', 'aprovacao', 'aceite', 'rdo'],
            'canalFiltro' => null,
            'statusFiltro' => null,
            'historico' => [
                'itens' => [
                    ['canal' => 'email', 'id' => 1, 'data_envio' => '2026-11-06 10:00:00', 'destino' => 'a@b.com', 'assunto' => 'OS', 'tipo' => 'fila', 'evento' => '', 'os_id' => 0, 'status' => 'enviado', 'erro' => '', 'fila_id' => 0],
                    ['canal' => 'whatsapp', 'id' => 2, 'data_envio' => '2026-11-06 09:00:00', 'destino' => '5592999998888', 'assunto' => null, 'tipo' => 'cliente', 'evento' => 'os_aberta', 'os_id' => 7, 'status' => 'falha', 'erro' => 'timeout', 'fila_id' => 0],
                ],
                'total' => 2,
                'pagina' => 1,
                'paginas' => 1,
            ],
            'totais' => ['enviados' => 1, 'falhas' => 1, 'pendentes' => 0, 'total' => 2],
            'filaPendente' => 0,
        ];
    }

    public function testCentralRenderizaCadaAba(): void
    {
        foreach (['eventos', 'canais', 'modelos', 'historico'] as $aba) {
            $this->load->vars = array_merge($this->contextoBase(), ['abaAtiva' => $aba]);

            $html = $this->load->view('notificacoes/central', null, true);

            $this->assertStringContainsString('Central de notificações', $html, 'aba ' . $aba);
            $this->assertStringContainsString('nt-abas', $html, 'aba ' . $aba);
            $this->assertStringContainsString('aria-selected="true"', $html, 'aba ' . $aba);
            $this->assertStringContainsString('assets/js/notificacoes.js', $html, 'aba ' . $aba);
        }
    }

    public function testEventoDeAceiteMostraReenvioSemCron(): void
    {
        require_once APPPATH . 'libraries/Aceite_reenvio.php';
        $gatilho = $this->gatilho(['evento' => 'atividade_np_finalizada', 'reenvio_intervalo_horas' => 24, 'reenvio_max_tentativas' => 5]);

        $this->load->vars = array_merge($this->contextoBase(), [
            'eventoSlug' => 'atividade_np_finalizada',
            'eventoInfo' => ['nome' => 'Atividade não programada finalizada', 'grupo' => 'Aceite do cliente'],
            'gatilho' => $gatilho,
            'gatilhosEvento' => [$gatilho],
            'canais' => Notification_triggers_model::canaisDisponiveis(),
            'destinatarios' => Notification_triggers_model::destinatariosDisponiveis(),
            'blocos' => [],
            'anexos' => [],
            'whatsappTemplates' => [],
            'emailTemplates' => [],
            'clientesSelecionados' => [],
            'projetosSelecionados' => [],
            'ehOs' => false,
            'ehProjeto' => false,
            'temReenvio' => true,
            'intervaloDisparo' => 120,
            'reenvioResumo' => [
                'totais' => ['ativo' => 1, 'aceito' => 4],
                'ativos' => [(object) ['os_id' => 812, 'nomeCliente' => 'TG Foods', 'tentativas' => 2, 'max_tentativas' => 5, 'proximo_envio' => '2026-11-13 09:00:00']],
            ],
        ]);

        $html = $this->load->view('notificacoes/evento', null, true);

        $this->assertStringContainsString('name="reenvio_intervalo_horas" value="24"', $html);
        $this->assertStringContainsString('name="reenvio_max_tentativas" value="5"', $html);
        $this->assertStringContainsString('Não precisa de cron', $html);
        $this->assertStringNotContainsString('lembretes/aceite', $html);
        $this->assertStringContainsString('notificacoes/processarReenvios', $html);
        $this->assertStringContainsString('#812', $html);
        $this->assertStringContainsString('2 de 5', $html);
        $this->assertStringContainsString('13/11/2026 09:00', $html);
    }

    public function testEventoDeOsRenderiza(): void
    {
        $this->load->vars = array_merge($this->contextoBase(), [
            'eventoSlug' => 'os_aberta',
            'eventoInfo' => ['nome' => 'OS aberta', 'grupo' => 'Ordem de Serviço'],
            'gatilho' => $this->gatilho(),
            'gatilhosEvento' => [$this->gatilho()],
            'canais' => Notification_triggers_model::canaisDisponiveis(),
            'destinatarios' => Notification_triggers_model::destinatariosDisponiveis(),
            'blocos' => Notification_triggers_model::blocosDisponiveis(),
            'anexos' => Notification_triggers_model::anexosDisponiveis(),
            'whatsappTemplates' => [(object) ['slug' => 'os', 'nome' => 'OS']],
            'emailTemplates' => [(object) ['slug' => 'os', 'nome' => 'OS']],
            'clientesSelecionados' => [(object) ['idClientes' => 3, 'nomeCliente' => 'Maria', 'documento' => '123']],
            'projetosSelecionados' => [],
            'ehOs' => true,
            'ehProjeto' => false,
        ]);

        $html = $this->load->view('notificacoes/evento', null, true);

        $this->assertStringContainsString('name="evento" value="os_aberta"', $html);
        $this->assertStringContainsString('name="canais[]"', $html);
        $this->assertStringContainsString('Maria', $html);
        $this->assertStringContainsString('O que a notificação de OS deve conter', $html);
    }

    public function testEventoDeProjetoMostraThreadEFiltroDeProjetos(): void
    {
        $gatilho = $this->gatilho([
            'evento' => 'rdo_registrado',
            'grupo' => 'Projetos',
            'blocos' => null,
            'projetos_id' => null,
            'email_conversa' => 1,
        ]);

        $this->load->vars = array_merge($this->contextoBase(), [
            'eventoSlug' => 'rdo_registrado',
            'eventoInfo' => ['nome' => 'Registro de execução', 'grupo' => 'Projetos'],
            'gatilho' => $gatilho,
            'gatilhosEvento' => [$gatilho],
            'canais' => Notification_triggers_model::canaisDisponiveis(),
            'destinatarios' => Notification_triggers_model::destinatariosDisponiveis(),
            'blocos' => Notification_triggers_model::blocosDisponiveis(),
            'anexos' => Notification_triggers_model::anexosDisponiveis(),
            'whatsappTemplates' => [],
            'emailTemplates' => [],
            'clientesSelecionados' => [],
            'projetosSelecionados' => [(object) ['idObra' => 9, 'nome' => 'Obra X', 'codigo' => 'OB-1']],
            'ehOs' => false,
            'ehProjeto' => true,
        ]);

        $html = $this->load->view('notificacoes/evento', null, true);

        $this->assertStringContainsString('E-mails fixos de acompanhamento', $html);
        $this->assertStringContainsString('única conversa', $html);
        $this->assertStringContainsString('Obra X', $html);
        $this->assertStringNotContainsString('O que a notificação de OS deve conter', $html);
    }

    public function testSemTabelaDeGatilhosAvisa(): void
    {
        $this->load->vars = array_merge($this->contextoBase(), [
            'abaAtiva' => 'eventos',
            'tabelaGatilhos' => false,
        ]);

        $html = $this->load->view('notificacoes/central', null, true);

        $this->assertStringContainsString('não existe neste banco', $html);
    }

    /**
     * A tela da fila de e-mails exige cEmail, mas a central abre com cSistema
     * OU cEmail. Sem o portao, quem entrava so com cSistema via um link que
     * terminava em redirect.
     */
    public function testHistoricoEscondeFilaDeEmailsSemPermissao(): void
    {
        $this->load->vars = array_merge($this->contextoBase(), [
            'abaAtiva' => 'historico',
            'podeEmail' => false,
        ]);

        $html = $this->load->view('notificacoes/central', null, true);

        $this->assertStringNotContainsString('mapos/emails', $html);
        $this->assertStringContainsString('E-mails ainda na fila aparecem no card acima.', $html);
    }

    public function testHistoricoMostraFilaDeEmailsComPermissao(): void
    {
        $this->load->vars = array_merge($this->contextoBase(), [
            'abaAtiva' => 'historico',
            'podeEmail' => true,
        ]);

        $html = $this->load->view('notificacoes/central', null, true);

        $this->assertStringContainsString('mapos/emails', $html);
        $this->assertStringContainsString('fila de e-mails', $html);
    }

    /**
     * A central abre com cSistema OU cEmail, mas salvar evento exige cSistema
     * (exigirSistema()). Quem so tem cEmail precisa ver o evento e nao receber
     * um botao que termina em redirect.
     */
    public function testEventoSemPermissaoDeSistemaFicaSomenteLeitura(): void
    {
        $this->load->vars = array_merge($this->contextoBase(), [
            'eventoSlug' => 'os_aberta',
            'eventoInfo' => ['nome' => 'OS aberta', 'grupo' => 'Ordem de Serviço'],
            'gatilho' => $this->gatilho(),
            'gatilhosEvento' => [$this->gatilho()],
            'canais' => Notification_triggers_model::canaisDisponiveis(),
            'destinatarios' => Notification_triggers_model::destinatariosDisponiveis(),
            'blocos' => Notification_triggers_model::blocosDisponiveis(),
            'anexos' => Notification_triggers_model::anexosDisponiveis(),
            'whatsappTemplates' => [],
            'emailTemplates' => [],
            'clientesSelecionados' => [],
            'projetosSelecionados' => [],
            'ehOs' => true,
            'ehProjeto' => false,
            'podeSistema' => false,
        ]);

        $html = $this->load->view('notificacoes/evento', null, true);

        $this->assertStringContainsString('Seu perfil pode consultar este evento', $html);
        $this->assertStringContainsString('disabled', $html, 'o botao de salvar aparece desabilitado');
        $this->assertStringNotContainsString('Outras ações deste evento', $html);
    }
}
