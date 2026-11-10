# Sistema Multiempresa de Orçamentos — Especificação Técnica

## Objetivo

Construir um sistema web multiempresa (multi-tenant) em **Laravel** para gestão de clientes, itens/produtos e orçamentos comerciais. O sistema deve ser modular: cada empresa cliente do sistema pode ter módulos habilitados ou desabilitados individualmente. O primeiro módulo a ser implementado é o de **Orçamentos**.

Implemente o sistema completo seguindo as especificações abaixo. Onde houver ambiguidade, escolha a solução mais simples e convencional dentro do ecossistema Laravel, mantendo consistência com as decisões técnicas já definidas neste documento.

---

## 1. Stack técnica definida

- **Framework**: Laravel (versão estável mais recente)
- **Banco de dados**: MySQL/MariaDB
- **Multiempresa**: banco de dados único, com coluna `empresa_id` em todas as tabelas que pertencem a uma empresa. Usar **Global Scope** do Eloquent para filtrar automaticamente por `empresa_id` da empresa do usuário autenticado.
- **Permissões**: `spatie/laravel-permission` para papéis (roles), combinado com **Policies** do Laravel para regras de ownership (ex.: vendedor só acessa seus próprios registros).
- **Auditoria/histórico**: `spatie/laravel-activitylog` para log de alterações; `SoftDeletes` nativo do Laravel em vez de exclusão física em tabelas sensíveis (orçamentos, clientes, itens).
- **Geração de PDF**: `barryvdh/laravel-dompdf`, renderizando o orçamento a partir de uma view Blade (HTML/CSS).
- **Busca de CNPJ**: integração com a **BrasilAPI** (`https://brasilapi.com.br/api/cnpj/v1/{cnpj}`), sem necessidade de chave de API.
- **E-mail**: Laravel Mail configurado com um provedor transacional (Amazon SES, Mailgun ou Brevo) em vez de SMTP direto da VPS, para evitar problemas de entregabilidade/spam.
- **Tarefas agendadas**: Laravel Scheduler (via cron) para rotina diária de expiração automática de orçamentos vencidos.
- **Autenticação**: Laravel Breeze ou Fortify (sessão padrão, sem necessidade de SPA/API token nesta fase).

---

## 2. Arquitetura Multiempresa (Super Admin)

### 2.1 Painel do Administrador Multiempresa

Usuário com papel `super_admin`, acima de qualquer empresa, com acesso a:

- **Cadastro de empresas (tenants)**:
  - Nome da empresa, razão social, CNPJ (com busca automática via BrasilAPI)
  - Upload de logo da empresa (usado futuramente nos PDFs de orçamento)
  - Status (ativa/inativa/suspensa)
- **Gestão de módulos por empresa**:
  - Tabela `modulos` (ex.: "orcamentos") e tabela pivot `empresa_modulo` com campo booleano `ativo`
  - Middleware que verifica `empresa->moduloAtivo('orcamentos')` antes de liberar qualquer rota do módulo
- **Gestão de usuários do sistema**:
  - Ao cadastrar uma empresa nova, o super admin deve poder, em uma tela subsequente, criar os usuários daquela empresa (admin da empresa, supervisor, vendedor)
- **Log de auditoria geral**: quem alterou o quê, quando, em qualquer entidade do sistema (não somente preços de item)

### 2.2 Papéis (Roles)

| Papel | Escopo | Permissões principais |
|---|---|---|
| `super_admin` | Sistema inteiro | Gerencia empresas, módulos, usuários globais |
| `admin_empresa` | Uma empresa | Gerencia usuários da própria empresa, configura comissionamento (tela "Representação"), vê todos os clientes/orçamentos da empresa |
| `supervisor` | Uma empresa | Vê panorama geral de todos os vendedores da empresa (clientes e orçamentos), mas não configura comissionamento |
| `vendedor` / `representante` | Próprios registros | Cria/edita/exclui (soft delete) apenas seus próprios clientes e orçamentos; não acessa comissionamento de outros |

