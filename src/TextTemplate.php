<?php
/**
 *
 * The MIT License (MIT)
 *
 * Copyright (c) 2015-2017 Matthias Leuffen, Aachen, Germany
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

namespace Leuffen\TextTemplate;

use Symfony\Component\Config\Definition\Exception\Exception;

class TextTemplate {

    const VERSION = "2.0.0";

    private $mTemplateText;
    private $mFilter = [];

    public function __construct ($text="") {
        $this->mTemplateText = $text;
        $this->mFilter["_DEFAULT_"] = function ($input) { return htmlspecialchars($input); };

        // Raw is only a pseudo-filter. If it is not in the chain of filters, __DEFAULT__ will be appended to the filter
        $this->mFilter["html"] = function ($input) { return htmlspecialchars($input); };
        $this->mFilter["raw"] = function ($input) { return $input; };
        $this->mFilter["singleLine"] = function ($input) { return str_replace("\n", " ", $input); };
        $this->mFilter["inivalue"] = function ($input) { return addslashes(str_replace("\n", " ", $input)); };

        $this->addFilter("fixedLength", function ($input, $length, $padChar=" ") {
            return str_pad(substr($input, 0, $length), $length, $padChar);
        });
    }

    /**
     * Set the default Filter
     *
     * @param $filterName
     */
    public function setDefaultFilter ($filterName) {
        $this->mFilter["_DEFAULT_"] = $this->mFilter[$filterName];
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


    public function _replaceElseIf ($input) {
        $lines = explode("\n", $input);
        for ($li=0; $li < count ($lines); $li++) {
            $lines[$li] = preg_replace_callback('/\{else(?<nestingLevel>[0-9]+)\}/im',
                function ($matches) use (&$nestingIndex, &$indexCounter, &$li) {
                    return "{/if{$matches["nestingLevel"]}}{if{$matches["nestingLevel"]} ::NL_ELSE_FALSE}";
                },
                $lines[$li]
            );
            $lines[$li] = preg_replace_callback('/\{elseif(?<nestingLevel>[0-9]+)(?<params>.*)\}/im',
                function ($matches) use (&$nestingIndex, &$indexCounter, &$li) {

                    return "{/if{$matches["nestingLevel"]}}{if{$matches["nestingLevel"]} ::NL_ELSE_FALSE {$matches["params"]}}";
                },
                $lines[$li]
            );

        }
        return implode ("\n", $lines);
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
                    if ($tag == "else" || $tag == "elseif"){

                        if ( ! isset ($nestingIndex["if"]))
                            throw new \Exception("Line {$li}: 'if' Opening tag not found for else/elseif tag: '{$matches[0]}'");
                        if (count ($nestingIndex["if"]) == 0)
                            throw new \Exception("Line {$li}: Nesting level does not match for closing tag: '{$matches[0]}'");
                        $curIndex = $nestingIndex["if"][count ($nestingIndex["if"])-1];
                        $out =  "{" . $tag . $curIndex[0] . rtrim($rest) . "}";
                        return $out;
                    }
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


    private function _applyFilter ($filterNameAndParams, $value) {
        $filterParameters = explode(":", $filterNameAndParams);
        $filterName = array_shift($filterParameters);

        if ( ! isset ($this->mFilter[$filterName]))
            throw new \Exception("Filter '$filterName' not defined");
        $fn = $this->mFilter[$filterName];


        array_unshift($filterParameters, $value);
        return call_user_func_array($fn, $filterParameters);

        // Change to variable-unpacking, when support for php5.4 ends:
        // return $fn($value, ...$filterParameters);

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
                    $value = $this->_applyFilter($curName, $value);
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
        if (is_numeric($compName)) {
            return $compName;
        }
        switch (strtoupper($compName)
        {
            case "FALSE":
                return FALSE;
                break;
            case "TRUE":
                return TRUE;
                break;
            case "NULL":
                return NULL;
            default:
                return $this->_getValueByName($context, $compName);
        }
    }


    private function _runIf ($context, $content, $cmdParam, $softFail=TRUE, &$ifConditionDidMatch) {
        //echo $cmdParam;
        $doIf = false;

        $cmdParam = trim ($cmdParam);
        //echo "\n+ $cmdParam " . strpos($cmdParam, "::NL_ELSE_FALSE");
        // Handle {else}{elseif} constructions
        if ($cmdParam === "::NL_ELSE_FALSE") {
            // This is the {else} path of a if construction
            if ($ifConditionDidMatch == true) {
                return ""; // Do not run else block
            }
            $cmdParam = "TRUE==TRUE";
        } elseif (strpos($cmdParam, "::NL_ELSE_FALSE") === 0) {
            // This is a {elseif (condition)} block
            if ($ifConditionDidMatch == true) {
                return ""; // Do not run ifelse block, if block before succeeded
            }

            $cmdParam = substr($cmdParam, strlen ("::NL_ELSE_FALSE")+1);
        } else {
            // This is the original {if}
            $ifConditionDidMatch = false;
        }

        if ( ! preg_match('/(([\"\']?).*?(\2))\s*(==|===|<|<=|>|>=|!=|!==)\s*(([\"\']?).*(\6))/i', $cmdParam, $matches)) {
            $comp1 = $this->_getItemValue($cmdParam, $context);
            $comp2 = true;
            $operator = '==';
        } else {
            $comp1 = $this->_getItemValue(trim($matches[1]), $context);
            $comp2 = $this->_getItemValue(trim($matches[5]), $context);
            $operator = $matches[4];
        }
        switch ($operator) {
            case '==':
                $doIf = ($comp1 == $comp2);
                break;
            case '===':
                $doIf = ($comp1 === $comp2);
                break;
            case '!=':
                $doIf = ($comp1 != $comp2);
                break;
            case '!=':
                $doIf = ($comp1 !== $comp2);
                break;
            case '<':
                $doIf = ($comp1 < $comp2);
                break;
            case '<=':
                $doIf = ($comp1 < $comp2);
                break;
            case '>':
                $doIf = ($comp1 > $comp2);
                break;
            case '>=':
                $doIf = ($comp1 > $comp2);
                break;

        }

        if ( ! $doIf) {
            return "";
        }

        $ifConditionDidMatch = true; // Skip further else / elseif execution
        $content = $this->_parseBlock($context, $content, $softFail);
        $content = $this->_parseValueOfTags($context, $content, $softFail);
        return $content;

    }

    private $ifConditionMatch = [];

    private function _parseBlock ($context, $block, $softFail=TRUE) {
        // (?!\{): Lookahead Regex: Don't touch double {{

        $result = preg_replace_callback('/\n?\{(?!=)((?<command>[a-z]+)(?<nestingLevel>[0-9]+))(?<cmdParam>.*?)\}(?<content>.*?)\n?\{\/\1\}/ism',
            function ($matches) use ($context, $softFail) {
                $command = $matches["command"];
                $cmdParam = $matches["cmdParam"];
                $content = $matches["content"];
                $nestingLevel = $matches["nestingLevel"];

                switch ($command) {
                    case "for":
                        return $this->_runFor($context, $content, $cmdParam, $softFail);

                    case "if":
                        return $this->_runIf ($context, $content, $cmdParam, $softFail, $this->ifConditionMatch[$nestingLevel]);

                    default:
                        return "!! Invalid command: '$command' !!";
                }
            }, $block);
        if ($result === NULL) {
            throw new \Exception("preg_replace_callback() returned NULL: preg_last_error() returns: " . preg_last_error() . " (error == 2: preg.backtracklimit to low)");
        }
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
        $text = $this->_replaceElseIf($text);

        $text = $this->_parseBlock($context, $text, $softFail);
        $result = $this->_parseValueOfTags($context, $text, $softFail);

        return $result;
    }


}
