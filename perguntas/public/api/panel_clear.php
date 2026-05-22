<?php
require_once __DIR__ . '/../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'error' => 'Método inválido'], 405);
}

try {
    db()->prepare("UPDATE panel_state SET active_question_id = NULL WHERE id = 1")->execute();
    json_response(['ok' => true]);
} catch (Throwable $e) {
    json_response(['ok' => false, 'error' => 'Erro ao limpar painel'], 500);
}
