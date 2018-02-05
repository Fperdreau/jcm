<?php

namespace Patch;

class Session
{
    
    /**
     * Convert post username
     */
    public static function patchTable()
    {
        $self = new self();
        $sql = "SELECT * FROM {$self->tablename}";
        $req = $self->db->sendQuery($sql);
        $user = new Users();
        while ($row = mysqli_fetch_assoc($req)) {
            $data = $user->get(array('fullname'=>$row['username']));
            if (!empty($data)) {
                $cur_post = new self($row['id']);
                $cur_post->update(array('username'=>$data['username']), array('id'=>$row['id']));
            }
        }
    }
}
