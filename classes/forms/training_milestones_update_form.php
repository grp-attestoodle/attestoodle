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
 * This is form for the modification of milestones values.
 *
 * @package    tool_attestoodle
 * @copyright  2018 Pole de Ressource Numerique de l'Université du Mans
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_attestoodle\forms;
use tool_attestoodle\utils\db_accessor;

defined('MOODLE_INTERNAL') || die;

// Class \moodleform is defined in formslib.php.
require_once("$CFG->libdir/formslib.php");
/**
 * This is the class that handles the modification of milestones values through moodleform.
 *
 * @copyright  2018 Pole de Ressource Numerique de l'Université du Mans
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class training_milestones_update_form extends \moodleform {
    /**
     * Method automagically called when the form is instanciated. It defines
     * all the elements (inputs, titles, buttons, ...) in the form.
     */
    public function definition() {
        global $CFG;
        $inputnameprefix = $this->_customdata['input_name_prefix'];
        $elements = $this->get_elements($this->_customdata['data'], $inputnameprefix);

        $mform = $this->_form;
        $this->add_filter();
        $suffix = get_string("training_milestones_form_input_suffix", "tool_attestoodle");
        foreach ($elements as $course) {
            $totact = count($course->activities);
            $lstactivities = $this->filter($course->activities);
            $totfilter = count($lstactivities);
            $mform->addElement('header', $course->id,
                    "{$course->name} : {$course->totalmilestones} ({$totfilter} / {$totact})");
            $mform->setExpanded($course->id, false);
            // For each activity in this course we add a form input element.
            foreach ($lstactivities as $activity) {
                $groupname = "group_" . $activity->name;
                // The group contains the input, the label and a fixed span (required to have more complex form lines).
                $group = array();
                $group[] =& $mform->createElement("text", $activity->name, null, array("size" => 5)); // Max 5 char.
                $mform->setType($activity->name, PARAM_ALPHANUM); // Parsing the value in INT after submit.
                $mform->setDefault($activity->name, $activity->milestone); // Set default value to the current milestone value.
                $group[] =& $mform->createElement("static", null, null, "<span>{$suffix}</span>");
                $libelactivity = "<a href='{$CFG->wwwroot}/course/modedit.php?update={$activity->id}'>"
                    . "{$activity->label} ({$activity->type})</a>";
                if (!empty($activity->availability)) {
                    $libelactivity = "<i class=\"fa fa-key\" aria-hidden=\"true\"></i> " . $libelactivity;
                }
                if ($activity->visible == 0) {
                    $libelactivity = "<i class=\"fa fa-eye-slash\" aria-hidden=\"true\"></i> " . $libelactivity;
                }

                $mform->addGroup($group, $groupname, $libelactivity, array(' '), false);
                $mform->addGroupRule($groupname, array(
                        $activity->name => array(
                                array(null, 'numeric', null, 'client')
                        )
                    ));
            }
        }
        $this->add_action_buttons();
    }

    /**
     * add filter bar to the form.
     */
    private function add_filter() {
        $mform = $this->_form;
        $filtergroup = array();
        $modules = db_accessor::get_instance()->get_allmodules();
        $lstmod = array();
        $lstmod[] = get_string('filtermodulealltype', 'tool_attestoodle');
        $lstmod[] = get_string('filtermoduleactivitytype', 'tool_attestoodle');
        foreach ($modules as $mod) {
            $lstmod[$mod->name] = get_string('modulename', $mod->name);
        }
        $filtergroup[] =& $mform->createElement('static', null, null, get_string('filtermodulename', 'tool_attestoodle'));
        $filtergroup[] =& $mform->createElement('text', 'namemod', '', array("size" => 10));
        $mform->setType('namemod', PARAM_TEXT );
        if (!empty($this->_customdata['namemod'])) {
            $mform->setDefault('namemod', $this->_customdata['namemod']);
        }

        $filtergroup[] =& $mform->createElement('static', null, null, get_string('filtermoduletype', 'tool_attestoodle'));
        $filtergroup[] =& $mform->createElement('select', 'typemod', '', $lstmod, null);
        if (!empty($this->_customdata['type'])) {
            $mform->setDefault('typemod', $this->_customdata['type']);
        }

        $selectyesno = array();
        $selectyesno[] = " ";
        $selectyesno[] = get_string('yes');
        $selectyesno[] = get_string('no');

        $filtergroup[] =& $mform->createElement('static', null, null, get_string('filtermodulevisible', 'tool_attestoodle'));
        $filtergroup[] =& $mform->createElement('select', 'visibmod', '', $selectyesno, null);
        $mform->setDefault('visibmod', $this->_customdata['visibmod']);

        $filtergroup[] =& $mform->createElement('static', null, null, get_string('filtermodulerestrict', 'tool_attestoodle'));
        $filtergroup[] =& $mform->createElement('select', 'restrictmod', '', $selectyesno, null);
        $mform->setDefault('restrictmod', $this->_customdata['restrictmod']);

        $filtergroup[] =& $mform->createElement('submit', 'filter',
            get_string('filtermodulebtn', 'tool_attestoodle'), array('class' => 'send-button'));
        $mform->addGroup($filtergroup, '', '', ' ', false);
    }
    /**
     * Filter the modules according to the chosen filters.
     * @param array $activities to filter.
     * @return array of modules that passes the filter.
     */
    private function filter($activities) {
        $ret = array();
        $lib = "";
        if (!empty($this->_customdata['type'])) {
            $filtertype = $this->_customdata['type'];
            if ($filtertype > "2") {
                $lib = get_string('modulename', $filtertype);
            }
        } else {
            $filtertype = 0;
        }

        foreach ($activities as $activity) {
            $pass = $this->filtertype($activity, $filtertype, $lib);
            if ($pass && $this->_customdata['visibmod'] == 1) {
                $pass = $activity->visible;
            }
            if ($pass && $this->_customdata['visibmod'] == 2) {
                $pass = !$activity->visible;
            }
            $pass = $this->filterrestrict($activity, $pass);
            // The filter on the name has priority.
            $pass = $this->filtername($activity, $pass);

            if ($pass) {
                $ret[] = $activity;
            }
        }
        return $ret;
    }

    /**
     * Determines whether the activity crosses the filter type.
     * @param stdClass $activity to test.
     * @param integer $filtertype to cross.
     * @param string $lib the name of module.
     * @return true if activity cross the filter type.
     */
    private function filtertype($activity, $filtertype, $lib) {
        $ret = true;
        if ($filtertype == 1 && $activity->ressource == 1) {
            $ret = false;
        }
        if (!empty($lib) && strcmp($activity->type, $lib) !== 0) {
            $ret = false;
        }
        return $ret;
    }
    /**
     * Determines whether the activity crosses the filter mane.
     * @param stdClass $activity to test.
     * @param bool $pass the actual result of other test.
     * @return true if activity cross the filter name.
     */
    private function filtername($activity, $pass) {
        $ret = $pass;
        if (!empty($this->_customdata['namemod'])) {
            if (stristr($activity->label, $this->_customdata['namemod']) != null) {
                $ret = true;
            } else {
                $ret = false;
            }
        }
        return $ret;
    }

    /**
     * Determines whether the activity crosses the filter availability.
     * @param stdClass $activity to test.
     * @param bool $pass the actual result of other test.
     * @return true if activity cross the filter availability.
     */
    private function filterrestrict($activity, $pass) {
        $ret = $pass;
        if ($ret && $this->_customdata['restrictmod'] == 0) {
            return $ret;
        }
        if ($ret && $this->_customdata['restrictmod'] == 2 && !empty($activity->availability)) {
            $ret = false;
        }
        if ($ret && $this->_customdata['restrictmod'] == 1 && empty($activity->availability)) {
            $ret = false;
        }
        return $ret;
    }
    /**
     * Build a table of courses and their activities to display.
     * The activities are arranged in their order of appearance.
     * @param stdClass[] $courses list of courses children of categories.
     * @param string $prefix text add to the elements of activities for make Id in the form.
     */
    private function get_elements($courses, $prefix) {
        $ret = array();
        foreach ($courses as $course) {
            $datacourse = new \stdClass();
            $datacourse->totalmilestones = parse_minutes_to_hours($course->get_total_milestones());
            $datacourse->id = $course->get_id();
            $datacourse->name = $course->get_name();
            $activities = db_accessor::get_instance()->get_activiesbysection($datacourse->id);

            foreach ($course->get_activities() as $activity) {
                $idfind = -1;
                foreach ($activities as $key => $value) {
                    if ($value->id == $activity->get_id()) {
                        $idfind = $key;
                    }
                }
                if ($idfind == -1) {
                    continue;
                }

                $dataactivity = $activities[$idfind];
                $dataactivity->id = $activity->get_id();
                $dataactivity->name = $prefix  . $activity->get_id();
                $dataactivity->label = $activity->get_name();
                $dataactivity->type = get_string('modulename', $activity->get_type());
                $dataactivity->milestone = $activity->get_milestone();
                $dataactivity->visible = $dataactivity->visible * $activity->get_visible();
                $dataactivity->availability = $dataactivity->availability . $activity->get_availability();
                if (plugin_supports('mod', $activity->get_type(), FEATURE_MOD_ARCHETYPE) != MOD_ARCHETYPE_RESOURCE) {
                    $dataactivity->ressource = 0;
                } else {
                    $dataactivity->ressource = 1;
                }
            }
            $datacourse->activities = $activities;
            $ret[] = $datacourse;
        }
        return $ret;
    }
    /**
     * Custom validation function automagically called when the form
     * is submitted. The standard validations, such as required inputs or
     * value type check, are done by the parent validation() method.
     * See validation() method in moodleform class for more details.
     * @param stdClass $data of form
     * @param string $files list of the form files
     * @return array of error.
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        return $errors;
    }
}
