<?php
//
// Description
// -----------
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:			The ID of the business to mail mailing belongs to.
// mailing_id:			The ID of the mailing to get.
//
// Returns
// -------
//
function ciniki_mail_mailingGet($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		'mailing_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Mailing'),
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
    $rc = ciniki_mail_checkAccess($ciniki, $args['business_id'], 'ciniki.mail.mailingGet', 0); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
	$date_format = ciniki_users_dateFormat($ciniki);

	//
	// Get the main information
	//
	$strsql = "SELECT "
		. "ciniki_mailings.id, ciniki_mailings.type, ciniki_mailings.type AS type_text, "
		. "ciniki_mailings.status, ciniki_mailings.status AS status_text, ciniki_mailings.theme, "
		. "ciniki_mailings.survey_id, ciniki_mailings.subject, "
		. "ciniki_mailings.html_content, ciniki_mailings.text_content, "
		. "ciniki_mailings.date_started, ciniki_mailings.date_sent, "
		. "ciniki_subscriptions.id AS subscription_ids, "
		. "ciniki_subscriptions.name AS subscription_names "
		. "FROM ciniki_mailings "
		. "LEFT JOIN ciniki_mailing_subscriptions ON (ciniki_mailings.id = ciniki_mailing_subscriptions.mailing_id "
			. "AND ciniki_mailing_subscriptions.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "') "
		. "LEFT JOIN ciniki_subscriptions ON (ciniki_mailing_subscriptions.subscription_id = ciniki_subscriptions.id "
			. "AND ciniki_subscriptions.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "') "
		. "WHERE ciniki_mailings.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND ciniki_mailings.id = '" . ciniki_core_dbQuote($ciniki, $args['mailing_id']) . "' "
		. "ORDER BY ciniki_mailings.id ASC ";

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.mail', array(
		array('container'=>'mailings', 'fname'=>'id', 'name'=>'mailing',
			'fields'=>array('id', 'type', 'type_text', 'status', 'status_text', 'theme', 'survey_id', 'subject', 
				'html_content', 'text_content', 'date_started', 'date_sent', 'subscription_ids', 'subscription_names'),
			'idlists'=>array('subscription_ids'), 
			'lists'=>array('subscription_names'),
			'maps'=>array(
				'status_text'=>array('10'=>'Creation', '20'=>'Approved', '30'=>'Queueing', '40'=>'Sending', '50'=>'Sent', '60'=>'Deleted'),
				'type_text'=>array('10'=>'General', '20'=>'Newsletter', '30'=>'Alert'),
				),
			),
//		array('container'=>'subscriptions', 'fname'=>'subscription_id', 'name'=>'subscription',
//			'fields'=>array('id'=>'subscription_id', 'name'=>'subscription_name')),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['mailings']) && !isset($rc['mailings'][0]['mailing']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1028', 'msg'=>'Unable to find mailing'));
	}
	
	return array('stat'=>'ok', 'mailing'=>$rc['mailings'][0]['mailing']);
}
?>
