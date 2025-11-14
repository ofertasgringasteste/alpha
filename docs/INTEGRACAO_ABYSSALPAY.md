# Integração Abyssal Pay - PIX IN

## 📋 Resumo

Este documento descreve a integração da API Abyssal Pay para processamento de pagamentos PIX no projeto AlphaBurguer.

## 🔑 Credenciais

As credenciais estão configuradas no arquivo `api/abyssalpay_config.php`:

- **Token**: `b0c1ebed-0c39-41e6-85b5-1b479c5b8c71`
- **Secret**: `e32cffc6-e822-4e4b-b625-9dcac3ed51db`
- **API Endpoint**: `https://abyssalpay.com/api/`
- **Deposit Endpoint**: `https://abyssalpay.com/api/wallet/deposit/payment`

## 📁 Arquivos Criados/Modificados

### Novos Arquivos

1. **`api/abyssalpay_config.php`**
   - Arquivo de configuração com credenciais e constantes da API
   - Funções auxiliares para URLs de webhook

2. **`api/checkout/abyssalpay-pagamento.php`**
   - Processa requisições de pagamento PIX
   - Gera QR Code via API Abyssal Pay
   - Salva transações no banco de dados SQLite

3. **`api/abyssalpay-webhook.php`**
   - Recebe notificações de pagamento da Abyssal Pay
   - Atualiza status das transações no banco

### Arquivos Modificados

1. **`assets/js/checkout-page.js`**
   - Atualizado para usar o novo endpoint `api/checkout/abyssalpay-pagamento.php`
   - Removido fallback para API Monetrix

## 🔄 Fluxo de Pagamento

### 1. Criação do Pagamento

1. Cliente preenche dados no checkout
2. Frontend envia requisição POST para `api/checkout/abyssalpay-pagamento.php`
3. Backend processa e envia requisição para Abyssal Pay
4. Abyssal Pay retorna:
   - `idTransaction`: ID da transação
   - `qrcode`: Código PIX copia e cola
   - `qr_code_image_url`: URL da imagem do QR Code
5. Backend salva transação no banco com status `pending`
6. Frontend exibe QR Code para o cliente

### 2. Webhook de Confirmação

1. Cliente realiza pagamento via PIX
2. Abyssal Pay envia webhook para `api/abyssalpay-webhook.php`
3. Webhook contém:
   ```json
   {
     "status": "paid",
     "idTransaction": "TX123",
     "typeTransaction": "PIX"
   }
   ```
4. Backend atualiza status da transação para `paid`

### 3. Verificação de Status

O frontend verifica o status periodicamente via `api/checkout/verificar.php?id={transaction_id}`

## 📊 Estrutura de Dados

### Requisição de Pagamento

```json
{
  "token": "b0c1ebed-0c39-41e6-85b5-1b479c5b8c71",
  "secret": "e32cffc6-e822-4e4b-b625-9dcac3ed51db",
  "postback": "https://seudominio.com/api/abyssalpay-webhook.php",
  "amount": 100.00,
  "debtor_name": "Nome do Cliente",
  "email": "email@dominio.com",
  "debtor_document_number": "12345678900",
  "phone": "11999999999",
  "method_pay": "pix"
}
```

### Resposta da API

```json
{
  "idTransaction": "TX123",
  "qrcode": "00020126580014BR.GOV.BCB.PIX...",
  "qr_code_image_url": "https://..."
}
```

### Webhook Recebido

```json
{
  "status": "paid",
  "idTransaction": "TX123",
  "typeTransaction": "PIX"
}
```

## 🔧 Configuração do Webhook

O webhook deve ser configurado na plataforma Abyssal Pay para apontar para:

```
https://seudominio.com/api/abyssalpay-webhook.php
```

**Nota**: Substitua `seudominio.com` pelo seu domínio real.

## 🗄️ Banco de Dados

A tabela `pedidos` armazena as transações:

```sql
CREATE TABLE IF NOT EXISTS pedidos (
    transaction_id TEXT PRIMARY KEY,
    status TEXT NOT NULL,
    valor INTEGER NOT NULL,
    nome TEXT,
    email TEXT,
    cpf TEXT,
    telefone TEXT,
    utm_params TEXT,
    created_at TEXT,
    updated_at TEXT
);
```

## 🧪 Testes

### Testar Criação de Pagamento

1. Acesse a página de checkout
2. Preencha os dados do cliente
3. Clique em "Finalizar Pedido"
4. Verifique se o QR Code é exibido corretamente

### Testar Webhook

Você pode simular um webhook usando curl:

```bash
curl -X POST https://seudominio.com/api/abyssalpay-webhook.php \
  -H "Content-Type: application/json" \
  -d '{
    "status": "paid",
    "idTransaction": "TX123",
    "typeTransaction": "PIX"
  }'
```

## 📝 Logs

Os logs são salvos no error_log do PHP. Procure por:

- `[AbyssalPay]` - Logs do processamento de pagamento
- `[AbyssalPay Webhook]` - Logs do webhook

## ⚠️ Observações Importantes

1. **Valor**: A API Abyssal Pay espera valores em formato decimal (ex: 100.00), não em centavos
2. **CPF/Telefone**: Devem conter apenas números (sem formatação)
3. **Webhook**: Certifique-se de que a URL do webhook está acessível publicamente
4. **HTTPS**: Em produção, use HTTPS para todas as requisições

## 🔄 Migração da API Anterior

A integração foi feita de forma que não quebra a API anterior (Monetrix). O arquivo `api/checkout/pagamento.php` ainda existe e pode ser usado como fallback se necessário.

Para usar exclusivamente Abyssal Pay, certifique-se de que o frontend está apontando para `api/checkout/abyssalpay-pagamento.php`.

## 📞 Suporte

Em caso de problemas:

1. Verifique os logs do PHP
2. Verifique se as credenciais estão corretas
3. Verifique se o webhook está configurado corretamente na plataforma Abyssal Pay
4. Teste a conectividade com a API usando curl ou Postman

