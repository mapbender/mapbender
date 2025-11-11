# Migration: jQuery UI Widget -> Native JavaScript Class

This document describes step by step how to convert an existing Mapbender jQuery UI widget (e.g. `mapbender.mbRuler`) into a native ES6 JavaScript class (e.g. `MbRuler`). The basis for this are the files

- before: `mapbender.element.ruler.js`
- after: `MbRuler.js`

The same principles apply to other elements.

## 1. Adjustments in the PHP Element-Class

Two adjustments are necessary in the PHP-Element-Class (e.g. `Mapbender\CoreBundle\Element\Ruler`):

1. `getWidgetName()` -> change return value from `mapbender.mbRuler` to `MbRuler`.
2. `getRequiredAssets()` -> Modify the path and refer to the new class script (new file name `MbRuler.js`). Delete the old path `.../mapbender.element.ruler.js`.

Example (simplified):

```php
public static function getWidgetName() : ?string
{
	return 'MbRuler';
}

public static function getRequiredAssets(Entity\Element $element) : array
{
	return [
		'js' => [
			'@MapbenderCoreBundle/Resources/public/elements/MbRuler.js',
		],
		// ... css / translation as before
	];
}
```

## 2. File / name conventions

| Old (widget)                    | New (native class) |
|---------------------------------|----------------------|
| `mapbender.element.ruler.js`    | `MbRuler.js`         |
| Widget-Namespace: `$.widget("mapbender.mbRuler", {...})` | ES6 class: `class MbRuler extends MapbenderElement {}` |
| Widget-Name (`getWidgetName`) = `mapbender.mbRuler` | class name = `MbRuler` |


## 3. Adjust wrapper & class definition

### Before (widget pattern)
```js
(function($) {
	$.widget("mapbender.mbRuler", {
		// methods + options
	});
})(jQuery);
```
### After (native class)
```js
(function() {
	class MbRuler extends MapbenderElement {
		// methods + constructor
	}

	window.Mapbender.Element = window.Mapbender.Element || {};
	window.Mapbender.Element.MbRuler = MbRuler;
})();
```
Important: The class must be registered globally under `window.Mapbender.Element.<Name>` so that the element loader can find it.

## 4. The **_create() -> constructor()**

The old jQuery-Widget used `_create()`. In the class, we replace this with an ES6 constructor:

### Before
```js
_create: function() {
	var self = this;
	if (this.options.type !== 'line' && ...) { throw ... }
	Mapbender.elementRegistry.waitReady('.mb-element-map').then(function(mbMap){
		self._setup(mbMap);
	});
}
```

### After
```js
constructor(configuration, $element) {
	super(configuration, $element);
	if (this.options.type !== 'line' && this.options.type !== 'area' && this.options.type !== 'both') {
		throw Mapbender.trans('mb.core.ruler.create_error');
	}
	Mapbender.elementRegistry.waitReady('.mb-element-map').then((mbMap) => {
		this._setup(mbMap);
	}, function() {
		Mapbender.checkTarget('mbRuler');
	});
}
```

The parameters `configuration` and `$element` are always passed. The parent constructor must first be called via `super`.

## 5. Change method syntax

All function literals in the object (`name: function (...) {}`) become class methods (`name(...) {}`).

### Example
Before:
```js
_setup: function(mbMap) {
	this.mapModel = mbMap.getModel();
}
```
After:
```js
_setup(mbMap) {
	this.mapModel = mbMap.getModel();
}
```

No more commas between methods. The `function` keyword is no longer required. 


## 6. Registration in the element registry

Instead of `this._trigger(‘ready’)` (jQuery UI), the following call is now used:

```js
Mapbender.elementRegistry.markReady(this);
```

This usually happens at the end of `_setup()`.


## 7. `this.element` -> `this.$element`

The widget received the root element in `this.element` from jQuery UI Core. In the new base class `MapbenderElement`, it is `this.$element` (jQuery object). Replace all occurrences:

| Old              | New              |
|------------------|------------------|
| `this.element`   | `this.$element`  |


## 8. Popup 

Previously, popups were controlled manually via `new Mapbender.Popup({...})`; activation was often via `defaultAction / activate / deactivate`. Now `MapbenderElement` provides a standard mechanism:

