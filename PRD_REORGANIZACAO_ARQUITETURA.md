# PRD - Reorganização da Arquitetura - AlphaBurguer (Vanessa Lanches)

**Data:** 13 de Novembro de 2025  
**Versão:** 1.0  
**Status:** 🚧 Em Implementação

---

## 📋 Sumário Executivo

### Problema Atual
O projeto apresenta uma estrutura desorganizada com múltiplas duplicações de arquivos, pastas de imagens dispersas, configurações conflitantes e APIs duplicadas. Isso dificulta a manutenção, aumenta o risco de erros e gera confusão sobre qual arquivo/versão está sendo utilizada.

### Principais Problemas Identificados

1. **Identidade Conflitante:**
   - Pasta do projeto: `AlphaBurguer`
   - Documentação menciona: "Phamella Gourmet" (frutas do amor)
   - Configuração atual: "Vanessa Lanches" (hamburguer ia)
   - Instagram no HTML: `@phamela.gourmetofc`

2. **Duplicação de Arquivos:**
   - `config.js` (raiz e `/js/`)
   - `database.sqlite` (`/api` e `/checkout`)
   - APIs duplicadas (`/api/payment.php` e `/checkout/pagamento.php`)
   - Arquivos de verificação duplicados

3. **Imagens Desorganizadas:**
   - `/assets/img/` (54 arquivos)
   - `/Imagens_produtos/` (69 arquivos)
   - `/images/produtos/` (25 arquivos)
   - `/product/` (subpastas por código)

4. **Logs e Transações:**
   - Logs antigos de julho/2025 não arquivados
   - Transações JSON espalhadas
   - Banco de dados SQLite duplicado

5. **Arquivos de Documentação:**
   - Documentação incompleta ou desatualizada
   - Instruções de migração já concluídas (podem ser arquivadas)

---

## 🎯 Objetivos da Reorganização

### Objetivos Principais
1. **Consolidar** todas as APIs em um único diretório organizado
2. **Unificar** pastas de imagens com estrutura hierárquica clara
3. **Remover** todos os arquivos duplicados e desnecessários
4. **Padronizar** nomenclatura de arquivos e diretórios
5. **Centralizar** configurações em arquivo único
6. **Organizar** logs e dados históricos
7. **Documentar** a nova arquitetura de forma clara

### Benefícios Esperados
- ✅ Redução de 60%+ no número de arquivos
- ✅ Estrutura clara e intuitiva
- ✅ Facilidade de manutenção
- ✅ Redução de bugs por confusão de versões
- ✅ Melhor performance (menos arquivos para servir)
- ✅ Onboarding mais rápido para novos desenvolvedores

---

## 📁 Estrutura Proposta

```
AlphaBurguer/
├── 📄 index.html                 # Página principal
├── 📄 carrinho.html              # Página do carrinho
├── 📄 checkout.html              # Página de checkout
├── 📄 thankyou.html              # Página de agradecimento
├── 📄 upsell1.html               # Upsells
├── 📄 upsell2.html
├── 📄 upsell3.html
├── 📄 config.js                  # ⭐ ÚNICO arquivo de configuração
├── 📄 start-server.sh            # Script para iniciar servidor
│
├── 📁 assets/                    # ⭐ TODOS os assets do projeto
│   ├── 📁 css/
│   │   └── styles.css
│   ├── 📁 img/
│   │   ├── 📁 produtos/          # Imagens dos produtos
│   │   ├── 📁 branding/          # Logo, banner, ícones
│   │   └── 📁 sistema/           # Imagens do sistema
│   └── 📁 js/
│       ├── app.js                # Script principal
│       ├── data.js               # Importa config.js
│       ├── carrinho-page.js
│       ├── checkout-page.js
│       ├── facebook-pixel.js
│       └── 📁 utm/               # Scripts UTM organizados
│           ├── utm-handler.js
│           ├── utm-navigation.js
│           ├── utm-checkout.js
│           └── utmify-events.js
│
├── 📁 api/                       # ⭐ ÚNICA pasta de APIs
│   ├── 📄 payment.php            # API de pagamento (principal)
│   ├── 📄 verify.php             # Verificação de pagamento
│   ├── 📄 monetrix-config.php    # Config Monetrix
│   ├── 📄 utmify-webhook.php     # Webhook UTMify
│   ├── 📄 cors-check.php         # Verificação CORS
│   ├── 📄 database.sqlite        # ⭐ ÚNICO banco
│   ├── 📁 logs/                  # Logs da API
│   │   ├── 📁 active/            # Logs ativos (últimos 7 dias)
│   │   └── 📁 archive/           # Logs arquivados
│   └── 📁 transactions/          # JSONs das transações
│       ├── 📁 pending/
│       ├── 📁 completed/
│       └── 📁 failed/
│
├── 📁 docs/                      # ⭐ Documentação centralizada
│   ├── 📄 README.md              # Documentação principal
│   ├── 📄 ARQUITETURA.md         # Arquitetura do sistema
│   ├── 📄 API_DOCS.md            # Documentação das APIs
│   ├── 📄 DEPLOYMENT.md          # Guia de deploy
│   └── 📁 archive/               # Docs antigas/migração
│       ├── ANALISE_COMPLETA_DO_PROJETO.md
│       ├── MIGRACAO_API_MONETRIX.md
│       ├── MIGRACAO_CONCLUIDA.md
│       └── INSTRUCOES_TESTE.md
│
└── 📁 .archive/                  # ⚠️ Arquivos removidos (backup temporário)
    └── ... (será deletado após validação)
```

