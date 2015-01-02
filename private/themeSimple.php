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
function ciniki_mail_themeSimple($ciniki, $business_id) {
	return array('stat'=>'ok', 'theme'=>array(
		'name'=>'Simple',
		'header_style'=>"body {margin: 0px; font-family: Helvetica, sans-serif; font-size: 14px;}\n"
			. "a:link {text-decoration: none;}\n"
			. "a:hover {text-decoration: none;}\n"
			. "a:active {text-decoration: none;}\n"
			. "",
		'wrapper_style'=>"background-color: #FFFFFF;",
		'title_style'=>'padding: 0px; margin-top: 10px; margin-bottom: 0px; font-size: 26px; font-family: Helvetica, sans-serif; line-height: 30px; color: #000000; display: none;',
		'title_show'=>'no',
		'subtitle_style'=>'font-size: 14px Helvetica, sans-serif; line-height: 28px; color: #000000;',
		'logo_style'=>'',
		'a'=>'color: #439BBD; text-decoration: none;',
		'p'=>'font-size: 14px; font-family: Helvetica, sans-serif; line-height: 18px; color: #000000; text-align: left;',
		'p_footer'=>'margin-top: 3px; margin-bottom: 3px; font-size: 10px; font-family: Helvetica, sans-serif; line-height: 16px; color: #777777; text-align: center;',
		'td_header'=>'padding: 10px; margin-bottom: 5px;',
		'td_body'=>'padding: 10px;',
		'td_footer'=>'margin-top: 20px; padding: 10px;',
		'h1'=>'text-decoration: bold; font-size: 22px;',
		'h2'=>'text-decoration: bold; font-size: 18px;',
		'h3'=>'text-decoration: bold; font-size: 16px;',
		'h4'=>'text-decoration: bold; font-size: 14px;',
		));
}
?>
