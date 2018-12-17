<?php
/**
 * File for classes Presentations and Presentation
 *
 * PHP version 5
 *
 * @author Florian Perdreau (fp@florianperdreau.fr)
 * @copyright Copyright (C) 2014 Florian Perdreau
 * @license <http://www.gnu.org/licenses/agpl-3.0.txt> GNU Affero General Public License v3
 *
 * This file is part of Journal Club Manager.
 *
 * Journal Club Manager is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Journal Club Manager is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with Journal Club Manager.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace includes;

use Exception;

/**
 * class Presentations.
 *
 * Handle methods to display presentations list (archives, homepage, wish list)
 */
class Presentation extends BaseSubmission
{

    public $id;
    public $type;
    public $date;
    public $jc_time;
    public $up_date;
    public $username;
    public $title;
    public $keywords;
    public $authors;
    public $summary;
    public $media;
    public $orator;
    public $chair;
    public $notified;
    public $session_id;

    private static $default = array();

    // CONTROLLER
    
    /**
     * Add a presentation to the database
     * @param array $data
     * @return bool|string
     * @throws Exception
     */
    public function make(array $data)
    {
        $Session = new Session();
        if (!$Session->isFull($data['session_id'])) {
            if ($data['title'] === 'TBA' || $this->isExist(array('title'=>$data['title'])) === false) {
                // Upload datetime
                $data['up_date'] = date('Y-m-d h:i:s');

                // Add publication to the database
                if ($this->db->insert($this->tablename, $this->parseData($data, array("media")))) {
                    $data['id'] = $this->db->getLastId();
                } else {
                    return false;
                }
                
                // Associates this presentation to an uploaded file if there is one
                if (!empty($data['media'])) {
                    $media = new Media();
                    if (!$media->addUpload(explode(',', $data['media']), $data['id'], self::getClassName())) {
                        return false;
                    }
                }
                return $data['id'];
            } else {
                return "exist";
            }
        } else {
            return 'booked';
        }
    }

    /**
     * Render list with all next user's presentations
     * @param $username: user name
     * @param string $filter: 'previous' or 'next'
     * @return null|string
     */
    public function getUserPresentations($username, $filter = 'next')
    {
        $content = null;
        $search = $filter == 'next' ? 'date >' : 'date <';
        foreach ($this->all(array('username'=>$username, $search=>'CURDATE()')) as $key => $item) {
            $content .= $this->show($item['id'], $username);
        }
        return $content;
    }

    /**
     * Get user's publications list
     * @param string $username
     * @return array
     */
    public function getList($username)
    {
        return $this->all(array('username'=>$username));
    }

    /**
     * Check if presentation exists in the database
     * @param $title
     * @return bool
     * @throws Exception
     */
    // private function isExist($title)
    // {
    //     $titlelist = $this->db->column($this->tablename, 'title');
    //     return in_array($title, $titlelist);
    // }

    /**
     * Show this presentation (in archives)
     * @param $id: presentation id
     * @param bool $profile : adapt the display for the profile page
     * @return string
     */
    public function show($id, $profile = false)
    {
        $data = $this->getInfo($id);
        if ($profile === false) {
            $speaker = new Users($this->orator);
            $speakerDiv = "<div class='pub_speaker warp'>$speaker->fullname</div>";
        } else {
            $speakerDiv = "";
        }
        return self::showInList((object)$data, $speakerDiv);
    }

    // MODEL

    /**
     * Get latest submitted presentations
     * @return array
     */
    public function getLatest()
    {
        $publicationList = array();
        foreach ($this->all(array('notified'=>0, 'title !='=>'TBA')) as $key => $item) {
            $publicationList[] = $item['id'];
        }
        return $publicationList;
    }

