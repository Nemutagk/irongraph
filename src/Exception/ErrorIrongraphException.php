<?php
namespace Nemutagk\Irongraph\Exception;

use Exception;

class ErrorIrongraphException extends Exception
{
	protected $response;
	protected $payload;

	public function __construct($message, $response=null, $payload=null, $code=0, Exception $previus=null) {
		$this->response = $response;
		$this->payload = $payload;

		parent::__construct($message, $code, $previus);
	}

	public function getPayload() {
		return $this->payload;
	}

	public function getResponse() {
		return $this->response;
	}
}