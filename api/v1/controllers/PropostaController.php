<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../database/db.php';
require_once __DIR__ . '/../libs/dompdf/autoload.inc.php';


use Dompdf\Dompdf;
use Dompdf\Options;



class PropostaController
{

    public static function createOrUpdateProposta($data)
    {
        global $pdo;

        try {
            $pdo->beginTransaction();

            $proposta = $data['proposta'];
            $itens = $data['itens'] ?? [];
            $payments = $data['payments'] ?? [];

            if (!empty($proposta['id'])) {
                $id = $proposta['id'];

                // Atualiza `propostas`
                $set = '';
                $values = [];
                foreach ($proposta as $key => $value) {
                    if ($key !== 'id') {
                        $set .= "$key = ?, ";
                        $values[] = $value;
                    }
                }
                $set = rtrim($set, ', ');
                $values[] = $id;

                $stmt = $pdo->prepare("UPDATE propostas SET $set WHERE id = ?");
                $stmt->execute($values);

                $num_controle = $proposta['num_controle'];
            } else {
                // Inserção
                $stmt = $pdo->prepare("INSERT INTO propostas (
                evento_id, cliente_id, data_montagem, data_recolhimento, status,
                contato_montagem, local_montagem, endereco, place_url, observacao
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

                $stmt->execute([
                    $proposta['evento_id'],
                    $proposta['cliente_id'],
                    $proposta['data_montagem'],
                    $proposta['data_recolhimento'],
                    $proposta['status'] ?? '1',
                    $proposta['contato_montagem'],
                    $proposta['local_montagem'],
                    $proposta['endereco'],
                    $proposta['place_url'] ?? null,
                    $proposta['observacao'] ?? null
                ]);

                $proposta_id = $pdo->lastInsertId();

                // Gera num_controle/documento
                $num_controle = $proposta_id;
                $documento =
                    str_pad($proposta['cliente_id'], 2, '0', STR_PAD_LEFT) .
                    str_pad($proposta['evento_id'], 2, '0', STR_PAD_LEFT) .
                    str_pad($proposta_id, 2, '0', STR_PAD_LEFT);

                $stmt = $pdo->prepare("UPDATE propostas SET num_controle = ?, documento = ? WHERE id = ?");
                $stmt->execute([$num_controle, $documento, $proposta_id]);
            }

            // Limpa registros anteriores (caso update)
            $pdo->prepare("DELETE FROM proposta_itens WHERE num_controle = ?")->execute([$num_controle]);
            $pdo->prepare("DELETE FROM proposta_pagamentos WHERE num_controle = ?")->execute([$num_controle]);

            // Insere Itens
            foreach ($itens as $item) {
                $stmt = $pdo->prepare("INSERT INTO proposta_itens (
                evento_id, cliente_id, num_controle, material_id, material_pai, sequencial, descricao, valor, custo, dias_uso,
                data_inicial, data_final, status, quantidade, observacao, local
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

                $stmt->execute([
                    $proposta['evento_id'],
                    $proposta['cliente_id'],
                    $num_controle,
                    $item['material_id'],
                    $item['material_pai'] ?? null,
                    $item['sequencial'] ?? null,
                    $item['descricao'] ?? null,
                    $item['valor'] ?? 0,
                    $item['custo'] ?? 0,
                    $item['dias_uso'] ?? 1,
                    $item['data_inicial'],
                    $item['data_final'],
                    $item['status'] ?? 'ativo',
                    $item['quantidade'] ?? 1,
                    $item['observacao'] ?? null,
                    $item['local'] ?? null
                ]);
            }

            // Insere Pagamentos
            foreach ($payments as $pay) {
                $stmt = $pdo->prepare("INSERT INTO proposta_pagamentos (
                evento_id, cliente_id, num_controle, forma_pg, valor_pg, data_prog, data_pg, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

                $stmt->execute([
                    $proposta['evento_id'],
                    $proposta['cliente_id'],
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
                'message' => 'Proposta processada com sucesso',
                'num_controle' => $num_controle
            ];
        } catch (PDOException $e) {
            $pdo->rollBack();
            return ['success' => false, 'message' => 'Erro ao processar proposta: ' . $e->getMessage()];
        }
    }

    public static function getPropostaDetailsByDocumento($documento)
    {
        global $pdo;

        $details = [];

        // Busca direta pelo número do documento
        $stmtProposta = $pdo->prepare("
        SELECT * FROM propostas 
        WHERE documento = :documento
        LIMIT 1
    ");
        $stmtProposta->execute([
            'documento' => $documento
        ]);
        $details['proposta'] = $stmtProposta->fetch(PDO::FETCH_ASSOC);

        if (!$details['proposta']) {
            return ['success' => false, 'message' => 'Proposta não encontrada'];
        }

        $cliente_id = $details['proposta']['cliente_id'];
        $evento_id = $details['proposta']['evento_id'];
        $num_controle = $details['proposta']['num_controle'];

        // Cliente
        $stmtCliente = $pdo->prepare("SELECT * FROM cliente WHERE id = ?");
        $stmtCliente->execute([$cliente_id]);
        $details['cliente'] = $stmtCliente->fetch(PDO::FETCH_ASSOC);

        // Evento
        $stmtEvento = $pdo->prepare("SELECT * FROM evento WHERE id = ?");
        $stmtEvento->execute([$evento_id]);
        $details['evento'] = $stmtEvento->fetch(PDO::FETCH_ASSOC);

        // Itens
        $stmtItens = $pdo->prepare("
        SELECT 
            pi.*, 
            m.sublocado 
        FROM proposta_itens pi
        LEFT JOIN material m ON pi.material_id = m.id
        WHERE pi.num_controle = ?
    ");
        $stmtItens->execute([$num_controle]);
        $details['itens'] = $stmtItens->fetchAll(PDO::FETCH_ASSOC);

        // Pagamentos
        $stmtPagamentos = $pdo->prepare("
        SELECT 
            pp.*, 
            orp.nome AS forma_nome,
            orp.descricao AS forma_descricao
        FROM proposta_pagamentos pp
        LEFT JOIN opcoes_recebimento orp ON pp.forma_pg = orp.id
        WHERE pp.num_controle = ?
    ");
        $stmtPagamentos->execute([$num_controle]);
        $details['payments'] = $stmtPagamentos->fetchAll(PDO::FETCH_ASSOC);

        return ['success' => true, 'details' => $details];
    }

    public static function getPropostaPdf($documento)
    {
        $response = self::getPropostaDetailsByDocumento($documento);

        if (!$response['success']) {
            return ['success' => false, 'message' => 'Dados da proposta não encontrados.'];
        }

        $data = $response['details'];
        $proposta = $data['proposta'];
        $cliente = $data['cliente'];
        $evento = $data['evento'];
        $itens = $data['itens'];
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

        <center><h2> Proposta <?= $proposta['documento'] ?></h2></center>
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
        $fileName = 'PROPOSTA_' . $safeCliente . '_' . $proposta['documento'] . '.pdf';
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

    public static function listPropostasByPeriodo($filters)
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
            p.*, 
            c.nome AS nome_cliente, 
            e.nome AS nome_evento, 
            e.data_inicio AS data_evento_inicio,
            e.data_fim AS data_evento_fim,
            e.local AS local_evento
        FROM propostas p
        LEFT JOIN cliente c ON p.cliente_id = c.id
        LEFT JOIN evento e ON p.evento_id = e.id
        WHERE p.data_montagem BETWEEN :data_inicio AND :data_fim
        ORDER BY p.data_montagem DESC
    ");

        $stmt->execute([
            ':data_inicio' => $filters['data_inicio'],
            ':data_fim' => $filters['data_fim']
        ]);

        return ['success' => true, 'propostas' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
    }




}