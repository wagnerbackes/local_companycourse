# local_companycourse

Plugin local Moodle 5 / IOMAD 5 para replicar um curso base para uma ou mais Companies via Backup/Restore nativo.

| Item | Valor |
|------|--------|
| Autor | Wagner Dos Santos Backes |
| E-mail | wagnerbackes@gmail.com |
| Versão | `2026081807` / release `0.7.0` (`MATURITY_BETA`) |
| Documentação de entrega | [docs/entrega.md](docs/entrega.md) |
| Evidências (prints) | [docs/prints/](docs/prints/) |

## Instalação

Com o ambiente Docker no ar:

1. O código já está em `moodle/local/companycourse/`.
2. Acesse **Administração do site → Notificações** ou execute:

```bash
docker compose exec -T -u www-data web php admin/cli/upgrade.php --non-interactive
```

Confirme em **Plugins → Visão geral dos plugins** (`Screenshot_1`).

## Acesso e uso

Capability: `local/companycourse:manage`.

| Entrada | URL | Print |
|---------|-----|-------|
| Admin → Cursos | `/local/companycourse/index.php` | `Screenshot_4` |
| Formulário | mesma URL | `Screenshot_5`–`7` |
| Resumo do lote | após executar | `Screenshot_8` |
| Admin → Relatórios → Histórico | `/local/companycourse/history.php` | `Screenshot_9`–`10` |
| IOMAD → Courses | `?dashboard=1` | `Screenshot_11` |
| IOMAD → Reports | `history.php?dashboard=1` | `Screenshot_12` |

Guia completo com imagens e descrição de cada tela: [docs/entrega.md](docs/entrega.md).

## Testes PHPUnit

Pré-requisitos (uma vez no container): `composer install` em `/var/www/html`, `$CFG->phpunit_*` em `config.php`, e:

```bash
docker compose exec -T -u www-data web php admin/tool/phpunit/cli/init.php --disable-composer
docker compose exec -T -u root web php admin/tool/phpunit/cli/util.php --buildconfig
```

Executar a suíte do plugin:

```bash
docker compose exec -T -u www-data web vendor/bin/phpunit \
  --testsuite local_companycourse_testsuite \
  --configuration phpunit.xml
```

Resultado verificado: **37 testes, 150 assertions, OK** (`local_companycourse_testsuite`).

Cobertura: capability, schema XMLDB, privacy (metadata/export/delete da tabela de log), DTOs, serviços de domínio, backup/restore (inclui log de falha de restore), associação IOMAD (inclui rollback de curso órfão), formulário admin, `iomadmenu`, tratamento de erros, logs (inclui falha de insert do log) e histórico.

## Decisões técnicas

| Tema | Decisão |
|------|---------|
| Backup/Restore | Nativo (`backup_controller` / `restore_controller`), `MODE_GENERAL` síncrono; 1 `.mbz` + N restores; sem cópia SQL |
| Falha de restore | Motivo + detalhe técnico gravados no log e exibidos na UI |
| Associação IOMAD | Se falhar após restore, o curso restaurado é removido (sem órfão) |
| Usuários | Default OFF (`includeusers`); curso-modelo |
| Categoria IOMAD | Reutiliza `company.category`; cria só se inválida; **não** usar `company::get_category()` (é user_info) |
| Associação | `company::add_course($course, 0, true, false)` → `shared=0` |
| Duplicidade | Skip se log `success` para o mesmo `(courseid, companyid)` |
| Erros | Falha isolada por empresa; lote só aborta para curso inválido ou lista vazia |
| Logs | `local_companycourse_log` via `log_service` no orquestrador; falha de insert não esconde o resultado; índice `(courseid, companyid, status)` para checagem de duplicidade |
| Privacy | Provider completo da tabela `local_companycourse_log` (metadata, export e delete) |
| Maturity | `MATURITY_BETA` |

## Roteiro de teste manual

1. `./setup.sh` ou `docker compose up -d` → http://localhost:8088 login admin (`admin` / `Admin123!`).
2. Confirmar plugin instalado (Notificações / upgrade).
3. Abrir **Replicar cursos** (Plugins locais, Cursos ou IOMAD Courses).
4. Selecionar 1 curso base + 2 companies → executar (`includeusers` OFF).
5. Ver resumo de sucesso; abrir **Histórico** e conferir 2 linhas.
6. Repetir o mesmo par → status `skipped`.
7. Usuário sem capability → acesso negado.
8. (Opcional) PHPUnit: suíte `local_companycourse_testsuite` — 37 testes OK.

## Ambiente Docker

Instruções de subida do stack: [README.md](../../../README.md) (raiz do repositório).

## Estrutura

```
local/companycourse/
├── version.php
├── settings.php
├── index.php
├── history.php
├── docs/
│   ├── entrega.md
│   └── prints/Screenshot_1.png … Screenshot_12.png
├── lang/en/local_companycourse.php
├── lang/pt_br/local_companycourse.php
├── db/
│   ├── access.php
│   ├── install.xml
│   ├── upgrade.php
│   └── iomadmenu.php
├── templates/
│   ├── dashboard_page.mustache
│   ├── history_page.mustache
│   └── replication_summary.mustache
├── tests/
│   ├── helpers.php
│   ├── access_test.php
│   ├── install_test.php
│   ├── iomadmenu_test.php
│   ├── form/
│   ├── local/
│   ├── privacy/
│   └── service/
└── classes/
    ├── exception/replication_exception.php
    ├── form/replicate_form.php
    ├── local/
    ├── output/
    ├── privacy/provider.php
    └── service/
        ├── company_repository.php
        ├── course_validator.php
        ├── course_replication_service.php
        ├── iomad_association_service.php
        ├── log_service.php
        └── replication_manager.php
```

| Camada | Namespace | Papel |
|--------|-----------|--------|
| `classes/service/` | `local_companycourse\service\` | Domínio: validação, orquestração, backup/restore, IOMAD, logs |
| `classes/form/` | `local_companycourse\form\` | Formulário administrativo (`moodleform`) |
| `classes/output/` | `local_companycourse\output\` | Renderer + renderables Mustache |
| `classes/local/` | `local_companycourse\local\` | DTOs (status, resultado por company, resumo do lote) |
| `classes/exception/` | `local_companycourse\exception\` | Exceções de domínio |

Orquestrador: `replication_manager::replicate($courseid, $companyids, $userid, $includeusers = false)`.

Replicação: sync `MODE_GENERAL` + `display_if_slow` (1 backup + N restores). Usuários opcionais, default off.

Associação IOMAD: `company::add_course()` com `shared=0`, categoria da company e rollback se a associação falhar.

Tabela XMLDB: `local_companycourse_log`.
