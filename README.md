# Text-Template
Single-Class PHP5 template engine with support for if/loops/filters
 
# Basic Example
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

