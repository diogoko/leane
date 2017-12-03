<?php

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class ArgumentsTest extends TestCase {

    private $command;

    private $tester;

    function setUp() {
        $this->command = new \Leane\RenderCommand();
        $this->tester = new CommandTester($this->command);
        $this->deleteResults(__DIR__ . '/resources');
    }

    function tearDown() {
        $this->deleteResults(__DIR__ . '/resources');
    }

    private function deleteResults($rootDir) {
        $results = ['a.txt', 'b.txt', 'subdir/p.txt'];
        foreach ($results as $n) {
            $n = $rootDir . '/' . $n;
            if (file_exists($n)) {
                unlink($n);
            }
        }
    }

    function testZeroArgs() {
        try {
            $this->tester->execute([
            ]);

            $this->fail();
        } catch (\RuntimeException $e) {
            $this->assertContains('Not enough arguments', $e->getMessage());
        }
    }

    function testOneArg() {
        try {
            $this->tester->execute([
                'template' => 'abc',
            ]);
        } catch (\RuntimeException $e) {
            $this->assertContains('Not enough arguments', $e->getMessage());
        }
    }

    function testTemplateDoesNotExist() {
        try {
            $this->tester->execute([
                'template' => 'non_existing',
                'data' => __DIR__ . '/resources/a.yaml',
            ]);

            $this->fail();
        } catch (\RuntimeException $e) {
            $this->assertContains('Template file or directory not found', $e->getMessage());
        }
    }

    function testTemplateDirectoryWithoutTwig() {
        try {
            $this->tester->execute([
                'template' => __DIR__ . '/resources/empty',
                'data' => __DIR__ . '/resources/a.yaml',
            ]);

            $this->fail();
        } catch (\RuntimeException $e) {
            $this->assertContains('No template found in directory', $e->getMessage());
        }
    }

    function testTemplateFileNotTwig() {
        try {
            $this->tester->execute([
                'template' => __DIR__ . '/resources/a.yaml',
                'data' => __DIR__ . '/resources/a.yaml',
            ]);

            $this->fail();
        } catch (\RuntimeException $e) {
            $this->assertContains('Template file must have \'.twig\' extension', $e->getMessage());
        }
    }

    function testDataDoesNotExist() {
        try {
            $this->tester->execute([
                'template' => __DIR__ . '/resources/a.txt.twig',
                'data' => 'non_existing.yaml',
            ]);

            $this->fail();
        } catch (\RuntimeException $e) {
            $this->assertContains('Data file not found', $e->getMessage());
        }
    }

    function testOutputWithDirectory() {
        try {
            $this->tester->execute([
                'template' => __DIR__ . '/resources/a.txt.twig',
                'data' => __DIR__ . '/resources/a.yaml',
                '--output' => 'x',
                '--directory' => 'y',
            ]);

            $this->fail();
        } catch (\RuntimeException $e) {
            $this->assertContains('Only one of --output or --directory can be used', $e->getMessage());
        }
    }

    function testTemplateFileNoOutputOption() {
        $this->tester->execute([
            'template' => __DIR__ . '/resources/a.txt.twig',
            'data' => __DIR__ . '/resources/a.yaml',
        ]);

        $this->assertTextFileEquals(__DIR__ . '/resources/a.txt.expected',
            __DIR__ . '/resources/a.txt');
    }

    function testTemplateFileOutputFile() {
        $this->tester->execute([
            'template' => __DIR__ . '/resources/a.txt.twig',
            'data' => __DIR__ . '/resources/a.yaml',
            '--output' => __DIR__ . '/resources/b.txt',
        ]);

        $this->assertTextFileEquals(__DIR__ . '/resources/a.txt.expected',
            __DIR__ . '/resources/b.txt');
}

    function testTemplateDirectoryNoOutputOption() {
        $this->tester->execute([
            'template' => __DIR__ . '/resources',
            'data' => __DIR__ . '/resources/a.yaml',
        ]);

        $this->assertTextFileEquals(__DIR__ . '/resources/a.txt.expected',
            __DIR__ . '/resources/a.txt');
        $this->assertTextFileEquals(__DIR__ . '/resources/subdir/p.txt.expected',
            __DIR__ . '/resources/subdir/p.txt');
    }

    function testTemplateDirectoryOutputFile() {
        // TODO
    }

    function testTemplateDirectoryOutputDirectory() {
        $tempDir = sys_get_temp_dir() . '/' . uniqid();
        mkdir($tempDir, 0777, true);

        try {
            $this->tester->execute([
                'template' => __DIR__ . '/resources',
                'data' => __DIR__ . '/resources/a.yaml',
                '--directory' => $tempDir,
            ]);

            $this->assertTextFileEquals(__DIR__ . '/resources/a.txt.expected',
                $tempDir . '/a.txt');
            $this->assertTextFileEquals(__DIR__ . '/resources/subdir/p.txt.expected',
                $tempDir . '/subdir/p.txt');
        } finally {
            $this->deleteResults($tempDir);
            rmdir($tempDir . '/subdir');
            rmdir($tempDir);
        }
    }

    private function assertTextFileEquals($expectedFile, $actualFile) {
        $this->assertEquals($this->normalizeLineEndings(file_get_contents($expectedFile)),
            $this->normalizeLineEndings(file_get_contents($actualFile)));
    }

    private function normalizeLineEndings($t) {
        return str_replace("\r\n", "\n", $t);
    }

}
