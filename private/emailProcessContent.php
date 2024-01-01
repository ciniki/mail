<?php
//
// Description
// -----------
// This method will create all the emails for a mailing, and start sending.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant to mail mailing belongs to.
// mailing_id:          The ID of the mailing to get.
//
// Returns
// -------
//
function ciniki_mail_emailProcessContent($ciniki, $tnid, $theme, $unprocessed_content) {

    if( $unprocessed_content == '' ) { 
        return array('stat'=>'ok', 'content'=>'');
    }

    $processed_content = $unprocessed_content;

    $p_style = isset($theme['p'])?$theme['p']:'';
    $h1_style = isset($theme['h1'])?$theme['h1']:'';
    $h2_style = isset($theme['h2'])?$theme['h2']:'';
    $h3_style = isset($theme['h3'])?$theme['h3']:'';
    $h4_style = isset($theme['h4'])?$theme['h4']:'';
    $h5_style = isset($theme['h5'])?$theme['h5']:'';
    $h6_style = isset($theme['h6'])?$theme['h6']:'';
    $table_style = isset($theme['table'])?$theme['table']:'';
    $td_style = isset($theme['td'])?$theme['td']:'';
    $th_style = isset($theme['th'])?$theme['th']:'';


    //
    // Similar code to web/private/processContent
    //
    $pattern = '#\b(((?<!(=(\"|\')|.>))https?://?|(?<!(//|.>))www[.])[^\s()<>]+(?:\([\w\d]+\)|([^[:punct:]\s]|/)))#';
/*    $callback = create_function('$matches', '
        $display_url = $matches[1];
        $url = $display_url;
        if( isset($matches[2]) && ($matches[2] == "http://" || $matches[2] == "https://") ) {
            $display_url = substr($display_url, strlen($matches[2]));
            $display_url = preg_replace("/\\\\/$/", "", $display_url);
        } elseif( isset($matches[2]) && $matches[2] == "www." )  {
            $url = "http://" . $display_url;
        }
//      $url = preg_replace("/www/", "http://www", $display_url);
        return sprintf(\'<a onclick="event.stopPropagation();" href="%s" target="_blank">%s</a>\', $url, $display_url);
    '); */
//  $processed_content = preg_replace_callback($pattern1, $callback, $processed_content);
//    $processed_content = preg_replace_callback($pattern, $callback, $processed_content);
    $processed_content = preg_replace_callback($pattern, function($match) {
        $display_url = $match[1];
        $url = $display_url;
        if( isset($match[2]) && ($match[2] == "http://" || $match[2] == "https://") ) {
            $display_url = substr($display_url, strlen($match[2]));
            $display_url = preg_replace("/\\\\/$/", "", $display_url);
        } elseif( isset($match[2]) && $match[2] == "www." )  {
            $url = "http://" . $display_url;
        }
//      $url = preg_replace("/www/", "http://www", $display_url);
        return sprintf("<a onclick=\"event.stopPropagation();\" href=\"%s\" target=\"_blank\">%s</a>", $url, $display_url);
        }, $processed_content);

    $processed_content = preg_replace('/((?<!mailto:|=|[a-zA-Z0-9._%+-])([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,64})(?![a-zA-Z]|<\/[aA]>))/', '<a href="mailto:$1">$1</a>', $processed_content);

    // Do the simple processing
    $processed_content = "<p style='$p_style'>" . preg_replace('/\n\s*\n/m', "</p><p style='$p_style'>", $processed_content) . '</p>';
    // Remove empty paragraphs that are followed by a <h tag
    $processed_content = preg_replace('/<p class=\'[A-Za-z\- ]*\'>(<h[1-6][^\>]*>[^<]+<\/h[1-6]>)<\/p>/', '$1', $processed_content);
    $processed_content = preg_replace('/([^>])\n/m', "$1<br/>", $processed_content);
    $processed_content = preg_replace('/<p>/', "<p style='$p_style'>", $processed_content);
    $processed_content = preg_replace('/<h1/', "<h1 style='$h1_style'", $processed_content);
    $processed_content = preg_replace('/<h2/', "<h2 style='$h2_style'", $processed_content);
    $processed_content = preg_replace('/<h3/', "<h3 style='$h3_style'", $processed_content);
    $processed_content = preg_replace('/<h4/', "<h4 style='$h4_style'", $processed_content);
    $processed_content = preg_replace('/<table/', "<table style='$table_style'", $processed_content);
    $processed_content = preg_replace("/<table style='([^>]+)' style=('|\")/", "<table style=$2$1", $processed_content);
    $processed_content = preg_replace('/<td(>| )/', "<td style='$td_style'$1", $processed_content);
    // Check for duplicate styles and combine
    $processed_content = preg_replace('/<td style=\'([^\']+)\'\s+style=\'/', "<td style='$1", $processed_content);
    $processed_content = preg_replace('/<th(>| )/', "<th style='$th_style'$1", $processed_content);
    $processed_content = preg_replace('/<th style=\'([^\']+)\'\s+style=\'/', "<th style='$1", $processed_content);

    return array('stat'=>'ok', 'content'=>$processed_content);
}
?>
