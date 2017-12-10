<?php
/**
 * Created by PhpStorm.
 * User: Andres
 * Date: 25/11/2017
 * Time: 12:53
 */

namespace Hydrogen\Core\Helpers;


final class RegexpHelper {

    private static $ANNOTATION_TOKEN_SCHEMA = "a-z-_.";

    public static function getterToPropertyName(string $getterName): string {
        $getterName = \preg_replace("/^get/", "", $getterName);
        return \lcfirst($getterName);
    }

    public static function setterToPropertyName(string $setterName): string {
        $setterName = \preg_replace("/^set/", "", $setterName);
        return \lcfirst($setterName);
    }

    public static function isGetterMethod($methodName): bool{
        return \preg_match("/^get.+$/", $methodName);
    }

    public static function isSetterMethod($methodName): bool{
        return \preg_match("/^set.+$/", $methodName);
    }

    private static function getDocObjectProperties(string $docObjectBody): ?array {
        if (\is_null($docObjectBody) || !\is_string($docObjectBody) || \strlen($docObjectBody) <= 0)
            return null;
        if (!\preg_match_all('/(@[' . self::$ANNOTATION_TOKEN_SCHEMA . ']+)(?:\s+)?([^\n]+)?/i', $docObjectBody, $properties, \PREG_SET_ORDER, 0))
            return null;
        if (\is_null($properties) || !\is_array($properties) || \count($properties) <= 0)
            return null;
        $propertiesArr = [];
        foreach ($properties as $prop) {
            if (!\is_null($prop) && \is_array($prop) && \count($prop) > 1 && \count($prop) < 4) {
                $tmpKey = \trim((string)$prop[1]);
                $tmpValue = \trim((string)($prop[2] ?? ""));
                if (!isset($propertiesArr[$tmpKey]))
                    $propertiesArr[$tmpKey] = [];
                $propertiesArr[$tmpKey][] = $tmpValue;
            }
        }
        return \count($propertiesArr) > 0 ? $propertiesArr : null;
    }

    private static function getDocObjectBody(string $docObject): ?string {
        if (!\preg_match("/^@[" . self::$ANNOTATION_TOKEN_SCHEMA . "]+\s*\(.*\)$/is", $docObject))
            return null; // i'm not an object
        if (!\preg_match_all("/^\s*(?:@[" . self::$ANNOTATION_TOKEN_SCHEMA . "]+)\s*\((.*)\)\s*$/is", $docObject, $docObjectBody, \PREG_SET_ORDER, 0))
            return null; //fail at get body
        if (\is_null($docObjectBody) || !\is_array($docObjectBody) || \count($docObjectBody) != 1)
            return null;
        $docObjectBody = $docObjectBody[0];
        if (\is_null($docObjectBody) || !\is_array($docObjectBody) || \count($docObjectBody) != 2)
            return null;
        $docObjectBody = $docObjectBody[1];
        if (\is_null($docObjectBody) || !\is_string($docObjectBody))
            return null;
        return $docObjectBody;
    }

    private static function getDocObjects(string &$doc, bool $cleanUp = false): ?array {
        $pattern = "/(@[" . self::$ANNOTATION_TOKEN_SCHEMA . "]+)\((?:[^()]|(?R))*\)/i";
        if (!\preg_match_all($pattern, $doc, $objects, \PREG_SET_ORDER, 0))
            return null;
        if (\is_null($objects) || !\is_array($objects) || \count($objects) <= 0)
            return null;
        if ($cleanUp)
            $doc = \preg_replace($pattern, "", $doc);
        $validObjects = [];
        foreach ($objects as $object) {
            if (!\is_null($object) && \is_array($object) || \count($object) == 2)
                $validObjects[\trim((string)$object[1])] = \trim((string)$object[0]);
        }
        return \count($validObjects) <= 0 ? null : $validObjects;
    }

    private static function docToArray(string $doc, string $metaNodeName = null): ?array {
        if (!\is_null($metaNodeName)) {
            $mainObjects = self::getDocObjects($doc);
            if (\is_null($mainObjects))
                return null;
            foreach ($mainObjects as $key => $obj) {
                if (\preg_match("/{$metaNodeName}/i", $key))
                    return self::docToArray($obj);
            }
            return null;
        }
        $doc = self::getDocObjectBody($doc);
        if (\is_null($doc))
            return null;
        if(\strlen(\trim($doc)) <= 0)
            return [];
        $finalArray = [];
        $subObjects = self::getDocObjects($doc, true);
        if (!\is_null($subObjects)) {
            foreach ($subObjects as $key => $obj) {
                if (!isset($finalArray[$key]))
                    $finalArray[$key] = [];
                $finalArray[$key][] = self::docToArray($obj);
            }
        }
        $subProperties = self::getDocObjectProperties($doc);
        if (!\is_null($subProperties)) {
            foreach ($subProperties as $key => $prop) {
                $finalArray[$key] = $prop;
            }
        }
        return \count($finalArray) > 0 ? $finalArray : null;
    }

