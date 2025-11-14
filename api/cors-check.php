<?php
// Definir headers CORS manualmente
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization');
header('Content-Type: application/json');

// Verificar os headers que foram realmente enviados
$sentHeaders = headers_list();
$corsHeaders = [
    'Access-Control-Allow-Origin' => 'Não encontrado',
    'Access-Control-Allow-Methods' => 'Não encontrado',
    'Access-Control-Allow-Headers' => 'Não encontrado'
];

// Verificar cada header na lista
foreach ($sentHeaders as $header) {
    foreach (array_keys($corsHeaders) as $corsHeader) {
        if (stripos($header, $corsHeader) === 0) {
            $corsHeaders[$corsHeader] = substr($header, strlen($corsHeader) + 1);
        }
    }
}

// Registrar informações do servidor
$serverInfo = [
    'php_version' => phpversion(),
    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Desconhecido',
    'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'Desconhecido',
    'headers_sent' => headers_sent() ? 'Sim' : 'Não',
    'output_buffering' => ini_get('output_buffering'),
    'cors_headers' => $corsHeaders,
    'all_headers' => $sentHeaders
];

// Responder com os detalhes
echo json_encode($serverInfo, JSON_PRETTY_PRINT);
?> 