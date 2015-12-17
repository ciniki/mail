<?php
//
// Description
// -----------
// This method returns the mail message.
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
function ciniki_mail_messageAction(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'message_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Message'), 
		'action'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Action'),
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
    $rc = ciniki_mail_checkAccess($ciniki, $args['business_id'], 'ciniki.mail.messageAction'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');

	//
	// Get the current message status
	//
	$strsql = "SELECT id, flags, status "
		. "FROM ciniki_mail "
		. "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['message_id']) . "' "
		. "AND business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.mail', 'message');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['message']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2598', 'msg'=>'Unable to find message'));
	}
	$message = $rc['message'];

	//
	// Actions
	//
	if( $args['action'] == 'queue' ) {
		if( $message['status'] == '7' ) {
			$rc = ciniki_core_objectUpdate($ciniki, $args['business_id'], 'ciniki.mail.message', $args['message_id'], array('status'=>'10'), 0x07);
			if( $rc['stat'] != 'ok' ) {
				return $rc;
			}
		}
	} elseif( $args['action'] == 'tryagain' ) {
		if( $message['status'] == '20' || $message['status'] == '50' ) {
			$rc = ciniki_core_objectUpdate($ciniki, $args['business_id'], 'ciniki.mail.message', $args['message_id'], array('status'=>'10'), 0x07);
			if( $rc['stat'] != 'ok' ) {
				return $rc;
			}
		}
	} elseif( $args['action'] == 'delete' ) {
		if( $message['status'] != '60' ) {
			$rc = ciniki_core_objectUpdate($ciniki, $args['business_id'], 'ciniki.mail.message', $args['message_id'], array('status'=>'60'), 0x07);
			if( $rc['stat'] != 'ok' ) {
				return $rc;
			}
		}
	}
	
	return array('stat'=>'ok');
}
?>
