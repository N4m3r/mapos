<#
  Gera o icone e a splash do APK a partir da logo da empresa (emitente).

  O icone do Android vai dentro do APK: nao muda sozinho quando a logo e
  trocada no sistema. Por isso o `npm run apk` roda este script antes de
  compilar — trocou a logo, recompile o APK e o icone acompanha.

  Origem da logo, nesta ordem:
    -Logo <arquivo ou URL>        logo informada na mao
    -Servidor <URL do MAPOS>      le /api/v1/marca (publico) desse servidor
    (padrao)                      DEFAULT_SERVER_URL de mobile/src/lib/config.ts

  Sem logo disponivel, mantem os icones atuais e nao interrompe o build.
#>
param(
    [string]$Logo = '',
    [string]$Servidor = '',
    [string]$Res = ''
)

$ErrorActionPreference = 'Stop'
Add-Type -AssemblyName System.Drawing

$raiz = Split-Path -Parent $PSScriptRoot
# PowerShell ignora maiusculas: $Res (parametro) e a pasta de destino.
if (-not $Res) { $Res = Join-Path $raiz 'mobile\android\app\src\main\res' }

function Obter-Servidor {
    if ($Servidor) { return $Servidor.TrimEnd('/') }
    $cfg = Get-Content (Join-Path $raiz 'mobile\src\lib\config.ts') -Raw
    if ($cfg -match "DEFAULT_SERVER_URL\s*=\s*'([^']+)'") { return $Matches[1].TrimEnd('/') }
    return ''
}

function Obter-UrlLogo([string]$base) {
    foreach ($caminho in @('/index.php/api/v1/marca', '/api/v1/marca')) {
        try {
            $r = Invoke-RestMethod -Uri ($base + $caminho) -TimeoutSec 15
            if ($r.status -and $r.result.url_logo) { return [string]$r.result.url_logo }
            if ($r.status) { return '' }
        } catch { }
    }
    return ''
}

if (-not $Logo) {
    $base = Obter-Servidor
    if ($base) { $Logo = Obter-UrlLogo $base }
    if (-not $Logo) {
        Write-Warning "Logo do emitente indisponivel em '$base' (sem logo cadastrada ou servidor sem /api/v1/marca). Icones atuais mantidos."
        exit 0
    }
}

$arquivo = $Logo
if ($Logo -match '^https?://') {
    $arquivo = Join-Path $env:TEMP ('mapos-logo-' + [guid]::NewGuid().ToString('N'))
    try {
        Invoke-WebRequest -Uri $Logo -OutFile $arquivo -UseBasicParsing -TimeoutSec 30
    } catch {
        Write-Warning "Nao foi possivel baixar a logo ($Logo). Icones atuais mantidos."
        exit 0
    }
}
if (-not (Test-Path $arquivo)) { throw "Logo nao encontrada: $arquivo" }

$fonte = [System.Drawing.Image]::FromFile((Resolve-Path $arquivo))

# Desenha a logo centralizada, sem distorcer, ocupando `escala` do lado menor.
function Desenhar([int]$w, [int]$h, [double]$escala, [string]$fundo, [string]$destino) {
    $bmp = New-Object System.Drawing.Bitmap $w, $h
    $g = [System.Drawing.Graphics]::FromImage($bmp)
    $g.SmoothingMode = 'AntiAlias'
    $g.InterpolationMode = 'HighQualityBicubic'
    $g.PixelOffsetMode = 'HighQuality'
    $g.Clear([System.Drawing.Color]::Transparent)
    $branco = New-Object System.Drawing.SolidBrush ([System.Drawing.Color]::White)
    if ($fundo -eq 'circulo') { $g.FillEllipse($branco, 0, 0, $w, $h) }
    elseif ($fundo -eq 'cheio') { $g.FillRectangle($branco, 0, 0, $w, $h) }
    $lado = [Math]::Min($w, $h) * $escala
    $prop = [Math]::Min($lado / $fonte.Width, $lado / $fonte.Height)
    $lw = $fonte.Width * $prop; $lh = $fonte.Height * $prop
    $g.DrawImage($fonte, [single](($w - $lw) / 2), [single](($h - $lh) / 2), [single]$lw, [single]$lh)
    $g.Dispose()
    $bmp.Save($destino, [System.Drawing.Imaging.ImageFormat]::Png)
    $bmp.Dispose()
}

$gerados = 0
foreach ($f in Get-ChildItem $Res -Recurse -Filter *.png) {
    # Le as dimensoes por um stream copiado para nao travar o arquivo que sera sobrescrito.
    $bytes = [System.IO.File]::ReadAllBytes($f.FullName)
    $ms = New-Object System.IO.MemoryStream (, $bytes)
    $img = [System.Drawing.Image]::FromStream($ms)
    $w = $img.Width; $h = $img.Height
    $img.Dispose(); $ms.Dispose()
    $modo = switch ($f.Name) {
        'ic_launcher.png'            { @(0.80, 'cheio') }
        'ic_launcher_round.png'      { @(0.62, 'circulo') }
        # Adaptive icon: so o centro (66/108) e garantido visivel.
        'ic_launcher_foreground.png' { @(0.56, 'nenhum') }
        'splash.png'                 { @(0.40, 'cheio') }
        default                      { $null }
    }
    if ($modo) {
        Desenhar $w $h $modo[0] $modo[1] $f.FullName
        $gerados++
    }
}
$fonte.Dispose()
if ($arquivo -ne $Logo) { Remove-Item $arquivo -ErrorAction SilentlyContinue }

Write-Host "Icone e splash gerados a partir da logo do emitente: $gerados arquivos."
