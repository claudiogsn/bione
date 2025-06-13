<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../database/db.php';

class OrderController {
    
    // Função para criar uma nova ordem de serviço
    public static function createOrder($data) {
        global $pdo;

        $evento_id = $data['evento_id'];
        $cliente_id = $data['cliente_id'];
        $data_montagem = $data['data_montagem'];
        $data_recolhimento = $data['data_recolhimento'];
        $status = 1; // Status padrão (1 = "Ativo"? Ajuste conforme sua regra)
        $contato_montagem = $data['contato_montagem'];
        $local_montagem = $data['local_montagem'];
        $endereco = $data['endereco'];

        try {
            // Inicia transação
            $pdo->beginTransaction();

            // Insere a ordem (SEM documento e num_controle inicialmente)
            $stmt = $pdo->prepare("
            INSERT INTO orders 
            (evento_id, cliente_id, data_montagem, data_recolhimento, status, contato_montagem, local_montagem, endereco) 
            VALUES 
            (:evento_id, :cliente_id, :data_montagem, :data_recolhimento, :status, :contato_montagem, :local_montagem, :endereco)
        ");
            $stmt->execute([
                ':evento_id' => $evento_id,
                ':cliente_id' => $cliente_id,
                ':data_montagem' => $data_montagem,
                ':data_recolhimento' => $data_recolhimento,
                ':status' => $status,
                ':contato_montagem' => $contato_montagem,
                ':local_montagem' => $local_montagem,
                ':endereco' => $endereco
            ]);

            // Obtém o ID da nova ordem
            $order_id = $pdo->lastInsertId();

            // Gera o documento (formato: OS-505013)
            $documento = 'OS-' .
                str_pad($cliente_id, 2, '0', STR_PAD_LEFT) .
                str_pad($evento_id, 2, '0', STR_PAD_LEFT) .
                str_pad($order_id, 2, '0', STR_PAD_LEFT);

            // Atualiza a ordem com num_controle e documento
            $updateStmt = $pdo->prepare("
            UPDATE orders 
            SET 
                num_controle = :order_id,
                documento = :documento
            WHERE id = :order_id
        ");
            $updateStmt->execute([
                ':order_id' => $order_id,
                ':documento' => $documento
            ]);

            // Confirma a transação
            $pdo->commit();

            return [
                'success' => true,
                'message' => 'Ordem criada com sucesso',
                'order' => self::getOrderById($order_id) // Retorna os dados completos
            ];

        } catch (PDOException $e) {
            $pdo->rollBack();
            return [
                'success' => false,
                'message' => 'Erro ao criar ordem: ' . $e->getMessage()
            ];
        }
    }
    public static function getOrderById($id) {
        global $pdo; 
    
        // Prepara e executa a consulta SQL para obter os detalhes de uma ordem de serviço pelo seu ID
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
    
        // Retorna os detalhes da ordem de serviço encontrada
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Função para obter detalhes de uma ordem de serviço
    public static function getOrderDetails($order_id) {
        global $pdo; 
    
        // Array para armazenar os resultados
        $orderDetails = array();
    
        // Consulta para obter detalhes do pedido
        $stmtOrder = $pdo->prepare("SELECT * FROM orders WHERE id = :id");
        $stmtOrder->bindParam(':id', $order_id, PDO::PARAM_INT);
        $stmtOrder->execute();
        $orderDetails['order'] = $stmtOrder->fetch(PDO::FETCH_ASSOC);
    
        // Consulta para obter detalhes do cliente
        $stmtCliente = $pdo->prepare("SELECT * FROM cliente WHERE id = :id");
        $stmtCliente->bindParam(':id', $orderDetails['order']['cliente_id'], PDO::PARAM_INT);
        $stmtCliente->execute();
        $orderDetails['cliente'] = $stmtCliente->fetch(PDO::FETCH_ASSOC);
    
        // Consulta para obter detalhes do evento
        $stmtEvento = $pdo->prepare("SELECT * FROM evento WHERE id = :id");
        $stmtEvento->bindParam(':id', $orderDetails['order']['evento_id'], PDO::PARAM_INT);
        $stmtEvento->execute();
        $orderDetails['evento'] = $stmtEvento->fetch(PDO::FETCH_ASSOC);
    
        // Consulta para obter itens do pedido
        $stmtItems = $pdo->prepare("SELECT * FROM order_itens WHERE num_controle = :num_controle");
        $stmtItems->bindParam(':num_controle', $order_id, PDO::PARAM_STR);
        $stmtItems->execute();
        $orderDetails['itens'] = $stmtItems->fetchAll(PDO::FETCH_ASSOC);
    
        // Consulta para obter serviços do pedido
        $stmtServices = $pdo->prepare("SELECT * FROM order_services WHERE num_controle = :num_controle");
        $stmtServices->bindParam(':num_controle', $order_id, PDO::PARAM_STR);
        $stmtServices->execute();
        $orderDetails['services'] = $stmtServices->fetchAll(PDO::FETCH_ASSOC);
    
        // Consulta para obter pagamentos do pedido
        $stmtPayments = $pdo->prepare("SELECT * FROM order_pagamentos WHERE num_controle = :num_controle");
        $stmtPayments->bindParam(':num_controle', $order_id, PDO::PARAM_STR);
        $stmtPayments->execute();
        $orderDetails['payments'] = $stmtPayments->fetchAll(PDO::FETCH_ASSOC);
    
        // Retorna os detalhes do pedido
        return $orderDetails;
    }

    // Função para atualizar os detalhes de uma ordem de serviço
    public static function updateOrder($id, $data) {
        global $pdo; 

        $sql = "UPDATE orders SET ";
        $values = [];
        foreach ($data as $key => $value) {
            $sql .= "$key = ?, ";
            $values[] = $value;
        }
        $sql = rtrim($sql, ", "); // Remove a vírgula e o espaço em branco extra no final da string SQL
        $sql .= " WHERE id = ?";
        $values[] = $id;

        // Prepara e executa a consulta SQL para atualizar os detalhes da ordem de serviço
        $stmt = $pdo->prepare($sql);
        $stmt->execute($values);

        if ($stmt->rowCount() > 0) {
            return array('success' => true, 'message' => 'Detalhes da ordem de serviço atualizados com sucesso');
        } else {
            return array('success' => false, 'message' => 'Falha ao atualizar detalhes da ordem de serviço');
        }
    }

    // Função para listar todas as ordens de serviço
    public static function listOrders($evento_id) {
        global $pdo;

        // Prepara e executa a consulta SQL para listar todas as ordens de serviço
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE evento_id = :evento_id");
        $stmt->bindParam(':evento_id', $evento_id, PDO::PARAM_INT);
        $stmt->execute();

        // Retorna os detalhes da ordem de serviço encontrada
        return $stmt->fetchAll(PDO::FETCH_ASSOC); // Use fetchAll() se esperar múltiplos resultados
    }

    public static function createOrderItem($data) {
        global $pdo;

        // Preparar a consulta SQL para inserir um novo item de pedido
        $sql = "INSERT INTO order_itens (evento_id, cliente_id, num_controle, material_id, valor, custo, dias_uso, data_inicial, status,quantidade) VALUES (:evento_id, :cliente_id, :num_controle, :material_id, :valor, :custo, :dias_uso, :data_inicial, :status,:quantidade)";

        // Preparar e executar a declaração PDO
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':evento_id' => $data['evento_id'],
            ':cliente_id' => $data['cliente_id'],
            ':num_controle' => $data['num_controle'],
            ':material_id' => $data['material_id'],
            ':valor' => $data['valor'],
            ':custo' => $data['custo'],
            ':dias_uso' => $data['dias_uso'],
            ':data_inicial' => $data['data_inicial'],
            ':status' => $data['status'],
            ':quantidade' => $data['quantidade']
        ]);

        // Verificar se a inserção foi bem-sucedida e retornar o ID do novo item de pedido
        if ($stmt->rowCount() > 0) {
            return array('success' => true, 'message' => 'Item de pedido criado com sucesso', 'order_item_id' => $pdo->lastInsertId());
        } else {
            return array('error' => 'Falha ao criar item de pedido');
        }
    }

    public static function updateOrderItem($id,$data) {
        global $pdo;

        $sql = "UPDATE order_itens SET ";
        $values = [];
        foreach ($data as $key => $value) {
            $sql .= "$key = ?, ";
            $values[] = $value;
        }
        $sql = rtrim($sql, ", "); // Remove a vírgula e o espaço em branco extra no final da string SQL
        $sql .= " WHERE id = ?";
        $values[] = $id;

        // Preparar e executar a declaração PDO
        $stmt = $pdo->prepare($sql);
        $stmt->execute($values);

        // Verificar se a atualização foi bem-sucedida
        if ($stmt->rowCount() > 0) {
            return array('success' => true, 'message' => 'Item de pedido atualizado com sucesso');
        } else {
            return array('error' => 'Falha ao atualizar item de pedido');
        }
    }

    public static function createOrderPayment($data) {
        global $pdo;

        // Preparar a consulta SQL para inserir um novo pagamento de pedido
        $sql = "INSERT INTO order_pagamentos (evento_id, cliente_id, num_controle, forma_pg, valor_pg, data_prog, data_pg, status) VALUES (:evento_id, :cliente_id, :num_controle, :forma_pg, :valor_pg, :data_prog, :data_pg, :status)";

        // Preparar e executar a declaração PDO
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':evento_id' => $data['evento_id'],
            ':cliente_id' => $data['cliente_id'],
            ':num_controle' => $data['num_controle'],
            ':forma_pg' => $data['forma_pg'],
            ':valor_pg' => $data['valor_pg'],
            ':data_prog' => $data['data_prog'],
            ':data_pg' => null,
            ':status' => "Pendente"
        ]);

