<?php
// ajax_admin.php - Backend admin para gerenciar freelancers e eventos
require 'config.php';

header('Content-Type: application/json');

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data || !isset($data['action'])) {
    echo json_encode(['error' => 'Requisição inválida']);
    exit;
}

$action = $data['action'];

// TODO: Implementar autenticação admin via token
// $token = $data['token'] ?? '';
// if (!validarTokenAdmin($token)) { http_response_code(401); echo json_encode(['error'=>'Não autorizado']); exit; }

try {

    // =============================================
    // FREELANCERS: Listar todos
    // =============================================
    if ($action === 'list_freelancers') {
        $busca = trim($data['busca'] ?? '');
        $status = $data['status'] ?? ''; // '', '1', '0'

        $sql = "SELECT f.*, 
                (SELECT COUNT(*) FROM inscricao_evento ie WHERE ie.freelancer_id = f.id AND ie.status IN ('pendente','aprovado')) as total_eventos,
                (SELECT COALESCE(SUM(p.valor),0) FROM pagamento p WHERE p.freelancer_id = f.id AND p.status = 'pago') as total_pago,
                (SELECT COALESCE(SUM(p.valor),0) FROM pagamento p WHERE p.freelancer_id = f.id AND p.status = 'previsto') as total_previsto
                FROM freelancer f WHERE 1=1";
        $params = [];

        if (!empty($busca)) {
            $sql .= " AND (f.nome LIKE ? OR f.email LIKE ? OR f.cpf LIKE ?)";
            $like = "%{$busca}%";
            $params = array_merge($params, [$like, $like, $like]);
        }
        if ($status !== '') {
            $sql .= " AND f.ativo = ?";
            $params[] = intval($status);
        }

        $sql .= " ORDER BY f.nome ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $freelancers = $stmt->fetchAll();

        // KPIs
        $kpi = $pdo->query("SELECT 
            COUNT(*) as total,
            SUM(ativo = 1) as ativos,
            SUM(ativo = 0) as inativos
            FROM freelancer")->fetch();

        echo json_encode(['freelancers' => $freelancers, 'kpi' => $kpi]);
        exit;
    }

    // =============================================
    // FREELANCERS: Ativar/Desativar
    // =============================================
    if ($action === 'toggle_freelancer') {
        $fid = intval($data['freelancer_id'] ?? 0);
        $ativo = intval($data['ativo'] ?? 0);

        $pdo->prepare("UPDATE freelancer SET ativo = ? WHERE id = ?")->execute([$ativo, $fid]);
        echo json_encode(['success' => true]);
        exit;
    }

    // =============================================
    // FREELANCERS: Deletar
    // =============================================
    if ($action === 'delete_freelancer') {
        $fid = intval($data['freelancer_id'] ?? 0);
        $pdo->prepare("DELETE FROM freelancer WHERE id = ?")->execute([$fid]);
        echo json_encode(['success' => true]);
        exit;
    }

    // =============================================
    // EVENTOS: Listar eventos com KPIs de freelancers
    // =============================================
    if ($action === 'list_eventos') {
        $busca = trim($data['busca'] ?? '');

        $sql = "SELECT e.id, e.nome, e.data_inicio, e.data_fim, e.cidade, e.estado, e.local,
                e.vagas_freelancer, e.valor_freelancer, e.status_freelancer,
                (SELECT COUNT(*) FROM inscricao_evento ie WHERE ie.evento_id = e.id AND ie.status IN ('pendente','aprovado')) as total_inscritos,
                (SELECT COUNT(*) FROM inscricao_evento ie WHERE ie.evento_id = e.id AND ie.status = 'aprovado') as total_aprovados,
                (SELECT COALESCE(SUM(ie.valor_extra),0) FROM inscricao_evento ie WHERE ie.evento_id = e.id AND ie.status IN ('pendente','aprovado')) as total_extras,
                (SELECT COALESCE(SUM(p.valor),0) FROM pagamento p WHERE p.evento_id = e.id AND p.status = 'pago') as total_pago,
                (SELECT COALESCE(SUM(p.valor),0) FROM pagamento p WHERE p.evento_id = e.id AND p.status = 'previsto') as total_previsto
                FROM evento e WHERE 1=1";
        $params = [];

        if (!empty($busca)) {
            $sql .= " AND (e.nome LIKE ? OR e.cidade LIKE ?)";
            $like = "%{$busca}%";
            $params = array_merge($params, [$like, $like]);
        }

        $sql .= " ORDER BY e.data_inicio DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $eventos = $stmt->fetchAll();

        echo json_encode(['eventos' => $eventos]);
        exit;
    }

    // =============================================
    // EVENTO: Freelancers de um evento específico
    // =============================================
    if ($action === 'get_evento_freelancers') {
        $evento_id = intval($data['evento_id'] ?? 0);

        // Dados do evento
        $stmt = $pdo->prepare("SELECT e.id, e.nome, e.data_inicio, e.data_fim, e.cidade, e.estado, e.local,
            e.vagas_freelancer, e.valor_freelancer, e.status_freelancer FROM evento e WHERE e.id = ?");
        $stmt->execute([$evento_id]);
        $evento = $stmt->fetch();

        if (!$evento) {
            echo json_encode(['error' => 'Evento não encontrado']);
            exit;
        }

        // Freelancers inscritos
        $stmt = $pdo->prepare("
            SELECT ie.id as inscricao_id, ie.status as inscricao_status, ie.valor_extra, ie.observacao, ie.criado_em as data_inscricao,
                   f.id as freelancer_id, f.nome, f.email, f.cpf, f.telefone, f.tipo_chave_pix, f.chave_pix, f.foto_url
            FROM inscricao_evento ie
            JOIN freelancer f ON ie.freelancer_id = f.id
            WHERE ie.evento_id = ?
            ORDER BY ie.criado_em DESC
        ");
        $stmt->execute([$evento_id]);
        $inscritos = $stmt->fetchAll();

        // KPIs
        $total_inscritos = count($inscritos);
        $total_aprovados = count(array_filter($inscritos, fn($i) => $i['inscricao_status'] === 'aprovado'));
        $total_pendentes = count(array_filter($inscritos, fn($i) => $i['inscricao_status'] === 'pendente'));
        $custo_base = $total_aprovados * floatval($evento['valor_freelancer']);
        $custo_extras = array_sum(array_map(fn($i) => ($i['inscricao_status'] !== 'cancelado' && $i['inscricao_status'] !== 'recusado') ? floatval($i['valor_extra']) : 0, $inscritos));
        $custo_total = $custo_base + $custo_extras;

        echo json_encode([
            'evento' => $evento,
            'inscritos' => $inscritos,
            'kpi' => [
                'total_inscritos' => $total_inscritos,
                'total_aprovados' => $total_aprovados,
                'total_pendentes' => $total_pendentes,
                'vagas_restantes' => max(0, intval($evento['vagas_freelancer']) - $total_inscritos),
                'custo_base' => $custo_base,
                'custo_extras' => $custo_extras,
                'custo_total' => $custo_total,
            ]
        ]);
        exit;
    }

    // =============================================
    // INSCRIÇÃO: Alterar status
    // =============================================
    if ($action === 'update_inscricao_status') {
        $inscricao_id = intval($data['inscricao_id'] ?? 0);
        $status = trim($data['status'] ?? '');

        if (!in_array($status, ['pendente', 'aprovado', 'recusado', 'cancelado'])) {
            echo json_encode(['error' => 'Status inválido']);
            exit;
        }

        $pdo->prepare("UPDATE inscricao_evento SET status = ? WHERE id = ?")->execute([$status, $inscricao_id]);
        echo json_encode(['success' => true]);
        exit;
    }

    // =============================================
    // INSCRIÇÃO: Atualizar valor extra
    // =============================================
    if ($action === 'update_valor_extra') {
        $inscricao_id = intval($data['inscricao_id'] ?? 0);
        $valor_extra = floatval($data['valor_extra'] ?? 0);
        $observacao = trim($data['observacao'] ?? '');

        $pdo->prepare("UPDATE inscricao_evento SET valor_extra = ?, observacao = ? WHERE id = ?")
            ->execute([$valor_extra, $observacao, $inscricao_id]);
        echo json_encode(['success' => true]);
        exit;
    }

    // =============================================
    // INSCRIÇÃO: Remover freelancer do evento
    // =============================================
    if ($action === 'remove_inscricao') {
        $inscricao_id = intval($data['inscricao_id'] ?? 0);
        $pdo->prepare("DELETE FROM inscricao_evento WHERE id = ?")->execute([$inscricao_id]);
        echo json_encode(['success' => true]);
        exit;
    }

    // =============================================
    // EVENTO: Atualizar config freelancer
    // =============================================
    if ($action === 'update_evento_freelancer_config') {
        $evento_id = intval($data['evento_id'] ?? 0);
        $vagas = intval($data['vagas_freelancer'] ?? 0);
        $valor = floatval($data['valor_freelancer'] ?? 0);
        $status = trim($data['status_freelancer'] ?? 'aberto');
        $descricao = trim($data['descricao_freelancer'] ?? '');

        $pdo->prepare("UPDATE evento SET vagas_freelancer = ?, valor_freelancer = ?, status_freelancer = ?, descricao_freelancer = ? WHERE id = ?")
            ->execute([$vagas, $valor, $status, $descricao, $evento_id]);
        echo json_encode(['success' => true]);
        exit;
    }

    echo json_encode(['error' => 'Ação não reconhecida.']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro interno: ' . $e->getMessage()]);
}