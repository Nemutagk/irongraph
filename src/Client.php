<?php
namespace Nemutagk\Irongraph;

use Log;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\{ClientException,RequestException,ServerException};
use Nemutagk\Irongraph\Exception\ErrorIrongraphException;

class Client
{
	protected $baseURL;
	protected $token;
	protected $debug = false;

	public function __construct(string $baseURL='') {
		if (!empty($baseURL))
			$this->baseURL = $baseURL;

		return $this;
	}

	public function setToken($token) : Client {
		$this->token = $token;

		return $this;
	}

	public function setParameters(array $parameters) : Client {
		$this->parameters = $parameters;

		return $this;
	}

	public function setDebug(bool $debug) : Client {
		$this->debug = $debug;

		return $this;
	}

	protected function getParameters($parameters = [], $level=1) : string {
		$query = '';

		if (count($parameters) == 0)
			$parameters = $this->parameters;

		foreach($parameters as $key => $param) {
			if (!is_array($param))
				$query .= $param." ";
			else
				$query .= " ".$key." {".$this->getParameters($param, ($level+1))."}";
		}

		if ($this->debug)
			Log::info('Query: '.$query);

		return $query;
	}

	public function query(string $query, array $parameters=[], array $config=[]) {
		$operationName = substr($query, 6, (strpos($query, '{') - 7));

		if ($this->debug) Log::info('OperationName: '.$operationName);

		try {
			if (method_exists($this, 'beforeInterceptor'))
				$config = $this->beforeInterceptor($config);

			$config = $this->parseConfig($config);

			if ($this->debug) Log::info('Config: ', $config ?? []);

			$client = new HttpClient($config);

			$payload = [
				'json' => [
					'operationName' => $operationName
					,'query' => $query
					,'variables' => $parameters
				]
			];

			if ($this->debug) Log::info('GraphQL Payload: ', $payload);

			$rawResponse = $client->post($this->baseURL, $payload);

			$response = json_decode($rawResponse->getBody()->getContents(), true);

			if (method_exists($this, 'afterInterceptor'))
				$response = $this->afterInterceptor($response);

			if ($this->debug) Log::info('Response: ', $response);

			return $response;
		}catch(ClientException | RequestException | ServerException $e) {
			// exception_error($e);
			throw new ErrorIrongraphException($e->getMessage(), json_decode($e->getResponse()->getBody()->getContents(), true), $e->getResponse()->getStatusCode());
		}catch(Exception $e) {
			// exception_error($e);
			throw new ErrorIrongraphException($e->getMessage());
		}
	}

	protected function parseConfig(array $config) : array {
		if (!isset($config['headers']))
			$config['headers'] = [];

		if (!empty($this->token))
			$config['headers']['authorization'] = 'Bearer '.$this->token;

		return $config;
	}
}