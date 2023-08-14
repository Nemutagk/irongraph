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
	protected $format = false;

	public function __construct(string $baseURL='') {
		if (!empty($baseURL))
			$this->baseURL = $baseURL;

		return $this;
	}

	public function setToken($token) : Client {
		$this->token = $token;

		return $this;
	}

	public function setFormat($format) {
		$this->format = $format;

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

	public function setTesting(bool $testing) : Client {
		$this->testing = $testing;

		return $this;
	}

	protected function getParameters($parameters = [], $level=1) : string {
		$query = '';

		if (count($parameters) == 0)
			$parameters = $this->parameters;

		foreach($parameters as $key => $param) {
			for($i=0;$i<$level;$i++) {
				$query .= $this->format ? "\t" : " ";
			}
			if (!is_array($param))
				$query .= $param.($this->format ? " \n" : " ");
			else {
				$query .= " ".$key." { ".($this->format ? "\n" : "").$this->getParameters($param, ($level+1));
				for($i=0;$i<$level;$i++) {
					$query .= $this->format ? "\t" : " ";
				}
				$query .= " } ".($this->format ? "\n" :  "");
			}
		}

		if ($this->debug)
			Log::info('Query: '.$query);

		return $query;
	}

	public function query(string $query, array $parameters=[], array $config=[]) {
		$query = preg_replace("/\s+/", " ", $query);

		if (strpos($query, 'query') !== false)
			$operationName = substr($query, 6, (strpos($query, '{') - 7));
		else
			$operationName = substr($query, 9, (strpos($query, '{') - 10));

		if ($this->debug) Log::info('OperationName: '.$operationName);

		try {
			if (method_exists($this, 'beforeInterceptor'))
				$config = $this->beforeInterceptor($config);

			$config = $this->parseConfig($config);

			if ($this->debug) Log::info('Config: ', $config ?? []);

			$testing = !property_exists($this, 'testing') ? env('IRONGRAPH_TESTING', false) : $this->testing;

			if (env('APP_ENV') == 'testing')
				$testing = true;

			if ($testing && method_exists($this, 'mockup')) {
				$response = $this->mockup($config);

				if (method_exists($this,'afterInterceptor'))
					$response = $this->afterInterceptor($response);

				if (isset($response['errors']))
					throw new ErrorIrongraphException($response['errors'][0]['message'], $response, 500);

				return $response;
			}

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
			$rawBody = $rawResponse->getBody()->getContents();
			$response = json_decode($rawBody, true);

			if (method_exists($this, 'afterInterceptor'))
				$response = $this->afterInterceptor($response);

			if ($this->debug) Log::info('Response: '.print_r($response, true));

			if (isset($response['errors']))
				throw new ErrorIrongraphException($response['errors'][0]['message'], $response, $payload, 500);
			
			return $response;
		}catch(ClientException | RequestException | ServerException $e) {
			// exception_error($e);
			$exception_payload = isset($payload) ? $payload : null;
			throw new ErrorIrongraphException($e->getMessage(), json_decode($e->getResponse()->getBody()->getContents(), true), $exception_payload, $e->getResponse()->getStatusCode());
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