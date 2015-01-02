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
		'header_style'=>"body {font-family: Helvetica, sans-serif; font-size: 14px;}\n"
			. "a:link {text-decoration: none;}\n"
			. "a:hover {text-decoration: none;}\n"
			. "a:active {text-decoration: none;}\n"
//			. "@media only screen and (max-device-width:480px; ) { table {width:320px !important;} }\n"
			. "",
		'wrapper_style'=>"padding: 0px; background-color: #FFFFFF;",
		'title_style'=>'padding: 0px; margin-top: 10px; margin-bottom: 0px; font-size: 26px; font-family: Helvetica, sans-serif; line-height: 30px; color: #439BBD;',
		'subtitle_style'=>'font-size: 14px; font-family: Helvetica, sans-serif; line-height: 28px; color: #439BBD;',
		'logo_style'=>'',
		'a'=>'color: #439BBD; text-decoration: none; font-size: 14px;',
		'p'=>'font-size: 14px; font-family: Helvetica, sans-serif; line-height: 18px; color: #000000; text-align: left;',
		'p_footer'=>'margin-top: 3px; margin-bottom: 3px; font-size: 10px; font-family: Helvetica, sans-serif; line-height: 16px; color: #777777; text-align: center;',
		'td_header'=>'padding: 10px; margin-bottom: 5px; border-bottom: 1px solid #555555;',
		'td_body'=>'padding: 10px;',
		'td_footer'=>'margin-top: 20px; padding: 10px; border-top: 1px solid #555555;',
		'a_footer'=>'color: #439BBD; text-decoration: none; font-size: 10px;',
		'h1'=>'text-decoration: bold; font-size: 24px; font-family: Helvetica, sans-serif; color: #439BBD;',
		'h2'=>'text-decoration: bold; font-size: 22px; font-family: Helvetica, sans-serif; color: #439BBD;',
		'h3'=>'text-decoration: bold; font-size: 18px; font-family: Helvetica, sans-serif;',
		'h4'=>'text-decoration: bold; font-size: 16px; font-family: Helvetica, sans-serif;',
		'image_wrap'=>'margin: 0px auto; text-align: center;',
		'image'=>'padding: 9px; border: 2px solid #ccc; display: inline-block; line-height: 10px;',
		'img'=>'display: block; border: 0px; padding: 0px; margin: 0px;',
		'image_caption'=>'',
		'file_description'=>'',
		'image_gallery'=>'margin: 0px auto; text-align: center;',
		'image_gallery_thumbnail'=>'margin: 7px; padding: 7px; border: 1px solid #aaa; display: inline-block;',
		'image_gallery_thumbnail_img'=>'display: block;',
		'linkback'=>'font-size: 16px; text-decoration: none; color: #439BBD;',
		));
}
?>
