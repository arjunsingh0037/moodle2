<?php

/**
 * *************************************************************************
 * *                       CCI - Global Search                          **
 * *************************************************************************
 * @package     block                                                   **
 * @subpackage  cci globalsearch                                        **
 * @name        CCI Global Search                                       **
 * @copyright   Dhruv Infoline Pvt Ltd                                  **
 * @author      Arjun Singh (arjunsingh@elearn10.com)                   **
 * @license     http://lmsofindia.com                                   **
 * *************************************************************************
 * ************************************************************************ */





class block_cci_globalsearch extends block_base
{

    /**
     * Init the block
     */
    function init()
    {
        $this->title = get_string('pluginname', 'block_cci_globalsearch');
    }

    /**
     * Applicable formats
     * @return array
     */
    function applicable_formats()
    {
        return array('all' => true);
    }

    /**
     * 
     * @return boolean
     */
    function instance_allow_multiple()
    {
        return false;
    }

    /**
     * Return the content
     * @global stdClass $CFG
     * @return string
     */
    function get_content()
    {
        global $CFG;

        require_once($CFG->dirroot . '/course/lib.php');
        $course = $this->page->course;

        if ($this->content !== NULL)
        {
            return $this->content;
        }

        $this->content = new stdClass;

        $context = context_system::instance();
        

        $this->content->footer = '&nbsp;';
        $text = "<div class=\"searchform\">";
        $text .= "<form style='display:inline;' name='cci_globalsearch_form' id='block_cci_globalsearch_form' action='$CFG->wwwroot/blocks/cci_globalsearch/results.php' method='post'>";
        $text .= "<fieldset class=\"invisiblefieldset\">";
        $text .= get_string('searchfor', 'block_cci_globalsearch') . "<br>";
        $text .= "<input type='hidden' name='courseid' id='courseid' value='$course->id'/>";
        $text .= "<input type='text' name='q' id='q' value=''/>";
        $text .= "<br><input type='submit' id='searchform_button' value='" . get_string('submit', 'block_cci_globalsearch') . "'>";
        $text .= "</fieldset>";
        $text .= "</form>";
        $text .= "</div>";
        $this->content->text = $text;

        return $this->content;
    }

    /**
     * The block should only be dockable when the title of the block is not empty
     * and when parent allows docking.
     *
     * @return bool
     */
    public function instance_can_be_docked()
    {
        return (!empty($this->title) && parent::instance_can_be_docked());
    }

}
