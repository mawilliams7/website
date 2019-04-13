<?php
session_start();
// Checks if same user is active
$user = $_SESSION['username'];
$_SESSION['new_user'] = FALSE;

//Checks if a new user has logged in
if($_SESSION['user'] != $user){
	$_SESSION['new_user'] = TRUE;
	$_SESSION['user'] = $user;
}
// Checks if user is known
$json = file_get_contents("users.json");
$jsonIterator = new RecursiveIteratorIterator(
	new RecursiveArrayIterator(json_decode($json, TRUE)),
	RecursiveIteratorIterator::SELF_FIRST);

$known = False;
$users = array();
$playlists = array();
$user_playlists = array();

// Maps users to their playlists
foreach ($jsonIterator as $key => $val){
	if(is_array($val) or $val == NULL){
		array_push($users, $key);
		$vals = array();
		if($val){
			for($i = 1; $i < sizeof($val)+1; $i++){
				$vals["playlist$i"] = $val["playlist$i"];
			}
		}
		$playlists[$key] = $vals;
		if($key == $user){
			$known = True;
			for($i = 1; $i < sizeof($vals)+1; $i++){
				array_push($user_playlists, $vals["playlist$i"]);
			}
		}
	}
}

//Sets the playlist for the current user depending on their choice.
// If a new user has logged on, no playlist is chosen
if($_SESSION['new_user']){
	$_SESSION['playlist'] = "";
}
else{
	if($_POST['chosen_playlist']){
		$_SESSION['playlist'] = $_POST['chosen_playlist'];
	}
}

//If the user is not known, this creates a directory and library for the user
if(!$known){
	array_push($users, $user);
	array_push($playlists, array($user => array()));
	mkdir($user);
	$entries = array();
	for($i = 0; $i<sizeof($users); $i++){
		$entries[$users[$i]] = $playlists[$users[$i]]; 
	}
	$f_out = fopen("users.json", 'w');
	fwrite($f_out, json_encode($entries));
	$f_out = fopen("$user/library.json", 'w');
	fwrite($f_out, json_encode(array()));
}

$all_keys = array();
$all_songs = array();
$all_artists = array();
$all_urls = array();

// Opens and collects info from zTunes library
$json = file_get_contents("zTunes.json");
$jsonIterator = new RecursiveIteratorIterator(
	new RecursiveArrayIterator(json_decode($json, TRUE)),
	RecursiveIteratorIterator::SELF_FIRST);
$keys_ztunes = array();
$songs_ztunes = array();
$artists_ztunes = array();
$urls_ztunes = array();
foreach ($jsonIterator as $key => $val) {
	if(is_array($val)){
		array_push($keys_ztunes, $key);
		array_push($all_keys, $key);
	}
	else {
		if($key == "artist"){
			array_push($artists_ztunes, $val);
			array_push($all_artists, $val);
		}
		if($key == "url"){
			array_push($urls_ztunes, $val);
			array_push($all_urls, $val);
		}
		if($key == "song"){
			array_push($songs_ztunes, $val);
			array_push($all_songs, $val);
		}
	}
}

// Opens and collects info from Zmazon library
$json = file_get_contents("Zmazon.json");
$jsonIterator = new RecursiveIteratorIterator(
	new RecursiveArrayIterator(json_decode($json, TRUE)),
	RecursiveIteratorIterator::SELF_FIRST);
$keys_zmazon = array();
$songs_zmazon = array();
$artists_zmazon = array();
$urls_zmazon = array();
foreach ($jsonIterator as $key => $val) {
	if(is_array($val)){
		array_push($keys_zmazon, $key);
		array_push($all_keys, $key);
	}
	else {
		if($key == "artist"){
			array_push($artists_zmazon, $val);
			array_push($all_artists, $val);
		}
		if($key == "url"){
			array_push($urls_zmazon, $val);
			array_push($all_urls, $val);
		}
		if($key == "song"){
			array_push($songs_zmazon, $val);
			array_push($all_songs, $val);
		}
	}
}

