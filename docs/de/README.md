# IPSymconLinkTap
[![Version](https://img.shields.io/badge/Symcon-PHPModul-red.svg)](https://www.symcon.de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/)
[![Version](https://img.shields.io/badge/Symcon%20Version-5.0%20%3E-green.svg)](https://www.symcon.de/forum/threads/38222-IP-Symcon-5-0-verf%C3%BCgbar)

Modul für IP-Symcon ab Version 5. Ermöglicht die Kommunikation mit einem LinkTap Gerät.

## Dokumentation

**Inhaltsverzeichnis**

1. [Funktionsumfang](#1-funktionsumfang)  
2. [Voraussetzungen](#2-voraussetzungen)  
3. [Installation](#3-installation)  
4. [Funktionsreferenz](#4-funktionsreferenz)  
5. [Anhang](#5-anhang)  

## 1. Funktionsumfang

Auslesen der Daten eines [LinkTap](https://www.link-tap.com/) Gerätes und Schalten des Geräts über die LinkTap API.


## 2. Voraussetzungen

 - IPS 5.2
 - LinkTap Benutzerkonto
 - [LinkTap](https://www.link-tap.com/)

## 3. Installation

### a. Laden des Moduls

Die Webconsole von IP-Symcon mit _http://{IP-Symcon IP}:3777/console/_ öffnen. 


Anschließend oben rechts auf das Symbol für den Modulstore (IP-Symcon > 5.2) klicken

![Store](img/store_icon.png?raw=true "open store")

Im Suchfeld nun

```
LinkTap
```  

eingeben

![Store](img/module_store_search.png?raw=true "module search")

und schließend das Modul auswählen und auf _Installieren_

![Store](img/install.png?raw=true "install")

drücken.

### b. LinkTap IO
Es wird ein Account bei LinkTap benötigt, den man für den LinkTap nutzt.

Nun öffnen wir die Konfigurator Instanz im Objekt Baum zu finden unter _Konfigurator Instanzen_. 

### c. Einrichtung des Konfigurator-Moduls

Jetzt wechseln wir im Objektbaum in die Instanz _**LinkTap**_ (Typ LinkTap Konfigurator) zu finden unter _Konfigurator Instanzen_.

Hier werden alle Geräte, die bei LinkTap unter dem Account registiert sind und von der LinkTap API unterstützt werden aufgeführt.

Ein einzelnes Gerät kann man durch markieren auf das Gerät und ein Druck auf den Button _Erstellen_ erzeugen. Der Konfigurator legt dann eine Geräte Instanz an.

### d. Einrichtung der Geräteinstanz
Eine manuelle Einrichtung eines Gerätemoduls ist nicht erforderlich, das erfolgt über den Konfigurator. In dem Geräte-Modul können noch einzelne Variablen bei Bedarf zur Anzeige im Webfront freigeschaltet werden.


## 4. Funktionsreferenz

### a. Webfront Ansicht

![Webfront](img/webfront_linktap.png?raw=true "Webfront")  

### b. Methoden

### LinkTap Gerät:
 
**Bewässerung einschalten**
```php
LINKTAP_Watering_On(int $InstanceID)
``` 
Parameter _$InstanceID_ ObjektID des Echo Remote Devices

**Bewässerung ausschalten**
```php
LINKTAP_Watering_Off(int $InstanceID)
``` 
Parameter _$InstanceID_ ObjektID des Echo Remote Devices

**Interval Modus aktivieren**
```php
LINKTAP_ActivateIntervalMode(int $InstanceID)
``` 
Parameter _$InstanceID_ ObjektID des Echo Remote Devices

**Ungerade-Gerade Modus aktivieren**
```php
LINKTAP_ActivateOddEvenMode(int $InstanceID)
``` 
Parameter _$InstanceID_ ObjektID des Echo Remote Devices

**Sieben-Tage Modus aktivieren**
```php
LINKTAP_ActivateSevenDayMode(int $InstanceID)
``` 
Parameter _$InstanceID_ ObjektID des Echo Remote Devices

**Monatsmodus aktivieren**
```php
LINKTAP_ActivateMonthMode(int $InstanceID)
``` 
Parameter _$InstanceID_ ObjektID des Echo Remote Devices

**Geräte Informationen auslesen**
```php
LINKTAP_Get_All_Devices(int $InstanceID)
``` 
Parameter _$InstanceID_ ObjektID des Echo Remote Devices

**Bewässerungsstatus auslesen**
```php
LINKTAP_Watering_Status(int $InstanceID)
``` 
Parameter _$InstanceID_ ObjektID des Echo Remote Devices


## 5. Konfiguration:



## 6. Anhang

###  GUIDs und Datenaustausch:

#### LinkTap IO:

GUID: `{F3E543DF-E914-748B-EB0B-E3348AF969B6}` 


#### LinkTap Device:

GUID: `{A3CE72F6-0C41-4B7D-3A3A-05E1C6E94CDE}` 