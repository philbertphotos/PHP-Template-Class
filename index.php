<?php
require_once 'Template.php';

// Initialize template engine with template directory and extension
$template = new Template(__DIR__ . '/views', 'html');

// Assign variables
$userData = [
    'name' => 'John Doe',
    'age' => 28,
    'details' => [
        'balance' => 1250.75,
        'verified' => true,
        'joined' => '2023-05-15',
        'level' => 'premium'
    ],
    'status' => 'active',
    'notifications' => 5,
    'preferences' => [
        'theme' => 'dark',
        'language' => 'en',
        'notifications' => true
    ]
];

// Products data
$products = [
    [
        'id' => 1,
        'name' => 'Premium Headphones',
        'price' => 149.99,
        'description' => 'High-quality noise cancelling headphones',
        'inStock' => true,
        'rating' => 4.8,
        'colors' => ['Black', 'Silver', 'Blue'],
        'features' => ['Noise cancellation', 'Bluetooth 5.0', '30h battery life']
    ],
    [
        'id' => 2,
        'name' => 'Wireless Mouse',
        'price' => 49.99,
        'description' => 'Ergonomic wireless mouse with long battery life',
        'inStock' => true,
        'rating' => 4.5,
        'colors' => ['Black', 'White', 'Gray'],
        'features' => ['Wireless', 'Ergonomic design', '12-month battery life']
    ],
    [
        'id' => 3,
        'name' => 'Mechanical Keyboard',
        'price' => 129.99,
        'description' => 'Mechanical gaming keyboard with RGB lighting',
        'inStock' => false,
        'rating' => 4.7,
        'colors' => ['Black', 'White'],
        'features' => ['Mechanical switches', 'RGB lighting', 'Programmable keys']
    ]
];

// Categories data
$categories = [
    [
        'id' => 1,
        'name' => 'Electronics',
        'subcategories' => ['Computers', 'Phones', 'Accessories']
    ],
    [
        'id' => 2,
        'name' => 'Clothing',
        'subcategories' => ['Men', 'Women', 'Kids']
    ],
    [
        'id' => 3,
        'name' => 'Home & Kitchen',
        'subcategories' => ['Appliances', 'Furniture', 'Decor']
    ]
];

// Site settings
$settings = [
    'site_name' => 'My Online Store',
    'logo' => 'logo.png',
    'currency' => 'USD',
    'contact_email' => 'support@example.com',
    'social_media' => [
        'facebook' => 'https://facebook.com/mystore',
        'twitter' => 'https://twitter.com/mystore',
        'instagram' => 'https://instagram.com/mystore'
    ]
];

// Assign all variables to the template
$template->assign('user', $userData);
$template->assign('products', $products);
$template->assign('categories', $categories);
$template->assign('settings', $settings);
$template->assign('current_year', date('Y'));
$template->assign('is_weekend', (date('N') >= 6));

// Load and render template
echo $template->load('index');
?>
