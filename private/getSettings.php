<?php
//
// Description
// -----------
// This function will return the ciniki.mail settings for a business.
//
// Arguments
// ---------
// ciniki:
// business_id:		The ID of the business to get the settings for.
// 
// Returns
// -------
//
function ciniki_mail_getSettings($ciniki, $business_id) {
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDetailsQueryDash');
	return ciniki_core_dbDetailsQueryDash($ciniki, 'ciniki_mail_settings', 'business_id', $business_id, 'ciniki.mail', 'settings', '');
}
