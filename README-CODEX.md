# Sistema de Orçamentos — contexto para manutenção

Este projeto é um sistema Laravel multiempresa para cadastros, orçamentos, pedidos e comissão.

## Como rodar localmente

Use PHP 8.3:

```powershell
& "C:/caminho/do/projeto" artisan serve
```

URL local:

```txt
http://127.0.0.1:8000/login
```

## Banco local

O ambiente local usa MySQL:

```txt
DB_CONNECTION=mysql
DB_DATABASE=orcamentos
DB_USERNAME=root
```

## Usuário principal

Existe um superadmin do sistema, responsável por criar empresas e usuários.

```txt
Email: mauriciogirotto22@gmail.com
Cargo: Administrador Multiempresa
```

Não registrar usuários pela rota pública. O cadastro público `/register` foi desativado de propósito.

## Cargos

Na operação das empresas:

- Gerente: administra usuários da empresa, itens, configurações e também pode criar orçamento.
- Supervisor: acompanha clientes/orçamentos/pedidos da empresa e pode criar orçamento.
- Vendedor: cria clientes e orçamentos próprios.

## Fluxo principal

1. Superadmin cria empresa.
2. Superadmin ativa módulo `orcamentos`.
3. Superadmin ou gerente cria usuários.
4. Gerente cadastra itens, tributação, frete e faixas de comissão.
5. Usuários criam orçamentos.
6. Orçamento pode virar pedido.
7. Relatório de comissão calcula por mês/vendedor, usando valor vendido sem impostos e desconto médio ponderado.

## Backup local sem Git

Antes de alterações grandes, criar um `.zip` em:

```txt
C:/caminho/do/projeto
```

Padrão de nome:

```txt
yyyy-MM-dd-HHmm-descricao-da-versao.zip
```

Os backups locais não precisam incluir:

- `vendor`
- `node_modules`
- caches de `storage/framework`
- logs de `storage/logs`

Depois de restaurar um backup sem `vendor` e `node_modules`, rodar:

```powershell
composer install
npm install
npm run build
& "C:/caminho/do/projeto" artisan optimize:clear
```

## Checklist antes de hospedagem

- `APP_ENV=production`
- `APP_DEBUG=false`
- domínio apontando para `/public`
- `php artisan storage:link`
- `php artisan migrate --force`
- cron para `php artisan schedule:run`
- SMTP real configurado se for usar recuperação de senha
- backup diário validado

## Testes

Rodar:

```powershell
& "C:/caminho/do/projeto" artisan test
```

