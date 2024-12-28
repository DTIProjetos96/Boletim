<?php
// ajax/list_materias_sem_boletim.php
require_once '../db.php'; // Caminho relativo para o arquivo db.php

$bole_cod = isset($_GET['bole_cod']) ? intval($_GET['bole_cod']) : 0;

if ($bole_cod <= 0) {
    echo "Código do boletim inválido.";
    exit;
}

try {
    $sql = "
        SELECT 
            mb.mate_bole_cod, 
            mb.mate_bole_texto, 
            mb.mate_bole_data
        FROM bg.materia_boletim mb
        LEFT JOIN bg.materia_publicacao mp ON mb.mate_bole_cod = mp.fk_mate_bole_cod
        WHERE mp.fk_mate_bole_cod IS NULL
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $materias = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Erro ao buscar matérias: " . $e->getMessage();
    exit;
}

if (empty($materias)) {
    echo "<p>Não há matérias disponíveis para adicionar.</p>";
    exit;
}

echo '<table class="table table-striped">';
echo '<thead><tr><th>Código</th><th>Texto</th><th>Data</th><th>Ação</th></tr></thead>';
echo '<tbody>';

foreach ($materias as $materia) {
    echo '<tr>';
    echo '<td>' . htmlspecialchars($materia['mate_bole_cod'], ENT_QUOTES, 'UTF-8') . '</td>';
    echo '<td>' . htmlspecialchars($materia['mate_bole_texto'], ENT_QUOTES, 'UTF-8') . '</td>';
    echo '<td>' . htmlspecialchars(date("d/m/Y", strtotime($materia['mate_bole_data'])), ENT_QUOTES, 'UTF-8') . '</td>';
    echo '<td><button class="btn btn-info btn-sm adicionar-materia" data-mate-cod="' . htmlspecialchars($materia['mate_bole_cod'], ENT_QUOTES, 'UTF-8') . '">Adicionar</button></td>';
    echo '</tr>';
}

echo '</tbody>';
echo '</table>';
?>
