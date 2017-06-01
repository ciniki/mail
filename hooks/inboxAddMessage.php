<?php
//
// Description
// -----------
// Insert a new message into the mail inbox, and send notification if requested.
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_mail_hooks_inboxAddMessage(&$ciniki, $business_id, $args) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbInsert');

    //
    // Check arguments
    //
    if( !isset($args['from_email']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.mail.11', 'msg'=>'No email address specified'));
    }
    if( !isset($args['from_name']) ) {
        $args['from_name'] = '';
        $args['mail_from'] = $args['from_email'];
    } else {
        $args['mail_from'] = $args['from_name'] . ' <' . $args['from_email'] . '>';
    }
    if( !isset($args['subject']) ) {
        $args['subject'] = '';
    }
    if( !isset($args['text_content']) && !isset($args['html_content']) ) {
        $args['html_content'] = '';
        $args['text_content'] = '';
    } elseif( !isset($args['html_content']) ) {
        $args['html_content'] = $args['text_content'];
    } else {
        $args['text_content'] = strip_tags($args['html_content']);
    }
    if( !isset($args['flags']) ) {
        $args['flags'] = '0';
    }
    $args['status'] = '40';


    //
    // If no customer was specified, then search for email 
    // in customers to attach
    //
    if( !isset($args['customer_id']) || $args['customer_id'] == '' ) {
        $args['customer_id'] = 0;
        //
        // FIXME: Search for customer
        //
    }

    //
    // Get a UUID
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUUID');
    $rc = ciniki_core_dbUUID($ciniki, 'ciniki.mail');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.mail.12', 'msg'=>'Unable to get a new UUID', 'err'=>$rc['err']));
    }
    $args['uuid'] = $rc['uuid'];

    //
    // Add the message to the inbox
    //
    $strsql = "INSERT INTO ciniki_mail (uuid, business_id, mailing_id, unsubscribe_key, "
        . "survey_invite_id, "
        . "customer_id, customer_name, customer_email, flags, status, "
        . "mail_to, mail_cc, mail_from, from_name, from_email, "
        . "subject, html_content, text_content, "
        . "date_added, last_updated) VALUES ("
        . "'" . ciniki_core_dbQuote($ciniki, $args['uuid']) . "', "
        . "'" . ciniki_core_dbQuote($ciniki, $business_id) . "', "
        . "0, '', 0, ";
    $strsql .= "'" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "', ";
    $strsql .= "'', ";
    $strsql .= "'', ";
    $strsql .= "'" . ciniki_core_dbQuote($ciniki, $args['flags']) . "', ";
    $strsql .= "'" . ciniki_core_dbQuote($ciniki, $args['status']) . "', ";
    $strsql .= "'', '', ";
    $strsql .= "'" . ciniki_core_dbQuote($ciniki, $args['mail_from']) . "', ";
    $strsql .= "'" . ciniki_core_dbQuote($ciniki, $args['from_name']) . "', ";
    $strsql .= "'" . ciniki_core_dbQuote($ciniki, $args['from_email']) . "', ";
    $strsql .= "'" . ciniki_core_dbQuote($ciniki, $args['subject']) . "', ";
    $strsql .= "'" . ciniki_core_dbQuote($ciniki, $args['html_content']) . "', ";
    $strsql .= "'" . ciniki_core_dbQuote($ciniki, $args['text_content']) . "', ";
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
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.mail.13', 'msg'=>'Unable to add attachment', 'err'=>$rc['err']));
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
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.mail.14', 'msg'=>'Unable to add object reference', 'err'=>$rc['err']));
        }
    }
    if( isset($args['parent_object']) && $args['parent_object'] != '' && isset($args['parent_object_id']) && $args['parent_object_id'] != '' ) {
        $rc = ciniki_core_objectAdd($ciniki, $business_id, 'ciniki.mail.objref', array(
            'mail_id'=>$mail_id,
            'object'=>$args['parent_object'],
            'object_id'=>$args['parent_object_id'],
            ), 0x04);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.mail.15', 'msg'=>'Unable to add parent object reference', 'err'=>$rc['err']));
        }
    }

    //
    // Send a notification of new message
    //
    if( isset($args['notification']) && $args['notification'] == 'yes' ) {
        $msg = "New message from " . $args['from_name'] . " (" . $args['from_email'] . ")\n"
            . "\n"
            . "Message: \n\n"
            . $args['text_content']
            . "";
        if( isset($args['notification_emails']) && $args['notification_emails'] != '' ) {
            $send_to_emails = explode(',', $args['notification_emails']);
            foreach($send_to_emails as $email) {
                $ciniki['emailqueue'][] = array('to'=>trim($email),
                    'replyto_email'=>$args['from_name'],
                    'replyto_name'=>$args['from_email'],
                    'subject'=>$args['subject'],
                    'textmsg'=>$msg,
                    );
            }
        } else {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'hooks', 'businessOwners');
            $rc = ciniki_businesses_hooks_businessOwners($ciniki, $business_id, array());
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.mail.16', 'msg'=>'Unable to get business owners', 'err'=>$rc['err']));
            }
            $owners = $rc['users'];
            foreach($owners as $user_id => $owner) {
                $ciniki['emailqueue'][] = array('user_id'=>$user_id,
                    'replyto_email'=>$args['from_email'],
                    'replyto_name'=>$args['from_name'],
                    'subject'=>$args['subject'],
                    'textmsg'=>$msg,
                    );
            }
        }
    }

    return array('stat'=>'ok', 'id'=>$mail_id);
}
?>
