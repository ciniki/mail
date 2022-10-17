<?php
//
// Description
// -----------
// This script exports the raw_content from ciniki_mail to files.
//

//
// Script must run as www-data
//
if( posix_getuid() != 33 ) {
    print "You must use sudo -u www-data to run this script.\n\n";
    exit;
}

//
// This script should run as www-data and will create the setup for an apache ssl domain
//
global $ciniki_root;
$ciniki_root = dirname(__FILE__);
if( !file_exists($ciniki_root . '/ciniki-api.ini') ) {
    $ciniki_root = dirname(dirname(dirname(dirname(__FILE__))));
}
// loadMethod is required by all function to ensure the functions are dynamically loaded
require_once($ciniki_root . '/ciniki-mods/core/private/loadMethod.php');
require_once($ciniki_root . '/ciniki-mods/core/private/init.php');
require_once($ciniki_root . '/ciniki-mods/core/private/checkModuleFlags.php');

$rc = ciniki_core_init($ciniki_root, 'rest');
if( $rc['stat'] != 'ok' ) {
    error_log("unable to initialize core");
    exit(1);
}

//
// Setup the $ciniki variable to hold all things ciniki.  
//
$ciniki = $rc['ciniki'];
$ciniki['session']['user']['id'] = -3;  // Setup to Ciniki Robot

ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUUID');
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbInsert');
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'makePermalink');
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDetailsQueryDash');
ciniki_core_loadMethod($ciniki, 'ciniki', 'cron', 'private', 'logMsg');

//
// Load tenants
//
$strsql = "SELECT id, uuid "
    . "FROM ciniki_tenants "
    . "";
if( isset($argv[1]) && $argv[1] != '' ) {
    $strsql .= "WHERE id = '" . ciniki_core_dbQuote($ciniki, $argv[1]) . "' ";
}
$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.core', 'item');
if( $rc['stat'] != 'ok' ) {
    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.mail.91', 'msg'=>'Unable to load item', 'err'=>$rc['err']));
}
$tenants = array();
foreach($rc['rows'] as $row) {
    $tenants[$row['id']] = $ciniki['config']['ciniki.core']['storage_dir'] . '/' . $row['uuid'][0] . '/' . $row['uuid'] . '/ciniki.mail';
}

//
// Load mail
//
foreach($tenants as $tnid => $uuid) {
    error_log('processing: ' . $tnid);
    $strsql = "SELECT id, uuid, tnid, content "
        . "FROM ciniki_mail_attachments "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.core', 'item');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.mail.92', 'msg'=>'Unable to load item', 'err'=>$rc['err']));
    }
    foreach($rc['rows'] as $row) {
        $mail_dir = $uuid . '/' . $row['uuid'][0];
        if( !file_exists($mail_dir) ) {
            mkdir($mail_dir, 0755, true);
        }
        $attachment_file = $mail_dir . '/' . $row['uuid'] . '.attachment';
        
        if( !file_exists($attachment_file) && $row['content'] != '' ) {
            file_put_contents($attachment_file, $row['content']);
        }
    }
}

exit(0);
?>
