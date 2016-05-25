# eyeem2koken
eyeem to koken import script

The aim of this script is to import automatically your pictures from your __Eyeem__ account (https://www.eyeem.com) to your __Koken__ installation (http://koken.me/)

It also keep datas from Eyeem and transferts the biggest picture available on Eyeem to koken.
This is working with the Eyeem API, the koken API, and could be used with cron.

What you need 
---------------------
* A Eyeem Account
* A koken installation
* A server where you can setup a crontab

Setup (https://github.com/pnizet/eyeem2koken/wiki/Setup.php)
---------------------
* Launch setup.php
* Open the config.php file on your server and modifiy your EyeemClientID, EyeemClientSecret, your eyeem username and the url of your koken installation.
* Refresh setup.php
* Eyeem access Token : 
	* Click on the provided button.
	* Now your Eyeem token is configured !
* koken access token : 
	* Click on the provided button to get yours
	* Grant Acces
	* Click on "Applications" (Left panel of your koken backend)
	* Copy the token value
	* Paste it on the line  `$koken_token = "YOUR_KOKEN_TOKEN"`;
* Refresh setup.php
* Setup is done !


Using it 
---------------------
* You can **import all** your pictures launching `eyeem2koken_import_all.php` (takes few seconds per pictures)
* You can **import one** picture by launching `eyeem2koken_import_one.php`

* This last one can be configured via cron to automate the process
 * launch the following command `vi /etc/crontab`
 * insert the following line at the beginning : `5  *   *   *   *   root    cd [YOUR_PATH]/eyeem2koken; php -f eyeem2koken_import_one.php`
 Once it's done the script will execute every 5 minutes to check if there is a new picture on your eyeem account and will upload it to your koken install.

Todo
---------------------
* Include GPS in the exif
* Improve setup.php for the koken part

Disclaimer
---------------------
* code could be improved for sure (I'm an amateur codeur)
* There is no doc concerning the Koken API (it as been ""reverse-engineered"" by reading the code)
