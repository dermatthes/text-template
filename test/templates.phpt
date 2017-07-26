<?php
namespace Leuffen\TextTemplate;

require __DIR__ . "/../vendor/autoload.php";

use de\leuffen\text_template\TextTemplate;
use Tester\Assert;

/**
 * Created by PhpStorm.
 * User: matthes
 * Date: 17.07.15
 * Time: 15:55
 */


\Tester\Environment::setup();


$in = "{ if xyz}{ if zzz}{=value}{ /if}{else}{/if}";
$tt = new TextTemplate();
$out = $tt->_replaceElseIf($in);
Assert::equal("{ if xyz}{ if zzz}{=value}{ /if}{/if}{if ::NL_ELSE_FALSE}{/if}", $out);

$in = "{ if xyz}{ if zzz}{=value}{ /if}{elseif bbb}{/if}";
$tt = new TextTemplate();
$out = $tt->_replaceElseIf($in);
Assert::equal("{ if xyz}{ if zzz}{=value}{ /if}{/if}{if ::NL_ELSE_FALSE  bbb}{/if}", $out);


$in = "{ if xyz}{ if zzz}{=value}{ /if}{/if}";
$tt = new TextTemplate();
$out = $tt->_replaceNestingLevels($in);
Assert::equal("{if0 xyz}{if1 zzz}{=value}{/if1}{/if0}", $out);




// Test all Templates

$dirs = glob(__DIR__ . "/unit/tpls/*");
$tt = new TextTemplate();
foreach ($dirs as $dir) {
    echo "\nTesting $dir...";
    $tt->loadTemplate(file_get_contents($dir . "/_in.txt"));
    $data = require ($dir . "/_in.php");
    $out = $tt->apply($data);
    Assert::equal(file_get_contents($dir . "/out.txt"), $out, "Error in check: {$dir}");
}







