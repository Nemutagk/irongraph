# irongraph
Cliente sencillo de Graphql para laravel/lumen 8.0

### Uso

El uso es muy sencillo, se agrega el namespace y se extiende la clase *Client*, se agregan los parametros que se 
obtendrán del query que se envie, es importante crear la estructura tal cual la espera el servidor GraphQL

```
<?php
namespace BienParaBien\GrahpqlClient\Client;

class Model extends Client
{
	//URL del endpoint donde se ejecutará el Query de GraphQL
	//También se puede definir en el constructor de la clase new Model($url_endpoint);
	protected $baseURL = '';

	//Se puede definir el token de acceso, también se puede llamar el método setToken($token);
	protected $token = '';



	//Parametros que se enviarán, también se puede llamar el método setParameters(array $parameters);
	protected $parameters = [
		'Query' => [
			'query' => [
				'llave'
				,'llave2'
				,'llave3'
			]
		]
	];

	//Indicia si se agregan logs de debug para saber como se construye el query y lo que se envia al endpoint,
	//también se puede llamer el método setDebug(true);
	protected $debug = true;

	//En caso necesario puedes crear el metodo beforeInterceptor($config); que lo que hace es ejecutarse antes de enviar
	//el query al endpoint
	protected function beforeInterceptor(array $config) {

	}

	//Del mismo modo tienes el metodo afterInterceptor($response)
	protected function afterResponse($response) {

	}
}
```