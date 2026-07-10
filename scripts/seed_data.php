#!/usr/bin/env php
<?php
/**
 * Seed de dados para testes do plugin local_companycourse.
 *
 * Gera:
 * - Categoria Moodle para cursos base (templates)
 * - Cursos base com seções e atividades (page, forum, label)
 * - Companies IOMAD com categoria de curso, departamento e certificado
 * - Company suspensa (cenário de erro)
 *
 * Uso (via Docker):
 *   ./scripts/seed.sh
 *   ./scripts/seed.sh --companies=5 --courses=2 --force
 *
 * Ou:
 *   docker compose exec -T web php /var/www/scripts/seed_data.php
 */

define('CLI_SCRIPT', true);

$configcandidates = [
    __DIR__ . '/../moodle/config.php', // host / layout do repositório
    '/var/www/html/config.php',        // container Docker
];
$configpath = null;
foreach ($configcandidates as $candidate) {
    if (is_readable($candidate)) {
        $configpath = $candidate;
        break;
    }
}
if ($configpath === null) {
    fwrite(STDERR, "Não foi possível localizar config.php do Moodle.\n");
    exit(1);
}

require($configpath);
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/course/modlib.php');
require_once($CFG->dirroot . '/local/iomad/lib/company.php');
require_once($CFG->dirroot . '/blocks/iomad_company_admin/lib.php');

use block_iomad_company_admin\event\company_created;

list($options, $unrecognized) = cli_get_params(
    [
        'help' => false,
        'companies' => 4,
        'courses' => 2,
        'force' => false,
        'with-suspended' => true,
    ],
    [
        'h' => 'help',
        'f' => 'force',
    ]
);

if (!empty($options['help'])) {
    echo <<<EOF
Seed de dados para testes do local_companycourse.

Opções:
  --companies=N       Quantidade de companies ativas a criar (padrão: 4)
  --courses=N         Quantidade de cursos base a criar (padrão: 2)
  --force, -f         Recria shortnames seed_* mesmo se já existirem
  --with-suspended    Inclui 1 company suspensa (padrão: true)
  -h, --help          Ajuda

EOF;
    exit(0);
}

$companycount = max(1, (int) $options['companies']);
$coursecount = max(1, (int) $options['courses']);
$force = !empty($options['force']);
$withsuspended = array_key_exists('with-suspended', $options)
    ? filter_var($options['with-suspended'], FILTER_VALIDATE_BOOLEAN)
    : true;

raise_memory_limit(MEMORY_EXTRA);
\core\session\manager::set_user(get_admin());

cli_heading('Seed local_companycourse');

$summary = [
    'categoria_base' => null,
    'cursos_base' => [],
    'companies' => [],
    'company_suspensa' => null,
];

// ---------------------------------------------------------------------------
// 1) Categoria para cursos base (templates do site, fora das companies)
// ---------------------------------------------------------------------------
$basecatname = 'Cursos base (seed)';
$basecat = $DB->get_record('course_categories', ['name' => $basecatname, 'parent' => 0]);
if (!$basecat) {
    $basecat = (object) [
        'name' => $basecatname,
        'idnumber' => 'seed_base_cat',
        'description' => 'Categoria de cursos template para replicação IOMAD',
        'descriptionformat' => FORMAT_HTML,
        'parent' => 0,
        'sortorder' => 999,
        'visible' => 1,
    ];
    $basecat->id = $DB->insert_record('course_categories', $basecat);
    context_coursecat::instance($basecat->id);
    fix_course_sortorder();
    cli_writeln("Categoria base criada: id={$basecat->id}");
} else {
    cli_writeln("Categoria base já existe: id={$basecat->id}");
}
$summary['categoria_base'] = ['id' => (int) $basecat->id, 'name' => $basecat->name];

