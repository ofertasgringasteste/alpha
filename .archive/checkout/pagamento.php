<?php
// No início do arquivo, antes de qualquer saída
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Preparar diretório de logs
$logDir = __DIR__ . '/logs';
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

// Log de depuração em arquivo
$debugLog = $logDir . '/payment_' . date('Y-m-d') . '.log';
function logDebug($message) {
    global $debugLog;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($debugLog, "[$timestamp] $message\n", FILE_APPEND);
}

// Registrar início da requisição
logDebug("Iniciando processamento de pagamento - Método: " . $_SERVER['REQUEST_METHOD']);

// Adicionar cabeçalhos CORS
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Lidar com requisições preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Log da requisição recebida
$input_raw = file_get_contents('php://input');
logDebug("Dados recebidos: $input_raw");

// Credenciais da API Monetrix (originais que funcionam)
define('MONETRIX_API_URL', 'https://api.monetrix.store/v1/transactions');
define('MONETRIX_API_KEY', 'pk__qJrExlJeIQpV3RrF157NP-Wk48qlza1_9mHCzo-69AbBsqr');
define('MONETRIX_API_SECRET', 'sk_cS7rqPG-8Q9BGcKy1hWpXJ-m1s3J9mi9s0f2CmvU15AIPcuo');

// Dados do SubMerchant (Phamela Gourmet)
define('SUBMERCHANT_DOCUMENT_TYPE', 'cpf');
define('SUBMERCHANT_DOCUMENT_NUMBER', '90283363207');
define('SUBMERCHANT_LEGAL_NAME', 'Atelier Phamela Gourmet LTDA');
define('SUBMERCHANT_ID', 'PHAMELA001');
define('SUBMERCHANT_PHONE', '11982141213');
define('SUBMERCHANT_URL', 'https://instagram.com/phamela.gourmetofc');
define('SUBMERCHANT_MCC', '5411');

