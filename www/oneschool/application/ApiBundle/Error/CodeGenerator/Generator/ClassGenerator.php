<?php
namespace Lychee\Bundle\ApiBundle\Error\CodeGenerator\Generator;

use Lychee\Bundle\ApiBundle\Error\CodeGenerator\Error;
use Lychee\Bundle\ApiBundle\Error\CodeGenerator\ErrorSection;

class ClassGenerator {
    public function generate(ErrorSection $section, $basePath, $namespace = null) {
        if ($namespace === null) {
            $output = $this->generateErrorClass($section->getErrors(), $section->getClass());
            $classPath = $this->getClassPath($basePath, $section->getClass());
            file_put_contents($classPath, $output);
        } else {
            list($oldNamespace, $className) = $this->splitNamespaceAndClassName($section->getClass());
            $newClass = $namespace . '\\' . $className;
            $output = $this->generateErrorClass($section->getErrors(), $newClass);
            $classPath = $this->getClassPath($basePath, $newClass);
            file_put_contents($classPath, $output);
        }
    }

    private function getClassPath($basePath, $class) {
        return $basePath . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';
    }

    private function splitNamespaceAndClassName($class) {
        $lastBackslashPos = strrpos($class, '\\');

        return ($lastBackslashPos === false || $lastBackslashPos === 0) ?
            array(null, $class) :
            array(substr($class, 0, $lastBackslashPos), substr($class, $lastBackslashPos + 1));
    }

    /**
     * @param Error[] $errors
     * @param $class
     * @return string
     */
    private function generateErrorClass($errors, $class) {
        list($namespace, $className) = $this->splitNamespaceAndClassName($class);

        $output = "<?php\n";
        $output .= $namespace === null ? "\n" : "namespace {$namespace};\n\n";
        $output .= "use Lychee\\Bundle\\ApiBundle\\Error\\Error;\n\nclass {$className} {\n";

        foreach ($errors as $error) {
            $output .= "    const CODE_{$error->getName()} = {$error->getCode()};\n";
        }

        foreach ($errors as $error) {
            if ($error->getDisplayMessage() !== null) {
                $displayMessage = '"' . addslashes($this->generateMessage($error->getDisplayMessage())) . '"';
            } else {
                $displayMessage = 'null';
            }

            $output .= "\n";
            $output .= "    static public function {$error->getName()}({$this->generateParameters($error->getMessage())}) {\n";
            $output .= "        \$_message = \"{$this->generateMessage($error->getMessage())}\";\n";
            $output .= "        \$_display = $displayMessage;\n";
            $output .= "        return new Error(self::CODE_{$error->getName()}, \$_message, \$_display);\n";
            $output .= "    }\n";
        }

        $output .= '}';
        return $output;
    }

    private function generateParameters($message) {
        $variableCount = preg_match_all('/\\{\s*([a-zA-Z_-]+)\s*\\}/', $message, $matches, PREG_PATTERN_ORDER);
        if ($variableCount == 0) {
            return '';
        } else {
            $parameters = array_unique($matches[1]);

            return implode(', ', array_map(function($parameter){return '$'.$parameter;}, $parameters));
        }
    }

    private function generateMessage($message) {
        $output = preg_replace('/\\{\s*([a-zA-Z_-]+)\s*\\}/', '{$$1}', $message);
        return $output;
    }
} 