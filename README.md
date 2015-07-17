# Text-Template
Single-Class PHP5 template engine with support for if/loops/filters

It is aimed to be a small Template-Engine to meet e-Mailing or small html-Template demands.

TextTemplate uses Regular-Expressions for text-Parsing. No code is generated or evaluated - so this might
be a secure solution to use in no time-critical situations.

## Basic Example
```php
$tplStr = <<<EOT
Welcome {user.name | capitalize},

{if isLoggedIn == TRUE}
You are logged in.
Your last logins:
{for curLoginDate in lastLogins}
Login on {curLoginDate}
{/for}
{/if}
EOT;

$data = [
    "user" => [
        "name" => "Matthias"
    ],
    "isLoggedIn" => TRUE,
    "lastLogins" => [
        "2015-01-15",
        "2015-02-17"
    ]
];

$tt = new TextTemplate($tplStr);
echo $tt->apply ($data);
```

## Limitations

Text-Template cannot handle nested if or loops. This limitation will be eliminated with in 
the next days.

The logic-Expression-Parser won't handle logic connections (OR / AND).

The Filter-Engine is not yet implemented.


