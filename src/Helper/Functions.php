<?php

if (!function_exists('exception_error')) {
	function exception_error($e) {
		$class = explode('\\',get_class($e));

		$payload = [
		 	'error'=>$e->getMessage()
		 	,'file'=>$e->getFile()
		 	,'line'=>$e->getLine()
		 	,'code'=>$e->getCode()
		 	,'trace' => $e->getTraceAsString()
		 ];

		 if (method_exists($e, 'getResponse'))
		 	$payload['http_response'] = $e->getResponse();

		 if (method_exists($e, 'getHttpCode'))
		 	$payload['http_code_response'] = $e->getHttpCode();

		 \Log::error('Error en sistema ('.array_pop($class).'): ',$payload);
	}
}

if (!function_exists('return_exception_error')) {
	function return_exception_error($e, $code=500) {
		Log::info('return_exception_error');

		$payload = [
			'success' => false
			,'message' => 'Error al procesar el request'
			,'error' => $e->getMessage()
			,'trace' => explode('#', $e->getTraceAsString())
		];

		if (env('APP_ENV') == 'production') {
			unset($payload['trace']);

			if (strpost($payload['error'], 'SQLSTATE') !== false)
				$payload['error'] = 'database error';
		}

		return response($payload, $code);
	}
}