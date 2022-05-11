<?php
//
// Description
// -----------
//
// Arguments
// ---------
// ciniki:
// tnid:     The ID of the tenant to the mail belongs to.
// mail_id:         The ID of the mail message to send.
// 
// Returns
// -------
//
function ciniki_mail_createCustomerMail($ciniki, $tnid, $settings, $customer, $subject, $html_message, $text_message, $args) {
    
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbInsert');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');

    //
    // Get a uuid
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUUID');
    $rc = ciniki_core_dbUUID($ciniki, 'ciniki.mail');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $uuid = $rc['uuid'];

    //
    // Get the tenant storage directory
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'hooks', 'storageDir');
    $rc = ciniki_tenants_hooks_storageDir($ciniki, $tnid, array());
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $mail_dir = $rc['storage_dir'] . '/ciniki.mail';

    if( !file_exists($mail_dir . '/' . $uuid[0]) ) {
        mkdir($mail_dir . '/' . $uuid[0], 0755, true);
    }

    //
    // Write mail to disk, when non-empty
    //
    if( $html_message != '' ) {
        file_put_contents($mail_dir . '/' . $uuid[0] . '/' . $uuid . '.html', $html_message);
    }
    if( $text_message != '' ) {
        file_put_contents($mail_dir . '/' . $uuid[0] . '/' . $uuid . '.text', $text_message);
    }
    
    //
    // Prepare the insert
    //
    $strsql = "INSERT INTO ciniki_mail (uuid, tnid, mailing_id, unsubscribe_key, "
        . "survey_invite_id, "
        . "customer_id, customer_name, customer_email, flags, status, "
        . "mail_to, mail_cc, mail_from, "
        . "subject, html_content, text_content, "
        . "date_added, last_updated) VALUES ("
        . "'" . ciniki_core_dbQuote($ciniki, $uuid) . "', "
        . "'" . ciniki_core_dbQuote($ciniki, $tnid) . "', ";
    if( isset($args['mailing_id']) ) {
        $strsql .= "'" . ciniki_core_dbQuote($ciniki, $args['mailing_id']) . "', ";
    } else {
        $strsql .= "'0', ";
    }
    if( isset($args['unsubscribe_key']) ) {
        $strsql .= "'" . ciniki_core_dbQuote($ciniki, $args['unsubscribe_key']) . "', ";
    } else {
        $strsql .= "'', ";
    }
    if( isset($args['survey_invite_id']) ) {
        $strsql .= "'" . ciniki_core_dbQuote($ciniki, $args['survey_invite_id']) . "', ";
    } else {
        $strsql .= "'0', ";
    }
    $strsql .= "'" . ciniki_core_dbQuote($ciniki, $customer['customer_id']) . "', ";
    $strsql .= "'" . ciniki_core_dbQuote($ciniki, $customer['customer_name']) . "', ";
    $strsql .= "'" . ciniki_core_dbQuote($ciniki, $customer['email']) . "', ";
    if( isset($args['flags']) ) {
        $strsql .= "'" . ciniki_core_dbQuote($ciniki, $args['flags']) . "', ";
    } else {
        $strsql .= "'0', ";
    }
    $strsql .= "'10', ";
    $strsql .= "'" . ciniki_core_dbQuote($ciniki, $customer['customer_name']) . " <" . ciniki_core_dbQuote($ciniki, $customer['email']) . ">', ";
    $strsql .= "'', ";
    $strsql .= "'" . ciniki_core_dbQuote($ciniki, $settings['smtp-from-name']) . " <" . ciniki_core_dbQuote($ciniki, $settings['smtp-from-address']) . ">', ";
    $strsql .= "'" . ciniki_core_dbQuote($ciniki, $subject) . "', ";
    // Message stored on disk
    $strsql .= "'', ";
    $strsql .= "'', ";
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
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.mail.90', 'msg'=>'Unable to add attachment', 'err'=>$rc['err']));
                }
            }
        }
    }

    //
    // Add the object references
    //
    if( isset($args['object']) && $args['object'] != '' && isset($args['object_id']) && $args['object_id'] != '' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
        $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.mail.objref', array(
            'mail_id'=>$mail_id,
            'object'=>$args['object'],
            'object_id'=>$args['object_id'],
            ), 0x04);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.mail.76', 'msg'=>'Unable to add object reference', 'err'=>$rc['err']));
        }
    }

    return array('stat'=>'ok', 'id'=>$mail_id);
}
