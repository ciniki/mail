<?php
//
// Description
// -----------
// Add a new message to the queue.
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_mail_hooks_emailSubscriptionLists(&$ciniki, $tnid, $args) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbInsert');

    //
    // Check arguments
    //
    if( !isset($args['subscriptions']) || count($args['subscriptions']) == 0 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.mail.68', 'msg'=>'No subscriptions specified'));
    }
    if( !isset($args['subject']) || $args['subject'] == '' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.mail.69', 'msg'=>'No subject specified'));
    }
    if( !isset($args['flags']) ) {
        $args['flags'] = '0';
    }
    if( !isset($args['status']) ) {
        $args['status'] = '10';
    }


    //
    // Get the settings for the mail module
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'mail', 'private', 'getSettings');
    $rc = ciniki_mail_getSettings($ciniki, $tnid); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $settings = $rc['settings'];

    //
    // Get the web tenant settings to include in email
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'web', 'details');
    $rc = ciniki_tenants_web_details($ciniki, $tnid); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $tenant_details = $rc['details'];

    //
    // Get the tenant storage directory
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'hooks', 'storageDir');
    $rc = ciniki_tenants_hooks_storageDir($ciniki, $tnid, array());
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $mail_dir = $rc['storage_dir'] . '/ciniki.mail';

    //
    // Check for both html and text content
    //
    if( !isset($args['text_content']) && !isset($args['html_content']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.mail.70', 'msg'=>'No message specified'));
    } elseif( isset($args['html_content']) && !isset($args['text_content']) ) {
        $args['text_content'] = strip_tags($args['html_content']);
    } elseif( isset($args['text_content']) && !isset($args['html_content']) ) {
        $args['html_content'] = $args['text_content'];
    }

    //
    // Get the list of customers to send email to
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'subscriptions', 'hooks', 'emailList');
    $rc = ciniki_subscriptions_hooks_emailList($ciniki, $tnid, array('subscription_ids'=>$args['subscriptions']));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $emails = $rc['emails'];

    //
    // Load tenant template for formatting
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'mail', 'private', 'loadTenantTemplate');
    $rc = ciniki_mail_loadTenantTemplate($ciniki, $tnid, array(
        'theme'=>(isset($args['theme'])?$args['theme']:''),
        'title'=>(isset($args['title'])?$args['title']:$args['subject']),
        'unsubscribe_url'=>'{_unsub_url_}',
        'unsubscribe_text'=>'{_unsub_text_}',
        'tenant_name'=>$tenant_details['name'],
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $template = $rc['template'];
    $theme = $rc['theme'];

    //
    // Build the message
    //
    $text_content = $template['text_header'];
    $html_content = $template['html_header'];

    //
    // Add the text content
    //
    $text_content .= $args['text_content'];

    //
    // Process the html email content to format
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'mail', 'private', 'emailProcessContent');
    $rc = ciniki_mail_emailProcessContent($ciniki, $tnid, $theme, $args['html_content']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $html_content .= "<tr><td style='" . $theme['td_body'] . "'>" . $rc['content'] . "</td></tr>";

    //
    // Add disclaimer if set
    //
    if( isset($settings['message-disclaimer']) && $settings['message-disclaimer'] != '' ) {
        $html_content .= "<tr><td style='" . $theme['td_body'] . "'><p style='" . $theme['p'] . "'>" . $settings['message-disclaimer'] . "</p></td></tr>";
        $text_content .= "\n\n" . $settings['message-disclaimer'];
    }

    //
    // Add the footer
    //
    $text_content .= $template['text_footer'];
    $html_content .= $template['html_footer'];

    //
    // Got through the customers in the subscription lists
    //
    foreach($emails as $email) {
        
        $unsub_url = '';
        $unsub_text = '';
        $text_content = str_replace('{_unsub_url_}', $unsub_url, $text_content);
        $text_content = str_replace('{_unsub_text_}', $unsub_text, $text_content);
        $html_content = str_replace('{_unsub_url_}', $unsub_url, $html_content);
        $html_content = str_replace('{_unsub_text_}', $unsub_text, $html_content);

        //
        // Get a UUID for use in permalink
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUUID');
        $rc = ciniki_core_dbUUID($ciniki, 'ciniki.mail');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.mail.5', 'msg'=>'Unable to get a new UUID', 'err'=>$rc['err']));
        }
        $args['uuid'] = $rc['uuid'];

        //
        // Write mail to disk, when non-empty
        //
        if( $html_content != '' ) {
            file_put_contents($mail_dir . '/' . $args['uuid'][0] . '/' . $args['uuid'] . '.html', $html_content);
        }
        if( $text_content != '' ) {
            file_put_contents($mail_dir . '/' . $args['uuid'][0] . '/' . $args['uuid'] . '.text', $text_content);
        }
    
        //
        // Add the message
        //
        $strsql = "INSERT INTO ciniki_mail (uuid, tnid, mailing_id, unsubscribe_key, "
            . "survey_invite_id, "
            . "customer_id, customer_name, customer_email, flags, status, "
            . "mail_to, mail_cc, mail_from, "
            . "subject, html_content, text_content, "
            . "date_added, last_updated) VALUES ("
            . "'" . ciniki_core_dbQuote($ciniki, $args['uuid']) . "', "
            . "'" . ciniki_core_dbQuote($ciniki, $tnid) . "', "
            . "0, '', 0, ";
        $strsql .= "'" . ciniki_core_dbQuote($ciniki, $email['customer_id']) . "', ";
        $strsql .= "'" . ciniki_core_dbQuote($ciniki, $email['customer_name']) . "', ";
        $strsql .= "'" . ciniki_core_dbQuote($ciniki, $email['email']) . "', ";
        $strsql .= "'" . ciniki_core_dbQuote($ciniki, $args['flags']) . "', ";
        $strsql .= "'" . ciniki_core_dbQuote($ciniki, $args['status']) . "', ";
        $strsql .= "'', '', '', ";
        $strsql .= "'" . ciniki_core_dbQuote($ciniki, $args['subject']) . "', ";
        $strsql .= "'', ";
        $strsql .= "'', ";
//        $strsql .= "'" . ciniki_core_dbQuote($ciniki, $html_content) . "', ";
//        $strsql .= "'" . ciniki_core_dbQuote($ciniki, $text_content) . "', ";
        $strsql .= "UTC_TIMESTAMP(), UTC_TIMESTAMP())";

        $rc = ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.mail');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $mail_id = $rc['insert_id'];    
        
        //
        // Add the attachments
        //
        if( isset($args['attachments']) ) {
            foreach($args['attachments'] as $attachment) {
                if( isset($attachment['filename']) && isset($attachment['content']) ) {
                    $attachment['mail_id'] = $mail_id;
                    //
                    // Get uuid
                    //
                    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUUID');
                    $rc = ciniki_core_dbUUID($ciniki, 'ciniki.mail');
                    if( $rc['stat'] != 'ok' ) {
                        return $rc;
                    }
                    $attachment['uuid'] = $rc['uuid'];

                    //
                    // Save attachment to disk
                    //
                    file_put_contents($mail_dir . '/' . $attachment['uuid'][0] . '/' . $attachment['uuid'] . '.attachment', $attachment['content']);
                    $attachment['content'] = '';

                    //
                    // Save object
                    //
                    $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.mail.attachment', $attachment, 0x04);
                    if( $rc['stat'] != 'ok' ) {
                        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.mail.6', 'msg'=>'Unable to add attachment', 'err'=>$rc['err']));
                    }
                }
            }
        }
    }
    
    return array('stat'=>'ok', 'id'=>$mail_id);
}
?>
