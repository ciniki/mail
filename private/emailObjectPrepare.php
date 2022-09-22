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
function ciniki_mail_emailObjectPrepare($ciniki, $tnid, $theme, $mailing, $object) {
    //
    // Get the tenant uuid
    //
    $strsql = "SELECT uuid, sitename "
        . "FROM ciniki_tenants "
        . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.tenants', 'tenant');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['tenant']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.mail.22', 'msg'=>'Tenant not found'));
    }
    $tenant = $rc['tenant'];

    //
    // Get the primary domain for the tenant
    //
    $strsql = "SELECT domain "
        . "FROM ciniki_tenant_domains "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND status = 1 "
        . "ORDER BY flags DESC "
        . "LIMIT 1 "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.tenants', 'domain');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['domain']) ) {
        $domain_base_url = 'http://' . $rc['domain']['domain'];
        $cache_url = 'http://' . $rc['domain']['domain'];
    } else {
        //
        // No domain, lookup as subdomain
        //
        $master_domain = $ciniki['config']['ciniki.web']['master.domain'];
        $domain_base_url = 'http://' . $master_domain . '/' . $tenant['sitename'];
        $cache_url = 'http://' . $master_domain;
    }

    //
    // Setup where we can store included images and downloadable files for the email message and
    // they will be accesible through the website
    //
    $cache_url .= '/ciniki-mail-cache'
        . '/' . $tenant['uuid'][0] . '/' . $tenant['uuid']
        . '/' . $mailing['uuid'][0] . '/' . $mailing['uuid'];
    $cache_dir = $ciniki['config']['ciniki.core']['modules_dir'] . '/mail/cache' 
        . '/' . $tenant['uuid'][0] . '/' . $tenant['uuid']
        . '/' . $mailing['uuid'][0] . '/' . $mailing['uuid'];

    //
    // Setup the subject, text and html content
    //
    $subject = $object['subject'];
    $text_content = '';
    $html_content = '';

    //
    // Prepare the content for the email
    //
    $a_style = isset($theme['a'])?$theme['a']:'';
    $h2_style = isset($theme['h2'])?$theme['h2']:'';
    $image_wrap_style = isset($theme['image_wrap'])?$theme['image_wrap']:'';
    $image_style = isset($theme['image'])?$theme['image']:'';
    $img_style = isset($theme['img'])?$theme['img']:'';
    $image_caption_style = isset($theme['image_caption'])?$theme['image_caption']:'';
    $file_description_style = isset($theme['file_description'])?$theme['file_description']:'';
    $image_gallery_style = isset($theme['image_gallery'])?$theme['image_gallery']:'';
    $image_gallery_thumbnail_style = isset($theme['image_gallery_thumbnail'])?$theme['image_gallery_thumbnail']:'';
    $image_gallery_thumbnail_img_style = isset($theme['image_gallery_thumbnail_img'])?$theme['image_gallery_thumbnail_img']:'';
    $linkback_style = isset($theme['linkback'])?$theme['linkback']:'';

    //
    // Check for a primary image
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'mail', 'private', 'getScaledImageURL');
    if( isset($object['image_id']) && $object['image_id'] > 0 ) {
        $rc = ciniki_mail_getScaledImageURL($ciniki, $tnid, $cache_dir, $object['image_id'], 'original', '400', '0');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $html_content .= "<div style='$image_wrap_style'>"
            . "<div style='$image_style'><img class='primary' style='$img_style' title='' src='$cache_url/" . $rc['filename'] . "' /></div>";
        if( isset($page['image_caption']) && $page['image_caption'] != '' ) {
            $html_content .= "<div style='$image_caption_style'>" . $page['image_caption'] . "</div>";
        }
        $html_content .= "</div>";
    }

    //
    // Format the content
    //
    if( isset($object['content']) ) {
        if( $object['content'] == '' && $object['synopsis'] != '' ) {
            $object['content'] = $object['synopsis'];
        }
        $text_content = strip_tags($object['content']);
        $text_content .= "\n";
        $text_content = preg_replace("/\n{3,}$/", "\n\n", $text_content);   // Remove extra blank lines at end
        ciniki_core_loadMethod($ciniki, 'ciniki', 'mail', 'private', 'emailProcessContent');
        $rc = ciniki_mail_emailProcessContent($ciniki, $tnid, $theme, $object['content']);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $html_content .= $rc['content'];
    }

    //
    // Check for files 
    //
    if( isset($object['files']) ) {
        $text_files = '';
        foreach($object['files'] as $file) {
            // Make sure directory is created
            if( !is_dir($cache_dir) ) {
                if( !mkdir($cache_dir, 0755, true) ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.mail.23', 'msg'=>'Unable to create mail cache'));
                }
            }
            $filename = $file['permalink'] . '.' . $file['extension'];
            // Save file
            $rc = file_put_contents($cache_dir . '/' . $filename, $file['binary_content']);
            if( $rc === false ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.mail.24', 'msg'=>'Unable to create mail file download'));
            }
            $url = $cache_url . "/" . $filename;
            $html_content .= "<a style='$a_style' target='_blank' href='" . $url . "' title='" . $file['name'] . "'>" . $file['name'] . "</a>";
            $text_files .= $file['name'] . ": " . $url . "\n";
            if( isset($file['description']) && $file['description'] != '' ) {
                $html_content .= "<br/><span style='file_description_style'>" . $file['description'] . "</span>";
            }
            $html_content .= "<br/>";
        }
        if( $text_files != '' ) {
            $text_content .= "\n" . $text_files;
            $html_content .= "<br/>";
        }
    }

    //
    // Check if links
    //
    if( isset($object['links']) ) {
        $text_links = '';
        ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'processURL');
        foreach($object['links'] as $link) {
            $rc = ciniki_web_processURL($ciniki, $link['url']);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( $rc['url'] != '' ) {
                $html_content .= "<a style='$a_style' href='" . $rc['url'] . "' title='" 
                    . ($link['name']!=''?$link['name']:$rc['display']) . "'>" 
                    . ($link['name']!=''?$link['name']:$rc['display'])
                    . "</a>";
                $text_links .= ($link['name']!=''?$link['name'] . ': ':'') . $rc['display'] . "\n";
            } else {
                $text_links .= $link['name'];
                $html_content .= $link['name'];
            }
            if( isset($link['description']) && $link['description'] != '' ) {
                $html_content .= "<br/><span class='downloads-description'>" . $link['description'] . "</span><br/>";
                $text_links .= $link['description'] . "\n\n";
            }
            $html_content .= "<br/>";
        }
        if( $text_links != '' ) {
            $text_content .= "\n" . $text_links;
            $html_content .= "</br>";
        }
    }

    //
    // Check for extra images
    //
    if( isset($object['images']) && count($object['images']) > 0 ) {
        $html_content .= "<br/>";
        $html_content .= "<h2 style='$h2_style'>Gallery</h2>";
        $html_content .= "<div style='$image_gallery_style'>";
        foreach($object['images'] as $inum => $img) {
            if( $img['image_id'] == 0 ) {
                $img_url = $domain_base_url . '/ciniki-web-layouts/default/img/noimage_240.png';
            } else {
                $rc = ciniki_mail_getScaledImageURL($ciniki, $tnid, $cache_dir, $img['image_id'], 'thumbnail', '100', 0);
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
                }
                $html_content .= "<div style='$image_gallery_thumbnail_style'>"
                    . "<img style='width: 100px; height: 100px; $image_gallery_thumbnail_img_style' title='' src='$cache_url/" . $rc['filename'] . "' /></div>";
            }
        }
        $html_content .= "</div>";
    }

    //
    // Check for linkback
    //
    if( isset($object['linkback']['url']) && $object['linkback']['url'] != '' ) {
        // IF linkback starts with / then reference website
        if( $object['linkback']['url'][0] == '/' ) {
            $url = $domain_base_url . $object['linkback']['url'];
        } else {
            $url = $object['linkback']['url'];
        }
        $text = isset($object['linkback']['text'])?$object['linkback']['text']:'View Online';
        $text_content .= "\n\n$text: " . $url;
        $html_content .= "<br/><center><a style='$linkback_style' href='$url' title='$text'>$text</a></center><br/>";
    }

    return array('stat'=>'ok', 'subject'=>$subject, 'text_content'=>$text_content, 'html_content'=>$html_content);
}
?>