---

## 🗑️ Arquivos a Serem Removidos

### Duplicados
- ❌ `/js/config.js` (mover conteúdo para `/config.js` raiz)
- ❌ `/checkout/` (toda pasta - consolidar em `/api`)
- ❌ `/Imagens_produtos/` (consolidar em `/assets/img/produtos`)
- ❌ `/images/` (consolidar em `/assets/img/`)
- ❌ `/product/` (consolidar em `/assets/img/produtos`)

### Temporários/Teste
- ❌ `teste_nova_api.php`
- ❌ `phpinfo.php`
- ❌ `iniciar-servidor.php`
- ❌ `fix-navegacao.js` (verificar se usado)
- ❌ `/js/remove-utm-debugger.js`
- ❌ `/api/buckpay-webhook.php` (se não usado)
- ❌ `/api/force-cors.php` (se não usado)

### Logs Antigos
- ❌ `/checkout/logs/*.log` (arquivar apenas)
- ❌ `/api/logs/utmify_integration_2025-07-30.log` (arquivar)

---

## 📝 Plano de Migração

### Fase 1: Preparação (5 min)
1. ✅ Criar backup completo do projeto
2. ✅ Criar nova estrutura de pastas
3. ✅ Documentar estrutura antiga vs nova

### Fase 2: Consolidação de Imagens (10 min)
1. Mover todas as imagens para `/assets/img/produtos/`
2. Renomear arquivos com padrão consistente
3. Atualizar referências no `config.js`

### Fase 3: Consolidação de APIs (15 min)
1. Consolidar APIs em `/api/`
2. Manter apenas `database.sqlite` em `/api/`
3. Atualizar referências no frontend
4. Testar endpoints

### Fase 4: Organização de Assets (10 min)
1. Mover CSS para `/assets/css/`
2. Organizar JS em `/assets/js/`
3. Criar subpasta `/utm/` para scripts UTM

### Fase 5: Limpeza (10 min)
1. Remover arquivos duplicados
2. Mover docs antigas para `/docs/archive/`
3. Arquivar logs antigos

### Fase 6: Validação e Testes (15 min)
1. Testar página principal
2. Testar carrinho
3. Testar checkout
4. Testar pagamento PIX
5. Verificar logs

### Fase 7: Documentação Final (10 min)
1. Criar `docs/README.md`
2. Criar `docs/ARQUITETURA.md`
3. Atualizar este PRD

---

## ✅ Critérios de Aceitação

### Funcionalidade
- [ ] Página principal carrega corretamente
- [ ] Produtos são exibidos com imagens corretas
- [ ] Carrinho funciona normalmente
- [ ] Checkout processa corretamente
- [ ] Pagamento PIX é gerado
- [ ] Verificação de pagamento funciona
- [ ] UTMs são capturados e enviados
- [ ] Logs são gravados corretamente

### Estrutura
- [ ] Apenas 1 arquivo `config.js`
- [ ] Apenas 1 `database.sqlite`
- [ ] Todas as imagens em `/assets/img/produtos/`
- [ ] Todas as APIs em `/api/`
- [ ] Logs organizados em `/api/logs/`
- [ ] Documentação em `/docs/`
- [ ] Sem arquivos duplicados

### Performance
- [ ] Tempo de carregamento mantido ou melhorado
- [ ] Sem erros 404 (arquivos não encontrados)
- [ ] Sem avisos no console

---

## 🚨 Riscos e Mitigações

### Risco 1: Quebrar funcionalidade existente
**Mitigação:** 
- Fazer backup completo antes
- Testar cada fase antes de prosseguir
- Manter versão antiga em `.archive/` temporariamente

### Risco 2: Perder dados de transações
**Mitigação:**
- Backup do banco SQLite
- Manter JSONs de transações
- Verificar integridade dos dados

### Risco 3: Referências quebradas no código
**Mitigação:**
- Fazer busca global por caminhos antigos
- Atualizar todos os `<script src="">` e `<img src="">`
- Testar todas as páginas

---

## 📊 Métricas de Sucesso

- **Redução de arquivos:** -60% (de ~200 para ~80 arquivos)
- **Tempo de build:** Mantido ou melhorado
- **Facilidade de manutenção:** +80% (subjetivo - pesquisa com devs)
- **Bugs por versão errada:** -100% (eliminar duplicações)
- **Tempo de onboarding:** -50% (estrutura mais clara)

---

## 👥 Stakeholders

- **Desenvolvedor Principal:** Responsável pela implementação
- **Usuários Finais:** Clientes que fazem pedidos
- **Proprietário:** Vanessa Lanches
- **Equipe de Marketing:** Campanhas UTM

---

## 📅 Timeline

| Fase | Duração | Status |
|------|---------|--------|
| Análise | 30 min | ✅ Concluído |
| PRD | 20 min | ✅ Concluído |
| Implementação | 60 min | 🚧 Em andamento |
| Testes | 15 min | ⏳ Pendente |
| Deploy | 5 min | ⏳ Pendente |

**Tempo Total Estimado:** ~2 horas

---

## 📚 Referências

- Documentação Monetrix API
- Análise Completa do Projeto (docs/archive/)
- Migração API Monetrix (docs/archive/)

---

**Aprovado por:** Sistema de IA  
**Data de Aprovação:** 13/11/2025  
**Próxima Revisão:** Após implementação

