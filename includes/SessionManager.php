<?php

namespace includes;

class SessionManager extends Session
{

    /**
     * Get all sessions
     * @param string $date: selected date
     * @return string
     */
    public function getSessionEditor($date)
    {
        if ($this->isAvailable(array('date'=>$date))) {
            return self::dayContainer(array('date'=>$date, 'content'=>self::nothingPlannedThisDay()));
        } else {
            return $this->getDayContent($this->all(array('s.date'=>$date)), $date, true);
        }
    }

    /**
     * Render session editor
     *
     * @param string $date: session date
     * @return string
     */
    public function editor($date = null)
    {
        if (!is_null($date)) {
            $data = $this->get(array('date'=>$date));
        } else {
            $data = $this->getDefaults();
        }

        return self::form(
            $data,
            self::getSessionContent($this->getSlots($data['id']), $date, $data['slots'], true),
            $this->settings['default_type']
        );
    }

    /**
     * Returns Session Manager view
     * @return string
     */
    public function getManager($date = null)
    {
        // Get next session date if none is provided
        if (is_null($date)) {
            $data = $this->getInstance()->getNext(1);
            $date = $data[0]['date'];
        }

        if (is_null($data)) {
            return self::nothingPlannedYet();
        } elseif ($this->getInstance()->isAvailable(array('date'=>$date))) {
            return self::sessionManager(
                null,
                $this->editor(),
                $date
            );
        } else {
            return self::sessionManager(
                $this->editor($date),
                $this->editor(),
                $date
            );
        }
    }

    /**
     * Renders email notifying presentation assignment
     * @param Users $user
     * @param array $info: array('type'=>session_type,'date'=>session_date, 'presid'=>presentation_id)
     * @param bool $assigned
     * @return mixed
     */
    public function notifyUpdate(Users $user, array $info, $assigned = true)
    {
        $MailManager = new MailManager();
        if ($assigned) {
            $dueDate = date('Y-m-d', strtotime($info['date'].' - 1 week'));
            $content = self::invitationEmail($user->fullname, $dueDate, $info['date'], $info['type']);
        } else {
            $content = self::cancelationUserEmail($user->fullname, $info['date']);
        }

        // Notify organizers of the cancellation but only for real users
        if (!$assigned && $user->username !== 'TBA') {
            $this->notifyOrganizers($user, $info);
        }

        // Send email
        $result = $MailManager->send($content, array($user->email));
        return $result;
    }

    /**
     * Notify organizers that a presentation has been manually canceled
     * @param Users $user
     * @param array $info
     * @return mixed
     */
    public function notifyOrganizers(Users $user, array $info)
    {
        $MailManager = new MailManager();
        foreach ($user->getAdmin() as $key => $info) {
            $content = self::cancelationOrganizerEmail($info['fullname'], $user->fullname, $info['date']);
            if (!$MailManager->send($content, array($info['email']))) {
                return false;
            }
        }
        return true;
    }

    /**
     * Cancel session (when session type is set to none)
     * @param Session $session
     * @return bool
     */
    public function cancelSession(Session $session)
    {
        $assignment = new Assignment();
        $result = true;

        // Loop over presentations scheduled for this session
        foreach ($session->presids as $id_pres) {
            $pres = new Presentation($id_pres);
            $speaker = new Users($pres->orator);

            // Delete presentation and notify speaker that his/her presentation has been canceled
            if ($result = $pres->delete_pres($id_pres)) {
                $info = array(
                    'speaker'=>$speaker->username,
                    'type'=>$session->type,
                    'presid'=>$pres->id_pres,
                    'date'=>$session->date
                );
                // Notify speaker
                $result = $assignment->updateAssignment($speaker, $info, false, true);
            }
        }

        // Update session information
        if ($result) {
            $result = $session->delete(array('id'=>$session->id));
        }
        return $result;
    }

    /**
     * Modify session type and change corresponding assignments
     *
     * @param string $id: session id
     * @param string $type: new session type
     * @return array
     */
    public function modifySessionType($id, $type)
    {
        if ($this->modifyAssignments($this->get(array('id'=>$id), $type))) {
            $result = $this->update(array('type'=>$type), array('id'=>$id));
            if ($result['status']) {
                $result['msg'] = "Session's type has been set to {$value}";
            }
        }
        return $result;
    }

