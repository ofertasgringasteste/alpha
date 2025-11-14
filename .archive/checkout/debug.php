<?php
// Ative a exibição de erros
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Defina cabeçalhos
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Tente verificar problemas básicos
echo json_encode([
    'status' => 'debug',
    'timestamp' => date('Y-m-d H:i:s'),
    'php_version' => phpversion(),
    'server' => $_SERVER['SERVER_SOFTWARE'] ?? 'Desconhecido',
    'pdo_drivers' => extension_loaded('pdo') ? PDO::getAvailableDrivers() : 'PDO não disponível',
    'sqlite_enabled' => extension_loaded('pdo_sqlite') ? 'Sim' : 'Não',
    'logs_writable' => is_writable(__DIR__ . '/logs') ? 'Sim' : 'Não',
    'database_exists' => file_exists(__DIR__ . '/database.sqlite') ? 'Sim' : 'Não',
    'database_writable' => file_exists(__DIR__ . '/database.sqlite') && is_writable(__DIR__ . '/database.sqlite') ? 'Sim' : 'Não'
]);

// Se o banco de dados existir, vamos testar a conexão
$dbPath = __DIR__ . '/database.sqlite';

if (file_exists($dbPath)) {
    try {
        $db = new PDO("sqlite:$dbPath");
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Verificar se a tabela pedidos existe
        $stmt = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='pedidos'");
        $tableExists = $stmt->fetchColumn() !== false;
        
        // Escrever no log
        $logFile = __DIR__ . '/logs/debug.log';
        file_put_contents($logFile, date('Y-m-d H:i:s') . " - Teste de debug executado. Tabela pedidos existe: " . ($tableExists ? 'Sim' : 'Não') . "\n", FILE_APPEND);
        
    } catch (PDOException $e) {
        // Escrever erro no log
        $logFile = __DIR__ . '/logs/debug.log';
        file_put_contents($logFile, date('Y-m-d H:i:s') . " - Erro na conexão SQLite: " . $e->getMessage() . "\n", FILE_APPEND);
    }
}
?> 