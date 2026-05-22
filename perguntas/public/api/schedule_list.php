<?php
require_once __DIR__ . '/../../config/db.php';

try {
    $stmt = db()->query("
        SELECT id, title, description,
               TIME_FORMAT(start_time, '%H:%i') AS start_time,
               TIME_FORMAT(end_time,   '%H:%i') AS end_time,
               sort_order
        FROM event_schedule
        ORDER BY sort_order ASC, start_time ASC
    ");
    json_response(['ok' => true, 'data' => $stmt->fetchAll()]);
} catch (Throwable $e) {
    json_response(['ok' => false, 'error' => 'Erro ao listar cronograma'], 500);
}
