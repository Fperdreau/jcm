<?php

namespace includes;

/**
 * Session Manager UI
 *
 * Render interface for managing sessions and corresponding presentations
 */
class SessionManager
{
    
    /**
     * Session instance
     *
     * @var Session
     */
    private static $instance;

    /**
     * Class constructor
     *
     */
    public function __construct()
    {
    }

    /**
     * Get Session instance
     *
     * @return Session
     */
    private static function factory()
    {
        if (is_null(self::$instance)) {
            self::$instance = new Session();
        }
        return self::$instance;
    }

    /**
     * Get session viewer
     *
     * @param string|null $date: day to show
     * @param int $n: number of days to display
     *
     * @return string
     */
    public static function show($date = null)
    {
        return self::layout(self::getCalendarContent($date), $date);
    }

    /**
     * Get list of future presentations (home page/mail)
     *
     * @param int $nsession: number of sessions to get
     *
     * @return string
     */
    public static function getCalendarContent($date = null, $nSession = 4)
    {
        if (!is_null($date)) {
            // Repeat sessions
            self::factory()->repeatAll($date);
            
            return self::getDayContent(self::factory()->all(array('s.date'=>$date)), $date, false);
        } else {
            return self::selectADate();
        }
    }

    /**
     * Get editor content
     *
     * @param int $nsession: number of sessions to get
     *
     * @return string
     */
    public static function getCalendarContentOld($date = null, $nSession = 1)
    {
        if (!is_null($date)) {
            $data = self::factory()->all(array('s.date'=>$date));
            if (!empty($data)) {
                return self::form(
                    $data,
                    self::getSessionContent(
                        $data,
                        $date,
                        true
                    ),
                    self::factory()->getSettings('default_type')
                );
            } else {
                return self::nothingPlannedThisDay();
            }
        } else {
            return self::selectADate();
        }
    }

    /**
     * Get and render day content
     *
     * @param array  $data: day information
     * @param string $day : requested date (d-m-Y)
     * @param bool   $edit: get editor (true) or viewer (false)
     *
     * @return string
     */
    public static function getDayContent(array $data, $day, $edit = false)
    {
        $date = date('d M Y', strtotime($day));
        $dayContent = null;
        if (!empty($data)) {
            if (count($data)>1) {
                foreach ($data as $session_id => $session_data) {
                    $dayContent .= self::getSessionSummary($session_data, $date);
                }
            } else {
                $dayContent = self::getSessionEditor(reset($data), $date);
            }
        } else {
            $dayContent .= self::getSessionEditor(self::factory()->getDefaults($date), $date);
        }
        return self::dayContainer(array('date'=>$date, 'content'=>$dayContent));
    }

    /**
     * Load session editor
     *
     * @param string $id: session id
     *
     * @return string
     */
    public static function loadSessionEditor($id)
    {
        $data = self::factory()->all(array('s.id'=>$id));
        $data = $data[$id];
        return self::getSessionEditor($data, $data['date']);
    }

    /**
     * Render list of sessions in a day
     *
     * @param array $date
     * @param string $date
     * @return string
     */
    private static function getSessionSummary(array $data, $date)
    {
        $content = null;
        $nSlots = max(count($data['content']), $data['slots']);
        $url = Router::buildUrl(
            'SessionManager',
            'loadSessionEditor',
            array(
                'id'=>$data['id']
            )
        );
        return "
        <div class='session_container'>
            <div class='session_header'>
                <div class='session_type'>{$data['type']}</div>
                <div class='session_info'>
                    <div>
                        <div><img src='".URL_TO_IMG . 'clock_bk.png'."'/></div>
                        <div>" . date('H:i', strtotime($data['start_time']))
                         . '-' . date('H:i', strtotime($data['end_time'])) . "</div>
                    </div>
                    <div>
                        <div><img src='".URL_TO_IMG . 'location_bk.png'."'/></div>
                        <div>{$data['room']}</div>
                    </div>
                </div>      
            </div>

            <div class='session_content' id='session_{$data['id']}'>
                <a href='' class='loadContent' data-url='{$url}' 
                data-destination='.session_content#session_{$data['id']}'>
                Click here to edit this session
                </a>
            </div>
        </div>
        ";
    }