    /**
     * Get publications by date
     * @param bool $excluded
     * @return array
     */
    public function getByDate($excluded = false)
    {
        // Get presentations dates
        $sql = "SELECT date,id FROM $this->tablename";
        if ($excluded !== false) {
            $sql .= " WHERE type!='$excluded'";
        }
        $req = $this->db->sendQuery($sql);
        $dates = array();
        while ($row = mysqli_fetch_assoc($req)) {
            $dates[$row['date']][] = $row['id'];
        }
        return $dates;
    }

    // VIEW

    /**
     * Render list of available speakers
     *
     * @param string $cur_speaker: username of currently assigned speaker
     * @param bool $selectable: is input selectable
     *
     * @return string
     */
    public static function speakerList($cur_speaker = null, $selectable = false, $modifiable = false, $idPres = null)
    {
        if (is_null($cur_speaker)) {
            $cur_speaker = $_SESSION['username'];
        }
        $Users = new Users();

        // Render list of available speakers
        $speakerOpt = (is_null($cur_speaker)) ? "<option selected disabled>Select a speaker</option>" : null;
        foreach ($Users->getAll(true) as $key => $speaker) {
            $selectOpt = ($speaker['username'] == $cur_speaker) ? 'selected' : null;
            $speakerOpt .= "<option value='{$speaker['username']}' {$selectOpt}>{$speaker['fullname']}</option>";
        }

        $select = $selectable ? null : 'disabled';
        $class = $modifiable ? "class='modSpeaker'" : null;
        $dataId = !is_null($idPres) ? "data-id={$idPres}" : null;
        return "
                <select {$class} {$dataId} name='orator' {$select}>
                    {$speakerOpt}
                </select>
                <label>Speaker</label>
                ";
    }

    /**
     * Show editable publication information in session list
     * @param array $data
     * @return array
     */
    public static function inSessionEdit(array $data)
    {
        $leanModalUrl = Router::buildUrl(
            self::getClassName(),
            'showDetails',
            array(
            'view'=>'modal',
            'id'=>$data['id'])
        );
        $view_button = "<a href='#' class='leanModal pub_btn icon_btn' 
        data-url='{$leanModalUrl}' data-section='presentation' data-title='Presentation'>
        <img src='" . URL_TO_IMG . 'view_bk.png' . "' /></a>";
        $selectable = Account::isAuthorized($_SESSION['username'], 'organizer');
        return array(
            "content"=>"  
                <div style='display: block !important;'>{$data['title']}</div>
                <div>
                    <span style='font-size: 12px; font-style: italic;'>Presented by </span>
                    <div class='form-group field_small inline_field' 
                    style='font-size: 14px; font-weight: 500; color: #777;'>
                    {$data['fullname']}</div>
                </div>
                ",
            "name"=>$data['type'],
            "button"=>$view_button
            );
    }

    /**
     * Render (clickable) presentation title.
     * @param array $data
     * @return string
     */
    private static function renderTitle(array $data)
    {
        $url = URL_TO_APP . "index.php?page=presentation&id=" . $data['id'];
        $leanModalUrl = Router::buildUrl(
            self::getClassName(),
            'showDetails',
            array(
            'view'=>'modal',
            'id'=>$data['id'])
        );
        return "<a href='{$url}' class='leanModal' data-url='{$leanModalUrl}' data-section='presentation' 
            data-title='Submission'>{$data['title']}</a>";
    }

    /**
     * Render short description of presentation in session list
     * @param array $data
     * @return array
     */
    public static function inSessionSimple(array $data)
    {
        $show_but = self::renderTitle($data);
        $Bookmark = new Bookmark();
        return array(
            "name"=>$data['type'],
            "content"=>"
            <div style='display: block !important;'>{$show_but}</div>
            <div>
                <span style='font-size: 12px; font-style: italic;'>Presented by </span>
                <span style='font-size: 14px; font-weight: 500; color: #777;'>{$data['fullname']}</span>
            </div>",
            "button"=>$Bookmark->getIcon(
                $data['id'],
                'Presentation',
                SessionInstance::isLogged() ? $_SESSION['username'] : null
            )
        );
    }

