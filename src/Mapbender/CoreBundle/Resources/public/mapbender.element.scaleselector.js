(function($) {

    $.widget("mapbender.mbScaleSelector", {
        options:      {
            target: null
        },
        elementUrl:   null,
        _create:      function() {
            var widget = this;
            if(!Mapbender.checkTarget("mbScaleSelector", widget.options.target)) {
                return;
            }
            var element = $(widget.element);
            widget.elementUrl = Mapbender.configuration.elementPath + element.attr('id') + '/';
            Mapbender.elementRegistry.onElementReady(widget.options.target, $.proxy(widget._setup, widget));
        },
        _setup:       function() {
            var widget = this;
            var options = widget.options;
            var mbMap = $('#' + options.target).data('mapbenderMbMap');
            var scale = mbMap.model.getScale();
            var scales = mbMap.scales();
            var select = $("select", $(widget.element));
            $.each(scales, function(idx, value) {
                value = Math.round(value);
                select.append('<option value="' + value + '">' + value + '</option>');
            });

            select.change($.proxy(widget._zoomToScale, widget));
            $('option[value="' + scale + '"]', select).attr('selected', true);
            initDropdown.call(this.element.get(0));
            widget._updateScale();
            mbMap.map.olMap.events.register('zoomend', widget, $.proxy(widget._updateScale, widget));
            widget._trigger('ready');
            widget._ready();
        },
        _zoomToScale: function() {
            var scale = $("#" + $(this.element).attr('id') + " select").val();
            var map = $('#' + this.options.target).data('mapbenderMbMap');
            map.zoomToScale(scale, true);
        },
        _updateScale: function() {
            var mbMap = $('#' + this.options.target).data('mapbenderMbMap');
            var scale = mbMap.model.getScale();
            var select = $("#" + $(this.element).attr('id') + " select");
            select.val(scale).siblings(".dropdownValue").text(scale);
        },
        ready:        function(callback) {
            if(this.readyState === true) {
                callback();
            } else {
                this.readyCallbacks.push(callback);
            }
        },
        _ready:       function() {
            for (callback in this.readyCallbacks) {
                callback();
                delete(this.readyCallbacks[callback]);
            }
            this.readyState = true;
        },
        _destroy:     $.noop
    });

})(jQuery);

