<?php

namespace Patches;

class Suggestion
{
    /**
     * Copy wishes from presentation table to suggestion table
     * @return bool: success or failure
     */
    public static function patch()
    {
        self::convert();
    }

    private static function convert()
    {
        $self = new self();
        $Presentations = new \includes\Presentation();

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
}
