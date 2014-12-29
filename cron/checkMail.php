<?php
//
// Description
// -----------
//
// Arguments
// ---------
// ciniki:
// 
// Returns
// -------
//
function ciniki_mail_cron_checkMail($ciniki) {
	print("CRON: Checking mail to be sent\n");
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQueryList');
	//
	// Get the list of businesses which have mail waiting to be sent
	//
	$strsql = "SELECT DISTINCT business_id "
		. "FROM ciniki_mail "
		. "WHERE status = 10 "
		. "";
	$rc = ciniki_core_dbQueryList($ciniki, $strsql, 'ciniki.mail', 'businesses', 'business_id');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['businesses']) || count($rc['businesses']) == 0 ) {
		return array('stat'=>'ok');		// No messages to deliver
	}
	$businesses = $rc['businesses'];

	//
	// For each business, load their mail settings
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'mail', 'private', 'getSettings');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'mail', 'private', 'sendMail');
	foreach($businesses as $business_id) {
		$rc = ciniki_mail_getSettings($ciniki, $business_id);
		if( $rc['stat'] != 'ok' ) {
			error_log("CRON-ERR: Unable to load business mail settings for $business_id (" . serialize($rc) . ")");
			continue;
		}
		$settings = $rc['settings'];	

		$limit = 1; 	// Default to really slow sending, 1 every 5 minutes
		if( isset($settings['smtp-5min-limit']) && is_numeric($settings['smtp-5min-limit']) && $settings['smtp-5min-limit'] > 0 ) {
			$limit = intval($settings['smtp-5min-limit']);
		}
		
		$strsql = "SELECT id "
			. "FROM ciniki_mail "
			. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "AND status = 10 "
			. "ORDER BY last_updated "	// Any that we have tried to send will get their last_updated changed and be bumped to back of the line
			. "LIMIT $limit "
			. "";
		$rc = ciniki_core_dbQueryList($ciniki, $strsql, 'ciniki.mail', 'mail', 'id');
		if( $rc['stat'] != 'ok' ) {
			error_log("CRON-ERR: Unable to load mail list for $business_id (" . serialize($rc) . ")");
			continue;
		}
		$emails = $rc['mail'];
		foreach($emails as $mail_id) {
			$rc = ciniki_mail_sendMail($ciniki, $business_id, $settings, $mail_id);
			if( $rc['stat'] != 'ok' ) {
				error_log("CRON-ERR: Unable to send mail for $business_id (" . serialize($rc) . ")");
				continue;
			}
		}
	}

	return array('stat'=>'ok');
}
