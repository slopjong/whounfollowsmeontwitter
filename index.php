<?php

// Tidy PHP (in sublime) uses the WordPress coding standard
// https://codex.wordpress.org/WordPress_Coding_Standards

/*
	This script checks who's unfollowing a twitter user by its screen name.

	Limitation:
		* Works with a maximum of 5000 followers
		* Twitter allows 150 requests per hour
*/

if(!file_exists("config.php"))
{
	echo "The config file doesn't exist.";
	exit();
}

include_once("config.php");

if(!user_exists($screen_name))
{	
	$id_nick_map = get_followers($screen_name);
	$json = json_encode($id_nick_map);
	file_put_contents("$screen_name.json", $json);
}
else
{
	// the new map
	$id_nick_map_new = get_followers($screen_name);
	
	// the old map
	$json = file_get_contents("$screen_name.json");
	$id_nick_map_old = json_decode($json);
	
	// check who has unfollowed
	foreach($id_nick_map_old as $user_id => $user_name)
	{
		if(!property_exists($id_nick_map_new, $user_id))
		{
			echo "$user_name has unfollowed you.\n";

            mail($to_mail, "Someone has unfollowed you!", "$user_name has unfollowed you.",
                "FROM: Twitterquitter <". $from_mail .">\r\n".
                "Reply-To: ". $from_mail ."\r\n".
                "Message-ID: <" . time() . ".". $from_mail .">\r\n".
                "X-Mailer: Twitterquitter\r\n",
                "-f". $from_mail
            );
		}
	}

	// write the new map to the json file
	$json = json_encode($id_nick_map_new);
	file_put_contents("$screen_name.json", $json);
}



/*******************************************************************************************************************
 * Helpers
 */


function user_exists($user)
{
	return file_exists("$user.json");
}

/**
 * Returns an object with twitter user IDs as the members and the screen names as their values.
 *
 */
function get_followers($screen_name)
{
	$ch = curl_init();
	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );

	// get the IDs
	curl_setopt( $ch, CURLOPT_URL, "https://api.twitter.com/1/followers/ids.json?cursor=-1&screen_name=$screen_name" );
	$data = json_decode( curl_exec_follow( $ch ) );
	exit_on_error($data);
	
	$ids = array();
	if($data !== null && property_exists($data, "ids"))
		$ids = implode(",",$data->ids);

	// get the screen names
	curl_setopt( $ch, CURLOPT_URL, "https://api.twitter.com/1/users/lookup.json?user_id=$ids,twitter&include_entities=false" );
	$data = json_decode( curl_exec_follow( $ch ) );
	exit_on_error($data);

	// populate a map with IDs and screen names
	$id_nick_map = new stdClass;	
	if($data !== null && is_array($data))
	{
		foreach($data as $index => $obj)
		{
			$id = $obj->id;
			$id_nick_map->$id = $obj->screen_name;	
		}
	}

	curl_close( $ch );
	
	return $id_nick_map;
}

/**
 * Checks if the returned data from twitter contains an error message. If an
 * error message is present, the script will exit.
 */
function exit_on_error($data)
{
	if(is_object($data) && property_exists($data, "error"))
	{
		echo $data->error . "\n";
		exit();
	}
}

function curl_exec_follow( $ch, &$maxredirect = null ) {
	// we emulate a browser here since some websites detect
	// us as a bot and don't let us do our job
	$user_agent = "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.7.5)".
		" Gecko/20041107 Firefox/1.0";
	curl_setopt( $ch, CURLOPT_USERAGENT, $user_agent );


	$mr = $maxredirect === null ? 5 : intval( $maxredirect );

	if ( ini_get( 'open_basedir' ) == '' && ini_get( 'safe_mode' == 'Off' ) ) {
		curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, $mr > 0 );
		curl_setopt( $ch, CURLOPT_MAXREDIRS, $mr );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );

	}
	else {
		curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, false );

		if ( $mr > 0 ) {
			$original_url = curl_getinfo( $ch, CURLINFO_EFFECTIVE_URL );
			$newurl = $original_url;

			$rch = curl_copy_handle( $ch );

			curl_setopt( $rch, CURLOPT_HEADER, true );
			curl_setopt( $rch, CURLOPT_NOBODY, true );
			curl_setopt( $rch, CURLOPT_FORBID_REUSE, false );

			do {
				curl_setopt( $rch, CURLOPT_URL, $newurl );
				$header = curl_exec( $rch );

				if ( curl_errno( $rch ) ) {
					$code = 0;
				}
				else {
					$code = curl_getinfo( $rch, CURLINFO_HTTP_CODE );
					if ( $code == 301 || $code == 302 ) {
						preg_match( '/Location:(.*?)\n/', $header, $matches );
						$newurl = trim( array_pop( $matches ) );

						// if no scheme is present then the new url is a
						// relative path and thus needs some extra care
						if ( !preg_match( "/^https?:/i", $newurl ) )
							$newurl = $original_url . $newurl;

					}
					else
						$code = 0;
				}
			} while ( $code && --$mr );

			curl_close( $rch );

			if ( !$mr ) {
				if ( $maxredirect === null )
					trigger_error( 'Too many redirects.', E_USER_WARNING );
				else
					$maxredirect = 0;

				return false;
			}
			curl_setopt( $ch, CURLOPT_URL, $newurl );
		}
	}
	return curl_exec( $ch );
}

?>
