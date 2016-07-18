<?php
//
// Description
// -----------
// Merge the mail from secondary_customer_id into primary_customer_id
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_mail_hooks_customerMerge($ciniki, $business_id, $args) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');

    if( !isset($args['primary_customer_id']) || $args['primary_customer_id'] == '' 
        || !isset($args['secondary_customer_id']) || $args['secondary_customer_id'] == '' ) {
        return array('stat'=>'ok');
    }

    //
    // Keep track of how many items we've updated
    //
    $updated = 0;

    //
    // Get the list of message to update
    //
    $strsql = "SELECT id "
        . "FROM ciniki_mail "
        . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
        . "AND customer_id = '" . ciniki_core_dbQuote($ciniki, $args['secondary_customer_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.mail', 'items');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2448', 'msg'=>'Unable to find mail items', 'err'=>$rc['err']));
    }
    $items = $rc['rows'];
    foreach($items as $i => $row) {
        $rc = ciniki_core_objectUpdate($ciniki, $business_id, 'ciniki.mail.message', $row['id'], array('customer_id'=>$args['primary_customer_id']), 0x04);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2449', 'msg'=>'Unable to update mail items.', 'err'=>$rc['err']));
        }
        $updated++;
    }

    if( $updated > 0 ) {
        //
        // Update the last_change date in the business modules
        // Ignore the result, as we don't want to stop user updates if this fails.
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'updateModuleChangeDate');
        ciniki_businesses_updateModuleChangeDate($ciniki, $business_id, 'ciniki', 'mail');
    }

    return array('stat'=>'ok', 'updated'=>$updated);
}
?>
