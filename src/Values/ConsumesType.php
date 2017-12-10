<?php
/**
 * Created by PhpStorm.
 * User: Andres
 * Date: 28/11/2017
 * Time: 23:10
 */

namespace Hydrogen\Core\Values;


class ConsumesType {

    const JSON = "__INPUT-JSON__"; //DEFAULT
    const XFORM = "__INPUT-X-WWW-FORM-URLENCODED__";

    public static function validate($consumeType) {
        switch ($consumeType) {
            case ConsumesType::JSON:
                return true;
            case ConsumesType::XFORM:
                return true;
        }
        return false;
    }

}