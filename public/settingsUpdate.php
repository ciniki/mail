<?php
//
// Description
// -----------
// This method will update one or more settings for the mail module.
//
// Info
// ----
// Status: 			defined
//
// Arguments
// ---------
// user_id: 		The user making the request
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_mail_settingsUpdate(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'sendtest'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Send Test'), 
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
    $rc = ciniki_mail_checkAccess($ciniki, $args['business_id'], 'ciniki.mail.settingsUpdate'); 
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
	// The list of allowed fields for updating
	//
	$changelog_fields = array(
		'smtp-servers',
		'smtp-username',
		'smtp-password',
		'smtp-secure',
		'smtp-port',
		'smtp-from-name',
		'smtp-from-address',
		'mail-default-theme',
		'message-disclaimer',
		'smtp-5min-limit',
        'message-style-header_style',
        'message-style-wrapper_style',
        'message-style-title_style',
        'message-style-subtitle_style',
        'message-style-logo_style',
        'message-style-a',
        'message-style-p',
        'message-style-p_footer',
        'message-style-td_footer',
        'message-style-a_footer',
        'message-style-td_header',
        'message-style-td_body',
        'message-style-h1',
        'message-style-h2',
        'message-style-h3',
        'message-style-h4',
        'message-style-image_wrap',
        'message-style-image',
        'message-style-img',
        'message-style-image_caption',
        'message-style-file_description',
        'message-style-image_gallery',
        'message-style-image_gallery_thumbnail',
        'message-style-image_gallery_thumbnail_img',
        'message-style-linkback',
        'message-style-table',
        'message-style-td',
        'message-style-th',
		);
	//
	// Check each valid setting and see if a new value was passed in the arguments for it.
	// Insert or update the entry in the ciniki_mail_settings table
	//
	foreach($changelog_fields as $field) {
		if( isset($ciniki['request']['args'][$field]) ) {
			$strsql = "INSERT INTO ciniki_mail_settings (business_id, detail_key, detail_value, date_added, last_updated) "
				. "VALUES ('" . ciniki_core_dbQuote($ciniki, $ciniki['request']['args']['business_id']) . "'"
				. ", '" . ciniki_core_dbQuote($ciniki, $field) . "'"
				. ", '" . ciniki_core_dbQuote($ciniki, $ciniki['request']['args'][$field]) . "'"
				. ", UTC_TIMESTAMP(), UTC_TIMESTAMP()) "
				. "ON DUPLICATE KEY UPDATE detail_value = '" . ciniki_core_dbQuote($ciniki, $ciniki['request']['args'][$field]) . "' "
				. ", last_updated = UTC_TIMESTAMP() "
				. "";
			$rc = ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.mail');
			if( $rc['stat'] != 'ok' ) {
				ciniki_core_dbTransactionRollback($ciniki, 'ciniki.mail');
				return $rc;
			}
			ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.mail', 'ciniki_mail_history', $args['business_id'], 
				2, 'ciniki_mail_settings', $field, 'detail_value', $ciniki['request']['args'][$field]);
			$ciniki['syncqueue'][] = array('push'=>'ciniki.mail.setting', 
				'args'=>array('id'=>$field));
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
	// Check if a test message should be sent
	//
	if( isset($args['sendtest']) && $args['sendtest'] == 'yes' ) {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'hooks', 'emailUser');
		$rc = ciniki_users_hooks_emailUser($ciniki, $args['business_id'], array(
			'user_id'=>$ciniki['session']['user']['id'],
			'subject'=>'Test of your mail settings',
			'textmsg'=>'You mail settings are configured properly.'));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
	}

	//
	// Update the last_change date in the business modules
	// Ignore the result, as we don't want to stop user updates if this fails.
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'updateModuleChangeDate');
	ciniki_businesses_updateModuleChangeDate($ciniki, $args['business_id'], 'ciniki', 'mail');

	return array('stat'=>'ok');
}
?>
