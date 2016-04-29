<?php
require('config.php');
require('functions.php');

//verification préliminaire
if (strcmp($EyeemClientID,'YOUR_EYEEM_CLIENT_ID')==0) {
	echo "<p>First of all, Modify your EyeemClientID and EyeemClientSecret in the config.php file</p>";
	echo "<p>Those values can be found on <a href=\"https://www.eyeem.com/developers\">https://www.eyeem.com/developers</a> by registring an app</p>";
	exit;
}


$protocol = strpos(strtolower($_SERVER['SERVER_PROTOCOL']),'https') === FALSE ? 'http' : 'https';
$host     = $_SERVER['HTTP_HOST'];
$script   = $_SERVER['SCRIPT_NAME'];
$params   = $_SERVER['QUERY_STRING'];

$currentUrl = $protocol . '://' . $host . $script . '?' . $params;
$currentUrlShort = $protocol . '://' . $host . $script ;


$url_OAuth_code = 'http://www.eyeem.com/oauth/authorize?response_type=code&client_id='.$EyeemClientID.'&redirect_uri='.$currentUrl;

///////////////////////////////////////////////////////////
echo "<br/>";
echo "<p>This script will get your Eyeem Token and add it the Eyeem2koken config file. </p>";
echo '<a href='.$url_OAuth_code.' ><p>Click here to start the Eyeem Authorization</p></a>';
echo "<br/>";
echo "<br/>";


//analyse de l'URL
$code = preg_split ( '/=/' , parse_url($currentUrl)[query])[1];



if ($code != "") {
	//$currentUrl = str_replace('?','&',$currentUrl);
	$eyeem_OAuth_url = 'oauth/token?grant_type=authorization_code&client_id='.$EyeemClientID.'&client_secret='.$EyeemClientSecret.'&code='.$code.'&redirect_url='.$currentUrlShort.'';
	$eyeem_OAuth_data = request($eyeem_api_url.$eyeem_OAuth_url);
	$token = $eyeem_OAuth_data->access_token;
	
	$file = 'config.php';
	//Writing token to Eyeem2koken.php file
	if(file_exists($file)) {
		$content = file($file); //Read the file into an array. Line number => line content
		foreach($content as $lineNumber => &$lineContent) { //Loop through the array (the "lines")
		  if (preg_match('/eyeem_token = "(.*)"/',  $lineContent, $matches)) {
			  $lineContent = preg_replace('/'.$matches[1].'/', $token, $lineContent) ;
			  echo ('<p>'.$file .' writen with your token :'. $token.'</p>');
			  echo "<p>Now complete the informations that are missing in the setup.php file (your koken url and token)</p>";
		  }	
		}
		
		$allContent = implode("", $content); //Put the array back into one string
		file_put_contents($file, $allContent); //Overwrite the file with the new content	
	}

}



       
?>
