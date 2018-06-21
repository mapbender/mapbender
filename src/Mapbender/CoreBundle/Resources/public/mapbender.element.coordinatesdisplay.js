(function ($) {
    'use strict';

    $.widget("mapbender.mbCoordinatesDisplay", $.mapbender.mbBaseElement, {
        options: {
            target: null,
            empty: 'x= -<br>y= -',
            prefix: 'x= ',
            separator: '<br/>y= ',
            suffix: ''
        },
        RADIX: 10,

        _create: function () {
            if (!Mapbender.checkTarget("mbCoordinatesDisplay", this.options.target)) {
                return;
            }

            Mapbender.elementRegistry.onElementReady(this.options.target, $.proxy(this._setup, this));
        },

        _setup: function () {
            this.map = Mapbender.elementRegistry.listWidgets().mapbenderMbMap;
            this.options.empty = this.options.empty || '';
            this.options.prefix = this.options.prefix || '';
            this.options.separator = this.options.separator || ' ';
            $(document).on('mbmapsrschanged', $.proxy(this._reset, this));

            this.options.numDigits = isNaN(parseInt(this.options.numDigits, this.RADIX))
                ? 0
                : parseInt(this.options.numDigits, this.RADIX);
            this.options.numDigits = this.options.numDigits < 0 ? 0 : this.options.numDigits;

            this._reset();
        },

        _reset: function (event, srs) {
            var model = this.map.model;
            var isdeg = model.getCurrentProjectionObject().getUnits() === 'dd';
            srs = { projection: {projCode: model.getCurrentProjectionCode()}};

            if (this.crs !== null && (this.crs === srs.projection.projCode)) {
                return;
            }

            var elementConfig = {
              target: $(this.element).attr('id'),
              emptyString: this.options.empty || '',
              prefix: this.options.prefix || '',
              separator: this.options.separator || ' ',
              suffix: this.options.suffix,
              numDigits: isdeg ? 5 + this.options.numDigits : this.options.numDigits,
              displayProjection: srs.projection
            };
            model.createMousePositionControl(elementConfig);
            this.crs = srs.projection.projCode;
        }
    });

})(jQuery);