    /**
     * Modify session type and notify speakers about the change
     * @param array $data
     * @param $new_type
     * @return bool
     */
    public function modifyAssignments(array $data, $new_type)
    {
        $assignment = new Assignment();
        $result = true;

        $previous_type = $data['type'];

        // Loop over presentations scheduled for this session
        foreach ($data['presids'] as $id_pres) {
            $pres = new Presentation($id_pres);
            $speaker = new Users($pres->orator);

            // Unassign
            $info = array(
                'speaker'=>$speaker->username,
                'type'=>$previous_type,
                'presid'=>$pres->id_pres,
                'date'=>$data['date']
            );

            // Update assignment table with new session type
            if ($assignment->updateAssignment($speaker, $info, false, false)) {
                $info['type'] = $new_type;
                $result = $assignment->updateAssignment($speaker, $info, true, false);
            }

            // Notify user about the change of session type
            $MailManager = new MailManager();
            $date = $info['date'];
            $contactURL = URL_TO_APP."index.php?page=contact";

            $content['body'] = "
            <div style='width: 100%; margin: auto;'>
                <p>Hello $speaker->fullname,</p>
                <p>This is to inform you that the type of your session ({$date}) 
                has been modified and will be a <strong>{$new_type}</strong> instead of a 
                <strong>{$previous_type}</strong>.</p>
                <p>If you need more information, please <a href='$contactURL'>contact</a> the organizers.</p>
            </div>
            ";
            $content['subject'] = "Your session ($date) has been modified";

            $result = $MailManager->send($content, array($speaker->email));
        }
        return $result;
    }

    /**
     * Render form to add session
     * @param array $data
     * @param $default_type
     * @return string
     */
    public static function form(array $data, $slots = null, $default_type = null)
    {
        // Repeat session option
        $repeat_options = null;
        foreach (array('Yes'=>1, 'No'=>0) as $label => $value) {
            $selected = (int)$data['to_repeat'] === $value ? 'selected' : null;
            $repeat_options .= "<option value={$value} {$selected}>{$label}</option>";
        }
        $show_repeat_settings = $data['to_repeat'] == 1 ? 'display: visible' : 'display: none';

        // Form action url
        $url = Router::buildUrl(
            'Session',
            'make'
        );

        // Select of input for session type
        $selectedType = (!empty($data['session_type'])) ? $data['session_type'] : $default_type;
        $type_list = TypesManager::getTypeSelectInput('Session', $selectedType);

        // Submit buttons
        if (isset($data['id'])) {
            $addButton = "<input type='submit' value='Add' class='processform' />";
            $deleteButton = "<input type='submit' value='Delete' class='delete_session' data-controller='Session' 
            data-action='delete' data-id='{$data['id']}' />";
            $modifyButton = "<input type='submit' class='modify_session' value='Modify' />";
            $buttons = "
            {$modifyButton}
            {$deleteButton}
            ";
        } else {
            $buttons = "<input type='submit' class='modify_session' value='Modify' />";
            $data['id'] = false;
        }

        if (!is_null($slots)) {
            $sessionContent = "
            <div class='session_presentations'>
                <h3>Presentations</h3>
                {$slots}
            </div>
            ";
        } else {
            $sessionContent = null;
        }

        return "
        <form action='{$url}' method='post'>
            <div class='form-group'>
                <select name='type'>{$type_list['options']}</select>
                <label>Type</label>
            </div>
            <div class='form-group field_small inline_field'>
                <input type='date' name='date' class='datepicker viewerCalendar' data-view='edit' 
                value='{$data['date']}' />
                <label>Date</label>
            </div>
            <div class='form-group field_small inline_field'>
                <input type='time' name='start_time' value='{$data['start_time']}' />
                <label>From</label>
            </div>
            <div class='form-group field_small inline_field'>
                <input type='time' name='end_time' value='{$data['end_time']}' />
                <label>To</label>
            </div>
            <div class='form-group field_small inline_field'>
                <input type='text' name='room' value='{$data['room']}' />
                <label>Room</label>
            </div>
            <div class='form-group field_small inline_field'>
                <input type='number' name='slots' value='{$data['slots']}' />
                <label>Slots</label>
            </div>
            <div>
                <div class='form-group field_small inline_field'>
                    <select name='to_repeat' class='repeated_session'>
                        <option value=1>Yes</option>
                        <option value=0 selected>No</option>
                    </select>
                    <label>Repeat</label>
                </div>
                <div class='form-group field_small inline_field settings_hidden' style='display: none;'>
                    <input type='date' name='end_date' value='{$data['date']}' />
                    <label>End date</label>
                </div>
                <div class='form-group field_small inline_field settings_hidden' style='display: none;'>
                    <input type='number' name='frequency' value='{$data['frequency']}' />
                    <label>Frequency (day)</label>
                </div>
                <div class='submit_btns'>
                    <input type='hidden' name='start_date' value='{$data['date']}' />
                    <input type='hidden' name='id' value='{$data['id']}' />
                </div>
            </div>
        </form>
        {$sessionContent}
        ";
    }

