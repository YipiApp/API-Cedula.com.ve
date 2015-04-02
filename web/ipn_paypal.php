<?php
/**
* Api de Consultas de Cedulas Venezolanas - IPN Paypal
*
* @author    Kijam Lopez <klopez@cuado.co>
* @copyright 2015 Kijam.com
* @license   GPLv3
*/
    include_once ("./lib/classDBAndUser.php");

    $verify_url = false;
    if (PAYPAL_SANDBOX)
        $verify_url = 'https://www.sandbox.paypal.com/cgi-bin/webscr?cmd=_notify-validate&' . http_build_query( $_POST );
    else
        $verify_url = 'https://www.paypal.com/cgi-bin/webscr?cmd=_notify-validate&' . http_build_query( $_POST );

    if (file_get_contents($verify_url) == 'VERIFIED') {
		email(ADMIN_EMAIL, 'IPN Paypal', print_r($_POST, true));
        if(isset($_POST['custom']) && (PAYPAL_SANDBOX || !((bool)$_POST['test_ipn'])) && $_POST['receiver_email'] == PAYPAL_EMAIL) {
            $custom = preg_split('/-/', $_POST['custom']);
            $id_usuario  = (int)$custom[1];
            $id_plan  = (int)$custom[0];
            $id_service = isset($custom[2])?(int)$custom[2]:false;
            $invoice = false;
            $service = false;
            $ref_id = $_POST['txn_id'];

            if(strlen($ref_id)>0) {
                $invoice = $db->ls("SELECT * FROM api_invoices WHERE payment_name = 'paypal' AND payment_reference = '%s'", array(secInjection($ref_id)), true);
                if($invoice)
                    $id_service = (int)$invoice['id_service'];
            }
            if($id_service>0)
                $service = $db->ls("SELECT * FROM api_services WHERE id_service = %d", array($id_service), true);

            $user = User::getUserByID($id_usuario);
            $plan_db = $user?$db->ls("SELECT planes.*, precios.* FROM api_planes planes INNER JOIN api_precio_planes precios ON planes.id_plan = precios.id_plan WHERE precios.currency = '%s' AND precios.id_plan = '%d'".($user->rol==1?'':' AND planes.activo = 1'), array(secInjection($_POST['mc_currency']), (int)$id_plan), true):false;

            if(!$plan_db || !$user || abs(((float)$plan_db['amount'] - (float)$_POST['mc_gross']))>0.1){
                email($user->mail, 'Ocurrio un error en su Pago', 'Su pedido no pudo ser procesado por un error en el monto pagado y el costo real del plan, porfavor contactenos si esto es un error');
            }else if($_POST['payment_status'] == 'Completed') {
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
                                    ('%d','%s','%s','paypal','completed','%s',NOW(),'%s','%s')", array
                    (
                        $id_service,
                        secInjection($_POST['mc_currency']),
                        secInjection($_POST['mc_gross']),
                        secInjection($ref_id),
                        date('Y-m-d H:i:s', strtotime('+'.$plan_db['periocidad'].' month')),
                        secInjection(print_r($_POST, true))
                    ));
                }else{
                    $db->qs("UPDATE api_invoices SET payment_status = 'completed', log_payment = '%s' WHERE id_invoice = %d", array(secInjection(print_r($_POST, true)), $invoice['id_invoice']));
                }
            }else if(
            $_POST['payment_status'] == 'Pending' ||
            $_POST['payment_status'] == 'Processed' ||
            $_POST['payment_status'] == 'In-Progress'
            ) {
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
                                    ('%d','%s','%s','paypal','pending','%s',NOW(),'%s','%s')", array
                    (
                        $id_service,
                        secInjection($_POST['mc_currency']),
                        secInjection($_POST['mc_gross']),
                        secInjection($ref_id),
                        date('Y-m-d H:i:s', strtotime('+'.$plan_db['periocidad'].' month')),
                        secInjection(print_r($_POST, true))
                    ));
                }else{
                    $db->qs("UPDATE api_invoices SET payment_status = 'pending', log_payment = '%s' WHERE id_invoice = %d", array(secInjection(print_r($_POST, true)), $invoice['id_invoice']));
                }
            }else {
                email($user->mail, 'Error en el Pago', 'Su pago fue rechazado por paypal (Error: '.$_POST['payment_status'].'), ingrese a "Mi Cuenta" en http://cedula.com.ve/ y vuelva a realizar su pedido para reintentar la compra.');
                if($invoice)
                    $db->qs("UPDATE api_invoices SET payment_status = 'rejected', log_payment = '%s' WHERE id_invoice = %d", array(secInjection(print_r($_POST, true)), $invoice['id_invoice']));
            }
        }
    }