# Template Class Documentation

A lightweight and efficient PHP template engine that supports variables, nested conditions, and basic template operations. Compatible with PHP 5.6 and above.

## Support Me

This software is developed during my free time and I will be glad if somebody will support me.

Everyone's time should be valuable, so please consider donating.

[https://buymeacoffee.com/oxcakmak](https://buymeacoffee.com/oxcakmak)

## Features

- **Variable Replacement** - Support for dot notation, bracket notation, and mixed notation
- **Conditional Logic** - Full if/elseif/else statements with nested support
- **Function Calls** - Call PHP functions directly from templates
- **Loop Iteration** - For loops with metadata (index, first, last)
- **Nested Loops** - Support for multi-level loop structures
- **Template Includes** - Include other template files
- **HTML Escaping** - Automatic escaping of output for security
- **Array Access** - Multiple ways to access array data:
  - Dot notation: `user.details.balance`
  - Bracket notation: `user[details][balance]`
  - Mixed notation: `user[details].balance`
- **Performance Optimized** - Efficient template processing with minimal overhead

## Installation

Simply include the Template.php file in your project:

```php
require_once 'Template.php';
```

## Usage

### Basic Setup

```php
// Initialize template engine with template directory and extension
$template = new Template(__DIR__ . '/views', 'html');

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

The template engine supports multiple ways to access variables:

```html
<!-- Dot notation -->
<h1>Hello {{ user.name }}</h1>
<p>You are {{ user.age }} years old</p>

<!-- Bracket notation -->
<p>Your balance: {{ user[details][balance] }}</p>

<!-- Mixed notation -->
<p>Verification status: {{ user[details].verified }}</p>
```

#### Function Calls

You can call PHP functions directly in your templates:

```html
<!-- Call strtoupper() on a variable -->
<h1>{{ strtoupper(user.name) }}</h1>

<!-- Format a number -->
<p>Balance: {{ number_format(user.details.balance, 2) }}</p>
```

#### Conditions

Simple conditional statements:

```html
{% if user.name == "John" && user.age == 26 %}
    <h1>Hello John</h1>
    <p>You are 26 years old</p>
{% endif %}
```

#### Nested Conditions with Else/Elseif

More complex conditional logic:

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

Comparison operators for numeric values:

```html
{% if user.details.balance > 100 %}
    <p>Balance is greater than 100</p>
{% elseif user.details.balance === 100 %}
    <p>Balance is exactly 100</p>
{% elseif user.details.balance < 50 %}
    <p>Low balance warning</p>
{% endif %}
```

#### Boolean Logic

Combine conditions with AND (&&) and OR (||) operators:

```html
{% if user.details.verified && (user.status === "active" || user.status === "premium") %}
    <p>Welcome verified user with good standing!</p>
{% endif %}
```

#### Including Templates

Include other template files:

```html
<!-- Include a header template -->
{{ inc("partials/header") }}

<!-- Main content here -->
<div class="content">
    <h1>Welcome {{ user.name }}</h1>
</div>

<!-- Include a footer template -->
{{ inc("partials/footer") }}
```

#### For Loops

Iterate through arrays:

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

#### Loop Metadata

Access special loop variables:

```html
<ul>
{% for item in items %}
    <li class="{% if loop.first %}first{% endif %}{% if loop.last %}last{% endif %}">
        <span>{{ loop.index }}. {{ item.name }}</span>
    </li>
{% endfor %}
</ul>
```

#### Nested Loops

Create multi-level loop structures:

```html
<div class="categories">
{% for category in categories %}
    <div class="category">
        <h2>{{ category.name }}</h2>
        <ul class="products">
            {% for product in category.products %}
                <li>
                    <h3>{{ product.name }}</h3>
                    <p>Price: {{ product.price }}</p>
                    <p>Colors:</p>
                    <ul class="colors">
                        {% for color in product.colors %}
                            <li style="color: {{ color }};">{{ color }}</li>
                        {% endfor %}
                    </ul>
                </li>
            {% endfor %}
        </ul>
    </div>
{% endfor %}
</div>
```

### Supported Operators

- `===` Strict equality
- `==` Loose equality
- `>` Greater than
- `<` Less than
- `>=` Greater than or equal
- `<=` Less than or equal
- `&&` Logical AND
- `||` Logical OR
- `!` Logical NOT (negation)

### Error Handling

The template engine provides helpful error messages:

- Missing template files: `Template file not found: {path}`
- Missing template directory: `Template directory does not exist: {path}`
- Missing includes: `<!-- Include not found: {name} -->`
- Missing functions: `<!-- Function not found: {name} -->`

## Requirements

- PHP 5.6 or higher
- File system read permissions for template directory

## License

This project is open-source and available under the MIT License.

## Contributing

Feel free to submit issues and enhancement requests!
```

This updated README provides more comprehensive documentation with:

1. Expanded features section highlighting all capabilities
2. Better organized usage examples with clear section headers
3. Added documentation for function calls, mixed notation, and loop metadata
4. More complex examples showing real-world usage patterns
5. Error handling section to help users troubleshoot
6. Improved formatting and structure for better readability

The documentation now better reflects all the capabilities of your Template class, including the recently added features like bracket notation and function calls.
```
