<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Permission Class
 *
 * Biblioteca para controle de permissões
 *
 * @author      Ramon Silva
 * @copyright   Copyright (c) 2013, Ramon Silva.
 *
 * @since       Version 1.0
 * v... Visualizar
 * e... Editar
 * d... Deletar ou Desabilitar
 * c... Cadastrar
 */
class Permission
{
    private $permissions = [];

    private $table = 'permissoes'; //Nome tabela onde ficam armazenadas as permissões

    private $pk = 'idPermissao'; // Nome da chave primaria da tabela

    private $select = 'permissoes'; // Campo onde fica o array de permissoes.

    public function __construct()
    {
        log_message('debug', 'Permission Class Initialized');
        $this->CI = &get_instance();
        $this->CI->load->database();
    }

    public function checkPermission($idPermissao = null, $atividade = null)
    {
        if ($idPermissao == null || $atividade == null) {
            return false;
        }
        // Se as permissões não estiverem carregadas, requisita o carregamento
        if ($this->permissions == null) {
            // Se não carregar retorna falso
            if (! $this->loadPermission($idPermissao)) {
                return false;
            }
        }

        if (is_array($this->permissions[0])) {
            if (array_key_exists($atividade, $this->permissions[0])) {
                // compara a atividade requisitada com a permissão.
                if ($this->permissions[0][$atividade] == 1) {
                    return true;
                }
            }
        }

        return false;
    }

    private function loadPermission($id = null)
    {
        if ($id != null) {
            $this->CI->db->select($this->table . '.' . $this->select);
            $this->CI->db->where($this->pk, $id);
            $this->CI->db->limit(1);
            $array = $this->CI->db->get($this->table)->row_array();

            if (is_array($array) && count($array) > 0) {
                $raw = isset($array[$this->select]) ? (string) $array[$this->select] : '';

                // Desserializa de forma resiliente: blobs de permissão podem estar
                // corrompidos (ex.: "Extra data" ao final). O unserialize retorna
                // o array válido do início mesmo assim; suprimimos o warning para
                // não derrubar a página (o Whoops o converteria em fatal).
                set_error_handler(static function () {
                    return true;
                });
                $permissoes = unserialize($raw);
                restore_error_handler();

                if (! is_array($permissoes)) {
                    $permissoes = [];
                }

                // Auto-corrige o registro no banco quando o valor salvo estava
                // corrompido mas foi possível recuperar as permissões (não sobrescreve
                // com vazio para não perder dados de um blob ilegível).
                if (! empty($permissoes)) {
                    $limpo = serialize($permissoes);
                    if ($limpo !== $raw) {
                        $this->CI->db->where($this->pk, $id);
                        $this->CI->db->update($this->table, [$this->select => $limpo]);
                    }
                }

                $this->permissions = [$permissoes];

                return true;
            }
        }

        return false;
    }
}
