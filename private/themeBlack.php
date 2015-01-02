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
function ciniki_mail_themeBlack($ciniki, $business_id) {
	return array('stat'=>'ok', 'theme'=>array(
		'name'=>'Blue Titles on Black',
		'header_style'=>"body {margin: 0px; font-family: Helvetica, sans-serif; font-size: 14px;}\n"
			. "",
		'wrapper_style'=>"background-color: #000000;",
		'title_style'=>'padding: 0px; margin-top: 10px; margin-bottom: 0px; font-size: 26px; font-family: Helvetica, sans-serif; line-height: 30px; color: #63BBDD;',
		'subtitle_style'=>'font-size: 14px; font-family: Helvetica, sans-serif; line-height: 28px; color: #63BBDD;',
		'logo_style'=>'',
		'a'=>'color: #439BBD; text-decoration: none;',
		'p'=>'font-size: 14px; font-family: Helvetica, sans-serif; line-height: 18px; color: #aaaaaa; text-align: left;',
		'p_footer'=>'margin-top: 3px; margin-bottom: 3px; font-size: 10px; font-family: Helvetica, sans-serif; line-height: 16px; color: #777777; text-align: center;',
		'td_header'=>'padding: 10px; margin-bottom: 5px; border-bottom: 1px solid #555555;',
		'td_body'=>'padding: 10px;',
		'td_footer'=>'margin-top: 20px; padding: 10px; border-top: 1px solid #555555;',
		'h1'=>'text-decoration: bold; font-size: 22px;',
		'h2'=>'text-decoration: bold; font-size: 18px;',
		'h3'=>'text-decoration: bold; font-size: 16px;',
		'h4'=>'text-decoration: bold; font-size: 14px;',
		));
}
?>
