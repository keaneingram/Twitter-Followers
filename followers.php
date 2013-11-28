<?php
	// followers.php version 1.1
	// this has been rewritten to take into account the changes required by version 1.1 of the
	// Twitter API. This requires use of OAuth, for which I use the twitteroauth library.
	// Despite the extra authentication, the new Twitter API makes this script more straightforward
	// as the GET followers/list service now provides everything we need.
	
	session_start();
	require_once("twitteroauth-master/twitteroauth/twitteroauth.php"); //Path to twitteroauth library
	// twitteroauth can be found at https://github.com/abraham/twitteroauth

	// connect to the MySQL database and retrieve the existing followers
    $username="USERNAME";
	$password = "PASSWORD";
    $database="DATABASE";

    mysql_connect('localhost',$username,$password);
    $db_selected = mysql_select_db($database);
    if ( !$db_selected ) {
		die( "Unable to select database");
    }

	$query = "SELECT * FROM `followers`";
	$result = mysql_query($query);

	// create the $oldFollowers array to store the existing followers
	// it uses the Twitter id, screen name and name like so:
	// $oldFollowers[id] = array('screen_name'=>screen name, 'username'=>name)
	$oldFollowers = array();
	
	while ($row = mysql_fetch_object($result)) {
		$oldFollowers[$row->id] = array('screen_name'=>$row->screen_name, 'username'=>$row->username);
	}

	// now, set up the details needed to connect using the twitterouth library
	// these details will be on your application page at https://dev.twitter.com/apps
	// (you'll need to create an app if you have not already done this)
	$consumerkey = "CONSUMERKEY";
	$consumersecret = "CONSUMERSECRET";
	$accesstoken = "ACCESSTOKEN";
	$accesstokensecret = "ACCESSTOKENSECRET";
	 
	function getConnectionWithAccessToken($cons_key, $cons_secret, $oauth_token, $oauth_token_secret) {
	  $connection = new TwitterOAuth($cons_key, $cons_secret, $oauth_token, $oauth_token_secret);
	  return $connection;
	}
	
	// make the connection
	$connection = getConnectionWithAccessToken($consumerkey, $consumersecret, $accesstoken, $accesstokensecret);
	
	// retrieve the list of followers - if you have more than 100 followers, adjust the count number accordingly
	// the maximum figure for count is 200 - if you have more than 200 followers, you'll need to use cursors
	// to download multiple pages of results - this is beyond the scope of this script, but information
	// can be found at https://dev.twitter.com/docs/misc/cursoring
	$result = $connection->get("https://api.twitter.com/1.1/followers/list.json?screen_name=keanei&count=100");

	// create an array to store the retrieved followers
	$newFollowers = array();
	
	foreach ($result->users as $follower) {
		$id = $follower->id;
		$name = mysql_real_escape_string($follower->name);
		$screen_name = mysql_real_escape_string($follower->screen_name);
		$newFollowers[$id] = array('screen_name'=>$screen_name, 'username'=>$name);
	}
	
	// one by one, check the existing followers array members to see if they exist in the retrieved followers array
	// if they do not, then that user is no longer following you, so output a message
	foreach (array_keys($oldFollowers) as $oldFollower) {
		if (is_null($newFollowers[$oldFollower])) { //if old follower is no longer following
			echo $oldFollowers[$oldFollower]['screen_name']." (".$oldFollowers[$oldFollower]['username'].") is no longer following you.\n";
		}
	}

	//  clear the old table
	$query = "TRUNCATE `followers`";
	$result = mysql_query($query);

	//  build the new table using the retrieved followers
	foreach (array_keys($newFollowers) as $newFollower) {
		$query = "INSERT INTO `followers` VALUES ('".$newFollower."', '".$newFollowers[$newFollower]['username']."', '".$newFollowers[$newFollower]['screen_name']."')";
		$result = mysql_query($query);
	}	
	
	mysql_close();
?>