    private static function getCleanDocText(string $doc) {
        return \preg_replace("/((?:^\s*\*+\/*)|(?:^\s*\/\*+))/m", "", $doc);
    }

    public static function processDoc(string $doc, string $metaNodeName): ?array {
        if (\is_null($doc) || \strlen($doc) <= 0 || \is_null($metaNodeName) || \strlen($metaNodeName) <= 0)
            return null;
        if (!\preg_match("/{$metaNodeName}/i", $doc))
            return null;
        $cleanDoc = self::getCleanDocText($doc);
        if (\is_null($doc) || \strlen($doc) <= 0)
            return null;
        return self::docToArray($cleanDoc, $metaNodeName);
    }

    public static function restControllerPathMatch(string $componentPath, string $inputPath): bool {
        $componentPath = "$componentPath.*";
        $componentPath = \preg_replace("/\//", "\/", $componentPath);
        return \preg_match("/$componentPath/", $inputPath) != false;
    }

    public static function requestMappingPathMatch(string $methodPath, string $inputPath): bool {
        $methodPath = \preg_replace('/\?.*$/', "", $methodPath);
        $methodPath = \preg_replace("/\//", "\/", $methodPath);
        $methodPath = \preg_replace("/\{[^\}]+\}/", "[^\/]+", $methodPath);
        return \preg_match("/^$methodPath$/", $inputPath) != false;
    }

    private static function getArgumentSectionName(string $methodPart): ?string {
        if (\preg_match("/^\{[^\}]+\}$/", $methodPart) == false)
            return null;
        return \preg_replace("/[\{\}]/", "", $methodPart);
    }

    public static function getInputPathVariables(string $definitionPath, string $inputPath): ?array {
        if (\is_null($definitionPath) || \is_null($inputPath) || !\is_string($definitionPath) ||
            !\is_string($inputPath) || \strlen($definitionPath) <= 0 || \strlen($inputPath) <= 0
        )
            return null;
        $definitionPath = \preg_replace("/^\//", "", $definitionPath);
        $inputPath = \preg_replace("/^\//", "", $inputPath);

        $methodPathArray = \preg_split("/\//", $definitionPath);
        $inputPathArray = \preg_split("/\//", $inputPath);
        if ($methodPathArray == false || $inputPathArray == false || !\is_array($methodPathArray) ||
            !\is_array($inputPathArray) || \count($methodPathArray) != \count($inputPathArray)
        )
            return null;
        $argumentArray = [];
        for ($i = 0; $i < \count($methodPathArray); $i++) {
            $tmpArgumentName = self::getArgumentSectionName($methodPathArray[$i]);
            if (!\is_null($tmpArgumentName))
                $argumentArray[$tmpArgumentName] = $inputPathArray[$i];
        }
        return $argumentArray;
    }

    public static function processParameterDoc(string $definition): ?array {
        if (\is_null($definition) || !\is_string($definition) || \strlen($definition) <= 0)
            return null;
        if (\preg_match('/^\s*\$?\w+\s*$/', $definition)) {
            return [
                "source" => \trim($definition),
                "target" => \trim($definition)
            ];
        } else if (\preg_match_all('/((?:\w+)|(?:\{\w+\}))(?:\s*(?:\-|\=)\>\s*)(\$?\w+)/', $definition, $sections, \PREG_SET_ORDER, 0)) {
            if (\is_null($sections) || !\is_array($sections) || \count($sections) != 1)
                return null;
            $sections = $sections[0];
            if (\is_null($sections) || !\is_array($sections) || \count($sections) != 3)
                return null;
            $source = \trim((string)$sections[1]);
            $target = \trim((string)$sections[2]);
            if (\preg_match('/^\{\w+\}$/', $source)) {
                $source = \preg_replace("/[{}]/", "", $source);
                if (\is_null($source))
                    return null;
            }
            if (\preg_match('/^\$\w+$/', $target)) {
                $target = \preg_replace('/^\$/', "", $target);
                if (\is_null($target))
                    return null;
            }
            return [
                "source" => $source,
                "target" => $target
            ];

        } elseif (\preg_match('/^\w+$/', $definition)) {
            return [
                "source" => $definition,
                "target" => $definition
            ];
        }
        return null;
    }

}