<?php
//
// Description
// -----------
// This method will create all the emails for a mailing, and start sending.
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
function ciniki_mail_mailingSend(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		'mailing_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Mailing'),
		'test'=>array('required'=>'no', 'default'=>'no', 'name'=>'Text'),
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
    $rc = ciniki_mail_checkAccess($ciniki, $args['business_id'], 'ciniki.mail.mailingSend', 0); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
	$modules = $rc['modules'];

	//
	// Get the settings for the mail module
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'mail', 'private', 'getSettings');
    $rc = ciniki_mail_getSettings($ciniki, $args['business_id']); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
	$settings = $rc['settings'];

	//
	// Get the business settings for the mail module
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'web', 'details');
    $rc = ciniki_businesses_web_details($ciniki, $args['business_id']); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
	$business_details = $rc['details'];

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
	$date_format = ciniki_users_dateFormat($ciniki);

	//
	// Get the mailing information
	//
	$strsql = "SELECT "
		. "ciniki_mailings.id, ciniki_mailings.type, "
		. "status, theme, survey_id, subject, "
		. "html_content, text_content, date_started, date_sent, "
		. "ciniki_mailing_subscriptions.subscription_id AS subscription_ids "
		. "FROM ciniki_mailings "
		. "LEFT JOIN ciniki_mailing_subscriptions ON (ciniki_mailings.id = ciniki_mailing_subscriptions.mailing_id "
			. "AND ciniki_mailing_subscriptions.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "') "
		. "WHERE ciniki_mailings.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND ciniki_mailings.id = '" . ciniki_core_dbQuote($ciniki, $args['mailing_id']) . "' "
		. "ORDER BY ciniki_mailings.id ASC ";

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.mail', array(
		array('container'=>'mailings', 'fname'=>'id', 'name'=>'mailing',
			'fields'=>array('id', 'type', 'status', 'theme', 'survey_id', 'subject', 'html_content', 'text_content', 'subscription_ids'),
			'idlists'=>array('subscription_ids')),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['mailings']) || !isset($rc['mailings'][0]['mailing']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1030', 'msg'=>'Unable to find mailing'));
	}
	$mailing = $rc['mailings'][0]['mailing'];
	if( !is_array($mailing['subscription_ids']) && count($mailing['subscription_ids']) == 0 ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1043', 'msg'=>'No subscriptions specified'));
	}

	if( $mailing['status'] >= 40 && (!isset($args['test']) || $args['test'] != 'yes') ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1036', 'msg'=>'Mailing has already been sent'));
	}

	//
	// Pull customer list (customer_id, customer_name, email)
	//
	if( isset($args['test']) && $args['test'] == 'yes' ) {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
		$strsql = "SELECT id, CONCAT_WS(' ', firstname, lastname) AS name, email "
			. "FROM ciniki_users "
			. "WHERE id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "' "
			. "";
		$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.users', 'user');
		if( $rc['stat'] != 'ok' || !isset($rc['user']) ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1047', 'msg'=>'Unable to find email information', 'err'=>$rc['err']));
		}
		$emails = array(array('customer_id'=>0, 'customer_name'=>$rc['user']['name'], 'email'=>$rc['user']['email'], 'subscription_uuid'=>'Test'));
	} else {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'subscriptions', 'private', 'emailList');
		$rc = ciniki_subscriptions_emailList($ciniki, $args['business_id'], explode(',', $mailing['subscription_ids']));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( !isset($rc['emails']) || count($rc['emails']) == 0 ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1045', 'msg'=>'No emails for the specified subscriptions'));
		}
		$emails = $rc['emails'];
	}

	//
	// Check if this is an alert message
	//
	if( isset($modules['ciniki.mail']['flags']) && (($modules['ciniki.mail']['flags'])&0x01) == 1 
		&& isset($mailing['type']) && $mailing['type'] == '30' ) {
		$flags = 1;
	} else {
		$flags = 0;
	}

	//
	// Load the theme
	//
	if( !file_exists($ciniki['config']['ciniki.core']['modules_dir'] . '/mail/private/theme' . $mailing['theme'] . '.php') ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1044', 'msg'=>'Theme does not exist'));
	}
	ciniki_core_loadMethod($ciniki, 'ciniki', 'mail', 'private', 'theme' . $mailing['theme']);
	$theme_load = 'ciniki_mail_theme' . $mailing['theme'];
	$rc = $theme_load($ciniki, $args['business_id']);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$theme = $rc['theme'];
	$title_style = $theme['title_style'];
	$subtitle_style = $theme['subtitle_style'];
	$logo_style = $theme['logo_style'];
	$a_style = $theme['a'];
	$p_style = $theme['p'];
	$p_footer = $theme['p_footer'];
	$td_header = $theme['td_header'];
	$td_body = $theme['td_body'];
	$td_footer = $theme['td_footer'];

	//
	// Prepare both the html and text version of the email messages
	//
	$text_message = $mailing['text_content'];

	$html_message = "<html><head><style>\n"
		. $theme['header_style']
		. "</style></head>\n"
		. "<body>\n"
		. "<div style='" . $theme['wrapper_style'] . "'>"
		. "<table width='100%' style='width:100%;'>\n"
		. "";

	//
	// Add header to the email
	//
	$html_message .= "<tr><td style='$td_header'><p style='$title_style'>" . $business_details['name'] . "</p></td></tr>";

	//
	// Convert to HTML
	//
	if( $mailing['html_content'] == '' ) {
		$html_content = "<tr><td style='$td_body'><p style='$p_style'>" . preg_replace('/\n\s*\n/m', "</p><p style='$p_style'>", $text_message) . '</p></td></tr>';
		$html_content = preg_replace('/\n/m', '<br/>', $html_content);
		// FUTURE: Add processing to find links and replace with email tracking links
	} else {
		$html_content = $mailing['html_content'];
	}

	$html_message .= $html_content;

	//
	// Check if surveys is enabled, and one is set for this mailing
	//
	if( isset($modules['ciniki.surveys']) && $mailing['survey_id'] > 0 ) {
		//
		// Get the survey message, url will be inserted later on
		//
		$rc = ciniki_surveys_emailDetails($ciniki, $business_id, $mailing['survey_id']);
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		$invite_message = $rc['survey']['email_preface'];
		$html_message = "<tr><td style='$td_body'>"
			. "<p style='$p_style'>$invite_message<br/><a style='$a_style' href='{_survey_url}'>{_survey_url}</a></p>"
			. "</td></tr>\n";
		$text_message .= "\n\n" . $invite_message . "\n{_survey_url}";
	}

	//
	// Add disclaimer
	//
	if( isset($settings['message-disclaimer']) && $settings['message-disclaimer'] != '' ) {
		$html_message .= "<tr><td style='$td_body'><p style='$p_style'>" . $settings['message-disclaimer'] . "</p></td></tr>";
		$text_message .= "\n\n" . $settings['message-disclaimer'];
	}

	//
	// Add powered by and unsubscribe
	//
	$html_message .= "<tr><td style='$td_footer'>"
		. "<p style='$p_footer'>All content &copy; Copyright " . date('Y') . " by " . $business_details['name'] . "</p>"
		. "";
	$text_message .= "\n\nAll content Copyright " . date('Y') . " by " . $business_details['name'];
	if( isset($ciniki['config']['ciniki.mail']['poweredby.url']) 
		&& $ciniki['config']['ciniki.mail']['poweredby.url'] != '' 
		&& $ciniki['config']['ciniki.core']['master_business_id'] != $args['business_id'] ) {
		$html_message .= "<p style='$p_footer'>Powered by <a style='$a_style' href='" . $ciniki['config']['ciniki.mail']['poweredby.url'] . "'>" . $ciniki['config']['ciniki.mail']['poweredby.name'] . "</a></p>";
		$text_message .= "\nPowered by Ciniki: " . $ciniki['config']['ciniki.mail']['poweredby.url'];
	}
	$html_message .= "<p style='$p_footer'><a style='$a_style' href='{_unsubscribe_url}'>Unsubscribe</a></p>";
	$text_message .= "\nUnsubscribe: {_unsubscribe_url}";
	$text_message .= "\n\n";

	$html_message .= "</td></tr>\n";
	$html_message .= "</table>\n"
		. "</div>\n"
		. "</body>\n"
		. "</html>\n"
		. "";

	//
	// Get the list of existing emails for this mailing, make sure we don't send twice
	//
	$strsql = "SELECT customer_email "
		. "FROM ciniki_mail "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND mailing_id = '" . ciniki_core_dbQuote($ciniki, $args['mailing_id']) . "' "
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuerylist');
	$rc = ciniki_core_dbQueryList($ciniki, $strsql, 'ciniki.mail', 'emails', 'customer_email');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$existing_emails = $rc['emails'];

	$email_alert = 'no';
	if( isset($modules['ciniki.mail']['flags']) && (($modules['ciniki.mail']['flags'])&0x01) == 1 
		&& isset($mailing['type']) && $mailing['type'] == '30' ) {
		$email_alert = 'yes';
		$ciniki['ciniki.mail.settings'] = $settings;
	}

	//
	// Get the business site url
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'lookupBusinessURL');
	$rc = ciniki_web_lookupBusinessURL($ciniki, $args['business_id']);
	if( $rc['stat'] != 'ok' ) {	
		return $rc;
	}
	$business_url = $rc['url'];

	//
	// Create all the customer emails, and load into ciniki_mail table.
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'mail', 'private', 'createCustomerMail');
	if( isset($modules['ciniki.surveys']) && $mailing['survey_id'] > 0 ) {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'surveys', 'private', 'createCustomerInvite');
	}
	foreach($emails as $eid => $email) {
		//
		// Check if email already exists
		//
		if( in_array($email['email'], $existing_emails) ) {
			continue;
		}

		//
		// Make the basic substitutions in email content
		//
		$text_message = preg_replace('/\{_name\}/', $email['customer_name'], $text_message);
		$html_message = preg_replace('/\{_name\}/', $email['customer_name'], $html_message);

		//
		// Create the unsubscribe url for the customer
		//
		$unsubscribe_key = substr(md5(date('Y-m-d-H-i-s') . rand()), 0, 32);
		$unsubscribe_url = $business_url . '/account/unsubscribe/?e=' . urlencode($email['email']) . '&s=' . $email['subscription_uuid'] . '&k=' . $unsubscribe_key;
		$text_message = preg_replace('/\{_unsubscribe_url\}/', $unsubscribe_url, $text_message);
		$html_message = preg_replace('/\{_unsubscribe_url\}/', $unsubscribe_url, $html_message);

		if( isset($args['test']) && $args['test'] == 'yes' ) {
			ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'emailUser');
			ciniki_users_emailUser($ciniki, $ciniki['session']['user']['id'], $mailing['subject'], $text_message, $html_message);
			continue;
		}

		//
		// Check if surveys is enabled, and one is set for this mailing
		//
		if( isset($modules['ciniki.surveys']) && $mailing['survey_id'] > 0 ) {
			//
			// Get the survey link to insert
			//
			$rc = ciniki_surveys_createCustomerInvite($ciniki, $business_id, $mailing['survey_id'], $mailing['id'], $email['customer_id']);
			if( $rc['stat'] != 'ok' ) {
				return $rc;
			}
			$invite_id = $rc['id'];
			$invite_url = $rc['url'];
			
			$text_message = preg_replace('/\{_survey_url\}/', $invite_url, $text_message);
			$html_message = preg_replace('/\{_survey_url\}/', $invite_url, $html_message);
		} else {
			$invite_id = 0;
		}

		//
		// Setup the customer email in the database
		//
		$rc = ciniki_mail_createCustomerMail($ciniki, $args['business_id'], $settings, $email, $mailing['subject'], $html_message, $text_message, array(
			'mailing_id'=>$mailing['id'],
			'flags'=>$flags,
			'survey_invite_id'=>$invite_id,
			'unsubscribe_key'=>$unsubscribe_key,
			));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		$emails[$eid]['mail_id'] = $rc['id'];
		
		//
		// Add to the email queue, if the emails are an alert and need to get send immediately
		//
		if( $email_alert == 'yes' ) {
			$ciniki['emailqueue'][] = array('mail_id'=>$rc['id'], 'business_id'=>$args['business_id']);
		}
	}

	//
	// Change the status to Sending
	//
	if( !isset($args['test']) || $args['test'] != 'yes' ) {
		$strsql = "UPDATE ciniki_mailings SET status = 40, last_updated = UTC_TIMESTAMP() "
			. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "AND id = '" . ciniki_core_dbQuote($ciniki, $args['mailing_id']) . "' "
			. "";
		$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.mail');
		if( $rc['stat'] != 'ok' ) {
			return $rc;	
		}
	}

	return array('stat'=>'ok');
}
?>
