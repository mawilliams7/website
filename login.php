<?php
session_start();
if($_SERVER['REQUEST_METHOD'] == 'POST'){
  if($_POST['username'] == ""){
    echo("Usernames must be of length 1 or greater.");
  }
  else{
    // Sets username for entire session and goes to music.php
    $_SESSION['username'] = htmlentities($_POST['username']);
    header('location: music.php');
  }
}
?>
<form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
Username : <input type="text" name="username">
<input type="submit" />
</form>