Regra importante: **role não resolve ownership**. Usar Policies (`OrcamentoPolicy`, `ClientePolicy`) para garantir que um vendedor só edite registros onde `user_id === auth()->id()`, exceto quando o papel for `admin_empresa` ou `supervisor`.

---

## 3. Módulo: Orçamentos

### 3.1 Cadastro de Clientes (por empresa)

- Nome ou Razão Social, CNPJ (com busca automática via BrasilAPI), telefone, e-mail
- Cada cliente pertence a um vendedor específico (campo `user_id`) dentro da empresa
- Validação obrigatória antes de excluir (soft delete) ou salvar

### 3.2 Cadastro de Itens/Produtos

- Descrição, número de série (editável), preço atual
- **Histórico de preços**: tabela separada (`item_precos_historico`) registrando preço + data, exibido como log na tela de edição do item. Toda alteração de preço gera um novo registro histórico, preservando os anteriores.

### 3.3 Configuração de Comissionamento ("Representação")

Tela acessível somente por `admin_empresa`:

- Tabela de faixas de desconto → percentual de comissão (ex.: desconto de 60% → comissão de 3%; desconto de 58% → comissão de 4%). Os valores são configuráveis por empresa, não fixos no código.
- Essa configuração alimenta o cálculo automático de comissão na tela de criação de orçamento, mas **não é visível** para o vendedor — apenas o admin da empresa acessa essa tela.

### 3.4 Criação de Orçamento

**Campos da tela:**

- **Dados da empresa** (somente leitura para o vendedor; editável apenas pelo super admin): nome, CNPJ, telefone, e-mail
- **Dados do cliente** *(obrigatório)*: busca por nome/CNPJ pré-cadastrado, preenchimento automático de telefone e e-mail
- **Número do orçamento**: gerado automaticamente e sequencialmente por empresa, formato `ORC-{ano}-{sequencial}` (ex.: `ORC-2026-001`). A geração deve ser atômica para evitar duplicidade em caso de concorrência (usar transação de banco com lock).
- **Data e hora de emissão**: automáticas
- **Itens do orçamento**: descrição, quantidade, valor unitário (buscado do cadastro, mas pode ter desconto individual por item), valor total calculado
  - **Desconto por item**: percentual individual, que alimenta o cálculo de comissão configurado na tela de Representação
- **Soma de todos os itens**: calculada automaticamente
- **Forma de pagamento** *(obrigatório)*: PIX, Boleto (com opções de parcelamento) ou Cartão
- **Validade da proposta** *(obrigatório)*: ex. "Válido por 15 dias" — usado pela rotina de expiração automática
- **Observações**: campo livre, opcional
- **Frete** *(obrigatório)*: CIF ou FOB
- **Responsável pelo orçamento**: nome e cargo do usuário logado, preenchido automaticamente

**Cálculo final**: valor total dos itens + impostos (impostos ficam como módulo futuro — estruturar o cálculo de forma isolada/extensível, mas não implementar regras fiscais completas nesta fase).

### 3.5 Fluxo de Status do Orçamento

Implementar status explícito (campo `status` enum), não apenas "aberto/excluído":

```
Rascunho → Enviado → Aprovado
                   → Rejeitado
                   → Expirado (automático, via Scheduler, após validade da proposta)
```

- Orçamento **não deve ser excluído fisicamente** depois de enviado — usar SoftDelete e/ou status para representar cancelamento, preservando histórico.
- Funcionalidade de **duplicar orçamento** (clonar todos os dados para um novo rascunho), útil para clientes recorrentes.

### 3.6 Geração de PDF e Envio por E-mail

- Ao salvar o orçamento, gerar PDF via `laravel-dompdf`, usando o logo da empresa cadastrado no painel multiempresa
- Ao salvar, abrir modal de confirmação para envio por e-mail (campo de e-mail em branco, preenchido manualmente no momento)
- Registrar histórico de envios do orçamento (para quem, quando) em tabela própria