    /**
     * Show presentation details
     * @param array $data: presentation information
     * @param bool $show : show list of attached files
     * @return string
     */
    public static function mailDetails(array $data, $show = false)
    {
        // Make download menu if required
        $file_div = $show ? Media::downloadMenuEmail($data['media'], App::getAppUrl()) : null;

        // Format presentation's type
        $type = ucfirst($data['type']);

        // Abstract
        $abstract = (!empty($data['summary'])) ? "
            <div style='width: 95%; box-sizing: border-box; border-top: 3px solid rgba(207,81,81,.5); 
            text-align: justify; margin: 5px auto; 
            padding: 10px;'>
                <span style='font-style: italic; font-size: 13px;'>{$data['summary']}</span>
            </div>
            " : null;

        // Build content
        $content = "
        <div style='width: 100%; padding-bottom: 5px; margin: auto auto 10px auto; 
        background-color: rgba(255,255,255,.5); border: 1px solid #bebebe;'>
            <div style='display: block; margin: 0 0 15px 0; padding: 0; text-align: justify; 
            min-height: 20px; height: auto; line-height: 20px; width: 100%;'>
                <div style='vertical-align: top; text-align: left; margin: 5px; font-size: 16px;'>
                    <span style='color: #222; font-weight: 900;'>{$type}</span>
                    <span style='color: rgba(207,81,81,.5); font-weight: 900; font-size: 20px;'> . </span>
                    <span style='color: #777; font-weight: 600;'>{$data['fullname']}</span>
                </div>
            </div>
            <div style='width: 100%; text-align: justify; margin: auto; box-sizing: border-box;'>
                <div style='max-width: 80%; margin: 5px;'>
                    <div style='font-weight: bold; font-size: 20px;'>{$data['title']}</div>
                </div>
                <div style='margin-left: 5px; font-size: 15px; font-weight: 400; font-style: italic;'>
                    {$data['authors']}
                </div>
            </div>
           {$abstract}
           {$file_div}
        </div>
        ";
        return $content;
    }

    /**
     * Get default information
     *
     * @return array
     */
    private function getDefaults()
    {
        $properties = get_class_vars(\get_class($this));
        $data = array();
        foreach ($properties as $prop => $value) {
            $data[$prop] = $value;
        }
        $data['media'] = array();
        return $data;
    }

    /**
     * Render submission editor
     * @param array|null $post
     * @return array
     */
    public function editor(array $data = null, $session_id = null)
    {
        $Session = new Session();

        // Get presentation id (if none, then this is a new presentation)
        $id_Presentation = isset($data['id']) ? $data['id'] : false;

        // Get presentation information
        $presentationData = $this->getInfo($id_Presentation);
        if (!$presentationData) {
            $presentationData = $this->getDefaults();
        } else {
            $data['session_id'] = $presentationData['session_id'];
        }

        // Get presentation date, and if not present, then automatically get next planned session date.
        if (!isset($data['session_id']) || is_null($data['session_id'])) {
            $next = $Session->getUpcoming(1);
            $next = reset($next);
            $data['date'] = $next['date'];
            $data['session_id'] = $next['id'];
        } else {
            // Get session date
            $session_data = $Session->get(array('id'=>$data['session_id']));
            $data['date'] = $session_data['date'];
        }

        // Get operation type
        $operation = (!empty($data['operation']) && $data['operation'] !== 'false') ? $data['operation'] : 'edit';

        // Get presentation type
        $type = (!empty($data['type']) && $data['type'] !== 'false') ? $data['type'] : null;

        return Presentation::form(
            new Users($_SESSION['username']),
            (object)$presentationData,
            $operation,
            $data,
            Account::isAuthorized($_SESSION['username'], 'organizer')
        );
    }

