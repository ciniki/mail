<?php
//
// Description
// -----------
//
// Arguments
// ---------
// ciniki:
// tnid:     The ID of the tenant to the mail belongs to.
// mail_id:         The ID of the mail message to send.
// 
// Returns
// -------
//
function ciniki_mail_sendMail($ciniki, $tnid, &$settings, $mail_id) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');

    //
    // This function is run after the API has returned status, or from cron,
    // so all errors should be send to mail log
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'mail', 'private', 'logMsg');
    
    //
    // Query for mail details
    //
    $strsql = "SELECT id, uuid, status, "
        . "mailing_id, survey_invite_id, "
        . "customer_id, customer_name, customer_email, "
        . "subject, html_content, text_content "
        . "FROM ciniki_mail "
        . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $mail_id) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND status >= 10 "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.mail', 'mail');
    if( $rc['stat'] != 'ok' ) {
        return ciniki_mail_logMsg($ciniki, $tnid, array('code'=>'ciniki.mail.51', 'msg'=>'Unable to find message',
            'mail_id'=>$mail_id, 'severity'=>50, 'err'=>$rc['err'],
            ));
    }
    if( !isset($rc['mail']) ) {
        return ciniki_mail_logMsg($ciniki, $tnid, array('code'=>'ciniki.mail.52', 'msg'=>'Message does not exist.',
            'mail_id'=>$mail_id, 'severity'=>50, 
            ));
    }
    $email = $rc['mail'];

    //
    // Get the tenant storage directory
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'hooks', 'storageDir');
    $rc = ciniki_tenants_hooks_storageDir($ciniki, $tnid, array());
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $mail_dir = $rc['storage_dir'] . '/ciniki.mail';

    //
    // Check for content on disk
    //
    if( file_exists($mail_dir . '/' . $email['uuid'][0] . '/' . $email['uuid'] . '.html') ) {
        $email['html_content'] = file_get_contents($mail_dir . '/' . $email['uuid'][0] . '/' . $email['uuid'] . '.html');
    }
    if( file_exists($mail_dir . '/' . $email['uuid'][0] . '/' . $email['uuid'] . '.text') ) {
        $email['text_content'] = file_get_contents($mail_dir . '/' . $email['uuid'][0] . '/' . $email['uuid'] . '.text');
    }

    //
    // Check for attachments
    //
    $strsql = "SELECT id, uuid, filename, content "
        . "FROM ciniki_mail_attachments "
        . "WHERE ciniki_mail_attachments.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND ciniki_mail_attachments.mail_id = '" . ciniki_core_dbQuote($ciniki, $email['id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.mail', 'attachment');
    if( $rc['stat'] != 'ok' ) {
        return ciniki_mail_logMsg($ciniki, $tnid, array('code'=>'ciniki.mail.53', 'msg'=>'Unable to find attachments for the message',
            'mail_id'=>$mail_id, 'severity'=>50, 'err'=>$rc['err'],
            ));
    }
    if( isset($rc['rows']) ) {
        $email['attachments'] = $rc['rows'];
    }

    //
    // Set timezone
    //
    date_default_timezone_set('UTC');

    //
    // Check if we can lock the message, by updating to status 20
    //
    $strsql = "UPDATE ciniki_mail SET status = 20, last_updated = UTC_TIMESTAMP() "
        . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $mail_id) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    $rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.mail');
    if( $rc['stat'] != 'ok' ) {
        return ciniki_mail_logMsg($ciniki, $tnid, array('code'=>'ciniki.mail.54', 'msg'=>'Unable to aquire lock.', 'pmsg'=>'Failed to update status=20',
            'mail_id'=>$mail_id, 'severity'=>50, 'err'=>$rc['err'],
            ));
    }
    if( $rc['num_affected_rows'] < 1 ) {
        return ciniki_mail_logMsg($ciniki, $tnid, array('code'=>'ciniki.mail.55', 'msg'=>'Unable to aquire lock.', 'pmsg'=>'No rows updated',
            'mail_id'=>$mail_id, 'severity'=>50, 
            ));
    }
    ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.mail', 'ciniki_mail_history', $tnid, 
        2, 'ciniki_mail', $mail_id, 'status', '20');

    //
    // Send via mailgun
    //
    if( isset($settings['mailgun-domain']) && $settings['mailgun-domain'] != '' 
        && isset($settings['mailgun-key']) && $settings['mailgun-key'] != '' 
        ) {
        //
        // Setup the message
        //
        $msg = array(
            'from' => $settings['smtp-from-name'] . ' <' . $settings['smtp-from-address'] . '>',
            'subject' => $email['subject'],
            'html' => $email['html_content'],
            'text' => $email['text_content'],
//            'subject' => iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $email['subject']),
//            'html' => iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $email['html_content']),
//            'text' => iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $email['text_content']),
            );
        if( isset($ciniki['config']['ciniki.mail']['force.mailto']) ) {
            $msg['to'] = $email['customer_name'] . ' <' . $ciniki['config']['ciniki.mail']['force.mailto'] . '>';
            $msg['subject'] .= ' [' . $email['customer_email'] . ']';
        } else {
            $msg['to'] = $email['customer_name'] . ' <' . $email['customer_email'] . '>';
        }

        //
        // Check for replyto_email
        //
        if( isset($email['replyto_email']) && $email['replyto_email'] != '' ) { 
            if( isset($email['replyto_name']) && $email['replyto_name'] != '' ) {
                $msg['h:Reply-To'] = $email['replyto_name'] . ' <' . $email['replyto_email'] . '> ';
            } else {
                $msg['h:Reply-To'] = $email['replyto_email'];
            }
        }

        //
        // Add attachments
        //
        $file_index = 1;
        if( isset($email['attachments']) ) {
            foreach($email['attachments'] as $attachment) {
                $attachment_file = $mail_dir . '/' . $attachment['uuid'][0] . '/' . $attachment['uuid'] . '.attachment';
//                $tmpname = tempnam(sys_get_temp_dir(), 'mailgun');
//                file_put_contents($tmpname, $attachment['content']);
                $cfile = curl_file_create($attachment_file);
                $cfile->setPostFilename($attachment['filename']);
                $msg['attachment[' . $file_index .']'] = $cfile;
                $file_index++;
            }
        }

        if( isset($ciniki['config']['ciniki.mail']['block.outgoing']) ) {
            error_log('EMAIL BLOCK BY CONFIG: ' . $msg['to'] . ' - ' . $msg['subject']);
        } else {
            //
            // Send to mailgun api
            //
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($ch, CURLOPT_USERPWD, 'api:' . $settings['mailgun-key']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_ENCODING, 'UTF-8');
            curl_setopt($ch, CURLOPT_URL, 'https://api.mailgun.net/v3/' . $settings['mailgun-domain'] . '/messages');
            curl_setopt($ch, CURLOPT_POSTFIELDS, $msg);

            $rsp = json_decode(curl_exec($ch));

            $info = curl_getinfo($ch);
            if( $info['http_code'] != 200 ) {
                //
                // Update the mail status to failed
                //
                $strsql = "UPDATE ciniki_mail SET status = 50, last_updated = UTC_TIMESTAMP() "
                    . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $mail_id) . "' "
                    . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                    . "";
                $rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.mail');
                if( $rc['stat'] != 'ok' ) {
                    curl_close($ch);
                    return ciniki_mail_logMsg($ciniki, $tnid, array('code'=>'ciniki.mail.66', 'msg'=>'Unable to send message and unable to unlock.', 'pmsg'=>'Could not set status=50',
                        'mail_id'=>$mail_id, 'severity'=>50, 'err'=>$rc['err'],
                        ));
                }
                ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.mail', 'ciniki_mail_history', $tnid, 
                    2, 'ciniki_mail', $mail_id, 'status', '50');
                curl_close($ch);
                return ciniki_mail_logMsg($ciniki, $tnid, array('code'=>'ciniki.mail.67', 'msg'=>'Unable to send message to ' . $msg['to'], 'pmsg'=>$rsp->message,
                    'mail_id'=>$mail_id, 'severity'=>50,
                    ));
            }
            curl_close($ch);
        }
    } 
   
    //
    // Send using PHPMailer and SMTP
    //
    else {
        //  
        // The from address can be set in the config file.
        //  
//        require_once($ciniki['config']['ciniki.core']['lib_dir'] . '/PHPMailer/class.phpmailer.php');
//        require_once($ciniki['config']['ciniki.core']['lib_dir'] . '/PHPMailer/class.smtp.php');
        require_once($ciniki['config']['ciniki.core']['lib_dir'] . '/PHPMailer_v6/src/PHPMailer.php');
        require_once($ciniki['config']['ciniki.core']['lib_dir'] . '/PHPMailer_v6/src/SMTP.php');
        require_once($ciniki['config']['ciniki.core']['lib_dir'] . '/PHPMailer_v6/src/Exception.php');

        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

        try {
            $mail->SMTPOptions = array(
                'tls'=>array(
                    'verify_peer'=> false,
                    'verify_peer_name'=> false,
                    'allow_self_signed'=> true,
                ),
                'ssl'=>array(
                    'verify_peer'=> false,
                    'verify_peer_name'=> false,
                    'allow_self_signed'=> true,
                ),
            );
            $mail->XMailer = ' ';
            $mail->IsSMTP();
            $use_config = 'yes';
            if( isset($settings['smtp-servers']) && $settings['smtp-servers'] != ''
                ) {
                $mail->Host = $settings['smtp-servers'];
                if( isset($settings['smtp-username']) && $settings['smtp-username'] != '' ) {
                    $mail->SMTPAuth = true;
                    $mail->Username = $settings['smtp-username'];
                    $mail->Password = $settings['smtp-password'];
                }
                if( isset($settings['smtp-secure']) && $settings['smtp-secure'] != '' ) {
                    $mail->SMTPSecure = $settings['smtp-secure'];
                }
                if( isset($settings['smtp-port']) && $settings['smtp-port'] != '' ) {
                    $mail->Port = $settings['smtp-port'];
                }
                $mail->From = $settings['smtp-from-address'];
                $mail->Sender = $settings['smtp-from-address'];
                $mail->FromName = $settings['smtp-from-name'];
            } else {
                $mail->Host = $ciniki['config']['ciniki.core']['system.smtp.servers'];
                if( isset($ciniki['config']['ciniki.core']['system.smtp.username']) ) {
                    $mail->SMTPAuth = true;
                    $mail->Username = $ciniki['config']['ciniki.core']['system.smtp.username'];
                    $mail->Password = $ciniki['config']['ciniki.core']['system.smtp.password'];
                }
                if( isset($ciniki['config']['ciniki.core']['system.smtp.secure']) ) {
                    $mail->SMTPSecure = $ciniki['config']['ciniki.core']['system.smtp.secure'];
                }
                if( isset($ciniki['config']['ciniki.core']['system.smtp.port']) ) {
                    $mail->Port = $ciniki['config']['ciniki.core']['system.smtp.port'];
                }

                if( isset($settings['smtp-from-address']) && $settings['smtp-from-address'] != '' ) {
                    $mail->From = $settings['smtp-from-address'];
                    $mail->Sender = $settings['smtp-from-address'];
                } else {
                    $mail->From = $ciniki['config']['ciniki.core']['system.email'];
                    $mail->Sender = $ciniki['config']['ciniki.core']['system.email'];
                }
                if( isset($settings['smtp-from-name']) && $settings['smtp-from-name'] != '' ) {
                    $mail->FromName = $settings['smtp-from-name'];
                } else {
                    $mail->FromName = $ciniki['config']['ciniki.core']['system.email.name'];
                }
            }

            //
            // Check for replyto_email
            //
            if( isset($settings['smtp-reply-address']) && $settings['smtp-reply-address'] != '' ) { 
                if( isset($settings['smtp-from-name']) && $settings['smtp-from-name'] != '' ) {
                    $mail->addReplyTo($settings['smtp-reply-address'], $settings['smtp-from-name']);
                } else {
                    $mail->addReplyTo($settings['smtp-reply-address']);
                }
            }


        //  $mail->SMTPAuth = true;
        //  $mail->Username = $ciniki['config']['ciniki.core']['system.smtp.username'];
        //  $mail->Password = $ciniki['config']['ciniki.core']['system.smtp.password'];

            $mail->IsHTML(true);
            $mail->Subject = $email['subject'];
            $mail->Body = $email['html_content'];
            $mail->AltBody = $email['text_content'];
    //        $mail->Subject = iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $email['subject']);
    //        $mail->Body = iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $email['html_content']);
    //        $mail->AltBody = iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $email['text_content']);

            if( isset($ciniki['config']['ciniki.mail']['force.mailto']) ) {
                $mail->AddAddress($ciniki['config']['ciniki.mail']['force.mailto'], $email['customer_name']);
                $mail->Subject .= ' [' . $email['customer_email'] . ']';
            } else {
                $mail->AddAddress($email['customer_email'], $email['customer_name']);
            }

            //
            // Add attachments
            //
            if( isset($email['attachments']) ) {
                foreach($email['attachments'] as $attachment) {
                    $attachment['content'] = file_get_contents($mail_dir . '/' . $attachment['uuid'][0] . '/' . $attachment['uuid'] . '.attachment');
                    $mail->addStringAttachment($attachment['content'], $attachment['filename']);
                }
            }

            if( isset($ciniki['config']['ciniki.mail']['block.outgoing']) ) {
                error_log('EMAIL BLOCK BY CONFIG: ' . $email['customer_email'] . ' - ' . $email['subject']);
            } 
            $mail->Send();
            ciniki_mail_logMsg($ciniki, $tnid, array('code'=>'0', 'msg'=>'Message sent.', 'mail_id'=>$mail_id, 'severity'=>10,)); 
            /*
            ** NOTE: Changed to PHPMailer v6 and no longer using multiple tries, as it always failed a second time anyway.
                ciniki_mail_logMsg($ciniki, $tnid, array('code'=>'ciniki.mail.57', 'msg'=>'Unable to send message, trying again.', 'pmsg'=>$mail->ErrorInfo,
                    'mail_id'=>$mail_id, 'severity'=>30,
                    ));
                sleep(3);
                if( !$mail->Send() ) {  
                    ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.mail', 'ciniki_mail_history', $tnid, 
                        2, 'ciniki_mail', $mail_id, 'status', '50');
                    return ciniki_mail_logMsg($ciniki, $tnid, array('code'=>'ciniki.mail.59', 'msg'=>'Unable to send message.', 'pmsg'=>$mail->ErrorInfo,
                        'mail_id'=>$mail_id, 'severity'=>50,
                        ));
                }
            } */
        } catch(Exception $e) {
            //
            // Update the mail status to failed
            //
            $strsql = "UPDATE ciniki_mail SET status = 50, last_updated = UTC_TIMESTAMP() "
                . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $mail_id) . "' "
                . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . "";
            $rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.mail');
            if( $rc['stat'] != 'ok' ) {
                return ciniki_mail_logMsg($ciniki, $tnid, array('code'=>'ciniki.mail.58', 'msg'=>'Unable to send message and unable to unlock.', 'pmsg'=>'Could not set status=50',
                    'mail_id'=>$mail_id, 'severity'=>50, 'err'=>$rc['err'],
                    ));
            }
            return ciniki_mail_logMsg($ciniki, $tnid, array('code'=>'ciniki.mail.94', 'msg'=>$e->getMessage(), 'pmsg'=>$e->getMessage(),
                'mail_id'=>$mail_id, 'severity'=>50,
                ));
        }
    }

    //
    // Update the mail status
    //
    $utc_datetime = strftime("%Y-%m-%d %H:%M:%S");
    $strsql = "UPDATE ciniki_mail SET status = 30, date_sent = '" . ciniki_core_dbQuote($ciniki, $utc_datetime) . "', last_updated = UTC_TIMESTAMP() "
        . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $mail_id) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    $rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.mail');
    if( $rc['stat'] != 'ok' ) {
        return ciniki_mail_logMsg($ciniki, $tnid, array('code'=>'ciniki.mail.60', 'msg'=>'Message send, unable to unlock.', 'pmsg'=>'Could not set status=30',
            'mail_id'=>$mail_id, 'severity'=>40, 'err'=>$rc['err'],
            ));
    }
    ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.mail', 'ciniki_mail_history', $tnid, 
        2, 'ciniki_mail', $mail_id, 'status', '30');
    ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.mail', 'ciniki_mail_history', $tnid, 
        2, 'ciniki_mail', $mail_id, 'date_sent', $utc_datetime);

    //
    // Update the survey invite
    //
    if( $email['survey_invite_id'] > 0 ) {
        $strsql = "UPDATE ciniki_survey_invites SET status = 10, date_sent = '" . ciniki_core_dbQuote($ciniki, $utc_datetime) . "', last_updated = UTC_TIMESTAMP() "
            . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $email['survey_invite_id']) . "' "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND status = 5 "
            . "";
        $rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.surveys');
        if( $rc['stat'] != 'ok' ) {
            return ciniki_mail_logMsg($ciniki, $tnid, array('code'=>'ciniki.mail.56', 'msg'=>'Unable to update survey.', 'pmsg'=>'Could not set survey status=10',
                'mail_id'=>$mail_id, 'severity'=>40, 'err'=>$rc['err'],
                ));
        }
        if( $rc['num_affected_rows'] > 0 ) {
            ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.surveys', 'ciniki_survey_history', $tnid, 
                2, 'ciniki_survey_invites', $email['survey_invite_id'], 'status', '10');
            ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.surveys', 'ciniki_survey_history', $tnid, 
                2, 'ciniki_survey_invites', $email['survey_invite_id'], 'date_sent', $utc_datetime);
        }
    }

    //
    // FIXME: Check for hooks to update other modules
    //

    return array('stat'=>'ok');
}
