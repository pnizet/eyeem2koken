# eyeem2koken
eyeem to koken import script

The aim of this script is to import automatically your pictures from __Eyeem__ (https://www.eyeem.com) to your __Koken__ installation (http://koken.me/)

It also keep datas from Eyeem (GPS coords, title, caption) and trasnfert the biggest picture available on eyeem.

This is working with the Eyeem API, the koken API, and could be used with cron.

What you need 
---------------------
* A Eyeem Account
* A koken installation
* A server where you can setup a crontab

Setup
---------------------
* Eyeem access Token :
  * Go to https://www.eyeem.com/developers/apps/list 
  * And create an app to get a token
* koken access token : 
  * Go to your admin panel
  * Settings / Application
  * Create new access token
  * Choose "Read and Write" add a description
* Upload the eyeem2koken.php file to your server
* Create a `img` directory next to eyeem2koken.php
* Modify the following configs :
	   * `$eyeem_token = "YOUR_EYEEM_TOKEN";                                    `
	   * `$eyeem_username = "YOUR_EYEEM_USERNAME";                              `
	   * `$koken_url = 'YOUR_KOKEN_SERVER_URL';                                 `
	   * `$koken_token = "YOUR_KOKEN_TOKEN";                                    `
	   * `$img_dir = 'YOUR_PATH/img/';    //YOU HAVE TO CREATE A img DIRECTORY  `
 * Configure your cron
  * launch the following command `vi /etc/crontab`
  * insert the line at the begining : `0  10   *   *   *   root    cd [YOUR_PATH]/eyeem2koken; php -f eyeem2koken.php   `
  
Once it's done the script will execute every day at 10h00 to check if there is a new picture on your eyeem account and will upload it to your koken install.


Disclaimer
---------------------
* code could be improved for sure
* There is no doc concerning the Koken API (it as been ""reverse-engineered"" by reading the code)
