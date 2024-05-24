<?php
header('Content-Type: application/json; charset=utf-8');

require_once 'controllers/OrderController.php';

$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (isset($data['method']) && isset($data['data'])) {
    $method = $data['method'];
    $requestData = $data['data'];

    //print_r($requestData);
   // exit;

    try {
        switch ($method) {
            // Métodos para OrderController
            case 'createOrder':
                $response = OrderController::createOrder($requestData);
                break;
            case 'updateOrder':
                if (isset($requestData['id']) && isset($requestData)) {
                    $response = OrderController::updateOrder($requestData['id'], $requestData);
                } else {
                    http_response_code(400);
                    $response = array('error' => 'Parâmetros order_id ou data ausentes');
                }
                break;

            
            case 'createOrderItem':
                $response = OrderController::createOrderItem($requestData);
                break;

            case 'updateOrderItem':
                if (isset($requestData['id'])) {
                    $response = OrderController::updateOrderItem($requestData['id'], $requestData);
                } else {
                    http_response_code(400);
                    $response = array('error' => 'Parâmetro num_controle ausente');
                }
                break;

            case 'createOrderPayment':
                $response = OrderController::createOrderPayment($requestData);
                break;

            case 'updateOrderPayment':
                if (isset($requestData['id'])) {
                    $response = OrderController::updateOrderPayment($requestData['id'], $requestData);
                } else {
                    http_response_code(400);
                    $response = array('error' => 'Parâmetro num_controle ausente');
                }
                break;

            case 'createOrderService':
                $response = OrderController::createOrderService($requestData);
                break;

            case 'updateOrderService':
                if (isset($requestData['id']) && isset($requestData)) {
                    $response = OrderController::updateOrderService($requestData['id'], $requestData);
                } else {
                    http_response_code(400);
                    $response = array('error' => 'Parâmetros num_controle ou data ausentes');
                }
                break;
            
            case 'getOrderDetails':
                if (isset($requestData['order_id'])) {
                    $response = OrderController::getOrderDetails($requestData['order_id']);
                } else {
                    http_response_code(400);
                    $response = array('error' => 'Parâmetro order_id ausente');
                }
                break;
            
            case 'listOrders':
                $response = OrderController::listOrders();
                break;
            
            default:
                http_response_code(405);
                $response = array('error' => 'Método não suportado');
                break;
        }

        header('Content-Type: application/json');
        echo json_encode($response);
    } catch (Exception $e) {
        http_response_code(500);
        $response = array('error' => 'Erro interno do servidor: ' . $e->getMessage());
        echo json_encode($response);
    }
} else {
    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode(array('error' => 'Parâmetros inválidos'));
}
?>
