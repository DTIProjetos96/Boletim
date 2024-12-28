<?php
// consulta_boletim_assinatura_dp.php

// Incluir a validação de sessão
require_once __DIR__ . '/../../valida_session.php'; // Ajuste o caminho conforme a estrutura do seu projeto

// Definir o cabeçalho de conteúdo para UTF-8
header('Content-Type: text/html; charset=UTF-8');

// Ativa a exibição de erros para depuração (REMOVA EM PRODUÇÃO)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Inclui o arquivo de funções se necessário
require_once __DIR__ . '/../../functions.php';   // Ajuste o caminho conforme a estrutura do seu projeto

// Função para gerar tokens CSRF
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
}

// Função para verificar tokens CSRF
function verifyCsrfToken($token) {
    if (isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token)) {
        return true;
    }
    return false;
}

generateCsrfToken();

// Captura os parâmetros de ordenação da URL
$validSortColumns = ['n_bg', 'data_bg', 'tipo_bg']; // Colunas permitidas para ordenação
$sort = isset($_GET['sort']) && in_array($_GET['sort'], $validSortColumns) ? $_GET['sort'] : 'data_bg'; // Coluna padrão
$order = isset($_GET['order']) && strtolower($_GET['order']) === 'asc' ? 'ASC' : 'DESC'; // Ordem padrão

// Inicialização das variáveis
$boletins = [];
$totalBoletins = 0;
$totalPaginas = 0;

// Captura os filtros do formulário via GET
if (isset($_GET['clear'])) {
    // Se o botão "Limpar Filtro" for clicado, redefina os filtros
    $dataInicio = '';
    $dataFim = '';
    $tipo_bg = '';
} else {
    // Caso contrário, capture os filtros da URL
    $dataInicio = isset($_GET['dataInicio']) ? $_GET['dataInicio'] : '';
    $dataFim = isset($_GET['dataFim']) ? $_GET['dataFim'] : '';
    $tipo_bg = isset($_GET['tipo_bg']) ? $_GET['tipo_bg'] : '';
}

// Definir o número da página atual
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10; // Número de registros por página
$offset = ($page - 1) * $limit;

// Função para listar os boletins com filtros, ordenação e paginação
function getBoletins($pdo, $dataInicio = '', $dataFim = '', $tipo_bg = '', $sort = 'data_bg', $order = 'DESC', $offset = 0, $limit = 10) {
    // Definir colunas permitidas para ordenação
    $validSortColumns = ['n_bg', 'data_bg', 'tipo_bg'];
    if (!in_array($sort, $validSortColumns)) {
        $sort = 'data_bg'; // Coluna padrão
    }

    // Definir ordem permitida
    $order = strtoupper($order);
    if ($order !== 'ASC' && $order !== 'DESC') {
        $order = 'DESC'; // Ordem padrão
    }

    $query = "SELECT n_bg, data_bg, tipo_bg, bole_cod, bole_ass_dp, bole_ass_cmt 
              FROM bg.vw_bg 
              WHERE 1=1";

    $params = [];

    if ($dataInicio && $dataFim) {
        $query .= " AND data_bg BETWEEN :dataInicio AND :dataFim";
        $params[':dataInicio'] = $dataInicio;
        $params[':dataFim'] = $dataFim;
    }

    if ($tipo_bg) {
        $query .= " AND tipo_bg = :tipo_bg";
        $params[':tipo_bg'] = $tipo_bg;
    }

    // Adiciona a lógica de ordenação personalizada
    $query .= " ORDER BY $sort $order 
               LIMIT :limit OFFSET :offset";

    $stmt = $pdo->prepare($query);

    // Bind values
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }

    // Bind limit e offset como inteiros
    $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);

    try {
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        // Depuração: Exibir o número de boletins retornados
        // echo '<pre>Boletins Retornados: ' . count($result) . '</pre>';
        return $result;
    } catch (PDOException $e) {
        // Depuração: Exibir erros na consulta
        echo '<pre>Erro na consulta: ' . $e->getMessage() . '</pre>';
        return [];
    }
}

