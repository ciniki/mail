<?php
//
// Description
// -----------
// The module flags
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_mail_flags($ciniki, $modules) {
	$flags = array(
		array('flag'=>array('bit'=>'1', 'name'=>'Mailings')),
		array('flag'=>array('bit'=>'2', 'name'=>'Alerts')),
		);

	return array('stat'=>'ok', 'flags'=>$flags);
}
?>
