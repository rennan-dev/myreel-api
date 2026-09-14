#!/usr/bin/env bash
#
# Deploy do myreel-api na Hostinger (Opção A: SSH + git + composer no servidor)
#
# Fluxo: o clone git em ~/repos/myreel-api é atualizado pelo workflow
# (git fetch + reset --hard), e este script sincroniza o clone com a
# pasta live do domínio, preservando o que NÃO vem do git:
#   - .env            (o servidor mantém o próprio)
#   - storage/        (dados/uploads)
#   - public_html/    (index.php ajustado + symlink storage)
#   - database/database.sqlite (produção usa MySQL)
#
# Depois roda migrations e caches artisan na pasta live.
#
# Configurável via variável de ambiente:
#   MYREEL_LIVE_DIR  (padrão: ~/api-myreel.rennan-alves.com)
#
set -euo pipefail

REPO_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
LIVE_DIR="${MYREEL_LIVE_DIR:-$HOME/api-myreel.rennan-alves.com}"

echo "==> Repo (origem): $REPO_DIR"
echo "==> Live (destino): $LIVE_DIR"

if [ ! -d "$LIVE_DIR" ]; then
    echo "::ERRO:: Pasta live não encontrada: $LIVE_DIR"
    echo "Defina MYREEL_LIVE_DIR se o caminho for diferente."
    exit 1
fi

# O composer install no clone roda o post-autoload-dump
# (artisan package:discover), que precisa de um .env.
# Usamos uma cópia do .env real do servidor.
if [ ! -f "$REPO_DIR/.env" ] && [ -f "$LIVE_DIR/.env" ]; then
    echo "==> Copiando .env do servidor para o clone (apenas para o build)"
    cp "$LIVE_DIR/.env" "$REPO_DIR/.env"
fi

echo "==> composer install (produção, no clone)"
composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader \
    --working-dir="$REPO_DIR"

echo "==> Sincronizando arquivos (rsync) para $LIVE_DIR"
rsync -az --delete \
    --exclude '.env' \
    --exclude '.env.example' \
    --exclude '.git/' \
    --exclude '.github/' \
    --exclude 'storage/' \
    --exclude 'public/' \
    --exclude 'public_html/' \
    --exclude 'node_modules/' \
    --exclude 'tests/' \
    --exclude 'database/database.sqlite' \
    --exclude 'DO_NOT_UPLOAD_HERE/' \
    --exclude 'php-lint.txt' \
    --exclude 'pint-out.txt' \
    --exclude 'test-out.txt' \
    --exclude 'yaml-check.txt' \
    --exclude '.phpunit.result.cache' \
    --exclude '.gitignore' \
    --exclude '.gitattributes' \
    --exclude '.editorconfig' \
    --exclude 'scripts/' \
    "$REPO_DIR"/ "$LIVE_DIR"/

echo "==> Artisan na pasta live (migrate + caches)"
cd "$LIVE_DIR"
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:clear

echo "==> Deploy concluído com sucesso: $(date)"