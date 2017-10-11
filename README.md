# Text-Template (Single Class, IF, FOR, FILTERS)

[![Downloads this Month](https://img.shields.io/packagist/dm/text/template.svg)](https://packagist.org/packages/text/template)
[<img src="https://travis-ci.org/dermatthes/text-template.svg">](https://travis-ci.org/dermatthes/text-template)
[![Coverage Status](https://coveralls.io/repos/github/dermatthes/text-template/badge.svg?branch=master)](https://coveralls.io/github/dermatthes/text-template?branch=master)
[![Latest Stable Version](https://poser.pugx.org/text/template/v/stable)](https://github.com/dermatthes/text-template/releases)
[![Supports PHP 5.4+](https://raw.githubusercontent.com/xp-framework/web/master/static/php-5_4plus.png)](http://php.net/)
[![Supports PHP 7.0+](https://raw.githubusercontent.com/xp-framework/web/master/static/php-7_0plus.png)](http://php.net/)

```
{if user.searching=='template'}This is for you {= user.name }{else}Welcome {= user.name }{/if}
```


Single-Class PHP5/7 template engine with support for if/loops/filters

- __Easy__: No compiling or caching - just parse `input string` into `output string`
- __Secure__: No eval(); no code is generated. No filesystem access needed. Unit-tested.
- __Features__: Nested loops, if/elseif/else, custom filters, auto-escaping

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
{elseif name == "Jan"}
Hi Buddy
{else}
You are not Matthias
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

I prefer and recommend using [composer](http://getcomposer.com):

```
composer require text/template
```

But you can do it the traditional way too:

* __Copy n Paste:__ Just Copy the contents of <https://github.com/dermatthes/text-template/blob/master/src/TextTemplate.php> to your project folder to
use TextTemplate. It's just one class.

* __Git externals:__ Clone this repository and add this as external to your project.

* ~~__Download gzip'ed phar:__-- If you like downloads we offer you a gzip'ed phar archive: <https://github.com/dermatthes/text-template/blob/master/_build/text-template.recent.phar.php.gz>~~ (Abandoned: July/2017 - please use composer or copy the source code)

* ~~__Download source tar:__ <https://raw.githubusercontent.com/dermatthes/text-template/master/_build/text-template.recent.src.tar.gz>~~ (Abandoned: July/2017 - please use composer or copy the source code)

_TextTemplate uses Phing to build the phar-archives and gzip them. Just execute main-target in build.xml to build your own version_


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

Inside loops you can `{break}` or `{continue}` the loop.


## Conditions (if)

You can use if-conditions:

```
{if someVarName == "SomeValue"}
Hello World
{/if}
```

Limitation: Logical connections like OR / AND are not possible at the moment. Maybe in the future.

## Conditions (else)
```
{if someVarName == "SomeValue"}
Hello World
{else}
Goodbye World
{/if}
```

Lists of choices:

```
{if someVarName == "SomeValue"}
Hello World
{elseif someVarName == "OtherValue"}
Hello Moon
{else}
Goodbye World
{/if}
```

### Calling Functions

You can register user-defined functions.

```
$template->addFunction("sayHello", 
    function ($paramArr, $command, $context, $cmdParam) {
        return "Hello " . $paramArr["msg"];
    }
);
```

Call the function and output into template

```
{sayHello msg="Joe"}
```

or inject the Result into the context for further processing:

```
{sayHello msg="Joe" > out}
{=out}
```

Processing Exceptions:

Use `!>` to catch exceptions and redirect them to the scope.

`{throw msg="SomeMsg" !> lastErr}` 


### Adding Filters

You can add custom filters or overwrite own filters.

Adding a new Filter:

```php
$tt->addFilter ("currency", function ($input, $decimals=2, $decSeparator=",", $thounsandsSeparator=".") {
    return number_format ($input, $decimals, $decSeparator, $thousandsSeparator);
});
```
Call the filter with parameters (parameter-separator `:`):

```
{= variable | currency:2:,:. }
```


Use this filter inside your template

```
{= someVariable | currency }
```

### Predefined Filters

| Name           | Description                                |
|----------------|--------------------------------------------|
| raw            | Display raw data (skip default escaping)   |
| singleLine     | Transform Line-breaks to spaces            |
| inivalue       | like singleLine including addslashes()     |
| html           | htmlspecialchars()                         |
| fixedLength:<length>:<pad_char: | Pad / shrink the output to <length> characters |
| inflect:tag | Convert to underline tag |
| sanitize:hostname | Convert to hostname |


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

or

```php
$tt->setDefaultFilter("singleLine");
```

This example will replace the htmlspecialchars() escaper by the strip_tags() function.




## Debugging the Parameters

To see all Parameters passed to the template use:

```
{= __CONTEXT__ | raw}
```

It will output the structure of the current context.


## Limitations

The logic-Expression-Parser won't handle logic connections (OR / AND) in conditions.

## Benchmark

Although the parser is build of pure regular-expressions, I tried to avoid too expensive constructions like
read ahead, etc.

And we got quite good results: 

| Template size | Parsing time[sec] |
|---------------|-------------------|
| 50kB          | 0.002             |
| 200kB         | 0.007             |



## Contributing, Bug-Reports, Enhancements

If you want to contribute, please send your Pull-Request or open
a github issue.

- __Bugs & Feature-Request:__ [GitHub Issues](https://github.com/dermatthes/text-template/issues)

__Keeping the tests green__: Please see / provide unit-tests. This project uses `nette/tester` for unit-testing.


## About
Text-Template was written by Matthias Leuffen <http://leuffen.de>

