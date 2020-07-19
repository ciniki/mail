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
function ciniki_mail_hooks_uiCustomersData($ciniki, $tnid, $args) {

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
        . "WHERE mail.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND mail.customer_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
        . "ORDER BY date_added DESC "
        . "LIMIT 16 "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.mail', array(
        array('container'=>'data', 'fname'=>'id', 
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
    $rsp['tabs'][] = array(
        'id' => 'ciniki.mail.messages',
        'label' => 'Mail',
        'priority' => 5000,
        'sections' => array(
            'ciniki.mail.messages' => array(
                'label' => 'Mail',
                'type' => 'simplegrid', 
                'num_cols' => 3,
                'limit' => 15,
                'headerValues' => array('Date', 'Subject', 'Status'),
                'cellClasses' => array('multiline', 'multiline', ''),
                'noData' => 'No mail message',
                'addTxt' => 'Add Message',
//                    'addApp' => array('app'=>'ciniki.mail.reminders', 'args'=>array('customer_id'=>$args['customer_id'])),
                'editApp' => array('app'=>'ciniki.mail.main', 'args'=>array('message_id'=>'d.id;')),
                'moreTxt' => 'More',
                'moreApp' => array('app'=>'ciniki.mail.main', 'args'=>array('customer_id'=>$args['customer_id'], 'status'=>"'30'")),
                'cellValues' => array(
                    '0' => "M.multiline(d.date, d.time)",
                    '1' => "M.multiline(d.subject, d.email_address)",
                    '2' => "d.status_text",
                    ),
                'data' => isset($rc['data']) ? $rc['data'] : array(),
                ),
            ),
        );

    return $rsp;
}
?>
