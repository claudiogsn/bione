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
    $stmt = db()->prepare("DELETE FROM event_schedule WHERE id = :id");
    $stmt->execute([':id' => $id]);
    json_response(['ok' => true]);
} catch (Throwable $e) {
    json_response(['ok' => false, 'error' => 'Erro ao excluir item'], 500);
}
