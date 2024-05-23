<?php

header('Content-Type: application/json');
require_once 'functions.php';

// Função para obter vouchers do banco de dados com base no CPF e código, ou todos se não forem fornecidos
function getVouchersByCPFAndCode($cpf = null, $code = null) {
    global $pdo;

    // Se ambos CPF e código forem fornecidos, adicionamos condições à consulta SQL
    if ($cpf !== null && $code !== null) {
        $sql = "SELECT * FROM voucher WHERE cpf_cliente = :cpf AND codigo = :code";
        $params = array(':cpf' => $cpf, ':code' => $code);
    } elseif ($cpf !== null) {
        // Se apenas o CPF foi fornecido, consulta pelo CPF
        $sql = "SELECT * FROM voucher WHERE cpf_cliente = :cpf";
        $params = array(':cpf' => $cpf);
    } elseif ($code !== null) {
        // Se apenas o código foi fornecido, consulta pelo código
        $sql = "SELECT * FROM voucher WHERE codigo = :code";
        $params = array(':code' => $code);
    } else {
        $sql = "SELECT * FROM voucher";
        $params = array();
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $vouchers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    return $vouchers;
}

// Manipulação da solicitação POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtenha os dados JSON do corpo da solicitação
    $json_data = file_get_contents('php://input');
    // Decodifique os dados JSON em um array associativo
    $data = json_decode($json_data, true);
    

    // Extrai CPF e código do array associativo
    $cpf = isset($data['cpf']) ? $data['cpf'] : null;
    $code = isset($data['code']) ? $data['code'] : null;
    
    // Chama a função para obter vouchers com base no CPF e código
    $vouchers = getVouchersByCPFAndCode($cpf, $code);
    echo json_encode($vouchers);
} else {
    http_response_code(405);
    echo json_encode(array("message" => "Método não permitido."));
}
