Installation 
############ 

Dieses Dokument beschreibt die wichtigsten Schritte, um Mapbender3 zu installieren. 


Voraussetzungen
***************

Mapbender3 benötigt die folgenden Komponenten:

* >= PHP 5.3 (php5) 
* PHP CLI Interpreter (php5-cli) 
* PHP SQLite Erweiterung (php5-sqlite) 
* PHP cURL Erweiterung (php5-curl) 

Um optional eine andere Datenbank als die vorkonfigurierte SQLite zu verwenden, wird eine PHP-Erweiterung benötigt, die von Doctrine unterstützt wird:
`Doctrine http://www.doctrine-project.org/projects/dbal.html`. 

Beachten Sie, dass die SQLite Erweiterung auf jeden Fall benötigt wird. Sie benötigen diese, um im Entwicklermodus zu arbeiten, um den Web Installer zu verwenden oder um Profiler-Daten zu erzeugen sowie um Fehler zu analysieren.


Download 
********** 

Installationspakete werden als komprimierte Pakete ausgegeben und sind auf der Download-Seite verfügbar unter  http://mapbender3.org/download.

Nach dem Herunterladen extrahieren Sie die komprimierten Pakete in ein Verzeichnis Ihrer Wahl. Stellen Sie sicher, dass der Webserver auf das gerade dekomprimierte Webverzeichnis in dem Mapbender Verzeichnis zeigt. Sorgen Sie dafür, dass *app.php* als Verzeichnis-Index eingestellt ist.


Eine :doc:`Git-basierte <installation_git>`-Installation - vorwiegend für Entwickler - ist ebenso möglich.


Konfiguration
============= 



Verwendung des Web-Installer
---------------------------------------

Die Konfiguration direkt über den Browser ist bisher nicht verfügbar. Bitte benutzen Sie derzeit die kommandozeilenbasierte Methode.



Verwendung der  Kommandozeile
----------------------------------------

Um die Mapbender3-Installation zu konfigurieren, sind die folgenden Schritte notwendig:

* Erzeugen der Datenbank
* Erzeugen der Datenbankschemas
* Kopieren des bundle Assets in das öffentliche web-Verzeichnis
* Initialisieren der Rollen
* Erzeugen des "root" Benutzers

Diese Schritte können mit dem console-Hilfsprogramm von Symfonie2 durchgeführt werden, auf dem das Mapbender3 Framework aufbaut. Hier noch ein wichtiger Hinweis, bevor Sie fortfahren: 


  | Das console-Hilfsprogramm wird Dateien in die Verzeichnisse app/cache und app/logs schreiben. 
  | Für diese Operationen werden die Benutzerrechte des Benutzers benötigt, mit dem Sie 
  | angemeldet sind. Sie benötigen ebenfalls Benutzerrechte für das Verzeichnis app/db und die
  | SQLite Datenbank.  Wenn Sie die Applikation in Ihrem Browser öffnen, wird der Server-PHP-
  | Prozess versuchen, auf  diese Dateien zuzugreifen oder in die Verzeichnisse zu schreiben mit
  |  anderen Benutzerrechten. Stellen Sie sicher,  dass Sie den Verzeichnissen und Dateien Schreib-
  |  und Leserechte zugewiesen haben. 


Anpassen der Konfigurationsdatei
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^ 
Die Parameter der Datenbankverbindung sind zusammen mit einigen anderen Konfigurationsparametern in der Datei app/config/parameters.yml gespeichert. In dieser Datei  wird YAML Syntax verwendet. Achten Sie darauf **keine** Tabulatoren für Einrückungen zu verwenden. Verwenden Sie stattdessen Leerzeichen.


Erzeugen der Datenbank
^^^^^^^^^^^^^^^^^^^^^^^^ 

Mit Symfony2 kann die Datenbank erzeugt werden. Beachten Sie, dass dazu die benötigten Datenbank-Benutzerrechte vorliegen. Rufen Sie folgenden Befehl mit dem console-Hilfsprogramm auf:

    :command:`app/console doctrine:database:create` 


Erzeugen des Datenbankschemas
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^ 

Erzeugen des Datenbankschemas über Symfony2:

    :command:`app/console doctrine:schema:create` 




Kopieren des bundles' assets
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^ 

Jedes Bundle hat seine eigenen Abhängigkeiten - CSS-Dateien, JavaScript-Dateien, Bilder und mehr – diese müssen in das öffentliche web-Verzeichnis kopiert werden:

    :command:`app/console assets:install web` 


Sie können auch einen symbolischen Link verwenden, statt die Dateien zu kopieren.  Dies erleichtert die Bearbeitung der abhängigen Dateien in den bundle-Verzeichnissen.



Initialisierung des Mapbender Rollen-Systems
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^ 

Die Standardrollen müssen in der Datenbank initialisiert werden:

    :command:`app/console fom:user:initroles` 



Erzeugen des administrativen Benutzers
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^ 

Der erste Benutzer, der alle Privilegien hat, wird mit folgendem Kommando erzeugt:

    :command:`app/console fom:user:resetroot` 


Dieses Kommando wird interaktiv alle notwendigen Informationen abfragen und den Benutzer in der Datenbank erzeugen.

Öffnen Sie nun den Browser und lernen Sie Mapbender3 kennen.
