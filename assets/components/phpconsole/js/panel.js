phpconsole.panel.Console = function (config) {
    config = config || {};

    Ext.applyIf(config, {
        id: 'phpconsole-panel-console',
        cls: 'container',
        items: [{
            xtype: 'modx-tabs',
            enableTabScroll: true,
            plugins: [new Ext.ux.panel.DraggableTabs(), new Ext.ux.TabCloseMenu({
                closeTabText: _('phpconsole_tab_close'),
                closeOthersTabsText: _('phpconsole_tab_close_others'),
            })],
            initComponent: function () {
                this.ownerCt.tabWrapper = this;
                this.constructor.prototype.initComponent.apply(this, arguments);
            },
            defaults: {
                border: false,
                autoHeight: true,
                deferredRender: false,
                forceLayout: true
            },
            border: true,
            items: [],
            listeners: {
                render: function (tabs) {
                    tabs.stripWrap.on('dblclick', function (e, el) {
                        e.stopEvent();
                        if (el.classList.contains('x-tab-strip-text')) {
                            return;
                        }
                        tabs.ownerCt.addTabItem();
                    });
                },
                remove: function (tabs) {
                    if (!tabs.items.length) {
                        tabs.ownerCt.loadTabItems();
                    }
                },
                reorder: {
                    fn: function (tabs, tab) {

                    }
                }
            },
        }]
    });
    phpconsole.panel.Console.superclass.constructor.call(this, config);
};

