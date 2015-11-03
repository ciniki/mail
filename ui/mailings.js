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
			'objects-40':{'label':'Sending', 'visible':'no', 'type':'simplegrid', 'num_cols':1,
				'cellClasses':[''],
				},
			'objects-50':{'label':'Sent', 'visible':'no', 'type':'simplegrid', 'num_cols':1,
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
			if( d.mailing.type == 40 ) {
				switch (d.mailing.object) {
					case 'ciniki.blog.post': return 'M.startApp(\'ciniki.blog.post\',null,\'M.ciniki_mail_mailings.showMenu();\',\'mc\',{\'post_id\':\'' + d.mailing.object_id + '\'});';
				}
			}
			return 'M.ciniki_mail_mailings.showMailing(\'M.ciniki_mail_mailings.showMenu();\',\'' + d.mailing.id + '\');';
		};
		this.menu.addButton('add', 'Add', 'M.ciniki_mail_mailings.showEdit(\'M.ciniki_mail_mailings.showMenu();\',0);');
		this.menu.addClose('Back');

		//
		// The mailing panel, which displays the info about the mailing, and the action buttons
		//
		this.mailing = new M.panel('Mailing',
			'ciniki_mail_mailings', 'mailing',
			'mc', 'medium mediumaside', 'sectioned', 'ciniki.mail.mailings.edit');
		this.mailing.mailing_id = 0;
		this.mailing.sections = {
			'details':{'label':'', 'aside':'yes', 'list':{	
				'status_text':{'label':'Status', 'history':'yes'},
//				'theme':{'label':'Theme'},
				'subject':{'label':'Subject'},
				'subscription_names':{'label':'Subscriptions'},
			}},
//			'text_content':{'label':'Message', 'type':'htmlcontent'},
			'_image':{'label':'', 'aside':'yes', 'type':'imageform', 'fields':{
				'primary_image_id':{'label':'', 'type':'image_id', 'hidelabel':'yes', 'history':'no'},
			}},
			'html_content':{'label':'Message', 'type':'htmlcontent'},
			'images':{'label':'Additional Images', 'type':'simplethumbs'},
			'_images':{'label':'', 'type':'simplegrid', 'num_cols':1,
				'addTxt':'Add Additional Image',
				'addFn':'M.startApp(\'ciniki.mail.mailingimages\',null,\'M.ciniki_mail_mailings.showMailing();\',\'mc\',{\'mailing_id\':M.ciniki_mail_mailings.mailing.mailing_id,\'add\':\'yes\'});',
			},
			'survey':{'label':'', 'visible':'no', 'list':{	
				'survey_name':{'label':'Survey'},
			}},
			'_buttons':{'label':'', 'buttons':{
				'edit':{'label':'Edit', 'fn':'M.ciniki_mail_mailings.showEdit(\'M.ciniki_mail_mailings.showMailing()\',M.ciniki_mail_mailings.mailing.mailing_id);'},
				'test':{'label':'Send Test Message', 'fn':'M.ciniki_mail_mailings.sendTest(\'M.ciniki_mail_mailings.showMailing()\',M.ciniki_mail_mailings.mailing.mailing_id);'},
				'send':{'label':'Send', 'fn':'M.ciniki_mail_mailings.sendMailing(\'M.ciniki_mail_mailings.showMailing()\',M.ciniki_mail_mailings.mailing.mailing_id);'},
				'download':{'label':'Download Survey Results', 'fn':'M.ciniki_mail_mailings.downloadSurveyMailingResults(M.ciniki_mail_mailings.mailing.survey_id,M.ciniki_mail_mailings.mailing.mailing_id);'},
				'delete':{'label':'Delete', 'visible':'yes', 'fn':'M.ciniki_mail_mailings.mailingDelete();'},
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
			if( s == 'html_content' ) {
				return this.data[s].replace(/\n/g, '<br/>');
			}
			if( s == 'images' ) {
				return this.data[s];
			}
			return this.sections[s].list;
		};
		this.mailing.addDropImage = function(iid) {
			var rsp = M.api.getJSON('ciniki.mail.mailingImageAdd',
				{'business_id':M.curBusinessID, 'image_id':iid, 'mailing_id':M.ciniki_mail_mailings.mailing.mailing_id});
			if( rsp.stat != 'ok' ) {
				M.api.err(rsp);
				return false;
			}
			return true;
		};
		this.mailing.addDropImageRefresh = function() {
			if( M.ciniki_mail_mailings.mailing.mailing_id > 0 ) {
				var rsp = M.api.getJSONCb('ciniki.mail.mailingGet', {'business_id':M.curBusinessID, 
					'mailing_id':M.ciniki_mail_mailings.mailing.mailing_id, 'images':'yes'}, function(rsp) {
						if( rsp.stat != 'ok' ) {
							M.api.err(rsp);
							return false;
						}
						M.ciniki_mail_mailings.mailing.data.images = rsp.mailing.images;
						M.ciniki_mail_mailings.mailing.refreshSection('images');
					});
			}
		};
		this.mailing.fieldValue = function(s, i, d) { 
			return this.data[i];
		};
		this.mailing.thumbFn = function(s, i, d) {
			return 'M.startApp(\'ciniki.mail.mailingimages\',null,\'M.ciniki_mail_mailings.showMailing();\',\'mc\',{\'mailing_image_id\':\'' + d.image.id + '\'});';
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
				'type':{'label':'Type', 'active':'no', 'type':'select', 'options':this.mailingTypes},
//				'theme':{'label':'Theme', 'type':'select', 'options':this.themes},
				'subject':{'label':'Subject', 'type':'text'},
			}},
			'_image':{'label':'', 'type':'imageform', 'fields':{
				'primary_image_id':{'label':'', 'type':'image_id', 'hidelabel':'yes', 'history':'no', 'controls':'all'},
			}},
			'_msg':{'label':'Message', 'fields':{
				'html_content':{'label':'', 'hidelabel':'yes', 'type':'textarea'},
			}},
			'survey':{'label':'', 'active':'no', 'fields':{
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
		this.edit.addDropImage = function(iid) {
			M.ciniki_mail_mailings.edit.setFieldValue('primary_image_id', iid, null, null);
			return true;
		};
		this.edit.deleteImage = function(fid) {
			this.setFieldValue(fid, 0, null, null);
			return true;
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
			this.edit.sections.details.fields.type.active = 'yes';
		} else {
			this.edit.sections.details.fields.type.active = 'no';
		}

		//
		// Check if surveys active
		//
		if( M.curBusiness.modules['ciniki.surveys'] != null ) {
			this.menu.sections._buttons.buttons.download.visible = 'yes';
			this.mailing.sections.survey.visible = 'yes';
			this.edit.sections.survey.active = 'yes';
			this.mailing.sections._buttons.buttons.download.visible = 'yes';
		} else {
			this.menu.sections._buttons.buttons.download.visible = 'no';
			this.mailing.sections.survey.visible = 'no';
			this.edit.sections.survey.active = 'no';
			this.mailing.sections._buttons.buttons.download.visible = 'no';
		}

		//
		// Load the subscriptions available
		//
		M.api.getJSONCb('ciniki.subscriptions.subscriptionList', 
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
	
				if( args.add != null && args.add == 'yes' && args.object != null && args.object_id != null ) {
					M.ciniki_mail_mailings.createFromObject(cb, args.object, args.object_id);
				} else {
					M.ciniki_mail_mailings.edit.mailing_id = 0;
					M.ciniki_mail_mailings.showMenu(cb);
				}
			});
	}

	//
	// Create new mailing from object
	//
	this.createFromObject = function(cb, obj, oid) {
		M.api.getJSONCb('ciniki.mail.mailingAddFromObject', 
			{'business_id':M.curBusinessID, 'object':obj, 'object_id':oid}, function(rsp) {
				if( rsp.stat != 'ok' ) {
					M.api.err(rsp);
					return false;
				}
				M.ciniki_mail_mailings.mailing.mailing_id = rsp.id;
				M.ciniki_mail_mailings.showMailingFinish(cb, rsp.mailing);
			});
	};

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
				p.sections['objects-40'].visible = 'no';
				p.sections['objects-50'].visible = 'no';
				for(i in rsp.statuses) {
					if( p.sections[rsp.statuses[i].status.id] != null ) {
						p.data[rsp.statuses[i].status.id] = rsp.statuses[i].status.mailings;
						p.sections[rsp.statuses[i].status.id].visible = 'yes';
					}
				}
				if( rsp.object_statuses != null ) {
					for(i in rsp.object_statuses) {
						if( p.sections['objects-' + rsp.object_statuses[i].status.id] != null ) {
							p.data['objects-' + rsp.object_statuses[i].status.id] = rsp.object_statuses[i].status.mailings;
							p.sections['objects-' + rsp.object_statuses[i].status.id].visible = 'yes';
						}
					}
				}
				p.refresh();
				p.show(cb);
			});
	};

	this.showMailing = function(cb, mid) {
		if( mid != null ) { this.mailing.mailing_id = mid; }
		M.api.getJSONCb('ciniki.mail.mailingGet', 
			{'business_id':M.curBusinessID, 'mailing_id':this.mailing.mailing_id, 'images':'yes'}, function(rsp) {
				if( rsp.stat != 'ok' ) {
					M.api.err(rsp);
					return false;
				}
				M.ciniki_mail_mailings.showMailingFinish(cb, rsp.mailing);
			});
	};

	this.showMailingFinish = function(cb, mailing) {
		var p = M.ciniki_mail_mailings.mailing;
		p.data = mailing;
		p.survey_id = mailing.survey_id;
		if( mailing.status == '10' ) {
			p.sections._buttons.buttons.edit.visible = 'yes';
		} else {
			p.sections._buttons.buttons.edit.visible = 'no';
		}
		if( mailing.status == '10' || mailing.status == '20' ) {
			p.sections._buttons.buttons.send.visible = 'yes';
		} else {
			p.sections._buttons.buttons.send.visible = 'no';
		}
		if( mailing.status > 20 && M.curBusiness.modules['ciniki.surveys'] != null ) {
			p.sections._buttons.buttons.download.visible = 'yes';
		} else {
			p.sections._buttons.buttons.download.visible = 'no';
		} 
		p.refresh();
		p.show(cb);
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
			} else {
				this.edit.close();
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
		M.api.getJSONCb('ciniki.mail.mailingSend', 
			{'business_id':M.curBusinessID, 'mailing_id':M.ciniki_mail_mailings.mailing.mailing_id, 'test':'yes'}, function(rsp) {
				if( rsp.stat != 'ok' ) {
					M.api.err(rsp);
					return false;
				}
				alert('Email sent, please check your email');
			});
	};

	this.downloadSurveyMailingResults = function(survey_id, mailing_id) {
		M.api.openFile('ciniki.surveys.downloadXLS',
			{'business_id':M.curBusinessID, 'survey_id':survey_id, 'mailing_id':mailing_id});
	};

	this.downloadAllResults = function() {
		M.api.openFile('ciniki.surveys.downloadMailingsXLS', {'business_id':M.curBusinessID});
	};

	this.mailingDelete = function() {
		var msg = "Are you sure you want to remove this mailing?";
		if( this.mailing.data.status > 10 ) {
			msg = "Are you sure you want to remove this mailing? \n\n**WARNING** This will remote all sent messages, tracking information and images. Any users opening the email will not see the images or files.";
		}
		if( confirm(msg) ) {
			var rsp = M.api.getJSONCb('ciniki.mail.mailingDelete', 
				{'business_id':M.curBusinessID, 'mailing_id':M.ciniki_mail_mailings.mailing.mailing_id}, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					M.ciniki_mail_mailings.mailing.close();
				});
		}
	};
}
