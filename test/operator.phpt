<?php
namespace Leuffen\TextTemplate;

require __DIR__ . "/../vendor/autoload.php";


use Tester\Assert;



\Tester\Environment::setup();


$p = new TextTemplate('{if text contains "Hello"}Hello{/if}{if text contains "Bye"}Bye{/if}');
$p->addOperator("contains", function ($operand1, $operand2) {  return strpos($operand1, $operand2) !== false; });
Assert::equal("Hello", $p->apply(["text" => "Hello world"]));

