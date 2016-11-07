//
function ciniki_mail_main() {
    this.page_size = 50;
    this.init = function() {
        this.menu = new M.panel('Mail',
            'ciniki_mail_main', 'menu',
            'mc', 'medium', 'sectioned', 'ciniki.mail.main.menu');
        this.menu.data = {};
        this.menu.label_id = '';
        this.menu.sections = {
            'search':{'label':'', 'type':'livesearchgrid', 'livesearchcols':3, 'hint':'Search messages',
                'noData':'No messages found',
                },
            'labels':{'label':'', 'aside':'yes', 'type':'simplegrid', 'num_cols':1,
                },
            '_mailings':{'label':'', 'aside':'yes', 'list':{
                'mailings':{'label':'Mailings', 'fn':'M.startApp(\'ciniki.mail.mailings\',null,\'M.ciniki_mail_main.menuShow();\');'},
                }},
//          'messages':{'label':'', 'visible':'yes', 'type':'simplegrid', 'num_cols':2,
//              'cellClasses':['multiline', 'multiline'],
//              },
            };
        this.menu.sectionData = function(s) {
            if( s == '_mailings' ) { return this.sections[s].list; }
            return this.data[s];
        };
        this.menu.cellValue = function(s, i, j, d) {
            if( s == 'labels' ) {
                return d.label.name + (d.label.num_messages!=null?' <span class="count">' + d.label.num_messages + '</span>':'');
            } else if( s == 'messages' ) {
                switch(j) {
                    case 0: 
                        if( d.message.status == 40 ) {
                            return '<div class="truncate"><span class="maintext">' + d.message.from_name + '</span><span class="subtext">' + d.message.from_email + '</span></div>';
                        } else {
                            return '<div class="truncate"><span class="maintext">' + d.message.customer_name + '</span><span class="subtext">' + d.message.customer_email + '</span></div>';
                        }
                    case 1: return '<div class="truncate"><span class="maintext">' + d.message.subject + '</span><span class="subtext">' + d.message.snippet + '</span></truncate>';
                    case 2: return '<div class="truncate"><span class="maintext">' + d.message.mail_date + '</span><span class="subtext">' + d.message.mail_time + '</span></truncate>';
                }
            }
        };
        this.menu.rowFn = function(s, i, d) {
            return 'M.ciniki_mail_main.messagesShow(\'M.ciniki_mail_main.menuShow();\',0,\'' + d.label.status + '\');';
        };
        this.menu.liveSearchCb = function(s, i, v) {
            if( v != '' ) {
                M.api.getJSONBgCb('ciniki.mail.messageSearch', {'business_id':M.curBusinessID, 'start_needle':v, 'limit':'15'},
                    function(rsp) {
                        M.ciniki_mail_main.menu.liveSearchShow(s, null, M.gE(M.ciniki_mail_main.menu.panelUID + '_' + s), rsp.messages);
                    });
            }
            return true;
        };
        this.menu.liveSearchResultClass = function(s, f, i, j, d) {
            return 'multiline';
        };
        this.menu.liveSearchResultValue = function(s, f, i, j, d) {
            switch(j) {
                case 0: return '<div class="truncate"><span class="maintext">' + d.message.customer_name + '</span><span class="subtext">' + d.message.customer_email + '</span></div>';
                case 1: return '<div class="truncate"><span class="maintext">' + d.message.subject + '</span><span class="subtext">' + d.message.snippet + '</span></truncate>';
                case 2: return '<div class="truncate"><span class="maintext">' + d.message.status_text + '</span><span class="subtext">' + d.message.mail_date + ' ' + d.message.mail_time + '</span></truncate>';
            }
        };
        this.menu.liveSearchResultRowFn = function(s, f, i, j, d) {
            return 'M.ciniki_mail_main.messageShow(\'M.ciniki_mail_main.menuShow();\', \'' + d.message.id + '\');'; 
        };
        this.menu.liveSearchSubmitFn = function(s, search_str) {
            M.ciniki_mail_main.searchShow('M.ciniki_mail_main.menuShow();', search_str, 0);
        };
//      this.menu.addButton('add', 'Add', 'M.ciniki_mail_mail.messageEdit(\'M.ciniki_mail_main.messagesShow();\');');
        this.menu.addClose('Back');

        //
        // The message listing panel
        //
        this.messages = new M.panel('Messages',
            'ciniki_mail_main', 'messages',
            'mc', 'large', 'sectioned', 'ciniki.mail.main.messages');
        this.messages.data = {};
        this.messages.label_id = '';
        this.messages.offset = 0;
        this.messages.prev_offset = 0;
        this.messages.next_offset = 0;
        this.messages.sections = {
//          'labels':{'label':'', 'aside':'yes', 'type':'simplegrid', 'num_cols':1,
//              },
            'search':{'label':'', 'type':'livesearchgrid', 'livesearchcols':3, 'hint':'Search messages',
                'noData':'No messages found',
                },
            'messages':{'label':'', 'visible':'yes', 'type':'simplegrid', 'num_cols':3, 'limit':this.page_size,
                'cellClasses':['multiline', 'multiline', 'multiline'],
                },
            };
        this.messages.sectionData = this.menu.sectionData;
        this.messages.cellValue = this.menu.cellValue;
        this.messages.noData = function(s, i, d) {
            if( s == 'messages' ) {
                return 'No messages found';
            }
        };
        this.messages.rowFn = function(s, i, d) {
            return 'M.ciniki_mail_main.messageShow(\'M.ciniki_mail_main.messagesShow();\',\'' + d.message.id + '\');';
        };
        this.messages.liveSearchCb = function(s, i, v) {
            if( v != '' ) {
                M.api.getJSONBgCb('ciniki.mail.messageSearch', {'business_id':M.curBusinessID, 'start_needle':v, 'limit':'15'},
                    function(rsp) {
                        M.ciniki_mail_main.messages.liveSearchShow(s, null, M.gE(M.ciniki_mail_main.messages.panelUID + '_' + s), rsp.messages);
                    });
            }
            return true;
        };
        this.messages.liveSearchResultClass = this.menu.liveSearchResultClass;
        this.messages.liveSearchResultValue = this.menu.liveSearchResultValue;
        this.messages.liveSearchResultRowFn = function(s, f, i, j, d) {
            return 'M.ciniki_mail_main.messageShow(\'M.ciniki_mail_main.messagesShow();\', \'' + d.message.id + '\');'; 
        };
        this.messages.prevButtonFn = function() {
            if( this.prev_offset >= 0 ) {
                return 'M.ciniki_mail_main.messagesShow(null,' + this.prev_offset + ');';
            }
            return null;
        };
        this.messages.nextButtonFn = function() {
            if( this.next_offset > 0 ) {
                return 'M.ciniki_mail_main.messagesShow(null,' + this.next_offset + ');';
            }
            return null;
        };
        this.messages.liveSearchSubmitFn = function(s, search_str) {
            M.ciniki_mail_main.searchShow('M.ciniki_mail_main.messagesShow();', search_str, 0);
        };
//      this.messages.addButton('add', 'Add', 'M.ciniki_mail_mail.messageEdit(\'M.ciniki_mail_main.messagesShow();\');');
        this.messages.addButton('next', 'Next');
        this.messages.addClose('Back');
        this.messages.addLeftButton('prev', 'Prev');

        //
        // The search panel show the full results of a search
        //
        this.search = new M.panel('Search Results',
            'ciniki_mail_main', 'search',
            'mc', 'large', 'sectioned', 'ciniki.mail.main.search');
        this.search.data = {};
        this.search.label_id = '';
        this.search.offset = 0;
        this.search.prev_offset = 0;
        this.search.next_offset = 0;
        this.search.sections = {
            'messages':{'label':'', 'visible':'yes', 'type':'simplegrid', 'num_cols':3, 'limit':this.page_size,
                'cellClasses':['multiline', 'multiline', 'multiline'],
                },
            };
        this.search.sectionData = this.menu.sectionData;
        this.search.cellValue = function(s, i, j, d) {
            return M.ciniki_mail_main.menu.liveSearchResultValue(s, null, i, j, d);
        };
        this.search.noData = this.messages.noData;
        this.search.rowFn = function(s, i, d) {
            return 'M.ciniki_mail_main.messageShow(\'M.ciniki_mail_main.searchShow();\',\'' + d.message.id + '\');';
        };
        this.search.prevButtonFn = function() {
            if( this.prev_offset >= 0 ) {
                return 'M.ciniki_mail_main.searchShow(null,null,' + this.prev_offset + ');';
            }
            return null;
        };
        this.search.nextButtonFn = function() {
            if( this.next_offset > 0 ) {
                return 'M.ciniki_mail_main.searchShow(null,null,' + this.next_offset + ');';
            }
            return null;
        };
        this.search.addButton('next', 'Next');
        this.search.addClose('Back');
        this.search.addLeftButton('prev', 'Prev');

        //
        // The message panel
        //
        this.message = new M.panel('Messages',
            'ciniki_mail_main', 'message',
            'mc', 'large', 'sectioned', 'ciniki.mail.main.message');
        this.message.data = {};
        this.message.label_id = '';
        this.message.sections = {
            'details':{'label':'', 'list':{
                'from_name':{'label':'Name', 'visible':'no'},
                'from_email':{'label':'Email', 'visible':'no'},
                'customer_name':{'label':'Name', 'visible':'yes'},
                'customer_email':{'label':'Email', 'visible':'yes'},
                'subject':{'label':'Subject'},
                }},
            'html_content':{'label':'Message', 'type':'htmlcontent'},
            'logs':{'label':'Logs', 'visible':'no', 'type':'simplegrid', 'num_cols':3,
                'cellClasses':['multiline','multiline','multiline'],
                },
            '_buttons':{'label':'', 'buttons':{
                'queue':{'label':'Send', 'visible':'no', 'fn':'M.ciniki_mail_main.messageAction(\'M.ciniki_mail_main.message.close();\',M.ciniki_mail_main.message.message_id,\'queue\');'},
                'tryagain':{'label':'Try Again', 'visible':'no', 'fn':'M.ciniki_mail_main.messageAction(\'M.ciniki_mail_main.message.close();\',M.ciniki_mail_main.message.message_id,\'tryagain\');'},
                'delete':{'label':'Delete', 'visible':'no', 'fn':'M.ciniki_mail_main.messageDelete(\'M.ciniki_mail_main.message.close();\',M.ciniki_mail_main.message.message_id);'},
                'purge':{'label':'Delete', 'visible':'no', 'fn':'M.ciniki_mail_main.messagePurge(\'M.ciniki_mail_main.message.close();\',M.ciniki_mail_main.message.message_id);'},
                }},
            };
        this.message.sectionData = function(s) {
            if( s == 'html_content' ) { 
                if( this.data[s].match(/<body>/) ) {
                    return this.data[s].replace(/^[\s\S]*<body>([\s\S]*)<\/body>[\s\S]*$/, '$1');
                }
                return this.data[s]; 
            }
            if( s == 'html_content' ) { return this.data[s].replace(/\n/g, '<br/>'); }
            if( s == 'details' ) { return this.sections[s].list; }
            return this.data[s];
        }
        this.message.cellValue = function(s, i, j, d) {
            switch(j) {
                case 0: return '<span class="maintext">' + d.log.log_date_date + '</span><span class="subtext">' + d.log.log_date_time + '</span>';
                case 1: return '<span class="maintext">' + d.log.severity_text + '</span><span class="subtext">' + (d.log.code != '' ?'error: '+d.log.code:'') + '</span>';
                case 2: return '<span class="maintext">' + d.log.msg + '</span><span class="subtext">' + ((M.userPerms&0x01)>0?d.log.pmsg:'') + '</span>';
            }
        };
        this.message.listLabel = function(s, i, d) {
            switch (s) {
                case 'details': return d.label;
            }
        };
        this.message.listValue = function(s, i, d) {
            return this.data[i];
        };
        this.message.addClose('Back');
    }

    //
    // Arguments:
    // aG - The arguments to be parsed into args
    //
    this.start = function(cb, appPrefix, aG) {
        args = {};
        if( aG != null ) { args = eval(aG); }

        this.menu.sections._mailings.active = ((M.curBusiness.modules['ciniki.mail'].flags&0x01)==1?'yes':'no');

        //
        // Create the app container if it doesn't exist, and clear it out
        // if it does exist.
        //
        var appContainer = M.createContainer(appPrefix, 'ciniki_mail_main', 'yes');
        if( appContainer == null ) {
            alert('App Error');
            return false;
        } 

        if( args.message_id != null && args.message_id > 0 ) {
            this.messageShow(cb, args.message_id);
        } else {
            this.menuShow(cb);
        }
    };

    this.menuShow = function(cb) {
        M.api.getJSONCb('ciniki.mail.messageLabels', {'business_id':M.curBusinessID}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_mail_main.menu;
            p.data = rsp;
            p.refresh();
            p.show(cb);
        });
    };
    
    this.messagesShow = function(cb, offset, status) {
        if( status != null ) { this.messages.status = status; }
        if( offset != null ) { this.messages.offset = offset; }
        M.api.getJSONCb('ciniki.mail.messageList', {'business_id':M.curBusinessID, 'status':this.messages.status, 
            'offset':this.messages.offset, 'limit':(M.ciniki_mail_main.page_size+1)}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_mail_main.messages;
                p.data = rsp;
                var last_msg = '';
                if( rsp.messages != null && rsp.messages.length > M.ciniki_mail_main.page_size ) {
                    // Show next
                    p.next_offset = parseInt(p.offset) + parseInt(M.ciniki_mail_main.page_size);
                    last_msg = parseInt(p.offset) + M.ciniki_mail_main.page_size;
                } else {
                    if( rsp.messages.length > 0 ) {
                        last_msg = parseInt(p.offset) + rsp.messages.length;
                    }
                    p.next_offset = 0;
                }
                if( p.offset >= 50 ) {
                    p.prev_offset = parseInt(p.offset) - parseInt(M.ciniki_mail_main.page_size);
                    if( p.prev_offset < 0 ) {
                        p.prev_offset = 0;
                    }
                } else if( p.offset > 0 ) {
                    p.prev_offset = 0;
                } else {
                    p.prev_offset = -1;
                }
                p.sections.messages.label = '';
                switch(p.status) {
                    case '5': p.sections.messages.label = 'Drafts'; break;
                    case '7': p.sections.messages.label = 'Drafts'; break;
                    case '10': p.sections.messages.label = 'Queued'; break;
                    case '15': p.sections.messages.label = 'Queue Failures'; break;
                    case '20': p.sections.messages.label = 'Sending'; break;
                    case '30': p.sections.messages.label = 'Sent'; break;
                    case '40': p.sections.messages.label = 'Inbox'; break;
                    case '41': p.sections.messages.label = 'Flagged'; break;
                    case '50': p.sections.messages.label = 'Failed'; break;
                    case '60': p.sections.messages.label = 'Trash'; break;
                }
                if( p.offset > 1 || rsp.messages.length > 5 ) {
                    p.sections.messages.label += ' ' + (parseInt(p.offset) + 1) + '-' + last_msg;
                }
                p.refresh();
                p.show(cb);
            });
    };

    this.searchShow = function(cb, search_str, offset, status) {
        if( search_str != null ) { this.search.search_str = search_str; }
        if( status != null ) { this.search.status = status; }
        if( offset != null ) { this.search.offset = offset; }
        M.api.getJSONCb('ciniki.mail.messageSearch', {'business_id':M.curBusinessID, 'start_needle':this.search.search_str, 
            'offset':this.search.offset, 'limit':(M.ciniki_mail_main.page_size+1)}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_mail_main.search;
                p.data = rsp;
                if( rsp.messages != null && rsp.messages.length > M.ciniki_mail_main.page_size ) {
                    // Show next
                    p.next_offset = parseInt(p.offset) + parseInt(M.ciniki_mail_main.page_size);
                } else {
                    p.next_offset = 0;
                }
                if( p.offset >= 50 ) {
                    p.prev_offset = parseInt(p.offset) - parseInt(M.ciniki_mail_main.page_size);
                    if( p.prev_offset < 0 ) {
                        p.prev_offset = 0;
                    }
                } else if( p.offset > 0 ) {
                    p.prev_offset = 0;
                } else {
                    p.prev_offset = -1;
                }
                p.refresh();
                p.show(cb);
            });
    };

    this.messageShow = function(cb, mid) {
        if( mid != null ) { this.message.message_id = mid; }
        M.api.getJSONCb('ciniki.mail.messageGet', {'business_id':M.curBusinessID, 'message_id':this.message.message_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_mail_main.message;
            p.data = rsp.message;
            if( rsp.message.status == 7 ) {
                p.sections._buttons.buttons.queue.visible = 'yes';
            } else {
                p.sections._buttons.buttons.queue.visible = 'no';
            }
            if( rsp.message.status == 40 ) {
                p.sections.details.list.from_name.visible = 'yes';
                p.sections.details.list.from_email.visible = 'yes';
                p.sections.details.list.customer_name.visible = 'no';
                p.sections.details.list.customer_email.visible = 'no';
            } else {
                p.sections.details.list.from_name.visible = 'no';
                p.sections.details.list.from_email.visible = 'no';
                p.sections.details.list.customer_name.visible = 'yes';
                p.sections.details.list.customer_email.visible = 'yes';
            }
            if( rsp.message.status == 50 || (rsp.message.status == 20 && (M.userPerms&0x01) > 0) ) {
                p.sections._buttons.buttons.tryagain.visible = 'yes';
            } else {
                p.sections._buttons.buttons.tryagain.visible = 'no';
            }
            if( rsp.message.status != 60 ) {
                p.sections._buttons.buttons.delete.visible = 'yes';
                p.sections._buttons.buttons.purge.visible = 'no';
            } else {
                p.sections._buttons.buttons.delete.visible = 'no';
                p.sections._buttons.buttons.purge.visible = 'yes';
            }
            if( rsp.message.logs != null ) {
                p.sections.logs.visible = 'yes';
                p.data.logs = rsp.message.logs;
            } else {
                p.sections.logs.visible = 'no';
            }
            p.refresh();
            p.show(cb);
        });
    };

    this.messageAction = function(cb, mid, action) {
        M.api.getJSONCb('ciniki.mail.messageAction', {'business_id':M.curBusinessID, 'message_id':mid, 'action':action}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            eval(cb);
        });
    };

    this.messageQueue = function(cb, mid) {
        M.api.getJSONCb('ciniki.mail.messageAction', {'business_id':M.curBusinessID, 'message_id':mid, 'action':'queue'}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            eval(cb);
        });
    };

    this.messageDelete = function(cb, mid) {
        var msg = "Are you sure you want to move this message to trash?";
        if( confirm(msg) ) {
            M.api.getJSONCb('ciniki.mail.messageAction', {'business_id':M.curBusinessID, 'message_id':mid, 'action':'delete'}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                eval(cb);
            });
        }
    };

    this.messagePurge = function(cb, mid) {
        var msg = "Are you sure you want to remove this message?";
        if( confirm(msg) ) {
            M.api.getJSONCb('ciniki.mail.messagePurge', {'business_id':M.curBusinessID, 'message_id':mid}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                eval(cb);
            });
        }
    };
}
