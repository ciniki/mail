<?php
//
// Description
// -----------
// This method returns the list of labels available for a business and the messages counts were applicable.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:			The ID of the business to add the mail mailing to.
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
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
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
    $rc = ciniki_mail_checkAccess($ciniki, $args['business_id'], 'ciniki.mail.messageLabels', 0); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }

	$rsp = array('stat'=>'ok', 'labels'=>array(
//		array('label'=>array('name'=>'Inbox', 'status'=>40)),
//		array('label'=>array('name'=>'Flagged', 'status'=>41)),
//		array('label'=>array('name'=>'Drafts', 'status'=>5)),
		array('label'=>array('name'=>'Pending', 'status'=>7)),
		array('label'=>array('name'=>'Queued', 'status'=>10)),
		array('label'=>array('name'=>'Queue Failures', 'status'=>15)),
		array('label'=>array('name'=>'Sending', 'status'=>20)),
		array('label'=>array('name'=>'Sent', 'status'=>30)),
		array('label'=>array('name'=>'Failed', 'status'=>50)),
		array('label'=>array('name'=>'Trash', 'status'=>60)),
		));

	//
	// Get the counts
	//
	$strsql = "SELECT status, COUNT(id) AS num_messages "
		. "FROM ciniki_mail "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
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
		if( isset($rc['status'][$label['label']['status']]) ) {
			$rsp['labels'][$lid]['label']['num_messages'] = $rc['status'][$label['label']['status']]['num_messages'];
		}
	}

	return $rsp;
}
?>