        // Verificar se a inserção foi bem-sucedida
        if ($stmt->rowCount() > 0) {
            return array('success' => true, 'message' => 'Pagamento de pedido criado com sucesso','order_payment_id' => $pdo->lastInsertId());
        } else {
            return array('error' => 'Falha ao criar pagamento de pedido');
        }
    }

    public static function updateOrderPayment($id,$data) {
        global $pdo;

        // Preparar a consulta SQL para atualizar um serviço de pedido com base no num_controle
        $sql = "UPDATE order_pagamentos SET ";
        $values = [];
        foreach ($data as $key => $value) {
            $sql .= "$key = ?, ";
            $values[] = $value;
        }
        $sql = rtrim($sql, ", "); // Remove a vírgula e o espaço em branco extra no final da string SQL
        $sql .= " WHERE id = ?";
        $values[] = $id;

        // Preparar e executar a declaração PDO
        $stmt = $pdo->prepare($sql);
        $stmt->execute($values);

        // Verificar se a atualização foi bem-sucedida
        if ($stmt->rowCount() > 0) {
            return array('success' => true, 'message' => 'Pagamento de pedido atualizado com sucesso');
        } else {
            return array('error' => 'Falha ao atualizar pagamento de pedido');
        }
    }

    public static function createOrderService($data) {
        global $pdo;

        // Preparar a consulta SQL para inserir um novo serviço de pedido
        $sql = "INSERT INTO order_services (evento_id, cliente_id, num_controle, servico_id, valor, custo, dias_uso, data_inicial, status,quantidade) VALUES (:evento_id, :cliente_id, :num_controle, :servico_id, :valor, :custo, :dias_uso, :data_inicial, :status,:quantidade)";

        // Preparar e executar a declaração PDO
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':evento_id' => $data['evento_id'],
            ':cliente_id' => $data['cliente_id'],
            ':num_controle' => $data['num_controle'],
            ':servico_id' => $data['servico_id'],
            ':valor' => $data['valor'],
            ':custo' => $data['custo'],
            ':dias_uso' => $data['dias_uso'],
            ':data_inicial' => $data['data_inicial'],
            ':status' => $data['status'],
            ':quantidade' => $data['quantidade']
        ]);

        // Verificar se a inserção foi bem-sucedida
        if ($stmt->rowCount() > 0) {
            return array('success' => true, 'message' => 'Serviço de pedido criado com sucesso','order_service_id' => $pdo->lastInsertId());
        } else {
            return array('error' => 'Falha ao criar serviço de pedido');
        }
    }

    public static function updateOrderService($id, $data) {
        global $pdo;

        // Preparar a consulta SQL para atualizar um serviço de pedido com base no num_controle
        $sql = "UPDATE order_services SET ";
        $values = [];
        foreach ($data as $key => $value) {
            $sql .= "$key = ?, ";
            $values[] = $value;
        }
        $sql = rtrim($sql, ", "); // Remove a vírgula e o espaço em branco extra no final da string SQL
        $sql .= " WHERE id = ?";
        $values[] = $id;

        // Preparar e executar a declaração PDO
        $stmt = $pdo->prepare($sql);
        $stmt->execute($values);

        // Verificar se a atualização foi bem-sucedida
        if ($stmt->rowCount() > 0) {
            return array('success' => true, 'message' => 'Serviço de pedido atualizado com sucesso');
        } else {
            return array('error' => 'Falha ao atualizar serviço de pedido');
        }
    }

    // Listar materiais
    public static function listMaterials($dataEvento = null) {
        try {
            global $pdo;

            if (!$dataEvento) {
                $dataEvento = date('Y-m-d');
            }

            $stmt = $pdo->prepare("
            SELECT 
                m.*,
                COUNT(mp.id) AS quantidade_total,
                IFNULL(SUM(oi.quantidade), 0) AS quantidade_reservada,
                (COUNT(mp.id) - IFNULL(SUM(oi.quantidade), 0)) AS quantidade_disponivel
            FROM material m
            LEFT JOIN material_patrimonio mp ON mp.material_id = m.id
            LEFT JOIN (
                SELECT 
                    material_id, 
                    SUM(quantidade) AS quantidade
                FROM order_itens
                WHERE :dataEvento BETWEEN DATE(data_inicial) AND DATE(data_final)
                GROUP BY material_id
            ) oi ON oi.material_id = m.id
            GROUP BY m.id
        ");

            $stmt->execute([
                ':dataEvento' => $dataEvento
            ]);

            $materials = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return ['success' => true, 'materials' => $materials];

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erro ao listar materiais: ' . $e->getMessage()];
        }
    }


    // Listar serviços
    public static function listServices() {
        try {
            global $pdo;
            $stmt = $pdo->query("SELECT * FROM servico");
            $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return ['success' => true, 'services' => $services];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erro ao listar serviços: ' . $e->getMessage()];
        }
    }


    // Listar opções de recebimento (pagamentos)
    public static function listPaymentMethods() {
        try {
            global $pdo;
            $stmt = $pdo->query("SELECT * FROM opcoes_recebimento");
            $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return ['success' => true, 'payment_methods' => $payments];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erro ao listar opções de pagamento: ' . $e->getMessage()];
        }
    }
}
?>
