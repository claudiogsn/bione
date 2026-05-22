<?php
require_once __DIR__ . '/../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'error' => 'Método inválido'], 405);
}

$in = json_input();

$name     = trim($in['participant_name'] ?? '');
$question = trim($in['question']         ?? '');

if ($name === '' || $question === '') {
    json_response(['ok' => false, 'error' => 'Nome e pergunta são obrigatórios'], 422);
}

if (mb_strlen($name) > 120) {
    json_response(['ok' => false, 'error' => 'Nome muito longo'], 422);
}

if (mb_strlen($question) > 2000) {
    json_response(['ok' => false, 'error' => 'Pergunta muito longa'], 422);
}

try {
    $stmt = db()->prepare("
        INSERT INTO event_questions (participant_name, question, status)
        VALUES (:name, :q, 'pending')
    ");
    $stmt->execute([':name' => $name, ':q' => $question]);

    json_response(['ok' => true, 'id' => (int)db()->lastInsertId()]);
} catch (Throwable $e) {
    json_response(['ok' => false, 'error' => 'Erro ao enviar pergunta'], 500);
}
