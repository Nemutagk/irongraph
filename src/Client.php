<?php
namespace Nemutagk\Irongraph;

use Log;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\{ClientException,RequestException,ServerException};
use Nemutagk\Irongraph\Exception\ErrorIrongraphException;

class Client
{
	private static $instance;
	protected $baseURL;
	protected $token;

	public function __construct(string $baseURL='') {
		if (!empty($baseURL))
			$this->baseURL = $baseURL;

		return $this;
	}

	public static function getInstance(string $baseURL) : Client {
		return empty(self::$instance) ? self::$instance = new self($baseURL) : self::$instance;
	}

	public function setToken($token) : Client {
		$this->token = $token;

		return $this;
	}

	public function setParameters(array $parameters) : Client {
		$this->parameters = $parameters;

		return $this;
	}

	protected function getParameters($parameters = [], $level=1) : string {
		$query = '';

		if (count($parameters) == 0)
			$parameters = $this->parameters;

		$tab = '';

		for($i=0; $i<$level; $i++) {
			$tab .= "\t";
		}

		foreach($parameters as $key => $param) {
			if (!is_array($param))
				$query .= $tab.$param."\n\t";
			else
				$query .= $tab.$key." {\n\t".$this->getParameters($param, ($level+1)).$tab."}\n\t";
		}

		return $query;
	}

	public function query(string $query, array $parameters=[], array $config=[]) {
		$operationName = substr($query, 7, (strpos($query, '{') - 8));

		try {
			$client = new HttpClient($this->parseConfig($config));

			$payload = [
				'json' => [
					'operationName' => $operationName
					,'query' => $query
					,'variables' => $parameters
				]
			];

			Log::info('GrapQL Params: ', $payload);

			$response = $client->post($this->baseURL, $payload);

			return json_decode($response->getBody()->getContents(), true);
		}catch(ClientException | RequestException | ServerException $e) {
			// exception_error($e);
			throw new ErrorIrongraphException($e->getMessage(), json_decode($e->getResponse()->getBody()->getContents(), true), $e->getResponse()->getStatusCode());
		}catch(Exception $e) {
			// exception_error($e);
			throw new ErrorIrongraphException($e->getMessage());
		}
	}

	protected function parseConfig(array $config) : array {
		if (!isset($config['header']))
			$config['header'] = [];

		if (!empty($this->token))
			$config['header']['Authorization'] = 'Bearer '.$this->token;

		return $config;
	}
}