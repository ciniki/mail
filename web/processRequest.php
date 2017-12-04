<?php
//
// Description
// -----------
// This function takes care of any links from email messages sent out on the companies behalf.
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_mail_web_processRequest($ciniki, $settings, $tnid, $args) {
    
    $page = array(
        'title'=>'Mailing Lists',
        'breadcrumbs'=>(isset($args['breadcrumbs'])?$args['breadcrumbs']:array()),
        'blocks'=>array(),
    );
    $page['breadcrumbs'][] = array('name'=>'Mailing Lists', 'url'=>$ciniki['request']['domain_base_url'] . '/account/subscriptions');

    //
    // Check for unsubscribe requests from subscriptions
    //
    if( isset($ciniki['request']['uri_split'][0]) && $ciniki['request']['uri_split'][0] == 'subscriptions'
        && isset($ciniki['request']['uri_split'][1]) && $ciniki['request']['uri_split'][1] == 'unsubscribe'
        && isset($_GET['e']) && $_GET['e'] != '' 
        && isset($_GET['s']) && $_GET['s'] != ''
        && isset($_GET['k']) && $_GET['k'] != ''
        ) {
        //
        // Get the information about the customer, from the link provided in the email.  The
        // email must be less than 30 days since it was sent for the link to still be active
        //
        $strsql = "SELECT ciniki_subscription_customers.subscription_id AS id, ciniki_subscription_customers.customer_id, "
            . "ciniki_subscriptions.name "
            . "FROM ciniki_mail, ciniki_subscription_customers, ciniki_subscriptions, ciniki_customer_emails "
            . "WHERE ciniki_mail.unsubscribe_key = '" . ciniki_core_dbQuote($ciniki, $_GET['k']) . "' "
            . "AND ciniki_mail.tnid = '" . ciniki_core_dbQuote($ciniki, $ciniki['request']['tnid']) . "' "
            . "AND ciniki_mail.customer_id = ciniki_subscription_customers.customer_id "
            . "AND (UNIX_TIMESTAMP(UTC_TIMESTAMP())-UNIX_TIMESTAMP(ciniki_mail.date_sent)) < 2592000 " // Mail was sent within 30 days
            . "AND ciniki_subscription_customers.tnid = '" . ciniki_core_dbQuote($ciniki, $ciniki['request']['tnid']) . "' "
            . "AND ciniki_subscription_customers.subscription_id = ciniki_subscriptions.id "
            . "AND ciniki_subscriptions.tnid = '" . ciniki_core_dbQuote($ciniki, $ciniki['request']['tnid']) . "' "
            . "AND ciniki_subscriptions.uuid = '" . ciniki_core_dbQuote($ciniki, $_GET['s']) . "' "
            . "AND ciniki_customer_emails.tnid = '" . ciniki_core_dbQuote($ciniki, $ciniki['request']['tnid']) . "' "
            . "AND ciniki_subscription_customers.customer_id = ciniki_customer_emails.customer_id "
            . "AND ciniki_customer_emails.email = '" . ciniki_core_dbQuote($ciniki, $_GET['e']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.mail', 'subscription');
        if( isset($rc['subscription']) && isset($rc['subscription']['id']) ) {
            if( !isset($ciniki['session']['change_log_id']) ) {
                $ciniki['session']['change_log_id'] = 'mail.' . date('ymd.His');
                $ciniki['session']['user'] = array('id'=>'-3');
            }
            $subscription_name = $rc['subscription']['name'];
            ciniki_core_loadMethod($ciniki, 'ciniki', 'subscriptions', 'web', 'unsubscribe');
            ciniki_subscriptions_web_unsubscribe($ciniki, $settings, $ciniki['request']['tnid'], 
                $rc['subscription']['id'], $rc['subscription']['customer_id']);
            $page['blocks'][] = array('type'=>'message', 'content'=>"You have been unsubscribed from the Mailing List.");
        } else {
            $page['blocks'][] = array('type'=>'message', 'content'=>"I'm sorry but you must unsubscribe within 30 days.");
        }
    }

    return array('stat'=>'ok', 'page'=>$page);
}
?>