- Continue to use `activate()` / `deactivate()` for technical activation (layers, interactions, etc.).
- For button-based activation (toolbar), use `activateByButton(callback)` and `closeByButton()`
- Overwrite `getPopupOptions()` to customize titles, sizes, buttons, etc.

### Example from `MbRuler.js`

```js
getPopupOptions() {
	return {
		title: this.$element.attr('data-title'),
		modal: false,
		draggable: true,
		resizable: true,
		closeOnESC: true,
		destroyOnClose: true,
		content: this.$element,
		width: 300,
		height: 300,
		buttons: [{
			label: Mapbender.trans('mb.actions.close'),
			cssClass: 'btn btn-sm btn-light popupClose'
		}]
	};
}
```

## 9. Other adjustments / patterns

1. Method order can be freely adjusted; recommended: constructor -> private setup functions -> event handlers -> calculations -> formatting.
2. Use arrow functions for callbacks if `this` is required by the class context (e.g., `geometry.on(‘change’, () => { ... })`).
3. Where `var self = this;` was previously required, this is no longer necessary thanks to arrow functions or direct method binding (`.bind(this)`).

### Example
Before:
```js
_createControl: function() {
	const source = this.layer.getNativeLayer().getSource();
	const control = new ol.interaction.Draw({
		type: this.options.type === 'line' ? 'LineString' : 'Polygon',
		source: source,
		stopClick: true,
		style: this._getStyle.bind(this)
	});
	control.on('drawstart', function(event) { /* self */ });
}
```
After:
```js
_createControl() {
	const source = this.layer.getNativeLayer().getSource();
	const control = new ol.interaction.Draw({
		type: this.options.type === 'line' ? 'LineString' : 'Polygon',
		source: source,
		stopClick: true,
		style: this._getStyle.bind(this)
	});
	control.on('drawstart', (event) => { /* this */ });
}
```

## 10. Inheritance

1. Inheritance is achieved by referencing the complete path of the parent class namespace.
2. Parent functions can be called using `super.parentFunction()`.

Example:

```js
(function() {

    class MbPrint extends Mapbender.Element.MbImageExport {
        constructor(configuration, $element) {
            super(configuration, $element);
            // Your own code ...
        }

        _setup() {
            super._setup();
            // Your own code ...
        }

        activateByButton(callback) {
            super.activateByButton(callback);
            // Your own code ...
        }

        closeByButton() {
            super.closeByButton();
            // Your own code ...
        }
    }
    window.Mapbender.Element = window.Mapbender.Element || {};
    window.Mapbender.Element.MbPrint = MbPrint;
})();
```

## 11. Migration checklist

1. PHP: Adjust `getWidgetName()` -> class name.
2. PHP: `getRequiredAssets()` – remove old JS file name, add new one.
3. Rename JS file (`mapbender.element.<name>.js` -> `Mb<Name>.js`).
4. Remove widget wrapper; introduce ES6 IIFE + class.
5. Registration at the end: `window.Mapbender.Element.<class> = <class>`.
6. `_create` -> `constructor(configuration, $element)` + `super(...)`.
7. Convert method syntax (`name: function` -> `name()`).
8. `this._trigger(‘ready’)` -> `Mapbender.elementRegistry.markReady(this)`.
9. Replace `this.element` -> `this.$element`.
10. Change popup handling to `getPopupOptions`, `activateByButton`, `closeByButton`.
11. Use arrow functions, remove `var self=this;`.


## 12. Minimal framework of an MbElement class

```js
(function() {
	class MyNewElement extends MapbenderElement {
		constructor(configuration, $element) {
			super(configuration, $element);
			Mapbender.elementRegistry.waitReady('.mb-element-map').then((mbMap) => {
				this._setup(mbMap);
			});
		}
		_setup(mbMap) {
			this.mapModel = mbMap.getModel();
			// ... init code ...
			Mapbender.elementRegistry.markReady(this);
		}
		activate() { /* optional override */ }
		deactivate() { /* optional override */ }
		getPopupOptions() { 
            return { 
                title: this.$element.attr('data-title'), 
                content: this.$element 
            }; 
        }
	}
	window.Mapbender.Element = window.Mapbender.Element || {};
	window.Mapbender.Element.MyNewElement = MyNewElement;
})();
```
