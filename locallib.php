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
 * This file contains the definition for the library class for comment feedback plugin
 *
 *
 * @package   assignfeedback_helixfeedback
 * @copyright 2014 Streaming LTD http://www.streaming.co.uk
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Tim Williams (tmw@autotrain.org) for Streaming LTD
 */

 defined('MOODLE_INTERNAL') || die();

 require_once($CFG->dirroot.'/mod/helixmedia/lib.php');
 require_once($CFG->dirroot.'/mod/helixmedia/locallib.php');

/**
 * Library class for video feedback plugin extending feedback plugin base class
 *
 * @copyright 2014 Streaming LTD http://www.streaming.co.uk
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class assign_feedback_helixfeedback extends assign_feedback_plugin {

    /**
     * Get the name of the online comment feedback plugin
     * @return string
     */
    public function get_name() {
        return get_string('pluginname', 'assignfeedback_helixfeedback');
    }

    /**
     * Get the feedback video entry from the database
     *
     * @param int $gradeid
     * @return stdClass|false The feedback entry for the given submission if it exists. False if it doesn't.
     */
    public function get_feedback_entry($gradeid) {
        global $DB;
        return $DB->get_record('assignfeedback_helixfeedback', array('grade' => $gradeid));
    }

    /**
     * Get form elements for the grading page
     *
     * @param stdClass|null $grade
     * @param MoodleQuickForm $mform
     * @param stdClass $data
     * @return bool true if elements were added to the form
     */
    public function get_form_elements($grade, MoodleQuickForm $mform, stdClass $data) {
        global $PAGE, $CFG;

        if ($grade) {
            $feedbackentry = $this->get_feedback_entry($grade->id);
        }

        $gradeid = $grade ? $grade->id : 0;

        $mform->addElement('hidden', 'helixfeedback_preid');
        $mform->setType('helixfeedback_preid', PARAM_INT);

        $mform->addElement('hidden', 'helixfeedback_activated');
        $mform->setType('helixfeedback_activated', PARAM_INT);

        if ($gradeid) {
            $feedbackentry = $this->get_feedback_entry($gradeid);
            if ($feedbackentry) {
                $preid = $feedbackentry->preid;
                $param = "e_feed=" . $feedbackentry->preid;
                $mform->setDefault('helixfeedback_preid', $feedbackentry->preid);
            }
        }

        if (!isset($param)) {
            $preid = helixmedia_preallocate_id();
            $param = "n_feed=".$preid."&aid=".$PAGE->cm->id;
            $mform->setDefault('helixfeedback_preid', $preid);
        }

        $splitline = false;
        // Moodle 3.1 and higher.
        if ($CFG->version >= 2016052300) {
            $splitline = true;
        }

        $mform->addElement('static', 'helixfeedback_choosemedia', "",
            helixmedia_get_modal_dialog($preid, "type=".HML_LAUNCH_FEEDBACK_THUMBNAILS."&".$param,
                "type=".HML_LAUNCH_FEEDBACK."&".$param, "", "", "", "", -1, "true", $splitline));

        return true;
    }

    /**
     * Saving the comment content into dtabase
     *
     * @param stdClass $grade
     * @param stdClass $data
     * @return bool
     */
    public function save(stdClass $grade, stdClass $data) {
        global $DB;
        $feedbackentry = $this->get_feedback_entry($grade->id);

        if ($data->helixfeedback_activated != 1) {
            return true;
        }

        if ($feedbackentry) {
            /***Nothing needs to change in the DB for an update since the only change is on the HML server, so just return true***/
            return true;
        } else {
            $feedbackentry = new stdClass();
            $feedbackentry->grade = $grade->id;
            $feedbackentry->assignment = $this->assignment->get_instance()->id;
            $prerec = $DB->get_record('helixmedia_pre', array('id' => $data->helixfeedback_preid));
            $feedbackentry->preid = $prerec->id;
            $feedbackentry->servicesalt = $prerec->servicesalt;
            return $DB->insert_record('assignfeedback_helixfeedback', $feedbackentry) > 0;
        }
    }

    /**
     * display the comment in the feedback table
     *
     * @param stdClass $grade
     * @param bool $showviewlink Set to true to show a link to view the full feedback
     * @return string
     */
    public function view_summary(stdClass $grade, & $showviewlink) {
        $showviewlink = true;

        $feedbackentry = $this->get_feedback_entry($grade->id);
        if ($feedbackentry) {
            global $PAGE;

            $type = HML_LAUNCH_VIEW_FEEDBACK;
            $thumbtype = HML_LAUNCH_VIEW_FEEDBACK_THUMBNAILS;

            $param = "e_feed=" . $feedbackentry->preid . "&userid=" . $grade->userid;
            return helixmedia_get_modal_dialog($feedbackentry->preid,
                "type=" . $thumbtype . "&" . $param,
                "type=" . $type . "&" . $param, "margin-left:auto;margin-right:auto;",
                get_string('view_feedback', 'assignfeedback_helixfeedback'), -1, -1, -1, "false");
        }
        return '';
    }

    /**
     * display the comment in the feedback table
     *
     * @param stdClass $grade
     * @return string
     */
    public function view(stdClass $grade) {
        $feedbackentry = $this->get_feedback_entry($grade->id);
        if ($feedbackentry) {
            global $PAGE;

            $type = HML_LAUNCH_VIEW_FEEDBACK;
            $thumbtype = HML_LAUNCH_VIEW_FEEDBACK_THUMBNAILS;

            $param = "e_feed=" . $feedbackentry->preid . "&userid=" . $grade->userid;
            return helixmedia_get_modal_dialog($feedbackentry->preid,
                "type=" . $thumbtype . "&".$param,
                "type=" . $type . "&".$param, "margin-left:auto;margin-right:auto;",
                "moodle-lti-viewfeed-btn.png", "", "", -1, "false");
        }
        return '';
    }

    /**
     * Return true if this plugin can upgrade an old Moodle 2.2 assignment of this type
     * and version.
     *
     * @param string $type old assignment subtype
     * @param int $version old assignment version
     * @return bool True if upgrade is possible
     */
    public function can_upgrade($type, $version) {
        return false;
    }


    /**
     * The assignment has been deleted - cleanup
     *
     * @return bool
     */
    public function delete_instance() {
        global $DB;
        // Will throw exception on failure.
        $DB->delete_records('assignfeedback_helixfeedback', array('assignment' => $this->assignment->get_instance()->id));
        return true;
    }

    /**
     * Returns true if there are no feedback comments for the given grade
     *
     * @param stdClass $grade
     * @return bool
     */
    public function is_empty(stdClass $grade) {
        return $this->view($grade) == '';
    }

}
