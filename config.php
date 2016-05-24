<?
//Configs
///////////////////////////////////////////////////////////////////////////////////////////////
// The following lines require to be manualy modified
///////////////////////////////////////////////////////////////////////////////////////////////
//Eyeem
$EyeemClientID = 'YOUR_EYEEM_CLIENT_ID';
$EyeemClientSecret = 'YOUR_EYEEM_CLIENT_SECRET';
$eyeem_username = "YOUR_EYEEM_USERNAME"; 

//koken
$koken_url = 'YOUR_KOKEN_SERVER_URL';

///////////////////////////////////////////////////////////////////////////////////////////////
// The following lines will be automatically modified by setup.php
// DO NOT MODIFY THEM, browse setup.php
///////////////////////////////////////////////////////////////////////////////////////////////
//Tokens
$eyeem_token = "YOUR_EYEEM_TOKEN";
$koken_token = "YOUR_KOKEN_TOKEN";

//eyeem2koken
$dir = getcwd().'/img/';  
$file = 'last.txt';

//API URLs
$eyeem_api_url = 'https://api.eyeem.com/v2/';
$koken_api_url = $koken_url.'/api.php?/content';
?>