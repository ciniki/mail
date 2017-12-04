<?php
//
// Description
// -----------
// This function will return the ciniki.mail settings for a tenant.
//
// Arguments
// ---------
// ciniki:
// tnid:     The ID of the tenant to get the settings for.
// 
// Returns
// -------
//
function ciniki_mail_getSettings($ciniki, $tnid) {
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDetailsQueryDash');
    return ciniki_core_dbDetailsQueryDash($ciniki, 'ciniki_mail_settings', 'tnid', $tnid, 'ciniki.mail', 'settings', '');
}
