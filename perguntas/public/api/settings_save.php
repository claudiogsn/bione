<?php
require_once __DIR__ . '/../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'error' => 'Método inválido'], 405);
}

$in = json_input();
$bg   = trim($in['background_color'] ?? '');
$font = trim($in['font_color']       ?? '');

// Validação simples de cor (hex #RGB ou #RRGGBB)
$hex = '/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/';
if (!preg_match($hex, $bg) || !preg_match($hex, $font)) {
    json_response(['ok' => false, 'error' => 'Cor inválida (use formato hex)'], 422);
}

try {
    $stmt = db()->prepare("
        UPDATE panel_settings
        SET background_color = :bg, font_color = :fc
        WHERE id = 1
    ");
    $stmt->execute([':bg' => $bg, ':fc' => $font]);
    json_response(['ok' => true]);
} catch (Throwable $e) {
    json_response(['ok' => false, 'error' => 'Erro ao salvar configurações'], 500);
}
