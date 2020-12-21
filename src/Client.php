<?php
namespace Nemutagk\Irongraph;

use Nemutagk\Irongraph\Exception\ErrorIrongraphException;

class Client
{
	private static $instance;
	protected $baseURL;
	protected $token;

	private function __construct(string $baseURL) : Client {
		$this->baseURL = $baseURL;

		return $this;
	}

	public static function getInstance(string $baseURL) : Client {
		return empty(self::$instance) ? self::$instance = new self(); : self::$instance;
	}

	public function setToken($token) : Client {
		$this->token = $token;

		return $this;
	}

	public function query(string $query, array $parameters=[], array $config=[]) {
		$config = $this->parseConfig($config);

		if (isset($this->token))
			if (isset($config['headers']))
				$config['headers']['Authorization'] = 'Bearer '.$this->token;

		try {
			$data = file_get_content($this->baseURL, false, stream_context_create([
				'http' => [
					'method' => 'POST'
					,'header' => $config['headers']
					,'content' => json_encode(['query' => $query, 'variables' => $parameters])
				]
			]));

			if (!$data)
				throw Exception("Error al consumir '".$this->baseURL.'"');

			$response = json_decode($data, true);

			$error = json_last_error_msg();

			if (!empty($error))
				throw new Exception('Error al parsear la data: '.$error);

			return $response;
		}catch(Exception $e) {
			exception_error($e);

			throw new ErrorIrongraphException($e->getMessage());
		}
	}

	protected function parseConfig(array $config) : array {
		if (empty($config['headers']))
			$config['headers'] = [];

		return $config;
	}
}