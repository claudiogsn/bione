<?php
require_once __DIR__ . '/../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'error' => 'Método inválido'], 405);
}

$in = json_input();
$id = isset($in['id']) ? (int)$in['id'] : 0;

if ($id <= 0) {
    json_response(['ok' => false, 'error' => 'ID inválido'], 422);
}

try {
    $pdo = db();
    $pdo->beginTransaction();

    // Valida existência
    $stmt = $pdo->prepare("SELECT id FROM event_questions WHERE id = :id AND status <> 'archived'");
    $stmt->execute([':id' => $id]);
    if (!$stmt->fetch()) {
        $pdo->rollBack();
        json_response(['ok' => false, 'error' => 'Pergunta não encontrada'], 404);
    }

    // Atualiza o estado do painel
    $pdo->prepare("UPDATE panel_state SET active_question_id = :id WHERE id = 1")
        ->execute([':id' => $id]);

    // Marca a pergunta como exibida
    $pdo->prepare("UPDATE event_questions SET status = 'shown' WHERE id = :id")
        ->execute([':id' => $id]);

    $pdo->commit();
    json_response(['ok' => true]);
} catch (Throwable $e) {
    if (db()->inTransaction()) db()->rollBack();
    json_response(['ok' => false, 'error' => 'Erro ao definir pergunta ativa'], 500);
}
