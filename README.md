# Sistema de orçamentos multiempresa

> Em revisão. A suíte da cópia pública apresentou 25 falhas e 1 erro em 10/11/2026. Veja a seção de validação ao final deste README.

Aplicação web em Laravel para gerir clientes, itens e orçamentos comerciais, com várias empresas no mesmo sistema. Cada empresa liga ou desliga módulos, define a própria comissão e só enxerga os próprios dados.

## O que tem

- Multiempresa com módulos por empresa (`EnsureModuloAtivo`).
- Papéis e permissões (Spatie Permission) e Policies: o vendedor edita só o que é dele; o administrador da empresa e o supervisor enxergam o conjunto.
- Histórico de alterações (Spatie Activitylog) e exclusão lógica nas tabelas sensíveis.
- Comissão por faixa de desconto, configurável por empresa; impostos gravados no orçamento.
- PDF de orçamento (DomPDF), consulta de CNPJ e CEP, comandos agendados de expiração e backup.
- Painel de superadmin, com acesso como outro usuário para suporte.

## Como rodar

Requisitos: PHP 8.3, Composer 2, Node 22, MySQL 8.

```bash
cp .env.example .env
composer install
php artisan key:generate
npm install && npm run build
```

No `.env`, ajuste `DB_*` e defina `SUPER_ADMIN_NAME`, `SUPER_ADMIN_EMAIL` e `SUPER_ADMIN_PASSWORD`. Depois:

```bash
php artisan migrate
php artisan db:seed
php artisan serve
```

O seeder cria os papéis, os módulos e o superadmin a partir do `.env`. Não há dados de clientes no repositório.

## Testes

```bash
php artisan test
```

## Documentação

`especificacao-sistema-orcamentos.md` descreve as regras de negócio e `README-CODEX.md`, o roteiro de implementação usado com o agente de código.

## Estado da cópia pública

A revisão de 10/11/2026 executou 113 testes, com 25 falhas, 1 erro e 483 asserções. Esta versão requer revisão. Entre os problemas registrados estão validações que exigem a tabela do item e uma chamada de `all()` sobre um array no histórico de preços.

[Relatório de validação](https://grxsistemas.com.br/portifolio/validacao)