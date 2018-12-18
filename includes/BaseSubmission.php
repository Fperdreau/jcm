<?php

namespace includes;

/**
 * Handles common properties and methods of submissions
 */
abstract class BaseSubmission extends BaseModel
{
    /**
     * Presentation settings
     * @var array
     */
    protected $settings = array(
        'default_type'=>"paper",
        'defaults'=>array("paper", "research", "methodology", "guest", "minute"),
        'types'=>array("paper", "research", "methodology", "guest", "minute")
    );

    /**
     * Constructor
     * @param null $id_pres
     */
    public function __construct($id = null)
    {
        parent::__construct();

        // Set types to defaults before loading custom information
        $this->settings['types'] = $this->settings['defaults'];

        if (!is_null($id)) {
            $this->getInfo($id);
        }
    }

    /**
     * Render submission index page
     * @param null $id
     * @return string
     */
    public function index($id = null)
    {
        if (!is_null($id)) {
            if (isset($_SESSION['username'])) {
                $user = new Users($_SESSION['username']);
            } elseif (!empty($_POST['Users'])) {
                $user = new Users($_POST['Users']);
            } else {
                $user = false;
            }

            $data = $this->getInfo(htmlspecialchars($id));
            $show = $user !== false && (in_array($user->status, array('organizer', 'admin'))
            || $data['orator'] === $user->username);
            if ($show && isset($_POST['operation']) && $_POST['operation'] === 'edit') {
                $content = $this->getForm('body');
            } else {
                $content = $this->showDetails($id, 'body');
            }
        } else {
            $content = "Nothing to show here";
        }

        return self::container($content);
    }

    /**
     * Update information
     * @param array $data
     * @param array $id
     * @return bool
     */
    public function update(array $data, array $id)
    {
        // Associates this presentation to an uploaded file if there is one
        if (!empty($data['media'])) {
            $media = new Media();
            if (!$media->addUpload(explode(',', $data['media']), $data['id'], self::getClassName())) {
                return false;
            }
        }
        return $this->db->update($this->tablename, $this->parseData($data, array("media","chair")), $id);
    }

    /**
     * Get associated files
     */
    private function getUploads()
    {
        $upload = new Media();
        $this->media = $upload->getUploads($this->id, self::getClassName());
        return $this->media;
    }

