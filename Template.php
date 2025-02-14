<?php
class Template {
    private $variables = [];
    private $templateDir;
    private $templateExt;
    
    public function __construct($templateDir, $templateExt = 'html') {
        if (empty($templateDir)) {
            throw new Exception("Template directory must be specified");
        }
        
        $this->templateDir = rtrim($templateDir, '/');
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
        $templatePath = $this->templateDir . '/' . $templateName . '.' . $this->templateExt;
        
        if (!file_exists($templatePath)) {
            throw new Exception("Template file not found: {$templatePath}");
        }
        
        $template = file_get_contents($templatePath);
        return $this->processTemplate($template);
    }
    
    private function processTemplate($template) {
        // Replace variables first
        $template = preg_replace_callback(
            '/\{\{\s*([a-zA-Z0-9._]+)\s*\}\}/',
            array($this, 'replaceVariable'),
            $template
        );

        // Process nested if conditions
        $maxIterations = 10;
        $iteration = 0;
        $lastTemplate = '';
        
        while ($template !== $lastTemplate && $iteration < $maxIterations) {
            $lastTemplate = $template;
            $template = preg_replace_callback(
                '/\{%\s*if\s+(.+?)\s*%\}(.*?)\{%\s*endif\s*%\}/s',
                array($this, 'processCondition'),
                $template
            );
            $iteration++;
        }

        // Clean up any remaining tags and extra whitespace
        $template = preg_replace('/\{%\s*(?:else|elseif|endif)\s*%\}/s', '', $template);
        $template = preg_replace('/(?:\s*<hr\s*\/>\s*){2,}/', '<hr />', $template);
        $template = preg_replace('/^\s+|\s+$/m', '', $template);
        
        return $template;
    }
    
    private function replaceVariable($matches) {
        return htmlspecialchars((string)$this->getVariableValue($matches[1]));
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
            'else' => null
        );
        
        // Split content by elseif and else tags
        $pattern = '/\{%\s*(elseif|else)(?:\s+([^%]+))?\s*%\}/';
        $parts = preg_split($pattern, $content, -1, PREG_SPLIT_DELIM_CAPTURE);
        
        // First part is always the if content
        $blocks['if'] = trim($parts[0]);
        
        // Process remaining parts
        $count = count($parts);
        for ($i = 1; $i < $count; $i += 3) {
            if (!isset($parts[$i])) {
                break;
            }
            
            if ($parts[$i] === 'elseif' && isset($parts[$i + 1])) {
                $blocks['elseif'][] = array(
                    'condition' => trim($parts[$i + 1]),
                    'content' => isset($parts[$i + 2]) ? trim($parts[$i + 2]) : ''
                );
            } elseif ($parts[$i] === 'else') {
                $blocks['else'] = isset($parts[$i + 2]) ? trim($parts[$i + 2]) : '';
                break;
            }
        }
        
        return $blocks;
    }
    
    private function evaluateCondition($condition) {
        $parts = explode('&&', $condition);
        
        foreach ($parts as $part) {
            if (!$this->evaluateSingleCondition(trim($part))) {
                return false;
            }
        }
        
        return true;
    }
    
    private function evaluateSingleCondition($condition) {
        if (preg_match('/(.+?)\s*(===|==|>|<|>=|<=)\s*(.+)/', $condition, $matches)) {
            $left = trim($matches[1]);
            $operator = $matches[2];
            $right = trim($matches[3], '"\'');
            
            $leftValue = $this->getVariableValue(trim($left));
            
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
            
            switch ($operator) {
                case '===':
                    return $leftValue === $rightValue;
                case '==':
                    return $leftValue == $rightValue;
                case '>':
                    if (is_numeric($leftValue) && is_numeric($rightValue)) {
                        return (float)$leftValue > (float)$rightValue;
                    }
                    return false;
                case '<':
                    if (is_numeric($leftValue) && is_numeric($rightValue)) {
                        return (float)$leftValue < (float)$rightValue;
                    }
                    return false;
                case '>=':
                    if (is_numeric($leftValue) && is_numeric($rightValue)) {
                        return (float)$leftValue >= (float)$rightValue;
                    }
                    return false;
                case '<=':
                    if (is_numeric($leftValue) && is_numeric($rightValue)) {
                        return (float)$leftValue <= (float)$rightValue;
                    }
                    return false;
                default:
                    return false;
            }
        }
        
        return (bool)$this->getVariableValue(trim($condition));
    }
    
    private function getVariableValue($path) {
        $parts = explode('.', $path);
        $value = $this->variables;
        
        foreach ($parts as $part) {
            if (is_array($value) && isset($value[$part])) {
                $value = $value[$part];
            } elseif (is_object($value) && isset($value->$part)) {
                $value = $value->$part;
            } else {
                return null;
            }
        }
        
        return $value;
    }
}
?>
