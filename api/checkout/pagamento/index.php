<?php
// IMPORTANTE: Headers CORS - não remover
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization');
header('Content-Type: application/json');

// Tratar requisições OPTIONS (pre-flight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Permitir tanto GET quanto POST para facilitar o teste
$input = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
} else {
    $input = $_GET;
}

// Registrar solicitação para depuração
file_put_contents('../payment_log.txt', date('Y-m-d H:i:s') . ' - ' . json_encode($input) . ' - ' . $_SERVER['REQUEST_METHOD'] . "\n", FILE_APPEND);

// Simular dados de PIX
$pixData = [
    'success' => true,
    'qrCodeUrl' => 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=00020126580014BR.GOV.BCB.PIX0136a629534c-7df9-4e4b-9c15-9af3040720520204123.455802BR5904NOME6006CIDADE62070503***63041234',
    'pixCode' => '00020126580014BR.GOV.BCB.PIX0136a629534c-7df9-4e4b-9c15-9af3040720520204123.455802BR5904NOME6006CIDADE62070503***63041234',
    'transactionId' => 'pix_' . time(),
    'expiresAt' => date('c', strtotime('+30 minutes')),
    'message' => 'PIX gerado com sucesso via pagamento/index.php'
];

// Retornar resposta
echo json_encode($pixData);
?> 