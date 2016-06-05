<?php
/**
* Api de Consultas de Cedulas Venezolanas - Modulo de 'Pruébalo Gratis'
*
* @author    Kijam Lopez <klopez@cuado.co>
* @copyright 2015 Desarrollos Cuado C.A.
* @license   GPLv3
*/

    include_once ("./lib/classDBAndUser.php");
    if($_POST['vat']) {
        if(!checkRecaptchar(RECAPTCHAR_SECRET, $_POST['g-recaptcha-response']))
            $error = 'reCAPTCHA Inválido';
        else{
            $consulta = getCI($_POST['vat'], true);
            if(!$consulta)
                $error = 'Ocurrio un error en el servidor';
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
    $html_title = 'DEMO - API';
    $html_description = 'Demo del API de cedulas Venezolanas';
    $menu_select = '';
    include('header.php');
?>
<form action="<?php echo ACTUAL_URL; ?>" method="POST">
<div class="clearfix"> </div>
<br />
<br />
<div class="clearfix"> </div>
<div class="container">
    <?php if($error) echo '<div class="alert alert-danger" role="alert">'.$error.'</div>'; ?>
    <?php if($msj) echo '<div class="alert alert-success" role="alert">'.$msj.'</div>'; ?>
    <div class="col-md-6">
        <?php echo input('vat', 'Cedula de Identidad', $_POST['vat']); ?>
        <div id="recaptcha1" class="g-recaptcha"></div>
        <button type="submit" class="btn btn-default navbar-btn">Consultar</button>
        <br /><a target="_blank" href="http://wiki.cedula.com.ve/index.php/Documentaci%C3%B3n_del_API_Cedula:Limitaci%C3%B3n_general_de_responsabilidad">¿Como se obtienen los datos?</a>
        <br /><a href="/web/app.php">Ahora tambien puedes usarlo desde tu Móvil</a>
        <br /><script src='https://www.google.com/recaptcha/api.js?onload=myCallBack&render=explicit' async defer></script>
    </div>

    <?php if(isset($consulta)) {
        $data = json_decode($consulta, true);
        if($data['data']) {
            $user = $data['data'];
        ?>

        <br /><div class="clearfix"> </div>    <br />
        <div class="heading"><span>Datos Encontrados</span></div>
        <div class="col-md-12">
            <center><iframe scrolling="no" style="border: 0; width: 468px; height: 60px;" src="//coinurl.com/get.php?id=52256"></iframe></center>
            <div class="col-md-6"><b>Cedula</b></div><div class="col-md-5"><?php echo $user['cedula']; ?></div>
            <div class="col-md-6"><b>R.I.F.</b></div><div class="col-md-5"><?php echo $user['rif']; ?></div>
            <div class="col-md-6"><b>Nombre</b></div><div class="col-md-5"><?php echo $user['primer_nombre']; ?> <?php echo isset($user['segundo_nombre'])?$user['segundo_nombre']:""; ?> <?php echo $user['primer_apellido']; ?> <?php echo isset($user['segundo_apellido'])?$user['segundo_apellido']:""; ?></div>
            <?php if($user['cne']) { ?>
            <div class="col-md-6"><b>Estado donde Vota</b></div><div class="col-md-5"><?php echo $user['cne']['estado']; ?></div>
            <div class="col-md-6"><b>Municipio donde Vota</b></div><div class="col-md-5"><?php echo $user['cne']['municipio']; ?></div>
            <div class="col-md-6"><b>Parroquia donde Vota</b></div><div class="col-md-5"><?php echo $user['cne']['parroquia']; ?></div>
            <div class="col-md-6"><b>Centro Electoral</b></div><div class="col-md-5"><?php echo $user['cne']['centro_electoral']; ?></div>
            <div class="col-md-6"><b>Ultima Actualización del CNE</b></div><div class="col-md-5">Abril - 2012</div>
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
          'sitekey' : '<?php echo RECAPTCHAR_KEY; ?>', //Replace this with your Site key
          'theme' : 'light'
        });
      };
    </script>
</div>
</form>
<?php
    include('footer.php');
