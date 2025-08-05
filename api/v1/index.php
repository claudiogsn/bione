<?php
header('Content-Type: application/json; charset=utf-8');

require_once 'controllers/OrderServiceController.php';
require_once 'controllers/ClienteController.php';
require_once 'controllers/EventController.php';
require_once 'controllers/MaterialController.php';
require_once 'controllers/UserController.php';
require_once 'controllers/FaturaController.php';
require_once 'controllers/MenuMobileController.php';
require_once 'controllers/PropostaController.php';

$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (isset($data['method']) && isset($data['data'])) {
    $method = $data['method'];
    $requestData = $data['data'];
    if (isset($data['token'])) {
        $requestToken = $data['token'];
    }

    $noAuthMethods = [
        'validateCPF',
        'validateCNPJ',
        'getOrderDetailsByControle',
        'getOrderDetailsByDocumento',
        'listMenus',
        'createMenu',
        'updateMenu',
        'deleteMenu',
        'getMenuById',
        'toggleMenuStatus',
        'createOrUpdateMenuPermission',
        'getPermissionsByMenu',
        'deleteMenuPermission',
        'getUserDetails',
        'getMenuMobile'
    ];


    if (!in_array($method, $noAuthMethods)) {
        if (!isset($requestToken)) {
            http_response_code(400);
            echo json_encode(['error' => 'Token ausente']);
            exit;
        }

        $userInfo = verifyToken($requestToken);
        $user = $userInfo['user'];
    }

    try {
        switch ($method) {
            // Métodos para UserController
            case 'getUserDetails':
                if (isset($requestData['user'])) {
                    $response = UserController::getUserDetails($requestData['user']);
                } else {
                    http_response_code(400);
                    $response = ['error' => 'Parâmetro user ausente'];
                }
                break;

            case 'getUsers':
                $response = UserController::getUsers();
                break;

            case 'getMenuMobile':
                if (isset($requestData['user_id'])) {
                    $response = UserController::getMenuMobile($requestData['user_id']);
                } else {
                    http_response_code(400);
                    $response = ['error' => 'Parâmetro user ausente'];
                }
                break;

            case 'getUserRoles':
                if (isset($requestData['user'])) {
                    $response = UserController::getUserRoles($requestData['user']);
                } else {
                    http_response_code(400);
                    $response = array('error' => 'Parâmetro user_id ausente');
                }
                break;
            // Métodos para MaterialController
            case 'createMaterial':
                $response = MaterialController::createMaterial($requestData);
                break;

            case 'updateMaterial':
                if (isset($requestData['id'])) {
                    $response = MaterialController::updateMaterial($requestData['id'], $requestData);
                } else {
                    throw new Exception("Campos obrigatórios: id e data.");
                }
                break;

            case 'deleteMaterial':
                if (isset($requestData['id'])) {
                    $response = MaterialController::deleteMaterial($requestData['id']);
                } else {
                    throw new Exception("Campo obrigatório: id.");
                }
                break;

            case 'getMaterialById':
                if (isset($requestData['id'])) {
                    $response = MaterialController::getMaterialById($requestData['id']);
                } else {
                    throw new Exception("Campo obrigatório: id.");
                }
                break;

                // Métodos para PatrimonioController
            case 'addPatrimonio':
                if (isset($requestData['material_id'])){
                    $response = MaterialController::addPatrimonio($requestData['material_id'],$requestData);
                } else {
                    throw new Exception("Campos obrigatórios: material_id e data.");
                }
                break;

            case 'updatePatrimonio':
                if (isset($requestData['id'])) {
                    $response = MaterialController::updatePatrimonio($requestData['id'], $requestData);
                } else {
                    throw new Exception("Campos obrigatórios: id e data.");
                }
                break;

            case 'deletePatrimonio':
                if (isset($requestData['id'])) {
                    $response = MaterialController::deletePatrimonio($requestData['id']);
                } else {
                    throw new Exception("Campo obrigatório: id.");
                }
                break;

            case 'listPatrimoniosByMaterial':
                if (isset($requestData['material_id'])) {
                    $response = MaterialController::listPatrimoniosByMaterial($requestData['material_id']);
                } else {
                    throw new Exception("Campo obrigatório: material_id.");
                }
                break;
            case 'verificaPatrimonioDisponivel':
                if (isset($requestData['patrimonio'])) {
                    $response = MaterialController::verificaPatrimonioDisponivel($requestData['patrimonio']);
                } else {
                    throw new Exception("Campo obrigatório: patrimonio.");
                }
                break;
            case 'listPatrimonios':
                    $response = MaterialController::listPatrimonios();
                break;

            case 'listPatrimoniosAgrupado':
                if (isset($requestData['material_id'])) {
                    $response = MaterialController::listPatrimoniosAgrupadosPorModelo($requestData['material_id']);
                } else {
                    throw new Exception("Campo obrigatório: material_id.");
                }
                break;

            case 'createCategoria':
                if (isset($requestData['nome'], $requestData['tipo'])) {
                    $response = MaterialController::createCategoria($requestData);
                } else {
                    throw new Exception("Campos obrigatórios: nome e tipo.");
                }
                break;

            case 'updateCategoria':
                if (isset($requestData['id'])) {
                    $response = MaterialController::updateCategoria($requestData['id'], $requestData);
                } else {
                    throw new Exception("Campos obrigatórios: id e data.");
                }
                break;

            case 'getCategoria':
                if (isset($requestData['id'])) {
                    $response = MaterialController::getCategoriaById($requestData['id']);
                } else {
                    throw new Exception("Campo obrigatório: id.");
                }
                break;

            case 'listCategorias':
                $response = MaterialController::listCategorias($requestData['tipo'] ?? null);
                break;
            case 'createFabricante':
                if (isset($requestData['nome'])) {
                    $response = MaterialController::createFabricante($requestData);
                } else {
                    throw new Exception("Campo obrigatório: nome.");
                }
                break;

            case 'updateFabricante':
                if (isset($requestData['id'])) {
                    $response = MaterialController::updateFabricante($requestData['id'], $requestData);
                } else {
                    throw new Exception("Campos obrigatórios: id e data.");
                }
                break;

            case 'getFabricante':
                if (isset($requestData['id'])) {
                    $response = MaterialController::getFabricanteById($requestData['id']);
                } else {
                    throw new Exception("Campo obrigatório: id.");
                }
                break;

            case 'deleteFabricante':
                if (isset($requestData['id'])) {
                    $response = MaterialController::deleteFabricante($requestData['id']);
                } else {
                    throw new Exception("Campo obrigatório: id.");
                }
                break;

            case 'listFabricantes':
                $response = MaterialController::listFabricantes();
                break;

            case 'createServico':
                if (isset($requestData['descricao'])) {
                    $response = MaterialController::createServico($requestData);
                } else {
                    throw new Exception("Campo obrigatório: descricao.");
                }
                break;

            case 'updateServico':
                if (isset($requestData['id'])) {
                    $response = MaterialController::updateServico($requestData['id'], $requestData);
                } else {
                    throw new Exception("Campos obrigatórios: id e data.");
                }
                break;

            case 'deleteServico':
                if (isset($requestData['id'])) {
                    $response = MaterialController::deleteServico($requestData['id']);
                } else {
                    throw new Exception("Campo obrigatório: id.");
                }
                break;

            case 'getServico':
                if (isset($requestData['id'])) {
                    $response = MaterialController::getServicoById($requestData['id']);
                } else {
                    throw new Exception("Campo obrigatório: id.");
                }
                break;
            case 'getPatrimonioById':
                if (isset($requestData['id'])) {
                    $response = MaterialController::getPatrimonioById($requestData['id']);
                } else {
                    throw new Exception("Campo obrigatório: id.");
                }
                break;

            case 'listServicos':
                $response = MaterialController::listServicos($requestData ?? []);
                break;


            case 'listMaterials':
                $response = MaterialController::listMaterials($requestData ?? []);
                break;

                // Métodos para OrderServiceController

            case 'createOrUpdateOrder':
                $response = OrderServiceController::createOrUpdateOrder($requestData);
                break;

            case 'createOrUpdateProposta':
                $response = PropostaController::createOrUpdateProposta($requestData);
                break;

            case 'getOrderDetailsByControle':
                if (isset($requestData['num_controle'])) {
                    $response = OrderServiceController::getOrderDetailsByControle($requestData['num_controle']);
                } else {
                    throw new Exception("Campo obrigatório: num_controle");
                }
                break;
            case 'getOrderDetailsByDocumento':
                if (isset($requestData['documento'])) {
                    $response = OrderServiceController::getOrderDetailsByDocumento($requestData['documento']);
                } else {
                    throw new Exception("Campo obrigatório: documento");
                }
                break;

            case 'getPropostaDetailsByDocumento':
                if (isset($requestData['documento'])) {
                    $response = PropostaController::getPropostaDetailsByDocumento($requestData['documento']);
                } else {
                    throw new Exception("Campo obrigatório: documento");
                }
                break;

            case 'generateOrdemServicoPdf':
                if (isset($requestData['documento'])) {
                    $response = OrderServiceController::generateOrdemServicoPdf($requestData['documento']);
                } else {
                    throw new Exception("Campo obrigatório: documento");
                }
                break;

            case 'generatePropostaPdf':
                if (isset($requestData['documento'])) {
                    $response = OrderServiceController::generatePropostaPdf($requestData['documento']);
                } else {
                    throw new Exception("Campo obrigatório: documento");
                }
                break;

            case 'getPropostaPdf':
                if (isset($requestData['documento'])) {
                    $response = PropostaController::getPropostaPdf($requestData['documento']);
                } else {
                    throw new Exception("Campo obrigatório: documento");
                }
                break;

            case 'listOrdersByEvento':
                if (isset($requestData['evento_id'])) {
                    $response = OrderServiceController::listOrdersByEvento($requestData['evento_id']);
                } else {
                    throw new Exception("Campo obrigatório: evento_id");
                }
                break;

            case 'generateQrBase64':
                if (isset($requestData['url'])) {
                    $response = OrderServiceController::generateQrBase64($requestData['url']);
                } else {
                    throw new Exception("Campo obrigatório: url");
                }
                break;
            case 'listOrdersByPeriodo':
                $response = OrderServiceController::listOrdersByPeriodo($requestData);
                break;
            case 'listMetodosPagamento':
                $response = OrderServiceController::listMetodosPagamento();
                break;
            case 'updateStatusOrderByDocumento':
                if (!empty($requestData['documento']) && isset($requestData['status'])) {
                    $response = OrderServiceController::updateStatusOrderByDocumento($requestData['documento'], $requestData['status']);
                } else {
                    http_response_code(400);
                    $response = ['success' => false, 'message' => 'Parâmetros obrigatórios ausentes.'];
                }
                break;


            // Métodos para ClienteController
            case 'createCliente':
                $response = ClienteController::createCliente($requestData);
                break;
            case 'updateCliente':
                if (isset($requestData['id'])) {
                    $response = ClienteController::updateCliente($requestData['id'], $requestData);
                } else {
                    http_response_code(400);
                    $response = array('error' => 'Parâmetro id ausente');
                }
                break;
            case 'getClienteById':
                if (isset($requestData['id'])) {
                    $response = ClienteController::getClienteById($requestData['id']);
                } else {
                    http_response_code(400);
                    $response = array('error' => 'Parâmetro id ausente');
                }
                break;
            case 'listClients':
                $response = ClienteController::listClients();
                break;

            // Métodos para EventController
            case 'createEvent':
                $response = EventController::createEvent($requestData);
                break;
            case 'updateEvent':
                if (isset($requestData['id']) && isset($requestData)) {
                    $response = EventController::updateEvent($requestData['id'], $requestData);
                } else {
                    http_response_code(400);
                    $response = array('error' => 'Parâmetros id ou data ausentes');
                }
                break;
            case 'getEventById':
                if (isset($requestData['id'])) {
                    $response = EventController::getEventById($requestData['id']);
                } else {
                    http_response_code(400);
                    $response = array('error' => 'Parâmetro id ausente');
                }
                break;
            case 'deleteEvent':
                if (isset($requestData['id'])) {
                    $response = EventController::deleteEvent($requestData['id']);
                } else {
                    http_response_code(400);
                    $response = array('error' => 'Parâmetro id ausente');
                }
                break;
            case 'listEvents':
                $response = EventController::listEvents($requestData);
                break;

            case 'validateCPF':
                if (isset($requestData['cpf'])) {
                    $response = ClienteController::validateCPF($requestData['cpf']);
                } else {
                    http_response_code(400);
                    $response = array('error' => 'Parâmetro cpf ausente');
                }
                break;
            case 'validateCNPJ':
                if (isset($requestData['cnpj'])) {
                    $response = ClienteController::validateCNPJ($requestData['cnpj']);
                } else {
                    http_response_code(400);
                    $response = array('error' => 'Parâmetro cnpj ausente');
                }
                break;

            case 'getFatura':
                if (isset($requestData['documento_os'])) {
                    $response = FaturaController::getFatura($requestData['documento_os']);
                } else {
                    throw new Exception("Campo obrigatório: documento_os");
                }
                break;

            case 'createFatura':
                if (!empty($requestData)) {
                    $response = FaturaController::createFatura($requestData);
                } else {
                    throw new Exception("Dados da fatura não enviados");
                }
                break;

            case 'updateFaturaStatus':
                if (isset($requestData['documento_os']) && isset($requestData['status'])) {
                    $response = FaturaController::updateFaturaStatus($requestData['documento_os'], $requestData['status']);
                } else {
                    throw new Exception("Campos obrigatórios: documento_os e status");
                }
                break;

            case 'generateFaturaByOrder':
                if (isset($requestData['documento'])) {
                    $response = FaturaController::generateFaturaByOrder($requestData['documento']);
                } else {
                    throw new Exception("Campo obrigatório: documento");
                }
                break;

            case 'generateFaturaPdf':
                if (isset($requestData['documento'])) {
                    $response = FaturaController::generateFaturaPdf($requestData['documento']);
                } else {
                    throw new Exception("Campo obrigatório: documento_os");
                }
                break;

            case 'listMenus':
                $response = MenuMobileController::listMenus();
                break;

            case 'createMenu':
                $response = MenuMobileController::createMenu($requestData);
                break;

            case 'updateMenu':
                $response = MenuMobileController::updateMenu($requestData);
                break;

            case 'deleteMenu':
                if (isset($requestData['id'])) {
                    $response = MenuMobileController::deleteMenu($requestData['id']);
                } else {
                    $response = ['success' => false, 'message' => 'ID não informado'];
                }
                break;

            case 'getMenuById':
                if (isset($requestData['id'])) {
                    $response = MenuMobileController::getMenuById($requestData['id']);
                } else {
                    $response = ['success' => false, 'message' => 'ID não informado'];
                }
                break;
            case 'toggleMenuStatus':
                if (isset($requestData['id'])) {
                    $response = MenuMobileController::toggleStatus($requestData['id']);
                } else {
                    $response = ["success" => false, "message" => "ID não informado."];
                }
                break;
            case 'createOrUpdateMenuPermission':
                $response = MenuMobileController::createOrUpdateMenuPermission($requestData);
                break;

            case 'getPermissionsByMenu':
                if (isset($requestData['menu_id'])) {
                    $response = MenuMobileController::getPermissionsByMenu($requestData['menu_id']);
                } else {
                    $response = ["success" => false, "message" => "ID do menu não informado."];
                }
                break;

            case 'deleteMenuPermission':
                if (isset($requestData['id'])) {
                    $response = MenuMobileController::deleteMenuPermission($requestData['id']);
                } else {
                    $response = ["success" => false, "message" => "ID do menu não informado."];
                }
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

// Função de verificação do token
function verifyToken($token) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM system_access_log WHERE sessionid = :sessionid");
    $stmt->bindParam(':sessionid', $token, PDO::PARAM_STR);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        if ($result['logout_time'] == "0000-00-00 00:00:00") {
            if ($result['impersonated'] == 'S') {
                return ['user' => $result['impersonated_by']];
            } else {
                return ['user' => $result['login']];
            }
        } else {
            http_response_code(401);
            echo json_encode(['error' => 'Sessão expirada']);
            exit;
        }
    } else {
        http_response_code(401);
        echo json_encode(['error' => 'Usuário não encontrado']);
        exit;
    }
}
?>
