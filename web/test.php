<?php 
	
    include_once ("./lib/classDBAndUser.php");
	if($_POST['vat']) {
		if(!checkRecaptchar(RECAPTCHAR_SECRET, $_POST['g-recaptcha-response']))
			$error = 'reCAPTCHA Inválido';
		else{
			$consulta = getCI($_POST['vat'], true);
			if(!$consulta)
				$error = 'Ocurrio un error con la comunicacion del servidor';
		}
	}
	function prettyPrint( $json )
	{
		$result = '';
		$level = 0;
		$in_quotes = false;
		$in_escape = false;
		$ends_line_level = NULL;
		$json_length = strlen( $json );

		for( $i = 0; $i < $json_length; $i++ ) {
			$char = $json[$i];
			$new_line_level = NULL;
			$post = "";
			if( $ends_line_level !== NULL ) {
				$new_line_level = $ends_line_level;
				$ends_line_level = NULL;
			}
			if ( $in_escape ) {
				$in_escape = false;
			} else if( $char === '"' ) {
				$in_quotes = !$in_quotes;
			} else if( ! $in_quotes ) {
				switch( $char ) {
					case '}': case ']':
						$level--;
						$ends_line_level = NULL;
						$new_line_level = $level;
						break;

					case '{': case '[':
						$level++;
					case ',':
						$ends_line_level = $level;
						break;

					case ':':
						$post = " ";
						break;

					case " ": case "\t": case "\n": case "\r":
						$char = "";
						$ends_line_level = $new_line_level;
						$new_line_level = NULL;
						break;
				}
			} else if ( $char === '\\' ) {
				$in_escape = true;
			}
			if( $new_line_level !== NULL ) {
				$result .= "\n".str_repeat( "\t", $new_line_level );
			}
			$result .= $char.$post;
		}

		return $result;
	}
?><!--A Design by W3layouts
Author: W3layout
Author URL: http://w3layouts.com
License: Creative Commons Attribution 3.0 Unported
License URL: http://creativecommons.org/licenses/by/3.0/
-->
<!DOCTYPE HTML>
<html>
<head>
<title>DEMO - API - Consultas de Cedulas Venezolanas</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="keywords" content="api,cedula,cedulas,venezuela,venezolanas,venezolanos,consultas,webservice,json" />
<script type="application/x-javascript"> addEventListener("load", function() { setTimeout(hideURLbar, 0); }, false); function hideURLbar(){ window.scrollTo(0,1); } </script>
<link href="css/bootstrap.css" rel='stylesheet' type='text/css' />
<link href="css/style.css" rel='stylesheet' type='text/css' />
<meta name="robots" content="nofollow" />
<script type="text/javascript" src="js/jquery-1.11.1.min.js"></script>
<link href='http://fonts.googleapis.com/css?family=Montserrat:400,700' rel='stylesheet' type='text/css'>
</head>
<body>
<form action="<?php echo ACTUAL_URL; ?>" method="POST">
<div class="container">
	<?php if($error) echo '<div class="alert alert-danger" role="alert">'.$error.'</div>'; ?>
	<?php if($msj) echo '<div class="alert alert-success" role="alert">'.$msj.'</div>'; ?>
	<div class="col-md-6">
		<?php echo input('vat', 'Cedula de Identidad', $_POST['vat']); ?>
		<div id="recaptcha1" class="g-recaptcha"></div>
		<button type="submit" class="btn btn-default navbar-btn">Consultar</button>
		<a target="_blank" href="http://wiki.cedula.com.ve/index.php/Documentaci%C3%B3n_del_API_Cedula:Limitaci%C3%B3n_general_de_responsabilidad">¿Como se obtienen los datos?</a>
		<script src='https://www.google.com/recaptcha/api.js?onload=myCallBack&render=explicit' async defer></script>
	</div>
	<?php if(isset($consulta)) { 
		$data = json_decode($consulta, true);
		if($data['data']) { 
			$user = $data['data'];
		?>
		
		<br /><div class="clearfix"> </div>	<br />
		<div class="heading"><span>Datos Encontrados</span></div>	
		<div class="col-md-12">
			<div class="col-md-6"><b>Nombre</b></div><div class="col-md-5"><?php echo $user['primer_nombre']; ?> <?php echo $user['segundo_nombre']?$user['segundo_nombre']:""; ?> <?php echo $user['primer_apellido']; ?> <?php echo $user['segundo_apellido']?$user['segundo_apellido']:""; ?></div>
			<?php if($user['cne_parroquia']) { ?>
			<div class="col-md-6"><b>Estado donde Vota</b></div><div class="col-md-5"><?php echo $user['cne_estado']; ?></div>
			<div class="col-md-6"><b>Municipio donde Vota</b></div><div class="col-md-5"><?php echo $user['cne_municipio']; ?></div>
			<div class="col-md-6"><b>Parroquia donde Vota</b></div><div class="col-md-5"><?php echo $user['cne_parroquia']; ?></div>
			<div class="col-md-6"><b>Centro Electoral</b></div><div class="col-md-5"><?php echo $user['centro_electoral']; ?></div>
			<?php } ?>		
		</div>
		<?php
		}
	?>
	<br /><div class="clearfix"> </div><br />	
	<div class="heading"><span>Datos retornados por el API</span></div>
	<div class="clearfix"> </div>		
	<div class="col-md-12">
		<pre><?php echo prettyPrint($consulta); ?></pre>
	</div>	
	<?php } ?>
	<script>
      var myCallBack = function() {
        grecaptcha.render('recaptcha1', {
          'sitekey' : '6LdeKwQTAAAAAIY0xUJxr3oVP7rJuwgb3y65Km9r', //Replace this with your Site key
          'theme' : 'light'
        });
      };
    </script>
</div>
</form>
</body>

</html>