// ---------------------------------------------------------------------------
// 2) Cursos base no Moodle (conteúdo para backup/restore)
// ---------------------------------------------------------------------------
for ($i = 1; $i <= $coursecount; $i++) {
    $shortname = sprintf('seed_base_%02d', $i);
    $existing = $DB->get_record('course', ['shortname' => $shortname]);

    if ($existing && !$force) {
        cli_writeln("Curso base já existe: {$shortname} (id={$existing->id})");
        $summary['cursos_base'][] = [
            'id' => (int) $existing->id,
            'shortname' => $existing->shortname,
            'fullname' => $existing->fullname,
            'category' => (int) $existing->category,
        ];
        continue;
    }

    if ($existing && $force) {
        delete_course($existing, false);
        fix_course_sortorder();
        cli_writeln("Curso base removido para recriar: {$shortname}");
    }

    $coursedata = (object) [
        'fullname' => "Curso base seed {$i}",
        'shortname' => $shortname,
        'idnumber' => $shortname,
        'category' => $basecat->id,
        'summary' => "Curso template #{$i} para testes de replicação via Backup/Restore + IOMAD.",
        'summaryformat' => FORMAT_HTML,
        'format' => 'topics',
        'numsections' => 3,
        'startdate' => time(),
        'visible' => 1,
        'enablecompletion' => 1,
    ];

    $course = create_course($coursedata);
    course_create_sections_if_missing($course, [0, 1, 2]);
    seed_add_course_activities($course, $i);

    cli_writeln("Curso base criado: {$shortname} (id={$course->id})");
    $summary['cursos_base'][] = [
        'id' => (int) $course->id,
        'shortname' => $course->shortname,
        'fullname' => $course->fullname,
        'category' => (int) $course->category,
    ];
}

// ---------------------------------------------------------------------------
// 3) Companies IOMAD (categoria + departamento + certificado)
// ---------------------------------------------------------------------------
$templates = seed_company_templates($companycount, $withsuspended);

foreach ($templates as $tpl) {
    $existing = $DB->get_record('company', ['shortname' => $tpl['shortname']]);

    if ($existing && !$force) {
        cli_writeln("Company já existe: {$tpl['shortname']} (id={$existing->id})");
        $item = [
            'id' => (int) $existing->id,
            'name' => $existing->name,
            'shortname' => $existing->shortname,
            'category' => (int) $existing->category,
            'suspended' => (int) $existing->suspended,
        ];
        if (!empty($tpl['suspended'])) {
            $summary['company_suspensa'] = $item;
        } else {
            $summary['companies'][] = $item;
        }
        continue;
    }

    if ($existing && $force) {
        seed_purge_company($existing);
        cli_writeln("Company removida para recriar: {$tpl['shortname']}");
    }

    $company = seed_create_company($tpl);
    $item = [
        'id' => (int) $company->id,
        'name' => $company->name,
        'shortname' => $company->shortname,
        'category' => (int) $company->category,
        'suspended' => (int) $company->suspended,
    ];
    if (!empty($tpl['suspended'])) {
        $summary['company_suspensa'] = $item;
        cli_writeln("Company suspensa criada: {$company->shortname} (id={$company->id}, cat={$company->category})");
    } else {
        $summary['companies'][] = $item;
        cli_writeln("Company criada: {$company->shortname} (id={$company->id}, cat={$company->category})");
    }
}

// ---------------------------------------------------------------------------
// Resumo
// ---------------------------------------------------------------------------
cli_writeln('');
cli_heading('Resumo do seed');
cli_writeln('Categoria base: #' . $summary['categoria_base']['id'] . ' — ' . $summary['categoria_base']['name']);
cli_writeln('Cursos base:');
foreach ($summary['cursos_base'] as $c) {
    cli_writeln("  - #{$c['id']} {$c['shortname']} ({$c['fullname']})");
}
cli_writeln('Companies ativas:');
foreach ($summary['companies'] as $c) {
    cli_writeln("  - #{$c['id']} {$c['shortname']} — categoria #{$c['category']}");
}
if (!empty($summary['company_suspensa'])) {
    $s = $summary['company_suspensa'];
    cli_writeln("Company suspensa: #{$s['id']} {$s['shortname']} (para testes de erro)");
}

