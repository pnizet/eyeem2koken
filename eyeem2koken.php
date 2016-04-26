<?
////////////////////////////////////////////////////////////////////////////////////////////////
//                                                                                                                                                                                        //
//                                                            EYEEM2KOKEN                                                                                                //
//                                                                                                                                                                                        //
////////////////////////////////////////////////////////////////////////////////////////////////
// created by niz                                                                                                                                                               //
////////////////////////////////////////////////////////////////////////////////////////////////
// This script transfert your pictures from your Eyeem Account to your Koken server                                           //
//   It transferts one picture at the time (aims to be used with cron once a day for ex)                                           //
////////////////////////////////////////////////////////////////////////////////////////////////
//                                                                                                                                                                                        //
//                 The sequence :                                                                                                                                              //
//                 	* Requesting picture list from Eyeem                                                                                             //
//                 	* Reading last picture sent                                                                                                               //
//                 	* Identifying picture to sent                                                                                                           //
//                 	* Requesting picture details                                                                                                             //
//                 	* Extracting datas form Eyeem Database                                                                                       //
//                 	* Download locally the picture                                                                                                         //
//                 	* Uploading the picture and datas to Koken                                                                                    //
//                 	* Storing id last uploaded picture                                                                                                   //
//                                                                                                                                                                                        //
////////////////////////////////////////////////////////////////////////////////////////////////
// I'm using it with cron                                                                                                                                                  //
// in a shell launch this command : vi /etc/crontab :                                                                                                     //
// Add the following line to the crontab file   (will launch the script everyday at 10h00                                          //
// 0  10   *   *   *   root    cd [YOUR_PATH]/eyeem2koken; php -f eyeem2koken.php                                                //
////////////////////////////////////////////////////////////////////////////////////////////////
//    TODO :                                                                                                                                                                       //
//    * Find the correct syntax to keep the captured date (Eyeem side is OK, Koken not)                                          //
////////////////////////////////////////////////////////////////////////////////////////////////

//Configs
$eyeem_token = "YOUR_EYEEM_TOKEN";
$eyeem_username = "YOUR_EYEEM_USERNAME"; 
$koken_url = 'YOUR_KOKEN_SERVER_URL';
$koken_token = "YOUR_KOKEN_TOKEN";
$img_dir = 'YOUR_PATH/img/';    //YOU HAVE TO CREATE A img DIRECTORY
$file = 'last.txt';

//API URLs
$eyeem_api_url = 'https://api.eyeem.com/v2/';
$koken_api_url = $koken_url.'/api.php?/content';

$eyeem_limit = "200";
$eyeem_photos_list = 'users/'.$eyeem_username.'/photos?access_token='.$eyeem_token.'&limit='.$eyeem_limit;


///////////////////////////////////////////////////////////////////////////////////////////////
//Requesting picture list from Eyeem
$curl = curl_init($eyeem_api_url.$eyeem_photos_list);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
$curl_response = curl_exec($curl);

if ($curl_response === false) {
    $info = curl_getinfo($curl);
    curl_close($curl);
    die('error occured during curl exec. Additional info: ' . var_export($info));
}
curl_close($curl);
$decoded = json_decode($curl_response);
if (isset($decoded->response->status) && $decoded->response->status == 'ERROR') {
    die('error occured: ' . $decoded->response->errormessage);
}
echo 'Request 1 Eyeem ok!';
echo '<br/>';

$lp = $decoded->photos->total;


///////////////////////////////////////////////////////////////////////////////////////////////
//Reading last picture sent
if(file_exists($file)) {
	$fh = fopen($file,'r');
	while ($line = fgets($fh)) {
	  $last_photo_uploaded = $line;
	}
	fclose($fh);
} else {
	$fh = fopen($file,'w');
	fwrite($fh,$decoded->photos->items[$lp]->id);
	fclose($fh);
}


///////////////////////////////////////////////////////////////////////////////////////////////
//Identifying picture to sent
for ($i = $eyeem_limit; $i >= 1; $i--) {
		if ($decoded->photos->items[$i]->id == $last_photo_uploaded) {
			$j=$i-1;
		}
}
$eyeem_photo = $decoded->photos->items[$j]->id;


