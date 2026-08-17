class SimpleAttributeTable {

    constructor(container, input, properties, propertyTitles) {
        this.container = container;
        this.input = input;
        this.propertyTitles = propertyTitles;
        this.properties = properties;
        this._invertOnChange = false;
        this._initCheckboxes();
        this._initSortable();
    }

    _initCheckboxes() {
        if (!this.container || this.container.dataset.loaded === 'true') return;

        let selected = [];
        if (this.input && this.input.value) {
            try {
                selected = JSON.parse(this.input.value);
            } catch (e) {
            }
            if (!Array.isArray(selected)) selected = [];
        }

        this._renderCheckboxes(selected);
    }

    _initSortable() {
        $(this.container).sortable({
            cursor: 'move',
            axis: 'y',
            items: '.form-check',
            distance: 6,
            cancel: 'input',
            containment: 'parent',
            stop: () => {
                this._syncHiddenField();
            }
        });
    }



    _renderCheckboxes(selected) {
        this.container.dataset.loaded = 'true';
        this.container.innerHTML = '';

        if (this.properties.length === 0) {
            this.container.innerHTML = '<span class="text-muted small">' + (this.container.dataset.emptyText || 'No properties') + '</span>';
            return;
        }

        // show the already selected items first
        [...selected, ...this.properties.filter((item) => !selected.includes(item))].forEach(prop => {
            const id = 'attribute_cb_' + Math.random().toString(36).substring(2, 8);
            const wrapper = document.createElement('div');
            wrapper.className = 'form-check simple-attribute-table-prop-check';

            const cb = document.createElement('input');
            cb.type = 'checkbox';
            cb.className = 'form-check-input';
            cb.id = id;
            cb.value = prop;
            cb.checked = selected.includes(prop);
            cb.addEventListener('click', (event) => {
                this._invertOnChange = event.shiftKey;
            });
            cb.addEventListener('change', () => {
                if (this._invertOnChange) {
                    this._invertCheckboxes(cb);
                }
                this._invertOnChange = false;
                this._syncHiddenField();
            });

            const label = document.createElement('label');
            label.className = 'form-check-label small';
            label.htmlFor = id;
            const title = this.propertyTitles?.[prop];
            if (title) {
                label.textContent = title + ' ';
                const keySpan = document.createElement('span');
                keySpan.className = 'prop-key';
                keySpan.textContent = '(' + prop + ')';
                label.appendChild(keySpan);
            } else {
                label.textContent = prop;
            }

            wrapper.appendChild(cb);
            wrapper.appendChild(label);
            this.container.appendChild(wrapper);
        });
    }

    _invertCheckboxes(except) {
        this.container.querySelectorAll('input[type="checkbox"]').forEach(cb => {
            if (cb !== except) {
                cb.checked = !cb.checked;
            }
        });
    }

    _syncHiddenField() {
        if (!this.input) return;
        const checked = [];
        this.container.querySelectorAll('input[type="checkbox"]:checked').forEach(cb => {
            checked.push(cb.value);
        });
        this.input.value = checked.length > 0 ? JSON.stringify(checked) : '';
    }
}