cli_writeln('');
cli_writeln('Dados prontos para testar listagem de cursos/empresas e replicação.');
cli_writeln('Os cursos base NÃO estão vinculados a companies (templates do site).');
exit(0);

/**
 * Atividades mínimas para o backup/restore ter conteúdo real.
 */
function seed_add_course_activities(stdClass $course, int $index): void {
    global $DB;

    $module = $DB->get_record('modules', ['name' => 'page'], '*', MUST_EXIST);
    $page = (object) [
        'modulename' => 'page',
        'module' => $module->id,
        'name' => "Página introdutória seed {$index}",
        'intro' => 'Conteúdo introdutório do curso base.',
        'introformat' => FORMAT_HTML,
        'content' => '<p>Este conteúdo deve ser preservado após Backup/Restore.</p>',
        'contentformat' => FORMAT_HTML,
        'section' => 1,
        'visible' => 1,
        'course' => $course->id,
        'display' => 5,
        'printheading' => 1,
        'printintro' => 0,
    ];
    add_moduleinfo($page, $course);

    $module = $DB->get_record('modules', ['name' => 'forum'], '*', MUST_EXIST);
    $forum = (object) [
        'modulename' => 'forum',
        'module' => $module->id,
        'name' => "Fórum de avisos seed {$index}",
        'intro' => 'Fórum do curso base.',
        'introformat' => FORMAT_HTML,
        'section' => 1,
        'visible' => 1,
        'course' => $course->id,
        'type' => 'general',
        'forcesubscribe' => 0,
    ];
    add_moduleinfo($forum, $course);

    $module = $DB->get_record('modules', ['name' => 'label'], '*', MUST_EXIST);
    $label = (object) [
        'modulename' => 'label',
        'module' => $module->id,
        'name' => "label_seed_{$index}",
        'intro' => "<p><strong>Label seed {$index}</strong> — marcador visual do template.</p>",
        'introformat' => FORMAT_HTML,
        'section' => 2,
        'visible' => 1,
        'course' => $course->id,
    ];
    add_moduleinfo($label, $course);
}

/**
 * Templates de companies (ativas + opcional suspensa).
 */
function seed_company_templates(int $count, bool $withsuspended): array {
    $names = [
        ['Empresa Alpha', 'SEED_ALPHA', 'São Paulo'],
        ['Empresa Beta', 'SEED_BETA', 'Curitiba'],
        ['Empresa Gama', 'SEED_GAMA', 'Belo Horizonte'],
        ['Empresa Delta', 'SEED_DELTA', 'Porto Alegre'],
        ['Empresa Épsilon', 'SEED_EPS', 'Recife'],
        ['Empresa Zeta', 'SEED_ZETA', 'Salvador'],
        ['Empresa Eta', 'SEED_ETA', 'Brasília'],
        ['Empresa Teta', 'SEED_TETA', 'Manaus'],
    ];

    $out = [];
    for ($i = 0; $i < $count; $i++) {
        if (isset($names[$i])) {
            [$name, $short, $city] = $names[$i];
        } else {
            $n = $i + 1;
            $name = "Empresa Seed {$n}";
            $short = sprintf('SEED_%02d', $n);
            $city = 'São Paulo';
        }
        $out[] = [
            'name' => $name,
            'shortname' => $short,
            'code' => $short,
            'city' => $city,
            'country' => 'BR',
            'theme' => 'iomadboost',
            'suspended' => 0,
        ];
    }

    if ($withsuspended) {
        $out[] = [
            'name' => 'Empresa Suspensa Seed',
            'shortname' => 'SEED_SUSP',
            'code' => 'SEED_SUSP',
            'city' => 'Rio de Janeiro',
            'country' => 'BR',
            'theme' => 'iomadboost',
            'suspended' => 1,
        ];
    }

    return $out;
}

