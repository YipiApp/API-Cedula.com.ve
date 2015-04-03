<?php
/**
* Api de Consultas de Cedulas Venezolanas
* - Consulta de Base de datos
* - Manejo de Usuarios
* - Funciones Utilitarias
*
* @author    Kijam Lopez <klopez@cuado.co>
* @copyright 2015 Desarrollos Cuado C.A.
* @license   GPLv3
*/
    //error_reporting(0);
    error_reporting(E_ERROR | E_WARNING | E_PARSE);
    if(isset($_POST['PHPSESSID'])) session_id($_POST['PHPSESSID']);
    if(isset($_GET['PHPSESSID'])) session_id($_GET['PHPSESSID']);
    session_start();

    define("E_USER_EXIST",1);
    define("E_MAIL_EXIST",2);
    define("E_SQL_ERROR",3);
    define("E_FORMAT_INVALID",4);
    define("E_USER_NOT_EXIST",5);
    define("E_USER_ADMIN_NOT_DELETE",6);
    define("OK",1000);

    define("PATH",str_replace('//','/',dirname(__FILE__)));

    include(PATH.'/settings.php');

    $uri = preg_split('/\?/', $_SERVER['REQUEST_URI']);
    define("ACTUAL_URL", sprintf('%s://%s%s',
                        isset($_SERVER['HTTPS']) ? 'https' : 'http',
                        $_SERVER['HTTP_HOST'],
                        $uri[0]
                    ));


    function dError($err)
    {
        switch($err){
           case E_USER_EXIST:               return "User already registered";
           case E_MAIL_EXIST:               return "E-Mail already registered";
           case E_FORMAT_INVALID:           return "The variables are invalid";
           case E_SQL_ERROR:                return "Error Database";
           case E_USER_NOT_EXIST:           return "The user does not exist";
           case E_USER_ADMIN_NOT_DELETE:    return "The super administrator can not be eliminated";

           default:                         return "Unknown error";
        }
    }

    function isError($err){
        if(is_bool($err)) return !$err;
        if($err==OK) return false;
        return true;
    }

    class DBEasyCommands
    {
        private $link;
        private $list_error;
        function __construct()
        {
            $this->list_error=array();
            $this->link=@mysql_connect(DB_HOST, DB_USER, DB_PASS);
            if (mysql_error($this->link))
                $this->list_error[]=mysql_error($this->link);
            else
            {
                @mysql_selectdb(DB_NAME, $this->link);
                if (mysql_error($this->link))
                    $this->list_error[]=mysql_error($this->link);
                else
                    mysql_set_charset('utf8',$this->link);
            }
        }
        function __destruct()
        {
            if(count($this->list_error)>0){
                print_r($this->list_error);
                email(ADMIN_EMAIL, 'DB Error', print_r($this->list_error, true));
            }
            @mysql_close ($this->link);
        }
        function q($query)
        {
            $r=@mysql_query($query, $this->link);
            if (mysql_error($this->link))
            {
                $this->list_error[]=mysql_error($this->link);
                //print_r(mysql_error($this->link));
                return false;
            }
            else
                return $r;
        }
        function pid($tabla){
            $a=$this->q("SHOW TABLE STATUS LIKE '".secInjection($tabla)."';");
            $assoc=mysql_fetch_assoc($a);
            return $assoc['Auto_increment'];
        }
        function id()
        {
            return @mysql_insert_id($this->link);
        }
        function qs($query, $params)
        {
            $res=@call_user_func_array("sprintf",@array_merge(array($query), $params));
            //echo $res;
            if ($res)
                if ($r=$this->q($res))
                    return $r;
            return false;
        }
        function l($query, $unique = true)
        {
            if ($unique)
                return @mysql_fetch_assoc($this->q($query));
            else
            {
                $ret=array();
                $q=$this->q($query);
                while ($r=@mysql_fetch_assoc($q))
                    $ret[]=$r;
                return $ret;
            }
        }
        function ls($query, $params, $unique = true)
        {
            if ($unique)
                return @mysql_fetch_assoc($this->qs($query, $params));
            else
            {
                $ret=array();
                $q=$this->qs($query, $params);
                while ($r=@mysql_fetch_assoc($q))
                {
                    $ret[]=$r;
                }
                return $ret;
            }
        }
        function n($query) {
            return @mysql_num_rows($this->q($query));
        }
    }

    $db=new DBEasyCommands();

    class CUser
    {
        public $id;
        public $rol;
        public $user;
        public $pass;
        public $name;
        public $mail;
        public $vat;
        public $address;
        public $phone;
        public $country;
    }

    class User
    {
        static public $keySecurity = SEMILLA_USUARIOS;

        static function getUserByID($id)
        {
            global $db;

            if ($r=$db->ls("SELECT * FROM api_usuarios WHERE id_usuario=%d", array(intval($id))))
            {
                $us      =new CUser();
                $us->id  =$r["id_usuario"];
                $us->rol =$r["rol"];
                $us->user=$r["user"];
                $us->pass=$r["pass"];
                $us->name=$r["name"];
                $us->mail=$r["mail"];
                $us->vat=$r["vat"];
                $us->address=$r["address"];
                $us->country=$r["country"];
                $us->phone=$r["phone"];
                return $us;
            }

            return false;
        }

        static function getUserByUsername($us)
        {
            global $db;

            if ($r=$db->ls("SELECT * FROM api_usuarios WHERE user='%s'", array(strtolower(secInjection($us)))))
            {
                $us      =new CUser();
                $us->id  =$r["id_usuario"];
                $us->rol =$r["rol"];
                $us->user=$r["user"];
                $us->pass=$r["pass"];
                $us->name=$r["name"];
                $us->mail=$r["mail"];
                $us->vat=$r["vat"];
                $us->address=$r["address"];
                $us->country=$r["country"];
                $us->phone=$r["phone"];
                return $us;
            }

            return false;
        }

        static function getUserByMail($us)
        {
            global $db;

            if ($r=$db->ls("SELECT * FROM api_usuarios WHERE mail='%s'", array(strtolower(secInjection($us)))))
            {
                $us      =new CUser();
                $us->id  =$r["id_usuario"];
                $us->rol =$r["rol"];
                $us->user=$r["user"];
                $us->pass=$r["pass"];
                $us->name=$r["name"];
                $us->mail=$r["mail"];
                $us->vat=$r["vat"];
                $us->address=$r["address"];
                $us->country=$r["country"];
                $us->phone=$r["phone"];
                return $us;
            }

            return false;
        }

        static function validpass($user, $pass)
        {

            if (($us=User::getUserByUsername($user)) && $us->rol < 3)
            {
                if (md5($pass . strtolower($user) . User::$keySecurity) == $us->pass)
                    return true;
            }

            return false;
        }

        static function authSession()
        {
            $isAuth = false;

            if (!isset($_SESSION['id_usuario']) || intval($_SESSION['id_usuario']) <1)
                return false;

            $_SESSION['user'] = User::getUserByID(intval($_SESSION['id_usuario']));

            if ($_SESSION['user'] instanceof CUser)
            {
                $t1=User::getUserByID($_SESSION['user']->id);
                $t2=User::getUserByUsername($_SESSION['user']->user);
                $t3=User::getUserByMail($_SESSION['user']->mail);

                if ($t1 instanceof CUser && $t2 instanceof CUser && $t3 instanceof CUser)
                {
                    $isAuth=     $t1->id==$t2->id &&
                                $t1->id == $t3->id &&
                                $t1->id == $_SESSION['user']->id &&
                                $t1->pass == $t2->pass &&
                                $t1->pass == $t3->pass &&
                                $t1->pass == $_SESSION['user']->pass;
                }
            }
            else
                $isAuth=false;

            return $isAuth;
        }

        static function generateRandomPassword($length)
        {
            $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ-_';
            $random_string = '';
            for ($i = 0; $i < $length; $i++)
                $random_string .= $characters[rand(0, strlen($characters) - 1)];

            return $random_string;
        }

        static function login($user, $pass)
        {
            if (User::validpass($user, $pass))
            {
                unset ($_SESSION['user']);
                $_SESSION['user'] = User::getUserByUsername($user);
                $_SESSION['id_usuario'] = $_SESSION['user']->id;
                return $_SESSION['user'] instanceof CUser;
            }

            return false;
        }

        static function isAdmin() { return User::authSession() && $_SESSION['user']->rol == 1; }

        static function logOut() { unset ($_SESSION['user']); unset ($_SESSION['id_usuario']); }

        static function addUser($us)
        {
            global $db;

            if (is_array($us))
            {
                $t      = new CUser();
                $t->user = $us['user'];
                $t->mail = $us['mail'];
                $t->pass = $us['pass'];
                $t->name = $us['name'];
                $t->rol = $us['rol'];
                $t->vat = $us["vat"];
                $t->address = $us["address"];
                $t->country = $us["country"];
                $t->phone = $us["phone"];
                $us     = $t;
            }
            if ($us instanceof CUser)
            {
                if (User::getUserByUsername(($us->user)))
                    return E_USER_EXIST;

                if (User::getUserByMail(strtolower($us->mail)))
                    return E_MAIL_EXIST;

                if ($db->qs("INSERT INTO api_usuarios (user,pass,mail,name,rol,vat,address,country,phone) VALUES ('%s','%s','%s','%s','%d','%s','%s','%s','%s')", array
                (
                strtolower(secInjection($us->user)),
                md5($us->pass . strtolower($us->user) . User::$keySecurity),
                strtolower(secInjection($us->mail)),
                secInjection($us->name),
                intval($us->rol),
                secInjection($us->vat),
                secInjection($us->address),
                secInjection($us->country),
                secInjection($us->phone)
                )))
                    return OK;
                else
                    return E_SQL_ERROR;
            }

            return E_FORMAT_INVALID;
        }

        static function updateUser($id, $rol, $mail, $pass = null)
        {
            global $db;
            if ($r=User::getUserByID(intval($id)))
            {
                if ($r2=$db->ls("SELECT * FROM api_usuarios WHERE mail='%s' and id_usuario<>%d", array(strtolower(secInjection($mail)),intval($id))))
                {
                    return E_MAIL_EXIST;
                }else
                if ($pass == null)
                    return $db->qs("UPDATE api_usuarios SET rol = '%d', mail = '%s' WHERE id_usuario=%d;", array
                    (
                    intval($rol),
                    strtolower(secInjection($mail)),
                    intval($id)
                    ));
                else
                    return $db->qs("UPDATE api_usuarios SET rol = '%d', mail = '%s', pass = '%s' WHERE id_usuario=%d;", array
                    (
                    intval($rol),
                    strtolower(secInjection($mail)),
                    md5($pass . $r->user . User::$keySecurity),
                    intval($id)
                    ));
            }
            return E_USER_NOT_EXIST;
        }

        static function updateUserData($id, $name, $vat, $address, $country, $phone)
        {
            global $db;
            if ($r=User::getUserByID(intval($id)))
            {
                    return $db->qs("UPDATE api_usuarios SET name = '%s', vat = '%s', address = '%s', country = '%s', phone = '%s' WHERE id_usuario=%d;", array
                    (
                        secInjection($name),
                        secInjection($vat),
                        secInjection($address),
                        secInjection($country),
                        secInjection($phone),
                        intval($id)
                    ));
            }
            return E_USER_NOT_EXIST;
        }

        static function deleteUser($id)
        {
            global $db;

            if ($r=User::getUserByID(intval($id)))
            {
                if ($r->user != 'admin')
                    return $db->qs("DELETE FROM api_usuarios WHERE id_usuario=%d;", array(intval($id)));
                else
                    return E_USER_ADMIN_NOT_DELETE;
            }

            return E_USER_NOT_EXIST;
        }
    }

    function validName($str)
    {
        return preg_match("/^[0-9a-zA-ZñÑáéíóúÁÉÍÓÚ., ]+$/",$str);
    }
    function validVat($str)
    {
        return preg_match("/^[a-zA-Z0-9., -]*$/",$str);
    }
    function validCed($str)
    {
        return preg_match("/^[0-9]+$/",$str);
    }
    function validVatVE($str)
    {
        return preg_match("/^[VJEGP]{0,1}-?[0-9]{6,8}(-?[0-9]{1}){0,1}$/",$str);
    }
    function validVatVE_J($str)
    {
        return preg_match("/^[VEJGP]{1}-?[0-9]{8}-?[0-9]{1}$/",$str);
    }

    function validAddress($str)
    {
        return preg_match("/^[a-zA-Z0-9\#ñÑáéíóúÁÉÍÓÚ., _-]+$/",$str);
    }

    function validUsername($str)
    {
        return preg_match("/^[a-zA-Z]+[a-zA-Z0-9]*$/",$str);
    }

    function validPhone($str)
    {
        return preg_match("/^[0-9 \(\)\+-]+$/",$str);
    }

    function emailValido($str)
    {
        $dm =split("@", $str);
        $dom=$dm[1];
        return filter_var($str, FILTER_VALIDATE_EMAIL) && gethostbyname($dom) != $dom;
    }
    function email($para, $asunto, $mensaje = "", $de = "API Cedulas-VE <no-reply@cedula.com.ve>")
    {
        $sCabeceras="From: ". ($de==""?($_SERVER['SERVER_NAME']." <info@".$_SERVER['SERVER_NAME']):$de)."\r\nReply-To: ".($de==""?($_SERVER['SERVER_NAME']." <info@".$_SERVER['SERVER_NAME']):$de)."\r\nX-Mailer: PHP/" . phpversion() . "\r\nMIME-version: 1.0\r\n";
        $sCabeceras.="X-Priority: 1 (Higuest)\r\n";
        $sCabeceras.="X-MSMail-Priority: High\r\n";
        $sCabeceras.="Importance: High\r\n";
        $bHayFicheros=0;
        $mensaje .= '<br /><br /><br /><br />--<br />Atentamente,<br />Sistema Automatizado de consultas de Cedulas Venezolanas<br />Cedula.com.ve';
/*
        foreach ($_POST as $nombre => $valor)
        {
            if ($nombre == "captcha")
                continue;

            $mensaje.="<b>" . ereg_replace("_", " ", $nombre) . "</b> = " . $valor . "<br>\n";
        }
*/
        foreach ($_FILES as $vAdjunto)
        {
            if ($vAdjunto["size"] > 0)
            {
                if ($bHayFicheros == 0)
                {
                    $bHayFicheros=1;
                    $sCabeceras.="Content-type: multipart/mixed;";
                    $sCabeceras.="boundary=\"--_Separador-de-mensajes_--\"\n";
                    $sCabeceraTexto="----_Separador-de-mensajes_--\n";
                    $sCabeceraTexto.="Content-type: text/html;charset=utf-8\n";
                    $sCabeceraTexto.="Content-transfer-encoding: 7BIT\n";
                    $mensaje=$sCabeceraTexto . $mensaje;
                }

                $sAdjuntos.="\n\n----_Separador-de-mensajes_--\n";
                $sAdjuntos.="Content-type: " . $vAdjunto["type"] . ";name=\"" . $vAdjunto["name"] . "\"\n";
                $sAdjuntos.="Content-Transfer-Encoding: BASE64\n";
                $sAdjuntos.="Content-disposition: attachment;filename=\"" . $vAdjunto["name"] . "\"\n\n";
                $oFichero  =fopen($vAdjunto["tmp_name"], 'r');
                $sContenido=fread($oFichero, filesize($vAdjunto["tmp_name"]));
                $sAdjuntos.=chunk_split(base64_encode($sContenido));
                fclose ($oFichero);
            }
        }

        if ($bHayFicheros)
            $mensaje.=$sAdjuntos . "\n\n----_Separador-de-mensajes_----\n";
        else
            $sCabeceras.="Content-type: text/html;charset=utf-8\n";

        return mail($para, $asunto, $mensaje, $sCabeceras);
    }
    function strleft($s1, $s2) { return substr($s1, 0, strpos($s1, $s2)); }
    function selfURL() {
        $s = empty($_SERVER["HTTPS"]) ? '' : ($_SERVER["HTTPS"] == "on") ? "s" : "";
        $protocol = strleft(strtolower($_SERVER["SERVER_PROTOCOL"]), "/").$s;
        $port = ($_SERVER["SERVER_PORT"] == "80") ? "" : (":".$_SERVER["SERVER_PORT"]);
        return $protocol."://".$_SERVER['SERVER_NAME'].$port.$_SERVER['REQUEST_URI'];
    }

    function secInjection($str){
        return mysql_real_escape_string(stripslashes($str));
    }

    function generateUrl($nombre){
        $nombre=preg_replace("/[^a-zA-Z0-9 áéíóúñÁÉÍÓÚÑ]+/i","",$nombre);
        $nombre=preg_replace("/ /","-",$nombre);
        return $nombre;
    }

    function getRealIP()
    {

        if( !empty($_SERVER['HTTP_X_FORWARDED_FOR']) )
        {
            $client_ip =
            ( !empty($_SERVER['REMOTE_ADDR']) ) ?
            $_SERVER['REMOTE_ADDR']
            :
            ( ( !empty($_ENV['REMOTE_ADDR']) ) ?
            $_ENV['REMOTE_ADDR']
            :
            "unknown" );

            // los proxys van añadiendo al final de esta cabecera
            // las direcciones ip que van "ocultando". Para localizar la ip real
            // del usuario se comienza a mirar por el principio hasta encontrar
            // una dirección ip que no sea del rango privado. En caso de no
            // encontrarse ninguna se toma como valor el REMOTE_ADDR

            $entries = split('[, ]', $_SERVER['HTTP_X_FORWARDED_FOR']);

            reset($entries);
            while (list(, $entry) = each($entries))
            {
                $entry = trim($entry);
                if ( preg_match("/^([0-9]+.[0-9]+.[0-9]+.[0-9]+)/", $entry, $ip_list) )
                {
                    // http://www.faqs.org/rfcs/rfc1918.html
                    $private_ip = array(
                    '/^0./',
                    '/^127.0.0.1/',
                    '/^192.168..*/',
                    '/^172.((1[6-9])|(2[0-9])|(3[0-1]))..*/',
                    '/^10..*/');

                    $found_ip = preg_replace($private_ip, $client_ip, $ip_list[1]);

                    if ($client_ip != $found_ip)
                    {
                        $client_ip = $found_ip;
                        break;
                    }
                }
            }
        }
        else
        {
            $client_ip =
            ( !empty($_SERVER['REMOTE_ADDR']) ) ?
            $_SERVER['REMOTE_ADDR']
            :
            ( ( !empty($_ENV['REMOTE_ADDR']) ) ?
            $_ENV['REMOTE_ADDR']
            :
            "unknown" );
        }

        return $client_ip;

    }

    $count_input = 0;
    function input($name, $description, $value = '', $placeholder = '', $type='text') {
        global $count_input;
        ++$count_input;
        return "<div class='input-group input-group'>
              <span class='input-group-addon' id='sizing-addon-$count_input'>$description</span>
              <input type='$type' name='$name' class='form-control' placeholder='$placeholder' value='$value' aria-describedby='sizing-addon$count_input'>
            </div>";
    }

    function select($name, $description, $placeholder = '', $options = array(), $defaults = array(), $multiple = false) {
        global $count_input;
        ++$count_input;
        $select = "<div class='input-group input-group'>
        <span class='input-group-addon' id='sizing-addon-$count_input'>$description</span>
        <select class='selectpicker' data-live-search='true' name='$name' title='$placeholder' ".($multiple?'multiple':'')." aria-describedby='sizing-addon$count_input'>";
        foreach($options as $value => $text)
            $select .= "<option value='$value' ".(in_array($value, $defaults)?'selected="selected"':'').">$text</option>";
        $select .= "</select></div>";
        return $select;
    }

    function getCountries($iso = false){
        $c = array(
            "AF" => "Afghanistan",
            "AX" => "Åland Islands",
            "AL" => "Albania",
            "DZ" => "Algeria",
            "AS" => "American Samoa",
            "AD" => "Andorra",
            "AO" => "Angola",
            "AI" => "Anguilla",
            "AQ" => "Antarctica",
            "AG" => "Antigua and Barbuda",
            "AR" => "Argentina",
            "AM" => "Armenia",
            "AW" => "Aruba",
            "AU" => "Australia",
            "AT" => "Austria",
            "AZ" => "Azerbaijan",
            "BS" => "Bahamas",
            "BH" => "Bahrain",
            "BD" => "Bangladesh",
            "BB" => "Barbados",
            "BY" => "Belarus",
            "BE" => "Belgium",
            "BZ" => "Belize",
            "BJ" => "Benin",
            "BM" => "Bermuda",
            "BT" => "Bhutan",
            "BO" => "Bolivia, Plurinational State of",
            "BQ" => "Bonaire, Sint Eustatius and Saba",
            "BA" => "Bosnia and Herzegovina",
            "BW" => "Botswana",
            "BV" => "Bouvet Island",
            "BR" => "Brazil",
            "IO" => "British Indian Ocean Territory",
            "BN" => "Brunei Darussalam",
            "BG" => "Bulgaria",
            "BF" => "Burkina Faso",
            "BI" => "Burundi",
            "KH" => "Cambodia",
            "CM" => "Cameroon",
            "CA" => "Canada",
            "CV" => "Cape Verde",
            "KY" => "Cayman Islands",
            "CF" => "Central African Republic",
            "TD" => "Chad",
            "CL" => "Chile",
            "CN" => "China",
            "CX" => "Christmas Island",
            "CC" => "Cocos (Keeling) Islands",
            "CO" => "Colombia",
            "KM" => "Comoros",
            "CG" => "Congo",
            "CD" => "Congo, the Democratic Republic of the",
            "CK" => "Cook Islands",
            "CR" => "Costa Rica",
            "CI" => "Côte d'Ivoire",
            "HR" => "Croatia",
            "CU" => "Cuba",
            "CW" => "Curaçao",
            "CY" => "Cyprus",
            "CZ" => "Czech Republic",
            "DK" => "Denmark",
            "DJ" => "Djibouti",
            "DM" => "Dominica",
            "DO" => "Dominican Republic",
            "EC" => "Ecuador",
            "EG" => "Egypt",
            "SV" => "El Salvador",
            "GQ" => "Equatorial Guinea",
            "ER" => "Eritrea",
            "EE" => "Estonia",
            "ET" => "Ethiopia",
            "FK" => "Falkland Islands (Malvinas)",
            "FO" => "Faroe Islands",
            "FJ" => "Fiji",
            "FI" => "Finland",
            "FR" => "France",
            "GF" => "French Guiana",
            "PF" => "French Polynesia",
            "TF" => "French Southern Territories",
            "GA" => "Gabon",
            "GM" => "Gambia",
            "GE" => "Georgia",
            "DE" => "Germany",
            "GH" => "Ghana",
            "GI" => "Gibraltar",
            "GR" => "Greece",
            "GL" => "Greenland",
            "GD" => "Grenada",
            "GP" => "Guadeloupe",
            "GU" => "Guam",
            "GT" => "Guatemala",
            "GG" => "Guernsey",
            "GN" => "Guinea",
            "GW" => "Guinea-Bissau",
            "GY" => "Guyana",
            "HT" => "Haiti",
            "HM" => "Heard Island and McDonald Mcdonald Islands",
            "VA" => "Holy See (Vatican City State)",
            "HN" => "Honduras",
            "HK" => "Hong Kong",
            "HU" => "Hungary",
            "IS" => "Iceland",
            "IN" => "India",
            "ID" => "Indonesia",
            "IR" => "Iran, Islamic Republic of",
            "IQ" => "Iraq",
            "IE" => "Ireland",
            "IM" => "Isle of Man",
            "IL" => "Israel",
            "IT" => "Italy",
            "JM" => "Jamaica",
            "JP" => "Japan",
            "JE" => "Jersey",
            "JO" => "Jordan",
            "KZ" => "Kazakhstan",
            "KE" => "Kenya",
            "KI" => "Kiribati",
            "KP" => "Korea, Democratic People's Republic of",
            "KR" => "Korea, Republic of",
            "KW" => "Kuwait",
            "KG" => "Kyrgyzstan",
            "LA" => "Lao People's Democratic Republic",
            "LV" => "Latvia",
            "LB" => "Lebanon",
            "LS" => "Lesotho",
            "LR" => "Liberia",
            "LY" => "Libya",
            "LI" => "Liechtenstein",
            "LT" => "Lithuania",
            "LU" => "Luxembourg",
            "MO" => "Macao",
            "MK" => "Macedonia, the Former Yugoslav Republic of",
            "MG" => "Madagascar",
            "MW" => "Malawi",
            "MY" => "Malaysia",
            "MV" => "Maldives",
            "ML" => "Mali",
            "MT" => "Malta",
            "MH" => "Marshall Islands",
            "MQ" => "Martinique",
            "MR" => "Mauritania",
            "MU" => "Mauritius",
            "YT" => "Mayotte",
            "MX" => "Mexico",
            "FM" => "Micronesia, Federated States of",
            "MD" => "Moldova, Republic of",
            "MC" => "Monaco",
            "MN" => "Mongolia",
            "ME" => "Montenegro",
            "MS" => "Montserrat",
            "MA" => "Morocco",
            "MZ" => "Mozambique",
            "MM" => "Myanmar",
            "NA" => "Namibia",
            "NR" => "Nauru",
            "NP" => "Nepal",
            "NL" => "Netherlands",
            "NC" => "New Caledonia",
            "NZ" => "New Zealand",
            "NI" => "Nicaragua",
            "NE" => "Niger",
            "NG" => "Nigeria",
            "NU" => "Niue",
            "NF" => "Norfolk Island",
            "MP" => "Northern Mariana Islands",
            "NO" => "Norway",
            "OM" => "Oman",
            "PK" => "Pakistan",
            "PW" => "Palau",
            "PS" => "Palestine, State of",
            "PA" => "Panama",
            "PG" => "Papua New Guinea",
            "PY" => "Paraguay",
            "PE" => "Peru",
            "PH" => "Philippines",
            "PN" => "Pitcairn",
            "PL" => "Poland",
            "PT" => "Portugal",
            "PR" => "Puerto Rico",
            "QA" => "Qatar",
            "RE" => "Réunion",
            "RO" => "Romania",
            "RU" => "Russian Federation",
            "RW" => "Rwanda",
            "BL" => "Saint Barthélemy",
            "SH" => "Saint Helena, Ascension and Tristan da Cunha",
            "KN" => "Saint Kitts and Nevis",
            "LC" => "Saint Lucia",
            "MF" => "Saint Martin (French part)",
            "PM" => "Saint Pierre and Miquelon",
            "VC" => "Saint Vincent and the Grenadines",
            "WS" => "Samoa",
            "SM" => "San Marino",
            "ST" => "Sao Tome and Principe",
            "SA" => "Saudi Arabia",
            "SN" => "Senegal",
            "RS" => "Serbia",
            "SC" => "Seychelles",
            "SL" => "Sierra Leone",
            "SG" => "Singapore",
            "SX" => "Sint Maarten (Dutch part)",
            "SK" => "Slovakia",
            "SI" => "Slovenia",
            "SB" => "Solomon Islands",
            "SO" => "Somalia",
            "ZA" => "South Africa",
            "GS" => "South Georgia and the South Sandwich Islands",
            "SS" => "South Sudan",
            "ES" => "Spain",
            "LK" => "Sri Lanka",
            "SD" => "Sudan",
            "SR" => "Suriname",
            "SJ" => "Svalbard and Jan Mayen",
            "SZ" => "Swaziland",
            "SE" => "Sweden",
            "CH" => "Switzerland",
            "SY" => "Syrian Arab Republic",
            "TW" => "Taiwan, Province of China",
            "TJ" => "Tajikistan",
            "TZ" => "Tanzania, United Republic of",
            "TH" => "Thailand",
            "TL" => "Timor-Leste",
            "TG" => "Togo",
            "TK" => "Tokelau",
            "TO" => "Tonga",
            "TT" => "Trinidad and Tobago",
            "TN" => "Tunisia",
            "TR" => "Turkey",
            "TM" => "Turkmenistan",
            "TC" => "Turks and Caicos Islands",
            "TV" => "Tuvalu",
            "UG" => "Uganda",
            "UA" => "Ukraine",
            "AE" => "United Arab Emirates",
            "GB" => "United Kingdom",
            "US" => "United States",
            "UM" => "United States Minor Outlying Islands",
            "UY" => "Uruguay",
            "UZ" => "Uzbekistan",
            "VU" => "Vanuatu",
            "VE" => "Venezuela",
            "VN" => "Viet Nam",
            "VG" => "Virgin Islands, British",
            "VI" => "Virgin Islands, U.S.",
            "WF" => "Wallis and Futuna",
            "EH" => "Western Sahara",
            "YE" => "Yemen",
            "ZM" => "Zambia",
            "ZW" => "Zimbabwe");
        if($iso) 
            return $c[$iso];
        return $c;
    }

    function getGeoip()
    {
        if (!isset($_SESSION['country']))
        {
            $json = @json_decode(@file_get_contents(SERVER_GEOIP.getRealIP()), true);
            if (isset($json['country_code']))
            {
                $_SESSION['country'] = $json['country_code'];
            }else
                $_SESSION['country'] = 'VE';
        }
        return $_SESSION['country'];
    }

    function validateMercadoPago($mp_op_id = false)
    {
        if (!$mp_op_id)
            return false;

        $mp = new MercadoPagoVE(MERCADOPAGO_KEY, MERCADOPAGO_SECRET);

        if (MERCADOPAGO_SANDBOX)
            $mp->sandbox_mode(true);

        try
        {
            $payment_info = $mp->get_payment_info($mp_op_id);
        }
        catch (Exception $e)
        {
            return false;
        }

        if (isset($payment_info['response']) && isset($payment_info['response']['collection']))
            $payment_info = $payment_info['response']['collection'];

        if (!isset($payment_info['status']) || !isset($payment_info['order_id']))
            return false;
        $ret = array();

        switch ($payment_info['status'])
        {
            case 'approved':
                $status_act = 'Completed';
                break;
            case 'refunded':
            case 'cancelled':
            case 'rejected':
                $status_act = 'Rejected';
                break;
            default:
                $status_act = 'Pending';
                break;
        }

        $ret['price'] = $payment_info['total_paid_amount'];
        $ret['currency'] = $payment_info['currency_id'];
        $ret['status'] = $status_act;
        $ret['order_id'] = $payment_info['order_id'];
        $ret['message'] = json_encode($payment_info).'\n';

        return $ret;
    }

    function getCurlData($url)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_TIMEOUT, 10);
        $curlData = curl_exec($curl);
        curl_close($curl);
        return $curlData;
    }

    function checkRecaptchar($secret, $recaptcha) {
        $res = getCurlData("https://www.google.com/recaptcha/api/siteverify?secret=".$secret."&response=".$recaptcha."&remoteip=".getRealIP());
        $res= json_decode($res, true);
        return isset($res['success']) && $res['success'];
    }

    function valid_rif($type, $ci) {
        $ci = $ci."";
        if(strlen($ci)>9 || strlen($ci)<=3) return false;
        $count_digits = strlen($ci);
        if($count_digits==9)
            $count_digits--;

        $calc = array(0,0,0,0,0,0,0,0,0,0);
        $constants = array(4,3,2,7,6,5,4,3,2);

        $type = strtoupper($type);
        if($type=="V")             $calc[0] = 1;
        else if($type=="E")     $calc[0] = 2;
        else if($type=="J")     $calc[0] = 3;
        else if($type=="P")        $calc[0] = 4;
        else if($type=="G")     $calc[0] = 5;
        else return false;

        $sum = $calc[0]*$constants[0];
        $index = count($constants)-1;

        for($i=$count_digits-1;$i>=0;$i--){
            $digit = $calc[$index] = intval($ci[$i]);
            $sum += $digit*$constants[$index--];
        }
        $final_digit = $sum%11;
        if($final_digit>1)
            $final_digit = 11 - $final_digit;

        $final_digit_legal = intval($ci[8]);
        if(strlen($ci)==9 && ($final_digit_legal!=$final_digit && $final_digit_legal!=0))
            return false;

        $calc[9] = strlen($ci)==9?$final_digit_legal:$final_digit;

        $rif = $type;
        for($i = 1; $i < count($calc); ++$i)
            $rif .= $calc[$i];

        return $rif;
    }

    function getCI($cedula, $return_raw = false) {
        $res = getCurlData(API_CEDULA_SERVER."/api/v1?app_id=".API_CEDULA_APP_ID."&token=".API_CEDULA_APP_TOKEN."&cedula=".(int)$cedula);
        if($return_raw)
            return strlen($res)>3?$res:false;
        $res= json_decode($res, true);
        return isset($res['data']) && $res['data']?$res['data']:false;
    }

    function getRifSeniat($rif, $return_result = false) {
        $rif = strtoupper(preg_replace("/[ ,_-]+/", "", $rif));
        try {
            $rawXml = getCurlData('http://contribuyente.seniat.gob.ve/getContribuyente/getrif?rif='.$rif);
            if($rawXml) {
                $rawXml = preg_replace("/<rif:Rif[^>]+>/i", "<rif>", $rawXml);
                $rawXml = preg_replace("/rif:/i", "", $rawXml);
                $rawXml = preg_replace("/<\/Rif>/i", "</rif>", $rawXml);
                $xml = @simplexml_load_string($rawXml);
                if($xml) {
                    $result = array(
                        "rif"=>$rif,
                        "name"=>trim(preg_replace("/\([^\)]*\)/", "", (string)$xml->{"Nombre"})),
                        "agente_retencion"=>((string)$xml->{"AgenteRetencionIVA"})=="SI",
                        "contribuyente_iva"=>((string)$xml->{"ContribuyenteIVA"})=="SI",
                        "contribuyente_tasa"=>floatval((string)$xml->{"Tasa"})
                    );
                    $result_rif = array("ok"=>true,"result"=>$result);
                }else{
                    $result_rif = array("ok"=>false,"error"=>htmlentities($rawXml));
                }
            }else{
                $result_rif =  array("ok"=>false,"error"=>htmlentities(lang("sales_remote_access_failed2")));
            }
        }catch(Exception $e)  {
            $result_rif = array("ok"=>false,"error"=>htmlentities(lang("sales_remote_access_failed")));
        }
        if($return_result) return $result_rif;
        else echo $result_rif;
    }

?>