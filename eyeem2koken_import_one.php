<?
/////////////////////////////////////////////////////////////////////////////////////////////////
//                                                                                             //
//                               IMPORT ONE                                                    //
//                                                                                             //
/////////////////////////////////////////////////////////////////////////////////////////////////
// created by niz                                                                              //
/////////////////////////////////////////////////////////////////////////////////////////////////
// This script transfert your one picture from your Eyeem Account to your Koken server         //
/////////////////////////////////////////////////////////////////////////////////////////////////
// I'm using it with cron                                                                      //
// in a shell launch this command : vi /etc/crontab :                                          //
// Add the following line to the crontab file   (will launch the script 5 minutes              //
// 5  *   *   *   *   root    cd [YOUR_PATH]/eyeem2koken; php -f eyeem2koken_import_one.php.php//
/////////////////////////////////////////////////////////////////////////////////////////////////
require('config.php');
require('functions.php');
require('eyeem2koken.php');

global $EyeemClientID, $EyeemClientSecret, $eyeem_token, $eyeem_username, $koken_url, $koken_token, $dir, $file, $eyeem_api_url, $koken_api_url;

// Importing
eyeem2koken();


?>