/**
 * Cria company no padrão IOMAD (profile + insert + event + departments + category + certificate).
 */
function seed_create_company(array $tpl): stdClass {
    global $DB, $USER;

    $catdata = (object) [
        'name' => $tpl['shortname'],
        'sortorder' => $DB->count_records('user_info_category') + 1,
    ];
    $profileid = $DB->insert_record('user_info_category', $catdata);

    $data = (object) [
        'name' => $tpl['name'],
        'shortname' => $tpl['shortname'],
        'code' => $tpl['code'],
        'address' => 'Endereço seed',
        'city' => $tpl['city'],
        'region' => '',
        'postcode' => '00000-000',
        'country' => $tpl['country'],
        'maildisplay' => 2,
        'mailformat' => 1,
        'maildigest' => 0,
        'autosubscribe' => 1,
        'trackforums' => 0,
        'htmleditor' => 1,
        'screenreader' => 0,
        'timezone' => '99',
        'lang' => 'pt_br',
        'theme' => $tpl['theme'],
        'category' => 0,
        'profileid' => $profileid,
        'supervisorprofileid' => 0,
        'departmentprofileid' => 0,
        'suspended' => (int) $tpl['suspended'],
        'parentid' => 0,
        'ecommerce' => 0,
        'managernotify' => 0,
        'managerdigestday' => 0,
        'previousroletemplateid' => 0,
        'previousemailtemplateid' => 0,
        'maxusers' => 0,
        'companyterminated' => 0,
    ];

    $companyid = $DB->insert_record('company', $data);

    $event = company_created::create([
        'context' => context_system::instance(),
        'userid' => $USER->id,
        'objectid' => $companyid,
        'other' => ['companyid' => $companyid],
    ]);
    $event->trigger();

    company::initialise_departments($companyid);

    $coursecat = (object) [
        'name' => $tpl['name'],
        'idnumber' => 'seed_cat_' . strtolower($tpl['shortname']),
        'sortorder' => 999,
        'parent' => 0,
        'visible' => 1,
    ];
    $coursecat->id = $DB->insert_record('course_categories', $coursecat);
    context_coursecat::instance($coursecat->id);
    fix_course_sortorder();

    $companydetails = $DB->get_record('company', ['id' => $companyid], '*', MUST_EXIST);
    $companydetails->category = $coursecat->id;
    $DB->update_record('company', $companydetails);

    $DB->insert_record('companycertificate', [
        'companyid' => $companyid,
        'uselogo' => 1,
        'usesignature' => 1,
        'useborder' => 1,
        'usewatermark' => 1,
        'showgrade' => 1,
    ]);

    return $DB->get_record('company', ['id' => $companyid], '*', MUST_EXIST);
}

/**
 * Remove company seed e artefatos principais (apenas shortnames seed_*).
 */
function seed_purge_company(stdClass $company): void {
    global $DB;

    $DB->delete_records('company_course', ['companyid' => $company->id]);
    $DB->delete_records('company_created_courses', ['companyid' => $company->id]);
    $DB->delete_records('company_shared_courses', ['companyid' => $company->id]);
    $DB->delete_records('company_course_options', ['companyid' => $company->id]);
    $DB->delete_records('company_users', ['companyid' => $company->id]);
    $DB->delete_records('companycertificate', ['companyid' => $company->id]);
    $DB->delete_records('department', ['company' => $company->id]);

    if (!empty($company->category)) {
        if ($cat = $DB->get_record('course_categories', ['id' => $company->category])) {
            $DB->delete_records('course_categories', ['id' => $cat->id]);
        }
    }
    if (!empty($company->profileid)) {
        $DB->delete_records('user_info_category', ['id' => $company->profileid]);
    }

    $DB->delete_records('company', ['id' => $company->id]);
    fix_course_sortorder();
}
