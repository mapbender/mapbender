(function() {
    class MbInteractiveHelp extends MapbenderElement {
        constructor(configuration, $element) {
            super(configuration, $element);

            this.currentChapter = 0;
            Mapbender.elementRegistry.waitReady('.mb-element-map').then((mbMap) => {
                this._setup(mbMap);
            }, () => {
                Mapbender.checkTarget('mbInteractiveHelp');
            });
        }

        _setup(mbMap) {
            const allElements = Mapbender.configuration.elements;
            this.options.helptexts.chapters.forEach((text) => {
                const element = $('.' + text.element.classSelector).data(text.element.jsClassName);
                let elementId = element.$element.attr('id');
                if (element.checkDialogMode(element.$element)) {
                    for (let id in allElements) {
                        if (allElements[id].init === 'MbControlButton' && allElements[id].configuration.target === parseInt(elementId)) {
                            elementId = id;
                            break;
                        }
                    }
                }
                text.element['id'] = elementId;
            });

            let popover = $($('#interactiveHelpPopover').html());

            $('.runShowBtn').on('click', () => {
                const currentChapter = this.options.helptexts.chapters[this.currentChapter];
                popover.find('h6').text(Mapbender.trans(currentChapter.title));
                popover.find('p').text(Mapbender.trans(currentChapter.description));
                if (currentChapter.element.region === 'toolbar') {
                    popover.addClass('popover-toolbar');
                }
                $('#' + currentChapter.element.id).append(popover);
                console.log(currentChapter)
                if (currentChapter.element.region === 'sidepane') {
                    $('#' + currentChapter.element.id).parent().parent().prev().click();
                }
                this.currentChapter++;
            });
        }
    }

    window.Mapbender.Element = window.Mapbender.Element || {};
    window.Mapbender.Element.MbInteractiveHelp = MbInteractiveHelp;
})();
