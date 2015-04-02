<?php
    include_once ("./lib/classDBAndUser.php");
    include_once ("./lib/mercadopagove.php");

    $isLogin = User::authSession();
    if(!$isLogin) {
        header('location: login.php');
        exit;
    }
    $user = $_SESSION['user'];
    $error = false;
    $msj = false;
    $service = false;
    $plan_db = false;
    if($_GET['currency'] != 'VEF' && $_GET['currency'] != 'USD')
        $error = 'La moneda seleccionada no esta soportada por nuestro sistema';

    if(!isset($_GET['id_plan']) || (int)$_GET['id_plan'] < 1)
        $error = 'Debe seleccionar un plan.';

    if($_GET['currency'] == 'VEF' && ($user->country != 'VE' || getGeoip() != 'VE'))
        $error = 'Se ha detectado que usted no esta residenciado en Venezuela, usted solo puede pagar en U$D.';

    if($_GET['id_service'])
        $service = $db->ls("SELECT * FROM api_invoices INNER JOIN api_services ON api_invoices.id_service = api_services.id_service WHERE api_services.id_usuario = %d AND api_services.id_service = %d ORDER BY date_created DESC LIMIT 1", array($user->id, (int)$_GET['id_service']));

    if($service && (!(bool)$service['activo'] || (strtotime($service['proximo_corte'])-time())>=15*24*3600))
        $error = 'El APP que intenta renovar no esta Activo o la fecha de vencimiento es superior a 15 dias.';

    if($service && !$error && $service['currency']!=$_GET['currency'])
        $error = 'Esta intentando pagar con una moneda distinta a la del servicio ya contratado';

    if(!$error)
        $plan_db = $db->ls("SELECT planes.*, precios.* FROM api_planes planes INNER JOIN api_precio_planes precios ON planes.id_plan = precios.id_plan WHERE precios.currency = '%s' AND precios.id_plan = '%d'".(User::isAdmin()?'':' AND planes.activo = 1'), array(secInjection($_GET['currency']), (int)$_GET['id_plan']), true);

    if(!$error && !$plan_db)
        $error = 'El plan seleccionado no existe o no se encuentra activo para la venta en este momento.';

    if(!$error && User::isAdmin()){
        $id_service = false;
        if($service) {
            $id_service = (int)$service['id_service'];
            $db->qs("UPDATE api_services SET activo = 1, id_plan = %d, proximo_corte = '%s' WHERE id_service = %d", array((int)$_GET['id_plan'], date('Y-m-d H:i:s', strtotime('+'.$plan_db['periocidad'].' month', $service?strtotime($service['proximo_corte']):time())), $id_service));
        }else{
            $db->qs("INSERT INTO api_services (id_usuario,id_plan,token,fecha_inicio,proximo_corte, activo) VALUES
                            ('%d','%d','%s',NOW(),'%s', 1)", array
            (
                (int)$user->id,
                (int)$id_plan,
                md5(time(). $user->id . User::$keySecurity),
                date('Y-m-d H:i:s', strtotime('+'.$plan_db['periocidad'].' month', $service?strtotime($service['proximo_corte']):time()))
            ));
            $id_service = (int)$db->id();
        }
        $db->qs("INSERT INTO api_invoices (id_service,currency,amount,payment_name,payment_status,payment_reference,date_created,date_expire,log_payment) VALUES
                        ('%d','%s','%s','superuser','completed','%s',NOW(),'%s','%s')", array
        (
            $id_service,
            secInjection($plan_db['currency']),
            secInjection('0.0'),
            secInjection('N/A'),
            date('Y-m-d H:i:s', strtotime('+'.$plan_db['periocidad'].' month')),
            secInjection(print_r($_POST, true))
        ));
        header('location: login.php');
    }

    $html_title = 'Plataforma de Pago';
    $html_description = 'Plataforma de pago';
    $menu_select = 'login';

    include('header.php');
?>

<?php if($error) echo '<div class="alert alert-danger" role="alert">'.$error.'</div>'; ?>
<?php if($msj) echo '<div class="alert alert-success" role="alert">'.$msj.'</div>'; ?>

