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

    // Se a pergunta estiver ativa no painel, limpa o painel antes
    $pdo->prepare("
        UPDATE panel_state SET active_question_id = NULL
        WHERE id = 1 AND active_question_id = :id
    ")->execute([':id' => $id]);

    // Arquiva (em vez de excluir de fato)
    $pdo->prepare("UPDATE event_questions SET status = 'archived' WHERE id = :id")
        ->execute([':id' => $id]);

    $pdo->commit();
    json_response(['ok' => true]);
} catch (Throwable $e) {
    if (db()->inTransaction()) db()->rollBack();
    json_response(['ok' => false, 'error' => 'Erro ao remover pergunta'], 500);
}
