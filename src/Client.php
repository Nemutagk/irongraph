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

	public function __construct(string $baseURL='') {
		if (!empty($baseURL))
			$this->baseURL = $baseURL;

		return $this;
	}

	public function setToken(string $token) : Client {
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

		foreach($parameters as $key => $param) {
			if (!is_array($param))
				$query .= (!empty($query) ? ' ' : '').$param;
			else
				$query .= (!empty($query) ? ' ' : '').$key." {".$this->getParameters($param, ($level+1))."}";
		}

		return $query;
	}

	public function query(string $query, array $parameters=[], array $config=[]) {
		$operationName = substr($query, 6, (strpos($query, '{') - 7));

		try {
			if (method_exists($this, 'beforeInterceptor'))
				$config = $this->beforeInterceptor($config);

			$config = $this->parseConfig($config);

			$client = new HttpClient($config);

			$payload = [
				'json' => [
					'operationName' => $operationName
					,'query' => $query
					,'variables' => $parameters
				]
			];

			Log::info('Payload: '.print_r($payload, true));

			$response = $client->post($this->baseURL, $payload);

			$rawResponse = $response->getBody()->getContents();
			$parseResponse = json_decode($rawResponse, true);

			$finalResponse = ['data'=>$parseResponse['data'], 'raw'=>$rawResponse];

			if (method_exists($this, 'afterInterceptor'))
				$finalResponse = $this->afterInterceptor($finalResponse);

			return $finalResponse;
		}catch(ClientException | RequestException | ServerException $e) {
			exception_error($e);
			throw new ErrorIrongraphException($e->getMessage(), json_decode($e->getResponse()->getBody()->getContents(), true), $e->getResponse()->getStatusCode());
		}catch(Exception $e) {
			exception_error($e);
			throw new ErrorIrongraphException($e->getMessage());
		}
	}

	protected function parseConfig(array $config) : array {
		if (!isset($config['headers']))
			$config['headers'] = [];

		if (!empty($this->token))
			$config['headers']['Authorization'] = 'Bearer '.$this->token;

		return $config;
	}
}