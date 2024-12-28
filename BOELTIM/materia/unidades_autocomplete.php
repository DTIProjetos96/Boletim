<?php
include '../db.php';

if (isset($_POST['term'])) {
    $term = $_POST['term'];

    try {
        $stmt = $pdo->prepare("SELECT DISTINCT unidade FROM vw_policiais_militares WHERE unidade ILIKE :term ORDER BY unidade");
        $stmt->execute(['term' => '%' . $term . '%']);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Extrair apenas as unidades para o autocomplete
        $unidades = [];
        foreach ($result as $row) {
            $unidades[] = $row['unidade'];
        }

        echo json_encode($unidades);
    } catch (PDOException $e) {
        echo json_encode([]);
    }
}
?>
