<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Tipos de serviço da segurança eletrônica (lista fixa).
 * chave => [rotulo, icone boxicons]
 */
if (! function_exists('tipos_servico')) {
    function tipos_servico()
    {
        return [
            'cftv' => ['rotulo' => 'CFTV / câmeras', 'icone' => 'bx-cctv'],
            'alarme' => ['rotulo' => 'Alarme e monitoramento', 'icone' => 'bx-bell-ring'],
            'controle_acesso' => ['rotulo' => 'Controle de acesso', 'icone' => 'bx-fingerprint'],
            'interfonia' => ['rotulo' => 'Interfonia e vídeo porteiro', 'icone' => 'bx-phone-call'],
            'cerca_eletrica' => ['rotulo' => 'Cerca elétrica', 'icone' => 'bx-bolt-circle'],
            'portao' => ['rotulo' => 'Portão automático', 'icone' => 'bx-door-open'],
            'rede' => ['rotulo' => 'Rede e Wi-Fi', 'icone' => 'bx-wifi'],
            'rastreamento' => ['rotulo' => 'Rastreamento veicular', 'icone' => 'bx-current-location'],
            'outros' => ['rotulo' => 'Outros serviços', 'icone' => 'bx-wrench'],
        ];
    }
}

if (! function_exists('tipo_servico_valido')) {
    function tipo_servico_valido($k)
    {
        return is_string($k) && $k !== '' && array_key_exists($k, tipos_servico());
    }
}

if (! function_exists('tipo_servico_rotulo')) {
    function tipo_servico_rotulo($k)
    {
        return tipo_servico_valido($k) ? tipos_servico()[$k]['rotulo'] : '';
    }
}

if (! function_exists('tipo_servico_icone')) {
    function tipo_servico_icone($k)
    {
        return tipo_servico_valido($k) ? tipos_servico()[$k]['icone'] : 'bx-wrench';
    }
}

/** Normaliza o valor vindo do POST: chave válida ou null. */
if (! function_exists('tipo_servico_normalizar')) {
    function tipo_servico_normalizar($k)
    {
        $k = is_string($k) ? trim($k) : '';

        return tipo_servico_valido($k) ? $k : null;
    }
}

/** Filtra formulários (objetos/arrays com tipo_servico): gerais (vazio) + os do tipo. */
if (! function_exists('tipo_servico_filtrar')) {
    function tipo_servico_filtrar(array $formularios, $tipo)
    {
        $out = [];
        foreach ($formularios as $f) {
            $t = is_array($f) ? ($f['tipo_servico'] ?? null) : ($f->tipo_servico ?? null);
            if ($t === null || $t === '' || ($tipo !== null && $tipo !== '' && $t === $tipo)) {
                $out[] = $f;
            }
        }

        return $out;
    }
}

/** Selo HTML (ícone + rótulo) do tipo; vazio se inválido. */
if (! function_exists('tipo_servico_selo')) {
    function tipo_servico_selo($k)
    {
        if (! tipo_servico_valido($k)) {
            return '';
        }

        return ' <span class="badge" style="background:#607d8b" title="Tipo de serviço"><i class="bx '
            . tipo_servico_icone($k) . '"></i> ' . htmlspecialchars(tipo_servico_rotulo($k), ENT_QUOTES, 'UTF-8') . '</span>';
    }
}
