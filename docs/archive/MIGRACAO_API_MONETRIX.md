# Migração API Monetrix - Nova Versão

## Resumo das Alterações

A API PIX da Monetrix foi atualizada com uma nova estrutura. As principais mudanças implementadas foram:

### 1. Autenticação
**Antes:**
```php
$auth = base64_encode($public_key . ':' . $secret_key);
```

**Agora:**
```php
$auth = 'c2tfX1EzOXhRZFN0NnFQb005Z09CYjVFK1hlRzBpLTNGbzFwTVA3N0JpV1M3Rnlnam5nOng=';
```

### 2. Estrutura do Payload

**Campos Removidos:**
- `currency` (não é mais necessário)
- `externalRef` (não é mais usado)
- `postbackUrl` (webhooks configurados diferentemente)
- `metadata` (estrutura simplificada)

**Novos Campos Obrigatórios:**
- `subMerchant` - Dados do comerciante
- `shipping` - Informações de entrega
- Estrutura de `items` atualizada

### 3. Expiração PIX
**Antes:** `expiresIn` (em minutos)
**Agora:** `expiresInDays` (em dias)

## Arquivos Modificados

### 1. `api/monetrix_config.php`
- ✅ Adicionada nova constante `MONETRIX_AUTH_TOKEN`
- ✅ Adicionadas constantes para `subMerchant`
- ✅ Criada função `getSubMerchantData()`
- ✅ Alterado `MONETRIX_PIX_EXPIRATION_DAYS`

### 2. `api/payment.php`
- ✅ Atualizada estrutura do payload
- ✅ Adicionado campo `subMerchant`
- ✅ Adicionado campo `shipping`
- ✅ Modificada estrutura dos `items`
- ✅ Atualizada autenticação

### 3. `checkout/pagamento.php`
- ✅ Atualizada estrutura do payload
- ✅ Adicionadas novas constantes
- ✅ Modificada lógica de autenticação
- ✅ Adaptada estrutura dos items

## Nova Estrutura de Payload

```json
{
  "amount": 1000,
  "paymentMethod": "pix",
  "pix": {
    "expiresInDays": 1
  },
  "items": [
    {
      "title": "Kit 3 Morangos do Amor",
      "unitPrice": 1000,
      "quantity": 1,
      "tangible": false
    }
  ],
  "shipping": {
    "fee": 0,
    "address": {
      "zipCode": "76912742",
      "street": "Rua Castanheira",
      "streetNumber": "13",
      "city": "Ji-Paraná",
      "state": "RO",
      "country": "BR",
      "neighborhood": "Jardim Souza"
    }
  },
  "subMerchant": {
    "document": {
      "type": "cpf",
      "number": "90283363207"
    },
    "legalName": "Atelier Phamela Gourmet LTDA",
    "id": "PHAMELA001",
    "phone": "11982141213",
    "url": "https://instagram.com/phamela.gourmetofc",
    "mcc": "5411",
    "address": {
      "zipCode": "01234567",
      "street": "Rua das Flores",
      "city": "São Paulo",
      "state": "SP",
      "country": "BR",
      "neighborhood": "Centro",
      "streetNumber": "123"
    }
  },
  "customer": {
    "name": "Fernando Alves",
    "email": "teste@phamellagourmet.com",
    "document": {
      "type": "cpf",
      "number": "90283363207"
    }
  }
}
```

## Dados do SubMerchant (Phamela Gourmet)

Os dados do `subMerchant` foram configurados especificamente para a Atelier Phamela Gourmet:

```php
'subMerchant' => [
    'document' => [
        'type' => 'cpf',
        'number' => '90283363207'
    ],
    'legalName' => 'Atelier Phamela Gourmet LTDA',
    'id' => 'PHAMELA001',
    'phone' => '11982141213',
    'url' => 'https://instagram.com/phamela.gourmetofc',
    'mcc' => '5411', // Grocery Stores, Supermarkets
    'address' => [
        'zipCode' => '01234567',
        'street' => 'Rua das Flores',
        'city' => 'São Paulo',
        'state' => 'SP',
        'country' => 'BR',
        'neighborhood' => 'Centro',
        'streetNumber' => '123'
    ]
]
```

## Teste da Nova API

Para testar a nova implementação, execute:

```bash
php teste_nova_api.php
```

Este script irá:
1. Criar um payload de teste
2. Fazer uma requisição para a nova API
3. Exibir a resposta
4. Validar campos importantes

## Compatibilidade

### ✅ Mantido:
- Estrutura de resposta similar
- Campos `qrCode` e `qrCodeUrl`
- Sistema de verificação de status
- Integração com UTMify
- Banco de dados SQLite

### ⚠️ Atenção:
- Webhooks podem ter estrutura diferente
- Alguns campos de resposta podem ter nomes diferentes
- Verificar logs para identificar possíveis incompatibilidades

## Logs e Debugging

Os logs continuam sendo salvos em:
- `api/payment_log.txt`
- `api/monetrix_response.log`
- `checkout/logs/payment_YYYY-MM-DD.log`

Monitore estes arquivos após a migração para identificar possíveis problemas.

## Rollback

Se necessário fazer rollback, os dados antigos estão comentados nos arquivos de configuração. Para reverter:

1. Restaurar `MONETRIX_API_KEY` e `MONETRIX_API_SECRET`
2. Remover campos `subMerchant` e `shipping`
3. Restaurar estrutura antiga dos `items`
4. Alterar `expiresInDays` para `expiresIn`

---

**Data da Migração:** 29 de julho de 2025
**Responsável:** Sistema automatizado
**Status:** ✅ Implementado e testado
