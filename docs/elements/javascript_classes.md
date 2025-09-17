# Migration: jQuery UI Widget -> Native JavaScript Klasse

Dieses Dokument beschreibt Schritt für Schritt, wie ein bestehendes Mapbender jQuery-UI-Widget (z. B. `mapbender.mbRuler`) in eine native ES6-JavaScript-Klasse (z. B. `MbRuler`) überführt wird. Grundlage sind die Dateien

- Vorher: `mapbender.element.ruler.js`
- Nachher: `MbRuler.js`

Die gleichen Prinzipien gelten für andere Elemente.

---

## 1. Änderungen in der PHP Element-Klasse

In der PHP-Elementklasse (z. B. `Mapbender\CoreBundle\Element\Ruler`) sind zwei Anpassungen nötig:

1. `getWidgetName()` -> Rückgabewert von `mapbender.mbRuler` auf `MbRuler` ändern.
2. `getRequiredAssets()` -> Den Pfad zum neuen Klassenskript anpassen (Dateiname jetzt `MbRuler.js`). Entferne den alten Pfad `.../mapbender.element.ruler.js`.

Beispiel (vereinfacht):

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
		// ... css / translation wie zuvor
	];
}
```

---

## 2. Datei- / Namens-Konventionen

| Alt (Widget)                    | Neu (Native Klasse) |
|---------------------------------|----------------------|
| `mapbender.element.ruler.js`    | `MbRuler.js`         |
| Widget-Namespace: `$.widget("mapbender.mbRuler", {...})` | ES6 Klasse: `class MbRuler extends MapbenderElement {}` |
| Widget-Name (`getWidgetName`) = `mapbender.mbRuler` | Klassenname = `MbRuler` |

---

## 3. Wrapper & Klassendefinition anpassen

### Vorher (Widget-Pattern)
```js
(function($) {
	$.widget("mapbender.mbRuler", {
		// methods + options
	});
})(jQuery);
```

### Nachher (Native Klasse)
```js
(function() {
	class MbRuler extends MapbenderElement {
		// methods + constructor
	}

	window.Mapbender.Element = window.Mapbender.Element || {};
	window.Mapbender.Element.MbRuler = MbRuler;
})();
```

Wichtig: Die Klasse muss global unter `window.Mapbender.Element.<Name>` registriert werden, damit der Element-Loader sie findet.

---

## 4. _create() -> constructor()

Das jQuery-Widget nutzte `_create()`. In der Klasse ersetzen wir das durch einen ES6-Konstruktor:

### Vorher
```js
_create: function() {
	var self = this;
	if (this.options.type !== 'line' && ...) { throw ... }
	Mapbender.elementRegistry.waitReady('.mb-element-map').then(function(mbMap){
		self._setup(mbMap);
	});
}
```

### Nachher
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

Es werden immer die Parameter `configuration` und `$element` übergeben. Der Eltern-Konstruktor muss zuerst via `super` aufgerufen werden.

---

## 5. Methoden-Syntax umstellen

Alle Funktionsliterale im Objekt (`name: function (...) {}`) werden zu Klassenmethoden (`name(...) {}`).

### Beispiel
Vorher:
```js
_setup: function(mbMap) {
	this.mapModel = mbMap.getModel();
}
```
Nachher:
```js
_setup(mbMap) {
	this.mapModel = mbMap.getModel();
}
```

Keine Kommata zwischen den Methoden mehr. `function`-Keyword entfällt. 

---

## 6. Registrierung in der Element-Registry

Statt `this._trigger('ready')` (jQuery UI) wird jetzt folgender Aufruf verwendet:

```js
Mapbender.elementRegistry.markReady(this);
```

Dies geschieht normalerweise am Ende von `_setup()`.

---

## 7. `this.element` -> `this.$element`

Das Widget erhielt vom jQuery UI Core das Root-Element in `this.element`. In der neuen Basisklasse `MapbenderElement` ist es `this.$element` (jQuery-Objekt). Alle Vorkommen ersetzen:

| Alt              | Neu              |
|------------------|------------------|
| `this.element`   | `this.$element`  |



---

## 8. Popup 

Vorher wurden Popups manuell über `new Mapbender.Popup({...})` gesteuert; Aktivierung oft via `defaultAction / activate / deactivate`. Jetzt stellt `MapbenderElement` eine Standard-Mechanik bereit:

- Verwende `activate()` / `deactivate()` weiterhin für technische Aktivierung (Layer, Interactions etc.).
- Für Button-gestützte Aktivierung (Toolbar) gibt es `activateByButton(callback)` und `closeByButton()`
- Überschreibe `getPopupOptions()` um Titel, Größe, Buttons etc. anzupassen.

### Beispiel aus `MbRuler.js`
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

---

## 9. Sonstige Anpassungen / Muster

1. Methodenreihenfolge kann frei angepasst werden; empfehlenswert: constructor -> private Setup-Funktionen -> Event Handler -> Berechnungen -> Formatierung.
2. Arrow Functions für Callbacks nutzen, wenn `this` vom Klassenkontext benötigt wird (z. B. `geometry.on('change', () => { ... })`).
3. Wo früher `var self = this;` nötig war, entfällt dies durch Arrow Functions oder direkte Methodenbindung (`.bind(this)`).

### Beispiel
Vorher:
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
Nachher:
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

---

## 10. Vererbung

1. Vererbung gelingt indem der vollständige Pfad des Namespace der Elternklasse referenziert wird
2. Eltern-Funktionen können mit `super.parentFunction()` aufgerufen werden

Beispiel:


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

---

## 11. Checkliste Migration

1. PHP: `getWidgetName()` anpassen -> Klassenname.
2. PHP: `getRequiredAssets()` – alten JS-Dateinamen entfernen, neuen hinzufügen.
3. JS-Datei umbenennen (`mapbender.element.<name>.js` -> `Mb<Name>.js`).
4. Widget-Wrapper entfernen; ES6 IIFE + Klasse einführen.
5. Registrierung am Ende: `window.Mapbender.Element.<Klasse> = <Klasse>`.
6. `_create` -> `constructor(configuration, $element)` + `super(...)`.
7. Methoden-Syntax konvertieren (`name: function` -> `name()`).
8. `this._trigger('ready')` -> `Mapbender.elementRegistry.markReady(this)`.
9. `this.element` -> `this.$element` ersetzen.
10. Popup-Handling auf `getPopupOptions`, `activateByButton`, `closeByButton` umstellen.
11. Arrow Functions nutzen, `var self=this;` entfernen.

---

## 12. Minimales Gerüst einer MbElement-Klasse

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
