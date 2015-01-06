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
function ciniki_mail_mailingGet($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		'mailing_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Mailing'),
		'images'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Images'),
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
    $rc = ciniki_mail_checkAccess($ciniki, $args['business_id'], 'ciniki.mail.mailingGet', 0); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
	$modules = $rc['modules'];

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
	$date_format = ciniki_users_dateFormat($ciniki);

	//
	// Load and return the mailing
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'mail', 'private', 'mailingLoad');
	$rc = ciniki_mail_mailingLoad($ciniki, $args['business_id'], $args['mailing_id']);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$mailing = $rc['mailing'];

	//
	// Get thumbnails if requested
	//
	if( isset($mailing['images']) && isset($args['images']) && $args['images'] == 'yes' ) {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'images', 'private', 'loadCacheThumbnail');
		foreach($mailing['images'] as $img_id => $img) {
			if( isset($img['image']['image_id']) && $img['image']['image_id'] > 0 ) {
				$rc = ciniki_images_loadCacheThumbnail($ciniki, $args['business_id'], $img['image']['image_id'], 75);
				if( $rc['stat'] != 'ok' ) {
					return $rc;
				}
				$mailing['images'][$img_id]['image']['image_data'] = 'data:image/jpg;base64,' . base64_encode($rc['image']);
			}
		}
	}

	return array('stat'=>'ok', 'mailing'=>$mailing);
}
?>
