<?php
header('Content-Type: application/json');

// Habilita exibição de erros para depuração (remover em produção)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Log para depuração
$logFile = 'utmify_pendente_log.txt';

/**
 * Função para notificar a UTMify sobre pagamentos pendentes
 * @param array $data Os dados do webhook recebido
 * @return array Resultado da operação
 */
function notificarPendente($data) {
    global $logFile;
    
    // Log dos dados recebidos
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - Notificação pendente recebida: " . json_encode($data, JSON_PRETTY_PRINT) . PHP_EOL, FILE_APPEND);
    
    // Verificar se os dados têm o formato esperado
    if (!isset($data['data']) || !isset($data['event']) || $data['event'] !== 'transaction.created') {
        file_put_contents($logFile, date('Y-m-d H:i:s') . " - Formato de dados inválido para pendente" . PHP_EOL, FILE_APPEND);
        return ['success' => false, 'message' => 'Formato de dados inválido'];
    }
    
    // Extrair informações relevantes
    $transactionData = $data['data'];
    $cliente = $transactionData['buyer'] ?? [];
    $offer = $transactionData['offer'] ?? [];
    $tracking = $transactionData['tracking'] ?? [];
    
    // Preparar dados para enviar à UTMify
    $utmifyData = [
        'transaction_id' => $transactionData['id'],
        'status' => 'pending',
        'amount' => $transactionData['total_amount'] / 100, // Converter de centavos para reais
        'payment_method' => 'pix',
        'customer' => [
            'name' => $cliente['name'] ?? '',
            'email' => $cliente['email'] ?? '',
            'document' => $cliente['document'] ?? '',
            'phone' => $cliente['phone'] ?? ''
        ],
        'product' => [
            'name' => $offer['name'] ?? '',
            'price' => isset($offer['discount_price']) ? ($offer['discount_price'] / 100) : 0,
            'quantity' => $offer['quantity'] ?? 1
        ],
        'utm' => [
            'source' => $tracking['utm_source'] ?? $tracking['source'] ?? 'direct',
            'medium' => $tracking['utm_medium'] ?? $tracking['medium'] ?? 'none',
            'campaign' => $tracking['utm_campaign'] ?? $tracking['campaign'] ?? '',
            'term' => $tracking['utm_term'] ?? $tracking['term'] ?? '',
            'content' => $tracking['utm_content'] ?? $tracking['content'] ?? ''
        ],
        'timestamp' => time(),
        'event_type' => 'purchase_pending'
    ];
    
    // Log dos dados preparados
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - Dados preparados: " . json_encode($utmifyData, JSON_PRETTY_PRINT) . PHP_EOL, FILE_APPEND);
    
    // Aqui você implementaria a chamada real à API da UTMify
    // Este é apenas um exemplo de simulação
    
    // Simular chamada bem-sucedida
    return [
        'success' => true,
        'message' => 'Pagamento pendente registrado na UTMify',
        'data' => $utmifyData
    ];
}

// Se este arquivo for chamado diretamente
if (basename($_SERVER['SCRIPT_FILENAME']) === basename(__FILE__)) {
    // Receber dados do webhook
    $webhookData = json_decode(file_get_contents('php://input'), true);
    
    // Se não houver dados JSON válidos, tentar $_POST
    if (!$webhookData && !empty($_POST)) {
        $webhookData = $_POST;
    }
    
    // Se ainda não tiver dados, retornar erro
    if (!$webhookData) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Dados inválidos ou não recebidos']);
        exit;
    }
    
    // Processar a notificação e retornar o resultado
    $resultado = notificarPendente($webhookData);
    echo json_encode($resultado);
}
?>
