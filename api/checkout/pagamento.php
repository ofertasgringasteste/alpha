<?php
// Habilita o log de erros
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Cabeçalhos CORS melhorados
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization');
header('Access-Control-Max-Age: 86400'); // Cache preflight por 24 horas

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

// Log de debug inicial
error_log("[Monetrix] 🚀 Iniciando processamento de pagamento");
error_log("[Monetrix] 📝 Método: " . $_SERVER['REQUEST_METHOD']);
error_log("[Monetrix] 📦 Input bruto: " . file_get_contents('php://input'));

// Função para gerar CPF válido
function gerarCPF() {
    $cpf = '';
    for ($i = 0; $i < 9; $i++) {
        $cpf .= rand(0, 9);
    }

    $soma = 0;
    for ($i = 0; $i < 9; $i++) {
        $soma += intval($cpf[$i]) * (10 - $i);
    }
    $resto = $soma % 11;
    $digito1 = ($resto < 2) ? 0 : 11 - $resto;
    $cpf .= $digito1;

    $soma = 0;
    for ($i = 0; $i < 10; $i++) {
        $soma += intval($cpf[$i]) * (11 - $i);
    }
    $resto = $soma % 11;
    $digito2 = ($resto < 2) ? 0 : 11 - $resto;
    $cpf .= $digito2;

    $invalidos = [
        '00000000000', '11111111111', '22222222222', '33333333333', 
        '44444444444', '55555555555', '66666666666', '77777777777', 
        '88888888888', '99999999999'
    ];

    if (in_array($cpf, $invalidos)) {
        return gerarCPF();
    }

    return $cpf;
}

