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
        if (is_null($date) || is_array($date)) {
            $data = self::factory()->getUpcoming(1);
            if (empty($data)) {
                return self::selectADate();
            } else {
                $date = reset($data)['date'];
            }
        }
        // Repeat sessions
        self::factory()->repeatAll($date);

        return self::getDayContent(self::factory()->all(array('s.date'=>$date)), $date, false);
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
    public static function loadSessionEditor($id = null)
    {
        if (is_array($id)) {
            // Get next session
            $data = self::factory()->getUpcoming(1);
            $id = reset($data)['id'];
        }
        $data = self::factory()->all(array('s.id'=>$id));
        $data = $data[$id];
        return self::getSessionEditor($data, $data['date']);
    }

     /**
     * Update session information
     *
     * @return array
     */
    public function updateSession(array $data)
    {
        $session_id = $data['id'];
        $operation = $data['operation'];
        $date = $data['date'];

        $session = new Session();
        $sessionData = $session->getInfo(array('id'=>$session_id));

        unset($data['date']);
        unset($data['operation']);
        unset($data['id']);
        $result = array('status'=>false, 'msg'=>null);

        if ($operation === 'present') {
            // Only update the current event
            $result['status'] = $this->notifyWrapper('update', $sessionData, $data);
        } elseif ($operation === 'future') {
            // Update all future occurences
            $result['status'] = $this->updateAllEvents($data, $session_id, 'future');
        } elseif ($operation === 'all') {
            // Update all (past/future) occurences
            $result['status'] = $this->updateAllEvents($data, $session_id, 'all');
        } else {
            Logger::getInstance(
                APP_NAME,
                get_class($this)
            )->error("SessionManager::updateSession(): '{$operation}' is not a valid operation");
        }

        if ((int)$data['recurrent'] == 1) {
            $data = $session->get(array('id'=>$session_id));
            $session->repeat($data, $session->getSettings('max_nb_session'));
        }

        $result['msg'] = $result['status'] ? "Session has been modified" : 'Something went wrong';
        return $result;
    }

    /**
     * Update all or only upcoming occurences of an event
     *
     * @param array $post: Updated information
     * @param string $id: id of current occurrence
     * @param string $what: 'future': only upcoming occurences, 'all': past and future occurences
     *
     * @return bool
     */
    public function updateAllEvents(array $post, $id, $what)
    {
        $session = new Session();
        // Get event id
        $data = $session->getInfo(array('id'=>$id));

        if ($what === 'future') {
            $all = $session->all(
                array('event_id'=>$data['event_id'],
                'date >='=>$data['date'])
            );
        } else {
            $all = $session->all(
                array('event_id'=>$data['event_id'])
            );
        }

        if (!empty($all)) {
            foreach ($all as $key => $item) {
                if (!$this->notifyWrapper('update', $item, $post)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Delete current and related sessions
     *
     * @param string $id: session id
     * @param string $operation: 'present', 'future', 'all'
     *
     * @return array
     */
    public function deleteSession($id, $operation)
    {
        $result = array('status'=>false, 'msg'=>null);
        $session = new Session();
        $sessionData = $session->getInfo(array('id'=>$id));
        if ($operation === 'present') {
            // Only update the current event
            $result['status'] = $this->notifyWrapper('delete', $sessionData);
        } elseif ($operation === 'future') {
            // Update all future occurrences
            $result['status'] = $this->deleteAllEvents($id, 'future');
        } elseif ($operation === 'all') {
            // Update all (past/future) occurrences
            $result['status'] = $this->deleteAllEvents($id, 'all');
        } else {
            Logger::getInstance(
                APP_NAME,
                get_class($this)
            )->error("Session::deleteSession(): '{$operation}' is not a valid operation");
        }
        $result['msg'] = $result['status'] ? "Session has been deleted" : 'Something went wrong';
        return $result;
    }

    /**
     * Delete all or only upcoming occurences of an event
     *
     * @param $id: id of current occurence
     * @param $what: 'future': only upcoming occurences, 'all': past and future occurences
     * @return bool
     */
    public function deleteAllEvents($id, $what)
    {
        // Get event id
        $session = new Session();
        $data = $session->getInfo(array('id'=>$id));
        if ($what === 'future') {
            $all = $session->all(
                array('event_id'=>$data['event_id'],
                'date >='=>$data['date'])
            );
        } else {
            $all = $session->all(
                array('event_id'=>$data['event_id'])
            );
        }

        if (!empty($all)) {
            foreach ($all as $key => $item) {
                if (!$this->notifyWrapper('delete', $item)) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Update/delete session and notify
     *
     * @param string $action: update/delete
     * @param array $sessionData: session data
     * @param array $post: new session data
     * @return bool: success or failure
     */
    private function notifyWrapper($action, array $sessionData, array $post = null)
    {
        $session = new Session;
        switch ($action) {
            case 'update':
                if ($sessionData['type'] !== $post['type']) {
                    if (!$this->modifyAssignments($sessionData, $post['type'])) {
                        return false;
                    }
                }
                if ($session->update($post, array('id'=>$sessionData['id']))) {
                    // Notify user about the change of session type
                    if (!self::notifyUpdate($action, 'speaker', $sessionData, $post)) {
                        return false;
                    }
                    return self::notifyUpdate($action, 'organizer', $sessionData, $post);
                } else {
                    return false;
                }
                break;
            case 'delete':
                if ($this->deleteAssignments($sessionData)) {
                    if ($session->delete(array('id'=>$sessionData['id']))) {
                        // Notify user about the cancelation of session
                        if (!self::notifyUpdate($action, 'speaker', $sessionData)) {
                            return false;
                        }
                        return self::notifyUpdate($action, 'organizer', $sessionData);
                    } else {
                        return false;
                    }
                } else {
                    return false;
                }
                break;
            default:
                return false;
                break;
        }
    }

    /**
     * Unassign presentations corresponding to session
     *
     * @param array $data: session info
     * @return bool: success or failure
     */
    public function deleteAssignments(array $data) {
        $assignment = new Assignment();
        $session_type = $data['type'];

        // Loop over presentations scheduled for this session
        foreach ($data['presids'] as $id) {
            $pres = new Presentation($id);
            $user = new Users($pres->orator);
            $userData = $user->get(array('username'=>$pres->orator));

            // Unassign
            $info = array(
                'speaker'=>$userData['username'],
                'type'=>$session_type,
                'presid'=>$pres->id,
                'date'=>$data['date']
            );

            // Update assignment table
            if (!$assignment->updateAssignment($userData['username'], $info, false, false)) {
                return false;
            };
        }
        return true;
    }
    
    /**
     * Modify session type and notify speakers about the change
     * @param array $data
     * @param $new_type
     * @return bool
     */
    public function modifyAssignments(array $data, $new_type = null)
    {
        $assignment = new Assignment();
        $result = true;

        $previous_type = $data['type'];

        // Loop over presentations scheduled for this session
        foreach ($data['presids'] as $id) {
            $pres = new Presentation($id);
            $user = new Users($pres->orator);
            $userData = $user->get(array('username'=>$pres->orator));

            // Unassign
            $info = array(
                'speaker'=>$userData['username'],
                'type'=>$previous_type,
                'presid'=>$pres->id,
                'date'=>$data['date']
            );

            // Update assignment table
            if ($assignment->updateAssignment($userData['username'], $info, false, false)) {
                if (!is_null($new_type)) {
                    // Update assignment table with new session type
                    $info['type'] = $new_type;
                    $result = $assignment->updateAssignment($userData['username'], $info, true, false);
                }
            }
        }
        return $result;
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
     * Send notification email to organizer about session cancelation
     *
     * @param array $session
     * @return bool
     */
    private static function notifyUpdate($action, $recipients, array $session, array $post = null)
    {
        $users = new Users();
        $MailManager = new MailManager();
        switch ($recipients) {
            case 'organizer':
                $recipientsData = $users->getAdmin(false);
                break;
            case 'speaker':
                $recipientsData = array();
                foreach ($session['usernames'] as $username) {
                    $recipientsData[] = $users->get(array('username'=>$username));
                }
                break;
            case 'all':
                $recipientsData = array();
                foreach ($session['usernames'] as $username) {
                    $recipientsData[$username] = $users->all();
                }
                break;
        }

        foreach ($recipientsData as $key => $userInfo) {
            $toSpeaker = in_array($userInfo['username'], $session['usernames']);
            if ($action == 'delete') {
                $content = self::cancelationEmail($userInfo['fullname'], $session['type'], $session['date'], $toSpeaker);
                $content['emails'] = $userInfo['id'];
            } elseif ($action == 'update') {
                $content = self::modificationEmail($userInfo['fullname'], $session, $post, $toSpeaker);
                $content['emails'] = $userInfo['id'];
            }
            if (!$MailManager->addToQueue($content)) {
                return false;
            }
        }
        return true;
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
        if ($this->modifyAssignments(self::get(array('id'=>$id), $type))) {
            $result = $this->update(array('type'=>$type), array('id'=>$id));
            if ($result['status']) {
                $result['msg'] = "Session's type has been set to {$value}";
            }
        }
        return $result;
    }

    /**
     * View for email notifying user about modification of session type
     *
     * @param array $user: user info
     * @param string $previous_type
     * @param string $new_type
     * @param string $date
     * @return array
     */
    private static function modificationSessionTypeEmail(array $user, $previous_type, $new_type, $date)
    {
        $contactURL = URL_TO_APP . "index.php?page=contact";
        return array(
            'body' => "
                <div style='width: 100%; margin: auto;'>
                    <p>Hello {$user['fullname']},</p>
                    <p>This is to inform you that the type of your session ({$date}) 
                    has been modified and will be a <strong>{$new_type}</strong> instead of a 
                    <strong>{$previous_type}</strong>.</p>
                    <p>If you need more information, please <a href='$contactURL'>contact</a> the organizers.</p>
                </div>
                ",
                'subject' => "Your session ($date) has been modified",
                'emails' => $user['id']
            );
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

        $user = new Users();
        // Get previous speaker info
        $previous = $user->get(array('username' => $presData['username']));

        // get new speaker info
        $speaker = $user->get(array('username' => $data['speaker']));

        // Get session info
        $session = new Session();
        $sessionData = $session->get(array('id'=>$presData['session_id']));

        // Updated info
        $info = array(
            'type'=>$sessionData['type'],
            'date'=>$sessionData['date'],
            'presid'=>$data['presid']
        );

        $assignment = new Assignment();

        // Send notification to previous speaker
        if (!is_null($previous['username'])) {
            // Only send notification to real users
            $result['status'] = $assignment->updateAssignment($previous['username'], $info, false, false);
        } else {
            $result['status'] = true;
        }

        // Send notification to new speaker
        if ($result['status']) {
            if (!is_null($speaker['username'])) {
                // Only send notification to real users
                $result['status'] = $assignment->updateAssignment($speaker['username'], $info, true, false);
            } else {
                $result['status'] = true;
            }

            // Update Presentation info
            if ($result['status']) {
                if ($Presentation->update(array('username'=>$speaker['username']), array('id'=>$data['presid']))) {
                    $result['msg'] = "{$speaker['fullname']} is the new speaker!";
                    $result['status'] = true;
                } else {
                    $result['status'] = false;
                }
            }
        }

        // Notify previous speaker
        $assignment::notifyUpdate($previous['username'], $info, $assigned = false);

        // Notify previous speaker
        $assignment::notifyUpdate($speaker['username'], $info, $assigned = true);

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
                'SessionManager',
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
     * Content of session cancelation email
     *
     * @param string $fullname: user's full name
     * @param string $date: date of presentation
     *
     * @return array: array('body'=>content of email, 'subject'=>email's title)
     */
    private static function cancelationEmail($fullname, $type, $date, $toSpeaker = false)
    {
        $url = URL_TO_APP . 'index.php?page=organizer/sessions';

        if ($toSpeaker) {
            $content = "<p>This is to inform you that your assignment for the <strong>{$type}</strong> session 
            planned on the <strong>{$date}</strong> has been canceled.</p>";
            $subject = "Your assignment ({$date}) has been canceled";
        } else {
            $content = "<p>This is to inform you that the {$type} session planned on the <strong>{$date}</strong>
            has been canceled.</p>";
            $subject = "A session ({$date}) has been canceled";
        }
        return array(
            'body'=>"
                <div style='width: 100%; margin: auto;'>
                    <p>Hello {$fullname},</p>
                    {$content}
                </div>
            ",
            'subject'=>$subject
        );
    }

    /**
     * Content of session update email
     *
     * @param string $fullname: user's full name
     * @param string $date: date of presentation
     *
     * @return array: array('body'=>content of email, 'subject'=>email's title)
     */
    private static function modificationEmail($fullname, array $sessionData, array $post, $toSpeaker = false)
    {
        $url = URL_TO_APP . 'index.php?page=organizer/sessions';

        $css = array();
        $data = $sessionData;
        foreach ($sessionData as $key => $value) {
            if (isset($post[$key]) && $post[$key] !== $sessionData[$key]) {
                $css[$key] = "font-weight: 500; color:rgba(207,81,81,1);";
                $data[$key] = $post[$key];
            } else {
                $css[$key] = null;
            }
        }

        $content = "
            <div style='background-color: rgba(255,255,255,.5); padding: 5px; margin-bottom: 10px;'>
                <div style='margin: 0 5px 5px 0; {$css['type']}'><b>Type: </b>{$data['type']}</div>
                <div style='display: inline-block; margin: 0 0 5px 0; {$css['date']}'><b>Date: </b>{$data['date']}</div>
                <div style='display: inline-block; margin: 0 5px 5px 0;'>
                    <span style='{$css['start_time']}'><b>From: </b>{$data['start_time']}</span>
                    <span style='{$css['end_time']}'><b> To: </b>{$data['end_time']}</span>
                </div>
                <div style='display: inline-block; margin: 0 5px 5px 0; {$css['room']}'>
                    <b>Room: </b>{$data['room']}
                </div>
            </div>";

        if ($toSpeaker) {
            $paragraph = "<p>This is to inform you that your <strong>{$data['type']}</strong> session planned on the 
            <strong>{$data['date']}</strong> has been updated.</p>";
            $subject = "Your assignment ({$data['date']}) has been updated";
        } else {
            $paragraph = "<p>This is to inform you that the {$data['type']} session planned on the 
            <strong>{$data['date']}</strong> has been updated.</p>";
            $subject = "A session ({$data['date']}) has been updated";
        }

        return array(
            'body'=>"
                <div style='width: 100%; margin: auto;'>
                    <p>Hello {$fullname},</p>
                    {$paragraph}
                    <div>{$content}</div>
                </div>
            ",
            'subject'=>$subject
        );
    }
}
