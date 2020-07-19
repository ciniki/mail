<?php
//
// Description
// -----------
// This method returns the list of labels available for a tenant and the messages counts were applicable.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant to add the mail mailing to.
//
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_mail_messageLabels(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
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
    $rc = ciniki_mail_checkAccess($ciniki, $args['tnid'], 'ciniki.mail.messageLabels', 0); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }

    $rsp = array('stat'=>'ok', 'labels'=>array());
    if( isset($ciniki['tenant']['modules']['ciniki.mail']['flags']) && ($ciniki['tenant']['modules']['ciniki.mail']['flags']&0x10) > 0 ) {
        $rsp['labels'][] = array('name'=>'Inbox', 'status'=>40);
    }
//      array('name'=>'Flagged', 'status'=>41),
//      array('name'=>'Drafts', 'status'=>5),
    $rsp['labels'][] = array('name'=>'Pending', 'status'=>7);
    $rsp['labels'][] = array('name'=>'Queued', 'status'=>10);
    $rsp['labels'][] = array('name'=>'Queue Failures', 'status'=>15);
    $rsp['labels'][] = array('name'=>'Sending', 'status'=>20);
    $rsp['labels'][] = array('name'=>'Sent', 'status'=>30);
    $rsp['labels'][] = array('name'=>'Failed', 'status'=>50);
    $rsp['labels'][] = array('name'=>'Trash', 'status'=>60);

    //
    // Get the counts
    //
    $strsql = "SELECT status, COUNT(id) AS num_messages "
        . "FROM ciniki_mail "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND (status <> 30 " // Don't count sent messages
            . "OR (status = 40 AND (flags&0x10)=0) " // Only count unread in inbox
            . ") "
        . "GROUP BY status "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.mail', array(
        array('container'=>'status', 'fname'=>'status', 'fields'=>array('status', 'num_messages')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    foreach($rsp['labels'] as $lid => $label) {
        if( isset($rc['status'][$label['status']]) ) {
            $rsp['labels'][$lid]['num_messages'] = $rc['status'][$label['status']]['num_messages'];
        }
    }

    return $rsp;
}
?>