try {
    // Configurações da API Monetrix - Usando variáveis de ambiente
    $apiUrl = 'https://api.monetrix.store/v1/transactions';
    $publicKey = getenv('API_KEY') ?: 'pk__qJrExlJeIQpV3RrF157NP-Wk48qlza1_9mHCzo-69AbBsqr';
    $secretKey = getenv('API_SECRET') ?: 'sk_cS7rqPG-8Q9BGcKy1hWpXJ-m1s3J9mi9s0f2CmvU15AIPcuo';
    
    // Basic Auth para Monetrix
    $auth = base64_encode($publicKey . ':' . $secretKey);
    
    error_log("[Monetrix] 🔑 Public Key: " . substr($publicKey, 0, 20) . "...");
    error_log("[Monetrix] 🔑 Secret Key: " . substr($secretKey, 0, 20) . "...");
    error_log("[Monetrix] 🔑 Auth Header: Basic " . substr($auth, 0, 30) . "...");
    
    // Verificar se as credenciais estão corretas
    if (empty($publicKey) || empty($secretKey)) {
        throw new Exception("Credenciais da API Monetrix não configuradas");
    }

    // Caminho do banco de dados - Usando variáveis de ambiente para Vercel
    $dbPath = getenv('DB_PATH') ?: __DIR__ . '/database.sqlite'; 
    
    // Conecta ao SQLite (arquivo de banco de dados)
    $db = new PDO("sqlite:$dbPath");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Verifica se a tabela 'pedidos' existe e cria se necessário
    $db->exec("CREATE TABLE IF NOT EXISTS pedidos (
        transaction_id TEXT PRIMARY KEY,
        status TEXT NOT NULL,
        valor INTEGER NOT NULL,
        nome TEXT,
        email TEXT,
        cpf TEXT,
        utm_params TEXT,
        created_at TEXT,
        updated_at TEXT
    )");

    // Recebe os parâmetros do frontend
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Usar dados do frontend se disponíveis, senão usar valores padrão
    $valor_centavos = $input['valor'] ?? 9358; // Valor em centavos
    $nome_cliente = $input['nome'] ?? null;
    $telefone_cliente = $input['telefone'] ?? "11999999999";
    $email_cliente = $input['email'] ?? "clienteteste@gmail.com";
    $cpf_cliente = $input['cpf'] ?? null;
    $itens_carrinho = $input['itens'] ?? [];

    if (!$valor_centavos || $valor_centavos <= 0) {
        throw new Exception('Valor inválido');
    }

    // Gera dados do cliente
    $nomes_masculinos = [
        'João', 'Pedro', 'Lucas', 'Miguel', 'Arthur', 'Gabriel', 'Bernardo', 'Rafael',
        'Gustavo', 'Felipe', 'Daniel', 'Matheus', 'Bruno', 'Thiago', 'Carlos'
    ];

    $nomes_femininos = [
        'Maria', 'Ana', 'Julia', 'Sofia', 'Isabella', 'Helena', 'Valentina', 'Laura',
        'Alice', 'Manuela', 'Beatriz', 'Clara', 'Luiza', 'Mariana', 'Sophia'
    ];

    $sobrenomes = [
        'Silva', 'Santos', 'Oliveira', 'Souza', 'Rodrigues', 'Ferreira', 'Alves', 
        'Pereira', 'Lima', 'Gomes', 'Costa', 'Ribeiro', 'Martins', 'Carvalho', 
        'Almeida', 'Lopes', 'Soares', 'Fernandes', 'Vieira', 'Barbosa'
    ];

    // Parâmetros UTM do frontend
    $utmParams = [
        'utm_source' => $input['utm_source'] ?? null,
        'utm_medium' => $input['utm_medium'] ?? null,
        'utm_campaign' => $input['utm_campaign'] ?? null,
        'utm_content' => $input['utm_content'] ?? null,
        'utm_term' => $input['utm_term'] ?? null,
        'xcod' => $input['xcod'] ?? null,
        'sck' => $input['sck'] ?? null,
        'fbclid' => $input['fbclid'] ?? null,
        'gclid' => $input['gclid'] ?? null,
        'ttclid' => $input['ttclid'] ?? null
    ];

    $utmParams = array_filter($utmParams, function($value) {
        return $value !== null && $value !== '';
    });

    error_log("[Monetrix] 📊 Parâmetros UTM recebidos: " . json_encode($utmParams));

    // Gera dados do cliente se não fornecidos
    if (!$nome_cliente) {
        $genero = rand(0, 1);
        $nome = $genero ? 
            $nomes_masculinos[array_rand($nomes_masculinos)] : 
            $nomes_femininos[array_rand($nomes_femininos)];
        
        $sobrenome1 = $sobrenomes[array_rand($sobrenomes)];
        $sobrenome2 = $sobrenomes[array_rand($sobrenomes)];
        
        $nome_cliente = "$nome $sobrenome1 $sobrenome2";
    }
    
    $email = $email_cliente;
    $cpf = $cpf_cliente ?: gerarCPF();

    error_log("[Monetrix] 📝 Preparando dados para envio: " . json_encode([
        'valor_centavos' => $valor_centavos,
        'nome' => $nome_cliente,
        'email' => $email,
        'cpf' => $cpf,
        'telefone' => $telefone_cliente,
        'itens_recebidos' => $itens_carrinho
    ]));

    // Estrutura de dados para Monetrix (baseada no projeto que funciona)
    $data = [
        "amount" => $valor_centavos,
        "paymentMethod" => "pix",
        "postbackUrl" => "https://" . $_SERVER['HTTP_HOST'] . "/checkout/webhook.php",
        "externalRef" => uniqid('doacao_'),
        "metadata" => json_encode($utmParams), // UTMs como metadata JSON string
        "customer" => [
            "name" => $nome_cliente,
            "email" => $email,
            "phone" => $telefone_cliente,
            "document" => [
                "type" => "cpf",
                "number" => $cpf
            ]
        ],
        "items" => count($itens_carrinho) > 0 ? array_map(function($item) {
            return [
                "title" => $item['nome'] ?? 'Produto',
                "quantity" => $item['quantidade'] ?? 1,
                "unitPrice" => isset($item['precoPromocional']) ? 
                    round($item['precoPromocional'] * 100) : 
                    (isset($item['precoOriginal']) ? round($item['precoOriginal'] * 100) : 1000),
                "tangible" => true
            ];
        }, $itens_carrinho) : [
            [
                "title" => "Liberação de Benefício",
                "quantity" => 1,
                "unitPrice" => $valor_centavos,
                "tangible" => false
            ]
        ],
        "pix" => [
            "expiresIn" => 60 // Expira em 60 minutos
        ]
    ];

    error_log("[Monetrix] 🌐 URL da requisição: " . $apiUrl);
    error_log("[Monetrix] 📦 Dados enviados: " . json_encode($data));

    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: ' . $auth,
        'Content-Type: application/json'
    ]);

    curl_setopt($ch, CURLOPT_VERBOSE, true);
    $verbose = fopen('php://temp', 'w+');
    curl_setopt($ch, CURLOPT_STDERR, $verbose);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    $curlErrno = curl_errno($ch);

    rewind($verbose);
    $verboseLog = stream_get_contents($verbose);
    error_log("[Monetrix] 🔍 Detalhes da requisição cURL:\n" . $verboseLog);

    if ($curlError) {
        error_log("[Monetrix] ❌ Erro cURL: " . $curlError . " (errno: " . $curlErrno . ")");
        throw new Exception("Erro na requisição: " . $curlError);
    }

    curl_close($ch);

    error_log("[Monetrix] 📊 HTTP Status Code: " . $httpCode);
    error_log("[Monetrix] 📄 Resposta bruta: " . $response);

    if ($httpCode !== 200 && $httpCode !== 201) {
        error_log("[Monetrix] ❌ Erro HTTP: " . $httpCode);
        error_log("[Monetrix] 📄 Resposta de erro: " . $response);
        throw new Exception("Erro na API Monetrix: HTTP " . $httpCode . " - " . $response);
    }

    $result = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("[Monetrix] ❌ Erro ao decodificar JSON: " . json_last_error_msg());
        error_log("[Monetrix] 📄 Resposta bruta: " . $response);
        throw new Exception("Resposta inválida da API: " . json_last_error_msg());
    }

    if (!$result) {
        error_log("[Monetrix] ❌ Resposta vazia da API");
        throw new Exception("Resposta vazia da API Monetrix");
    }

    // Verificar se a resposta contém os dados necessários
    if (!isset($result['id'])) {
        error_log("[Monetrix] ❌ Resposta da API não contém ID da transação");
        error_log("[Monetrix] 📄 Estrutura recebida: " . print_r($result, true));
        throw new Exception("ID da transação não encontrado na resposta");
    }

    // Verificar se contém dados do PIX
    if (!isset($result['pix']) || !isset($result['pix']['qrcode'])) {
        error_log("[Monetrix] ⚠️ Resposta não contém dados do PIX");
        error_log("[Monetrix] 📄 Estrutura recebida: " . print_r($result, true));
        // Não é erro fatal, pode ser que o PIX seja gerado depois
    }

    // Salva os dados no SQLite
    $stmt = $db->prepare("INSERT INTO pedidos (transaction_id, status, valor, nome, email, cpf, utm_params, created_at) 
        VALUES (:transaction_id, 'pending', :valor, :nome, :email, :cpf, :utm_params, :created_at)");
    $stmt->execute([
        'transaction_id' => $result['id'],
        'valor' => $valor_centavos,
        'nome' => $nome_cliente,
        'email' => $email,
        'cpf' => $cpf,
        'utm_params' => json_encode($utmParams),
        'created_at' => date('c')
    ]);

    session_start();
    $_SESSION['payment_id'] = $result['id'];

    error_log("[Monetrix] 💳 Transação criada com sucesso: " . $result['id']);
    error_log("[Monetrix] 📄 Resposta completa da API: " . $response);
    error_log("[Monetrix] 🔑 Token gerado: " . $result['id']);

    error_log("[Sistema] 📡 Iniciando comunicação com utmify-pendente.php");

    $utmifyData = [
        'orderId' => $result['id'],
        'platform' => 'Monetrix',
        'paymentMethod' => 'pix',
        'status' => 'waiting_payment',
        'createdAt' => date('Y-m-d H:i:s'),
        'approvedDate' => null,
        'refundedAt' => null,
        'customer' => [
            'name' => $nome_cliente,
            'email' => $email,
            'phone' => $telefone_cliente,
            'document' => $cpf,
            'country' => 'BR',
            'ip' => $_SERVER['REMOTE_ADDR'] ?? null
        ],
        'products' => [
            [
                'id' => uniqid('PROD_'),
                'name' => 'Liberação de Benefício',
                'planId' => null,
                'planName' => null,
                'quantity' => 1,
                'priceInCents' => $valor_centavos
            ]
        ],
        'trackingParameters' => $utmParams,
        'commission' => [
            'totalPriceInCents' => $valor_centavos,
            'gatewayFeeInCents' => 0, // Monetrix não retorna fee na criação
            'userCommissionInCents' => $valor_centavos
        ],
        'isTest' => false
    ];

    error_log("[Utmify] 📦 Preparando dados para envio ao utmify-pendente.php: " . json_encode($utmifyData));

    // Envia para utmify-pendente.php
    error_log("[Sistema] 📡 Enviando requisição POST para ../utmify-pendente.php");
    
    $serverUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
    $utmifyUrl = $serverUrl . "/checkout/utmify-pendente.php";
    error_log("[Sistema] 🔍 URL do utmify-pendente.php: " . $utmifyUrl);
    
    $ch = curl_init($utmifyUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($utmifyData),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_TIMEOUT => 10, // Timeout de 10 segundos
        CURLOPT_CONNECTTIMEOUT => 5 // Timeout de conexão de 5 segundos
    ]);

    $utmifyResponse = curl_exec($ch);
    $utmifyHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $utmifyError = curl_error($ch);
    $utmifyErrno = curl_errno($ch);
    
    curl_close($ch);

    // Log da tentativa de envio para utmify (não bloquear se falhar)
    if ($utmifyError) {
        error_log("[Sistema] ⚠️ Erro ao conectar com utmify-pendente.php: " . $utmifyError);
    } elseif ($utmifyHttpCode !== 200) {
        error_log("[Sistema] ⚠️ Erro HTTP do utmify-pendente.php ($utmifyHttpCode): " . $utmifyResponse);
    } else {
        error_log("[Sistema] ✅ Dados enviados com sucesso para utmify-pendente.php");
        error_log("[Sistema] ✉️ Resposta: " . $utmifyResponse);
    }

    // Função para buscar QR code em diferentes locais da resposta
    function findQRCode($result) {
        $possiblePaths = [
            'pix.qrcode',
            'pix.qrCode', 
            'pix.code',
            'qrcode',
            'qrCode',
            'code'
        ];
        
        foreach ($possiblePaths as $path) {
            $parts = explode('.', $path);
            $current = $result;
            
            foreach ($parts as $part) {
                if (isset($current[$part])) {
                    $current = $current[$part];
                } else {
                    $current = null;
                    break;
                }
            }
            
            if ($current && is_string($current)) {
                return $current;
            }
        }
        
        return null;
    }

    // Buscar QR code na resposta
    $pixCode = findQRCode($result);
    
    if ($pixCode) {
        error_log("[Monetrix] ✅ QR Code encontrado: " . substr($pixCode, 0, 50) . "...");
    } else {
        error_log("[Monetrix] ⚠️ QR Code não encontrado na resposta");
        error_log("[Monetrix] 📄 Estrutura completa da resposta: " . print_r($result, true));
    }

    // Preparar resposta adaptada para Monetrix
    $responseData = [
        'success' => true,
        'token' => $result['id'],
        'pixCode' => $pixCode,
        'qrCodeUrl' => $pixCode ? 
            'https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=' . urlencode($pixCode) : 
            null,
        'valor' => $valor_centavos,
        'expiresAt' => $result['expiresAt'] ?? null,
        'status' => $result['status'] ?? 'pending',
        'logs' => [
            'utmParams' => $utmParams,
            'transacao' => [
                'valor' => $valor_centavos,
                'cliente' => $nome_cliente,
                'email' => $email,
                'cpf' => $cpf,
                'telefone' => $telefone_cliente
            ],
            'utmifyResponse' => [
                'status' => $utmifyHttpCode,
                'resposta' => $utmifyResponse
            ],
            'monetrixResponse' => $result
        ]
    ];

    error_log("[Monetrix] 📤 Enviando resposta ao frontend: " . json_encode($responseData));
    echo json_encode($responseData);

} catch (Exception $e) {
    error_log("[Monetrix] ❌ Erro: " . $e->getMessage());
    error_log("[Monetrix] 🔍 Stack trace: " . $e->getTraceAsString());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao gerar o PIX: ' . $e->getMessage(),
        'error_details' => [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]
    ]);
}
?>