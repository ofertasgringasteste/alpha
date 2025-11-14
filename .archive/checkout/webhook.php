<?php
// Habilitar log de erros
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Preparar diretório de logs
$logDir = __DIR__ . '/logs';
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

// Log de depuração em arquivo
$debugLog = $logDir . '/webhook_' . date('Y-m-d') . '.log';
function logDebug($message) {
    global $debugLog;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($debugLog, "[$timestamp] $message\n", FILE_APPEND);
}

// Adicionar cabeçalhos CORS
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Lidar com requisições OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Log da requisição
logDebug("Webhook recebido - Método: " . $_SERVER['REQUEST_METHOD']);

// Capturar dados brutos do webhook
$input = file_get_contents('php://input');
logDebug("Dados recebidos: " . $input);

// Decodificar os dados JSON
$data = json_decode($input, true);
if (!$data) {
    logDebug("Erro ao decodificar JSON");
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON']);
    exit;
}

logDebug("Webhook decodificado: " . json_encode($data, JSON_PRETTY_PRINT));

// Verificar se é um evento de webhook válido
$event_type = $data['type'] ?? '';
logDebug("Tipo de evento: " . $event_type);

// Extrair dados relevantes baseado no formato do webhook da Monetrix
$transaction_data = $data['data'] ?? [];

// Verificar se temos um ID de transação
if (!isset($transaction_data['id'])) {
    logDebug("ID da transação não encontrado no webhook");
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Transaction ID not found']);
    exit;
}

// Obter dados importantes
$transaction_id = $transaction_data['id'];
$status = $transaction_data['status'] ?? 'unknown';
$external_ref = $transaction_data['externalRef'] ?? '';

logDebug("Processando transação: $transaction_id, Status: $status, External Ref: $external_ref");

