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
 * Useful global functions for Attestoodle.
 *
 * @package    tool_attestoodle
 * @copyright  2018 Pole de Ressource Numerique de l'Université du Mans
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * Parse a number of minutes into a readable hours string.
 *
 * @param integer $minutes The number of minutes to parse
 * @return string The hourse corresponding (formatted as 'XhYY')
 */
function parse_minutes_to_hours($minutes) {
    $h = floor($minutes / 60);
    $m = $minutes % 60;
    $m = $m < 10 ? '0' . $m : $m;

    return $h . "h" . $m;
}

/**
 * Parse a DateTime object into a readable format like "DD/MM/YYYY".
 *
 * @param \DateTime $datetime The DateTime object to parse
 * @return string The date in a readable format
 */
function parse_datetime_to_readable_format($datetime) {
    return $datetime->format(get_string('dateformat', 'tool_attestoodle'));
}

/**
 * Function automagically called by moodle to retrieve a file on the server that
 * the plug-in can interact with.
 *
 * @link See doc at https://docs.moodle.org/dev/File_API#Serving_files_to_users
 */
function tool_attestoodle_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options=array()) {
    if ($course && $cm) {
        $cm = $cm;
        $course = $course;
    }

    // Make sure the filearea is one of those used by the plugin.
    if ($filearea !== 'certificates' && $filearea !== 'fichier') {
        return false;
    }

    // Leave this line out if you set the itemid to null in make_pluginfile_url (set $itemid to 0 instead).
    $itemid = 0;

    // Use the itemid to retrieve any relevant data records and perform any security checks to see if the
    // user really does have access to the file in question.

    // Extract the filename / filepath from the $args array.
    $filename = array_pop($args); // The last item in the $args array.
    if (!$args) {
        $filepath = '/'; // If $args is empty => the path is '/'.
    } else {
        $filepath = '/'.implode('/', $args).'/'; // Var $args contains elements of the filepath.
    }

    // Retrieve the file from the Files API.
    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'tool_attestoodle', $filearea, $itemid, $filepath, $filename);
    if (!$file) {
        return false; // The file does not exist.
    }

    // Force non image formats to be downloaded.
    if ($file->is_valid_image()) {
        $forcedownload = false;
    }

    // We can now send the file back to the browser - in this case with a cache lifetime of 1 day and no filtering.
    // From Moodle 2.3, use send_stored_file instead.
    send_stored_file($file, 1, 0, $forcedownload, $options);
}

/**
 * Function automagically called by moodle to add a setting navigation entry
 *
 * @param array     $settingsnav
 * @param object    $context
 * @return void
 */

function tool_attestoodle_extend_navigation_category_settings(navigation_node $parentnode, context_coursecat $context) {
    global $PAGE, $CFG;
    $userhascapability = has_capability('tool/attestoodle:managetraining', $context);
    $toolpath = $CFG->wwwroot. "/" . $CFG->admin . "/tool/attestoodle";
    if ($userhascapability) {
        $categoryid = $PAGE->context->instanceid;
        $url = new moodle_url($toolpath . '/index.php',
                array(
                        "page" => "trainingmanagement",
                        "categoryid" => $categoryid,
                        "call" => "categ"
                ));
        $node = navigation_node::create(
                "Attestoodle",
                $url,
                navigation_node::NODETYPE_LEAF,
                'admincompetences',
                'admincompetences',
                new pix_icon('navigation', "Attestoodle", "tool_attestoodle"));
        $node->showinflatnavigation = false;
        $parentnode->add_node($node);
    }
}