// Integração com a API da Monetrix
try {
    // Decodificar dados recebidos
    $input = json_decode($input_raw, true);
    if (!$input) {
        throw new Exception("Erro ao decodificar JSON");
    }
    
    // Extrair dados básicos
    $valor_centavos = intval($input['valor'] ?? 0);
    $cliente = $input['cliente'] ?? [];
    $itens = $input['itens'] ?? [];
    
    logDebug("Valor: $valor_centavos centavos, Cliente: " . json_encode($cliente));
    
    // Gerar ID externo único para a transação
    $external_id = 'TRX-' . uniqid() . '-' . rand(1000, 9999);
    
    // Preparar os dados para a API da Monetrix (formato original)
    $monetrix_payload = [
        'amount' => $valor_centavos,
        'currency' => 'BRL',
        'paymentMethod' => 'pix',
        'pix' => [
            'expiresIn' => 60
        ],
        'items' => [],
        'customer' => [
            'name' => $cliente['nome'] ?? 'Cliente Teste',
            'email' => $cliente['email'] ?? 'cliente@teste.com',
            'document' => [
                'type' => 'cpf',
                'number' => preg_replace('/[^0-9]/', '', $cliente['cpf'] ?? '90283363207')
            ]
        ]
    ];
    
    // Adicionar itens do pedido
    if (!empty($itens) && is_array($itens)) {
        foreach ($itens as $item) {
            $monetrix_payload['items'][] = [
                'title' => $item['nome'] ?? 'Produto',
                'unitPrice' => intval(($item['precoPromocional'] ?? $item['precoUnitario'] ?? 0) * 100),
                'quantity' => intval($item['quantidade'] ?? 1),
                'tangible' => false
            ];
        }
    } else {
        // Adicionar pelo menos um item padrão
        $monetrix_payload['items'][] = [
            'title' => 'Pedido Completo - Phamela Gourmet',
            'unitPrice' => $valor_centavos,
            'quantity' => 1,
            'tangible' => false
        ];
    }
    
    logDebug("Payload Nova API Monetrix: " . json_encode($monetrix_payload));
    
    // Usar autenticação original que funciona
    $auth = base64_encode(MONETRIX_API_KEY . ':' . MONETRIX_API_SECRET);
    
    // Iniciar a requisição cURL para a API da Monetrix
    $ch = curl_init(MONETRIX_API_URL);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($monetrix_payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Basic ' . $auth
    ]);
    
    // Executar a requisição
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    // Verificar se ocorreu algum erro no cURL
    if ($response === false) {
        throw new Exception('Erro na requisição cURL: ' . curl_error($ch));
    }
    
    curl_close($ch);
    
    logDebug("Resposta HTTP: $http_code - $response");
    
    // Decodificar a resposta
    $monetrix_response = json_decode($response, true);

    // Verificar se a resposta é válida
    if ($http_code !== 200 || !isset($monetrix_response['id'])) {
        logDebug("Erro na API da Monetrix: " . json_encode($monetrix_response));
        throw new Exception('Erro na API da Monetrix: ' . ($monetrix_response['message'] ?? 'Resposta inválida'));
    }

    // Verificar se é necessário buscar detalhes do PIX (se não estiver na resposta inicial)
    logDebug("Resposta da Monetrix recebida: " . json_encode($monetrix_response));

    // Extrair dados do QR Code do campo 'pix' da resposta
    $pix_code = '';
    $qr_code_url = '';

    // Se há informações de PIX na resposta
    if (isset($monetrix_response['pix'])) {
        if (isset($monetrix_response['pix']['qrcode'])) {
            $pix_code = $monetrix_response['pix']['qrcode'];
        } else if (isset($monetrix_response['pix']['qr_code'])) {
            $pix_code = $monetrix_response['pix']['qr_code'];
        } else if (isset($monetrix_response['pix']['text'])) {
            $pix_code = $monetrix_response['pix']['text'];
        }

        if (isset($monetrix_response['pix']['imageUrl'])) {
            $qr_code_url = $monetrix_response['pix']['imageUrl'];
        } else if (isset($monetrix_response['pix']['image_url'])) {
            $qr_code_url = $monetrix_response['pix']['image_url'];
        } else if (isset($monetrix_response['pix']['png'])) {
            $qr_code_url = $monetrix_response['pix']['png'];
        }
    }

    // Se ainda não encontramos as informações de PIX, buscar os detalhes da transação
    if (empty($pix_code) || empty($qr_code_url)) {
        // Buscar os detalhes da transação para obter o QR code
        logDebug("Buscando detalhes adicionais da transação Monetrix ID: " . $monetrix_response['id']);
        
        $ch = curl_init(MONETRIX_API_URL . '/' . $monetrix_response['id']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Basic ' . $auth
        ]);
        
        $details_response = curl_exec($ch);
        $details_http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        logDebug("Resposta detalhes HTTP: $details_http_code - $details_response");
        
        if ($details_http_code === 200) {
            $details = json_decode($details_response, true);
            logDebug("Detalhes transação: " . json_encode($details));
            
            // Verificar se há dados de PIX nos detalhes
            if (isset($details['pix'])) {
                if (empty($pix_code)) {
                    if (isset($details['pix']['qrcode'])) {
                        $pix_code = $details['pix']['qrcode'];
                    } else if (isset($details['pix']['qr_code'])) {
                        $pix_code = $details['pix']['qr_code'];
                    } else if (isset($details['pix']['text'])) {
                        $pix_code = $details['pix']['text'];
                    }
                }
                
                if (empty($qr_code_url)) {
                    if (isset($details['pix']['imageUrl'])) {
                        $qr_code_url = $details['pix']['imageUrl'];
                    } else if (isset($details['pix']['image_url'])) {
                        $qr_code_url = $details['pix']['image_url'];
                    } else if (isset($details['pix']['png'])) {
                        $qr_code_url = $details['pix']['png'];
                    }
                }
            }
        }
    }

    // Se ainda não conseguimos obter o QR code, usar o Google Charts API para gerar
    // Gerar QR code via API do QR Server se necessário
    if (empty($qr_code_url) && !empty($pix_code)) {
        $qr_code_url = "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" . urlencode($pix_code);
        logDebug("Gerando QR code via QR Server API: $qr_code_url");
    }

    // Se não tivermos nem o código PIX, falhar
    if (empty($pix_code)) {
        logDebug("Não foi possível obter o código PIX da resposta da Monetrix");
        throw new Exception('Não foi possível obter o código PIX. Tente novamente mais tarde.');
    }

    // Preparar a resposta para o cliente
    $response = [
        'success' => true,
        'token' => $external_id,
        'pixCode' => $pix_code,
        'qrCodeUrl' => $qr_code_url,
        'externalId' => $external_id,
        'transactionId' => $monetrix_response['id'] ?? '',
        'amount' => $valor_centavos / 100,
        'monetrixId' => $monetrix_response['id'] ?? '',
        'status' => $monetrix_response['status'] ?? 'pending'
    ];
    
    // Salvar a transação no banco de dados local
    try {
        $dbPath = __DIR__ . '/database.sqlite';
        if (file_exists($dbPath)) {
            $db = new PDO("sqlite:$dbPath");
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Verificar se a tabela pedidos existe
            $tablesQuery = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='pedidos'");
            $tableExists = false;
            while ($row = $tablesQuery->fetch(PDO::FETCH_ASSOC)) {
                if ($row['name'] === 'pedidos') {
                    $tableExists = true;
                    break;
                }
            }
            
            // Criar tabela se não existir
            if (!$tableExists) {
                logDebug("Criando tabela pedidos");
                $db->exec("CREATE TABLE pedidos (
                    transaction_id TEXT PRIMARY KEY,
                    status TEXT NOT NULL,
                    valor INTEGER NOT NULL,
                    nome TEXT,
                    email TEXT,
                    cpf TEXT,
                    utm_params TEXT,
                    monetrix_id TEXT,
                    created_at TEXT,
                    updated_at TEXT
                )");
            }
            
            // Inserir registro
            logDebug("Inserindo transação no banco de dados: $external_id");
            $stmt = $db->prepare("INSERT INTO pedidos (
                transaction_id, status, valor, nome, email, cpf, utm_params, monetrix_id, created_at, updated_at
            ) VALUES (
                :transaction_id, :status, :valor, :nome, :email, :cpf, :utm_params, :monetrix_id, :created_at, :updated_at
            )");
            
            $stmt->execute([
                'transaction_id' => $external_id,
                'status' => 'pending',
                'valor' => $valor_centavos,
                'nome' => $cliente['nome'] ?? '',
                'email' => $cliente['email'] ?? '',
                'cpf' => $cliente['cpf'] ?? '',
                'utm_params' => json_encode($input['utmParams'] ?? []),
                'monetrix_id' => $monetrix_response['id'] ?? '',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            
            logDebug("Transação inserida com sucesso");
        }
    } catch (Exception $e) {
        logDebug("Erro ao salvar no banco de dados: " . $e->getMessage());
        // Não interromper o fluxo se houver erro no banco de dados
    }
    
    // Retornar resposta
    logDebug("Retornando resposta: " . json_encode($response));
    echo json_encode($response);

} catch (Exception $e) {
    // Log do erro
    logDebug("ERRO: " . $e->getMessage());
    
    // Retornar erro
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao processar pagamento: ' . $e->getMessage()
    ]);
}
?>