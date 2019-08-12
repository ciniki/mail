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
function ciniki_mail_createCustomerMail($ciniki, $tnid, $settings, $email, $subject, $html_message, $text_message, $args) {
    
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbInsert');

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
    $strsql .= "'" . ciniki_core_dbQuote($ciniki, $email['customer_id']) . "', ";
    $strsql .= "'" . ciniki_core_dbQuote($ciniki, $email['customer_name']) . "', ";
    $strsql .= "'" . ciniki_core_dbQuote($ciniki, $email['email']) . "', ";
    if( isset($args['flags']) ) {
        $strsql .= "'" . ciniki_core_dbQuote($ciniki, $args['flags']) . "', ";
    } else {
        $strsql .= "'0', ";
    }
    $strsql .= "'10', ";
    $strsql .= "'" . ciniki_core_dbQuote($ciniki, $email['customer_name']) . " <" . ciniki_core_dbQuote($ciniki, $email['email']) . ">', ";
    $strsql .= "'', ";
    $strsql .= "'" . ciniki_core_dbQuote($ciniki, $settings['smtp-from-name']) . " <" . ciniki_core_dbQuote($ciniki, $settings['smtp-from-address']) . ">', ";
    $strsql .= "'" . ciniki_core_dbQuote($ciniki, $subject) . "', ";
    $strsql .= "'', ";
    $strsql .= "'', ";
//    $strsql .= "'" . ciniki_core_dbQuote($ciniki, $html_message) . "', ";
//    $strsql .= "'" . ciniki_core_dbQuote($ciniki, $text_message) . "', ";
    $strsql .= "UTC_TIMESTAMP(), UTC_TIMESTAMP())";

    $rc = ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.mail');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    return array('stat'=>'ok', 'id'=>$rc['insert_id']);
}
