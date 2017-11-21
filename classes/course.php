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
 * This is the class describing a course in Attestoodle
 *
 * @package    block_attestoodle
 * @copyright  2017 Pole de Ressource Numerique de l'Université du Mans
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_attestoodle;

defined('MOODLE_INTERNAL') || die;

class course {
    /** @var string Id of the course */
    private $id;

    /** @var string Name of the course */
    private $name;

    /** @var array Activities of the course */
    private $activities;

    /**
     * Constructor of the course class
     *
     * @param string $id Id of the course
     * @param string $name Name of the course
     * @param array $activities Activities of the course
     */
    public function __construct($id, $name) {
        $this->id = $id;
        $this->name = $name;
        $this->activities = array();
    }

    public function get_data_as_table() {
        return [
                $this->id,
                $this->name,
            ];
    }

    public function get_object_as_stdclass() {
        $obj = new \stdClass();
        $obj->id = $this->id;
        $obj->name = $this->name;

        return $obj;
    }

    /**
     * Getter for $id property
     *
     * @return int Id of the course
     */
    public function get_id() {
        return $this->id;
    }

    /**
     * Getter for $name property
     *
     * @return string Name of the course
     */
    public function get_name() {
        return $this->name;
    }

    /**
     * Getter for $activities property
     *
     * @return array Activities of the course
     */
    public function get_activities() {
        return $this->activities;
    }

    /**
     * Setter for $id property
     *
     * @param int $prop Id to set for the course
     */
    public function set_id($prop) {
        $this->id = $prop;
    }

    /**
     * Setter for $name property
     *
     * @param string $prop Name to set for the course
     */
    public function set_name($prop) {
        $this->name = $prop;
    }

    /**
     * Setter for $activities property
     *
     * @param array $prop Activities to set for the course
     */
    public function set_activities($prop) {
        $this->activities = $prop;
    }

    /**
     * Add an activity to the course activities list
     *
     * @param activity $activity Activity to add to the course
     */
    public function add_activity($activity) {
        $this->activities[] = $activity;
    }
}