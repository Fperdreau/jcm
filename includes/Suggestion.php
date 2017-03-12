<?php

/**
 * Created by PhpStorm.
 * User: Florian
 * Date: 23/02/2017
 * Time: 08:19
 */
class Suggestion extends AppTable {

    protected $table_data = array(
        "id" => array("INT NOT NULL AUTO_INCREMENT", false),
        "id_pres" => array("BIGINT(15)", false),
        "up_date" => array("DATETIME", false),
        "username" => array("CHAR(255) NOT NULL"),
        "type" => array("CHAR(30)", false),
        "keywords" => array("CHAR(255)", false),
        "title" => array("CHAR(150)", false),
        "authors" => array("CHAR(150)", false),
        "summary" => array("TEXT(5000)", false),
        "notified" => array("INT(1) NOT NULL", 0),
        "vote" => array("INT(10) NOT NULL", 0),
        "primary" => "id"
    );

    public $id;
    public $id_pres;
    public $type;
    public $up_date;
    public $username;
    public $title;
    public $authors;
    public $summary;
    public $link = array();
    public $notified = 0;
    public $vote;
    public $keywords;

    /**
     * Constructor
     */
    function __construct(){
        parent::__construct("Suggestion", $this->table_data);
    }

    /**
     * Add a presentation to the database
     * @param array $post
     * @return bool|string
     */
    public function add_suggestion(array $post){
        $class_vars = get_class_vars(get_called_class());
        if ($this->is_exist(array('title'=>$post['title'])) === false) {

            $post['id_pres'] = $this->generateID('id_pres');
            $post['up_date'] = date('Y-m-d h:i:s');

            // Associates this presentation to an uploaded file if there is one
            if (!empty($post['link'])) {
                $media = new Media();
                $media->add_upload(explode(',', $post['link']), $post['id_pres'], 'Suggestion');
            }

            // Add publication to the database
            if ($this->db->addcontent($this->tablename, $this->parsenewdata($class_vars, $post, array("link")))) {
                return $post['id_pres'];
            } else {
                return false;
            }
        } else {
            return "exist";
        }
    }

    /**
     * Register into DigestMaker table
     */
    public static function registerDigest() {
        $DigestMaker = new DigestMaker();
        $DigestMaker->register('Suggestion');
    }

    /**
     * Get wish list
     * @param null $number: number of wishes to display
     * @return string
     */
    public function getWishList($number=null) {
        $limit = (is_null($number)) ? null : " LIMIT {$number}";

        $wish_list = null;
        foreach ($this->getAll($limit) as $key=>$item) {
            $wish_list .= self::inList((object)$item);
        }
        return (is_null($wish_list)) ? self::no_wish() : $wish_list;
    }

    /**
     * Generate wish list (select menu)
     * @param string $target
     * @return string
     */
    public function generate_selectwishlist($target='.submission') {

        $option = "<option disabled selected>Select a suggestion</option>";
        foreach ($this->getAll() as $key=>$item) {
            $option .= "<option value='{$item['id_pres']}'>{$item['authors']} | {$item['title']}</option>";
        }

        return self::select_menu($option, $target);
    }

    /**
     * Copy wishes from presentation table to suggestion table
     * @return bool: success or failure
     */
    public static function patch() {
        $self = new self();
        $Presentations = new Presentation();

        foreach ($Presentations->all(array('type' => 'wishlist')) as $key => $item) {
            $item['type'] = 'paper'; // Set type as paper by default
            if ($self->add_suggestion($item) === false) {
                return false;
            } else {
                $Presentations->delete(array('id_pres'=>$item['id_pres']));
            }
        }

        return true;
    }

    // MODEL

    /**
     * Fetch list of suggestions
     * @param null $limit
     * @param string $order
     * @param string $dir
     * @return array
     */
    public function getAll($limit=null, $order='up_date', $dir='DESC') {
        $sql = "SELECT * 
                  FROM {$this->tablename} p 
                  LEFT JOIN " . AppDb::getInstance()->getAppTables('Users'). " u 
                    ON p.username = u.username
                  LEFT JOIN " . AppDb::getInstance()->getAppTables('Media') . " m
                    ON p.id_pres = m.presid
                  ORDER BY {$order} {$dir}" . $limit;
        $req = $this->db->send_query($sql);
        $data = array();
        while ($row = $req->fetch_assoc()) {
            $data[] = $row;
        }
        return $data;
    }

