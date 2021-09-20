(function($) {
    // @todo v3.3: Remove this file
    var Autocomplete = function(input, options){
        console.error('DEPRECATED: Custom FOM Autocomplete widget is abandoned and will be removed in v3.3. Use jQueryUI autocomplete instead.');
        var self = this;
        this.input = $(input);
        for(var name in options){
            if(name === 'url'){
                this.options[name] = options[name];
            }else if('undefined' !== typeof this.options[name]){ // replace options attribute
                this.options[name] = options[name];
            }else if(typeof this[name] == 'function'){ // replace function
                this[name] = options[name];
            }
        }
        this.autocompleteList = this.input.parent(".autocompleteWrapper").find(".autocompleteList");
        if(!this.options.url || !this.autocompleteList)
            window.console && console.error("mbAutoComplete can't be implemented.");
        else{
            if(this.options.delay > 0) {
                // @todo: delayed triggering
                this.input.on('keyup', function(e){
                    self.autocompleteList.html('').hide();

                    if($(e.target).val().length < self.options.minLength) {
                        return;
                    }

                    if(null !== this.delay) {
                        clearTimeout(this.delay);
                    }
                    this.delay = setTimeout(function() {
                        self.find($(e.target).val());
                    }, self.options.delay);
                });
            }
        }
    };
    Autocomplete.prototype = {
        delay: null,
        data: null,
        options: {
            delay: 300,
            minLength: 2,
            requestType: 'GET',
            requestParamTerm: 'term',
            requestParamMaxresults: 'maxresults',
            requestValueMaxresults: 10,
            dataType: "json",
            dataIdx: 'idx',
            dataTitle: 'title',
            preProcessor: null
        },
        find: function(term){
            var self = this;
            var data = {};

            var _term = term;
            if('function' == typeof this.options.preProcessor) {
                _term = this.options.preProcessor(term);
            }

            data[this.options.requestParamMaxresults] = this.options.requestValueMaxresults;
            data[this.options.requestParamTerm] = _term;
            $.ajax({
                url: this.options.url,
                type: this.options.requestType,
                data: data,
                dataType: this.options.dataType,
                success: $.proxy(self.open, self),
                error: function(data){
                    window.console && console.error("mbAutoComplete");
                }
            });
        },
        select: function(e){
            var target = $(e.target);
            var index = $('li', target.parent()).index(target);
            this.selected = {
                idx: target.attr('data-idx'),
                title: target.text(),
                data: this.data[index]
            };
            this.input.val(this.selected.title);
            this.close();

            this.input.trigger('mbautocomplete.selected', this.selected);
        },
        open: function(data){
            this.selected = null;
            if(data.length > 0){
                var self = this;
                this.data = data;
                var res = "<ul>";
                $.each(data, function(idx, item){
                    var itemIndex = self.options.dataIdx ? item[self.options.dataIdx] : idx;
                    res += '<li data-idx="' + itemIndex + '">' + item[self.options.dataTitle] + '</li>';
                });
                res += "</ul>";
                this.autocompleteList.html(res).show();
                this.autocompleteList.find('li').on('click', $.proxy(self.select, self));
            }
        },
        close: function(){
            this.autocompleteList.html('').hide();
        }
    };
    window.FOM = window.FOM || {};
    window.FOM.Autocomplete = Autocomplete;
    if (!window.Mapbender || !window.Mapbender.Autocomplete) {
        window.Mapbender = window.Mapbender || {};
        window.Mapbender.Autocomplete = FOM.Autocomplete;
    }
})(jQuery);

// TODO: why it's here?
$('body').delegate(':input', 'keyup', function(event){
    event.stopPropagation();
});