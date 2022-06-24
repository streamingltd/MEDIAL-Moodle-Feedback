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

    // Used for group assignments on the submission summary page so we have a unique frame ID
    private $count = 0;

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

        $thumbparams = array('type' => HML_LAUNCH_FEEDBACK_THUMBNAILS);
        $params = array('type' => HML_LAUNCH_FEEDBACK, 'userid' => $grade->userid);

        if ($gradeid) {
            $feedbackentry = $this->get_feedback_entry($gradeid);
            if ($feedbackentry) {
                $preid = $feedbackentry->preid;
                //$param = "e_feed=" . $feedbackentry->preid;
                $thumbparams['e_feed'] = $feedbackentry->preid;
                $params['e_feed'] = $feedbackentry->preid;
                $mform->setDefault('helixfeedback_preid', $feedbackentry->preid);
            }
        }

        if (!array_key_exists('e_feed', $params)) {
            $preid = helixmedia_preallocate_id();
            
            $thumbparams['n_feed'] = $preid;
            $params['n_feed'] = $preid;
            $thumbparams['aid'] = $PAGE->cm->id;
            $params['aid'] = $PAGE->cm->id;
            $mform->setDefault('helixfeedback_preid', $preid);
        }

        $output = $PAGE->get_renderer('mod_helixmedia');
        $disp = new \mod_helixmedia\output\modal($preid, $thumbparams, $params, 'upload',
            get_string('add_feedback', 'assignfeedback_helixfeedback'), false, true, "column");

        $mform->addElement('static', 'helixfeedback_choosemedia', "", $output->render($disp));
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

        if (helixmedia_is_preid_empty($data->helixfeedback_preid, $this, $grade->grader)) {
            return true;
        }

        $feedbackentry = $this->get_feedback_entry($grade->id);

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
    public function view_summary(stdClass $grade, &$showviewlink) {
        // We want to show just the link on the grading table to keep things condensed, otherwise the normal graphic button.
        if (optional_param('action', false, PARAM_TEXT) != 'grading') {
            return $this->view($grade);
        }

        $feedbackentry = $this->get_feedback_entry($grade->id);
        if ($feedbackentry) {
            global $PAGE;

            $extraid = $this->count;
            $this->count++;

            $params = array('type' => HML_LAUNCH_VIEW_FEEDBACK, 'e_feed' => $feedbackentry->preid, 'userid' => $grade->userid);
            $output = $PAGE->get_renderer('mod_helixmedia');
            $disp = new \mod_helixmedia\output\modal($feedbackentry->preid, array(), $params, false,
                 get_string('view_feedback', 'assignfeedback_helixfeedback'), false, false, 'row', $extraid);
            return $output->render($disp);
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

            $thumbparams = array('type' => HML_LAUNCH_VIEW_FEEDBACK_THUMBNAILS, 'e_feed' =>$feedbackentry->preid, 'userid' => $grade->userid);
            $params = array('type' => HML_LAUNCH_VIEW_FEEDBACK, 'e_feed' => $feedbackentry->preid, 'userid' => $grade->userid);
            $output = $PAGE->get_renderer('mod_helixmedia');
            $disp = new \mod_helixmedia\output\modal($feedbackentry->preid, $thumbparams, $params, "moodle-lti-viewfeed-btn.png",
                 get_string('view_feedback', 'assignfeedback_helixfeedback'), false, false);
            return $output->render($disp);
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

    /**
     * Has the plugin form element been modified in the current submission?
     *
     * @param stdClass $grade The grade.
     * @param stdClass $data Form data from the feedback form.
     * @return boolean - True if the form element has been modified.
     */
    public function is_feedback_modified(stdClass $grade, stdClass $data) {
        return true;
    }

}
