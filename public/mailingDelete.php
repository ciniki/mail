<?php
//
// Description
// -----------
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:			The ID of the business to mail mailing belongs to.
// mailing_id:			The ID of the mailing to get.
//
// Returns
// -------
//
function ciniki_mail_mailingDelete($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		'mailing_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Mailing'),
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
    $rc = ciniki_mail_checkAccess($ciniki, $args['business_id'], 'ciniki.mail.mailingDelete', 0); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
	$modules = $rc['modules'];

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
	$date_format = ciniki_users_dateFormat($ciniki);

	//
	// Get the uuid of the mailing to be deleted
	//
	$strsql = "SELECT uuid FROM ciniki_mailings "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND id = '" . ciniki_core_dbQuote($ciniki, $args['mailing_id']) . "' "
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.mail', 'mailing');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['mailing']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2145', 'msg'=>'The mailing does not exist'));
	}
	$mailing_uuid = $rc['mailing']['uuid'];

	//  
	// Turn off autocommit
	//  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');
	$rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.mail');
	if( $rc['stat'] != 'ok' ) { 
		return $rc;
	}   

	//
	// Remove the mail messages
	//
	$strsql = "SELECT id, uuid FROM ciniki_mail "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND mailing_id = '" . ciniki_core_dbQuote($ciniki, $args['mailing_id']) . "' "
		. "";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.mail', 'image');
	if( $rc['stat'] != 'ok' ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.mail');
		return $rc;
	}
	if( isset($rc['rows']) && count($rc['rows']) > 0 ) {
		$items = $rc['rows'];
		
		foreach($items as $iid => $item) {
			$rc = ciniki_core_objectDelete($ciniki, $args['business_id'], 'ciniki.mail.mail', 
				$item['id'], $item['uuid'], 0x04);
			if( $rc['stat'] != 'ok' ) {
				ciniki_core_dbTransactionRollback($ciniki, 'ciniki.mail');
				return $rc;	
			}
		}
	}

	//
	// Remove the images
	//
	$strsql = "SELECT id, uuid, image_id FROM ciniki_mailing_images "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND mailing_id = '" . ciniki_core_dbQuote($ciniki, $args['mailing_id']) . "' "
		. "";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.mail', 'image');
	if( $rc['stat'] != 'ok' ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.mail');
		return $rc;
	}
	if( isset($rc['rows']) && count($rc['rows']) > 0 ) {
		$images = $rc['rows'];
		
		foreach($images as $iid => $image) {
			$rc = ciniki_core_objectDelete($ciniki, $args['business_id'], 'ciniki.mail.mailing_image', 
				$image['id'], $image['uuid'], 0x04);
			if( $rc['stat'] != 'ok' ) {
				ciniki_core_dbTransactionRollback($ciniki, 'ciniki.mail');
				return $rc;	
			}
		}
	}

	//
	// Remove the files for the mailing
	//
/*	$strsql = "SELECT id, uuid "
		. "FROM ciniki_mailing_files "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND mailing_id = '" . ciniki_core_dbQuote($ciniki, $args['mailing_id']) . "' "
		. "";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.mail', 'file');
	if( $rc['stat'] != 'ok' ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.mail');
		return $rc;
	}
	if( isset($rc['rows']) && count($rc['rows']) > 0 ) {
		$files = $rc['rows'];
		foreach($files as $fid => $file) {
			$rc = ciniki_core_objectDelete($ciniki, $args['business_id'], 'ciniki.mail.mailing_file', 
				$file['id'], $file['uuid'], 0x04);
			if( $rc['stat'] != 'ok' ) {
				ciniki_core_dbTransactionRollback($ciniki, 'ciniki.mail');
				return $rc;	
			}
		}
	} */

	//
	// Delete the mailing
	//
	$rc = ciniki_core_objectDelete($ciniki, $args['business_id'], 'ciniki.mail.mailing', $args['mailing_id'], $mailing_uuid, 0x04);
	if( $rc['stat'] != 'ok' ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.mail');
		return $rc;
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

	return array('stat'=>'ok');
}
?>
