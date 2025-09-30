<?php
require_once 'X:\Secoes\D4920S012\Comum_S012\Servidor_Portal_Expresso\Server2Go\htdocs\teste\Andre\tabler_portalexpresso_paginaEncerramento\control\encerramento\analise_encerramento_control.php';
?>

<head>
    <style>
        :root {
            --thead-encerramento: #d8d8d8;
            --thead-text-encerramento: #262626
        }

        :root[data-theme="light"] {
            --thead-encerramento: #ac193d;
            --thead-text-encerramento: #ffffffff
        }

        :root[data-theme="dark"] {
            --thead-encerramento: #d8d8d8;
            --thead-text-encerramento: #262626
        }

        .thead-encerramento {
            background: var(--thead-encerramento) !important;
            font-size: .75rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: .04em;
            line-height: 1rem;
            color: var(--thead-text-encerramento) !important;
            padding-top: .5rem;
            padding-bottom: .5rem;
            white-space: nowrap;
        }
    </style>
</head>
<body>
    <!-- Filter and Search Section -->
    <div class="card mb-3">
        <div class="card-header">
            <h3 class="card-title">Filtros e Busca</h3>
        </div>
        <div class="card-body">
            <form method="GET" id="filterForm">
                <div class="row g-3">
                    <!-- Search Bar -->
                    <div class="col-md-12">
                        <div class="input-group">
                            <input type="text" class="form-control" id="searchInput" name="search" 
                                   placeholder="Buscar por Solicitação, Agência, Chave Loja, PACB, Motivo..." 
                                   value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                            <button class="btn btn-primary" type="submit">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon">
                                    <circle cx="11" cy="11" r="8"></circle>
                                    <path d="m21 21-4.35-4.35"></path>
                                </svg>
                                Buscar
                            </button>
                        </div>
                    </div>
                    
                    <!-- Bloqueio Filter -->
                    <div class="col-md-3">
                        <label class="form-label">Status Bloqueio</label>
                        <select class="form-select" name="bloqueio" id="bloqueioFilter">
                            <option value="">Todos</option>
                            <option value="bloqueado" <?php echo (isset($_GET['bloqueio']) && $_GET['bloqueio'] === 'bloqueado') ? 'selected' : ''; ?>>Bloqueado</option>
                            <option value="nao_bloqueado" <?php echo (isset($_GET['bloqueio']) && $_GET['bloqueio'] === 'nao_bloqueado') ? 'selected' : ''; ?>>Não Bloqueado</option>
                        </select>
                    </div>
                    
                    <!-- Orgao Pagador Filter -->
                    <div class="col-md-3">
                        <label class="form-label">Órgão Pagador</label>
                        <input type="text" class="form-control" name="orgao_pagador" id="orgaoPagadorFilter" 
                               placeholder="Filtrar por órgão"
                               value="<?php echo isset($_GET['orgao_pagador']) ? htmlspecialchars($_GET['orgao_pagador']) : ''; ?>">
                    </div>
                    
                    <!-- Data Inicio -->
                    <div class="col-md-3">
                        <label class="form-label">Data Início</label>
                        <input type="date" class="form-control" name="data_inicio" id="dataInicioFilter"
                               value="<?php echo isset($_GET['data_inicio']) ? htmlspecialchars($_GET['data_inicio']) : ''; ?>">
                    </div>
                    
                    <!-- Data Fim -->
                    <div class="col-md-3">
                        <label class="form-label">Data Fim</label>
                        <input type="date" class="form-control" name="data_fim" id="dataFimFilter"
                               value="<?php echo isset($_GET['data_fim']) ? htmlspecialchars($_GET['data_fim']) : ''; ?>">
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="col-md-12">
                        <button type="submit" class="btn btn-primary">
                            Aplicar Filtros
                        </button>
                        <button type="button" class="btn btn-secondary" id="clearFilters">
                            Limpar Filtros
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- DataTable Section -->
    <div class="fs-base border rounded my-5 justify-content-center overflow-auto position-relative" style="height: 50rem; width:100rem; right:100px;">
        <table class="table table-vcenter table-nowrap" id="dataTable">
            <thead class="sticky-top">
                <tr>
                    <th class="thead-encerramento" style="text-align: center;">Solicitação</th>  
                    <th class="thead-encerramento" style="text-align: center;">Agência/PACB</th>
                    <th class="thead-encerramento" style="text-align: center;">Chave Loja</th>
                    <th class="thead-encerramento" style="text-align: center;">Data Recepção</th>
                    <th class="thead-encerramento" style="text-align: center;">Data Retirada Equip.</th>
                    <th class="thead-encerramento" style="text-align: center;">Bloqueio</th>
                    <th class="thead-encerramento" style="text-align: center;">Data Última Tran.</th>
                    <th class="thead-encerramento" style="text-align: center;">Motivo Bloqueio</th>
                    <th class="thead-encerramento" style="text-align: center;">Motivo Encerramento</th>
                    <th class="thead-encerramento" style="text-align: center;">Órgão Pagador</th>
                    <th class="thead-encerramento" style="text-align: center;">Cluster</th>
                    <th class="thead-encerramento" style="text-align: center;">PARM</th>
                    <th class="thead-encerramento" style="text-align: center;">TRAG</th>
                    <th class="thead-encerramento" style="text-align: center;">TRAG SEM TRAG</th>
                    <th class="thead-encerramento" style="text-align: center;">Média Tran. Contábeis</th>
                    <th class="thead-encerramento" style="text-align: center;">Média Tran. Negócio</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $length = is_array($dados) ? count($dados) : 0;
                if ($length > 0) {
                    for ($i = 0; $i < $length; $i++) { ?>
                        <tr>
                            <th><?php echo '<span style="display: block; text-align: center;">' . htmlspecialchars($dados[$i]['COD_SOLICITACAO']) . '</span>'; ?></th>
                            <td><?php echo '<span style="display: block; text-align: center;">' . htmlspecialchars($dados[$i]['COD_AG']) . htmlspecialchars($dados[$i]['NR_PACB']) . '</span>'; ?></td>
                            <td><?php echo '<span style="display: block; text-align: center;">' . htmlspecialchars($dados[$i]['CHAVE_LOJA']) . '</span>'; ?></td>
                            <td><?php echo '<span style="display: block; text-align: center;">' . $dados[$i]['DATA_RECEPCAO']->format('d/m/Y') . '</span>'; ?></td>
                            <td><?php 
                                if (!is_null($dados[$i]['DATA_RETIRADA_EQPTO'])) {
                                    echo '<span style="display: block; text-align: center;">' . $dados[$i]['DATA_RETIRADA_EQPTO']->format('d/m/Y') . '</span>';
                                } else {
                                    echo '<span class="text-red" style="display: block; text-align: center;">Sem Data</span>';
                                }
                            ?></td>
                            <td><?php 
                                if (!is_null($dados[$i]['DATA_BLOQUEIO'])) {
                                    echo '<span class="text-green" style="display: block; text-align: center;">Bloqueado</span>';
                                } else {
                                    echo '<span class="text-red" style="display: block; text-align: center;">Não Bloqueado</span>';
                                }
                            ?></td> 
                            <td><?php echo '<span style="display: block; text-align: center;">' . htmlspecialchars($dados[$i]['DATA_LAST_TRANS']) . '</span>'; ?></td>
                            <td><?php 
                                if (!is_null($dados[$i]['MOTIVO_BLOQUEIO'])) {
                                    echo '<span style="display: block; text-align: center;">'. htmlspecialchars($dados[$i]['MOTIVO_BLOQUEIO']) .'</span>';
                                } else {
                                    echo '<span class="text-red" style="display: block; text-align: center;">Sem Motivo de Bloqueio</span>';
                                }
                            ?></td> 
                            <td><?php 
                                if (!is_null($dados[$i]['DESC_MOTIVO_ENCERRAMENTO'])) {
                                    echo '<span style="display: block; text-align: center;">'. htmlspecialchars($dados[$i]['DESC_MOTIVO_ENCERRAMENTO']) .'</span>';
                                } else {
                                    echo '<span class="text-red" style="display: block; text-align: center;">Sem Motivo de Encerramento</span>';
                                }
                            ?></td> 
                            <td><?php echo '<span style="display: block; text-align: center;">' . htmlspecialchars($dados[$i]['ORGAO_PAGADOR']) . '</span>'; ?></td>
                            <td><?php echo '<span style="display: block; text-align: center;">' . htmlspecialchars($dados[$i]['CLUSTER']) . '</span>'; ?></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                        </tr>
                    <?php }
                } else { ?>
                    <tr>
                        <td colspan="16" class="text-center">Nenhum registro encontrado</td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>

    <div class="card-footer">
        <div class="row g-2 justify-content-center justify-content-sm-between">
            <div class="col-auto d-flex align-items-center">
                <p class="m-0 text-secondary">Mostrando <strong><?php echo $totalRecords; ?></strong> registros</p>
            </div>
            <div class="col-auto">
                <ul class="pagination m-0 ms-auto">
                    <li class="page-item disabled">
                        <a class="page-link" href="#" tabindex="-1" aria-disabled="true">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-1">
                                <path d="M15 6l-6 6l6 6"></path>
                            </svg>
                        </a>
                    </li>
                    <li class="page-item active">
                        <a class="page-link" href="#">1</a>
                    </li>
                    <li class="page-item">
                        <a class="page-link" href="#">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-1">
                                <path d="M9 6l6 6l-6 6"></path>
                            </svg>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <script src="../../encerramento/analise_encerramento/analise_encerramento.js"></script>

    <script>
        (function () {
            const params = new URLSearchParams(window.location.search);
            const rawTheme = (params.get("theme") || "").trim().toLowerCase();
            const allowed = new Set(["light", "dark"]);

            const storedTheme = localStorage.getItem("theme");
            const chosen = allowed.has(rawTheme)
                ? rawTheme
                : (allowed.has(storedTheme) ? storedTheme : "light");

            document.documentElement.setAttribute("data-theme", chosen);
            localStorage.setItem("theme", chosen);
        })();
    </script>
</body>