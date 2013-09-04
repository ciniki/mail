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
function ciniki_mail_loadBusinessTemplate($ciniki, $business_id, $args) {

	//
	// If there is no theme sent, load them from defaults
	//
	if( !isset($args['theme']) ) {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'mail', 'private', 'getSettings');
		$rc = ciniki_mail_getSettings($ciniki, $business_id);
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		$settings = $rc['settings'];
		if( isset($settings['mail-default-theme']) ) {
			$args['theme'] = $settings['mail-default-theme'];
		} else {
			$args['theme'] = 'Default';
		}
	}

	//
	// If there is no business_name set, load
	//
	if( !isset($args['business_name']) ) {
		//
		// Get the business settings for the mail module
		//
		ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'web', 'details');
		$rc = ciniki_businesses_web_details($ciniki, $business_id);
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		$business_details = $rc['details'];
		$args['business_name'] = $business_details['name'];
	}
	if( !isset($args['title']) ) {
		$args['title'] = $args['business_name'];
	}

	//
	// Load the theme
	//
	if( !file_exists($ciniki['config']['ciniki.core']['modules_dir'] . '/mail/private/theme' . $args['theme'] . '.php') ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1044', 'msg'=>'Theme does not exist'));
	}
	ciniki_core_loadMethod($ciniki, 'ciniki', 'mail', 'private', 'theme' . $args['theme']);
	$theme_load = 'ciniki_mail_theme' . $args['theme'];
	$rc = $theme_load($ciniki, $business_id);
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
	// Prepare the html_header
	//
	$text_header = "";
	$html_header = "<html><head><style>\n"
		. $theme['header_style']
		. "</style></head>\n"
		. "<body>\n"
		. "<div style='" . $theme['wrapper_style'] . "'>"
		. "<table width='100%' style='width:100%;'>\n"
		. "";

	//
	// Add header to the email
	//
	if( isset($args['title']) && $args['title'] != '' && (!isset($theme['title_show']) || $theme['title_show'] != 'no') ) {
		$html_header .= "<tr><td style='$td_header'><p style='$title_style'>" . $args['title'] . "</p></td></tr>";
	}

	//
	// Add powered by and unsubscribe
	//
	$html_footer = "<tr><td style='$td_footer'>"
		. "<p style='$p_footer'>All content &copy; Copyright " . date('Y') . " by " . $args['business_name'] . "</p>\n"
		. "";
	$text_footer = "\n\nAll content Copyright " . date('Y') . " by " . $args['business_name'];
	if( isset($ciniki['config']['ciniki.mail']['poweredby.url']) 
		&& $ciniki['config']['ciniki.mail']['poweredby.url'] != '' 
		&& $ciniki['config']['ciniki.core']['master_business_id'] != $business_id ) {
		$html_footer .= "<p style='$p_footer'>Powered by <a style='$a_style' href='" . $ciniki['config']['ciniki.mail']['poweredby.url'] . "'>" . $ciniki['config']['ciniki.mail']['poweredby.name'] . "</a></p>\n";
		$text_footer .= "\nPowered by Ciniki: " . $ciniki['config']['ciniki.mail']['poweredby.url'];
	}
	if( isset($args['unsubscribe_link']) && $args['unsubscribe_link'] == 'yes' ) {
		$html_footer .= "<p style='$p_footer'><a style='$a_style' href='{_unsubscribe_url_}'>Unsubscribe</a></p>\n";
		$text_footer .= "\nUnsubscribe: {_unsubscribe_url_}";
		$text_footer .= "\n\n";
	}

	$html_footer .= "</td></tr>\n";
	$html_footer .= "</table>\n"
		. "</div>\n"
		. "</body>\n"
		. "</html>\n"
		. "";

	return array('stat'=>'ok', 'theme'=>$theme, 'template'=>array(
		'html_header'=>$html_header,
		'html_footer'=>$html_footer,
		'text_header'=>$text_header,
		'text_footer'=>$text_footer,
		));
}
?>
