<?php
/**
* Api de Consultas de Cedulas Venezolanas - Modulo de App Móvil
*
* @author    Kijam Lopez <klopez@cuado.co>
* @copyright 2015 Desarrollos Cuado C.A.
* @license   GPLv3
*/

    include_once ("./lib/classDBAndUser.php");
    $html_title = 'App Móvil Android e iOS';
    $html_description = 'Consulta de Cedulas Venezolanas desde tu Telefono Móvil Android e iOS';
    $menu_select = 'app';
    include('header.php');
?>
<div class="clearfix"> </div>
<br />
<br />
<div class="clearfix"> </div>
<div class="container">
    <div class="heading"><span>Buscanos en Google Play Store</span></div>
    <div class="col-md-12">
        <center><a href="https://play.google.com/store/apps/details?id=com.kijam.consultadecedulavenezolana" target="_blank"><img width="267" height="100" src="/web/images/get-play.png" /></a></center>
        <div class="clearfix"> </div><br />
    </div>
    <div class="clearfix"> </div><br />
    <div class="heading"><span>Buscanos en Telegram</span></div>
    <div class="col-md-12">
        <div class="col-md-4"><b>1) Descargar e Instalar Telegram:</b></div><div class="col-md-7"><a href="https://telegram.org/dl/ios" target="_blank">iPhone/iPad</a> / <a href="https://telegram.org/dl/android" target="_blank">Android</a> / <a href="https://telegram.org/dl/wp" target="_blank">Windows Phone</a> / <a href="https://web.telegram.org/" target="_blank">Desktop Linux/Windows/Mac</a></div>
        <div class="clearfix"> </div><br />
        <div class="col-md-4"><b>2) Agregar nuestro Bot a tus contactos:</b></div><div class="col-md-7"><a href="http://telegram.me/CedulaBot">@CedulaBot</a></div>
        <div class="clearfix"> </div><br />
        <div class="col-md-4"><b>3.1) Escribele un mensaje con el siguiente formato:</b></div><div class="col-md-7">/cedula [numero_de_cedula], ejemplo: /cedula 11222333 y respondera:</div>
        <div class="clearfix"> </div><br /><center><img src="/web/images/ejemplo-telegram.png" /></center><br /><br /><br />
        <div class="col-md-4"><b>3.2) Tambien puedes usarlo mencionandolo en tus Chats o Grupos:</b></div><div class="col-md-7">Escribe en tu chat: @CedulaBot [numero_de_cedula], ejemplo:</div>
        <div class="clearfix"> </div><br /><center><img src="/web/images/ejemplo-telegram2.png" /></center><br />
         <div class="clearfix"> </div><br /><center><em>*Nota: El bot tiene un limite diario de 50 consultas por usuario.<br />
    </div>

</div>
<?php
    include('footer.php');
