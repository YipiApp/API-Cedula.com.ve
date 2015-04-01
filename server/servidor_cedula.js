var fs = require('fs');
var mysql = require('mysql');
var https = require('https');
var url = require('url');

var db = {
		user : '',
		password : '',
		database: '',
		socketPath: '/var/run/mysqld/mysqld.sock'
	};
	
var options = {
  key: fs.readFileSync('ssl.key'),
  cert: fs.readFileSync('ssl.crt')
};

function getConnection(mysql, db_config, cb) {
	try{
		var MyConn = mysql.createConnection(db_config);
		var isConnected = false;
		var db_error = false;
		MyConn.connect(function(error){
			if(error){
				console.log('Connected: '+error);
				return cb(error, false);
			}else{
				console.log('Connected: OK');
				return cb(false, MyConn);
			}
		});
		MyConn.on('error', function (err) {
			console.log('DB error: ' + JSON.stringify(err));
		});
	}catch(e) {
		console.log('getConnection error: ' + JSON.stringify(e));
		return cb('Exception' + JSON.stringify(e), false);
	}
}

function selectOne(query, args, conn, cb) {
	try{
		conn.query(query, args, function(error, result){
			if(error)
				return cb(error, false);

			if(result.length > 0)
				return cb(false, result[0]);
			
			return cb(false, false);
		});
	}catch(e) {
		console.log('selectOne error: ' + JSON.stringify(e));
		return cb('Exception' + JSON.stringify(e), false);
	}
}

function respondError(res, name_error){
	res.writeHead(200);
	var foo = new Object();
	foo.error = true;
	foo.data = false;
	foo.error_str = name_error;
	res.end(JSON.stringify(foo));
}

function respondCedula(res, conn, plan, cedula){
	selectOne("SELECT registro_civil.*, parroquias.*, municipios.*, estados.*, centros_cne.* FROM registro_civil "+
			"LEFT JOIN centros_cne ON registro_civil.id_centro_cne = centros_cne.id_centro_cne "+
			"LEFT JOIN parroquias ON parroquias.id_parroquia = centros_cne.id_parroquia "+
			"LEFT JOIN municipios ON municipios.id_municipio = parroquias.id_municipio "+
			"LEFT JOIN estados ON estados.id_estado = parroquias.id_estado "+
			"LEFT JOIN not_show ON not_show.rif = registro_civil.rif "+
			"WHERE cedula = ? AND not_show.rif IS NULL", [cedula], conn, function(error, result) {
		conn.end();
		if(error)
			respondError(res, 'DB_ERROR');
		else if(!result)
			respondError(res, 'RECORD_NOT_FOUND');
		else 
		{			
			res.writeHead(200);
			var foo = new Object();
			foo.error = false;
			foo.error_str = false;
			foo.data = new Object();
			
			foo.data.nacionalidad = result.nacionalidad;
			foo.data.cedula = result.cedula;
			if(parseInt(plan.show_rif))
				foo.data.rif = result.rif;
				
			if(result.nombre_centro) {
				foo.data.cne = new Object();
				foo.data.cne.estado = result.estado.trim();
				foo.data.cne.municipio = result.municipio.trim();
				foo.data.cne.parroquia = result.parroquia.trim();
				foo.data.cne.centro_electoral =  result.nombre_centro.trim();
			}
			
			if(parseInt(plan.show_names))
			{
				foo.data.primer_apellido = result.primer_apellido;
				if(result.segundo_apellido)
					foo.data.segundo_apellido = result.segundo_apellido;
					
				foo.data.primer_nombre = result.primer_nombre;
				if(result.segundo_nombre)
					foo.data.segundo_nombre = result.segundo_nombre;
			}

			foo.data.request_date = new Date();
			
			res.end(JSON.stringify(foo));
		}
	});
}

https.createServer(options, function (req, res) {
	var parsedUrl = url.parse(req.url, true);
    console.log('parsed url', parsedUrl);
    var query = parsedUrl.query;
	if(!query || !query.app_id || !query.token  || !query.cedula)
		respondError(res, 'URL_ERROR');
	else{
		var app_id = parseInt(query.app_id);
		var cedula = parseInt(query.cedula);
		var token = query.token.toLowerCase().replace(/[^0-9a-f]+/g, '');
		getConnection(mysql, db, function(error, conn) {
			if(error)
				respondError(res, 'DB_ERROR');
			else
				selectOne("SELECT * FROM api_services INNER JOIN api_planes plan ON plan.id_plan = api_services.id_plan WHERE id_service = ? AND token = ? AND api_services.activo = 1 AND proximo_corte > NOW() LIMIT 1", [app_id, token], conn, function(error, plan) {
					if(!plan || error)
						conn.end();
					
					if(!plan) 
						respondError(res, 'INVALID_TOKEN');
					else if(error)
						respondError(res, 'DB_ERROR');
					else
						selectOne("SELECT num_request FROM api_requests WHERE id_service = ? AND fecha = DATE(NOW()) AND hora = HOUR(NOW()) LIMIT 1", [app_id], conn, function(error, result) {
							if(error)
							{
								conn.end();
								respondError(res, 'DB_ERROR');
							}
							else
							{
								if (result && plan.max_request_per_hour <= result.num_request)
								{
									conn.end();
									respondError(res, 'MAX_NUM_REQUEST');
								}else {
									if(result)
										conn.query("UPDATE api_requests SET num_request = num_request + 1 WHERE  id_service = ? AND fecha = DATE(NOW()) AND hora = HOUR(NOW())", [app_id], function(error, result){
											if(error)
											{
												conn.end();
												respondError(res, 'DB_ERROR');
											}
											else
												respondCedula(res, conn, plan, cedula);
										});
									else
										conn.query("INSERT INTO api_requests (id_service, fecha, hora, num_request) VALUES (?, DATE(NOW()), HOUR(NOW()), 1)", [app_id], function(error, result){
											if(error)
											{
												conn.end();
												respondError(res, 'DB_ERROR');
											}
											else
												respondCedula(res, conn, plan, cedula);
										});
								}
							}							
						});					
				});
		});
	}
}).listen(444);