//Opens and collect info from user library
$json = file_get_contents("$user/library.json");
$jsonIterator = new RecursiveIteratorIterator(
	new RecursiveArrayIterator(json_decode($json, TRUE)),
	RecursiveIteratorIterator::SELF_FIRST);
$keys_music = array();
$songs_music = array();
$artists_music = array();
$urls_music = array();
$comments_music = array();
foreach ($jsonIterator as $key => $val) {
	if(is_array($val)){
		array_push($keys_music, $key);
	}
	else {
		if($key == "artist"){
			array_push($artists_music, $val);
		}
		if($key == "url"){
			array_push($urls_music, $val);
		}
		if($key == "song"){
			array_push($songs_music, $val);
		}
		if($key == "comment"){
			array_push($comments_music, $val);
		}
	}
}

// Opens and collects info from user's playlist, if one exists
$keys_playlist = array();
$songs_playlist = array();
$artists_playlist = array();
$urls_playlist = array();
if($_SESSION['playlist']){
	$playlist_information = load_playlist($_SESSION['playlist']);
	$keys_playlist = $playlist_information[0];
	$songs_playlist = $playlist_information[1];
	$artists_playlist = $playlist_information[2];
	$urls_playlist = $playlist_information[3];
}

// Function that is used to load playlist
function load_playlist($playlist){
	global $user;
	$keys_playlist = array();
	$songs_playlist = array();
	$artists_playlist = array();
	$urls_playlist = array();
	$json = file_get_contents("$user/$playlist.json");
	$jsonIterator = new RecursiveIteratorIterator(
		new RecursiveArrayIterator(json_decode($json, TRUE)),
		RecursiveIteratorIterator::SELF_FIRST);
	foreach ($jsonIterator as $key => $val) {
		if(is_array($val)){
			array_push($keys_playlist, $key);
		}
		else {
			if($key == "artist"){
				array_push($artists_playlist, $val);
			}
			if($key == "url"){
				array_push($urls_playlist, $val);
			}
			if($key == "song"){
				array_push($songs_playlist, $val);
			}
			if($key == "comment"){
				array_push($comments_playlist, $val);
			}
		}
	}
	return array($keys_playlist, $songs_playlist, $artists_playlist, $urls_playlist);
}

// Beginning of web page
echo("<html>");
echo("Hello $user!");
echo("<body>");
echo("<header><h1>zTunes Store</h1></header>");
//zTunes Display
echo("<h3>Song - Artist</h3>");
echo('<form method="post">');
for($i = 0; $i<sizeof($keys_ztunes); $i++){
	$song = $songs_ztunes[$i];
	$artist = $artists_ztunes[$i];
	$key = $keys_ztunes[$i];
	$url = $urls_ztunes[$i];
	echo ("<br> $song - $artist <input type = 'submit' value = 'Buy' name = $key></input></br>");
}

echo("<header><h1>Zmazon Store</h1></header>");
//Zmazon Display
echo("<h3>Song - Artist</h3>");
for($i = 0; $i<sizeof($keys_zmazon); $i++){
	$song = $songs_zmazon[$i];
	$artist = $artists_zmazon[$i];
	$key = $keys_zmazon[$i];
	$url = $urls_zmazon[$i];
	echo ("<br> $song - $artist <input type = 'submit' value = 'Buy' name = $key></input></br>");
}
echo("</form>");

// Checks what user wants to do
for($i = 0; $i<sizeof($all_keys); $i++){
	$song = $all_songs[$i];
	$artist = $all_artists[$i];
	$key = $all_keys[$i];
	$url = $all_urls[$i];
	if($_POST[$key] == "Buy"){
		add_to_music($key, $song, $artist, $url);
		break;
	}
	if($_POST[$key] == "Remove"){
		remove_music($key);
		break;
	}
	if($_POST[$key] == "Listen"){
		listen_music($url);
		break;
	}
	if($_POST[$key] == "Add to playlist"){
		// Checks if user actually has chosen a playlist before adding song to said playlist
		if($_SESSION['playlist']){
			add_to_playlist($key, $song, $artist, $url);
			break;
		}
	}
	if($_POST[$key] == "Remove from playlist"){
		remove_from_playlist($_SESSION['playlist'], $key);
		break;
	}
}

