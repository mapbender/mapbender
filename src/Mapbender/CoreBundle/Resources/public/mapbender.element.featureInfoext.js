(function($) {

    $.widget("mapbender.mbFeatureInfoExt", {
        options: {
            title: '',
            map: null,
            featureinfo: null,
            highlightsource: [],
            loadWms: []
        },
        map: null,
        geomElmSelector: '[data-geometry][data-srid]',
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
            $(document).on('mbfeatureinfofeatureinfo', $.proxy(this._featureInfoChanged, this));
            this._trigger('ready');
            this._ready();
        },
        _getHighLighter: function(key) {
            if (!this.highlighter[key]) {
                this.highlighter[key] = new Mapbender.Highlighting(this.map);
            }
            return this.highlighter[key];
        },
        _highLight: function(geometryElms, highLighterName, turn_on){
            var self = this;
            geometryElms.each(function(idx, item){
                var el = $(item);
                if (el.data('geometry') && el.data('srid')) {
                    var geometry = OpenLayers.Geometry.fromWKT(el.data('geometry'));
                    var proj = self.map.getModel().getProj(el.data('srid'));
                    if(turn_on){
                        self._getHighLighter(highLighterName).on(geometry, proj);
                    } else {
                        self._getHighLighter(highLighterName).off(geometry, proj);
                    }
                    
                }
            });
        },
        _highLightOn: function(fi_el_id, container_id) {
            var self = this;
            var baseSelector = '#' + fi_el_id + ' #' + container_id;
            if(this.featureinfo.options.showOriginal) {
                self._highLight($(baseSelector + ' iframe').contents().find(self.geomElmSelector), container_id, true);
            } else {
                this._highLight($(baseSelector + ' ' + this.geomElmSelector), container_id, true);
            }
        },
        _featureInfoMouseEventsOn: function(fi_el_id, container_id) {
            this.eventIdentifiers.featureinfo_mouse = {
                fiid: fi_el_id,
                cid: container_id
            };
            var baseSelector = '#' + fi_el_id + ' #' + container_id;
            if(this.featureinfo.options.showOriginal) {
                $(baseSelector + ' iframe').contents().find(this.geomElmSelector).on('mouseover', $.proxy(this._showGeometry, this));
                $(baseSelector + ' iframe').contents().find(this.geomElmSelector).on('mouseout', $.proxy(this._hideGeometry, this));
            } else {
                $(baseSelector + ' ' + this.geomElmSelector).on('mouseover', $.proxy(this._showGeometry, this));
                $(baseSelector + ' ' + this.geomElmSelector).on('mouseout', $.proxy(this._hideGeometry, this));
            }
        },
        _featureInfoMouseEventsOff: function() {
            if(this.eventIdentifiers.featureinfo_mouse.fiid && this.eventIdentifiers.featureinfo_mouse.cid){
                var fi_el_id = this.eventIdentifiers.featureinfo_mouse.fiid;
                var container_id = this.eventIdentifiers.featureinfo_mouse.cid;
                this.eventIdentifiers.featureinfo_mouse = {};
                var baseSelector = '#' + fi_el_id + ' #' + container_id;
                if(this.featureinfo.options.showOriginal) {
                    $(baseSelector + ' iframe').contents().find(this.geomElmSelector).off('mouseover', $.proxy(this._showGeometry, this));
                    $(baseSelector + ' iframe').contents().find(this.geomElmSelector).off('mouseout', $.proxy(this._hideGeometry, this));
                } else {
                    $(baseSelector + ' ' + this.geomElmSelector).off('mouseover', $.proxy(this._showGeometry, this));
                    $(baseSelector + ' ' + this.geomElmSelector).off('mouseout', $.proxy(this._hideGeometry, this));
                }
            }
        },
        _showGeometry: function(e){
            var el = $(e.currentTarget);
            if (el.data('geometry') && el.data('srid')) {
                var geometry = OpenLayers.Geometry.fromWKT(el.data('geometry'));
                var proj = this.map.getModel().getProj(el.data('srid'));
                this._getHighLighter('mouse').on(geometry, proj);
            }
        },
        _hideGeometry: function(e){
            var el = $(e.currentTarget);
            if (el.data('geometry') && el.data('srid')) {
                var geometry = OpenLayers.Geometry.fromWKT(el.data('geometry'));
                var proj = this.map.getModel().getProj(el.data('srid'));
                this._getHighLighter('mouse').off(geometry, proj);
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
                if(this.options.highlightsource){
                    for (var key in this.highlighter) {
                        this.highlighter[key].offAll();
                    }
                    this._featureInfoMouseEventsOff();
                    this._featureInfoMouseEventsOn(options.id, options.activated_content);
                    for(var i = 0; i < options.activated_content.length; i++){
                        this._highLightOn(options.id, options.activated_content);
                    }
                }
                if(this.options.highlightsource){
                    this._loadWmsOn(options.id, options.activated_content);
                }
            } else if (options.action === 'closedialog' || options.action === 'deactivate') {
                if(this.options.highlightsource){
                    this._featureInfoMouseEventsOff();
                    for (var key in this.highlighter) {
                        this.highlighter[key].offAll();
                    }
                }
                if(this.options.highlightsource){
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
