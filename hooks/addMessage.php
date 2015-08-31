<?php
//
// Description
// -----------
// Merge the mail from secondary_customer_id into primary_customer_id
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_mail_hooks_addMessage(&$ciniki, $business_id, $args) {

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbInsert');

	//
	// Check arguments
	//
	if( !isset($args['customer_id']) || $args['customer_id'] == '' ) {
		$args['customer_id'] = 0;
	}
	if( !isset($args['customer_email']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2465', 'msg'=>'No email specified'));
	}
	if( !isset($args['customer_name']) ) {
		$args['customer_name'] = '';
	}
	if( !isset($args['flags']) ) {
		$args['flags'] = '0';
	}

	//
	// Check for both html and text content
	//
	if( !isset($args['text_content']) && !isset($args['html_content']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2466', 'msg'=>'No message specified'));
	} elseif( isset($args['text_content']) && !isset($args['html_content']) ) {
		$args['text_content'] = strip_tags($args['html_content']);
	} elseif( isset($args['html_content']) && !isset($args['text_content']) ) {
		$args['html_content'] = $args['text_content'];
	}

	//
	// Get a UUID for use in permalink
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUUID');
	$rc = ciniki_core_dbUUID($ciniki, 'ciniki.mail');
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2503', 'msg'=>'Unable to get a new UUID', 'err'=>$rc['err']));
	}
	$args['uuid'] = $rc['uuid'];

	//
	// Add the message
	//
	$strsql = "INSERT INTO ciniki_mail (uuid, business_id, mailing_id, unsubscribe_key, "
		. "survey_invite_id, "
		. "customer_id, customer_name, customer_email, flags, status, "
		. "mail_to, mail_cc, mail_from, "
		. "subject, html_content, text_content, "
		. "date_added, last_updated) VALUES ("
		. "'" . ciniki_core_dbQuote($ciniki, $args['uuid']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $business_id) . "', "
		. "0, '', 0, ";
	$strsql .= "'" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "', ";
	$strsql .= "'" . ciniki_core_dbQuote($ciniki, $args['customer_name']) . "', ";
	$strsql .= "'" . ciniki_core_dbQuote($ciniki, $args['customer_email']) . "', ";
	$strsql .= "'" . ciniki_core_dbQuote($ciniki, $args['flags']) . "', ";
	$strsql .= "'10', '', '', '', ";
	$strsql .= "'" . ciniki_core_dbQuote($ciniki, $args['subject']) . "', ";
	$strsql .= "'" . ciniki_core_dbQuote($ciniki, $args['html_content']) . "', ";
	$strsql .= "'" . ciniki_core_dbQuote($ciniki, $args['text_content']) . "', ";
	$strsql .= "UTC_TIMESTAMP(), UTC_TIMESTAMP())";

	$rc = ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.mail');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$mail_id = $rc['insert_id'];	
	
	//
	// Add the attachments
	//
	if( isset($args['attachments']) ) {
		foreach($args['attachments'] as $attachment) {
			if( isset($attachment['filename']) && isset($attachment['content']) ) {
				$attachment['mail_id'] = $mail_id;
				$rc = ciniki_core_objectAdd($ciniki, $business_id, 'ciniki.mail.attachment', $attachment, 0x04);
				if( $rc['stat'] != 'ok' ) {
					return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2469', 'msg'=>'Unable to add attachment', 'err'=>$rc['err']));
				}
			}
		}
	}

	//
	// Add the object references
	//
	if( isset($args['object']) && $args['object'] != '' && isset($args['object_id']) && $args['object_id'] != '' ) {
		$rc = ciniki_core_objectAdd($ciniki, $business_id, 'ciniki.mail.objref', array(
			'mail_id'=>$mail_id,
			'object'=>$args['object'],
			'object_id'=>$args['object_id'],
			), 0x04);
		if( $rc['stat'] != 'ok' ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2467', 'msg'=>'Unable to add object reference', 'err'=>$rc['err']));
		}

	}

	return array('stat'=>'ok', 'id'=>$mail_id);
}
?>