    /**
     * Render session slot
     *
     * @param array $data
     *
     * @return string
     */
    public static function sessionContainer(array $data, $content)
    {
        return "
            <div class='session_container'>
                <div class='session_header'>
                    <div class='session_type'>{$data['type']}</div>
                    <div class='session_info'>
                        <div>
                            <div><img src='".URL_TO_IMG . 'clock_bk.png'."'/></div>
                            <div>" . date('H:i', strtotime($data['start_time']))
                             . '-' . date('H:i', strtotime($data['end_time'])) . "</div>
                        </div>
                        <div>
                            <div><img src='".URL_TO_IMG . 'location_bk.png'."'/></div>
                            <div>{$data['room']}</div>
                        </div>
                    </div>      
                </div>
    
                <div class='session_content'>
                    {$content}
                </div>
            </div>
             ";
    }

    /**
     * Show presentation slot as empty
     *
     * @param array $data : session data
     * @param bool $show_button: display add button
     *
     * @return string
     */
    public static function emptySlotContainer(array $data, $show_button = true)
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
        return self::slotContainer(array('name'=>'Free slot', 'button'=>$content, 'content'=>null));
    }

    /**
     * Get session editor (form)
     *
     * @param array $data
     * @param [type] $date
     * @return void
     */
    private static function getSessionEditor(array $data, $date)
    {
        return self::form(
            $data,
            self::getSessionContent(
                $data,
                $date
            ),
            self::factory()->getSettings('default_type')
        );
    }

    /**
     * Get and render session content
     *
     * @param array $data: session data
     * @param string $date: selected date
     * @param bool $edit: Get editor or viewer
     *
     * @return string
     */
    private static function getSessionContent(array $data, $date)
    {
        $content = null;
        if (!isset($data['content'])) {
            return $content;
        }

        $nSlots = max(count($data['content']), $data['slots']);
        for ($i=0; $i<$nSlots; $i++) {
            if (isset($data['content'][$i]) && !is_null($data['content'][$i]['id'])) {
                $content .= self::slotContainer(Presentation::inSessionSimple($data['content'][$i]));
            } else {
                $content .= self::emptySlotContainer($data, SessionInstance::isLogged());
            }
        }
        return $content;
    }
  
    /**
     * Renders email notifying presentation assignment
     * @param Users $user
     * @param array $info: array('type'=>session_type,'date'=>session_date, 'presid'=>presentation_id)
     * @param bool $assigned
     * @return mixed
     */
    public static function notifyUpdate(Users $user, array $info, $assigned = true)
    {
        $MailManager = new MailManager();
        if ($assigned) {
            $dueDate = date('Y-m-d', strtotime($info['date'].' - 1 week'));
            $content = self::invitationEmail(
                $user->username,
                $user->fullname,
                $dueDate,
                $info['date'],
                $info['type'],
                $info['presid']
            );
        } else {
            $content = self::cancelationUserEmail($user->fullname, $info['date']);
        }

        // Notify organizers of the cancellation but only for real users
        if (!$assigned && $user->username !== 'TBA') {
            self::notifyOrganizers($user, $info);
        }

        // Send email
        $content['emails'] = $user->id;
        $result = $MailManager->addToQueue($content);
        return $result;
    }

    /**
     * Notify organizers that a presentation has been manually canceled
     * @param Users $user
     * @param array $info
     * @return mixed
     */
    public static function notifyOrganizers(Users $user, array $info)
    {
        $MailManager = new MailManager();
        foreach ($user->getAdmin() as $key => $userInfo) {
            $content = self::cancelationOrganizerEmail($userInfo['fullname'], $user->fullname, $info['date']);
            $content['emails'] = $userInfo['id'];
            if (!$MailManager->addToQueue($content)) {
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
    public static function cancelSession(Session $session)
    {
        $assignment = new Assignment();
        $result = true;

        // Loop over presentations scheduled for this session
        foreach ($session->presids as $id) {
            $pres = new Presentation($id);
            $speaker = new Users($pres->orator);

            // Delete presentation and notify speaker that his/her presentation has been canceled
            if ($result = $pres->delete_pres($id)) {
                $info = array(
                    'speaker'=>$speaker->username,
                    'type'=>$session->type,
                    'presid'=>$pres->id,
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
        if (self::modifyAssignments(self::get(array('id'=>$id), $type))) {
            $result = self::update(array('type'=>$type), array('id'=>$id));
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
        foreach ($data['presids'] as $id) {
            $pres = new Presentation($id);
            $speaker = new Users($pres->orator);

            // Unassign
            $info = array(
                'speaker'=>$speaker->username,
                'type'=>$previous_type,
                'presid'=>$pres->id,
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
            $contactURL = URL_TO_APP . "index.php?page=contact";

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
            $content['emails'] = $speaker->id;
            $result = $MailManager->addToQueue($content);
        }
        return $result;
    }

    /**
     * Modify speaker associated with a presentation and send notification email to previous and new speakers
     *
     * @param array $data: array('presid'=>presentation id, 'speaker'=>username of new speaker)
     * @return array
     */
    public function modifySpeaker(array $data)
    {

        // Get presentation info
        $Presentation = new \includes\Presentation();
        $presData = $Presentation->get(array('id'=>$data['presid']));

        // Get previous speaker info
        $previous = new Users($presData['username']);

        // get new speaker info
        $speaker = new Users($data['speaker']);

        // Get session info
        $session = new Session();
        $sessionData = $session->get(array('id'=>$presData['session_id']));

        // Updated info
        $info = array(
            'type'=>$sessionData['type'],
            'date'=>$sessionData['date'],
            'presid'=>$data['presid']
        );

        $Assignment = new Assignment();

        // Send notification to previous speaker
        if (!is_null($previous->username)) {
            // Only send notification to real users
            $result['status'] = $Assignment->updateAssignment($previous, $info, false, true);
        } else {
            $result['status'] = true;
        }

        // Send notification to new speaker
        if ($result['status']) {
            if (!is_null($speaker->username)) {
                // Only send notification to real users
                $result['status'] = $Assignment->updateAssignment($speaker, $info, true, true);
            } else {
                $result['status'] = true;
            }

            // Update Presentation info
            if ($result['status']) {
                if ($Presentation->update(array('username'=>$speaker->username), array('id'=>$data['presid']))) {
                    $result['msg'] = "{$speaker->fullname} is the new speaker!";
                    $result['status'] = true;
                } else {
                    $result['status'] = false;
                }
            }
        }

        // Notify previous speaker
        $this->notifyUpdate($previous, $info, $assigned = false);

        // Notify previous speaker
        $this->notifyUpdate($speaker, $info, $assigned = true);

        return $result;
    }

    /**
     * Render session viewer
     * @param string $sessions: list of sessions
     * @return string
     */
    public static function layout($sessions, $selectedDate = null)
    {
        return "
        <div class='section_content'>
            <div id='dateInput'>". self::dateInput($selectedDate) . "</div>
            <div id='session_editor'>{$sessions}</div>
        </div>
        ";
    }

    private static function dateInput($selectedDate = null)
    {
        $url = Router::buildUrl('SessionManager', 'getCalendarContent');
        return "
        <div class='form-group'>
            <input type='date' class='selectSession datepicker viewerCalendar' data-url='{$url}'
            name='date' value='{$selectedDate}' data-destination='#session_editor'/>
            <label>Select session</label>
        </div>";
    }

        /**
     * Render day container
     *
     * @param array $data
     *
     * @return string
     */
    public static function dayContainer(array $data)
    {
        $date = date('d M Y', strtotime($data['date']));
        return "
            <div class='day_container'>
                <!-- Day header -->
                <div class='day_header'>
                    <div class='day_date'>{$date}</div>
                </div>
                
                <!-- Day content -->
                <div class='day_content'>{$data['content']}</div>
            </div>";
    }

    /**
     * Template for slot container
     *
     * @param array $data
     * @param null|string $div_id: container id
     *
     * @return string
     */
    public static function slotContainer(array $data, $div_id = null)
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
     * Render form to add session
     * @param array $data
     * @param $default_type
     * @return string
     */
    public static function form(array $data, $slots = null, $default_type = null)
    {
        // Select of input for session type
        $selectedType = (!empty($data['session_type'])) ? $data['session_type'] : $default_type;
        $type_list = TypesManager::getTypeSelectInput('Session', $selectedType);

        // Submit buttons
        if (isset($data['id']) && !is_null($data['id'])) {
            // Form action url
            $url = Router::buildUrl(
                'Session',
                'updateSession'
            );

            // Submit buttons
            $addButton = "<input type='submit' value='Add' class='processform' />";
            $deleteButton = "<input type='submit' value='Delete' class='delete_session' data-controller='Session' 
            data-action='delete' data-id='{$data['id']}' />";
            $modifyButton = "<input type='submit' class='modify_session' value='Modify' />";
            $buttons = "
            {$modifyButton}
            {$deleteButton}
            ";
        } else {
            // Form action url
            $url = Router::buildUrl(
                'Session',
                'make'
            );
            $buttons = "<input type='submit' class='modify_session' value='Create session' />";
            $data['id'] = false;
            $data['event_id'] = false;
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
                " . self::repeatInput($data) . "
                
                <div class='submit_btns'>
                    <input type='hidden' name='start_date' value='{$data['date']}' />
                    <input type='hidden' name='id' value='{$data['id']}' />
                    <input type='hidden' name='event_id' value='{$data['event_id']}' />
                    {$buttons}
                </div>
            </div>
        </form>
        {$sessionContent}
        ";
    }

    /**
     * Render 'Repeat' selection input
     *
     * @param array $data
     *
     * @return string
     */
    private static function repeatInput(array $data)
    {
        $values = array('Yes'=>1, 'No'=>0);
        $options = "";
        foreach ($values as $label => $value) {
            $selected = (int)$data['recurrent'] === $value ? 'selected' : null;
            $options .= "<option value='{$value}' {$selected}>{$label}</option>";
        }
        
        // Hide associated inputs
        $hide = $data['recurrent'] === '1' ? null : "style='display: none;'";

        return "
        <div class='form-group field_small inline_field'>
            <select name='recurrent' class='repeated_session'>
                {$options}
            </select>
            <label>Repeat</label>
        </div>
        <div class='form-group field_small inline_field settings_hidden' {$hide}>
            <input type='date' class='datepicker viewerCalendar' name='end_date' value='{$data['end_date']}' />
            <label>End date</label>
        </div>
        <div class='form-group field_small inline_field settings_hidden' {$hide}>
            <input type='number' name='frequency' value='{$data['frequency']}' />
            <label>Frequency (day)</label>
        </div>
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
     * Render waring message
     *
     * @return string
     */
    private static function selectADate()
    {
        return "<div class='sys_msg status'>Please, select a session to edit</div>";
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
    private static function invitationEmail($username, $fullname, $dueDate, $date, $session_type, $presId)
    {
        $contactURL = URL_TO_APP."index.php?page=contact";
        $editUrl = URL_TO_APP."index.php?page=submission&op=edit&id={$presId}&user={$username}";
        return array(
            'body'=> "<div style='width: 100%; margin: auto;'>
                    <p>Hello {$fullname},</p>
                    <p>You have been automatically invited to present at a 
                    <span style='font-weight: 500'>{$session_type}</span> 
                    session on the <span style='font-weight: 500'>{$date}</span>.</p>
                    <p>Please, submit your presentation on the Journal Club Manager before the 
                    <span style='font-weight: 500'>{$dueDate}</span>.</p>
                    <p>If you think you will not be able to present on the assigned date, please 
                    <a href='{$contactURL}'>contact</a> the organizers as soon as possible.</p>
                    <div>
                        You can edit your presentation from this link: <a href='{$editUrl}'>{$editUrl}</a>
                    </div>
                </div>
            ",
            'subject'=> "Invitation to present on the {$date}"
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
                <p>Hello {$fullname},</p>
                <p>This is to inform you that the presentation of 
                <strong>{$speaker}</strong> planned on the <strong>{$date}</strong> has been canceled. 
                You can either manually assign another speaker on this day in the 
                <a href='{$url}'>Admin>Session</a> section or let the automatic 
                assignment select a member for you.</p>
                </div>
            ",
            'subject'=>"A presentation ($date) has been canceled"
        );
    }
}
