<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Modelos (templates) de mensagens de WhatsApp, editáveis por contexto.
 */
class Whatsapp_templates_model extends CI_Model
{
    protected $table = 'whatsapp_templates';

    public function suportado()
    {
        return $this->db->table_exists($this->table);
    }

    public function getAll()
    {
        if (! $this->suportado()) {
            return [];
        }

        return $this->db->order_by('id', 'ASC')->get($this->table)->result();
    }

    public function getBySlug($slug)
    {
        if (! $this->suportado()) {
            return null;
        }

        return $this->db->where('slug', $slug)->limit(1)->get($this->table)->row();
    }

    public function update($slug, array $data)
    {
        if (! $this->suportado()) {
            return false;
        }

        $this->db->where('slug', $slug)->update($this->table, $data);

        return $this->db->affected_rows() >= 0;
    }

    /**
     * Conteúdo cru do template ativo. Se não existir/estiver vazio/desativado,
     * retorna o $fallback (mantém compatibilidade sem a tabela migrada).
     */
    public function conteudo($slug, $fallback = '')
    {
        $tpl = $this->getBySlug($slug);
        if (! $tpl || (int) $tpl->ativo !== 1 || trim((string) $tpl->conteudo) === '') {
            return $fallback;
        }

        return $tpl->conteudo;
    }

    /**
     * Renderiza um template substituindo as tags (['{LINK}' => 'http...']).
     * Converte \n literais em quebras de linha reais.
     */
    public function renderizar($slug, array $subs, $fallback = '')
    {
        $texto = $this->conteudo($slug, $fallback);
        $texto = str_replace(array_keys($subs), array_values($subs), $texto);

        return str_replace('\n', "\n", $texto);
    }
}
