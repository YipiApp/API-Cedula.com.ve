<?php
/**
* Api de Consultas de Cedulas Venezolanas - Modulo de CronJob
*
* @author    Kijam Lopez <klopez@cuado.co>
* @copyright 2015 Desarrollos Cuado C.A.
* @license   GPLv3
*/
    include_once ("./lib/classDBAndUser.php");

    if(!isset($_GET['key']) || $_GET['key'] != SEMILLA_CRONJOB)
        die('cronjob not run!...');

    function sendNotify($d) {
        global $db;
        email($d['mail'], 'Notificación de Vencimiento', 'Su APP vencera el '.$d['proximo_corte'].', si no desea que su servicio sea suspendido porfavor renuevalo en el siguiente link: http://cedula.com.ve/web/login.php');
        $db->qs("UPDATE api_services SET last_remember = NOW() WHERE id_service = %d", array($d['id_service']));
    }

    $notify = $db->ls("SELECT * FROM api_services
        INNER JOIN api_usuarios ON api_usuarios.id_usuario = api_services.id_usuario
        WHERE activo = 1
        AND proximo_corte < DATE_ADD(NOW(),INTERVAL 14 DAY)
        AND last_remember < DATE_SUB(NOW(),INTERVAL 2 DAY)
        LIMIT 30
        ", array(), false);

    if($notify)
        foreach($notify as $n)
            sendNotify($n);

    $notify = $db->ls("SELECT * FROM api_services
        WHERE activo = 1
        AND proximo_corte < DATE_ADD(NOW(),INTERVAL 7 DAY)
        AND last_remember < DATE_SUB(NOW(),INTERVAL 2 DAY)
        LIMIT 30
        ", array(), false);

    if($notify)
        foreach($notify as $n)
            sendNotify($n);


    $notify = $db->ls("SELECT * FROM api_services
        WHERE activo = 1
        AND proximo_corte < DATE_ADD(NOW(),INTERVAL 2 DAY)
        AND last_remember < DATE_SUB(NOW(),INTERVAL 2 DAY)
        LIMIT 30
        ", array(), false);

    if($notify)
        foreach($notify as $n)
            sendNotify($n);


    $mp_pending = $db->ls("SELECT * FROM api_invoices
        WHERE
            payment_status = 'pending'
        AND
            payment_name = 'mercadopago-ve'
        LIMIT 30
        ", array(), false);

    if($mp_pending)
        foreach($mp_pending as $invoice) {
            $result = validateMercadoPago($invoice['payment_reference']);
            if(isset($result['order_id']) && isset($result['status']) && $result['status'] != 'Pending') {
                $custom = preg_split('/-/', $result['order_id']);
                $id_plan  = (int)$custom[0];
                $id_service = (int)$invoice['id_service'];
                $service = $db->ls("SELECT * FROM api_services WHERE id_service = %d", array($id_service), true);
                $user = User::getUserByID($service['id_usuario']);
                $plan_db = $user?$db->ls("SELECT planes.*, precios.* FROM api_planes planes INNER JOIN api_precio_planes precios ON planes.id_plan = precios.id_plan WHERE precios.currency = '%s' AND precios.id_plan = '%d'".($user->rol==1?'':' AND planes.activo = 1'), array(secInjection($result['currency']), (int)$id_plan), true):false;

                if($result['status'] == 'Completed'){
                    email($user->mail, 'Pago Aceptado', 'Su pago fue aceptado y ya fue creado su APP para poder disfrutar de nuestro servicios, ingrese a "Mi Cuenta" en http://cedula.com.ve/ para mayor información.');
                    $db->qs("UPDATE api_services SET activo = 1, proximo_corte = '%s' WHERE id_service = %d", array($id_plan, date('Y-m-d H:i:s', strtotime('+'.$plan_db['periocidad'].' month', strtotime($service['proximo_corte']))), $id_service));
                    $db->qs("UPDATE api_invoices SET payment_status = 'completed', log_payment = '%s' WHERE id_invoice = %d", array(secInjection($result['message']), $invoice['id_invoice']));
                }else{
                    email($user->mail, 'Error en el Pago', 'Su pago fue rechazado por Mercadopago, ingrese a "Mi Cuenta" en http://cedula.com.ve/ y vuelva a realizar su pedido para reintentar la compra.');
                    $db->qs("UPDATE api_invoices SET payment_status = 'rejected', log_payment = '%s' WHERE id_invoice = %d", array(secInjection($result['message']), $invoice['id_invoice']));
                }
            }
        }