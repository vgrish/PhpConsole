var phpconsole = function (config) {
    config = config || {};
    phpconsole.superclass.constructor.call(this, config);
    this.config = config;
    this.startup();
};
Ext.extend(phpconsole, Ext.Component, {
    page: {}, window: {}, panel: {}, config: {},
    startup: function () {
        this.Ajax = this.load({xtype: 'phpconsole-ajax'});
        this.Ajax.latency = 700;
    },
    load: function () {
        var a = arguments, l = a.length;
        var os = [];
        for (var i = 0; i < l; i = i + 1) {
            if (!a[i].xtype || a[i].xtype === '') {
                return false;
            }
            os.push(Ext.ComponentMgr.create(a[i]));
        }
        return (os.length === 1) ? os[0] : os;
    }
});
Ext.reg('phpconsole', phpconsole);

phpconsole.Ajax = function (config) {
    config = config || {};

    phpconsole.Ajax.superclass.constructor.call(this, config);
};
Ext.extend(phpconsole.Ajax, Ext.Component, {
    requestId: null,
    abort: function (trans, maskEl) {
        const result = Ext.Ajax.abort(trans);
        if (maskEl) {
            maskEl.unmask();
        }
        return result;
    },
    request: function (config) {
        Ext.apply(config, {
            success: function (r, o) {
                if (config.maskEl) {
                    config.maskEl.unmask();
                }

                try {
                    r = Ext.decode(r.responseText);
                } catch (e) {
                }
                if (!r) {
                    return false;
                }

                r.options = o;
                if (r.success) {
                    if (config.listeners.success && config.listeners.success.fn) {
                        this._runCallback(config.listeners.success, [r]);
                    }
                } else if (config.listeners.failure && config.listeners.failure.fn) {
                    this._runCallback(config.listeners.failure, [r]);
                }
                return true;
            },
            failure: function (r, o) {
                if (config.maskEl) {
                    config.maskEl.unmask();
                }

                try {
                    r = Ext.decode(r.responseText);
                } catch (e) {
                }
                if (!r) {
                    return false;
                }

                r.options = o;
                if (config.listeners.failure && config.listeners.failure.fn) {
                    this._runCallback(config.listeners.failure, [r]);
                }
                return true;
            },

            scope: this,
            headers: {
                'Powered-By': 'MODx',
                'modAuth': MODx.siteId
            }
        });
        if (config.maskEl) {
            config.maskEl.mask(_('loading'), 'x-mask-loading');
        }

        if (config.listeners.beforerequest && config.listeners.beforerequest.fn) {
            this._runCallback(config.listeners.beforerequest, [this]);
        }

        (function () {
            this.requestId = Ext.Ajax.request(config);

            if (config.listeners.initrequest && config.listeners.initrequest.fn) {
                this._runCallback(config.listeners.initrequest, [this]);
            }
        }).bind(this).defer(this.latency || 0);
    },

    /**
     * Execute the listener callback
     *
     * @param {Object} config - The listener configuration (ie.failure/success)
     * @param {Array} args - An array of arguments to pass to the callback
     */
    _runCallback: function (config, args) {
        var scope = window,
            fn = config.fn;

        if (config.scope) {
            scope = config.scope;
        }
        fn.apply(scope || window, args);
    }
});
Ext.reg('phpconsole-ajax', phpconsole.Ajax);

phpconsole = new phpconsole();

phpconsole.Msg = {
    alert: function (title, text) {
        let d = Ext.Msg.show({
            title: title,
            msg: text,
            buttons: Ext.Msg.OK,
            minWidth: 400
        });
        d.getDialog().getEl().addClass('x-window-phpconsole');
    },
    confirm: function (title, text, fn, scope) {
        let d = Ext.Msg.show({
            title: title,
            msg: text,
            buttons: Ext.Msg.YESNO,
            icon: Ext.Msg.QUESTION,
            minWidth: 400,
            fn: fn,
            scope: scope,
        });
        d.getDialog().getEl().addClass('x-window-phpconsole');
    },
};

