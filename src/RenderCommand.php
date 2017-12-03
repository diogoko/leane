<?php

namespace Leane;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Yaml\Yaml;

class RenderCommand extends Command {

    protected function configure() {
        $this->setName('render')
            ->setDescription('Generate files from templates')
            ->addArgument('template', InputArgument::REQUIRED, 'Twig template file or directory with templates')
            ->addArgument('data', InputArgument::REQUIRED, 'YAML file with data to insert in the templates')
            ->addOption('output', 'o', InputOption::VALUE_REQUIRED, 'Output file (only if a single template file is specified)')
            ->addOption('directory', 'D', InputOption::VALUE_REQUIRED, 'Output directory');
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $data = $this->readData($input);

        $templateEngine = new TwigTemplateEngine();
        $templates = $templateEngine->findAllTemplates($input->getArgument('template'));
        $this->setOutputOption($input, $templateEngine);

        foreach ($templates as $t) {
            $templateEngine->render($t, $data);
        }
    }

    private function setOutputOption(InputInterface $input, TwigTemplateEngine $templateEngine) {
        $outputFile = $input->getOption('output');
        $outputDirectory = $input->getOption('directory');

        if ($outputFile && $outputDirectory) {
            throw new \RuntimeException('Only one of --output or --directory can be used');
        }

        if ($outputFile) {
            $templateEngine->setOutputFile($outputFile);
        } else {
            if (!$outputDirectory) {
                $outputDirectory = $templateEngine->getTemplateDirectory();
            }
            $templateEngine->setOutputDirectory($outputDirectory);
        }
    }

    private function readData(InputInterface $input) {
        $dataOption = $input->getArgument('data');
        if (!file_exists($dataOption)) {
            throw new \RuntimeException("Data file not found: $dataOption");
        }

        return Yaml::parse(file_get_contents($dataOption));
    }

}
