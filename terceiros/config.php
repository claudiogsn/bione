<?php
// config.php
session_start();

$db_host = 'srv887.hstgr.io';
$db_name = 'u520111578_bione';
$db_user = 'u520111578_bione';
$db_pass = 'Bione@159';

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erro de conexão: " . $e->getMessage());
}

// Bloqueio de Robôs Básicos
$ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
if (empty($ua) || strlen($ua) < 10) {
    http_response_code(403);
    die("Acesso Negado.");
}

// Função de Log
function registrarLog($pdo, $freelancer_id, $acao) {
    $ip = $_SERVER['REMOTE_ADDR'];
    $ua = $_SERVER['HTTP_USER_AGENT'];
    $stmt = $pdo->prepare("INSERT INTO log_acesso (freelancer_id, acao, ip, user_agent) VALUES (?, ?, ?, ?)");
    $stmt->execute([$freelancer_id, $acao, $ip, $ua]);
}

// Template de Email - Boas-Vindas
function enviarEmailBoasVindas($email, $nome) {
    $assunto = "Bem-vindo ao Portal Bione Tecnologia";
    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: Portal Bione <naoresponda@bionetecnologia.com.br>\r\n";

    $ano = date('Y');
    $mensagem = <<<HTML
    <div style='font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif; background-color:#F5F5F7; padding:40px 20px; text-align:center;'>
        <div style='max-width:500px; margin:0 auto; background:#FFFFFF; border-radius:18px; padding:40px 30px; box-shadow:0 4px 24px rgba(0,0,0,0.04);'>
            <img src='https://bionetecnologia.com.br/logos/logo-bione-preta.png' alt='Bione Logo' style='height:40px; margin-bottom:30px;'>
            <h1 style='font-size:24px; font-weight:600; color:#1D1D1F; margin:0 0 10px 0; letter-spacing:-0.5px;'>Olá, {$nome}.</h1>
            <p style='font-size:15px; line-height:1.6; color:#515154; margin:0 0 30px 0;'>Seu cadastro foi realizado com sucesso.<br>Agora você pode acompanhar seus pagamentos e se inscrever em eventos diretamente pelo portal.</p>
            <a href='https://bionetecnologia.com.br/portal/' style='display:inline-block; background:#1D1D1F; color:#FFFFFF; text-decoration:none; padding:14px 28px; border-radius:980px; font-size:14px; font-weight:600;'>Acessar o Portal</a>
        </div>
        <p style='font-size:12px; color:#86868B; margin-top:30px;'>© {$ano} Bione Tecnologia. Todos os direitos reservados.</p>
    </div>
HTML;

    return mail($email, $assunto, $mensagem, $headers);
}

// Template de Email - Código OTP
function enviarEmailCodigo($email, $codigo) {
    $assunto = "Seu código de acesso: $codigo";
    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: Portal Bione <naoresponda@bionetecnologia.com.br>\r\n";

    $ano = date('Y');
    $mensagem = <<<HTML
    <div style='font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif; background-color:#F5F5F7; padding:40px 20px; text-align:center;'>
        <div style='max-width:500px; margin:0 auto; background:#FFFFFF; border-radius:18px; padding:40px 30px; box-shadow:0 4px 24px rgba(0,0,0,0.04);'>
            <img src='https://bionetecnologia.com.br/logos/logo-bione-preta.png' alt='Bione Logo' style='height:40px; margin-bottom:30px;'>
            <h1 style='font-size:20px; font-weight:600; color:#1D1D1F; margin:0 0 10px 0;'>Código de Autenticação</h1>
            <p style='font-size:15px; color:#515154; margin:0 0 20px 0;'>Use o código abaixo para acessar sua conta.<br>Ele expira em <strong>10 minutos</strong>.</p>
            <div style='font-size:32px; font-weight:700; color:#1D1D1F; letter-spacing:8px; padding:15px 25px; background:#F5F5F7; border-radius:12px; display:inline-block;'>{$codigo}</div>
            <p style='font-size:13px; color:#86868B; margin-top:25px;'>Se você não solicitou este código, ignore este e-mail.</p>
        </div>
        <p style='font-size:12px; color:#86868B; margin-top:30px;'>© {$ano} Bione Tecnologia.</p>
    </div>
HTML;

    return mail($email, $assunto, $mensagem, $headers);
}
?>