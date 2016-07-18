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
// business_id:         The ID of the business to mail message belongs to.
// message_id:          The ID of the message to delete.
//
// Returns
// -------
//
function ciniki_mail_messagePurge($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'message_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Message'),
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];

    //  
    // Make sure this module is activated, and
    // check permission to run this function for this business
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'mail', 'private', 'checkAccess');
    $rc = ciniki_mail_checkAccess($ciniki, $args['business_id'], 'ciniki.mail.messagePurge'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $modules = $rc['modules'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');

    //
    // Get the message
    //
    $strsql = "SELECT id, status, uuid "
        . "FROM ciniki_mail "
        . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "AND id = '" . ciniki_core_dbQuote($ciniki, $args['message_id']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.mail', 'message');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['message']) ) {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2599', 'msg'=>'The message does not exist'));
    }
    $message = $rc['message'];

    //  
    // Turn off autocommit
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.mail');
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // Remove the mail attachments
    //
    $strsql = "SELECT id, uuid "
        . "FROM ciniki_mail_attachments "
        . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "AND mail_id = '" . ciniki_core_dbQuote($ciniki, $args['message_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.mail', 'item');
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.mail');
        return $rc;
    }
    if( isset($rc['rows']) && count($rc['rows']) > 0 ) {
        $items = $rc['rows'];
        foreach($items as $iid => $item) {
            $rc = ciniki_core_objectDelete($ciniki, $args['business_id'], 'ciniki.mail.attachment', 
                $item['id'], $item['uuid'], 0x04);
            if( $rc['stat'] != 'ok' ) {
                ciniki_core_dbTransactionRollback($ciniki, 'ciniki.mail');
                return $rc; 
            }
        }
    }

    //
    // Remove the mail objrefs
    //
    $strsql = "SELECT id, uuid "
        . "FROM ciniki_mail_objrefs "
        . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "AND mail_id = '" . ciniki_core_dbQuote($ciniki, $args['message_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.mail', 'item');
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.mail');
        return $rc;
    }
    if( isset($rc['rows']) && count($rc['rows']) > 0 ) {
        $items = $rc['rows'];
        foreach($items as $iid => $item) {
            $rc = ciniki_core_objectDelete($ciniki, $args['business_id'], 'ciniki.mail.objref', 
                $item['id'], $item['uuid'], 0x04);
            if( $rc['stat'] != 'ok' ) {
                ciniki_core_dbTransactionRollback($ciniki, 'ciniki.mail');
                return $rc; 
            }
        }
    }

    //
    // Delete the message
    //
    $rc = ciniki_core_objectDelete($ciniki, $args['business_id'], 'ciniki.mail.message', $args['message_id'], $message['uuid'], 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.mail');
        return $rc;
    }

    //
    // Commit the database changes
    //
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.mail');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Update the last_change date in the business modules
    // Ignore the result, as we don't want to stop user updates if this fails.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'updateModuleChangeDate');
    ciniki_businesses_updateModuleChangeDate($ciniki, $args['business_id'], 'ciniki', 'mail');

    return array('stat'=>'ok');
}
?>
