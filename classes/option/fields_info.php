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
 * Handle fields for booking option.
 *
 * @package mod_booking
 * @copyright 2023 Wunderbyte GmbH <info@wunderbyte.at>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_booking\option;

use coding_exception;
use core_component;
use MoodleQuickForm;
use stdClass;

/**
 * Control and manage booking dates.
 *
 * @copyright Wunderbyte GmbH <info@wunderbyte.at>
 * @author Georg Maißer
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class fields_info {

     /**
      * This function runs through all installed field classes and executes the prepare save function.
      * Returns an array of warnings as string.
      * @param stdClass $formdata
      * @param stdClass $newoption
      * @param int $updateparam
      * @return array
      */
    public static function prepare_save_fields(stdClass &$formdata, stdClass &$newoption, int $updateparam):array {

        $warnings = [];
        $error = [];
        $fields = core_component::get_component_classes_in_namespace(
            "mod_booking",
            'option\fields'
        );

        foreach (array_keys($fields) as $classname) {
            if (class_exists($classname)) {

                // Execute the prepare function of every field.
                try {
                    $warning = $classname::prepare_save_field($formdata, $newoption, $updateparam);
                } catch (Exception $e) {
                    $error[] = $e;
                }

                if (!empty($warning)) {
                    $warnings[] = $warning;
                }
            }
        }

        return $warnings;
    }

    /**
     * A quick way to get classname without namespace.
     * @param mixed $classname
     * @return int|false|string
     */
    public static function get_class_name($classname) {
        if ($pos = strrpos($classname, '\\')) {
            return substr($classname, $pos + 1);
        }
        return $pos;
    }

    /**
     * This is a standard function to add a header, if it is not yet there.
     * @param MoodleQuickForm $mform
     * @param string $headeridentifier
     * @return void
     * @throws coding_exception
     */
    public static function add_header_to_mform(MoodleQuickForm &$mform, string $headeridentifier) {

        $elementexists = $mform->elementExists($headeridentifier);
        switch ($headeridentifier) {
            case MOD_BOOKING_HEADER_CUSTOMFIELDS:
            case MOD_BOOKING_HEADER_ACTIONS:
            case MOD_BOOKING_HEADER_ELECTIVE:
            case MOD_BOOKING_HEADER_PRICE:
            case MOD_BOOKING_HEADER_TEACHERS:
            case MOD_BOOKING_HEADER_SUBBOOKINGS:
            case MOD_BOOKING_HEADER_AVAILABILITY:
                // For some identifiers, we do nothing.
                // Because they take care of everything in one step.
                break;
            default:
                if (!$elementexists) {
                    $mform->addElement('header', $headeridentifier, get_string($headeridentifier, 'mod_booking'));
                }
                break;
        }
    }

    /**
     * Add all available fields in the right order.
     * @param MoodleQuickForm $mform
     * @param array $formdata
     * @param array $optionformconfig
     * @return void
     */
    public static function instance_form_definition(MoodleQuickForm &$mform, array &$formdata, array &$optionformconfig) {

        $fields = core_component::get_component_classes_in_namespace(
            "mod_booking",
            'option\fields'
        );

        $classes = [];
        foreach (array_keys($fields) as $classname) {
            $classes[$classname::$id] = $classname;
        }

        ksort($classes);

        foreach ($classes as $class) {
            $class::instance_form_definition($mform, $formdata, $optionformconfig);
        }
    }
}