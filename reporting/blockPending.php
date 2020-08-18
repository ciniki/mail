<?php
//
// Description
// -----------
// Return the list of messages pending to be sent.
//
// Arguments
// ---------
// ciniki:
// tnid:         The ID of the tenant to get the birthdays for.
// args:                The options for the query.
//
// Additional Arguments
// --------------------
// 
// Returns
// -------
//
function ciniki_mail_reporting_blockPending(&$ciniki, $tnid, $args) {
    //
    // Get the tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuoteIDs');

    $date_format = "M j, Y";
    $datetime_format = "M j, Y g:i A";

    //
    // Load maps
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'mail', 'private', 'maps');
    $rc = ciniki_mail_maps($ciniki);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $maps = $rc['maps'];

    $strsql = "SELECT mail.id, "
        . "mail.customer_id, "
        . "mail.customer_name, "
        . "mail.customer_email, "
        . "mail.subject "
        . "FROM ciniki_mail AS mail "
        . "WHERE mail.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND mail.status = 7 "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.mail', array(
        array('container'=>'mail', 'fname'=>'id', 
            'fields'=>array('id', 'customer_id', 'customer_name', 'customer_email', 'subject'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $mail = isset($rc['mail']) ? $rc['mail'] : array();

    $chunks = array();
    if( count($mail) > 0 ) {
        //
        // Create the report blocks
        //
        $chunk = array(
            'type'=>'table',
            'columns'=>array(
                array('label'=>'Customer', 'pdfwidth'=>'40%', 'field'=>'customer_name'),
                array('label'=>'Subject', 'pdfwidth'=>'60%', 'field'=>'subject'),
                ),
            'data'=>$mail,
//            'editApp'=>array('app'=>'ciniki.mail.main', 'args'=>array('mail_id'=>'d.id')),
            'textlist'=>'',
            );
        foreach($mail as $mid => $message) {
            //
            // Add emails to customer
            //
            $chunk['textlist'] .= sprintf("%50s %80s\n", $message['customer_name'], $message['subject']);
        }
        $chunks[] = $chunk;
    }
//  Leave section blank if no pending email
//    else {
//        $chunks[] = array('type'=>'message', 'content'=>'No pending mail.');
//    }
    
    return array('stat'=>'ok', 'chunks'=>$chunks);
}
?>
