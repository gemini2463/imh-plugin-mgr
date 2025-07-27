# imh-plugin-mgr, v0.0.2

cPanel and CWP plugin manager.

- cPanel/WHM path: `/usr/local/cpanel/whostmgr/docroot/cgi/imh-plugin-mgr/index.php`
- CWP path: `/usr/local/cwpsrv/htdocs/resources/admin/modules/imh-plugin-mgr.php`

## Screenshot

![Screenshot](screenshot.png)

# Installation

- Run as the Root user: `curl -fsSL https://raw.githubusercontent.com/gemini2463/imh-plugin-mgr/master/install.sh | sh`

# Files

## Shell installer

- install.sh

## Main script

- index.php - Identical to `imh-plugin-mgr.php`.
- index.php.sha256 - `sha256sum index.php > index.php.sha256`
- imh-plugin-mgr.php - Identical to `index.php`.
- imh-plugin-mgr.php.sha256 - `sha256sum imh-plugin-mgr.php > imh-plugin-mgr.php.sha256`

## Javascript

- imh-plugin-mgr.js - Bundle React or any other javascript in this file.
- imh-plugin-mgr.js.sha256 - `sha256sum imh-plugin-mgr.js > imh-plugin-mgr.js.sha256`

## Icon

- imh-plugin-mgr.png - [48x48 png image](https://api.docs.cpanel.net/guides/guide-to-whm-plugins/guide-to-whm-plugins-plugin-files/#icons)
- imh-plugin-mgr.png.sha256 - `sha256sum imh-plugin-mgr.png > imh-plugin-mgr.png.sha256`

## cPanel conf
- imh-plugin-mgr.conf - [AppConfig Configuration File](https://api.docs.cpanel.net/guides/guide-to-whm-plugins/guide-to-whm-plugins-appconfig-configuration-file)
- imh-plugin-mgr.conf.sha256 - `sha256sum imh-plugin-mgr.conf > imh-plugin-mgr.conf.sha256`

## CWP include

- cwp-include.php - [CWP include](https://wiki.centos-webpanel.com/how-to-build-a-cwp-module)
- cwp-include.php.sha256 - `sha256sum cwp-include.php > cwp-include.php.sha256`

