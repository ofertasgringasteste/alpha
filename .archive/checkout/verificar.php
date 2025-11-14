<?php
// Habilitar log de erros
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Configurações da API Monetrix
define('MONETRIX_API_URL', 'https://api.monetrix.store/v1/transactions');
define('MONETRIX_PUBLIC_KEY', 'pk__qJrExlJeIQpV3RrF157NP-Wk48qlza1_9mHCzo-69AbBsqr');
define('MONETRIX_SECRET_KEY', 'sk_cS7rqPG-8Q9BGcKy1hWpXJ-m1s3J9mi9s0f2CmvU15AIPcuo');

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
logDebug("Iniciando verificação de status - Método: " . $_SERVER['REQUEST_METHOD'] . " - URL: " . $_SERVER['REQUEST_URI']);

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
    // Conecta ao SQLite
    $dbPath = __DIR__ . '/database.sqlite';
    logDebug("Conectando ao banco de dados: $dbPath");
    
    if (!file_exists($dbPath)) {
        logDebug("Banco de dados não encontrado");
        echo json_encode([
            'status' => 'error',
            'message' => 'Banco de dados não encontrado'
        ]);
        exit;
    }
    
    $db = new PDO("sqlite:$dbPath");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Busca o status do pagamento no banco local (tanto por transaction_id quanto por monetrix_id)
    $stmt = $db->prepare("SELECT * FROM pedidos WHERE transaction_id = :id OR monetrix_id = :id LIMIT 1");
    $stmt->execute(['id' => $id]);
    $pedido = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$pedido) {
        logDebug("Pedido não encontrado no banco local: $id");
        
        // Para fins de teste em ambiente de desenvolvimento, retornar status pendente
        if (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || 
            strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false) {
            echo json_encode([
                'success' => true,
                'status' => 'pending',
                'transaction_id' => $id,
                'data' => [
                    'amount' => 0,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                    'customer' => [
                        'name' => 'Cliente Teste',
                        'email' => 'teste@exemplo.com',
                        'document' => '12345678901'
                    ]
                ]
            ]);
            exit;
        }
        
        echo json_encode([
            'success' => false,
            'status' => 'error',
            'message' => 'Pedido não encontrado'
        ]);
        exit;
    }
    
    logDebug("Pedido encontrado no banco local: " . json_encode($pedido));
    
    // Se temos o ID da Monetrix, verificar diretamente com a API deles
    if (!empty($pedido['monetrix_id'])) {
        logDebug("Consultando status na API da Monetrix para ID: " . $pedido['monetrix_id']);
        
        // Autenticação para API Monetrix
        $auth = base64_encode(MONETRIX_PUBLIC_KEY . ':' . MONETRIX_SECRET_KEY);
        
        // Consultar status atual na API Monetrix
        $ch = curl_init(MONETRIX_API_URL . '/' . $pedido['monetrix_id']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Basic ' . $auth
        ]);
        
        $api_response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if ($api_response === false) {
            logDebug("Erro na requisição cURL: " . curl_error($ch));
        } else {
            logDebug("Resposta API Monetrix HTTP: $http_code - $api_response");
            
            if ($http_code === 200) {
                $api_data = json_decode($api_response, true);
                
                // Atualizar status no banco de dados se mudou
                if (isset($api_data['status']) && $api_data['status'] !== $pedido['status']) {
                    logDebug("Status atualizado: " . $pedido['status'] . " -> " . $api_data['status']);
                    
                    $updateStmt = $db->prepare("UPDATE pedidos SET status = :status, updated_at = :updated_at WHERE transaction_id = :transaction_id");
                    $updateStmt->execute([
                        'status' => $api_data['status'],
                        'updated_at' => date('Y-m-d H:i:s'),
                        'transaction_id' => $id
                    ]);
                    
                    // Atualizar o objeto do pedido para a resposta
                    $pedido['status'] = $api_data['status'];
                    $pedido['updated_at'] = date('Y-m-d H:i:s');
                }
            }
        }
        
        curl_close($ch);
    }

    // Mapear status da Monetrix para os status que o front-end espera
    $status_mapeado = $pedido['status'];
    if ($pedido['status'] === 'paid' || $pedido['status'] === 'approved' || $pedido['status'] === 'completed') {
        $status_mapeado = 'paid';
    } else if ($pedido['status'] === 'pending' || $pedido['status'] === 'waiting_payment') {
        $status_mapeado = 'pending';
    } else if ($pedido['status'] === 'failed' || $pedido['status'] === 'canceled' || $pedido['status'] === 'refunded') {
        $status_mapeado = 'failed';
    }

    echo json_encode([
        'success' => true,
        'status' => $status_mapeado,
        'transaction_id' => $pedido['transaction_id'],
        'data' => [
            'amount' => $pedido['valor'],
            'created_at' => $pedido['created_at'],
            'updated_at' => $pedido['updated_at'],
            'customer' => [
                'name' => $pedido['nome'],
                'email' => $pedido['email'],
                'document' => $pedido['cpf']
            ]
        ]
    ]);

} catch (Exception $e) {
    logDebug("❌ Erro: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'status' => 'error',
        'message' => 'Erro ao verificar o status do pagamento: ' . $e->getMessage()
    ]);
} 