    /**
     * Delete an item, its corresponding files and assignments
     * @param int $id: submission id
     * @return bool
     */
    public function deleteSubmission($id)
    {
        // Delete corresponding file
        $uploads = new Media();
        if ($uploads->deleteFiles($id, self::getClassName())) {
            // Delete corresponding entry in the publication table
            $assignment = new Assignment();
            if ($assignment->unlinkPresentation(array('id'=>$id), true)) {
                return $this->delete(array('id'=>$id));
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * Edit submission
     *
     * @param array $data
     * @return mixed
     * @throws Exception
     */
    public function edit(array $data)
    {
        // check entries
        $id = htmlspecialchars($data['id']);

        // IF not a guest presentation, the one who posted is the planned speaker
        if ($data['type'] !== "guest" && !isset($data['orator'])) {
            $data['orator'] = $_SESSION['username'];
        }

        if ($id !== "false") {
            $created = $this->modify($data, $id);
        } else {
            $created = $this->make($data);
        }

        $result['status'] = !in_array($created, array(false, 'exist', 'booked', 'no_session', 'assigned'), true);
        $result['id'] = $id;
        if ($created === false) {
            $result['msg'] = 'Oops, something went wrong';
        } elseif ($created === 'exist') {
            $result['msg'] = "Sorry, a submission with a similar title already exists in our database.";
        } elseif ($created === 'booked') {
            $result['msg'] = "Sorry, the selected session is already full.";
        } elseif ($created === 'no_session') {
            $result['msg'] = "Sorry, there is no session planned on this date.";
        } elseif ($created === 'assigned') {
            $result['msg'] = "You already have a presentation on this session.";
        } else {
            $result['msg'] = "Thank you for your submission!";
            $result['id'] = $created;
        }

        return $result;
    }
    
    /**
     * Update a presentation (new info)
     * @param array $post
     * @param null $id
     * @return bool
     */
    public function modify(array $post = array(), $id = null)
    {
        if (null!=$id) {
            $this->id = $id;
        } elseif (array_key_exists('id', $post)) {
            $this->id = $post['id'];
        }

        // Associate the new uploaded file (if there is one)
        if (array_key_exists("media", $post)) {
            $media = new Media();
            $media->addUpload(explode(',', $post['media']), $post['id'], self::getClassName());
        }

        // Get presentation's type
        $this->type = (array_key_exists("type", $post)) ? $post['type']:$this->type;

        // Update table
        if ($this->db->update(
            $this->tablename,
            $this->parseData($post, array('media', 'chair')),
            array('id'=>$this->id)
        )) {
            Logger::getInstance(APP_NAME, get_class($this))->info("Submission ({$this->id}) updated");
            return true;
        } else {
            Logger::getInstance(APP_NAME, get_class($this))->error("Could not update submission ({$this->id})");
            return false;
        }
    }

    /**
     * Get submission's information from the database
     * @param int $id: submission id
     * @return bool|array
     */
    public function getInfo($id)
    {
        $sql = "SELECT p.*, u.fullname as fullname 
                FROM {$this->tablename} p
                LEFT JOIN ". Db::getInstance()->getAppTables('Users') . " u
                    ON u.username=p.username
                WHERE p.id='{$id}'";
        $data = $this->db->sendQuery($sql)->fetch_assoc();

        if (!empty($data)) {
            $this->map($data);

            // Get associated files
            $data['media'] = $this->getUploads();
            return $data;
        } else {
            return false;
        }
    }

    /**
     * Get submission types
     * @return array
     * @throws Exception
     */
    public function getTypes()
    {
        if (!empty($this->getSettings('types'))) {
            return $this->getSettings('types');
        } else {
            return $this->getSettings('defaults');
        }
    }

    /**
     * Get submission form
     *
     * @param string $type: object type (presentation, suggestion)
     * @param int $id: object id
     * @return string
     */
    public function getFormContent($type, $id = null)
    {
        $className = get_class($this);
        return SubmissionForms::get($type, new $className($id));
    }

    /**
     * Get submission form
     * @param string $view
     * @return mixed
     * @throws Exception
     */
    public function getForm(array $data)
    {
        $view = isset($data['view']) ? $data['view'] : "body";
        $operation = isset($data['operation']) ? $data['operation'] : 'edit';
        $id = isset($data['id']) ? $data['id'] : null;
        $section = isset($data['section']) ? $data['section'] : null;
        $session_id = isset($data['session_id']) ? $data['session_id'] : null;

        if ($view === "body") {
            return self::formatSection($this->editor(array(
                'operation'=>$operation,
                'id'=>$id,
                'session_id'=>$session_id
            )));
        } else {
            $content = $this->editor(array(
                'operation'=>$operation,
                'id'=>$id,
                'session_id'=>$session_id
            ));
            
            if (is_null($section)) {
                $section = strtolower(self::getClassName());
            }
            return array(
                'content'=>$content['content'],
                'id'=>$section,
                'buttons'=>null,
                'title'=>$content['title']);
        }
    }

     /**
     * Get list of publications (sorted/archives page)
     * @param null $filter
     * @param null $user
     * @return string
     */
    public function getAllList($filter = null, $user = null)
    {
        if (is_null($filter)) {
            $filter = array('year' => date('Y'));
        }

        $fieldName = self::getClassName() == 'Presentation' ? 'date' : 'up_date';
        if (is_null($filter) || $filter['year'] == 'all') {
            $year_pub = $this->getByYears(null, $user, $fieldName);
        } else {
            $year_pub = $this->getByYears($filter, $user, $fieldName);
        }
        if (empty($year_pub)) {
            return "Sorry, there is nothing to display here.";
        }

        $content = null;
        foreach ($year_pub as $year => $list) {
            $yearContent = null;
            foreach ($list as $id) {
                $yearContent .= $this->show($id);
            }
            $content .= self::yearContent($year, $yearContent);
        }
        return $content;
    }

    /**
     * Generate years selection list
     * @return string
     */
    public function generateYearsList()
    {
        $fieldName = self::getClassName() == 'Presentation' ? 'date' : 'up_date';
        return self::yearsSelectionList($this->getYears($fieldName));
    }

    /**
     * Get publication list by years
     * @param null $filter
     * @param null $username
     * @return array
     */
    public function getByYears($filter = null, $username = null, $fieldName = 'date')
    {
        $search = array(
            'title !='=>'TBA',
            );
        if (!is_null($filter)) {
            $search["YEAR({$fieldName})"] = $filter;
        }
        if (!is_null($username)) {
            $search['username'] = $username;
        }

        $data = $this->db->resultSet(
            $this->tablename,
            array("YEAR({$fieldName})", 'id'),
            $search,
            "ORDER BY {$fieldName} DESC"
        );

        $years = array();
        foreach ($data as $key => $item) {
            $years[$item["YEAR({$fieldName})"]][] = $item['id'];
        }
        return $years;
    }

    /**
     * Collect years of presentations present in the database
     * @return array
     */
    protected function getYears($fieldName = 'date')
    {
        $data = $this->all();
        $sql = "SELECT DISTINCT(YEAR({$fieldName})) as year_date FROM {$this->tablename}";
        $req = $this->db->sendQuery($sql);
        $years = array();
        while ($row = $req->fetch_assoc()) {
            $years[] = $row['year_date'];
        }
        return $years;
    }

    /**
     * Show submission details
     * @param bool $id: submission unique id
     * @param string $view: requested view
     * @return array|string
     */
    public function showDetails($id = false, $view = 'body')
    {
        $data = $this->getInfo($id);
        $user = SessionInstance::isLogged() ? new Users($_SESSION['username']) : null;
        $show = !is_null($user) && (in_array($user->status, array('organizer', 'admin'))
                || $data['username'] === $user->username);
        if ($data !== false) {
            return self::details($data, $show, $view);
        } else {
            if ($view === 'body') {
                return self::notFound();
            } else {
                return array(
                    'content'=> self::notFound(),
                    'title'=> 'Not found',
                    'buttons'=>null,
                    'id'=>strtolower(self::getClassName())
                );
            }
        }
    }

    /**
     * Register into DigestMaker table
     */
    public static function registerDigest()
    {
        $DigestMaker = new DigestMaker();
        $DigestMaker->register(get_called_class());
    }

    // VIEW
    /**
     * Submission menu
     *
     * @param string $destination : body or modal
     * @param string $style: submitMenuFloat or submitMenu_fixed
     * @return string
     */
    public static function menu($destination = 'body', $style = 'submitMenu_fixed')
    {
        $modal = $destination == 'body' ? 'loadContent' : "leanModal";
        $leanModalUrl_presentation = Router::buildUrl(
            'Presentation',
            'getForm',
            array(
            'view'=>'modal',
            'operation'=>'edit')
        );
        $leanModalUrl_suggestion = Router::buildUrl(
            'Suggestion',
            'getForm',
            array(
            'view'=>'modal',
            'operation'=>'edit')
        );
        $leanModalUrl_select = Router::buildUrl(
            'Suggestion',
            'getSelectionList',
            array('view'=>'modal')
        );
        return "
            <div class='{$style}'>
                <div class='submitMenuContainer'>
                    <div class='submitMenuSection'>
                        <a href='" . App::getAppUrl() . 'index.php?page=member/submission&op=edit' . "' 
                        class='{$modal}' data-url='{$leanModalUrl_presentation}' 
                        data-destination='.submission_container'>
                           <div class='icon_container'>
                                <div class='icon'><img src='" . URL_TO_IMG . 'add_paper.png' . "'></div>
                                <div class='text'>Add a presentation</div>
                            </div>
                       </a>
                    </div>
                    <div class='submitMenuSection'>
                        <a href='" . App::getAppUrl() . 'index.php?page=member/submission&op=suggest' . "' 
                        class='{$modal}' data-url='{$leanModalUrl_suggestion}' data-destination='.submission_container'>
                           <div class='icon_container'>
                                <div class='icon'><img src='" . URL_TO_IMG . 'wish_paper.png' . "'></div>
                                <div class='text'>Add a suggestion</div>
                            </div>
                        </a>
                    </div>
                    <div class='submitMenuSection'>
                        <a href='" . App::getAppUrl() . 'index.php?page=member/submission&op=wishpick' . "' 
                        class='{$modal}' data-url='{$leanModalUrl_select}' data-destination='.submission_container'>
                            <div class='icon_container'>
                                <div class='icon'><img src='" . URL_TO_IMG . 'select_paper.png'. "'></div>
                                <div class='text'>Select a suggestion</div>
                            </div>
                        </a>
                    </div>
                </div>
        </div>";
    }

    /**
     * Display submission details.
     * @param array $data : submission information
     * @param bool $show : show buttons (true)
     * @param string $view: requested view ('modal' or 'body')
     * @return array|string
     */
    public static function details(array $data, $show = false, $view = 'modal')
    {
        if (!isset($data['date'])) {
            $data['date'] = null;
        }

        // Download menu
        $dl_menu = Media::downloadMenu($data['media'], $show);
        $file_div = $show ? $dl_menu['menu'] : null;

        // Section name
        $containerName = strtolower(self::getClassName());

        // Destination name (where to load content)
        $destination = $view === 'modal' ? "{$containerName}" : "{$containerName}_container";

        // Triggers of buttons
        $trigger = $view == 'modal' ? 'leanModal' : 'loadContent';

        // URL used by event handler to load content on button click
        $leanModalUrl = Router::buildUrl(
            self::getClassName(),
            'getForm',
            array(
            'view'=>$view,
            'operation'=>'edit',
            'id'=>$data['id'])
        );

        $leanModalUrlPresent = Router::buildUrl(
            'Suggestion',
            'getForm',
            array(
            'view'=>$view,
            'operation'=>'select',
            'id'=>$data['id'],
            'destination'=>'select_suggestion')
        );

        // Add a delete link (only for admin and organizers or the authors)
        if ($show) {
            $delete_button = "<div class='pub_btn icon_btn'><a href='#' data-id='{$data['id']}' class='delete'
                data-controller='" . self::getClassName() . "' data-action='deleteSubmission'>
                <img src='" . URL_TO_IMG . "trash.png'></a></div>";
            $modify_button = "<div class='pub_btn icon_btn'><a href='#' class='{$trigger}'
                data-controller='" . self::getClassName() . "' data-url='{$leanModalUrl}' 
                data-section='{$containerName}' data-date='{$data['date']}'>
                <img src='" . URL_TO_IMG . "edit.png'></a></div>";
        } else {
            $delete_button = "<div style='width: 100px'></div>";
            $modify_button = "<div style='width: 100px'></div>";
        }

        // Presentation type
        $type = ucfirst($data['type']);
        $type_in_body = $view !== 'modal' ? "<div class='pub_type'>{$type}</div>" : null;

        // Present button (For suggestions only)
        $present_button = (self::getClassName() == 'Suggestion' && SessionInstance::isLogged()) ? "<div>
        <input type='submit' class='{$trigger}' value='Present it' data-url='{$leanModalUrlPresent}' 
        data-section='select_suggestion'/></div>" : null;

        // Action buttons
        $buttons = "
            <div class='first_half'>
                {$present_button}
            </div>
            <div class='last_half'>
                {$delete_button}
                {$modify_button}
            </div>
        ";

        $buttons_body = $view !== 'modal' ? "
           <div class='first_half'>
               {$present_button}
           </div>
            <div class='last_half'>
                {$delete_button}
                {$modify_button}
            </div>" : null;

        // List of keywords
        $keywords = !empty($data['keywords']) ? self::keywordsList($data['keywords']) : null;

        // Header
        $header = "
            <span style='color: #222; font-weight: 900;'>" . self::getClassName() . "</span>
            <span style='color: rgba(207,81,81,.5); font-weight: 900; font-size: 20px;'> . </span>
            <span style='color: #777; font-weight: 600;'>{$type}</span>
        ";

        $result = "
            <div class='pub_caps' itemscope itemtype='http://schema.org/ScholarlyArticle'>
                <div class='pub_title' itemprop='name'>{$data['title']}</div>
                {$type_in_body}
                <div class='pub_authors' itemprop='author'>{$data['authors']}</div>

                <div class='pub_header_table'>
                    
                    <div class='pub_orator'>
                        <span class='pub_label'>Presented by: </span>{$data['fullname']}
                    </div>
                </div>
            </div>

            <div class='pub_abstract'>
                <span class='pub_label'>Abstract: </span>
                {$data['summary']}
            </div>

            {$keywords}
            
            {$file_div}

            <div class='pub_action_btn'>
                {$buttons_body}
            </div>
        ";

        if ($view === 'body') {
            return $result;
        } else {
            return array(
                'content'=>$result,
                'title'=>$header,
                'buttons'=>$buttons,
                'id'=>$containerName
            );
        }
    }

    /**
     * Render submission container
     *
     * @param $content
     * @return string
     */
    protected static function container($content)
    {
        $containerName = strtolower(self::getClassName()) . '_container';
        return "<section><div class='section_content' id='{$containerName}'>
        {$content}</div></section>";
    }

    /**
     * @param array $content
     * @return string
     */
    public static function formatSection(array $content)
    {
        $sectionName = strtolower(self::getClassName()) . '_form';
        return "
        <section id='{$sectionName}}'>
            <h2>{$content['title']}</h2>
            <p class='page_description'>{$content['description']}</p>       
            <div class='section_content'>{$content['content']}</div>
        </section>
        ";
    }

    /**
     * View for modal windows
     * @param string $content
     * @return string
     */
    public static function formatModal($content)
    {
        return "<div class='section_content'>{$content}</div>";
    }

    /**
     * Render keywords list
     * @param string $keywords: list of keywords (comma-separated)
     * @return null|string
     */
    public static function keywordsList($keywords)
    {
        $content = null;
        if (!empty($keywords)) {
            foreach (explode(',', $keywords) as $keyword) {
                $content .= "<div>{$keyword}</div>";
            }
        }
        return "<div class='keywords_container'>{$content}</div>";
    }

    /**
     * Format date to be displayed
     *
     * @param string $date
     * @return string
     */
    protected static function formatDate($date = null)
    {
        if (is_null($date)) {
            $date = date('now');
        }
        return date('d M y', strtotime($date));
    }

    /**
     * Render error message (not found)
     * @return string
     */
    public static function notFound()
    {
        return "
            <section>
                <div class='section_content'>
                    <div style='color: rgb(105,105,105); font-size: 50px; text-align: center; font-weight: 600; 
                    margin-bottom: 20px;'>Oops</div>
                    <div style=\"color: rgb(105,105,105); text-align: center; font-size: 1.2em; font-weight: 400;\">
                        If you were looking for the answer to the question:
                        <p style='font-size: 1.4em; text-align: center;'>What is the universe?</p>
                        We can tell you it is 42.
                        <p>But since you were looking for a page that does not exist, then we must tell you:</p>
                        <p style='font-size: 2em; text-align: center;'>ERROR 404!</p>
                    </div>
                </div>
            </section>
            ";
    }

    /**
     * Empty suggestion list
     *
     * @return string
     */
    protected static function emptyList()
    {
        return "<p>Were you looking for suggestions? Sorry, there is none yet.</p>";
    }

    /**
     * Render years selection list
     * @param array $data
     * @return string
     */
    protected static function yearsSelectionList(array $data)
    {
        $options = "<option value='all'>All</option>";
        foreach ($data as $year) {
            $options .= "<option value='$year'>$year</option>";
        }
        $url = Router::buildUrl(self::getClassName(), 'getAllList');
        return "
            <div class='form-group inline_field' style='width: 200px'>
                <select name='year' class='archive_select' data-url='{$url}' data-destination='#archives_list'>
                    {$options}
                </select>
                <label>Filter by year</label>
            </div>
        ";
    }

    /**
     * Render list of presentations for a specific year
     * @param $year
     * @param $data
     * @return string
     */
    private static function yearContent($year, $data)
    {
        return "
        <section>
            <h2 class='section_header'>$year</h2>
            <div class='section_content'>
                <div class='table_container'>
                <div class='list-container list-heading'>
                    <div style='width: 10%;'>Date</div>
                    <div style='width: auto;'>Title</div>
                    <div style='width: 20%;'>Member</div>
                </div>
                {$data}
                </div>
            </div>
        </section>";
    }

    /**
     * Render presentation in list
     *
     * @param \stdClass $presentation
     * @param $speakerDiv
     * @return string
     */
    public static function showInList(\stdClass $presentation, $speakerDiv)
    {
        $date = property_exists($presentation, 'date') ? $presentation->date: $presentation->up_date;
        $date = date('d M y', strtotime($date));
        $leanModalUrl = Router::buildUrl(
            self::getClassName(),
            'showDetails',
            array(
            'view'=>'modal',
            'id'=>$presentation->id)
        );
        $pageName = \strtolower(self::getClassName());
        return "
            <div class='pub_container' style='display: table-row; position: relative; 
            box-sizing: border-box; font-size: 0.85em;  text-align: justify; margin: 5px auto; 
            padding: 0 5px 0 5px; height: 25px; line-height: 25px;'>
                <div style='display: table-cell; vertical-align: top; text-align: left; 
                min-width: 50px; font-weight: bold;'>$date</div>
                
                <div style='display: table-cell; vertical-align: top; text-align: left; 
                width: 60%; overflow: hidden; text-overflow: ellipsis;'>
                    <a href='" . URL_TO_APP . "index.php?page={$pageName}&id={$presentation->id}" . "' 
                    class='leanModal' data-url='{$leanModalUrl}' data-section='{$pageName}'>
                        $presentation->title
                    </a>
                </div>
                {$speakerDiv}
            </div>
        ";
    }
}
