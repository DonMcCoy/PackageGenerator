<?php

namespace WsdlToPhp\PackageGenerator\Parser\SoapClient;

class Structs extends AbstractParser
{
    /**
     * @var string
     */
    const STRUCT_DECLARATION = 'struct';
    /**
     * @var string
     */
    const UNION_DECLARATION = 'union';
    /**
     * @var string
     */
    const ANY_XML_DECLARATION = '<anyXML>';
    /**
     * @var string
     */
    const ANY_XML_TYPE = '\DOMDocument';
    /**
     * @var string[]
     */
    private $definedStructs = array();
    /**
     * Parses the SoapClient types
     * @see \WsdlToPhp\PackageGenerator\Parser\ParserInterface::parse()
     */
    public function parse()
    {
        $types = $this->getGenerator()
            ->getSoapClient()
            ->getSoapClient()
            ->getSoapClient()
            ->__getTypes();
        if (is_array($types)) {
            foreach ($types as $type) {
                $this->parseType($type);
            }
        }
    }
    /**
     * @param string $type
     */
    protected function parseType($type)
    {
        if (!$this->isStructDefined($type)) {
            $cleanType = self::cleanType($type);
            $typeDef = explode(' ', $cleanType);
            if (array_key_exists(1, $typeDef) && !empty($typeDef)) {
                $structName = $typeDef[1];
                if ($typeDef[0] === self::UNION_DECLARATION) {
                    $this->parseUnionStruct($typeDef);
                } elseif ($typeDef[0] === self::STRUCT_DECLARATION) {
                    $this->parseComplexStruct($typeDef);
                } else {
                    $this->getGenerator()->getStructs()->addVirtualStruct($structName);
                }
            }
            $this->structHasBeenDefined($type);
        }
    }
    /**
     * @param array $typeDef
     */
    protected function parseComplexStruct($typeDef)
    {
        $typeDefCount = count($typeDef);
        if ($typeDefCount > 3) {
            for ($i = 2; $i < $typeDefCount; $i += 2) {
                $structParamType = str_replace(self::ANY_XML_DECLARATION, self::ANY_XML_TYPE, $typeDef[$i]);
                $structParamName = $typeDef[$i + 1];
                $this->getGenerator()->getStructs()->addStructWithAttribute($typeDef[1], $structParamName, $structParamType);
            }
        } else {
            $this->getGenerator()->getStructs()->addStruct($typeDef[1]);
        }
    }
    /**
     * union types are passed such as ",dateTime,time" or ",PMS_ResStatusType,TransactionActionType,UpperCaseAlphaLength1to2"
     * @param array $typeDef
     */
    protected function parseUnionStruct($typeDef)
    {
        $typeDefCount = count($typeDef);
        if ($typeDefCount === 3) {
            $unionName = $typeDef[1];
            $unionTypes = array_filter(explode(',', $typeDef[2]), function ($type) {
                return !empty($type);
            });
            sort($unionTypes);
            $this->getGenerator()->getStructs()->addUnionStruct($unionName, $unionTypes);
        }
    }
    /**
     * Remove useless break line, tabs
     * Remove curly braces
     * Remove brackets
     * Adds space before semicolon to parse it
     * Remove duplicated spaces
     * @param string $type
     * @return string
     */
    protected static function cleanType($type)
    {
        $type = str_replace(array(
            "\r",
            "\n",
            "\t",
            '{',
            '}',
            '[',
            ']',
            ';',
        ), '', $type);
        $type = preg_replace('/[\s]+/', ' ', $type);
        return trim($type);
    }
    /**
     * @param string $type
     * @return boolean
     */
    private function isStructDefined($type)
    {
        return in_array(self::typeSignature($type), $this->definedStructs);
    }
    /**
     * @param string $type
     * @return Structs
     */
    private function structHasBeenDefined($type)
    {
        $this->definedStructs[] = self::typeSignature($type);
        return $this;
    }
    /**
     * @param string $type
     * @return string
     */
    private static function typeSignature($type)
    {
        return md5($type);
    }
}
