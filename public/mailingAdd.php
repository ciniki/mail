<?php
//
// Description
// -----------
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:         The ID of the business to add the mail mailing to.
//
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_mail_mailingAdd(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'type'=>array('required'=>'no', 'trimblanks'=>'yes', 'default'=>'10', 'blank'=>'no', 'validlist'=>array('10','20','30'), 'name'=>'Type'),
        'subject'=>array('required'=>'yes', 'trimblanks'=>'yes', 'blank'=>'no', 'name'=>'Subject'),
        'primary_image_id'=>array('required'=>'no', 'default'=>'0', 'blank'=>'no', 'name'=>'Image'),
        'theme'=>array('required'=>'no', 'default'=>'Default', 'trimblanks'=>'yes', 'blank'=>'no', 'name'=>'Theme'),
        'survey_id'=>array('required'=>'no', 'default'=>'0', 'blank'=>'no', 'name'=>'Survey'),
        'html_content'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'HTML Content'),
        'text_content'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Text Content'),
        'subscription_ids'=>array('required'=>'no', 'type'=>'idlist', 'name'=>'Subscriptions'),
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];
    $args['status'] = '10';

    //  
    // Make sure this module is activated, and
    // check permission to run this function for this business
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'mail', 'private', 'checkAccess');
    $rc = ciniki_mail_checkAccess($ciniki, $args['business_id'], 'ciniki.mail.mailingAdd', 0); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }

    //  
    // Turn off autocommit
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
//  ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbInsert');
//  ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.mail');
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    $rc = ciniki_core_objectAdd($ciniki, $args['business_id'], 'ciniki.mail.mailing', $args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $mailing_id = $rc['id'];

    //
    // Add the subscriptions
    //
    if( isset($args['subscription_ids']) && is_array($args['subscription_ids']) ) {
        foreach($args['subscription_ids'] as $sid) {
            if( $sid != '' && $sid > 0 ) {
                $rc = ciniki_core_objectAdd($ciniki, $args['business_id'], 'ciniki.mail.mailing_subscription', 
                    array('mailing_id'=>$mailing_id, 'subscription_id'=>$sid), 0x04);
                if( $rc['stat'] != 'ok' ) { 
                    ciniki_core_dbTransactionRollback($ciniki, 'ciniki.mail');
                    return $rc;
                }
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

    return array('stat'=>'ok', 'id'=>$mailing_id);
}
?>
