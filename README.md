# Template Class Documentation

This documentation describes the Template class, which is a lightweight, flexible templating engine for PHP. It supports features such as variable substitution, loops, conditionals, and includes.

## Support Me

This software is developed during my free time and I will be glad if somebody will support me.

Everyone's time should be valuable, so please consider donating.

[https://buymeacoffee.com/oxcakmak](https://buymeacoffee.com/oxcakmak)

## Features
```php
<?php
// Variables
{variable}
{user.name}

// Inline Conditions
{[age >= 18 ? 'Adult' : 'Minor']}

// Foreach Loops
{f[items>item]e:}
    <li>{item.title} - ${item.price}</li>
{fe;}

// With Key
{f[items>item=key]e:}
    <li>{key}: {item.title}</li>
{fe;}

// For Loops
{f[user>users]:}
    <li>{user.username}</li>
{f;}

// Comments
{# This is a comment #}

// Include Files
{inc('header')}
{inc('footer')}

// If Statements
{if[role===admin]:}
    <h1>Admin Panel</h1>
{if;}

// If-Else
{if[logged_in]:}
    Welcome back!
{else:}
    Please login.
{if;}

// Switch Case
{s[page]:}
{c[login]:}
    <h1>Login Page</h1>
{c[register]:}
    <h1>Register Page</h1>
{s;}
```

## Example Template
```
<!-- views/home.html -->
{inc('header')}

<h1>{title}</h1>

{if[logged_in]:}
    <p>Welcome, {user.name}!</p>
    
    {if[user.role===admin]:}
        <div class="admin-panel">
            <h2>Admin Panel</h2>
            {f[items>item]e:}
            <div class="product">
                <h3>{item.title}</h3>
                <p>Price: ${item.price}</p>
            </div>
            {fe;}
        </div>
    {else:}
        <div class="user-panel">
            <h2>User Panel</h2>
            <p>Limited access</p>
        </div>
    {if;}
{else:}
    <p>Please login to continue</p>
{if;}

{inc('footer')}
```

## Installation
```php
<?php
// 1. Download Template.php
// 2. Include in your project:
require_once 'Template.php';

// 3. Create instance with views directory:
$template = new Template(__DIR__ . '/views');

// 4. Create template files in views folder with .html extension
// 5. Render template with data:
$template->render('template-name', $data);
?>
```

## Data Structure Example
```php
<?php
$data = [
    'variable' => 'value',
    'nested' => [
        'key' => 'value'
    ],
    'items' => [
        ['title' => 'Item 1', 'price' => 100],
        ['title' => 'Item 2', 'price' => 200]
    ],
    'user' => [
        'name' => 'John',
        'role' => 'admin'
    ],
    'logged_in' => true,
    'page' => 'login'
];
?>
```

## License
This project is open-source and available under the MIT License.

