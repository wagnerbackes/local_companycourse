<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Strings de idioma do local_companycourse (português do Brasil).
 *
 * @package    local_companycourse
 * @copyright  2026 Inicie
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Replicação de cursos para empresas';
$string['privacy:metadata:local_companycourse_log'] = 'Armazena logs de auditoria das replicações de cursos para empresas IOMAD.';
$string['privacy:metadata:local_companycourse_log:userid'] = 'O usuário que executou a replicação.';
$string['privacy:metadata:local_companycourse_log:courseid'] = 'O id do curso base.';
$string['privacy:metadata:local_companycourse_log:newcourseid'] = 'O id do curso criado, quando houver sucesso.';
$string['privacy:metadata:local_companycourse_log:companyid'] = 'O id da empresa IOMAD de destino.';
$string['privacy:metadata:local_companycourse_log:status'] = 'O status do resultado da operação.';
$string['privacy:metadata:local_companycourse_log:message'] = 'A mensagem amigável de resultado ou erro.';
$string['privacy:metadata:local_companycourse_log:timecreated'] = 'A data/hora em que a operação foi registrada.';
$string['privacy:export:logs'] = 'Logs de replicação de cursos';
$string['companycourse:manage'] = 'Replicar cursos para empresas IOMAD';
$string['manage'] = 'Replicar cursos para empresas';
$string['manage_desc'] = 'Selecione um curso base e uma ou mais empresas IOMAD para replicar o curso.';
$string['manage_counts'] = 'Disponíveis para seleção: {$a->courses} curso(s), {$a->companies} empresa(s).';
$string['basecourse'] = 'Curso base';
$string['basecourse_nocompany'] = 'sem empresa';
$string['destinationcompanies'] = 'Empresas de destino';
$string['selectcompanies'] = 'Selecione uma ou mais empresas';
$string['includeusers'] = 'Incluir dados de usuários';
$string['includeusers_label'] = 'Também copiar matrículas e informações relacionadas de usuários do curso base';
$string['includeusers_help'] = 'Dados de usuários ficam desativados por padrão. Ativar copia matrículas, papéis, grupos e informações de conclusão para cada cópia da empresa. Isso pode duplicar registros de alunos entre tenants e costuma ser inadequado para um curso modelo. Prefira deixar desativado, salvo se realmente precisar dos dados de usuários.';
$string['includeusers_warning'] = 'Dados de usuários ficam desativados por padrão. Ativar copia matrículas, papéis, grupos e informações de conclusão para cada cópia da empresa. Isso pode duplicar registros de alunos entre tenants e costuma ser inadequado para um curso modelo. Prefira deixar desativado, salvo se realmente precisar dos dados de usuários.';
$string['executereplication'] = 'Replicar curso';
$string['backupprogress'] = 'Fazendo backup do curso base';
$string['restoreprogress'] = 'Restaurando cópia do curso';
$string['summaryheading'] = 'Resumo da replicação';
$string['summarycourse'] = 'Curso base: {$a}';
$string['summarycounts'] = 'Total: {$a->total} — sucesso: {$a->success}, erros: {$a->error}, ignorados: {$a->skipped}';
$string['summaryhaserrors'] = 'Uma ou mais empresas falharam. Veja a tabela abaixo e a página de histórico.';
$string['summaryhassuccess'] = 'Pelo menos uma empresa recebeu uma nova cópia do curso.';
$string['company'] = 'Empresa';
$string['status'] = 'Status';
$string['message'] = 'Mensagem';
$string['newcourseid'] = 'ID do novo curso';
$string['status_success'] = 'Sucesso';
$string['status_error'] = 'Erro';
$string['status_skipped'] = 'Ignorado';
$string['backtodashboard'] = 'Voltar ao painel IOMAD';
$string['error_course_required'] = 'Selecione um curso base.';
$string['history'] = 'Histórico de replicações';
$string['history_empty'] = 'Nenhuma operação de replicação registrada ainda.';
$string['history_count'] = 'Exibindo as {$a} entradas de log mais recentes.';
$string['history_user'] = 'Usuário';
$string['history_timecreated'] = 'Data/hora';
$string['history_missing_user'] = 'Usuário #{$a} (removido)';
$string['history_missing_course'] = 'Curso #{$a} (removido)';
$string['history_missing_company'] = 'Empresa #{$a} (removida)';
$string['error_course_not_found'] = 'Curso base não encontrado (id: {$a}).';
$string['error_company_not_found'] = 'Empresa não encontrada (id: {$a}).';
$string['error_company_suspended'] = 'A empresa "{$a}" está suspensa e não pode receber cursos.';
$string['error_no_companies'] = 'Selecione pelo menos uma empresa de destino.';
$string['error_association_failed'] = 'Falha ao associar o curso à empresa IOMAD: {$a}';
$string['error_move_course_failed'] = 'Não foi possível mover o curso restaurado {$a} para a categoria da empresa.';
$string['error_add_course_failed'] = 'company::add_course() retornou falha para o curso {$a}.';
$string['error_company_course_missing'] = 'O curso {$a} não ficou vinculado em company_course após add_course().';
$string['error_backup_failed'] = 'Falha no backup do curso: {$a}';
$string['error_restore_failed'] = 'Falha na restauração do curso: {$a}';
$string['error_backup_file_missing'] = 'Arquivo do pacote de backup ausente ou ilegível.';
$string['error_category_not_found'] = 'Categoria de curso de destino não encontrada (id: {$a}).';
$string['error_backup_no_user'] = 'Não há usuário autenticado para executar backup/restauração.';
$string['error_backup_no_file'] = 'O backup terminou sem gerar o arquivo de destino.';
$string['error_backup_copy_failed'] = 'Não foi possível copiar o pacote de backup para o caminho temporário.';
$string['error_restore_extract_failed'] = 'Não foi possível extrair o pacote de backup.';
$string['error_restore_no_course'] = 'A restauração terminou, mas o novo curso não foi encontrado.';
$string['restoredcoursefullname'] = 'Curso restaurado';
$string['error_unexpected'] = 'Erro inesperado: {$a}';
$string['error_log_failed'] = 'O resultado da operação foi mantido, mas a gravação do log de auditoria falhou: {$a}';
$string['error_technical_detail'] = 'Detalhe técnico: {$a}';
$string['result_success'] = 'Curso replicado com sucesso (id do novo curso: {$a}).';
$string['result_skipped_duplicate'] = 'Ignorado: já existe replicação bem-sucedida deste curso base para esta empresa.';