// Verificar se é um status que nos interessa (paid, refunded, failed)
if ($status === 'paid' || $status === 'refunded' || $status === 'failed' || 
    $status === 'approved' || $status === 'completed' || $status === 'canceled') {
    try {
        // Conectar ao banco de dados SQLite
        $dbPath = __DIR__ . '/database.sqlite';
        if (file_exists($dbPath)) {
            $db = new PDO("sqlite:$dbPath");
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Procurar a transação pelo ID da Monetrix
            $stmt = $db->prepare("SELECT * FROM pedidos WHERE monetrix_id = :monetrix_id LIMIT 1");
            $stmt->execute(['monetrix_id' => $transaction_id]);
            $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($transaction) {
                logDebug("Transação encontrada no banco de dados local: " . $transaction['transaction_id']);
                
                // Atualizar o status da transação
                $updateStmt = $db->prepare("UPDATE pedidos SET status = :status, updated_at = :updated_at WHERE monetrix_id = :monetrix_id");
                $updateStmt->execute([
                    'status' => $status,
                    'updated_at' => date('Y-m-d H:i:s'),
                    'monetrix_id' => $transaction_id
                ]);
                
                logDebug("Status da transação atualizado para: $status");
                
                // Realizar ações adicionais com base no status
                if ($status === 'paid' || $status === 'approved' || $status === 'completed') {
                    // Processar pagamento bem-sucedido
                    logDebug("Pagamento aprovado! Registrando conversão...");
                    
                    // Integração com UTMify para pagamentos aprovados
                    try {
                        include_once '../api/utmify-integration.php';
                        
                        $dadosUTMify = [
                            'transaction_id' => $transaction['transaction_id'],
                            'created_at' => $transaction['created_at'],
                            'paid_at' => date('Y-m-d H:i:s'),
                            'amount_cents' => $transaction['valor'],
                            'customer' => [
                                'name' => $transaction['nome'],
                                'email' => $transaction['email'],
                                'phone' => preg_replace('/[^0-9]/', '', $transaction['telefone'] ?? ''),
                                'document' => preg_replace('/[^0-9]/', '', $transaction['cpf'])
                            ],
                            'products' => [
                                [
                                    'id' => 'produto-' . $transaction['transaction_id'],
                                    'name' => 'Pedido Phamela Gourmet',
                                    'planId' => null,
                                    'planName' => null,
                                    'quantity' => 1,
                                    'priceInCents' => $transaction['valor']
                                ]
                            ],
                            'utm_params' => extrairParametrosUTM($transaction['utm_params'])
                        ];
                        
                        $resultadoUTMify = enviarPixPagoUTMify($dadosUTMify);
                        logDebug("UTMify PIX Pago: " . json_encode($resultadoUTMify));
                        
                    } catch (Exception $e) {
                        logDebug("Erro ao notificar UTMify (PIX Pago): " . $e->getMessage());
                    }
                }
            } else {
                // Se não encontrou pelo ID da Monetrix, tentar pelo external_ref
                if (!empty($external_ref)) {
                    $stmt = $db->prepare("SELECT * FROM pedidos WHERE transaction_id = :transaction_id LIMIT 1");
                    $stmt->execute(['transaction_id' => $external_ref]);
                    $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($transaction) {
                        logDebug("Transação encontrada pelo external_ref: " . $external_ref);
                        
                        // Atualizar o status e adicionar o ID da Monetrix
                        $updateStmt = $db->prepare("UPDATE pedidos SET status = :status, monetrix_id = :monetrix_id, updated_at = :updated_at WHERE transaction_id = :transaction_id");
                        $updateStmt->execute([
                            'status' => $status,
                            'monetrix_id' => $transaction_id,
                            'updated_at' => date('Y-m-d H:i:s'),
                            'transaction_id' => $external_ref
                        ]);
                        
                        logDebug("Status da transação atualizado para: $status");
                        
                        // Realizar ações adicionais com base no status
                        if ($status === 'paid' || $status === 'approved' || $status === 'completed') {
                            // Processar pagamento bem-sucedido
                            logDebug("Pagamento aprovado! Registrando conversão...");
                            
                            // Integração com UTMify para pagamentos aprovados
                            if (file_exists('../api/utmify-webhook.php')) {
                                try {
                                    include_once '../api/utmify-webhook.php';
                                    if (function_exists('notificarUtmify')) {
                                        notificarUtmify([
                                            'event' => 'transaction.processed',
                                            'data' => [
                                                'id' => $transaction['transaction_id'],
                                                'status' => 'paid',
                                                'externalId' => $external_ref,
                                                'valor' => $transaction['valor'],
                                                'metadata' => json_decode($transaction['utm_params'] ?? '{}', true)
                                            ]
                                        ]);
                                        logDebug("UTMify notificado com sucesso");
                                    }
                                } catch (Exception $e) {
                                    logDebug("Erro ao notificar UTMify: " . $e->getMessage());
                                }
                            }
                        }
                    } else {
                        logDebug("Transação não encontrada no banco de dados");
                        
                        // Criar um novo registro para esta transação
                        $insertStmt = $db->prepare("INSERT INTO pedidos (transaction_id, monetrix_id, status, valor, created_at, updated_at) 
                                                   VALUES (:transaction_id, :monetrix_id, :status, :valor, :created_at, :updated_at)");
                        $insertStmt->execute([
                            'transaction_id' => $external_ref ?: 'MON-'.$transaction_id,
                            'monetrix_id' => $transaction_id,
                            'status' => $status,
                            'valor' => $transaction_data['amount'] ?? 0,
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s')
                        ]);
                        
                        logDebug("Novo registro de transação criado");
                    }
                } else {
                    logDebug("Transação não encontrada e não há external_ref para buscar");
                }
            }
        } else {
            logDebug("Banco de dados não encontrado: $dbPath");
        }
    } catch (Exception $e) {
        logDebug("Erro ao processar webhook: " . $e->getMessage());
    }
}

// Responder com sucesso
http_response_code(200);
echo json_encode(['success' => true, 'message' => 'Webhook processado com sucesso']);
?> 