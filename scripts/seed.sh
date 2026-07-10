#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

FORCE=0
SIZE="S"
BYPASS_CHECK=0

usage() {
  cat <<'EOF'
Gera dados de teste via tool nativa do IOMAD (admin/tool/iomadsite).

Uso:
  ./scripts/seed.sh
  ./scripts/seed.sh --force
  ./scripts/seed.sh --size=S

Opções:
  --force, -f       Executa mesmo se o conteúdo IOMAD já existir
                    (o gerador adiciona mais cursos às companies existentes)
  --size=XS|S|M|L|XL|XXL
                    Tamanho passado ao CLI do IOMAD (padrão: S)
  --bypasscheck     Repassa --bypasscheck ao CLI (não recomendado)
  -h, --help        Ajuda

Fluxo:
  1. Ativa modo developer (debug=DEVELOPER, debugdisplay=1)
  2. Verifica se as companies do gerador já existem
  3. Executa php admin/tool/iomadsite/cli/maketestsite.php
EOF
}

while [[ $# -gt 0 ]]; do
  case "$1" in
    -h|--help)
      usage
      exit 0
      ;;
    -f|--force)
      FORCE=1
      shift
      ;;
    --size=*)
      SIZE="${1#*=}"
      shift
      ;;
    --size)
      SIZE="${2:?Informe o valor de --size}"
      shift 2
      ;;
    --bypasscheck)
      BYPASS_CHECK=1
      shift
      ;;
    *)
      echo "Opção desconhecida: $1" >&2
      usage >&2
      exit 1
      ;;
  esac
done

case "${SIZE^^}" in
  XS|S|M|L|XL|XXL) ;;
  *)
    echo "Tamanho inválido: $SIZE (use XS, S, M, L, XL ou XXL)" >&2
    exit 1
    ;;
esac

if ! docker compose ps --status running --services 2>/dev/null | grep -qx web; then
  echo "Container web não está rodando. Execute antes: docker compose up -d"
  exit 1
fi

if ! docker compose ps --status running --services 2>/dev/null | grep -qx db; then
  echo "Container db não está rodando. Execute antes: docker compose up -d"
  exit 1
fi

IOMAD_SHORTNAMES="Acme,Globex,Soylent,Initech,Umbrella,Hooli,Vehement,Massive"
EXPECTED_COUNT=8

echo "==> Verificando se o conteúdo IOMAD já foi gerado..."
EXISTING_COUNT="$(docker compose exec -T db mariadb -umoodle -pmoodle moodle -N -e \
  "SELECT COUNT(*) FROM mdl_company WHERE shortname IN ('Acme','Globex','Soylent','Initech','Umbrella','Hooli','Vehement','Massive');" \
  2>/dev/null | tr -d '\r' || echo 0)"

if [[ "${EXISTING_COUNT:-0}" -ge "$EXPECTED_COUNT" && "$FORCE" -eq 0 ]]; then
  echo "==> Conteúdo IOMAD já presente ($EXISTING_COUNT/$EXPECTED_COUNT companies: $IOMAD_SHORTNAMES)."
  echo "    Pulei a geração. Use ./scripts/seed.sh --force para executar de novo"
  echo "    (atenção: o gerador nativo adiciona mais cursos às companies existentes)."
  exit 0
fi

if [[ "${EXISTING_COUNT:-0}" -gt 0 && "${EXISTING_COUNT:-0}" -lt "$EXPECTED_COUNT" && "$FORCE" -eq 0 ]]; then
  echo "==> Conteúdo IOMAD parcial detectado ($EXISTING_COUNT/$EXPECTED_COUNT companies)."
  echo "    Pulei para não duplicar cursos. Use ./scripts/seed.sh --force para continuar."
  exit 0
fi

if [[ "$FORCE" -eq 1 && "${EXISTING_COUNT:-0}" -gt 0 ]]; then
  echo "==> --force: conteúdo parcial/completo já existe ($EXISTING_COUNT companies)."
  echo "    O gerador IOMAD vai criar companies faltantes e adicionar mais cursos às existentes."
fi

echo "==> Ativando modo developer no Moodle..."
docker compose exec -T -u www-data web php admin/cli/cfg.php --name=debug --set=32767
docker compose exec -T -u www-data web php admin/cli/cfg.php --name=debugdisplay --set=1

DEBUG_VALUE="$(docker compose exec -T -u www-data web php admin/cli/cfg.php --name=debug --no-eol 2>/dev/null | tr -d '\r' || true)"
if [[ "${DEBUG_VALUE:-0}" -lt 32767 ]]; then
  echo "Falha ao ativar debug developer (valor atual: ${DEBUG_VALUE:-vazio})." >&2
  exit 1
fi
echo "    debug=$DEBUG_VALUE, debugdisplay=1"

CLI_ARGS=(admin/tool/iomadsite/cli/maketestsite.php "--size=${SIZE^^}")
if [[ "$BYPASS_CHECK" -eq 1 ]]; then
  CLI_ARGS+=(--bypasscheck)
fi

echo "==> Gerando site de teste IOMAD (tool_iomadsite, size=${SIZE^^})..."
echo "    Aviso: ferramenta de desenvolvimento; não usar em produção."
docker compose exec -T -u www-data web php "${CLI_ARGS[@]}"

echo ""
echo "==> Seed IOMAD concluído."
echo "    Companies esperadas: $IOMAD_SHORTNAMES"
echo "    Modo developer permanece ativo (debug=32767)."
