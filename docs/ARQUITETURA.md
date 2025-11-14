# Arquitetura do Sistema - AlphaBurguer

**Data:** 13 de Novembro de 2025  
**Versão:** 2.0  
**Status:** ✅ Reorganização Concluída

---

## 📐 Visão Geral da Arquitetura

O sistema AlphaBurguer segue uma arquitetura **monolítica simplificada** com separação clara entre frontend (HTML/JS) e backend (PHP).

```
┌─────────────────────────────────────────────────────────┐
│                      FRONTEND                            │
│  ┌─────────────┐  ┌──────────────┐  ┌──────────────┐   │
│  │  index.html │  │ carrinho.html│  │ checkout.html│   │
│  │  (Catálogo) │→ │  (Carrinho)  │→ │  (Checkout)  │   │
│  └──────┬──────┘  └──────┬───────┘  └──────┬───────┘   │
│         │                 │                  │           │
│         └─────────────────┴──────────────────┘           │
│                           │                              │
│                      config.js                           │
│                (Configuração Central)                    │
│                           │                              │
│         ┌─────────────────┴───────────────┐              │
│         │                                 │              │
│    assets/js/                      assets/css/           │
│  ├── app.js                        └── styles.css        │
│  ├── carrinho-page.js                                    │
│  ├── checkout-page.js                                    │
│  └── utm/ (tracking)                                     │
└─────────────────────────────────────────────────────────┘
                           │
                    AJAX/Fetch API
                           │
┌─────────────────────────────────────────────────────────┐
│                      BACKEND (PHP)                       │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐  │
│  │ payment.php  │  │  verify.php  │  │utmify-*.php  │  │
│  │ (Gera PIX)   │  │ (Verifica $) │  │ (Webhooks)   │  │
│  └──────┬───────┘  └──────┬───────┘  └──────┬───────┘  │
│         │                  │                  │          │
│         └──────────────────┴──────────────────┘          │
│                           │                              │
│                   database.sqlite                        │
│                  (Transações locais)                     │
└─────────────────────────────────────────────────────────┘
                           │
                  Integração Externa
                           │
            ┌──────────────┴──────────────┐
            │                             │
     ┌──────▼───────┐           ┌────────▼────────┐
     │  Monetrix    │           │     UTMify      │
     │   (PIX API)  │           │  (Tracking)     │
     └──────────────┘           └─────────────────┘
```

---

## 🗂️ Estrutura de Diretórios Detalhada

### Raiz do Projeto

```
AlphaBurguer/
├── index.html          # Página principal - catálogo de produtos
├── carrinho.html       # Visualização e edição do carrinho
├── checkout.html       # Processo de checkout (3 etapas)
├── thankyou.html       # Confirmação de pedido
├── upsell1.html        # Ofertas pós-compra (página 1)
├── upsell2.html        # Ofertas pós-compra (página 2)
├── upsell3.html        # Ofertas pós-compra (página 3)
├── config.js           # ⭐ Arquivo de configuração central
└── start-server.sh     # Script bash para iniciar servidor
```

### Assets (`/assets`)

```
assets/
├── css/
│   └── styles.css                 # Estilos customizados
├── img/
│   ├── produtos/                  # Imagens de produtos (69 arquivos)
│   ├── branding/                  # Logo, banner, marca
│   └── sistema/                   # Ícones, UI elements
└── js/
    ├── app.js                     # Lógica principal do app
    ├── data.js                    # Wrapper que importa config.js
    ├── carrinho-page.js           # Lógica específica do carrinho
    ├── checkout-page.js           # Lógica do checkout (3 etapas)
    ├── facebook-pixel.js          # Tracking Facebook Pixel
    └── utm/                       # Módulos de tracking UTM
        ├── utm-handler.js         # Captura parâmetros UTM
        ├── utm-navigation.js      # Propaga UTMs entre páginas
        ├── utm-checkout.js        # Envia UTMs no checkout
        ├── utmify-events.js       # Eventos customizados UTMify
        └── remove-utm-debugger.js # Remove debugger de produção
```

### Backend API (`/api`)

```
api/
├── payment.php               # Gera pagamento PIX via Monetrix
├── verify.php                # Verifica status do pagamento
├── monetrix-config.php       # Configuração da API Monetrix
├── utmify-webhook.php        # Webhook para UTMify
├── utmify-pendente.php       # Notifica UTMify (status pendente)
├── cors-check.php            # Verificação CORS
├── database.sqlite           # Banco SQLite local
├── logs/
│   ├── active/               # Logs dos últimos 7 dias
│   └── archive/              # Logs arquivados
└── transactions/
    ├── pending/              # JSONs de transações pendentes
    ├── completed/            # JSONs de transações completadas
    └── failed/               # JSONs de transações falhas
```

