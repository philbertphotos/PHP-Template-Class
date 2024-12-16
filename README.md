# Template Class Documentation

The **Template Class** is a lightweight, flexible, and easy-to-use PHP class for managing views and rendering templates with dynamic data. It supports loops, includes, and customizable delimiters, providing an efficient way to create dynamic web pages.

## Support Me

This software is developed during my free time and I will be glad if somebody will support me.

Everyone's time should be valuable, so please consider donating.

[https://buymeacoffee.com/oxcakmak](https://buymeacoffee.com/oxcakmak)

## Features

- **Dynamic Variable Parsing**: Replace placeholders in your templates with data from PHP arrays.
- **Loop Support**: Easily iterate over arrays directly in the templates using custom loop syntax.
- **Includes**: Load reusable templates within other templates.
- **Customizable Delimiters**: Choose your own delimiters for placeholders.
- **Error Handling**: Provides informative error messages for missing files or incorrect data.
- **PHP Compatibility**: Supports PHP 5.6 and later.

---

## Installation

1. Download the `Template` class file and include it in your project:

    ```php
    require_once 'Template.php';
    ```

2. Create a directory for your template files (e.g., `views/`).

---

## Initialization

```php
$template = new Template(__DIR__ . '/views/', 'html', ['{', '}']);
```

- **`__DIR__ . '/views/'`**: Path to the directory containing template files.
- **`'html'`**: File extension for the templates (default: `html`).
- **`['{', '}']`**: Custom delimiters for placeholders (default: `{ }`).

---

## Usage

### Rendering a View

To render a view and pass dynamic data:

```php
$data = [
    'title' => 'Welcome',
    'description' => 'This is a test site.'
];

$template->view('home', $data);
```

This will load the `views/home.html` file and replace placeholders with values from `$data`.

### Template Syntax

#### Variables

In the template, use `{key}` to display data:

```html
<h1>{title}</h1>
<p>{description}</p>
```

#### Loops

Use the following syntax for loops:

```html
{items:}
<div class="item">
    <h3>{items.title}</h3>
    <p>Price: {items.price} USD</p>
</div>
{items;}
```

- `{items:}` starts the loop.
- `{items.title}` accesses the `title` key in each item.
- `{items;}` ends the loop.

#### Includes

You can include other templates using the `include` method:

```php
$templateContent = $template->include('header', ['title' => 'My Site']);
echo $templateContent;
```

---

## Example

### Template: `home.html`

```html
<h1>{title}</h1>
<p>{description}</p>

{items:}
<div class="item">
    <h3>{items.title}</h3>
    <p>Price: {items.price} USD</p>
</div>
{items;}
```

### PHP Script

```php
$template = new Template(__DIR__ . '/views/', 'html', ['{', '}']);

$data = [
    'title' => 'Welcome',
    'description' => 'A list of items:',
    'items' => [
        ['title' => 'Item 1', 'price' => '10'],
        ['title' => 'Item 2', 'price' => '20'],
        ['title' => 'Item 3', 'price' => '30']
    ]
];

$template->view('home', $data);
```

### Output

```html
<h1>Welcome</h1>
<p>A list of items:</p>

<div class="item">
    <h3>Item 1</h3>
    <p>Price: 10 USD</p>
</div>
<div class="item">
    <h3>Item 2</h3>
    <p>Price: 20 USD</p>
</div>
<div class="item">
    <h3>Item 3</h3>
    <p>Price: 30 USD</p>
</div>
```

---

## Error Handling

- **Missing View File**: Throws an exception if the specified view file is not found.
- **Invalid Loop Data**: Skips loop processing if the provided data is not an array.

---

## Notes

- Use meaningful array keys for your data to ensure clarity in templates.
- The default delimiters `{ }` can be replaced with custom delimiters if needed.

---

## License

This class is released under the MIT License. Feel free to use and modify it as needed.

