# Análise Completa do Projeto - Phamella Gourmet

## Estrutura Geral do Projeto

Este é um projeto de cardápio online/e-commerce para a **Atelier Phamela Gourmet**, especializada em frutas do amor (morangos, uvas, maracujá, etc.) cobertas com chocolate.

### Arquivos Principais do Frontend:
- `index.html` - Página principal do cardápio
- `carrinho.html` - Página do carrinho de compras
- `checkout.html` - Página de finalização do pedido
- `thankyou.html` - Página de confirmação
- `upsell1.html`, `upsell2.html`, `upsell3.html` - Páginas de vendas adicionais

### Estrutura de Pastas:
```
morango02/
├── assets/img/ - Imagens dos produtos e banners
├── css/styles.css - Estilos customizados
├── js/ - Scripts JavaScript
│   ├── config.js - Configurações da loja e produtos
│   ├── app.js - Funções principais
│   ├── carrinho-page.js - Lógica do carrinho
│   ├── checkout-page.js - Lógica do checkout
│   └── utm-*.js - Handlers de UTM
├── api/ - APIs PHP para pagamento
├── checkout/ - APIs alternativas de checkout
└── backup/ - Arquivos de backup
```

## Configuração da Loja (config.js)

### Informações da Loja:
- **Nome**: Atelier Phamela Gourmet
- **Instagram**: @phamela.gourmetofc
- **Tempo de Entrega**: 30-45 min
- **Avaliação**: 4.9 (939 avaliações)
- **Entrega**: Grátis

### Categorias de Produtos:
1. **Frutas do Amor** 🍓
   - Morangos do amor (kits 3, 6)
   - Uvas do amor (kits 3, 4)
   - Maracujá do amor (kits 3, 4)
   - Abacaxi do amor (kits 3, 4)
   - Morango de pistache (kits 3, 4)
   - Brownie do amor (kits 3, 4)

2. **Combos Especiais** 🎁
   - Combo mais vendido: 12 morangos + 4 uvas (R$ 49,99)
   - Combo completo todas as frutas (R$ 99,99)
   - Trio clássico (R$ 54,99)
   - Combo tropical (R$ 42,99)
   - Combo premium pistache (R$ 47,99)

3. **Bolos & Doces** 🍰
   - Chocolate com morango (R$ 29,99)
   - Vulcão ninho nutella (R$ 29,99)
   - Pudim de leite (R$ 19,99)
   - Mini naked brownie (R$ 19,99)

4. **Promoções Especiais** 🔥
   - Bombom de morango "Compre 3, leve 4" (R$ 19,90)
   - Bombom coração de morango (R$ 7,90)
   - Coxinha de brigadeiro (R$ 4,90)

## Sistema de Pagamento Atual

### API PIX - Monetrix (Atual)
**Arquivo**: `api/payment.php` e `checkout/pagamento.php`

**Credenciais Atuais:**
- URL: `https://api.monetrix.store/v1/transactions`
- Public Key: `pk_ouwx4hvdzP2IcG-qH-KG4tBeF7_rhkba_HYje6SsTjHo5umn`
- Secret Key: `sk__Q39xQdSt6qPoM9gOBb5EKXeG0i-3Fo1pMP77BiWS7Fygjng`

**Fluxo Atual:**
1. Usuário finaliza pedido
2. Sistema gera dados aleatórios de cliente (CPF, endereço)
3. Chama API Monetrix para gerar PIX
4. Exibe QR Code e código PIX
5. Verifica status do pagamento periodicamente
6. Envia notificação para UTMify

### Estrutura dos Dados Enviados:
```json
{
  "amount": valor_em_centavos,
  "currency": "BRL",
  "paymentMethod": "pix",
  "customer": {
    "name": "nome",
    "email": "email",
    "document": {"type": "cpf", "number": "cpf"},
    "phone": "telefone",
    "address": {...}
  },
  "items": [...],
  "metadata": {...}
}
```

## JavaScript Frontend

### Arquivo: `js/checkout-page.js`
**Funções Principais:**
- `iniciarPagamentoPixPage()` - Inicia processo de pagamento
- `exibirPixGeradoPage()` - Mostra QR Code e código PIX
- `verificarStatusPagamentoPage()` - Verifica status do pagamento
- `copiarCodigoPixPage()` - Copia código PIX

**Endpoints Utilizados:**
- `checkout/pagamento.php` - Gerar PIX
- `checkout/verificar.php` - Verificar status

### Arquivo: `js/app.js`
**Principais Funcionalidades:**
- Gerenciamento do carrinho
- Cálculo de totais
- Navegação entre páginas
- Captura de parâmetros UTM

## Sistema UTM e Tracking

