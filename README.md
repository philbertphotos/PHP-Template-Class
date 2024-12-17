# Template Class Documentation

This documentation describes the Template class, which is a lightweight, flexible templating engine for PHP. It supports features such as variable substitution, loops, conditionals, and includes.

## Support Me

This software is developed during my free time and I will be glad if somebody will support me.

Everyone's time should be valuable, so please consider donating.

[https://buymeacoffee.com/oxcakmak](https://buymeacoffee.com/oxcakmak)

## Features

1. **Template Inclusion**: Include templates directly within other templates using the `inc('template_file')` syntax.
2. **Inline Conditions**: Use `[condition ? true : false]` or `i[condition ? true : false]i` for inline conditional statements.
3. **If-Else Statements**: Define conditional blocks using `i[condition]:`, `e:`, and `e[condition]:`.
4. **Switch Statements**: Handle multi-case conditions with `s[condition]:` and `c[value]:` blocks.
5. **For Loops**: Define `for` loops using `f[initialization, condition, increment]:` syntax.
6. **Foreach Loops**: Use `f[variable>array]` or `f[variable>array=key]:` for iterating over arrays.

## Installation

1. Save the `Template` class in a PHP file (e.g., `Template.php`).
2. Include the class in your project:
   ```php
   require_once 'Template.php';
   ```
3. Initialize the Template class:
   ```php
   $template = new Template('/path/to/views/', 'html', ['{', '}']);
   ```

## Usage

### Basic Usage

```php
$data = [
    'title' => 'My Page Title',
    'description' => 'This is a sample description.',
    'items' => [
        ['title' => 'Item 1', 'price' => 10],
        ['title' => 'Item 2', 'price' => 20],
    ]
];

$template->view('home', $data);
```

### Template Syntax

#### Variable Substitution
Variables can be included using `{variable}` syntax. For nested arrays, use dot notation (e.g., `{items.title}`).

#### Loops

**Foreach Loop**:
```html
{ items: }
<div class="item">
    <h3>{ items.title }</h3>
    <p>Price: { items.price } USD</p>
</div>
{ items; }
```

**For Loop**:
```html
f[i, i < 10, i++]:
<p>Number: { i }</p>
f;
```

#### Conditionals

**Inline If**:
```html
<p>Status: [isActive ? 'Active' : 'Inactive']</p>
```

**If-Else**:
```html
i[isAdmin]:
<p>Welcome, Admin!</p>
e[isEditor]:
<p>Welcome, Editor!</p>
e:
<p>Welcome, User!</p>
```

**Switch Case**:
```html
s[user.role]:
c["admin"]:
<p>Admin Dashboard</p>
c["editor"]:
<p>Editor Panel</p>
```

#### Template Includes
```html
{ inc('header') }
<h1>{ title }</h1>
{ inc('footer') }
```

## Error Handling
The Template class will throw exceptions for missing template files or syntax errors, ensuring that errors are caught early.

## Example Project Structure

```
project/
├── views/
│   ├── home.html
│   ├── header.html
│   └── footer.html
└── index.php
```

## Performance
The class is optimized for PHP 5.6 and above, ensuring compatibility and high performance across environments. Caching can be implemented for additional speed improvements if needed.

## License
This project is open-source and available under the MIT License.

