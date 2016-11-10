$(function(){

    $.widget("mapbender.mobilePane", {
        options:    {
            frames: []
        },
        /**
         * @private
         */
        _create:    function() {
            var widget = this;
            var element = $(widget.element)

        },

        _setOption: function(key, value) {
            this._super(key, value);
        },

        open:       function() {

        },

        close:      function() {

        }
    });

    $(document).on('mbfeatureinfofeatureinfo', function(e, options){
        if(options.action === 'haveresult') {
            $.each($('#mobilePane .mobileContent').children(), function(idx, item){
                $(item).addClass('hidden');
            });
            $('#' + options.id).removeClass('hidden');
            $('#mobilePane .contentTitle').text($('#' + options.id).attr('data-title'));
            $('#mobilePane').attr('data-state', 'opened');
        }
    });

    $('#footer').on('click', '.mb-element,.mb-button', function(e) {

        var button = $('#' + $(e.target).attr('for')).data('mapbenderMbButton');
        var buttonOptions = button.options;
        var target = $('#' + buttonOptions.target);
        var targets = target.data();
        var pane = target.parents('.mobilePane');
        var paneContent = $('.mobileContent', pane);
        var paneTitle = $('.contentTitle', pane);
        var hasPane = pane.size();

        if(buttonOptions.click) {
            if(buttonOptions.click && buttonOptions.click.length > 0 && buttonOptions.click.charAt(0) === '#') {
                return false;
            } else if(buttonOptions.click) {
                window.open(buttonOptions.click, '_blank');
                return false;
            }
        }

        if(!hasPane) {
            return false;
        }

        // hide frames
        $.each(paneContent.children(), function(idx, item) {
            $(item).addClass('hidden');
        });

        target.removeClass('hidden');
        paneTitle.text('undefined');

        var title, _widget, isWidget, _element, isWidget;
        for (var widgetName in targets) {
            _widget = targets[widgetName];
            isWidget = typeof _widget === 'object' && _widget.options;

            if(!isWidget) {
                continue;
            }

            if(typeof  _widget.open == "function") {
                _widget.open("mobile");
            }

            _element = _widget.element;
            paneTitle.text(_element.attr('title') ? _element.attr('title') : _element.data('title'));
            break;
        }

        pane.attr('data-state', 'opened');

        return false;
    });
    $('#mobilePaneClose').on('click', function(){
        $('#mobilePane').removeAttr('data-state');
    });
    $('.mb-element-basesourceswitcher li').on('click', function(e){
        $('#mobilePaneClose').click();
    });
    $('.mb-element-basesourceswitcher li').on('click', function(e){
        $('#mobilePaneClose').click();
    });
    $('.mb-element-simplesearch input[type="text"]').on('mbautocomplete.selected', function(e){
        $('#mobilePaneClose').click();
    });
    /* START close mobilePane if a map is centred after search */
    var moved = false;
    Mapbender.elementRegistry.onElementReady("map", function() {
        Mapbender.Model.mbMap.map.olMap.events.register('moveend', null, function() {
            if($('#mobilePane').attr('data-state')) {
                moved = true;
            } else {
                moved = false;
            }
        });
    });

    $('.search-results').on('click', function(){
        if(moved){
            moved = false;
            $('#mobilePaneClose').click();
        }
    });
    /* END close mobilePane if a map is centred after search */
    /* START center notifyjs dialog */
    $.notify.defaults({position: "top left"});
    /* END center notifyjs dialog */
});