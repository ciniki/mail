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
function ciniki_mail_cron_jobs($ciniki) {
	ciniki_cron_logMsg($ciniki, 0, array('code'=>'0', 'msg'=>'Checking for mail jobs', 'severity'=>'5'));

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
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2630', 'msg'=>'Unable to get list of businesses with mail', 'err'=>$rc['err']));
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
		ciniki_cron_logMsg($ciniki, $business_id, array('code'=>'0', 'msg'=>'Sending mail', 'severity'=>'10'));
		$rc = ciniki_mail_getSettings($ciniki, $business_id);
		if( $rc['stat'] != 'ok' ) {
			ciniki_cron_logMsg($ciniki, $business_id, array('code'=>'2586', 'msg'=>'Unable to mail settings', 
				'severity'=>50, 'err'=>$rc['err']));
			continue;
		}
		$settings = $rc['settings'];	

		$limit = 1; 	// Default to really slow sending, 1 every 5 minutes
		if( isset($settings['smtp-5min-limit']) && is_numeric($settings['smtp-5min-limit']) && $settings['smtp-5min-limit'] > 0 ) {
            if( $settings['smtp-5min-limit'] < 1 ) {
                //
                // Check to see when last message was sent
                //
                $strsql = "SELECT (UNIX_TIMESTAMP(UTC_TIMESTAMP()) - UNIX_TIMESTAMP(MAX(date_sent))) AS last_date_sent "
                    . "FROM ciniki_mail "
                    . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
                    . "";
                $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.mail', 'last_sent');
                if( $rc['stat'] != 'ok' ) {
                    ciniki_cron_logMsg($ciniki, $business_id, array('code'=>'3230', 'msg'=>'Unable to check last sent', 'severity'=>50, 'err'=>$rc['err']));
                    continue;
                }
                //
                // Calculate how many minutes need to be elapse between messages.
                // If business sends unqueued message, it will delay queue
                //
                // 1 = 5min
                // 0.5 = 10min
                // 0.25 = 20min  (1/0.25) = 4 * 5 = 20min
                //
                $min_minutes = ((1/$settings['smtp-5min-limit'])*5);

                //
                // Check if the minutes between last message sent and now is less than minumum required.
                //
                if( isset($rc['last_sent']['last_date_sent']) && ($rc['last_sent']['last_date_sent']/60) < $min_minutes ) {
                    continue;
                }
            } else {
                $limit = intval($settings['smtp-5min-limit']);
            }
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
			ciniki_cron_logMsg($ciniki, $business_id, array('code'=>'2631', 'msg'=>'Unable to load the list of mail to send', 
				'severity'=>50, 'err'=>$rc['err']));
			continue;
		}
		$emails = $rc['mail'];
		foreach($emails as $mail_id) {
			$rc = ciniki_mail_sendMail($ciniki, $business_id, $settings, $mail_id);
			if( $rc['stat'] != 'ok' ) {
				ciniki_cron_logMsg($ciniki, $business_id, array('code'=>'2632', 'msg'=>'Unable to send message',
					'severity'=>50, 'err'=>$rc['err']));
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
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2634', 'msg'=>'Unable to get list of mailings', 'err'=>$rc['err']));
	}
	if( isset($rc['rows']) ) {
		$mailings = $rc['rows'];
		foreach($mailings as $mailing) {
			$rc = ciniki_core_objectUpdate($ciniki, $mailing['business_id'], 'ciniki.mail.mailing', $mailing['id'], array('status'=>50));
			if( $rc['stat'] != 'ok' ) {
				ciniki_cron_logMsg($ciniki, $business_id, array('code'=>'2633', 'msg'=>'Unable to update mailing', 'pmsg'=>'Unable to set mailing status=50',
					'severity'=>40, 'err'=>$rc['err']));
				continue;
			}
		}
	}

	return array('stat'=>'ok');
}
