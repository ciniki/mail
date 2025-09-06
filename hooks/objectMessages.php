<?php
//
// Description
// -----------
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_mail_hooks_objectMessages($ciniki, $tnid, $args) {

    //
    // Load the status maps for the text description of each status
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'mail', 'private', 'maps');
    $rc = ciniki_mail_maps($ciniki);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $maps = $rc['maps'];

    //
    // Load intl date settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'datetimeFormat');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'timeFormat');
    $datetime_format = ciniki_users_datetimeFormat($ciniki, 'php');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');
    $time_format = ciniki_users_timeFormat($ciniki, 'php');

    //
    // Check for messages
    //
    if( isset($args['object']) && $args['object'] != '' 
        && (
            (isset($args['object_id']) && $args['object_id'] != '')
            ||
            (isset($args['object_ids']) && is_array($args['object_ids']))
            )
        && isset($args['xml']) && $args['xml'] == 'no'
        ) {
        //
        // Check if there is any mail for this object
        //
        $strsql = "SELECT ciniki_mail.id, "
            . "ciniki_mail.status, "
            . "ciniki_mail.status AS status_text, "
            . "UNIX_TIMESTAMP(ciniki_mail.date_sent) AS ts_date_sent, "
            . "ciniki_mail.date_sent, "
            . "ciniki_mail.customer_id, "
            . "ciniki_mail.customer_name, "
            . "ciniki_mail.customer_email, "
            . "ciniki_mail.subject "
            . "FROM ciniki_mail_objrefs, ciniki_mail "
            . "WHERE ciniki_mail_objrefs.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND ciniki_mail_objrefs.object = '" . ciniki_core_dbQuote($ciniki, $args['object']) . "' ";
        if( isset($args['object_ids']) && is_array($args['object_ids']) ) {
            $strsql .= "AND ciniki_mail_objrefs.object_id IN (" . ciniki_core_dbQuoteIDs($ciniki, $args['object_ids']) . ") ";
        } else {
            $strsql .= "AND ciniki_mail_objrefs.object_id = '" . ciniki_core_dbQuote($ciniki, $args['object_id']) . "' ";
        }
        $strsql .= "AND ciniki_mail_objrefs.mail_id = ciniki_mail.id "
            . "AND ciniki_mail.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "";
        if( isset($args['customer_id']) && $args['customer_id'] > 0 ) {
            $strsql .= "AND ciniki_mail.customer_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' ";
        }
        $strsql .= "ORDER BY ciniki_mail.date_sent DESC "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.mail', array(
            array('container'=>'messages', 'fname'=>'id', 
                'fields'=>array('id', 'status', 'status_text', 'ts_date_sent', 'date_sent', 'customer_id', 'customer_name', 'customer_email', 'subject'),
                'maps'=>array('status_text'=>$maps['mail']['status']),
                'utctotz'=>array('date_sent'=>array('timezone'=>$intl_timezone, 'format'=>$datetime_format)),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['messages']) ) {
            return array('stat'=>'ok', 'messages'=>$rc['messages']);
        }
    }
    elseif( isset($args['object']) && $args['object'] != '' 
        && isset($args['object_id']) && $args['object_id'] != ''
        ) {
        // FIXME: Change all references to this hook to accept simple ArrayTree output instead of xml ready Tree output.
        //
        // Check if there is any mail for this object
        //
        $strsql = "SELECT ciniki_mail.id, "
            . "ciniki_mail.status, "
            . "ciniki_mail.status AS status_text, "
            . "ciniki_mail.date_sent, "
            . "ciniki_mail.customer_id, "
            . "ciniki_mail.customer_name, "
            . "ciniki_mail.customer_email, "
            . "ciniki_mail.subject "
            . "FROM ciniki_mail_objrefs, ciniki_mail "
            . "WHERE ciniki_mail_objrefs.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND ciniki_mail_objrefs.object = '" . ciniki_core_dbQuote($ciniki, $args['object']) . "' "
            . "AND ciniki_mail_objrefs.object_id = '" . ciniki_core_dbQuote($ciniki, $args['object_id']) . "' "
            . "AND ciniki_mail_objrefs.mail_id = ciniki_mail.id "
            . "AND ciniki_mail.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "";
        if( isset($args['customer_id']) && $args['customer_id'] > 0 ) {
            $strsql .= "AND ciniki_mail.customer_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' ";
        }
        $strsql .= "ORDER BY ciniki_mail.date_sent DESC "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
        $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.mail', array(
            array('container'=>'mail', 'fname'=>'id', 'name'=>'message',
                'fields'=>array('id', 'status', 'status_text', 'date_sent', 'customer_name', 'customer_email', 'subject'),
                'maps'=>array('status_text'=>$maps['mail']['status']),
                'utctotz'=>array('date_sent'=>array('timezone'=>$intl_timezone, 'format'=>$datetime_format)),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['mail']) ) {
            return array('stat'=>'ok', 'messages'=>$rc['mail']);
        }
    }
    elseif( isset($args['object']) && $args['object'] != '' 
        && isset($args['customer_id']) && $args['customer_id'] != ''
        ) {
        //
        // Check if there is any mail for this object
        //
        $strsql = "SELECT ciniki_mail.id, "
            . "ciniki_mail.status, "
            . "ciniki_mail.status AS status_text, "
            . "ciniki_mail.date_sent, "
            . "ciniki_mail.date_sent AS mail_date, "
            . "ciniki_mail.date_sent AS mail_time, "
            . "ciniki_mail.customer_id, "
            . "ciniki_mail.customer_name, "
            . "ciniki_mail.customer_email, "
            . "ciniki_mail.subject, "
            . "SUBSTR(IF(text_content<>'',text_content,html_content), 1, 150) AS snippet "
            . "FROM ciniki_mail_objrefs, ciniki_mail "
            . "WHERE ciniki_mail_objrefs.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND ciniki_mail_objrefs.object = '" . ciniki_core_dbQuote($ciniki, $args['object']) . "' "
            . "AND ciniki_mail_objrefs.mail_id = ciniki_mail.id "
            . "AND ciniki_mail.customer_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
            . "AND ciniki_mail.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "ORDER BY ciniki_mail.date_sent DESC "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.mail', array(
            array('container'=>'mail', 'fname'=>'id', 
                'fields'=>array('id', 'status', 'status_text', 'date_sent', 'mail_date', 'mail_time', 
                    'customer_name', 'customer_email', 'subject', 'snippet'),
                'maps'=>array('status_text'=>$maps['mail']['status']),
                'utctotz'=>array('date_sent'=>array('timezone'=>$intl_timezone, 'format'=>$datetime_format),
                    'mail_date'=>array('timezone'=>$intl_timezone, 'format'=>$date_format),
                    'mail_time'=>array('timezone'=>$intl_timezone, 'format'=>$time_format)),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['mail']) ) {
            return array('stat'=>'ok', 'messages'=>$rc['mail']);
        }
    }

    return array('stat'=>'ok');
}
?>
