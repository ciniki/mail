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
function ciniki_mail_objects($ciniki) {
	
	$objects = array();
	$objects['mailing'] = array(
		'name'=>'Mailing',
		'sync'=>'yes',
		'table'=>'ciniki_mailings',
		'fields'=>array(
			'type'=>array(),
			'status'=>array(),
			'theme'=>array(),
			'survey_id'=>array('ref'=>'ciniki.surveys.survey', 'default'=>0),
			'object'=>array('default'=>''),
			'object_id'=>array('default'=>''),
			'subject'=>array('default'=>''),
			'primary_image_id'=>array('ref'=>'ciniki.images.image', 'default'=>'0'),
			'html_content'=>array('default'=>''),
			'text_content'=>array('default'=>''),
			'date_started'=>array('default'=>''),
			'date_sent'=>array('default'=>''),
			),
		'history_table'=>'ciniki_mail_history',
		);
	$objects['mailing_subscription'] = array(
		'name'=>'Mailing Subscription',
		'sync'=>'yes',
		'table'=>'ciniki_mailing_subscriptions',
		'fields'=>array(
			'mailing_id'=>array('ref'=>'ciniki.mail.mailing'),
			'subscription_id'=>array('ref'=>'ciniki.subscriptions.subscription'),
			),
		'history_table'=>'ciniki_mail_history',
		);
	$objects['mailing_image'] = array(
		'name'=>'Mailing Image',
		'sync'=>'yes',
		'table'=>'ciniki_mailing_images',
		'fields'=>array(
			'mailing_id'=>array('ref'=>'ciniki.mail.mailing'),
			'name'=>array(),
			'permalink'=>array(),
			'image_id'=>array('ref'=>'ciniki.images.image'),
			'description'=>array(),
			),
		'history_table'=>'ciniki_mail_history',
		);

	
	return array('stat'=>'ok', 'objects'=>$objects);
}
?>
