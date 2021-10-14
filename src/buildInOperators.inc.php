<?php
/**
 * Created by PhpStorm.
 * User: matthes
 * Date: 11.10.17
 * Time: 08:16
 */


namespace Leuffen\TextTemplate;


TextTemplate::$__DEFAULT_OPERATOR["=="] =  function ($operand1, $operand2) {
    return $operand1 == $operand2;
};

TextTemplate::$__DEFAULT_OPERATOR["!="] =  function ($operand1, $operand2) {
    return $operand1 != $operand2;
};

TextTemplate::$__DEFAULT_OPERATOR[">"] =  function ($operand1, $operand2) {
    return $operand1 > $operand2;
};

TextTemplate::$__DEFAULT_OPERATOR["<"] =  function ($operand1, $operand2) {
    return $operand1 < $operand2;
};

TextTemplate::$__DEFAULT_OPERATOR[">="] =  function ($operand1, $operand2) {
    return $operand1 >= $operand2;
};

TextTemplate::$__DEFAULT_OPERATOR["<="] =  function ($operand1, $operand2) {
    return $operand1 <= $operand2;
};
