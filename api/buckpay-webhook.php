<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Habilita exibição de erros para depuração (remover em produção)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Lidar com requisições OPTIONS para CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Log para registrar a chamada do webhook
$logFile = 'webhook_log.txt';
file_put_contents($logFile, date('Y-m-d H:i:s') . " - BuckPay Webhook recebido\n", FILE_APPEND);
file_put_contents($logFile, date('Y-m-d H:i:s') . " - Method: " . $_SERVER['REQUEST_METHOD'] . "\n", FILE_APPEND);

// Recebe os dados do webhook
$rawInput = file_get_contents('php://input');
file_put_contents($logFile, date('Y-m-d H:i:s') . " - Raw Input: " . $rawInput . "\n", FILE_APPEND);

$webhookData = json_decode($rawInput, true);
if (!$webhookData && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Tentar obter dados via $_POST
    $webhookData = $_POST;
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - Usando dados do _POST: " . json_encode($webhookData) . "\n", FILE_APPEND);
}

if (!$webhookData) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Dados inválidos recebidos']);
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - Erro: Dados inválidos recebidos\n", FILE_APPEND);
    exit;
}

// Log dos dados recebidos
file_put_contents($logFile, date('Y-m-d H:i:s') . " - Dados: " . json_encode($webhookData, JSON_PRETTY_PRINT) . "\n", FILE_APPEND);

// Verifica o tipo de evento
$event = $webhookData['event'] ?? '';
$data = $webhookData['data'] ?? [];

if (!$event || !$data) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Evento ou dados ausentes']);
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - Erro: Evento ou dados ausentes\n", FILE_APPEND);
    exit;
}

// Garantir que a pasta transactions exista
if (!is_dir('transactions')) {
    mkdir('transactions', 0755, true);
}

// Processar o evento
switch ($event) {
    case 'transaction.processed': // Pagamento aprovado
        if ($data['status'] === 'paid') {
            // Registra o pagamento aprovado
            file_put_contents('payment_log.txt', date('Y-m-d H:i:s') . " - Pagamento aprovado: " . json_encode($data, JSON_PRETTY_PRINT) . "\n", FILE_APPEND);
            
            // Atualiza o status da transação no arquivo local (se existir)
            $transactionFile = 'transactions/' . $data['id'] . '.json';
            if (file_exists($transactionFile)) {
                $transactionInfo = json_decode(file_get_contents($transactionFile), true);
                $transactionInfo['status'] = 'paid';
                $transactionInfo['paid_at'] = date('Y-m-d H:i:s');
                file_put_contents($transactionFile, json_encode($transactionInfo, JSON_PRETTY_PRINT));
            } else {
                // Se o arquivo não existe, criamos um novo com as informações do webhook
                $transactionInfo = [
                    'id' => $data['id'],
                    'status' => 'paid',
                    'paid_at' => date('Y-m-d H:i:s'),
                    'data' => $data
                ];
                file_put_contents($transactionFile, json_encode($transactionInfo, JSON_PRETTY_PRINT));
            }
            
            // Integração com UTMify para transações pagas
            integrarComUtmify($webhookData, 'paid');
            
            echo json_encode(['success' => true, 'message' => 'Pagamento processado com sucesso']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Status de pagamento inválido']);
            file_put_contents($logFile, date('Y-m-d H:i:s') . " - Erro: Status de pagamento inválido: " . $data['status'] . "\n", FILE_APPEND);
        }
        break;
        
    case 'transaction.created': // Pagamento pendente
        // Registra a transação pendente
        file_put_contents('payment_log.txt', date('Y-m-d H:i:s') . " - Pagamento pendente: " . json_encode($data, JSON_PRETTY_PRINT) . "\n", FILE_APPEND);
        
        // Criar ou atualizar o registro da transação
        $transactionFile = 'transactions/' . $data['id'] . '.json';
        $transactionInfo = [
            'id' => $data['id'],
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s'),
            'data' => $data
        ];
        file_put_contents($transactionFile, json_encode($transactionInfo, JSON_PRETTY_PRINT));
        
        // Integração com UTMify para transações pendentes
        integrarComUtmify($webhookData, 'pending');
        
        echo json_encode(['success' => true, 'message' => 'Transação pendente registrada']);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Evento não reconhecido: ' . $event]);
        file_put_contents($logFile, date('Y-m-d H:i:s') . " - Evento não reconhecido: " . $event . "\n", FILE_APPEND);
        break;
}

/**
 * Função para integrar com a UTMify
 * @param array $webhookData Dados do webhook
 * @param string $status Status da transação (paid ou pending)
 */
function integrarComUtmify($webhookData, $status) {
    $logFile = 'utmify_integration_log.txt';
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - Integrando com UTMify: " . $status . "\n", FILE_APPEND);
    
    // Determinar qual arquivo de integração usar
    $integrationFile = '';
    if ($status === 'paid') {
        $integrationFile = 'utmify-webhook.php'; // Integração para pagamentos confirmados
    } else {
        $integrationFile = 'utmify-pendente.php'; // Integração para pagamentos pendentes
    }
    
    // Verificar se o arquivo existe
    if (file_exists($integrationFile)) {
        file_put_contents($logFile, date('Y-m-d H:i:s') . " - Arquivo de integração encontrado: " . $integrationFile . "\n", FILE_APPEND);
        
        try {
            // Incluir o arquivo
            include_once $integrationFile;
            
            // Verificar se a função existe
            $functionName = ($status === 'paid') ? 'notificarUtmify' : 'notificarPendente';
            
            if (function_exists($functionName)) {
                // Chamar a função
                file_put_contents($logFile, date('Y-m-d H:i:s') . " - Chamando função: " . $functionName . "\n", FILE_APPEND);
                $result = $functionName($webhookData);
                file_put_contents($logFile, date('Y-m-d H:i:s') . " - Resultado: " . json_encode($result) . "\n", FILE_APPEND);
            } else {
                // Fazer uma chamada HTTP para o arquivo
                file_put_contents($logFile, date('Y-m-d H:i:s') . " - Função não encontrada, tentando HTTP request\n", FILE_APPEND);
                
                $ch = curl_init('http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/' . $integrationFile);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($webhookData));
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                
                file_put_contents($logFile, date('Y-m-d H:i:s') . " - HTTP Response: " . $response . " (Code: " . $httpCode . ")\n", FILE_APPEND);
            }
        } catch (Exception $e) {
            file_put_contents($logFile, date('Y-m-d H:i:s') . " - Erro: " . $e->getMessage() . "\n", FILE_APPEND);
        }
    } else {
        file_put_contents($logFile, date('Y-m-d H:i:s') . " - Arquivo de integração não encontrado: " . $integrationFile . "\n", FILE_APPEND);
    }
}
?> 