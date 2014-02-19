<?php
ini_set('display_errors', 'On');
error_reporting(E_ALL | E_STRICT);

$server="aasv9tbhx6ftj7.cxeyoa7zavg2.us-east-1.rds.amazonaws.com"; // Host name 
$port="3306";
$user="root"; // Mysql username 
$pass=""; // Mysql password 
$database="LCS"; // Database name 

// Connect to server and select databse.
connect($server, $port, $database, $user, $pass);

// username and password sent from form 
$myusername=$_POST['myusername']; 
$mypassword=$_POST['mypassword']; 

// To protect MySQL injection (more detail about MySQL injection)
$myusername = stripslashes($myusername);
$mypassword = stripslashes($mypassword);
$myusername = mysql_real_escape_string($myusername);
$mypassword = mysql_real_escape_string($mypassword);
$sql="SELECT * FROM $tbl_name WHERE username='$myusername' and password='$mypassword'";
$result=mysql_query($sql);

// Mysql_num_row is counting table row
$count=mysql_num_rows($result);

// If result matched $myusername and $mypassword, table row must be 1 row
if($count==1){

// Register $myusername, $mypassword and redirect to file "login_success.php"
session_register("myusername");
session_register("mypassword"); 
header("location:login_success.php");
}
else {
echo "Wrong Username or Password";
}
