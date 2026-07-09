<?php

defined('BASEPATH') or exit('No direct script access allowed');
/**
 * CodeIgniter Email Queue
 *
 * A CodeIgniter library to queue e-mails.
 *
 * @category    Libraries
 *
 * @author      Thaynã Bruno Moretti
 *
 * @link    http://www.meau.com.br/
 *
 * @license http://www.opensource.org/licenses/mit-license.html
 *
 * Updated by @RamonSilva for Map-OS
 */
class MY_Email extends CI_Email
{
    // DB table
    private $table_email_queue = 'email_queue';

    // Main controller
    private $main_controller = 'email/process';

    // PHP Nohup command line
    private $phpcli = 'nohup php';

    private $expiration = null;

    // Status (pending, sending, sent, failed)
    private $status;

    /**
     * Constructor
     */
    public function __construct($config = [])
    {
        parent::__construct($config);

        log_message('debug', 'Email Queue Class Initialized');

        $this->expiration = 60 * 5;
        $this->CI = &get_instance();

        $this->CI->load->database('default');
    }

    public function set_status($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get
     *
     * Get queue emails.
     *
     * @return mixed
     */
    public function get($limit = null, $offset = null)
    {
        if ($this->status != false) {
            $this->CI->db->where('status', $this->status);
        }

        $query = $this->CI->db->get("{$this->table_email_queue}", $limit, $offset);

        return $query->result();
    }

    /**
     * Save
     *
     * Add queue email to database.
     *
     * @return mixed
     */
    public function send($skip_job = false)
    {
        if ($skip_job === true) {
            return parent::send();
        }

        $date = date('Y-m-d H:i:s');

        $to = is_array($this->_recipients) ? implode(', ', $this->_recipients) : $this->_recipients;
        $cc = implode(', ', $this->_cc_array);
        $bcc = implode(', ', $this->_bcc_array);

        $dbdata = [
            'to' => $to,
            'cc' => $cc,
            'bcc' => $bcc,
            'message' => $this->_body,
            'headers' => serialize($this->_headers),
            'status' => 'pending',
            'date' => $date,
        ];

        return $this->CI->db->insert($this->table_email_queue, $dbdata);
    }

    /**
     * Start process
     *
     * Start php process to send emails
     *
     * @return mixed
     */
    public function start_process()
    {
        $filename = FCPATH . 'index.php';
        $exec = shell_exec("{$this->phpcli} {$filename} {$this->main_controller} > /dev/null &");

        return $exec;
    }

    /**
     * Send queue
     *
     * Send queue emails.
     *
     * @return void
     */
    public function send_queue()
    {
        $this->set_status('pending');
        $emails = $this->get();

        $this->CI->db->where('status', 'pending');
        $this->CI->db->set('status', 'sending');
        $this->CI->db->set('date', date('Y-m-d H:i:s'));
        $this->CI->db->update($this->table_email_queue);

        foreach ($emails as $email) {
            $recipients = explode(', ', $email->to);

            $cc = ! empty($email->cc) ? explode(', ', $email->cc) : [];
            $bcc = ! empty($email->bcc) ? explode(', ', $email->bcc) : [];

            $this->_headers = unserialize($email->headers);

            $this->to($recipients);
            $this->cc($cc);
            $this->bcc($bcc);

            $this->message($email->message);

            // Anexos: baixa URLs para arquivos temporários ou usa caminhos locais.
            $tempFiles = [];
            if (isset($email->attachments) && ! empty($email->attachments)) {
                $lista = json_decode($email->attachments, true);
                if (is_array($lista)) {
                    foreach ($lista as $item) {
                        $anexo = $this->resolverAnexo($item, $tempFiles);
                        if ($anexo) {
                            $this->attach($anexo['path'], '', $anexo['nome']);
                        }
                    }
                }
            }

            if ($this->send(true)) {
                $status = 'sent';
            } else {
                log_message('error', $this->print_debugger());
                $status = 'failed';
            }

            // Remove os arquivos temporários baixados para os anexos.
            foreach ($tempFiles as $tf) {
                @unlink($tf);
            }

            $this->CI->db->where('id', $email->id);

            $this->CI->db->set('status', $status);
            $this->CI->db->set('date', date('Y-m-d H:i:s'));
            $this->CI->db->update($this->table_email_queue);
        }
    }

    /**
     * Retry failed emails
     *
     * Resend failed or expired emails
     *
     * @return void
     */
    public function retry_queue()
    {
        $expire = (time() - $this->expiration);
        $date_expire = date('Y-m-d H:i:s', $expire);

        $this->CI->db->set('status', 'pending');
        $this->CI->db->where("(date < '{$date_expire}' AND status = 'sending')");
        $this->CI->db->or_where("status = 'failed'");

        $this->CI->db->update($this->table_email_queue);

        log_message('debug', 'Email queue retrying...');
    }

    /**
     * Resolve um item de anexo (string ou array) num arquivo local.
     *
     * Aceita:
     *   - 'https://...pdf'                          (URL pública, baixada)
     *   - '/caminho/local/arquivo.pdf'              (arquivo local)
     *   - ['url' => '...', 'nome' => 'boleto.pdf']  (URL com nome amigável)
     *   - ['path' => '...', 'nome' => 'nota.pdf']   (caminho com nome)
     *
     * Devolve ['path' => ..., 'nome' => ...] ou null. Arquivos temporários
     * baixados são acrescentados em $tempFiles para remoção posterior.
     */
    private function resolverAnexo($item, &$tempFiles)
    {
        $origem = '';
        $nome = null;
        if (is_array($item)) {
            $origem = $item['url'] ?? ($item['path'] ?? '');
            $nome = $item['nome'] ?? null;
        } else {
            $origem = (string) $item;
        }
        $origem = trim($origem);
        if ($origem === '') {
            return null;
        }

        // URL pública: baixa para um arquivo temporário.
        if (preg_match('#^https?://#i', $origem)) {
            $conteudo = $this->baixarUrl($origem);
            if ($conteudo === null || $conteudo === '') {
                log_message('error', 'Falha ao baixar anexo de e-mail: ' . $origem);

                return null;
            }
            $ext = pathinfo(parse_url($origem, PHP_URL_PATH), PATHINFO_EXTENSION);
            $ext = $ext ?: 'pdf';
            $dest = tempnam(sys_get_temp_dir(), 'mapatt_');
            if ($dest === false) {
                return null;
            }
            if (file_put_contents($dest, $conteudo) === false) {
                @unlink($dest);

                return null;
            }
            $tempFiles[] = $dest;

            return ['path' => $dest, 'nome' => $nome ?: ('anexo.' . $ext)];
        }

        // Caminho local existente.
        if (is_file($origem)) {
            if (strpos($origem, sys_get_temp_dir()) === 0) {
                $tempFiles[] = $origem;
            }

            return ['path' => $origem, 'nome' => $nome ?: basename($origem)];
        }

        return null;
    }

    /**
     * Baixa o conteúdo de uma URL. Retorna o corpo ou null em caso de falha.
     */
    private function baixarUrl($url)
    {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_SSL_VERIFYPEER => true,
            ]);
            $data = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            return ($data !== false && $code >= 200 && $code < 300) ? $data : null;
        }

        $ctx = stream_context_create(['http' => ['timeout' => 30]]);
        $data = @file_get_contents($url, false, $ctx);

        return $data === false ? null : $data;
    }
}
