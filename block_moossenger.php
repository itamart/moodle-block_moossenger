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
 * @subpackage moossenger
 * @copyright 2012 Itamar Tzadok
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("$CFG->dirroot/message/lib.php");

class block_moossenger extends block_base {
    /**
     *
     */
    function init() {
        $this->blockname = 'block_'. $this->name();
        $this->title = get_string('pluginname', $this->blockname);
    }

    /**
     *
     */
    function specialization() {
        // load userdefined title and make sure it's never empty
        if (empty($this->config->title)) {
            $this->title = get_string('pluginname', $this->blockname);
        } else {
            $this->title = $this->config->title;
        }
        
        // Show notifications
        if (empty($this->config->waiting)) {
            $this->showwaiting = 0;
        } else {
            $this->showwaiting = $this->config->waiting;
        }
        
        // Show notifications
        if (empty($this->config->notifications)) {
            $this->shownotifications = 0;
        } else {
            $this->shownotifications = $this->config->notifications;
        }
        
        // Show messages
        if (empty($this->config->conversations)) {
            $this->showconversations = 0;
        } else {
            $this->showconversations = $this->config->conversations;
        }
    }

    /**
     * All multiple instances of this block
     * @return bool true
     */
    function instance_allow_multiple() {
        return true;
    }

    /**
     * Set the applicable formats for this block to all
     * @return array
     */
    function applicable_formats() {
        return array('all' => true);
    }

    /**
     *
     * @return bool true
     */
    function  instance_can_be_hidden() {
        return true;
    }

    
    /**
     *
     */
    function get_content() {
        global $CFG, $OUTPUT;

        if (!$CFG->messaging) {
            $this->content->text = '';
            if ($this->page->user_is_editing()) {
                $this->content->text = get_string('disabled', 'message');
            }
            return $this->content;
        }

        if ($this->content !== NULL) {
            return $this->content;
        }

        $this->content = new stdClass;
        $this->content->text = '';
        $this->content->footer = '';

        if (empty($this->instance) or !isloggedin() or isguestuser() or empty($CFG->messaging)) {
            return $this->content;
        }

        // Show links to new messages
        $messages = '';
        if ($this->showwaiting) {
            $messages = $this->print_waiting_messages();
        }
        
        // Show recent notifications
        $notifications = '';
        if ($this->shownotifications) {
            $notifications = $this->print_recent_messages('notifications');
        }    
        
        // Show recent messages
        $conversations = '';
        if ($this->showconversations) {
            $conversations = $this->print_recent_messages('conversations');
        }    
        
        // Put the content together
        $this->content->text = $OUTPUT->box_start('message'). $messages. $notifications. $conversations. $OUTPUT->box_end();
        
        // Add footer link to the messages page
        $link = '/message/index.php';
        $action = null; //this was using popup_action() but popping up a fullsize window seems wrong
        $this->content->footer = $OUTPUT->action_link($link, get_string('messages', 'message'), $action);

        return $this->content;
    }
    
    /**
     * Print the user's recent notifications
     *
     * @param stdClass $user the current user
     */
    protected function print_recent_messages($type, $user=null) {
        global $USER;

        if (empty($user)) {
            $user = $USER;
        }

        $messages = '';
        $getfunc = "message_get_recent_$type";
        $showtype = "show$type";
        $limitto = $this->$showtype;
        if ($items = $getfunc($user, 0, $limitto)) {
            $strheading = html_writer::tag(
                'p',
                get_string("mostrecent$type", 'message'),
                array('class' => 'heading')
            );

            $showotheruser = ($type == 'conversations' ? true : false);
            ob_start();
            message_print_recent_messages_table($items, $user, $showotheruser, false);
            $messages = html_writer::tag('div', $strheading. ob_get_contents(), array('class' => 'messagearea'));
            ob_end_clean();
        }
        return $messages;        
    }
    
    /**
     *
     */
    protected function print_waiting_messages() {
        global $DB, $USER, $OUTPUT;
        
        $messagelinks = '';
        
        $ufields = user_picture::fields('u', array('lastaccess'));
        $users = $DB->get_records_sql("SELECT $ufields, COUNT(m.useridfrom) AS count
                                         FROM {user} u, {message} m
                                        WHERE m.useridto = ? AND u.id = m.useridfrom AND m.notification = 0
                                     GROUP BY $ufields", array($USER->id));


        //Now, we have in users, the list of users to show because they are online
        if (!empty($users)) {
            $mlinks = array();
            $userurl = new moodle_url('/user/view.php', array('course' => SITEID));
            foreach ($users as $user) {
                $timeago = format_time(time() - $user->lastaccess);
                $userurl->param('id', $user->id);
                $userpic = $OUTPUT->user_picture($user, array('courseid'=>SITEID));
                $username = fullname($user);
                $userlink = html_writer::link($userurl, $username, array('title' => $timeago));
                $userinfo = html_writer::tag('div', $userpic. '  '. $userlink, array('class' => 'user'));
 
                $anchortagcontents = $OUTPUT->pix_icon('t/message', get_string('message', 'message')). "&nbsp;". $user->count;
                $link = new moodle_url('/message/index.php', array('usergroup' => 'unread', 'id' => $user->id));
                $action = null; // popup is gone now
                $anchortag = $OUTPUT->action_link($link, $anchortagcontents, $action);
                $message = html_writer::tag('div', "$userinfo&nbsp;&nbsp;&nbsp;&nbsp;$anchortag", array('class' => 'singlemessage'));
                $mlinks[] = html_writer::tag('li', $message, array('class' => "listentry"));
            }
            $msglinks = html_writer::tag('ul', implode("\n", $mlinks), array('class' => 'list'));
            $strheading = html_writer::tag(
                'p',
                get_string("messageswaiting", $this->blockname),
                array('class' => 'heading')
            );
            $messagelinks = html_writer::tag('div', $strheading. $msglinks, array('class' => "messagerecent"));
        } else {
            $messagelinks = html_writer::tag('div', get_string('nomessages', 'message'), array('class' => "info"));
        }
        return html_writer::tag('div', $messagelinks, array('class' => "messagearea"));
    }

}


