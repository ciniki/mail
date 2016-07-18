<?php
//
// Description
// -----------
// Return the list of subscriptions available to another module, and if there is a mailing and subscription for the object.
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_mail_hooks_objectSubscriptions($ciniki, $business_id, $args) {

    //
    // Load the status maps for the text description of each status
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'mail', 'private', 'maps');
    $rc = ciniki_mail_maps($ciniki);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $maps = $rc['maps'];


    if( isset($args['object']) && $args['object'] != '' 
        && isset($args['object_id']) && $args['object_id'] != ''
        ) {
        //
        // Check if there is a mailing for this object
        //
        $mailing = NULL;
        if( $args['object_id'] > 0 ) {
            $strsql = "SELECT ciniki_mailings.id, status "
                . "FROM ciniki_mailings "
                . "WHERE ciniki_mailings.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
                . "AND ciniki_mailings.type = 40 "
                . "AND ciniki_mailings.object = '" . ciniki_core_dbQuote($ciniki, $args['object']) . "' "
                . "AND ciniki_mailings.object_id = '" . ciniki_core_dbQuote($ciniki, $args['object_id']) . "' "
                . "";
            $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.mail', 'mailing');
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( isset($rc['num_rows']) && $rc['num_rows'] > 1 ) {
                return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2135', 'msg'=>'More than one mailing for this object'));
            }
            if( isset($rc['mailing']) ) {
                $mailing = $rc['mailing'];
                if( isset($maps['mailing']['status'][$mailing['status']]) ) {
                    $mailing['status_text'] = $maps['mailing']['status'][$mailing['status']];
                } else {
                    $mailing['status_text'] = 'Unsent';
                }
            }
        }
    
        //
        // If there is no mailing for this object, then get the list of subscriptions
        //
        if( $mailing == NULL ) {
            $strsql = "SELECT ciniki_subscriptions.id, "
                . "ciniki_subscriptions.name, "
                . "'no' AS status, "
                . "0 AS mailing_status, "
                . "'Unsent' AS mailing_status_text "
                . "FROM ciniki_subscriptions "
                . "WHERE ciniki_subscriptions.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
                . "AND ciniki_subscriptions.status = 10 "
                . "";
        } else {
            $strsql = "SELECT ciniki_subscriptions.id, "
                . "IFNULL(ciniki_mailing_subscriptions.id, 0) AS mailing_id, "
                . "ciniki_subscriptions.name, "
                . "IF(IFNULL(ciniki_mailing_subscriptions.id, 0)<1, 'no', 'yes') AS status, "
                . "'" . ciniki_core_dbQuote($ciniki, $mailing['status']) . "' AS mailing_status, "
                . "'" . ciniki_core_dbQuote($ciniki, $mailing['status_text']) . "' AS mailing_status_text "
                . "FROM ciniki_subscriptions "
                . "LEFT JOIN ciniki_mailing_subscriptions ON ("
                    . "ciniki_subscriptions.id = ciniki_mailing_subscriptions.subscription_id "
                    . "AND ciniki_mailing_subscriptions.mailing_id = '" . ciniki_core_dbQuote($ciniki, $mailing['id']) . "' "
                    . "AND ciniki_mailing_subscriptions.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
                . ") "
                . "WHERE ciniki_subscriptions.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
                . "AND ciniki_subscriptions.status = 10 "
                . "";
        }
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
        $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.blog', array(
        array('container'=>'subscriptions', 'fname'=>'id', 'name'=>'subscription',
            'fields'=>array('id', 'name', 'status', 'mailing_status', 'mailing_status_text')),
        ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['subscriptions']) ) {
            return array('stat'=>'ok', 'subscriptions'=>$rc['subscriptions'], 'mailing'=>($mailing==NULL?array():$mailing));
        }
        return array('stat'=>'ok', 'subscriptions'=>array());
    }

    return array('stat'=>'ok');
}
?>
