<?php
require_once __DIR__ . '/../../config/db.php';

try {
    $stmt = db()->query("
        SELECT q.id, q.participant_name, q.question,
               UNIX_TIMESTAMP(ps.updated_at) AS version
        FROM panel_state ps
        LEFT JOIN event_questions q ON q.id = ps.active_question_id
        WHERE ps.id = 1
        LIMIT 1
    ");
    $row = $stmt->fetch();

    if (!$row || !$row['id']) {
        // Ainda retorna a versão do state (para o painel saber quando limpar)
        $v = db()->query("SELECT UNIX_TIMESTAMP(updated_at) AS version FROM panel_state WHERE id = 1")->fetch();
        json_response([
            'ok'       => true,
            'active'   => false,
            'version'  => $v ? (int)$v['version'] : 0,
            'question' => null,
        ]);
    }

    json_response([
        'ok'      => true,
        'active'  => true,
        'version' => (int)$row['version'],
        'question' => [
            'id'               => (int)$row['id'],
            'participant_name' => $row['participant_name'],
            'question'         => $row['question'],
        ],
    ]);
} catch (Throwable $e) {
    json_response(['ok' => false, 'error' => 'Erro ao buscar pergunta ativa'], 500);
}