### Documentação (`/docs`)

```
docs/
├── README.md                 # Documentação principal
├── ARQUITETURA.md            # Este arquivo
├── API_DOCS.md               # Documentação das APIs
├── PRD_REORGANIZACAO.md      # PRD da reorganização
└── archive/                  # Documentos históricos
    ├── ANALISE_COMPLETA_DO_PROJETO.md
    ├── MIGRACAO_API_MONETRIX.md
    ├── MIGRACAO_CONCLUIDA.md
    └── INSTRUCOES_TESTE.md
```

---

## 🔄 Fluxo de Dados

### 1. Carregamento Inicial

```javascript
// 1. Navegador carrega index.html
// 2. Carrega config.js (configurações centralizadas)
// 3. data.js importa e expõe variáveis do config
const produtos = PRODUTOS_CONFIG;
const loja = LOJA_CONFIG;

// 4. app.js inicializa o sistema
carregarInformacoesLoja();
carregarProdutos();
verificarCacheEndereco();
```

### 2. Adição ao Carrinho

```
Usuário clica em produto
     ↓
app.js → abrirModalProduto()
     ↓
Usuário define quantidade
     ↓
app.js → adicionarAoCarrinho()
     ↓
localStorage.setItem('carrinho_produtos')
     ↓
Facebook Pixel → trackAddToCart()
     ↓
Atualiza UI (barra de carrinho visível)
```

### 3. Processo de Checkout

```
checkout.html carrega
     ↓
checkout-page.js inicializa
     ↓
Etapa 1: Dados do cliente
  - Nome
  - Telefone
  - (Email e CPF gerados automaticamente)
     ↓
Etapa 2: Confirmação de endereço
  - Carrega do localStorage
  - Permite alterar
     ↓
Etapa 3: Pagamento PIX
  - Prepara dados do pedido
  - Chama api/payment.php
     ↓
Backend (payment.php):
  1. Valida dados
  2. Chama Monetrix API
  3. Salva em database.sqlite
  4. Retorna PIX Code + QR Code
     ↓
Frontend exibe:
  - QR Code para scan
  - Código PIX para copiar
  - Inicia verificação automática
     ↓
Verificação (loop a cada 3s):
  - Chama api/verify.php
  - Consulta status na Monetrix
  - Se pago → redireciona thankyou.html
```

### 4. Confirmação de Pagamento

```
thankyou.html
     ↓
Exibe resumo do pedido
     ↓
Facebook Pixel → trackPurchase()
     ↓
UTMify → registra conversão
     ↓
Limpa carrinho (localStorage)
     ↓
Oferece upsells (opcional)
```

---

## 💾 Banco de Dados (SQLite)

### Tabela: `pedidos`

```sql
CREATE TABLE pedidos (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    transaction_id TEXT UNIQUE NOT NULL,
    external_ref TEXT,
    status TEXT DEFAULT 'pending',
    valor INTEGER NOT NULL,
    cliente TEXT NOT NULL,
    produtos TEXT NOT NULL,
    pix_code TEXT,
    qrcode_url TEXT,
    utm_source TEXT,
    utm_medium TEXT,
    utm_campaign TEXT,
    utm_content TEXT,
    utm_term TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
```

### Campos

- `transaction_id`: ID retornado pela Monetrix
- `external_ref`: Referência interna (gerada pelo sistema)
- `status`: `pending`, `paid`, `expired`, `failed`
- `valor`: Valor em centavos (ex: 5000 = R$ 50,00)
- `cliente`: JSON com dados do cliente
- `produtos`: JSON com itens do pedido
- `pix_code`: Código PIX copia-e-cola
- `qrcode_url`: URL do QR Code gerado
- `utm_*`: Parâmetros de tracking

---

## 🔌 Integrações Externas

### Monetrix API (Pagamentos PIX)

**Endpoint:** `https://api.monetrix.store/v1/transactions`  
**Autenticação:** Bearer Token (Base64)  
**Método:** POST

**Request Body:**
```json
{
  "amount": 5000,
  "paymentMethod": "pix",
  "pix": { "expiresInDays": 1 },
  "customer": {
    "name": "Cliente Nome",
    "email": "cliente@email.com",
    "document": { "type": "cpf", "number": "12345678901" }
  },
  "items": [
    {
      "title": "X-Tudo",
      "unitPrice": 1608,
      "quantity": 1,
      "tangible": false
    }
  ],
  "shipping": {
    "fee": 0,
    "address": { ... }
  },
  "subMerchant": { ... }
}
```

**Response:**
```json
{
  "id": "transaction_id",
  "status": "pending",
  "pix": {
    "qrcode": "00020126...",
    "imageUrl": "https://...",
    "expiresAt": "2025-11-14T10:00:00Z"
  }
}
```

