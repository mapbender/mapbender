(function($) {

    $.widget("mapbender.mbFeatureInfoExt", {
        options: {
            title: '',
            map: null,
            featureinfo: null,
            highlight_source: true,
            load_declarative_wms: true,
            hits_style: {
                'strokeColor': '#99FF99',
                'strokeOpacity': 1,
                'strokeWidth': 1,
                'strokeLinecap': 'round',
                'strokeDashstyle': 'solid',
                'fillColor': "#99FF99",
                'fillOpacity': 0.4,
                'pointRadius': 6
            },
            hover_style: {
                'strokeColor': '#FF9999',
                'strokeOpacity': 1,
                'strokeWidth': 1,
                'strokeLinecap': 'round',
                'strokeDashstyle': 'solid',
                'fillColor': "#FF9999",
                'fillOpacity': 0.4,
                'pointRadius': 6
            }
        },
        map: null,
        geomElmSelector: '.geometryElement',//'[data-geometry][data-srid]',//
        loadWmsSelector: '[mb-action]',
        featureinfo: null,
        highlighter: {},
        eventIdentifiers: {
            featureinfo_mouse: {},
            loadwms_mouse: {}
        },
        _create: function() {
            if (!Mapbender.checkTarget("mbFeatureInfoExt", this.options.map) || !Mapbender.checkTarget(
                "mbFeatureInfoExt", this.options.featureinfo)) {
                return;
            }
            Mapbender.elementRegistry.onElementReady(this.options.map, $.proxy(this._setMap, this));
            Mapbender.elementRegistry.onElementReady(this.options.featureinfo, $.proxy(this._setFeatureInfo, this));
        },
        _setMap: function() {
            this.map = $("#" + this.options.map).data("mapbenderMbMap");
            this._setup();
        },
        
        _setFeatureInfo: function() {
            this.featureinfo = $("#" + this.options.featureinfo).data("mapbenderMbFeatureInfo");
            this._setup();
        },
        _setup: function() {
            var self = this;
            if (!this.map || !this.featureinfo) {
                return;
            }
            Mapbender.Model.highlightOptions.style.feature = this.options.hits_style;
            Mapbender.Model.highlightOptions.style.hover = this.options.hover_style;
            $(document).on('mbfeatureinfofeatureinfo', $.proxy(this._featureInfoChanged, this));
            this._trigger('ready');
            this._ready();
        },
        _getHighLighter: function(key) {
            if (!this.highlighter[key]) {
                this.highlighter[key] = new Mapbender.Highlighting(this.map, {}, key === 'mouse' ? this.options.hover_style : this.options.hits_style);
            }
            return this.highlighter[key];
        },
        _readGeometry: function(element){
            if (element.data('geometry')) {
                try {
                    return OpenLayers.Geometry.fromWKT(element.data('geometry'));
                } catch(e){
                    Mapbender.error('Geometry cannot be created!');
                    return null;
                }
            } else {
                Mapbender.error('WKT-geometry cannot be found!');
                return null;
            }
        },
        _readProj: function(element){
            if (element.data('srid')) {
                try {
                    return this.map.getModel().getProj(element.data('srid'));
                } catch(e){
                    Mapbender.error('Projection cannot be created!');
                    return null;
                }
            } else {
                Mapbender.error('Srid cannot be found!');
                return null;
            }
        },
        _highLightOn: function(fi_el_id, container_id) {
            var self = this;
            var baseSelector = '#' + fi_el_id + ' #' + container_id;
            var geometryElms = [];
            if(this.featureinfo.options.showOriginal) {
                geometryElms = $(this.geomElmSelector, $(baseSelector + ' iframe').contents());
            } else {
                geometryElms = $(baseSelector + ' ' + this.geomElmSelector);
            }
            var geometries = [];
            geometryElms.each(function(idx, item){
                var el = $(item);
                var geometry = self._readGeometry(el);
                var proj = self._readProj(el);
                if (geometry && proj) {
                    geometries.push({geometry: geometry, srs: proj});
                }
            });
            self._getHighLighter(container_id).on(geometries);
        },
        _featureInfoMouseEventsOn: function(options) {
            var self = this;
            var fi_el_id = options.id;
            var container_id = options.activated_content;
            this.eventIdentifiers.featureinfo_mouse = {
                fiid: fi_el_id,
                cid: container_id
            };
            var baseSelector = '#' + fi_el_id + ' #' + container_id;
            if (this.featureinfo.options.showOriginal) {
                var iframe = $(baseSelector + ' iframe').contents();
                var interval = null;
                interval = window.setInterval(function() {
                    if (iframe) {
                        if (iframe.get(0).readyState === 'complete') {
                            window.clearInterval(interval);
                            $(self.geomElmSelector, $(baseSelector + ' iframe').contents()).on('mouseover', $.proxy(
                                self._showGeometry, self));
                            $(self.geomElmSelector, $(baseSelector + ' iframe').contents()).on('mouseout', $.proxy(
                                self._hideGeometry, self));
                            for(var i = 0; i < options.activated_content.length; i++){
                                self._highLightOn(options.id, options.activated_content);
                            }
                        }
                    } else {
                        window.clearInterval(interval);
                    }
                }, 50);
            } else {
                $(baseSelector + ' ' + this.geomElmSelector).on('mouseover', $.proxy(this._showGeometry, this));
                $(baseSelector + ' ' + this.geomElmSelector).on('mouseout', $.proxy(this._hideGeometry, this));
                for(var i = 0; i < options.activated_content.length; i++){
                    this._highLightOn(options.id, options.activated_content);
                }
            }
        },
        _featureInfoMouseEventsOff: function() {
            if(this.eventIdentifiers.featureinfo_mouse.fiid && this.eventIdentifiers.featureinfo_mouse.cid){
                var fi_el_id = this.eventIdentifiers.featureinfo_mouse.fiid;
                var container_id = this.eventIdentifiers.featureinfo_mouse.cid;
                this.eventIdentifiers.featureinfo_mouse = {};
                var baseSelector = '#' + fi_el_id + ' #' + container_id;
                if(this.featureinfo.options.showOriginal) {
                    $(this.geomElmSelector, $(baseSelector + ' iframe').contents()).off('mouseover', $.proxy(this._showGeometry, this));
                    $(this.geomElmSelector, $(baseSelector + ' iframe').contents()).off('mouseout', $.proxy(this._hideGeometry, this));
                } else {
                    $(baseSelector + ' ' + this.geomElmSelector).off('mouseover', $.proxy(this._showGeometry, this));
                    $(baseSelector + ' ' + this.geomElmSelector).off('mouseout', $.proxy(this._hideGeometry, this));
                }
            }
        },
        _showGeometry: function(e){
            var el = $(e.currentTarget);
            var geometry = this._readGeometry(el);
            var proj = this._readProj(el);
            if (geometry && proj) {
                this._getHighLighter('mouse').on([{geometry: geometry, srs: proj}]);
            }
        },
        _hideGeometry: function(e){
            var el = $(e.currentTarget);
            var geometry = this._readGeometry(el);
            var proj = this._readProj(el);
            if (geometry && proj) {
                this._getHighLighter('mouse').off();
            }
        },
        _clickLoadWms: function(e){
            var clel = $(e.currentTarget);
            if(Mapbender.declarative && Mapbender.declarative[clel.attr('mb-action')]
                && typeof Mapbender.declarative[clel.attr('mb-action')] === 'function'){
                e.preventDefault();
                Mapbender.declarative[clel.attr('mb-action')](clel);
            }
            return false;
        },
        _loadWmsOn: function(fi_el_id, container_id) {
            this.eventIdentifiers.loadwms_mouse = {
                fiid: fi_el_id,
                cid: container_id
            };
            var baseSelector = '#' + fi_el_id + ' #' + container_id;
            if(this.featureinfo.options.showOriginal) {
                $(baseSelector + ' iframe').contents().find(this.loadWmsSelector).on('click', $.proxy(this._clickLoadWms, this));
            } else {
                $(baseSelector + ' ' + this.loadWmsSelector).on('click', $.proxy(this._clickLoadWms, this));
            }
        },
        _loadWmsOff: function() {
            if(this.eventIdentifiers.loadwms_mouse.fiid && this.eventIdentifiers.loadwms_mouse.cid){
                var fi_el_id = this.eventIdentifiers.loadwms_mouse.fiid;
                var container_id = this.eventIdentifiers.loadwms_mouse.cid;
                this.eventIdentifiers.loadwms_mouse = {};
                var baseSelector = '#' + fi_el_id + ' #' + container_id;
                if(this.featureinfo.options.showOriginal) {
                    $(baseSelector + ' iframe').contents().find(this.loadWmsSelector).off('click', $.proxy(this._clickLoadWms, this));
                } else {
                    $(baseSelector + ' ' + this.loadWmsSelector).off('click', $.proxy(this._clickLoadWms, this));
                }
            }
        },
        _featureInfoChanged: function(e, options) {
            if (options.action === 'activated_content') {
                if(this.options.highlight_source){
                    for (var key in this.highlighter) {
                        this.highlighter[key].offAll();
                    }
                    this._featureInfoMouseEventsOff();
                    this._featureInfoMouseEventsOn(options);
                }
                if(this.options.load_declarative_wms){
                    this._loadWmsOn(options.id, options.activated_content);
                }
            } else if (options.action === 'closedialog' || options.action === 'deactivate' || options.action === 'clicked') {
                if(this.options.highlight_source){
                    this._featureInfoMouseEventsOff();
                    for (var key in this.highlighter) {
                        this.highlighter[key].offAll();
                    }
                }
                if(this.options.load_declarative_wms){
                    this._loadWmsOff(options.id, options.activated_content);
                }
            }
        },
        /**
         *
         */
        ready: function(callback) {
            if (this.readyState === true) {
                callback();
            } else {
                this.readyCallbacks.push(callback);
            }
        },
        /**
         *
         */
        _ready: function() {
            for (callback in this.readyCallbacks) {
                callback();
                delete(this.readyCallbacks[callback]);
            }
            this.readyState = true;
        }
    });
})(jQuery);

(function($) {
    if($.MapQuery){ // MpaQuery creates automatic Control.SelectFeature -> overwrite function _updateSelectFeatureControl
        $.MapQuery.Map.prototype._updateSelectFeatureControl = function(a){ return; };
    }
    if(Mapbender.Model.highlightOptions && Mapbender.Model.highlightOptions.feature){
        Mapbender.Model.highlightOptions.feature["stopClick"] = false;
    }
})(jQuery);