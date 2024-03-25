# Frequently Asked Questions

## Systemanforderungen

### Funktioniert Mapbender mit PHP 7?

Ja! Ein frisch installierter Mapbender 3.0.8.5 funktioniert "out of the box" mit PHP ab (einschließlich) 5.4 bis (einschließlich) 7.2.

### 7.2 ... funktioniert Mapbender mit >= PHP7.3?

Ein vollständiger Test ist noch nicht durchlaufen, aber grundlegend ja. Dazu sind allerdings noch manuelle Eingriffe notwendig.

#### Update auf Symfony 3.4

Die platform in der *composer.json* des Starters muss auf z.B. 7.1.9 gesetzt, oder gleich ganz entfernt werden. Danach einmal `bin/composer update doctrine/common doctrine/dbal doctrine/orm`

## Installation

### Wie installiere ich mir einen Mapbender auf meinem Arbeitsplatzrechner?

* [Systemvoraussetzungen beachten](https://github.com/mapbender/mapbender-starter#requirements)

* [Klonen und bootstrappen](https://github.com/mapbender/mapbender-starter#getting-the-code)

### Wie löse ich Dateisystemzugriffsfehler in Apache und/oder auf der Konsole?

Diese Fehler treten auf, weil Dateien sowohl von Apache als auch von Kommandozeilenprozessen erzeugt und gelesen werden, und diese standardmäßig inkompatiblen Besitzern und Rechten zugeschrieben werden.

Dazu gilt es, Apache als "Verfahrensuser" zu konfigurieren. Der Server heißt und verhält sich dabei wie der Nutzer, der die **Konsolen-Prozesse** anstößt. Lokal ist das euer SSH-Username, bei einem Kunden der SSH-User, über den ihr euch anmeldet.

Erreicht wird das über Setzen von `APACHE_RUN_USER` und `APACHE_RUN_GROUP`, gefolgt von einem `sudo service apache restart`.

Die abgeschwächte Alternative lautet, dass ihr euch Apache annähert, indem ihr in die Gruppe `www-data` eintretet.

> [!IMPORTANT]
> Das ist distributions- und versionsspezifisch unterschiedlich.

```console
sudo usermod -aG <euer-account-name> www-data
```

### Wie bekomme ich meine Mapbender-Entwicklung auf einen Server?

Im Idealfall direkt aus dem Gitlab über `git clone` oder `git pull` gefolgt von `composer install`.

Sollte vom Zielsystem aus lesender Zugriff auf Gitlab und/oder packagist nicht erlaubt sein, müssen zwei Dinge getan werden:

1. Der zuständige PM muss informiert werden, dass der eingeschränkte Serverzugriff ein Impediment ist, das dem Kunden als Mehraufwand und Risiko zu erläutern ist.
2. Das Gesamtpaket wird per `bin/composer run build` in ein Archiv geschnürt, auf den Server übertragen und dort wieder ausgepackt.

Besondere Vorsicht muss walten, wenn auf dem Server eine sqlite-Datenbank genutzt wird. Überschreiben der Sqlite-Datei aus dem Archiv ins Zielsystem ist nur bei Erstinstallation wünschenswert.

### Wie binde ich meinen featurespezifischen Branch von Paket X/Y ein?

Aktuelle Version von *spezifischerbranchname*:

```console
bin/composer require 'mapbender/digitizer:dev-spezifischerbranchname@dev'
```

Exakt auf einen bestimmten Commit festgenagelte Version von *spezifischerbranchname*:

```console
bin/composer require 'mapbender/digitizer:dev-spezifischerbranchname#5a5a2f497c9244d186624c925946e886fab25b39@dev'
```

### Was mache ich, wenn die Installation dabei mit Versionskonflikten abbricht?

> [!CAUTION]
>Versionskonflikte werden modelliert, weil Paket X in/ab/vor bestimmter Version mit Paket Y in/ab/vor bestimmter Version **wirklich nicht** funktioniert. Wenn ihr die Gründe für den konkreten Konflikt kennt, und sicher seid, dass eure Branch-Version keine Probleme machen wird, definiert eine Pseudo-Versionsnummer die nicht mehr als Konflikt erkannt wird:

```console
`bin/composer require 'mapbender/digitizer:dev-spezifischerbranchname as 1.1.99@dev'
```

Alles gleichzeitig (volle Syntax):

```console
bin/composer require 'mapbender/digitizer:dev-spezifischerbranchname#5a5a2f497c9244d186624c925946e886fab25b39 as 1.1.99@dev'
```

## Laufzeitumgebung (JavaScript)

### Wie komme ich von meinem Element aus an die Hauptkarte?

Klassisch über ein konfiguriertes *Target*: In diesem steht die *DOM-ID* des Kartenelements. Zur Laufzeit kann die Karte dann über die *DOM-ID* gefunden werden, oder (besser) über die *elementRegistry*.

Vorteil von *elementRegistry*:

1) man erhält die Widget-Instanz als echtes JavaScript-Objekt;
2) man bekommt eine Promise auf die man einzeln oder sogar `via $.when` in Kombination mit weiteren Promises warten kann;
3) man bekommt (wiederum durch die Promise) einen separaten Codepfad fürs Fehlerhandling.

Der Zugriff von der Widget-Instanz aus auf den DOM-Knoten ist einfacher als umgekehrt: `widget.element` für JQuery-Collection, bzw. `widget.element.get(0)` für das nackte DOM-Element.

#### Beispiel siehe "Ruler": Formulartyp und Frontend-Script

Der Formulartyp für Karten initialisiert sich automatisch beim Aufruf des Formulars.
Für Yaml-definierte Applikationen muss die ID der Karte eingetragen werden.

### Kann es mehrere Kartenelemente geben?

Eine Mapbender-Applikation soll und muss immer exakt eine Karte beinhalten.
Beispiel für ein scriptseitigen Lookup in der Registry anhand des CSS-Klassenselektors für `.mb-element-map`:

```javascript
Mapbender.elementRegistry.waitReady('.mb-element-map').then(function(mbMap) { console.log(mbMap); })
  t.<computed>.<computed> {element: n.fn.init(1), uuid: 11, eventNamespace: ".mbMap11", bindings: n.fn.init(1), hoverable: n.fn.init, …}
    element: n.fn.init [div#52.mb-element.mb-element-map.olMap, context: div#52.mb-element.mb-element-map.olMap]
    uuid: 11
    eventNamespace: ".mbMap11"
    bindings: n.fn.init [div#52.mb-element.mb-element-map.olMap, prevObject: n.fn.init, context: undefined]
    hoverable: n.fn.init {}
    focusable: n.fn.init {}
    classesElementLookup: {}
    document: n.fn.init [document, context: document]
    window: n.fn.init [Window]
    options: {classes: {…}, disabled: false, create: null, poiIcon: {…}, layersets: Array(2), …}
    _super: undefined
    _superApply: undefined
    elementUrl: "/app_dev.php/application/aliks-bb-test/element/52/"
    model: {mbMap: t.<…>.<computed>, map: NotMapQueryMap, sourceTree: Array(16), srsDefs: Array(7), mapMaxExtent: {…}, …}
    map: NotMapQueryMap {idCounter: 16, layersList: {…}, element: n.fn.init(1), olMap: initialize}
<...>
```

Unterstützung für Auffinden der Karte via *Target* wird bestehen bleiben.

### Wie komme ich von meinem Element aus an ein beliebiges anderes Element?

Weiterhin über ein konfiguriertes *Target*. Mehrfachzuweisungen von verschiedenen Targets (inklusive klassisch Map) sind durchaus möglich. Siehe z.B. `POIAdminType`. Die Werte (Element-IDs) landen im Element unter dem gewählten Attribut in den options.

Zugriff auf das Element-Widget ebenfalls am besten über die Registry.

Das Element sollte statt einer einfachen DOM-Selektion ebenfalls über die Element-Registry abgeholt werden:

```php
_create: function() {
   var self = this;
   Mapbender.elementRegistry.waitReady(this.options.irgendein_target).then(function(fremdesWidget) {
      self.fremdesWidget = fremdesWidget;
   });
},
_irgendeineMethode: function() {
    // Zugriff auf Widget-Instanz
    this.fremdesWidget.deactivate();
    // Zugriff auf Markup via jQuery
    this.fremdesWidget.element.addClass('test').show();
    // Zugriff auf nackten DOM-Knoten
    alert("Das Ding ist ein " + this.fremdesWidget.element.get(0).nodeName);
}
```
