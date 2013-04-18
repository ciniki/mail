<?php
//
// Description
// -----------
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:			The ID of the business to add the mail mailing to.
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
		'theme'=>array('required'=>'no', 'default'=>'Default', 'trimblanks'=>'yes', 'blank'=>'no', 'name'=>'Theme'),
		'survey_id'=>array('required'=>'no', 'default'=>'0', 'blank'=>'no', 'name'=>'Survey'),
		'html_content'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'HTML Content'),
		'text_content'=>array('required'=>'yes', 'blank'=>'yes', 'name'=>'Text Content'),
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
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbInsert');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
	$rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.mail');
	if( $rc['stat'] != 'ok' ) { 
		return $rc;
	}   

	//
	// Get a new UUID
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUUID');
	$rc = ciniki_core_dbUUID($ciniki, 'ciniki.mail');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$args['uuid'] = $rc['uuid'];

	//
	// Add the mailing to the database
	//
	$strsql = "INSERT INTO ciniki_mailings (uuid, business_id, "
		. "type, status, theme, survey_id, subject, html_content, text_content, "
		. "date_added, last_updated) VALUES ("
		. "'" . ciniki_core_dbQuote($ciniki, $args['uuid']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['type']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['status']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['theme']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['survey_id']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['subject']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['html_content']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['text_content']) . "', "
		. "UTC_TIMESTAMP(), UTC_TIMESTAMP())";
	$rc = ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.mail');
	if( $rc['stat'] != 'ok' ) { 
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.mail');
		return $rc;
	}
	if( !isset($rc['insert_id']) || $rc['insert_id'] < 1 ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.mail');
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1029', 'msg'=>'Unable to add mailing'));
	}
	$mailing_id = $rc['insert_id'];

	//
	// Add all the fields to the change log
	//
	$changelog_fields = array(
		'uuid',
		'type',
		'status',
		'theme',
		'survey_id',
		'subject',
		'html_content',
		'text_content',
		);
	foreach($changelog_fields as $field) {
		if( isset($args[$field]) && $args[$field] != '' ) {
			$rc = ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.mail', 
				'ciniki_mail_history', $args['business_id'], 
				1, 'ciniki_mailings', $mailing_id, $field, $args[$field]);
		}
	}

	$ciniki['syncqueue'][] = array('push'=>'ciniki.mail.mailing', 
		'args'=>array('id'=>$mailing_id));

	//
	// Add the subscriptions
	//
	if( isset($args['subscription_ids']) && is_array($args['subscription_ids']) ) {
		foreach($args['subscription_ids'] as $sid) {
			if( $sid != '' && $sid > 0 ) {
				//
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
					. "'" . ciniki_core_dbQuote($ciniki, $mailing_id) . "', "
					. "'" . ciniki_core_dbQuote($ciniki, $sid) . "', "
					. "UTC_TIMESTAMP(), UTC_TIMESTAMP())";
				$rc = ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.mail');
				if( $rc['stat'] != 'ok' ) { 
					ciniki_core_dbTransactionRollback($ciniki, 'ciniki.mail');
					return $rc;
				}
				if( !isset($rc['insert_id']) || $rc['insert_id'] < 1 ) {
					ciniki_core_dbTransactionRollback($ciniki, 'ciniki.mail');
					return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1034', 'msg'=>'Unable to add mailing'));
				}
				$ms_id = $rc['insert_id'];

				ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.mail', 'ciniki_mail_history', $args['business_id'], 
					1, 'ciniki_mailing_subscriptions', $ms_id, 'uuid', $uuid);
				ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.mail', 'ciniki_mail_history', $args['business_id'], 
					1, 'ciniki_mailing_subscriptions', $ms_id, 'mailing_id', $mailing_id);
				ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.mail', 'ciniki_mail_history', $args['business_id'], 
					1, 'ciniki_mailing_subscriptions', $ms_id, 'subscription_id', $sid);
				$ciniki['syncqueue'][] = array('push'=>'ciniki.mail.mailingsubscription', 
					'args'=>array('id'=>$ms_id));
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