    /**
     * Render session manager
     * @param $sessionEditor
     * @param $form: edit form
     * @return string
     */
    public static function sessionManager($sessionEditor, $form, $selected_date = null)
    {
        return "
            <div class='session_viewer_container'>
                <h3>Edit a session</h3>
                <div class='form-group'>
                    <input type='date' class='selectSession datepicker viewerCalendar' 
                    name='date' data-view='edit' data-destination='#session_list' value='{$selected_date}' />
                    <label>Select a session</label>
                </div>
                <div id='session_list'>{$sessionEditor}</div>
            </div>

            <div>
                <h3>Add a new session</h3>
                {$form}
            </div>
        ";
    }

    /**
     * Render session slot
     * @param array $data
     * @param $presentations
     * @param $session_type: session type
     * @return string
     */
    public static function sessionEditContainer(array $data, $presentations, $session_type)
    {
        return "
            <div class='session_div session_editor_div' id='session_{$data['session_id']}' 
            data-id='{$data['session_id']}'>
                <div class='session_editor_core'>
                    <div class='session_settings'>
                        ". self::sessionSettings($data, $session_type) ."
                    </div>
    
                    <div class='session_presentations'>
                        <h3>Presentations</h3>
                        {$presentations}
                    </div>
                </div>
            </div>
        ";
    }
    

    /**
     * Show session slot as empty
     *
     * @return string
     */
    public static function nothingPlannedThisDay()
    {
        return "<div style='display: block; margin: 0 auto 10px 0; padding-left: 10px; font-size: 14px; 
                    font-weight: 600; overflow: hidden;'>
                    No Journal Club this day</div>";
    }

    /**
     * Show presentation slot as empty
     *
     * @param array $data : session data
     * @param bool $show_button: display add button
     * @return string
     */
    public static function emptyPresentationSlot(array $data, $show_button = true)
    {
        $url = URL_TO_APP . "index.php?page=member/submission&op=edit&date=" . $data['date'];
        $leanModalUrl = Router::buildUrl(
            'Presentation',
            'getForm',
            array(
                'view'=>'modal',
                'operation'=>'edit',
                'session_id'=>$data['id']
            )
        );
        $addButton = ($show_button) ? "
            <a href='{$url}' class='leanModal' data-url='{$leanModalUrl}' data-section='presentation'>
                <div class='add-button'></div>
            </a>" : null;

        $content = "
                <div>{$addButton}</div>";
        return self::slotContainerBody(array('name'=>'Free slot', 'button'=>$content, 'content'=>null));
    }

