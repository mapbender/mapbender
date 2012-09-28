Git-basierte Installation
######################


Wenn Sie sich an der Mapbender3-Entwicklung beteiligen möchten oder aus anderen Gründen die Git Repositories für Mapbender3 verwenden, folgen Sie dieser Anleitung statt des normalen Downloads. Diese Anleitung basiert auf Ubuntu 12.04.  Für andere Distributionen benötigen Sie vielleicht spezielle Pakete wie z.B. sphinx-common.


Klonen des Repositories
**********************

Klonen ist einfach, geben Sie das folgende Kommando auf Ihrer Shell ein:

    :command:`git clone -b 3.0 git://github.com/mapbender/mapbender-starter`


Entwickler, die Zugriff auf den Code haben möchten,  müssen die SSH-URL verwenden: git@github.com:mapbender/mapbender-starter



Submodule abrufen
*****************

Die Starter-Applikation enthält nicht die Mapbender3 bundles, diese sind in einem eigenen Repository gespeichert und werden als Submodule in das Starter-Repository eingefügt. Rufen Sie das folgende Kommando im root-Verzeichnis ihres geklonten Repositories auf.

    :command:`git submodule update --init --recursive`



Build-Management mit Phing
****************************


Das Build-Management wird mit Phing vorgenommen, welches die Pear-Bibliothek benötigt. Zunächst muss Pear installiert werden.  Hier wird ein Debian-basiertes System verwendet:

    :command:`sudo apt-get install php-pear`


Dann muss Pear gezeigt werden, wie ein Autodiscover seiner Repositories erzeugt wird.  Vorsichtshalber wird ein Update von Pear gemacht.

    :command:`sudo pear upgrade-all`


Dann wird Phing installiert:


    :command:`sudo pear install phing/phing`


Die Build-Skripte  benötigen weitere Abhängigkeiten, um Unit-Tests durchzuführen, die Dokumentation zu generieren und die Installationspakete zu erstellen.

Wenn Sie die Abhängigkeiten installiert haben, erhalten Sie einen Überblick der verfügbaren build-Tasks über:

    :command:`phing -l`


Der ersten Task, den Sie benötigen, ist der debs task. Dieser benötigt `Composer http://getcomposer.org`, um die Laufzeit-Abhängigkeiten wie Symfony und Doctrine zu installieren.
    :command:`phing deps`



cURL
====

Das build-System benutzt cURL, um einige Remote-Komponenten abzurufen. Dazu müssen Sie das cURL-Kommandozeilen-Werkzeug installieren:

    :command:`sudo apt-get install curl` 



Package Build Tools
===================

Dokumentation im Aufbau


PHPUnit
=======

Symfony2 benötigt ein neueres PHPUnit als z.B. Ubuntu 12.04 enthält. Pear wird verwendet, um  PHPUnit zu installieren:

    :command:`sudo pear install phpunit/PHPUnit`#



Sphinx
======

Sphinx wird für die Dokumentation benötigt, die Sie gerade lesen. In Debian-basierten Systemen wird Sphinx folgendermaßen installiert.

    :command:`sudo apt-get install sphinx-common`


ApiGen
======

`ApiGen <http://apigen.org>` - ist der API-Dokumentations-Generator erster Wahl. Es wird auch mit Pear installiert: 

    :command:`sudo pear install pear.apigen.org/apigen`#



Troubleshooting
***************

Die ApiGen-Bestandteile laufen nur in der neusten Version von Phing. 2.4.12  ist ausreichend,  2.4.9 reicht nicht aus! Testen Sie mit: :command:`phing -v`. Mit dem folgenden Befehl können Sie ein Update all Ihrer Pear-Pakete vornehmen: 

    :command:`sudo pear upgrade-all`


