<?php
//
// Description
// ===========
//
// Arguments
// =========
// ciniki:
// tnid:         The ID of the tenant the theme is for.
// 
// Returns
// =======
// <rsp stat="ok" />
//
function ciniki_mail_themeTinymce($ciniki, $tnid) {
    return array('stat'=>'ok', 'theme'=>array(
        'name'=>'Tiny MCE',
        'title_show' => 'no',
//        'header_style'=>"body {font-family: Helvetica, sans-serif; font-size: 14px;}\n"
//            . "a:link {text-decoration: none;}\n"
//            . "a:hover {text-decoration: none;}\n"
//            . "a:active {text-decoration: none;}\n"
//            . "",
        'header_style' => '',
        'wrapper_style'=>"font-size: 12pt; font-family: arial, helvetica, sans-serif;",
        'title_style'=>'',
        'subtitle_style'=>'',
        'logo_style'=>'',
        'a'=>'',
        'p'=>'',
        'p_footer'=>'margin-top: 3px; margin-bottom: 3px; font-size: 10px; font-family: Helvetica, sans-serif; line-height: 16px; color: #777777; text-align: center;',
        'td_header'=>'',
        'td_body'=>'',
        'td_footer'=>'',
        'a_footer'=>'',
        'h1'=>'',
        'h2'=>'',
        'h3'=>'',
        'h4'=>'',
        'image_wrap'=>'',
        'image'=>'',
        'img'=>'',
        'image_caption'=>'',
        'file_description'=>'',
        'image_gallery'=>'',
        'image_gallery_thumbnail'=>'',
        'image_gallery_thumbnail_img'=>'',
        'linkback'=>'',
        'table'=>'',
        'td'=>'',
        'th'=>'',
        ));
}
?>
