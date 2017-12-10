<?php
/**
 * Created by PhpStorm.
 * User: asandoval
 * Date: 29/11/2017
 * Time: 8:48
 */

namespace Hydrogen\Core\Helpers;


use Hydrogen\Core\Exceptions\ClassNotFoundException;
use Hydrogen\Core\Exceptions\MissingParameterException;
use Hydrogen\Core\Model\Metadata\Bean\IgnoreNodeMetadata;

final class BeanHelper {

    /**
     * @param object $bean Objeto a convertir en array
     * @param bool   $root [optional] <p>Indica si el objeto enviado es el nodo raiz del ararya generar, por defecto
     *     <b>true</b></p>
     * @param bool   $useMetadata [optional] <p>Indica si se debe usar el Metadata presente en las propiedades del
     *     objeto, por defecto <b>true</b></p>
     * @return array Retorna un array asociativo multidimensional que representa a <b>$bean</b>
     */
    private static function beanToArray($bean, bool $root = true, bool $useMetadata = true): array {
        if (\is_null($bean))
            return $root ? [] : null;
        if (\is_array($bean))
            return $bean;
        if (!\is_object($bean)) {
            $beanValue = (string)$bean;
            return $root ? [$beanValue] : $beanValue;
        }

        $reflectionObject = new \ReflectionClass($bean);
        $publicMethods = $reflectionObject->getMethods(\ReflectionMethod::IS_PUBLIC);
        $getterMethods = \array_filter($publicMethods,
            function (\ReflectionMethod $method) use ($useMetadata) {
                if ($method->getNumberOfParameters() <= 0 && RegexpHelper::isGetterMethod($method->getName())) {
                    if (!$useMetadata)
                        return true;
                    $doc = $method->getDocComment();
                    if (false == $doc)
                        return true;
                    $ignoreNodeMeta = IgnoreNodeMetadata::fromDoc($doc);
                    return \is_null($ignoreNodeMeta);
                }
                return false;
            });

        $array = [];
        foreach ($getterMethods as $method) {
            $tmpArrayKey = RegexpHelper::getterToPropertyName($method->getName());
            $tmpArrayValue = $method->invoke($bean);
            $array[$tmpArrayKey] = $tmpArrayValue;
        }
        $array = \array_map(function ($value) use ($useMetadata) {
            if (\is_object($value))
                return self::beanToArray($value, false, $useMetadata);
            return $value;
        },
            $array);
        return $array;
    }


    /**
     * @param array  $array
     * @param string $beanClassName
     * @param bool   $useMetadata
     * @return null|object
     * @throws ClassNotFoundException|MissingParameterException
     */
    public static function arrayToBean(array $array, string $beanClassName, bool $useMetadata = true) {
        if (\is_null($array) || !\is_array($array))
            return null;
        if (!\class_exists($beanClassName))
            throw new ClassNotFoundException("Class $beanClassName not exists", null);
        $beanReflectionObj = new \ReflectionClass($beanClassName);
        if (!\is_null($beanReflectionObj->getConstructor()) &&
            $beanReflectionObj->getConstructor()->getNumberOfParameters() > 0)
            throw new MissingParameterException("Too many parameters in constructor on $beanClassName");
        $beanInstance = $beanReflectionObj->newInstance();
        $beanMethods = $beanReflectionObj->getMethods(\ReflectionMethod::IS_PUBLIC);
        $beanSetters = \array_filter($beanMethods,
            function (\ReflectionMethod $method) use ($useMetadata) {
                if ($method->getNumberOfParameters() == 1 && RegexpHelper::isSetterMethod($method->getName())) {
                    if (!$useMetadata)
                        return true;
                    $doc = $method->getDocComment();
                    if (false == $doc)
                        return true;
                    $ignoreNodeMeta = IgnoreNodeMetadata::fromDoc($doc);
                    return \is_null($ignoreNodeMeta);
                }
                return false;
            });
        if (\is_array($beanSetters) && \count($beanSetters) > 0) {
            foreach ($beanSetters as $beanSetter) {
                $arrayKey = RegexpHelper::setterToPropertyName($beanSetter->getName());

                if (isset($array[$arrayKey])) {
                    $arrayValue = $array[$arrayKey];
                    $setterParameter = $beanSetter->getParameters()[0];

                    if ($setterParameter->hasType()) {
                        if (\is_array($arrayValue)) {
                            $arrayValue = self::arrayToBean($arrayValue,
                                $setterParameter->getClass()->getName(),
                                $useMetadata);
                            if (!\is_null($arrayValue))
                                $beanSetter->invokeArgs($beanInstance, [$arrayValue]);
                        }
                    } else {
                        $beanSetter->invokeArgs($beanInstance, [$arrayValue]);
                    }
                }
            }
        }
        return $beanInstance;
    }

    /**
     * @param $bean
     * @return string
     */
    public static function beanToJson($bean): string {
        return \json_encode(self::beanToArray($bean));
    }

}