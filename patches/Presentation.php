<?php

namespace Patches;

class Presentation
{

    public static function patch()
    {
        self::patchTable();
        //self::patchPresentationId();
        self::patchUploads();
        self::patchSessionId();
    }

    private static function patchTable()
    {
        $db = \includes\Db::getInstance();
        $self = new \includes\Presentation();
        var_dump($db->getAppTables('Presentations'));
        if ($db->tableExists($db->getAppTables('Presentations'))) {
            $sql = "SELECT * FROM {$db->getAppTables('Presentations')}";
            $data = $db->sendQuery($sql)->fetch_all(MYSQL_ASSOC);
            foreach ($data as $key => $item) {
                if (!$self->get(array('title'=>$item['title']))) {
                    $self->add($item);
                }
            }
        }
    }

    /**
     * Add session id to presentation info
    */
    private static function patchPresentationId()
    {
        $self = new \includes\Presentation();
        foreach ($self->all() as $key => $item) {
            $pres_obj = new \includes\Presentation($item['id']);
            $pres_obj->update(
                array('session_id'=>$self->get_session_id($pres_obj->date)),
                array('id'=>$item['id'])
            );
        }
    }

    /**
     * Patch upload table: add object name ('Presentation').
     */
    private static function patchUploads()
    {
        $Publications = new \includes\Presentation();
        $Media = new \includes\Media();
        foreach ($Publications->all() as $key => $item) {
            if ($Media->isExist(array('id'=>$item['id']))) {
                if (!$Media->update(array('obj'=>'Presentation'), array('id'=>$item['id']))) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Patch Presentation table by adding session ids if missing
     * @return bool
     */
    private static function patchSessionId()
    {
        $Publications = new \includes\Presentation();
        $Session = new \includes\Session();
        foreach ($Publications->all() as $key => $item) {
            if ($Session->isExist(array('date'=>$item['date']))) {
                $session_info = $Session->getInfo(array('date'=>$item['date']));
                if (!$Publications->update(array('session_id'=>$session_info['id']), array('id'=>$item['id']))) {
                    \includes\Logger::getInstance(APP_NAME, __CLASS__)->error('Could not update publication 
                    table with new session id');
                    return false;
                }
            }
        }
        return true;
    }
}
