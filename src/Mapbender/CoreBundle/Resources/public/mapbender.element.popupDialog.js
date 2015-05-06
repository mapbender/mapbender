(function($) {

    /**
     * jQuery dialog with bootstrap styles
     *
     * @author Andriy Oblivantsev <eslider@gmail.com>
     * @copyright 05.11.2014 by WhereGroup GmbH & Co. KG
     */
    $.widget("mapbender.popupDialog", $.ui.dialog, {

        /**
         * Constructor, runs only if the object wasn't created before
         *
         * @return {*}
         * @private
         */
        _create: function() {
            var element = $(this.element);
            var widget = this;

            // overrides default options
            $.extend(this.options, {
                show: {
                    effect:   "fadeIn",
                    duration: 300
                },
                hide: {
                    effect:   "fadeOut",
                    duration: 300
                }
            });

            //resize dialog height fix
            element.bind('popupdialogresize', function(e, ui) {
                var win = $(e.target).closest('.ui-dialog');
                var height = 0;
                $.each($('> .modal-header, > .modal-body, > .modal-footer', win), function(idx, el) {
                    height += $(el).outerHeight();
                });
                win.height(Math.round(height));
                //$("> .modal-body",win).height(height-40);
            });

            // prevent key listening outside the dialog
            element.on('keydown', function(e) {
                e.stopPropagation();
            });

            var result = this._super();

            element.dialogExtend($.extend(true, {
                closable:    true,
                maximizable: true,
                //dblclick: true,
                //minimizable: true,
                //modal: true,
                collapsable: true
            }, this.options));

            var dialog = element.closest('.ui-dialog');
            if(this.options.modal){
                var modal = $('<div class="mb-element-modal-dialog"><div class="background" unselectable="on"></div></div>');

                modal.insertBefore(dialog);
                modal.prepend(dialog);
                modal.find('> .background').on('click mousemove mouseout mouseover',function(e){
                    e.preventDefault();
                    e.stopPropagation();
                    return false;
                });
                element.bind('popupdialogclose',function(){
                    modal.fadeOut(function(){
                        modal.remove();
                    });
                });
            }

            // Fullscreen on double click
            $(dialog).dblclick(function(event) {
                var target = $(event.target);
                if(!target.is('.ui-dialog-titlebar, .ui-dialog-title')){
                    return;
                }

                if(element.dialogExtend('state') == 'normal'){
                    element.dialogExtend('maximize');
                }else{
                    element.dialogExtend('restore');
                }
            });

            return result;
        },

        /**
         * Overrides default open method, but adds some Bootstrap classes to the dialog
         * @return {}
         */
        open: function() {
            var content = $(this.element);
            var dialog = content.closest('.ui-dialog');
            var header = $('.ui-widget-header', dialog);
            var closeButton = $('.ui-dialog-titlebar-close', header);
            var dialogBody = $('.ui-dialog-content', dialog);
            var dialogBottomPane = $('.ui-dialog-buttonpane', dialog);
            var dialogBottomButtons = $('.ui-dialog-buttonset > .ui-button', dialogBottomPane);


            // Marriage of jQuery UI and Bootstrap
            dialog.addClass('modal-content');
            dialogBottomPane.addClass('modal-footer');
            dialogBottomButtons.addClass('button');
            header.addClass('modal-header');
            closeButton.addClass('close');
            dialogBody.addClass('modal-body');

            // Set as mapbender element
            dialog.addClass('mb-element-popup-dialog');
            return this._super();
        }
    });

})(jQuery);