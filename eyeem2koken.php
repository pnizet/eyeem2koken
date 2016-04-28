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
$dir = 'YOUR_PATH/img/';  
$file = 'last.txt';

//API URLs
$eyeem_api_url = 'https://api.eyeem.com/v2/';
$koken_api_url = $koken_url.'/api.php?/content';

$eyeem_limit = "200";
$eyeem_photos_list = 'users/'.$eyeem_username.'/photos?access_token='.$eyeem_token.'&limit='.$eyeem_limit;

//create dir if doesn't exist
if (!file_exists($dir) && !is_dir($dir)) {
    mkdir($dir);         
}

echo date('Y-m-d\TH:i:sO'); //for log
echo '\n';                  //for log
///////////////////////////////////////////////////////////////////////////////////////////////
//Requesting picture list from Eyeem
$photo_list_data = request($eyeem_api_url.$eyeem_photos_list);
echo 'Request photo list Eyeem ok!'; //for log
echo '\n';                           //for log
$lp = $photo_list_data->photos->total;

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
	fwrite($fh,$photo_list_data->photos->items[$lp]->id);
	fclose($fh);
}

echo 'last_photo_uploaded : '.$last_photo_uploaded; //for log
echo '\n';                                          //for log

///////////////////////////////////////////////////////////////////////////////////////////////
//Identifying picture to sent
for ($i = $eyeem_limit; $i >= 1; $i--) {
	if ($photo_list_data->photos->items[$i]->id == $last_photo_uploaded) {
		$j=$i-1;
	}
}

$eyeem_photo = $photo_list_data->photos->items[$j]->id;
echo 'photo_uploading : '.$last_photo_uploaded; //for log
echo '\n';                                      //for log

///////////////////////////////////////////////////////////////////////////////////////////////
//Requesting picture details
$eyeem_photos_data = 'photos/'.$eyeem_photo.'?access_token='.$eyeem_token.'&detailed=1';
$photo_data = request($eyeem_api_url.$eyeem_photos_data);
echo 'Request photo details Eyeem ok!'; //for log
echo '\n';                              //for log

///////////////////////////////////////////////////////////////////////////////////////////////
//Extracting datas 
$width = $photo_data->photo->width; 
$height = $photo_data->photo->height; 
$latitude = $photo_data->photo->latitude; 
$longitude = $photo_data->photo->longitude; 
$updated = $photo_data->photo->updated; 
$title = replace_tag_number_by_text($photo_data->photo->title); 
$caption = replace_tag_number_by_text($photo_data->photo->caption); 
$photoUrl= $photo_data->photo->photoUrl; 
$url_photo_big = preg_replace('(\d+/\d+)',$width.'/'.$height,$photoUrl,1); 
$tags = 'mobile_photography,eyeem,'.preg_replace('/\s/',',',$caption);

echo $title; //for log
echo '\n';   //for log
if ($title == "") {	$title = "Untitled_".$updated;}
///////////////////////////////////////////////////////////////////////////////////////////////
// Download locally the picture (with a clean filename)
$tmp_filename = preg_replace(array('/\s/', '/\.[\.]+/', '/[^\w_\.\-]/'), array('_', '.', ''), $title.'.jpg');
$result = download($url_photo_big,$dir.$tmp_filename);
$file_name_with_full_path = realpath($dir.$tmp_filename);

///////////////////////////////////////////////////////////////////////////////////////////////
//Uploading the picture and datas to Koken
$service_url = $koken_api_url;
$curl = curl_init($service_url);
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
curl_setopt($curl, CURLOPT_HTTPHEADER, array('X-Koken-Token: '.$koken_token));
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
echo 'Upload koken ok!'; //for log
echo '\n';               //for log

///////////////////////////////////////////////////////////////////////////////////////////////
// Storing id last uploaded picture
$fh = fopen($file,'w');
fwrite($fh,$eyeem_photo);
fclose($fh);













///////////////////////////////////////////////////////////////////////////////////////////////
//
//                         FUNCTIONS
//
///////////////////////////////////////////////////////////////////////////////////////////////

/*****************************************************************************/
// Replace tag numbers (string like [a:46548] with the name of it
function replace_tag_number_by_text($string)
{
	global $eyeem_token,$eyeem_api_url;
	$corres = array();

	if (preg_match_all('/\[a:\d+\]/', $string, $matches)) {
		//on va chercher le titre de chaque
		foreach ($matches[0] as $item) {
			//on fait une requete eyeem pour aller chercher le tag
			preg_match('/\d+/',$item,$eyeem_album_id);
			
				///////////////////////////////////////////////////////////////////////////////////////////////
				//Requesting album details

				$eyeem_album_data = 'albums/'.$eyeem_album_id[0].'?access_token='.$eyeem_token;
				$album_data = request($eyeem_api_url.$eyeem_album_data);

		// on aggregue les données dans un tableau
		array_push($corres, array ($item, $album_data->album->name));
		}
	}
	//on fait la substitution : 
	for ($i = 0; $i <= sizeof($corres)-1; $i++) {	
		$string = preg_replace($corres[$i][0],$corres[$i][1],preg_replace('/[\[\]]/','',preg_replace('/[\[\]]/','',$string)));
		}
	return $string;
}




/*****************************************************************************/
// do a curl request
function request($url)
{
	$curl = curl_init($url);
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
	return $decoded;
}

/*****************************************************************************/
// just download a file
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
