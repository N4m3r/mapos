<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

// Rotas API V1
$route['api/v1'] = 'api/v1/ApiController/index';
$route['api/v1/audit'] = 'api/v1/ApiController/audit';
$route['api/v1/emitente'] = 'api/v1/ApiController/emitente';
$route['api/v1/marca'] = 'api/v1/ApiController/marca';
$route['api/v1/os/(:num)/pdf'] = 'api/v1/RelatorioPdfController/os/$1';
$route['api/v1/projetos/(:num)/pdf'] = 'api/v1/RelatorioPdfController/projeto/$1';
$route['api/v1/calendario'] = 'api/v1/ApiController/calendario';
$route['api/v1/login'] = 'api/v1/UsuariosController/login';
$route['api/v1/reGenToken'] = 'api/v1/UsuariosController/reGenToken';
$route['api/v1/conta'] = 'api/v1/UsuariosController/conta';
$route['api/v1/clientes'] = 'api/v1/ClientesController/index';
$route['api/v1/clientes/(:num)'] = 'api/v1/ClientesController/index/$1';
$route['api/v1/produtos'] = 'api/v1/ProdutosController/index';
$route['api/v1/produtos/(:num)'] = 'api/v1/ProdutosController/index/$1';
$route['api/v1/servicos'] = 'api/v1/ServicosController/index';
$route['api/v1/servicos/(:num)'] = 'api/v1/ServicosController/index/$1';
$route['api/v1/usuarios'] = 'api/v1/UsuariosController/index';
$route['api/v1/usuarios/(:num)'] = 'api/v1/UsuariosController/index/$1';
$route['api/v1/os'] = 'api/v1/OsController/index';
$route['api/v1/os/(:num)'] = 'api/v1/OsController/index/$1';
$route['api/v1/os/(:num)/produtos'] = 'api/v1/OsController/produtos/$1';
$route['api/v1/os/(:num)/produtos/(:num)'] = 'api/v1/OsController/produtos/$1/$2';
$route['api/v1/os/(:num)/servicos'] = 'api/v1/OsController/servicos/$1';
$route['api/v1/os/(:num)/servicos/(:num)'] = 'api/v1/OsController/servicos/$1/$2';
$route['api/v1/os/(:num)/anotacoes'] = 'api/v1/OsController/anotacoes/$1';
$route['api/v1/os/(:num)/anotacoes/(:num)'] = 'api/v1/OsController/anotacoes/$1/$2';
$route['api/v1/os/(:num)/anexos'] = 'api/v1/OsController/anexos/$1';
$route['api/v1/os/(:num)/anexos/(:num)'] = 'api/v1/OsController/anexos/$1/$2';
$route['api/v1/os/(:num)/desconto'] = 'api/v1/OsController/desconto/$1';

/*
Routes for the Tecnico area
Rotas da área do técnico (app mobile). A ordem importa: rotas mais
específicas precisam vir antes das que aceitam (:num).
*/

