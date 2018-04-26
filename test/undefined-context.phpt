<?php
namespace Leuffen\TextTemplate;

require __DIR__ . "/../vendor/autoload.php";


use Tester\Assert;

/**
 * Created by PhpStorm.
 * User: matthes
 * Date: 17.07.15
 * Time: 15:55
 */


\Tester\Environment::setup();



Assert::throws(function() {
    $in = "{= some.var.bla}";
    $tt = new TextTemplate($in);
    $tt->apply(["some"], false);
}, TemplateParsingException::class);

Assert::throws(function() {
    $in = "{= some.var}";
    $tt = new TextTemplate($in);
    $obj = new \stdClass();
    $tt->apply($obj, false);
}, TemplateParsingException::class);
