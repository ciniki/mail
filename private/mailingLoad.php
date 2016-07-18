<?php
//
// Description
// -----------
// This function loads a mailing from the database.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:         The ID of the business to mail mailing belongs to.
// mailing_id:          The ID of the mailing to get.
//
// Returns
// -------
//
function ciniki_mail_mailingLoad($ciniki, $business_id, $mailing_id) {
    //
    // Get the main information
    //
    $strsql = "SELECT "
        . "ciniki_mailings.id, ciniki_mailings.type, ciniki_mailings.type AS type_text, "
        . "ciniki_mailings.status, ciniki_mailings.status AS status_text, ciniki_mailings.theme, "
        . "ciniki_mailings.survey_id, ciniki_mailings.subject, "
        . "ciniki_mailings.primary_image_id, "
        . "ciniki_mailings.html_content, ciniki_mailings.text_content, "
        . "ciniki_mailings.date_started, ciniki_mailings.date_sent, "
        . "ciniki_subscriptions.id AS subscription_ids, "
        . "ciniki_subscriptions.name AS subscription_names ";
    if( isset($modules['ciniki.surveys']) ) {
        $strsql .= ", IFNULL(ciniki_surveys.name, 'None') AS survey_name ";
    } else {
        $strsql .= ", 'None' AS survey_name ";
    }
    $strsql .= "FROM ciniki_mailings "
        . "LEFT JOIN ciniki_mailing_subscriptions ON (ciniki_mailings.id = ciniki_mailing_subscriptions.mailing_id "
            . "AND ciniki_mailing_subscriptions.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "') "
        . "LEFT JOIN ciniki_subscriptions ON (ciniki_mailing_subscriptions.subscription_id = ciniki_subscriptions.id "
            . "AND ciniki_subscriptions.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "') ";
    if( isset($modules['ciniki.surveys']) ) {
        $strsql .= "LEFT JOIN ciniki_surveys ON (ciniki_mailings.survey_id = ciniki_surveys.id "
            . "AND ciniki_surveys.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "') ";
    }
    $strsql .= "WHERE ciniki_mailings.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
        . "AND ciniki_mailings.id = '" . ciniki_core_dbQuote($ciniki, $mailing_id) . "' "
        . "ORDER BY ciniki_mailings.id ASC ";

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.mail', array(
        array('container'=>'mailings', 'fname'=>'id', 'name'=>'mailing',
            'fields'=>array('id', 'type', 'type_text', 'status', 'status_text', 
                'theme', 'survey_id', 'survey_name', 'primary_image_id', 'subject', 
                'html_content', 'text_content', 'date_started', 'date_sent', 
                'subscription_ids', 'subscription_names'),
            'idlists'=>array('subscription_ids'), 
            'lists'=>array('subscription_names'),
            'maps'=>array(
                'status_text'=>array('10'=>'Creation', '20'=>'Approved', '30'=>'Queueing', '40'=>'Sending', '50'=>'Sent', '60'=>'Deleted'),
                'type_text'=>array('10'=>'General', '20'=>'Newsletter', '30'=>'Alert'),
                ),
            ),
//      array('container'=>'subscriptions', 'fname'=>'subscription_id', 'name'=>'subscription',
//          'fields'=>array('id'=>'subscription_id', 'name'=>'subscription_name')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['mailings']) && !isset($rc['mailings'][0]['mailing']) ) {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1028', 'msg'=>'Unable to find mailing'));
    }
    $mailing = $rc['mailings'][0]['mailing'];
    
    //
    // Get any images for the mailing
    //
    $strsql = "SELECT ciniki_mailing_images.id, "
        . "ciniki_mailing_images.name, "
        . "0 as webflags, "
        . "ciniki_mailing_images.image_id, "
        . "ciniki_mailing_images.description, "
        . "'' as url "
        . "FROM ciniki_mailing_images "
        . "WHERE ciniki_mailing_images.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
        . "AND ciniki_mailing_images.mailing_id = '" . ciniki_core_dbQuote($ciniki, $mailing_id) . "' "
        . "";
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.mail', array(
        array('container'=>'images', 'fname'=>'id', 'name'=>'image',
            'fields'=>array('id', 'name', 'webflags', 'image_id', 'description', 'url')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['images']) ) {
        $mailing['images'] = $rc['images'];
    } else {
        $mailing['images'] = array();
    }

    return array('stat'=>'ok', 'mailing'=>$mailing);
}
?>
