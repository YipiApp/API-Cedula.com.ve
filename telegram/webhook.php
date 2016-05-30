<?php

$entityBody = @file_get_contents('php://input');

define('API_CEDULA_SERVER', '');
define('API_CEDULA_APP_ID', '');
define('API_CEDULA_APP_TOKEN', '');
define('TOKEN_BOT_TELEGRAM', '');

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

function getCI($cedula, $return_raw = false) {
    $res = getCurlData(API_CEDULA_SERVER."/api/v1?app_id=".API_CEDULA_APP_ID."&token=".API_CEDULA_APP_TOKEN."&cedula=".(int)$cedula);
    if($return_raw)
        return strlen($res)>3?$res:false;
    $res= json_decode($res, true);
    return isset($res['data']) && $res['data']?$res['data']:false;
}

if($entityBody && !empty($entityBody)) {
    $data = @json_decode($entityBody, true);
    if(isset($data['message'])) {
        $msj = $data['message'];
        $chat = $msj['chat'];
        $from = $msj['from'];
        $text = preg_split('/ /', $msj['text']);
        switch($text[0]) {
            case '/cedula@CedulaBot':
            case '/cedula':
            case '/cédula@CedulaBot':
            case '/cédula':
                if (!is_dir('./request_cedula/'.date('Y-m-d'))) {
                    @mkdir('./request_cedula/'.date('Y-m-d'));
                }
                $count = (int)@file_get_contents('./request_cedula/'.date('Y-m-d').'/'.(int)$from['id'].'.txt');
                if($count + 1 > 50) {
                    die(@file_get_contents("https://api.telegram.org/bot".TOKEN_BOT_TELEGRAM."/sendMessage?chat_id={$chat['id']}&reply_to_message_id={$msj['message_id']}&text=".urlencode(trim("{$from['first_name']} {$from['last_name']}")." no puedes solicitar mas de 50 cedulas por dia.")));
                }
                ++$count;
                file_put_contents('./request_cedula/'.date('Y-m-d').'/'.(int)$from['id'].'.txt', "".$count);
                if(isset($text[1]) && (int)$text[1] > 0) {
                    $data = getCI((int)$text[1], true);
                    $result = json_decode($data, true);
                    $msj_result = 'Cédula no encontrada.';
                    if($result && !$result['error'] && $result['data']) {
                        $msj_result = 'Nombres: '.trim($result['data']['primer_nombre'].' '.$result['data']['segundo_nombre']);
                        $msj_result .= "\nApellidos: ".trim($result['data']['primer_apellido'].' '.$result['data']['segundo_apellido']);
                        $msj_result .= "\nR.I.F.: ".trim($result['data']['rif']);
                        if($result['data']['cne'])
                            $msj_result .= "\nCNE: (Abril 2012) ".trim($result['data']['cne']['estado'].' - '.$result['data']['cne']['municipio'].' - '.$result['data']['cne']['parroquia'].' - '.$result['data']['cne']['centro_electoral']);
                    }
                    die(@file_get_contents("https://api.telegram.org/bot".TOKEN_BOT_TELEGRAM."/sendMessage?chat_id={$chat['id']}&reply_to_message_id={$msj['message_id']}&text=".urlencode(trim("{$from['first_name']} {$from['last_name']}")." resultado:\n\n".$msj_result)));
                } else {
                    die(@file_get_contents("https://api.telegram.org/bot".TOKEN_BOT_TELEGRAM."/sendMessage?chat_id={$chat['id']}&reply_to_message_id={$msj['message_id']}&text=".urlencode(trim("{$from['first_name']} {$from['last_name']}")." envía el comando: /cedula [cedula_de_identidad]")));
                }
            break;
            case '/help':
            case '/help@CedulaBot':
                die(@file_get_contents("https://api.telegram.org/bot".TOKEN_BOT_TELEGRAM."/sendMessage?chat_id={$chat['id']}&reply_to_message_id={$msj['message_id']}&text=".urlencode(trim("{$from['first_name']} {$from['last_name']}")." envía el comando: /cedula [cedula_de_identidad]")));
            default:
                break;
        }
    } else if(isset($data['inline_query'])) {
        $inline = $data['inline_query'];
        $from = $inline['from'];
        if (!is_dir('./request_cedula/'.date('Y-m-d'))) {
            @mkdir('./request_cedula/'.date('Y-m-d'));
        }
        $count = (int)@file_get_contents('./request_cedula/'.date('Y-m-d').'/'.(int)$from['id'].'.txt');
        if($count + 1 > 50) {
            die(@file_get_contents("https://api.telegram.org/bot/answerInlineQuery?inline_query_id={$inline['id']}&results=".urlencode(json_encode(array(array('type'=>'article', 'id'=>md5($from['id'].'-'.$inline['query']), 'title'=>'Limite alcanzado', 'input_message_content'=>array('message_text'=>'No puedes solicitar mas de 50 cédulas por dia')))))));
        }
        ++$count;
        file_put_contents('./request_cedula/'.date('Y-m-d').'/'.(int)$from['id'].'.txt', "".$count);
        $data = getCI((int)$inline['query'], true);
        $result = json_decode($data, true);
        $msj_result = 'Cédula no encontrada.';
        $title = "No encontrado";
        if($result && !$result['error'] && $result['data']) {
            $title = 'V-'.intval($inline['query']).': '.trim($result['data']['primer_nombre'].' '.$result['data']['segundo_nombre']).' '.trim($result['data']['primer_apellido'].' '.$result['data']['segundo_apellido']);
            $msj_result = '<b>Cédula:</b> V-'.intval($inline['query']);
            $msj_result .= "\n<b>Nombres:</b> ".trim($result['data']['primer_nombre'].' '.$result['data']['segundo_nombre']);
            $msj_result .= "\n<b>Apellidos:</b> ".trim($result['data']['primer_apellido'].' '.$result['data']['segundo_apellido']);
            $msj_result .= "\n<b>R.I.F.:</b> ".trim($result['data']['rif']);
            if($result['data']['cne'])
                $msj_result .= "\n<b>CNE:</b> (Abril 2012) ".trim($result['data']['cne']['estado'].' - '.$result['data']['cne']['municipio'].' - '.$result['data']['cne']['parroquia'].' - '.$result['data']['cne']['centro_electoral']);
            $msj_result .= "\n<b>Mayor información en:</b> http://cedula.com.ve/";
           
        }
        die(@file_get_contents("https://api.telegram.org/bot".TOKEN_BOT_TELEGRAM."/answerInlineQuery?inline_query_id={$inline['id']}&results=".urlencode(json_encode(array(array('type'=>'article', 'id'=>md5($from['id'].'-'.$inline['query']), 'title'=>$title, 'input_message_content'=>array('message_text'=>$msj_result, 'parse_mode'=>'HTML', 'disable_web_page_preview'=>true)))))));
    }
}
echo 'OK';