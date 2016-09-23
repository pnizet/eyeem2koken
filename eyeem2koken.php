<?
/////////////////////////////////////////////////////////////////////////////////////////////////
//                                                                                             //
//                               EYEEM2KOKEN                                                   //
//                                                                                             //
/////////////////////////////////////////////////////////////////////////////////////////////////
// created by niz                                                                              //
/////////////////////////////////////////////////////////////////////////////////////////////////
// This function transfert your pictures from your Eyeem Account to your Koken server          //
//   It transferts one picture at the time (aims to be used with cron once a day for ex)       //
/////////////////////////////////////////////////////////////////////////////////////////////////
//                                                                                             //
//                 The sequence :                                                              //
//                 	* Requesting picture list from Eyeem                                       //
//                 	* Reading last picture sent                                                //
//                 	* Identifying picture to sent                                              //
//                 	* Requesting picture details                                               //
//                 	* Extracting datas form Eyeem Database                                     //
//                 	* Download locally the picture                                             //
//                 	* Uploading the picture and datas to Koken                                 //
//                 	* Storing id last uploaded picture                                         //
//                                                                                             //
/////////////////////////////////////////////////////////////////////////////////////////////////

function eyeem2koken () {
	global $EyeemClientID, $EyeemClientSecret, $eyeem_token, $eyeem_username, $koken_url, $koken_token, $dir, $file, $eyeem_api_url, $koken_api_url;
	
	echo '<br/><br/>';
	//create dir if doesn't exist
	if (!file_exists($dir) && !is_dir($dir)) {
		mkdir($dir);         
	}

	echo date('Y-m-d\TH:i:sO'); //for log
	echo '  ';                  //for log
	///////////////////////////////////////////////////////////////////////////////////////////////
	//Requesting picture list from Eyeem
	//one first request to get the number of pictures of this account
	$eyeem_limit = "2";
	$eyeem_photos_list = 'users/'.$eyeem_username.'/photos?access_token='.$eyeem_token.'&limit='.$eyeem_limit;
	$photo_list_data = request($eyeem_api_url.$eyeem_photos_list);
	$lp = $photo_list_data->photos->total;

	$eyeem_photos_list = 'users/'.$eyeem_username.'/photos?access_token='.$eyeem_token.'&limit='.$lp;
	$photo_list_data = request($eyeem_api_url.$eyeem_photos_list);
	//second one the request all the pictures
	echo 'Request photo list Eyeem ok!'; //for log
	echo '  ';                           //for log


	///////////////////////////////////////////////////////////////////////////////////////////////
	//Reading last picture sent
	if(file_exists($file)) {
		$fh = fopen($file,'r');
		while ($line = fgets($fh)) {
		  $last_photo_uploaded = $line;
		}
		fclose($fh);
	} else {
		// if file doesn't exist we create it
		$fh = fopen($file,'w');
		fwrite($fh,$photo_list_data->photos->items[$lp-1]->id);
		fclose($fh);
	}

	echo 'last_photo_uploaded : '.$last_photo_uploaded; //for log
	echo '  ';                                          //for log

	///////////////////////////////////////////////////////////////////////////////////////////////
	//Identifying picture to sent
	for ($i = $lp; $i >= 1; $i--) {
		if ($photo_list_data->photos->items[$i]->id == $last_photo_uploaded) {
			$j=$i-1;
		}
	}


	$eyeem_photo = $photo_list_data->photos->items[$j]->id;
	echo 'photo_uploading : '.$eyeem_photo; //for log
	echo '  ';                              //for log

	//if no more pictures need to be uploaded 
	if (strcmp($eyeem_photo,$last_photo_uploaded)==0 || strcmp($eyeem_photo,"")==0) {echo "No more pictures"; exit; }

	///////////////////////////////////////////////////////////////////////////////////////////////
	//Requesting picture details
	$eyeem_photos_data = 'photos/'.$eyeem_photo.'?access_token='.$eyeem_token.'&detailed=1';
	$photo_data = request($eyeem_api_url.$eyeem_photos_data);
	echo 'Request photo details Eyeem ok!'; //for log
	echo '  ';                              //for log

	///////////////////////////////////////////////////////////////////////////////////////////////
	//Extracting datas 
	$width = $photo_data->photo->width; 
	$height = $photo_data->photo->height; 
	$latitude = $photo_data->photo->latitude; 
	$longitude = $photo_data->photo->longitude; 
	$updated = $photo_data->photo->updated; 
	$title = replace_tag_number_by_text($photo_data->photo->title,'#'); 
	$caption = replace_tag_number_by_text($photo_data->photo->caption,''); 
	$photoUrl= $photo_data->photo->photoUrl; 
	$url_photo_big = preg_replace('(\d+/\d+)',$width.'/'.$height,$photoUrl,1); 
	preg_match_all('/#(\S*)/',$title,$extracted_tags_from_title); //extract tags form title
	$tags = preg_replace('/[^:alnum:](la|le|les|des|du|de|in|the|of|at)(\s|,)/','','mobile_photography,eyeem,'.preg_replace('/\s/',',',$caption).preg_replace('/#/',',',implode(' ',$extracted_tags_from_title[0])));

	echo $title; //for log
	echo '  ';   //for log
	if ($title == "") {	$title = "Untitled_".$updated;}
	
	///////////////////////////////////////////////////////////////////////////////////////////////
	// Download locally the picture (with a clean filename)
	$tmp_filename = preg_replace(array('/\s/', '/\.[\.]+/', '/[^\w_\.\-]/'), array('_', '.', ''), $title.'.jpg');
	$result = download($url_photo_big,$dir.$tmp_filename);
	$file_name_with_full_path = realpath($dir.$tmp_filename);

	///////////////////////////////////////////////////////////////////////////////////////////////
	// Embbed Exif data
	$i = new iptc($file_name_with_full_path);
	echo $i->set(IPTC_CREATED_DATE,date("Ymd",strtotime($updated)));
	echo $i->set(IPTC_CREATED_TIME,date("His",strtotime($updated)));
	echo $i->set(IPTC_OBJECT_NAME,$title);
	echo $i->set(IPTC_HEADLINE,$title);
	echo $i->set(IPTC_KEYWORDS,$tags);
	$i->write();
	addGpsInfo($file_name_with_full_path, $file_name_with_full_path, $caption, "", "", $longitude, $latitude, "", $updated);
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
	echo '  ';               //for log

	///////////////////////////////////////////////////////////////////////////////////////////////
	// Storing id last uploaded picture
	$fh = fopen($file,'w');
	fwrite($fh,$eyeem_photo);
	fclose($fh);
}

?>
