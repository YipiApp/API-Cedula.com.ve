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
       <div class="heading"><span>Código Fuente del Sistema</div>
       <div>Nuestro sistema está publicado bajo licencia GPLv3 en GitHub, la misma puede ser consultada en <a href="https://github.com/DesarrollosCuado/API-Cedula.com.ve/" target="_blank">GitHub</a>.<br /><br />
       <b>Comunidades relacionadas al Software Libre Venezolano que apoyan este proyecto:</b><br /><br />
       - <a href="http://cnsl.org.ve/" target="_blank">Congreso Nacional de Software Libre</a> <a href="http://es.slideshare.net/KijamLpez/11vo-cnsl-aragua-venezuela-como-ganar-dinero-con-software-libre" target="_blank">[Ver Ponencia]</a><br />
       - <a href="https://telegram.me/VenezuelaTG" target="_blank">[DEV] Telegram Venezuela</a><br />
       - <a href="https://cuado.co/" target="_blank">Desarrollos Cuado C.A.</a><br /><br />
       </div>
       <div class="heading"><span>Precios del API</span></div>
       <div>Los costos para acceder al API son debido al mantenimiento que origina el sitio web y el soporte técnico del sistema, pues la información contenida en nuestra base de datos fue obtenida de forma pública y gratuita. La data presentada actualmente por nuestro sistema, es producto de una fuerte investigación en Internet y recopilación de datos. En aproximadamente dos meses de esfuerzo, logramos hacer el primer API Venezolano que presta este tipo de servicio, el cual puede ser copiado y distribuido libremente bajo licencia GPL. Los precios marcados con las siglas <b>"Ven."</b> sólo aplican para los residentes en Venezuela (I.V.A. incluido), para el resto del mundo aplica tarifa <b>"Ext."</b></div>
       
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
                <a class="order-btn" href="login.php">Contratalo Ya!</a>
            </div>
         </div>
        <?php
        }
        ?>
        <div class="clearfix"> </div>
   </div>
<?php
    include('footer.php');
