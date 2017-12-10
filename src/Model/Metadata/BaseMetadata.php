<?php
/**
 * Created by PhpStorm.
 * User: Andres
 * Date: 25/11/2017
 * Time: 8:50
 */

namespace Hydrogen\Core\Model\Metadata;


abstract class BaseMetadata {

    public function __construct() {
    }

    protected final static function getConstantValue($constantName, $from = null) {
        if(\is_null($constantName))
            return null;
        $fullName = \is_null($from) ? $constantName : "$from::$constantName";
        return \defined($fullName) ? \constant($fullName) : null;
    }

    public abstract static function fromDoc(string $doc);


}