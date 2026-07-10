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

    /** Slugs dos modelos fixos do sistema (não podem ser excluídos). */
    public static function slugsCore()
    {
        return ['os', 'cobranca', 'aprovacao', 'aceite'];
    }

    /** Tags disponíveis por padrão para um modelo novo (contexto de OS). */
    public static function tagsPadraoOs()
    {
        return '{CLIENTE_NOME},{NUMERO_OS},{STATUS_OS},{VALOR_OS},{DESCRI_PRODUTOS},{EMITENTE},{TELEFONE_EMITENTE},{OBS_OS},{DEFEITO_OS},{LAUDO_OS},{DATA_FINAL},{DATA_INICIAL},{DATA_GARANTIA}';
    }

    public function slugExiste($slug)
    {
        if (! $this->suportado()) {
            return false;
        }

        return $this->db->where('slug', $slug)->count_all_results($this->table) > 0;
    }

    /**
     * Gera um slug único e seguro a partir de um nome.
     */
    public function gerarSlug($nome)
    {
        $base = strtolower(trim((string) $nome));
        $base = preg_replace('/[àáâãä]/u', 'a', $base);
        $base = preg_replace('/[éêë]/u', 'e', $base);
        $base = preg_replace('/[íï]/u', 'i', $base);
        $base = preg_replace('/[óôõö]/u', 'o', $base);
        $base = preg_replace('/[úü]/u', 'u', $base);
        $base = preg_replace('/[ç]/u', 'c', $base);
        $base = preg_replace('/[^a-z0-9]+/', '_', $base);
        $base = trim($base, '_');
        if ($base === '') {
            $base = 'modelo';
        }
        // Evita colidir com os slugs fixos e garante unicidade.
        if (in_array($base, self::slugsCore(), true)) {
            $base .= '_custom';
        }
        $slug = $base;
        $i = 2;
        while ($this->slugExiste($slug)) {
            $slug = $base . '_' . $i;
            $i++;
        }

        return $slug;
    }

    public function create(array $data)
    {
        if (! $this->suportado()) {
            return false;
        }

        $this->db->insert($this->table, $data);

        return $this->db->insert_id();
    }

    public function delete($slug)
    {
        if (! $this->suportado() || in_array($slug, self::slugsCore(), true)) {
            return false;
        }

        $this->db->where('slug', $slug)->delete($this->table);

        return $this->db->affected_rows() > 0;
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
