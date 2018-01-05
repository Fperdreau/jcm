<?php

namespace includes;

use includes\BaseModel;

/**
 * Created by PhpStorm.
 * User: Florian
 * Date: 25/04/2017
 * Time: 17:52
 */
class Lab extends BaseModel {

    /**
     * Lab info
     *
     */
    protected $settings = array(
        'name'=>null,
        'street'=>null,
        'postcode'=>null,
        'city'=>null,
        'country'=>null,
        'url'=>null
    );

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
    }

    // VIEWS

    /**
     * Render settings form
     * @param array $settings
     * @return array
     */
    public static function settingsForm(array $settings) {
        return array(
            'title'=>'Lab information',
            'body'=>"
                    <form method='post' action='php/router.php?controller=Lab&action=updateSettings'>
                        <div class='submit_btns'>
                            <input type='submit' name='modify' value='Modify' class='processform'>
                        </div>
                        <div class='form-group'>
                            <input type='text' name='name' placeholder='Name of your Lab' value='{$settings['name']}'>
                            <label for='name'>Name</label>
                        </div>
                        <div class='form-group'>
                            <input type='text' name='street' placeholder='Street of your Lab' value='{$settings['street']}'>
                            <label for='street'>Street</label>
                        </div>
                        <div class='form-group'>
                            <input type='text' name='postcode' placeholder='Postcode of your lab' value='{$settings['postcode']}'>
                            <label for='postcode'>Post Code</label>
                        </div>
                        <div class='form-group'>
                            <input type='text' name='city' placeholder='Your city' value='{$settings['city']}'>
                            <label for='city'>City</label>
                        </div>
                        <div class='form-group'>
                            <input type='text' name='country' placeholder='Your country' value='{$settings['country']}'>
                            <label for='country'>Country</label>
                        </div>
                        <div class='form-group'>
                            <input type='text' name='url' placeholder='URL to the Google map' value='{$settings['url']}'>
                            <label for='url'>Google Map's URL</label>
                        </div>
                        <div class='feedback' id='feedback_lab'></div>
                    </form>
            ");
    }


}