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
			'survey_id'=>array('ref'=>'ciniki.surveys.survey'),
			'subject'=>array(),
			'html_content'=>array(),
			'text_content'=>array(),
			'date_started'=>array(),
			'date_sent'=>array(),
			),
		'history_table'=>'ciniki_mail_history',
		);
	$objects['mailing_subscription'] = array(
		'name'=>'Mailing',
		'sync'=>'yes',
		'table'=>'ciniki_mailing_subscriptions',
		'fields'=>array(
			'mailing_id'=>array('ref'=>'ciniki.mail.mailing'),
			'subscription_id'=>array('ref'=>'ciniki.subscriptions.subscription'),
			),
		'history_table'=>'ciniki_mail_history',
		);

	
	return array('stat'=>'ok', 'objects'=>$objects);
}
?>
