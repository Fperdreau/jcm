<?php

namespace includes;

use includes\BaseModel;

/**
 * Created by PhpStorm.
 * User: Florian
 * Date: 15/04/2016
 * Time: 19:24
 */
class Availability extends BaseModel
{

    /**
     * @var string $date
     */
    public $date;

    /**
     * @var string $username
     */
    public $username;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @param $post
     * @return bool|mysqli_result
     */
    public function add(array $post)
    {
        return $this->db->insert($this->tablename, $this->parseData($post, array('session')));
    }

    /**
     * @param array $id
     * @return bool|mysqli_result
     */
    public function edit(array $id)
    {
        if ($this->isExist($id)) {
            return $this->db->delete($this->tablename, $id);
        } else {
            return $this->add($id);
        }
    }

    /**
     * Check if date exist
     *
     * @param array $id
     * @return bool
     */
    public function isExist(array $id, $tablename = null)
    {
        $where = array();
        foreach ($id as $field => $value) {
            $where[] = "{$field}='{$value}'";
        }
        $where = implode(' AND ', $where);
        $sql = "SELECT * FROM {$this->tablename} WHERE {$where}";
        $data = $this->db->sendQuery($sql)->fetch_assoc();
        return !empty($data);
    }

    /**
     * Update user's availability on the selected date
     *
     * @param string $date: selected date (Y-m-d)
     * @return array
     */
    public function updateUserAvailability($date)
    {
        $username = $_SESSION['username'];
        //$date = $data['date'];
        $Presentation = new Presentation();

        $result['status'] = $this->edit(array('date'=>$date, 'username'=>$username));
        if ($result['status'] !== false) {
            // Check whether user has a presentation planned on this day, if yes, then we delete it and notify the user that
            // this presentation has been canceled
            $data = $Presentation->get(array('date'=>$date, 'orator'=>$username));
            if (!empty($data)) {
                $speaker = new Users($username);
                $Assignment = new Assignment();
                $session = new Session($data['session_id']);
                $Presentation = new Presentation($data['id']);
                $info['type'] = $session->type;
                $info['date'] = $date;
                $info['presid'] = $data['id'];
                $result['status'] = $Presentation->deleteSubmission($data['id']);
                if ($result['status']) {
                    $result['status'] = $Assignment->updateAssignment($speaker, $info, false, true);
                }
            }
        }
        return $result;
    }
}
