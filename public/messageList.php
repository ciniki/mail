<?php
//
// Description
// -----------
// This method returns the list of messages
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
function ciniki_mail_messageList(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'status'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Label'), 
        'offset'=>array('required'=>'no', 'blank'=>'no', 'default'=>'0', 'name'=>'Offset'), 
        'limit'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Limit'), 
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
    $rc = ciniki_mail_checkAccess($ciniki, $args['business_id'], 'ciniki.mail.messageList', 0); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }

	//
	// Get the business date/time settings
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'intlSettings');
	$rc = ciniki_businesses_intlSettings($ciniki, $args['business_id']);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$intl_timezone = $rc['settings']['intl-default-timezone'];

	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
	$date_format = ciniki_users_dateFormat($ciniki, 'php');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'timeFormat');
	$time_format = ciniki_users_timeFormat($ciniki, 'php');

	//
	// Get the messages for the label
	//
	$strsql = "SELECT id, status, subject, customer_id, customer_name, customer_email, from_name, from_email, "
        . "IF(text_content<>'',text_content,html_content) AS snippet, ";
	switch($args['status']) {
		case '5': $strsql .= "date_added AS mail_date "; break;
		case '7': $strsql .= "date_added AS mail_date "; break;
		case '10': $strsql .= "date_added AS mail_date "; break;
		case '15': $strsql .= "date_added AS mail_date "; break;
		case '20': $strsql .= "date_added AS mail_date "; break;
		case '30': $strsql .= "date_sent AS mail_date "; break;
		case '40': $strsql .= "date_received AS mail_date "; break;
		case '41': $strsql .= "date_received AS mail_date "; break;
		case '50': $strsql .= "date_added AS mail_date "; break;
		case '60': $strsql .= "date_added AS mail_date "; break;
	}

	$strsql .= "FROM ciniki_mail "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND status = '" . ciniki_core_dbQuote($ciniki, $args['status']) . "' "
		. "";
	switch($args['status']) {
		case '5': $strsql .= "ORDER BY date_added DESC "; break;
		case '7': $strsql .= "ORDER BY date_added DESC "; break;
		case '10': $strsql .= "ORDER BY date_added DESC "; break;
		case '15': $strsql .= "ORDER BY date_added DESC "; break;
		case '20': $strsql .= "ORDER BY date_added DESC "; break;
		case '30': $strsql .= "ORDER BY date_sent DESC "; break;
		case '40': $strsql .= "ORDER BY date_received DESC "; break;
		case '41': $strsql .= "ORDER BY date_received DESC "; break;
		case '50': $strsql .= "ORDER BY date_added DESC "; break;
		case '60': $strsql .= "ORDER BY date_added DESC "; break;
	}

	if( isset($args['offset']) && $args['offset'] > 0 && isset($args['limit']) && $args['limit'] > 0 ) {
		$strsql .= "LIMIT " . filter_var($args['offset'], FILTER_SANITIZE_NUMBER_INT) . ', ' . filter_var($args['limit'], FILTER_SANITIZE_NUMBER_INT);
	} elseif( isset($args['limit']) && $args['limit'] > 0 ) {
		$strsql .= "LIMIT " . filter_var($args['limit'], FILTER_SANITIZE_NUMBER_INT);
	}

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.mail', array(
		array('container'=>'messages', 'fname'=>'id', 'name'=>'message',
			'fields'=>array('id', 'status', 'subject', 'customer_id', 'customer_name', 'customer_email', 'from_name', 'from_email', 'snippet', 'mail_time'=>'mail_date', 'mail_date'),
			'utctotz'=>array('mail_date'=>array('timezone'=>$intl_timezone, 'format'=>$date_format),
				'mail_time'=>array('timezone'=>$intl_timezone, 'format'=>$time_format)),
			),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( isset($rc['messages']) ) {
		foreach($rc['messages'] as $mid => $message) {
			$snippet = preg_replace('/<\/p>/', ' ', $message['message']['snippet']);
			$snippet = preg_replace('/<style>.*<\/style>/m', '', $snippet);
			$snippet = strip_tags($snippet);
			if( strlen($snippet) > 150) {
				$snippet = substr($snippet, 0, 150);
			}
			$rc['messages'][$mid]['message']['snippet'] = $snippet;
		}
		return array('stat'=>'ok', 'messages'=>$rc['messages']);
	} 
	return array('stat'=>'ok', 'messages'=>array());
}
?>
