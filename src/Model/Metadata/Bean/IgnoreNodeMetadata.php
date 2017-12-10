<?php
/**
 * Created by PhpStorm.
 * User: asandoval
 * Date: 05/12/2017
 * Time: 16:06
 */

namespace Hydrogen\Core\Model\Metadata\Bean;


use Hydrogen\Core\Helpers\RegexpHelper;
use Hydrogen\Core\Model\Metadata\BaseMetadata;

class IgnoreNodeMetadata extends BaseMetadata {

    private static $metaName = "@Ignore";

    /**
     * @param string $doc
     * @return IgnoreNodeMetadata|null
     */
    public static function fromDoc(string $doc) {
        $docArray = RegexpHelper::processDoc($doc, self::$metaName);
        if (\is_null($docArray))
            return null;
        return new self();
    }

}