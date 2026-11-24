set -euo pipefail
umask 0027

ROOT="$(cd "$(dirname "$0")/.." && pwd -P)"
if [ "$ROOT" != /home3/troofeak/repositories/troofe ]; then
    printf '%s\n' 'Déploiement réservé au dépôt cPanel /home3/troofeak/repositories/troofe.' >&2
    exit 1
fi
if [ ! -f "$ROOT/.env" ] || [ -L "$ROOT/.env" ]; then
    printf '%s\n' 'Créer et configurer le fichier privé .env dans le dépôt avant le déploiement. Ne pas copier le .env du Mac.' >&2
    exit 1
fi
chmod 600 "$ROOT/.env"

PHP=/opt/cpanel/ea-php83/root/usr/bin/php
if [ ! -x "$PHP" ]; then
    printf '%s\n' 'PHP CLI ea-php83 indisponible : demander son chemin à l’hébergeur.' >&2
    exit 1
fi
"$PHP" -r 'exit(PHP_VERSION_ID >= 80300 ? 0 : 1);'
COMPOSER="$(command -v composer || true)"
if [ -z "$COMPOSER" ]; then
    COMPOSER=/opt/cpanel/composer/bin/composer
fi
if [ ! -f "$COMPOSER" ]; then
    printf '%s\n' 'Composer CLI indisponible : demander à l’hébergeur son activation pour les tâches de déploiement.' >&2
    exit 1
fi

HOME_DIR=/home3/troofeak
if [ -L "$HOME_DIR/troofe-releases" ]; then
    printf '%s\n' 'Le dossier des versions ne doit pas être un lien symbolique.' >&2
    exit 1
fi
mkdir -p "$HOME_DIR/troofe-releases"
RELEASE="$(mktemp -d "$HOME_DIR/troofe-releases/release-XXXXXXXX")"
git -C "$ROOT" archive HEAD -- . ':(exclude)storage' | tar -x -C "$RELEASE"
ln -s "$ROOT/.env" "$RELEASE/.env"
ln -s "$ROOT/storage" "$RELEASE/storage"
export PATH="$(dirname "$PHP"):$PATH"

"$PHP" "$COMPOSER" install --working-dir="$RELEASE" --no-dev --prefer-dist --optimize-autoloader --no-interaction --no-progress --no-scripts
"$PHP" "$COMPOSER" check-platform-reqs --working-dir="$RELEASE" --no-dev
"$PHP" "$RELEASE/deployment/cpanel.php" check "$ROOT"
"$PHP" "$RELEASE/artisan" package:discover --no-interaction
"$PHP" "$RELEASE/artisan" filament:assets --no-interaction
"$PHP" "$RELEASE/artisan" config:cache --no-interaction
"$PHP" "$RELEASE/artisan" route:cache --no-interaction
"$PHP" "$RELEASE/artisan" view:cache --no-interaction
"$PHP" "$RELEASE/deployment/cpanel.php" publish "$ROOT"
printf '%s\n' 'Version publiée. Vérifier ensuite HTTPS et le DNS avant toute bascule de trafic.'