### Arquivos UTM:
- `js/utm-handler.js` - Captura e armazena UTMs
- `js/utm-navigation.js` - Passa UTMs entre páginas
- `js/utm-checkout.js` - Envia UTMs no checkout
- `api/utmify-webhook.php` - Webhook para UTMify
- `api/utmify-pendente.php` - Status pendente para UTMify

### Parâmetros UTM Capturados:
- utm_source
- utm_medium
- utm_campaign
- utm_content
- utm_term

## Banco de Dados

### SQLite Database:
**Arquivo**: `api/database.sqlite`

**Tabela pedidos:**
```sql
CREATE TABLE pedidos (
    id INTEGER PRIMARY KEY,
    transaction_id TEXT,
    external_ref TEXT,
    status TEXT,
    valor INTEGER,
    cliente TEXT,
    produtos TEXT,
    pix_code TEXT,
    qrcode_url TEXT,
    utm_source TEXT,
    utm_medium TEXT,
    utm_campaign TEXT,
    utm_content TEXT,
    utm_term TEXT,
    created_at DATETIME,
    updated_at DATETIME
)
```

## APIs e Webhooks

### Endpoints Disponíveis:
1. **POST** `/api/payment.php` - Gerar PIX (API principal)
2. **POST** `/checkout/pagamento.php` - Gerar PIX (alternativo)
3. **GET** `/api/verify.php` - Verificar status do pagamento
4. **POST** `/api/utmify-webhook.php` - Webhook UTMify
5. **POST** `/checkout/webhook.php` - Webhook Monetrix

### Logs:
- `payment_log.txt` - Log de requisições
- `monetrix_response.log` - Respostas da Monetrix
- `utmify_result.log` - Resultados UTMify
- `checkout/logs/payment_YYYY-MM-DD.log` - Logs diários

## Sistema de Endereços

### Funcionalidades:
- Modal de captura de CEP
- Validação de endereço via API
- Cálculo de distância
- Armazenamento no localStorage

### Estados Suportados:
- SP, RJ, MG, RS, PR, SC

## Fluxo de Compra

### Etapas:
1. **Página Inicial** (`index.html`)
   - Captura CEP e endereço
   - Exibe produtos por categoria
   - Adiciona ao carrinho

2. **Carrinho** (`carrinho.html`)
   - Lista itens selecionados
   - Calcula totais
   - Permite editar quantidades

3. **Checkout** (`checkout.html`)
   - **Etapa 1**: Dados pessoais (nome, telefone)
   - **Etapa 2**: Confirmação endereço
   - **Etapa 3**: Pagamento PIX

4. **Confirmação** (`thankyou.html`)
   - Exibe dados do pedido
   - Informações de entrega

## Recursos Técnicos

### Frameworks/Bibliotecas:
- **Tailwind CSS** - Framework CSS
- **Feather Icons** - Ícones
- **Google Fonts** - Fonte Inter

### Funcionalidades JavaScript:
- LocalStorage para persistência
- Fetch API para requisições
- Máscaras de input (telefone)
- Geração de CPF válido
- QR Code via QR Server API (https://api.qrserver.com)

### Recursos PHP:
- cURL para API calls
- PDO SQLite para banco
- Geração de dados aleatórios
- Headers CORS configurados

## Integração com Monetrix

### Método Atual:
```php
$payload = [
    'amount' => $valor_centavos,
    'currency' => 'BRL', 
    'paymentMethod' => 'pix',
    'customer' => [...],
    'items' => [...],
    'pix' => ['expiresIn' => 60]
];
```

### Headers de Autenticação:
```php
'Authorization: Basic ' . base64_encode($public_key . ':' . $secret_key)
```

## Próximos Passos

### Atualização da API Monetrix
Será necessário atualizar para a nova estrutura da API conforme o exemplo fornecido:

**Nova Estrutura:**
- URL: `https://api.monetrix.store/v1/transactions`
- Authorization: `Basic c2tfX1EzOXhRZFN0NnFQb005Z09CYjVFS1hlRzBpLTNGbzFwTVA3N0JpV1M3Rnlnam5nOng=`
- Novos campos obrigatórios: `subMerchant`, `shipping`
- Estrutura de `items` atualizada

### Campos que Precisam ser Atualizados:
1. Adicionar `subMerchant` com dados do comerciante
2. Adicionar `shipping` com endereço completo
3. Atualizar estrutura dos `items`
4. Revisar `pix.expiresInDays` vs `expiresIn`
5. Verificar novos campos obrigatórios

---

**Data da Análise**: 29 de julho de 2025
**Versão**: 1.0
**Projeto**: Atelier Phamela Gourmet - Cardápio Online
