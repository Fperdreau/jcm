<?php

namespace includes;

use includes\Submission;

/**
 * Created by PhpStorm.
 * User: Florian
 * Date: 23/02/2017
 * Time: 08:19
 */
class Suggestion extends Submission {

    public $id;
    public $id_pres;
    public $type;
    public $up_date;
    public $username;
    public $title;
    public $authors;
    public $summary;
    public $media;
    public $notified;
    public $vote;
    public $keywords;

    /**
     * Add a presentation to the database
     * @param array $post
     * @return bool|string
     * @throws Exception
     */
    public function add_suggestion(array $post)
    {
        if ($this->isExist(array('title'=>$post['title'])) === false) {
            $post['up_date'] = date('Y-m-d h:i:s');

            // Add publication to the database
            if ($this->db->insert($this->tablename, $this->parseData($post, array("media")))) {
                $id = $this->db->getLastId();
                // Associates this presentation to an uploaded file if there is one
                if (!empty($post['media'])) {
                    $media = new Media();
                    $media->addUpload(explode(',', $post['media']), $id, self::getClassName());
                }
                return $id;
            } else {
                return false;
            }
        } else {
            return "exist";
        }
    }

    /**
     * Select suggestion to present it
     * @param array $data
     * @return array: returns status and msg
     * @throws Exception
     */
    public function select(array $data)
    {
        $id_pres = $data['id'];
        $data['id'] = "false";
        $Presentation = new Presentation();
        $result = $Presentation->edit($data);
        if ($result['status'] == true) {
            $result['status'] = $this->delete_pres($id_pres);
        }
        return $result;
    }

    /**
     * Render submission editor
     * @param array|null $post
     * @param string $view
     * @return array
     * @throws Exception
     */
    public function editor(array $post = null, $view = 'body', $destination=null)
    {
        $operation = (!empty($post['operation']) && $post['operation'] !== 'false') ? $post['operation'] : null;
        $type = (!empty($post['type']) && $post['type'] !== 'false') ? $post['type'] : null;
        $id = isset($post['id']) ? $post['id'] : false;
        $destination = (!empty($post['destination'])) ? '#' . $post['destination'] : '#' . $destination;

        if ($operation === 'selection_list') {
            $result['content'] = $this->getSelectionList($destination, $view);
            $result['title'] = "Select a wish";
            $result['description'] = Suggestion::description("wishpick");
            return $result;
        } elseif ($operation === 'select') {
            $Suggestion = new Suggestion();
            $Suggestion->getInfo($id);
            $Session = new Session();
            $next = $Session->getNext(1);
            return Presentation::form(
                new Users($_SESSION['username']),
                $Suggestion,
                'select',
                $operation,
                array(
                    'date'=>$next[0]['date'],
                    'session_id'=>$next[0]['id'],
                    'controller'=>'Suggestion')
            );
        } elseif ($operation === 'edit') {
            $this->getInfo($id);
            return Suggestion::form(new Users($_SESSION['username']), $this, $operation, $type);
        } else {
            return self::not_found();
        }
    }

    /**
     * Render suggestions section
     * @param null $number: number of wishes to display
     * @return string
     */
    public function getSuggestionSection($number = null)
    {
        $limit = (is_null($number)) ? null : " LIMIT {$number}";
        $wish_list = null;
        $username = isset($_SESSION['username']) ? $_SESSION['username'] : null;

        $Vote = new Vote();
        $Bookmark = new Bookmark();

        foreach ($this->getAll($limit) as $key => $item) {
            $vote_icon = $Vote->getIcon($item['id'], 'Suggestion', $username);
            $bookmark_icon = $Bookmark->getIcon($item['id'], 'Suggestion', $username);
            $wish_list .= self::inList((object)$item, $vote_icon, $bookmark_icon);
        }

        $wish_list = is_null($wish_list) ? self::no_wish() : $wish_list;
        $add_button = SessionInstance::isLogged() ? self::add_button() : null;

        return $add_button . $wish_list;
    }

    /**
     * Alias for editor()
     * @param $view
     * @return array|string
     * @throws Exception
     */
    public function get_suggestion_list($view, $destination)
    {
        $data = array('destination'=>$destination, 'operation' => 'selection_list');
        if ($view === "body") {
            return self::format_section($this->editor($data, $view));
        } else {
            return $this->editor($data, $view);
        }
    }

