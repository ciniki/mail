<?php
//
// Description
// -----------
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
function ciniki_mail_mailingAddFromObject(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'object'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Object'), 
        'object_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Object ID'), 
        'type'=>array('required'=>'no', 'trimblanks'=>'yes', 'default'=>'10', 'blank'=>'no', 'validlist'=>array('10','20','30'), 'name'=>'Type'),
        'theme'=>array('required'=>'no', 'default'=>'Default', 'trimblanks'=>'yes', 'blank'=>'no', 'name'=>'Theme'),
        'survey_id'=>array('required'=>'no', 'default'=>'0', 'blank'=>'no', 'name'=>'Survey'),
        'subscription_ids'=>array('required'=>'no', 'type'=>'idlist', 'name'=>'Subscriptions'),
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];
    $args['status'] = '10';

    //  
    // Make sure this module is activated, and
    // check permission to run this function for this tenant
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'mail', 'private', 'checkAccess');
    $rc = ciniki_mail_checkAccess($ciniki, $args['tnid'], 'ciniki.mail.mailingAddFromObject', 0); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }

    //
    // Go lookup the object information
    //
    list($pkg, $mod, $obj) = explode('.', $args['object']);
    $rc = ciniki_core_loadMethod($ciniki, $pkg, $mod, 'hooks', 'emailGet');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $fn = $rc['function_call'];
    $rc = $fn($ciniki, $args['tnid'], array(
        'object'=>$args['object'],
        'object_id'=>$args['object_id']));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $email = $rc['email'];
    $args['subject'] = $email['subject'];
    $args['text_content'] = $email['text_content'];
    $args['html_content'] = $email['html_content'];
    
    //
    // Setup defaults for dates
    //
    $args['date_started'] = '';
    $args['date_sent'] = '';

    //  
    // Turn off autocommit
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbInsert');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.mail');
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    $rc = ciniki_core_objectAdd($ciniki, $args['tnid'], 'ciniki.mail.mailing', $args, 0x04); 
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.mail');
        return $rc;
    }
    $mailing_id = $rc['id'];

    //
    // Add the subscriptions
    //
    if( isset($args['subscription_ids']) && is_array($args['subscription_ids']) ) {
        foreach($args['subscription_ids'] as $sid) {
            if( $sid != '' && $sid > 0 ) {
                $rc = ciniki_core_objectAdd($ciniki, $args['tnid'], 
                    'ciniki.mail.mailing_subscription', array(
                        'mailing_id'=>$mailing_id,
                        'subscription_id'=>$sid));
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
    // Update the last_change date in the tenant modules
    // Ignore the result, as we don't want to stop user updates if this fails.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'updateModuleChangeDate');
    ciniki_tenants_updateModuleChangeDate($ciniki, $args['tnid'], 'ciniki', 'mail');

    //
    // Load the mailing
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'mail', 'private', 'mailingLoad');
    $rc = ciniki_mail_mailingLoad($ciniki, $args['tnid'], $mailing_id);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    return array('stat'=>'ok', 'id'=>$mailing_id, 'mailing'=>$rc['mailing']);
}
?>
