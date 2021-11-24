<?php

namespace BiiiiiigMonster\Aop;

use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter;
use SplFileInfo;

class Proxy
{
    private SplFileInfo $file;
    private Parser $parser;
    private NodeTraverser $traverser;
    private PrettyPrinter\Standard $printer;
    /** @var NodeVisitor[] $visitors */
    private array $visitors;
    private string $proxyCode;

    /**
     * Proxy constructor.
     * @param SplFileInfo $file
     * @param array $visitors
     */
    public function __construct(SplFileInfo $file, array $visitors = [])
    {
        $this->parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);
        $this->traverser = new NodeTraverser();
        $this->printer = new PrettyPrinter\Standard();

        $this->file = $file;
        $this->visitors = $visitors;
        $this->proxyCode = $this->generateProxyCode();
    }

    /**
     * Generate proxy code.
     *
     * @return string
     */
    private function generateProxyCode(): string
    {
        $ast = $this->parser->parse(
            // original code.
            file_get_contents($this->file->getPathname())
        );

        // add visitor.
        foreach ($this->visitors as $visitor) {
            $this->traverser->addVisitor($visitor);
        }
        $nodes = $this->traverser->traverse($ast);

        // print proxy code.
        return $this->printer->prettyPrintFile($nodes);
    }

    /**
     * Proxy file path.
     *
     * @return string
     */
    private function proxyFilepath(): string
    {
        $relativePath = array_reverse(explode(base_path(), $this->file->getPath(), 2))[0];
        $dir = AopConfig::instance()->getStorageDir() . $relativePath;
        // create storage path dir when not exist.
        !is_dir($dir) && mkdir($dir, 0755, true);
        return sprintf(
            '%s' . DIRECTORY_SEPARATOR . '%s',
            $dir,
            $this->file->getFilename(),
        );
    }

    /**
     * Generate proxy file.
     *
     * @return string Return proxy file pathname.
     */
    public function generateProxyFile(): string
    {
        $proxyFile = $this->proxyFilepath();
        if (!file_exists($proxyFile)) {
            $temPath = $proxyFile . '.' . uniqid();
            file_put_contents($temPath, $this->proxyCode);
            rename($temPath, $proxyFile);
        }
        return $proxyFile;
    }
}
