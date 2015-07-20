# Text-Template
Single-Class PHP5 template engine with support for if/loops/filters

It is aimed to be a small Template-Engine to meet e-Mailing or small html-Template demands.

TextTemplate uses Regular-Expressions for text-Parsing. No code is generated or evaluated - so this might
be a secure solution to use in no time-critical situations.

TextTemplate supports infinite-nested loops and sequences.

## Security by design

TextTemplate useses Regular-Expressions only to parse the templates. Nor any intermediate code is generated nor is
any code eval'ed. So TextTemplate should be  more secure than Smarty or Twig by design.


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

## Value injection

Use the value Tag
```
{= varName}
```

To inject a value to the Code. Any variables will be ```htmlspecialchars()``` encoded by default. To
output the RAW content use the ```raw```-Filter: ```{=htmlCode|raw}```


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

## If

You can use if-conditions:

```
{if someVarName == "SomeValue"}
Hello World
{/if}
```

Limitation: Logical connections like OR / AND are not possible at the moment. Maybe in the future.





## Limitations

The logic-Expression-Parser won't handle logic connections (OR / AND).


