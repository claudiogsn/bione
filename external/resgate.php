<?php
session_start();
require_once '../api/config.php';

if(isset($_GET['username'])){
    $username = $_GET['username'];
    $_SESSION['username'] = $username;
}
if(isset($_POST['username'])){
    $username = $_POST['username'];
    $_SESSION['username'] = $username;
}

// Função para buscar voucher e cliente no banco de dados
function buscarVoucherCliente($codigo, $cpf) {
    global $pdo;
    try {
        $sql = "SELECT v.voucher_id, c.nome, c.email, v.cpf_cliente, v.codigo, c.data_nascimento, v.data_criacao 
                FROM voucher v 
                INNER JOIN cliente c ON v.cpf_cliente = c.cpf 
                WHERE v.codigo = :codigo AND v.cpf_cliente = :cpf AND v.data_uso IS NULL";

        $stmt = $pdo->prepare($sql);
        $stmt->execute(array(':codigo' => $codigo, ':cpf' => $cpf));
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result;
    } catch (PDOException $e) {
        return $e->getMessage();
    }
}

// Função para resgatar voucher
function resgatarVoucher($voucher_id, $username) {
    global $pdo;

    // Obtendo o IP do cliente
    $ip_uso = $_SERVER['REMOTE_ADDR'];
    $user = $username;
    $data_uso = date("Y-m-d H:i:s");

    try {
        $sql = "UPDATE voucher 
                SET data_uso = :data_uso, ip_uso = :ip_uso, usuario_uso_id = :user 
                WHERE voucher_id = :voucher_id";

        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':data_uso', $data_uso);
        $stmt->bindParam(':ip_uso', $ip_uso);
        $stmt->bindParam(':user', $user);
        $stmt->bindParam(':voucher_id', $voucher_id);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        echo json_encode(array("message" => "Nenhum voucher encontrado.{$e->getMessage()}"));
        return false;
    }
}


