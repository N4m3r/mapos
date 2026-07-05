<div class="row-fluid" style="margin-top: 0">
    <div class="span12">
        <div class="widget-box">
            <div class="widget-title">
                <span class="icon"><i class="bx bx-time"></i></span>
                <h5>Relatório de Atendimentos</h5>
            </div>

            <div class="widget-content">
                <!-- Filtros -->
                <div class="filtros-relatorio" style="background: #f5f5f5; padding: 15px; border-radius: 4px; margin-bottom: 20px;">
                    <form id="form-filtros" class="form-inline">
                        <div class="row-fluid">
                            <div class="span3">
                                <label for="filtro-data-inicio">Data Início:</label>
                                <input type="date" id="filtro-data-inicio" class="span12" value="<?php echo $results['data_inicio']; ?>">
                            </div>

                            <div class="span3">
                                <label for="filtro-data-fim">Data Fim:</label>
                                <input type="date" id="filtro-data-fim" class="span12" value="<?php echo $results['data_fim']; ?>">
                            </div>

                            <div class="span4">
                                <label for="filtro-tecnico">Técnico:</label>
                                <select id="filtro-tecnico" class="span12">
                                    <option value="">Todos os Técnicos</option>
                                    <?php foreach ($results['tecnicos'] as $tecnico): ?>
                                        <option value="<?php echo $tecnico->idUsuarios; ?>"
                                            <?php echo ($results['usuario_id'] == $tecnico->idUsuarios) ? 'selected' : ''; ?>>
                                            <?php echo $tecnico->nome; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="span2" style="padding-top: 25px;">
                                <button type="button" id="btn-filtrar" class="btn btn-primary span12">
                                    <i class="bx bx-filter"></i> Filtrar
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Cards de Estatísticas -->
                <div id="cards-estatisticas" class="row-fluid" style="margin-bottom: 20px;">
                    <div class="span3">
                        <div class="card-estatistica" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                            <div class="icone" style="font-size: 36px; opacity: 0.8; margin-bottom: 10px;">
                                <i class="bx bx-calendar-check"></i>
                            </div>
                            <div class="valor" id="card-total" style="font-size: 32px; font-weight: bold; margin-bottom: 5px;">0</div>
                            <div class="label" style="font-size: 14px; opacity: 0.9;">Total de Atendimentos</div>
                        </div>
                    </div>

                    <div class="span3">
                        <div class="card-estatistica" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                            <div class="icone" style="font-size: 36px; opacity: 0.8; margin-bottom: 10px;">
                                <i class="bx bx-time"></i>
                            </div>
                            <div class="valor" id="card-tempo-medio" style="font-size: 32px; font-weight: bold; margin-bottom: 5px;">0h</div>
                            <div class="label" style="font-size: 14px; opacity: 0.9;">Tempo Médio</div>
                        </div>
                    </div>

                    <div class="span3">
                        <div class="card-estatistica" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                            <div class="icone" style="font-size: 36px; opacity: 0.8; margin-bottom: 10px;">
                                <i class="bx bx-check-circle"></i>
                            </div>
                            <div class="valor" id="card-finalizados" style="font-size: 32px; font-weight: bold; margin-bottom: 5px;">0</div>
                            <div class="label" style="font-size: 14px; opacity: 0.9;">Finalizados</div>
                        </div>
                    </div>

                    <div class="span3">
                        <div class="card-estatistica" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); color: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                            <div class="icone" style="font-size: 36px; opacity: 0.8; margin-bottom: 10px;">
                                <i class="bx bx-loader"></i>
                            </div>
                            <div class="valor" id="card-andamento" style="font-size: 32px; font-weight: bold; margin-bottom: 5px;">0</div>
                            <div class="label" style="font-size: 14px; opacity: 0.9;">Em Andamento</div>
                        </div>
                    </div>
                </div>

                <!-- Botão Exportar -->
                <div class="row-fluid" style="margin-bottom: 20px;">
                    <div class="span12" style="text-align: right;">
                        <a href="#" id="btn-exportar" class="btn btn-success">
                            <i class="bx bx-download"></i> Exportar Excel
                        </a>
                    </div>
                </div>

                <!-- Gráficos -->
                <div class="row-fluid" style="margin-bottom: 20px;">
                    <!-- Gráfico de Atendimentos por Dia -->
                    <div class="span6">
                        <div class="widget-box">
                            <div class="widget-title">
                                <span class="icon"><i class="bx bx-line-chart"></i></span>
                                <h5>Atendimentos por Dia</h5>
                            </div>
                            <div class="widget-content">
                                <canvas id="grafico-atendimentos-dia" height="200"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Gráfico por Técnico -->
                    <div class="span6">
                        <div class="widget-box">
                            <div class="widget-title">
                                <span class="icon"><i class="bx bx-bar-chart"></i></span>
                                <h5>Atendimentos por Técnico</h5>
                            </div>
                            <div class="widget-content">
                                <canvas id="grafico-atendimentos-tecnico" height="200"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row-fluid" style="margin-bottom: 20px;">
                    <!-- Gráfico por Status -->
                    <div class="span6">
                        <div class="widget-box">
                            <div class="widget-title">
                                <span class="icon"><i class="bx bx-pie-chart-alt"></i></span>
                                <h5>Distribuição por Status</h5>
                            </div>
                            <div class="widget-content">
                                <canvas id="grafico-status" height="200"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Gráfico de Tempo Médio -->
                    <div class="span6">
                        <div class="widget-box">
                            <div class="widget-title">
                                <span class="icon"><i class="bx bx-time"></i></span>
                                <h5>Tempo Médio por Técnico (horas)</h5>
                            </div>
                            <div class="widget-content">
                                <canvas id="grafico-tempo-medio" height="200"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabela de Atendimentos -->
                <div class="widget-box">
                    <div class="widget-title">
                        <span class="icon"><i class="bx bx-list-ul"></i></span>
                        <h5>Lista de Atendimentos</h5>
                    </div>
                    <div class="widget-content">
                        <table id="tabela-atendimentos" class="table table-bordered table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>OS</th>
                                    <th>Técnico</th>
                                    <th>Data Entrada</th>
                                    <th>Data Saída</th>
                                    <th>Tempo</th>
                                    <th>Status</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Configurações globais
    const baseUrl = '<?php echo base_url(); ?>';

    // Instâncias dos gráficos
    let graficoAtendimentosDia = null;
    let graficoAtendimentosTecnico = null;
    let graficoStatus = null;
    let graficoTempoMedio = null;

    // DataTable
    let tabelaAtendimentos = null;

    // Cores dos gráficos (tema MAP-OS)
    const cores = {
        primaria: '#2d335b',
        sucesso: '#28a745',
        perigo: '#dc3545',
        alerta: '#ffc107',
        info: '#17a2b8',
        roxo: '#6f42c1',
        laranja: '#fd7e14',
        cinza: '#6c757d',
        paleta: [
            '#2d335b', '#28a745', '#dc3545', '#ffc107',
            '#17a2b8', '#6f42c1', '#fd7e14', '#20c997',
            '#6610f2', '#e83e8c', '#6c757d', '#343a40'
        ]
    };

    /**
     * Inicializa a página
     */
    function init() {
        inicializarTabela();
        carregarDados();
        bindEventos();
    }

    /**
     * Configura eventos
     */
    function bindEventos() {
        // Botão filtrar
        $('#btn-filtrar').on('click', function() {
            carregarDados();
        });

        // Botão exportar
        $('#btn-exportar').on('click', function(e) {
            e.preventDefault();
            exportarExcel();
        });

        // Change nos filtros (auto-filtrar)
        $('#filtro-data-inicio, #filtro-data-fim, #filtro-tecnico').on('change', function() {
            // Opcional: auto-filtrar ao mudar
            // carregarDados();
        });
    }

    /**
     * Inicializa DataTable
     */
    function inicializarTabela() {
        tabelaAtendimentos = $('#tabela-atendimentos').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: baseUrl + 'index.php/relatorioatendimentos/listar',
                type: 'POST',
                data: function(d) {
                    d.data_inicio = $('#filtro-data-inicio').val();
                    d.data_fim = $('#filtro-data-fim').val();
                    d.usuario_id = $('#filtro-tecnico').val();
                }
            },
            language: {
                url: '//cdn.datatables.net/plug-ins/1.10.21/i18n/Portuguese-Brasil.json'
            },
            columns: [
                { data: 'idCheckin', width: '50px' },
                { data: 'os_id', width: '60px' },
                { data: 'nome_tecnico' },
                { data: 'data_entrada', width: '130px' },
                { data: 'data_saida', width: '130px' },
                { data: 'tempo', width: '80px' },
                { data: 'status', width: '100px', orderable: false },
                { data: 'acoes', width: '60px', orderable: false, searchable: false }
            ],
            order: [[3, 'desc']], // Ordena por data de entrada descendente
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]]
        });
    }

    /**
     * Carrega todos os dados (estatísticas e gráficos)
     */
    function carregarDados() {
        const dataInicio = $('#filtro-data-inicio').val();
        const dataFim = $('#filtro-data-fim').val();
        const usuarioId = $('#filtro-tecnico').val();

        // Mostra loading nos cards
        $('#card-total, #card-tempo-medio, #card-finalizados, #card-andamento').html('<i class="bx bx-loader bx-spin"></i>');

        // Carrega estatísticas
        $.ajax({
            url: baseUrl + 'index.php/relatorioatendimentos/estatisticas',
            type: 'POST',
            dataType: 'json',
            data: {
                data_inicio: dataInicio,
                data_fim: dataFim,
                usuario_id: usuarioId
            },
            success: function(response) {
                if (response.success) {
                    atualizarCards(response.estatisticas);
                    atualizarGraficos(response);
                }
            },
            error: function(xhr, status, error) {
                console.error('Erro ao carregar estatísticas:', error);
                alert('Erro ao carregar estatísticas. Tente novamente.');
            }
        });

        // Recarrega tabela
        if (tabelaAtendimentos) {
            tabelaAtendimentos.ajax.reload();
        }
    }

    /**
     * Atualiza cards de estatísticas
     */
    function atualizarCards(estatisticas) {
        // Total de atendimentos
        $('#card-total').text(estatisticas.total_atendimentos || 0);

        // Tempo médio (converte para horas com 1 decimal)
        const tempoMedio = estatisticas.tempo_medio_horas || 0;
        $('#card-tempo-medio').text(tempoMedio.toFixed(1) + 'h');

        // Finalizados
        $('#card-finalizados').text(estatisticas.finalizados || 0);

        // Em andamento
        $('#card-andamento').text(estatisticas.em_andamento || 0);
    }

    /**
     * Atualiza todos os gráficos
     */
    function atualizarGraficos(dados) {
        criarGraficoAtendimentosDia(dados.atendimentosPorDia);
        criarGraficoAtendimentosTecnico(dados.atendimentosPorTecnico);
        criarGraficoStatus(dados.atendimentosPorStatus);
        criarGraficoTempoMedio(dados.tempoMedioPorTecnico);
    }

    /**
     * Gráfico de atendimentos por dia
     */
    function criarGraficoAtendimentosDia(dados) {
        const ctx = document.getElementById('grafico-atendimentos-dia').getContext('2d');

        // Destrói gráfico anterior se existir
        if (graficoAtendimentosDia) {
            graficoAtendimentosDia.destroy();
        }

        const labels = dados.map(d => {
            const data = new Date(d.data);
            return data.toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit' });
        });

        const valores = dados.map(d => parseInt(d.quantidade));

        graficoAtendimentosDia = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Atendimentos',
                    data: valores,
                    borderColor: cores.primaria,
                    backgroundColor: cores.primaria + '20', // 20% opacidade
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 4,
                    pointBackgroundColor: cores.primaria
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        callbacks: {
                            label: function(context) {
                                return context.parsed.y + ' atendimento(s)';
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    }

    /**
     * Gráfico de atendimentos por técnico
     */
    function criarGraficoAtendimentosTecnico(dados) {
        const ctx = document.getElementById('grafico-atendimentos-tecnico').getContext('2d');

        if (graficoAtendimentosTecnico) {
            graficoAtendimentosTecnico.destroy();
        }

        const labels = dados.map(d => d.tecnico);
        const valores = dados.map(d => parseInt(d.quantidade));

        // Gera cores para cada barra
        const backgroundColors = valores.map((_, i) => cores.paleta[i % cores.paleta.length]);

        graficoAtendimentosTecnico = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Atendimentos',
                    data: valores,
                    backgroundColor: backgroundColors,
                    borderColor: backgroundColors,
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.parsed.y + ' atendimento(s)';
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    }

    /**
     * Gráfico de distribuição por status
     */
    function criarGraficoStatus(dados) {
        const ctx = document.getElementById('grafico-status').getContext('2d');

        if (graficoStatus) {
            graficoStatus.destroy();
        }

        const labels = dados.map(d => d.status);
        const valores = dados.map(d => parseInt(d.quantidade));

        // Cores específicas para status
        const coresStatus = [
            cores.sucesso,  // Finalizado
            cores.alerta    // Em Andamento
        ];

        graficoStatus = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: valores,
                    backgroundColor: coresStatus,
                    borderColor: '#fff',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.parsed;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((value / total) * 100).toFixed(1);
                                return label + ': ' + value + ' (' + percentage + '%)';
                            }
                        }
                    }
                }
            }
        });
    }

    /**
     * Gráfico de tempo médio por técnico
     */
    function criarGraficoTempoMedio(dados) {
        const ctx = document.getElementById('grafico-tempo-medio').getContext('2d');

        if (graficoTempoMedio) {
            graficoTempoMedio.destroy();
        }

        const labels = dados.map(d => d.tecnico);
        const valores = dados.map(d => parseFloat(d.tempo_medio_horas).toFixed(1));

        graficoTempoMedio = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Tempo Médio (horas)',
                    data: valores,
                    backgroundColor: cores.info,
                    borderColor: cores.info,
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.parsed.y + ' horas';
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Horas'
                        }
                    }
                }
            }
        });
    }

    /**
     * Exporta dados para Excel
     */
    function exportarExcel() {
        const dataInicio = $('#filtro-data-inicio').val();
        const dataFim = $('#filtro-data-fim').val();
        const usuarioId = $('#filtro-tecnico').val();

        let url = baseUrl + 'index.php/relatorioatendimentos/exportar';
        url += '?data_inicio=' + encodeURIComponent(dataInicio);
        url += '&data_fim=' + encodeURIComponent(dataFim);
        if (usuarioId) {
            url += '&usuario_id=' + encodeURIComponent(usuarioId);
        }

        window.open(url, '_blank');
    }

    // Inicializa
    init();
});
</script>

