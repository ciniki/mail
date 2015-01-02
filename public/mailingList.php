<?php
//
// Description
// -----------
// This method returns the list of mail mailings for a business.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:		The ID of the business to get mail mailings for.
//
// Returns
// -------
//
function ciniki_mail_mailingList($ciniki) {
	//
	// Find all the required and optional arguments
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
	$rc = ciniki_core_prepareArgs($ciniki, 'no', array(
		'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		'limit'=>array('required'=>'no', 'blank'=>'no', 'default'=>'25', 'name'=>'Limit'),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$args = $rc['args'];
	
    //  
    // Check access to business_id as owner, or sys admin. 
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'mail', 'private', 'checkAccess');
    $ac = ciniki_mail_checkAccess($ciniki, $args['business_id'], 'ciniki.mail.mailingList');
    if( $ac['stat'] != 'ok' ) { 
        return $ac;
    }   

	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
	$date_format = ciniki_users_dateFormat($ciniki);

	//
	// Load the status maps for the text description of each status
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'mail', 'private', 'maps');
	$rc = ciniki_mail_maps($ciniki);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$maps = $rc['maps'];

	//
	// Get the list of mailings
	//
	$strsql = "SELECT ciniki_mailings.id, "
		. "ciniki_mailings.status, "
		. "ciniki_mailings.status AS status_text, "
		. "ciniki_mailings.type, "
		. "ciniki_mailings.type AS type_text, "
		. "ciniki_mailings.subject "
		. "FROM ciniki_mailings "
		. "WHERE ciniki_mailings.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND ciniki_mailings.status < 40 "
		. "ORDER BY date_added DESC ";
	if( isset($args['limit']) && is_numeric($args['limit']) && $args['limit'] > 0 ) {
		$strsql .= "LIMIT " . ciniki_core_dbQuote($ciniki, $args['limit']) . " ";
	} else {
		$strsql .= "LIMIT 25 ";
	}

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.mail', array(
		array('container'=>'mailings', 'fname'=>'id', 'name'=>'mailing',
			'fields'=>array('id', 'status', 'status_text', 'subject'),
			'maps'=>array('status_text'=>$maps['mailing']['status']), 
				'type_text'=>$maps['mailing']['type']),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( isset($rc['mailings']) ) {
		return array('stat'=>'ok', 'mailings'=>$rc['mailings']);
	} 

	return array('stat'=>'ok', 'mailings'=>array());
}
?>
