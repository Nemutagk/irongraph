<?php
namespace Nemutagk\Irongraph\Exception;

use Exception;

class ErrorIrongraphException extends Exception
{
	protected $response;

	public function __construct($message, $response=null, $code=0, Exception $previus=null) {
		$this->response = $response;

		parent::__construct($message, $code, $previus);
	}

	public function getResponse() {
		return $this->response;
	}
}