<?php

header('Content-Type: application/json');
require_once 'functions.php';

// Função para obter todas as campanhas do banco de dados
function getAllCampaigns() {
    global $pdo;
    
    $stmt = $pdo->query("SELECT * FROM campanha");
    $campaigns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return $campaigns;
}

// Manipulação da solicitação GET
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $campaigns = getAllCampaigns();
    echo json_encode($campaigns);
} else {
    http_response_code(405);
    echo json_encode(array("message" => "Método não permitido."));
}
?>
