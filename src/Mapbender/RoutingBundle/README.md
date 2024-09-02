# Mapbender Routing Module

This module contains routing element and the ability to communicate external API's. More over about in [features] section.

## Dokumentation

There is a short [Dokumenation of Routing](https://repo.wheregroup.com/frankfurt/mapbender-documentation-routing/blob/develop/de/functions/routing/routing.rst)

## Features 

* Simple routing between two point
* Complex routing between more as two points (intermodal routing)
* Managing multiply points on the map (intermodal routing)
* Flexible configurability of element:
  * [Customizable][FormGenerator] frontend view
  * Customizable Info-Text
    * Placeholder for `{start}|{destination}|{length}|{time}`
  * Customizable Time-Format `default=ms` only for PgRouting
  * Customizable layer style maps (feature styling) 
  * Customizable driving types
  * Search with SOLR-Driver
  * Reverse Geocoding with PostgreSQL 
* Interactive intermodal points
* Buffered zoom to route
* Input of coordinates
    * support View-Grid coordinates
    * support GPS-Coordinates of DG-scheme (decimal degree (DG): 41.40338, 2.17403)
* Integration as dialog types
    * element
    * dialog
* [Customizable][FormGenerator] segments view and settable container 
* Customizable external API requests 
* [GeoJSON] frontend-backend communication
* API Support:
  * [PostGIS]/[PgRouting]
  * [EKAP]/[Trias] 
  * [Grappher]
  * [OSRM]
* Based on [Data-Store][DataStore] data model

## Installation

* First you need installed mapbender3-starter https://github.com/mapbender/mapbender-starter#installation project
* Add required module to mapbender `composer.json`

### Example Default:
```yaml
{
    "require-dev": {
        "mapbender/routing": "dev-develop"
    },
    "require": {
        "mapbender/routing": "dev-master"
    }
    ...
    "repositories": [
        {
        "type": "git",
        "url": "https://repo.wheregroup.com/mapbender3/routing.git"
        }
    ]
}
```

### Example:

* add mapbender-routing-bundle "develop"
* add Routingbundle into Path "mapbender/src/Mapbender/"

```yaml
{
    "require": {
        "mapbender/routing": "dev-develop"
    }
    ...
    "extra": {
            ...
            "mapbender/src/Mapbender/RoutingBundle/": ["mapbender/routing"]
        }
    }
    ...
    "repositories": [
        {
        "type": "git",
        "url": "https://repo.wheregroup.com/mapbender3/routing.git"
        }
    ]
}
```

#### update config.yml

````yaml
[...]
# Doctrine Configuration
doctrine:
    dbal:
        default_connection: default    
        connections:
            # Default MB
            default:
                driver:   %database_driver%
                host:     %database_host%
                port:     %database_port%
                dbname:   %database_name%
                path:     %database_path%
                user:     %database_user%
                password: %database_password%
                persistent: true
                charset:  UTF8
                logging: %kernel.debug%
                profiling: %kernel.debug%

            # Configuration search_db
            search_db:
                driver:   %database2_driver%
                host:     %database2_host%
                port:     %database2_port%
                dbname:   %database2_name%
                path:     %database2_path%
                user:     %database2_user%
                password: %database2_password%
                charset:  UTF8
                logging: %kernel.debug%
                profiling: %kernel.debug%

            # Configuration routing_db
            routing_db:
                driver:   %database3_driver%
                host:     %database3_host%
                port:     %database3_port%
                dbname:   %database3_name%
                path:     %database3_path%
                user:     %database3_user%
                password: %database3_password%
                charset:  UTF8
                logging: %kernel.debug%
                profiling: %kernel.debug%

            # Configuration reverseGeocode_db
            reverseGeocode_db:
                driver:   %database4_driver%
                host:     %database4_host%
                port:     %database4_port%
                dbname:   %database4_name%
                path:     %database4_path%
                user:     %database4_user%
                password: %database4_password%
                charset:  UTF8
                logging: %kernel.debug%
                profiling: %kernel.debug%
    orm:
        auto_generate_proxy_classes: %kernel.debug%
        auto_mapping: true
[...]
````

#### update parameters.yml

````yml
parameters:
   # Configuration default with postgreSQL-DB

    #database_driver:   pdo_sqlite
    #database_host:     ~
    #database_port:     ~
    #database_name:     ~
    #database_path:     %kernel.root_dir%/db/demo.sqlite
    #database_user:     ~
    #database_password: ~

    # Configuration "mapbender_postgresql_db"
    database_driver:   pdo_pgsql
    database_host:     localhost
    database_port:     5432
    database_name:     mapbender
    database_path:     ~
    database_user:     app_mb3
    database_password: ~

    # Configuration "routing_search_db"
    database2_driver:   pdo_pgsql
    database2_host:     localhost
    database2_port:     5432
    database2_name:     test
    database2_path:     ~
    database2_user:     postgres
    database2_password: ~

    # Configuration "routing_postgres"
    database3_driver:   pdo_pgsql
    database3_host:     localhost
    database3_port:     5432
    database3_name:     test
    database3_path:     ~
    database3_user:     postgres
    database3_password: ~

    # Configuration "routing_reverseGeocode_db"
    database4_driver:   pdo_pgsql
    database4_host:     localhost
    database4_port:     5432
    database4_name:     test
    database4_path:     ~
    database4_user:     postgres
    database4_password: ~
````


#### update composer

```bash
$ cd application
$ ../composer.phar update -o mapbender/routing
```

#### update doctrine

```bash
# Check Update
app/console doctrine:schema:update

# run doctrine update
app/console doctrine:schema:update --force
```


#### customer for Developer:

    * disable remote push from mapbender-starter

    ```bash
    $ cd ~/mapbender-starter/
   
    $ git remote set-url --push origin DISABLE
    ```
    
    * add git-version to PHP-Storm Subdirectory
    
    ```txt
    File -> Settings-> Version Controll -> add neu Directory -> ok
    ```
### Graphhopper

An open source route planning library and server using OpenStreetMap. Written to JAVA.

- Links
    - [Graphhopper-Quickstart](https://github.com/graphhopper/graphhopper/blob/master/docs/web/quickstart.md)
    - [Graphhopper-API](https://github.com/graphhopper/graphhopper/blob/master/docs/web/api-doc.md)

- must have
    - Docker
    - or JAVA JRE for Quickstart Web
    
#### install Graphhopper Backend

```bash
 cd  Projekte/graphhopper/
 
 git clone https://github.com/graphhopper/graphhopper.git
```

### OSRM

An open source route planning library and server using OpenStreetMap. Written to C++.

- Links
    - [OSRM-Backend-Server-API](https://github.com/Project-OSRM/osrm-backend/blob/master/docs/http.md)
    - [OSRM API Documentation-v5.10.0](http://project-osrm.org/docs/v5.10.0/api/#general-options)

- must have
    - Docker 

#### install OSRM-backend

```bash
 cd  Projekte/osrm/
 
 git clone https://github.com/Project-OSRM/osrm-backend.git
```
#### Quickstart Web

- [Quickstart-Install-Link](https://github.com/graphhopper/graphhopper/blob/master/docs/web/quickstart.md)

 1. Install the latest JRE and get the GraphHopper Web Service as jar file
 2. Copy an OSM file to the same directory. For example berlin-latest.osm.pbf
 3. Start GraphHopper Maps via: 
    ```bash
    java -Dgraphhopper.datareader.file=berlin-latest.osm.pbf -jar *.jar server config-example.yml
    ```   
 4. After you see `Started server at HTTP 8989` go to http://localhost:8989/ and you should see a map of Berlin. You should be able to click on the map and a route appears.

#### Start build GraphHopper from source with JRE for Developer

```bash
$ git clone git://github.com/graphhopper/graphhopper.git
$ cd graphhopper; git checkout master
$ ./graphhopper.sh -a web -i europe_germany_berlin.pbf
now go to http://localhost:8989/
```

#### run Docker

- Start simply Dockerfile

```bash
cd core/files/

docker-compose up -d

```

### Running OSRM

- https://github.com/Project-OSRM/osrm-backend/wiki/Running-OSRM


- Contraction Hierarchies (CH) which best fits use-cases where query performance is key, especially for large distance matrices

- Multi-Level Dijkstra (MLD) which best fits use-cases where query performance still needs to be very good; and live-updates to the data need to be made e.g. for regular Traffic updates

- `osrm-extract` = create Graph of OSM-Data
- `osrm-partition` = this graph recursively into cells
- `osrm-customize` = customize the cells by calculating routing weights for all cells
- `osrm-routed` = pawning up the development HTTP server 


#### Quickstart Hessen:

```bash
cd Projekte/osrm/osrm-backend/

## Download OSM-Data
wget https://download.geofabrik.de/europe/germany/hessen-latest.osm.pbf
```
- MLD-Graph

```bash
docker run -t -v $(pwd):/data osrm/osrm-backend osrm-extract  /data/hessen-latest.osm.pbf

docker run -t -v $(pwd):/data osrm/osrm-backend osrm-partition  /data/hessen-latest.osm.pbf

docker run -t -v $(pwd):/data osrm/osrm-backend osrm-customize  /data/hessen-latest.osm.pbf

# Docker run
docker run -t -i -p 5000:5000 -v $(pwd):/data osrm/osrm-backend osrm-routed --algorithm mld /data/hessen-latest.osrm
```

- CH-Graph

```bash
## extract Graph of Profile Name
# car
docker run -t -v $(pwd):/data osrm/osrm-backend osrm-extract -p /data/profiles/car.lua  /data/hessen-latest.osm.pbf
# foot
docker run -t -v $(pwd):/data osrm/osrm-backend osrm-extract -p /data/profiles/foot.lua  /data/hessen-latest.osm.pbf
# bike
docker run -t -v $(pwd):/data osrm/osrm-backend osrm-extract -p /data/profiles/bicycle.lua  /data/hessen-latest.osm.pbf

# Create CH
docker run -t -v $(pwd):/data osrm/osrm-backend osrm-contract /data/hessen-latest.osrm

# Docker run
docker run -t -i -p 5000:5000 -v $(pwd):/data osrm/osrm-backend osrm-routed /data/hessen-latest.osrm
``` 

### Suche


### Reverse Geocoding

    
## Configuration 

### Configuration of dialog or element

You can not configure the routing element directly into the toolbar.The routing element has a Type dialog selection. This is required. 

##### element

+ add routing element to Sidepane

##### dialog

+  add routing element to Content

+ choose the dialog type from Admin type to Dialog in the form
    - element
    - dialog

+ add button to toolbar
    +  `Target: Routing`
    +  `Action: open`
    +  `Deactivate: close`


### Map element ID
```yaml
target: map
```

### Displaying start time form

Erlaubt dem Benutzer die Abfahrtzeit zu wählen mit Hilfe eines  Date/Time-Pickers

```yaml
allowSelectStartTime: false
```


### Driving type selection

Unterdrückt den Wert der formularelemente.
Globaler Wert, ob man man den Fahrzeugtyp wechseln kann.

```yaml
allowSwitchVehicleType: true
```

### Intermodal routing

Erlaubt dem Benutzer Zwischenpunkte zu setzen.
Unterdrückt den Wert der Formularelemente.

```yaml
allowIntermediateNodes: true
```

Erlaubt dem Benutzer den Startpunkt mit einem Kontextmenü der Karte zu setzen

```yaml
allowSetStartPointwithMapClick: true
```

Erlaubt dem Benutzer den Zielpunkt mit einem Kontextmenü der Karte zu setzen
```yaml
allowSetDestinationPointwithMapClick: true
```

Erlaubt dem Benutzer einen neuen Zwischenpunkt mit einem Kontextmenü der Karte zu setzen
Durch einschalte der Option wird auch das Löschen von zwischen Punkten ermöglicht
Wenn allowIntermediateNodes = true
```yaml
allowAddPointwithMapClick: true
```

### Automation

Startet die Suche wenn alle Suchinputs gefüllt sind ohne einen Button zu  drücken

```yaml
routeDirectly: true
```

### Segments

Erlaubt die Darstellung der Fahrtanweisungen ein/auszuschalten
```yaml
showDrivingInstructions: false
```

jQuery-Selector Container ID für Darstellung der Fahrtanweisungen | Option "popup"
```yaml
drivingInstructionContainer: "#driving-instruction"
```

### Map view
Zoom zum Extent der Route, nachdem die Route auf der Karte angezeigt wird
```yaml
zoomToExtentAfterRouting: true
```

Zoomstufe wenn man auf ein Segment der Fahrtanweisung springt
```yaml
segmentZoomLevel: 500
```


### Shemas

Schemas Abschnitt definiert unterschiedliche Fahrtypen
Mindestens ein Typ soll bestimmt werden, das die Verbindung und das aussehen der Route auf der Karte in form von Styles beschreibt
```yaml
schemas: []
```


Die ID der default Schnitstelle
```yaml
defaultSchema: ~
```

#### Schema

The definition of how to communicate external API and to be viewed.
At least one schema should be defined this.


##### Title

Schema Beschriftuing erscheint beim HTML Select Auswahliste

```yaml
title: ÖVPN
```

##### Data source

Feature type definition is mit Digitizer gleich

```yaml
featureType: routing_featureType
```


Limitiere maximal gültigen Bereich, indem geroutet werden darf.
Wenn Punkte außerhalb dieses Bereiches liegen,
wird eine Fehelermeldung ausgegeben und es wird keine Anfrage an die externe Schnittstelle weitergereicht
```yaml
limitExtent: [-500000,4350000,1600000,6850000]
```

##### Language

Die Sprache für das Resultat und Segmentebeschreibung
```yaml
locale: de
```

##### Custom API parameters

Schnitstelle/Treiber spezifische paramtern die in Treiber Konfiguration Entity beschriben sind
Diese sind beim generieren von API-Dokumentation verfügbar.
```yaml
parameters:
   optimize: true
   instructions: true
   elevation: false
   averageSpeedInKm: 0.2
```


##### Styling

Optinal kann StyleMap pro Schema bestimmt werden.


```yaml
styleMap:
  default:
    border:
    ## @var Boolean  Set to false if no fill is desired.
    fill: false

    # @var String  Hex fill color.  Default is “#ee9900”.
    fillColor: “#ee9900”

    # @var Number  Fill opacity (0-1).  Default is 0.4
    fillOpacity: 1

    # @var Boolean  Set to false if no stroke is desired.
    stroke: true

    # @var String  Hex stroke color.  Default is “#ee9900”.
    strokeColor: “#ee9900”

    # @var Number  Stroke opacity (0-1).  Default is 1.
    strokeOpacity: ~

    # @var Number  Pixel stroke width.  Default is 1.
    strokeWidth: 1

    # @var String  Stroke cap type.  Default is “round”.  [butt | round | square]
    strokeLinecap: ~

    # @var String  Stroke dash style.  Default is “solid”.  [dot | dash | dashdot | longdash | longdashdot | solid]
    strokeDashstyle: ~

    # @var Boolean  Set to false if no graphic is desired.
    graphic: ~

    # @var Number  Pixel point radius.  Default is 6.
    pointRadius: ~

    # @var String  Default is “visiblePainted”.
    pointerEvents: ~

    # @var String  Default is “”.
    cursor: ~

    # @var String  Url to an external graphic that will be used for rendering points.
    externalGraphic: ~

    # @var Number  Pixel width for sizing an external graphic.
    graphicWidth: ~

    # @var Number  Pixel height for sizing an external graphic.
    graphicHeight: ~

    # @var Number  Opacity (0-1) for an external graphic.
    graphicOpacity: ~

    # @var Number  Pixel offset along the positive x axis for displacing an external graphic.
    graphicXOffset: ~

    # @var Number  Pixel offset along the positive y axis for displacing an external graphic.
    graphicYOffset: ~

    # @var Number  For point symbolizers, this is the rotation of a graphic in the clockwise direction about its center point (or any point off center as specified by graphicXOffset and graphicYOffset).
    rotation: ~

    # @var Number  The integer z-index value to use in rendering.
    graphicZIndex: ~

    # @var String  Named graphic to use when rendering points.  Supported values include “circle” (default), “square”, “star”, “x”, “cross”, “triangle”.
    graphicName: ~

    # @var String  Tooltip when hovering over a feature.  deprecated, use title instead
    graphicTitle: ~

    # @var String  Tooltip when hovering over a feature.  Not supported by the canvas renderer.
    title: ~

    # @var String  Url to a graphic to be used as the background under an externalGraphic.
    backgroundGraphic: ~

    # @var Number  The integer z-index value to use in rendering the background graphic.
    backgroundGraphicZIndex: ~

    # @var Number  The x offset (in pixels) for the background graphic.
    backgroundXOffset: ~

    # @var Number  The y offset (in pixels) for the background graphic.
    backgroundYOffset: ~

    # @var Number  The height of the background graphic.  If not provided, the graphicHeight will be used.
    backgroundHeight: ~

    # @var Number  The width of the background width.  If not provided, the graphicWidth will be used.
    backgroundWidth: ~

    # @var String  The text for an optional label.  For browsers that use the canvas renderer, this requires either fillText or mozDrawText to be available.
    label: ~

    # @var String  Label alignment.  This specifies the insertion point relative to the text.  It is a string composed of two characters.  The first character is for the horizontal alignment, the second for the vertical alignment.  Valid values for horizontal alignment: “l”=left, “c”=center, “r”=right.  Valid values for vertical alignment: “t”=top, “m”=middle, “b”=bottom.  Example values: “lt”, “cm”, “rb”.  Default is “cm”.
    labelAlign: ~

    # @var Number  Pixel offset along the positive x axis for displacing the label.  Not supported by the canvas renderer.
    labelXOffset: ~

    # @var Number  Pixel offset along the positive y axis for displacing the label.  Not supported by the canvas renderer.
    labelYOffset: ~

    # @var Boolean  If set to true, labels will be selectable using SelectFeature or similar controls.  Default is false.
    labelSelect: ~

    # @var String  The color of the label outline.  Default is ‘white’.  Only supported by the canvas & SVG renderers.
    labelOutlineColor: ~

    # @var Number  The width of the label outline.  Default is 3, set to 0 or null to disable.  Only supported by the SVG renderers.
    labelOutlineWidth: ~

    # @var Number  The opacity (0-1) of the label outline.  Default is fontOpacity.  Only supported by the canvas & SVG renderers.
    labelOutlineOpacity: ~

    # @var String  The font color for the label, to be provided like CSS.
    fontColor: ~

    # @var Number  Opacity (0-1) for the label
    fontOpacity: ~

    # @var String  The font family for the label, to be provided like in CSS.
    fontFamily: ~

    # @var String  The font size for the label, to be provided like in CSS.
    fontSize: ~

    # @var String  The font style for the label, to be provided like in CSS.
    fontStyle: ~

    # @var String  The font weight for the label, to be provided like in CSS.
    fontWeight: ~

    # @var String  Symbolizers will have no effect if display is set to “none”.  All other values have no effect.
    display: ~

    # @var int User ID
    protected $userId: ~
    
  selected: ~
  hover: ~
```

## Class Diagramm

![screenshot](doc/images/ClassDiagrammElement.png)

Die Interaktion zwischen dem RoutingElement, dem RoutingElementRequest und dem GraphhopperDriver.

## Links

### Stadt Frankfurt 

* [Anforderungen Dokument](smb://center/wheregroup/Projects/Frankfurt_Stadt/5_Projektdoku/Routing/Stadt_Frankfurt_Anforderungen_Mapbender3_Routing_02_2017_WhereGroup.odt)
* [PostGIS DB](/uploads/38f1d0de7c1b5bea038fe311473c16f6/frankfurt_routing.backup)
* [Chilly-Ticket](https://trac.wheregroup.com/cp/issues/7787)

### LBM 

* [Anforderungen/Disskusion](
https://repo.wheregroup.com/rlp/radwanderland/issues/10
)


#### TRIAS 

* [TRIAS Homepage](https://www.vdv.de/ip-kom-oev.aspx) 
* [API Dokument](https://www.vdv.de/431-2sds-v1.1.pdfx?forced=true)

### Bahn

* [Mockup Dokument](/uploads/446032cb2f8b93730499c1176d1fa2ab/Mockup_Routenerstellung__1_.odg)
* [Anforderungen Dokument](/uploads/e00b6587dffa0ff0fea2735d441f01b0/Anforderungen_Prosa__1_.odt)


### Protocol
* [MediaWiki](https://troubadix.wheregroup.com/wiki/index.php/MB3-Routing)


[features]: #Features
[Grappher]: https://graphhopper.com/api/1/docs/routing/
[OSRM]: http://project-osrm.org/docs/v5.15.2/api/#general-options
[Trias]: https://www.vdv.de/ip-kom-oev.aspx
[EKAP]: https://www.dresden.de/media/pdf/wirtschaft/VVO_Beschreibung_der_Schnittstelle_API_fuer_die_Verbindungsauskunft.pdf
[postgis]: http://postgis.net/
[pgrouting]: http://pgrouting.org/
[OWS Proxy]: https://github.com/mapbender/owsproxy3  "OWS proxy submodule"
[DataStore]: https://github.com/mapbender/data-source "Mapbender data source"
[GeoJSON]: https://tools.ietf.org/html/rfc7946
[FormGenerator]: https://github.com/eSlider/vis-ui.js#elements

