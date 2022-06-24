<?php

namespace Yoder\YIPS;

defined('ABSPATH') || exit;

/**
 * Class UserRoles
 * @package Yoder\YIPS
 */

class Helper
{
    public static function get_headers_for_email($from_email, $from_name = '')
    {
        if (empty($from_email)) {
            throw new \Exception('From email is missing');
        }

        if (empty($from_name)) {
            $from_name = get_bloginfo('name');
        }

        $headers  = "From: {$from_name} <{$from_email}>\n";
        //$headers .= "Cc: testsite <mail@testsite.com>\n";
        //$headers .= "X-Sender: testsite <mail@testsite.com>\n";
        $headers .= 'X-Mailer: PHP/' . phpversion();
        $headers .= "X-Priority: 1\n"; // Urgent message!
        //$headers .= "Return-Path: mail@testsite.com\n"; // Return path for errors
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=iso-8859-1\n";

        return $headers;
    }

    /**
     * Create plain PHP associative array from XML.
     *
     * Example usage:
     *   $xmlNode = simplexml_load_file('example.xml');
     *   $arrayData = xmlToArray($xmlNode);
     *   echo json_encode($arrayData);
     *
     * @param SimpleXMLElement $xml The root node
     * @param array $options Associative array of options
     * @return array
     * @link http://outlandishideas.co.uk/blog/2012/08/xml-to-json/ More info
     * @author Tamlyn Rhodes <http://tamlyn.org>
     * @license http://creativecommons.org/publicdomain/mark/1.0/ Public Domain
     */

    public static function xmlToArray($xml, $options = array())
    {
        $defaults = array(
            'namespaceRecursive' => false,  //setting to true will get xml doc namespaces recursively
            'removeNamespace' => false,     //set to true if you want to remove the namespace from resulting keys (recommend setting namespaceSeparator = '' when this is set to true)
            'namespaceSeparator' => ':',    //you may want this to be something other than a colon
            'attributePrefix' => '@',       //to distinguish between attributes and nodes with the same name
            'alwaysArray' => array(),       //array of xml tag names which should always become arrays
            'autoArray' => true,            //only create arrays for tags which appear more than once
            'textContent' => '$',           //key used for the text content of elements
            'autoText' => true,             //skip textContent key if node has no attributes or child nodes
            'keySearch' => false,           //optional search and replace on tag and attribute names
            'keyReplace' => false           //replace values for above search values (as passed to str_replace())
        );
        $options = array_merge($defaults, $options);
        $namespaces = $xml->getDocNamespaces($options['namespaceRecursive']);
        $namespaces[''] = null; //add base (empty) namespace

        //get attributes from all namespaces
        $attributesArray = array();
        foreach ($namespaces as $prefix => $namespace) {
            if ($options['removeNamespace']) {
                $prefix = '';
            }
            foreach ($xml->attributes($namespace) as $attributeName => $attribute) {
                //replace characters in attribute name
                if ($options['keySearch']) {
                    $attributeName =
                        str_replace($options['keySearch'], $options['keyReplace'], $attributeName);
                }
                $attributeKey = $options['attributePrefix']
                    . ($prefix ? $prefix . $options['namespaceSeparator'] : '')
                    . $attributeName;
                $attributesArray[$attributeKey] = (string)$attribute;
            }
        }

        //get child nodes from all namespaces
        $tagsArray = array();
        foreach ($namespaces as $prefix => $namespace) {
            if ($options['removeNamespace']) {
                $prefix = '';
            }

            foreach ($xml->children($namespace) as $childXml) {
                //recurse into child nodes
                $childArray = self::xmlToArray($childXml, $options);
                $childTagName = key($childArray);
                $childProperties = current($childArray);

                //replace characters in tag name
                if ($options['keySearch']) {
                    $childTagName =
                        str_replace($options['keySearch'], $options['keyReplace'], $childTagName);
                }

                //add namespace prefix, if any
                if ($prefix) {
                    $childTagName = $prefix . $options['namespaceSeparator'] . $childTagName;
                }

                if (!isset($tagsArray[$childTagName])) {
                    //only entry with this key
                    //test if tags of this type should always be arrays, no matter the element count
                    $tagsArray[$childTagName] =
                        in_array($childTagName, $options['alwaysArray'], true) || !$options['autoArray']
                        ? array($childProperties) : $childProperties;
                } elseif (
                    is_array($tagsArray[$childTagName]) && array_keys($tagsArray[$childTagName])
                    === range(0, count($tagsArray[$childTagName]) - 1)
                ) {
                    //key already exists and is integer indexed array
                    $tagsArray[$childTagName][] = $childProperties;
                } else {
                    //key exists so convert to integer indexed array with previous value in position 0
                    $tagsArray[$childTagName] = array($tagsArray[$childTagName], $childProperties);
                }
            }
        }

        //get text content of node
        $textContentArray = array();
        $plainText = trim((string)$xml);
        if ($plainText !== '') {
            $textContentArray[$options['textContent']] = $plainText;
        }

        //stick it all together
        $propertiesArray = !$options['autoText'] || $attributesArray || $tagsArray || ($plainText === '')
            ? array_merge($attributesArray, $tagsArray, $textContentArray) : $plainText;

        //return node as array
        return array(
            $xml->getName() => $propertiesArray
        );
    }

    public static function xmlToJson($xml, $options = array())
    {
        $xml_to_array = self::xmlToArray($xml, $options);
        $xml_to_json = json_encode($xml_to_array);
        return $xml_to_json;
    }

    //function to decode the Json response.
    public static function jsonDecode($json_string)
    {
        //Decode the Json Response.
        if (empty($json_string)) {
            return null;
        } else {
            $json = json_decode($json_string, true);
        }
        return $json;
    }

    //function to encode the string into Json
    public static function jsonEncode($json_string)
    {
        //Encode the Json Response.
        $json = json_encode($json_string);
        return $json;
    }
}
