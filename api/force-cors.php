<?php
// Definir headers CORS manualmente
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization');
header('Content-Type: application/json');

// Registrar a solicitação para depuração
file_put_contents('force_cors_log.txt', date('Y-m-d H:i:s') . " - Request: " . $_SERVER['REQUEST_METHOD'] . " " . $_SERVER['REQUEST_URI'] . "\n", FILE_APPEND);
file_put_contents('force_cors_log.txt', date('Y-m-d H:i:s') . " - Params: " . json_encode($_GET) . "\n", FILE_APPEND);

// Se for uma requisição OPTIONS, responder imediatamente
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Verificar se o parâmetro path foi fornecido
if (empty($_GET['path'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Parâmetro path não fornecido']);
    exit;
}

// Montar o caminho do arquivo PHP a ser incluído
$path = $_GET['path'];
$phpFile = null;

// Verificar diferentes possibilidades de caminhos
$possiblePaths = [
    __DIR__ . '/' . $path . '.php',
    __DIR__ . '/' . $path . '/index.php',
    dirname(__DIR__) . '/api/' . $path . '.php',
    dirname(__DIR__) . '/api/' . $path . '/index.php',
];

foreach ($possiblePaths as $possiblePath) {
    if (file_exists($possiblePath)) {
        $phpFile = $possiblePath;
        break;
    }
}

// Se o arquivo não foi encontrado
if (!$phpFile) {
    http_response_code(404);
    echo json_encode([
        'success' => false, 
        'message' => 'Arquivo não encontrado', 
        'requested_path' => $path,
        'paths_checked' => $possiblePaths
    ]);
    exit;
}

// Registrar o arquivo encontrado
file_put_contents('force_cors_log.txt', date('Y-m-d H:i:s') . " - Incluindo arquivo: $phpFile\n", FILE_APPEND);

// Capturar a saída do arquivo incluído
ob_start();
include $phpFile;
$output = ob_get_clean();

// Enviar a resposta
echo $output;
?> 