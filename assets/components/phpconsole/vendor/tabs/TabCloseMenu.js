Ext.namespace("Ext.ux");
try {
    Ext.ux.TabCloseMenu = function(cnf){
        var tabs, menu, ctxItem;
        this.init = function(tp){
            tabs = tp;
            tabs.on('contextmenu', onContextMenu);
        };
        function onContextMenu(ts, item, e){
            if(!menu){ // create context menu on first right click
                menu = new Ext.menu.Menu({
                    items: [{
                        id: tabs.id + '-close',
                        cls: 'modx-tab-close',
                        text: cnf.closeTabText || 'Close Tab',
                        handler : function(){
                            //tp.on("afterrender", this.afterRender, this); beforeclose
                            tabs.remove(ctxItem);
                        }
                    },{
                        id: tabs.id + '-close-others',
                        cls: 'modx-tab-close',
                        text: cnf.closeOthersTabsText || 'Close Other Tabs',
                        handler : function(){
                            tabs.items.each(function(item){
                                if(item.closable && item != ctxItem){
                                    tabs.remove(item);
                                }
                            });
                        }
                    }]});
            }
            ctxItem = item;
            var items = menu.items;
            items.get(tabs.id + '-close').setDisabled(!item.closable);
            var disableOthers = true;
            tabs.items.each(function(){
                if(this != item && this.closable){
                    disableOthers = false;
                    return false;
                }
            });
            items.get(tabs.id + '-close-others').setDisabled(disableOthers);
            e.stopEvent();
            menu.showAt(e.getPoint());
        }
    };

    Ext.preg('tabclosemenu', Ext.ux.TabCloseMenu);
} catch (eq) {
    console.log(eq);
}