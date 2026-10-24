<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Parser de extratos OFX (1.x SGML e 2.x XML). Classe pura, sem CI.
 */
class Ofx_parser
{
    public function parse(string $conteudo): array
    {
        $conteudo = $this->paraUtf8($conteudo);
        $inicio = stripos($conteudo, '<OFX>');
        if ($inicio !== false) {
            $conteudo = substr($conteudo, $inicio);
        }

        $conta = [
            'banco' => $this->tag($conteudo, 'BANKID') ?? $this->tag($conteudo, 'ORG'),
            'agencia' => $this->tag($conteudo, 'BRANCHID'),
            'conta' => $this->tag($conteudo, 'ACCTID'),
            'moeda' => $this->tag($conteudo, 'CURDEF'),
        ];

        $itens = [];
        if (preg_match_all('/<STMTTRN>(.*?)(?:<\/STMTTRN>|(?=<STMTTRN>)|<\/BANKTRANLIST>|$)/is', $conteudo, $blocos)) {
            foreach ($blocos[1] as $b) {
                $data = $this->data($this->tag($b, 'DTPOSTED'));
                $valor = $this->numero($this->tag($b, 'TRNAMT'));
                if ($data === null || $valor === null) {
                    continue;
                }
                $memo = $this->tag($b, 'MEMO') ?? '';
                $descricao = trim($memo !== '' ? $memo : ($this->tag($b, 'NAME') ?? ''));
                $fitid = $this->tag($b, 'FITID');
                if ($fitid === null || $fitid === '') {
                    $fitid = md5($data . '|' . $valor . '|' . $descricao);
                }
                $itens[] = [
                    'fitid' => $fitid,
                    'data' => $data,
                    'valor' => $valor,
                    'descricao' => $descricao,
                    'tipo' => $valor >= 0 ? 'credito' : 'debito',
                ];
            }
        }

        return ['conta' => $conta, 'itens' => $itens];
    }

    private function paraUtf8(string $s): string
    {
        $s = preg_replace('/^\xEF\xBB\xBF/', '', $s);
        if (! mb_check_encoding($s, 'UTF-8')) {
            $s = mb_convert_encoding($s, 'UTF-8', 'Windows-1252');
        }

        return $s;
    }

    private function tag(string $texto, string $nome): ?string
    {
        if (preg_match('/<' . $nome . '>\s*([^<\r\n]*)/i', $texto, $m)) {
            return trim(html_entity_decode($m[1], ENT_QUOTES | ENT_XML1, 'UTF-8'));
        }

        return null;
    }

    private function data(?string $v): ?string
    {
        if ($v !== null && preg_match('/^(\d{4})(\d{2})(\d{2})/', $v, $m) && checkdate((int) $m[2], (int) $m[3], (int) $m[1])) {
            return "$m[1]-$m[2]-$m[3]";
        }

        return null;
    }

    private function numero(?string $v): ?float
    {
        if ($v === null || $v === '') {
            return null;
        }
        $v = str_replace(' ', '', $v);
        if (strpos($v, ',') !== false) {
            $v = str_replace('.', '', $v);
            $v = str_replace(',', '.', $v);
        }

        return is_numeric($v) ? (float) $v : null;
    }
}
