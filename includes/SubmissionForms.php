<?php

namespace includes;

/**
 * Handles submission forms views
 */
class SubmissionForms
{

    /**
     * Undocumented function
     *
     * @param string $formType
     * @param Submission|Presentation|Suggestion $obj
     * @return string
     */
    public static function get($type, $obj = null)
    {
        if (empty($type)) {
            $type = 'paper';
        }

        $methodName = $type . 'Form';
        if (method_exists(__CLASS__, $methodName)) {
            try {
                return self::$methodName($obj);
            } catch (\Exception $e) {
                return self::notFound($type);
            }
        } else {
            return self::notFound($type);
        }
    }

    /**
     * Render not found message
     *
     * @param string $type: selected type
     * @return string
     */
    private static function notFound($type)
    {
        return "The selected type ['{$type}'] is not available";
    }

    /**
     * Render form for wishes
     * @param Suggestion|Presentation $Presentation
     * @return string
     */
    private static function suggestForm($Presentation)
    {
        return "
        <div class='form_description'>
            Provide presentation information
        </div>

        <div class='form-group'>
            <input type='text' id='title' name='title' value='$Presentation->title' required/>
            <label>Title</label>
        </div>
        <div class='form-group'>
            <input type='text' id='authors' name='authors' value='$Presentation->authors' required>
            <label>Authors</label>
        </div>
        ";
    }

    /**
     * Render form for research article
     * @param Suggestion|Presentation $Presentation
     * @return string
     */
    private static function paperForm($Presentation)
    {
        return "
        <div class='form_description'>
            Provide presentation information
        </div>

        <div class='form-group'>
            <input type='text' id='title' name='title' value='$Presentation->title' required/>
            <label>Title</label>
        </div>
        <div class='form-group'>
            <input type='text' id='authors' name='authors' value='$Presentation->authors' required>
            <label>Authors</label>
        </div>
        ";
    }

    /**
     * Render form for guest speakers
     * @param Suggestion|Presentation $Presentation
     * @return string
     */
    private static function guestForm($Presentation)
    {
        return "
        <div class='form_description'>
            Provide presentation information
        </div>

        <div class='form-group'>
            <input type='text' id='title' name='title' value='$Presentation->title' required/>
            <label>Title</label>
        </div>
        <div class='form-group'>
            <input type='text' id='authors' name='authors' value='$Presentation->authors' required>
            <label>Authors </label>
        </div>
        <div class='form-group' id='guest'>
            <input type='text' id='orator' name='orator' required>
            <label>Speaker</label>
        </div>
        ";
    }

    /**
     * Render form for minutes
     *
     * @param Suggestion|Presentation $Presentation
     * @return string
     */
    private static function minuteForm($Presentation)
    {
        return "
        <div class='form_description'>
            Provide presentation information
        </div>

        <div class='form-group'>
            <input type='text' id='title' name='title' value='Minutes for session held on 
            {$Presentation->date}' disabled/>
            <label>Title</label>
        </div>
        ";
    }
}
