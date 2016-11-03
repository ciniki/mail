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
function ciniki_mail_hooks_addMessage(&$ciniki, $business_id, $args) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbInsert');

    //
    // Check arguments
    //
    if( !isset($args['customer_id']) || $args['customer_id'] == '' ) {
        $args['customer_id'] = 0;
    }
    if( !isset($args['customer_email']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.mail.3', 'msg'=>'No email specified'));
    }
    if( !isset($args['customer_name']) ) {
        $args['customer_name'] = '';
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
    $rc = ciniki_mail_getSettings($ciniki, $business_id); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $settings = $rc['settings'];

    //
    // Get the web business settings to include in email
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'web', 'details');
    $rc = ciniki_businesses_web_details($ciniki, $business_id); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $business_details = $rc['details'];

    //
    // Check for both html and text content
    //
    if( !isset($args['text_content']) && !isset($args['html_content']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.mail.4', 'msg'=>'No message specified'));
    } elseif( isset($args['html_content']) && !isset($args['text_content']) ) {
        $args['text_content'] = strip_tags($args['html_content']);
    } elseif( isset($args['text_content']) && !isset($args['html_content']) ) {
        $args['html_content'] = $args['text_content'];
    }

    //
    // FIXME: load business template for formatting
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'mail', 'private', 'loadBusinessTemplate');
    $rc = ciniki_mail_loadBusinessTemplate($ciniki, $business_id, array(
        'theme'=>(isset($args['theme'])?$args['theme']:''),
        'title'=>(isset($args['title'])?$args['title']:$args['subject']),
        'unsubscribe_url'=>(isset($args['unsubscribe_url'])?$args['unsubscribe_url']:''),
        'unsubscribe_text'=>(isset($args['unsubscribe_text'])?$args['unsubscribe_text']:''),
        'business_name'=>$business_details['name'],
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
    $rc = ciniki_mail_emailProcessContent($ciniki, $business_id, $theme, $args['html_content']);
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
    // Get a UUID for use in permalink
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUUID');
    $rc = ciniki_core_dbUUID($ciniki, 'ciniki.mail');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.mail.5', 'msg'=>'Unable to get a new UUID', 'err'=>$rc['err']));
    }
    $args['uuid'] = $rc['uuid'];

    //
    // Add the message
    //
    $strsql = "INSERT INTO ciniki_mail (uuid, business_id, mailing_id, unsubscribe_key, "
        . "survey_invite_id, "
        . "customer_id, customer_name, customer_email, flags, status, "
        . "mail_to, mail_cc, mail_from, "
        . "subject, html_content, text_content, "
        . "date_added, last_updated) VALUES ("
        . "'" . ciniki_core_dbQuote($ciniki, $args['uuid']) . "', "
        . "'" . ciniki_core_dbQuote($ciniki, $business_id) . "', "
        . "0, '', 0, ";
    $strsql .= "'" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "', ";
    $strsql .= "'" . ciniki_core_dbQuote($ciniki, $args['customer_name']) . "', ";
    $strsql .= "'" . ciniki_core_dbQuote($ciniki, $args['customer_email']) . "', ";
    $strsql .= "'" . ciniki_core_dbQuote($ciniki, $args['flags']) . "', ";
    $strsql .= "'" . ciniki_core_dbQuote($ciniki, $args['status']) . "', ";
    $strsql .= "'', '', '', ";
    $strsql .= "'" . ciniki_core_dbQuote($ciniki, $args['subject']) . "', ";
//  $strsql .= "'" . ciniki_core_dbQuote($ciniki, $args['html_content']) . "', ";
//  $strsql .= "'" . ciniki_core_dbQuote($ciniki, $args['text_content']) . "', ";
    $strsql .= "'" . ciniki_core_dbQuote($ciniki, $html_content) . "', ";
    $strsql .= "'" . ciniki_core_dbQuote($ciniki, $text_content) . "', ";
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
                $rc = ciniki_core_objectAdd($ciniki, $business_id, 'ciniki.mail.attachment', $attachment, 0x04);
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.mail.6', 'msg'=>'Unable to add attachment', 'err'=>$rc['err']));
                }
            }
        }
    }

    //
    // Add the object references
    //
    if( isset($args['object']) && $args['object'] != '' && isset($args['object_id']) && $args['object_id'] != '' ) {
        $rc = ciniki_core_objectAdd($ciniki, $business_id, 'ciniki.mail.objref', array(
            'mail_id'=>$mail_id,
            'object'=>$args['object'],
            'object_id'=>$args['object_id'],
            ), 0x04);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.mail.7', 'msg'=>'Unable to add object reference', 'err'=>$rc['err']));
        }
    }
    if( isset($args['parent_object']) && $args['parent_object'] != '' && isset($args['parent_object_id']) && $args['parent_object_id'] != '' ) {
        $rc = ciniki_core_objectAdd($ciniki, $business_id, 'ciniki.mail.objref', array(
            'mail_id'=>$mail_id,
            'object'=>$args['parent_object'],
            'object_id'=>$args['parent_object_id'],
            ), 0x04);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.mail.8', 'msg'=>'Unable to add parent object reference', 'err'=>$rc['err']));
        }
    }

    return array('stat'=>'ok', 'id'=>$mail_id);
}
?>
