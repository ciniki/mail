<?php
//
// Description
// -----------
// This script will check for the mail files on disk, and remove content from database
//

//
// Script must run as www-data
//
if( posix_getuid() != 33 ) {
    print "You must use sudo -u www-data to run this script.\n\n";
    print "purgeOldMail <tnid> <year>\n\n";
    exit;
}

if( !isset($argv[1]) || $argv[1] == '' ) {
    print "Missing tenant id.\n\n";
    print "purgeOldMail <tnid> <year>\n\n";
    exit;
}

if( !isset($argv[2]) || $argv[2] == '' ) {
    print "Missing year.\n\n";
    print "purgeOldMail <tnid> <year>\n\n";
    exit;
}
$tnid = $argv[1];
$year = $argv[2];

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
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
ciniki_core_loadMethod($ciniki, 'ciniki', 'mail', 'private', 'messagePurge');

//
// Load tenants
//
$strsql = "SELECT id, uuid "
    . "FROM ciniki_tenants "
    . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
    . '';
$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.core', 'item');
if( $rc['stat'] != 'ok' ) {
    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.mail.80', 'msg'=>'Unable to load item', 'err'=>$rc['err']));
}
$storage_dir = '';
foreach($rc['rows'] as $row) {
    $storage_dir = $ciniki['config']['ciniki.core']['storage_dir'] . '/' . $row['uuid'][0] . '/' . $row['uuid'] . '/ciniki.mail';
}

//
// Load mail
//
$strsql = "SELECT id, uuid, tnid, customer_id, customer_email, date_sent "
    . "FROM ciniki_mail "
    . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
    . "AND mailing_id > 0 "
    . "AND YEAR(date_sent) = '" . ciniki_core_dbQuote($ciniki, $year) . "' "
    . "";
$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.core', 'item');
if( $rc['stat'] != 'ok' ) {
    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.mail.81', 'msg'=>'Unable to load item', 'err'=>$rc['err']));
}
foreach($rc['rows'] as $row) {
    print "Purge: {$row['date_sent']} to {$row['customer_email']}\n";
    $rc = ciniki_mail__messagePurge($ciniki, $row['tnid'], $row['id']);
    if( $rc['stat'] != 'ok' ) {
        print_r($rc);
        exit;
    }
}

exit(0);
?>
