<?php

namespace Leane;

use Symfony\Component\Yaml\Yaml;

class TwigTemplateEngine {

    private $foundTemplates = [];

    private $templateDirectory;

    private $twig;

    private $outputFile;

    private $outputDirectory;

    public function getTemplateDirectory() {
        return $this->templateDirectory;
    }

    public function setOutputFile($outputFile) {
        $this->outputFile = $outputFile;
    }

    public function setOutputDirectory($outputDirectory) {
        $this->outputDirectory = $outputDirectory;
    }

    public function findAllTemplates($templateArgument) {
        if (is_dir($templateArgument)) {
            $di = new \RecursiveDirectoryIterator($templateArgument);
            $ii = new \RecursiveIteratorIterator($di);

            $this->foundTemplates = [];
            foreach ($ii as $item) {
                if ($item->isFile() && $this->hasTwigExtension($item->getBasename())) {
                    $this->foundTemplates[] = $item;
                }
            }

            if (empty($this->foundTemplates)) {
                throw new \RuntimeException("No template found in directory: $templateArgument");
            }

            $this->templateDirectory = new \SplFileInfo($templateArgument);
        } else if (is_file($templateArgument)) {
            if (!$this->hasTwigExtension($templateArgument)) {
                throw new \RuntimeException("Template file must have '.twig' extension: $templateArgument");
            }

            $singleTemplate = new \SplFileInfo($templateArgument);
            $this->foundTemplates = [$singleTemplate];
            $this->templateDirectory = $singleTemplate->getPathInfo();
        } else {
            throw new \RuntimeException("Template file or directory not found: $templateArgument");
        }

        return $this->foundTemplates;
    }

    private function hasTwigExtension($name) {
        return preg_match('/\.twig$/', $name);
    }

    private function getTwig() {
        if (!$this->twig) {
            $loader = new \Twig_Loader_Filesystem($this->templateDirectory->getRealpath());
            $this->twig = new \Twig_Environment($loader, []);
        }

        return $this->twig;
    }

    public function render(\SplFileInfo $templateFile, $data) {
        $templateKey = substr($templateFile->getRealpath(), strlen($this->templateDirectory->getRealPath()));
        $template = $this->getTwig()->load($templateKey);
        $result = $template->render($data);

        $outputFilename = $this->calculateOutputFilename($templateFile);

        $outputFileInfo = new \SplFileInfo($outputFilename);
        if (!is_dir($outputFileInfo->getPath())) {
            mkdir($outputFileInfo->getPath(), 0777, true);
        }

        file_put_contents($outputFilename, $result);
    }

    private function calculateOutputFilename(\SplFileInfo $templateFile) {
        if ($this->outputFile) {
            return $this->outputFile;
        } else if ($this->outputDirectory) {
            $relativePath = $this->calculateRelativePath($this->templateDirectory, $templateFile->getPathInfo());
            return $this->outputDirectory
                . '/'
                . $relativePath
                . '/'
                . $templateFile->getBasename('.twig');
        } else {
            throw new \Exception('Missing output configuration (either outputFile or outputDirectory must be set)');
        }
    }

    private function calculateRelativePath(\SplFileInfo $rootPath, \SplFileInfo $descendantPath) {
        return substr($descendantPath->getRealPath(), strlen($rootPath->getRealPath()) + 1);
    }

}
