<?php
// ajax_auth.php - Backend real para autenticação e operações do portal
require 'config.php';

header('Content-Type: application/json');

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data || !isset($data['action'])) {
    echo json_encode(['error' => 'Requisição inválida']);
    exit;
}

$action = $data['action'];

try {

    // =============================================
    // LOGIN: Verifica se o email existe e envia OTP
    // =============================================
    if ($action === 'check_login') {
        $email = trim($data['email'] ?? '');
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['error' => 'E-mail inválido']);
            exit;
        }

        $stmt = $pdo->prepare("SELECT id, nome FROM freelancer WHERE email = ? AND ativo = 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            $codigo = sprintf("%06d", mt_rand(1, 999999));

            $update = $pdo->prepare("UPDATE freelancer SET codigo_login = ?, expira_codigo = DATE_ADD(NOW(), INTERVAL 10 MINUTE) WHERE id = ?");
            $update->execute([$codigo, $user['id']]);

            // Envia o e-mail com o código
            enviarEmailCodigo($email, $codigo);

            echo json_encode(['exists' => true, 'message' => 'Código enviado para ' . $email]);
        } else {
            echo json_encode(['exists' => false, 'message' => 'E-mail não cadastrado']);
        }
        exit;
    }

    // =============================================
    // CADASTRO: Verifica se email já existe
    // =============================================
    if ($action === 'check_register') {
        $email = trim($data['email'] ?? '');
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['error' => 'E-mail inválido']);
            exit;
        }

        $stmt = $pdo->prepare("SELECT id FROM freelancer WHERE email = ?");
        $stmt->execute([$email]);
        $exists = $stmt->fetch() ? true : false;

        echo json_encode(['exists' => $exists]);
        exit;
    }

    // =============================================
    // CADASTRO: Registra o freelancer
    // =============================================
    if ($action === 'register') {
        $nome       = trim($data['nome'] ?? '');
        $email      = trim($data['email'] ?? '');
        $cpf        = trim($data['cpf'] ?? '');
        $telefone   = trim($data['telefone'] ?? '');
        $nascimento = trim($data['data_nascimento'] ?? '');
        $tipo_chave = trim($data['tipo_chave_pix'] ?? 'cpf');
        $chave_pix  = trim($data['chave_pix'] ?? '');

        // Validações básicas
        if (empty($nome) || empty($email) || empty($cpf) || empty($telefone) || empty($chave_pix)) {
            echo json_encode(['error' => 'Preencha todos os campos obrigatórios.']);
            exit;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['error' => 'E-mail inválido.']);
            exit;
        }

        // Verifica duplicidade
        $stmt = $pdo->prepare("SELECT id FROM freelancer WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            echo json_encode(['error' => 'Este e-mail já possui cadastro.']);
            exit;
        }

        // Insere o freelancer
        $insert = $pdo->prepare("INSERT INTO freelancer (nome, email, cpf, telefone, data_nascimento, tipo_chave_pix, chave_pix) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $insert->execute([$nome, $email, $cpf, $telefone, $nascimento ?: null, $tipo_chave, $chave_pix]);

        $newId = $pdo->lastInsertId();

        // Gera código OTP para login imediato
        $codigo = sprintf("%06d", mt_rand(1, 999999));
        $pdo->prepare("UPDATE freelancer SET codigo_login = ?, expira_codigo = DATE_ADD(NOW(), INTERVAL 10 MINUTE) WHERE id = ?")
            ->execute([$codigo, $newId]);

        // Envia e-mail de boas-vindas
        enviarEmailBoasVindas($email, $nome);

        // Envia código de acesso
        enviarEmailCodigo($email, $codigo);

        // Log
        registrarLog($pdo, $newId, 'Cadastro realizado');

        echo json_encode([
            'success' => true,
            'message' => 'Cadastro realizado! Código de acesso enviado para ' . $email
        ]);
        exit;
    }

    // =============================================
    // OTP: Valida o código de login
    // =============================================
    if ($action === 'verify_otp') {
        $email = trim($data['email'] ?? '');
        $code  = trim($data['code'] ?? '');

        if (empty($email) || empty($code)) {
            echo json_encode(['success' => false, 'message' => 'Dados incompletos.']);
            exit;
        }

        $stmt = $pdo->prepare("SELECT id, nome FROM freelancer WHERE email = ? AND codigo_login = ? AND expira_codigo >= NOW() AND ativo = 1");
        $stmt->execute([$email, $code]);
        $user = $stmt->fetch();

        if ($user) {
            // Limpa o código
            $pdo->prepare("UPDATE freelancer SET codigo_login = NULL, expira_codigo = NULL WHERE id = ?")
                ->execute([$user['id']]);

            // Cria sessão
            $_SESSION['freelancer_id'] = $user['id'];
            $_SESSION['freelancer_nome'] = $user['nome'];

            // Log
            registrarLog($pdo, $user['id'], 'Login via OTP');

            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Código inválido ou expirado.']);
        }
        exit;
    }

    // =============================================
    // DASHBOARD: Buscar dados do freelancer logado
    // =============================================
    if ($action === 'get_dashboard') {
        if (!isset($_SESSION['freelancer_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Não autenticado']);
            exit;
        }

        $fid = $_SESSION['freelancer_id'];

        // Dados do freelancer
        $stmt = $pdo->prepare("SELECT id, nome, email, foto_url FROM freelancer WHERE id = ?");
        $stmt->execute([$fid]);
        $freelancer = $stmt->fetch();

        // Pagamentos previstos (total)
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(valor), 0) as total FROM pagamento WHERE freelancer_id = ? AND status = 'previsto'");
        $stmt->execute([$fid]);
        $totalPrevisto = $stmt->fetch()['total'];

        // Pagamentos pagos (total)
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(valor), 0) as total FROM pagamento WHERE freelancer_id = ? AND status = 'pago'");
        $stmt->execute([$fid]);
        $totalPago = $stmt->fetch()['total'];

        // Lista de pagamentos
        $stmt = $pdo->prepare("SELECT p.*, e.nome as evento_titulo FROM pagamento p LEFT JOIN evento e ON p.evento_id = e.id WHERE p.freelancer_id = ? ORDER BY p.criado_em DESC LIMIT 20");
        $stmt->execute([$fid]);
        $pagamentos = $stmt->fetchAll();

        // Eventos abertos (não inscritos)
        $stmt = $pdo->prepare("
            SELECT e.id, e.nome, e.descricao_freelancer, e.data_inicio, e.local, e.cidade, e.estado,
                   e.vagas_freelancer, e.valor_freelancer, e.status_freelancer
            FROM evento e
            WHERE e.status_freelancer = 'aberto'
            AND e.data_inicio > NOW()
            AND e.id NOT IN (SELECT evento_id FROM inscricao_evento WHERE freelancer_id = ? AND status != 'cancelado')
            ORDER BY e.data_inicio ASC
        ");
        $stmt->execute([$fid]);
        $eventosDisponiveis = $stmt->fetchAll();

        // Eventos que o freelancer está inscrito
        $stmt = $pdo->prepare("
            SELECT e.id, e.nome, e.data_inicio, e.local, e.cidade, e.estado, e.valor_freelancer,
                   ie.status as inscricao_status, ie.id as inscricao_id
            FROM inscricao_evento ie
            JOIN evento e ON ie.evento_id = e.id
            WHERE ie.freelancer_id = ? AND ie.status != 'cancelado'
            ORDER BY e.data_inicio ASC
        ");
        $stmt->execute([$fid]);
        $meusEventos = $stmt->fetchAll();

        echo json_encode([
            'freelancer'          => $freelancer,
            'total_previsto'      => floatval($totalPrevisto),
            'total_pago'          => floatval($totalPago),
            'pagamentos'          => $pagamentos,
            'eventos_disponiveis' => $eventosDisponiveis,
            'meus_eventos'        => $meusEventos,
        ]);
        exit;
    }

    // =============================================
    // INSCRIÇÃO: Freelancer se inscreve num evento
    // =============================================
    if ($action === 'inscrever_evento') {
        if (!isset($_SESSION['freelancer_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Não autenticado']);
            exit;
        }

        $fid = $_SESSION['freelancer_id'];
        $evento_id = intval($data['evento_id'] ?? 0);

        if ($evento_id <= 0) {
            echo json_encode(['error' => 'Evento inválido']);
            exit;
        }

        // Verifica se o evento existe e está aberto
        $stmt = $pdo->prepare("SELECT id, vagas_freelancer, nome FROM evento WHERE id = ? AND status_freelancer = 'aberto'");
        $stmt->execute([$evento_id]);
        $evento = $stmt->fetch();

        if (!$evento) {
            echo json_encode(['error' => 'Evento não disponível.']);
            exit;
        }

        // Verifica vagas
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM inscricao_evento WHERE evento_id = ? AND status IN ('pendente','aprovado')");
        $stmt->execute([$evento_id]);
        $inscritos = $stmt->fetch()['total'];

        if ($inscritos >= $evento['vagas_freelancer']) {
            echo json_encode(['error' => 'Vagas esgotadas para este evento.']);
            exit;
        }

        // Verifica se já está inscrito
        $stmt = $pdo->prepare("SELECT id FROM inscricao_evento WHERE freelancer_id = ? AND evento_id = ? AND status != 'cancelado'");
        $stmt->execute([$fid, $evento_id]);
        if ($stmt->fetch()) {
            echo json_encode(['error' => 'Você já está inscrito neste evento.']);
            exit;
        }

        // Inscreve
        $pdo->prepare("INSERT INTO inscricao_evento (freelancer_id, evento_id, status) VALUES (?, ?, 'pendente')")
            ->execute([$fid, $evento_id]);

        registrarLog($pdo, $fid, "Inscreveu-se no evento: " . $evento['nome']);

        echo json_encode(['success' => true, 'message' => 'Inscrição realizada com sucesso!']);
        exit;
    }

    // =============================================
    // PERFIL: Dados do freelancer para tela de perfil
    // =============================================
    if ($action === 'get_perfil') {
        if (!isset($_SESSION['freelancer_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Não autenticado']);
            exit;
        }

        $fid = $_SESSION['freelancer_id'];
        $stmt = $pdo->prepare("SELECT id, nome, email, cpf, telefone, data_nascimento, foto_url, tipo_chave_pix, chave_pix FROM freelancer WHERE id = ?");
        $stmt->execute([$fid]);
        $freelancer = $stmt->fetch();

        echo json_encode(['freelancer' => $freelancer]);
        exit;
    }

    // =============================================
    // PERFIL: Atualizar dados
    // =============================================
    if ($action === 'update_perfil') {
        if (!isset($_SESSION['freelancer_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Não autenticado']);
            exit;
        }

        $fid        = $_SESSION['freelancer_id'];
        $telefone   = trim($data['telefone'] ?? '');
        $tipo_chave = trim($data['tipo_chave_pix'] ?? '');
        $chave_pix  = trim($data['chave_pix'] ?? '');
        $foto_url   = trim($data['foto_url'] ?? '');

        $pdo->prepare("UPDATE freelancer SET telefone = ?, tipo_chave_pix = ?, chave_pix = ?, foto_url = ? WHERE id = ?")
            ->execute([$telefone, $tipo_chave, $chave_pix, $foto_url, $fid]);

        echo json_encode(['success' => true, 'message' => 'Perfil atualizado.']);
        exit;
    }

    // =============================================
    // LOGOUT
    // =============================================
    if ($action === 'logout') {
        session_destroy();
        echo json_encode(['success' => true]);
        exit;
    }

    echo json_encode(['error' => 'Ação não reconhecida.']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro interno do servidor.']);
}