// Função para obter o total de boletins
function getTotalBoletins($pdo, $dataInicio = '', $dataFim = '', $tipo_bg = '') {
    $query = "SELECT COUNT(*) as total 
              FROM bg.vw_bg 
              WHERE 1=1";

    $params = [];

    if ($dataInicio && $dataFim) {
        $query .= " AND data_bg BETWEEN :dataInicio AND :dataFim";
        $params[':dataInicio'] = $dataInicio;
        $params[':dataFim'] = $dataFim;
    }

    if ($tipo_bg) {
        $query .= " AND tipo_bg = :tipo_bg";
        $params[':tipo_bg'] = $tipo_bg;
    }

    $stmt = $pdo->prepare($query);

    // Bind values
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }

    try {
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'] ?? 0;
    } catch (PDOException $e) {
        // Depuração: Exibir erros na consulta
        echo '<pre>Erro na consulta de total: ' . $e->getMessage() . '</pre>';
        return 0;
    }
}

// Função para obter tipos de boletins
function getTiposBoletins($pdo) {
    try {
        $stmt = $pdo->query('SELECT tipo_bole_descricao FROM bg.tipo_boletim ORDER BY tipo_bole_descricao');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Depuração: Exibir erros na consulta
        echo '<pre>Erro ao obter tipos de boletins: ' . $e->getMessage() . '</pre>';
        return [];
    }
}

