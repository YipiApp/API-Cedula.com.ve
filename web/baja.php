<?php
/**
* Api de Consultas de Cedulas Venezolanas - Modulo de Baja
*
* @author    Kijam Lopez <klopez@cuado.co>
* @copyright 2015 Desarrollos Cuado C.A.
* @license   GPLv3
*/
    include_once ("./lib/classDBAndUser.php");

    if (isset($_POST['cedula']) && isset($_POST['nacionalidad']) && validCed($_POST['cedula']))
    {
        if(!checkRecaptchar(RECAPTCHAR_SECRET, $_POST['g-recaptcha-response']))
            $error = 'reCAPTCHA inválido';
        else {
            $rif = valid_rif($_POST['nacionalidad'], trim($_POST['cedula']));
            if(!$rif)
                $error = 'Su cedula tiene un formato inválido';
            else if(($data = $db->ls("SELECT * FROM not_show WHERE rif = '%s'", array(secInjection($rif)))))
                $error = 'Esta cedula ya fue dado de baja el dia '.$data['created_date'];
            else if($db->qs("INSERT INTO not_show (rif, created_date, ip_submit) VALUES ('%s', NOW(), '%s')", array(secInjection($rif), getRealIP())))
                $msj = "Su cedula fue dado de baja de nuestro sistema correctamente";
            else
                $error = "Ocurrio un error temporal en nuestro sistema, intente mas tarde";
        }
    }
    $html_title = 'Baja';
    $html_description = 'Sacar cedula del Sistema';
    $menu_select = 'baja';
    include('header.php');
?>
<br />
<?php if($error) echo '<div class="alert alert-danger" role="alert">'.$error.'</div>'; ?>
<?php if($msj) echo '<div class="alert alert-success" role="alert">'.$msj.'</div>'; ?>
<br />
 <div class="heading"><span>Sacar mi Cedula del Sistema</span></div>
    <div class="heading"></div>
    <div class="col-md-12">Aunque los datos mostrados por el sistema son públicos, entendemos que hay usuarios que no quieren facilitar el acceso a esta información; por lo cual hemos previsto un mecanismo de fácil acceso que la desincorpora de nuestra API, <b>pero de igual forma esta información seguirá visible en los sistemas informáticos del gobierno (CNE, jefaturas a nivel nacional, etc.)</b> ya que se considera un acto ilegal ocultar o remover alguna persona en particular del registro civil (según dicta la ley). Desarrollos Cuado C.A. es una empresa autónoma e independiente al gobierno actual y por ende es técnicamente imposible poder alterar cualquier sistema electrónico que preste el gobierno de la República Bolivariana de Venezuela.
        Para mayor información le recomendamos leer nuestro <a href="http://wiki.cedula.com.ve/index.php/Documentaci%C3%B3n_del_API_Cedula:Limitaci%C3%B3n_general_de_responsabilidad" target="_blank">Aviso Legal</a> y  <a href="http://wiki.cedula.com.ve/index.php/Documentaci%C3%B3n_del_API_Cedula:Pol%C3%ADtica_de_protecci%C3%B3n_de_datos" target="_blank">Política de protección de datos</a>.
        <br /><br />
    </div>

    <div class="col-md-6">
        <form method="POST" action="<?php echo ACTUAL_URL; ?>">
            <?php echo select('nacionalidad', 'Nacionalidad', '', array('V'=>'Venezolana', 'E'=>'Extranjera'), array($_POST['nacionalidad'])); ?>
            <?php echo input('cedula', 'Cédula'); ?>
            <div id="recaptcha3" class="g-recaptcha"></div>
            <button type="submit" class="btn btn-default navbar-btn">Darme de Baja</button>
        </form>
    </div>
    <script src='https://www.google.com/recaptcha/api.js?onload=myCallBack&render=explicit' async defer></script>
    <script>
      var myCallBack = function() {
        grecaptcha.render('recaptcha3', {
          'sitekey' : '<?php echo RECAPTCHAR_KEY; ?>', //Replace this with your Site key
          'theme' : 'light'
        });
      };
    </script>
<?php
include('footer.php');