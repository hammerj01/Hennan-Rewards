<?php
	require_once (dirname(__FILE__) . '/api/config/config.php');
	require_once (dirname(__FILE__) . '/api/class/cipher.class.php');


	$key = randomizer(32);
	$iv = randomizer(16);
	$cipher = NEW cipher($key, $iv);
	$data = array("oauth"=>$key, "token"=>$iv);
?>

<!DOCTYPE html>
<html>
<head>
	<title></title>
	<link rel="stylesheet" type="text/css" href="<?php echo DOMAIN; ?>/assets/css/styles.css">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.css">
</head>
<body>

	<div class="container">
	
			<section>
				<div class="content">
					<div id="success">
						<div class="logo-cp"><img src="<?php echo DOMAIN; ?>/assets/images/henann-logo.png"></div>
						<div class="details">
							<inut type="hidden" name="email" value=<?php echo $_GET['email']; ?> id="email" />
							<p>Please input numeric characters only.</p>
							<br />
							<input class="changeP" maxlength="6" type="password" name="password" id="password"  placeholder="New Password" onkeypress="validate(event)"  />
							<i id="pass-status" class="fa fa-eye" aria-hidden="true" onClick="viewPassword()"></i><br /><br />
							
							<input style="margin-left: 3px;" class="changeP" maxlength="6" type="password" name="reenter" id="reenter"  placeholder="Reenter New Password" onkeypress="validate(event)"  />
							<i id="pass-status2" class="fa fa-eye" aria-hidden="true" onClick="viewPassword2()"></i>
							<br /><br />
							<button class="changeP-btn" onclick="Validate();">SUBMIT</button>
							
						</div>
					</div>
				</div>
			</section>
			<footer>
				<div id="logo"><img id="footer_logo_image" src="<?php echo DOMAIN; ?>/assets/images/logo.png" /></div>
			</footer>
		
	</div>
	
</body>
</html>

<script type="text/javascript">

    function Validate() {

    	var xmlhttp = new XMLHttpRequest(); 



        var email = getParameter("email");
        var password = document.getElementById("password").value;
        var confirmPassword = document.getElementById("reenter").value;
        var params = 'password='+password;

        if (password != confirmPassword) {
            alert("Password does not match.");
            return false;
        }
        if (password == "") {
        	alert("Please input password.");
        }


        // submitting to encryption 
        xmlhttp.onreadystatechange = function() {
        if (xmlhttp.readyState == XMLHttpRequest.DONE) {   // XMLHttpRequest.DONE == 4
          
           if (xmlhttp.status == 200) {
               
               console.log(xmlhttp.responseText);
               var resp = xmlhttp.responseText;

               if (resp.includes("Success")) {
               		window.location = "password_success.php?response=success";
               }

               else {
               		window.location = "password_success.php?response=fail";
               }
           }
           else if (xmlhttp.status == 400) {
              alert('There was an error connecting with the server.');
           }
           else {
               alert('something else other than 200 was returned');
           }
        }
   		};

    	xmlhttp.open("POST", "success_password.php?email="+email+"&password="+password, true);
    	xmlhttp.send();

      // window.location = "<?php //echo DOMAIN; ?>/success_password.php?email="+email+"&password=" + password;
    
        // window.location.assign = murl;
	 

		

    }

//onkeypress="validate(event)"   
//onkeypress="validate(event)"



    function validate(evt) {
	  var theEvent = evt || window.event;
	  var key = theEvent.keyCode || theEvent.which;
	  key = String.fromCharCode( key );
	  var regex = /[0-9]|\./;
	  if( !regex.test(key) ) {
	    theEvent.returnValue = false;
	    if(theEvent.preventDefault) theEvent.preventDefault();
	  }
	}

    function viewPassword()
	{
	  var passwordInput = document.getElementById('password');
	  var passStatus = document.getElementById('pass-status');
	 
	  if (passwordInput.type == 'password'){
	    passwordInput.type='text';
	    passStatus.className='fa fa-eye-slash';
	    
	  }
	  else{
	    passwordInput.type='password';
	    passStatus.className='fa fa-eye';
	  }
	}

	function viewPassword2()
	{
	  var passwordInput = document.getElementById('reenter');
	  var passStatus = document.getElementById('pass-status2');
	 
	  if (passwordInput.type == 'password'){
	    passwordInput.type='text';
	    passStatus.className='fa fa-eye-slash';
	    
	  }
	  else{
	    passwordInput.type='password';
	    passStatus.className='fa fa-eye';
	  }
	}

function getParameter(theParameter) { 
  var params = window.location.search.substr(1).split('&');
 
  for (var i = 0; i < params.length; i++) {
    var p=params[i].split('=');
	if (p[0] == theParameter) {
	  return decodeURIComponent(p[1]);
	}
  }
  return false;
}


</script>

