<?php
/**
 * CORREÇÃO ÚNICA: limpa blobs corrompidos em `permissoes.permissoes`
 * (erro "unserialize(): Extra data ...").
 *
 * Roda FORA do framework (não sofre com OPcache). Ele lê as permissões,
 * recupera o array válido e regrava um serialize() limpo. Não toca em linhas
 * que não conseguir recuperar (seguro / não destrutivo).
 *
 * COMO USAR:
 *   - Navegador:  https://SEU_DOMINIO/mapos3/updates/fix_permissoes.php?run=1
 *   - OU SSH/CLI: php updates/fix_permissoes.php
 *
 * >>> APAGUE ESTE ARQUIVO logo após rodar. <<<
 */

$isCli = (php_sapi_name() === 'cli');
$nl = $isCli ? "\n" : "<br>\n";

if (! $isCli && (isset($_GET['run']) ? $_GET['run'] : '') !== '1') {
    exit('Para executar, adicione ?run=1 na URL. Ex.: fix_permissoes.php?run=1');
}

$envPath = __DIR__ . '/../application/.env';
if (! is_file($envPath)) {
    exit('Nao encontrei application/.env em ' . $envPath . $nl);
}

$env = [];
foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    $line = trim($line);
    if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) {
        continue;
    }
    list($k, $v) = explode('=', $line, 2);
    $env[trim($k)] = trim($v, " \t\"'");
}

$host = isset($env['DB_HOSTNAME']) ? $env['DB_HOSTNAME'] : 'localhost';
$user = isset($env['DB_USERNAME']) ? $env['DB_USERNAME'] : '';
$pass = isset($env['DB_PASSWORD']) ? $env['DB_PASSWORD'] : '';
$db   = isset($env['DB_DATABASE']) ? $env['DB_DATABASE'] : '';
$prefix = isset($env['DB_PREFIX']) ? $env['DB_PREFIX'] : '';
$table = $prefix . 'permissoes';

$mysqli = @new mysqli($host, $user, $pass, $db);
if ($mysqli->connect_errno) {
    exit('Erro de conexao com o banco: ' . $mysqli->connect_error . $nl);
}
$mysqli->set_charset('utf8');

$res = $mysqli->query('SELECT idPermissao, nome, permissoes FROM `' . $table . '`');
if (! $res) {
    exit('Erro na consulta: ' . $mysqli->error . $nl);
}

$corrigidos = 0;
$ok = 0;
$ilegiveis = 0;

while ($row = $res->fetch_assoc()) {
    $id = (int) $row['idPermissao'];
    $nome = $row['nome'];
    $raw = (string) $row['permissoes'];

    set_error_handler(static function () {
        return true;
    });
    $arr = unserialize($raw);
    restore_error_handler();

    if (! is_array($arr) || empty($arr)) {
        $ilegiveis++;
        echo "ID {$id} ({$nome}): ilegivel — PULADO (nada alterado){$nl}";
        continue;
    }

    $clean = serialize($arr);
    if ($clean !== $raw) {
        $stmt = $mysqli->prepare('UPDATE `' . $table . '` SET permissoes = ? WHERE idPermissao = ?');
        $stmt->bind_param('si', $clean, $id);
        $stmt->execute();
        $stmt->close();
        $corrigidos++;
        echo "ID {$id} ({$nome}): CORRIGIDO (" . strlen($raw) . ' -> ' . strlen($clean) . " bytes){$nl}";
    } else {
        $ok++;
    }
}

echo $nl . "Concluido. Corrigidos: {$corrigidos} | Ja OK: {$ok} | Ilegiveis: {$ilegiveis}{$nl}";
echo ">>> APAGUE agora este arquivo: updates/fix_permissoes.php <<<{$nl}";
