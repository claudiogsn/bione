<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../database/db.php';

class OrderController {
    
    // Função para criar uma nova ordem de serviço
    public static function createOrder($data) {
        global $pdo; 

        $evento_id = $data['evento_id'];
        $cliente_id = $data['cliente_id'];
        $num_controle = $data['num_controle'];
        $data_montagem = $data['data_montagem'];
        $data_recolhimento = $data['data_recolhimento'];
        $status = 1;
        $contato_montagem = $data['contato_montagem'];
        $local_montagem = $data['local_montagem'];
        $endereco = $data['endereco'];

        $stmt = $pdo->prepare("INSERT INTO orders (evento_id, cliente_id, num_controle, data_montagem, data_recolhimento, status, contato_montagem, local_montagem, endereco) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$evento_id, $cliente_id, $num_controle, $data_montagem, $data_recolhimento, $status, $contato_montagem, $local_montagem, $endereco]);

        if ($stmt->rowCount() > 0) {
            return array('success' => true, 'message' => 'Ordem de serviço criada com sucesso');
        } else {
            return array('success' => false, 'message' => 'Falha ao criar ordem de serviço');
        }
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
    public static function listOrders() {
        global $pdo; 

        // Prepara e executa a consulta SQL para listar todas as ordens de serviço
        $stmt = $pdo->query("SELECT * FROM orders");

        // Retorna um array contendo todas as ordens de serviço encontradas
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function createOrderItem($data) {
        global $pdo;

        // Preparar a consulta SQL para inserir um novo item de pedido
        $sql = "INSERT INTO order_itens (evento_id, cliente_id, num_controle, material_id, valor, custo, dias_uso, data_inicial, status) VALUES (:evento_id, :cliente_id, :num_controle, :material_id, :valor, :custo, :dias_uso, :data_inicial, :status)";

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
            ':status' => $data['status']
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
            ':data_pg' => $data['data_pg'],
            ':status' => $data['status']
        ]);

        // Verificar se a inserção foi bem-sucedida
        if ($stmt->rowCount() > 0) {
            return array('success' => true, 'message' => 'Pagamento de pedido criado com sucesso');
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
        $sql = "INSERT INTO order_services (evento_id, cliente_id, num_controle, servico_id, valor, custo, dias_uso, data_inicial, status) VALUES (:evento_id, :cliente_id, :num_controle, :servico_id, :valor, :custo, :dias_uso, :data_inicial, :status)";

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
            ':status' => $data['status']
        ]);

        // Verificar se a inserção foi bem-sucedida
        if ($stmt->rowCount() > 0) {
            return array('success' => true, 'message' => 'Serviço de pedido criado com sucesso');
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
}
?>
