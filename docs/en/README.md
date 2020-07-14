# IPSymconLinkTap
[![Version](https://img.shields.io/badge/Symcon-PHPModul-red.svg)](https://www.symcon.de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/)
[![Version](https://img.shields.io/badge/Symcon%20Version-5.0%20%3E-green.svg)](https://www.symcon.de/forum/threads/37412-IP-Symcon-5-0-%28Testing%29)

Module for IP-Symcon from version 5. Allows communication with [LinkTap](https://www.link-tap.com/) devices.

## Documentation

**Table of Contents**

1. [Features](#1-features)
2. [Requirements](#2-requirements)
3. [Installation](#3-installation)
4. [Function reference](#4-functionreference)
5. [Configuration](#5-configuration)
6. [Annex](#6-annex)

## 1. Features

Read data from a LinkTap devices and control the device via LinkTap API. 
	  
## 2. Requirements

 - IPS 5.2
 - LinkTap account
 - [LinkTap](https://www.link-tap.com/)

## 3. Installation

### a. Loading the module

Open the IP Console's web console with _http://{IP-Symcon IP}:3777/console/_.

Then click on the module store (IP-Symcon > 5.2) icon in the upper right corner.

![Store](img/store_icon.png?raw=true "open store")

In the search field type

```
LinkTap
```  


![Store](img/module_store_search_en.png?raw=true "module search")

Then select the module and click _Install_

![Store](img/install_en.png?raw=true "install")

### b. LinkTap IO
An account with LinkTap is required, which is used for the LinkTap device.

First, when installing the module, you are asked whether you want to create a configutator instance, you answer this with _yes_, but you can also create the configurator instance yourself

### c. Setup of the configurator module

Now we switch to the instance _**LinkTap**_ (type LinkTap Configurator) in the object tree under _Configurator Instances_.

All devices that are registered with LinkTap under the account and supported by the LinkTap API are listed here.

A single device can be created by marking the device and pressing the _Create_ button. The configurator then creates a device instance.

### d. Device instance setup
Manual configuration of a device module is not necessary, this is done using the configurator. Individual variables can still be activated in the device module for display on the web front if required.


## 4. Function reference

### a. Webfront View

![Webfront](img/webfront_linktap.png?raw=true "Webfront")  

### b. Methods

### LinkTap Device:
 
**Watering On**
```php
LINKTAP_Watering_On(int $InstanceID)
``` 
Parameter _$InstanceID_ ObjektID des Echo Remote Devices

**Watering Off**
```php
LINKTAP_Watering_Off(int $InstanceID)
``` 
Parameter _$InstanceID_ ObjektID des Echo Remote Devices

**Activate Interval Mode**
```php
LINKTAP_ActivateIntervalMode(int $InstanceID)
``` 
Parameter _$InstanceID_ ObjektID des Echo Remote Devices

**Activate Odd EvenMode**
```php
LINKTAP_ActivateOddEvenMode(int $InstanceID)
``` 
Parameter _$InstanceID_ ObjektID des Echo Remote Devices

**Activate Seven Day Mode**
```php
LINKTAP_ActivateSevenDayMode(int $InstanceID)
``` 
Parameter _$InstanceID_ ObjektID des Echo Remote Devices

**Activate Month Mode**
```php
LINKTAP_ActivateMonthMode(int $InstanceID)
``` 
Parameter _$InstanceID_ ObjektID des Echo Remote Devices

**Get All Devices**
```php
LINKTAP_Get_All_Devices(int $InstanceID)
``` 
Parameter _$InstanceID_ ObjektID des Echo Remote Devices

**Watering Status**
```php
LINKTAP_Watering_Status(int $InstanceID)
``` 
Parameter _$InstanceID_ ObjektID des Echo Remote Devices

## 5. Configuration:




## 6. Annnex

###  GUIDs und Data Flow:

#### LinkTap IO:

GUID: `{F3E543DF-E914-748B-EB0B-E3348AF969B6}` 


#### LinkTap Device:

GUID: `{A3CE72F6-0C41-4B7D-3A3A-05E1C6E94CDE}` 
