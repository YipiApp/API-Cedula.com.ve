<?php
/**
* Api de Consultas de Cedulas Venezolanas - Modulo de Precios
*
* @author    Kijam Lopez <klopez@cuado.co>
* @copyright 2015 Desarrollos Cuado C.A.
* @license   GPLv3
*/
    include_once ("./lib/classDBAndUser.php");
    $html_title = 'API';
    $html_description = 'Los precios el bolivares sólo aplican para los residentes en Venezuela (I.V.A. incluido), para el resto del mundo aplica tarifa internacional en dolares.';
    $menu_select = 'precios';
    include('header.php');
    
    $planes_db = $db->ls("SELECT planes.*, precios.* FROM api_planes planes INNER JOIN api_precio_planes precios ON planes.id_plan = precios.id_plan".(User::isAdmin()?'':' AND planes.activo = 1'), array(), false);

    $planes = array();
    $planes_precios = array();
    foreach($planes_db as $plan){
        $planes_precios[$plan['id_plan']][$plan['currency']] = $plan;
        $name = preg_split("/ - /",  $plan['nombre']);
        $planes[$plan['id_plan']] = $name[0];
    }
?>
   <div class="plans-head">
       <div class="heading"><span>Precios del API</span></div>
       <div>Los precios marcados con las siglas <b>"Ven."</b> sólo aplican para los residentes en Venezuela (I.V.A. incluido), para el resto del mundo aplica tarifa <b>"Ext."</b></div>

       <div class="heading"></div>
        <?php
        foreach($planes as $id_plan => $plan) {
        ?>
        <div class="col-md-3">
            <div class="pricing-table-grid">
                <h3><?php echo $plan; ?></h3>
                <ul>
                    <li><span>Ven.: <?php echo $planes_precios[$id_plan]['VEF']['amount'] < 0.01?'GRATIS':$planes_precios[$id_plan]['VEF']['amount'].' Bs x '.$planes_precios[$id_plan]['VEF']['periocidad'].' Meses'; ?></span></li>
                    <li><span>Ext.: <?php echo $planes_precios[$id_plan]['USD']['amount'] < 0.01?'GRATIS':$planes_precios[$id_plan]['USD']['amount'].' $ x '.$planes_precios[$id_plan]['USD']['periocidad'].' Meses'; ?></span></li>
                    <li><a href="login.php"><?php echo $planes_precios[$id_plan]['VEF']['max_request_per_hour']; ?> consultas por Hora</a></li>
                    <li><a href="login.php"><?php echo (bool)$planes_precios[$id_plan]['VEF']['show_names']?'':'No '; ?>Incluye Nombre Completo</a></li>
                    <li><a href="login.php"><?php echo (bool)$planes_precios[$id_plan]['VEF']['show_rif']?'':'No '; ?> Incluye RIF</a></li>
                    <li><a href="login.php">99.9% Uptime</a></li>
                </ul>
                <a class="order-btn" href="login.php">Comprar Ya</a>
            </div>
         </div>
        <?php
        }
        ?>
        <div class="clearfix"> </div>
   </div>
<?php
    include('footer.php');
