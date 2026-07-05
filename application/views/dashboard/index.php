<link href="<?= base_url('assets/css/custom.css'); ?>" rel="stylesheet">

<style>
.dashboard-container {
    padding: 20px;
}

.kpi-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.kpi-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 10px;
    padding: 20px;
    color: white;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    transition: transform 0.2s;
}

.kpi-card:hover {
    transform: translateY(-5px);
}

.kpi-card.success {
    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
}

.kpi-card.warning {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
}

.kpi-card.info {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
}

.kpi-card.dark {
    background: linear-gradient(135deg, #434343 0%, #000000 100%);
}

.kpi-value {
    font-size: 32px;
    font-weight: bold;
    margin: 10px 0;
}

.kpi-label {
    font-size: 14px;
    opacity: 0.9;
}

.kpi-icon {
    font-size: 24px;
    margin-bottom: 10px;
}

.charts-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.chart-container {
    background: white;
    border-radius: 10px;
    padding: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
}

.chart-title {
    font-size: 16px;
    font-weight: bold;
    margin-bottom: 15px;
    color: #333;
    display: flex;
    align-items: center;
    gap: 10px;
}

.chart-title i {
    color: #667eea;
    font-size: 20px;
}

.chart-canvas {
    max-height: 300px;
}

.filters-bar {
    background: white;
    padding: 15px;
    border-radius: 10px;
    margin-bottom: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
    align-items: center;
}

.filters-bar select,
.filters-bar input {
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 5px;
}

.btn-filter {
    background: #667eea;
    color: white;
    border: none;
    padding: 8px 20px;
    border-radius: 5px;
    cursor: pointer;
}

.btn-filter:hover {
    background: #5568d3;
}

.relatorios-links {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 15px;
    margin-top: 30px;
}

.relatorio-card {
    background: white;
    border-radius: 10px;
    padding: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    text-decoration: none;
    color: #333;
    transition: all 0.2s;
    border-left: 4px solid #667eea;
}

.relatorio-card:hover {
    transform: translateX(5px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.relatorio-card i {
    font-size: 32px;
    color: #667eea;
    margin-bottom: 10px;
}

.relatorio-card h4 {
    margin: 0 0 5px 0;
    font-size: 16px;
}

.relatorio-card p {
    margin: 0;
    font-size: 13px;
    color: #666;
}

.loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(255,255,255,0.8);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 9999;
}

.loading-spinner {
    width: 50px;
    height: 50px;
    border: 4px solid #f3f3f3;
    border-top: 4px solid #667eea;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Responsivo */
@media (max-width: 768px) {
    .charts-row {
        grid-template-columns: 1fr;
    }

    .kpi-cards {
        grid-template-columns: repeat(2, 1fr);
    }

    .kpi-value {
        font-size: 24px;
    }
}
</style>

<div class="widget-box">
    <div class="widget-title">
        <span class="icon"><i class="bx bx-dashboard"></i></span>
        <h5>Dashboard - Visão Geral</h5>
    </div>

    <div class="dashboard-container">
        <!-- Filtros -->
        <div class="filters-bar">
            <label><i class="bx bx-calendar"></i> Período:</label>
            <select id="filtro-periodo" onchange="carregarDados()">
                <option value="hoje">Hoje</option>
                <option value="semana">Esta Semana</option>
                <option value="mes" selected>Este Mês</option>
                <option value="ano">Este Ano</option>
            </select>

            <input type="date" id="data-inicio" style="display:none;">
            <input type="date" id="data-fim" style="display:none;">

            <button class="btn-filter" onclick="carregarDados()">
                <i class="bx bx-refresh"></i> Atualizar
            </button>

            <button class="btn btn-small" onclick="exportarDados()">
                <i class="bx bx-download"></i> Exportar CSV
            </button>
        </div>

        <!-- KPIs -->
        <div class="kpi-cards">
            <div class="kpi-card">
                <div class="kpi-icon"><i class="bx bx-file"></i></div>
                <div class="kpi-label">Total de OS</div>
                <div class="kpi-value" id="kpi-total-os">-</div>
            </div>

            <div class="kpi-card warning">
                <div class="kpi-icon"><i class="bx bx-time"></i></div>
                <div class="kpi-label">OS Pendentes</div>
                <div class="kpi-value" id="kpi-os-pendentes">-</div>
            </div>

            <div class="kpi-card success">
                <div class="kpi-icon"><i class="bx bx-check-circle"></i></div>
                <div class="kpi-label">OS Finalizadas</div>
                <div class="kpi-value" id="kpi-os-finalizadas">-</div>
            </div>

            <div class="kpi-card info">
                <div class="kpi-icon"><i class="bx bx-dollar-circle"></i></div>
                <div class="kpi-label">Valor Faturado</div>
                <div class="kpi-value" id="kpi-valor-faturado">-</div>
            </div>

            <div class="kpi-card dark">
                <div class="kpi-icon"><i class="bx bx-receipt"></i></div>
                <div class="kpi-label">Ticket Médio</div>
                <div class="kpi-value" id="kpi-ticket-medio">-</div>
            </div>

            <div class="kpi-card">
                <div class="kpi-icon"><i class="bx bx-user-plus"></i></div>
                <div class="kpi-label">Novos Clientes</div>
                <div class="kpi-value" id="kpi-novos-clientes">-</div>
            </div>
        </div>

        <!-- Gráficos -->
        <div class="charts-row">
            <div class="chart-container">
                <div class="chart-title">
                    <i class="bx bx-pie-chart-alt-2"></i>
                    OS por Status
                </div>
                <canvas id="chart-os-status" class="chart-canvas"></canvas>
            </div>

            <div class="chart-container">
                <div class="chart-title">
                    <i class="bx bx-line-chart"></i>
                    OS por Período
                </div>
                <canvas id="chart-os-mes" class="chart-canvas"></canvas>
            </div>
        </div>

        <div class="charts-row">
            <div class="chart-container">
                <div class="chart-title">
                    <i class="bx bx-bar-chart-alt-2"></i>
                    Faturamento Mensal
                </div>
                <canvas id="chart-faturamento" class="chart-canvas"></canvas>
            </div>

            <div class="chart-container">
                <div class="chart-title">
                    <i class="bx bx-user-check"></i>
                    OS por Técnico
                </div>
                <canvas id="chart-por-tecnico" class="chart-canvas"></canvas>
            </div>
        </div>

        <div class="charts-row">
            <div class="chart-container">
                <div class="chart-title">
                    <i class="bx bx-package"></i>
                    Top Produtos
                </div>
                <canvas id="chart-top-produtos" class="chart-canvas"></canvas>
            </div>

            <div class="chart-container">
                <div class="chart-title">
                    <i class="bx bx-wrench"></i>
                    Top Serviços
                </div>
                <canvas id="chart-top-servicos" class="chart-canvas"></canvas>
            </div>
        </div>

        <!-- Links para Relatórios Detalhados -->
        <h3 style="margin-top: 40px; margin-bottom: 20px;">
            <i class="bx bx-file-find" style="color: #667eea;"></i> Relatórios Detalhados
        </h3>

        <div class="relatorios-links">
            <a href="<?= site_url('dashboard/relatorio_atendimentos') ?>" class="relatorio-card">
                <i class="bx bx-time"></i>
                <h4>Relatório de Atendimentos</h4>
                <p>Visualize todos os atendimentos por período, técnico e status</p>
            </a>

            <a href="<?= site_url('dashboard/relatorio_financeiro') ?>" class="relatorio-card">
                <i class="bx bx-dollar-circle"></i>
                <h4>Relatório Financeiro</h4>
                <p>Análise de receitas, despesas e projeções financeiras</p>
            </a>

            <a href="<?= site_url('dashboard/relatorio_produtos') ?>" class="relatorio-card">
                <i class="bx bx-package"></i>
                <h4>Relatório de Produtos</h4>
                <p>Produtos mais vendidos, estoque crítico e rotatividade</p>
            </a>

            <a href="<?= site_url('dashboard/relatorio_clientes') ?>" class="relatorio-card">
                <i class="bx bx-group"></i>
                <h4>Relatório de Clientes</h4>
                <p>Análise de clientes novos, recorrentes e ticket médio</p>
            </a>
        </div>
    </div>
</div>

<!-- Loading Overlay -->
<div id="loading-overlay" class="loading-overlay" style="display: none;">
    <div class="loading-spinner"></div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// Variáveis globais para os gráficos
let charts = {};

// Configurações padrão do Chart.js
Chart.defaults.font.family = "'Segoe UI', 'Helvetica Neue', 'Helvetica', 'Arial', sans-serif";
Chart.defaults.color = '#666';

$(document).ready(function() {
    carregarDados();
});

function showLoading() {
    $('#loading-overlay').show();
}

function hideLoading() {
    $('#loading-overlay').hide();
}

function carregarDados() {
    showLoading();

    const periodo = $('#filtro-periodo').val();

    $.ajax({
        url: '<?= site_url("dashboard/dadosGraficos") ?>',
        type: 'GET',
        data: { periodo: periodo },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                atualizarKPIs(response.data.kpi);
                criarGraficoStatus(response.data.os_por_status);
                criarGraficoMes(response.data.os_por_mes);
                criarGraficoFaturamento(response.data.faturamento_mensal);
                criarGraficoTecnico(response.data.por_tecnico);
                criarGraficoProdutos(response.data.top_produtos);
                criarGraficoServicos(response.data.top_servicos);
            }
            hideLoading();
        },
        error: function() {
            alert('Erro ao carregar dados do dashboard');
            hideLoading();
        }
    });
}

function atualizarKPIs(kpi) {
    $('#kpi-total-os').text(kpi.total_os);
    $('#kpi-os-pendentes').text(kpi.os_pendentes);
    $('#kpi-os-finalizadas').text(kpi.os_finalizadas);
    $('#kpi-valor-faturado').text('R$ ' + formatarValor(kpi.valor_faturado));
    $('#kpi-ticket-medio').text('R$ ' + formatarValor(kpi.ticket_medio));
    $('#kpi-novos-clientes').text(kpi.novos_clientes);
}

function formatarValor(valor) {
    return parseFloat(valor).toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
}

function criarGraficoStatus(dados) {
    const ctx = document.getElementById('chart-os-status').getContext('2d');

    if (charts.status) charts.status.destroy();

    const labels = dados.map(d => d.status);
    const valores = dados.map(d => d.total);
    const cores = ['#667eea', '#764ba2', '#f093fb', '#f5576c', '#4facfe', '#11998e'];

    charts.status = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: valores,
                backgroundColor: cores,
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
}

function criarGraficoMes(dados) {
    const ctx = document.getElementById('chart-os-mes').getContext('2d');

    if (charts.mes) charts.mes.destroy();

    const labels = dados.map(d => {
        const [ano, mes] = d.mes.split('-');
        return `${mes}/${ano}`;
    });
    const valores = dados.map(d => d.total);

    charts.mes = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Quantidade de OS',
                data: valores,
                borderColor: '#667eea',
                backgroundColor: 'rgba(102, 126, 234, 0.1)',
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: { beginAtZero: true }
            }
        }
    });
}

