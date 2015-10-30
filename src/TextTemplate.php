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
 *
 * For further information about me or my projects visit
 *
 * http://leuffen.de
 * https://github.com/dermatthes
 *
 */

namespace de\leuffen\text_template;

class TextTemplate {

    const VERSION = "1.0.1";


    private $mTemplateText;
    private $mFilter = [];
    private $mConf = [
        "varStartTag" => "{=",
        "varEndTag" => "}",
        "comStartTag" => "{",
        "comEndTag" => "}"
    ];

    public function __construct ($text="") {
        $this->mTemplateText = $text;
        $this->mFilter["_DEFAULT_"] = function ($input) { return htmlspecialchars($input); };

        // Raw is only a pseudo-filter. If it is not in the chain of filters, __DEFAULT__ will be appended to the filter
        $this->mFilter["raw"] = function ($input) { return $input; };
    }

    /**
     * Add a user-defined filter function to the list of available filters.
     *
     * A filter function must accept at least one parameter: input and return the resulting
     * value.
     *
     * Example:
     *
     * addFilter("currency", function (input) {
     *      return number_format ($input, 2, ",", ".");
     * });
     *
     * @param $filterName
     * @param callable $filterFn
     * @return $this
     */
    public function addFilter ($filterName, callable $filterFn) {
        $this->mFilter[$filterName] = $filterFn;
        return $this;
    }


    /**
     * Tag-Nesting is done by initially adding an index to both the opening and the
     * closing tag. (By the way some cleanup is done)
     *
     * Example
     *
     * {if xyz}
     * {/if}
     *
     * Becomes:
     *
     * {if0 xyz}
     * {/if0}
     *
     * This trick makes it possible to add tag nesting functionality
     *
     *
     * @param $input
     * @return mixed
     * @throws \Exception
     */
    public function _replaceNestingLevels ($input) {
        $indexCounter = 0;
        $nestingIndex = [];

        $lines = explode("\n", $input);
        for ($li=0; $li < count ($lines); $li++) {
            $lines[$li] = preg_replace_callback('/\{(?!=)\s*(\/?)\s*([a-z]+)(.*?)\}/im',
                function ($matches) use (&$nestingIndex, &$indexCounter, &$li) {
                    $slash = $matches[1];
                    $tag = $matches[2];
                    $rest = $matches[3];
                    if ($slash == "") {
                        if ( ! isset ($nestingIndex[$tag]))
                            $nestingIndex[$tag] = [];
                        $nestingIndex[$tag][] = [$indexCounter, $li];
                        $out =  "{" . $tag . $indexCounter . rtrim($rest) . "}";
                        $indexCounter++;

                        return $out;
                    } else if ($slash == "/") {
                        if ( ! isset ($nestingIndex[$tag]))
                            throw new \Exception("Line {$li}: Opening tag not found for closing tag: '{$matches[0]}'");
                        if (count ($nestingIndex[$tag]) == 0)
                            throw new \Exception("Line {$li}: Nesting level does not match for closing tag: '{$matches[0]}'");
                        $curIndex = array_pop($nestingIndex[$tag]);
                        return "{/" . $tag . $curIndex[0] . "}";
                    } else {
                        throw new \Exception("Line {$li}: This exception should not appear!");
                    }
                },
                $lines[$li]
            );

        }
        foreach ($nestingIndex as $tag => $curNestingIndex) {
            if (count ($curNestingIndex) > 0)
                throw new \Exception("Unclosed tag '{$tag}' opened in line {$curNestingIndex[0][1]} ");
        }
        return implode ("\n", $lines);
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
                        throw new \Exception("ParsingError: Can't parse element: '{$name}' Error on subelement: '$cur'");
                    }
                    $value = NULL;
                }
            }
        }
        if (is_object($value) && ! method_exists($value, "__toString"))
            $value = "##ERR:OBJECT_IN_TEXT:[{$name}]ON[{$cur}]:" . gettype($value) . "###";

        return $value;
    }



    private function _parseValueOfTags ($context, $block, $softFail=TRUE) {
        $result = preg_replace_callback ("/\\{=(.+?)\\}/im",
            function ($_matches) use ($softFail, $context) {
                $match = $_matches[1];

                $chain = explode("|", $match);
                for ($i=0; $i<count ($chain); $i++)
                    $chain[$i] = trim ($chain[$i]);

                if ( ! in_array("raw", $chain))
                    $chain[] = "_DEFAULT_";

                $varName = trim (array_shift($chain));

                if ($varName === "__CONTEXT__") {
                    $value = "\n----- __CONTEXT__ -----\n" . var_export($context, true) . "\n----- / __CONTEXT__ -----\n";
                } else {
                    $value = $this->_getValueByName($context, $varName, $softFail);
                }

                foreach ($chain as $curName) {
                    if ( ! isset ($this->mFilter[$curName]))
                        throw new \Exception("Filter '$curName' not defined");
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
            $curContent = $this->_parseValueOfTags($context, $curContent, $softFail);

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
        //echo $cmdParam;
        if ( ! preg_match('/([\"\']?.*?[\"\']?)\s*(==|<|>|!=)\s*([\"\']?.*[\"\']?)/i', $cmdParam, $matches))
            return "!! Invalid command sequence: '$cmdParam' !!";

        //print_r ($matches);

        $comp1 = $this->_getItemValue(trim ($matches[1]), $context);
        $comp2 = $this->_getItemValue(trim ($matches[3]), $context);

        //decho $comp1 . $comp2;

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
        $content = $this->_parseValueOfTags($context, $content, $softFail);
        return $content;

    }


    private function _parseBlock ($context, $block, $softFail=TRUE) {
        // (?!\{): Lookahead Regex: Don't touch double {{
        $result = preg_replace_callback('/\n?\{(?!=)(([a-z]+)[0-9]+)(.*?)\}(.*?)\n?\{\/\1\}/ism',
            function ($matches) use ($context, $softFail) {
                $command = $matches[2];
                $cmdParam = $matches[3];
                $content = $matches[4];

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

        $context = $params;

        $text = $this->_replaceNestingLevels($text);

        $text = $this->_parseBlock($context, $text, $softFail);
        $result = $this->_parseValueOfTags($context, $text, $softFail);

        return $result;
    }


}