<?php

$response = [
    'campanha' => 'Título da Campanha',
    'message' => 'Este é o texto dos termos, condições e políticas da campanha.',
];

header('Content-Type: application/json');

echo json_encode($response);
?>
