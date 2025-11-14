<?php
/**
 * Configurações da API Abyssal Pay
 * 
 * Este arquivo contém as credenciais e configurações para integração com Abyssal Pay
 */

// Credenciais da API Abyssal Pay
define('ABYSSALPAY_TOKEN', 'b0c1ebed-0c39-41e6-85b5-1b479c5b8c71');
define('ABYSSALPAY_SECRET', 'e32cffc6-e822-4e4b-b625-9dcac3ed51db');
define('ABYSSALPAY_API_URL', 'https://abyssalpay.com/api/');
define('ABYSSALPAY_DEPOSIT_ENDPOINT', 'https://abyssalpay.com/api/wallet/deposit/payment');

// URL base do site para webhooks
function getBaseUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $protocol . '://' . $host;
}

// URL do webhook para notificações de pagamento
function getWebhookUrl() {
    return getBaseUrl() . '/api/abyssalpay-webhook.php';
}

