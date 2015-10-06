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
	//
	// FIXME: Remove mail once transitition is done to message
	//
	$maps['mail'] = array(
		'status'=>array(
			'5'=>'Draft',
			'7'=>'Pending Approval',
			'10'=>'Queued',
			'15'=>'Failed, trying again',
			'20'=>'Sending',
			'30'=>'Sent',
			'40'=>'Received',
			'50'=>'Failed',
			'60'=>'Deleted',
			),
		);
	$maps['message'] = array(
		'status'=>array(
			'5'=>'Draft',
			'7'=>'Pending Approval',
			'10'=>'Queued',
			'15'=>'Failed, trying again',
			'20'=>'Sending',
			'30'=>'Sent',
			'40'=>'Received',
			'50'=>'Failed',
			'60'=>'Deleted',
			),
		);
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
