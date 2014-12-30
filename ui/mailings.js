//
function ciniki_mail_mailings() {

	this.subscriptions = {};

	this.themes = {
		'Default':'Default',
		'Simple':'Simple',
		'Black':'Black',
		};
	this.mailingTypes = {
		'10':'General',
		'20':'Newsletter',
		'30':'Alert',
		};

	this.init = function() {
		this.menu = new M.panel('Mailings',
			'ciniki_mail_mailings', 'menu',
			'mc', 'medium', 'sectioned', 'ciniki.mail.mailings.menu');
		this.menu.sections = {
//			'search':{'label':'', 'type':'livesearchgrid', },
			'10':{'label':'Creating', 'visible':'yes', 'type':'simplegrid', 'num_cols':1,
				'cellClasses':[''],
				'addTxt':'Start New Mailing',
				'addFn':'M.ciniki_mail_mailings.showEdit(\'M.ciniki_mail_mailings.showMenu();\',0);',
				},
			'20':{'label':'Approved', 'visible':'no', 'type':'simplegrid', 'num_cols':1,
				'cellClasses':[''],
				},
			'30':{'label':'Queueing', 'visible':'no', 'type':'simplegrid', 'num_cols':1,
				'cellClasses':[''],
				},
			'40':{'label':'Sending', 'visible':'no', 'type':'simplegrid', 'num_cols':1,
				'cellClasses':[''],
				},
			'50':{'label':'Sent', 'visible':'no', 'type':'simplegrid', 'num_cols':1,
				'cellClasses':[''],
				},
			'_buttons':{'label':'', 'buttons':{
				'download':{'label':'Download Survey Results', 'visible':'no', 'fn':'M.ciniki_mail_mailings.downloadAllResults();'},
			}},
		};
		this.menu.sectionData = function(s) {
			return this.data[s];
		};
		this.menu.cellValue = function(s, i, j, d) {
			return d.mailing.subject;
		};
		this.menu.rowFn = function(s, i, d) {
			return 'M.ciniki_mail_mailings.showMailing(\'M.ciniki_mail_mailings.showMenu();\',\'' + d.mailing.id + '\');';
		};
		this.menu.addButton('add', 'Add', 'M.ciniki_mail_mailings.showEdit(\'M.ciniki_mail_mailings.showMenu();\',0);');
		this.menu.addClose('Back');

		//
		// The mailing panel, which displays the info about the mailing, and the action buttons
		//
		this.mailing = new M.panel('Mailing',
			'ciniki_mail_mailings', 'mailing',
			'mc', 'medium', 'sectioned', 'ciniki.mail.mailings.edit');
		this.mailing.mailing_id = 0;
		this.mailing.sections = {
			'details':{'label':'', 'list':{	
				'status_text':{'label':'Status', 'history':'yes'},
				'theme':{'label':'Theme'},
				'subject':{'label':'Subject'},
				'subscription_names':{'label':'Subscriptions'},
			}},
			'text_content':{'label':'Message', 'type':'htmlcontent'},
			'survey':{'label':'', 'list':{	
				'survey_name':{'label':'Survey'},
			}},
			'_buttons':{'label':'', 'buttons':{
				'edit':{'label':'Edit', 'fn':'M.ciniki_mail_mailings.showEdit(\'M.ciniki_mail_mailings.showMailing()\',M.ciniki_mail_mailings.mailing.mailing_id);'},
				'test':{'label':'Send Test Message', 'fn':'M.ciniki_mail_mailings.sendTest(\'M.ciniki_mail_mailings.showMailing()\',M.ciniki_mail_mailings.mailing.mailing_id);'},
				'send':{'label':'Send', 'fn':'M.ciniki_mail_mailings.sendMailing(\'M.ciniki_mail_mailings.showMailing()\',M.ciniki_mail_mailings.mailing.mailing_id);'},
				'download':{'label':'Download Survey Results', 'fn':'M.ciniki_mail_mailings.downloadSurveyMailingResults(M.ciniki_mail_mailings.mailing.survey_id,M.ciniki_mail_mailings.mailing.mailing_id);'},
			}},
		};
		this.mailing.listLabel = function(s, i, d) {
			switch (s) {
				case 'details': return d.label;
				case 'survey': return d.label;
			}
		};
		this.mailing.listValue = function(s, i, d) {
			if( i == 'theme' ) { 
				return M.ciniki_mail_mailings.themes[this.data.theme];
			}
			return this.data[i];
		};
		this.mailing.listFn = function(s, i, d) {
			if( s == 'survey' && this.data['survey_id'] > 0 ) {
				return 'M.startApp(\'ciniki.surveys.main\',null,\'M.ciniki_mail_mailings.showMailing();\',\'mc\',{\'survey_id\':\'' + this.data['survey_id'] + '\'});';
			}
			return null;
		};
		this.mailing.sectionData = function(s) {
			if( s == 'text_content' ) {
				return this.data[s].replace(/\n/g, '<br/>');
			}
			return this.sections[s].list;
		};
//		this.mailing.addButton('edit', 'Edit', 'M.ciniki_mail_mailings.showEdit(\'M.ciniki_mail_mailings.showMailing()\',M.ciniki_mail_mailings.mailing.mailing_id);');
		this.mailing.addClose('Back');

		//
		// The main panel, which lists the options for production
		//
		this.edit = new M.panel('Mailing',
			'ciniki_mail_mailings', 'edit',
			'mc', 'medium', 'sectioned', 'ciniki.mail.mailings.edit');
		this.edit.mailing_id = 0;
		this.edit.data = {};
		this.edit.default_data = {'type':'30', 'survey_id':0};
		this.edit.sections = {
			'details':{'label':'', 'fields':{	
				'type':{'label':'Type', 'type':'select', 'options':this.mailingTypes},
				'theme':{'label':'Theme', 'type':'select', 'options':this.themes},
				'subject':{'label':'Subject', 'type':'text'},
			}},
			'_msg':{'label':'Message', 'fields':{
				'text_content':{'label':'', 'hidelabel':'yes', 'type':'textarea'},
			}},
			'survey':{'label':'', 'visible':'no', 'fields':{
				'survey_id':{'label':'Survey', 'active':'no', 'type':'select', 'options':this.availableSurveys},
			}},
			'_subscriptions':{'label':'Subscriptions', 'fields':{
				'subscription_ids':{'label':'', 'hidelabel':'yes', 'type':'multiselect',
					'toggle':'yes', 'none':'yes', 'joined':'no', 'history':'no', 'options':this.subscriptions},
			}},
			'_buttons':{'label':'', 'buttons':{
				'save':{'label':'Save', 'fn':'M.ciniki_mail_mailings.saveMailing();'},
			}},
		};
		this.edit.fieldValue = function(s, i, d) { 
			return this.data[i];
		};
		this.edit.fieldHistoryArgs = function(s, i) {
			return {'method':'ciniki.mail.mailingHistory', 'args':{'business_id':M.curBusinessID, 
				'mailing_id':this.mailing_id, 'field':i}};
		};
		this.edit.addButton('save', 'Save', 'M.ciniki_mail_mailings.saveMailing();');
		this.edit.addClose('Cancel');
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
		var appContainer = M.createContainer(appPrefix, 'ciniki_mail_mailings', 'yes');
		if( appContainer == null ) {
			alert('App Error');
			return false;
		} 
	
		//
		// Check if alerts is enabled 
		//
		if( M.curBusiness.modules['ciniki.mail'].flags != null && (M.curBusiness.modules['ciniki.mail'].flags&0x02) == 0x02 ) {
			this.edit.sections.details.fields.type.options = {
				'10':'General',
				'20':'Newsletter',
				'30':'Alert',
			};
		} else {
			this.edit.sections.details.fields.type.options = {
				'10':'General',
				'20':'Newsletter',
			};
		}

		//
		// Check if surveys active
		//
		if( M.curBusiness.modules['ciniki.surveys'] != null ) {
			this.menu.sections._buttons.buttons.download.visible = 'yes';
		} else {
			this.menu.sections._buttons.buttons.download.visible = 'no';
		}

		//
		// Load the subscriptions available
		//
		var rsp = M.api.getJSONCb('ciniki.subscriptions.list', 
			{'business_id':M.curBusinessID}, function(rsp) {
				if( rsp.stat != 'ok' ) {
					M.api.err(rsp);
					return false;
				}
				M.ciniki_mail_mailings.subscriptions = {};
				for(i in rsp.subscriptions) {
					M.ciniki_mail_mailings.subscriptions[rsp.subscriptions[i].subscription.id] = rsp.subscriptions[i].subscription.name;
				}
				M.ciniki_mail_mailings.edit.sections._subscriptions.fields.subscription_ids.options = M.ciniki_mail_mailings.subscriptions;

				M.ciniki_mail_mailings.edit.mailing_id = 0;
				M.ciniki_mail_mailings.showMenu(cb);
			});
	}

	//
	// Grab the stats for the business from the database and present the list of orders.
	//
	this.showMenu = function(cb) {
		var rsp = M.api.getJSONCb('ciniki.mail.mailingListByStatus', 
			{'business_id':M.curBusinessID}, function(rsp) {
				if( rsp.stat != 'ok' ) {
					M.api.err(rsp);
					return false;
				}
				var p = M.ciniki_mail_mailings.menu;
				p.data = {};
				p.sections[10].visible = 'yes';
				p.sections[20].visible = 'no';
				p.sections[30].visible = 'no';
				p.sections[40].visible = 'no';
				p.sections[50].visible = 'no';
				for(i in rsp.statuses) {
					if( p.sections[rsp.statuses[i].status.id] != null ) {
						p.data[rsp.statuses[i].status.id] = rsp.statuses[i].status.mailings;
						p.sections[rsp.statuses[i].status.id].visible = 'yes';
					}
				}
				p.refresh();
				p.show(cb);
			});
	};

	this.showMailing = function(cb, mid) {
		if( mid != null ) {
			this.mailing.mailing_id = mid;
		}
		var rsp = M.api.getJSONCb('ciniki.mail.mailingGet', 
			{'business_id':M.curBusinessID, 'mailing_id':this.mailing.mailing_id}, function(rsp) {
				if( rsp.stat != 'ok' ) {
					M.api.err(rsp);
					return false;
				}
				var p = M.ciniki_mail_mailings.mailing;
				p.data = rsp.mailing;
				p.survey_id = rsp.mailing.survey_id;
				if( rsp.mailing.status == '10' ) {
					p.sections._buttons.buttons.edit.visible = 'yes';
				} else {
					p.sections._buttons.buttons.edit.visible = 'no';
				}
				if( rsp.mailing.status == '10' || rsp.mailing.status == '20' ) {
					p.sections._buttons.buttons.send.visible = 'yes';
				} else {
					p.sections._buttons.buttons.send.visible = 'no';
				}
				if( rsp.mailing.status > 20 ) {
					p.sections._buttons.buttons.download.visible = 'yes';
				} else {
					p.sections._buttons.buttons.download.visible = 'no';
				} 
				p.refresh();
				p.show(cb);
			});
	};

	this.showEdit = function(cb, mid) {
		if( mid != null ) {
			this.edit.mailing_id = mid;
		}
		if( this.edit.mailing_id > 0 ) {
			var rsp = M.api.getJSONCb('ciniki.mail.mailingGet', 
				{'business_id':M.curBusinessID, 'mailing_id':this.edit.mailing_id}, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					M.ciniki_mail_mailings.edit.data = rsp.mailing;
					M.ciniki_mail_mailings.showEditFinish(cb);
				});
		} else {
			this.edit.data = this.edit.default_data;
			this.showEditFinish(cb);
		}
	};

	this.showEditFinish = function(cb) {
		if( (this.edit.data.theme == null || this.edit.data.theme == '') 
			&& M.curBusiness.mail != null && M.curBusiness.mail.settings != null && M.curBusiness.mail.settings['mail-default-theme'] != null ) {
			this.edit.data.theme = M.curBusiness.mail.settings['mail-default-theme'];
		}
		if( M.curBusiness.modules['ciniki.surveys'] != null ) {
			this.edit.sections.survey.visible = 'yes';
			this.edit.sections.survey.fields.survey_id.active = 'yes';
			this.edit.sections.survey.fields.survey_id.options = {'0':'None'};
			// Load surveys which are in status open
			var rsp = M.api.getJSONCb('ciniki.surveys.surveyActiveList', 
				{'business_id':M.curBusinessID, 'survey_id':this.edit.data.survey_id}, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					var p = M.ciniki_mail_mailings.edit;
					if( rsp.surveys != null && rsp.surveys.length > 0 ) {
						for(i in rsp.surveys) {
							p.sections.survey.fields.survey_id.options[rsp.surveys[i].survey.id] = rsp.surveys[i].survey.name;
						}
					} else {
						p.sections.survey.visible = 'no';
						p.sections.survey.fields.survey_id.active = 'no';

					}
					p.refresh();
					p.show(cb);
				});
		} else {
			this.edit.sections.survey.visible = 'no';
			this.edit.sections.survey.fields.survey_id.active = 'no';
			this.edit.refresh();
			this.edit.show(cb);
		}
	};

	this.saveMailing = function() {
		if( this.edit.mailing_id > 0 ) {
			var c = this.edit.serializeForm('no');
			if( c != '' ) {
				var rsp = M.api.postJSONCb('ciniki.mail.mailingUpdate', 
					{'business_id':M.curBusinessID, 'mailing_id':this.edit.mailing_id}, c, function(rsp) {
						if( rsp.stat != 'ok' ) {
							M.api.err(rsp);
							return false;
						} 
						M.ciniki_mail_mailings.edit.close();
					});
			}
		} else {
			var c = this.edit.serializeForm('yes');
			var rsp = M.api.postJSONCb('ciniki.mail.mailingAdd', 
				{'business_id':M.curBusinessID}, c, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					} 
					M.ciniki_mail_mailings.edit.close();
				});
		}
	}

	this.sendMailing = function() {
		if( confirm('Are you sure the message is correct and ready to send?') ) {
			var rsp = M.api.getJSONCb('ciniki.mail.mailingSend', 
				{'business_id':M.curBusinessID, 'mailing_id':M.ciniki_mail_mailings.mailing.mailing_id}, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					M.stopLoad();
					alert('The emails are being delivered');
					M.ciniki_mail_mailings.mailing.close();
				});
		}
	};

	this.sendTest = function() {
		var rsp = M.api.getJSONCb('ciniki.mail.mailingSend', 
			{'business_id':M.curBusinessID, 'mailing_id':M.ciniki_mail_mailings.mailing.mailing_id, 'test':'yes'}, function() {});
		if( rsp.stat != 'ok' ) {
			M.api.err(rsp);
			return false;
		}
	};

	this.downloadSurveyMailingResults = function(survey_id, mailing_id) {
		window.open(M.api.getUploadURL('ciniki.surveys.downloadXLS',
			{'business_id':M.curBusinessID, 'survey_id':survey_id, 'mailing_id':mailing_id}));
	};

	this.downloadAllResults = function() {
		window.open(M.api.getUploadURL('ciniki.surveys.downloadMailingsXLS', {'business_id':M.curBusinessID}));
	};
}
