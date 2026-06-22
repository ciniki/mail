<?php
//
// Description
// -----------
// This method will remove the message and all attachments/references from the database.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant to mail message belongs to.
// message_id:          The ID of the message to delete.
//
// Returns
// -------
//
function ciniki_mail__messagePurge($ciniki, $tnid, $mail_id) {
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');

    //
    // Get the tenant storage directory
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'hooks', 'storageDir');
    $rc = ciniki_tenants_hooks_storageDir($ciniki, $tnid, array());
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $mail_dir = $rc['storage_dir'] . '/ciniki.mail';

    //
    // Get the message
    //
    $strsql = "SELECT id, status, uuid "
        . "FROM ciniki_mail "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND id = '" . ciniki_core_dbQuote($ciniki, $mail_id) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.mail', 'message');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['message']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.mail.50', 'msg'=>'The message does not exist'));
    }
    $message = $rc['message'];

    //
    // Remove the mail attachments
    //
    $strsql = "SELECT id, uuid "
        . "FROM ciniki_mail_attachments "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND mail_id = '" . ciniki_core_dbQuote($ciniki, $mail_id) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.mail', 'item');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['rows']) && count($rc['rows']) > 0 ) {
        $items = $rc['rows'];
        foreach($items as $iid => $item) {
            //
            // Remove the files
            //
            $filename = $mail_dir . '/' . $item['uuid'][0] . '/' . $item['uuid'] . '.attachment';
            if( file_exists($filename) ) {
                unlink($filename);
            }

            //
            // Remove the object
            //
            $rc = ciniki_core_objectDelete($ciniki, $tnid, 'ciniki.mail.attachment', 
                $item['id'], $item['uuid'], 0x04);
            if( $rc['stat'] != 'ok' ) {
                return $rc; 
            }
        }
    }

    //
    // Remove the mail objrefs
    //
    $strsql = "SELECT id, uuid "
        . "FROM ciniki_mail_objrefs "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND mail_id = '" . ciniki_core_dbQuote($ciniki, $mail_id) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.mail', 'item');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['rows']) && count($rc['rows']) > 0 ) {
        $items = $rc['rows'];
        foreach($items as $iid => $item) {
            $rc = ciniki_core_objectDelete($ciniki, $tnid, 'ciniki.mail.objref', 
                $item['id'], $item['uuid'], 0x04);
            if( $rc['stat'] != 'ok' ) {
                return $rc; 
            }
        }
    }

    //
    // Remove the mail log
    //
    $strsql = "SELECT id, uuid "
        . "FROM ciniki_mail_log "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND mail_id = '" . ciniki_core_dbQuote($ciniki, $mail_id) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.mail', 'item');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['rows']) && count($rc['rows']) > 0 ) {
        $items = $rc['rows'];
        foreach($items as $iid => $item) {
            $rc = ciniki_core_objectDelete($ciniki, $tnid, 'ciniki.mail.log', 
                $item['id'], $item['uuid'], 0x04);
            if( $rc['stat'] != 'ok' ) {
                return $rc; 
            }
        }
    }

    //
    // Remove the mail files
    //
    $html_filename = $mail_dir . '/' . $message['uuid'][0] . '/' . $message['uuid'] . '.html';
    if( file_exists($html_filename) ) {
        unlink($html_filename);
    }
    $text_filename = $mail_dir . '/' . $message['uuid'][0] . '/' . $message['uuid'] . '.text';
    if( file_exists($text_filename) ) {
        unlink($text_filename);
    }

    //
    // Delete the message
    //
    $rc = ciniki_core_objectDelete($ciniki, $tnid, 'ciniki.mail.message', $mail_id, $message['uuid'], 0x04);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    return array('stat'=>'ok');
}
?>
