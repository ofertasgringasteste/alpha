<?php
/**
 * Configurações da API Monetrix - NOVA VERSÃO
 * Este arquivo contém as constantes de configuração para integração com a nova API Monetrix
 */

// URL base da API
define('MONETRIX_API_URL', 'https://api.monetrix.store/v1/transactions');

// Nova chave de autorização fornecida no exemplo
define('MONETRIX_AUTH_TOKEN', 'c2tfX1EzOXhRZFN0NnFQb005Z09CYjVFS1hlRzBpLTNGbzFwTVA3N0JpV1M3Rnlnam5nOng=');

// Credenciais antigas (manter como backup)
define('MONETRIX_API_KEY', 'pk__qJrExlJeIQpV3RrF157NP-Wk48qlza1_9mHCzo-69AbBsqr');
define('MONETRIX_API_SECRET', 'sk_cS7rqPG-8Q9BGcKy1hWpXJ-m1s3J9mi9s0f2CmvU15AIPcuo');

// Tempo de expiração do PIX em dias (nova API usa dias)
define('MONETRIX_PIX_EXPIRATION_DAYS', 1);

// Dados do SubMerchant (comerciante)
define('SUBMERCHANT_DOCUMENT_TYPE', 'cpf');
define('SUBMERCHANT_DOCUMENT_NUMBER', '90283363207');
define('SUBMERCHANT_LEGAL_NAME', 'Atelier Phamela Gourmet LTDA');
define('SUBMERCHANT_ID', 'PHAMELA001');
define('SUBMERCHANT_PHONE', '11982141213');
define('SUBMERCHANT_URL', 'https://www.instagram.com/alpha_burgueer?igsh=MXZoODR5dGx4dDl4aA==');
define('SUBMERCHANT_MCC', '5411'); // Grocery Stores, Supermarkets

// Endereço do SubMerchant
define('SUBMERCHANT_ZIPCODE', '01234567');
define('SUBMERCHANT_STREET', 'Rua das Flores');
define('SUBMERCHANT_STREET_NUMBER', '123');
define('SUBMERCHANT_CITY', 'São Paulo');
define('SUBMERCHANT_STATE', 'SP');
define('SUBMERCHANT_COUNTRY', 'BR');
define('SUBMERCHANT_NEIGHBORHOOD', 'Centro');

// Função para gerar autenticação Basic (voltando ao original)
function getMonetrixAuth() {
    return 'Basic ' . base64_encode(MONETRIX_API_KEY . ':' . MONETRIX_API_SECRET);
}

// Função para obter dados do SubMerchant
function getSubMerchantData() {
    return [
        'document' => [
            'type' => SUBMERCHANT_DOCUMENT_TYPE,
            'number' => SUBMERCHANT_DOCUMENT_NUMBER
        ],
        'legalName' => SUBMERCHANT_LEGAL_NAME,
        'id' => SUBMERCHANT_ID,
        'phone' => SUBMERCHANT_PHONE,
        'url' => SUBMERCHANT_URL,
        'mcc' => SUBMERCHANT_MCC,
        'address' => [
            'zipCode' => SUBMERCHANT_ZIPCODE,
            'street' => SUBMERCHANT_STREET,
            'streetNumber' => SUBMERCHANT_STREET_NUMBER,
            'city' => SUBMERCHANT_CITY,
            'state' => SUBMERCHANT_STATE,
            'country' => SUBMERCHANT_COUNTRY,
            'neighborhood' => SUBMERCHANT_NEIGHBORHOOD
        ]
    ];
}
?>

