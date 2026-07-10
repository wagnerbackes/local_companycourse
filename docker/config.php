<?php
unset($CFG);
global $CFG;
$CFG = new stdClass();

$CFG->dbtype    = 'mariadb';
$CFG->dblibrary = 'native';
$CFG->dbhost    = 'db';
$CFG->dbname    = 'moodle';
$CFG->dbuser    = 'moodle';
$CFG->dbpass    = 'moodle';
$CFG->prefix    = 'mdl_';
$CFG->dboptions = [
    'dbpersist' => false,
    'dbsocket'  => false,
    'dbport'    => '',
    'dbcollation' => 'utf8mb4_unicode_ci',
];

$CFG->wwwroot   = 'http://localhost:8088';
$CFG->dataroot  = '/var/www/moodledata';
$CFG->admin     = 'admin';
$CFG->directorypermissions = 02777;

$CFG->phpunit_prefix = 'phpu_';
$CFG->phpunit_dataroot = '/var/www/moodledata/phpunit';

require_once(__DIR__ . '/lib/setup.php');
