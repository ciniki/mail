<?php
//
// Description
// -----------
// This function will return the data for customer(s) to be displayed in the IFB display panel.
// The request might be for 1 individual, or multiple customer ids for a family.
//
// Arguments
// ---------
// ciniki:
// tnid:     The ID of the tenant to get mail for.
//
// Returns
// -------
//
function ciniki_mail_hooks_customerMessages($ciniki, $tnid, $args) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuoteIDs');

    //
    // Load the date format strings for the user
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'timeFormat');
    $time_format = ciniki_users_timeFormat($ciniki, 'php');
    
    //
    // Load the tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    
    //
    // Load maps
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'mail', 'private', 'maps');
    $rc = ciniki_mail_maps($ciniki);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $maps = $rc['maps'];
    
    //
    // Default response
    //
    $rsp = array('stat'=>'ok', 'tabs'=>array());

    //
    // Get the list of emails sent to customer
    //
    $date_format = preg_replace('/ /', '\&\n\b\s\p\;', $date_format);
    $strsql = "SELECT mail.id, "
        . "CONCAT_WS(' ', 'To: ', mail.customer_email) AS email_address, "
        . "mail.status, "
        . "mail.status AS status_text, "
        . "IF(mail.status = 30, mail.date_sent, mail.date_added) AS dt, "
        . "mail.subject "
        . "FROM ciniki_mail AS mail "
        . "WHERE mail.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' ";
    if( isset($args['customer_ids']) ) {
        $strsql .= "AND mail.customer_id IN (" . ciniki_core_dbQuoteIDs($ciniki, $args['customer_ids']) . ") ";
    } else {
        $strsql .= "AND mail.customer_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' ";
    }
    $strsql .= "ORDER BY mail.date_added DESC "
        . "LIMIT 50 "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.mail', array(
        array('container'=>'messages', 'fname'=>'id', 
            'fields'=>array('id', 'email_address', 'status', 'status_text', 'date'=>'dt', 'time'=>'dt', 'subject'),
            'utctotz'=>array(
                'date'=>array('timezone'=>$intl_timezone, 'format'=>$date_format),
                'time'=>array('timezone'=>$intl_timezone, 'format'=>$time_format),
                ),
            'maps'=>array('status_text'=>$maps['mail']['status']),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $messages = isset($rc['messages']) ? $rc['messages'] : array();

    return array('stat'=>'ok', 'messages'=>$messages);
}
?>
