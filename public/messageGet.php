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
function ciniki_mail_messageGet(&$ciniki) {
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
    $rc = ciniki_mail_checkAccess($ciniki, $args['business_id'], 'ciniki.mail.messageGet'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }

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
	// Get the business date/time settings
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'intlSettings');
	$rc = ciniki_businesses_intlSettings($ciniki, $args['business_id']);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$intl_timezone = $rc['settings']['intl-default-timezone'];

	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'datetimeFormat');
	$datetime_format = ciniki_users_datetimeFormat($ciniki, 'php');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
	$date_format = ciniki_users_dateFormat($ciniki, 'php');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'timeFormat');
	$time_format = ciniki_users_timeFormat($ciniki, 'php');

	//
	// Get the messages for the label
	//
	$strsql = "SELECT id, "
		. "customer_id, "
		. "customer_name, "
		. "customer_email, "
		. "from_name, "
		. "from_email, "
		. "flags, "
		. "status, "
		. "status AS status_text, "
		. "date_sent, "
		. "date_received, "
		. "mail_to, "
		. "mail_cc, "
		. "mail_from, "
		. "subject, "
		. "html_content, "
		. "text_content, "
		. "raw_headers, "
		. "raw_content "
		. "FROM ciniki_mail "
		. "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['message_id']) . "' "
		. "AND business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.mail', array(
		array('container'=>'messages', 'fname'=>'id', 'name'=>'message',
			'fields'=>array('id', 'customer_id', 'customer_name', 'customer_email', 
				'flags', 'status', 'status_text', 'date_sent', 'date_received', 
				'mail_to', 'mail_cc', 'mail_from', 'from_name', 'from_email', 
				'subject', 'html_content', 'text_content',
				'raw_headers', 'raw_content'),
			'maps'=>array('status_text'=>$maps['message']['status']),
			'utctotz'=>array('date_sent'=>array('timezone'=>$intl_timezone, 'format'=>$datetime_format),
				'date_received'=>array('timezone'=>$intl_timezone, 'format'=>$datetime_format)),
			),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['messages'][0]['message']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2596', 'msg'=>'Unable to find message'));
	} 
	$message = $rc['messages'][0]['message'];

	//
	// Get any logs for this message
	//
	$strsql = "SELECT id, severity, severity AS severity_text, "
		. "log_date, log_date AS log_date_date, log_date AS log_date_time, code, msg, pmsg, errors, raw_logs "
		. "FROM ciniki_mail_log "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND mail_id = '" . ciniki_core_dbQuote($ciniki, $args['message_id']) . "' "
		. "ORDER BY log_date "
		. "";
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.mail', array(
		array('container'=>'logs', 'fname'=>'id', 'name'=>'log',
			'fields'=>array('id', 'severity_text', 'log_date', 'log_date_date', 'log_date_time', 'code', 'msg', 'pmsg', 'errors', 'raw_logs'),
			'maps'=>array('severity_text'=>$maps['log']['severity']),
			'utctotz'=>array( 'log_date'=>array('timezone'=>$intl_timezone, 'format'=>$datetime_format),
				'log_date_date'=>array('timezone'=>$intl_timezone, 'format'=>$date_format),
				'log_date_time'=>array('timezone'=>$intl_timezone, 'format'=>$time_format),
				),
			),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( isset($rc['logs']) ) {
		$message['logs'] = $rc['logs'];
	}

	return array('stat'=>'ok', 'message'=>$message);
}
?>
