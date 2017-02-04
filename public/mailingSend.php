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
// business_id:         The ID of the business to mail mailing belongs to.
// mailing_id:          The ID of the mailing to get.
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
        'test'=>array('required'=>'no', 'default'=>'no', 'name'=>'Test'),
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

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki);

    date_default_timezone_set('UTC');

    //
    // Get the mailing information
    //
    $strsql = "SELECT "
        . "ciniki_mailings.id, "
        . "ciniki_mailings.uuid, "
        . "ciniki_mailings.type, "
        . "status, theme, survey_id, object, object_id, subject, primary_image_id, "
        . "html_content AS content, "
        . "text_content, "
        . "date_started, "
        . "date_sent, "
        . "ciniki_mailing_subscriptions.subscription_id AS subscription_ids "
        . "FROM ciniki_mailings "
        . "LEFT JOIN ciniki_mailing_subscriptions ON ("
            . "ciniki_mailings.id = ciniki_mailing_subscriptions.mailing_id "
            . "AND ciniki_mailing_subscriptions.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . ") "
        . "WHERE ciniki_mailings.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "AND ciniki_mailings.id = '" . ciniki_core_dbQuote($ciniki, $args['mailing_id']) . "' "
        . "ORDER BY ciniki_mailings.id ASC ";

    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.mail', array(
        array('container'=>'mailings', 'fname'=>'id', 'name'=>'mailing',
            'fields'=>array('id', 'uuid', 'type', 'status', 'theme', 'survey_id', 'image_id'=>'primary_image_id',
            'object', 'object_id', 
            'subject', 'content', 'text_content', 'subscription_ids'),
            'idlists'=>array('subscription_ids')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['mailings']) || !isset($rc['mailings'][0]['mailing']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.mail.39', 'msg'=>'Unable to find mailing'));
    }
    $mailing = $rc['mailings'][0]['mailing'];
    // Check for the subscriptions if this is not a test message
    if( (!isset($args['test']) || $args['test'] != 'yes') && !is_array($mailing['subscription_ids']) && count($mailing['subscription_ids']) == 0 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.mail.40', 'msg'=>'No subscriptions specified'));
    }

    if( $mailing['status'] >= 40 && (!isset($args['test']) || $args['test'] != 'yes') ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.mail.41', 'msg'=>'Mailing has already been sent'));
    }

    //
    // Get any images for the mailing
    //
    $strsql = "SELECT ciniki_mailing_images.id, "
        . "ciniki_mailing_images.name, "
        . "0 as webflags, "
        . "ciniki_mailing_images.image_id, "
        . "ciniki_mailing_images.description, "
        . "'' as url "
        . "FROM ciniki_mailing_images "
        . "WHERE ciniki_mailing_images.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "AND ciniki_mailing_images.mailing_id = '" . ciniki_core_dbQuote($ciniki, $args['mailing_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.mail', array(
        array('container'=>'images', 'fname'=>'id', 
            'fields'=>array('id', 'name', 'webflags', 'image_id', 'description', 'url')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['images']) ) {
        $mailing['images'] = $rc['images'];
    } else {
        $mailing['images'] = array();
    }

    //
    // Pull customer list (customer_id, customer_name, email)
    //
    if( isset($args['test']) && $args['test'] == 'yes' ) {
        //
        // test message, send to session user email
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
        $strsql = "SELECT id, CONCAT_WS(' ', firstname, lastname) AS name, email "
            . "FROM ciniki_users "
            . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.users', 'user');
        if( $rc['stat'] != 'ok' || !isset($rc['user']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.mail.42', 'msg'=>'Unable to find email information', 'err'=>$rc['err']));
        }
        $emails = array(array('customer_id'=>0, 'customer_name'=>$rc['user']['name'], 'email'=>$rc['user']['email'], 'subscription_uuid'=>'Test'));
    } 
    
    else {
        //
        // Pull the list of emails from the subscription
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'subscriptions', 'hooks', 'emailList');
        $rc = ciniki_subscriptions_hooks_emailList($ciniki, $args['business_id'], array('subscription_ids'=>explode(',', $mailing['subscription_ids'])));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( !isset($rc['emails']) || count($rc['emails']) == 0 ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.mail.43', 'msg'=>'No emails for the specified subscriptions'));
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

    if( $mailing['type'] == 40 ) {
        if( !isset($mailing['object']) || $mailing['object'] == ''
            || !isset($mailing['object_id']) || $mailing['object_id'] == '' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.mail.44', 'msg'=>'Object not specified'));
        }
        //
        // Load the object content
        //
        list($pkg, $mod, $obj) = explode('.', $mailing['object']);
        $rc = ciniki_core_loadMethod($ciniki, $pkg, $mod, 'hooks', 'mailingContent');
        $fn = $rc['function_call'];
        $rc = $fn($ciniki, $args['business_id'], array('object'=>$mailing['object'], 'object_id'=>$mailing['object_id']));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $object = $rc['object'];
        $header_title = $object['title'];
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
        $rc = ciniki_core_objectUpdate($ciniki, $args['business_id'], 'ciniki.mail.mailing', $mailing['id'], array('subject'=>$object['subject']), 0x07);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
    } else {
        $header_title = $business_details['name'];
    }

    //
    // Load the business mail template
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'mail', 'private', 'loadBusinessTemplate');
    $rc = ciniki_mail_loadBusinessTemplate($ciniki, $args['business_id'], array(
        'theme'=>$mailing['theme'],
        'unsubscribe_link'=>'yes',
        'title'=>$header_title,
        'business_name'=>$business_details['name'],
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $template = $rc['template'];
    $theme = $rc['theme'];

    //
    // Prepare Messages
    //
    $html_template = $template['html_header'];
    $text_template = $template['text_header'];

    //
    // Build the message from the another module content
    //
    if( $mailing['type'] == 40 ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'mail', 'private', 'emailObjectPrepare');
        $rc = ciniki_mail_emailObjectPrepare($ciniki, $args['business_id'], $theme, $mailing, $object);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $mailing['subject'] = $rc['subject'];
        $text_content = $rc['text_content'];
        $html_content = "<tr><td style='" . $theme['td_body'] . "'>" . $rc['html_content'] . "</td></tr>";
    } 

    //
    // Build the message
    //
    else {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'mail', 'private', 'emailObjectPrepare');
        $rc = ciniki_mail_emailObjectPrepare($ciniki, $args['business_id'], $theme, $mailing, $mailing);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $mailing['subject'] = $rc['subject'];
        $text_content = $rc['text_content'];
        $html_content = "<tr><td style='" . $theme['td_body'] . "'>" . $rc['html_content'] . "</td></tr>";
        
/*      $text_content = $mailing['text_content'];
        //
        // Convert to HTML
        //
        if( $mailing['html_content'] == '' ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'mail', 'private', 'emailProcessContent');
            $rc = ciniki_mail_emailProcessContent($ciniki, $args['business_id'], $theme, $mailing['text_content']);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            $html_content = "<tr><td style='" . $theme['td_body'] . "'>" . $rc['content'] . "</td></tr>";
//          $html_content = "<tr><td style='" . $theme['td_body'] . "'><p style='" . $theme['p'] . "'>" . preg_replace('/\n\s*\n/m', "</p><p style='" . $theme['p'] . "'>", $text_template) . '</p></td></tr>';
//          $html_content = preg_replace('/\n/m', "<br/>\n", $html_content);
//          $html_content = preg_replace('/<\/p><p/', "</p>\n<p", $html_content);
            // FUTURE: Add processing to find links and replace with email tracking links
        } else {
            $html_content = "<tr><td style='" . $theme['td_body'] . "'>" . $mailing['html_content'] . "</td></tr>";
        } */
    }

    $text_template .= $text_content;
    $html_template .= $html_content;

    //
    // Check if surveys is enabled, and one is set for this mailing
    //
    if( isset($modules['ciniki.surveys']) && $mailing['survey_id'] > 0 ) {
        //
        // Get the survey message, url will be inserted later on
        //
        $html_template .= "<tr><td style='" . $theme['td_body'] . "'>"
            . "<p style='" . $theme['p'] . "'><a style='" . $theme['a'] . "' href='{_survey_url_}'>{_survey_url_}</a></p>"
            . "</td></tr>\n";
        $text_template .= "\n\n{_survey_url_}";
    }

    //
    // Add disclaimer
    //
    if( isset($settings['message-disclaimer']) && $settings['message-disclaimer'] != '' ) {
        $html_template .= "<tr><td style='" . $theme['td_body'] . "'><p style='" . $theme['p'] . "'>" . $settings['message-disclaimer'] . "</p></td></tr>";
        $text_template .= "\n\n" . $settings['message-disclaimer'];
    }

    //
    // Add footer
    //
    $html_template .= $template['html_footer'];
    $text_template .= $template['text_footer'];

    //
    // Get the list of existing emails for this mailing, make sure we don't send twice
    //
    if( !isset($args['test']) || $args['test'] != 'yes' ) {
        $strsql = "SELECT customer_email "
            . "FROM ciniki_mail "
            . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . "AND mailing_id = '" . ciniki_core_dbQuote($ciniki, $args['mailing_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQueryList');
        $rc = ciniki_core_dbQueryList($ciniki, $strsql, 'ciniki.mail', 'emails', 'customer_email');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $existing_emails = $rc['emails'];
    } else {
        $existing_emails = array();
    }

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
        // Copy the template into the email
        //
        $html_message = $html_template;
        $text_message = $text_template;

        //
        // Make the basic substitutions in email content
        //
        $text_message = preg_replace('/\{_name_\}/', $email['customer_name'], $text_message);
        $html_message = preg_replace('/\{_name_\}/', $email['customer_name'], $html_message);

        //
        // Create the unsubscribe url for the customer
        //
        $unsubscribe_key = substr(md5(date('Y-m-d-H-i-s') . rand()), 0, 32);
        $unsubscribe_url = $business_url . '/mail/subscriptions/unsubscribe?e=' . urlencode($email['email']) . '&s=' . $email['subscription_uuid'] . '&k=' . $unsubscribe_key;
        $text_message = preg_replace('/\{_unsubscribe_url_\}/', $unsubscribe_url, $text_message);
        $html_message = preg_replace('/\{_unsubscribe_url_\}/', $unsubscribe_url, $html_message);

        //
        // If sending a test message, don't load it into the database, just send and quit
        //
        if( isset($args['test']) && $args['test'] == 'yes' ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'hooks', 'emailUser');
            ciniki_users_hooks_emailUser($ciniki, $args['business_id'], array(
                'user_id'=>$ciniki['session']['user']['id'], 
                'subject'=>$mailing['subject'], 'textmsg'=>$text_message, 'htmlmsg'=>$html_message));
            continue;
        }

        //
        // Check if surveys is enabled, and one is set for this mailing
        //
        if( isset($modules['ciniki.surveys']) && $mailing['survey_id'] > 0 && (!isset($args['test']) || $args['test'] != 'yes') ) {
            //
            // Get the survey link to insert
            //
            $rc = ciniki_surveys_createCustomerInvite($ciniki, $args['business_id'], $mailing['survey_id'], $mailing['id'], $email['customer_id'], array());
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            $invite_id = $rc['id'];
            $invite_url = $business_url . $rc['url'];
            
            $text_message = preg_replace('/\{_survey_url_\}/', $invite_url, $text_message);
            $html_message = preg_replace('/\{_survey_url_\}/', $invite_url, $html_message);
        } else {
            $invite_id = 0;
        }

        //
        // Setup the customer email in the database
        //
        $rc = ciniki_mail_createCustomerMail($ciniki, $args['business_id'], $settings, $email, 
            $mailing['subject'], $html_message, $text_message, array(
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
        $utc_datetime = strftime("%Y-%m-%d %H:%M:%S");
        $strsql = "UPDATE ciniki_mailings SET status = 40, last_updated = '" . ciniki_core_dbQuote($ciniki, $utc_datetime) . "' "
            . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . "AND id = '" . ciniki_core_dbQuote($ciniki, $args['mailing_id']) . "' "
            . "";
        $rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.mail');
        if( $rc['stat'] != 'ok' ) {
            return $rc; 
        }
        ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.mail', 'ciniki_mail_history', $args['business_id'],
            2, 'ciniki_mailings', $args['mailing_id'], 'status', '40');
        ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.mail', 'ciniki_mail_history', $args['business_id'],
            2, 'ciniki_mailings', $args['mailing_id'], 'date_sent', $utc_datetime);
    }

    return array('stat'=>'ok');
}
?>
