<?php

//MySQL authentication details
    $username="USERNAME";
	$password = "PASSWORD";
    $database="DATABASE";

    mysql_connect(localhost,$username,$password);
    $db_selected = mysql_select_db($database);
    if ( !$db_selected ) {
		die( "Unable to select database");
    }

	$query = "SELECT * FROM `followers`";
	$result = mysql_query($query);

	$oldFollowers = array();
	
	//read the previously stored followers from the database and store in an array
	while ($row = mysql_fetch_object($result)) {
		$oldFollowers[$row->id] = array('screen_name'=>$row->screen_name, 'username'=>$row->username);
	}

	//replace TWITTERID with the required Twitter user ID
	$followerURL = "http://api.twitter.com/1/followers/ids/TWITTERID.json";

	//get the current follower details using curl
	$curl;
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 2);
	curl_setopt($curl, CURLOPT_HEADER, false);
	curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	//curl_setopt($curl, CURLOPT_USERPWD, "$username:$password");
	curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
	curl_setopt($curl, CURLOPT_URL, $followerURL);
	 
	$result = curl_exec($curl);

	$followers = json_decode($result); 
	
	//create new array to store current follower list
	$newFollowers = array();
	$userURL = "http://api.twitter.com/1/users/show/";

	foreach ($followers as $follower) {
		//if this is a new follower, retrieve username details with another curl request and store in $newFollowers
		if (is_null($oldFollowers[$follower])) {
			curl_setopt($curl, CURLOPT_URL, $userURL.$follower.".json");
			$result = curl_exec($curl);
			$followerDetails = json_decode($result);
			$newFollowers[$follower] = array('screen_name'=>$followerDetails->screen_name, 'username'=>$followerDetails->name);
		} else {
		//if we already know about this follower, just add to $newFollowers
			$newFollowers[$follower] = array('screen_name'=>$oldFollowers[$follower]['screen_name'], 'username'=>$oldFollowers[$follower]['username']);
		}
	}
	
	foreach (array_keys($oldFollowers) as $oldFollower) {
		//if old follower is no longer following, display message
		if (is_null($newFollowers[$oldFollower])) { 
			echo $oldFollowers[$oldFollower]['screen_name']." (".$oldFollowers[$oldFollower]['username'].") is no longer following you.\n";
		}
	}
	
	//clear the old table
	$query = "TRUNCATE `followers`";
	$result = mysql_query($query);

	//build new table
	foreach (array_keys($newFollowers) as $newFollower) {
		$query = "INSERT INTO `followers` VALUES ('".$newFollower."', '".$newFollowers[$newFollower]['username']."', '".$newFollowers[$newFollower]['screen_name']."')";
		$result = mysql_query($query);
	}
	
	curl_close($curl);
	mysql_close();	
?>