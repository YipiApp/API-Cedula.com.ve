<?php
/**
* Api de Consultas de Cedulas Venezolanas - Index
*
* @author    Kijam Lopez <klopez@cuado.co>
* @copyright 2015 Desarrollos Cuado C.A.
* @license   GPLv3
*/
    include_once ("./lib/classDBAndUser.php");
    $html_title = 'API';
    $html_description = 'API de Consulta de Cédulas Venezolanas. Ya no tendrá que transcribir todos los nombres de las personas que compren en su tienda, con sólo consultar este API podrá tener todos los datos del cliente. Agilice sus ventas, ahorre tiempo y disminuya las colas de las cajas.';
    $html_tags_header = '
    <script src="js/jquery.magnific-popup.js" type="text/javascript"></script>
    <link href="css/popup.css" rel="stylesheet" type="text/css">
    <script>
        $(document).ready(function() {
            $(".popup-with-zoom-anim").magnificPopup({
                type: "inline",
                fixedContentPos: false,
                fixedBgPos: true,
                overflowY: "auto",
                closeBtnInside: true,
                preloader: false,
                midClick: true,
                removalDelay: 300,
                mainClass: "my-mfp-zoom-in"
        });
    });
    </script>';
    $menu_select = 'index';
    $html_show_body = false;
    include('header.php');
?>
<div class="header">
   <div class="container">
      <div class="header_top">
          <div class="header-left">
             <div class="logo" onclick="window.location.href='index.html'">
                <img src="images/logo-cedula2.png" alt=""/>
                <div class="textLogo">Cédula</div>
             </div>
             <div class="menu">
                  <a class="toggleMenu" href="#"><img src="images/nav.png" alt="" /></a>
                    <ul class="nav" id="nav">
                        <li class="active"><a href="index.php">API</a></li>
                        <li><a href="precios.php">Precios</a></li>
                        <li><a href="login.php">Registro / Login</a></li>
                        <div class="clearfix"></div>
                    </ul>
                    <script type="text/javascript" src="js/responsive-nav.js"></script>
            </div>
            <div class="clearfix"></div>
          </div>

          <ul class="phone">
              <li><i style="color:white" class="glyphicon glyphicon-info-sign"> </i></li>
              <li><p style="color:white">+58 (416) 835-1191</p></li>
              <li><p style="color:white">soporte@cedula.com.ve</p></li>
          </ul>
          <div class="clearfix"> </div>
       </div>
       <div class="header_bottom">
             <h1 class="m_head rollIn">API de Consulta de Cédulas Venezolanas<br> Ideal para tu Negocio o Punto de Venta</h1>
             <div class="video_buttons">
                 <div class="video_right">
                           <a class="fa-btn btn-1 btn-1e popup-with-zoom-anim" href="#small-dialog">
                            <p class="video_desc">Pruébalo Gratis!</p>
                        </a>
                           <a class="fa-btn btn-1 btn-1e" href="http://wiki.cedula.com.ve/" target="_blank">
                            <p class="video_desc">Documentación</p>
                        </a>
                        <div id="small-dialog" class="mfp-hide">
                        <iframe width="560" height="450" src="test.php" frameborder="0" allowfullscreen></iframe>
                      </div>
              </div>
              <div class="clearfix"></div>
         </div>
       </div>
       <div class="clearfix"></div>
    </div>
</div>
<div class="main">
     <div class="content_top">
         <div class="container">
            <div class="wmuSlider example1">
               <div class="wmuSliderWrapper">
                   <article style="position: absolute; width: 100%; opacity: 0;">
                        <div class="banner-wrap">
                            <h2>Ideal para su Negocio o Punto de Venta</h2>
                            <p>Ya no tendrá que transcribir todos los nombres de las personas que compren</p>
                            <p>en su tienda, con sólo consultar este API podrá tener todos los datos del cliente.</p>
                            <p>Agilice sus ventas, ahorre tiempo y disminuya las colas de las cajas. </p>
                          <h3><span class="m_1">Garantizamos 99.9% de uptime.</span></h3>
                        </div>
                    </article>
                    <article style="position: relative; width: 100%; opacity: 1;">
                          <div class="banner-wrap">
                            <h2>Consulta de RIF</h2>
                            <p>Podrá saber el RIF de las personas, aún cuando esta no se haya registrado en el Seniat y no lo tenga todavía.</p>
                        </div>
                    </article>
                    <article style="position: relative; width: 100%; opacity: 1;">
                          <div class="banner-wrap">
                            <h2>Sistema de distribución gratuita bajo GPLv3</h2>
                            <p>Nuestro sistema puede ser copiado y distribuido totalmente Gratis bajo la licencia GPLv3</p>
                            <p>Pude obtenerlo libremente desde nuestro repositorio en <a href="https://github.com/DesarrollosCuado/API-Cedula.com.ve" target="_blank">GitHub</a></p>
                          <h3><span class="m_1">Cuado C.A. Siempre contribuira al progreso del Pais.</span></h3>
                        </div>
                    </article>
                 </div>
                <a class="wmuSliderPrev">Anterior</a><a class="wmuSliderNext">Siguiente</a>
            </div>
            <script src="js/jquery.wmuSlider.js"></script>
              <script>
                   $('.example1').wmuSlider();
                </script>
       </div>
     </div>
     <div class="content_bottom rollIn">
       <div class="container">
           <h2>Sistema Protegido y Seguro</h2>
           <p>Contamos con un API seguro y confiable, evitando DDoS y abusos del sistema. Garantizando 99.9% de uptime y accesible desde cualquier parte del mundo.</p>
           <div class="grid_1 text-center">
                <div class="col-md-4 span_1">
                    <img src="images/sharing.jpg" class="img-responsive" alt=""/>
                    <h3>API Accessible desde cualquier parte del Mundo</h3>
               </div>
               <div class="col-md-4 span_1">
                    <img src="images/shield.jpg" class="img-responsive" alt=""/>
                    <h3>Seguridad de Acceso al API</h3>
                </div>
                <div class="col-md-4 span_1">
                    <img src="images/access.jpg" class="img-responsive" alt=""/>
                    <h3>Panel de Control - Consultas y Pagos</h3>
                </div>
                <div class="clearfix"> </div>
           </div>
       </div>
     </div>
<?php
    include('footer.php');