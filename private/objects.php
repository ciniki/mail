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
function ciniki_mail_objects($ciniki) {
    
    $objects = array();
    //
    // Mail accounts are not yet implemented
    //
/*    $objects['account'] = array(
        'name'=>'Mail Account',
        'sync'=>'yes',
        'table'=>'ciniki_mail_accounts',
        'fields'=>array(
            'smtp_server'=>array(),
            'smtp_username'=>array(),
            'smtp_password'=>array(),
            'smtp_security'=>array(),
            'smtp_port'=>array(),
            'smtp_from_address'=>array('default'=>''),
            'smtp_from_name'=>array('default'=>''),
            'smtp_5min_limit'=>array('default'=>'1'),
            'theme_id'=>array('default'=>'0'),
            'subject'=>array(),
            'html_content'=>array(),
            'text_content'=>array(),
            'raw_headers'=>array(),
            'raw_content'=>array(),
            'date_read'=>array(),
            ),
        'history_table'=>'ciniki_mail_history',
        ); */
    // Mail object will be deprecated
    $objects['mail'] = array(
        'name'=>'Mail',
        'sync'=>'yes',
        'table'=>'ciniki_mail',
        'fields'=>array(
            'parent_id'=>array('ref'=>'ciniki.mail.message'),
            'account_id'=>array('default'=>'0', 'ref'=>'ciniki.mail.account'),
            'mailing_id'=>array('ref'=>'ciniki.mail.mailing'),
            'unsubscribe_key'=>array('default'=>''),
            'survey_invite_id'=>array('ref'=>'ciniki.surveys.survey', 'default'=>'0'),
            'customer_id'=>array('ref'=>'ciniki.customers.customer', 'default'=>'0'),
            'customer_name'=>array('default'=>''),
            'customer_email'=>array('default'=>''),
            'flags'=>array('default'=>'0'),
            'status'=>array('default'=>'0'),
            'date_sent'=>array(),
            'date_received'=>array(),
            'mail_to'=>array(),
            'mail_cc'=>array(),
            'mail_from'=>array(),
            'subject'=>array('history'=>'no'),
            'html_content'=>array('history'=>'no'),
            'text_content'=>array('history'=>'no'),
            'raw_headers'=>array('history'=>'no'),
            'raw_content'=>array('history'=>'no'),
            'date_read'=>array(),
            ),
        'history_table'=>'ciniki_mail_history',
        );
    // Same as mail object above
    $objects['message'] = array(
        'name'=>'Mail Message',
        'sync'=>'yes',
        'table'=>'ciniki_mail',
        'fields'=>array(
            'parent_id'=>array('ref'=>'ciniki.mail.message'),
            'account_id'=>array('default'=>'0', 'ref'=>'ciniki.mail.account'),
            'mailing_id'=>array('ref'=>'ciniki.mail.mailing'),
            'unsubscribe_key'=>array('default'=>''),
            'survey_invite_id'=>array('ref'=>'ciniki.surveys.survey', 'default'=>'0'),
            'customer_id'=>array('ref'=>'ciniki.customers.customer', 'default'=>'0'),
            'customer_name'=>array('default'=>''),
            'customer_email'=>array('default'=>''),
            'flags'=>array('default'=>'0'),
            'status'=>array('default'=>'0'),
            'date_sent'=>array(),
            'date_received'=>array(),
            'mail_to'=>array(),
            'mail_cc'=>array(),
            'mail_from'=>array(),
            'subject'=>array(),
            'html_content'=>array(),
            'text_content'=>array(),
            'raw_headers'=>array(),
            'raw_content'=>array(),
            'date_read'=>array(),
            ),
        'history_table'=>'ciniki_mail_history',
        );
    $objects['objref'] = array(
        'name'=>'Mail Object Reference',
        'sync'=>'yes',
        'table'=>'ciniki_mail_objrefs',
        'fields'=>array(
            'mail_id'=>array('ref'=>'ciniki.mail.message'),
            'object'=>array(),
            'object_id'=>array(),
            ),
        'history_table'=>'ciniki_mail_history',
        );
    $objects['attachment'] = array(
        'name'=>'Mail Attachment',
        'sync'=>'yes',
        'table'=>'ciniki_mail_attachments',
        'fields'=>array(
            'mail_id'=>array('name'=>'Mail Message', 'ref'=>'ciniki.mail.message'),
            'filename'=>array('name'=>'Filename'),
            'content'=>array(),
            ),
        'history_table'=>'ciniki_mail_history',
        );
    $objects['mailing'] = array(
        'name'=>'Mailing',
        'sync'=>'yes',
        'table'=>'ciniki_mailings',
        'fields'=>array(
            'type'=>array(),
            'status'=>array(),
            'theme'=>array(),
            'survey_id'=>array('ref'=>'ciniki.surveys.survey', 'default'=>0),
            'object'=>array('default'=>''),
            'object_id'=>array('default'=>''),
            'subject'=>array('default'=>''),
            'primary_image_id'=>array('ref'=>'ciniki.images.image', 'default'=>'0'),
            'html_content'=>array('default'=>''),
            'text_content'=>array('default'=>''),
            'date_started'=>array('default'=>''),
            'date_sent'=>array('default'=>''),
            ),
        'history_table'=>'ciniki_mail_history',
        );
    $objects['mailing_subscription'] = array(
        'name'=>'Mailing Subscription',
        'sync'=>'yes',
        'table'=>'ciniki_mailing_subscriptions',
        'fields'=>array(
            'mailing_id'=>array('ref'=>'ciniki.mail.mailing'),
            'subscription_id'=>array('ref'=>'ciniki.subscriptions.subscription'),
            ),
        'history_table'=>'ciniki_mail_history',
        );
    $objects['mailing_attachment'] = array(
        'name'=>'Mailing Attachment',
        'sync'=>'yes',
        'table'=>'ciniki_mailing_attachments',
        'fields'=>array(
            'mail_id'=>array('ref'=>'ciniki.mail.mailing'),
            'filename'=>array(),
            'content'=>array(),
            ),
        'history_table'=>'ciniki_mail_history',
        );
    $objects['mailing_image'] = array(
        'name'=>'Mailing Image',
        'sync'=>'yes',
        'table'=>'ciniki_mailing_images',
        'fields'=>array(
            'mailing_id'=>array('ref'=>'ciniki.mail.mailing'),
            'name'=>array(),
            'permalink'=>array(),
            'image_id'=>array('ref'=>'ciniki.images.image'),
            'description'=>array(),
            ),
        'history_table'=>'ciniki_mail_history',
        );
    $objects['log'] = array(
        'name'=>'Mail Log',
        'sync'=>'yes',
        'table'=>'ciniki_mail_log',
        'fields'=>array(
            'mail_id'=>array('default'=>'0', 'ref'=>'ciniki.mail.message'),
            'severity'=>array('default'=>'10'),
            'log_date'=>array(),
            'code'=>array(),
            'msg'=>array(),
            'pmsg'=>array('default'=>''),
            'errors'=>array('default'=>''),
            'raw_logs'=>array('default'=>''),
            ),
        );
    $objects['setting'] = array(
        'type'=>'settings',
        'name'=>'Mail Settings',
        'table'=>'ciniki_mail_settings',
        'history_table'=>'ciniki_mail_history',
        );

    
    return array('stat'=>'ok', 'objects'=>$objects);
}
?>
