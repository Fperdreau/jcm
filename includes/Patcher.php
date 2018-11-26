<?php

/**
 * Created by PhpStorm.
 * User: Florian
 * Date: 05/05/2017
 * Time: 22:03
 */
namespace includes;

/**
 * Undocumented class
 */
class Patcher
{

    /**
     * @var $directory string: path to patch files
     */
    private static $directory;

    /**
     * Patcher constructor.
     */
    public function __construct()
    {
        self::$directory = PATH_TO_APP . DS . 'patches';
    }

    /**
     * Patch application
     * @return mixed
     */
    public function patching($op)
    {
        if ($op === 'update') {
            // Register Patches autoloader
            require_once PATH_TO_APP . 'patches' . DS . 'Autoloader.php';
            \Patches\Autoloader::register();

            // Install all tables
            $patchesList = scandir(PATH_TO_APP . 'patches');
            foreach ($patchesList as $patchFile) {
                if (!in_array($patchFile, array('.', '..', 'Autoloader.php'))) {
                    $class_name = explode('.', $patchFile);
                    $result = self::loadClass($class_name[0])->patch();
                    if ($result['status'] === false) {
                        return $result;
                    }
                }
            }
                
            $result['msg'] = "Patch successfully applied";
            $result['status'] = true;
        } else {
            $result['msg'] = 'Skipped, because unnecessary.';
            $result['status'] = true;
        }
        return $result;
    }

    /**
     * Load class function
     *
     * @param string $name
     * @return stdClass
     */
    private static function loadClass($name)
    {
        $className = '\\Patches\\' . $name;
        return new $className();
    }

    /**
     * Patching database tables for version older than 1.2.
     */
    public function patchingOld()
    {
        $db = Db::getInstance();
        $version = (float)$_SESSION['installed_version'];
        if ($version <= 1.2) {
            // Patch Presentation table
            // Set username of the uploader
            $sql = 'SELECT * FROM ' . $db->tablesname['Presentation'];
            $req = $db->send_query($sql);
            while ($row = mysqli_fetch_assoc($req)) {
                $pub = new Presentation($db, $row['id_pres']);
                $data = array();
                $data['summary'] = str_replace('\\', '', htmlspecialchars_decode($row['summary']));
                $data['authors'] = str_replace('\\', '', htmlspecialchars_decode($row['authors']));
                $data['title'] = str_replace('\\', '', htmlspecialchars_decode($row['title']));

                // If publication's submission date is past, we assume it has already been notified
                if ($row['up_date'] < date('Y-m-d H:i:s', strtotime('-2 days', strtotime(date('Y-m-d H:i:s'))))) {
                    $data['notified'] = 1;
                }

                if (empty($row['username']) || $row['username'] == "") {
                    $sql = "SELECT username FROM " . $db->tablesname['Users'] . " WHERE username='{$row['orator']}' OR fullname LIKE '%{$row['orator']}%'";
                    $userreq = $db->send_query($sql);
                    $user_data = mysqli_fetch_assoc($userreq);
                    if (!empty($data)) {
                        $data['orator'] = $user_data['username'];
                        $data['username'] = $user_data['username'];
                    }
                }
                $pub->update($data, array('id_pres'=>$row['id_pres']));
            }

            // Patch POST table
            // Give ids to posts that do not have one yet (compatibility with older versions)
            $post = new Posts();
            $sql = "SELECT postid,date,username FROM " . $db->tablesname['Posts'];
            $req = $db->send_query($sql);
            while ($row = mysqli_fetch_assoc($req)) {
                $date = $row['date'];
                if (empty($row['postid']) || $row['postid'] == "NULL") {
                    // Get uploader username
                    $userid = $row['username'];
                    $sql = "SELECT username FROM " . $db->tablesname['Users'] . " WHERE username='$userid' OR fullname='$userid'";
                    $userreq = $db->send_query($sql);
                    $data = mysqli_fetch_assoc($userreq);

                    $username = $data['username'];
                    $post->date = $date;
                    $postid = $post->generateID('postid');
                    $post->update(array('postid'=>$postid, 'username'=>$username), array('date'=>$date));
                }
            }

            // Patch MEDIA table
            // Write previous uploads to this new table
            $columns = $db->getColumns($db->tablesname['Presentation']);
            $filenames = $db->resultSet($db->tablesname['Media'], array('filename'));
            if (in_array('link', $columns)) {
                $sql = "SELECT up_date,id_pres,link FROM " . $db->tablesname['Presentation'];
                $req = $db->send_query($sql);
                while ($row = mysqli_fetch_assoc($req)) {
                    $links = explode(',', $row['link']);
                    if (!empty($links)) {
                        foreach ($links as $link) {
                            // Check if uploads does not already exist in the table
                            if (!in_array($link, $filenames)) {
                                // Make a unique id for this link
                                $exploded = explode('.', $link);
                                if (!empty($exploded)) {
                                    $id = $exploded[0];
                                    $type = $exploded[1];
                                    // Add upload to the Media table
                                    $content = array(
                                        'date' => $row['up_date'],
                                        'fileid' => $id,
                                        'filename' => $link,
                                        'presid' => $row['id_pres'],
                                        'type' => $type
                                    );
                                    $db->insert($db->tablesname['Media'], $content);
                                }
                            }
                        }
                    }
                }
            }
        }

        // Clean session duplicates
        $Session = new Session();
        $Session->clean_duplicates();
    }
}
