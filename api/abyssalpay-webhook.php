<?php
/**
 * Webhook handler para receber notificações de pagamento da Abyssal Pay
 * 
 * Este arquivo recebe notificações quando um pagamento PIX é confirmado
 */

// Habilita o log de erros
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Cabeçalhos CORS
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

// Carregar configurações
require_once __DIR__ . '/abyssalpay_config.php';

// Log inicial
error_log("[AbyssalPay Webhook] 🚀 Webhook recebido");
error_log("[AbyssalPay Webhook] 📝 Método: " . $_SERVER['REQUEST_METHOD']);

try {
    // Recebe os dados do webhook
    $rawInput = file_get_contents('php://input');
    error_log("[AbyssalPay Webhook] 📦 Input bruto: " . $rawInput);
    
    $webhookData = json_decode($rawInput, true);
    
    if (!$webhookData) {
        // Tentar obter dados via $_POST
        $webhookData = $_POST;
        error_log("[AbyssalPay Webhook] 📦 Tentando dados do _POST: " . json_encode($webhookData));
    }

    if (!$webhookData) {
        error_log("[AbyssalPay Webhook] ❌ Dados inválidos recebidos");
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Dados inválidos recebidos']);
        exit;
    }

    error_log("[AbyssalPay Webhook] 📄 Dados recebidos: " . json_encode($webhookData, JSON_PRETTY_PRINT));

    // Extrair informações do webhook
    $status = $webhookData['status'] ?? null;
    $idTransaction = $webhookData['idTransaction'] ?? null;
    $typeTransaction = $webhookData['typeTransaction'] ?? null;

    if (!$idTransaction) {
        error_log("[AbyssalPay Webhook] ❌ ID da transação não encontrado");
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID da transação não encontrado']);
        exit;
    }

    error_log("[AbyssalPay Webhook] 🔍 Processando transação: " . $idTransaction);
    error_log("[AbyssalPay Webhook] 📊 Status: " . $status);
    error_log("[AbyssalPay Webhook] 📊 Tipo: " . $typeTransaction);

    // Conecta ao banco de dados
    $dbPath = __DIR__ . '/checkout/database.sqlite';
    $db = new PDO("sqlite:$dbPath");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Buscar pedido no banco
    $stmt = $db->prepare("SELECT * FROM pedidos WHERE transaction_id = :transaction_id");
    $stmt->execute(['transaction_id' => $idTransaction]);
    $pedido = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$pedido) {
        error_log("[AbyssalPay Webhook] ⚠️ Pedido não encontrado no banco: " . $idTransaction);
        // Não retornar erro, apenas logar - pode ser uma transação antiga
        echo json_encode(['success' => true, 'message' => 'Pedido não encontrado, mas webhook processado']);
        exit;
    }

    // Atualizar status do pedido
    $novoStatus = 'pending';
    if ($status === 'paid') {
        $novoStatus = 'paid';
    } elseif ($status === 'failed' || $status === 'error' || $status === 'canceled') {
        $novoStatus = 'failed';
    }

    $stmt = $db->prepare("UPDATE pedidos SET status = :status, updated_at = :updated_at WHERE transaction_id = :transaction_id");
    $stmt->execute([
        'status' => $novoStatus,
        'updated_at' => date('c'),
        'transaction_id' => $idTransaction
    ]);

    error_log("[AbyssalPay Webhook] ✅ Status atualizado para: " . $novoStatus);

    // Retornar sucesso
    echo json_encode([
        'success' => true,
        'message' => 'Webhook processado com sucesso',
        'transaction_id' => $idTransaction,
        'status' => $novoStatus
    ]);

} catch (Exception $e) {
    error_log("[AbyssalPay Webhook] ❌ Erro: " . $e->getMessage());
    error_log("[AbyssalPay Webhook] 🔍 Stack trace: " . $e->getTraceAsString());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao processar webhook: ' . $e->getMessage()
    ]);
}
?>

