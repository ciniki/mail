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
		'wrapper_style'=>"background-color: #FFFFFF;",
		'title_style'=>'padding: 0px; margin-top: 10px; margin-bottom: 0px; font-size: 26px; font-family: Helvetica, sans-serif; line-height: 30px; color: #439BBD;',
		'subtitle_style'=>'font-size: 14px Helvetica, sans-serif; line-height: 28px; color: #439BBD;',
		'logo_style'=>'',
		'a'=>'color: #439BBD; text-decoration: none;',
		'p'=>'font-size: 14px; font-family: Helvetica, sans-serif; line-height: 18px; color: #000000; text-align: left;',
		'p_footer'=>'margin-top: 3px; margin-bottom: 3px; font-size: 10px; font-family: Helvetica, sans-serif; line-height: 16px; color: #777777; text-align: center;',
		'td_header'=>'padding: 10px; margin-bottom: 5px; border-bottom: 1px solid #555555;',
		'td_body'=>'padding: 10px;',
		'td_footer'=>'margin-top: 20px; padding: 10px; border-top: 1px solid #555555;',
		'h1'=>'text-decoration: bold; font-size: 22px;',
		'h2'=>'text-decoration: bold; font-size: 18px;',
		'h3'=>'text-decoration: bold; font-size: 16px;',
		'h4'=>'text-decoration: bold; font-size: 14px;',
		'image_warp'=>'text-align: center; margin: 0px auto;',
		'image'=>'border: 2px solid #ccc; padding: 9px; display: inline-block;',
		'img'=>'padding: 0px; margin: 0px;',
		'image_caption'=>'',
		'file_description'=>'',
		'image_gallery'=>'margin: 0px auto; text-align: center;',
		'image_gallery_thumbnail'=>'width: 130px; height: 130px; margin: 7px; border: 1px solid #aaa; display: inline-block;',
		));
}
?>
