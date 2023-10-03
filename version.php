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
 * This file contains the version information for the helix feedback plugin
 *
 * @package   assignfeedback_helixfeedback
 * @copyright 2014 Streaming LTD http://www.streaming.co.uk
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Tim Williams (tmw@autotrain.org) for Streaming LTD
 */

defined('MOODLE_INTERNAL') || die();

$plugin->version   = 2023100302;
$plugin->requires  = 2020061500;
$plugin->component = 'assignfeedback_helixfeedback';
$plugin->release = '8.5.004';
$plugin->dependencies = array(
    'mod_helixmedia' => '2023100302'
);
$plugin->supported = [39, 402];
