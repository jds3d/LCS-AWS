<?php
	require("app/framework.php");
?>
<!doctype html>
<!--[if lt IE 7]> <html class="no-js lt-ie9 lt-ie8 lt-ie7" lang="en"> <![endif]-->
<!--[if IE 7]>    <html class="no-js lt-ie9 lt-ie8" lang="en"> <![endif]-->
<!--[if IE 8]>    <html class="no-js lt-ie9" lang="en"> <![endif]-->
<!--[if gt IE 8]><!--> <html class="no-js" lang="en"> <!--<![endif]-->
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
	<meta name="description" content="">
	<meta name="author" content="">
	<meta name="viewport" content="width=1100, user-scalable=no">
	<meta name="apple-mobile-web-app-capable" content="yes" />
	<title><?php print Config::$appTitle; ?></title>

<?php
	// let the JSPress module handle displaying the css tags (individuals for debug, compressed for release) 
	JSPressModule::printScriptTags(array('css/style.css'), 'css', 'css/');	 

	// let the JSPress module handle displaying the js tags (individuals for debug, compressed for release) 
	JSPressModule::printScriptTags(array('js/modernizr-2.5.3.min.js'), 'js');
	 
?>
	<script type="text/javascript">
		var DEBUG_ENABLED = <?php print (Config::$debug?'true':'false'); ?>;
		Modernizr.load([{
		                  test : Modernizr.localstorage,
		                  nope : []
		                },
		              ]);
	</script>
</head>
<body> 
	<table width="300" border="0" align="center" cellpadding="0" cellspacing="1" bgcolor="#CCCCCC">
		<tr>
			<form name="form1" method="post" action="checklogin.php">
			<td>
				<table width="100%" border="0" cellpadding="3" cellspacing="1" bgcolor="#FFFFFF">
					<tr>
						<td colspan="3"><strong>Member Login </strong></td>
					</tr>
					<tr>
						<td width="78">Username</td>
						<td width="6">:</td>
						<td width="294"><input name="myusername" type="text" id="myusername"></td>
					</tr>
					<tr>
						<td>Password</td>
						<td>:</td>
						<td><input name="mypassword" type="password" id="mypassword"></td>
					</tr>
					<tr>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td><input type="submit" name="Submit" value="Login"></td>
					</tr>
				</table>
			</td>
			</form>
		</tr>
	</table>
</body>
</html>