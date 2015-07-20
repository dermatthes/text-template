# Text-Template
Single-Class PHP5 template engine with support for if/loops/filters

It is aimed to be a small Template-Engine to meet e-Mailing or small html-Template demands.

TextTemplate uses Regular-Expressions for text-Parsing. No code is generated or evaluated - so this might
be a secure solution to use in no time-critical situations.

## Basic Example
```php
$tplStr = <<<EOT
Hello World {= name }
{if name == "Matthias"}
Hallo {= name | capitalize }
{/if}
EOT;

$data = [
    "name" => "Matthias"
];

$tt = new TextTemplate($tplStr);
echo $tt->apply ($data);
```

## Limitations

The logic-Expression-Parser won't handle logic connections (OR / AND).


