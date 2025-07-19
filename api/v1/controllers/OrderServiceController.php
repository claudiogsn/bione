<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../database/db.php';

class OrderServiceController
{
    public static function createOrUpdateOrder($data)
    {
        global $pdo;

        try {
            $pdo->beginTransaction();

            $order = $data['order'];
            $itens = $data['itens'] ?? [];
            $services = $data['services'] ?? [];
            $payments = $data['payments'] ?? [];

            // Se for update, já existe ID
            if (!empty($order['id'])) {
                $id = $order['id'];

                // Atualiza `orders`
                $set = '';
                $values = [];
                foreach ($order as $key => $value) {
                    if ($key !== 'id') {
                        $set .= "$key = ?, ";
                        $values[] = $value;
                    }
                }
                $set = rtrim($set, ', ');
                $values[] = $id;

                $stmt = $pdo->prepare("UPDATE orders SET $set WHERE id = ?");
                $stmt->execute($values);

                $num_controle = $order['num_controle'];
            } else {
                // Inserção
                $stmt = $pdo->prepare("INSERT INTO orders (
                    evento_id, cliente_id, data_montagem, data_recolhimento, status,
                    contato_montagem, local_montagem, endereco, place_url, observacao
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

                $stmt->execute([
                    $order['evento_id'],
                    $order['cliente_id'],
                    $order['data_montagem'],
                    $order['data_recolhimento'],
                    $order['status'] ?? '1',
                    $order['contato_montagem'],
                    $order['local_montagem'],
                    $order['endereco'],
                    $order['place_url'] ?? null,
                    $order['observacao'] ?? null
                ]);


                $order_id = $pdo->lastInsertId();

                // Gera num_controle/documento
                $num_controle = $order_id;
                $documento = 'OS-' .
                    str_pad($order['cliente_id'], 2, '0', STR_PAD_LEFT) .
                    str_pad($order['evento_id'], 2, '0', STR_PAD_LEFT) .
                    str_pad($order_id, 2, '0', STR_PAD_LEFT);

                $stmt = $pdo->prepare("UPDATE orders SET num_controle = ?, documento = ? WHERE id = ?");
                $stmt->execute([$num_controle, $documento, $order_id]);
            }

            // Limpa registros anteriores (caso update)
            $pdo->prepare("DELETE FROM order_itens WHERE num_controle = ?")->execute([$num_controle]);
            $pdo->prepare("DELETE FROM order_services WHERE num_controle = ?")->execute([$num_controle]);
            $pdo->prepare("DELETE FROM order_pagamentos WHERE num_controle = ?")->execute([$num_controle]);

            // Insere Itens
            foreach ($itens as $item) {
                $stmt = $pdo->prepare("INSERT INTO order_itens (
                    evento_id, cliente_id, num_controle, material_id, descricao, valor, custo, dias_uso, 
                    data_inicial, data_final, status, quantidade
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

                $stmt->execute([
                    $order['evento_id'],
                    $order['cliente_id'],
                    $num_controle,
                    $item['material_id'],
                    $item['descricao'] ?? null,
                    $item['valor'] ?? 0,
                    $item['custo'] ?? 0,
                    $item['dias_uso'] ?? 1,
                    $item['data_inicial'],
                    $item['data_final'],
                    $item['status'] ?? 'ativo',
                    $item['quantidade'] ?? 1
                ]);

            }

            // Insere Serviços
            foreach ($services as $srv) {
                $stmt = $pdo->prepare("INSERT INTO order_services (
                    evento_id, cliente_id, num_controle, servico_id, valor, custo, dias_uso, 
                    data_inicial, status, quantidade
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

                $stmt->execute([
                    $order['evento_id'],
                    $order['cliente_id'],
                    $num_controle,
                    $srv['servico_id'],
                    $srv['valor'] ?? 0,
                    $srv['custo'] ?? 0,
                    $srv['dias_uso'] ?? 1,
                    $srv['data_inicial'],
                    $srv['status'] ?? 'ativo',
                    $srv['quantidade'] ?? 1
                ]);
            }

            // Insere Pagamentos
            foreach ($payments as $pay) {
                $stmt = $pdo->prepare("INSERT INTO order_pagamentos (
                    evento_id, cliente_id, num_controle, forma_pg, valor_pg, data_prog, data_pg, status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

                $stmt->execute([
                    $order['evento_id'],
                    $order['cliente_id'],
                    $num_controle,
                    $pay['forma_pg'],
                    $pay['valor_pg'],
                    $pay['data_prog'],
                    $pay['data_pg'] ?? null,
                    $pay['status'] ?? 'pendente'
                ]);
            }

            $pdo->commit();

            return [
                'success' => true,
                'message' => 'Ordem processada com sucesso',
                'num_controle' => $num_controle
            ];
        } catch (PDOException $e) {
            $pdo->rollBack();
            return ['success' => false, 'message' => 'Erro ao processar ordem: ' . $e->getMessage()];
        }
    }

    public static function getOrderDetailsByControle($num_controle)
    {
        global $pdo;

        $details = [];

        $stmtOrder = $pdo->prepare("SELECT * FROM orders WHERE num_controle = ?");
        $stmtOrder->execute([$num_controle]);
        $details['order'] = $stmtOrder->fetch(PDO::FETCH_ASSOC);

        if (!$details['order']) return ['success' => false, 'message' => 'Ordem não encontrada'];

        $cliente_id = $details['order']['cliente_id'];
        $evento_id = $details['order']['evento_id'];

        $stmtCliente = $pdo->prepare("SELECT * FROM cliente WHERE id = ?");
        $stmtCliente->execute([$cliente_id]);
        $details['cliente'] = $stmtCliente->fetch(PDO::FETCH_ASSOC);

        $stmtEvento = $pdo->prepare("SELECT * FROM evento WHERE id = ?");
        $stmtEvento->execute([$evento_id]);
        $details['evento'] = $stmtEvento->fetch(PDO::FETCH_ASSOC);

        $stmtItens = $pdo->prepare("SELECT * FROM order_itens WHERE num_controle = ?");
        $stmtItens->execute([$num_controle]);
        $details['itens'] = $stmtItens->fetchAll(PDO::FETCH_ASSOC);

        $stmtServicos = $pdo->prepare("
            SELECT 
                os.*, 
                s.descricao 
            FROM order_services os
            LEFT JOIN servico s ON os.servico_id = s.id
            WHERE os.num_controle = ?
        ");
        $stmtServicos->execute([$num_controle]);
        $details['services'] = $stmtServicos->fetchAll(PDO::FETCH_ASSOC);


        $stmtPagamentos = $pdo->prepare("
            SELECT 
                op.*, 
                orp.nome AS forma_nome,
                orp.descricao AS forma_descricao
            FROM order_pagamentos op
            LEFT JOIN opcoes_recebimento orp ON op.forma_pg = orp.id
            WHERE op.num_controle = ?
        ");
        $stmtPagamentos->execute([$num_controle]);
        $details['payments'] = $stmtPagamentos->fetchAll(PDO::FETCH_ASSOC);


        return ['success' => true, 'details' => $details];
    }

    public static function getOrderDetailsByDocumento($documento)
    {
        global $pdo;

        $details = [];

        // Remove o prefixo "OS-" se existir
        $numero = preg_replace('/^OS-/', '', $documento);

        // Busca a ordem comparando com e sem o prefixo "OS-"
        $stmtOrder = $pdo->prepare("
        SELECT * FROM orders 
        WHERE documento = :completo 
           OR REPLACE(documento, 'OS-', '') = :numero
        LIMIT 1
    ");
        $stmtOrder->execute([
            'completo' => $documento,
            'numero'   => $numero
        ]);
        $details['order'] = $stmtOrder->fetch(PDO::FETCH_ASSOC);

        if (!$details['order']) {
            return ['success' => false, 'message' => 'Ordem não encontrada'];
        }

        $cliente_id = $details['order']['cliente_id'];
        $evento_id = $details['order']['evento_id'];
        $num_controle = $details['order']['num_controle'];

        $stmtCliente = $pdo->prepare("SELECT * FROM cliente WHERE id = ?");
        $stmtCliente->execute([$cliente_id]);
        $details['cliente'] = $stmtCliente->fetch(PDO::FETCH_ASSOC);

        $stmtEvento = $pdo->prepare("SELECT * FROM evento WHERE id = ?");
        $stmtEvento->execute([$evento_id]);
        $details['evento'] = $stmtEvento->fetch(PDO::FETCH_ASSOC);

        $stmtItens = $pdo->prepare("SELECT * FROM order_itens WHERE num_controle = ?");
        $stmtItens->execute([$num_controle]);
        $details['itens'] = $stmtItens->fetchAll(PDO::FETCH_ASSOC);

        $stmtServicos = $pdo->prepare("
        SELECT 
            os.*, 
            s.descricao 
        FROM order_services os
        LEFT JOIN servico s ON os.servico_id = s.id
        WHERE os.num_controle = ?
    ");
        $stmtServicos->execute([$num_controle]);
        $details['services'] = $stmtServicos->fetchAll(PDO::FETCH_ASSOC);

        $stmtPagamentos = $pdo->prepare("
        SELECT 
            op.*, 
            orp.nome AS forma_nome,
            orp.descricao AS forma_descricao
        FROM order_pagamentos op
        LEFT JOIN opcoes_recebimento orp ON op.forma_pg = orp.id
        WHERE op.num_controle = ?
    ");
        $stmtPagamentos->execute([$num_controle]);
        $details['payments'] = $stmtPagamentos->fetchAll(PDO::FETCH_ASSOC);

        return ['success' => true, 'details' => $details];
    }

    public static function listOrdersByEvento($evento_id)
    {
        global $pdo;

        $stmt = $pdo->prepare("SELECT * FROM orders WHERE evento_id = ? ORDER BY created_at DESC");
        $stmt->execute([$evento_id]);
        return ['success' => true, 'orders' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
    }

    public static function listOrdersByPeriodo($filters)
    {
        global $pdo;

        if (empty($filters['data_inicio']) || empty($filters['data_fim'])) {
            return ['success' => false, 'message' => 'Data inicial e final são obrigatórias.'];
        }

        $inicio = strtotime($filters['data_inicio']);
        $fim = strtotime($filters['data_fim']);

        if ($inicio > $fim) {
            return ['success' => false, 'message' => 'A data inicial não pode ser maior que a data final'];
        }

        $stmt = $pdo->prepare("
        SELECT 
            o.*, 
            c.nome AS nome_cliente, 
            e.nome AS nome_evento, 
            e.data_inicio AS data_evento_inicio,
            e.data_fim AS data_evento_fim,
            e.local AS local_evento
        FROM orders o
        LEFT JOIN cliente c ON o.cliente_id = c.id
        LEFT JOIN evento e ON o.evento_id = e.id
        WHERE o.data_montagem BETWEEN :data_inicio AND :data_fim
        ORDER BY o.data_montagem DESC
    ");

        $stmt->execute([
            ':data_inicio' => $filters['data_inicio'],
            ':data_fim' => $filters['data_fim']
        ]);

        return ['success' => true, 'orders' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
    }


    public static function listMetodosPagamento()
    {
        global $pdo;

        $stmt = $pdo->query("SELECT * FROM opcoes_recebimento ORDER BY nome ASC");
        $metodos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($metodos) {
            return ['success' => true, 'metodos' => $metodos];
        } else {
            return ['success' => false, 'message' => 'Nenhum método de pagamento encontrado'];
        }

    }

}


?>
