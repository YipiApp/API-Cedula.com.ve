<?php
/**
* Api de Consultas de Cedulas Venezolanas - Panel de Control
*
* @author    Kijam Lopez <klopez@cuado.co>
* @copyright 2015 Desarrollos Cuado C.A.
* @license   GPLv3
*/
    include_once ("./lib/classDBAndUser.php");

    $user = false;
    $error = false;
    $msj = false;

    if($_GET['op']=='logout'){
        User::logOut();
        header('location: login.php');
        exit;
    }

    if($_GET['op']=='validate'){
        $userValid = User::getUserByUsername($_GET['user']);
        if($userValid && $userValid->rol == 3 && $_GET['token'] == md5($userValid->user.SEMILLA_NEW_USER)){
            User::updateUser($userValid->id, 2, $userValid->mail);
            $msj = 'Su e-mail ha sido verificado con éxito, ya puede usar su cuenta para disfrutar nuestro servicio.';
        }else
            $error = 'El token de validación no es correcto.';
    }
    $isLogin = User::authSession();
    if($isLogin)
        $user = $_SESSION['user'];

    if($isLogin && $_GET['op']=='renew' && (int)$_GET['id_service'] > 0){
        $service = $db->ls("SELECT * FROM api_invoices INNER JOIN api_services ON api_invoices.id_service = api_services.id_service WHERE api_services.id_usuario = %d AND api_services.id_service = %d ORDER BY date_created DESC LIMIT 1", array($user->id, (int)$_GET['id_service']));
        if($service)
            header('location: payment.php?id_service='.$service['id_service'].'&id_plan='.$service['id_plan'].'&currency='.$service['currency']);
    }

    if($isLogin && $_GET['op']=='renew_token' && (int)$_GET['id_service'] > 0){
        $service = $db->ls("SELECT * FROM api_invoices INNER JOIN api_services ON api_invoices.id_service = api_services.id_service WHERE api_services.id_usuario = %d AND api_services.id_service = %d ORDER BY date_created DESC LIMIT 1", array($user->id, (int)$_GET['id_service']));
        if($service){
            if($db->qs("UPDATE api_services SET token = '%s' WHERE id_service = %d", array(secInjection(md5(time(). $user->id . User::$keySecurity)), (int)$_GET['id_service'])))
                $msj = 'Se ha generado un nuevo token para su APP';
            else
                $error = 'Ocurrió un error actualizando su APP';
        }else $error = 'El APP no existe o usted no es el dueño';
    }

    if (isset($_POST))
    {
        foreach($_POST as $k => $v)
            if($k!='pass'&&$k!='pass2')
                $_POST[$k] = trim($v);

        if(!$isLogin) {
            if($_GET['op']=='login'){
                if(!checkRecaptchar(RECAPTCHAR_SECRET, $_POST['g-recaptcha-response']))
                    $error = 'reCAPTCHA Invalido';
                else if (User::login($_POST['user'], $_POST['pass']))
                    header('location: login.php');
                else
                    $error = 'Usuario o clave Inválida';
            }else if($_GET['op']=='forgot'){
                if(!checkRecaptchar(RECAPTCHAR_SECRET, $_POST['g-recaptcha-response']))
                    $error = 'reCAPTCHA Inválido';
                else
                {
                    $userForgot = User::getUserByUsername($_POST['user']);
                    if(!$userForgot)
                        $userForgot = User::getUserByMail(strtolower($_POST['user']));

                    if($userForgot) {
                        $newPassword = User::generateRandomPassword(8);
                        if(User::updateUser($userForgot->id, $userForgot->rol, $userForgot->mail, $newPassword)){
                            $msj = 'Se le envió un correo electrónico con su nueva clave.';
                            email($userForgot->mail, 'Nueva Clave', 'Sr. '.$userForgot->name.',<br /><br />Su nueva clave de ingreso al sistema es: <b>'.$newPassword.'</b>');
                        }else{
                            $error = 'Ocurrió un error interno, intente más tarde.';
                        }
                    }else{
                        $error = 'Usuario o E-Mail no está registrado';
                    }
                }
            }else if($_GET['op']=='register'){
                $countries = getCountries();

                if(strlen($_POST['user']) < 3 || !validUsername($_POST['user']))
                    $error = '- El usuario debe tener al menos cuatro caracteres y el primer caracter no puede ser un número<br />';

                if(strlen($_POST['mail']) < 3 || !emailValido($_POST['mail']))
                    $error = '- El E-Mail no es válido<br />';

                if(strlen($_POST['country']) != 2 || !isset($countries[$_POST['country']]))
                    $error = '- Su País de origen es requerido<br />';

				$isRif = false;

                if($_POST['country'] == 'VE') {
					$_POST['vat'] = strtoupper(trim(preg_replace('/[\. -]+/', '', $_POST['vat'])));
					if(strlen($_POST['vat']) < 6 || !validVatVE($_POST['vat']))
						$error .= '- Su identificación fiscal no tiene un formato válido para Venezuela<br />';
					else {
						switch($_POST['vat'][0]){
							case 'J':
							case 'G':
							case 'E':
							case 'P':
							case 'V':
								if(!validVatVE_J($_POST['vat']))
									$error .= '- El R.I.F. suministrado tiene un formato invalido, debe tener [VEJGEP] + 9 digitos exactos, si su rif tiene menos de nueve digitos completar con 0 a la izquierda. Ejemplo: J000012345<br />';
								$isRif = true;
								break;
						}
					}
				}

                if(!checkRecaptchar(RECAPTCHAR_SECRET, $_POST['g-recaptcha-response']))
                    $error = '- reCAPTCHA Inválido<br />';

                if(!$error)
                {
                    $_SESSION['valid_register'] = $_POST['country'];
                    if($_POST['country'] == 'VE')
                    {
						if(!$isRif || $_POST['vat'][0] == 'V' || $_POST['vat'][0] == 'E') {
							$cedula = $_POST['vat'];
							if($isRif)
								$cedula = substr($cedula, 1, strlen($cedula) - 2) * 1;
							$consulta_api = getCI($cedula);
							if($consulta_api)
							{
								$_POST['name'] = $consulta_api['primer_nombre']." ".$consulta_api['primer_apellido'].(isset($consulta_api['segundo_apellido'])?" ".$consulta_api['segundo_apellido']:"");
								if($consulta_api['cne'])
									$_POST['address'] = $consulta_api['cne']['estado'].", ".$consulta_ap['cne']['municipio'].", ".$consulta_api['cne']['parroquia'];
							}
						}else{
							$consulta_seniat = getRifSeniat($_POST['vat'], true);
							if($consulta_seniat && $consulta_seniat['ok'] && $consulta_seniat['result']['name'])
								$_POST['name'] = preg_replace('/[^0-9a-zA-ZñÑáéíóúÁÉÍÓÚ., ]+/', '', $consulta_seniat['result']['name']);
						}
                    }
                } else {
                    unset($_SESSION['valid_register']);
                }
            }else if($_GET['op']=='register2' && isset($_SESSION['valid_register']) && strlen($_SESSION['valid_register']) == 2){
                $error = '';

                if(strlen($_POST['pass']) < 5)
                    $error .= '- La clave debe tener al menos cinco caracteres<br />';
                if($_POST['pass'] != $_POST['pass2'])
                    $error .= '- Su contraseña no coincide en ambos campos<br />';
                if(strlen($_POST['name']) < 3 || !validName($_POST['name']))
                    $error .= '- Su Nombre es requerido, sólo puede usar caracteres alfanuméricos<br />';
                if(strlen($_POST['address']) < 3 || !validAddress($_POST['address']))
                    $error .= '- Su Dirección es requerida, sólo puede usar caracteres alfanuméricos<br />';
                if(strlen($_POST['phone']) < 3 || !validPhone($_POST['phone']))
                    $error .= '- Su Teléfono es requerido, sólo puede usar dígitos<br />';
                if(strlen($_POST['vat']) > 0 && !validVat($_POST['vat']))
                    $error .= '- Su identificación fiscal sólo puede tener caracteres alfanuméricos<br />';

                if($_SESSION['valid_register'] == 'VE') {
					$_POST['vat'] = strtoupper(trim(preg_replace('/[\. -]+/', '', $_POST['vat'])));
					if(strlen($_POST['vat']) < 6 || !validVatVE($_POST['vat']))
						$error .= '- Su identificación fiscal no tiene un formato válido para Venezuela<br />';
					else {
						switch($_POST['vat'][0]){
							case 'J':
							case 'G':
							case 'E':
							case 'P':
							case 'V':
								if(!validVatVE_J($_POST['vat']))
									$error .= '- El R.I.F. suministrado es invalido o no existe, debe tener [VEJGEP] + 9 digitos exactos, si su rif tiene menos de nueve digitos completar con 0 a la izquierda. Ejemplo: J000012345<br />';
								break;
						}
					}
				}

                if(strlen($_POST['user']) < 3 || !validUsername($_POST['user']))
                    $error .= '- El usuario debe tener al menos cuatro caracteres y el primer caracter no puede ser un número<br />';

                if(strlen($_POST['mail']) < 3 || !emailValido($_POST['mail']))
                    $error .= '- El E-Mail no es válido<br />';

                if($error == '')
                {
                    $_POST['country'] = $_SESSION['valid_register'];
                    $_POST['rol'] = 3;
                    $error = false;
                    $result = User::addUser($_POST);
                    if($result == OK) {
                        $msj = 'Se le envió un correo electrónico, debe revisarlo para completar el registro.';
                        email($_POST['mail'], 'Confirmación de Registro', 'Sr. '.$_POST['name'].',<br /><br />Para completar el registro haga click en el siguiente link: '.ACTUAL_URL.'?op=validate&user='.$_POST['user'].'&token='.md5($_POST['user'].SEMILLA_NEW_USER).'</b>');
                        unset($_POST);
                        $_POST = array();
                        unset($_SESSION['valid_register']);
                    }else if($result == E_MAIL_EXIST){
                        $error = 'El E-Mail ya está siendo utilizado por otro usuario';
                    }else if($result == E_USER_EXIST){
                        $error = 'El Nombre de Usuario ya está siendo utilizado por otro usuario';
                    }else{
                        $error = 'Ocurrió un error interno registrando su cuenta, intente más tarde.';
                    }
                }
            }
        } else {
            if($_GET['op']=='update_user'){
                if(strlen($_POST['pass']) < 5)
                    $error = 'La clave debe tener al menos cinco caracteres<br />';
                else if($_POST['pass'] != $_POST['pass2'])
                    $error = 'Su contraseña no coincide en ambos campos<br />';

                if(!$error)
                {
                    if(User::updateUser($user->id, $user->rol, $user->mail, $_POST['pass']))
                        $msj = 'Se actualizó su contraseña con éxito.';
                    else
                        $error = 'Ocurrió un error interno registrando su cuenta, intente más tarde.';
                }
            }else if($_GET['op']=='update_data'){
                $error = '';
                $countries = getCountries();
                if(strlen($_POST['name']) < 3 || !validName($_POST['name']))
                    $error .= '- Su Nombre es requerido, solo puede usar caracteres alfanuméricos<br />';
                if(strlen($_POST['address']) < 3 || !validAddress($_POST['address']))
                    $error .= '- Su Dirección es requerida, solo puede usar caracteres alfanuméricos<br />';
                if(strlen($_POST['phone']) < 3 || !validPhone($_POST['phone']))
                    $error .= '- Su Teléfono es requerido, solo puede usar dígitos<br />';
                if(strlen($_POST['vat']) > 0 && !validVat($_POST['vat']))
                    $error .= '- Su identificación fiscal solo puede tener caracteres alfanuméricos<br />';
                if($error == '')
                {
                    if(User::updateUserData($user->id, $_POST['name'], $_POST['vat'], $_POST['address'], $user->country, $_POST['phone']))
                        $msj = 'Se actualizó su información con éxito.';
                    else
                        $error = 'Ocurrió un error interno registrando su cuenta, intente más tarde.';
                }
            }else if($_GET['op']=='new_app'){
                if($_POST['currency'] == 'VEF' && ($user->country != 'VE' || getGeoip() != 'VE'))
                    $error = 'Se ha detectado que usted no está residenciado en Venezuela, usted sólo puede pagar en U$D.';

                if(!$error)
                    header('location: payment.php?id_plan='.$_POST['id_plan'].'&currency='.$_POST['currency']);
            }
        }
    }
    $html_title = 'Panel de Control';
    $html_description = 'Panel de control, administra tus aplicaciones y controla el acceso a nuestro API. Realiza tus pagos y mucho mas.';
    $menu_select = 'login';
    include('header.php');
