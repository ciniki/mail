//
// The settings for the mail module
//
function ciniki_mail_settings() {
    //
    // The main panel, which lists the options for production
    //
    this.main = new M.panel('Settings',
        'ciniki_mail_settings', 'main',
        'mc', 'medium', 'sectioned', 'ciniki.mail.settings.main');
    this.main.sections = {
        '_tabs':{'label':'', 'type':'paneltabs', 'selected':'smtp',
//            'visible':function() { return ((M.userPerms&0x01) == 1 ? 'yes' : 'no'); },
            'tabs':{
                'smtp':{'label':'SMTP', 'fn':'M.ciniki_mail_settings.main.switchTab(\'smtp\');'},
                'mailgun':{'label':'Mailgun', 'fn':'M.ciniki_mail_settings.main.switchTab(\'mailgun\');'},
                'styles':{'label':'Styles', 
                    'visible':function() { return ((M.userPerms&0x01) == 1 ? 'yes' : 'no'); },
                    'fn':'M.ciniki_mail_settings.main.switchTab(\'styles\');'},
            }},
        'smtp':{'label':'SMTP', 
            'visible':function() { return (M.ciniki_mail_settings.main.sections._tabs.selected == 'smtp' ? 'yes' : 'hidden'); },
            'fields':{
                'smtp-servers':{'label':'Servers', 'type':'text'},
                'smtp-username':{'label':'Username', 'type':'text'},
                'smtp-password':{'label':'Password', 'type':'text'},
                'smtp-secure':{'label':'Security', 'type':'text', 'size':'small', 'hint':'tls or ssl'},
                'smtp-port':{'label':'Port', 'type':'text', 'size':'small'},
            }},
        'mailgun':{'label':'Mailgun', 
            'visible':function() { return (M.ciniki_mail_settings.main.sections._tabs.selected == 'mailgun' ? 'yes' : 'hidden'); },
            'fields':{
                'mailgun-domain':{'label':'Domain', 'type':'text'},
                'mailgun-key':{'label':'Key', 'type':'text'},
            }},
        'smtp-from':{'label':'Send Email As', 
            'visible':function() { var s=M.ciniki_mail_settings.main.sections._tabs.selected; return (s == 'smtp' || s == 'mailgun' ? 'yes' : 'hidden'); },
            'fields':{
                'smtp-from-name':{'label':'Name', 'type':'text'},
                'smtp-from-address':{'label':'Address', 'type':'email'},
            }},
        'throttling':{'label':'Sending Limits', 
            'visible':function() { var s=M.ciniki_mail_settings.main.sections._tabs.selected; return (s == 'smtp' || s == 'mailgun' ? 'yes' : 'hidden'); },
            'fields':{
                'smtp-5min-limit':{'label':'5 Minutes', 'type':'text', 'size':'small'},
            }},
//          'theme':{'label':'Options', 'fields':{
//              'mail-default-theme':{'label':'Theme', 'type':'select', 'options':this.themes},
//          }},
        '_disclaimer':{'label':'Disclaimer', 
            'visible':function() { var s=M.ciniki_mail_settings.main.sections._tabs.selected; return (s == 'smtp' || s == 'mailgun' ? 'yes' : 'hidden'); },
            'fields':{
                'message-disclaimer':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'small'},
            }},
        'header_styles':{'label':'Mail Header Style', 
            'visible':function() { return (M.ciniki_mail_settings.main.sections._tabs.selected == 'styles' ? 'yes' : 'hidden'); },
            'fields':{
                'message-style-header_style':{'label':'Header', 'hidelabel':'yes', 'type':'textarea'},
            }},
        'content_styles':{'label':'Mail Styles', 
            'visible':function() { return (M.ciniki_mail_settings.main.sections._tabs.selected == 'styles' ? 'yes' : 'hidden'); },
            'fields':{
                'message-style-wrapper_style':{'label':'Wrapper', 'type':'text'},
                'message-style-title_style':{'label':'Title', 'type':'text'},
                'message-style-subtitle_style':{'label':'Sub-Title', 'type':'text'},
                'message-style-logo_style':{'label':'Logo', 'type':'text'},
                'message-style-a':{'label':'A', 'type':'text'},
                'message-style-p':{'label':'P', 'type':'text'},
                'message-style-p_footer':{'label':'P Footer', 'type':'text'},
                'message-style-td_footer':{'label':'TD Footer', 'type':'text'},
                'message-style-a_footer':{'label':'A Footer', 'type':'text'},
                'message-style-td_header':{'label':'TD Header', 'type':'text'},
                'message-style-td_body':{'label':'TD Body', 'type':'text'},
                'message-style-h1':{'label':'H1', 'type':'text'},
                'message-style-h2':{'label':'H2', 'type':'text'},
                'message-style-h3':{'label':'H3', 'type':'text'},
                'message-style-h4':{'label':'H4', 'type':'text'},
                'message-style-image_wrap':{'label':'Image Wrap', 'type':'text'},
                'message-style-image':{'label':'Image', 'type':'text'},
                'message-style-img':{'label':'Img', 'type':'text'},
                'message-style-image_caption':{'label':'Image Caption', 'type':'text'},
                'message-style-file_description':{'label':'File Description', 'type':'text'},
                'message-style-image_gallery':{'label':'Image Gallery', 'type':'text'},
                'message-style-image_gallery_thumbnail':{'label':'Image Gallery Thumbnail', 'type':'text'},
                'message-style-image_gallery_thumbnail_img':{'label':'Image Gallery Thumbnail img', 'type':'text'},
                'message-style-linkback':{'label':'Linkback', 'type':'text'},
                'message-style-table':{'label':'Table', 'type':'text'},
                'message-style-td':{'label':'TD', 'type':'text'},
                'message-style-th':{'label':'TH', 'type':'text'},
            }},
        'footer_links':{'label':'Footer Links', 
            'visible':function() { return (M.ciniki_mail_settings.main.sections._tabs.selected == 'styles' ? 'yes' : 'hidden'); },
            'fields':{
                'footer-link-1-name':{'label':'Name 1', 'type':'text'},
                'footer-link-1-url':{'label':'URL 1', 'type':'text'},
                'footer-link-2-name':{'label':'Name 2', 'type':'text'},
                'footer-link-2-url':{'label':'URL 2', 'type':'text'},
                'footer-link-3-name':{'label':'Name 3', 'type':'text'},
                'footer-link-3-url':{'label':'URL 3', 'type':'text'},
                'footer-link-4-name':{'label':'Name 4', 'type':'text'},
                'footer-link-4-url':{'label':'URL 4', 'type':'text'},
                'footer-link-5-name':{'label':'Name 5', 'type':'text'},
                'footer-link-5-url':{'label':'URL 5', 'type':'text'},
            }},
        '_buttons':{'label':'', 'buttons':{
            'test':{'label':'Send Test Message', 'fn':'M.ciniki_mail_settings.main.sendTest();'},
            'save':{'label':'Save', 'fn':'M.ciniki_mail_settings.main.save();'},
        }},
    };
    this.main.fieldValue = function(s, i, d) { 
        return this.data[i];
    };
    this.main.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.mail.settingsHistory', 'args':{'tnid':M.curTenantID, 'setting':i}};
    };
    this.main.switchTab = function(tab) {
        this.sections._tabs.selected = tab;
        this.refreshSection('_tabs');
        this.showHideSection('smtp');
        this.showHideSection('mailgun');
        this.showHideSection('smtp-from');
        this.showHideSection('throttling');
        this.showHideSection('_disclaimer');
        this.showHideSection('header_styles');
        this.showHideSection('content_styles');
        this.showHideSection('footer_links');
    };
    this.main.open = function(cb) {
        M.api.getJSONCb('ciniki.mail.settingsGet', {'tnid':M.curTenantID}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_mail_settings.main;
            p.data = rsp.settings;
            if( rsp.settings['mailgun-key'] != null && rsp.settings['mailgun-key'] != '' ) {
                p.sections._tabs.selected = 'mailgun';
            } else {
                p.sections._tabs.selected = 'smtp';
            }
            p.refresh();
            p.show(cb);
        });
    }
    this.main.save = function() {
        var c = this.serializeForm('no');
        if( c != '' ) {
            M.api.postJSONCb('ciniki.mail.settingsUpdate', {'tnid':M.curTenantID}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                } 
                M.ciniki_mail_settings.main.close();
            });
        } else {
            M.ciniki_mail_settings.main.close();
        }
    }
    this.main.sendTest = function() {
        var c = this.serializeForm('no');
        M.api.postJSONCb('ciniki.mail.settingsUpdate', {'tnid':M.curTenantID, 'sendtest':'yes'}, c, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            } 
            alert('Email sent');
        });
    }
    this.main.addButton('save', 'Save', 'M.ciniki_mail_settings.main.save();');
    this.main.addButton('test', 'Test', 'M.ciniki_mail_settings.main.sendTest();');
    this.main.addClose('Cancel');

    //
    // Arguments:
    // aG - The arguments to be parsed into args
    //
    this.start = function(cb, appPrefix, aG) {
        args = {};
        if( aG != null ) { args = eval(aG); }

        //
        // Create the app container if it doesn't exist, and clear it out
        // if it does exist.
        //
        var appContainer = M.createContainer(appPrefix, 'ciniki_mail_settings', 'yes');
        if( appContainer == null ) {
            alert('App Error');
            return false;
        } 
        this.main.open(cb);
    }
}
