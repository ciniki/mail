//
// The app to add/edit mail mailing images
//
function ciniki_mail_mailingimages() {
    this.webFlags = {
        '1':{'name':'Hidden'},
        };
    this.init = function() {
        //
        // The panel to display the edit form
        //
        this.edit = new M.panel('Edit Image',
            'ciniki_mail_mailingimages', 'edit',
            'mc', 'medium', 'sectioned', 'ciniki.mail.mailingimages.edit');
        this.edit.default_data = {};
        this.edit.data = {};
        this.edit.mailing_id = 0;
        this.edit.sections = {
            '_image':{'label':'Photo', 'type':'imageform', 'fields':{
                'image_id':{'label':'', 'type':'image_id', 'hidelabel':'yes', 'controls':'all', 'history':'no'},
            }},
//          'info':{'label':'Information', 'type':'simpleform', 'fields':{
//              'name':{'label':'Title', 'type':'text'},
//          }},
//          '_description':{'label':'Description', 'type':'simpleform', 'fields':{
//              'description':{'label':'', 'type':'textarea', 'size':'small', 'hidelabel':'yes'},
//          }},
            '_save':{'label':'', 'buttons':{
                'save':{'label':'Save', 'fn':'M.ciniki_mail_mailingimages.saveImage();'},
                'delete':{'label':'Delete', 'fn':'M.ciniki_mail_mailingimages.deleteImage();'},
            }},
        };
        this.edit.fieldValue = function(s, i, d) { 
            if( this.data[i] != null ) {
                return this.data[i]; 
            } 
            return ''; 
        };
        this.edit.fieldHistoryArgs = function(s, i) {
            return {'method':'ciniki.mail.mailingImageHistory', 'args':{'tnid':M.curTenantID, 
                'mailing_image_id':this.mailing_image_id, 'field':i}};
        };
        this.edit.addDropImage = function(iid) {
            M.ciniki_mail_mailingimages.edit.setFieldValue('image_id', iid, null, null);
            return true;
        };
        this.edit.addButton('save', 'Save', 'M.ciniki_mail_mailingimages.saveImage();');
        this.edit.addClose('Cancel');
    };

    this.start = function(cb, appPrefix, aG) {
        args = {};
        if( aG != null ) { args = eval(aG); }

        //
        // Create container
        //
        var appContainer = M.createContainer(appPrefix, 'ciniki_mail_mailingimages', 'yes');
        if( appContainer == null ) {
            alert('App Error');
            return false;
        }

        if( args.add != null && args.add == 'yes' ) {
            this.showEdit(cb, 0, args.mailing_id);
        } else if( args.mailing_image_id != null && args.mailing_image_id > 0 ) {
            this.showEdit(cb, args.mailing_image_id);
        }
        return false;
    }

    this.showEdit = function(cb, iid, eid) {
        if( iid != null ) {
            this.edit.mailing_image_id = iid;
        }
        if( eid != null ) {
            this.edit.mailing_id = eid;
        }
        if( this.edit.mailing_image_id > 0 ) {
            var rsp = M.api.getJSONCb('ciniki.mail.mailingImageGet', 
                {'tnid':M.curTenantID, 'mailing_image_id':this.edit.mailing_image_id}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    var p = M.ciniki_mail_mailingimages.edit;
                    p.data = rsp.image;
                    p.refresh();
                    p.show(cb);
                });
        } else {
            this.edit.reset();
            this.edit.data = {};
            this.edit.refresh();
            this.edit.show(cb);
        }
    };

    this.saveImage = function() {
        if( this.edit.mailing_image_id > 0 ) {
            var c = this.edit.serializeFormData('no');
            if( c != '' ) {
                var rsp = M.api.postJSONFormData('ciniki.mail.mailingImageUpdate', 
                    {'tnid':M.curTenantID, 
                    'mailing_image_id':this.edit.mailing_image_id}, c,
                        function(rsp) {
                            if( rsp.stat != 'ok' ) {
                                M.api.err(rsp);
                                return false;
                            } else {
                                M.ciniki_mail_mailingimages.edit.close();
                            }
                        });
            } else {
                this.edit.close();
            }
        } else {
            var c = this.edit.serializeFormData('yes');
            var rsp = M.api.postJSONFormData('ciniki.mail.mailingImageAdd', 
                {'tnid':M.curTenantID, 'mailing_id':this.edit.mailing_id}, c,
                    function(rsp) {
                        if( rsp.stat != 'ok' ) {
                            M.api.err(rsp);
                            return false;
                        } else {
                            M.ciniki_mail_mailingimages.edit.close();
                        }
                    });
        }
    };

    this.deleteImage = function() {
        if( confirm('Are you sure you want to delete this image?') ) {
            var rsp = M.api.getJSONCb('ciniki.mail.mailingImageDelete', {'tnid':M.curTenantID, 
                'mailing_image_id':this.edit.mailing_image_id}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    M.ciniki_mail_mailingimages.edit.close();
                });
        }
    };
}
