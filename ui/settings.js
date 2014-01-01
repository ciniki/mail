//
function ciniki_mail_settings() {
	//
	// Panels
	//
	this.main = null;
	this.add = null;

	this.cb = null;
	this.toggleOptions = {'off':'Off', 'on':'On'};

	this.themes = {
		'Black':'Blue Titles on Black',
		'Default':'Black Titles on White',
		};

	this.init = function() {
		//
		// The main panel, which lists the options for production
		//
		this.main = new M.panel('Settings',
			'ciniki_mail_settings', 'main',
			'mc', 'medium', 'sectioned', 'ciniki.mail.settings.main');
		this.main.sections = {
			'smtp':{'label':'SMTP', 'fields':{
				'smtp-servers':{'label':'Servers', 'type':'text'},
				'smtp-username':{'label':'Username', 'type':'text'},
				'smtp-password':{'label':'Password', 'type':'text'},
				'smtp-secure':{'label':'Security', 'type':'text', 'size':'small', 'hint':'tls or ssl'},
				'smtp-port':{'label':'Port', 'type':'text', 'size':'small'},
			}},
			'smtp-from':{'label':'Send Email As', 'fields':{
				'smtp-from-name':{'label':'Name', 'type':'text'},
				'smtp-from-address':{'label':'Address', 'type':'email'},
			}},
			'throttling':{'label':'Sending Limits', 'fields':{
				'smtp-5min-limit':{'label':'5 Minutes', 'type':'text', 'size':'small'},
			}},
			'theme':{'label':'Options', 'fields':{
				'mail-default-theme':{'label':'Theme', 'type':'select', 'options':this.themes},
			}},
			'_disclaimer':{'label':'Disclaimer', 'fields':{
				'message-disclaimer':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'small'},
			}},
		};

		this.main.fieldValue = function(s, i, d) { 
			return this.data[i];
		};

		//  
		// Callback for the field history
		//  
		this.main.fieldHistoryArgs = function(s, i) {
			return {'method':'ciniki.mail.settingsHistory', 'args':{'business_id':M.curBusinessID, 'setting':i}};
		};

		this.main.addButton('save', 'Save', 'M.ciniki_mail_settings.saveSettings();');
		this.main.addClose('Cancel');
	}

	//
	// Arguments:
	// aG - The arguments to be parsed into args
	//
	this.start = function(cb, appPrefix, aG) {
		args = {};
		if( aG != null ) {
			args = eval(aG);
		}

		//
		// Create the app container if it doesn't exist, and clear it out
		// if it does exist.
		//
		var appContainer = M.createContainer(appPrefix, 'ciniki_mail_settings', 'yes');
		if( appContainer == null ) {
			alert('App Error');
			return false;
		} 

		this.showMain(cb);
	}

	//
	// Grab the stats for the business from the database and present the list of orders.
	//
	this.showMain = function(cb) {
		var rsp = M.api.getJSON('ciniki.mail.settingsGet', {'business_id':M.curBusinessID});
		if( rsp.stat != 'ok' ) {
			M.api.err(rsp);
			return false;
		}
		this.main.data = rsp.settings;
		this.main.refresh();
		this.main.show(cb);
	}

	this.saveSettings = function() {
		var c = this.main.serializeForm('no');
		if( c != '' ) {
			var rsp = M.api.postJSON('ciniki.mail.settingsUpdate', 
				{'business_id':M.curBusinessID}, c);
			if( rsp.stat != 'ok' ) {
				M.api.err(rsp);
				return false;
			} 
		}
		M.ciniki_mail_settings.main.close();
	}
}
