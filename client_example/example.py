import urllib.request
import json

APPID_CEDULA = 'APP-ID-AQUI'
TOKEN_CEDULA = 'TOKEN-AQUI'

def getCI( cedula ):
	global APPID_CEDULA
	global TOKEN_CEDULA
	response = urllib.request.urlopen('https://api.cedula.com.ve/api/v1?app_id='+APPID_CEDULA+'&token='+TOKEN_CEDULA+'&cedula='+cedula).read()
	if response:
		data = json.loads(response.decode("utf-8"))
		if data['data']:
			return data['data']
	return false
	
persona = getCI('00000')
if persona:
	print(persona['primer_nombre'])
	print(persona['primer_apellido'])
	print(persona)
else:
	print('Ocurrio un error')