//Function that transfer music from one of the stores to the user's library
function add_to_music($id, $song, $artist, $url){
	global $user;
	global $keys_music;
	global $songs_music;
	global $artists_music;
	global $urls_music;
	global $comments_music;
	$add = True;
	foreach($keys_music as $key){
		if($key == $id){
			$add = False;
			break;
		}
	}
	if($add){
		$entries = array();
		for($i = 0; $i<sizeof($keys_music)+1; $i++){
			if($i == sizeof($keys_music)){
				$values = array('song' => $song, 'artist' => $artist, 'url' => $url, 'comment' => "");
				$entries[$id] = $values;
				break;
			}
			$key = $keys_music[$i];
			$values = array('song' => $songs_music[$i], 'artist' => $artists_music[$i], 'url' => $urls_music[$i], 'comment' => $comments_music[$i]);
			$entries[$key] = $values; 
		}
		$f_out = fopen("$user/library.json", 'w');
		fwrite($f_out, json_encode($entries));
		array_push($keys_music, $id);
		array_push($artists_music, $artist);
		array_push($urls_music, $url);
		array_push($songs_music, $song);
		array_push($comments_music, "");
	}
}
//Function that adds music from user library to current playlist
function add_to_playlist($id, $song, $artist, $url){
	global $user;
	global $keys_playlist;
	global $songs_playlist;
	global $artists_playlist;
	global $urls_playlist;
	$add = True;
	// Checks if current song is already in playlist
	foreach($keys_playlist as $key){
		if($key == $id){
			$add = False;
			break;
		}
	}
	if($add){
		$entries = array();
		for($i = 0; $i<sizeof($keys_playlist)+1; $i++){
			if($i == sizeof($keys_playlist)){
				$values = array('song' => $song, 'artist' => $artist, 'url' => $url);
				$entries[$id] = $values; 
				break;
			}
			$key = $keys_playlist[$i];
			$values = array('song' => $songs_playlist[$i], 'artist' => $artists_playlist[$i], 'url' => $urls_playlist[$i]);
			$entries[$key] = $values; 
		}
		$temp = $_SESSION['playlist'];
		$f_out = fopen("$user/$temp.json", 'w');
		fwrite($f_out, json_encode($entries));
		array_push($keys_playlist, $id);
		array_push($artists_playlist, $artist);
		array_push($urls_playlist, $url);
		array_push($songs_playlist, $song);
	}
}
// Removes music from user library and ALL playlists
function remove_music($removee){
	global $user;
	global $keys_music;
	global $songs_music;
	global $artists_music;
	global $urls_music;
	global $comments_music;
	global $user_playlists;
	$removee_index = 0;
	$entries = array();
	for($i = 0; $i<sizeof($keys_music); $i++){
		$key = $keys_music[$i];
		if($key == $removee){
			$removee_index = $i;
			continue;
		}
		$values = array('song' => $songs_music[$i], 'artist' => $artists_music[$i], 'url' => $urls_music[$i], 'comment' => $comments_music[$i]);
		$entries[$key] = $values; 
	}
	$f_out = fopen("$user/library.json", 'w');
	fwrite($f_out, json_encode($entries));
	if(sizeof($keys_music) == 1){
		$keys_music = array();
	}
	else{
		\array_splice($keys_music, $removee_index, 1);
		\array_splice($songs_music, $removee_index, 1);
		\array_splice($artists_music, $removee_index, 1);
		\array_splice($urls_music, $removee_index, 1);
		\array_splice($comments_music, $removee_index, 1);
	}
	// Removes song from each of the user's playlists
	foreach($user_playlists as $playlist){
		remove_from_playlist($playlist, $removee);
	}
}
// Removes music from a user playlist
function remove_from_playlist($playlist, $removee){
	global $user;
	$information = load_playlist($playlist);
	$keys_playlist = $information[0];
	$songs_playlist = $information[1];
	$artists_playlist = $information[2];
	$urls_playlist = $information[3];
	// Checks if the song needs to be removed from the playlist
	if(in_array($removee, $keys_playlist)){
		$removee_index = 0;
		$entries = array();
		for($i = 0; $i<sizeof($keys_playlist); $i++){
			$key = $keys_playlist[$i];
			if($key == $removee){
				$removee_index = $i;
				continue;
			}
			$values = array('song' => $songs_playlist[$i], 'artist' => $artists_playlist[$i], 'url' => $urls_playlist[$i]);
			$entries[$key] = $values; 
		}
		$f_out = fopen("$user/$playlist.json", 'w');
		fwrite($f_out, json_encode($entries));
		//Checks if the edited playlist is the current playlist the user is viewing
		if($_SESSION['playlist'] == $playlist){
			global $keys_playlist;
			global $songs_playlist;
			global $artists_playlist;
			global $urls_playlist;
			\array_splice($keys_playlist, $removee_index, 1);
			\array_splice($songs_playlist, $removee_index, 1);
			\array_splice($artists_playlist, $removee_index, 1);
			\array_splice($urls_playlist, $removee_index, 1);
		}
	}
}
// Opnes music video in another window
function listen_music($url){
	//Cleans url
	$url = str_replace('\\', '', $url);
	echo("<script>window.open('$url');</script>");
}

