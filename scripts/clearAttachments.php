<?php
//
// Description
// -----------
// This script will check for attachments on disk that no longer exist in the database
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

$strsql = "SELECT id, uuid, mail_id, filename "
    . "FROM ciniki_mail_attachments "
    . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
    . "";
ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.mail', array(
    array('container'=>'attachments', 'fname'=>'uuid', 
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
$mail_storage_dir = $rc['storage_dir'] . '/ciniki.mail';

foreach(['0','1','2','3','4','5','6','7','8','9','a','b','c','d','e','f'] as $d1) {
   
    $dir = $mail_storage_dir . '/' . $d1;
    if( file_exists($dir) ) {
        if( $h = opendir($dir) ) {
            while (false !== ($file = readdir($h))) {
                if ($file == '.' || $file == '..') {
                    continue;
                }
                if( preg_match("/^(.*)\.attachment/", $file, $m) ) {
                    if( !isset($attachments[$m[1]]) ) {
                        print "Remove: $dir/$file\n";
                        unlink($dir . '/' . $file);
                    }
                }
            }
        } else {
            print "Unable to open dir\n";
        }
    }
}

exit(0);
?>
