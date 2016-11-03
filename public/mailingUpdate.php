<?php
//
// Description
// -----------
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:         The ID of the business to update the mail mailing for.
//
// Returns
// -------
// <rsp stat='ok' />
//
function ciniki_mail_mailingUpdate(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'type'=>array('required'=>'no', 'trimblanks'=>'yes', 'blank'=>'no', 'validlist'=>array('10','20','30'), 'name'=>'Type'),
        'mailing_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Mailing'),
        'subject'=>array('required'=>'no', 'trimblanks'=>'yes', 'blank'=>'no', 'name'=>'Subject'),
        'primary_image_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Image'),
        'theme'=>array('required'=>'no', 'trimblanks'=>'yes', 'blank'=>'no', 'name'=>'Theme'),
        'survey_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Survey'),
        'html_content'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'HTML Content'),
        'text_content'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Text Content'),
        'subscription_ids'=>array('required'=>'no', 'type'=>'idlist', 'name'=>'Subscriptions'),
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];

    //  
    // Make sure this module is activated, and
    // check permission to run this function for this business
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'mail', 'private', 'checkAccess');
    $rc = ciniki_mail_checkAccess($ciniki, $args['business_id'], 'ciniki.mail.mailingUpdate', 0); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }

    $strsql = "SELECT id, uuid, status "
        . "FROM ciniki_mailings "
        . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "AND id = '" . ciniki_core_dbQuote($ciniki, $args['mailing_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.mail', 'mailing');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $mailing = $rc['mailing'];
    if( $mailing['status'] >= 40 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.mail.45', 'msg'=>'Mailing has already been sent'));
    }

    //  
    // Turn off autocommit
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
//  ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
//  ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbInsert');
//  ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
//  ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDelete');
//  ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.mail');
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // Add all the fields to the change log
    //
    $rc = ciniki_core_objectUpdate($ciniki, $args['business_id'], 'ciniki.mail.mailing', $args['mailing_id'], $args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.mail');
        return $rc;
    }
/*  $strsql = "UPDATE ciniki_mailings SET last_updated = UTC_TIMESTAMP()";

    $changelog_fields = array(
        'type',
        'status',
        'theme',
        'survey_id',
        'subject',
        'html_content',
        'text_content',
        );
    foreach($changelog_fields as $field) {
        if( isset($args[$field]) ) {
            $strsql .= ", $field = '" . ciniki_core_dbQuote($ciniki, $args[$field]) . "' ";
            $rc = ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.mail', 
                'ciniki_mail_history', $args['business_id'], 
                2, 'ciniki_mailings', $args['mailing_id'], $field, $args[$field]);
        }
    }
    $strsql .= "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "AND id = '" . ciniki_core_dbQuote($ciniki, $args['mailing_id']) . "' "
        . "";
    $rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.mail');
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.mail');
        return $rc;
    }
    if( !isset($rc['num_affected_rows']) || $rc['num_affected_rows'] != 1 ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.mail');
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.mail.46', 'msg'=>'Unable to update mailing')); 
    }
    */

    //
    // Check for updated subscriptions
    //
    if( isset($args['subscription_ids']) ) {
        $strsql = "SELECT id, uuid, subscription_id "
            . "FROM ciniki_mailing_subscriptions "
            . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . "AND mailing_id = '" . ciniki_core_dbQuote($ciniki, $args['mailing_id']) . "' "
            . "";
        $rc = ciniki_core_dbHashIDQuery($ciniki, $strsql, 'ciniki.mail', 'subscriptions', 'subscription_id');
        if( $rc['stat'] != 'ok' ) { 
            return $rc;
        }
        $subscription_ids = $rc['subscriptions'];

        //
        // Additions
        //
        foreach($args['subscription_ids'] as $sid) {
            if( $sid > 0 && !isset($subscription_ids[$sid]) ) {
                $rc = ciniki_core_objectAdd($ciniki, $args['business_id'], 'ciniki.mail.mailing_subscription', 
                    array('mailing_id'=>$args['mailing_id'], 'subscription_id'=>$sid), 0x04);
//                  $subscription['id'], NULL, 0x04);
                if( $rc['stat'] != 'ok' ) { 
                    ciniki_core_dbTransactionRollback($ciniki, 'ciniki.mail');
                    return $rc;
                }
/*              //
                // Get a new UUID
                //
                ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUUID');
                $rc = ciniki_core_dbUUID($ciniki, 'ciniki.mail');
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
                }
                $uuid = $rc['uuid'];

                $strsql = "INSERT INTO ciniki_mailing_subscriptions (uuid, business_id, mailing_id, subscription_id, "
                    . "date_added, last_updated) VALUES ("
                    . "'" . ciniki_core_dbQuote($ciniki, $uuid) . "', "
                    . "'" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "', "
                    . "'" . ciniki_core_dbQuote($ciniki, $args['mailing_id']) . "', "
                    . "'" . ciniki_core_dbQuote($ciniki, $sid) . "', "
                    . "UTC_TIMESTAMP(), UTC_TIMESTAMP())";
                $rc = ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.mail');
                if( $rc['stat'] != 'ok' ) { 
                    ciniki_core_dbTransactionRollback($ciniki, 'ciniki.mail');
                    return $rc;
                }
                if( !isset($rc['insert_id']) || $rc['insert_id'] < 1 ) {
                    ciniki_core_dbTransactionRollback($ciniki, 'ciniki.mail');
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.mail.47', 'msg'=>'Unable to update mailing'));
                }
                $ms_id = $rc['insert_id'];

                ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.mail', 'ciniki_mail_history', $args['business_id'], 
                    1, 'ciniki_mailing_subscriptions', $ms_id, 'uuid', $uuid);
                ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.mail', 'ciniki_mail_history', $args['business_id'], 
                    1, 'ciniki_mailing_subscriptions', $ms_id, 'mailing_id', $args['mailing_id']);
                ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.mail', 'ciniki_mail_history', $args['business_id'], 
                    1, 'ciniki_mailing_subscriptions', $ms_id, 'subscription_id', $sid);
                $ciniki['syncqueue'][] = array('push'=>'ciniki.mail.mailingsubscription', 
                    'args'=>array('id'=>$ms_id)); */
            }
        }

        //
        // Deletions
        //
        foreach($subscription_ids as $sid => $subscription) {
            if( $sid > 0 && !in_array($sid, $args['subscription_ids']) ) {
                $rc = ciniki_core_objectDelete($ciniki, $args['business_id'], 'ciniki.mail.mailing_subscription', 
                    $subscription['id'], $subscription['uuid'], 0x04);
                if( $rc['stat'] != 'ok' ) { 
                    ciniki_core_dbTransactionRollback($ciniki, 'ciniki.mail');
                    return $rc;
                }

/*              $strsql = "DELETE FROM ciniki_mailing_subscriptions "
                    . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
                    . "AND id = '" . ciniki_core_dbQuote($ciniki, $subscription['id']) . "' ";
                $rc = ciniki_core_dbDelete($ciniki, $strsql, 'ciniki.mail');
                if( $rc['stat'] != 'ok' ) { 
                    ciniki_core_dbTransactionRollback($ciniki, 'ciniki.mail');
                    return $rc;
                }
                ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.mail', 'ciniki_mail_history', $args['business_id'], 
                    3, 'ciniki_mailing_subscriptions', $subscription['id'], '*', '');
                $ciniki['syncqueue'][] = array('push'=>'ciniki.mail.mailingsubscription', 
                    'args'=>array('delete_id'=>$subscription['id'], 'delete_uuid'=>$subscription['uuid'])); 
*/
            }
        }
    }

    //
    // Commit the database changes
    //
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.mail');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Update the last_change date in the business modules
    // Ignore the result, as we don't want to stop user updates if this fails.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'updateModuleChangeDate');
    ciniki_businesses_updateModuleChangeDate($ciniki, $args['business_id'], 'ciniki', 'mail');

//  $ciniki['syncqueue'][] = array('push'=>'ciniki.mail.mailing', 
//      'args'=>array('id'=>$args['mailing_id']));

    return array('stat'=>'ok');
}
?>
