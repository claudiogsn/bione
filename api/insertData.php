<?php

header('Content-Type: application/json');
require_once 'functions.php';

function handlePOSTRequest() {
    $data = json_decode(file_get_contents("php://input"), true);

    if (!validateData($data)) {
        return response(400, "Dados inválidos.");
    }

    return registerClient($data);
}

function response($code, $message) {
    http_response_code($code);
    echo json_encode(array("message" => $message));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = handlePOSTRequest();
    echo json_encode($response);
} else {
    response(405, "Método não permitido.");
}
?>
