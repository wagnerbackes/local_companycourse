#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$ROOT"

IOMAD_BRANCH="${IOMAD_BRANCH:-IOMAD_500_STABLE}"
IOMAD_REPO="${IOMAD_REPO:-https://github.com/iomad/iomad.git}"
PLUGIN_DIR="moodle/local/companycourse"

die() {
  echo "Erro: $*" >&2
  exit 1
}

require_cmd() {
  command -v "$1" >/dev/null 2>&1 || die "'$1' não encontrado. Instale-o antes de continuar."
}

echo "==> Instalação completa — Moodle 5 / IOMAD 5 / local_companycourse"
echo "    Diretório: $ROOT"

echo "==> Verificando pré-requisitos..."
require_cmd git
require_cmd docker
docker compose version >/dev/null 2>&1 || die "'docker compose' não disponível. Instale Docker Compose v2."
docker info >/dev/null 2>&1 || die "Docker daemon inacessível. Inicie o Docker e tente de novo."

[[ -f docker-compose.yml ]] || die "docker-compose.yml não encontrado. Execute a partir da raiz do repositório (após git clone)."
[[ -f .env.example ]] || die ".env.example não encontrado."
[[ -f docker/config.php ]] || die "docker/config.php não encontrado."
[[ -f scripts/seed.sh ]] || die "scripts/seed.sh não encontrado."
chmod +x scripts/seed.sh
[[ -d "$PLUGIN_DIR" && -f "$PLUGIN_DIR/version.php" ]] || die "Plugin $PLUGIN_DIR ausente. O repositório deve incluir moodle/local/companycourse/."

if [[ ! -f .env ]]; then
  cp .env.example .env
  echo "    .env criado a partir de .env.example"
else
  echo "    .env já existe"
fi

# shellcheck disable=SC1091
set -a && source .env && set +a

if [[ -d moodle/.git ]]; then
  echo "==> Código IOMAD já presente em moodle/"
  [[ -f "$PLUGIN_DIR/version.php" ]] || die "IOMAD presente, mas o plugin $PLUGIN_DIR sumiu."
else
  echo "==> Clonando IOMAD (${IOMAD_BRANCH})..."
  tmp="$(mktemp -d)"
  plugin_backup="$(mktemp -d)"
  cleanup_clone() {
    rm -rf "$tmp" "$plugin_backup"
  }
  trap cleanup_clone EXIT

  cp -a "$PLUGIN_DIR" "$plugin_backup/companycourse"
  git clone --depth 1 --branch "$IOMAD_BRANCH" "$IOMAD_REPO" "$tmp/iomad"

  mkdir -p "$tmp/iomad/local"
  rm -rf "$tmp/iomad/local/companycourse"
  cp -a "$plugin_backup/companycourse" "$tmp/iomad/local/companycourse"

  rm -rf moodle
  mv "$tmp/iomad" moodle
  trap - EXIT
  cleanup_clone
  echo "    IOMAD clonado; plugin local_companycourse restaurado em $PLUGIN_DIR"
fi

if [[ ! -f moodle/config.php ]]; then
  cp docker/config.php moodle/config.php
  echo "    config.php copiado de docker/config.php"
else
  echo "    moodle/config.php já existe"
fi

mkdir -p moodledata
chmod -R 777 moodledata || true

echo "==> Subindo containers (docker compose up -d)..."
docker compose up -d

echo "==> Aguardando banco ficar saudável..."
db_ready=0
for _ in $(seq 1 60); do
  if docker compose exec -T db healthcheck.sh --connect --innodb_initialized >/dev/null 2>&1; then
    db_ready=1
    break
  fi
  sleep 2
done
[[ "$db_ready" -eq 1 ]] || die "Banco não ficou pronto a tempo."

echo "==> Ajustando permissões do moodledata..."
docker compose exec -T -u root web chown -R www-data:www-data /var/www/moodledata || true
docker compose exec -T -u root web chmod -R 777 /var/www/moodledata || true

TABLE_EXISTS="$(docker compose exec -T db mariadb -umoodle -pmoodle moodle -N -e "SHOW TABLES LIKE 'mdl_config';" 2>/dev/null | tr -d '\r' || true)"
if [[ "$TABLE_EXISTS" == "mdl_config" ]]; then
  echo "==> Moodle já instalado."
else
  echo "==> Instalando banco Moodle/IOMAD (CLI)..."
  docker compose exec -T web php admin/cli/install_database.php \
    --lang=pt_br \
    --adminuser=admin \
    --adminpass=Admin123! \
    --adminemail=admin@example.com \
    --fullname="Inicie Moodle IOMAD" \
    --agree-license
fi

echo "==> Aplicando upgrades / instalando plugins pendentes..."
docker compose exec -T web php admin/cli/upgrade.php --non-interactive

echo "==> Seed IOMAD (tool_iomadsite)..."
./scripts/seed.sh

echo ""
echo "Ambiente pronto."
echo "  URL:    ${MOODLE_WWWROOT:-http://localhost:${WEB_PORT:-8088}}"
echo "  Admin:  admin / Admin123!"
echo "  Banco:  moodle / moodle @ localhost:${DB_PORT:-3307}"
echo "  Plugin: local_companycourse"
echo "  Seed:   ./scripts/seed.sh (use --force para forçar)"
