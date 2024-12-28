<?php
include '../db.php';

// Função para buscar matérias para boletim utilizando a nova consulta
function getMateriasBoletim($pdo, $texto_materia = '', $assunto_especifico = '', $assunto_geral = '', $status = '') {
    $query = "SELECT mate_bole_cod, mate_bole_data, mate_bole_texto, assu_espe_descricao, assu_gera_descricao, status
              FROM vw_materias_boletins 
              WHERE 1=1";

    if ($texto_materia) {
        $query .= " AND mate_bole_texto LIKE :texto_materia";
    }
    if ($assunto_especifico) {
        $query .= " AND assu_espe_descricao LIKE :assunto_especifico";
    }
    if ($assunto_geral) {
        $query .= " AND assu_gera_descricao LIKE :assunto_geral";
    }
    if ($status) {
        $query .= " AND status = :status";
    }

    $query .= " ORDER BY mate_bole_cod LIMIT 50";

    try {
        $stmt = $pdo->prepare($query);

        if ($texto_materia) {
            $stmt->bindValue(':texto_materia', "%$texto_materia%");
        }
        if ($assunto_especifico) {
            $stmt->bindValue(':assunto_especifico', "%$assunto_especifico%");
        }
        if ($assunto_geral) {
            $stmt->bindValue(':assunto_geral', "%$assunto_geral%");
        }
        if ($status) {
            $stmt->bindValue(':status', $status);
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erro na consulta SQL: " . $e->getMessage());
        return [];
    }
}

// Recebendo os dados do formulário de filtro
$texto_materia = isset($_POST['texto_materia']) ? $_POST['texto_materia'] : '';
$assunto_especifico = isset($_POST['assunto_especifico']) ? $_POST['assunto_especifico'] : '';
$assunto_geral = isset($_POST['assunto_geral']) ? $_POST['assunto_geral'] : '';
$status = isset($_POST['status']) ? $_POST['status'] : '';

$materias = getMateriasBoletim($pdo, $texto_materia, $assunto_especifico, $assunto_geral, $status);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <!-- Cabeçalho permanece o mesmo -->
</head>
<body>
    <div class="container">
        <h1>Consulta de Matérias para Boletim</h1>

        <!-- Filtro -->
        <form method="POST" action="consulta_materia1.php">
            <!-- Campos de texto para filtros -->
            <div class="form-row">
                <div class="form-group col-md-3">
                    <label for="texto_materia">Texto da Matéria</label>
                    <input type="text" class="form-control" id="texto_materia" name="texto_materia" placeholder="Texto da Matéria" value="<?php echo htmlspecialchars($texto_materia); ?>">
                </div>
                <div class="form-group col-md-3">
                    <label for="assunto_especifico">Assunto Específico</label>
                    <input type="text" class="form-control" id="assunto_especifico" name="assunto_especifico" placeholder="Assunto Específico" value="<?php echo htmlspecialchars($assunto_especifico); ?>">
                </div>
                <div class="form-group col-md-3">
                    <label for="assunto_geral">Assunto Geral</label>
                    <input type="text" class="form-control" id="assunto_geral" name="assunto_geral" placeholder="Assunto Geral" value="<?php echo htmlspecialchars($assunto_geral); ?>">
                </div>
            </div>

            <!-- Campo de seleção para status -->
            <div class="form-row">
                <div class="form-group col-md-4">
                    <label>Status da Matéria</label>
                    <select class="form-control" name="status">
                        <option value="">Todos</option>
                        <option value="Cadastrada" <?php echo ($status === 'Cadastrada') ? 'selected' : ''; ?>>Cadastrada</option>
                        <option value="Enviada" <?php echo ($status === 'Enviada') ? 'selected' : ''; ?>>Enviada</option>
                        <option value="Enviada e não recebida" <?php echo ($status === 'Enviada e não recebida') ? 'selected' : ''; ?>>Enviada e não recebida</option>
                        <option value="Recebida" <?php echo ($status === 'Recebida') ? 'selected' : ''; ?>>Recebida</option>
                        <option value="Recebida e publicada" <?php echo ($status === 'Recebida e publicada') ? 'selected' : ''; ?>>Recebida e publicada</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-md-2">
                    <button type="submit" class="btn btn-primary btn-block">Filtrar</button>
                </div>
                <div class="form-group col-md-2">
                    <button type="button" class="btn btn-secondary btn-block" onclick="resetForm()">Limpar</button>
                </div>
            </div>
        </form>

        <!-- Tabela de Resultados -->
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Texto da Matéria</th>
                    <th>Data</th>
                    <th>Assunto Específico</th>
                    <th>Assunto Geral</th>
                    <th>Status</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($materias): ?>
                    <?php foreach ($materias as $materia): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($materia['mate_bole_cod']); ?></td>
                            <td>
                                <span class="expand-toggle" onclick="toggleExpand(this)">+</span>
                                <div class="text-preview" onmouseover="showModal(this)" onmouseout="hideModal()" data-full-text="<?php echo htmlspecialchars_decode($materia['mate_bole_texto']); ?>">
                                    <?php echo htmlspecialchars_decode(mb_strimwidth($materia['mate_bole_texto'], 0, 20, '...')); ?>
                                </div>
                            </td>
                            <td><?php echo date('d-m-Y', strtotime($materia['mate_bole_data'])); ?></td>
                            <td><?php echo htmlspecialchars($materia['assu_espe_descricao']); ?></td>
                            <td><?php echo htmlspecialchars($materia['assu_gera_descricao']); ?></td>
                            <td><?php echo htmlspecialchars($materia['status']); ?></td>
                            <td class="actions">
                                <!-- Ações permanecem as mesmas -->
                                <a href="enviar_materia.php?mate_bole_cod=<?php echo htmlspecialchars($materia['mate_bole_cod']); ?>" 
                                   class="btn btn-info btn-sm" 
                                   onclick="return confirm('Tem certeza que deseja enviar esta matéria?');">Enviar</a>
                                <a href="cad_materia.php?mate_bole_cod=<?php echo htmlspecialchars($materia['mate_bole_cod']); ?>" 
                                   class="btn btn-warning btn-sm">Alterar</a>
                                <a href="excluir_materia.php?mate_bole_cod=<?php echo htmlspecialchars($materia['mate_bole_cod']); ?>" 
                                   class="btn btn-danger btn-sm" 
                                   onclick="return confirm('Tem certeza que deseja excluir esta matéria e os registros relacionados? Esta ação não pode ser desfeita.');">Excluir</a>
                                <button class="btn btn-primary btn-sm" onclick="openPdfModal('<?php echo htmlspecialchars($materia['mate_bole_cod']); ?>')">PDF</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center">Nenhuma matéria encontrada.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Modais e scripts permanecem os mesmos -->
</body>
</html>