### 3.7 Listagem e Filtros

- Tela de orçamentos em aberto com filtro por número de orçamento e por cliente
- Vendedor vê apenas seus próprios orçamentos; supervisor e admin_empresa veem o panorama completo da empresa (todos os vendedores)

---

## 4. Modelo de Dados (entidades principais)

```
empresas (id, razao_social, cnpj, telefone, email, logo_path, status)
modulos (id, nome, slug)
empresa_modulo (empresa_id, modulo_id, ativo)
users (id, empresa_id, name, email, password, cargo)
roles / permissions (via spatie/laravel-permission)

clientes (id, empresa_id, user_id, nome_razao_social, cnpj, telefone, email)

itens (id, empresa_id, descricao, numero_serie, preco_atual)
item_precos_historico (id, item_id, preco, registrado_em)

faixas_comissao (id, empresa_id, desconto_percentual, comissao_percentual)

orcamentos (
  id, empresa_id, user_id, cliente_id, numero, status,
  data_emissao, hora_emissao, forma_pagamento, validade_dias,
  observacoes, frete_tipo, valor_total, deleted_at
)
orcamento_itens (
  id, orcamento_id, item_id, quantidade, valor_unitario,
  desconto_percentual, valor_total
)
orcamento_envios (id, orcamento_id, email_destino, enviado_em)

activity_log (gerado pelo spatie/laravel-activitylog)
```

---

## 5. Requisitos não-funcionais

- **Validação**: toda ação de salvar ou excluir deve passar por validação de formulário (Form Requests do Laravel) e confirmação explícita no frontend antes de excluir.
- **Auditoria**: todas as alterações relevantes (preços, orçamentos, comissionamento) devem ser registradas via `spatie/laravel-activitylog`.
- **Segurança**: uso de Policies para todo controle de acesso a registros (não confiar apenas em middleware de role); proteção CSRF padrão do Laravel; sanitização e uso de Eloquent/Query Builder (nunca SQL bruto concatenado).
- **LGPD**: já que o sistema armazena CNPJ e dados de contato de clientes, considerar desde já campos de auditoria de acesso a dados pessoais e política de retenção, mesmo que a implementação completa fique para uma fase posterior.

---

## 6. Ordem sugerida de implementação

1. Setup do projeto Laravel + autenticação (Breeze/Fortify)
2. Estrutura multiempresa: migrations de `empresas`, `modulos`, `empresa_modulo`, Global Scope de `empresa_id`
3. Papéis e permissões (`spatie/laravel-permission`) + Policies básicas
4. Painel do super admin: CRUD de empresas (com busca CNPJ via BrasilAPI), upload de logo, ativação de módulos, criação de usuários da empresa
5. Módulo Orçamentos — Cadastros base: clientes, itens (com histórico de preço)
6. Tela de Representação (faixas de comissão) — acesso restrito a `admin_empresa`
7. Tela de criação/edição de orçamento, com cálculo de itens, desconto individual e geração de número sequencial
8. Fluxo de status + Scheduler de expiração automática
9. Geração de PDF + envio por e-mail + histórico de envios
10. Listagem com filtros + dashboard de panorama geral (supervisor/admin)

---

## Instruções para o Codex

Implemente este sistema seguindo rigorosamente as decisões técnicas da Seção 1. Gere as migrations, models (com relacionamentos, Global Scopes e SoftDeletes onde indicado), Policies, Form Requests, controllers e views Blade necessárias. Priorize código idiomático Laravel (Eloquent, Resource Controllers, Form Requests) em vez de soluções customizadas quando o framework já oferece a ferramenta padrão. Comece pela ordem sugerida na Seção 6 e pergunte antes de tomar decisões de arquitetura que não estejam cobertas neste documento.
