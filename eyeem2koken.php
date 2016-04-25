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
//var_export($decoded->response); A SUPPR
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

echo 'last_photo_uploaded : '.$last_photo_uploaded;
echo '<br/>';

///////////////////////////////////////////////////////////////////////////////////////////////
//Identifying picture to sent
//Extraction de liste des photos les plus anciennes en premier (on sort de la boucle des que l'on a trouvé la derniere photo traitée,)   A SUPPR
for ($i = $eyeem_limit; $i >= 1; $i--) {
	//echo $decoded->photos->items[$i]->id;  A SUPPR
	//echo '<br/>';  A SUPPR
		if ($decoded->photos->items[$i]->id == $last_photo_uploaded) {
			$j=$i-1;
		}
}


$eyeem_photo = $decoded->photos->items[$j]->id;
//echo $eyeem_photo;  A SUPPR
//echo '<br/>';  A SUPPR

///////////////////////////////////////////////////////////////////////////////////////////////
//Requesting picture details
//requete 2 Eyeem  A SUPPR
$eyeem_photos_data = 'photos/'.$eyeem_photo.'?access_token='.$eyeem_token.'&detailed=1';
//echo($eyeem_api_url.$eyeem_photos_data);  A SUPPR
//echo '<br/>';  A SUPPR
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
//dump($decoded->photo);A SUPPR
echo 'Request 2 Eyeem ok!'; //A SUPPR
echo '<br/>'; //A SUPPR

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
curl_setopt($curl, CURLOPT_HTTPHEADER, array('X-Koken-Token: d6e721dd70adea71317c38c95fbd5c56'));
curl_setopt($curl, CURLOPT_POSTFIELDS, $curl_post_data);

//dump($curl_post_data); A SUPPR

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
echo 'Upload koken ok!'; //A SUPPR
echo '<br/>'; //A SUPPR
//var_export($decoded->response); A SUPPR


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


















