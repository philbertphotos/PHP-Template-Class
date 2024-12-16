<?php

class Template {
    private $viewPath;
    private $fileExtension;
    private $delimiters;

    public function __construct($viewPath, $fileExtension = 'html', $delimiters = ['{', '}']) {
        $this->viewPath = rtrim($viewPath, '/') . '/';
        $this->fileExtension = $fileExtension ?: 'html';
        $this->delimiters = is_array($delimiters) && count($delimiters) === 2 ? $delimiters : ['{', '}'];
    }

    public function view($templateName, $data = []) {
        $filePath = $this->viewPath . $templateName . '.' . $this->fileExtension;

        if (!file_exists($filePath)) {
            throw new Exception("View file not found: " . $filePath);
        }

        $templateContent = file_get_contents($filePath);
        echo $this->parseTemplate($templateContent, $data);
    }

    private function parseTemplate($templateContent, $data) {
        list($startDelimiter, $endDelimiter) = $this->delimiters;
        $flattenedData = $this->flattenArray($data);

        // Replace placeholders
        foreach ($flattenedData as $key => $value) {
            $placeholder = $startDelimiter . $key . $endDelimiter;
            $templateContent = str_replace($placeholder, $value, $templateContent);
        }

        // Handle loops
        $pattern = '/' . preg_quote($startDelimiter, '/') . '\s*(\w+)\s*:\s*(.*?)' . preg_quote($endDelimiter, '/') . '\s*(.*?)' . preg_quote($startDelimiter, '/') . '\s*\1;\s*' . preg_quote($endDelimiter, '/') . '/s';
        $templateContent = preg_replace_callback($pattern, function ($matches) use ($data, $startDelimiter, $endDelimiter) {
            $key = $matches[1]; // Loop key
            $block = $matches[3]; // Loop content

            if (!isset($data[$key]) || !is_array($data[$key])) {
                return ''; // Skip if key is not an array
            }

            $output = '';
            foreach ($data[$key] as $item) {
                $flattenedItem = $this->flattenArray($item, $key);
                $blockContent = $block;
                foreach ($flattenedItem as $itemKey => $value) {
                    $itemPlaceholder = $startDelimiter . $itemKey . $endDelimiter;
                    $blockContent = str_replace($itemPlaceholder, $value, $blockContent);
                }
                $output .= $blockContent;
            }

            return $output;
        }, $templateContent);

        return $templateContent;
    }

    private function flattenArray($array, $prefix = '') {
        $result = [];
        foreach ($array as $key => $value) {
            $newKey = $prefix ? $prefix . '.' . $key : $key;
            if (is_array($value)) {
                $result = array_merge($result, $this->flattenArray($value, $newKey));
            } else {
                $result[$newKey] = $value;
            }
        }
        return $result;
    }

    public function include($templateName, $data = []) {
        $filePath = $this->viewPath . $templateName . '.' . $this->fileExtension;

        if (!file_exists($filePath)) {
            throw new Exception("Include file not found: " . $filePath);
        }

        $templateContent = file_get_contents($filePath);
        return $this->parseTemplate($templateContent, $data);
    }
}

?>
