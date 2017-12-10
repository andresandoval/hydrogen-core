<?php
/**
 * Created by PhpStorm.
 * User: Andres
 * Date: 03/12/2017
 * Time: 11:18
 */

namespace Hydrogen\Core\Exceptions;


use Throwable;

class EndpointException extends \Exception {

    public function __construct(string $message = "", int $code = 0, Throwable $previous = null) {
        parent::__construct($message, $code, $previous);
    }

}