function criarGraficoFaturamento(dados) {
    const ctx = document.getElementById('chart-faturamento').getContext('2d');

    if (charts.faturamento) charts.faturamento.destroy();

    const labels = dados.map(d => {
        const [ano, mes] = d.mes.split('-');
        return `${mes}/${ano}`;
    });
    const valores = dados.map(d => d.total);

    charts.faturamento = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Faturamento (R$)',
                data: valores,
                backgroundColor: 'rgba(17, 153, 142, 0.8)',
                borderRadius: 5
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return 'R$ ' + value.toFixed(0);
                        }
                    }
                }
            }
        }
    });
}

function criarGraficoTecnico(dados) {
    const ctx = document.getElementById('chart-por-tecnico').getContext('2d');

    if (charts.tecnico) charts.tecnico.destroy();

    const labels = dados.map(d => d.tecnico || 'N/A');
    const valores = dados.map(d => d.total);

    charts.tecnico = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Quantidade de OS',
                data: valores,
                backgroundColor: 'rgba(118, 75, 162, 0.8)',
                borderRadius: 5
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: 'y',
            plugins: {
                legend: { display: false }
            },
            scales: {
                x: { beginAtZero: true }
            }
        }
    });
}

function criarGraficoProdutos(dados) {
    const ctx = document.getElementById('chart-top-produtos').getContext('2d');

    if (charts.produtos) charts.produtos.destroy();

    const labels = dados.slice(0, 5).map(d => d.descricao.substring(0, 20) + '...');
    const valores = dados.slice(0, 5).map(d => d.total_vendido);

    charts.produtos = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Quantidade Vendida',
                data: valores,
                backgroundColor: 'rgba(240, 147, 251, 0.8)',
                borderRadius: 5
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: { beginAtZero: true }
            }
        }
    });
}

function criarGraficoServicos(dados) {
    const ctx = document.getElementById('chart-top-servicos').getContext('2d');

    if (charts.servicos) charts.servicos.destroy();

    const labels = dados.slice(0, 5).map(d => d.nome.substring(0, 20) + '...');
    const valores = dados.slice(0, 5).map(d => d.total_vendido);

    charts.servicos = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Quantidade Realizada',
                data: valores,
                backgroundColor: 'rgba(79, 172, 254, 0.8)',
                borderRadius: 5
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: { beginAtZero: true }
            }
        }
    });
}

function exportarDados() {
    const periodo = $('#filtro-periodo').val();
    window.open('<?= site_url("dashboard/exportar") ?>?tipo=atendimentos&periodo=' + periodo, '_blank');
}
</script>