<?php if($plan_db) {

    //print_r($plan_db);
    //print_r($user);
?>
    <br /><br /><div class="clearfix"> </div>
    <div class="heading"><span>Datos de la Compra</span></div>
    <div class="col-md-6"><b>Nombre</b></div><div class="col-md-6"><?php echo $user->name; ?></div>
    <div class="col-md-6"><b>Plan Contratado</b></div><div class="col-md-6"><?php echo $plan_db['nombre']; ?></div>
    <div class="col-md-6"><b>Fecha de Vencimiento</b></div><div class="col-md-6"><?php echo date('Y-m-d', strtotime('+'.$plan_db['periocidad'].' month', $service?strtotime($service['proximo_corte']):time())); ?></div>
    <div class="col-md-6"><b>Monto a Pagar</b></div><div class="col-md-6"><?php echo $plan_db['amount']; ?> <?php echo $plan_db['currency']; ?></div>
    <div class="col-md-6"><b>Puede ver Nombre</b></div><div class="col-md-6"><?php echo (bool)$plan_db['show_names']?'Si':'No'; ?></div>
    <div class="col-md-6"><b>Puede ver RIF</b></div><div class="col-md-6"><?php echo (bool)$plan_db['show_rif']?'Si':'No'; ?></div>
    <div class="col-md-6"><b>Numero de Consultas por Hora</b></div><div class="col-md-6"><?php echo $plan_db['max_request_per_hour']; ?></div>
    <br /><br /><div class="clearfix"> </div><br /><br />
    <div class="col-md-12"><center>
    <?php if($plan_db['currency'] == 'USD') { ?>

    <?php if(PAYPAL_SANDBOX) { ?>
    <form name="_xclick" action="https://www.sandbox.paypal.com/cgi-bin/webscr" method="post">
    <?php } else { ?>
    <form name="_xclick" action="https://www.paypal.com/cgi-bin/webscr" method="post">
    <?php } ?>
    <input type="hidden" name="cmd" value="_xclick">
    <input type="hidden" name="business" value="<?php echo PAYPAL_EMAIL; ?>">
    <input type="hidden" name="currency_code" value="<?php echo $plan_db['currency']; ?>">
    <input type="hidden" name="item_number" value="">
    <input type="hidden" name="custom" value="<?php echo $_GET['id_plan']; ?>-<?php echo $user->id; ?><?php echo $service?'-'.$service['id_service']:''; ?>">
    <input type="hidden" name="email" value="<?php echo $user->mail; ?>">
    <input type="hidden" name="first_name" value="<?php echo $user->name; ?>">
    <input type="hidden" name="item_name" value="<?php echo $plan_db['nombre']; ?>">
    <input type="hidden" name="amount" value="<?php echo $plan_db['amount']; ?>">
    <input type="hidden" name="charset" value="utf-8">
    <input type="hidden" name="no_shipping" value="1">
    <input type="hidden" name="notify_url" value="<?php echo str_replace('payment.php', 'ipn_paypal.php', ACTUAL_URL); ?>">
    <input type="hidden" name="return" value="<?php echo str_replace('payment.php', 'login.php', ACTUAL_URL); ?>">
    <input type="hidden" name="cancel_return" value="<?php echo str_replace('payment.php', 'login.php', ACTUAL_URL); ?>">
    <input type="image" src="https://www.paypalobjects.com/webstatic/mktg/logo-center/logotipo_paypal_pagos.png" border="0" name="submit" alt="Pagar">
    </form><?php }else{

        $mp = new MercadoPagoVE(MERCADOPAGO_KEY, MERCADOPAGO_SECRET);

        if (MERCADOPAGO_SANDBOX)
            $mp->sandbox_mode(true);

        $preference_data = array(
            'items' => array(
                array(
                    'id' => $_GET['id_plan'],
                    'title' => $plan_db['nombre'],
                    'quantity' => 1,
                    'currency_id' => $plan_db['currency'],
                    'unit_price' => (float)$plan_db['amount']
                )
            ),
            'back_urls' => array(
                'success'=> str_replace('payment.php', 'login.php', ACTUAL_URL),
                'failure'=> str_replace('payment.php', 'login.php', ACTUAL_URL),
                'pending'=> str_replace('payment.php', 'login.php', ACTUAL_URL)
            ),

            'external_reference' => $_GET['id_plan'].'-'.$user->id.($service?'-'.$service['id_service']:''),
            'payer'=> array(
                'email' => $user->mail,
                'name' =>  $user->name
            )
        );
        $preference_data['payment_methods']['excluded_payment_types'][] = array('id' => 'bank_transfer');
        $preference_data['payment_methods']['excluded_payment_types'][] = array('id' => 'atm');
        $preference_data['payment_methods']['excluded_payment_types'][] = array('id' => 'ticket');
        try
        {
            $preference = $mp->create_preference($preference_data);
        }
        catch(Exception $error)
        {
            echo ('Error conectando a MercadoPago: ').print_r($error, true).print_r($preference_data, true);
        }

        if (MERCADOPAGO_SANDBOX)
            $init_point = $preference['response']['sandbox_init_point'];
        else
            $init_point = $preference['response']['init_point'];
        ?>
        <a href="<?php echo $init_point; ?>" id="botonMP" name="MP-Checkout" class="lightblue-M-Ov-VeOn" mp-mode="modal" onreturn="execute_my_onreturn_ve">
            Pagar
        </a>
        <style>
            #MP-Checkout-dialog {
                z-index: 200000 !important;
            }
        </style>
        <script type="text/javascript" src="https://www.mercadopago.com/org-img/jsapi/mptools/buttons/render.js"></script>
        <script type="text/javascript">
            function execute_my_onreturn_ve(data) {
                console.log(data);
                window.location.href="<?php echo str_replace('payment.php', 'login.php', ACTUAL_URL); ?>?external_reference="+data.external_reference+"&collection_status="+data.collection_status;
            }
        </script>
        <?php
    } ?></center></div>
<div class="clearfix"> </div>
<?php } ?>


<?php
include('footer.php');
