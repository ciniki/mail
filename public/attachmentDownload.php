<?php
//
// Description
// -----------
// Download a file from a message.
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_mail_attachmentDownload(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'message_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Message'),
        'attachment_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Attachment'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Make sure this module is activated, and
    // check permission to run this function for this tenant
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'mail', 'private', 'checkAccess');
    $rc = ciniki_mail_checkAccess($ciniki, $args['tnid'], 'ciniki.mail.attachmentDownload');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the tenant storage directory
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'hooks', 'storageDir');
    $rc = ciniki_tenants_hooks_storageDir($ciniki, $args['tnid'], array());
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $mail_dir = $rc['storage_dir'] . '/ciniki.mail';

    //
    // Get the attachment
    //
    $strsql = "SELECT messages.id AS message_id, "
        . "attachments.id AS attachment_id, "
        . "attachments.uuid, "
        . "attachments.filename "
        . "FROM ciniki_mail AS messages "
        . "INNER JOIN ciniki_mail_attachments AS attachments ON ( "
            . "messages.id = attachments.mail_id "
            . "AND attachments.id = '" . ciniki_core_dbQuote($ciniki, $args['attachment_id']) . "' "
            . "AND attachments.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "WHERE messages.id = '" . ciniki_core_dbQuote($ciniki, $args['message_id']) . "' "
        . "AND messages.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.mail', 'attachment');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.mail.95', 'msg'=>'Unable to load attachment', 'err'=>$rc['err']));
    }
    if( !isset($rc['attachment']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.mail.96', 'msg'=>'Unable to find requested attachment'));
    }
    $attachment = $rc['attachment'];

    $storage_filename = $mail_dir . '/' . $attachment['uuid'][0] . '/' . $attachment['uuid'] . '.attachment';
    $binary_content = file_get_contents($storage_filename);
    if( is_file($storage_filename) ) {
        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); 
        header("Last-Modified: " . gmdate("D,d M YH:i:s") . " GMT"); 
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');

        // Specify Filename
        header('Content-Disposition: attachment;filename="' . $attachment['filename'] . '"');
        header('Content-Length: ' . strlen($binary_content));
        header('Cache-Control: max-age=0');

        print $binary_content;

        return array('stat'=>'exit');
    }

    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.mail.97', 'msg'=>'Attachment does not exist'));
}
?>
