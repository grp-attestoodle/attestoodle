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
 * File that handles all the requested page from the user.
 *
 * @package    tool_attestoodle
 * @copyright  2018 Pole de Ressource Numerique de l'Universite du Mans
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Main configuration importation (instanciate the $CFG global variable).
require_once(dirname(__FILE__) . '/../../../config.php');

// Libraries imports.
require_once($CFG->libdir.'/pdflib.php');
require_once(dirname(__FILE__) .'/lib.php');

/*
 * Imports of class files.
 */
$toolpath = dirname(__FILE__);

require_once($toolpath . "/classes/factories/learners_factory.php");
require_once($toolpath . "/classes/output/renderable/trainings_list.php");
require_once($toolpath . "/classes/output/renderable/training_management.php");
require_once($toolpath . "/classes/output/renderable/training_learners_list.php");
require_once($toolpath . "/classes/output/renderable/learner_details.php");
require_once($toolpath . "/classes/output/renderable/training_milestones.php");
require_once($toolpath . "/classes/certificate.php");

use tool_attestoodle\factories\trainings_factory;
use tool_attestoodle\output\renderable;

$page = optional_param('page', '', PARAM_ALPHA);
$action = optional_param('action', '', PARAM_ALPHA);

$context = context_system::instance();
$PAGE->set_context($context);

require_login();

$renderer = $PAGE->get_renderer('tool_attestoodle');
// Always create trainings.
trainings_factory::get_instance()->create_trainings();
$PAGE->navbar->ignore_active();
$navlevel1 = get_string('navlevel1', 'tool_attestoodle');
$PAGE->navbar->add($navlevel1, new moodle_url('/admin/tool/attestoodle/index.php',
                                                array()));