    /**
     * Submission form instruction
     * @return string
     */
    public static function description()
    {
        return "
            Book a Journal Club session to present a paper, your research, or a
            methodology topic. <br>
            Fill in the form below, select a date (only available dates can be selected) and it's all done!
            Your submission will be automatically added to our database.<br>
            If you want to edit or delete your submission, you can find it on your 
            <a href='index.php?page=member/profile'>profile page</a>!
        ";
    }

    /**
     * Generate submission form and automatically fill it up with data provided by Presentation object.
     * @param Users $user
     * @param \StdClass $Presentation
     * @param string $operation
     * @param bool $type
     * @param array $data
     *
     * @return array
     */
    public static function form(
        Users $user,
        $Presentation = null,
        $operation = "edit",
        array $data = null,
        $organizer = true
    ) {

        // Presentation date
        $date = array_key_exists('date', $data) ? $data['date'] : $Presentation->date;

        // Session id assigned to current presentation
        $session_id = array_key_exists('session_id', $data) ? $data['session_id'] : $Presentation->session_id;

        // Get class of instance
        $controller = !empty($data['controller']) ? $data['controller'] : self::getClassName();

        // Presentation ID
        $idPres = ($Presentation->id != "") ? $Presentation->id : 'false';

        // Speaker input
        $orator = \property_exists($Presentation, 'orator') ? $Presentation->orator : $_SESSION['username'];
        $modifiable = $idPres !== 'false' & $organizer;
        $speakerList = self::speakerList($orator, $organizer, $modifiable, $idPres);

        // Make submission's type selection list
        $type_list = TypesManager::getTypeSelectInput('Presentation');

        // Download links
        $links = !is_null($Presentation->media) ? $Presentation->media : array();

        // Output
        $result['title'] = "Add/Edit presentation";
        $result['content'] = "
            <div class='submission'>
                <div class='form_container'>
                    <div class='form_aligned_block matched_bg'>
                        <div class='form_description'>
                            Upload files attached to this presentation
                        </div>
                        " . Media::uploader('Presentation', $links, 'presentation_form') . "
                    </div>
                    
                    <form method='post' action='php/router.php?controller={$controller}&action={$operation}' 
                    enctype='multipart/form-data' id='presentation_form'>
                        
                        <div class='form_aligned_block matched_bg'>
                            
                            <div class='form_description'>
                                Select a presentation type and pick a date
                            </div>
                            <div class='form-group'>
                                <select class='change_pres_type' name='type' id='{$controller}_{$idPres}' required>
                                    {$type_list['options']}
                                </select>
                                <label>Type</label>
                            </div>
                            
                            <div class='form-group'>
                                <input type='date' class='datepicker submissionCalendar' name='date' value='{$date}' 
                                data-view='view'/>
                                <label>Date</label>
                            </div>

                            <div class='form-group'>
                                {$speakerList}
                            </div>
                        </div>
                    
                        <div class='form_lower_container'>
                            <div class='special_inputs_container'>
                            " . SubmissionForms::get($Presentation->type, $Presentation) . "
                            </div>

                            <div class='form-group'>
                                <input type='text' id='keywords' name='keywords' value='$Presentation->keywords'>
                                <label>Keywords (comma-separated)</label>
                            </div>

                            <div class='form-group'>
                                <label>Abstract</label>
                                <textarea name='summary' class='wygiwym' id='summary' 
                                placeholder='Abstract (5000 characters maximum)' required>
                                {$Presentation->summary}</textarea>
                            </div>
                        </div>

                        <div class='submit_btns'>
                            <input type='submit' name='{$operation}' class='submit_pres' />
                            <input type='hidden' name='selected_date' id='selected_date' value='{$date}'/>
                            <input type='hidden' name='session_id' value='{$session_id}'/>
                            <input type='hidden' name='username' value='$user->username'/>
                            <input type='hidden' id='id' name='id' value='{$idPres}'/>
                        </div>
                    </form>
                </div>
            </div>
            ";
        $result['description'] = self::description();
        return $result;
    }
}
