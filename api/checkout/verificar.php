<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization');
header('Access-Control-Max-Age: 86400'); // Cache preflight por 24 horas

// Responder a solicitações OPTIONS sem processar
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

if (!isset($_GET['id'])) {
    echo json_encode(['error' => 'ID não fornecido']);
    exit;
}

$id = $_GET['id'];

// Remove qualquer caractere especial ou espaço do ID
$id = trim($id);
$id = preg_replace('/[^a-zA-Z0-9\-]/', '', $id);

try {
    // Conecta ao SQLite usando variáveis de ambiente para compatibilidade com Vercel
    $dbPath = getenv('DB_PATH') ?: __DIR__ . '/database.sqlite';
    $db = new PDO("sqlite:$dbPath");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Busca o status do pagamento
    $stmt = $db->prepare("SELECT * FROM pedidos WHERE transaction_id = :transaction_id");
    $stmt->execute(['transaction_id' => $id]);
    $pedido = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$pedido) {
        echo json_encode([
            'success' => false,
            'status' => 'error',
            'message' => 'Pedido não encontrado'
        ]);
        exit;
    }

    echo json_encode([
        'success' => true,
        'status' => $pedido['status'],
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
    error_log("[Verificar] ❌ Erro: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'status' => 'error',
        'message' => 'Erro ao verificar o status do pagamento: ' . (getenv('ENVIRONMENT') == 'development' ? $e->getMessage() : 'Contate o suporte.')
    ]);
} 