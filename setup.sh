#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$ROOT"

IOMAD_BRANCH="${IOMAD_BRANCH:-IOMAD_500_STABLE}"
IOMAD_REPO="${IOMAD_REPO:-https://github.com/iomad/iomad.git}"
PLUGIN_DIR="moodle/local/companycourse"
WEB_USER="www-data"
WEB_GROUP="www-data"
MOODLEDATA_HOST="${ROOT}/moodledata"
MOODLEDATA_CONTAINER="/var/www/moodledata"

die() {
  echo "Erro: $*" >&2
  exit 1
}

require_cmd() {
  command -v "$1" >/dev/null 2>&1 || die "'$1' não encontrado. Instale-o antes de continuar."
}

fix_moodledata_permissions() {
  echo "==> Ajustando permissões de pastas..."

  mkdir -p "$MOODLEDATA_HOST"
  chmod 777 "$MOODLEDATA_HOST" 2>/dev/null || true

  if ! docker compose ps --status running --services 2>/dev/null | grep -qx web; then
    echo "    Container web ainda não está no ar; permissões finais serão aplicadas depois."
    return 0
  fi

  docker compose exec -T -u root web bash -c "
    set -e
    mkdir -p '${MOODLEDATA_CONTAINER}'/{cache,localcache,temp,filedir,sessions,trashdir,lang,muc}
    chown -R ${WEB_USER}:${WEB_GROUP} '${MOODLEDATA_CONTAINER}'
    find '${MOODLEDATA_CONTAINER}' -type d -exec chmod 2777 {} +
    find '${MOODLEDATA_CONTAINER}' -type f -exec chmod 0666 {} +
    if [[ ! -f '${MOODLEDATA_CONTAINER}/.htaccess' ]]; then
      printf '%s\n' 'order deny,allow' 'deny from all' 'AllowOverride None' > '${MOODLEDATA_CONTAINER}/.htaccess'
      chown ${WEB_USER}:${WEB_GROUP} '${MOODLEDATA_CONTAINER}/.htaccess'
      chmod 0666 '${MOODLEDATA_CONTAINER}/.htaccess'
    fi
  "

  docker compose exec -T -u "${WEB_USER}" web bash -c "
    set -e
    test -w '${MOODLEDATA_CONTAINER}'
    touch '${MOODLEDATA_CONTAINER}/.write_test' && rm -f '${MOODLEDATA_CONTAINER}/.write_test'
  " || die "moodledata não está gravável por ${WEB_USER}."

  echo "    moodledata → ${WEB_USER}:${WEB_GROUP} (dirs 2777, files 0666)"
}

run_moodle_cli() {
  docker compose exec -T -u "${WEB_USER}" web php "$@"
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
chmod +x setup.sh scripts/seed.sh
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

fix_moodledata_permissions

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

fix_moodledata_permissions

TABLE_EXISTS="$(docker compose exec -T db mariadb -umoodle -pmoodle moodle -N -e "SHOW TABLES LIKE 'mdl_config';" 2>/dev/null | tr -d '\r' || true)"
if [[ "$TABLE_EXISTS" == "mdl_config" ]]; then
  echo "==> Moodle já instalado."
else
  echo "==> Instalando banco Moodle/IOMAD (CLI)..."
  run_moodle_cli admin/cli/install_database.php \
    --lang=pt_br \
    --adminuser=admin \
    --adminpass=Admin123! \
    --adminemail=admin@example.com \
    --fullname="Inicie Moodle IOMAD" \
    --agree-license
fi

echo "==> Aplicando upgrades / instalando plugins pendentes..."
run_moodle_cli admin/cli/upgrade.php --non-interactive

fix_moodledata_permissions

echo "==> Seed IOMAD (tool_iomadsite)..."
./scripts/seed.sh

fix_moodledata_permissions

echo ""
echo "Ambiente pronto."
echo "  URL:    ${MOODLE_WWWROOT:-http://localhost:${WEB_PORT:-8088}}"
echo "  Admin:  admin / Admin123!"
echo "  Banco:  moodle / moodle @ localhost:${DB_PORT:-3307}"
echo "  Plugin: local_companycourse"
echo "  Seed:   ./scripts/seed.sh (use --force para forçar)"
