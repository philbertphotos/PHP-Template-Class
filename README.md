# Template Class Documentation

A lightweight and efficient PHP template engine that supports variables, nested conditions, and basic template operations. Compatible with PHP 5.6 and above.

## Support Me

This software is developed during my free time and I will be glad if somebody will support me.

Everyone's time should be valuable, so please consider donating.

[https://buymeacoffee.com/oxcakmak](https://buymeacoffee.com/oxcakmak)

## Features

- Variable replacement with dot notation support
- Conditional statements (if, elseif, else)
- Nested conditions
- HTML escaping
- Array and object access
- Boolean and numeric comparisons
- Performance optimized

## Installation

Simply include the Template.php file in your project:

```php
require_once 'Template.php';
```

## Usage

### Basic Setup

```php
// Initialize template engine with template directory and extension
$template = new Template(__DIR__ . '/views', 'tpl');

// Assign variables
$userData = [
    'name' => 'John',
    'age' => '26',
    'details' => [
        'balance' => 100,
        'verified' => true
    ],
    'status' => 'active'
];

$template->assign('user', $userData);

// Load and render template
echo $template->load('home');
```

### Template Syntax

#### Variables
```html
<h1>Hello {{ user.name }}</h1>
<p>You are {{ user.age }} years old</p>
```

#### Conditions
```html
{% if user.name == "John" && user.age == 26 %}
    <h1>Hello John</h1>
    <p>You are 26 years old</p>
{% endif %}
```

#### Nested Conditions
```html
{% if user.details.verified === true && user.status === "active" %}
    <p>Verified active user</p>
{% elseif user.status === "pending" %}
    <p>Pending user</p>
{% else %}
    <p>Unverified user</p>
{% endif %}
```

#### Numeric Comparisons
```html
{% if user.details.balance > 100 %}
    <p>Balance is greater than 100</p>
{% elseif user.details.balance === 100 %}
    <p>Balance is exactly 100</p>
{% elseif user.details.balance === 5 %}
    <p>Balance is 5</p>
{% elseif user.details.balance === 0 %}
    <p>Balance is zero</p>
{% endif %}
```

## Supported Operators

- `===` Strict equality
- `==` Loose equality
- `>` Greater than
- `<` Less than
- `>=` Greater than or equal
- `<=` Less than or equal
- `&&` Logical AND
- `||` Logical OR

#### For loop
```html
<ul>
{% for product in products %}
    <li>
        <h2>{{ product.name }}</h2>
        <p>Price: {{ product.price }}</p>
        <p>{{ product.description }}</p>
    </li>
{% endfor %}
</ul>
```

#### For loop - Nested
```html
<ul>
{% for product in products %}
    <li>
        <h2>{{ product.name }}</h2>
        <p>Price: {{ product.price }}</p>
        <p>{{ product.description }}</p>
        <p>Colors:</p>
        <ul>
            {% for color in product.colors %}
                <li>{{ color }}</li>
            {% endfor %}
        </ul>
    </li>
{% endfor %}
</ul>
```

## Requirements

- PHP 5.6 or higher
- File system read permissions for template directory

## License

This project is open-source and available under the MIT License.

## Contributing

Feel free to submit issues and enhancement requests!
