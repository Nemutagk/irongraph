<?php
namespace Nemutagk\Irongraph\Exception;

use Exception;

class ErrorIrongraphException extends Exception
{
	protected $response;
	protected $httpCode;

	public function __construct($message, $response=null, $httpCode=0, Exception $previus=null) {
		$this->response = $response;
		$this->httpCode = $httpCode;

		parent::__construct($message, $httpCode, $previus);
	}

	public function getResponse() {
		return $this->response;
	}

	public function getHttpCode() {
		return $this->httpCode;
	}
}