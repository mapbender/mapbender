(function($){

    $.widget("mapbender.mbBaseSourceSwitcher", {
        options: {
        },
        scalebar: null,
        /* Creates the map tool bar */
        _create: function(){
            if(!Mapbender.checkTarget("mbBaseSourceSwitcher", this.options.target)){
                return;
            }
            var self = this;
            Mapbender.elementRegistry.onElementReady(this.options.target, $.proxy(self._setup, self));
        },
        /* Initializes the map tool bar */
        _setup: function(){
            var self = this;
//            if(this.options.anchor === "left-top"){
//                $(this.element).css({
//                    left: this.options.position[0],
//                    top: this.options.position[1]
//                });
//            }else if(this.options.anchor === "right-top"){
//                $(this.element).css({
//                    right: this.options.position[0],
//                    top: this.options.position[1]
//                });
//            }else if(this.options.anchor === "left-bottom"){
//                $(this.element).css({
//                    left: this.options.position[0],
//                    bottom: this.options.position[1]
//                });
//            }else if(this.options.anchor === "right-bottom"){
//                $(this.element).css({
//                    right: this.options.position[0],
//                    bottom: this.options.position[1]
//                });
//            }
            $(this.element).find('a.mapsetswitch').click($.proxy(self._toggleMapset, self));
            $(this.element).find('a#fullscreenswitch').click($.proxy(self._toggleFullscreen, self));
            $("html").bind("keydown", $.proxy(this._closeOnEscape, this));
            this._hideSources("");
            this._showActive();
        },
        _hideSources: function(addedClass){
            var me = $(this.element),
                    map = $('#' + this.options.target).data('mapbenderMbMap'),
                    model = map.getModel();
            //window.console && console.log("BaseSourceSwitcher hide start");
            $.each(me.find('a.mapsetswitch' + addedClass), function(idx, elm){
                var sourcesIds = $(elm).attr("data-mapset").split(",");
                for(var i = 0; i < sourcesIds.length; i++){
                    if(sourcesIds[i] !== ''){
                        var tochange = {change: {sourceIdx: {id: sourcesIds[i]}, options: {configuration: {options: {visibility: false}},type: 'selected'}}};
                        model.changeSource(tochange);
                    }
                }
            });
            //window.console && console.log("BaseSourceSwitcher hide end");
        },
        _showActive: function(){
            var me = $(this.element),
                    map = $('#' + this.options.target).data('mapbenderMbMap'),
                    model = map.getModel();
            //window.console && console.log("BaseSourceSwitcher show start");
            $.each(me.find('a.mapsetswitch.active'), function(idx, elm){
                var sourcesIds = $(elm).attr("data-mapset").split(",");
                for(var i = 0; i < sourcesIds.length; i++){
                    if(sourcesIds[i] !== ''){
                        var tochange = {change: {sourceIdx: {id: sourcesIds[i]}, options: {configuration: {options: {visibility: true}},type: 'selected'}}};
                        model.changeSource(tochange);
                    }
                }
            });
            //window.console && console.log("BaseSourceSwitcher show end");
        },
        _toggleMapset: function(event){
            var me = $(this.element),
                    map = $('#' + this.options.target).data('mapbenderMbMap'),
                    a = $(event.currentTarget);
            this._hideSources("");
            me.find('a.mapsetswitch').not(a).removeClass('active');
            a.addClass('active');
            this._showActive();
            return false;
        },
        _closeOnEscape: function(e){
            if(e.keyCode == $.ui.keyCode.ESCAPE && $('body.fullscreen').length){
                this._toggleFullscreen(e);
            }
        },
        _toggleFullscreen: function(event){
            var map = $('#' + this.options.target).data('mapbenderMbMap');
            var centerOpts = map.getCenterOptions();
            $('body').toggleClass('fullscreen');
            map.setCenter(centerOpts);
            return false;
        }


    });

})(jQuery);