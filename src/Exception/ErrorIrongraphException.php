<?php
namespace Nemutagk\Irongraph\Exception;

use Exception;

class ErrorIrongraphException extends Exception
{
	protected $error;

	public function __construct($message, $error=null, $code=0, Exception $previus=null)) {
		$this->error = $error;

		parent::__construct($message, $code, $previus);
	}

	public function getError() {
		return $this->error;
	}
}