//Checks if a user requests to add annotations
if(array_key_exists("add_annotations", $_POST)){
	for($i = 0; $i<sizeof($keys_music); $i++){
		$key = $keys_music[$i];
		if(array_key_exists($key . '-comment', $_POST)){
			// Cleans comment input
			add_comment(htmlentities($_POST[$key . '-comment']), $i);
		}
	}
}
// Adds comments to a song
function add_comment($comment, $index){
	if($comment == ""){
		return;
	}
	// Adds comment to user library
	global $user;
	global $keys_music;
	global $songs_music;
	global $artists_music;
	global $urls_music;
	global $comments_music;
	$comments_music[$index] = $comment;
	$entries = array();
	for($i = 0; $i<sizeof($keys_music); $i++){
		$key = $keys_music[$i];
		$values = array('song' => $songs_music[$i], 'artist' => $artists_music[$i], 'url' => $urls_music[$i], 'comment' => $comments_music[$i]);
		$entries[$key] = $values; 
	}
	$f_out = fopen("$user/library.json", 'w');
	fwrite($f_out, json_encode($entries));
}
// Checks if a user is requesting to add a playlist
if(array_key_exists("add_playlist", $_POST)){
	if(array_key_exists("playlist", $_POST)){
		add_playlist(htmlentities($_POST["playlist"]));
	}
}
// Checks if a user is requesting to rename a playlist
if(array_key_exists("rename_playlist", $_POST)){
	if(array_key_exists("playlist_name", $_POST)){
		rename_playlist(htmlentities($_POST["playlist_name"]));
	}
}

// Adds playlist to user's profile
function add_playlist($playlist){
	global $user_playlists;
	global $user;
	global $users;
	global $playlists;
	if($playlist == ""){
		return;
	}
	if($playlist == "library"){
		return;
	}
	if(in_array($playlist, $user_playlists)){
		echo("<i>Playlist with that name already exists</i>");
		return;
	}
	// Cleans user input
	$playlist = htmlentities($playlist);
	array_push($user_playlists, $playlist);
	$playlist_number = sizeof($user_playlists);
	$playlists[$user]["playlist$playlist_number"] = $playlist;
	$f_out = fopen("users.json", 'w');
	fwrite($f_out, json_encode($playlists));
	$f_out = fopen("$user/$playlist.json", 'w');
	fwrite($f_out, json_encode(array()));
}

// Renames a playlist in the users profile;
function rename_playlist($playlist){
	global $user;
	global $users;
	global $playlists;
	global $user_playlists;
	if($playlist == ""){
		return;
	}
	if($playlist == "library"){
		return;
	}
	if(in_array($playlist, $user_playlists)){
		echo("<i>Playlist with that name already exists</i>");
		return;
	}
	// Cleans user input
	$playlist = htmlentities($playlist);
	$index = array_search($_SESSION['playlist'], $user_playlists) + 1;
	$playlists[$user]["playlist$index"] = $playlist;
	$f_out = fopen("users.json", 'w');
	fwrite($f_out, json_encode($playlists));
	$temp = $_SESSION['playlist'];
	rename("$user/$temp.json", "$user/$playlist.json");
	$_SESSION['playlist'] = $playlist;
}

