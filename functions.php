<?
///////////////////////////////////////////////////////////////////////////////////////////////
//
//                         FUNCTIONS
//
///////////////////////////////////////////////////////////////////////////////////////////////

/*****************************************************************************/
// Replace tag numbers (string like [a:46548] with the name of it
function replace_tag_number_by_text($string,$prefix)
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
		$string = preg_replace($corres[$i][0],'#'.$corres[$i][1],preg_replace('/[\[\]]/','',preg_replace('/[\[\]]/','',$string)));
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
		die('error occured during curl exec. Additional info: ' . var_export($info));
	}
	curl_close($curl);
	$decoded = json_decode($curl_response);
	if (isset($decoded->response->status) && $decoded->response->status == 'ERROR') {
		die('Curl error occured: ' . $decoded->response->errormessage);
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




/*****************************************************************************/
// IPTC EASY
    /************************************************************\
   
        IPTC EASY 1.0 - IPTC data manipulator for JPEG images
           
        All reserved www.image-host-script.com
       
        Sep 15, 2008
   
    \************************************************************/

    DEFINE('IPTC_OBJECT_NAME', '005');
    DEFINE('IPTC_EDIT_STATUS', '007');
    DEFINE('IPTC_PRIORITY', '010');
    DEFINE('IPTC_CATEGORY', '015');
    DEFINE('IPTC_SUPPLEMENTAL_CATEGORY', '020');
    DEFINE('IPTC_FIXTURE_IDENTIFIER', '022');
    DEFINE('IPTC_KEYWORDS', '025');
    DEFINE('IPTC_RELEASE_DATE', '030');
    DEFINE('IPTC_RELEASE_TIME', '035');
    DEFINE('IPTC_SPECIAL_INSTRUCTIONS', '040');
    DEFINE('IPTC_REFERENCE_SERVICE', '045');
    DEFINE('IPTC_REFERENCE_DATE', '047');
    DEFINE('IPTC_REFERENCE_NUMBER', '050');
    DEFINE('IPTC_CREATED_DATE', '055');
    DEFINE('IPTC_CREATED_TIME', '060');
    DEFINE('IPTC_ORIGINATING_PROGRAM', '065');
    DEFINE('IPTC_PROGRAM_VERSION', '070');
    DEFINE('IPTC_OBJECT_CYCLE', '075');
    DEFINE('IPTC_BYLINE', '080');
    DEFINE('IPTC_BYLINE_TITLE', '085');
    DEFINE('IPTC_CITY', '090');
    DEFINE('IPTC_PROVINCE_STATE', '095');
    DEFINE('IPTC_COUNTRY_CODE', '100');
    DEFINE('IPTC_COUNTRY', '101');
    DEFINE('IPTC_ORIGINAL_TRANSMISSION_REFERENCE',     '103');
    DEFINE('IPTC_HEADLINE', '105');
    DEFINE('IPTC_CREDIT', '110');
    DEFINE('IPTC_SOURCE', '115');
    DEFINE('IPTC_COPYRIGHT_STRING', '116');
    DEFINE('IPTC_CAPTION', '120');
    DEFINE('IPTC_LOCAL_CAPTION', '121');

    class iptc {
        var $meta=Array();
        var $hasmeta=false;
        var $file=false;
       
       
        function iptc($filename) {
            $size = getimagesize($filename,$info);
            $this->hasmeta = isset($info["APP13"]);
            if($this->hasmeta)
                $this->meta = iptcparse ($info["APP13"]);
            $this->file = $filename;
        }
        function set($tag, $data) {
            $this->meta ["2#$tag"]= Array( $data );
            $this->hasmeta=true;
        }
        function get($tag) {
            return isset($this->meta["2#$tag"]) ? $this->meta["2#$tag"][0] : false;
        }
       
        function dump() {
            print_r($this->meta);
        }
        function binary() {
            $iptc_new = '';
            foreach (array_keys($this->meta) as $s) {
                $tag = str_replace("2#", "", $s);
                $iptc_new .= $this->iptc_maketag(2, $tag, $this->meta[$s][0]);
            }       
            return $iptc_new;   
        }
        function iptc_maketag($rec,$dat,$val) {
            $len = strlen($val);
            if ($len < 0x8000) {
                   return chr(0x1c).chr($rec).chr($dat).
                   chr($len >> 8).
                   chr($len & 0xff).
                   $val;
            } else {
                   return chr(0x1c).chr($rec).chr($dat).
                   chr(0x80).chr(0x04).
                   chr(($len >> 24) & 0xff).
                   chr(($len >> 16) & 0xff).
                   chr(($len >> 8 ) & 0xff).
                   chr(($len ) & 0xff).
                   $val;
                  
            }
        }   
        function write() {
            if(!function_exists('iptcembed')) return false;
            $mode = 0;
            $content = iptcembed($this->binary(), $this->file, $mode);   
            $filename = $this->file;
               
            @unlink($filename); #delete if exists
           
            $fp = fopen($filename, "w");
            fwrite($fp, $content);
            fclose($fp);
        }   
       
        #requires GD library installed
        function removeAllTags() {
            $this->hasmeta=false;
            $this->meta=Array();
            $img = imagecreatefromstring(implode(file($this->file)));
            @unlink($this->file); #delete if exists
            imagejpeg($img,$this->file,100);
        }
    };
      
  
/*****************************************************************************/
// nonce generation
/* 
Copyright 2009 Full Throttle Development, LLC

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/
		
define( 'FT_NONCE_UNIQUE_KEY' , 'kBvfm7V89k3Sx0dKAU69sPGHj0IWp633' );
define( 'FT_NONCE_DURATION' , 300 ); // 300 makes link or form good for 5 minutes from time of generation
define( 'FT_NONCE_KEY' , '' );

// This method creates a key / value pair for a url string
function ft_nonce_create_query_string( $action = '' , $user = '' ){
	return FT_NONCE_KEY."".ft_nonce_create( $action , $user );
}

// This method creates an nonce for a form field
function ft_nonce_create_form_input( $action = '' , $user='' ){
	echo "<input type='hidden' name='".FT_NONCE_KEY."' value='".ft_nonce_create( $action . $user )."' />";
}

// This method creates an nonce. It should be called by one of the previous two functions.
function ft_nonce_create( $action = '' , $user='' ){
	return substr( ft_nonce_generate_hash( $action . $user ), -12, 10);
}

// This method validates an nonce
function ft_nonce_is_valid( $nonce , $action = '' , $user='' ){
	// Nonce generated 0-12 hours ago
	if ( substr(ft_nonce_generate_hash( $action . $user ), -12, 10) == $nonce ){
		return true;
	}
	return false;
}

?>
