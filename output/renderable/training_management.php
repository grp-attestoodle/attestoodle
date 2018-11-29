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
 * Page training management.
 *
 * Renderable class that is used to render the page that allow user to manage
 * a single training in Attestoodle.
 *
 * @package    tool_attestoodle
 * @copyright  2018 Pole de Ressource Numerique de l'Universite du Mans
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_attestoodle\output\renderable;

defined('MOODLE_INTERNAL') || die;

use tool_attestoodle\factories\categories_factory;
use tool_attestoodle\factories\trainings_factory;
use tool_attestoodle\forms\category_training_update_form;
/**
 * Display information of a single training in Attestoodle.
 *
 * @copyright  2018 Pole de Ressource Numerique de l'Universite du Mans
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class training_management implements \renderable {
    /** @var category_training_update_form The form used to manage trainings */
    private $form;

    /** @var integer The category ID that we want to manage */
    private $categoryid = null;

    /** @var category the actual category we want to manage */
    private $category = null;

    /**
     * Constructor method that instanciates the form.
     * @param integer $categoryid Id of the category associate with training (nav bar)
     */
    public function __construct($categoryid) {
        global $PAGE, $DB;

        $this->categoryid = $categoryid;
        $this->category = categories_factory::get_instance()->get_category($categoryid);

        // Handling form is useful only if the category exists.
        if (isset($this->category)) {
            $PAGE->set_heading(get_string('training_management_main_title', 'tool_attestoodle', $this->category->get_name()));

            $idtemplate = -1;
            $idtraining = -1;
            $grp1 = null;
            $grp2 = null;
            if ($this->category->is_training()) {
                $idtemplate = 0;
                $idtraining = $DB->get_field('attestoodle_training', 'id', ['categoryid' => $this->categoryid]);
                if ($DB->record_exists('attestoodle_train_template', ['trainingid' => $idtraining])) {
                    $associate = $DB->get_record('attestoodle_train_template', array('trainingid' => $idtraining));
                    $idtemplate = $associate->templateid;
                    $grp1 = $associate->grpcriteria1;
                    if (empty($grp1)) {
                        $grp1 = 'coursename';
                    }
                    $grp2 = $associate->grpcriteria2;
                    if (empty($grp2)) {
                        $grp2 = '';
                    }
                }
            }
            $this->form = new category_training_update_form(
                    new \moodle_url('/admin/tool/attestoodle/index.php',
                        array('page' => 'trainingmanagement', 'categoryid' => $this->categoryid)),
                        array('data' => $this->category, 'idtemplate' => $idtemplate,
                        'idtraining' => $idtraining), 'get' );
            if ($idtemplate > -1) {
                $this->form->set_data(array ('template' => $idtemplate, 'group1' => $grp1, 'group2' => $grp2));
            }
            $this->handle_form();
        } else {
            $PAGE->set_heading(get_string('training_management_main_title_no_category', 'tool_attestoodle'));
        }
    }

    /**
     * Main form handling method (calls other actual handling method).
     *
     * @return void Return void if no handling is needed (first render).
     */
    private function handle_form() {
        // Form processing and displaying is done here.
        if ($this->form->is_cancelled()) {
            $this->handle_form_cancelled();
        } else if ($this->form->is_submitted()) {
            $this->handle_form_submitted();
        } else {
            // First render, no process.
            return;
        }
    }

    /**
     * Handles the form cancellation (redirect to trainings list with a message).
     */
    private function handle_form_cancelled() {
        // Handle form cancel operation.
        $redirecturl = new \moodle_url('/admin/tool/attestoodle/index.php', ['page' => 'trainingslist']);
        $message = get_string('training_management_info_form_canceled', 'tool_attestoodle');
        redirect($redirecturl, $message, null, \core\output\notification::NOTIFY_INFO);
    }

    /**
     * Handles the form submission (calls other actual form submission handling
     * methods).
     */
    private function handle_form_submitted() {
        // Handle form submit operation.
        // Check the data validity.
        if (!$this->form->is_validated()) {
            $this->handle_form_not_validated();
        } else {
            // If data are valid, process persistance.
            // Try to retrieve the submitted data.
            $this->handle_form_has_submitted_data();
        }
    }

    /**
     * Handle form submission if its not valid (notify an error to the user).
     */
    private function handle_form_not_validated() {
        // If not valid, warn the user.
        \core\notification::error(get_string('training_management_warning_invalid_form', 'tool_attestoodle'));
    }

    /**
     * Handles form submission if its valid. Return a notification message
     * to the user to let him know how much categories have been updated and if
     * there is any error while save in DB.
     *
     * @todo create a new private method to notify the user
     *
     * @return void Return void if the user has not the rights to update in DB
     */
    private function handle_form_has_submitted_data() {
        global $DB;
        $context = \context_coursecat::instance($this->categoryid);
        if (has_capability('tool/attestoodle:managetraining', $context)) {
            $datafromform = $this->form->get_submitted_data();
            // Instanciate global variables to output to the user.
            $error = false;
            $updated = false;

            $value = $datafromform->checkbox_is_training;

            $oldistrainingvalue = $this->category->is_training();
            $boolvalue = boolval($value);

            if ($this->category->set_istraining($boolvalue)) {
                $updated = true;
                try {
                    // Try to persist training in DB.
                    $this->category->persist_training();
                } catch (\Exception $ex) {
                    // If record in DB failed, re-set the old value.
                    $this->category->set_istraining($oldistrainingvalue);
                    $error = true;
                }
                // Notify the user of the submission result.
                $this->notify_result($error, $updated, $boolvalue);
                if (!$error) {
                    $redirecturl = new \moodle_url('/admin/tool/attestoodle/index.php',
                        array ('page' => 'trainingmanagement', 'categoryid' => $this->categoryid));
                    redirect($redirecturl);
                    return;
                }
            } else {
                $training = trainings_factory::get_instance()->retrieve_training($this->category->get_id());
                if (!empty($training)) {
                    $training->changename($datafromform->name);
                    $nvxtemplate = $datafromform->template;
                    $idtraining = $DB->get_field('attestoodle_training', 'id', ['categoryid' => $this->categoryid]);
                    $record = $DB->get_record('attestoodle_train_template', ['trainingid' => $idtraining]);
                    $record->templateid = $nvxtemplate;
                    $record->grpcriteria1 = $datafromform->group1;
                    $record->grpcriteria2 = $datafromform->group2;
                    if (empty($datafromform->group2)) {
                        $record->grpcriteria2 = null;
                    }
                    \core\notification::info(get_string('updatetraitemplate', 'tool_attestoodle'));
                    $DB->update_record('attestoodle_train_template', $record);
                }
            }
        }
    }

    /**
     * Method that throws a notification to user to let him know the result of
     * the form submission.
     *
     * @param boolean $error If there was an error
     * @param boolean $updated If the training has been updated
     * @param boolean $boolvalue True if the training has been added, false if
     * it has been removed
     */
    private function notify_result($error, $updated, $boolvalue) {
        $message = "";
        if (!$error) {
            if ($updated) {
                if ($boolvalue) {
                    $message .= get_string('training_management_submit_added', 'tool_attestoodle');
                } else {
                    $message .= get_string('training_management_submit_removed', 'tool_attestoodle');
                }
                \core\notification::success($message);
            } else {
                $message .= get_string('training_management_submit_unchanged', 'tool_attestoodle');
                \core\notification::info($message);
            }
        } else {
            $message .= get_string('training_management_submit_error', 'tool_attestoodle');
            \core\notification::warning($message);
        }
    }

    /**
     * Computes the content header.
     *
     * @return string The computed HTML string of the page header
     */
    public function get_header() {
        $output = "";

        $retcateg = optional_param('call', null, PARAM_ALPHA);

        if (isset($this->category)) {
            $output .= \html_writer::start_div('clearfix');
            // Link back to the category.
            if (isset($retcateg)) {
                $output .= \html_writer::link(
                    new \moodle_url("/course/index.php", array("categoryid" => $this->category->get_id())),
                    get_string('training_management_backto_category_link', 'tool_attestoodle'),
                    array('class' => 'btn-create pull-right'));
            } else {
                $output .= \html_writer::link(
                    new \moodle_url("/admin/tool/attestoodle/index.php", array()),
                    get_string('training_list_link', 'tool_attestoodle'),
                    array('class' => 'btn-create pull-right'));
            }

            $output .= \html_writer::end_div();
        }

        return $output;
    }

    /**
     * Render the form.
     *
     * @return string HTML string corresponding to the form
     */
    public function get_content() {
        $output = "";
        if (!isset($this->categoryid)) {
            $output .= get_string('training_management_no_category_id', 'tool_attestoodle');
        } else if (!isset($this->category)) {
            $output .= get_string('training_management_unknow_category_id', 'tool_attestoodle');
        } else {
            $output .= $this->form->render();

            if ($this->category->is_training()) {
                $output .= \html_writer::start_div('clearfix training-management-content');

                // Link to the learners list of the training.
                $parameters = array(
                        'page' => 'learners',
                        'categoryid' => $this->category->get_id()
                );
                $url = new \moodle_url('/admin/tool/attestoodle/index.php', $parameters);
                $label = get_string('training_management_training_details_link', 'tool_attestoodle');
                $attributes = array('class' => 'attestoodle-button');
                $output .= \html_writer::link($url, $label, $attributes);

                $output .= "<br />";

                // Link to the milestones management of the training.
                $parametersmilestones = array(
                        'page' => 'managemilestones',
                        'categoryid' => $this->category->get_id()
                );
                $urlmilestones = new \moodle_url('/admin/tool/attestoodle/index.php', $parametersmilestones);
                $labelmilestones = get_string('training_management_manage_training_link', 'tool_attestoodle');
                $attributesmilestones = array('class' => 'attestoodle-button');
                $output .= \html_writer::link($urlmilestones, $labelmilestones, $attributesmilestones);

                $output .= \html_writer::end_div();
            }
        }
        return $output;
    }
}
