<?php
//
// Description
// ===========
//
// Arguments
// =========
// ciniki:
// business_id: 		The ID of the business the theme is for.
// 
// Returns
// =======
// <rsp stat="ok" />
//
function ciniki_mail_themeDefault($ciniki, $business_id) {
	return array('stat'=>'ok', 'theme'=>array(
		'name'=>'Blue Titles on Black',
		'header_style'=>"body {margin: 0px; font-family: Helvetica, sans-serif; font-size: 14px;}\n"
			. "a:link {text-decoration: none;}\n"
			. "a:hover {text-decoration: none;}\n"
			. "a:active {text-decoration: none;}\n"
			. "",
		'wrapper_style'=>"background-color: #FFF;",
		'title_style'=>'font-size: 16px Helvetica, sans-serif; line-height: 20px; color: #439BBD;',
		'subtitle_style'=>'font-size: 14px Helvetica, sans-serif; line-height: 28px; color: #439BBD;',
		'logo_style'=>'',
		'p'=>'font-size: 12px Helvetica, sans-serif; line-height: 16px; color: #969696; text-align: left;',
		'h1'=>'',
		'h2'=>'',
		'h3'=>'',
		'a'=>'',
		));
}
?>
