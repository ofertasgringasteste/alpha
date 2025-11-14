# AlphaBurguer - Vanessa Lanches

**Sistema de Cardápio Digital e Checkout com PIX**

---

## 📋 Visão Geral

Este projeto é um sistema completo de cardápio digital para hamburgeria, com:
- ✅ Catálogo de produtos organizado por categorias
- ✅ Carrinho de compras interativo
- ✅ Checkout com múltiplas etapas
- ✅ Pagamento via PIX (integração Monetrix)
- ✅ Tracking de conversões (UTMify + Facebook Pixel)
- ✅ Verificação automática de pagamento

---

## 🏗️ Arquitetura do Projeto

```
AlphaBurguer/
├── 📄 index.html             # Página principal (catálogo)
├── 📄 carrinho.html          # Página do carrinho
├── 📄 checkout.html          # Checkout (3 etapas)
├── 📄 thankyou.html          # Confirmação de pedido
├── 📄 upsell1-3.html         # Páginas de upsell
├── 📄 config.js              # ⭐ Configuração central (produtos, loja, etc)
├── 📄 start-server.sh        # Script para iniciar servidor local
│
├── 📁 assets/                # Todos os recursos estáticos
│   ├── 📁 css/
│   │   └── styles.css
│   ├── 📁 img/
│   │   ├── produtos/         # Imagens dos produtos
│   │   ├── branding/         # Logo e banner
│   │   └── sistema/          # Ícones e UI
│   └── 📁 js/
│       ├── app.js            # Lógica principal
│       ├── data.js           # Importa config
│       ├── carrinho-page.js  # Lógica do carrinho
│       ├── checkout-page.js  # Lógica do checkout
│       ├── facebook-pixel.js # Tracking Facebook
│       └── utm/              # Scripts de UTM
│
├── 📁 api/                   # Backend PHP
│   ├── payment.php           # Gerar pagamento PIX
│   ├── verify.php            # Verificar status
│   ├── monetrix-config.php   # Config API Monetrix
│   ├── utmify-webhook.php    # Webhook UTMify
│   ├── database.sqlite       # Banco local
│   ├── logs/
│   │   ├── active/           # Logs ativos
│   │   └── archive/          # Logs arquivados
│   └── transactions/
│       ├── pending/
│       ├── completed/
│       └── failed/
│
└── 📁 docs/                  # Documentação
    ├── README.md             # Este arquivo
    ├── ARQUITETURA.md        # Detalhes técnicos
    ├── API_DOCS.md           # Documentação das APIs
    └── archive/              # Docs antigas
```

---

## 🚀 Como Usar

### 1. Iniciar o Servidor Local

```bash
chmod +x start-server.sh
./start-server.sh
```

Ou manualmente:

```bash
php -S localhost:8000 -t .
```

Acesse: **http://localhost:8000**

### 2. Configurar Produtos

Edite o arquivo `config.js` na raiz do projeto:

```javascript
const LOJA_CONFIG = {
    nome: "Vanessa Lanches",
    logo: "assets/img/branding/logo.png",
    // ... outras configurações
};

const PRODUTOS_CONFIG = {
    maisVendidos: [
        {
            id: 'x-tudo',
            nome: 'X-Tudo',
            precoOriginal: 26.80,
            precoPromocional: 16.08,
            imagem: 'assets/img/produtos/X_Tudo.jpg',
            // ...
        }
    ]
};
```

### 3. Configurar API de Pagamento

Edite `api/monetrix-config.php` com suas credenciais:

```php
define('MONETRIX_API_URL', 'https://api.monetrix.store/v1/transactions');
define('MONETRIX_TOKEN', 'seu_token_base64');
```

---

## 📱 Fluxo do Usuário

```
1. 👤 Usuário acessa index.html
   ↓
2. 📦 Informa CEP (modal inicial)
   ↓
3. 🍔 Navega pelo cardápio
   ↓
4. 🛒 Adiciona produtos ao carrinho
   ↓
5. 💳 Clica em "Ver Carrinho" → carrinho.html
   ↓
6. ✅ Clica em "Finalizar Pedido" → checkout.html
   ↓
7. 📝 Preenche dados (nome, telefone)
   ↓
8. 🏠 Confirma endereço
   ↓
9. 💰 Gera PIX e aguarda pagamento
   ↓
10. ✨ Pagamento confirmado → thankyou.html
```

---

## 🔌 APIs Disponíveis

### `POST /api/payment.php`
Gera pagamento PIX via Monetrix

**Request:**
```json
{
  "nome": "Cliente Teste",
  "telefone": "(11) 98765-4321",
  "email": "cliente@email.com",
  "valor": 5000,
  "itens": [...],
  "endereco": {...}
}
```

**Response:**
```json
{
  "success": true,
  "token": "transaction_id",
  "pixCode": "00020126...",
  "qrCodeUrl": "https://api.qrserver.com/...",
  "valor": 50.00
}
```

### `GET /api/verify.php?id={transaction_id}`
Verifica status do pagamento

**Response:**
```json
{
  "success": true,
  "status": "paid",
  "data": {...}
}
```

---

## 📊 Tracking e Analytics

### Facebook Pixel
Eventos rastreados:
- `PageView` - Visualização de página
- `ViewContent` - Produto visualizado
- `AddToCart` - Item adicionado ao carrinho
- `InitiateCheckout` - Checkout iniciado
- `Purchase` - Compra concluída

### UTMify
Parâmetros capturados:
- `utm_source`
- `utm_medium`
- `utm_campaign`
- `utm_content`
- `utm_term`
- `xcod` / `sck` (subafiliados)

---

## 🛠️ Tecnologias

- **Frontend:** HTML, TailwindCSS, Vanilla JavaScript
- **Backend:** PHP 8.4+
- **Banco de Dados:** SQLite
- **Pagamentos:** Monetrix API (PIX)
- **Tracking:** Facebook Pixel, UTMify
- **Ícones:** Feather Icons

---

## 📦 Dependências

### PHP
- PHP 8.4 ou superior
- Extensões: `sqlite3`, `curl`, `json`

### JavaScript
- Nenhuma dependência npm (Vanilla JS)
- CDN: TailwindCSS, Feather Icons

---

## 🔐 Segurança

- ✅ CORS configurado
- ✅ Validação de inputs
- ✅ Geração de CPF válido para testes
- ✅ Logs detalhados de transações
- ✅ Webhooks com validação

---

## 📝 Logs

### Localização
- Logs ativos: `/api/logs/active/`
- Logs arquivados: `/api/logs/archive/`

### Formato
```
[2025-11-13 14:30:45] INFO: Pagamento gerado - ID: abc123
[2025-11-13 14:31:10] SUCCESS: Pagamento confirmado - ID: abc123
```

---

## 🐛 Troubleshooting

### Erro 404 em imagens
- Verifique se as imagens estão em `assets/img/produtos/`
- Confira os caminhos no `config.js`

### PIX não é gerado
- Verifique credenciais em `api/monetrix-config.php`
- Confira logs em `/api/logs/active/`
- Teste conectividade: `curl -X POST http://localhost:8000/api/payment.php`

### Carrinho vazio após recarregar
- Verifique o localStorage do navegador
- Console: `localStorage.getItem('carrinho_produtos')`

---

## 📞 Suporte

- **Documentação:** `/docs/`
- **Issues:** Entre em contato com o desenvolvedor
- **Logs:** Verifique `/api/logs/`

---

## 📄 Licença

Projeto proprietário - Todos os direitos reservados.

---

**Última atualização:** 13 de Novembro de 2025  
**Versão:** 2.0 (Arquitetura Reorganizada)

