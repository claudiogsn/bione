<?php

header('Content-Type: application/json');
require_once 'functions.php';

// Função para obter todos os clientes do banco de dados
function getAllClients() {
    global $pdo;
    
    $stmt = $pdo->query("SELECT * FROM cliente");
    $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return $clients;
}

// Manipulação da solicitação GET
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $clients = getAllClients();
    echo json_encode($clients);
} else {
    http_response_code(405);
    echo json_encode(array("message" => "Método não permitido."));
}
?>
