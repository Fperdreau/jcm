<?php

namespace Patches;

class Posts
{

    public static function patch()
    {
        self::patchTable();
    }
    
    /**
     * Convert post username
     */
    private static function patchTable()
    {
        $db = \includes\Db::getInstance();
        $db->getAppTables('Posts');
        $self = new \includes\Posts();
        $sql = "SELECT * FROM {$db->getAppTables('Posts')}";
        $req = $db->sendQuery($sql);
        $user = new \includes\Users();
        while ($row = mysqli_fetch_assoc($req)) {
            $data = $user->get(array('fullname'=>$row['username']));
            if (!empty($data)) {
                $cur_post = new self($row['id']);
                $cur_post->update(array('username'=>$data['username']), array('id'=>$row['id']));
            }
        }
    }
}
