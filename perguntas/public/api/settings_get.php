<?php
require_once __DIR__ . '/../../config/db.php';

try {
    $stmt = db()->query("SELECT background_color, font_color FROM panel_settings WHERE id = 1");
    $row = $stmt->fetch();
    if (!$row) {
        $row = ['background_color' => '#0f172a', 'font_color' => '#ffffff'];
    }
    json_response(['ok' => true, 'data' => $row]);
} catch (Throwable $e) {
    json_response(['ok' => false, 'error' => 'Erro ao buscar configurações'], 500);
}
