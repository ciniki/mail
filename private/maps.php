<?php
//
// Description
// -----------
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_mail_maps($ciniki) {

	$maps = array();
	$maps['mailing'] = array(
		'type'=>array(
			'10'=>'General',
			'20'=>'Newsletter',
			'30'=>'Alert',
			'40'=>'Object Mailing',
			),
		'status'=>array(
			'10'=>'Unsent',
			'20'=>'Approved',
			'30'=>'Queuing',
			'40'=>'Sending',
			'50'=>'Sent',
			),
		);
	
	return array('stat'=>'ok', 'maps'=>$maps);
}
?>