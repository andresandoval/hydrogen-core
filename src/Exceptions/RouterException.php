<?php
/**
 * Created by PhpStorm.
 * User: Andres
 * Date: 30/11/2017
 * Time: 23:15
 */

namespace Hydrogen\Core\Exceptions;


use Throwable;

class RouterException extends \Exception {

    public function __construct($message = "", $code = 0, Throwable $previous = null) {
        parent::__construct($message, $code, $previous);
    }

}