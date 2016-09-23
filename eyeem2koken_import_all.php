<?
/////////////////////////////////////////////////////////////////////////////////////////////////
//                                                                                             //
//                               IMPORT ALL                                                    //
//                                                                                             //
/////////////////////////////////////////////////////////////////////////////////////////////////
// created by niz                                                                              //
/////////////////////////////////////////////////////////////////////////////////////////////////
// This script transfert your ALL pictures from your Eyeem Account to your Koken server        //
/////////////////////////////////////////////////////////////////////////////////////////////////
require('gps.php');
require('config.php');
require('functions.php');
require('eyeem2koken.php');

global $EyeemClientID, $EyeemClientSecret, $eyeem_token, $eyeem_username, $koken_url, $koken_token, $dir, $file, $eyeem_api_url, $koken_api_url;
///////////////////////////////////////////////////////////////////////////////////////////////
//Requesting picture list from Eyeem
//one first request to get the number of pictures of this account
///////////////////////////////////////////////////////////////////////////////////////////////
$eyeem_photos_list = 'users/'.$eyeem_username.'/photos?access_token='.$eyeem_token;
$photo_list_data = request($eyeem_api_url.$eyeem_photos_list);
$lp = $photo_list_data->photos->total;

// Importing
for ($a = 1; $a <= $lp; $a++) {
	eyeem2koken();
}

?>
