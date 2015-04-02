/*
	Este algoritmo es utilizado para obtener las cedulas
	que no estan en el REP de febrero 2012
	
	Utilizar con precaucion para no hacer ataque DDoS al sistema
	automatizado del CNE.
	
	Se agregaron aprox. 7 millones de cedulas en 2 meses que duro
	ejecutandose el script. Con un ratio controlado de 80 request/Min.
	
	Ejemplo de consulta:
	
	search_cne(false, 'V', 12345678, function(result) {
		console.log(result);
		// Aqui se debe guardar la data en la tabla registro_civil
	});
*/
var http = require('http');
var querystring = require('querystring');

// Algoritmo para el Calculo del RIF
function ci_to_rif(type, ci) {
	ci = ci+"";
    if(ci.length>9) return false;
	
    var count_digits = ci.length;
    if(count_digits==9) 
        count_digits--;
        
    var calc = [0,0,0,0,0,0,0,0,0,0];
    var constants = [4,3,2,7,6,5,4,3,2];
    
    if(type=="V")         calc[0] = 1;
    else if(type=="E")     calc[0] = 2;
    else if(type=="J")     calc[0] = 3;
    else if(type=="P")    calc[0] = 4;
    else if(type=="G")     calc[0] = 5;
    else return false;
    
    var sum = calc[0]*constants[0];
    var index = constants.length-1;
    
    for(var i=count_digits-1;i>=0;i--){
        var digit = calc[index] = parseInt(ci[i]);
        sum += digit*constants[index--];
    }
    var final_digit = sum%11;
    if(final_digit>1) 
        final_digit = 11 - final_digit;
    var final_digit_legal = parseInt(ci[8]);
    if(ci.length==9 && (final_digit_legal!=final_digit && final_digit_legal!=0))
        return false;
        
    calc[9] = ci.length==9?final_digit_legal:final_digit;
    
    var rif = type;
    for(var i = 1; i < calc.length; ++i)
        rif += calc[i];
    
    return rif;
}

//Algoritmo para obtener una Cedula del Registro Civil
function search_cne(rif, nacionalidad, cedula, res_ced) {
	try {
		if(rif) {
			nacionalidad = rif[0];
			cedula = rif.substr(1, rif.length - 2)*1;
		}
		
		if(!rif)
			rif = ci_to_rif(nacionalidad, cedula);
		
		// Build the post string from an object
		var post_data = querystring.stringify({});

		// An object of options to indicate where to post to
		var post_options = {
		  host: 'www.cne.gob.ve',
		  port: '80',
		  path: "/web/registro_civil/buscar_rep.php?nac="+nacionalidad+"&ced="+cedula,
		  method: 'GET',
		  headers: {
			  'Content-Type': 'application/x-www-form-urlencoded',
			  'Content-Length': 0
		  }
		};

		// Set up the request
		var post_req = http.request(post_options, function(res) {
			res.setEncoding('utf8');
			var chunkAcum = "";
			res.on('error', function (err) {
				console.log('CNE error: ' + JSON.stringify(err));
				res_ced(false);
			});
			res.on('data', function (chunk) {
				chunkAcum += chunk;
			});
			res.on('end', function () {
				try {
					console.log('Resultado CNE: ' + chunkAcum);
					var data = chunkAcum.match(/<b[^>]*>[^<]*<\/b>/ig);
					if(data && data.length == 1) {
						var un_apellido = data[0].indexOf(' </b>') > 0;
						data = data[0].replace('<b>','').replace('</b>','')
						var un_nombre = data.indexOf('  ') > 0;
						data = data.replace('  ',' ').trim();
						var name_split = data.split(" ");
						var foo = new Object();
						foo.nacionalidad = nacionalidad;
						foo.cedula = cedula;
						foo.rif = rif;
						var len = name_split.length;
						if(len >= 4) {
							if(un_apellido){
								foo.primer_nombre = name_split[0];
								foo.segundo_nombre = "";
								for(var i = 1; i < len - 1; ++i)
									foo.segundo_nombre += name_split[i] + " ";
								foo.segundo_nombre = foo.segundo_nombre.trim();
								foo.primer_apellido = name_split[len - 1];
							}else{
								foo.primer_nombre = name_split[0];
								foo.segundo_nombre = "";
								for(var i = 1; i < len - 2; ++i)
									foo.segundo_nombre += name_split[i] + " ";
								foo.segundo_nombre = foo.segundo_nombre.trim();
								foo.primer_apellido = name_split[len - 2];
								foo.segundo_apellido = name_split[len - 1];
							}
						}else if(len == 2) {
							foo.primer_nombre = name_split[0];
							foo.primer_apellido = name_split[1];
						}else if(len == 3) {
							if(un_nombre>0)
							{
								foo.primer_nombre = name_split[0];
								foo.primer_apellido = name_split[1];
								foo.segundo_apellido = name_split[2];
							}else{
								foo.primer_nombre = name_split[0];
								foo.segundo_nombre = name_split[1];
								foo.primer_apellido = name_split[2];
							}
						}else
							return false;
					
						console.log('Found in CNE: ' + cedula + ' -> '+foo.primer_nombre+' '+foo.primer_apellido);
					
						res_ced(foo);
					}else{
						console.log('Not Found in CNE: ' + cedula);
						res_ced(false);
					}
				}catch(e) {
					console.log('CNE error 3: ' + JSON.stringify(e));
					res_ced(false);
				}
			});
		});
		
		post_req.on('error', function(err) {
			console.log('CNE error 2 ('+cedula+'): ' + JSON.stringify(err));
			listCI.push(cedula);
			res_ced(false);
		});
		post_req.end();
	}catch(e) {
		console.log('CNE error 1: ' + JSON.stringify(e));
		res_ced(false);
	}
}
