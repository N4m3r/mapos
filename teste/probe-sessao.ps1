$env:Path = "C:\Program Files\nodejs;" + $env:Path
$env:U = "thailer.alfaia@gmail.com"
$env:P = "admin123"
Set-Location C:\Users\admin\Documents\api\mapos\teste
node probe-sessao.mjs https://jj-ferreiras.com.br/mapos
