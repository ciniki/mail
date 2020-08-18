<?php
//
// Description
// -----------
// This function will return the list of available blocks to the ciniki.reporting module.
//
// Arguments
// ---------
// ciniki:
// tnid:     
// args:            The possible arguments for.
//
//
// Returns
// -------
//
function ciniki_mail_reporting_blocks(&$ciniki, $tnid, $args) {

    //
    // Check to make sure the module is enabled
    //
    if( !isset($ciniki['tenant']['modules']['ciniki.mail']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.mail.88', 'msg'=>"I'm sorry, the block you requested does not exist."));
    }

    $blocks = array();

    //
    // Return the list of blocks for the tenant
    //
    $blocks['ciniki.mail.pending'] = array(
        'name'=>'Pending Mail',
        'module' => 'Mail',
        'options'=>array(),
        );

    return array('stat'=>'ok', 'blocks'=>$blocks);
}
?>
