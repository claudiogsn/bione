<?php

require_once __DIR__ . '/../database/db.php';
require_once __DIR__ . '/../libs/dompdf/autoload.inc.php';
use Dompdf\Dompdf;
use Dompdf\Options;
class FaturaController
{
    public static function getFatura($documento_os)
    {
        global $pdo;

        // Buscar fatura com dados do cliente (destinatário)
        $stmt = $pdo->prepare("
        SELECT 
            f.*,
            c.nome AS cliente_nome,
            c.cpf_cnpj AS cliente_cnpj,
            c.endereco AS cliente_endereco,
            c.bairro AS cliente_bairro,
            c.cidade AS cliente_cidade,
            c.estado AS cliente_estado,
            c.cep AS cliente_cep,
            c.telefone AS cliente_telefone,
            c.email AS cliente_email
        FROM fatura f
        INNER JOIN cliente c ON c.id = f.cliente_id
        WHERE f.documento_os = :documento_os
    ");
        $stmt->execute([':documento_os' => $documento_os]);
        $fatura = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$fatura) {
            return ['success' => false, 'message' => 'Fatura não encontrada.'];
        }

        // Itens da fatura
        $stmtItens = $pdo->prepare("
        SELECT * FROM fatura_itens WHERE fatura_id = :fatura_id
    ");
        $stmtItens->execute([':fatura_id' => $fatura['id']]);
        $fatura['itens'] = $stmtItens->fetchAll(PDO::FETCH_ASSOC);

        // Pagamentos da fatura
        $stmtPagamentos = $pdo->prepare("
            SELECT 
                fp.*,
                orc.nome AS forma_nome,
                orc.descricao AS forma_descricao
            FROM fatura_pagamentos fp
            LEFT JOIN opcoes_recebimento orc ON orc.id = fp.forma_pg
            WHERE fp.fatura_id = :fatura_id
        ");
        $stmtPagamentos->execute([':fatura_id' => $fatura['id']]);
        $fatura['pagamentos'] = $stmtPagamentos->fetchAll(PDO::FETCH_ASSOC);


        // Opcional: estrutura para agrupar os dados do destinatário separadamente
        $fatura['destinatario'] = [
            'nome'     => $fatura['cliente_nome'],
            'cnpj'     => $fatura['cliente_cnpj'],
            'endereco' => "{$fatura['cliente_endereco']} – {$fatura['cliente_bairro']} – {$fatura['cliente_cidade']}/{$fatura['cliente_estado']} – CEP {$fatura['cliente_cep']}",
            'telefone' => $fatura['cliente_telefone'],
            'email'    => $fatura['cliente_email']
        ];

        return ['success' => true, 'data' => $fatura];
    }

    public static function createFatura($data)
    {
        global $pdo;

        $pdo->beginTransaction();
        try {
            // Gerar número sequencial da fatura
            $stmtLast = $pdo->query("SELECT MAX(id) as last_id FROM fatura");
            $lastId = $stmtLast->fetchColumn() ?: 0;
            $numeroFatura = str_pad($lastId + 1, 9, '0', STR_PAD_LEFT);

            // Inserir fatura
            $stmtFatura = $pdo->prepare("
                INSERT INTO fatura (numero_fatura, cliente_id, documento_os, url_assinado, data_emissao, periodo_inicio, periodo_fim, observacoes, valor_total, status)
                VALUES (:numero_fatura, :cliente_id, :documento_os, :url_assinado, :data_emissao, :periodo_inicio, :periodo_fim, :observacoes, :valor_total, 1)
            ");
            $stmtFatura->execute([
                ':numero_fatura' => $numeroFatura,
                ':cliente_id' => $data['cliente_id'],
                ':documento_os' => $data['documento_os'],
                ':url_assinado' => $data['url_assinado'] ?? null,
                ':data_emissao' => $data['data_emissao'],
                ':periodo_inicio' => $data['periodo_inicio'],
                ':periodo_fim' => $data['periodo_fim'],
                ':observacoes' => $data['observacoes'] ?? null,
                ':valor_total' => $data['valor_total']
            ]);

            $faturaId = $pdo->lastInsertId();

            // Inserir itens
            foreach ($data['itens'] as $item) {
                $stmtItem = $pdo->prepare("
                    INSERT INTO fatura_itens (fatura_id, documento_os, codigo_item, descricao, periodo_inicio, periodo_fim, quantidade, valor_unitario, subtotal)
                    VALUES (:fatura_id, :documento_os, :codigo_item, :descricao, :periodo_inicio, :periodo_fim, :quantidade, :valor_unitario, :subtotal)
                ");
                $stmtItem->execute([
                    ':fatura_id' => $faturaId,
                    ':documento_os' => $data['documento_os'],
                    ':codigo_item' => $item['codigo_item'],
                    ':descricao' => $item['descricao'],
                    ':periodo_inicio' => $item['periodo_inicio'],
                    ':periodo_fim' => $item['periodo_fim'],
                    ':quantidade' => $item['quantidade'],
                    ':valor_unitario' => $item['valor_unitario'],
                    ':subtotal' => $item['subtotal']
                ]);
            }

            // Inserir pagamentos
            foreach ($data['pagamentos'] as $pg) {
                $stmtPg = $pdo->prepare("
                    INSERT INTO fatura_pagamentos (fatura_id, documento_os, descricao, forma_pg, valor_pg, data_pg, status)
                    VALUES (:fatura_id, :documento_os, :descricao, :forma_pg, :valor_pg, :data_pg, :status)
                ");
                $stmtPg->execute([
                    ':fatura_id' => $faturaId,
                    ':documento_os' => $data['documento_os'],
                    ':descricao' => $pg['descricao'] ?? null,
                    ':forma_pg' => $pg['forma_pg'] ?? null,
                    ':valor_pg' => $pg['valor_pg'] ?? null,
                    ':data_pg' => $pg['data_pg'] ?? null,
                    ':status' => $pg['status'] ?? null
                ]);
            }

            $pdo->commit();
            return ['success' => true, 'message' => 'Fatura criada com sucesso', 'numero_fatura' => $numeroFatura];

        } catch (Exception $e) {
            $pdo->rollBack();
            return ['success' => false, 'message' => 'Erro ao criar fatura: ' . $e->getMessage()];
        }
    }

    public static function updateFaturaStatus($documento_os, $status)
    {
        global $pdo;

        $stmt = $pdo->prepare("UPDATE fatura SET status = :status WHERE documento_os = :documento_os");
        $stmt->execute([
            ':status' => $status,
            ':documento_os' => $documento_os
        ]);

        if ($stmt->rowCount()) {
            return ['success' => true, 'message' => 'Status atualizado com sucesso'];
        } else {
            return ['success' => false, 'message' => 'Fatura não encontrada ou status inalterado'];
        }
    }

    public static function generateFaturaByOrder($documento)
    {
        global $pdo;

        try {
            // 1. Buscar ordem
            $stmt = $pdo->prepare("SELECT * FROM orders WHERE documento = :documento");
            $stmt->execute([':documento' => $documento]);
            $ordem = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$ordem) {
                throw new Exception("Ordem de serviço não encontrada.");
            }

            // ❌ Verificar se já tem fatura
            if (!empty($ordem['numero_fatura'])) {
                throw new Exception("Essa OS já possui uma fatura gerada: " . $ordem['numero_fatura']);
            }

            $num_controle = $ordem['num_controle'];

            // 2. Buscar itens e 3. pagamentos da ordem
            $stmtItens = $pdo->prepare("SELECT * FROM order_itens WHERE num_controle = :num_controle");
            $stmtItens->execute([':num_controle' => $num_controle]);
            $itens = $stmtItens->fetchAll(PDO::FETCH_ASSOC);

            $stmtPg = $pdo->prepare("SELECT * FROM order_pagamentos WHERE num_controle = :num_controle");
            $stmtPg->execute([':num_controle' => $num_controle]);
            $pagamentos = $stmtPg->fetchAll(PDO::FETCH_ASSOC);

            // 4. Calcular total
            $valor_total = 0;
            foreach ($itens as $item) {
                $quantidade = $item['quantidade'] ?? 1;
                $dias_uso = $item['dias_uso'] ?? 1;
                $valor_unitario = $item['valor'] ?? 0;
                $valor_total += $quantidade * $dias_uso * $valor_unitario;
            }

            // 5. Gerar número da fatura
            $stmtLast = $pdo->query("SELECT MAX(id) FROM fatura");
            $lastId = $stmtLast->fetchColumn() ?: 0;
            $numeroFatura = str_pad($lastId + 1, 9, '0', STR_PAD_LEFT);

            // 6. Inserir fatura
            $stmtInsertFatura = $pdo->prepare("
            INSERT INTO fatura (
                numero_fatura, cliente_id, documento_os,
                data_emissao, periodo_inicio, periodo_fim,
                observacoes, valor_total, status
            ) VALUES (
                :numero_fatura, :cliente_id, :documento_os,
                :data_emissao, :periodo_inicio, :periodo_fim,
                :observacoes, :valor_total, 1
            )
        ");
            $stmtInsertFatura->execute([
                ':numero_fatura' => $numeroFatura,
                ':cliente_id' => $ordem['cliente_id'],
                ':documento_os' => $documento,
                ':data_emissao' => date('Y-m-d'),
                ':periodo_inicio' => date('Y-m-d', strtotime($ordem['data_montagem'])),
                ':periodo_fim' => date('Y-m-d', strtotime($ordem['data_recolhimento'])),
                ':observacoes' => $ordem['observacao'] ?? null,
                ':valor_total' => $valor_total
            ]);

            $faturaId = $pdo->lastInsertId();

            // 7. Inserir itens
            foreach ($itens as $item) {
                $quantidade = $item['quantidade'] ?? 1;
                $dias_uso = $item['dias_uso'] ?? 1;
                $valor_unitario = $item['valor'] ?? 0;
                $subtotal = $quantidade * $dias_uso * $valor_unitario;

                $stmtItem = $pdo->prepare("
                INSERT INTO fatura_itens (
                    fatura_id, documento_os, codigo_item, descricao,
                    periodo_inicio, periodo_fim, quantidade, dias_uso,
                    valor_unitario, subtotal
                ) VALUES (
                    :fatura_id, :documento_os, :codigo_item, :descricao,
                    :periodo_inicio, :periodo_fim, :quantidade, :dias_uso,
                    :valor_unitario, :subtotal
                )
            ");
                $stmtItem->execute([
                    ':fatura_id' => $faturaId,
                    ':documento_os' => $documento,
                    ':codigo_item' => $item['material_id'],
                    ':descricao' => $item['descricao'],
                    ':periodo_inicio' => date('Y-m-d', strtotime($item['data_inicial'])),
                    ':periodo_fim' => date('Y-m-d', strtotime($item['data_final'])),
                    ':quantidade' => $quantidade,
                    ':dias_uso' => $dias_uso,
                    ':valor_unitario' => $valor_unitario,
                    ':subtotal' => $subtotal
                ]);
            }

            // 8. Inserir pagamentos
            foreach ($pagamentos as $pg) {
                $stmtPg = $pdo->prepare("
                INSERT INTO fatura_pagamentos (
                    fatura_id, documento_os, descricao,
                    forma_pg, valor_pg, data_pg, status
                ) VALUES (
                    :fatura_id, :documento_os, :descricao,
                    :forma_pg, :valor_pg, :data_pg, :status
                )
            ");
                $stmtPg->execute([
                    ':fatura_id' => $faturaId,
                    ':documento_os' => $documento,
                    ':descricao' => $pg['forma_pg'],
                    ':forma_pg' => $pg['forma_pg'],
                    ':valor_pg' => $pg['valor_pg'],
                    ':data_pg' => $pg['data_pg'] ?? null,
                    ':status' => $pg['status'] ?? 'pendente'
                ]);
            }

            // 9. Atualizar campo numero_fatura na ordem
            $stmtUpdateOrder = $pdo->prepare("
            UPDATE orders SET numero_fatura = :numero_fatura, status = '6' WHERE documento = :documento
        ");
            $stmtUpdateOrder->execute([
                ':numero_fatura' => $numeroFatura,
                ':documento' => $documento
            ]);

            return ['success' => true, 'message' => 'Fatura gerada com sucesso', 'numero_fatura' => $numeroFatura];

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erro ao gerar fatura: ' . $e->getMessage()];
        }
    }

    public static function generateFaturaPdf($documento_os)
    {
        global $pdo;

        // Busca os dados da fatura
        $faturaResult = self::getFatura($documento_os);
        if (!$faturaResult['success']) {
            return ['success' => false, 'message' => 'Fatura não encontrada.'];
        }

        $fatura = $faturaResult['data'];
        $cliente = $fatura['destinatario']['nome'];
        $numero_fatura = $fatura['numero_fatura'];

        // Montar HTML dinamicamente
        ob_start();
        ?>
        <!DOCTYPE html>
        <html lang="pt-BR">
        <head>
            <meta charset="UTF-8">
            <title>Fatura de Locação</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    font-size: 11px;
                    margin: 0;
                    padding: 0;
                    color: #000;
                }
                .container {
                    width: 100%;
                    padding: 5px;
                    border: 1px solid #000;
                    box-sizing: border-box;
                }
                .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #000; padding-bottom: 10px; }
                .logo { width: 180px; height: auto; }
                .titulo-nf { font-size: 16px; font-weight: bold; text-align: right; }
                .section { margin-top: 10px; border: 1px solid #000; padding: 8px; }
                .section-title { font-weight: bold; margin-bottom: 5px; border-bottom: 1px solid #000; padding-bottom: 3px; }
                table { width: 100%; border-collapse: collapse; margin-top: 5px; }
                th, td { border: 1px solid #000; padding: 6px; text-align: left; }
                .total-row td { font-weight: bold; }
                .footer { margin-top: 20px; font-size: 10px; }
            </style>
        </head>
        <body>

        <div class="container">
            <div class="header">
                <img src="https://bionetecnologia.com.br/logo-fatura.png" alt="Logo da Empresa" class="logo">
                <div class="titulo-nf">FATURA DE LOCAÇÃO</div>
            </div>

            <div class="section">
                <div class="section-title">Emitente</div>
                <p><strong>Nome:</strong> BIONE ALUGUEIS E SERVIÇOS DE INFORMÁTICA LTDA</p>
                <p><strong>CNPJ:</strong> 11.204.447/0001-07</p>
                <p><strong>Endereço:</strong> RUA LUIZA MARIA DA CONCEIÇÃO, 187 – RENASCER – CABEDELO/PB – CEP 58108-062</p>
                <p><strong>Telefone:</strong> (83) 98871-9620 | <strong>Email:</strong> rodrigobione@hotmail.com</p>
            </div>

            <div class="section">
                <div class="section-title">Destinatário</div>
                <p><strong>Nome:</strong> <?= $fatura['destinatario']['nome'] ?></p>
                <p><strong>CNPJ:</strong> <?= $fatura['destinatario']['cnpj'] ?></p>
                <p><strong>Endereço:</strong> <?= $fatura['destinatario']['endereco'] ?></p>
                <p><strong>Email:</strong> <?= $fatura['destinatario']['email'] ?></p>
            </div>

            <div class="section">
                <div class="section-title">Dados da Fatura</div>
                <p>
                    <strong>Nº Fatura:</strong> <?= $fatura['numero_fatura'] ?> &nbsp;&nbsp;&nbsp;
                    <strong>Data de Emissão:</strong> <?= date('d/m/Y', strtotime($fatura['data_emissao'])) ?> &nbsp;&nbsp;&nbsp;
                    <strong>Período:</strong> <?= date('d/m/Y', strtotime($fatura['periodo_inicio'])) ?> a <?= date('d/m/Y', strtotime($fatura['periodo_fim'])) ?>
                </p>
            </div>

            <div class="section">
                <div class="section-title">Itens Locados</div>
                <table>
                    <thead>
                    <tr>
                        <th>Item</th>
                        <th>Descrição</th>
                        <th>Período</th>
                        <th>Qtd</th>
                        <th>Valor Unitário</th>
                        <th>Subtotal</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($fatura['itens'] as $i => $item): ?>
                        <tr>
                            <td><?= str_pad($i + 1, 3, '0', STR_PAD_LEFT) ?></td>
                            <td><?= $item['descricao'] ?></td>
                            <td><?= date('d/m/Y', strtotime($item['periodo_inicio'])) ?> a <?= date('d/m/Y', strtotime($item['periodo_fim'])) ?></td>
                            <td><?= $item['quantidade'] ?></td>
                            <td>R$ <?= number_format($item['valor_unitario'], 2, ',', '.') ?></td>
                            <td>R$ <?= number_format($item['subtotal'], 2, ',', '.') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                    <tr class="total-row">
                        <td colspan="5" style="text-align: right;">TOTAL</td>
                        <td>R$ <?= number_format($fatura['valor_total'], 2, ',', '.') ?></td>
                    </tr>
                    </tfoot>
                </table>
            </div>

            <div class="section">
                <div class="section-title">Forma de Pagamento</div>
                <?php if (!empty($fatura['pagamentos'])): ?>
                    <?php foreach ($fatura['pagamentos'] as $pg): ?>
                        <p>
                            <strong>Forma:</strong> <?= $pg['forma_nome'] ?? '---' ?> &nbsp;&nbsp;&nbsp;
                            <strong>Valor:</strong> R$ <?= number_format($pg['valor_pg'], 2, ',', '.') ?> &nbsp;&nbsp;&nbsp;
                        </p>
                        <?php if (!empty($pg['descricao'])): ?>
                            <p><strong>Descrição:</strong> <?= $pg['forma_descricao'] ?></p>
                        <?php endif; ?>
                        <hr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>---</p>
                <?php endif; ?>
            </div>


            <div class="footer">
                <p><strong>Observações:</strong>Não é fato gerador do ISSQN a locação de bens móveis. Dispensado da emissão de notas fiscais. Conforme Lei Complementar 116 de 31/07/2003. Natureza da operação: Locação de Bens Móveis.</p>
                <p><?= $fatura['observacoes'] ?></p>
            </div>

        </div>
        </body>
        </html>
        <?php
        $html = ob_get_clean();



        $dompdf = new Dompdf((new Options())->set('isRemoteEnabled', true));
        $dompdf->loadHtml('<html><head><meta charset="UTF-8"></head><body>' . $html . '</body></html>');
        //$dompdf->setPaper('A4', 'portrait');
        $dompdf->setPaper([0, 0, 595.28, 841.89]); // A4 sem margens
        $dompdf->render();

        $safeCliente = preg_replace('/[^a-zA-Z0-9]/', '_', $cliente);
        $fileName = 'Fatura_' . $safeCliente . '_' . $numero_fatura . '.pdf';
        $filePath = __DIR__ . '/../public/invoices/' . $fileName;
        $publicUrl = 'https://bionetecnologia.com.br/crm/api/v1/public/invoices/' . $fileName;

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