?>

<?php if($error) echo '<div class="alert alert-danger" role="alert">'.$error.'</div>'; ?>
<?php if($msj) echo '<div class="alert alert-success" role="alert">'.$msj.'</div>'; ?>

<?php
if(!$isLogin) {

    if(($_GET['op']=='register' || $_GET['op']=='register2') && isset($_SESSION['valid_register']) && $_SESSION['valid_register']) {
?>

    <div class="heading"><span>Registro</span></div>
    <div class="heading"></div>
    <div class="col-md-6">
        <form method="POST" action="<?php echo ACTUAL_URL; ?>?op=register2">
            <?php echo input('user', 'Nombre de Usuario', ($_GET['op']=='register' || $_GET['op']=='register2')?$_POST['user']:''); ?>
            <?php echo input('pass', 'Contraseña', '', '', 'password'); ?>
            <?php echo input('pass2', 'Confirmar Contraseña', '', '', 'password'); ?>
            <?php echo input('mail', 'E-Mail', ($_GET['op']=='register' || $_GET['op']=='register2')?$_POST['mail']:''); ?>
            <?php echo input('name', 'Nombre', ($_GET['op']=='register' || $_GET['op']=='register2')?$_POST['name']:''); ?>
            <?php echo input('vat', 'C.I./RIF/DNI', ($_GET['op']=='register' || $_GET['op']=='register2')?$_POST['vat']:''); ?>
            <?php echo input('address', 'Dirección', ($_GET['op']=='register' || $_GET['op']=='register2')?$_POST['address']:''); ?>
            <?php echo input('phone', 'Teléfono', ($_GET['op']=='register' || $_GET['op']=='register2')?$_POST['phone']:''); ?>
            <button type="submit" class="btn btn-default navbar-btn">Registrarme</button>
    </div>

<?php
    }else{

?>

    <br /><br /><div class="clearfix"> </div>
    <div class="heading"><span>Iniciar Sesión</span></div>
    <div class="col-md-6">
        <form method="POST" action="<?php echo ACTUAL_URL; ?>?op=login">
            <?php echo input('user', 'Nombre de Usuario'); ?>
            <?php echo input('pass', 'Contraseña', '', '', 'password'); ?>
            <div id="recaptcha1" class="g-recaptcha"></div>
            <button type="submit" class="btn btn-default navbar-btn">Iniciar Sesión</button>
            <br /><a href="javascript:void(0)" onclick="$('#forgot-login').show()">¿Olvidó su contraseña?</a>
        </form>
    </div>
    <div class="clearfix"> </div>
    <br /><br />
    <div id="forgot-login" style="display:none">

        <div class="heading"><span>Olvidé Contraseña</span></div>
        <div class="col-md-6">
            <form method="POST" action="<?php echo ACTUAL_URL; ?>?op=forgot">
                <?php echo input('user', 'Usuario o E-Mail'); ?>
                <div id="recaptcha2" class="g-recaptcha"></div>
                <button type="submit" class="btn btn-default navbar-btn">Recuperar Contraseña</button>
            </form>
        </div>
        <div class="clearfix"> </div>
        <br /><br />
    </div>
    <div class="heading"><span>Registro</span></div>
    <div class="heading"></div>
    <div class="col-md-6">
        <form method="POST" action="<?php echo ACTUAL_URL; ?>?op=register">
            <?php echo input('user', 'Nombre de Usuario', ($_GET['op']=='register')?$_POST['user']:''); ?>
            <?php echo input('mail', 'E-Mail', ($_GET['op']=='register')?$_POST['mail']:''); ?>
            <?php echo input('vat', 'C.I./RIF/DNI', ($_GET['op']=='register')?$_POST['vat']:''); ?>
            <?php echo select('country', 'País', '', getCountries(), ($_GET['op']=='register')?array($_POST['country']):array()); ?>
            <div id="recaptcha3" class="g-recaptcha"></div>
            <button type="submit" class="btn btn-default navbar-btn">Registrarme</button>
    </div>
    <script src='https://www.google.com/recaptcha/api.js?onload=myCallBack&render=explicit' async defer></script>
    <script>
      var myCallBack = function() {
        grecaptcha.render('recaptcha1', {
          'sitekey' : '<?php echo RECAPTCHAR_KEY; ?>', //Replace this with your Site key
          'theme' : 'light'
        });
        grecaptcha.render('recaptcha2', {
          'sitekey' : '<?php echo RECAPTCHAR_KEY; ?>', //Replace this with your Site key
          'theme' : 'light'
        });
        grecaptcha.render('recaptcha3', {
          'sitekey' : '<?php echo RECAPTCHAR_KEY; ?>', //Replace this with your Site key
          'theme' : 'light'
        });
      };
    </script>
<?php
    }
} else {

$services = $db->ls("SELECT * FROM api_services WHERE id_usuario = %d ORDER BY proximo_corte ASC", array($user->id), false);
$invoices = $db->ls("SELECT * FROM api_invoices INNER JOIN api_services ON api_invoices.id_service = api_services.id_service WHERE api_services.id_usuario = %d ORDER BY date_created DESC", array($user->id), false);
$planes_db = $db->ls("SELECT planes.*, precios.* FROM api_planes planes INNER JOIN api_precio_planes precios ON planes.id_plan = precios.id_plan".(User::isAdmin()?'':' AND planes.activo = 1'), array(), false);

$planes = array();
$planes_precios = array();
foreach($planes_db as $plan){
    $planes_precios[$plan['id_plan']][$plan['currency']] = $plan;
    $planes[$plan['id_plan']] = $plan['nombre'];
}
?>
<br /><br /><div class="clearfix"> </div>
<div class="heading"><span>Mi Cuenta</span></div>
<div class="col-md-12">
<table class="table table-hover">
      <thead>
        <tr>
          <th>Nombre</th>
          <th>Usuario</th>
          <th>E-Mail</th>
          <th>Teléfono</th>
          <th>Opciones</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td><?php echo $user->vat; ?> <?php echo $user->name; ?></td>
          <td><?php echo $user->user; ?></td>
          <td><?php echo $user->mail; ?></td>
          <td><?php echo $user->phone; ?></td>
          <td>
          <button type="button" class="btn btn-info" onclick="$('#myaccount-user').show()">Cambiar Contraseña</button>
          <button type="button" class="btn btn-success" onclick="$('#myaccount-data').show()">Actualizar mis datos</button>
          <button type="button" class="btn btn-danger" onclick="window.location.href='<?php echo ACTUAL_URL; ?>?op=logout'">Cerrar Sesión</button></td>
        </tr>
      </tbody>
</table>
</div>

<div id="myaccount-data" style="display:none" class="col-md-6">
    <form method="POST" action="<?php echo ACTUAL_URL; ?>?op=update_data">
        <?php echo input('name', 'Nombre', ($_GET['op']=='update_data')?$_POST['name']:$user->name); ?>
        <?php echo input('vat', 'C.I./RIF/DNI', ($_GET['op']=='update_data')?$_POST['vat']:$user->vat); ?>
        <!-- <?php echo select('country', 'País', '', getCountries(), ($_GET['op']=='update_data')?array($_POST['country']):array($user->country)); ?> -->
        <?php echo input('address', 'Dirección', ($_GET['op']=='update_data')?$_POST['address']:$user->address); ?>
        <?php echo input('phone', 'Teléfono', ($_GET['op']=='update_data')?$_POST['phone']:$user->phone); ?>
        <button type="button" onclick="$('#myaccount-data').hide()" class="btn btn-info">Cancelar</button>
        <button type="submit" class="btn btn-default navbar-btn">Actualizar Datos</button>
    </form>
</div>
<div id="myaccount-user" style="display:none" class="col-md-6">
    <form method="POST" action="<?php echo ACTUAL_URL; ?>?op=update_user">
        <?php echo input('pass', 'Contraseña', '', '', 'password'); ?>
        <?php echo input('pass2', 'Confirmar Contraseña', '', '', 'password'); ?>
        <button type="button" onclick="$('#myaccount-user').hide()" class="btn btn-info">Cancelar</button>
        <button type="submit" class="btn btn-default navbar-btn">Actualizar Contraseña</button>
    </form>
</div>
<br /><br /><div class="clearfix"> </div>    <br /><br />
<div class="heading"><span>Mis APP's</span></div>
<div class="col-md-12">
<table class="table table-hover">
      <thead>
        <tr>
          <th>APP-ID</th>
          <th>Plan Contratado</th>
          <th>Última Renovación</th>
          <th>Fecha de Vencimiento</th>
          <th>Estado</th>
          <th>Opciones</th>
        </tr>
      </thead>
      <tbody>
      <?php
      foreach ($services as $service)
         echo '
            <tr>
              <th scope="row">'.$service['id_service'].'</th>
              <td>'.$planes[$service['id_plan']].'</td>
              <td>'.$service['fecha_inicio'].'</td>
              <td>'.$service['proximo_corte'].'</td>
              <td><span '.((bool)$service['activo']?(strtotime($service['proximo_corte'])<time()?'class="label label-warning">Vencido':'class="label label-success">Activo'):'class="label label-danger">Suspendido').'</span></td>
              <td>'.((bool)$service['activo']?'
              <button type="button" class="btn btn-info" data-toggle="modal" data-target="#dialogToken" onclick="$(\'#show-app-id\').html(\''.$service['id_service'].'\');$(\'#show-token\').html(\''.$service['token'].'\');">Mostrar Token</button>
              <button type="button" class="btn btn-success" onclick="window.location.href=\''.ACTUAL_URL.'?op=renew_token&id_service='.$service['id_service'].'\'">Generar nuevo Token</button>
              '.((bool)$service['activo'] && (strtotime($service['proximo_corte'])-time())<15*24*3600?'<button type="button" class="btn btn-danger" onclick="window.location.href=\''.ACTUAL_URL.'?op=renew&id_service='.$service['id_service'].'\'">Renovar Servicio</button>':'').'
              ':'').'</td>
            </tr>';
    ?>
      </tbody>
    </table>
</div>

<button type="button" class="btn btn-success" onclick="$('#new-app').show()">Añadir nuevo APP</button><br /><br />
<div class="clearfix"> </div>
<div id="new-app" style="display:none" class="col-md-6">
    <form method="POST" action="<?php echo ACTUAL_URL; ?>?op=new_app">
        <?php echo select('id_plan', 'Plan', 'Seleccione el plan que comprará', $planes); ?>
        <?php echo select('currency', 'Forma de Pago', '', array('USD'=>'Pagar en U$D','VEF'=>'Pagar en Bsf.')); ?>
        <button type="button" onclick="$('#new-app').hide()" class="btn btn-info">Cancelar</button>
        <button type="submit" class="btn btn-default navbar-btn">Comprar</button>
    </form>
</div><div class="clearfix"> </div>
<br /><br /><div class="clearfix"> </div>    <br /><br />
<div class="heading"><span>Mis Facturas</span></div>
<div class="col-md-12">
<table class="table table-hover">
      <thead>
        <tr>
          <th>#INV</th>
          <th>APP-ID</th>
          <th>Metodo</th>
          <th>Ref.</th>
          <th>Monto</th>
          <th>Fecha</th>
          <th>Estado</th>
        </tr>
      </thead>
      <tbody>
      <?php
      foreach ($invoices as $invoice)
         echo '
            <tr>
              <th scope="row">'.$invoice['id_invoice'].'</th>
              <td>'.$invoice['id_service'].'</td>
              <td>'.$invoice['payment_name'].'</td>
              <td>'.$invoice['payment_reference'].'</td>
              <td>'.$invoice['amount'].' '.$invoice['currency'].'</td>
              <td>'.$invoice['date_created'].'</td>
              <td>'.$invoice['payment_status'].'</td>
            </tr>';
    ?>
      </tbody>
    </table>
</div>
<div class="clearfix"> </div>

<div class="modal fade" id="dialogToken" tabindex="-1" role="dialog" aria-labelledby="dialogTokenLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="dialogTokenLabel">Datos de Acceso al API</h4>
      </div>
      <div class="modal-body">
        <b>APP-ID:</b> <div id="show-app-id"></div><br />
        <b>Access Token:</b> <div id="show-token"></div><div class="clearfix"> </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
      </div>
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->

<?php } ?>


<?php
include('footer.php');
