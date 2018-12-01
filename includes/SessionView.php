<?php

namespace includes;

class SessionView
{
     /**
     * Get and render session content
     * @param array $data: session data
     * @param string $date: selected date
     * @param bool $edit: Get editor or viewer
     * @return string
     */
    private static function getSessionContent(array $data, $date, $nSlots, $edit = false)
    {
        $content = null;
        $nSlots = max(count($data), $nSlots);
        for ($i=0; $i<$nSlots; $i++) {
            if (isset($data[$i]) && !is_null($data[$i]['id_pres'])) {
                if (!$edit) {
                    $content .= self::slotContainerBody(Presentation::inSessionSimple($data[$i]), $data[$i]['id_pres']);
                } else {
                    $content .= self::slotEditContainer(Presentation::inSessionEdit($data[$i]), $data[$i]['id_pres']);
                }
            } else {
                if ($edit) {
                    $content .= self::emptySlotEdit();
                } else {
                    $content .= self::emptyPresentationSlot($data[0], SessionInstance::isLogged());
                }
            }
        }
    }

    private static function renderSession($edit)
    {
        if ($edit) {
            return self::sessionEditContainer($data[0], $content, TypesManager::getTypes('Session'));
        } else {
            return self::sessionContainer(array(
                'date'=>$date,
                'content'=>$content,
                'type'=>$data[0]['session_type'],
                'start_time'=>$data[0]['start_time'],
                'end_time'=>$data[0]['end_time'],
                'room'=>$data[0]['room']
            ));
        }
    }
}
