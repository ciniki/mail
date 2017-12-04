<?php
//
// Description
// -----------
//
// Arguments
// ---------
// ciniki:
// tnid:     The ID of the tenant to the mail belongs to.
// mail_id:         The ID of the mail message to send.
// 
// Returns
// -------
//
function ciniki_mail_logMsg($ciniki, $tnid, $args) {

    //
    // Log date on the server
    //
    $dt = new DateTime('now', new DateTimeZone('UTC'));
    $args['log_date'] = $dt->format('Y-m-d H:i:s');

    //
    // Setup error response. This allows the calling function to return the output of logMsg instead
    // of building a second array to return error code.
    //
    $rsp = array('stat'=>'ok', 'err'=>array());
    $rsp['code'] = $args['code'];
    $rsp['msg'] = $args['msg'];
    if( isset($args['pmsg']) ) {
        $rsp['pmsg'] = $args['pmsg'];
    }

    //
    // Serialize error array
    //
    if( isset($args['err']) ) {
        $args['errors'] = serialize($args['err']);
        $rsp['err'] = $args['err'];
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.mail.log', $args, 0x07);
    if( $rc['stat'] != 'ok' ) {
        error_log("MAIL-ERR[$tnid]: Unable to add log message (" . print_r($args, true) . ")");
    }

    return $rsp;
}
