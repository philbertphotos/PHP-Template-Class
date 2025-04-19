<?php
/**
 * Template Class
 *
 * @category  Template
 * @package   Template
 * @author    Osman Cakmak <info@oxcakmak.com>
 * @copyright Copyright (c) 2024-?
 * @license   http://opensource.org/licenses/gpl-3.0.html GNU Public License
 * @link      https://github.com/oxcakmak/PHP-Template-Class
 * @version   1.1.2
 */
class Template {
    private $variables = [];
    private $templateDir;
    private $templateExt;
    
    public function __construct($templateDir, $templateExt = 'html') {
        if (empty($templateDir)) {
            throw new Exception("Template directory must be specified");
        }
        
        $this->templateDir = rtrim($templateDir, '/') . '/';
        $this->templateExt = ltrim($templateExt, '.');
        
        if (!is_dir($this->templateDir)) {
            throw new Exception("Template directory does not exist: {$this->templateDir}");
        }
    }
    
    public function assign($key, $value) {
        $this->variables[$key] = $value;
        return $this;
    }
    
    public function load($templateName) {
        $templatePath = $this->templateDir . $templateName . '.' . $this->templateExt;
        
        if (!file_exists($templatePath)) {
            throw new Exception("Template file not found: {$templatePath}");
        }
        
        $template = file_get_contents($templatePath);
        return $this->processTemplate($template);
    }
    
