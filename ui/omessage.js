//
function ciniki_mail_omessage() {

    this.message = new M.panel('New Message',
        'ciniki_mail_omessage', 'message',
        'mc', 'large narrowaside', 'sectioned', 'ciniki.mail.omessage.message');
    this.message.data = {};
    this.message.customers_removeable = 'no';
    this.message.sections = {
        'customers':{'label':'To', 'type':'simplegrid', 'num_cols':1, 'aside':'yes',
            'cellClasses':['', 'alignright'],
            },
        'details':{'label':'Subject', 'fields':{
            'subject':{'label':'Subject', 'hidelabel':'yes', 'type':'text'},
            }},
        '_content':{'label':'Message', 'fields':{
            'text_content':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'large'},
            }},
        '_file':{'label':'Attach Files', 
            'fields':{
                'attachment1':{'label':'File 1', 'type':'file', 'hidelabel':'no'},
                'attachment2':{'label':'File 2', 'type':'file', 'hidelabel':'no'},
                'attachment3':{'label':'File 3', 'type':'file', 'hidelabel':'no'},
                'attachment4':{'label':'File 4', 'type':'file', 'hidelabel':'no'},
                'attachment5':{'label':'File 5', 'type':'file', 'hidelabel':'no'},
            }},
        '_buttons':{'label':'', 'buttons':{
            'send':{'label':'Send Message', 'fn':'M.ciniki_mail_omessage.message.send();'},
            }},
        };
    this.message.fieldValue = function(s, i, d) {
        return this.data[i];
    }
    this.message.cellValue = function(s, i, j, d) {
        switch(j) {
            case 0: return ''//'<span class="maintext">' 
                + (d.name != null ? d.name : (d.customer_name != null ? d.customer_name : 'Unknown'))
                // + '</span><span class="subtext">' + (d.emails != null ? d.emails : '') + '</span>'
                + '';
            case 1: return '<span class="faicon">&#xf014;</span>&nbsp';
//            case 1: return '<button onclick="M.ciniki_mail_omessage.message.removeCustomer(' + d.id + ');">Remove</button>';
        }
    }
    this.message.cellFn = function(s, i, j, d) {
        if( s == 'customers' && j == 1 ) {
            return 'M.ciniki_mail_omessage.message.removeCustomer(' + d.id + ');';
        }
        return '';
    }
    this.message.open = function(cb, subject, list, object, oid) {
        this.data = {
            'customers':list,
            'subject':(subject != null ? subject : ''),
            'content':'',
            'object':object,
            'object_id':oid,
        }
        this.refresh();
        this.show(cb);
    }
    this.message.removeCustomer = function(cid) {
        for(var i in this.data.customers) {
            if( this.data.customers[i].id == cid ) {   
                delete this.data.customers[i];
            }
        }
        this.refreshSection('customers');
    }
    this.message.send = function() {
        var customer_ids = [];
        for(var i in this.data.customers) {
            if( this.data.customers[i].customer_id != null ) {
                customer_ids.push(this.data.customers[i].customer_id);
            } else if( this.data.customers[i].id != null ) {
                customer_ids.push(this.data.customers[i].id);
            }
        }
        var c = this.serializeFormData('yes');
        var args = {
            'tnid':M.curTenantID,
            'customer_ids':customer_ids.join(),
            'object':this.data.object,
            'object_id':this.data.object_id,
            };
        M.api.postJSONCb('ciniki.mail.customerListSend', args, c, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            } 
            M.ciniki_mail_omessage.message.close();
        });
    }
    this.message.addClose('Back');

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
        var appContainer = M.createContainer(appPrefix, 'ciniki_mail_omessage', 'yes');
        if( appContainer == null ) {
            M.alert('App Error');
            return false;
        } 
        if( args.removeable != null && args.removeable == 'yes' ) {
            this.message.sections.customers.num_cols = 2;
        } else {
            this.message.sections.customers.num_cols = 1;
        }
        
        this.message.open(cb, args.subject, args.list, args.object, args.object_id);
    }
}
