(function() {

    class MbSimpleSearch extends MapbenderElement {
        constructor(configuration, $element) {
            super(configuration, $element);
            this.initialised = false;
            this.layer = null;
            this.mbMap = null;
            this.iconUrl_ = null;
            this.selectedConfiguration = 0;
            this.autocompleteMinLength = 2;

            // Former _create body
            this._setSelectedConfiguration(this._getSavedConfiguration());
            this.initializeAutocompletePosition_();
            Mapbender.elementRegistry.waitReady('.mb-element-map').then((mbMap) => {
                this.mbMap = mbMap;
                this._setup();
            });
        }

        _setSelectedConfiguration(index) {
            this.selectedConfiguration = parseInt(index);
            if (!(this.selectedConfiguration in this.options['configurations'])) {
                this.selectedConfiguration = 0;
            }
            const configuration = this.options['configurations'][this.selectedConfiguration];

            this.iconUrl_ = configuration.result_icon_url || null;

            if (configuration.result_icon_url && !/^(\w+:)?\/\//.test(configuration.result_icon_url)) {
                // Local, asset-relative
                var parts = [
                    Mapbender.configuration.application.urls.asset.replace(/\/$/, ''),
                    configuration.result_icon_url.replace(/^\//, '')
                ];
                this.iconUrl_ = parts.join('/');
            }

            if (this.initialised) {
                this.searchInput.autocomplete('option', {
                    delay: configuration.delay || 300,
                });
                this.searchInput.attr('placeholder', Mapbender.trans(configuration.placeholder || configuration.title));
                this._saveConfiguration(this.selectedConfiguration);
            } else {
                this.$element.find('.-fn-simple_search-toggle-dropdown').find('[data-value=' + index + ']').trigger('click');
            }
        }

        _getSavedConfiguration() {
            if (!window.localStorage) return 0;
            const storedJson = localStorage.getItem('mb.simple-search');
            if (!storedJson) return 0;
            let storedConfiguration;
            try {
                storedConfiguration = JSON.parse(storedJson);
            } catch (e) {
                return 0;
            }
            if (this.$element.attr('id') in storedConfiguration) {
                return storedConfiguration[this.$element.attr('id')];
            }
            return 0;
        }

        _saveConfiguration(index) {
            if (!window.localStorage) return 0;
            let storedConfiguration = {};
            const storedJson = localStorage.getItem('mb.simple-search');
            if (storedJson) {
                try {
                    storedConfiguration = JSON.parse(storedJson);
                } catch (e) {
                    storedConfiguration = {};
                }
            }
            storedConfiguration[this.$element.attr('id')] = index;
            localStorage.setItem('mb.simple-search', JSON.stringify(storedConfiguration));
        }

        initializeAutocompletePosition_() {
            /** @see https://api.jqueryui.com/autocomplete/#option-position */
            var vertical = this.$element.closest('.toolBar.bottom,.anchored-element-wrap-lb,.anchored-element-wrap-rb').length ? 'up' : 'down';
            var horizontal = 'right';
            if (this.$element.closest('.toolBar,.anchored-element-wrap').length) {
                var windowWidth = document.documentElement.clientWidth;
                var node = this.$element.get(0);
                var ownWidth = node.clientWidth;
                var distanceLeft = 0;
                do {
                    distanceLeft += node.offsetLeft;
                    node = node.offsetParent;
                } while (node);
                var distanceRight = windowWidth - distanceLeft - ownWidth;
                if (windowWidth && distanceRight >= windowWidth / 2) {
                    horizontal = 'right';
                } else {
                    horizontal = 'left';
                }
            }
            // Adds .left-up / .left-down / .right-up / .right-down
            $('.autocompleteWrapper', this.$element).addClass([horizontal, vertical].join('-'));
        }

        _setup() {
            var self = this;
            const configuration = this.options['configurations'][this.selectedConfiguration];
            this.searchInput = $('.searchterm', this.$element);
            this.searchIcon = $('.-fn-search-icon', this.$element);
            this.clearIcon = $('.-fn-reset', this.$element);
            this.searchInput.attr('placeholder', Mapbender.trans(configuration.placeholder || configuration.title));

            this.clearIcon.on('click', () => this._clearInputAndMarker());

            this._updateSearchAndClearIconState();
            this.searchInput.on('input focus blur', () => {
                this._updateSearchAndClearIconState();
            });

            this.$element.on('change', '.-fn-simple_search-select-configuration', function(e) {
                const selectedVal = $(e.target).val();
                if (selectedVal < 0 || selectedVal >= this.options.configurations.length) return;
                this._setSelectedConfiguration(selectedVal)
            }.bind(this));
            const form = this.$element.find('form').get(0);
            this.layer = Mapbender.vectorLayerPool.getElementLayer(this, 0);
            if (this.iconUrl_) {
                var offset = (configuration.result_icon_offset || '').split(new RegExp('[, ;]')).map(function(x) {
                    return parseInt(x) || 0;
                });
                this.layer.addCustomIconMarkerStyle('simplesearch', this.iconUrl_, offset[0], offset[1]);
            }

            this.searchInput.autocomplete({
                appendTo: this.searchInput.closest('.autocompleteWrapper').get(0),
                delay: configuration.delay || 300,
                minLength: this.autocompleteMinLength,
                /** @see https://api.jqueryui.com/autocomplete/#option-source */
                source: this._queryAutocomplete.bind(this),
                position: {
                    of: false
                },
                classes: {
                    'ui-autocomplete': 'dropdownList'
                },
                select: function(event, ui) {
                    self._onAutocompleteSelected(ui.item);
                }
            });
            // On manual submit (enter key), trigger autocomplete manually
            this.$element.on('submit', function(evt) {
                evt.preventDefault();
                if (form && form.reportValidity && !form.reportValidity()) return;
                this.searchInput.autocomplete("search");
            }.bind(this));
            this.mbMap.element.on('mbmapsrschanged', function(event, data) {
                self.layer.retransform(data.from, data.to);
            });
            this.initialised = true;
            Mapbender.elementRegistry.markReady(this);
        }

        _queryAutocomplete(request, responseCallback) {
            const term = this._tokenize(request.term);
            const url = Mapbender.configuration.application.urls.element + '/' + this.$element.attr('id') + '/search';

            if (!term || term.length < this.autocompleteMinLength) {
                responseCallback([]);
                return;
            }
            $.getJSON(url, {term: term, selectedConfiguration: this.selectedConfiguration})
                .then((response) => {
                    const formatted = (response || [])
                        .map((item) => Object.assign(item, {
                            label: this._formatLabel(item)
                        }))
                        .filter((item) => {
                            const geomEmpty = !item[this.options['configurations'][this.selectedConfiguration].geom_attribute];
                            if (geomEmpty) {
                                console.warn("Missing geometry in SimpleSearch item", item);
                            }
                            return item.label && !geomEmpty;
                        });
                    responseCallback(formatted);
                })
                .fail((err) => {
                    Mapbender.handleAjaxError(err, () => this._queryAutocomplete(request, responseCallback));
                })
            ;
        }

        _updateSearchAndClearIconState() {
            if (this.searchInput.val().length > 0) {
                this.searchIcon.hide();
                this.clearIcon.show();
                this.searchInput.removeClass('with-icon');
            } else {
                this.searchIcon.show();
                this.clearIcon.hide();
                this.searchInput.addClass('with-icon');
            }
        }

        _parseFeature(doc) {
            const configuration = this.options['configurations'][this.selectedConfiguration];
            switch ((configuration.geom_format || '').toUpperCase()) {
                case 'WKT':
                    return this.mbMap.getModel().parseWktFeature(doc, configuration.sourceSrs);
                case 'GEOJSON':
                    return this.mbMap.getModel().parseGeoJsonFeature(doc, configuration.sourceSrs);
                default:
                    throw new Error("Invalid geom_format " + configuration.geom_format);
            }
        }

        /**
         * @param {Object} obj
         * @param {String} path
         * @return {string|null}
         */
        _extractAttribute(obj, path) {
            var props = obj;
            var parts = path.split('.');
            var last = parts.pop();
            for (var i = 0; i < parts.length; ++i) {
                props = props && props[parts[i]];
                if (!props) {
                    break;
                }
            }
            if (props && (props[last] || (typeof props[last] === 'number'))) {
                return [props[last]].join('');  // force to string
            } else {
                return null;
            }
        }

        _formatLabel(doc) {
            // Find / match '${attribute_name}' / '${nested.attribute.path}' placeholders
            const configuration = this.options['configurations'][this.selectedConfiguration];
            var templateParts = configuration.label_attribute.split(/\${([^}]+)}/g);
            if (templateParts.length > 1) {
                var parts = [];
                for (var i = 0; i < templateParts.length; i += 2) {
                    var fixedText = templateParts[i];
                    // NOTE: attributePath is undefined (index >= length of list) if label_attribute defines static text after last placeholder
                    var attributePath = templateParts[i + 1];
                    var attributeValue = attributePath && this._extractAttribute(doc, attributePath);
                    if (attributeValue) {
                        parts.push(fixedText);
                        parts.push(attributeValue);
                    } else {
                        // Show text before label component only if attribute data was non-empty
                        if (!attributePath) {
                            parts.push(fixedText);
                        }
                    }
                }
                return parts.join('').replace(/(^[\s.,:]+)|([\s.,:]+$)/g, '');
            } else {
                return this._extractAttribute(doc, configuration.label_attribute);
            }
        }

        _onAutocompleteSelected(item) {
            const configuration = this.options['configurations'][this.selectedConfiguration];
            var feature = this._parseFeature(item[configuration.geom_attribute]);

            var zoomToFeatureOptions = {
                maxScale: parseInt(configuration.result_maxscale) || null,
                minScale: parseInt(configuration.result_minscale) || null,
                buffer: parseInt(configuration.result_buffer) || null
            };
            this.mbMap.getModel().zoomToFeature(feature, zoomToFeatureOptions);
            this._hideMobile();
            this._setFeatureMarker(feature);

            // Move cursor to the beginning of the input field
            setTimeout(() => {
                var inputElement = this.searchInput.get(0);
                if (inputElement) {
                    inputElement.focus();
                    inputElement.setSelectionRange(0, 0);
                }
            }, 10);
        }

        _setFeatureMarker(feature) {
            this.layer.clear();
            Mapbender.vectorLayerPool.raiseElementLayers(this);

            if (feature.getGeometry().getType() === 'Point') {
                var layer = this.layer;
                // @todo: add feature center / centroid api
                var bounds = Mapbender.mapEngine.getFeatureBounds(feature);
                var center = {
                    lon: .5 * (bounds.left + bounds.right),
                    lat: .5 * (bounds.top + bounds.bottom)
                };
                // fallback for broken icon: render a simple point geometry
                const onMissingIcon = () => layer.addMarker(center.lon, center.lat);
                if (this.iconUrl_) {
                    layer.addIconMarker('simplesearch', center.lon, center.lat).then(null, onMissingIcon);
                } else {
                    onMissingIcon();
                }
            } else {
                this.layer.addNativeFeatures([feature]);
            }
        }

        _hideMobile() {
            $('.mobileClose', $(this.$element).closest('.mobilePane')).click();
        }

        _tokenize(string) {
            const configuration = this.options['configurations'][this.selectedConfiguration];
            if (!(configuration.token_regex_in && configuration.token_regex_out)) return string;

            if (configuration.token_regex) {
                var regexp = new RegExp(configuration.token_regex, 'g');
                string = string.replace(regexp, " ");
            }

            var tokens = string.split(' ');
            var regex = new RegExp(configuration.token_regex_in);
            for(var i = 0; i < tokens.length; i++) {
                tokens[i] = tokens[i].replace(regex, configuration.token_regex_out);
            }

            return tokens.join(' ');
        }

        _clearInputAndMarker() {
            this.searchInput.val('');
            this._updateSearchAndClearIconState();
            this.layer.clear();
        }
    }

    window.Mapbender.Element = window.Mapbender.Element || {};
    window.Mapbender.Element.MbSimpleSearch = MbSimpleSearch;

})();
