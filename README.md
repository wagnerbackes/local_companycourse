# local_companycourse — Moodle 5.0 + IOMAD 5.0

Ambiente Docker e plugin local para replicar um curso base para Companies IOMAD via Backup/Restore nativo.

| Item | Valor |
|------|--------|
| Autor | Wagner Dos Santos Backes |
| E-mail | wagnerbackes@gmail.com |
| Plugin | `local_companycourse` `2026070974` / `0.6.2` (`MATURITY_BETA`) |
| Repositório | https://github.com/wagnerbackes/local_companycourse |

## Requisitos

- Docker e Docker Compose (`docker compose`)
- Git
- Portas livres (padrão: `8088` web e `3307` banco — ajustáveis no `.env`)

O instalador **não** instala Docker, Compose nem Git no host.

## Subir

```bash
git clone https://github.com/wagnerbackes/local_companycourse.git .
./setup.sh
```

Na primeira execução o `./setup.sh`:

1. Cria `.env` a partir de `.env.example` (se necessário)
2. Clona o IOMAD (`IOMAD_500_STABLE`) em `moodle/`, preservando o plugin
3. Sobe os containers (`web`, `db`, `cron`)
4. Ajusta permissões de `moodledata` para `www-data`
5. Instala o banco / aplica upgrades e plugins
6. Executa o seed IOMAD

Se o código e o banco já estiverem prontos:

```bash
docker compose up -d
```

Acesse: [http://localhost:8088](http://localhost:8088)

| Item | Valor |
|------|--------|
| Admin | `admin` / `Admin123!` |
| Banco | host `localhost:3307`, db/user/pass `moodle` |

## O que sobe

| Serviço | Imagem | Função |
|---------|--------|--------|
| `web` | `moodlehq/moodle-php-apache:8.3` | Apache + PHP 8.3 |
| `db` | `mariadb:11.4` | Banco utf8mb4 |
| `cron` | mesma do web (`user: www-data`) | Cron do Moodle a cada 60s |

Código: branch `IOMAD_500_STABLE` do [iomad/iomad](https://github.com/iomad/iomad).

## Dados de teste (seed)

O `./setup.sh` chama `./scripts/seed.sh` ao final. O seed usa a tool nativa do IOMAD (`admin/tool/iomadsite`), ativa o modo developer e gera um site de teste com 8 companies (Acme, Globex, Soylent, Initech, Umbrella, Hooli, Vehement, Massive).

**Aviso:** a tool é só para sites de teste. Com `--force`, o gerador adiciona mais cursos às companies existentes.

```bash
./scripts/seed.sh
./scripts/seed.sh --size=S
./scripts/seed.sh --force
```

Se as 8 companies já existirem (ou houver conteúdo parcial), o seed pula sem `--force`.

## Parar

```bash
docker compose down
```

## Recursos atendidos (Moodle 5)

- PHP **8.3** (mínimo Moodle 5.0: 8.2)
- Extensões via imagem oficial Moodle HQ (`sodium`, `intl`, `gd`, `zip`, `curl`, etc.)
- `max_input_vars >= 5000` e limites em `docker/php/moodle.ini`
- MariaDB **11.4** com `utf8mb4_unicode_ci`
- `moodledata` com dono `www-data` (dirs `2777`, files `0666` — ajustado pelo `setup.sh`)
- Cron como `www-data` (evita pastas root em `moodledata`)

## Estrutura

```
setup.sh                              # instalador canônico
docker-compose.yml
.env.example
docker/php/moodle.ini
docker/config.php                     # modelo → moodle/config.php
scripts/seed.sh                       # seed IOMAD (tool_iomadsite)
moodle/local/companycourse/           # plugin versionado
moodle/local/companycourse/docs/      # documentação de entrega + prints
moodle/                               # IOMAD (clonado pelo setup; resto ignorado no git)
moodledata/                           # dados do Moodle (ignorado no git)
```

## Plugin `local_companycourse`

Replica um curso base para uma ou mais Companies IOMAD via Backup/Restore nativo, com histórico e logs.

| Item | Detalhe |
|------|---------|
| Uso e decisões | [moodle/local/companycourse/README.md](moodle/local/companycourse/README.md) |
| Documentação de entrega | [moodle/local/companycourse/docs/entrega.md](moodle/local/companycourse/docs/entrega.md) |
| Evidências (prints) | [moodle/local/companycourse/docs/prints/](moodle/local/companycourse/docs/prints/) |
| Capability | `local/companycourse:manage` |
| Replicar | `/local/companycourse/index.php` |
| Histórico | `/local/companycourse/history.php` |
