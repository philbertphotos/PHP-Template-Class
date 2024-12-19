<?php

class Template {
    private $viewPath;
    private $fileExtension;
    private $data;
    private $cache;

    public function __construct($viewPath, $fileExtension = 'html') {
        $this->viewPath = rtrim($viewPath, '/') . '/';
        $this->fileExtension = $fileExtension;
        $this->data = [];
        $this->cache = [];
    }

    public function render($templateName, $data = []) {
        $this->data = $data;
        $filePath = $this->viewPath . $templateName . '.' . $this->fileExtension;

        if (!file_exists($filePath)) {
            throw new Exception("Template file not found: {$filePath}");
        }

        if (isset($this->cache[$filePath])) {
            $content = $this->cache[$filePath];
        } else {
            $content = file_get_contents($filePath);
            $this->cache[$filePath] = $content;
        }

        echo $this->parse($content);
    }

    private function parse($content) {
        // Process in specific order to handle nested elements
        $content = preg_replace('/\{#.*?#\}/s', '', $content);
        
        $content = preg_replace_callback('/\{inc\([\'\"](.+?)[\'\"]\)\}/', function($matches) {
            return $this->includeTemplate($matches[1]);
        }, $content);
    
        $content = $this->parseIfStatements($content);
        $content = $this->parseSwitchStatements($content);
        $content = $this->parseLoops($content);
        $content = $this->parseInlineConditions($content);
        $content = $this->parseVariables($content);
    
        return $content;
    }

    private function parseIfStatements($content) {
        $pattern = '/\{if\[(.*?)\]:\}(.*?)(?:\{else:\}(.*?))?\{if;\}/s';
        return preg_replace_callback($pattern, function($matches) {
            $condition = $this->evaluateCondition($matches[1]);
            $thenContent = isset($matches[2]) ? $matches[2] : '';
            $elseContent = isset($matches[3]) ? $matches[3] : '';
    
            // Process nested content first
            $content = $condition ? $thenContent : $elseContent;
            return $this->parse($content);
        }, $content);
    }

    private function parseLoops($content) {
        // For foreach loops with nested content
        $pattern = '/\{f\[(.*?)>(.*?)(?:=(\w+))?\]e:\}(.*?)\{fe;\}/s';
        return preg_replace_callback($pattern, function($matches) {
            $array = $this->getValue($matches[1]);
            $itemVar = $matches[2];
            $valueVar = isset($matches[3]) ? $matches[3] : null;
            $loopContent = $matches[4];

            if (!is_array($array)) return '';

            $output = '';
            foreach ($array as $key => $item) {
                $tempData = $this->data;
                $this->data[$itemVar] = $item;
                if ($valueVar) {
                    $this->data[$valueVar] = $key;
                }
                // Process nested templates first
                $processedContent = $this->parse($loopContent);
                // Then handle variables
                $processedContent = $this->parseVariables($processedContent);
                $output .= $processedContent;
                $this->data = $tempData;
            }

            return $output;
        }, $content);
    }

    private function parseForeachLoops($content) {
        $pattern = '/\{f\[(.*?)>(.*?)(?:=(\w+))?\]e:\}(.*?)\{fe;\}/s';
        return preg_replace_callback($pattern, function($matches) {
            $array = $this->getValue($matches[1]);
            $itemVar = $matches[2];
            $valueVar = isset($matches[3]) ? $matches[3] : null;
            $loopContent = $matches[4];

            if (!is_array($array)) return '';

            $output = '';
            foreach ($array as $key => $item) {
                $tempData = $this->data;
                $this->data[$itemVar] = $item;
                if ($valueVar) {
                    $this->data[$valueVar] = $key;
                }
                $output .= $this->parse($loopContent);
                $this->data = $tempData;
            }

            return $output;
        }, $content);
    }

    private function parseForLoops($content) {
        $pattern = '/\{f\[(.*?)>(.*?)\]:\}(.*?)\{f;\}/s';
        return preg_replace_callback($pattern, function($matches) {
            $array = $this->getValue($matches[1]);
            $itemVar = $matches[2];
            $loopContent = $matches[3];

            if (!is_array($array)) return '';

            $output = '';
            foreach ($array as $item) {
                $tempData = $this->data;
                $this->data[$itemVar] = $item;
                $output .= $this->parse($loopContent);
                $this->data = $tempData;
            }

            return $output;
        }, $content);
    }

    private function parseSwitchStatements($content) {
        $pattern = '/\{s\[(.*?)\]:\}(.*?)\{s;\}/s';
        return preg_replace_callback($pattern, function($matches) {
            $value = $this->getValue($matches[1]);
            
            preg_match_all('/\{c\[(.*?)\]:(.*?)\}(?=\{c\[|$)/s', $matches[2], $cases);
            
            foreach ($cases[1] as $i => $case) {
                if ($this->getValue($case) == $value) {
                    return $this->parse($cases[2][$i]);
                }
            }
            
            return '';
        }, $content);
    }

    private function parseInlineConditions($content) {
        return preg_replace_callback('/\{\[(.*?)\?(.*?):(.*?)\]\}/', function($matches) {
            $condition = $this->evaluateCondition($matches[1]);
            return $condition ? $this->parse($matches[2]) : $this->parse($matches[3]);
        }, $content);
    }

    private function parseVariables($content) {
        return preg_replace_callback('/\{([^}\s]+)\}/', function($matches) {
            return $this->getValue($matches[1]);
        }, $content);
    }

    private function includeTemplate($templateName) {
        $filePath = $this->viewPath . $templateName . '.' . $this->fileExtension;
        if (!file_exists($filePath)) return '';
        return $this->parse(file_get_contents($filePath));
    }

    private function getValue($key) {
        $parts = explode('.', $key);
        $value = $this->data;

        foreach ($parts as $part) {
            if (isset($value[$part])) {
                $value = $value[$part];
            } else {
                return '';
            }
        }

        return $value;
    }

    private function evaluateCondition($condition) {
        $condition = preg_replace_callback('/\b([a-zA-Z_][a-zA-Z0-9_]*(?:\.[a-zA-Z_][a-zA-Z0-9_]*)*)\b/', function($matches) {
            $value = $this->getValue($matches[1]);
            return is_string($value) ? "'" . addslashes($value) . "'" : $value;
        }, $condition);

        try {
            return eval("return {$condition};");
        } catch (Exception $e) {
            return false;
        }
    }
}

?>
