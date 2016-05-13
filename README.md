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

Setup
---------------------
* Launch setup.php
* Eyeem access Token : 
	* Click on the provided link to get yours
	* Go to https://www.eyeem.com/developers/apps/list 
	* And create an app to get ClientID and a ClientSecret
	* copy the value of ClientID and ClientSecret inside config.php and save it
	* Replace YOUR_KOKEN_USERNAME value by your username
* Relaunch setup.php
* Click on the provided link, Now your Eyeem token is configured !
* koken access token : 
  * Go to your admin panel
  * Settings / Application
  * Create new access token
  * Choose "Read and Write" add a description
  * Copy the given token and replace YOUR_KOKEN_TOKEN in the following line `$koken_token = "YOUR_KOKEN_TOKEN";  ` of config.php
  * Replace YOUR_KOKEN_SERVER_URL in the following line `$koken_url = 'YOUR_KOKEN_SERVER_URL';` by your url
* Setup is done !

Using
---------------------
* You can launch eyeem2koken.php to upload your first picture to your koken install
* Or you can Configure your cron
 * launch the following command `vi /etc/crontab`
 * insert the line at the begining : `5  *   *   *   *   root    cd [YOUR_PATH]/eyeem2koken; php -f eyeem2koken.php   `
  
Once it's done the script will execute every 5 minutes to check if there is a new picture on your eyeem account and will upload it to your koken install.


Disclaimer
---------------------
* code could be improved for sure (I'm an amateur codeur)
* There is no doc concerning the Koken API (it as been ""reverse-engineered"" by reading the code)
