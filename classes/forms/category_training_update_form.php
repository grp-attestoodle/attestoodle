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
 * This is the class that handles the addition/suppression of trainings through
 * a moodleform moodle object.
 *
 * @package    block_attestoodle
 * @copyright  2018 Pole de Ressource Numerique de l'Université du Mans
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_attestoodle\forms;

defined('MOODLE_INTERNAL') || die;

// Class \moodleform is defined in formslib.php.
require_once("$CFG->libdir/formslib.php");

class category_training_update_form extends \moodleform {
    /**
     * Method automagically called when the form is instanciated. It defines
     * all the elements (inputs, titles, buttons, ...) in the form.
     */
    public function definition() {
        $name = "checkbox_is_training";
        $category = $this->_customdata['data'];

        $mform = $this->_form;

        $label = get_string('training_management_checkbox_label', 'block_attestoodle');
        $istraining = $category->is_training();

        $mform->addElement("advcheckbox", $name, $label);
        $mform->setDefault($name, $istraining);

        $this->add_action_buttons(false);
    }

    /**
     * Custom validation function automagically called when the form
     * is submitted. The standard validations, such as required inputs or
     * value type check, are done by the parent validation() method.
     *
     * See validation() method in moodleform class for more details.
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        return $errors;
    }
}