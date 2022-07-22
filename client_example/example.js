var request = require("request")
var APPID_CEDULA = 'APP-ID-AQUI';
var TOKEN_CEDULA = 'TOKEN-AQUI';

function getCI(cedula, cb) {
	request({
		url: 'https://api.cedula.com.ve/api/v1?app_id='+APPID_CEDULA+'&token='+TOKEN_CEDULA+'&cedula='+cedula,
		json: true,
		rejectUnauthorized: false
	}, function (error, response, body) {
		if (!error && response.statusCode === 200) {
			if(body.data)
				return cb(true, body.data, false);
			else 
				return cb(true, false, body.error_str);
		}else{
			cb(false, false, false);
		}
	});
}


getCI('00000', function(result, data, error_str){
	if(result)
		if(error_str)
			console.log('Ocurrio un error: '+error_str);
		else 
			console.log(data);
	else
		console.log('Ocurrio un error en la consulta');
});
