<?php
$totalPresentes = $dados['totalPresentes'] ?? 0;
$producaoTotal = $dados['producaoTotal'] ?? 0.00;
$base_url = '/sgi_erp';
?>

<div class="pt-4">
    <!-- Earnings (Monthly) Card Example -->
    <div class="row">

        <!-- Card Funcionários -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total de Funcionários Presentes</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $totalPresentes; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card Produção -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Produção Lançada (Hoje)</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($producaoTotal, 2, ',', '.'); ?> Kg</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-balance-scale fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card Warning Exemplo (adapte ou remova) -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Outras Métricas</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">Exemplo</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-comments fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card Danger Exemplo (adapte ou remova) -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-danger shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Alertas</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">0</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Fluxo Operacional (adaptado como cards coloridos) -->
<div class="row">

    <div class="col-xl-3 col-md-6 mb-4">
        <a href="<?php echo $base_url; ?>/presenca" class="btn btn-primary btn-lg w-100 shadow py-3">
            <i class="fas fa-calendar-check me-2"></i>
            REGISTRAR PRESENÇA (CHAMADA)
        </a>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <a href="<?php echo $base_url; ?>/equipes" class="btn btn-warning btn-lg w-100 shadow py-3">
            <i class="fas fa-users me-2"></i>
            MONTAR EQUIPES
        </a>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <a href="<?php echo $base_url; ?>/producao" class="btn btn-success btn-lg w-100 shadow py-3">
            <i class="fas fa-balance-scale me-2"></i>
            LANÇAR PRODUÇÃO
        </a>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <a href="<?php echo $base_url; ?>/relatorios" class="btn btn-danger btn-lg w-100 shadow py-3">
            <i class="fas fa-chart-line me-2"></i>
            RELATÓRIOS / PAGAMENTOS
        </a>
    </div>
</div>

<!-- Gráficos Exemplo (adapte com dados reais usando Chart.js do theme) -->
<div class="row">

    <div class="col-lg-6 mb-4">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Area Chart Example</h6>
            </div>
            <div class="card-body">
                <div class="chart-area">
                    <canvas id="myAreaChart"></canvas>
                </div>
                <hr>
                Placeholder para gráfico de área (use JS do theme para preencher).
            </div>
        </div>
    </div>

    <div class="col-lg-6 mb-4">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Bar Chart Example</h6>
            </div>
            <div class="card-body">
                <div class="chart-bar">
                    <canvas id="myBarChart"></canvas>
                </div>
                <hr>
                Placeholder para gráfico de barras (use JS do theme para preencher).
            </div>
        </div>
    </div>

</div>