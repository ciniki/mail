<?php
//
// Description
// -----------
// Return the list of subscriptions available to another module, and if there is a mailing and subscription for the object.
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_mail_hooks_objectMessages($ciniki, $business_id, $args) {

	//
	// Load the status maps for the text description of each status
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'mail', 'private', 'maps');
	$rc = ciniki_mail_maps($ciniki);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$maps = $rc['maps'];

	//
	// Load intl date settings
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'intlSettings');
	$rc = ciniki_businesses_intlSettings($ciniki, $business_id);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$intl_timezone = $rc['settings']['intl-default-timezone'];
	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'datetimeFormat');
	$datetime_format = ciniki_users_datetimeFormat($ciniki, 'php');

	//
	// Check for messages
	//
	if( isset($args['object']) && $args['object'] != '' 
		&& isset($args['object_id']) && $args['object_id'] != ''
		) {
		//
		// Check if there is any mail for this object
		//
		$strsql = "SELECT ciniki_mail.id, "
			. "ciniki_mail.status, "
			. "ciniki_mail.status AS status_text, "
			. "ciniki_mail.date_sent, "
			. "ciniki_mail.customer_id, "
			. "ciniki_mail.customer_name, "
			. "ciniki_mail.customer_email, "
			. "ciniki_mail.subject "
			. "FROM ciniki_mail_objrefs, ciniki_mail "
			. "WHERE ciniki_mail_objrefs.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "AND ciniki_mail_objrefs.object = '" . ciniki_core_dbQuote($ciniki, $args['object']) . "' "
			. "AND ciniki_mail_objrefs.object_id = '" . ciniki_core_dbQuote($ciniki, $args['object_id']) . "' "
			. "AND ciniki_mail_objrefs.mail_id = ciniki_mail.id "
			. "AND ciniki_mail.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "";
		$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.mail', array(
			array('container'=>'mail', 'fname'=>'id', 'name'=>'message',
				'fields'=>array('id', 'status', 'status_text', 'date_sent', 'customer_name', 'customer_email', 'subject'),
				'maps'=>array('status_text'=>$maps['mail']['status']),
				'utctotz'=>array('date_sent'=>array('timezone'=>$intl_timezone, 'format'=>$datetime_format)),
				),
			));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( isset($rc['mail']) ) {
			return array('stat'=>'ok', 'messages'=>$rc['mail']);
		}
	}

	return array('stat'=>'ok');
}
?>
