<?php
//
// Description
// -----------
// This function will return the cache-url to an image, and generate the image cache 
// if it does not exist.  This allows a normal url to be presented to the browser, and
// proper caching in the browser.
//
// Arguments
// ---------
// ciniki:
// image_id:        The ID of the image in the images module to prepare for the mail message.
// version:         The version of the image, original or thumbnail.  Thumbnail down not
//                  refer to the size, but the square cropped version of the original.
// maxwidth:        The maximum width the rendered photo should be.
// maxheight:       The maximum height the rendered photo should be.
// quality:         The quality setting for jpeg output.  The default if unspecified is 60.
//
// Returns
// -------
//
function ciniki_mail_getScaledImageURL($ciniki, $tnid, $cache_dir, $image_id, $version, $maxwidth, $maxheight, $quality='60') {

    //
    // Load last_updated date to check against the cache
    //
    $strsql = "SELECT id, uuid, type, UNIX_TIMESTAMP(ciniki_images.last_updated) AS last_updated "
        . "FROM ciniki_images "
        . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $image_id) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.images', 'image');
    if( $rc['stat'] != 'ok' ) { 
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.mail.25', 'msg'=>'Unable to load image', 'err'=>$rc['err']));
    }
    if( !isset($rc['image']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.mail.26', 'msg'=>'Unable to load image'));
    }
    $img = $rc['image'];

    //
    // Build working path, and final url
    //
    if( $img['type'] == 2 ) {
        $extension = 'png';
    } else {
        $extension = 'jpg';
    }
    if( $maxwidth == 0 ) {
//      $filename = '/' . sprintf('%02d', ($ciniki['request']['tnid']%100)) . '/'
//          . sprintf('%07d', $ciniki['request']['tnid'])
//          . '/h' . $maxheight . '/' . sprintf('%010d', $img['id']) . '.' . $extension;
        $filename = $img['uuid'] . '-h' . $maxheight . '.' . $extension;
    } else {
//      $filename = '/' . sprintf('%02d', ($ciniki['request']['tnid']%100)) . '/'
//          . sprintf('%07d', $ciniki['request']['tnid'])
//          . '/w' . $maxwidth . '/' . sprintf('%010d', $img['id']) . '.' . $extension;
        $filename = $img['uuid'] . '-w' . $maxwidth . '.' . $extension;
    }
    $img_filename = $cache_dir . '/' . $filename;
//  $img_url = $ciniki['request']['cache_url'] . $filename;
//  $img_domain_url = 'http://' . $ciniki['request']['domain'] . $ciniki['request']['cache_url'] . $filename;

    //
    // Load the image from the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'images', 'private', 'loadImage');
    $rc = ciniki_images_loadImage($ciniki, $tnid, $img['id'], $version);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $image = $rc['image'];

    //
    // Scale image
    //
    $image->scaleImage($maxwidth, $maxheight);

    //
    // Apply a border
    //
    // $image->borderImage("rgb(255,255,255)", 10, 10);

    //
    // Check if directory exists
    //
    if( !file_exists(dirname($img_filename)) ) {
        if( !mkdir(dirname($img_filename), 0755, true) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.mail.27', 'msg'=>'Unable to create mail cache'));
        }
    }

    //
    // Write the file
    //
    $h = fopen($img_filename, 'w');
    if( $h ) {
        if( $img['type'] == 2 ) {
            $image->setImageFormat('png');
        } else {
            $image->setImageCompressionQuality($quality);
        }
        fwrite($h, $image->getImageBlob());
        fclose($h);
    } else {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.mail.28', 'msg'=>'Unable to load image'));
    }

    return array('stat'=>'ok', 'filename'=>$filename);
}
?>