switch($page) {
    case 'trainingmanagement':
        $categoryid = optional_param('categoryid', null, PARAM_INT);

        $urlact = new moodle_url($toolpath . '/index.php', ['page' => $page, 'categoryid' => $categoryid]);
        $PAGE->set_url($urlact);

        $PAGE->set_title(get_string('training_management_page_title', 'tool_attestoodle'));
        $navlevel2 = get_string('navlevel2', 'tool_attestoodle');
        $PAGE->navbar->add($navlevel2, new moodle_url('/admin/tool/attestoodle/index.php',
                                                array('page' => $page, 'categoryid' => $categoryid)));

        if (!empty($categoryid)) {
            $context = context_coursecat::instance($categoryid);
            $PAGE->set_context($context);
        }
        require_capability('tool/attestoodle:managetraining', $context);
        $renderable = new renderable\training_management($categoryid);

        break;
    case 'managemilestones':
        $categoryid = required_param('categoryid', PARAM_INT);
        $PAGE->set_url(new moodle_url($toolpath . '/index.php',
                ['page' => $page, 'categoryid' => $categoryid]));
        $PAGE->set_title(get_string('training_milestones_page_title', 'tool_attestoodle'));
        $context = context_coursecat::instance($categoryid);
        $PAGE->set_context($context);
        require_capability('tool/attestoodle:managemilestones', $context);

        $navlevel2 = get_string('navlevel2', 'tool_attestoodle');
        $PAGE->navbar->add($navlevel2, new moodle_url('/admin/tool/attestoodle/index.php',
                                                array('page' => 'trainingmanagement', 'categoryid' => $categoryid)));
        $navlevel3b = get_string('navlevel3b', 'tool_attestoodle');
        $PAGE->navbar->add($navlevel3b, new moodle_url('/admin/tool/attestoodle/index.php',
                                                array('page' => $page, 'categoryid' => $categoryid)));
        $renderable = new renderable\training_milestones($categoryid);
        $PAGE->set_heading($renderable->get_heading());

        break;
    case 'learners':
        // Required params.
        $categoryid = required_param('categoryid', PARAM_INT);
        // Optional params.
        $begindate = optional_param('begindate', null, PARAM_ALPHANUMEXT);
        $enddate = optional_param('enddate', null, PARAM_ALPHANUMEXT);

        $start = optional_param_array('input_begin_date', null, PARAM_INT);
        $end = optional_param_array('input_end_date', null, PARAM_INT);

        if (isset($start)) {
            $begindate = "" . $start['year'] . "-" . $start['month'] . "-" . $start['day'];
        }
        if (isset($end)) {
            $enddate = "" . $end['year'] . "-" . $end['month'] . "-" . $end['day'];
        }

        $PAGE->set_url(new moodle_url($toolpath . '/index.php',
                array(
                        'page' => $page,
                        'action' => $action,
                        'categoryid' => $categoryid,
                        'begindate' => $begindate,
                        'enddate' => $enddate
                )
        ));
        $PAGE->set_title(get_string('training_learners_list_page_title', 'tool_attestoodle'));
        $context = context_coursecat::instance($categoryid);
        $PAGE->set_context($context);
        require_capability('tool/attestoodle:displaylearnerslist', $context);

        $navlevel2 = get_string('navlevel2', 'tool_attestoodle');
        $PAGE->navbar->add($navlevel2, new moodle_url('/admin/tool/attestoodle/index.php',
                                                array('page' => 'trainingmanagement', 'categoryid' => $categoryid)));
        $navlevel3a = get_string('navlevel3a', 'tool_attestoodle');
        $PAGE->navbar->add($navlevel3a, new moodle_url('/admin/tool/attestoodle/index.php',
                                                array('page' => $page, 'categoryid' => $categoryid)));
        // Instanciate the training in the renderable.
        $training = null;
        $trainingexist = trainings_factory::get_instance()->has_training($categoryid);
        if ($trainingexist) {
            $training = trainings_factory::get_instance()->retrieve_training($categoryid);
            $PAGE->set_heading(get_string('training_learners_list_main_title', 'tool_attestoodle', $training->get_name()));
        } else {
            $PAGE->set_heading(get_string('training_learners_list_main_title_error', 'tool_attestoodle'));
        }

        $renderable = new renderable\training_learners_list($training, $begindate, $enddate);
        if (count($renderable->training->get_learners()) == 0) {
            $redirecturl = new \moodle_url(
                '/admin/tool/attestoodle/index.php',
                array('page' => 'trainingmanagement', 'categoryid' => $categoryid));
            $message = get_string('infonostudent', 'tool_attestoodle');
            redirect($redirecturl, $message, null, \core\output\notification::NOTIFY_INFO);
            return;
        }

        if ($action == 'downloadzip') {
            $renderable->send_certificates_zipped();
        } else if ($action == 'generatecertificates') {
            $renderable->generate_certificates();
        }

        break;
    case 'learnerdetails':
        // Required param.
        $learnerid = required_param('learner', PARAM_INT);
        $categorylnk = required_param('categorylnk', PARAM_INT);

        // Optional params.
        $begindate = optional_param('begindate', null, PARAM_ALPHANUMEXT);
        $enddate = optional_param('enddate', null, PARAM_ALPHANUMEXT);
        $start = optional_param('input_begin_date', null, PARAM_INT);
        $end = optional_param('input_end_date', null, PARAM_INT);

        if (isset($start)) {
            $begindate = "" . $start['year'] . "-" . $start['month'] . "-" . $start['day'];
        }
        if (isset($end)) {
            $enddate = "" . $end['year'] . "-" . $end['month'] . "-" . $end['day'];
        }

        $PAGE->set_url(new moodle_url($toolpath . '/index.php',
                array(
                        'page' => $page,
                        'action' => $action,
                        'learner' => $learnerid,
                        'begindate' => $begindate,
                        'enddate' => $enddate,
                        'categorylnk' => $categorylnk
                )
        ));

        // Set page title.
        $PAGE->set_title(get_string('learner_details_page_title', 'tool_attestoodle'));
        $navlevel2 = get_string('navlevel2', 'tool_attestoodle');
        $PAGE->navbar->add($navlevel2, new moodle_url('/admin/tool/attestoodle/index.php',
                                                array('page' => 'trainingmanagement', 'categoryid' => $categorylnk)));
        $navlevel3a = get_string('navlevel3a', 'tool_attestoodle');
        $PAGE->navbar->add($navlevel3a, new moodle_url('/admin/tool/attestoodle/index.php',
                                                array('page' => 'learners', 'categoryid' => $categorylnk)));
        $navlevel4a = get_string('navlevel4a', 'tool_attestoodle');
        $PAGE->navbar->add($navlevel4a, new moodle_url('/admin/tool/attestoodle/index.php',
                                                array('page' => $page, 'categorylnk' => $categorylnk,
                                                'learner' => $learnerid, 'begindate' => $begindate,
                                                'enddate' => $enddate)));
        // Checking capabilities.
        if (!empty($categorylnk)) {
            $context = context_coursecat::instance($categorylnk);
            $PAGE->set_context($context);
        }
        require_capability('tool/attestoodle:learnerdetails', $context);

        $renderable = new renderable\learner_details($learnerid, $begindate, $enddate, $categorylnk);
        if ($action == 'generatecertificate') {
            $categoryid = required_param('categoryid', PARAM_INT);
            $renderable->generate_certificate_file($categoryid);
        }
        $PAGE->set_heading($renderable->get_heading());

        break;
    case 'trainingslist':
    default:
        $PAGE->set_url(new moodle_url($toolpath . '/index.php'));
        $PAGE->set_title(get_string('trainings_list_page_title', 'tool_attestoodle'));
        $PAGE->set_heading(get_string('trainings_list_main_title', 'tool_attestoodle'));

        require_capability('tool/attestoodle:displaytrainings', $context);

        $renderable = new renderable\trainings_list(trainings_factory::get_instance()->get_trainings());

        break;
}

echo $OUTPUT->header();

// ... to be callable by the output->render method bellow.
// Note: the method automagically call the method "render_[renderable_class]"...
// ...defined in the renderer object (here $output).
echo $renderer->render($renderable);

echo $OUTPUT->footer();
