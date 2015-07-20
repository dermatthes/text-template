<?php
use de\leuffen\text_template\TextTemplate;

/**
 * Created by PhpStorm.
 * User: matthes
 * Date: 17.07.15
 * Time: 15:55
 */


    class TextTemplateTest extends PHPUnit_Framework_TestCase {




        public function testReplaceNestingTags () {
            $in = "{ if xyz}{ if zzz}{=value}{ /if}{/if}";
            $tt = new TextTemplate();
            $out = $tt->_replaceNestingLevels($in);
            $this->assertEquals("{if0 xyz}{if1 zzz}{=value}{/if1}{/if0}", $out);
        }




        public function testAllResultsMatchExpectedResult () {
            $dirs = glob(__DIR__ . "/tpls/*");
            $tt = new TextTemplate();
            foreach ($dirs as $dir) {
                echo "\nTesting $dir...";
                $tt->loadTemplate(file_get_contents($dir . "/_in.txt"));
                $data = require ($dir . "/_in.php");
                $out = $tt->apply($data);
                $this->assertEquals(file_get_contents($dir . "/out.txt"), $out, "Error in check: {$dir}");
            }

        }





    }