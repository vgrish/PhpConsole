phpconsole.page.Console = function (config) {
    config = config || {};

    this.panel = MODx.load({
        xtype: 'phpconsole-panel-console',
    });
    Ext.applyIf(config, {
        formpanel: 'phpconsole-panel-console',
        buttons: [{
            text: '<i class="icon icon-plus"></i>',
            id: 'modx-abtn-refresh',
            cls: 'primary-button',
            handler: this.addItem,
            scope: this
        }, {
            text: '<i class="icon icon-question-circle"></i>',
            id: 'modx-abtn-help',
            handler: this.loadHelp,
            scope: this,
        }, {
            text: '<i class="icon icon-trash-o"></i>',
            id: 'modx-abtn-delete',
            handler: this.clearItems,
            scope: this,
        }],
        components: [{
            xtype: 'modx-header',
            cls: 'phpconsole-page-header',
            html: _('phpconsole_main')
        }, this.panel]
    });
    phpconsole.page.Console.superclass.constructor.call(this, config);
    this.loadItems();
};
Ext.extend(phpconsole.page.Console, MODx.Component, {
    panel: null,

    addItem: function () {
        this.panel.addTabItem();
    },

    loadItems: function () {
        let progress = Ext.MessageBox.progress(_('please_wait')).updateProgress(0.5);
        MODx.Ajax.request({
            url: phpconsole.config.connectorUrl,
            params: {
                action: 'Code\\GetList',
            },
            listeners: {
                success: {
                    fn: function (r) {
                        this.panel.loadTabItems(r.data);
                        progress.hide();
                    }, scope: this
                },
                failure: {
                    fn: function (r) {
                        progress.hide();
                        if (r.message) {
                            phpconsole.Msg.alert(_('error'), r.message);
                        }
                    },
                    scope: this
                }
            }
        });
    },

    clearItems: function () {
        let progress = Ext.MessageBox.progress(_('please_wait')).updateProgress(0.5);
        MODx.Ajax.request({
            url: phpconsole.config.connectorUrl,
            params: {
                action: 'Code\\Clear',
            },
            listeners: {
                success: {
                    fn: function (r) {
                        this.panel.removeTabItems();
                        progress.hide();
                    }, scope: this
                },
                failure: {
                    fn: function (r) {
                        progress.hide();
                        if (r.message) {
                            phpconsole.Msg.alert(_('error'), r.message);
                        }
                    },
                    scope: this
                }
            }
        });
    },

    loadHelp: function (b) {
        MODx.helpWindow = new Ext.Window({
            title: _('help'),
            width: 850,
            height: 500,
            resizable: true,
            maximizable: true,
            modal: false,
            layout: 'fit',
            bodyStyle: 'padding: 0;',
            items: [{
                xtype: 'container',
                layout: {
                    type: 'vbox',
                    align: 'stretch'
                },
                width: '100%',
                height: '100%',
                items: [{
                    autoEl: {
                        tag: 'iframe',
                        src: MODx.config.assets_url + 'components/phpconsole/readme.md',
                        width: '100%',
                        height: '100%',
                        frameBorder: 0
                    }
                }]
            }]
        });
        MODx.helpWindow.show(b);
        return true;
    }
});
Ext.reg('phpconsole-page-console', phpconsole.page.Console);