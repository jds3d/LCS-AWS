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
	<title> LCS Admin </title>
    <script src="javascript/jquery.js"></script>
	<script src="javascript/jquery.ajaxfileupload.js"></script>
	<script type="text/javascript">
        // This function Starts the file upload
        $(function() {
            $("#submitButton").click(function(){
                 var checked = true;
                 $("input").each( function() {
                     if ( this.value == null || this.value =="" ){
                         checked = false;
                     }
                 });
                 if(checked){
                     $('#configFile').ajaxfileupload({
                         'params': {
                             'adminPassCode':  $("#adminPassword").val(),
                             'fileName': $("#configName").val()
                         },
                         'action': '../api/Admin/addConfigFile',
                         'onComplete': function(response) {
							
                            if(response == '{"Action":"Bad Passcode"}') {
                                 alert("The Password is invalid");
                            }
                            else if(response == '{"Action":"Failed"}') {
                                alert("The File Could Not Be Uploaded");
                            }
                            else {
                                alert("The Upload Was Successful");
                            }
                         },
                         'onStart': function() {

                         }
                     });
                 }
                 else{
                     alert("Please Fill Out The Form.");
                 }
             });
	    });
	</script>
    <style type="text/css">
        .adminHeader {
            color: #FFFFFF;
            font-size: 160%;
            font-family: "Arial Black", Gadget, sans-serif ;
            background-color: #51AF37;
            border-top: 3px solid;
            border-left: 3px solid;
            border-right: 3px solid;
            border-bottom: 0px solid;
            width: 50%;
            height:70px;
            border-radius: 25px 25px 0px 0px;
            text-align: center;
            margin: auto;
        }
        .adminBody {
            font-family: "Arial Black", Gadget, sans-serif ;
            border:3px solid;
            width: 50%;
            height: 300px;
            color: #FFFFFF;
            background-color: #002984;
            border-radius: 0px 0px 25px 25px;
            text-align: left;
            margin: auto;
        }
        .formBody {
            margin: 10%;
        }
        .inputField {
            float:  right;
            width: 200px;
            font-family: "Arial Black", Gadget, sans-serif ;
        }
        .headerText {
            margin: 2%;
        }
        .label {
            float: left;
            width: 200px;
        }
        .formSubmit{
            margin: 20%;
            text-align: center;
        }
        .clear
        {
         clear: both;
        }
        .formBody button {
            font-family: "Arial Black", Gadget, sans-serif ;
            height : 30px;
            border-radius:  5px  5px 5px 5px;
            background-color:#D1D1D1 ;

        }
        .background {
          background-color: #D1D1D1;
        }
    </style>
</head>
<body class="background">
      <div class="adminHeader">
            <div class="headerText" tabindex="0">DLCS ADMIN PORTAL </div>
      </div>
      <div class="adminBody">
          <form name="fileUploader" id="fileUploader" action="index.php" enctype="multipart/form-data" method="post">
                <div class="formBody">
                    <div class="label">
                        <label for="adminPassword">Admin Password : </label>
                   </div>
                   <div  class="inputField">
                        <input type="password" id="adminPassword" name="adminPassword" tabindex="1"/>
                   </div>
                    <div class="clear">

                    </div>
                    <div class="label">
                        <label for="configName"> Configuration Name  : </label>
                    </div>
                    <div  class="inputField">
                        <input type="text" name="configName" id="configName" tabindex="2"/>

                    </div>
                    <div class="clear">

                    </div>
                    <div class="label">
                        <label for="configFile"> Configuration  JSON  : </label>
                    </div>
                    <div class="inputField">

                        <input type="file" name="configFile" id="configFile" tabindex="3"  accept="text"/>

                    </div>
                    <div class="clear" >

                    </div>
                    <div class="formSubmit">
                        <button name ="submitButton" id="submitButton"  tabindex="4" type="button">
                            Submit DLCS Config File
                        </button>
                    </div>
                </div>
          </form>
       </div>
</body>
</html>