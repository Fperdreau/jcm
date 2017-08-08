<?php

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
                        <input type='hidden' name='config_modify' value='true'/>
                        <div class='form-group'>
                            <input type='text' name='lab_name' placeholder='Name of your Lab' value='{$settings['name']}'>
                            <label for='lab_name'>Name</label>
                        </div>
                        <div class='form-group'>
                            <input type='text' name='lab_street' placeholder='Street of your Lab' value='{$settings['street']}'>
                            <label for='lab_street'>Street</label>
                        </div>
                        <div class='form-group'>
                            <input type='text' name='lab_postcode' placeholder='Postcode of your lab' value='{$settings['postcode']}'>
                            <label for='lab_postcode'>Post Code</label>
                        </div>
                        <div class='form-group'>
                            <input type='text' name='lab_city' placeholder='Your city' value='{$settings['city']}'>
                            <label for='lab_city'>City</label>
                        </div>
                        <div class='form-group'>
                            <input type='text' name='lab_country' placeholder='Your country' value='{$settings['country']}'>
                            <label for='lab_country'>Country</label>
                        </div>
                        <div class='form-group'>
                            <input type='text' name='lab_mapurl' placeholder='URL to the Google map' value='{$settings['url']}'>
                            <label for='lab_mapurl'>Google Map's URL</label>
                        </div>
                        <div class='feedback' id='feedback_lab'></div>
                    </form>
            ");
    }


}