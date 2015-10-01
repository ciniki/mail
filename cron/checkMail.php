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
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQueryList');
	//
	// Get the list of businesses which have mail waiting to be sent
	//
	$strsql = "SELECT DISTINCT business_id "
		. "FROM ciniki_mail "
		. "WHERE status = 10 OR status = 15 "
		. "";
	$rc = ciniki_core_dbQueryList($ciniki, $strsql, 'ciniki.mail', 'businesses', 'business_id');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['businesses']) || count($rc['businesses']) == 0 ) {
		$businesses = array();
	} else {
		$businesses = $rc['businesses'];
	}

	//
	// For each business, load their mail settings
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'mail', 'private', 'getSettings');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'mail', 'private', 'sendMail');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
	foreach($businesses as $business_id) {
		print("CRON: Sending mail for $business_id\n");
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
			. "AND (status = 10 OR status = 15) "
			. "ORDER BY status DESC, last_updated "	// Any that we have tried to send will get their last_updated changed and be bumped to back of the line
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

	//
	// Check for mailings which are completed
	//
	$strsql = "SELECT ciniki_mailings.id, "
		. "ciniki_mailings.business_id, "
		. "COUNT(ciniki_mail.mailing_id) AS num_msgs "
		. "FROM ciniki_mailings "
		. "LEFT JOIN ciniki_mail ON ("
			. "ciniki_mailings.id = ciniki_mail.mailing_id "
			. "AND ciniki_mail.status < 30 "
			. "AND ciniki_mailings.business_id = ciniki_mail.business_id "
			. ") "
		. "WHERE ciniki_mailings.status > 10 "
		. "AND ciniki_mailings.status < 50 "
		. "GROUP BY ciniki_mailings.business_id, ciniki_mailings.id "
		. "HAVING num_msgs = 0 "
		. "ORDER BY ciniki_mailings.business_id "
		. "";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.mail', 'mailing');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( isset($rc['rows']) ) {
		$mailings = $rc['rows'];
		foreach($mailings as $mailing) {
			$rc = ciniki_core_objectUpdate($ciniki, $mailing['business_id'], 'ciniki.mail.mailing', $mailing['id'], array('status'=>50));
			if( $rc['stat'] != 'ok' ) {
				error_log("CRON-ERR: Unable to update mailing status for $business_id (" . serialize($rc) . ")");
				continue;
			}
		}
	}

	return array('stat'=>'ok');
}