    /**
     * Generate suggestions list (select menu)
     *
     * @param string $target: div in which the returned edit form will be loaded
     * @param string $destination: body or modal window
     * @return string
     */
    public function selectionMenu($target = '.submission', $destination = 'body')
    {
        $option = "<option disabled selected>Select a suggestion</option>";
        foreach ($this->getAll() as $key => $item) {
            $option .= "<option value='{$item['id']}'>{$item['authors']} | {$item['title']}</option>";
        }

        return self::select_menu($option, $target, $destination);
    }

    /**
     * Copy wishes from presentation table to suggestion table
     * @return bool: success or failure
     * @throws Exception
     */
    public static function patch()
    {
        $self = new self();
        $Presentations = new Presentation();

        foreach ($Presentations->all(array('type' => 'wishlist')) as $key => $item) {
            $item['type'] = 'paper'; // Set type as paper by default
            if ($self->add_suggestion($item) === false) {
                return false;
            } else {
                $Presentations->delete(array('id'=>$item['id']));
            }
        }

        return true;
    }

    /**
     * Renders digest section (called by DigestMaker)
     * @param null|string $username
     * @return mixed
     */
    public function makeMail($username = null)
    {
        $content['body'] = $this->getSuggestionSection(4);
        $content['title'] = "Suggestions";
        return $content;
    }

    // MODEL
    /**
     * Fetch list of suggestions
     * @param null $limit
     * @param string $order
     * @param string $dir
     * @return array
     */
    public function getAll($limit=null, $order='count_vote', $dir='DESC') {
        $sql = "SELECT *, p.id as id, COUNT((v.ref_id)) as count_vote
                  FROM {$this->tablename} p 
                  LEFT JOIN " . Db::getInstance()->getAppTables('Users'). " u 
                    ON p.username = u.username
                  LEFT JOIN " . Db::getInstance()->getAppTables('Media') . " m
                    ON p.id = m.obj_id
                  LEFT JOIN " . Db::getInstance()->getAppTables('Vote') . " v
                    ON v.ref_id = p.id
                  GROUP BY p.id
                  ORDER BY {$order} {$dir}" . $limit;
        $data = array();
        if ($req = $this->db->sendQuery($sql)) {
            while ($row = $req->fetch_assoc()) {
                $data[] = $row;
            }
        }
        return $data;
    }

    // VIEWS

    /**
     * Render "Add" button
     *
     * @return string
     */
    private static function add_button()
    {
        $leanModalUrl = Modal::buildUrl(
            'Suggestion',
            'get_form',
            array('view'=>'modal', 'operation'=>'edit')
        );
        return "
            <div>
                <a href='" . App::getAppUrl() . 'index.php?page=submission&op=suggest' . "' 
                    class='leanModal' data-url='{$leanModalUrl}' data-section='suggestion'>
                    <input type='submit' value='Add' />
                </a>
            </div>
        ";
    }

    /**
     * Render suggestion in list
     * @param \stdClass $item
     * @param null|string $vote
     * @param null|string $bookmark
     * @return string
     */
    public static function inList(\stdClass $item, $vote = null, $bookmark = null)
    {
        $update = self::formatDate($item->up_date);
        $url = App::getAppUrl() . "index.php?page=suggestion&id={$item->id}";
        $keywords = self::keywords_list($item->keywords);
        $leanModalUrl = Modal::buildUrl(
            'Suggestion',
            'show_details',
            array(
            'view'=>'modal',
            'id'=> $item->id
            )
        );

        return "
        <div class='suggestion_container' id='{$item->id}''>
            <div class='suggestion_date'>
                {$update}
            </div>
            <div class='suggestion_details_container'>
                <div class='suggestion_details'>
                   <a href='{$url}' class='leanModal' data-url='{$leanModalUrl}' data-section='suggestion' 
                   data-id='{$item->id}'>
                        <div style='font-size: 16px;'>{$item->title}</div>
                        <div style='font-style: italic; color: #000000; font-size: 12px;'>
                            Suggested by <span style='color: #CF5151; font-size: 14px;'>{$item->fullname}</span>
                        </div>
                   </a>
                </div>
                {$keywords}
            </div>
            <div class='tiny_icon_container'>
                {$vote}
                {$bookmark}            
            </div>

        </div>";
    }

    /**
     * Empty suggestion list
     * @return string
     */
    private static function no_wish() {
        return "<p>Were you looking for suggestions? Sorry, there is none yet.</p>";
    }

