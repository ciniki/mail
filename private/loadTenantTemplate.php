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
// tnid:         The ID of the tenant to mail mailing belongs to.
// mailing_id:          The ID of the mailing to get.
//
// Returns
// -------
//
function ciniki_mail_loadTenantTemplate($ciniki, $tnid, $args) {

    //
    // If there is no theme sent, load them from defaults
    //
//  if( !isset($args['theme']) || $args['theme'] == '' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'mail', 'private', 'getSettings');
        $rc = ciniki_mail_getSettings($ciniki, $tnid);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $settings = $rc['settings'];
        if( isset($settings['mail-default-theme']) ) {
            $args['theme'] = $settings['mail-default-theme'];
        } else {
            $args['theme'] = 'Default';
        }
//  }

    //
    // If there is no tenant_name set, load
    //
    if( !isset($args['tenant_name']) ) {
        //
        // Get the tenant settings for the mail module
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'web', 'details');
        $rc = ciniki_tenants_web_details($ciniki, $tnid);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $tenant_details = $rc['details'];
        $args['tenant_name'] = $tenant_details['name'];
    }
    if( !isset($args['title']) ) {
        $args['title'] = $args['tenant_name'];
    }

    //
    // FIXME: Check if themes flag is enabled, and if theme_id is specified, then load that theme
    //

    //
    // Load the theme
    //
    if( !file_exists($ciniki['config']['ciniki.core']['modules_dir'] . '/mail/private/theme' . $args['theme'] . '.php') ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.mail.77', 'msg'=>'Theme does not exist'));
    }
    if( isset($args['tinymce']) && $args['tinymce'] == 'yes' ) {
        $args['theme'] = 'Tinymce';
    }
    ciniki_core_loadMethod($ciniki, 'ciniki', 'mail', 'private', 'theme' . $args['theme']);
    $theme_load = 'ciniki_mail_theme' . $args['theme'];
    $rc = $theme_load($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $theme = $rc['theme'];

    //
    // Check if theme settings
    //
    foreach($theme as $name => $style) {
        if( isset($settings['message-style-' . $name]) && $settings['message-style-' . $name] != '' ) {
            $theme[$name] = $settings['message-style-' . $name];
        }
    }

    $title_style = $theme['title_style'];
    $subtitle_style = $theme['subtitle_style'];
    $logo_style = $theme['logo_style'];
    $a_style = $theme['a'];
    $p_style = $theme['p'];
    $p_footer = $theme['p_footer'];
    $td_header = $theme['td_header'];
    $td_body = $theme['td_body'];
    $td_footer = $theme['td_footer'];
    $a_footer = $theme['a_footer'];

    //
    // Check for theme settings
    //
    

    // 
    // Prepare the html_header
    //
    $text_header = "";
    if( isset($args['tinymce']) && $args['tinymce'] == 'yes' ) {
        $html_header = "<html>"
//            . "<head><style>"
//            . $theme['header_style']
//            . "</style></head>"
            . "<body>"
            . "<div style=\"" . $theme['wrapper_style'] . "\">\n"
//            . "<table width='100%' style='width:100%;'>\n"
            . "";
    } else {
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
    }

    $links = '';
    for($i = 1; $i < 6; $i++) {
        if( isset($settings['footer-link-' . $i . '-name']) && $settings['footer-link-' . $i . '-name'] != '' 
            && isset($settings['footer-link-' . $i . '-url']) && $settings['footer-link-' . $i . '-url'] != '' 
            ) {
            $links .= ($links != '' ? ' - ' : '') . "<a style='$a_style' href='" . $settings['footer-link-' . $i . '-url'] . "'>" . $settings['footer-link-' . $i . '-name'] . "</a>";
        }
    }

    //
    // Add powered by and unsubscribe
    //
    $html_footer = '';
    if( $links != '' ) {
        if( isset($args['tinymce']) && $args['tinymce'] == 'yes' ) {
            $html_footer .= "<div style='text-align: center;'>$links</div>";
        } else {
            $html_footer .= "<tr><td style='$td_body;align=center;'><center>$links</center></td></tr>";
        }
    }
    if( isset($args['tinymce']) && $args['tinymce'] == 'yes' ) {
        $html_footer .= "<div style='border-top:1px solid #ddd;margin-top:20px;padding-top:1px;'></div><div style='$p_footer'>All content &copy; Copyright " . date('Y') . " by " . $args['tenant_name'] . "</div>\n"
            . "";
    } else {
        $html_footer .= "<tr><td style='$td_footer'>"
            . "<p style='$p_footer'>All content &copy; Copyright " . date('Y') . " by " . $args['tenant_name'] . "</p>\n"
            . "";
    }
    $text_footer = "\n\nAll content Copyright " . date('Y') . " by " . $args['tenant_name'];
    if( isset($ciniki['config']['ciniki.mail']['poweredby.url']) 
        && $ciniki['config']['ciniki.mail']['poweredby.url'] != '' 
        && $ciniki['config']['ciniki.core']['master_tnid'] != $tnid ) {
        $html_footer .= "<p style='$p_footer'>Powered by <a style='$a_footer' href='" . $ciniki['config']['ciniki.mail']['poweredby.url'] . "'>" . $ciniki['config']['ciniki.mail']['poweredby.name'] . "</a></p>\n";
        $text_footer .= "\nPowered by Ciniki: " . $ciniki['config']['ciniki.mail']['poweredby.url'];
    }
    if( isset($args['unsubscribe_link']) && $args['unsubscribe_link'] == 'yes' ) {
        $html_footer .= "<p style='$p_footer'><a style='$a_footer' href='{_unsubscribe_url_}'>Unsubscribe</a></p>\n";
        $text_footer .= "\nUnsubscribe: {_unsubscribe_url_}";
        $text_footer .= "\n\n";
    }
    elseif( isset($args['unsubscribe_url']) && $args['unsubscribe_url'] != '' ) {
        if( !isset($args['unsubscribe_text']) || $args['unsubscribe_text'] == '' ) {
            $args['unsubscribe_text'];
        }
        $html_footer .= "<p style='$p_footer'><a style='$a_footer' href='" . $args['unsubscribe_url'] . "'>" . $args['unsubscribe_text'] . "</a></p>\n";
        $text_footer .= "\n" . $args['unsubscribe_text'] . ": " . $args['unsubscribe_url'];
        $text_footer .= "\n\n";
    }

    if( isset($args['tinymce']) && $args['tinymce'] == 'yes' ) {
        $html_footer .= "</div></body></html>";
    } else {
        $html_footer .= "</td></tr>\n";
        $html_footer .= "</table>\n"
            . "</div>\n"
            . "</body>\n"
            . "</html>\n"
            . "";
    }

    return array('stat'=>'ok', 'theme'=>$theme, 'template'=>array(
        'html_header'=>$html_header,
        'html_footer'=>$html_footer,
        'text_header'=>$text_header,
        'text_footer'=>$text_footer,
        ));
}
?>