// Verifica se a requisição é via POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Verifica se os campos voucher e cpf foram enviados
    if (isset($_POST['voucher']) && isset($_POST['cpf'])) {
        $voucher = $_POST['voucher'];
        $cpf = $_POST['cpf'];
        $codigo = strtoupper($voucher);
        // Busca voucher e cliente no banco de dados
        $dadosVoucherCliente = buscarVoucherCliente($codigo, $cpf);

        if ($dadosVoucherCliente) {
            // Voucher encontrado
            echo json_encode($dadosVoucherCliente);
        } else {
            // Voucher não encontrado
            http_response_code(404);
            echo json_encode(array("message" => "Nenhum voucher encontrado."));
        }
        exit;
    }

    // Verifica se os campos voucher_id, data_uso, ip_uso e userid foram enviados
    if (isset($_POST['voucher_id'])&& isset($_POST['username'])) {

        if($_POST['voucher_id'] == ''){
            http_response_code(400);
            echo json_encode(array("message" => "Voucher não informado."));
            exit;
        }
        $voucher_id = $_POST['voucher_id'];
        $username = $_POST['username'];
        // Resgata o voucher
        if (resgatarVoucher($voucher_id, $username)) {
            // Voucher resgatado com sucesso
            echo json_encode(array("message" => "Voucher resgatado com sucesso."));
        } else {
            // Falha ao resgatar o voucher
            http_response_code(500);
            echo json_encode(array("message" => "Falha ao resgatar voucher."));
        }
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resgatar Voucher</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <div class="container mx-auto pt-10">
        <div class="max-w-lg mx-auto bg-white rounded-lg shadow-md overflow-hidden">
            <div class="p-4">
                <h1 class="text-xl font-semibold text-gray-700">Resgatar Voucher</h1>
                <div class="mt-4">
                    <label for="voucher" class="block text-sm font-medium text-gray-700">Voucher</label>
                    <input type="text" id="voucher" name="voucher" class="mt-1 p-2 w-full border rounded-md">
                </div>
                <div class="mt-4">
                    <label for="cpf" class="block text-sm font-medium text-gray-700">CPF</label>
                    <input type="text" id="cpf" name="cpf" class="mt-1 p-2 w-full border rounded-md">
                </div>
                <div class="mt-6">
                    <button id="buscarBtn" class="bg-green-500 text-white py-2 px-4 rounded-md hover:bg-green-600">Buscar Voucher</button>
                </div>
            </div>
        </div>
    </div>

    <div id="voucherCard" class="container mx-auto mt-6 hidden">
        <div class="max-w-lg mx-auto bg-white rounded-lg shadow-md overflow-hidden">
            <div class="p-4">
                <h1 class="text-xl font-semibold text-gray-700">Informações do Voucher</h1>
                <input type="hidden" id="voucher_id">
                <input type="hidden" id="username" value="<?php echo $username; ?>">
                <div id="dadosVoucher" class="mt-4"></div>
                <div class="mt-6">
                    <button id="resgatarVoucherBtn" class="bg-green-500 text-white py-2 px-4 rounded-md hover:bg-green-600">Resgatar Voucher</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div id="modal" class="fixed z-10 inset-0 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full" role="dialog" aria-modal="true" aria-labelledby="modal-headline">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                Voucher Resgatado com Sucesso
                            </h3>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button id="fecharModal" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-green-600 text-base font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 sm:ml-3 sm:w-auto sm:text-sm">Fechar</button>
                </div>
            </div>
        </div>
    </div>

</body>


    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const buscarBtn = document.getElementById('buscarBtn');
            const resgatarVoucherBtn = document.getElementById('resgatarVoucherBtn');
            const voucherCard = document.getElementById('voucherCard');
            const modal = document.getElementById('modal');
            const fecharModal = document.getElementById('fecharModal');

            buscarBtn.addEventListener('click', function() {
                const voucher = document.getElementById('voucher').value;
                const cpf = document.getElementById('cpf').value;
                const username = document.getElementById('username').value; 

                // Requisição AJAX para buscar voucher
                const xhr = new XMLHttpRequest();
                xhr.onreadystatechange = function() {
                    if (xhr.readyState === XMLHttpRequest.DONE) {
                        if (xhr.status === 200) {
                            // Voucher encontrado, exibe os dados
                            const dadosVoucher = JSON.parse(xhr.responseText);
                            exibirDadosVoucher(dadosVoucher);
                        } else {
                            // Voucher não encontrado
                            alert('Nenhum voucher encontrado.');
                        }
                    }
                };

                xhr.open('POST', '<?php echo $_SERVER['PHP_SELF']; ?>');
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                xhr.send(`voucher=${voucher}&cpf=${cpf}&username=${username}`);
            });

            resgatarVoucherBtn.addEventListener('click', function() {
                const voucher_id = document.getElementById('voucher_id').value;
                const username = document.getElementById('username').value;

                console.log(voucher_id);

                // Requisição AJAX para resgatar voucher
                const xhr = new XMLHttpRequest();
                xhr.onreadystatechange = function() {
                    if (xhr.readyState === XMLHttpRequest.DONE) {
                        if (xhr.status === 200) {
                            // Voucher resgatado com sucesso
                            modal.classList.remove('hidden');
                            document.getElementById('voucher').value = '';
                            document.getElementById('cpf').value = '';
                            document.getElementById('voucherCard').classList.add('hidden');
                        } else {
                            // Falha ao resgatar voucher
                            alert('Falha ao resgatar voucher.');
                        }
                    }
                };

                xhr.open('POST', '<?php echo $_SERVER['PHP_SELF']; ?>');
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                xhr.send(`voucher_id=${voucher_id}&username=${username}`);
            });

            fecharModal.addEventListener('click', function() {
                modal.classList.add('hidden');
            });

            function exibirDadosVoucher(dadosVoucher) {
                
                voucherCard.classList.remove('hidden');
                const dadosVoucherDiv = document.getElementById('dadosVoucher');
                document.getElementById('voucher_id').value = dadosVoucher.voucher_id;
                console.log(dadosVoucher.voucher_id);
                dadosVoucherDiv.innerHTML = `
                    <p><strong>Nome:</strong> ${dadosVoucher.nome}</p>
                    <p><strong>E-mail:</strong> ${dadosVoucher.email}</p>
                    <p><strong>CPF:</strong> ${dadosVoucher.cpf_cliente}</p>
                    <p><strong>Código:</strong> ${dadosVoucher.codigo}</p>
                    <p><strong>Data de Nascimento:</strong> ${dadosVoucher.data_nascimento}</p>
                    <p><strong>Data de Criação:</strong> ${dadosVoucher.data_criacao}</p>
                `;
            }
        });
    </script>
</body>
</html>