### UTMify (Tracking de Conversões)

**Webhook:** `api/utmify-webhook.php`  
**Eventos:**
- `lead` - Lead capturado
- `pending` - Pagamento pendente
- `paid` - Pagamento confirmado

**Payload Enviado:**
```json
{
  "event": "paid",
  "value": 50.00,
  "transaction_id": "abc123",
  "customer": {
    "name": "Cliente Nome",
    "phone": "(11) 98765-4321",
    "email": "cliente@email.com"
  },
  "utmParams": {
    "utm_source": "facebook",
    "utm_campaign": "promo_novembro"
  }
}
```

### Facebook Pixel

**Pixel ID:** Configurado em `assets/js/facebook-pixel.js`

**Eventos Padrão:**
- `PageView`
- `ViewContent`
- `AddToCart`
- `InitiateCheckout`
- `Purchase`

**Parâmetros Customizados:**
```javascript
fbq('track', 'Purchase', {
  value: 50.00,
  currency: 'BRL',
  content_ids: ['x-tudo', 'batata-frita'],
  content_type: 'product'
});
```

---

## 🔐 Segurança

### CORS (Cross-Origin Resource Sharing)

Configurado em todas as APIs PHP:
```php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
```

### Validação de Inputs

- CPF: Gerado com algoritmo válido
- Telefone: Máscara `(XX) XXXXX-XXXX`
- Email: Validação regex
- CEP: Consultado via ViaCEP

### Logs de Segurança

Todas as requisições são logadas:
```
[2025-11-13 14:30:45] INFO: Requisição recebida
[2025-11-13 14:30:46] SUCCESS: Pagamento gerado - ID: abc123
```

---

## 📊 Performance

### Frontend

- **Carregamento:** < 2s (first contentful paint)
- **Assets:** Imagens otimizadas (WebP quando possível)
- **JavaScript:** Vanilla JS (sem frameworks pesados)
- **CSS:** TailwindCSS via CDN (prod) ou local (dev)

### Backend

- **Tempo de resposta:** < 300ms (99th percentile)
- **Banco SQLite:** Queries otimizadas com índices
- **Cache:** localStorage para carrinho e endereço

### Otimizações

- ✅ Lazy loading de imagens
- ✅ Minificação de assets (produção)
- ✅ Gzip compression (servidor)
- ✅ CDN para bibliotecas externas

---

## 🧪 Testes

### Manual

1. **Fluxo Completo:**
   ```bash
   # Iniciar servidor
   ./start-server.sh
   
   # Acessar: http://localhost:8000
   # Adicionar produto ao carrinho
   # Preencher checkout
   # Gerar PIX (ambiente de teste)
   # Verificar logs
   ```

2. **Verificar APIs:**
   ```bash
   # Testar geração de PIX
   curl -X POST http://localhost:8000/api/payment.php \
     -H "Content-Type: application/json" \
     -d '{"valor": 5000, "cliente": {...}, "itens": [...]}'
   
   # Testar verificação
   curl http://localhost:8000/api/verify.php?id=transaction_id
   ```

### Checklist

- [ ] Produtos carregam corretamente
- [ ] Imagens são exibidas
- [ ] Carrinho funciona
- [ ] Checkout processa
- [ ] PIX é gerado
- [ ] Verificação automática funciona
- [ ] UTMs são capturados
- [ ] Facebook Pixel dispara eventos

---

## 🔧 Manutenção

### Adicionar Novo Produto

1. Adicionar imagem em `assets/img/produtos/`
2. Editar `config.js`:
   ```javascript
   hamburgueresEspeciais: [
       {
           id: 'novo-produto',
           nome: 'Novo Burguer',
           precoOriginal: 35.90,
           precoPromocional: 21.54,
           imagem: 'assets/img/produtos/novo_burguer.jpg',
           disponivel: true,
           descricao: 'Descrição do produto'
       }
   ]
   ```

### Atualizar Preços

Editar apenas `config.js` - não há cache de preços.

### Logs

Rotacionar logs manualmente:
```bash
mv api/logs/active/*.log api/logs/archive/
```

Ou configurar cron job para rotação automática.

---

## 📈 Melhorias Futuras

- [ ] Admin panel para gerenciar produtos
- [ ] Relatórios de vendas
- [ ] Cupons de desconto
- [ ] Integração com delivery (iFood, Uber Eats)
- [ ] Notificações por WhatsApp
- [ ] App mobile (PWA)
- [ ] Sistema de fidelidade

---

**Última atualização:** 13 de Novembro de 2025  
**Mantido por:** Equipe de Desenvolvimento AlphaBurguer

