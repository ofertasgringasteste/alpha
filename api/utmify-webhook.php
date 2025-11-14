<?php
header('Content-Type: application/json');

// Habilita exibição de erros para depuração (remover em produção)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Recebe os dados do webhook
$webhookData = json_decode(file_get_contents('php://input'), true);
$logFile = 'webhook_log.txt';

// Função para notificar a UTMify
function notificarUtmify($data) {
    // Log dos dados recebidos
    file_put_contents('utmify_log.txt', date('Y-m-d H:i:s') . " - Dados recebidos: " . json_encode($data) . PHP_EOL, FILE_APPEND);

    // Verifica se é um evento de pagamento processado
    if ($data['event'] === 'transaction.processed' && $data['data']['status'] === 'paid') {
        // Log específico para pagamentos
        file_put_contents('utmify_paid_log.txt', date('Y-m-d H:i:s') . " - Pagamento aprovado: " . $data['data']['id'] . PHP_EOL, FILE_APPEND);
        
        // Aqui você implementa a integração real com UTMify
        // Este é apenas um exemplo; você deve adaptá-lo conforme necessário
        
        // Obtem informações do cliente e produto
        $cliente = $data['data']['buyer'] ?? [];
        $offer = $data['data']['offer'] ?? [];
        $tracking = $data['data']['tracking'] ?? [];

        // Prepara os dados para envio à UTMify
        $utmifyData = [
            'transaction_id' => $data['data']['id'],
            'amount' => $data['data']['total_amount'] / 100, // Converte centavos para reais
            'customer' => [
                'name' => $cliente['name'] ?? '',
                'email' => $cliente['email'] ?? '',
                'document' => $cliente['document'] ?? '',
                'phone' => $cliente['phone'] ?? ''
            ],
            'product' => [
                'name' => $offer['name'] ?? '',
                'price' => $offer['discount_price'] ? ($offer['discount_price'] / 100) : 0,
                'quantity' => $offer['quantity'] ?? 1
            ],
            'tracking' => $tracking
        ];
        
        // Log dos dados preparados
        file_put_contents('utmify_pixel_log.txt', date('Y-m-d H:i:s') . " - Dados UTMify: " . json_encode($utmifyData) . PHP_EOL, FILE_APPEND);
        
        // Retorna sucesso (em uma implementação real, você enviaria estes dados para a UTMify)
        return [
            'success' => true,
            'message' => 'Dados enviados para UTMify'
        ];
    } 
    // Verifica se é um evento de transação criada (pendente)
    elseif ($data['event'] === 'transaction.created' && $data['data']['status'] === 'pending') {
        // Log para transações pendentes
        file_put_contents('utmify_pendente_log.txt', date('Y-m-d H:i:s') . " - Pagamento pendente: " . $data['data']['id'] . PHP_EOL, FILE_APPEND);
        
        // Aqui você implementaria o código para registrar uma transação pendente na UTMify
        // se necessário
        
        return [
            'success' => true,
            'message' => 'Pagamento pendente registrado'
        ];
    }
    
    return [
        'success' => false,
        'message' => 'Evento não tratado'
    ];
}

// Se for uma chamada direta (webhook da BuckPay)
if ($webhookData) {
    $resultado = notificarUtmify($webhookData);
    echo json_encode($resultado);
    }
// Se for uma chamada de função (interna)
else {
    // Esta parte é usada quando o arquivo é incluído por outro script
    // e a função notificarUtmify é chamada diretamente
} 