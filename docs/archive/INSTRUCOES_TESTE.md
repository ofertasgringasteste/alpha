# Instruções para Testar a Nova API Monetrix

## ✅ Migração Concluída

A migração da API PIX da Monetrix foi concluída com sucesso! Todas as alterações necessárias foram implementadas nos seguintes arquivos:

### Arquivos Atualizados:
1. **`api/monetrix_config.php`** - Nova configuração com token de autenticação e dados do subMerchant
2. **`api/payment.php`** - Payload atualizado para nova estrutura da API
3. **`checkout/pagamento.php`** - Implementação alternativa com nova estrutura
4. **`api/verify.php`** - Verificação de status com nova autenticação

### Arquivos de Documentação:
1. **`ANALISE_COMPLETA_DO_PROJETO.md`** - Análise completa do projeto
2. **`MIGRACAO_API_MONETRIX.md`** - Documentação da migração
3. **`INSTRUCOES_TESTE.md`** - Este arquivo

## 🧪 Como Testar

### 1. Teste Via Linha de Comando (Recomendado)

```bash
# No diretório do projeto
cd c:\Users\Pichau\Downloads\vini2\morango02\

# Executar teste
php teste_nova_api.php
```

### 2. Teste Via Navegador

Acesse: `http://localhost/morango02/teste_nova_api.php`

### 3. Teste de Integração Completa

1. **Abra o cardápio**: `http://localhost/morango02/index.html`
2. **Adicione produtos ao carrinho**
3. **Vá para checkout**: `http://localhost/morango02/checkout.html`
4. **Preencha os dados e finalize**
5. **Verifique se o PIX é gerado corretamente**

## 📋 O Que Verificar

### ✅ Checklist de Teste:

- [ ] **Token de autenticação** está sendo usado corretamente
- [ ] **Payload** está na nova estrutura
- [ ] **subMerchant** está sendo enviado
- [ ] **shipping** está sendo enviado
- [ ] **items** estão na nova estrutura (`unitPrice`, `quantity`, `tangible`)
- [ ] **PIX** está configurado com `expiresInDays`
- [ ] **QR Code** está sendo gerado
- [ ] **Código PIX** está sendo retornado
- [ ] **Status** de pagamento está funcionando

### 📊 Códigos de Resposta Esperados:

- **200**: Sucesso - PIX gerado corretamente
- **400**: Erro de validação - verificar payload
- **401**: Erro de autenticação - verificar token
- **500**: Erro interno da API

## 🔍 Monitoramento

### Logs para Acompanhar:

```bash
# Logs principais
tail -f api/payment_log.txt
tail -f api/monetrix_response.log
tail -f checkout/logs/payment_*.log

# Logs de teste
ls -la teste_nova_api_*.log
```

### Banco de Dados:

```sql
-- Verificar transações no SQLite
sqlite3 api/database.sqlite "SELECT * FROM pedidos ORDER BY created_at DESC LIMIT 10;"
```

## 🚨 Solução de Problemas

### Problema: Erro 401 (Não Autorizado)
**Solução**: Verificar se o token `MONETRIX_AUTH_TOKEN` está correto

### Problema: Erro 400 (Bad Request)
**Solução**: Verificar estrutura do payload, especialmente:
- `subMerchant` com todos os campos obrigatórios
- `shipping.address` com todos os campos
- `items` com `unitPrice`, `quantity`, `tangible`

### Problema: QR Code não aparece
**Solução**: Verificar se a resposta contém `pix.qrcode` ou `pix.imageUrl`

### Problema: Código PIX vazio
**Solução**: Adicionar fallback para diferentes campos de resposta

## 🔄 Rollback (Se Necessário)

Se algo der errado, você pode reverter para a API antiga:

1. **Restaurar `api/monetrix_config.php`**:
```php
define('MONETRIX_API_KEY', 'pk_ouwx4hvdzP2IcG-qH-KG4tBeF7_rhkba_HYje6SsTjHo5umn');
define('MONETRIX_API_SECRET', 'sk__Q39xQdSt6qPoM9gOBb5EKXeG0i-3Fo1pMP77BiWS7Fygjng');

function getMonetrixAuth() {
    return base64_encode(MONETRIX_API_KEY . ':' . MONETRIX_API_SECRET);
}
```

2. **Reverter estrutura do payload nos arquivos PHP**
3. **Remover campos `subMerchant` e `shipping`**
4. **Restaurar `expiresIn` (minutos) em vez de `expiresInDays`**

## 📞 Suporte

Em caso de dúvidas ou problemas:

1. **Verificar logs** primeiro
2. **Testar com `teste_nova_api.php`**
3. **Comparar com exemplo fornecido**
4. **Verificar documentação da Monetrix**

---

**Data**: 29 de julho de 2025  
**Status**: ✅ Pronto para teste  
**Próximo passo**: Executar `php teste_nova_api.php`
