<?php
//
// Description
// -----------
// This function will return the history for an element in the mail mailing.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant to get the history for.
// mailing_id:          The ID of the mailing to get the history for.
// field:               The field to get the history for.
//
// Returns
// -------
//  <history>
//      <action date="2011/02/03 00:03:00" value="Value field set to" user_id="1" />
//      ...
//  </history>
//  <users>
//      <user id="1" name="users.display_name" />
//      ...
//  </users>
//
function ciniki_mail_mailingHistory($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'mailing_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Mailing'), 
        'field'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Field'), 
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];
    
    //
    // Check access to tnid as owner, or sys admin
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'mail', 'private', 'checkAccess');
    $rc = ciniki_mail_checkAccess($ciniki, $args['tnid'], 'ciniki.mail.mailingHistory', 0);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

//  if( $args['field'] == 'subscription_ids' ) {
//      ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbGetModuleHistoryList');
//      return ciniki_core_dbGetModuleHistoryList($ciniki, 'ciniki.mail', 
//          'ciniki_mail_history', $args['tnid'], 
//          'ciniki_mailing_subscriptions', $args['mailing_id'], 'subscription_id', 'mailing_id', 'ciniki_subscriptions', 'name');
//  }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbGetModuleHistory');
    return ciniki_core_dbGetModuleHistory($ciniki, 'ciniki.mail', 'ciniki_mail_history', 
        $args['tnid'], 'ciniki_mailings', $args['mailing_id'], $args['field']);
}
?>
