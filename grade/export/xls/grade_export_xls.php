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

require_once($CFG->dirroot.'/grade/export/lib.php');

class grade_export_xls extends grade_export {

    public $plugin = 'xls';

    /**
     * To be implemented by child classes
     */
    public function print_grades() {
        global $CFG;
        require_once($CFG->dirroot.'/lib/excellib.class.php');

        $export_tracking = $this->track_exports();

        $strgrades = get_string('grades');

        // Calculate file name
        $shortname = format_string($this->course->shortname, true, array('context' => context_course::instance($this->course->id)));
        $downloadfilename = clean_filename("$shortname $strgrades.xls");
        // Creating a workbook
        $workbook = new MoodleExcelWorkbook("-");
        // Sending HTTP headers
        $workbook->send($downloadfilename);
        // Adding the worksheet
        $myxls = $workbook->add_worksheet($strgrades);

		$pageMargins = new PHPExcel_Worksheet_PageMargins();
		$pageMargins->setLeft(0.8);
		$pageMargins->setRight(0.3);
		$pageMargins->setTop(0.4);
		$pageMargins->setBottom(0.4);
		$myxls->set_margins($pageMargins);

		$pageSetup = new PHPExcel_Worksheet_PageSetup();
		$pageSetup->setPaperSize(9);
		$pageSetup->setOrientation('landscape');
		$pageSetup->setColumnsToRepeatAtLeftByStartAndEnd('A');
		$pageSetup->setRowsToRepeatAtTopByStartAndEnd('1');
		$myxls->set_page_setup($pageSetup);

		$myxls->freeze_panes('B2');

		$myxls->set_column(0, 0, 40);
		$myxls->set_column(1, 1, 30);

		$myfrm = $workbook->add_format();
		$myfrm->set_align('vcenter');
		$myfrm->set_align('center');
		$myfrm->set_text_wrap();


        // Print names of all the fields
        $profilefields = grade_helper::get_user_profile_fields($this->course->id, $this->usercustomfields);
		$myxls->write_string(0, 0, 'Фамилия, Имя, Отчество', $myfrm);
		$myxls->write_string(0, 1, 'Группа', $myfrm);
		$pos = 2;
        if (!$this->onlyactive) {
            $myxls->write_string(0, $pos++, get_string("suspended"), $myfrm);
        }
        foreach ($this->columns as $grade_item) {
            $myxls->write_string(0, $pos++, $this->format_column_name($grade_item), $myfrm);

            // Add a column_feedback column
            if ($this->export_feedback) {
                $myxls->write_string(0, $pos++, $this->format_column_name($grade_item, true));
            }
        }

        // Print all the lines of data.
        $i = 0;
        $geub = new grade_export_update_buffer();
        $gui = new graded_users_iterator($this->course, $this->columns, $this->groupid);
        $gui->require_active_enrolment($this->onlyactive);
        $gui->allow_user_custom_fields($this->usercustomfields);
        $gui->init();
        while ($userdata = $gui->next_user()) {
            $i++;
            $user = $userdata->user;
			$lname = '';
			$fname = '';
			$group = '';
            foreach ($profilefields as $id => $field) {
                $fieldvalue = grade_helper::get_user_field_value($user, $field);
				if ($field->shortname == 'firstname') {
					$fname = $fieldvalue;
				}
				if ($field->shortname == 'lastname') {
					$lname = $fieldvalue;
				}
				if ($field->shortname == 'department') {
					$group = $fieldvalue;
				}
            }
            $myxls->write_string($i, 0, $lname . ' ' . $fname);
			$myxls->write_string($i, 1, $group);
            $j = 2;
            if (!$this->onlyactive) {
                $issuspended = ($user->suspendedenrolment) ? get_string('yes') : '';
                $myxls->write_string($i, $j++, $issuspended);
            }
            foreach ($userdata->grades as $itemid => $grade) {
                if ($export_tracking) {
                    $status = $geub->track($grade);
                }

                $gradestr = $this->format_grade($grade);
                if (is_numeric($gradestr)) {
                    $myxls->write_number($i,$j++,$gradestr);
                }
                else {
                    $myxls->write_string($i,$j++,$gradestr);
                }

                // writing feedback if requested
                if ($this->export_feedback) {
                    $myxls->write_string($i, $j++, $this->format_feedback($userdata->feedbacks[$itemid]));
                }
            }
        }
        $gui->close();
        $geub->close();

    /// Close the workbook
        $workbook->close();

        exit;
    }
}


