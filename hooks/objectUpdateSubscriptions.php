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
function ciniki_mail_hooks_objectUpdateSubscriptions($ciniki, $tnid, $args) {

    if( isset($args['object']) && $args['object'] != '' 
        && isset($args['object_id']) && $args['object_id'] != ''
        ) {
        //
        // Check if there is a mailing for this object
        //
        $mailing = NULL;
        if( $args['object_id'] > 0 ) {
            $strsql = "SELECT ciniki_mailings.id, "
                . "ciniki_mailings.status "
                . "FROM ciniki_mailings "
                . "WHERE ciniki_mailings.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . "AND ciniki_mailings.type = 40 "
                . "AND ciniki_mailings.object = '" . ciniki_core_dbQuote($ciniki, $args['object']) . "' "
                . "AND ciniki_mailings.object_id = '" . ciniki_core_dbQuote($ciniki, $args['object_id']) . "' "
                . "";
            $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.mail', 'mailing');
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( isset($rc['num_rows']) && $rc['num_rows'] > 1 ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.mail.18', 'msg'=>'More than one mailing for this object'));
            }
            if( isset($rc['mailing']) ) {
                $mailing = $rc['mailing'];
                if( $mailing['status'] != '10' ) {
                    return array('stat'=>'ok', 'err'=>array('code'=>'ciniki.mail.19', 'msg'=>'Mailing has already been sent and can no longer be changed'));
                }
            }
        }

        //
        // Get the subscriptions which are active
        //
        if( $mailing == NULL ) {
            $strsql = "SELECT ciniki_subscriptions.id, "
                . "ciniki_subscriptions.name, "
                . "'0' AS mailing_subscription_id, "
                . "'no' AS status "
                . "FROM ciniki_subscriptions "
                . "WHERE ciniki_subscriptions.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . "AND ciniki_subscriptions.status = 10 "
                . "";
        } else {
            $strsql = "SELECT ciniki_subscriptions.id, "
                . "IFNULL(ciniki_mailing_subscriptions.mailing_id, 0) AS mailing_id, "
                . "IFNULL(ciniki_mailing_subscriptions.id, 0) AS mailing_subscription_id, "
                . "ciniki_subscriptions.name, "
                . "IF(IFNULL(ciniki_mailing_subscriptions.id,0)<1, 'no', 'yes') AS status "
                . "FROM ciniki_subscriptions "
                . "LEFT JOIN ciniki_mailing_subscriptions ON ("
                    . "ciniki_subscriptions.id = ciniki_mailing_subscriptions.subscription_id "
                    . "AND ciniki_mailing_subscriptions.mailing_id = '" . ciniki_core_dbQuote($ciniki, $mailing['id']) . "' "
                    . "AND ciniki_mailing_subscriptions.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
                . "WHERE ciniki_subscriptions.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . "AND ciniki_subscriptions.status = 10 "
                . "";
        }
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
        $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.mail', array(
        array('container'=>'subscriptions', 'fname'=>'id', 
            'fields'=>array('id', 'mailing_subscription_id', 'name', 'status')),
        ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }

        //
        // Update the subscriptions
        //
        if( isset($rc['subscriptions']) ) {
            $subscriptions = $rc['subscriptions'];
            //
            // Check if we need a mailing created
            //
            if( $mailing == NULL ) {
                foreach($subscriptions as $subscription_id => $subscription) {
                    if( isset($ciniki['request']['args']['subscription-' . $subscription_id]) 
                        && $ciniki['request']['args']['subscription-' . $subscription_id] == 'yes' 
                        ) {
                        $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.mail.mailing', array(
                            'type'=>'40',
                            'status'=>'10',
                            'theme'=>'',
                            'object'=>$args['object'],
                            'object_id'=>$args['object_id'],
                            ), 0x04);
                        if( $rc['stat'] != 'ok' ) {
                            return $rc;
                        }
                        $mailing = array('id'=>$rc['id'], 'status'=>10);
                        break;
                    }
                }
            } 
            
            // 
            // If mailing has been sent, no more changes are permitted
            //
            if( $mailing['status'] <= 10 ) {
                //
                // Add/Update/Delete the subscriptions attached to the mailing
                //
                foreach($subscriptions as $subscription_id => $subscription) {
                    //
                    // The subscription is selected for the object and is currently not attached
                    //
                    if( isset($ciniki['request']['args']['subscription-' . $subscription_id]) 
                        && $ciniki['request']['args']['subscription-' . $subscription_id] == 'yes' 
                        && $subscription['status'] == 'no'
                        && $subscription['mailing_subscription_id'] == 0
                        && $mailing['status'] <= 10
                        ) {
                        $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.mail.mailing_subscription', array(
                            'mailing_id'=>$mailing['id'],
                            'subscription_id'=>$subscription_id,
                            'status'=>'10'), 0x04);
                        if( $rc['stat'] != 'ok' ) {
                            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.mail');
                            return $rc;
                        }
                    }
                    //
                    // Update an existing subscription record
                    //
                    elseif( isset($ciniki['request']['args']['subscription-' . $subscription_id]) 
                        && $ciniki['request']['args']['subscription-' . $subscription_id] == 'yes' 
                        && $subscription['mailing_subscription_id'] > 0
                        && $mailing['status'] <= 10
                        ) {
                        $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.mail.mailing_subscription', 
                            $subscription['mailing_subscription_id'], array('status'=>'10'), 0x04);
                        if( $rc['stat'] != 'ok' ) {
                            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.mail');
                            return $rc;
                        }
                    }
                    //
                    // Subscription is to be removed, it must be unsent status, otherwise it can't be removed
                    //
                    elseif( isset($ciniki['request']['args']['subscription-' . $subscription_id]) 
                        && $ciniki['request']['args']['subscription-' . $subscription_id] == 'no' 
                        && $subscription['mailing_subscription_id'] > 0
                        && $mailing['status'] <= 10
                        ) {
                        $rc = ciniki_core_objectDelete($ciniki, $tnid, 'ciniki.mail.mailing_subscription', 
                            $subscription['mailing_subscription_id'], NULL, 0x04);
                        if( $rc['stat'] != 'ok' ) {
                            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.mail');
                            return $rc;
                        }
                    }
                }
            }
        }

        return array('stat'=>'ok');
    }

    return array('stat'=>'ok');
}
?>
