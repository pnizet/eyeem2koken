<?
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
}    

?>
