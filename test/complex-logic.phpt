<?php
namespace Leuffen\TextTemplate;

require __DIR__ . "/../vendor/autoload.php";


use Tester\Assert;



\Tester\Environment::setup();


$vars = ["val" => true];

Assert::throws(function() use ($vars) {
    $in = "{if && val}{/if}";
    $tt = new TextTemplate($in);
    $tt->apply($vars, false);
}, TemplateParsingException::class, "Error parsing expression '&& val': Unexpected '&&'.");

Assert::throws(function() use ($vars) {
    $in = "{if || val}{/if}";
    $tt = new TextTemplate($in);
    $tt->apply($vars, false);
}, TemplateParsingException::class, "Error parsing expression '|| val': Unexpected '||'.");

Assert::throws(function() use ($vars) {
    $in = "{if val &&}{/if}";
    $tt = new TextTemplate($in);
    $tt->apply($vars, false);
}, TemplateParsingException::class, "Error parsing expression 'val &&': Unexpected end of expression.");

Assert::throws(function() use ($vars) {
    $in = "{if val ||}{/if}";
    $tt = new TextTemplate($in);
    $tt->apply($vars, false);
}, TemplateParsingException::class, "Error parsing expression 'val ||': Unexpected end of expression.");

Assert::throws(function() use ($vars) {
    $in = "{if (val}{/if}";
    $tt = new TextTemplate($in);
    $tt->apply($vars, false);
}, TemplateParsingException::class, "Error parsing expression '(val': Unmatched '('.");

Assert::throws(function() use ($vars) {
    $in = "{if val)}{/if}";
    $tt = new TextTemplate($in);
    $tt->apply($vars, false);
}, TemplateParsingException::class, "Error parsing expression 'val)': Unexpected ')'. No matching opening '('.");

Assert::throws(function() use ($vars) {
    $in = "{if !(val}{/if}";
    $tt = new TextTemplate($in);
    $tt->apply($vars, false);
}, TemplateParsingException::class, "Error parsing expression '!(val': Unmatched '('.");

Assert::throws(function() use ($vars) {
    $in = "{if (val))}{/if}";
    $tt = new TextTemplate($in);
    $tt->apply($vars, false);
}, TemplateParsingException::class, "Error parsing expression '(val))': Unexpected ')'. No matching opening '('.");

Assert::throws(function() use ($vars) {
    $in = "{if ((val)}{/if}";
    $tt = new TextTemplate($in);
    $tt->apply($vars, false);
}, TemplateParsingException::class, "Error parsing expression '((val)': Unmatched '('.");

Assert::throws(function() use ($vars) {
    $in = "{if (!(val)}{/if}";
    $tt = new TextTemplate($in);
    $tt->apply($vars, false);
}, TemplateParsingException::class, "Error parsing expression '(!(val)': Unmatched '('.");


Assert::throws(function() use ($vars) {
    $in = "{if (val) val}{/if}";
    $tt = new TextTemplate($in);
    $tt->apply($vars, false);
}, TemplateParsingException::class, "Error parsing expression '(val) val': Unexpected 'val'.");

Assert::throws(function() use ($vars) {
    $in = "{if val (val)}{/if}";
    $tt = new TextTemplate($in);
    $tt->apply($vars, false);
}, TemplateParsingException::class, "Error parsing expression 'val (val)': Unexpected '('.");

Assert::throws(function() use ($vars) {
    $in = "{if (val) (val)}{/if}";
    $tt = new TextTemplate($in);
    $tt->apply($vars, false);
}, TemplateParsingException::class, "Error parsing expression '(val) (val)': Unexpected '('.");

Assert::throws(function() use ($vars) {
    $in = "{if val && &&}{/if}";
    $tt = new TextTemplate($in);
    $tt->apply($vars, false);
}, TemplateParsingException::class, "Error parsing expression 'val && &&': Unexpected '&&' instead of value.");

Assert::throws(function() use ($vars) {
    $in = "{if val && ||}{/if}";
    $tt = new TextTemplate($in);
    $tt->apply($vars, false);
}, TemplateParsingException::class, "Error parsing expression 'val && ||': Unexpected '||' instead of value.");

Assert::throws(function() use ($vars) {
    $in = "{if val && )}{/if}";
    $tt = new TextTemplate($in);
    $tt->apply($vars, false);
}, TemplateParsingException::class, "Error parsing expression 'val && )': Unexpected ')' instead of value.");