Ext.extend(phpconsole.panel.Console, MODx.Panel, {
    tabWrapper: null,

    getTabItem: function (config) {
        config = config || {};
        return {
            title: _('phpconsole_code') + (config.code_id ? ' - ' + config.code_id : ''),
            tabTip: '',
            layout: 'form',
            hideLabels: true,
            autoHeight: true,
            border: true,
            closable: true,
            dropTarget: null,
            execRequestId: null,
            execRequestIdx: 0,
            listeners: {
                activate: function (tab) {
                    // FIX tab Height
                    if ((panel = this.findParentByType('modx-panel'))) {
                        panel.setTabHeight();
                        (function () {
                            panel.setTabHeight();
                        }).defer(100);
                    }
                },
                render: function (tab) {
                    let el = tab.getEl();
                    let panel = tab.findParentByType('modx-panel');

                    this.dropTarget = new Ext.dd.DropTarget(el, {
                        ddGroup: 'modx-treedrop-sources-dd',
                        notifyOut: function (ddSource, e, data) {
                            tab.removeClass('dz-drag-hover');
                        },
                        notifyEnter: function (ddSource, e, data) {
                            if (!data.node || !data.node.attributes || data.node.attributes.type !== 'file') return;
                            tab.addClass('dz-drag-hover');
                            if (el) {
                                el.focus();
                            }
                        },
                        notifyDrop: function (ddSource, e, data) {
                            if (!data.node || !data.node.attributes || data.node.attributes.type !== 'file') return;
                            return panel.getFileContent(data, tab);
                        }
                    });
                }
            },
            items: [{
                xtype: 'form',
                method: 'POST',
                listeners: {
                    beforerender: function (fp) {
                        var el = fp.ownerCt.getEl();
                        el.mask(_('loading'), 'x-mask-loading');

                        MODx.Ajax.request({
                            url: phpconsole.config.connectorUrl,
                            params: {
                                action: 'Code\\Get',
                                code_id: config.code_id || 0
                            },
                            listeners: {
                                success: {
                                    fn: function (r) {
                                        el.unmask();
                                        if (r.data) {
                                            fp.getForm().setValues(r.data);
                                            fp.fireEvent("change", fp);
                                        }
                                    }, scope: this
                                },
                                failure: {
                                    fn: function (r) {
                                        el.unmask();
                                        if (r.message) {
                                            phpconsole.Msg.alert(_('error'), r.message);
                                        }
                                    },
                                    scope: this
                                }
                            }
                        });
                    },
                    change: function (fp) {
                        let record = fp.getForm().getValues();
                        if (record.code_id && parseInt(record.code_id)) {
                            fp.ownerCt.setTitle(_('phpconsole_code') + ' - ' + record.code_id);
                            if (fp.ownerCt.tabEl) {
                                fp.ownerCt.tabEl.qtip = String.format('<div>createdon: {0}</div><div>updatedon: {1}</div>',
                                    record.createdon,
                                    record.updatedon
                                );
                            }
                        }
                    }
                },
                initComponent: function () {
                    this.ownerCt.fp = this;
                    this.constructor.prototype.initComponent.apply(this, arguments);
                },
                border: false,
                cls: 'main-wrapper',
                layout: 'form',
                labelAlign: 'top',
                items: [{
                    xtype: 'hidden',
                    name: 'code_id',
                    value: config.code_id || 0,
                }, {
                    xtype: 'hidden',
                    name: 'createdon',
                }, {
                    xtype: 'hidden',
                    name: 'updatedon',
                }, {
                    xtype: 'hidden',
                    name: 'stop',
                }, {
                    xtype: Ext.ComponentMgr.types['modx-texteditor'] ? 'modx-texteditor' : 'textarea',
                    mimeType: 'application/x-php',
                    name: 'content',
                    hideLabel: true,
                    height: '100%',
                    grow: false,
                    anchor: '100%',
                    cls: 'phpconsole-tab-content',
                    onDestroy: function () {
                        if (this.xtype === 'modx-texteditor') {
                            if (this.editor) this.editor.destroy();
                            Ext.ux.Ace.superclass.onDestroy.call(this);
                        } else {
                            Ext.form.TextArea.superclass.onDestroy.call(this);
                        }
                    },
                    setValue: function (value) {
                        if (!value || value === '') {
                            value = '<?php\r\n';
                        }
                        if (this.xtype === 'modx-texteditor') {
                            if (this.editor) {
                                this.editor.getSession().setValue(value);
                            } else {
                                this.valueHolder.value = value;
                            }
                            this.value = value;
                        } else {
                            return Ext.form.TextArea.superclass.setValue.call(this, value);
                        }
                    },
                }, {
                    layout: 'column',
                    cls: 'phpconsole-console-bbar',
                    border: false,
                    items: [{
                        columnWidth: .15,
                        border: false,
                        layout: 'form',
                        cls: 'button-column',
                        items: [
                            {
                                xtype: 'button',
                                text: '<i class="icon icon-play"></i>',
                                cls: 'primary-button',
                                handler: this.codeExec,
                                scope: this,
                                initComponent: function () {
                                    if ((form = this.findParentByType('form'))) {
                                        form.ownerCt.btnExec = this;
                                    }
                                    this.constructor.prototype.initComponent.apply(this, arguments);
                                },
                            },
                            {
                                xtype: 'button',
                                text: '<i class="icon icon-stop"></i>',
                                handler: this.codeStop,
                                scope: this,
                                hidden: true,
                                initComponent: function () {
                                    if ((form = this.findParentByType('form'))) {
                                        form.ownerCt.btnStop = this;
                                    }
                                    this.constructor.prototype.initComponent.apply(this, arguments);
                                },
                            },
                            {
                                xtype: 'button',
                                text: _('phpconsole_log'),
                                cls: '_primary-button',
                                handler: function (btn) {
                                    let tab = this.tabWrapper.getActiveTab();
                                    if (!tab) return;
                                    const active = tab.outputTab.items.indexOf(tab.outputTab.getActiveTab());
                                    if (active) {
                                        tab.outputTab.setActiveTab(0);
                                        btn.setText(_('phpconsole_log'));
                                    } else {
                                        tab.outputTab.setActiveTab(1);
                                        btn.setText(_('phpconsole_result'));
                                    }
                                    this.setTabHeight();
                                },
                                scope: this
                            },
                        ]
                    }, {
                        columnWidth: .1,
                        border: false,
                        layout: 'form',
                        cls: 'left-column',
                        items: [
                            {
                                xtype: 'statictextfield',
                                fieldLabel: _('phpconsole_total_idx'),
                                name: 'total_idx',
                                anchor: '100%',
                                value: '0'
                            },
                        ]
                    }, {
                        columnWidth: .1,
                        border: false,
                        layout: 'form',
                        cls: 'left-column',
                        items: [
                            {
                                xtype: 'statictextfield',
                                fieldLabel: _('phpconsole_total_sql_time'),
                                name: 'total_sql_time',
                                anchor: '100%',
                                value: '0 s'
                            },
                        ]
                    }, {
                        columnWidth: .1,
                        border: false,
                        layout: 'form',
                        cls: 'left-column',
                        items: [
                            {
                                xtype: 'statictextfield',
                                fieldLabel: _('phpconsole_total_php_time'),
                                name: 'total_php_time',
                                anchor: '100%',
                                value: '0 s'
                            },
                        ]
                    }, {
                        columnWidth: .1,
                        border: false,
                        layout: 'form',
                        cls: 'left-column',
                        items: [
                            {
                                xtype: 'statictextfield',
                                fieldLabel: _('phpconsole_total_time'),
                                name: 'total_time',
                                anchor: '100%',
                                value: '0 s'
                            },
                        ]
                    }, {
                        columnWidth: .1,
                        border: false,
                        layout: 'form',
                        cls: 'left-column',
                        items: [
                            {
                                xtype: 'statictextfield',
                                fieldLabel: _('phpconsole_total_queries'),
                                name: 'total_queries',
                                anchor: '100%',
                                value: '0'
                            },
                        ]
                    }, {
                        columnWidth: .1,
                        border: false,
                        layout: 'form',
                        cls: 'separator-column',
                        items: [
                            {
                                html: '',
                                anchor: '100%',
                            },
                        ]
                    }, {
                        columnWidth: .1,
                        border: false,
                        layout: 'form',
                        cls: 'right-column',
                        items: [
                            {
                                xtype: 'statictextfield',
                                fieldLabel: _('phpconsole_total_memory'),
                                name: 'total_memory',
                                anchor: '100%',
                                value: '0 mb'
                            },
                        ]
                    }, {
                        columnWidth: .1,
                        border: false,
                        layout: 'form',
                        cls: 'right-column',
                        items: [
                            {
                                xtype: 'statictextfield',
                                fieldLabel: _('phpconsole_total_peak_memory'),
                                name: 'total_peak_memory',
                                anchor: '100%',
                                value: '0 mb'
                            },
                        ]
                    }]
                }, {
                    xtype: 'modx-vtabs',
                    deferredRender: false,
                    cls: 'vertical-tabs-panel phpconsole-output-vtab',
                    initComponent: function () {
                        this.ownerCt.ownerCt.outputTab = this;
                        this.constructor.prototype.initComponent.apply(this, arguments);
                    },
                    items: [{
                        deferredRender: false,
                        items: [{
                            xtype: 'textarea',
                            name: 'result',
                            hideLabel: true,
                            height: '100%',
                            grow: false,
                            anchor: '100%',
                        }]
                    }, {
                        deferredRender: false,
                        items: [{
                            xtype: 'textarea',
                            name: 'log',
                            hideLabel: true,
                            height: '100%',
                            grow: false,
                            anchor: '100%',
                        }]
                    }]
                }
                ]
            }]
        };
    },

    removeTabItems: function () {
        this.tabWrapper.removeAll();
    },
    addTabItem: function () {
        this.tabWrapper.add(this.getTabItem({}));
        this.tabWrapper.setActiveTab(this.tabWrapper.items.length - 1);
    },

    loadTabItems: function (config) {
        this.tabWrapper.removeAll();
        if (config && config.length) {
            for (i in config) {
                if (!config.hasOwnProperty(i)) {
                    continue;
                }
                this.tabWrapper.add(this.getTabItem(config[i]));
            }
        } else {
            this.tabWrapper.add(this.getTabItem({}));
        }
        this.tabWrapper.setActiveTab(0);
    },

    setTabHeight: function () {
        let tab = this.tabWrapper.getActiveTab();
        if (!tab) return;

        let content = tab.fp.getForm().findField('content');
        let result = tab.fp.getForm().findField('result');
        let log = tab.fp.getForm().findField('log');

        var clientHeight = document.documentElement.clientHeight || window.innerHeight || document.body.clientHeight
            // Our textarea "top" position
            ,
            elemTop = tab.fp.el.getTop()
            // The followings are to prevent scrolling if possible (slice is to remove "px" from the values, since we want integers)
            ,
            wrapperPadding = this.el.select('.main-wrapper').first().getStyle('padding-bottom').slice(0, -2),
            containerMargin = this.el.getStyle('margin-bottom').slice(0, -2);

        let allHeight = clientHeight - elemTop - wrapperPadding - containerMargin;

        let contentHeight = allHeight * 0.5;
        let resultHeight = allHeight * 0.5;
        let bbarHeight = 95;

        content.el.setHeight(contentHeight);
        if (result.el) result.el.setHeight(resultHeight - bbarHeight);
        if (log.el) log.el.setHeight(resultHeight - bbarHeight);
    },

    toggleTabExecBtn(tab) {
        if (tab.execRequestId) {
            tab.btnExec.hide();
            tab.btnStop.show();
        } else {
            tab.btnStop.hide();
            tab.btnExec.show();
        }
    },

    codeExec: function (btn, e, reexecute) {
        let tab = this.tabWrapper.getActiveTab();
        if (!tab) return;
        if (tab.execRequestId) return;

        let content = tab.fp.getForm().findField('content');
        let stop = tab.fp.getForm().findField('stop');

        if (!!reexecute && stop.getValue() === 'true') {
            return;
        } else {
            stop.setValue('false');
        }

        phpconsole.Ajax.request({
            url: phpconsole.config.connectorUrl,
            params: Ext.apply({}, {
                action: 'Code\\Exec',
                output: '',
                log: '',
                stop: '',
            }, tab.fp.getForm().getValues()),
            maskEl: content.getEl(),
            listeners: {
                beforerequest: {
                    fn: function () {
                        tab.execRequestId = -1;
                        this.toggleTabExecBtn(tab);
                    },
                    scope: this
                },
                initrequest: {
                    fn: function (request) {
                        tab.execRequestId = request.requestId;
                        tab.execRequestIdx++;
                    },
                    scope: this
                },
                success: {
                    fn: function (r) {
                        tab.execRequestId = null;
                        this.toggleTabExecBtn(tab);

                        if (r.data) {
                            r.data['total_idx'] = tab.execRequestIdx;

                            tab.fp.getForm().setValues(r.data);
                            tab.fp.fireEvent("change", tab.fp);

                            if (r.data.reexecute === true) {
                                (function () {
                                    this.codeExec(null, null, true);
                                }).bind(this).defer(phpconsole.Ajax.latency || 0);
                            }
                        }
                    },
                    scope: this
                },
                failure: {
                    fn: function (r) {
                        tab.execRequestId = null;
                        this.toggleTabExecBtn(tab);

                        if (r.message) {
                            phpconsole.Msg.alert(_('error'), r.message);
                        }
                    },
                    scope: this
                }
            }
        });
    },

    codeStop: function (btn, e) {
        let tab = this.tabWrapper.getActiveTab();
        if (!tab) return;

        let content = tab.fp.getForm().findField('content');
        let stop = tab.fp.getForm().findField('stop');

        if (tab.execRequestId) {
            phpconsole.Ajax.abort(tab.execRequestId, content.getEl());
            tab.execRequestId = null;
        }

        stop.setValue('true');

        this.toggleTabExecBtn(tab);
    },

    getFileContent: function (data, tab) {
        let content = tab.fp.getForm().findField('content');

        MODx.Ajax.request({
            url: phpconsole.config.connectorUrl,
            params: {
                action: 'Tree\\Get',
                source: data.node.attributes.loader.baseParams.source,
                type: data.node.attributes.type,
                path: data.node.attributes.id,
            },
            listeners: {
                success: {
                    fn: function (r) {
                        if (r.data && r.data.content) {
                            content.setValue(r.data.content);
                        }
                    },
                    scope: this
                },
            }
        });
    },

});
Ext.reg('phpconsole-panel-console', phpconsole.panel.Console);