<style>
/* Estilos adicionais para o dashboard */
.card-estatistica {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.card-estatistica:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15) !important;
}

/* Animação de entrada */
@keyframes slideInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.card-estatistica {
    animation: slideInUp 0.5s ease forwards;
}

.card-estatistica:nth-child(1) { animation-delay: 0s; }
.card-estatistica:nth-child(2) { animation-delay: 0.1s; }
.card-estatistica:nth-child(3) { animation-delay: 0.2s; }
.card-estatistica:nth-child(4) { animation-delay: 0.3s; }

/* Responsividade */
@media (max-width: 768px) {
    .card-estatistica {
        margin-bottom: 15px;
    }

    #cards-estatisticas .span3 {
        width: 100%;
        margin-left: 0;
    }
}

/* DataTable personalizações */
#tabela-atendimentos.dataTable {
    width: 100% !important;
}

#tabela-atendimentos .label {
    padding: 4px 8px;
    font-size: 11px;
    border-radius: 3px;
}

/* Filtros em mobile */
@media (max-width: 768px) {
    .filtros-relatorio .span3,
    .filtros-relatorio .span4,
    .filtros-relatorio .span2 {
        width: 100%;
        margin-left: 0;
        margin-bottom: 10px;
    }

    .filtros-relatorio label {
        margin-bottom: 5px;
    }
}
</style>
