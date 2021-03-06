<?php
//
// Description
// -----------
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant to add the mailing image to.
// mailing_image_id:    The ID of the mailing image to get.
//
// Returns
// -------
//
function ciniki_mail_mailingImageGet($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'mailing_image_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Mailing Image'),
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];

    //  
    // Make sure this module is activated, and
    // check permission to run this function for this tenant
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'mail', 'private', 'checkAccess');
    $rc = ciniki_mail_checkAccess($ciniki, $args['tnid'], 'ciniki.mail.mailingImageGet'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki);

    //
    // Get the main information
    //
    $strsql = "SELECT ciniki_mailing_images.id, "
        . "ciniki_mailing_images.name, "
        . "ciniki_mailing_images.permalink, "
        . "ciniki_mailing_images.image_id, "
        . "ciniki_mailing_images.description "
        . "FROM ciniki_mailing_images "
        . "WHERE ciniki_mailing_images.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND ciniki_mailing_images.id = '" . ciniki_core_dbQuote($ciniki, $args['mailing_image_id']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.mail', array(
        array('container'=>'images', 'fname'=>'id', 'name'=>'image',
            'fields'=>array('id', 'name', 'permalink', 'image_id', 'description')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['images']) ) {
        return array('stat'=>'ok', 'err'=>array('code'=>'ciniki.mail.36', 'msg'=>'Unable to find image'));
    }
    $image = $rc['images'][0]['image'];
    
    return array('stat'=>'ok', 'image'=>$image);
}
?>
