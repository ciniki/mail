<?php
//
// Description
// -----------
// This method will setup emails to the list of customer_ids provided.
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_mail_customerListSend(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'customer_ids'=>array('required'=>'yes', 'blank'=>'no', 'type'=>'idlist', 'name'=>'Customers'), 
        'subject'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Subject'), 
        'text_content'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Message'), 
        'object'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Object'), 
        'object_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Object ID'), 
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];

    //  
    // Make sure this module is activated, and
    // check permission to run this function for this tenant
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'mail', 'private', 'checkAccess');
    $rc = ciniki_mail_checkAccess($ciniki, $args['tnid'], 'ciniki.mail.customerListSend'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }

    //
    // Make sure required fields are filled in, may become args later
    //
    if( !isset($args['flags']) ) {
        $args['flags'] = '0';
    }
    if( !isset($args['status']) ) {
        $args['status'] = '10';
    }
   
    //
    // Get the settings for the mail module
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'mail', 'private', 'getSettings');
    $rc = ciniki_mail_getSettings($ciniki, $args['tnid']); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $settings = $rc['settings'];

    //
    // Get the web tenant settings to include in email
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'web', 'details');
    $rc = ciniki_tenants_web_details($ciniki, $args['tnid']); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $tenant_details = $rc['details'];

    //
    // Check for both html and text content
    //
    if( !isset($args['text_content']) && !isset($args['html_content']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.mail.4', 'msg'=>'No message specified'));
    } elseif( isset($args['html_content']) && !isset($args['text_content']) ) {
        $args['text_content'] = strip_tags($args['html_content']);
    } elseif( isset($args['text_content']) && !isset($args['html_content']) ) {
        $args['html_content'] = $args['text_content'];
    }

    //
    // load tenant template for formatting
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'mail', 'private', 'loadTenantTemplate');
    $rc = ciniki_mail_loadTenantTemplate($ciniki, $args['tnid'], array(
        'theme'=>(isset($args['theme'])?$args['theme']:''),
        'title'=>(isset($args['title'])?$args['title']:$args['subject']),
        'unsubscribe_url'=>(isset($args['unsubscribe_url'])?$args['unsubscribe_url']:''),
        'unsubscribe_text'=>(isset($args['unsubscribe_text'])?$args['unsubscribe_text']:''),
        'tenant_name'=>$tenant_details['name'],
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $template = $rc['template'];
    $theme = $rc['theme'];

    //
    // Build the message
    //
    $text_content = $template['text_header'];
    $html_content = $template['html_header'];

    //
    // Add the text content
    //
    $text_content .= $args['text_content'];

    //
    // Process the html email content to format
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'mail', 'private', 'emailProcessContent');
    $rc = ciniki_mail_emailProcessContent($ciniki, $args['tnid'], $theme, $args['html_content']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $html_content .= "<tr><td style='" . $theme['td_body'] . "'>" . $rc['content'] . "</td></tr>";

    //
    // Add disclaimer if set
    //
    if( isset($settings['message-disclaimer']) && $settings['message-disclaimer'] != '' ) {
        $html_content .= "<tr><td style='" . $theme['td_body'] . "'><p style='" . $theme['p'] . "'>" . $settings['message-disclaimer'] . "</p></td></tr>";
        $text_content .= "\n\n" . $settings['message-disclaimer'];
    }

    //
    // Add the footer
    //
    $text_content .= $template['text_footer'];
    $html_content .= $template['html_footer'];

    //
    // Load the customer names and emails
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'hooks', 'customersEmails');
    $rc = ciniki_customers_hooks_customersEmails($ciniki, $args['tnid'], $args);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.mail.82', 'msg'=>'Unable to lookup customers', 'err'=>$rc['err']));
    }
    $customers = isset($rc['customers']) ? $rc['customers'] : array();

    //
    // Loop through the customers
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'mail', 'private', 'createCustomerMail');
    foreach($customers as $customer) {
    
        //
        // Send to each email address attached to user to make sure they get it
        //
        if( isset($customer['emails']) ) {
            foreach($customer['emails'] as $email) {
                //
                // Send email
                //
                $rc = ciniki_mail_createCustomerMail($ciniki, $args['tnid'], $settings, $email, 
                    $args['subject'], $html_content, $text_content, array(
                        'object' => $args['object'],
                        'object_id' => $args['object_id'],
                    ));
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.mail.84', 'msg'=>'Unable to send message to customer', 'err'=>$rc['err']));
                }

                $ciniki['emailqueue'][] = array('mail_id'=>$rc['id'], 'tnid'=>$args['tnid']);
                
            }
        }
    }

    return array('stat'=>'ok');
}
?>
