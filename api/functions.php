<?php

require_once 'config.php';

function validateData($data) {
    return true;
}

function registerClient($data) {
    global $pdo;
    
    $email = strtolower($data['email']);

    
    // Valida e limpa o CPF
    $cleaned_cpf = cleanCPF($data['cpf']);
    if (!validaCPF($cleaned_cpf)) {
        http_response_code(400);
        return array("error" => "CPF inválido");
    }

    // Verifica se o CPF já existe no banco de dados
    if (checkExistingCPF($cleaned_cpf)) {
        http_response_code(400);
        return array("error" => "CPF já cadastrado");
    }
    
    // Insira os dados do cliente na tabela cliente
    $stmt = $pdo->prepare("INSERT INTO cliente (nome, email, telefone, cpf, data_nascimento) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$data['nome'], $data['email'], $data['telefone'], $cleaned_cpf, $data['data_nascimento']]);
    
    // Gera o código do voucher
    $voucherCode = generateVoucherCode();

    // Verifica se o código do voucher já existe no banco de dados
    $stmt = $pdo->prepare("SELECT COUNT(*) AS count FROM voucher WHERE codigo = ?");
    $stmt->execute([$voucherCode]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row['count'] > 0) {
        // Se o código do voucher já existir, gera outro código
        $voucherCode = generateVoucherCode();
    }
    
    // Insere o voucher na tabela voucher
    $ip = $_SERVER['REMOTE_ADDR'];
    $stmt = $pdo->prepare("INSERT INTO voucher (cpf_cliente, campanha_id, codigo, data_criacao, ip_criacao) VALUES (?, ?, ?, NOW(), ?)");
    $stmt->execute([$cleaned_cpf, $data['campanha_id'], $voucherCode, $ip]);

    $email = $data['email'];
    $subject = "Confirmação de Registro - Voucher do Deck";
    $message = "Olá " . $data['nome'] . ",\n\nVocê se registrou com sucesso na promoção Chopp Grátis do Deck !\n\nSeu código do voucher é: " . $voucherCode . "\n\nApresente este código no caixa de uma das seguintes unidades para resgatar o seu Chopp Grátis:\n• DECK TRATTORIA\n• DECK SUSHI\n• DECK ABC\n\nObrigado!";
    $headers = "From: Vem Pro Deck! marketing@vemprodeck.com.br\r\n";
    mail($email, $subject, $message, $headers);
    
    return array("voucher_code" => $voucherCode);
}


function generateVoucherCode() {
    // Implementa a lógica para gerar o código do voucher (3 letras e 3 números)
    $letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $numbers = '0123456789';
    $voucherCode = '';
    for ($i = 0; $i < 3; $i++) {
        $voucherCode .= $letters[rand(0, strlen($letters) - 1)];
    }
    for ($i = 0; $i < 3; $i++) {
        $voucherCode .= $numbers[rand(0, strlen($numbers) - 1)];
    }
    return $voucherCode;
}

function cleanCPF($cpf) {
    // Remove pontos e traços do CPF
    $cpf = preg_replace('/[^0-9]/', '', $cpf);

    return $cpf;
}

function validaCPF($cpf) {
    // Extrai somente os números
    $cpf = preg_replace('/[^0-9]/', '', (string) $cpf);

    // Verifica se o CPF tem 11 caracteres
    if (strlen($cpf) !== 11) {
        return false;
    }

    // Verifica se todos os números são iguais, o que torna o CPF inválido
    if (preg_match('/(\d)\1{10}/', $cpf)) {
        return false;
    }

    // Calcula o dígito verificador
    for ($i = 9; $i < 11; $i++) {
        for ($j = 0, $sum = 0; $j < $i; $j++) {
            $sum += $cpf[$j] * (($i + 1) - $j);
        }
        $digit = ((10 * $sum) % 11) % 10;
        if ($cpf[$j] != $digit) {
            return false;
        }
    }

    return true;
}

function checkExistingCPF($cpf) {
    global $pdo;

    // Limpa o CPF
    $cleaned_cpf = cleanCPF($cpf);

    // Verifica se o CPF já existe no banco de dados
    $stmt = $pdo->prepare("SELECT COUNT(*) AS count FROM cliente WHERE cpf = :cpf");
    $stmt->execute(['cpf' => $cleaned_cpf]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row['count'] > 0;
}

?>


