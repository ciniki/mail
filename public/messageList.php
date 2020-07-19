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
function ciniki_mail_messageList(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'status'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Label'), 
        'customer_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Customer'), 
        'offset'=>array('required'=>'no', 'blank'=>'no', 'default'=>'0', 'name'=>'Offset'), 
        'limit'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Limit'), 
        'labels'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Labels'), 
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
    $rc = ciniki_mail_checkAccess($ciniki, $args['tnid'], 'ciniki.mail.messageList', 0); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }

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
    $strsql = "SELECT id, "
        . "status, "
        . "subject, "
        . "customer_id, "
        . "customer_name, "
        . "customer_email, "
        . "from_name, "
        . "from_email, "
        . "IF(text_content<>'',text_content,html_content) AS snippet, "
        . "";
    switch($args['status']) {
        case '5': $strsql .= "date_added AS mail_date "; break;
        case '7': $strsql .= "date_added AS mail_date "; break;
        case '10': $strsql .= "date_added AS mail_date "; break;
        case '15': $strsql .= "date_added AS mail_date "; break;
        case '20': $strsql .= "date_added AS mail_date "; break;
        case '30': $strsql .= "date_sent AS mail_date "; break;
        case '40': $strsql .= "date_received AS mail_date "; break;
        case '41': $strsql .= "date_received AS mail_date "; break;
        case '50': $strsql .= "date_added AS mail_date "; break;
        case '60': $strsql .= "date_added AS mail_date "; break;
    }

    $strsql .= "FROM ciniki_mail "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND status = '" . ciniki_core_dbQuote($ciniki, $args['status']) . "' "
        . "";
    if( isset($args['customer_id']) && $args['customer_id'] > 0 ) {
        $strsql .= "AND customer_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' ";
    }
    switch($args['status']) {
        case '5': $strsql .= "ORDER BY date_added DESC "; break;
        case '7': $strsql .= "ORDER BY date_added DESC "; break;
        case '10': $strsql .= "ORDER BY date_added DESC "; break;
        case '15': $strsql .= "ORDER BY date_added DESC "; break;
        case '20': $strsql .= "ORDER BY date_added DESC "; break;
        case '30': $strsql .= "ORDER BY date_sent DESC "; break;
        case '40': $strsql .= "ORDER BY date_received DESC "; break;
        case '41': $strsql .= "ORDER BY date_received DESC "; break;
        case '50': $strsql .= "ORDER BY date_added DESC "; break;
        case '60': $strsql .= "ORDER BY date_added DESC "; break;
    }

    if( isset($args['offset']) && $args['offset'] > 0 && isset($args['limit']) && $args['limit'] > 0 ) {
        $strsql .= "LIMIT " . filter_var($args['offset'], FILTER_SANITIZE_NUMBER_INT) . ', ' . filter_var($args['limit'], FILTER_SANITIZE_NUMBER_INT);
    } elseif( isset($args['limit']) && $args['limit'] > 0 ) {
        $strsql .= "LIMIT " . filter_var($args['limit'], FILTER_SANITIZE_NUMBER_INT);
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.mail', array(
        array('container'=>'messages', 'fname'=>'id', 
            'fields'=>array('id', 'status', 'status_text'=>'status', 'subject', 'customer_id', 'customer_name', 'customer_email', 'from_name', 'from_email', 'snippet', 'mail_time'=>'mail_date', 'mail_date'),
            'maps'=>array('status_text'=>$maps['message']['status']),
            'utctotz'=>array(
                'mail_date'=>array('timezone'=>$intl_timezone, 'format'=>$date_format),
                'mail_time'=>array('timezone'=>$intl_timezone, 'format'=>$time_format),
                ),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $rsp = array('stat'=>'ok', 'messages' => isset($rc['messages']) ? $rc['messages'] : array());
    foreach($rsp['messages'] as $mid => $message) {
        $snippet = preg_replace('/<\/p>/', ' ', $message['snippet']);
        $snippet = preg_replace('/<style>.*<\/style>/m', '', $snippet);
        $snippet = strip_tags($snippet);
        if( strlen($snippet) > 150) {
            $snippet = substr($snippet, 0, 150);
        }
        $rsp['messages'][$mid]['snippet'] = $snippet;
    }

    //
    // Get the customer details if specified
    //
    if( isset($args['customer_id']) && $args['customer_id'] > 0 ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'hooks', 'customerDetails2');
        $rc = ciniki_customers_hooks_customerDetails2($ciniki, $args['tnid'], array(
            'customer_id' => $args['customer_id'],
            'phones' => 'yes',
            'addresses' => 'yes',
            'subscriptions' => 'yes',
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.mail.83', 'msg'=>'Unable to load customer details', 'err'=>$rc['err']));
        }
        if( isset($rc['details']) ) {
            $rsp['customer_details'] = $rc['details'];
        }
    }

    //
    // Get the list of labels if customer_id is specified
    //
    if( isset($args['labels']) && $args['labels'] == 'yes' ) {
        $rsp['labels'] = array();
        if( isset($ciniki['tenant']['modules']['ciniki.mail']['flags']) && ($ciniki['tenant']['modules']['ciniki.mail']['flags']&0x10) > 0 ) {
            $rsp['labels'][] = array('name'=>'Inbox', 'status'=>40);
        }
    //      array('name'=>'Drafts', 'status'=>5),
    //      array('name'=>'Flagged', 'status'=>41),
        $rsp['labels'][] = array('name'=>'Pending', 'status'=>7);
        $rsp['labels'][] = array('name'=>'Queued', 'status'=>10);
        $rsp['labels'][] = array('name'=>'Queue Failures', 'status'=>15);
        $rsp['labels'][] = array('name'=>'Sending', 'status'=>20);
        $rsp['labels'][] = array('name'=>'Sent', 'status'=>30);
        $rsp['labels'][] = array('name'=>'Failed', 'status'=>50);
        $rsp['labels'][] = array('name'=>'Trash', 'status'=>60);

        //
        // Get the counts
        //
        $strsql = "SELECT status, COUNT(id) AS num_messages "
            . "FROM ciniki_mail "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ( status <> 40 " 
                . "OR (status = 40 AND (flags&0x10)=0) " // Only count unread in inbox
                . ") ";
        if( isset($args['customer_id']) && $args['customer_id'] > 0 ) {
            $strsql .= "AND customer_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' ";
        }
        $strsql .= "GROUP BY status "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
        $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.mail', array(
            array('container'=>'status', 'fname'=>'status', 'fields'=>array('status', 'num_messages')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        foreach($rsp['labels'] as $lid => $label) {
            if( isset($rc['status'][$label['status']]) ) {
                $rsp['labels'][$lid]['num_messages'] = $rc['status'][$label['status']]['num_messages'];
            }
        }
    }

    return $rsp;
}
?>
