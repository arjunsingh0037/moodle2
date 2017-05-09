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



**
 * *************************************************************************
 * ************************************************************************ */

defined('MOODLE_INTERNAL') || die();

$capabilities = array(
    'block/cci_globalsearch:addinstance' => array(
        'riskbitmask' => RISK_SPAM | RISK_XSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        ),

        'clonepermissionsfrom' => 'moodle/site:manageblocks'
    ),
        'block/cci_globalsearch:myaddinstance' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
            'user' => CAP_ALLOW
        ),

        'clonepermissionsfrom' => 'moodle/my:manageblocks'
    ),
);
