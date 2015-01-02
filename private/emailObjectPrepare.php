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
// business_id:			The ID of the business to mail mailing belongs to.
// mailing_id:			The ID of the mailing to get.
//
// Returns
// -------
//
function ciniki_mail_emailObjectPrepare($ciniki, $business_id, $theme, $mailing) {

	//
	// Make sure the object is specified
	//
	if( !isset($mailing['object']) || $mailing['object'] == '' 
		|| !isset($mailing['object_id']) || $mailing['object_id'] == '' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2129', 'msg'=>'Object not specified'));
	}

	//
	// Get the business uuid
	//
	$strsql = "SELECT uuid, sitename "
		. "FROM ciniki_businesses "
		. "WHERE id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.businesses', 'business');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['business']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2134', 'msg'=>'Business not found'));
	}
	$business = $rc['business'];

	//
	// Get the primary domain for the business
	//
	$strsql = "SELECT domain "
		. "FROM ciniki_business_domains "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "AND status = 1 "
		. "ORDER BY flags DESC "
		. "LIMIT 1 "
		. "";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.businesses', 'domain');
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
		$domain_base_url = 'http://' . $master_domain . '/' . $business['sitename'];
		$cache_url = 'http://' . $master_domain;
	}

	//
	// Setup where we can store included images and downloadable files for the email message and
	// they will be accesible through the website
	//
	$cache_url .= '/ciniki-mail-cache'
		. '/' . $business['uuid'][0] . '/' . $business['uuid']
		. '/' . $mailing['uuid'][0] . '/' . $mailing['uuid'];
	$cache_dir = $ciniki['config']['ciniki.core']['modules_dir'] . '/mail/cache' 
		. '/' . $business['uuid'][0] . '/' . $business['uuid']
		. '/' . $mailing['uuid'][0] . '/' . $mailing['uuid'];

	//
	// Load the object content
	//
	list($pkg, $mod, $obj) = explode('.', $mailing['object']);
	$rc = ciniki_core_loadMethod($ciniki, $pkg, $mod, 'hooks', 'mailingContent');
	$fn = $rc['function_call'];
	$rc = $fn($ciniki, $business_id, array('object'=>$mailing['object'], 'object_id'=>$mailing['object_id']));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$object = $rc['object'];
	$subject = '';
	$text_content = '';
	$html_content = '';

	//
	// Prepare the content for the email
	//
	$h2_style = isset($theme['h2'])?$theme['h2']:'';
	$image_wrap_style = isset($theme['image_wrap'])?$theme['image_wrap']:'';
	$image_style = isset($theme['image'])?$theme['image']:'';
	$img_style = isset($theme['img'])?$theme['img']:'';
	$image_caption_style = isset($theme['image_caption'])?$theme['image_caption']:'';
	$file_description_style = isset($theme['file_description'])?$theme['file_description']:'';
	$image_gallery_style = isset($theme['image_gallery'])?$theme['image_gallery']:'';
	$image_gallery_thumbnail_style = isset($theme['image_gallery_thumbnail'])?$theme['image_gallery_thumbnail']:'';

	//
	// Check for a primary image
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'mail', 'private', 'getScaledImageURL');
	if( isset($object['image_id']) && $object['image_id'] > 0 ) {
		$rc = ciniki_mail_getScaledImageURL($ciniki, $business_id, $cache_dir, $object['image_id'], 'original', '500', 0);
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		$html_content .= "<div style='$image_wrap_style'>"
			. "<div style='$image_style'><img style='$img_style' title='' src='$cache_url/" . $rc['filename'] . "' /></div>";
		if( isset($page['image_caption']) && $page['image_caption'] != '' ) {
			$html_content .= "<div style='$image_caption_style'>" . $page['image_caption'] . "</div>";
		}
		$html_content .= "</div>";
	}

	//
	// Format the content
	//
	if( isset($object['content']) ) {
		$text_content = strip_tags($object['content']);
		$text_content .= "\n";
		$text_content = preg_replace("/\n{3,}$/", "\n\n", $text_content);	// Remove extra blank lines at end
		ciniki_core_loadMethod($ciniki, 'ciniki', 'mail', 'private', 'emailProcessContent');
		$rc = ciniki_mail_emailProcessContent($ciniki, $business_id, $theme, $object['content']);
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
					return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2126', 'msg'=>'Unable to create mail cache'));
				}
			}
			$filename = $file['permalink'] . '.' . $file['extension'];
			// Save file
			$rc = file_put_contents($cache_dir . '/' . $filename, $file['binary_content']);
			if( $rc === false ) {
				return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2127', 'msg'=>'Unable to create mail file download'));
			}
			$url = $cache_url . "/" . $filename;
			$html_content .= "<a target='_blank' href='" . $url . "' title='" . $file['name'] . "'>" . $file['name'] . "</a>";
			$text_files .= $file['name'] . ": " . $url . "\n";
			if( isset($file['description']) && $file['description'] != '' ) {
				$html_content .= "<br/><span style='file_description_style'>" . $file['description'] . "</span>";
			}
			$html_content .= "<br/>";
		}
		if( $text_files != '' ) {
			$text_content .= "\n" . $text_files;
		}
	}

	//
	// Check if links
	//
	if( isset($object['links']) ) {
		$text_links = '';
		foreach($object['links'] as $link) {
			$url = $link['url'];
			if( $url != '' && !preg_match('/^\s*http/i', $url) ) {
				$display_url = $url;
				$url = "http://" . preg_replace('/^\s*/', '', $url);
			} elseif( $url != '' && !preg_match('/^\s*\//i', $url) ) {	// starts with /
				$url = $domain_base_url + $url;
				$display_url = preg_replace('/^\s*http:\/\//i', '', $url);
				$display_url = preg_replace('/\/$/i', '', $display_url);
			} else {
				$display_url = preg_replace('/^\s*http:\/\//i', '', $url);
				$display_url = preg_replace('/\/$/i', '', $display_url);
			}
			$text_links .= $display_url . "\n";
			$html_content .= "<br/><a href='" . $url . "' title='" . ($link['name']!=''?$link['name']:$display_url) . "'>" . $display_url . "</a>";
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
				$rc = ciniki_mail_getScaledImageURL($ciniki, $business_id, $cache_dir, $img['image_id'], 'original', '500', 0);
				if( $rc['stat'] != 'ok' ) {
					return $rc;
				}
				$html_content .= "<div style='$image_gallery_thumbnail_style'>"
					. "<img style='$img_style' title='' src='$cache_url/" . $rc['filename'] . "' /></div>";
			}
		}
		$html_content .= "</div>";
	}

	return array('stat'=>'ok', 'subject'=>$subject, 'text_content'=>$text_content, 'html_content'=>$html_content);
}
?>
