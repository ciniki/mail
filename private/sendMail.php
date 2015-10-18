<?php
//
// Description
// -----------
//
// Arguments
// ---------
// ciniki:
// business_id:		The ID of the business to the mail belongs to.
// mail_id:			The ID of the mail message to send.
// 
// Returns
// -------
//
function ciniki_mail_sendMail($ciniki, $business_id, $settings, $mail_id) {

	//
	// Query for mail details
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
	$strsql = "SELECT id, status, "
		. "mailing_id, survey_invite_id, "
		. "customer_id, customer_name, customer_email, "
		. "subject, html_content, text_content "
		. "FROM ciniki_mail "
		. "WHERE id = '" . ciniki_core_dbQuote($ciniki, $mail_id) . "' "
		. "AND business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "AND status >= 10 "
		. "";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.mail', 'mail');
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1031', 'msg'=>'Unable to find email information', 'err'=>$rc['err']));
	}
	if( !isset($rc['mail']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1032', 'msg'=>'Unable to find email information'));
	}
	$email = $rc['mail'];

	//
	// Check for attachments
	//
	$strsql = "SELECT id, filename, content "
		. "FROM ciniki_mail_attachments "
		. "WHERE ciniki_mail_attachments.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "AND ciniki_mail_attachments.mail_id = '" . ciniki_core_dbQuote($ciniki, $email['id']) . "' "
		. "";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.mail', 'attachment');
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2468', 'msg'=>'Unable to find email attachments'));
	}
	if( isset($rc['rows']) ) {
		$email['attachments'] = $rc['rows'];
	}

	//
	// Set timezone
	//
	date_default_timezone_set('UTC');

	//
	// Check if we can lock the message, by updating to status 20
	//
	$strsql = "UPDATE ciniki_mail SET status = 20, last_updated = UTC_TIMESTAMP() "
		. "WHERE id = '" . ciniki_core_dbQuote($ciniki, $mail_id) . "' "
		. "AND business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "";
	$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.mail');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( $rc['num_affected_rows'] < 1 ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1035', 'msg'=>'Unable to get lock on email'));
	}
	ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.mail', 'ciniki_mail_history', $business_id, 
		2, 'ciniki_mail', $mail_id, 'status', '20');

	//  
	// The from address can be set in the config file.
	//  
	require_once($ciniki['config']['ciniki.core']['lib_dir'] . '/PHPMailer/class.phpmailer.php');
	require_once($ciniki['config']['ciniki.core']['lib_dir'] . '/PHPMailer/class.smtp.php');

	$mail = new PHPMailer;

	$mail->IsSMTP();
	$use_config = 'yes';
	if( isset($settings['smtp-servers']) && $settings['smtp-servers'] != ''
		) {
		$mail->Host = $settings['smtp-servers'];
		if( isset($settings['smtp-username']) && $settings['smtp-username'] != '' ) {
			$mail->SMTPAuth = true;
			$mail->Username = $settings['smtp-username'];
			$mail->Password = $settings['smtp-password'];
		}
		if( isset($settings['smtp-secure']) && $settings['smtp-secure'] != '' ) {
			$mail->SMTPSecure = $settings['smtp-secure'];
		}
		if( isset($settings['smtp-port']) && $settings['smtp-port'] != '' ) {
			$mail->Port = $settings['smtp-port'];
		}
		$mail->From = $settings['smtp-from-address'];
		$mail->FromName = $settings['smtp-from-name'];
	} else {
		$mail->Host = $ciniki['config']['ciniki.core']['system.smtp.servers'];
		$mail->SMTPAuth = true;
		$mail->Username = $ciniki['config']['ciniki.core']['system.smtp.username'];
		$mail->Password = $ciniki['config']['ciniki.core']['system.smtp.password'];
		$mail->SMTPSecure = $ciniki['config']['ciniki.core']['system.smtp.secure'];
		$mail->Port = $ciniki['config']['ciniki.core']['system.smtp.port'];

		$mail->From = $ciniki['config']['ciniki.core']['system.email'];
		$mail->FromName = $ciniki['config']['ciniki.core']['system.email.name'];
	}


//	$mail->SMTPAuth = true;
//	$mail->Username = $ciniki['config']['ciniki.core']['system.smtp.username'];
//	$mail->Password = $ciniki['config']['ciniki.core']['system.smtp.password'];

	$mail->IsHTML(true);
	$mail->Subject = iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $email['subject']);
	$mail->Body = iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $email['html_content']);
	$mail->AltBody = iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $email['text_content']);

	if( isset($ciniki['config']['ciniki.mail']['force.mailto']) ) {
		$mail->AddAddress($ciniki['config']['ciniki.mail']['force.mailto'], $email['customer_name']);
		$mail->Subject .= ' [' . $email['customer_email'] . ']';
	} else {
		$mail->AddAddress($email['customer_email'], $email['customer_name']);
	}

	//
	// Add attachments
	//
	if( isset($email['attachments']) ) {
		foreach($email['attachments'] as $attachment) {
			$mail->addStringAttachment($attachment['content'], $attachment['filename']);
		}
	}

	if( !$mail->Send() ) {
		sleep(2);
		if( !$mail->Send() ) {	
			error_log("MAIL-ERR [" . $business_id . "]: [" . $email['customer_email'] . "] " . $mail->ErrorInfo);
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1033', 'msg'=>'Unable to send email', 'pmsg'=>$mail->ErrorInfo));
		}
	}

	//
	// Update the mail status
	//
	$utc_datetime = strftime("%Y-%m-%d %H:%M:%S");
	$strsql = "UPDATE ciniki_mail SET status = 30, date_sent = '" . ciniki_core_dbQuote($ciniki, $utc_datetime) . "', last_updated = UTC_TIMESTAMP() "
		. "WHERE id = '" . ciniki_core_dbQuote($ciniki, $mail_id) . "' "
		. "AND business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "";
	$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.mail');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.mail', 'ciniki_mail_history', $business_id, 
		2, 'ciniki_mail', $mail_id, 'status', '30');
	ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.mail', 'ciniki_mail_history', $business_id, 
		2, 'ciniki_mail', $mail_id, 'date_sent', $utc_datetime);

	//
	// Update the survey invite
	//
	if( $email['survey_invite_id'] > 0 ) {
		$strsql = "UPDATE ciniki_survey_invites SET status = 10, date_sent = '" . ciniki_core_dbQuote($ciniki, $utc_datetime) . "', last_updated = UTC_TIMESTAMP() "
			. "WHERE id = '" . ciniki_core_dbQuote($ciniki, $email['survey_invite_id']) . "' "
			. "AND business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "AND status = 5 "
			. "";
		$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.surveys');
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( $rc['num_affected_rows'] > 0 ) {
			ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.surveys', 'ciniki_survey_history', $business_id, 
				2, 'ciniki_survey_invites', $email['survey_invite_id'], 'status', '10');
			ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.surveys', 'ciniki_survey_history', $business_id, 
				2, 'ciniki_survey_invites', $email['survey_invite_id'], 'date_sent', $utc_datetime);
		}
	}

	return array('stat'=>'ok');
}
