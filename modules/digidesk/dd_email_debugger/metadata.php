<?php
/**
 *
 *     |o     o    |          |
 * ,---|.,---..,---|,---.,---.|__/
 * |   |||   |||   ||---'`---.|  \
 * `---'``---|``---'`---'`---'`   `
 *       `---'    [media solutions]
 *
 * @copyright   (c) digidesk - media solutions
 * @link        http://www.digidesk.de
 * @author      digidesk - media solutions
 * @version     Git: $Id$
 */

/**
 * Metadata version
 */
$sMetadataVersion = '1.1';

/**
 * Module information
 */
$aModule = array(
    'id'          => 'dd_email_debugger',
    'title'       => '<img src="../modules/digidesk/dd_email_debugger/ddicon.png" width="15" height="15"> digidesk - E-Mail Template Debugger',
    'description' => array(
        'de' => 'Dieses Modul ermöglicht das einfache Debugging von E-Mails im OXID eShop.',
    ),
    'thumbnail'   => 'module.png',
    'version'     => '1.0.0',
    'author'      => 'digidesk - media solutions',
    'url'         => 'http://www.digidesk.de/',
    'email'       => 'support@digidesk.de',
    'extend'      => array(
        'oxemail' => 'digidesk/dd_email_debugger/core/dd_email_debugger_oxemail',
        'oxorder' => 'digidesk/dd_email_debugger/application/models/dd_email_debugger_oxorder',
    ),
    'files' => array(
        'dd_email_debugger' => 'digidesk/dd_email_debugger/application/controllers/admin/dd_email_debugger.php',
    ),
    'templates' => array(
        'dd_email_debugger.tpl' => 'digidesk/dd_email_debugger/application/views/admin/tpl/dd_email_debugger.tpl',
    ),
    /*'events' => array(
        'onActivate'   => 'dd_email_debugger_events::onActivate',
        'onDeactivate' => 'dd_email_debugger_events::onDeactivate'
    ),*/
);