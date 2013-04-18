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
	$strsql = "SELECT id, "
		. "mailing_id, survey_invite_id, "
		. "customer_id, customer_name, customer_email, "
		. "subject, html_content, text_content "
		. "FROM ciniki_mail "
		. "WHERE id = '" . ciniki_core_dbQuote($ciniki, $mail_id) . "' "
		. "AND business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
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

	//  
	// The from address can be set in the config file.
	//  
	require_once($ciniki['config']['ciniki.core']['lib_dir'] . '/PHPMailer/class.phpmailer.php');
	require_once($ciniki['config']['ciniki.core']['lib_dir'] . '/PHPMailer/class.smtp.php');

	$mail = new PHPMailer;

	$mail->IsSMTP();
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

//	$mail->SMTPAuth = true;
//	$mail->Username = $ciniki['config']['ciniki.core']['system.smtp.username'];
//	$mail->Password = $ciniki['config']['ciniki.core']['system.smtp.password'];

	$mail->From = $settings['smtp-from-address'];
	$mail->FromName = $settings['smtp-from-name'];
	$mail->AddAddress($email['customer_email'], $email['customer_name']);

	$mail->IsHTML(true);
	$mail->Subject = $email['subject'];
	$mail->Body = $email['html_content'];
	$mail->AltBody = $email['text_content'];

	if( !$mail->Send() ) {
		error_log("MAIL-ERR: [" . $email['customer_email'] . "] " . $mail->ErrorInfo);
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1033', 'msg'=>'Unable to send email', 'pmsg'=>$mail->ErrorInfo));
	}

	//
	// Update the mail status
	//
	$strsql = "UPDATE ciniki_mail SET status = 30, date_sent = UTC_TIMESTAMP(), last_updated = UTC_TIMESTAMP() "
		. "WHERE id = '" . ciniki_core_dbQuote($ciniki, $mail_id) . "' "
		. "AND business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "";
	$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.mail');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	//
	// Update the survey invite
	//
	if( $email['survey_invite_id'] > 0 ) {
		$strsql = "UPDATE ciniki_survey_invites SET date_sent = UTC_TIMESTAMP(), last_updated = UTC_TIMESTAMP() "
			. "WHERE id = '" . ciniki_core_dbQuote($ciniki, $mail_id) . "' "
			. "AND business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "";
		$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.surveys');
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
	}

	return array('stat'=>'ok');
}
