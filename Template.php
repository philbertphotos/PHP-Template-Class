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
 * @version   1.1.4
 */
class Template {
    private $variables = [];
    private $templateDir;
    private $templateExt;
    private $maxMemory = 268435456; // 256MB default limit
    private $maxIterations = 20; // Increased from 10 to handle more complex templates
    private $errorMode = 'comment'; // 'comment', 'exception', or 'silent'
    private $debugMode = false;
    
    public function __construct($templateDir, $templateExt = 'html', $options = []) {
        if (empty($templateDir)) {
            throw new Exception("Template directory must be specified");
        }
        
        $this->templateDir = rtrim($templateDir, '/') . '/';
        $this->templateExt = ltrim($templateExt, '.');
        
        // Set options if provided
        if (isset($options['maxMemory'])) {
            $this->maxMemory = (int)$options['maxMemory'];
        }
        
        if (isset($options['maxIterations'])) {
            $this->maxIterations = (int)$options['maxIterations'];
        }
        
        if (isset($options['errorMode']) && in_array($options['errorMode'], ['comment', 'exception', 'silent'])) {
            $this->errorMode = $options['errorMode'];
        }
        
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
        // Check memory usage before processing
        $this->checkMemoryUsage();
        
        try {
            // Process includes first
            $template = preg_replace_callback(
                '/\{\{\s*inc\([\'"]([^\'"]+)[\'"]\)\s*\}\}/',
                array($this, 'processInclude'),
                $template
            );
            
            // Process function calls in variables with improved pattern
            $template = preg_replace_callback(
                '/\{\{\s*([a-zA-Z0-9_]+)\(([^{}]*(?:\{[^{}]*\}[^{}]*)*)\)\s*\}\}/',
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
            $template = $this->processForLoops($template);
            
            // Process variables again after loops
            $template = preg_replace_callback(
                '/\{\{\s*([a-zA-Z0-9._]+)\s*\}\}/',
                array($this, 'replaceVariable'),
                $template
            );
            
            // Process function calls again after loops
            $template = preg_replace_callback(
                '/\{\{\s*([a-zA-Z0-9_]+)\(([^{}]*(?:\{[^{}]*\}[^{}]*)*)\)\s*\}\}/',
                array($this, 'processFunction'),
                $template
            );
            
            // Process nested if conditions with improved pattern
            $template = $this->processIfConditions($template);
            
            // Clean up any remaining tags and extra whitespace - OPTIMIZED to prevent memory issues
            $template = $this->cleanupTemplate($template);
            
        } catch (Exception $e) {
            if ($this->errorMode === 'exception') {
                throw $e;
            } elseif ($this->errorMode === 'comment') {
                $template = "<!-- Template processing error: " . htmlspecialchars($e->getMessage()) . " -->";
            }
            // In 'silent' mode, we just return the template as-is
        }
        
        return $template;
    }
    
    private function processForLoops($template) {
        $iteration = 0;
        $lastTemplate = '';
    
        while ($template !== $lastTemplate && $iteration < $this->maxIterations) {
            $lastTemplate = $template;
            $this->checkMemoryUsage();
    
            $processed = false;
    
            // Key-value for loop: {% for key, value in array %}
            $patternKeyValue = '/\{%\s*for\s+(\w+)\s*,\s*(\w+)\s+in\s+([a-zA-Z0-9._\[\]]+)\s*%\}(.*?)\{%\s*endfor\s*%\}/si';
            if (preg_match_all($patternKeyValue, $template, $kvMatches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
                // Process from last to first to avoid offset issues
                usort($kvMatches, function($a, $b) {
                    return $b[0][1] - $a[0][1];
                });
                foreach ($kvMatches as $match) {
                    $fullMatch = $match[0][0];
                    $offset = $match[0][1];
                    $keyName = $match[1][0];
                    $valueName = $match[2][0];
                    $arrayPath = $match[3][0];
                    $content = $match[4][0];
    
                    // Support both dot and bracket notation
                    $array = $this->getNestedValue($arrayPath);
                    $result = '';
                    if (is_array($array)) {
                        $result = $this->processKeyValueLoop($array, $keyName, $valueName, $arrayPath, $content);
                    }
                    $template = substr_replace($template, $result, $offset, strlen($fullMatch));
                    $processed = true;
                }
            }
    
            // Regular for loop: {% for item in array %}
            $patternRegular = '/\{%\s*for\s+(\w+)\s+in\s+([a-zA-Z0-9._\[\]]+)\s*%\}(.*?)\{%\s*endfor\s*%\}/si';
            if (preg_match_all($patternRegular, $template, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
                // Process from last to first to avoid offset issues
                usort($matches, function($a, $b) {
                    return $b[0][1] - $a[0][1];
                });
                foreach ($matches as $match) {
                    $fullMatch = $match[0][0];
                    $offset = $match[0][1];
                    $itemName = $match[1][0];
                    $arrayPath = $match[2][0];
                    $content = $match[3][0];
    
                    // Support both dot and bracket notation
                    $array = $this->getNestedValue($arrayPath);
                    $result = '';
                    if (is_array($array)) {
                        $result = $this->processLoop($array, $itemName, $arrayPath, $content);
                    }
                    $template = substr_replace($template, $result, $offset, strlen($fullMatch));
                    $processed = true;
                }
            }
    
            if (!$processed) {
                break;
            }
            $iteration++;
        }
    
        return $template;
    }
    
    /**
     * Process a key-value loop (for key, value in array)
     */
    private function processKeyValueLoop($array, $keyName, $valueName, $arrayPath, $content) {
        $result = '';
        $originalVars = $this->variables; // Backup original scope
        $arrayLength = count($array);
        $index = 0;

        foreach ($array as $key => $value) {
            $this->checkMemoryUsage();

            // Create new scope for this iteration
            $iterationVars = $originalVars;
            $iterationVars[$keyName] = $key;
            $iterationVars[$valueName] = $value;

            // Add loop metadata
            $iterationVars['loop'] = [
                'index' => $index + 1,
                'first' => ($index === 0),
                'last' => ($index === $arrayLength - 1),
                'length' => $arrayLength,
                'parent' => isset($originalVars['loop']) ? $originalVars['loop'] : null
            ];

            // Set the current scope for variable replacement
            $this->variables = $iterationVars;

            // Process the template with the current variables
            $processedContent = $this->processTemplate($content);
            $result .= $processedContent;
            
            $index++;
        }

        // Restore original variables scope
        $this->variables = $originalVars;

        return $result;
    }

    private function processLoop($array, $itemName, $arrayPath, $content) {
        $result = '';
        $originalVars = $this->variables; // Backup original scope
        $arrayLength = count($array);
        $index = 0;

        // Use array_values to ensure sequential numeric keys for index calculation if original keys are not sequential
        $arrayValues = array_values($array);

        foreach ($arrayValues as $item) {
            $this->checkMemoryUsage();

            // Create new scope for this iteration
            $iterationVars = $originalVars;
            $iterationVars[$itemName] = $item;

            // Add loop metadata
            $iterationVars['loop'] = [
                'index' => $index + 1,
                'first' => ($index === 0),
                'last' => ($index === $arrayLength - 1),
                'length' => $arrayLength,
                'parent' => isset($originalVars['loop']) ? $originalVars['loop'] : null // Support nested loops
            ];

            // Set the current scope for variable replacement
            $this->variables = $iterationVars;

            // Process the template with the current variables
            $processedContent = $this->processTemplate($content);
            $result .= $processedContent;
            
            $index++;
        }

        // Restore original variables scope
        $this->variables = $originalVars;

        return $result;
    }

    /**
     * Helper method to replace variables within a given string using the current scope.
     */
    private function processVariablesInString($string) {
        // Process variables with bracket notation first
        $string = preg_replace_callback(
            '/\{\{\s*([a-zA-Z0-9_]+(?:\[[^\]]+\])+(?:\.[a-zA-Z0-9_]+)*)\s*\}\}/',
            array($this, 'replaceVariable'),
            $string
        );
        // Process regular variables (must come after bracket notation)
        $string = preg_replace_callback(
            '/\{\{\s*([a-zA-Z0-9._]+)\s*\}\}/',
            array($this, 'replaceVariable'),
            $string
        );
        return $string;
    }

    private function processIfConditions($template) {
        $iteration = 0;
        $lastTemplate = '';
        
        while ($template !== $lastTemplate && $iteration < $this->maxIterations) {
            $this->checkMemoryUsage();
            
            $lastTemplate = $template;
            
            // First, process if-else blocks (simpler case)
            $template = preg_replace_callback(
                '/\{%\s*if\s+(.+?)\s*%\}(.*?)(?:\{%\s*else\s*%\}(.*?))?\{%\s*endif\s*%\}/s',
                array($this, 'processSimpleCondition'),
                $template
            );
            
            // Then process if-elseif-else blocks (more complex case)
            // This pattern matches the entire if-elseif-else structure
            $ifElseifPattern = '/\{%\s*if\s+(.+?)\s*%\}(.*?)(?:\{%\s*elseif\s+(.+?)\s*%\}(.*?))+(?:\{%\s*else\s*%\}(.*?))?\{%\s*endif\s*%\}/s';
            
            if (preg_match_all($ifElseifPattern, $template, $complexMatches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
                // Process from end to beginning to avoid offset issues
                usort($complexMatches, function($a, $b) {
                    return $b[0][1] - $a[0][1]; // Sort by position in descending order
                });
                
                foreach ($complexMatches as $match) {
                    $fullMatch = $match[0][0];
                    $offset = $match[0][1];
                    $length = strlen($fullMatch);
                    
                    // Extract all parts of the complex condition
                    $blocks = $this->parseIfElseifElseBlocks($fullMatch);
                    
                    // Evaluate conditions in order
                    $result = '';
                    if ($this->evaluateCondition($blocks['if']['condition'])) {
                        $result = $this->processTemplate($blocks['if']['content']);
                    } else {
                        $elseifMatched = false;
                        foreach ($blocks['elseif'] as $elseif) {
                            if ($this->evaluateCondition($elseif['condition'])) {
                                $result = $this->processTemplate($elseif['content']);
                                $elseifMatched = true;
                                break;
                            }
                        }
                        
                        if (!$elseifMatched && isset($blocks['else'])) {
                            $result = $this->processTemplate($blocks['else']);
                        }
                    }
                    
                    // Replace the entire if-elseif-else block with the result
                    $template = substr_replace($template, $result, $offset, $length);
                }
            }
            
            $iteration++;
        }
        
        // Clean up any remaining if conditions that couldn't be processed
        if ($this->debugMode === false) {
            // More specific patterns to avoid over-cleaning
            $template = preg_replace('/\{%\s*if\s+[^%]+\s*%\}.*?\{%\s*endif\s*%\}/s', '', $template);
            $template = preg_replace('/\{%\s*(?:else|elseif\s+[^%]+|endif)\s*%\}/s', '', $template);
        }
        
        return $template;
    }
    
    private function parseIfElseifElseBlocks($content) {
        $blocks = [
            'if' => ['condition' => '', 'content' => ''],
            'elseif' => [],
            'else' => ''
        ];
        
        // Extract the if condition
        if (preg_match('/\{%\s*if\s+(.+?)\s*%\}/s', $content, $ifMatch)) {
            $blocks['if']['condition'] = trim($ifMatch[1]);
        }
        
        // Extract content between if and first elseif/else/endif
        if (preg_match('/\{%\s*if\s+.+?\s*%\}(.*?)(?:\{%\s*(?:elseif|else|endif)\s*.*?%\})/s', $content, $ifContentMatch)) {
            $blocks['if']['content'] = $ifContentMatch[1];
        }
        
        // Extract all elseif blocks with their conditions and content
        preg_match_all('/\{%\s*elseif\s+(.+?)\s*%\}(.*?)(?:\{%\s*(?:elseif|else|endif)\s*.*?%\})/s', $content, $elseifMatches, PREG_SET_ORDER);
        
        foreach ($elseifMatches as $match) {
            $blocks['elseif'][] = [
                'condition' => trim($match[1]),
                'content' => $match[2]
            ];
        }
        
        // Extract else content if it exists
        if (preg_match('/\{%\s*else\s*%\}(.*?)\{%\s*endif\s*%\}/s', $content, $elseMatch)) {
            $blocks['else'] = $elseMatch[1];
        }
        
        return $blocks;
    }
    
    private function cleanupTemplate($template) {
        // Process in smaller chunks to avoid memory issues
        $chunks = $this->splitTemplateIntoChunks($template, 10000);
        $processedChunks = [];
        
        foreach ($chunks as $chunk) {
            // Clean up any remaining tags
            $chunk = preg_replace('/\{%\s*(?:else|elseif|endif)\s*%\}/s', '', $chunk);
            
            // Clean up whitespace more efficiently
            $chunk = preg_replace('/^\s+|\s+$/m', '', $chunk);
            
            $processedChunks[] = $chunk;
        }
        
        return implode('', $processedChunks);
    }
    
    /**
     * Split a template string into smaller chunks to avoid memory issues
     * 
     * @param string $template The template to split
     * @param int $chunkSize The maximum size of each chunk
     * @return array An array of template chunks
     */
    private function splitTemplateIntoChunks($template, $chunkSize) {
        $chunks = [];
        $length = strlen($template);
        
        for ($i = 0; $i < $length; $i += $chunkSize) {
            $chunks[] = substr($template, $i, $chunkSize);
        }
        
        return $chunks;
    }
    
    private function processInclude($matches) {
        $includePath = $matches[1];
        $fullPath = $this->templateDir . $includePath . '.' . $this->templateExt;
        
        if (!file_exists($fullPath)) {
            // Handle null path
            return '<!-- Include not found: ' . htmlspecialchars((string)$includePath) . ' -->';
        }
        
        try {
            $content = file_get_contents($fullPath);
            return $this->processTemplate($content);
        } catch (Exception $e) {
            return '<!-- Error including ' . htmlspecialchars((string)$includePath) . ': ' . 
                   htmlspecialchars($e->getMessage()) . ' -->';
        }
    }
    
    private function replaceVariable($matches) {
        $path = $matches[1];
        
        try {
            // Check if this is a nested property access within a loop item
            if (strpos($path, '.') !== false) {
                $parts = explode('.', $path);
                $firstPart = $parts[0];
                
                // If the first part exists in variables, use getNestedValue
                if (array_key_exists($firstPart, $this->variables)) {
                    $value = $this->getNestedValue($path);
                    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
                }
            }
            
            // Regular variable access
            $value = $this->getNestedValue($path);
            
            // Handle null values
            if ($value === null) {
                if ($this->debugMode) {
                    return "<!-- Debug: Variable '{$path}' not found -->";
                }
                return '';
            }
            
            return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
        } catch (Exception $e) {
            if ($this->debugMode) {
                return '<!-- Error processing variable ' . htmlspecialchars($path) . ': ' . 
                       htmlspecialchars($e->getMessage()) . ' -->';
            }
            return '';
        }
    }
    
    private function processCondition($matches) {
        $condition = trim($matches[1]);
        $content = $matches[2];
        
        try {
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
        } catch (Exception $e) {
            return '<!-- Error processing condition: ' . htmlspecialchars($e->getMessage()) . ' -->';
        }
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
        
        try {
            // Handle function arguments that might contain nested variables
            if (preg_match('/^[\'"](.+?)[\'"]$/', $argument, $stringMatch)) {
                // String literal argument
                $argValue = $stringMatch[1];
            } elseif (preg_match('/^[\'"](.+?)[\'"],\s*(.+)$/', $argument, $multiArgMatch)) {
                // Multiple arguments (string + variable)
                $firstArg = $multiArgMatch[1];
                $secondArg = $this->getNestedValue(trim($multiArgMatch[2]));
                return call_user_func($functionName, $firstArg, $secondArg);
            } else {
                // Variable argument
                $argValue = $this->getNestedValue($argument);
            }
            
            // Check if function exists
            if (function_exists($functionName)) {
                return call_user_func($functionName, $argValue);
            }
            
            // If function doesn't exist, return a comment
            return '<!-- Function not found: ' . htmlspecialchars($functionName) . ' -->';
        } catch (Exception $e) {
            return '<!-- Error processing function ' . htmlspecialchars($functionName) . ': ' . 
                   htmlspecialchars($e->getMessage()) . ' -->';
        }
    }
    
    private function getNestedValue($path) {
        try {
            // Normalize path by handling mixed notation (brackets and dots)
            $path = preg_replace_callback(
                '/\[([^\[\]]+)\]/', 
                function($match) {
                    return '.' . trim($match[1], '"\'');
                }, 
                $path
            );
            
            $parts = explode('.', $path);
            if (empty($parts)) {
                return null;
            }
            
            // Always start from the current scope (which may be a loop context)
            $current = $this->variables;
            
            foreach ($parts as $part) {
                if (empty($part)) continue;
                
                // Special handling for .length
                if ($part === 'length') {
                    if (is_array($current) || $current instanceof Countable) {
                        return count($current);
                    } elseif (is_string($current)) {
                        return strlen($current);
                    } else {
                        return null;
                    }
                }
            
                if (is_array($current) && array_key_exists($part, $current)) {
                    $current = $current[$part];
                } elseif (is_object($current) && property_exists($current, $part)) {
                    $current = $current->$part;
                } else {
                    // Property not found
                    if ($this->debugMode) {
                        $type = is_array($current) ? 'array' : (is_object($current) ? 'object of class ' . get_class($current) : gettype($current));
                        error_log("Template Debug: Property '{$part}' not found in {$type}");
                        if (is_array($current)) {
                            error_log("Template Debug: Available keys: " . implode(', ', array_keys($current)));
                        }
                    }
                    return null;
                }
            }
            
            return $current;
        } catch (Exception $e) {
            if ($this->errorMode === 'exception') {
                throw $e;
            }
            return null;
        }
    }
    
    private function checkMemoryUsage() {
        $memoryUsage = memory_get_usage(true);
        $memoryLimit = $this->maxMemory;
        
        if ($memoryUsage > $memoryLimit * 0.9) {
            // If we're using more than 90% of our limit, throw an exception
            throw new Exception("Memory usage too high: {$memoryUsage} bytes used, limit is {$memoryLimit} bytes");
        }
    }
    
    /**
     * Set the maximum memory usage allowed for template processing
     * 
     * @param int $bytes Maximum memory in bytes
     * @return Template
     */
    public function setMaxMemory($bytes) {
        $this->maxMemory = (int)$bytes;
        return $this;
    }
    
    /**
     * Set the maximum number of iterations for loops and conditions
     * 
     * @param int $iterations Maximum iterations
     * @return Template
     */
    public function setMaxIterations($iterations) {
        $this->maxIterations = (int)$iterations;
        return $this;
    }
    
    /**
     * Set the error handling mode
     * 
     * @param string $mode 'comment', 'exception', or 'silent'
     * @return Template
     */
    public function setErrorMode($mode) {
        if (in_array($mode, ['comment', 'exception', 'silent'])) {
            $this->errorMode = $mode;
        }
        return $this;
    }

     /**
      * 
      * @param string $template The template string
      * @return string The processed template
      */
     private function processSimpleCondition($matches) {
        $condition = trim($matches[1]);
        $ifContent = isset($matches[2]) ? $matches[2] : '';
        $elseContent = isset($matches[3]) ? $matches[3] : '';
    
        try {
            if ($this->evaluateCondition($condition)) {
                return $this->processTemplate($ifContent);
            } else {
                return $this->processTemplate($elseContent);
            }
        } catch (Exception $e) {
            if ($this->errorMode === 'exception') {
                throw $e;
            } elseif ($this->errorMode === 'comment') {
                return '<!-- Error processing condition: ' . htmlspecialchars($e->getMessage()) . ' -->';
            }
            return '';
        }
    }

    /**
     *
     * @param bool $debugMode
     * @return Template
     */
    public function setDebugMode($debugMode) {
        $this->debugMode = $debugMode;
        return $this;
    }
}
?>