    /**
     * Render select menu
     * @param $option
     * @param string $target
     * @param string $destination
     * @return string
     */
    private static function select_menu($option, $target = '.submission', $destination = 'body')
    {
        $url = "php/router.php?controller=Suggestion&action=get_form&operation=select&view={$destination}";
        return "
          <form method='post' action='{$url}'>
              <input type='hidden' name='page' value='presentations'/>
              <input type='hidden' name='op' value='wishpick'/>
              <div class='form-group field_auto' style='margin: auto; width: 250px;'>
                <select name='id' id='select_wish' data-url='{$url}' data-section='suggestion' 
                data-destination='{$target}'>
                    {$option}
                </select>
                <label for='id'>Select a suggestion</label>
              </div>
          </form>
          <div class='selection_container'></div> ";
    }

    /**
     * Generate submission form and automatically fill it up with data provided by Presentation object.
     * @param Users $user
     * @param null|Suggestion $Suggestion
     * @param string $submit
     * @param bool $type
     * @return array
     * @throws Exception
     */
    public static function form(Users $user, Suggestion $Suggestion = null, $submit = "edit", $type = null)
    {
        if (is_null($Suggestion)) {
            $Suggestion = new self();
        }

        // Get class of instance
        $controller = get_class($Suggestion);

        // Presentation ID
        $idPres = ($Suggestion->id != "") ? $Suggestion->id : 'false';

        // Make submission's type selection list
        $Presentation = new Presentation();
        $type_options = $Presentation::renderTypes(
            $Presentation->getSettings('types'),
            $Presentation->getSettings('default_type'),
            array('minute')
        );

        // Download medias
        $medias = !is_null($Suggestion->media) ? $Suggestion->media : array();

        // Text of the submit button
        $form = ($submit !== "wishpick") ? "
            <div class='feedback'></div>
            <div class='form_container'>
                <div class='form_aligned_block matched_bg'>
                    <div class='form_description'>
                        Upload files attached to this presentation
                    </div>
                    " . Media::uploader('Suggestion', $medias, 'suggestion_form') . "
                </div>
                
                <form method='post' action='php/router.php?controller=Suggestion&action={$submit}' 
                enctype='multipart/form-data' id='suggestion_form'>
                    
                    <div class='form_aligned_block matched_bg'>
                        
                        <div class='form_description'>
                            Select a presentation type
                        </div>
                        <div class='form-group'>
                            <select class='change_pres_type' name='type' id='{$controller}_{$idPres}' required>
                                {$type_options['options']}
                            </select>
                            <label>Type</label>
                        </div>
                        
                    </div>
                
                    <div class='form_lower_container'>
                        <div class='special_inputs_container'>
                        " . Presentation::get_form_content($Suggestion, $type) . "
                        </div>
                        
                        <div class='form-group'>
                            <input type='text' id='keywords' name='keywords' value='$Suggestion->keywords'>
                            <label>Keywords (comma-separated)</label>
                        </div>
                        
                        <div class='form-group'>
                            <label>Abstract</label>
                            <textarea name='summary' id='summary' class='wygiwym' 
                            placeholder='Abstract (5000 characters maximum)' 
                            style='width: 90%;' required>$Suggestion->summary</textarea>
                        </div>
                    </div>
                    <div class='submit_btns'>
                        <input type='submit' name='$submit' class='submit_pres'>
                        <input type='hidden' name='username' value='$user->username'/>
                        <input type='hidden' id='id' name='id' value='{$idPres}'/>
                    </div>
                </form>
            </div>
        ":"";

        if ($submit == 'suggest') {
            $result['title'] = "Add a suggestion";
        } elseif ($submit == "edit") {
            $result['title'] = "Edit suggestion";
        } elseif ($submit == "wishpick") {
            $result['title'] = "Select a wish";
        }
        $result['content'] = "
            <div class='submission'>
                $form
            </div>
            ";
        $result['description'] = self::description($submit);
        return $result;
    }

    /**
     * Submission form instruction
     * @param $type
     * @return null|string
     */
    public static function description($type) {
        $result = null;
        switch ($type) {
            case "suggest":
                $result = "
                Here you can suggest a paper that somebody else could present at a Journal Club session.
                Fill in the form below and that's it! Your suggestion will immediately appear in the wishlist.<br>
                If you want to edit or delete your submission, you can find it on your <a href='index.php?page=member/profile'>profile page</a>!
                ";
                break;
            case "wishpick":
                $result = "
                Here you can choose a suggested paper from the wishlist that you would like to present.<br>
                The form below will be automatically filled in with the data provided by the user who suggested the selected paper.
                Check that all the information is correct and modify it if necessary, choose a date to present and it's done!<br>
                If you want to edit or delete your submission, you can find it on your <a href='index.php?page=member/profile'>profile page</a>!
                ";
                break;
            case "edit":
                $result = "
                Here you can edit a suggestion.
                ";
                break;

        }
        return $result;
    }
}
