# CI/CD — Deploy do myreel-api na Hostinger

Workflow: `.github/workflows/deploy-backend.yml`
Espelha o fluxo do frontend (`myreel-web`): **push na `main` → build → FTPS para a Hostinger**.

## Como funciona

1. **Checkout** do repositório.
2. **Setup PHP 8.3** (mínimo exigido pelo `composer.json` é 8.2).
3. **Criação do `.env` de produção** a partir dos *secrets* do GitHub (ver abaixo).
   O `.env` criado é usado apenas para o build — **ele NÃO é enviado ao servidor**.
4. **`composer install --no-dev --optimize-autoloader`** — gera o `vendor/` de produção.
5. **Smoke test** (`php artisan --version`).
6. **Upload via FTPS** de todo o projeto (inclui `vendor/`) para a pasta do domínio
   `api-myreel.rennan-alves.com`.

## O que NÃO é sobrescrito no servidor

O upload usa `dangerous-clean-slate: false` e exclui:

| Excluído | Motivo |
|---|---|
| `.env` | O servidor mantém o próprio `.env` de produção |
| `storage/` | Contém dados/uploads e caches do servidor |
| `public/` | O `public_html` do servidor já tem o `index.php` ajustado (caminho do Laravel) e o symlink `storage` |
| `database/database.sqlite` | Banco local; produção usa MySQL |
| `tests/`, `node_modules/`, `.git*`, `.github/` | Desenvolvimento apenas |
| `php-lint.txt`, `pint-out.txt`, `test-out.txt`, `.phpunit.result.cache` | Artefatos de dev |

## Secrets necessários (GitHub → Settings → Secrets and variables → Actions)

| Secret | Valor |
|---|---|
| `HOSTINGER_FTP_HOST` | Host FTP da Hostinger (mesmo do frontend) |
| `HOSTINGER_FTP_USER` | Usuário FTP (mesmo do frontend) |
| `HOSTINGER_FTP_PASSWORD` | Senha FTP (mesma do frontend) |
| `HOSTINGER_FTP_PORT` | (opcional) 21 |
| `HOSTINGER_FTP_DIR` | Caminho da pasta `api-myreel.rennan-alves.com` no FTP — **diferente do frontend** (que aponta para o `public_html` do site) |
| `API_APP_KEY` | `APP_KEY` de produção (copie do `.env` que já está no servidor, formato `base64:...`) |
| `API_APP_URL` | `https://api-myreel.rennan-alves.com` |
| `API_FRONTEND_URL` | URL do frontend em produção (usada no CORS) |
| `API_DB_HOST` | `127.0.0.1` |
| `API_DB_PORT` | `3306` |
| `API_DB_DATABASE` | Nome do banco MySQL na Hostinger |
| `API_DB_USERNAME` | Usuário do banco |
| `API_DB_PASSWORD` | Senha do banco |

## Pós-deploy (manual, via SSH)

O MySQL da Hostinger só é acessível pelo próprio servidor, então **migrations não
podem rodar no GitHub Actions**. Sempre que um deploy incluir migrations novas,
rode por SSH:

```bash
cd ~/api-myreel.rennan-alves.com   # ajuste para o caminho real
php artisan migrate --force
php artisan config:clear
php artisan route:clear
```

Verificação rápida após o deploy:

```bash
php artisan --version
curl -s https://api-myreel.rennan-alves.com/up
```

## Observações

- O deploy só acontece se **todas** as etapas anteriores passarem (build falhou = job abortado).
- `concurrency: deploy-backend` evita dois deploys simultâneos brigando pelos arquivos.
- O upload do `vendor/` via FTP é pesado (milhares de arquivos): na primeira execução
  pode levar vários minutos; nas seguintes, o FTP-Deploy só sincroniza o que mudou.
- Se quiser automatizar também as migrations no futuro, dá para adicionar um passo
  SSH após o FTP (ex.: `appleboy/ssh-action` rodando `migrate --force`).
