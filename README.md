# Text-Template
Single-Class PHP5 template engine with support for if/loops/filters

It is aimed to be a small string Template-Engine to meet e-Mailing or small html-Template demands. It is not meant
to be a replacement for pre-compiled full featured Template-Engines like Smarty or Twig.

TextTemplate uses Regular-Expressions for text-Parsing. No code is generated or evaluated - so this might
be a secure solution to use in no time-critical situations.

Whilst most template-engines rely on eval'ing generated code and filesystem-access, Text-Template uses a  
set of regular-expressions to parse the template. Nor any intermediate code is generated nor any 
code is eval'ed. So TextTemplate should be more secure than Smarty or Twig by design.

TextTemplate supports infinite-nested loops and sequences.

## Basic Example
```php

// 1. Define the Template
$tplStr = <<<EOT
Hello World {= name }
{if name == "Matthias"}
Hallo {= name | capitalize }
{/if}
EOT;

// 2. Define the Data for the Template
$data = [
    "name" => "Matthias"
];

// 3. Parse
$tt = new TextTemplate($tplStr);
echo $tt->apply ($data);
```

## Install

Just Copy the contents of `_build/TextTemplate.nightly.php` to your project folder to
use TextTemplate. It's just one class.


## Value injection

Use the value Tag
```
{= varName}
```

To inject a value to the Code. Any variables will be ```htmlspecialchars()``` encoded by default. To
output the RAW content use the ```raw```-Filter: ```{=htmlCode|raw}```

To access array elements or objects use "." to access sub-elements:
 
 ```
 {= users.0.name}
 ```
 


### Adding Filters

You can add custom filters or overwrite own filters.

Adding a new Filter:

```php
$tt->addFilter ("currency", function ($input) {
    return number_format ($input, 2, ",", ".");
});
```

Use this filter inside your template

```
{= someVariable | currency }
```


### Replacing the default-Filter
By default and for security reason all values will be escaped using the "_DEFAULT_"-Filter. (except if
"raw" was selected within the filter section)

If you, for some reason, want to disable this functionality or change the escape function you can 
overwrite the _DEFAULT_-Filter:

```php
$tt->addFilter ("_DEFAULT_", function ($input) {
    return strip_tags ($input);
});
```

This example will replace the htmlspecialchars() escaper by the strip_tags() function.

## Loops

You can insert loops:

```
{for curName in names}
Current Name: {= curName}
{/for}
```

Inside each loop, there are to magick-values ```@index0``` (index starting with 0) and ```@index1``` for a
index starting with 1.

```
{for curName in names}
Line {= @index1 }: {= curName}
{/for}
```


## Conditions (if)

You can use if-conditions:

```
{if someVarName == "SomeValue"}
Hello World
{/if}
```

Limitation: Logical connections like OR / AND are not possible at the moment. Maybe in the future.




## Limitations

The logic-Expression-Parser won't handle logic connections (OR / AND) in conditions.


## About
Text-Template was written by Matthias Leuffen <matthes [at] leuffen [dot] de>

