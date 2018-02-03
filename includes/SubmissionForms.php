<?php

namespace includes;

/**
 * Handles submission forms views
 */
class SubmissionForms
{

    /**
     * Get form for submission type
     *
     * @param string $type: submission type
     * @param Submission|Presentation|Suggestion $obj
     * @return string
     */
    public static function get($type, $obj = null)
    {
        if (empty($type)) {
            $type = 'paper';
        }

        if (method_exists(__CLASS__, $type)) {
            try {
                return self::$type($obj);
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
        return "<div class='sys_msg warning'>The selected type ['{$type}'] is not available</div>";
    }

    /**
     * Render form for presentation about methodology
     *
     * @param Suggestion|Presentation $Presentation
     * @return string
     */
    private static function methodology($Presentation)
    {
        return self::paper($Presentation);
    }

    /**
     * Render form for presentation about one's research
     *
     * @param Suggestion|Presentation $Presentation
     * @return string
     */
    private static function research($Presentation)
    {
        return self::paper($Presentation);
    }

    /**
     * Render form for research article
     *
     * @param Suggestion|Presentation $Presentation
     * @return string
     */
    private static function paper($Presentation)
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
    private static function guest($Presentation)
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
    private static function minute($Presentation)
    {
        if (\property_exists($Presentation, 'date')) {
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
        } else {
            return self::notFound('minute');
        }
    }
}
