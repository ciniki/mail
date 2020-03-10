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
function ciniki_mail_hooks_addMessage(&$ciniki, $tnid, $args) {

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
    // Check for both html and text content
    //
    if( !isset($args['text_content']) && !isset($args['html_content']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.mail.75', 'msg'=>'No message specified'));
    } elseif( isset($args['html_content']) && !isset($args['text_content']) ) {
        $args['text_content'] = strip_tags($args['html_content']);
    } elseif( isset($args['text_content']) && !isset($args['html_content']) ) {
        $args['html_content'] = $args['text_content'];
    }

    //
    // load tenant template for formatting
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'mail', 'private', 'loadTenantTemplate');
    $rc = ciniki_mail_loadTenantTemplate($ciniki, $tnid, array(
        'theme'=>(isset($args['theme'])?$args['theme']:''),
        'title'=>(isset($args['title'])?$args['title']:$args['subject']),
        'unsubscribe_url'=>(isset($args['unsubscribe_url'])?$args['unsubscribe_url']:''),
        'unsubscribe_text'=>(isset($args['unsubscribe_text'])?$args['unsubscribe_text']:''),
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
    // Get a UUID for use in permalink
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUUID');
    $rc = ciniki_core_dbUUID($ciniki, 'ciniki.mail');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.mail.79', 'msg'=>'Unable to get a new UUID', 'err'=>$rc['err']));
    }
    $args['uuid'] = $rc['uuid'];

    //
    // Get the tenant storage directory
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'hooks', 'storageDir');
    $rc = ciniki_tenants_hooks_storageDir($ciniki, $tnid, array());
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $mail_dir = $rc['storage_dir'] . '/ciniki.mail/' . $args['uuid'][0];

    //
    // Create the directory if it doesn't exist
    //
    if( !file_exists($mail_dir) ) {
        if( mkdir($mail_dir, 0700, true) === false ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.mail.78', 'msg'=>'Unable to create mail message', 'err'=>$rc['err']));
        }
    }

    //
    // Write mail to disk, when non-empty
    //
    if( $args['html_content'] != '' ) {
        file_put_contents($mail_dir . '/' . $args['uuid'] . '.html', $html_content);
    }
    if( $args['text_content'] != '' ) {
        file_put_contents($mail_dir . '/' . $args['uuid'] . '.text', $text_content);
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
    $strsql .= "'" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "', ";
    $strsql .= "'" . ciniki_core_dbQuote($ciniki, $args['customer_name']) . "', ";
    $strsql .= "'" . ciniki_core_dbQuote($ciniki, $args['customer_email']) . "', ";
    $strsql .= "'" . ciniki_core_dbQuote($ciniki, $args['flags']) . "', ";
    $strsql .= "'" . ciniki_core_dbQuote($ciniki, $args['status']) . "', ";
    $strsql .= "'', '', '', ";
    $strsql .= "'" . ciniki_core_dbQuote($ciniki, $args['subject']) . "', ";
//  $strsql .= "'" . ciniki_core_dbQuote($ciniki, $args['html_content']) . "', ";
//  $strsql .= "'" . ciniki_core_dbQuote($ciniki, $args['text_content']) . "', ";
    $strsql .= "'', ";
    $strsql .= "'', ";
//    $strsql .= "'" . ciniki_core_dbQuote($ciniki, $html_content) . "', ";
//    $strsql .= "'" . ciniki_core_dbQuote($ciniki, $text_content) . "', ";
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
                $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.mail.attachment', $attachment, 0x04);
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.mail.74', 'msg'=>'Unable to add attachment', 'err'=>$rc['err']));
                }
            }
        }
    }

    //
    // Add the object references
    //
    if( isset($args['object']) && $args['object'] != '' && isset($args['object_id']) && $args['object_id'] != '' ) {
        $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.mail.objref', array(
            'mail_id'=>$mail_id,
            'object'=>$args['object'],
            'object_id'=>$args['object_id'],
            ), 0x04);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.mail.7', 'msg'=>'Unable to add object reference', 'err'=>$rc['err']));
        }
    }
    if( isset($args['parent_object']) && $args['parent_object'] != '' && isset($args['parent_object_id']) && $args['parent_object_id'] != '' ) {
        $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.mail.objref', array(
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
