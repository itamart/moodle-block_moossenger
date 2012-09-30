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
 * @package   block
 * @subpackage   moossenger
 * @copyright 2012 Itamar Tzadok
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class block_moossenger_edit_form extends block_edit_form {

    protected function specific_definition($mform) {
        global $DB, $SITE, $CFG, $PAGE;

        $blockname = $this->block->blockname;

        // buttons
        //-------------------------------------------------------------------------------
    	$this->add_action_buttons();

        // Fields for editing HTML block title and contents.
        //--------------------------------------------------------------
        $mform->addElement('header', 'configheader', get_string('blocksettings', 'block'));

        // Title
        $mform->addElement('text', 'config_title', get_string('title', $blockname));
        $mform->setDefault('config_title', get_string('pluginname', $blockname));
        $mform->setType('config_title', PARAM_MULTILANG);

        // Show waiting
        $mform->addElement('selectyesno', 'config_waiting', get_string('showwaiting', $blockname));
        $mform->setDefault('config_waiting', 1);
        // Show recent notifications
        $options = array(0 => get_string('none')) + array_combine(range(1,20), range(1,20));
        $mform->addElement('select', 'config_notifications', get_string('shownotifications', $blockname), $options);
        // Show recent messages
        $options = array(0 => get_string('none')) + array_combine(range(1,20), range(1,20));
        $mform->addElement('select', 'config_conversations', get_string('showconversations', $blockname), $options);
    }    
}