////////////////////////////////////////////////    
function dump($var,$caption="",$echo=true,$depth=0,$tableWrap=true)                                                                                                                                                       // A SUPPR
{                                                                                                                                                                                                                         // A SUPPR
	global $dumped_css;                                                                                                                                                                                                   // A SUPPR
                                                                                                                                                                                                                          // A SUPPR
	$wrapInTable=true;                                                                                                                                                                                                    // A SUPPR
                                                                                                                                                                                                                          // A SUPPR
	if($depth>12)                                                                                                                                                                                                         // A SUPPR
	{                                                                                                                                                                                                                     // A SUPPR
		$ret = "<i>[too deep]</i>\n";                                                                                                                                                                                     // A SUPPR
		if($echo) echo $ret;                                                                                                                                                                                              // A SUPPR
		return($ret);                                                                                                                                                                                                     // A SUPPR
	}                                                                                                                                                                                                                     // A SUPPR
                                                                                                                                                                                                                          // A SUPPR
	$type=gettype($var);                                                                                                                                                                                                  // A SUPPR
                                                                                                                                                                                                                          // A SUPPR
	if($caption=="")                                                                                                                                                                                                      // A SUPPR
	{                                                                                                                                                                                                                     // A SUPPR
		$caption=$type;                                                                                                                                                                                                   // A SUPPR
	}                                                                                                                                                                                                                     // A SUPPR
	else                                                                                                                                                                                                                  // A SUPPR
	{                                                                                                                                                                                                                     // A SUPPR
		$caption.=" - ".$type;                                                                                                                                                                                            // A SUPPR
	}                                                                                                                                                                                                                     // A SUPPR
                                                                                                                                                                                                                          // A SUPPR
	$ret="";                                                                                                                                                                                                              // A SUPPR
	$css="";                                                                                                                                                                                                              // A SUPPR
                                                                                                                                                                                                                          // A SUPPR
	if(!$dumped_css)                                                                                                                                                                                                      // A SUPPR
	{                                                                                                                                                                                                                     // A SUPPR
		$dumped_css=true;                                                                                                                                                                                                 // A SUPPR
                                                                                                                                                                                                                          // A SUPPR
		$css.="<style type='text/css'>";                                                                                                                                                                                  // A SUPPR
		$css.=".dump";                                                                                                                                                                                                    // A SUPPR
		$css.="{";                                                                                                                                                                                                        // A SUPPR
		$css.="	background:white;";                                                                                                                                                                                       // A SUPPR
		$css.="	color:black;";                                                                                                                                                                                            // A SUPPR
		$css.="	border-top:1px solid black;";                                                                                                                                                                             // A SUPPR
		$css.="	border-left:1px solid black;";                                                                                                                                                                            // A SUPPR
		$css.="}\n";                                                                                                                                                                                                      // A SUPPR
		$css.=".dump td";                                                                                                                                                                                                 // A SUPPR
		$css.="{";                                                                                                                                                                                                        // A SUPPR
		$css.="	padding-left:6px;";                                                                                                                                                                                       // A SUPPR
		$css.="	padding-right:6px;";                                                                                                                                                                                      // A SUPPR
		$css.="	border-bottom:1px solid black;";                                                                                                                                                                          // A SUPPR
		$css.="	border-right:1px solid black;";                                                                                                                                                                           // A SUPPR
		$css.="}\n";                                                                                                                                                                                                      // A SUPPR
		$css.=".dump_header";                                                                                                                                                                                             // A SUPPR
		$css.="{";                                                                                                                                                                                                        // A SUPPR
		$css.="	background:black;";                                                                                                                                                                                       // A SUPPR
		$css.="	color:white;";                                                                                                                                                                                            // A SUPPR
		$css.="}\n";                                                                                                                                                                                                      // A SUPPR
		$css.=".dump_type";                                                                                                                                                                                               // A SUPPR
		$css.="{";                                                                                                                                                                                                        // A SUPPR
		$css.="	background:gray;";                                                                                                                                                                                        // A SUPPR
		$css.="	color:white;";                                                                                                                                                                                            // A SUPPR
		$css.="}\n";                                                                                                                                                                                                      // A SUPPR
		$css.=".dump_label";                                                                                                                                                                                              // A SUPPR
		$css.="{";                                                                                                                                                                                                        // A SUPPR
		$css.="	background:silver;";                                                                                                                                                                                      // A SUPPR
		$css.="	color:black;";                                                                                                                                                                                            // A SUPPR
		$css.="}\n";                                                                                                                                                                                                      // A SUPPR
		$css.=".dump_value";                                                                                                                                                                                              // A SUPPR
		$css.="{";                                                                                                                                                                                                        // A SUPPR
		$css.="}\n";                                                                                                                                                                                                      // A SUPPR
		$css.=".dump_scalar";                                                                                                                                                                                             // A SUPPR
		$css.="{";                                                                                                                                                                                                        // A SUPPR
		$css.="max-height:200px; max-width:800px; overflow:auto;";                                                                                                                                                        // A SUPPR
		$css.="}";                                                                                                                                                                                                        // A SUPPR
		$css.="</style>";                                                                                                                                                                                                 // A SUPPR
	}                                                                                                                                                                                                                     // A SUPPR
                                                                                                                                                                                                                          // A SUPPR
	if(is_null($var))                                                                                                                                                                                                     // A SUPPR
	{                                                                                                                                                                                                                     // A SUPPR
		$ret.="<tr><td colspan='3'><i>-NULL-</i></td></tr>\n";                                                                                                                                                            // A SUPPR
	}                                                                                                                                                                                                                     // A SUPPR
	elseif(is_scalar($var))                                                                                                                                                                                               // A SUPPR
	{                                                                                                                                                                                                                     // A SUPPR
		$ret.="<tr><td colspan='3' class='dump_value'><div class='dump_scalar'>".($var.""=="" ? "<i>[empty]</i>" : htmlentities($var))."</div></td></tr>\n";                                                              // A SUPPR
	}                                                                                                                                                                                                                     // A SUPPR
	elseif(is_resource($var))                                                                                                                                                                                             // A SUPPR
	{                                                                                                                                                                                                                     // A SUPPR
		$ret.="<tr><td colspan='3'><i>[handle]</i></td></tr>\n";                                                                                                                                                          // A SUPPR
	}                                                                                                                                                                                                                     // A SUPPR
	elseif(is_array($var))                                                                                                                                                                                                // A SUPPR
	{                                                                                                                                                                                                                     // A SUPPR
		foreach($var as $k=>$v)                                                                                                                                                                                           // A SUPPR
		{                                                                                                                                                                                                                 // A SUPPR
			$tp=strtolower(gettype($v));                                                                                                                                                                                  // A SUPPR
			if(is_bool($v)) $v=($v?"true":"false");                                                                                                                                                                       // A SUPPR
			$ret.="<tr valign='top'>";                                                                                                                                                                                    // A SUPPR
			$ret.="<td class='dump_type' style='cursor:pointer' onclick='l=this.nextSibling; v=l.nextSibling; vis=v.style.display==\"\"; v.style.display=(vis ? \"none\" : \"\"); l.colSpan=(vis ? 2 : 1);'>".$tp."</td>"; // A SUPPR
			$ret.="<td class='dump_label'>".$k."</td>";                                                                                                                                                                   // A SUPPR
			if(is_scalar($v))                                                                                                                                                                                             // A SUPPR
			{                                                                                                                                                                                                             // A SUPPR
				$ret.="<td class='dump_value'><div class='dump_scalar'>".($v.""=="" ? "<i>[empty]</i>" : htmlentities($v))."</div></td>";                                                                                 // A SUPPR
			}                                                                                                                                                                                                             // A SUPPR
			elseif(is_null($v))                                                                                                                                                                                           // A SUPPR
			{                                                                                                                                                                                                             // A SUPPR
				$ret.="<td class='dump_value'><i>[null]</i></td>";                                                                                                                                                        // A SUPPR
			}                                                                                                                                                                                                             // A SUPPR
			else                                                                                                                                                                                                          // A SUPPR
			{                                                                                                                                                                                                             // A SUPPR
				try                                                                                                                                                                                                       // A SUPPR
				{                                                                                                                                                                                                         // A SUPPR
					if(preg_match("/\bGLOBALS\b/",$k) && is_array($v) && isset($v[$k]))                                                                                                                                   // A SUPPR
					{                                                                                                                                                                                                     // A SUPPR
						$ret.="<td class='dump_value'><i>[global recursion loop]</i></td>";                                                                                                                               // A SUPPR
					}                                                                                                                                                                                                     // A SUPPR
					else                                                                                                                                                                                                  // A SUPPR
					{                                                                                                                                                                                                     // A SUPPR
						$ret.="<td class='dump_value'>".dump($v,"",false,$depth+1)."</td>";                                                                                                                               // A SUPPR
					}                                                                                                                                                                                                     // A SUPPR
				}                                                                                                                                                                                                         // A SUPPR
				catch(Exception $e)                                                                                                                                                                                       // A SUPPR
				{                                                                                                                                                                                                         // A SUPPR
					$ret.="<td class='dump_value'><i>".dump($e,"ERROR",false)."</i></td>";                                                                                                                                // A SUPPR
				}                                                                                                                                                                                                         // A SUPPR
			}                                                                                                                                                                                                             // A SUPPR
			$ret.="</tr>\n";                                                                                                                                                                                              // A SUPPR
		}                                                                                                                                                                                                                 // A SUPPR
	}                                                                                                                                                                                                                     // A SUPPR
	elseif($type=="object")                                                                                                                                                                                               // A SUPPR
	{                                                                                                                                                                                                                     // A SUPPR
		$class=get_class($var);                                                                                                                                                                                           // A SUPPR
		$caption.=" ~ ".$class;                                                                                                                                                                                           // A SUPPR
		$properties=get_class_vars($class);                                                                                                                                                                               // A SUPPR
		$methods=get_class_methods($class);                                                                                                                                                                               // A SUPPR
		$v=array();                                                                                                                                                                                                       // A SUPPR
		$obj=print_r($var,true);                                                                                                                                                                                          // A SUPPR
		$k=explode("\n",$obj);                                                                                                                                                                                            // A SUPPR
		for($x=0;$x<count($k);$x++)                                                                                                                                                                                       // A SUPPR
		{                                                                                                                                                                                                                 // A SUPPR
			if(preg_match("/^[\s\t]*\[[a-z][a-z0-9]+\][\s\t]*=>/i",$k[$x]))                                                                                                                                               // A SUPPR
			{                                                                                                                                                                                                             // A SUPPR
				$key=preg_replace("/^[\s\t]*\[([a-z][a-z0-9]+)\].*/i","$1",$k[$x]);                                                                                                                                       // A SUPPR
				try                                                                                                                                                                                                       // A SUPPR
				{                                                                                                                                                                                                         // A SUPPR
					eval('$v["'.$key.'"]=$var->'.$key.';');                                                                                                                                                               // A SUPPR
					if(strtolower($key)=="parentnode" && gettype($v[$key])=="object")                                                                                                                                     // A SUPPR
					{                                                                                                                                                                                                     // A SUPPR
						$v[$key]="object ~ ".get_class($v[$key]);                                                                                                                                                         // A SUPPR
					}                                                                                                                                                                                                     // A SUPPR
				}                                                                                                                                                                                                         // A SUPPR
				catch(Exception $e)                                                                                                                                                                                       // A SUPPR
				{                                                                                                                                                                                                         // A SUPPR
					$v[$key]='Error: '.$e->getMessage();                                                                                                                                                                  // A SUPPR
				}                                                                                                                                                                                                         // A SUPPR
				try                                                                                                                                                                                                       // A SUPPR
				{                                                                                                                                                                                                         // A SUPPR
					if($v[$key]===$var) $v[$key]="<i>recursion loop</i>";                                                                                                                                                 // A SUPPR
				}                                                                                                                                                                                                         // A SUPPR
				catch(Exception $e)                                                                                                                                                                                       // A SUPPR
				{                                                                                                                                                                                                         // A SUPPR
					$v[$key]="Error: ".$e->getMessage();                                                                                                                                                                  // A SUPPR
				}                                                                                                                                                                                                         // A SUPPR
			}                                                                                                                                                                                                             // A SUPPR
		}                                                                                                                                                                                                                 // A SUPPR
		try                                                                                                                                                                                                               // A SUPPR
		{                                                                                                                                                                                                                 // A SUPPR
			$ret=dump($v,"",false,$depth+1,false);                                                                                                                                                                        // A SUPPR
		}                                                                                                                                                                                                                 // A SUPPR
		catch(Exception $e)                                                                                                                                                                                               // A SUPPR
		{                                                                                                                                                                                                                 // A SUPPR
			$ret.="<tr><td colspan='3'><i>".$e->getMessage()."</i></td></tr>\n";                                                                                                                                          // A SUPPR
		}                                                                                                                                                                                                                 // A SUPPR
	}                                                                                                                                                                                                                     // A SUPPR
	else                                                                                                                                                                                                                  // A SUPPR
	{                                                                                                                                                                                                                     // A SUPPR
		$ret.="<tr><td colspan='3'><i>[unknown type]</i></td></tr>\n";                                                                                                                                                    // A SUPPR
	}                                                                                                                                                                                                                     // A SUPPR
                                                                                                                                                                                                                          // A SUPPR
	if($tableWrap)                                                                                                                                                                                                        // A SUPPR
	{                                                                                                                                                                                                                     // A SUPPR
		$ret=$css."<table border='0' cellpadding='2' cellspacing='0' class='dump'><tr class='dump_header'><td colspan='3'>".$caption."</td></tr>\n".$ret."</table>\n";                                                    // A SUPPR
		if($echo) echo $ret;                                                                                                                                                                                              // A SUPPR
	}                                                                                                                                                                                                                     // A SUPPR
                                                                                                                                                                                                                          // A SUPPR
	return($ret);                                                                                                                                                                                                         // A SUPPR
}                                                                                                                                                                                                                         // A SUPPR
                                                                                                                                                                                                                          // A SUPPR
                                                                                                                                                                                                                          // A SUPPR
                                                                                                                                                                                                                          // A SUPPR
?>
