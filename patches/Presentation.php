<?php

namespace Patches;

class Presentation
{

    public static function patch()
    {
        self::patchUploads();
        self::patchSessionId();
    }

    /**
     * Add session id to presentation info
    */
    public function patchPresentationId()
    {
        foreach ($this->all() as $key => $item) {
            $pres_obj = new \includes\Presentation($item['presid']);
            $pres_obj->update(
                array('session_id'=>$this->get_session_id($pres_obj->date)),
                array('id'=>$item['presid'])
            );
        }
    }

    /**
     * Patch upload table: add object name ('Presentation').
     */
    public static function patchUploads()
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
    public static function patchSessionId()
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