    /**
     * Get associated files
     */
    private function get_uploads() {
        $upload = new Uploads();
        $this->link = $upload->get_uploads($this->id_pres, 'Presentation');
    }

    private function get_votes() {

    }

    // VIEWS
    /**
     * Render suggestion in list
     * @param stdClass $item
     * @return string
     */
    public static function inList(stdClass $item) {
        $update = date('d M y',strtotime($item->up_date));
        $url = AppConfig::getInstance()->getAppUrl() . "index.php?page=submission&op=wishpick&id={$item->id_pres}";
        return "
        <div class='wish_container' id='{$item->id_pres}' style='display: block; position: relative; margin: 10px auto; 
        font-size: 0.9em; font-weight: 300; overflow: hidden; padding: 5px; border-radius: 5px;'>
            <div style='display: inline-block;font-weight: 600; color: #222222; vertical-align: top; font-size: 0.9em;'>
                {$update}
            </div>
            <div style='display: inline-block; margin-left: 20px; max-width: 70%;'>
               <a href='$url' class='leanModal' id='modal_trigger_pubmod' data-section='submission_form' data-id='{$item->id_pres}'>
                    <div style='font-size: 16px;'>{$item->title}</div>
                    <div style='font-style: italic; color: #000000; font-size: 12px;'>Suggested by <span style='color: #CF5151; font-size: 14px;'>{$item->fullname}</span></div>
                </a>
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
     * @return string
     */
    private static function select_menu($option, $target='.submission') {
        return "<form method='post' action='php/form.php' class='form'>
              <input type='hidden' name='page' value='presentations'/>
              <input type='hidden' name='op' value='wishpick'/>
              <div class='form-group field_auto' style='margin: auto; width: 250px;'>
                <select name='id' id='select_wish' data-target='{$target}'>
                    {$option}
                </select>
                <label for='id'>Select a suggestion</label>
              </div>
          </form>";
    }

    /**
     * Generate submission form and automatically fill it up with data provided by Presentation object.
     * @param User $user
     * @param null|Suggestion $Presentation
     * @param string $submit
     * @param bool $type
     * @return array
     */
    public static function form(User $user, Suggestion $Presentation=null, $submit="edit", $type=null) {
        if (is_null($Presentation)) {
            $Presentation = new self();
        }

        // Submission type
        $type = (is_null($type)) ? $type : $Presentation->type;
        if (empty($type)) $type = 'paper';

        // Presentation ID
        $idPres = ($Presentation->id_pres != "") ? $Presentation->id_pres : 'false';

        // Make submission's type selection list
        $type_options = Session::presentation_type();
        // Text of the submit button
        $form = ($submit !== "wishpick") ? "
            <div class='feedback'></div>
            <div class='form_container'>
                <div class='form_aligned_block matched_bg'>
                    <div class='form_description'>
                        Upload files attached to this presentation
                    </div>
                    " . Media::uploader($Presentation->link) . "
                </div>
                
                <form method='post' action='php/form.php' enctype='multipart/form-data' id='submit_form'>
                    
                    <div class='form_aligned_block matched_bg'>
                        
                        <div class='form_description'>
                            Select a presentation type
                        </div>
                        <div class='form-group'>
                            <select class='change_pres_type' name='type' id='type' required>
                                {$type_options['options']}
                            </select>
                            <label>Type</label>
                        </div>
                        
                    </div>
                
                    <div class='form_lower_container'>
                        " . Presentation::get_form_content($Presentation, $submit) . "
                    </div>
                    <div class='submit_btns'>
                        <input type='submit' name='$submit' class='submit_pres processform'>
                        <input type='hidden' name='$submit' value='true'/>
                        <input type='hidden' name='username' value='$user->username'/>
                        <input type='hidden' id='id_pres' name='id_pres' value='{$idPres}'/>
                    </div>
                </form>
            </div>
        ":"";

        if ($submit == 'suggest') {
            $result['title'] = "Add a suggestion";
        } elseif ($submit == "edit") {
            $result['title'] = "Add/Edit suggestion";
        } elseif ($submit == "wishpick") {
            $result['title'] = "Select a wish";
        }
        $result['content'] = "
            <div class='section_suggestion_container'>" . $Presentation->generate_selectwishlist() . "</div>
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

        }
        return $result;

    }

}