<?php
require_once __DIR__ . '/../../config/db.php';

try {
    $stmt = db()->query("
        SELECT q.id, q.participant_name, q.question, q.status,
               DATE_FORMAT(q.created_at, '%d/%m/%Y %H:%i') AS created_at,
               (ps.active_question_id = q.id) AS is_active
        FROM event_questions q
        CROSS JOIN panel_state ps
        WHERE ps.id = 1
          AND q.status <> 'archived'
        ORDER BY q.created_at DESC
        LIMIT 500
    ");
    $rows = $stmt->fetchAll();

    // Normaliza is_active para boolean
    foreach ($rows as &$r) {
        $r['is_active'] = (bool)$r['is_active'];
    }

    json_response(['ok' => true, 'data' => $rows]);
} catch (Throwable $e) {
    json_response(['ok' => false, 'error' => 'Erro ao listar perguntas'], 500);
}
