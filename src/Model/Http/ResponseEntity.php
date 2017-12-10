<?php
/**
 * Created by PhpStorm.
 * User: Andres
 * Date: 25/11/2017
 * Time: 11:05
 */

namespace Hydrogen\Core\Model\Http;


final class ResponseEntity {

    private $httpStatus;
    private $body;

    /**
     * ResponseEntity constructor.
     * @param int $httpStatus
     * @param     $body
     */
    public function __construct(int $httpStatus, $body = null) {
        $this->httpStatus = $httpStatus;
        $this->body = $body;
    }

    /**
     * @return int
     */
    public function getHttpStatus(): int {
        return $this->httpStatus;
    }

    /**
     * @return null
     */
    public function getBody() {
        return $this->body;
    }
}