// Função para gerar links de ordenação com ícones do Bootstrap Icons
function sortLink($column, $label, $currentSort, $currentOrder) {
    // Define os ícones usando Bootstrap Icons
    $icons = [
        'asc' => '<i class="bi bi-arrow-up"></i>',
        'desc' => '<i class="bi bi-arrow-down"></i>',
        'number' => '<i class="bi bi-sort-numeric-up"></i>',
        'date' => '<i class="bi bi-calendar"></i>'
    ];

    // Determina a ordem inversa para o próximo clique
    $newOrder = 'asc';
    $icon = '';
    if ($currentSort === $column) {
        if ($currentOrder === 'ASC') {
            $newOrder = 'desc';
            $icon = $icons['asc'];
        } else {
            $newOrder = 'asc';
            $icon = $icons['desc'];
        }
    }

    // Determina o ícone para a coluna
    if ($column === 'n_bg') {
        $columnIcon = $icons['number'];
    } elseif ($column === 'data_bg') {
        $columnIcon = $icons['date'];
    } else {
        $columnIcon = '';
    }

    // Constrói os parâmetros para a URL
    $queryParams = $_GET;
    $queryParams['sort'] = $column;
    $queryParams['order'] = $newOrder;
    $queryString = http_build_query($queryParams);

    // Retorna o link HTML com classes do Bootstrap para consistência
    return '<a href="' . htmlspecialchars($_SERVER['PHP_SELF'] . '?' . $queryString) . '" class="text-white text-decoration-none d-flex align-items-center justify-content-center">' . 
           htmlspecialchars($label) . ' ' . $columnIcon . ' ' . $icon . '</a>';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assinar Boletim</title>
    <!-- Bootstrap 5.3.0 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .container {
            padding-top: 20px;
        }
        h1 {
            margin-bottom: 20px;
            font-size: 24px;
            color: #0d6efd; /* Azul do btn-primary */
        }
        /* Ajuste para garantir responsividade */
        .table-responsive {
            overflow-x: auto;
        }
        table {
            width: 100%;
            margin-top: 20px;
        }
        thead th {
            /* Mantendo a cor via Bootstrap classes */
        }
        tbody td {
            padding: 10px;
            text-align: center;
        }
        .btn-assinar {
            background-color: #28a745;
            color: white;
        }
        .btn-assinar.disabled {
            background-color: #6c757d;
            cursor: not-allowed;
        }
        .btn-primary {
            background-color: #0d6efd;
            border-color: #0d6efd;
        }
        .btn-secondary {
            background-color: #6c757d;
            border-color: #6c757d;
        }
        /* Estilo para os ícones de ordenação */
        .sort-icon {
            margin-left: 5px;
            vertical-align: middle;
            color: white; /* Cor branca para melhor visibilidade */
        }
        .pagination {
            justify-content: center;
        }
        .pagination .page-item.disabled .page-link {
            pointer-events: none;
            opacity: 0.6;
        }
        /* Cursor pointer para o ícone de toggle de senha */
        .input-group .input-group-text.toggle-password {
            cursor: pointer;
        }
        /* Ajuste para posicionamento do ícone de olho */
        .toggle-password {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        /* Forçar a cor do cabeçalho para igualar ao botão "Filtrar" */
        .table-primary {
            background-color: #0d6efd !important; /* Azul do btn-primary */
            color: #fff !important; /* Texto em branco */
        }
    </style>
</head>
<body>
<div class="container">
    <h1>Assinar Boletim</h1>

    <!-- Exibir mensagens de feedback -->
    <?php if (!empty($message)): ?>
        <div class="alert alert-<?= htmlspecialchars($message['type']) ?> alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($message['text']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        </div>
    <?php endif; ?>

    <form method="GET" action="">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
        <div class="row">
            <div class="col-md-3 mb-3">
                <label for="dataInicio" class="form-label">Data Início</label>
                <input type="date" class="form-control" id="dataInicio" name="dataInicio" value="<?php echo htmlspecialchars($dataInicio); ?>">
            </div>
            <div class="col-md-3 mb-3">
                <label for="dataFim" class="form-label">Data Fim</label>
                <input type="date" class="form-control" id="dataFim" name="dataFim" value="<?php echo htmlspecialchars($dataFim); ?>">
            </div>
            <div class="col-md-3 mb-3">
                <label for="tipo_bg" class="form-label">Tipo de Boletim</label>
                <select class="form-select" id="tipo_bg" name="tipo_bg">
                    <option value="">Selecione</option>
                    <?php foreach (getTiposBoletins($pdo) as $tipo): ?>
                        <option value="<?php echo htmlspecialchars($tipo['tipo_bole_descricao']); ?>" <?php echo ($tipo_bg == $tipo['tipo_bole_descricao']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($tipo['tipo_bole_descricao']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 align-self-end mb-3">
                <button type="submit" class="btn btn-primary w-100">Filtrar</button>
                <button type="submit" name="clear" value="1" class="btn btn-secondary w-100 mt-2">Limpar Filtro</button>
            </div>
        </div>
    </form>

    <?php
    // Buscar os boletins com os filtros e paginação
    $boletins = getBoletins($pdo, $dataInicio, $dataFim, $tipo_bg, $sort, $order, $offset, $limit);
    $totalBoletins = getTotalBoletins($pdo, $dataInicio, $dataFim, $tipo_bg);
    $totalPaginas = ceil($totalBoletins / $limit);

    // Depuração Temporária: Exibir os boletins retornados (REMOVA EM PRODUÇÃO)
    /*
    echo '<pre>';
    echo "Boletins Retornados:\n";
    print_r($boletins);
    echo "</pre>";
    */
    ?>

    <div class="table-responsive">
        <table id="boletins-table" class="table table-bordered table-striped table-hover mt-4">
            <thead class="table-primary">
            <tr>
                <th><?php echo sortLink('n_bg', 'Número BG', $sort, $order); ?></th>
                <th><?php echo sortLink('data_bg', 'Data', $sort, $order); ?></th>
                <th><?php echo sortLink('tipo_bg', 'Tipo de Boletim', $sort, $order); ?></th>
                <th>Quem Assinou?</th>
                <th>Ações</th>
            </tr>
            </thead>
            <tbody>
            <?php if (!empty($boletins)): ?>
                <?php foreach ($boletins as $boletim): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($boletim['n_bg']); ?></td>
                        <td><?php echo date('d/m/Y', strtotime($boletim['data_bg'])); ?></td>
                        <td><?php echo htmlspecialchars($boletim['tipo_bg']); ?></td>
                        <td>
                            <?php if ($boletim['bole_ass_dp'] == 1): ?>
                                <!-- Ícone de Check em Verde para DP -->
                                <i class="bi bi-check-circle-fill text-success" title="Assinado pelo DP"></i> DP
                            <?php else: ?>
                                <!-- Ícone de Cross em Vermelho para DP -->
                                <i class="bi bi-x-circle-fill text-danger" title="Não Assinado pelo DP"></i> DP
                            <?php endif; ?>

                            <?php if ($boletim['bole_ass_cmt'] == 1): ?>
                                <!-- Ícone de Check em Verde para CMT -->
                                <i class="bi bi-check-circle-fill text-success ms-3" title="Assinado pelo CMT"></i> CMT
                            <?php else: ?>
                                <!-- Ícone de Cross em Vermelho para CMT -->
                                <i class="bi bi-x-circle-fill text-danger ms-3" title="Não Assinado pelo CMT"></i> CMT
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php
                            $acao_assinar = 'consulta_boletim_assinatura_dp.php|assinar_boletim';
                            if ($boletim['bole_ass_dp'] == 1 || $boletim['bole_ass_cmt'] == 1 || !usuario_tem_acesso($acao_assinar)) {
                                echo '<button class="btn btn-assinar disabled" disabled>Assinar Boletim</button>';
                            } else {
                                echo '<button class="btn btn-assinar assinar-boletim-btn" data-bolecod="' . htmlspecialchars($boletim['bole_cod']) . '">Assinar Boletim</button>';
                            }
                            ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" class="text-center">Nenhum boletim encontrado.</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Controles de Paginação Sempre Visíveis -->
    <nav aria-label="Page navigation">
        <ul class="pagination justify-content-center">
            <!-- Botão Anterior -->
            <li class="page-item <?php if ($page <= 1) echo 'disabled'; ?>">
                <a class="page-link" href="<?php 
                    // Manter os filtros e ordenação na URL ao navegar
                    $prevPage = $page - 1;
                    if ($prevPage < 1) $prevPage = 1;
                    $queryParams = [
                        'page' => $prevPage,
                        'sort' => $sort,
                        'order' => $order
                    ];
                    if ($dataInicio) $queryParams['dataInicio'] = $dataInicio;
                    if ($dataFim) $queryParams['dataFim'] = $dataFim;
                    if ($tipo_bg) $queryParams['tipo_bg'] = $tipo_bg;
                    echo htmlspecialchars($_SERVER['PHP_SELF']) . '?' . http_build_query($queryParams);
                ?>" aria-label="Anterior">
                    <span aria-hidden="true">&laquo;</span>
                </a>
            </li>

            <!-- Números das Páginas -->
            <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
                <li class="page-item <?php if ($i == $page) echo 'active'; ?>">
                    <a class="page-link" href="<?php 
                        $queryParams = [
                            'page' => $i,
                            'sort' => $sort,
                            'order' => $order
                        ];
                        if ($dataInicio) $queryParams['dataInicio'] = $dataInicio;
                        if ($dataFim) $queryParams['dataFim'] = $dataFim;
                        if ($tipo_bg) $queryParams['tipo_bg'] = $tipo_bg;
                        echo htmlspecialchars($_SERVER['PHP_SELF']) . '?' . http_build_query($queryParams);
                    ?>"><?php echo $i; ?></a>
                </li>
            <?php endfor; ?>

            <!-- Botão Próximo -->
            <li class="page-item <?php if ($page >= $totalPaginas) echo 'disabled'; ?>">
                <a class="page-link" href="<?php 
                    $nextPage = $page + 1;
                    if ($nextPage > $totalPaginas) $nextPage = $totalPaginas;
                    $queryParams = [
                        'page' => $nextPage,
                        'sort' => $sort,
                        'order' => $order
                    ];
                    if ($dataInicio) $queryParams['dataInicio'] = $dataInicio;
                    if ($dataFim) $queryParams['dataFim'] = $dataFim;
                    if ($tipo_bg) $queryParams['tipo_bg'] = $tipo_bg;
                    echo htmlspecialchars($_SERVER['PHP_SELF']) . '?' . http_build_query($queryParams);
                ?>" aria-label="Próximo">
                    <span aria-hidden="true">&raquo;</span>
                </a>
            </li>
        </ul>
    </nav>

    <!-- Controles de Paginação - Caso haja apenas uma página -->
    <?php if ($totalPaginas == 0): ?>
        <nav aria-label="Page navigation">
            <ul class="pagination justify-content-center">
                <li class="page-item disabled">
                    <a class="page-link" href="#" aria-label="Anterior">
                        <span aria-hidden="true">&laquo;</span>
                    </a>
                </li>
                <li class="page-item active">
                    <a class="page-link" href="#">1</a>
                </li>
                <li class="page-item disabled">
                    <a class="page-link" href="#" aria-label="Próximo">
                        <span aria-hidden="true">&raquo;</span>
                    </a>
                </li>
            </ul>
        </nav>
    <?php endif; ?>
</div>

<!-- Modal de Assinatura de Boletim -->
<div class="modal fade" id="assinarBoletimModal" tabindex="-1" aria-labelledby="assinarBoletimModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Assinar Boletim</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <iframe id="modalContentFrame" src="" width="100%" height="400px" frameborder="0"></iframe>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Confirmação de Senha -->
<div class="modal fade" id="confirmPasswordModal" tabindex="-1" aria-labelledby="confirmPasswordModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="passwordConfirmForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmPasswordModalLabel">Confirmação de Senha</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <div id="passwordError" class="alert alert-danger d-none"></div>
                    <div id="passwordLogs" class="alert alert-secondary d-none">
                        <strong>Logs de Depuração:</strong>
                        <ul id="logsList"></ul>
                    </div>
                    <div class="form-group position-relative">
                        <label for="userPassword" class="form-label">Digite sua senha para confirmar:</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="userPassword" name="userPassword" required placeholder="Digite sua senha">
                            <span class="input-group-text toggle-password" title="Mostrar senha" aria-label="Mostrar senha">
                                <i class="bi bi-eye-fill"></i>
                            </span>
                        </div>
                    </div>
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Confirmar</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Bootstrap 5.3.0 JS Bundle (Inclui Popper) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- jQuery 3.6.0 -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Script para Toggle de Senha e Funcionalidades dos Modais -->
<script>
    $(document).ready(function() {
        console.log('Document is ready.');
        var selectedBoleCod = null;

        // Usar Event Delegation para garantir que todos os botões sejam capturados
        $(document).on('click', '.assinar-boletim-btn', function(e) {
            e.preventDefault(); // Evita comportamentos padrão indesejados
            console.log('Botão "Assinar Boletim" clicado.');

            selectedBoleCod = $(this).data('bolecod');
            console.log('Código do Boletim selecionado:', selectedBoleCod);

            // Limpar campos e mensagens no modal de senha
            $('#userPassword').val('');
            $('#passwordError').addClass('d-none').text('');
            $('#passwordLogs').empty().addClass('d-none');

            // Abrir o modal de confirmação de senha
            var confirmPasswordModal = new bootstrap.Modal(document.getElementById('confirmPasswordModal'));
            confirmPasswordModal.show();
        });

        $('#passwordConfirmForm').on('submit', function(e) {
            e.preventDefault();
            console.log('Formulário de confirmação de senha enviado.');

            var password = $('#userPassword').val();
            var csrfToken = $('input[name="csrf_token"]').val();

            console.log('Senha digitada:', password);
            console.log('Token CSRF:', csrfToken);

            $.ajax({
                url: '../../verify_password.php',
                type: 'POST',
                data: { 
                    password: password,
                    csrf_token: csrfToken
                },
                dataType: 'json',
                success: function(response) {
                    console.log('Resposta do servidor:', response);
                    if (response.status === 'success') {
                        console.log('Senha verificada com sucesso.');
                        var confirmPasswordModal = bootstrap.Modal.getInstance(document.getElementById('confirmPasswordModal'));
                        confirmPasswordModal.hide();

                        var url = 'cad_assina_boletim_dp.php?bole_cod=' + encodeURIComponent(selectedBoleCod);
                        $('#modalContentFrame').attr('src', url);

                        var assinarBoletimModal = new bootstrap.Modal(document.getElementById('assinarBoletimModal'));
                        assinarBoletimModal.show();
                    } else {
                        console.log('Falha na verificação da senha:', response.message);
                        $('#passwordError').removeClass('d-none').text(response.message);
                        if (response.logs && response.logs.length > 0) {
                            var logsHtml = '<ul>';
                            response.logs.forEach(function(log) {
                                logsHtml += '<li>' + $('<div>').text(log).html() + '</li>';
                            });
                            logsHtml += '</ul>';
                            $('#passwordLogs').html(logsHtml).removeClass('d-none');
                        }
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error('Erro na requisição AJAX:', textStatus, errorThrown);
                    $('#passwordError').removeClass('d-none').text('Ocorreu um erro ao verificar a senha. Por favor, tente novamente.');
                }
            });
        });

        // Toggle para mostrar/ocultar a senha com Bootstrap Icons
        $(document).on('click', '.toggle-password', function() {
            var senhaInput = $('#userPassword');
            var toggleIcon = $(this).find('i');

            if (senhaInput.attr('type') === 'password') {
                senhaInput.attr('type', 'text');
                toggleIcon.removeClass('bi-eye-fill').addClass('bi-eye-slash-fill');
                $(this).attr('title', 'Ocultar senha').attr('aria-label', 'Ocultar senha');
            } else {
                senhaInput.attr('type', 'password');
                toggleIcon.removeClass('bi-eye-slash-fill').addClass('bi-eye-fill');
                $(this).attr('title', 'Mostrar senha').attr('aria-label', 'Mostrar senha');
            }
        });

        $('#assinarBoletimModal').on('hidden.bs.modal', function() {
            $('#modalContentFrame').attr('src', '');
            selectedBoleCod = null;
            console.log('Modal de assinatura de boletim fechado.');

            // Recarregar a página para atualizar a lista de boletins
            location.reload();
        });
    });
</script>
</body>
</html>