///////////////////////////////////////////////////////////////////////////////////////////////
//Requesting picture details
$eyeem_photos_data = 'photos/'.$eyeem_photo.'?access_token='.$eyeem_token.'&detailed=1';

$curl = curl_init($eyeem_api_url.$eyeem_photos_data);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
$curl_response = curl_exec($curl);

if ($curl_response === false) {
    $info = curl_getinfo($curl);
    curl_close($curl);
    die('error occured during Eyeem curl exec. Additioanl info: ' . var_export($info));
}
curl_close($curl);
$decoded = json_decode($curl_response);
if (isset($decoded->response->status) && $decoded->response->status == 'ERROR') {
    die('Eyeem error occured: ' . $decoded->response->errormessage);
}


///////////////////////////////////////////////////////////////////////////////////////////////
//Extracting datas 
$width = $decoded->photo->width; 
$height = $decoded->photo->height; 
$latitude = $decoded->photo->latitude; 
$longitude = $decoded->photo->longitude; 
$updated = $decoded->photo->updated; 
$title = $decoded->photo->title; 
$caption = $decoded->photo->caption; 
$photoUrl= $decoded->photo->photoUrl; 
$url_photo_big = preg_replace('(\d+/\d+)',$width.'/'.$height,$photoUrl,1); 
$tags = 'mobile_photography,eyeem,'.preg_replace('/\s/',',',$caption);


///////////////////////////////////////////////////////////////////////////////////////////////
// Download locally the picture (with a clean filename)
$tmp_filename = preg_replace(array('/\s/', '/\.[\.]+/', '/[^\w_\.\-]/'), array('_', '.', ''), $title.'.jpg');
$result = download($url_photo_big,$img_dir.$tmp_filename);


///////////////////////////////////////////////////////////////////////////////////////////////
//Uploading the picture and datas to Koken
$service_url = $koken_api_url;
$curl = curl_init($service_url);
$file_name_with_full_path = realpath($img_dir.$tmp_filename);
$curl_post_data = array(
	'file'          => new CurlFile($file_name_with_full_path, 'image/jpg'),
    'visibility' 	=> 'public',
    'name'			=> $tmp_filename,
    'upload_session_start'=> $updated ,
    'license'		=> 'all',
    'max_download'	=> 'none',
	'title'			=> $title ,
	'tags'			=> $tags ,
	'latitude' 		=> $latitude ,
	'longitude' 	=> $longitude 	
);
curl_setopt($curl, CURLOPT_SAFE_UPLOAD, false);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_TIMEOUT, 100);
curl_setopt($curl, CURLOPT_POST, true);
curl_setopt($curl, CURLOPT_HTTPHEADER, array('X-Koken-Token: $koken_token'));
curl_setopt($curl, CURLOPT_POSTFIELDS, $curl_post_data);

$curl_response = curl_exec($curl);

if ($curl_response === false) {
    $info = curl_getinfo($curl);
    curl_close($curl);
    die('error occured during Koken curl exec. Additioanl info: ' . var_export($info));
}
curl_close($curl);
$decoded = json_decode($curl_response);
if (isset($decoded->response->status) && $decoded->response->status == 'ERROR') {
    die('Koken error occured: ' . $decoded->response->errormessage);
}


///////////////////////////////////////////////////////////////////////////////////////////////
// Storing id last uploaded picture
$fh = fopen($file,'w');
fwrite($fh,$eyeem_photo);
fclose($fh);


///////////////////////////////////////////////////////////////////////////////////////////////
function download($file_source, $file_target) {
    $rh = fopen($file_source, 'rb');
    $wh = fopen($file_target, 'w+b');
    if (!$rh || !$wh) {
        return false;
    }

    while (!feof($rh)) {
        if (fwrite($wh, fread($rh, 4096)) === FALSE) {
            return false;
        }
        echo ' ';
        flush();
    }

    fclose($rh);
    fclose($wh);

    return true;
}


?>
