<?php
/**
 *
 * The MIT License (MIT)
 *
 * Copyright (c) 2015 Matthias Leuffen, Aachen, Germany
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.

 * For further information about me or my projects visit
 *
 * http://www.leuffen.de
 * https://github.com/dermatthes
 *
 */


class TextTemplate {

    private $mTemplateText;
    private $mFilter = [];
    private $mConf = [
        "varStartTag" => "{{",
        "varEndTag" => "}}",
        "comStartTag" => "{",
        "comEndTag" => "}"
    ];

    public function __construct ($text) {
        $this->mTemplateText = $text;
        $this->mFilter["_DEFAULT_"] = function ($input) { return htmlspecialchars($input); };
        $this->mFilter["raw"] = function ($input) { return html_entity_decode($input); };
    }

    /**
     * @param $filterName
     * @param callable $filterFn
     * @return $this
     */
    public function addFilter ($filterName, callable $filterFn) {
        $this->mFilter[$filterName] = $filterFn;
        return $this;
    }



    private function _getValueByName ($context, $name, $softFail=TRUE) {
        $dd = explode (".", $name);
        $value = $context;
        $cur = "";
        foreach ($dd as $cur) {
            if (is_array($value)) {
                if ( ! isset ( $value[$cur] )) {
                    $value = NULL;
                } else {
                    $value = $value[$cur];
                }

            } else {
                if (is_object($value)) {
                    if ( ! isset ( $value->$cur )) {
                        $value = NULL;
                    } else {
                        $value = $value->$cur;
                    }
                } else {
                    if ( ! $softFail) {
                        throw new Exception("ParsingError: Can't parse element: '{$name}' Error on subelement: '$cur'");
                    }
                    $value = NULL;
                }
            }
        }
        if (is_object($value) && ! method_exists($value, "__toString"))
            $value = "##ERR:OBJECT_IN_TEXT:[{$name}]ON[{$cur}]:" . gettype($value) . "###";

        return $value;
    }



    private function _parseTags ($context, $block, $softFail=TRUE) {
        $result = preg_replace_callback ("/\\{\\{.+?)\\}\\}/im",
            function ($_matches) use ($softFail, $context) {
                $match = $_matches[1];

                $chain = explode("|", $match);
                $chain[] = "_DEFAULT_";

                $varName = array_shift($chain);
                $value = $this->_getValueByName($context, $varName, $softFail);

                foreach ($chain as $curName) {
                    if ( ! isset ($this->mFilter[$curName]))
                        throw new Exception("Filter '$curName' not defined");
                    $fn = $this->mFilter[$curName];
                    $value = $fn($value);
                }

                return $value;
            }, $block);
        return $result;
    }



    private function _runFor ($context, $content, $cmdParam, $softFail=TRUE) {
        if ( ! preg_match ('/([a-z0-9\.\_]+) in ([a-z0-9\.\_]+)/i', $cmdParam, $matches)) {

        }
        $iterateOverName = $matches[2];
        $localName = $matches[1];

        $repeatVal = $this->_getValueByName($context, $iterateOverName, $softFail);


        if ( ! is_array($repeatVal))
            return "";
        $index = 0;
        $result = "";
        foreach ($repeatVal as $key => $curVal) {
            $context[$localName] = $curVal;
            $context["@key"] = $key;
            $context["@index0"] = $index;
            $context["@index1"] = $index+1;
            $curContent = $this->_parseBlock($context, $content, $softFail);
            $curContent = $this->_parseTags($context, $curContent, $softFail);

            $result .= $curContent;
            $index++;
        }
        return $result;
    }


    private function _getItemValue ($compName, $context) {
        if (preg_match ('/^("|\')(.*?)\1$/i', $compName, $matches))
            return $matches[2]; // string Value
        if (is_numeric($compName))
            return ($compName);
        if (strtoupper($compName) == "FALSE")
            return FALSE;
        if (strtoupper($compName) == "TRUE")
            return TRUE;
        if (strtoupper($compName) == "NULL")
            return NULL;
        return $this->_getValueByName($context, $compName);
    }


    private function _runIf ($context, $content, $cmdParam, $softFail=TRUE) {
        if ( ! preg_match('/([\"\']?[a-z0-9\.]*[\"\']?)\s*(==|<|>|!=)\s*([\"\']?[a-z0-9\.]*[\"\']?)/i', $cmdParam, $matches))
            return "!! Invalid command sequence: '$cmdParam' !!";

        $comp1 = $this->_getItemValue($matches[1], $context);
        $comp2 = $this->_getItemValue($matches[3], $context);

        echo $comp1 . $comp2;

        $doIf = FALSE;
        switch ($matches[2]) {
            case "==":
                $doIf = ($comp1 == $comp2);
                break;
            case "!=":
                $doIf = ($comp1 != $comp2);
                break;
            case "<":
                $doIf = ($comp1 < $comp2);
                break;
            case ">":
                $doIf = ($comp1 > $comp2);
                break;
        }

        if ( ! $doIf)
            return "";

        $content = $this->_parseBlock($context, $content, $softFail);
        $content = $this->_parseTags($context, $content, $softFail);
        return $content;

    }


    private function _parseBlock ($context, $block, $softFail=TRUE) {
        $result = preg_replace_callback('/\n?\{([a-z]+)(.*?)\}(.*?)\n?\{\/\1\}\n?/ism',
            function ($matches) use ($context, $softFail) {
                $command = $matches[1];
                $cmdParam = $matches[2];
                $content = $matches[3];

                switch ($command) {
                    case "for":
                        return $this->_runFor($context, $content, $cmdParam, $softFail);

                    case "if":
                        return $this->_runIf ($context, $content, $cmdParam, $softFail);

                    default:
                        return "!! Invalid command: '$command' !!";
                }
            }, $block);
        return $result;
    }


    /**
     * @param $template
     * @return $this
     */
    public function loadTemplate ($template) {
        $this->mTemplateText = $template;
        return $this;
    }


    /**
     * Parse Tokens in Text (Search for $(name.subname.subname)) of
     *
     *
     * @return string
     */
    public function apply ($params, $softFail=TRUE) {

        $text = $this->mTemplateText;
        $text = str_replace("\n\r", "\n", $text);

        $context = $params;

        $text = $this->_parseBlock($context, $text, $softFail);
        $result = $this->_parseTags($context, $text, $softFail);

        return $result;
    }


}