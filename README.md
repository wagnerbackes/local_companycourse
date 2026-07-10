# Ambiente local — Moodle 5.0 + IOMAD 5.0

Stack Docker simples para desenvolvimento e avaliação do plugin `local_companycourse`.

## Requisitos

- Docker e Docker Compose (`docker compose`)
- Git
- Portas livres (padrão do projeto: `8088` web e `3307` banco — ajustáveis no `.env`)

O instalador **não** instala Docker, Compose nem Git no host; eles precisam estar disponíveis antes.

## O que sobe

| Serviço | Imagem | Função |
|---------|--------|--------|
| `web` | `moodlehq/moodle-php-apache:8.3` | Apache + PHP 8.3 com extensões do Moodle |
| `db` | `mariadb:11.4` | Banco (utf8mb4) |
| `cron` | mesma do web | Cron do Moodle a cada 60s |

Código: branch `IOMAD_500_STABLE` do [iomad/iomad](https://github.com/iomad/iomad) (Moodle 5.0 + IOMAD).

## Subir

```bash
git clone <repo> .
./setup.sh
```

Na primeira execução o `./setup.sh` clona o IOMAD, sobe os containers, instala o banco se necessário, aplica upgrades/plugins pendentes e executa o seed.

Se o código já estiver em `moodle/` e o banco já instalado:

```bash
docker compose up -d
```

Acesse: [http://localhost:8088](http://localhost:8088)

| Item | Valor |
|------|--------|
| Admin | `admin` / `Admin123!` |
| Banco | host `localhost:3307`, db/user/pass `moodle` |

## Dados de teste (seed)

O `./setup.sh` chama `./scripts/seed.sh` ao final. O seed usa a tool nativa do IOMAD (`admin/tool/iomadsite`), ativa o modo developer e gera um site de teste com 8 companies (Acme, Globex, Soylent, Initech, Umbrella, Hooli, Vehement, Massive).

**Aviso:** a tool é só para sites de teste; pode destruir/alterar o site. Com `--force`, o gerador adiciona mais cursos às companies existentes (não limpa o site).

Para reexecução manual:

```bash
./scripts/seed.sh

# opções
./scripts/seed.sh --size=S
./scripts/seed.sh --force   # executa de novo (adiciona mais cursos)
```

| Dado | Onde | Para quê |
|------|------|----------|
| Companies Acme…Massive | IOMAD (`mdl_company` + estrutura gerada pela tool) | Destinos multi-tenant / testes do plugin |
| Cursos e demais dados | Gerados por `maketestsite.php` conforme `--size` | Conteúdo de teste no site |

Se as 8 companies já existirem (ou houver conteúdo parcial), o seed pula sem `--force`. Detalhes: [docs/documentation/scripts/seed-dados-teste.md](docs/documentation/scripts/seed-dados-teste.md).

## Parar

```bash
docker compose down
```

## Recursos atendidos (Moodle 5)

- PHP **8.3** (mínimo Moodle 5.0: 8.2), 64-bit
- Extensões via imagem oficial Moodle HQ (inclui `sodium`, `intl`, `gd`, `zip`, `curl`, etc.)
- `max_input_vars >= 5000` e limites de upload/memória em `docker/php/moodle.ini`
- MariaDB **11.4** com `utf8mb4_unicode_ci`
- Apache no mesmo container PHP
- `moodledata` em volume local + cron para tarefas agendadas (necessário para backup/restore em fila)

## Estrutura

```
setup.sh                   # instalador canônico (raiz do repositório)
docker-compose.yml
.env.example
docker/php/moodle.ini
docker/config.php          # modelo copiado para moodle/config.php
scripts/seed.sh            # wrapper: modo developer + tool_iomadsite
scripts/seed_data.php      # legado; não usado pelo seed.sh
moodle/                    # código IOMAD (gerado pelo setup; plugin local/companycourse versionado)
moodledata/                # dados do Moodle (ignorado no git)
```

## Plugin `local_companycourse`

Replica um curso base para uma ou mais Companies IOMAD via Backup/Restore nativo do Moodle, com histórico e logs por operação.

| Item | Detalhe |
|------|---------|
| Código e uso | [moodle/local/companycourse/README.md](moodle/local/companycourse/README.md) |
| Entrega / decisões | [docs/tasks/12-documentacao-entregaveis.md](docs/tasks/12-documentacao-entregaveis.md) |
| Enunciado | [docs/Task.md](docs/Task.md) |
| Capability | `local/companycourse:manage` |
| Replicar | `/local/companycourse/index.php` |
| Histórico | `/local/companycourse/history.php` |
