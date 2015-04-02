<?php
/**
* Api de Consultas de Cedulas Venezolanas - IPN MercadoPago
*
* @author    Kijam Lopez <klopez@cuado.co>
* @copyright 2015 Kijam.com
* @license   GPLv3
*/

    include_once ("./lib/classDBAndUser.php");
    include_once ("./lib/mercadopagove.php");
    $mp_op_id = $_REQUEST['id'];
    if($mp_op_id) {
        $result = validateMercadoPago($mp_op_id);

        email(ADMIN_EMAIL, 'IPN Mercadopago', print_r($_POST, true).print_r($result, true));

        $custom = preg_split('/-/', $result['order_id']);

        $id_usuario  = (int)$custom[1];
        $id_plan  = (int)$custom[0];
        $id_service = isset($custom[2])?(int)$custom[2]:false;
        $invoice = false;
        $service = false;
        $ref_id = $mp_op_id;

        if(strlen($ref_id)>0) {
            $invoice = $db->ls("SELECT * FROM api_invoices WHERE payment_name = 'mercadopago-ve' AND payment_reference = '%s'", array(secInjection($ref_id)), true);
            if($invoice)
                $id_service = (int)$invoice['id_service'];
        }

        if($id_service>0)
            $service = $db->ls("SELECT * FROM api_services WHERE id_service = %d", array($id_service), true);

        $user = User::getUserByID($id_usuario);
        $plan_db = $user?$db->ls("SELECT planes.*, precios.* FROM api_planes planes INNER JOIN api_precio_planes precios ON planes.id_plan = precios.id_plan WHERE precios.currency = '%s' AND precios.id_plan = '%d'".($user->rol==1?'':' AND planes.activo = 1'), array(secInjection($result['currency']), (int)$id_plan), true):false;

        if(!$plan_db || !$user || abs(((float)$plan_db['amount'] - (float)$result['price']))>0.1){
            email($user->mail, 'Ocurrio un error en su Pago', 'Su pedido no pudo ser procesado por un error en el monto pagado y el costo real del plan, porfavor contactenos si esto es un error');
        }else if($result['status'] == 'Completed') {
            email($user->mail, 'Pago Aceptado', 'Su pago fue aceptado y ya fue creado su APP para poder disfrutar de nuestro servicios, ingrese a "Mi Cuenta" en http://cedula.com.ve/ para mayor información.');
            if($id_service) {
                $db->qs("UPDATE api_services SET activo = 1, id_plan = %d, proximo_corte = '%s' WHERE id_service = %d", array($id_plan, date('Y-m-d H:i:s', strtotime('+'.$plan_db['periocidad'].' month', $service?strtotime($service['proximo_corte']):time())), $id_service));
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
            if(!$invoice){
                $db->qs("INSERT INTO api_invoices (id_service,currency,amount,payment_name,payment_status,payment_reference,date_created,date_expire,log_payment) VALUES
                                ('%d','%s','%s','mercadopago-ve','completed','%s',NOW(),'%s','%s')", array
                (
                    $id_service,
                    secInjection($result['currency']),
                    secInjection($result['price']),
                    secInjection($ref_id),
                    date('Y-m-d H:i:s', strtotime('+'.$plan_db['periocidad'].' month')),
                    secInjection($result['message'])
                ));
            }else{
                $db->qs("UPDATE api_invoices SET payment_status = 'completed', log_payment = '%s' WHERE id_invoice = %d", array(secInjection($result['message']), $invoice['id_invoice']));
            }
        }else if($result['status'] == 'Pending') {
            if(!$id_service) {
                email($user->mail, 'Su Pago quedo Pendiente', 'Su pago tiene un estado de "Pendiente", cuando se procese el pago su APP pasara de "Suspendido" a "Activo" y podra disfrutar del servicio.');
                $db->qs("INSERT INTO api_services (id_usuario,id_plan,token,fecha_inicio,proximo_corte, activo) VALUES
                                ('%d', '%d', '%s', NOW(), NOW(), 0)", array
                (
                    (int)$user->id,
                    (int)$id_plan,
                    md5(time(). $user->id . User::$keySecurity)
                ));
                $id_service = (int)$db->id();
            }
            if(!$invoice){
                $db->qs("INSERT INTO api_invoices (id_service,currency,amount,payment_name,payment_status,payment_reference,date_created,date_expire,log_payment) VALUES
                                ('%d','%s','%s','mercadopago-ve','pending','%s',NOW(),'%s','%s')", array
                (
                    $id_service,
                    secInjection($_POST['mc_currency']),
                    secInjection($_POST['mc_gross']),
                    secInjection($ref_id),
                    date('Y-m-d H:i:s', strtotime('+'.$plan_db['periocidad'].' month')),
                    secInjection($result['message'])
                ));
            }else{
                $db->qs("UPDATE api_invoices SET payment_status = 'pending', log_payment = '%s' WHERE id_invoice = %d", array(secInjection($result['message']), $invoice['id_invoice']));
            }
        }else {
            email($user->mail, 'Error en el Pago', 'Su pago fue rechazado por Mercadopago, ingrese a "Mi Cuenta" en http://cedula.com.ve/ y vuelva a realizar su pedido para reintentar la compra.');

            if($invoice)
                $db->qs("UPDATE api_invoices SET payment_status = 'rejected', log_payment = '%s' WHERE id_invoice = %d", array(secInjection($result['message']), $invoice['id_invoice']));
        }
    }