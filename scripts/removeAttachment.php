<?php
//
// Description
// -----------
// This script will remove an attachment that has been sent out via a tenant. This is useful
// when really large attachment gets sent.
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
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'makePermalink');
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDetailsQueryDash');
ciniki_core_loadMethod($ciniki, 'ciniki', 'cron', 'private', 'logMsg');
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');

//
// Make sure tenant specified
//
if( !isset($argv[1]) || $argv[1] == '' ) {
    print "Must include tnid\n";
    exit;
}
$tnid = $argv[1];

//
// Make sure attachment name specified
//
if( !isset($argv[2]) || $argv[2] == '' ) {
    print "Must include attachment name\n";
    exit;
}
$filename = $argv[2];

$strsql = "SELECT id, uuid, mail_id, filename "
    . "FROM ciniki_mail_attachments "
    . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
    . "AND filename = '" . ciniki_core_dbQuote($ciniki, $filename) . "' "
    . "";
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
$rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.mail', array(
    array('container'=>'attachments', 'fname'=>'id', 
        'fields'=>array('id', 'uuid', 'mail_id', 'filename'),
        ),
    ));
if( $rc['stat'] != 'ok' ) {
    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.mail.98', 'msg'=>'Unable to load attachments', 'err'=>$rc['err']));
}
$attachments = isset($rc['attachments']) ? $rc['attachments'] : array();

//
// Get the tenant storage directory
//
ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'hooks', 'storageDir');
$rc = ciniki_tenants_hooks_storageDir($ciniki, $tnid, array());
if( $rc['stat'] != 'ok' ) {
    return $rc;
}
$tenant_storage_dir = $rc['storage_dir'];

//
// Remove the attachments
//
foreach($attachments as $attachment) {
    $f = $tenant_storage_dir . '/ciniki.mail/' . $attachment['filename'][0] . '/' . $attachment['filename'];
    if( file_exists($f) ) {
        unlink(f);
    }

    $rc = ciniki_core_objectDelete($ciniki, $tnid, 'ciniki.mail.attachment', 
        $attachment['id'], $attachment['uuid'], 0x04);
}


exit(0);
?>