// Checks if user wants to remove all song comments
if(array_key_exists("remove_annotations", $_POST)){
	remove_comments();
}

//Removes annotations from all songs
function remove_comments(){
	global $user;
	global $keys_music;
	global $songs_music;
	global $artists_music;
	global $urls_music;
	global $comments_music;
	$entries = array();
	for($i = 0; $i<sizeof($keys_music); $i++){
		$key = $keys_music[$i];
		$values = array('song' => $songs_music[$i], 'artist' => $artists_music[$i], 'url' => $urls_music[$i], 'comment' => "");
		$entries[$key] = $values; 
		$comments_music[$i] = "";
	}
	$f_out = fopen("$user/library.json", 'w');
	fwrite($f_out, json_encode($entries));
}

//Shows user their music
echo("<header><h1>Your Library $user</h1></header>");
echo("<form method='post'>");
if($keys_music){
	echo("<h3>Song - Artist</h3>");
	for($i = 0; $i<sizeof($keys_music); $i++){
		$song = $songs_music[$i];
		$artist = $artists_music[$i];
		$key = $keys_music[$i];
		$url = $urls_music[$i];
		$comment = $comments_music[$i];
		if($comment == ""){
			echo ("<br> $song - $artist <input type = 'submit' value = 'Remove' name = $key></input> <input type = 'submit' value = 'Listen' name = $key></input> <input type = 'submit' value = 'Add to playlist' name = $key></input> Annotation:<input type = 'text' value = '' name = $key-comment></input></br>");
		}
		else{
			echo ("<br> $song - $artist ($comment) <input type = 'submit' value = 'Remove' name = $key></input> <input type = 'submit' value = 'Listen' name = $key></input> <input type = 'submit' value = 'Add to playlist' name = $key></input> Annotation:<input type = 'text' value = '' name = $key-comment></input></br>");
		}
	}
	echo("<br><input type = 'submit' value = 'Add annotations' name = 'add_annotations'></input>");
	echo("<br><br><input type = 'submit' value = 'Remove all annotations' name = 'remove_annotations'></input></br>");
	echo("<br><br>Playlist name:<input type = 'text' value = '' name = playlist></input> <input type = 'submit' value = 'Add playlist' name = 'add_playlist'></input>");
	echo("</form>");
	// Gives user the option to choose what playlist they want to see/edit
	if($user_playlists){
		echo("<form method='post'>");
		echo("<br><select name = 'chosen_playlist'>");
		for($i = 1; $i < sizeof($playlists[$user]) + 1; $i++){
			$playlist = $playlists[$user]["playlist$i"];
			echo("<option value='$playlist'>$playlist</option>");
		}
		echo("</select>");
		echo("  <input type='submit' value='Change playlist'/>");
		echo("</form>");
	}
	echo("<form method='post'>");
	// Shows user playlist information
	if($_SESSION['playlist']){
		$temp = $_SESSION['playlist'];
		echo("<br><br>Playlist: $temp <br> New playlist name: <input type = 'text' value = '' name = playlist_name> <input type = 'submit' value = 'Rename playlist' name = rename_playlist></br>");
		if($keys_playlist){
			for($i = 0; $i<sizeof($keys_playlist); $i++){
				$song = $songs_playlist[$i];
				$artist = $artists_playlist[$i];
				$key = $keys_playlist[$i];
				$url = $urls_playlist[$i];
				echo ("<br> $song - $artist <input type = 'submit' value = 'Remove from playlist' name = $key></input> <input type = 'submit' value = 'Listen' name = $key></input></br>");
			}
		}
		else{
			echo("<br> No songs to show in the playlist.");
		}
	}
}
else{
	echo("No songs to show.");
}
// End of web page
echo("</form>");
echo("</body>");
echo("</html>");
?>
