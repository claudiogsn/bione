<?php
require_once __DIR__ . '/../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'error' => 'Método inválido'], 405);
}

$in = json_input();

$id          = isset($in['id']) ? (int)$in['id'] : 0;
$title       = trim($in['title']       ?? '');
$description = trim($in['description'] ?? '');
$startTime   = trim($in['start_time']  ?? '');
$endTime     = trim($in['end_time']    ?? '');
$sortOrder   = isset($in['sort_order']) ? (int)$in['sort_order'] : 0;

if ($title === '' || $startTime === '') {
    json_response(['ok' => false, 'error' => 'Título e horário de início são obrigatórios'], 422);
}

try {
    if ($id > 0) {
        $stmt = db()->prepare("
            UPDATE event_schedule
            SET title=:title, description=:desc, start_time=:st, end_time=:et, sort_order=:so
            WHERE id=:id
        ");
        $stmt->execute([
            ':title' => $title,
            ':desc'  => $description ?: null,
            ':st'    => $startTime,
            ':et'    => $endTime ?: null,
            ':so'    => $sortOrder,
            ':id'    => $id,
        ]);
    } else {
        $stmt = db()->prepare("
            INSERT INTO event_schedule (title, description, start_time, end_time, sort_order)
            VALUES (:title, :desc, :st, :et, :so)
        ");
        $stmt->execute([
            ':title' => $title,
            ':desc'  => $description ?: null,
            ':st'    => $startTime,
            ':et'    => $endTime ?: null,
            ':so'    => $sortOrder,
        ]);
        $id = (int)db()->lastInsertId();
    }

    json_response(['ok' => true, 'id' => $id]);
} catch (Throwable $e) {
    json_response(['ok' => false, 'error' => 'Erro ao salvar item'], 500);
}