    private function processTemplate($template) {
        $maxIterations = 10;
        $iteration = 0;
    
        // Process includes first
        $template = preg_replace_callback(
            '/\{\{\s*inc\([\'"]([^\'"]+)[\'"]\)\s*\}\}/',
            array($this, 'processInclude'),
            $template
        );
    
        // Process function calls in variables
        $template = preg_replace_callback(
            '/\{\{\s*(\w+)\(([^)]+)\)\s*\}\}/',
            array($this, 'processFunction'),
            $template
        );
    
        // Process variables with bracket notation first
        $template = preg_replace_callback(
            '/\{\{\s*([a-zA-Z0-9_]+(?:\[[^\]]+\])+(?:\.[a-zA-Z0-9_]+)*)\s*\}\}/',
            array($this, 'replaceVariable'),
            $template
        );
    
        // Process regular variables (must come after bracket notation)
        $template = preg_replace_callback(
            '/\{\{\s*([a-zA-Z0-9._]+)\s*\}\}/',
            array($this, 'replaceVariable'),
            $template
        );
    
        // Process nested for loops (from innermost to outermost)
        while ($iteration < $maxIterations) {
            // Find all for loops in the template
            if (!preg_match_all('/\{%\s*for\s+(\w+)\s+in\s+([a-zA-Z0-9._]+)\s*%\}(.*)\{%\s*endfor\s*%\}/s', $template, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
                break;
            }
    
            // Sort matches by length (longest first - innermost loops)
            usort($matches, function($a, $b) {
                return strlen($b[0][0]) - strlen($a[0][0]);
            });
    
            $processed = false;
            foreach ($matches as $match) {
                $fullMatch = $match[0][0];
                $offset = $match[0][1];
    
                $itemName = $match[1][0];
                $arrayPath = $match[2][0];
                $content = $match[3][0];
    
                // Get the array to iterate over
                $array = $this->getNestedValue($arrayPath);
                if (!is_array($array)) {
                    continue;
                }
    
                // Process the loop
                $result = '';
                $originalVars = $this->variables;
    
                foreach ($array as $key => $item) {
                    // Create new scope for this iteration
                    $iterationVars = $originalVars;
                    
                    // Set current item in scope
                    $iterationVars[$itemName] = $item;
    
                    // Handle nested array access and maintain parent context
                    if (strpos($arrayPath, '.') !== false) {
                        $pathParts = explode('.', $arrayPath);
                        $parentKey = $pathParts[0];
                        if (isset($originalVars[$parentKey])) {
                            $iterationVars[$parentKey] = $originalVars[$parentKey];
                        }
                    }
    
                    // Add loop metadata
                    $iterationVars['loop'] = [
                        'index' => $key + 1,
                        'first' => $key === 0,
                        'last' => $key === count($array) - 1,
                        'parent' => isset($originalVars['loop']) ? $originalVars['loop'] : null
                    ];
    
                    // Set the current scope and process content
                    $this->variables = $iterationVars;
                    $processedContent = $this->processTemplate($content);
                    $result .= $processedContent;
                }
    
                // Replace the for loop with its processed content
                $template = substr_replace($template, $result, $offset, strlen($fullMatch));
                $this->variables = $originalVars;
                $processed = true;
                break; // Process one loop at a time
            }
    
            if (!$processed) {
                break;
            }
    
            $iteration++;
        }
    
        // Process variables
        $template = preg_replace_callback(
            '/\{\{\s*([a-zA-Z0-9._]+)\s*\}\}/',
            array($this, 'replaceVariable'),
            $template
        );
    
        // Process nested if conditions with improved pattern
        $iteration = 0;
        $lastTemplate = '';
        
        while ($template !== $lastTemplate && $iteration < $maxIterations) {
            $lastTemplate = $template;
            $template = preg_replace_callback(
                '/\{%\s*if\s+(.+?)\s*%\}((?:(?!\{%\s*(?:if|endif)\s*%\}).)*?)(?:\{%\s*endif\s*%\})/s',
                array($this, 'processCondition'),
                $template
            );
            $iteration++;
        }
    
        // Clean up any remaining tags and extra whitespace
        $template = preg_replace('/\{%\s*(?:else|elseif|endif)\s*%\}/s', '', $template);
        $template = preg_replace('/^\s+|\s+$/m', '', $template);
        
        return $template;
    }
    
    private function processInclude($matches) {
        $includePath = $matches[1];
        $fullPath = $this->templateDir . $includePath . '.' . $this->templateExt;
        
        if (!file_exists($fullPath)) {
            // Handle null path
            return '<!-- Include not found: ' . htmlspecialchars((string)$includePath) . ' -->';
        }
        
        $content = file_get_contents($fullPath);
        return $this->processTemplate($content);
    }
    
    private function replaceVariable($matches) {
        $path = $matches[1];
        $value = $this->getNestedValue($path);
        // Handle null values
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
    
    private function processCondition($matches) {
        $condition = trim($matches[1]);
        $content = $matches[2];
        
        // Parse blocks
        $blocks = $this->parseBlocks($content);
        $result = '';
        
        // Check main if condition
        if ($this->evaluateCondition($condition)) {
            $result = $blocks['if'];
        } else {
            // Check elseif conditions
            $elseifMatched = false;
            foreach ($blocks['elseif'] as $elseif) {
                if ($this->evaluateCondition($elseif['condition'])) {
                    $result = $elseif['content'];
                    $elseifMatched = true;
                    break;
                }
            }
            
            // If no elseif matched and there's an else block
            if (!$elseifMatched && isset($blocks['else'])) {
                $result = $blocks['else'];
            }
        }
        
        // Process nested conditions in the result
        return trim($this->processTemplate($result));
    }
    
    private function parseBlocks($content) {
        $blocks = array(
            'if' => '',
            'elseif' => array(),
            'else' => ''
        );
    
        // Split content into if/elseif/else blocks
        $pattern = '/\{%\s*(elseif|else)\s*([^%]*?)\s*%\}/';
        $parts = preg_split($pattern, $content, -1, PREG_SPLIT_DELIM_CAPTURE);
    
        // First part is always the if content
        $blocks['if'] = trim($parts[0]);
    
        // Process remaining parts
        for ($i = 1; $i < count($parts); $i += 3) {
            if (!isset($parts[$i])) break;
    
            if ($parts[$i] === 'elseif' && isset($parts[$i + 1], $parts[$i + 2])) {
                $blocks['elseif'][] = array(
                    'condition' => trim($parts[$i + 1]),
                    'content' => trim($parts[$i + 2])
                );
            } elseif ($parts[$i] === 'else' && isset($parts[$i + 2])) {
                $blocks['else'] = trim($parts[$i + 2]);
                break; // Stop after else block
            }
        }
    
        return $blocks;
    }
    
    private function evaluateSingleCondition($condition) {
        // Handle negation first
        $isNegated = false;
        if (strpos($condition, '!') === 0) {
            $isNegated = true;
            $condition = ltrim(substr($condition, 1));
        }
    
        // Check for comparison operators
        if (preg_match('/(.+?)\s*(===|==|>|<|>=|<=)\s*(.+)/', $condition, $matches)) {
            $left = trim($matches[1]);
            $operator = $matches[2];
            $right = trim($matches[3], '"\'');
            
            $leftValue = $this->getNestedValue($left);
            
            if (is_numeric($right) && is_numeric($leftValue)) {
                $leftValue = (float)$leftValue;
                $rightValue = (float)$right;
            } elseif ($right === 'true') {
                $rightValue = true;
            } elseif ($right === 'false') {
                $rightValue = false;
            } else {
                $rightValue = $right;
            }
            
            $result = false;
            switch ($operator) {
                case '===': $result = $leftValue === $rightValue; break;
                case '==': $result = $leftValue == $rightValue; break;
                case '>': $result = is_numeric($leftValue) && is_numeric($rightValue) ? (float)$leftValue > (float)$rightValue : false; break;
                case '<': $result = is_numeric($leftValue) && is_numeric($rightValue) ? (float)$leftValue < (float)$rightValue : false; break;
                case '>=': $result = is_numeric($leftValue) && is_numeric($rightValue) ? (float)$leftValue >= (float)$rightValue : false; break;
                case '<=': $result = is_numeric($leftValue) && is_numeric($rightValue) ? (float)$leftValue <= (float)$rightValue : false; break;
            }
            return $isNegated ? !$result : $result;
        }
        
        // Simple boolean check
        $value = $this->getNestedValue(trim($condition));
        $result = !empty($value);
        return $isNegated ? !$result : $result;
    }
    
    private function evaluateCondition($condition) {
        // Split by OR operator first
        $orParts = explode('||', $condition);
        
        foreach ($orParts as $orPart) {
            // Split by AND operator
            $andParts = explode('&&', trim($orPart));
            
            // Check all AND conditions
            $andResult = true;
            foreach ($andParts as $part) {
                if (!$this->evaluateSingleCondition(trim($part))) {
                    $andResult = false;
                    break;
                }
            }
            
            // If any OR condition is true, return true
            if ($andResult) {
                return true;
            }
        }
        
        return false;
    }
    
    private function processFunction($matches) {
        $functionName = trim($matches[1]);
        $argument = trim($matches[2]);
        
        // Get the argument value (could be a variable path)
        $argValue = $this->getNestedValue($argument);
        
        // Check if function exists
        if (function_exists($functionName)) {
            return call_user_func($functionName, $argValue);
        }
        
        // If function doesn't exist, return a comment
        return '<!-- Function not found: ' . htmlspecialchars($functionName) . ' -->';
    }
    
    private function getNestedValue($path) {
        // Normalize path by handling mixed notation (brackets and dots)
        // First, handle the case where we have brackets followed by dots
        // Example: user[details].balance
        $path = preg_replace_callback(
            '/\[([^\[\]]+)\]\.([a-zA-Z0-9_]+)/', 
            function($match) {
                return '[' . $match[1] . '][' . $match[2] . ']';
            }, 
            $path
        );
        
        // Fix mismatched brackets if present
        $path = preg_replace('/\]\s*\]/', ']', $path);
        $path = preg_replace('/\[\s*\[/', '[', $path);
        
        // Fix common syntax errors like mixed brackets
        if (substr_count($path, '[') != substr_count($path, ']')) {
            $path = preg_replace('/\]?\s*$/', '', $path); // Remove trailing mismatched bracket
        }
        
        // Handle multi-level bracket notation
        // Convert all bracket notation to dot notation for consistent processing
        $path = preg_replace_callback(
            '/\[([^\[\]]+)\]/', 
            function($match) {
                return '.' . trim($match[1], '"\'');
            }, 
            $path
        );
        
        $parts = explode('.', $path);
        $current = $this->variables;

        foreach ($parts as $part) {
            if (empty($part)) continue; // Skip empty parts
            
            if (is_array($current)) {
                // Support both associative arrays and arrays with name/type structure
                if (isset($current[$part])) {
                    $current = $current[$part];
                } elseif (isset($current['name']) && $part === 'name') {
                    $current = $current['name'];
                } elseif (isset($current['type']) && $part === 'type') {
                    $current = $current['type'];
                } else {
                    return null;
                }
            } elseif (is_object($current) && isset($current->$part)) {
                $current = $current->$part;
            } else {
                return null;
            }
        }
    
        return $current;
    }
}
?>
