<?php
// Habilitar log de erros
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Carregar configurações da nova API Monetrix
require_once __DIR__ . '/monetrix_config.php';

// Preparar diretório de logs
$logDir = __DIR__ . '/logs';
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

// Log de depuração em arquivo
$debugLog = $logDir . '/verify_' . date('Y-m-d') . '.log';
function logDebug($message) {
    global $debugLog;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($debugLog, "[$timestamp] $message\n", FILE_APPEND);
}

// Adicionar cabeçalhos CORS
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Lidar com requisições preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Log do início da requisição
logDebug("Iniciando verificação de status - Método: " . $_SERVER['REQUEST_METHOD']);

// Obter o ID da transação da query ou do post
$id = $_GET['id'] ?? $_POST['id'] ?? null;

if (!$id) {
    logDebug("ID da transação não fornecido");
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'ID da transação não fornecido'
    ]);
    exit;
}

logDebug("Verificando transação: $id");

try {
    // Primeiro, buscar no banco de dados local
    $dbPath = __DIR__ . '/../checkout/database.sqlite';
    
    if (file_exists($dbPath)) {
        $db = new PDO("sqlite:$dbPath");
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Primeiro, tentar encontrar a transação pelo ID da transação local
        $stmt = $db->prepare("SELECT * FROM pedidos WHERE transaction_id = :id OR monetrix_id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($transaction) {
            logDebug("Transação encontrada no banco de dados local: " . json_encode($transaction));
            
            // Se o status já for 'paid', retornar imediatamente
            if ($transaction['status'] === 'paid') {
                echo json_encode([
                    'success' => true,
                    'status' => 'paid',
                    'transaction_id' => $transaction['transaction_id'],
                    'monetrix_id' => $transaction['monetrix_id'],
                    'updated_at' => $transaction['updated_at'],
                    'source' => 'database'
                ]);
                exit;
            }
            
            // Se tiver monetrix_id, consultar API da Monetrix para status atualizado
            if (!empty($transaction['monetrix_id'])) {
                $monetrixId = $transaction['monetrix_id'];
                logDebug("Consultando API da Monetrix para o ID: $monetrixId");
                
                // Usar nova autenticação da API Monetrix
                $auth = getMonetrixAuth();
                
                // Iniciar a requisição cURL para a API da Monetrix
                $ch = curl_init(MONETRIX_API_URL . '/' . $monetrixId);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/json',
                    'Authorization: Basic ' . $auth
                ]);
                
                // Executar a requisição
                $response = curl_exec($ch);
                $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                
                logDebug("Resposta da API Monetrix: $http_code - $response");
                
                if ($http_code === 200) {
                    $monetrix_data = json_decode($response, true);
                    $api_status = $monetrix_data['status'] ?? 'unknown';
                    
                    // Atualizar o status no banco de dados se for diferente
                    if ($api_status !== $transaction['status']) {
                        $updateStmt = $db->prepare("UPDATE pedidos SET status = :status, updated_at = :updated_at WHERE transaction_id = :transaction_id");
                        $updateStmt->execute([
                            'status' => $api_status,
                            'updated_at' => date('Y-m-d H:i:s'),
                            'transaction_id' => $transaction['transaction_id']
                        ]);
                        logDebug("Status atualizado no banco de dados: " . $api_status);
}

                    // Retornar o status da API
                    echo json_encode([
                        'success' => true,
                        'status' => $api_status,
                        'transaction_id' => $transaction['transaction_id'],
                        'monetrix_id' => $monetrixId,
                        'updated_at' => date('Y-m-d H:i:s'),
                        'source' => 'api'
                    ]);
                    exit;
                } else {
                    logDebug("Erro ao consultar API da Monetrix. Usando status do banco");
                    // Se houver erro na API, retornar o status do banco de dados
                    echo json_encode([
                        'success' => true,
                        'status' => $transaction['status'],
                        'transaction_id' => $transaction['transaction_id'],
                        'monetrix_id' => $transaction['monetrix_id'],
                        'updated_at' => $transaction['updated_at'],
                        'source' => 'database_fallback',
                        'api_error' => 'API da Monetrix retornou código ' . $http_code
                    ]);
                    exit;
        }
            } else {
                // Se não tiver monetrix_id, retornar o status do banco de dados
                echo json_encode([
                    'success' => true,
                    'status' => $transaction['status'],
                    'transaction_id' => $transaction['transaction_id'],
                    'updated_at' => $transaction['updated_at'],
                    'source' => 'database_only'
                ]);
                exit;
            }
        }
        // Se não encontrou no banco de dados, tentar diretamente na API
    }
    
    // Se chegou aqui, ou não tem banco de dados ou não encontrou a transação
    // Tentar consultar diretamente na API da Monetrix (assumindo que id é um ID da Monetrix)
    logDebug("Transação não encontrada no banco de dados. Consultando API diretamente: $id");
        
    // Codificar as credenciais para autenticação Basic
    $auth = base64_encode(MONETRIX_PUBLIC_KEY . ':' . MONETRIX_SECRET_KEY);
    
    // Iniciar a requisição cURL para a API da Monetrix
    $ch = curl_init(MONETRIX_API_URL . '/' . $id);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Basic ' . $auth
    ]);
    
    // Executar a requisição
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    logDebug("Resposta direta da API: $http_code - $response");
    
    if ($http_code === 200) {
        $monetrix_data = json_decode($response, true);
        $api_status = $monetrix_data['status'] ?? 'unknown';
        
        echo json_encode([
            'success' => true,
            'status' => $api_status,
            'transaction_id' => $monetrix_data['externalRef'] ?? $id,
            'monetrix_id' => $id,
            'updated_at' => date('Y-m-d H:i:s'),
            'source' => 'api_direct'
        ]);
        } else {
        // Se não encontrou na API, retornar erro
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Transação não encontrada',
            'api_error' => 'API da Monetrix retornou código ' . $http_code
        ]);
        }
    } catch (Exception $e) {
    logDebug("Erro: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao verificar status: ' . $e->getMessage()
    ]);
}
?> 