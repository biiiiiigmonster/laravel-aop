<?php

namespace BiiiiiigMonster\Aop;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;
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
    private array $visitors;
    private string $proxyCode;

    /**
     * Proxy constructor.
     * @param SplFileInfo $file
     * @param array $visitors
     */
    public function __construct(SplFileInfo $file, NodeVisitor ...$visitors)
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
        $dir = Str::replaceFirst(
            App::basePath(),
            App::make('config')->get('aop.storage_path', App::storagePath('aop')),
            $this->file->getPath()
        );

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
        if (!App::isProduction() || !file_exists($proxyFile)) {
            $temPath = $proxyFile . '.' . uniqid();
            file_put_contents($temPath, $this->proxyCode);
            rename($temPath, $proxyFile);
        }

        return $proxyFile;
    }
}
