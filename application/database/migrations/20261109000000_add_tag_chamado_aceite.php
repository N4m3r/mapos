<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Tag {CHAMADO} no modelo de WhatsApp "aceite" (link de assinatura do
 * cliente). O envio já substitui a tag; aqui ela só passa a aparecer na lista
 * de tags do editor do modelo. Idempotente.
 */
class Migration_add_tag_chamado_aceite extends CI_Migration
{
    public function up()
    {
        if (! $this->db->table_exists('whatsapp_templates')) {
            return;
        }
        $tpl = $this->db->where('slug', 'aceite')->get('whatsapp_templates')->row();
        if (! $tpl) {
            return;
        }
        $tags = array_filter(array_map('trim', explode(',', (string) $tpl->tags)));
        if (! in_array('{CHAMADO}', $tags, true)) {
            $tags[] = '{CHAMADO}';
            $this->db->where('slug', 'aceite')->update('whatsapp_templates', ['tags' => implode(',', $tags)]);
        }
    }

    public function down()
    {
        if (! $this->db->table_exists('whatsapp_templates')) {
            return;
        }
        $tpl = $this->db->where('slug', 'aceite')->get('whatsapp_templates')->row();
        if ($tpl) {
            $tags = array_filter(array_map('trim', explode(',', (string) $tpl->tags)), static function ($t) {
                return $t !== '{CHAMADO}';
            });
            $this->db->where('slug', 'aceite')->update('whatsapp_templates', ['tags' => implode(',', $tags)]);
        }
    }
}