$route['api/v1/tecnico'] = 'api/v1/TecnicoController/index';
$route['api/v1/tecnico/os'] = 'api/v1/TecnicoController/os';
$route['api/v1/tecnico/os/(:num)'] = 'api/v1/TecnicoController/os_detalhe/$1';
$route['api/v1/tecnico/nao-realizadas'] = 'api/v1/TecnicoController/nao_realizadas';
$route['api/v1/tecnico/nao-realizadas/reagendar'] = 'api/v1/TecnicoOcorrenciaController/reagendar';
$route['api/v1/tecnico/nao-realizadas/reabrir'] = 'api/v1/TecnicoOcorrenciaController/reabrir';
$route['api/v1/tecnico/nao-realizado'] = 'api/v1/TecnicoOcorrenciaController/nao_realizado';
$route['api/v1/tecnico/motivos'] = 'api/v1/TecnicoController/motivos';
// Cadastros da atividade não programada (sem preço)
$route['api/v1/tecnico/clientes'] = 'api/v1/TecnicoController/clientes';
$route['api/v1/tecnico/servicos'] = 'api/v1/TecnicoController/servicos';
$route['api/v1/tecnico/produtos'] = 'api/v1/TecnicoController/produtos';
$route['api/v1/tecnico/atividade'] = 'api/v1/TecnicoOcorrenciaController/atividade';
$route['api/v1/tecnico/projetos'] = 'api/v1/TecnicoController/projetos';
$route['api/v1/tecnico/projetos/foto/(:num)'] = 'api/v1/TecnicoProjetoController/foto/$1';
$route['api/v1/tecnico/projetos/(:num)/rdo'] = 'api/v1/TecnicoProjetoController/rdo/$1';
$route['api/v1/tecnico/projetos/(:num)'] = 'api/v1/TecnicoController/projeto/$1';
// Configuração de câmeras e NVR Hikvision (rede local, pelo app do técnico)
$route['api/v1/tecnico/projetos/(:num)/camera-config'] = 'api/v1/TecnicoCameraController/plano/$1';
$route['api/v1/tecnico/projetos/(:num)/camera-config/pontos/(:num)'] = 'api/v1/TecnicoCameraController/ponto/$1/$2';
$route['api/v1/tecnico/projetos/(:num)/camera-config/nova'] = 'api/v1/TecnicoCameraController/criar/$1';
$route['api/v1/tecnico/projetos/(:num)/camera-config/lote'] = 'api/v1/TecnicoCameraController/lote/$1';
$route['api/v1/tecnico/pontos/(:num)/status'] = 'api/v1/TecnicoProjetoController/ponto_status/$1';
$route['api/v1/tecnico/pontos/(:num)/assumir'] = 'api/v1/TecnicoProjetoController/ponto_assumir/$1';
$route['api/v1/tecnico/pontos/(:num)/repassar'] = 'api/v1/TecnicoProjetoController/ponto_repassar/$1';
$route['api/v1/tecnico/pontos/(:num)'] = 'api/v1/TecnicoProjetoController/ponto/$1';
$route['api/v1/tecnico/fila'] = 'api/v1/TecnicoController/fila';
$route['api/v1/tecnico/relatorio'] = 'api/v1/TecnicoController/relatorio';
$route['api/v1/tecnico/perfil'] = 'api/v1/TecnicoController/perfil';
$route['api/v1/tecnico/checkin'] = 'api/v1/TecnicoAtendimentoController/checkin';
$route['api/v1/tecnico/checkout'] = 'api/v1/TecnicoAtendimentoController/checkout';
$route['api/v1/tecnico/fotos'] = 'api/v1/TecnicoAtendimentoController/foto';
$route['api/v1/tecnico/foto/(:num)'] = 'api/v1/TecnicoAtendimentoController/foto/$1';
$route['api/v1/tecnico/perfil/foto'] = 'api/v1/TecnicoController/foto';
$route['api/v1/tecnico/observacoes'] = 'api/v1/TecnicoAtendimentoController/observacoes';

/*
Routes for the Colaborador (RH self-service) area
Rotas da área do colaborador e do ponto.
*/

$route['api/v1/colaborador'] = 'api/v1/ColaboradorController/index';
$route['api/v1/colaborador/espelho'] = 'api/v1/ColaboradorController/espelho';
$route['api/v1/colaborador/batidas'] = 'api/v1/ColaboradorController/batidas';
$route['api/v1/colaborador/holerite'] = 'api/v1/ColaboradorController/holerite';
$route['api/v1/colaborador/holerite/arquivo'] = 'api/v1/ColaboradorController/holerite_arquivo';
$route['api/v1/colaborador/dados'] = 'api/v1/ColaboradorController/dados';
$route['api/v1/colaborador/foto'] = 'api/v1/ColaboradorController/foto';
$route['api/v1/colaborador/ocorrencias'] = 'api/v1/ColaboradorSolicitacaoController/ocorrencias';
$route['api/v1/colaborador/ausencias'] = 'api/v1/ColaboradorSolicitacaoController/ausencias';

$route['api/v1/ponto'] = 'api/v1/PontoController/index';
$route['api/v1/ponto/registrar'] = 'api/v1/PontoController/registrar';

/*
Routes for clients API
Rotas Para API area do cliente.
*/

$route['api/v1/client'] = 'api/v1/client/ClientOsController/index';
$route['api/v1/client/auth'] = 'api/v1/client/ClientLoginController/login';

$route['api/v1/client/os'] = 'api/v1/client/ClientOsController/os';
$route['api/v1/client/os/(:num)'] = 'api/v1/client/ClientOsController/os/$1';

$route['api/v1/client/compras'] = 'api/v1/client/ClientComprasController/index';
$route['api/v1/client/compras/(:num)'] = 'api/v1/client/ClientComprasController/index/$1';

$route['api/v1/client/cobrancas'] = 'api/v1/client/ClientCobrancasController/index';

