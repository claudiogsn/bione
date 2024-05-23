<?php
header('Content-Type: application/json');
require_once 'config.php';
require_once 'functions.php';

function handlePOSTRequest() {
    $data = json_decode(file_get_contents('php://input'));

    if (!$data) {
        return response(400, 'Nenhuma entrada fornecida.');
    }

    $cpf = $data->cpf;

    if (!$cpf) {
        return response(400, 'CPF não fornecido');
    }

    if (!validaCPF($cpf)) {
        return response(400, 'CPF inválido');
    }

    if (checkExistingCPF($cpf)) {
        return response(400, 'CPF já cadastrado');
    }

    return response(200, 'CPF válido e disponível para cadastro');
}

function response($code, $message) {
    http_response_code($code);
    echo json_encode(array('message' => $message));
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    handlePOSTRequest();
} else {
    response(405, 'Método de requisição inválido');
}

?>
