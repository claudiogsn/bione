<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../database/db.php';
require_once __DIR__ . '/../libs/dompdf/autoload.inc.php';


use Dompdf\Dompdf;
use Dompdf\Options;



class OrderServiceController
{


    public static function generateQrBase64($url){
        $endpoint = 'https://smiley.codes/qrcode/generator.php';

        $payload = [
            'inputstring'       => $url,
            'version'           => '5',
            'quietzone'         => 'on',
            'circularmodules'   => 'on',
            'circleradius'      => '0.45',
            'squarefinder'      => 'on',
            'squarealignment'   => 'on',
            'connectpaths'      => 'on',
            'logo'              => '',
            'logoscale'         => '25',
            'clearlogospace'    => 'on',
            'qrcode_dark'      => '282828',
            'qrcode_light'      => 'eaeaea',
            'qrcode_logo'       => '000000'
        ];

        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json;charset=UTF-8',
                'Accept: application/json',
            ],
            CURLOPT_POSTFIELDS     => json_encode($payload),
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        if (!$response) {
            return null;
        }

        $data = json_decode($response, true);

        return $data['qrcode'] ?? null; // <- isso já é o base64 da imagem
    }

    public static function removerPrefixoOs($documento)
    {
        return preg_replace('/^OS-/', '', $documento);
    }

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
                    data_inicial, data_final, status, quantidade, observacao
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");


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
                    $item['quantidade'] ?? 1,
                    $item['observacao'] ?? null
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

        $stmtItens = $pdo->prepare("
            SELECT 
                oi.*, 
                m.sublocado 
            FROM order_itens oi
            LEFT JOIN material m ON oi.material_id = m.id
            WHERE oi.num_controle = ?
        ");
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

    public static function updateStatusOrderByDocumento($documento, $novo_status)

    {
        global $pdo;

        try {
            $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE documento = ?");
            $stmt->execute([$novo_status, $documento]);

            if ($stmt->rowCount() > 0) {
                return ['success' => true, 'message' => 'Status atualizado com sucesso'];
            } else {
                return ['success' => false, 'message' => 'Nenhuma ordem encontrada com esse documento ou status já está igual'];
            }
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Erro ao atualizar status: ' . $e->getMessage()];
        }
    }

    public static function generateOrdemServicoPdf($documento)
    {

        $response = self::getOrderDetailsByDocumento($documento);

        $urlQrCode = "https://bionetecnologia.com.br/os/".self::removerPrefixoOs($documento);

        $qrcodeBase64 = self::generateQrBase64($urlQrCode);

        if (!$response['success']) {
            return ['success' => false, 'message' => 'Dados da OS não encontrados.'];
        }

        $data = $response['details'];
        $order = $data['order'];
        $cliente = $data['cliente'];
        $evento = $data['evento'];
        $itens = $data['itens'];
        $services = $data['services'];

        ob_start();
        ?>
        <!DOCTYPE html>
        <html lang="pt-br">
        <head>
            <meta charset="UTF-8">
            <title>Ordem de Serviço</title>
            <style>
                .footer table,
                .footer td {
                    border: none !important;
                }
                .footer {
                    position: fixed;
                    bottom: 0;
                    left: 0;
                    right: 0;
                    height: 80px;
                    font-size: 10px;
                    background: #fff;
                }

                .footer-fixed {
                    position: fixed;
                    bottom: 0;
                    left: 0;
                    right: 0;
                    padding-bottom: 20px;
                }

                body {
                    font-family: Calibri, sans-serif;
                    font-size: 12px;
                    padding: 15px;
                    color: #000;
                    padding-bottom: 100px; /* deixa espaço pro rodapé */
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-top: 8px;
                }
                table, th, td {
                    border: 0.1px solid #000;
                }
                th, td {
                    padding: 5px;
                    text-align: left;
                }
                h2 { margin: 8px 0; }
                .rodape {
                    text-align: center;
                    font-size: 10px;
                    margin-top: 30px;
                }
                .header {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    margin-bottom: 15px;
                }
                .header img {
                    height: 100px;
                }
            </style>
        </head>
        <body>
        <div class="header">
            <div style='position: absolute; top: 0; right: 0;'>
                <img src="https://bionetecnologia.com.br/crm/external/imagens/logo_os.png" alt="Logo Bione">
            </div>
            <div>
                <h2>Ordem de Serviço</h2>
                <div><b>Cliente:</b> <?= $cliente['nome'] ?></div>
                <div><b>CNPJ:</b> <?= $cliente['cpf_cnpj'] ?></div>
                <div><b>Telefone:</b> <?= $cliente['telefone'] ?></div>
                <div><b>Email:</b> <?= $cliente['email'] ?></div>
            </div>
        </div>

        <center><h2><?= $order['documento'] ?></h2></center>
        <div><b>Evento:</b> <?= $evento['nome'] ?></div>
        <div><b>Local:</b> <?= $evento['local'] ?></div>
        <div><b>Endereço:</b> <?= $evento['endereco'] ?></div>
        <div><b>Período:</b> <?= date('d/m/Y H:i', strtotime($evento['data_inicio'])) ?> à <?= date('d/m/Y H:i', strtotime($evento['data_fim'])) ?></div>
        <hr>
        <div><b>Local Montagem:</b> <?= $order['local_montagem'] ?></div>
        <div><b>Montagem:</b> <?= date('d/m/Y H:i', strtotime($order['data_montagem'])) ?>
            <b>Recolhimento:</b> <?= date('d/m/Y', strtotime($order['data_recolhimento'])) ?></div>
        <div><b>Contato:</b> <?= $order['contato_montagem'] ?></div>

        <?php if (count($itens)): ?>
            <h3>Itens</h3>
            <table>
                <thead style="background-color: #015e9b; color: white;">
                <tr><th>Descrição</th><th>Qtd</th><th>Período</th></tr>
                </thead>
                <tbody>
                <?php
                $total = 0;
                foreach ($itens as $item):
                    $isSublocado = isset($item['sublocado']) && $item['sublocado'] == 1;
                    ?>
                    <tr>
                        <td>
                            <?= $item['descricao'] ?>
                            <?php if ($isSublocado): ?>
                                <span style="color: red; font-weight: bold;"> - SUBLOCADO</span>
                            <?php endif; ?>
                            <br> <small><?= $item['observacao'] ?></small>
                        </td>
                        <td><?= $item['quantidade'] ?></td>
                        <td><?= date('d/m', strtotime($item['data_inicial'])) ?> - <?= date('d/m', strtotime($item['data_final'])) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>


        <?php if (count($services)): ?>
            <h3>Serviços</h3>
            <table>
                <thead style="background-color: #015e9b; color: white;"><tr><th>Serviço</th><th>Qtd</th><th>Data</th></tr></thead>
                <tbody>
                <?php foreach ($services as $s): ?>
                    <tr>
                        <td><?= $s['descricao'] ?></td>
                        <td><?= $s['quantidade'] ?></td>
                        <td><?= date('d/m', strtotime($s['data_inicial'])) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <p><b>Observações:</b><br><?= nl2br($order['observacao'] ?: 'Nenhuma observação.') ?></p>

        <div class="footer">
            <table width="100%" style="font-size: 10px;border: none">
                <tr>
                    <td style="text-align: center;">
                        Bione Alugueis e Servicos de Informatica LTDA / CNPJ: 11.204.447/0001-07<br>
                        Rua Luiza Maria da Conceicao, 187, Renascer - Cabedelo – PB<br>
                        FONE: (83) 98871-9620
                    </td>

                    <?php if ($qrcodeBase64): ?>
                        <td style="text-align: right; vertical-align: middle; width: 110px;">
                            <div style="position: relative; display: inline-block; width: 100px; height: 100px;">
                                <img src="<?= $qrcodeBase64 ?>" style="width: 100px; height: 100px;">
                                <img src="https://bionetecnologia.com.br/logo-selo.png"
                                     style="position: absolute; top: 30%; left: 47%; width: 25px; height: 25px; transform: translate(-50%, -50%);">
                            </div>
                        </td>
                    <?php endif; ?>
                </tr>
            </table>
        </div>


        </body>
        </html>
        <?php

        $html = ob_get_clean();

        $dompdf = new Dompdf((new Options())->set('isRemoteEnabled', true));
        $dompdf->set_option('isHtml5ParserEnabled', true); // se ainda não estiver
        $dompdf->loadHtml($html);
        $dompdf->setPaper([0, 0, 595.28, 841.89]); // A4
        $dompdf->render();

        $safeCliente = preg_replace('/[^a-zA-Z0-9]/', '_', $cliente['nome']);
        $fileName = 'OS_' . $safeCliente . '_' . $order['documento'] . '.pdf';
        $filePath = __DIR__ . '/../public/orders/' . $fileName;
        $publicUrl = 'https://bionetecnologia.com.br/crm/api/v1/public/orders/' . $fileName;

        if (!is_dir(dirname($filePath))) {
            mkdir(dirname($filePath), 0777, true);
        }

        file_put_contents($filePath, $dompdf->output());

        return [
            'success' => true,
            'url' => $publicUrl
        ];
    }

    public static function generatePropostaPdf($documento)
    {

        $response = self::getOrderDetailsByDocumento($documento);

        if (!$response['success']) {
            return ['success' => false, 'message' => 'Dados da OS não encontrados.'];
        }

        $data = $response['details'];
        $order = $data['order'];
        $cliente = $data['cliente'];
        $evento = $data['evento'];
        $itens = $data['itens'];
        $services = $data['services'];
        $payments = $data['payments'];

        ob_start();
        ?>
        <!DOCTYPE html>
        <html lang="pt-br">
        <head>
            <meta charset="UTF-8">
            <title>Proposta de Locação</title>
            <style>
                body {
                    font-family: Calibri, sans-serif;
                    font-size: 12px;
                    padding: 15px;
                    color: #000;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-top: 8px;
                }
                table, th, td {
                    border: 0.1px solid #000;
                }
                th, td {
                    padding: 5px;
                    text-align: left;
                }
                h2 { margin: 8px 0; }
                .rodape {
                    text-align: center;
                    font-size: 10px;
                    margin-top: 30px;
                }
                .header {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    margin-bottom: 15px;
                }
                .header img {
                    height: 100px;
                }
            </style>
        </head>
        <body>
        <div class="header">
            <div style='position: absolute; top: 0; right: 0;'>
                <img src="https://bionetecnologia.com.br/crm/external/imagens/logo_os.png" alt="Logo Bione">
            </div>
            <div>
                <h2>Proposta de Locação</h2>
                <div><b>Cliente:</b> <?= $cliente['nome'] ?></div>
                <div><b>CNPJ:</b> <?= $cliente['cpf_cnpj'] ?></div>
                <div><b>Telefone:</b> <?= $cliente['telefone'] ?></div>
                <div><b>Email:</b> <?= $cliente['email'] ?></div>
            </div>
        </div>

        <center><h2> Proposta <?=self::removerPrefixoOs($order['documento'])?></h2></center>
        <div><b>Evento:</b> <?= $evento['nome'] ?></div>
        <div><b>Local:</b> <?= $evento['local'] ?></div>
        <div><b>Endereço:</b> <?= $evento['endereco'] ?></div>
        <div><b>Período:</b> <?= date('d/m/Y H:i', strtotime($evento['data_inicio'])) ?> à <?= date('d/m/Y H:i', strtotime($evento['data_fim'])) ?></div>

        <?php if (count($itens)): ?>
            <h3>Itens</h3>
            <table>
                <thead style="background-color: #015e9b; color: white;">
                <tr><th>Descrição</th><th>Qtd</th><th>Período</th><th>Valor</th><th>Subtotal</th></tr>
                </thead>
                <tbody>
                <?php
                $total = 0;
                foreach ($itens as $item):
                    $subtotal = $item['quantidade'] * $item['valor'] * $item['dias_uso'];
                    $total += $subtotal;
                    ?>
                    <tr>
                        <td><?= $item['descricao'] ?></td>
                        <td><?= $item['quantidade'] ?></td>
                        <td><?= date('d/m', strtotime($item['data_inicial'])) ?> - <?= date('d/m', strtotime($item['data_final'])) ?></td>
                        <td>R$ <?= number_format($item['valor'], 2, ',', '.') ?></td>
                        <td>R$ <?= number_format($subtotal, 2, ',', '.') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot>
                <tr>
                    <td colspan="4" style="text-align: right"><strong>Total:</strong></td>
                    <td><strong>R$ <?= number_format($total, 2, ',', '.') ?></strong></td>
                </tr>
                </tfoot>
            </table>
        <?php endif; ?>

        <?php if (count($services)): ?>
            <h3>Serviços</h3>
            <table>
                <thead style="background-color: #015e9b; color: white;"><tr><th>Serviço</th><th>Qtd</th><th>Data</th></tr></thead>
                <tbody>
                <?php foreach ($services as $s): ?>
                    <tr>
                        <td><?= $s['descricao'] ?></td>
                        <td><?= $s['quantidade'] ?></td>
                        <td><?= date('d/m', strtotime($s['data_inicial'])) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <?php if (count($payments)): ?>
            <h3>Pagamentos</h3>
            <table>
                <thead style="background-color: #015e9b; color: white;"><tr><th>Forma</th><th>Valor</th><th>Data</th><th>Descrição</th></tr></thead>
                <tbody>
                <?php foreach ($payments as $pg): ?>
                    <tr>
                        <td><?= $pg['forma_nome'] ?? '---' ?></td>
                        <td>R$ <?= number_format($pg['valor_pg'], 2, ',', '.') ?></td>
                        <td><?= date('d/m/Y', strtotime($pg['data_pg'])) ?></td>
                        <td><?= nl2br($pg['forma_descricao']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>


        <div><p>Essa proposta tem validade de 30 dias</p></div>
        <div class="rodape">
            Bione Alugueis e Servicos de Informatica LTDA / CNPJ: 11.204.447/0001-07<br>
            Rua Luiza Maria da Conceicao, 187, Renascer - Cabedelo – PB<br>
            FONE: (83) 98871-9620
        </div>

        </body>
        </html>
        <?php

        $html = ob_get_clean();

        $dompdf = new Dompdf((new Options())->set('isRemoteEnabled', true));
        $dompdf->loadHtml($html);
        $dompdf->setPaper([0, 0, 595.28, 841.89]); // A4
        $dompdf->render();

        $safeCliente = preg_replace('/[^a-zA-Z0-9]/', '_', $cliente['nome']);
        $fileName = 'PROPOSTA_' . $safeCliente . '_' . $order['documento'] . '.pdf';
        $filePath = __DIR__ . '/../public/proposta/' . $fileName;
        $publicUrl = 'https://bionetecnologia.com.br/crm/api/v1/public/proposta/' . $fileName;

        if (!is_dir(dirname($filePath))) {
            mkdir(dirname($filePath), 0777, true);
        }

        file_put_contents($filePath, $dompdf->output());

        return [
            'success' => true,
            'url' => $publicUrl
        ];
    }






}


?>
