<?php

    define('SEMILLA_USUARIOS','CAMBIAR_ESTO_A_CARACTERES_RANDOMS');
    define('SEMILLA_NEW_USER','CAMBIAR_ESTO_A_CARACTERES_RANDOMS');
    define('SEMILLA_CRONJOB','CAMBIAR_ESTO_A_CARACTERES_RANDOMS'); 

    define('ADMIN_EMAIL','tu@correo.com');

    define('DB_HOST','');
    define('DB_USER','');
    define('DB_PASS','');
    define('DB_NAME','');

    define('MERCADOPAGO_SANDBOX', false);

    // Obtener aqui: https://www.mercadopago.com/mlv/herramientas/aplicaciones
    define('MERCADOPAGO_KEY','');
    define('MERCADOPAGO_SECRET','');
    // Debe colocar el URL del ipn_mp.php en https://www.mercadopago.com/mlv/herramientas/notificaciones

    define('PAYPAL_SANDBOX', false);
    define('PAYPAL_EMAIL','');

    define('RECAPTCHAR_KEY', '');
    define('RECAPTCHAR_SECRET', '');

    define('API_CEDULA_SERVER', 'https://cuado.co:444');
    define('API_CEDULA_APP_ID', '');
    define('API_CEDULA_APP_TOKEN', '');

    define('GOOGLE_ANALYTICS_KEY', 'UA-XXXXXX-X');
    
    define('SERVER_GEOIP', 'http://freegeoip.net/json/');
    
    //E-Mail Sender: php, smtp
    define('EMAIL_SENDER', 'php');
    define('EMAIL_FROM_MAIL', 'no-reply@'.$_SERVER['SERVER_NAME']);
    define('EMAIL_FROM_NAME', 'API-Cedula');
    
    //Configuracion de SMTP
    define('EMAIL_SMTP_AUTH', false);
    define('EMAIL_SMTP_USER', '');
    define('EMAIL_SMTP_PASS', '');
    define('EMAIL_SMTP_HOST', '');
    define('EMAIL_SMTP_PORT', 25);
    define('EMAIL_SMTP_CRYPT', false); // false, 'tls' o 'ssl'
    
    
