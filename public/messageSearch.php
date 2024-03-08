<?php
//
// Description
// -----------
// This method returns the list of messages
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant to add the mail mailing to.
//
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_mail_messageSearch(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'start_needle'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Search String'), 
        'status'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Label'), 
        'offset'=>array('required'=>'no', 'blank'=>'no', 'default'=>'0', 'name'=>'Offset'), 
        'limit'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Limit'), 
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];

    //  
    // Make sure this module is activated, and
    // check permission to run this function for this tenant
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'mail', 'private', 'checkAccess');
    $rc = ciniki_mail_checkAccess($ciniki, $args['tnid'], 'ciniki.mail.messageSearch'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }

    //
    // Load the status maps for the text description of each status
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'mail', 'private', 'maps');
    $rc = ciniki_mail_maps($ciniki);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $maps = $rc['maps'];

    //
    // Get the tenant date/time settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $args['tnid']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'timeFormat');
    $time_format = ciniki_users_timeFormat($ciniki, 'php');

    //
    // Get the messages for the label
    //
    $strsql = "SELECT id, subject, customer_id, customer_name, customer_email, "
        . "status, status AS status_text, "
        . "IF(text_content<>'',text_content,html_content) AS snippet, "
        . "IF(status='30',date_sent,date_added) AS mail_date "
        . "FROM ciniki_mail "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    if( isset($args['status']) && $args['status'] != '' ) {
        $strsql .= "AND status = '" . ciniki_core_dbQuote($ciniki, $args['status']) . "' ";
    }
    
    $strsql .= "AND ("
        . "customer_name like '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
        . "OR customer_name like '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
        . "OR customer_email like '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
        . "OR customer_email like '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
        . "OR subject like '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
        . "OR subject like '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
        . ") ";

    $strsql .= "ORDER BY mail_date DESC ";

    if( isset($args['offset']) && $args['offset'] > 0 && isset($args['limit']) && $args['limit'] > 0 ) {
        $strsql .= "LIMIT " . filter_var($args['offset'], FILTER_SANITIZE_NUMBER_INT) . ', ' . filter_var($args['limit'], FILTER_SANITIZE_NUMBER_INT);
    } elseif( isset($args['limit']) && $args['limit'] > 0 ) {
        $strsql .= "LIMIT " . filter_var($args['limit'], FILTER_SANITIZE_NUMBER_INT);
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.mail', array(
        array('container'=>'messages', 'fname'=>'id', 'name'=>'message',
            'fields'=>array('id', 'subject', 'customer_id', 'customer_name', 'customer_email', 'snippet', 
                'mail_time'=>'mail_date', 'mail_date', 'status', 'status_text'),
            'maps'=>array('status_text'=>$maps['message']['status']),
            'utctotz'=>array('mail_date'=>array('timezone'=>$intl_timezone, 'format'=>$date_format),
                'mail_time'=>array('timezone'=>$intl_timezone, 'format'=>$time_format)),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['messages']) ) {
        foreach($rc['messages'] as $mid => $message) {
            $snippet = preg_replace('/<\/p>/', ' ', $message['snippet']);
            $snippet = preg_replace('/<style>.*<\/style>/m', '', $snippet);
            $snippet = strip_tags($snippet);
            if( strlen($snippet) > 150) {
                $snippet = substr($snippet, 0, 150);
            }
            $rc['messages'][$mid]['snippet'] = $snippet;
        }
        return array('stat'=>'ok', 'messages'=>$rc['messages']);
    } 
    return array('stat'=>'ok', 'messages'=>array());
}
?>