    /**
     * Show presentation slot as empty
     *
     * @return string
     */
    public static function emptySlotEdit()
    {
        return self::slotContainerBody(array('name'=>'Free slot', 'button'=>null, 'content'=>
            "<span style='font-size: 14px; font-weight: 500; color: #777;'>" . Presentation::speakerList() . "</span>
            "));
    }

    /**
     * Template for slot container
     * @param array $data
     * @param null $div_id
     * @return string
     */
    public static function slotContainerBody(array $data, $div_id = null)
    {
        return "
            <div class='pres_container ' id='{$div_id}' data-id='{$div_id}'>
                <div class='pres_type'>
                    {$data['name']}
                </div>
                <div class='pres_content'>
                    <div class='pres_info'>
                        {$data['content']}
                    </div>
                    <div class='pres_btn'>{$data['button']}</div>
                </div>
            </div>
            ";
    }

    /**
     * Template for slot container
     * @param array $data
     * @return string
     */
    public static function slotContainerEmail(array $data)
    {
        return "
            <div class='pres_container '>
                <div class='pres_type' style='display: inline-block; width: 50px; font-weight: 600; 
                color: #222222; vertical-align: middle; 
                    text-transform: capitalize;'>
                    {$data['name']}
                </div>
                <div class='pres_info' style='display: inline-block; width: 210px; 
                margin-left: 20px; vertical-align: middle;'>
                    {$data['content']}
                </div>
                <div class='pres_btn' style='display: inline-block; width: 35px; 
                vertical-align: middle;'>{$data['button']}</div>
            </div>
            ";
    }

    /**
     * Template for editable slot container
     *
     * @param array $data
     * @param null $div_id
     * @return string
     */
    public static function slotEditContainer(array $data, $div_id = null)
    {
        return "
            <div class='pres_container' id='{$div_id}' data-section='submission_form' data-id='{$div_id}'>
                <div class='pres_type'>
                    {$data['name']}
                </div>
                <div class='pres_info'>
                    {$data['content']}
                </div>
                <div class='pres_btn'>{$data['button']}</div>
            </div>
            ";
    }

    /**
     * Content of invitation email
     *
     * @param string $fullname: user's full name
     * @param string $dueDate: deadline for submitting presentation
     * @param string $date: date of presentation
     * @param string $session_type: session type
     *
     * @return array: array('body'=>content of email, 'subject'=>email's title)
     */
    private static function invitationEmail($fullname, $dueDate, $date, $session_type)
    {
        $contactURL = URL_TO_APP."index.php?page=contact";
        $editUrl = URL_TO_APP."index.php?page=submission&op=edit&id={$info['presid']}&user={$user->username}";
        return array(
            'body'=> "<div style='width: 100%; margin: auto;'>
                    <p>Hello {$fullname},</p>
                    <p>You have been automatically invited to present at a 
                    <span style='font-weight: 500'>{$session_type}</span> 
                    session on the <span style='font-weight: 500'>$date</span>.</p>
                    <p>Please, submit your presentation on the Journal Club Manager before the 
                    <span style='font-weight: 500'>{$dueDate}</span>.</p>
                    <p>If you think you will not be able to present on the assigned date, please 
                    <a href='{$contactURL}'>contact</a> the organizers as soon as possible.</p>
                    <div>
                        You can edit your presentation from this link: <a href='{$editUrl}'>{$editUrl}</a>
                    </div>
                </div>
            ",
            'subject'=> "Invitation to present on the $date"
        );
    }

    /**
     * Content of presentation cancelation email sent to speaker
     *
     * @param string $fullname: user's full name
     * @param string $date: presentation date
     *
     * @return array: array('body'=>content of email, 'subject'=>email's title)
     */
    private static function cancelationUserEmail($fullname, $date)
    {
        $contactURL = URL_TO_APP . "index.php?page=contact";
        return array(
            'body'=>"<div style='width: 100%; margin: auto;'>
                <p>Hello {$fullname},</p>
                <p>Your presentation planned on {$date} has been manually canceled. 
                You are no longer required to give a presentation on this day.</p>
                <p>If you need more information, please <a href='{$contactURL}'>contact</a> the organizers.</p>
                </div>
                ",
            'subject'=>"Your presentation ($date) has been canceled"
        );
    }

    /**
     * Content of presentation cancelation email sent to organizers
     *
     * @param string $fullname: organizer's full name
     * @param string $speaker: speaker's full name
     * @param string $date: date of presentation
     *
     * @return array: array('body'=>content of email, 'subject'=>email's title)
     */
    private static function cancelationOrganizerEmail($fullname, $speaker, $date)
    {
        $url = URL_TO_APP . 'index.php?page=organizer/sessions';
        return array(
            'body'=>"<div style='width: 100%; margin: auto;'>
                <p>Hello {$info['fullname']},</p>
                <p>This is to inform you that the presentation of 
                <strong>{$user->fullname}</strong> planned on the <strong>{$date}</strong> has been canceled. 
                You can either manually assign another speaker on this day in the 
                <a href='{$url}'>Admin>Session</a> section or let the automatic 
                assignment select a member for you.</p>
                </div>
            ",
            'subject'=>"A presentation ($date) has been canceled"
        );
    }
}