<?php
namespace Lychee\Bundle\ApiBundle\Error\CodeGenerator\Loader;

use Lychee\Bundle\ApiBundle\Error\CodeGenerator\Error;
use Lychee\Bundle\ApiBundle\Error\CodeGenerator\ErrorSection;
use Symfony\Component\Finder\Finder;
use Lychee\Bundle\ApiBundle\Error\CodeGenerator\ErrorSectionBuilder;
use Symfony\Component\Finder\SplFileInfo;

class XmlLoader implements LoaderInterface {

    static public function create() {
        return new self();
    }

    protected function getDefaultSourceRootPath() {
        if (DIRECTORY_SEPARATOR === '/') {
            $pattern = '/(?<=\\/src\\/).*?$/';
        } else {
            $pattern = '/(?<='.DIRECTORY_SEPARATOR.'src'.DIRECTORY_SEPARATOR.').*?$/';
        }

        return preg_replace($pattern, '', __DIR__);
    }

    public function load($rootPath = null) {
        if ($rootPath == null) {
            $rootPath = $this->getDefaultSourceRootPath();
        }

        $files = Finder::create()->in($rootPath)->files()
            ->name('*Error.xml')->getIterator();

        $sections = array();
        /** @var SplFileInfo $file*/
        foreach($files as $file) {
            /** @var ErrorSection $section */
            $section = $this->loadErrorSection($file->getPathname());
            $section->setClass($this->getFileClass($file));
            $code = $section->getCode();
            if (isset($sections[$code])) {
                throw new \LogicException("{$section->getClass()} has same code with {$sections[$code]->getClass()}");
            }

            $sections[$code] = $section;
        }

        ksort($sections, SORT_NUMERIC);
        return new \ArrayIterator($sections);
    }

    /**
     * @param string $filePath
     *
     * @return ErrorSectionBuilder
     * @throws \LogicException
     */
    private function loadErrorSection($filePath) {
        $sectionXml = simplexml_load_file($filePath);
        $sectionCode = intval($sectionXml->attributes()['code']);

        $section = new ErrorSectionBuilder();
        $section->setCode($sectionCode);

        $groupCodes = array();
        $nextGroupCode = 0;
        foreach ($sectionXml->children() as $groupXml) {
            $groupCode = $groupXml->attributes()['code'];
            $groupCode = $groupCode === null ? $nextGroupCode : intval($groupCode);
            $nextGroupCode = max($groupCode, $nextGroupCode) + 1;
            if (in_array($groupCode, $groupCodes)) {
                throw new \LogicException("duplicate group code {$groupCode} at file '{$filePath}'");
            }
            $groupCodes[] = $groupCode;

            if ($groupXml->children()->count() >= 100) {
                throw new \LogicException("group with code {$groupCode} exceed 99 error at file '{$filePath}'");
            }

            $this->loadErrorGroup($groupXml, $groupCode, $section, $filePath);
        }

        return $section;
    }

    private function loadErrorGroup($groupXml, $groupCode, $section, $filePath) {
        $errorCodes = array();
        $nextErrorCode = 1;
        foreach ($groupXml->children() as $error) {
            $name = $error->attributes()['name'];
            if ($name == null) {
                throw new \LogicException("error without name at file '{$filePath}'");
            }

            $message = $error->__toString();
            if (strlen($message) === 0) {
                $message = $this->buildMessageFromErrorName($name);
            }

            $errorCode = $error->attributes()['code'];
            $errorCode = $errorCode === null ? $nextErrorCode : intval($errorCode);
            $nextErrorCode = max($errorCode, $nextErrorCode) + 1;
            if (in_array($errorCode, $errorCodes)) {
                throw new \LogicException("duplicate group code {$errorCode} at file '{$filePath}'");
            }
            $errorCodes[] = $errorCode;

            $code = intval(sprintf('%02d%02d%02d', $section->getCode(), $groupCode, $errorCode));

            $displayMessage = $error->attributes()['display'];
            $section->addError(new Error($name, $code, $message, $displayMessage));
        }
    }

    private function getFileClass(SplFileInfo $file) {
        $namespace = str_replace('/', '\\', $file->getRelativePath());
        $className = preg_replace('/\\.xml/', '', $file->getFilename());
        return "{$namespace}\\{$className}";
    }

    private function buildMessageFromErrorName($name) {
        $words = preg_split('/(?<=.)(?=[A-Z])/', $name);
        return implode(' ', $words);
    }
} 