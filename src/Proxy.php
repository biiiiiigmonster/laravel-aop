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

    /**
     * Proxy constructor.
     * @param SplFileInfo $file
     * @param array $visitors
     */
    public function __construct(SplFileInfo $file, array $visitors = [])
    {
        $this->file = $file;
        // 初始化解析器
        $this->parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);
        $this->traverser = new NodeTraverser();
        $this->printer = new PrettyPrinter\Standard();
        $this->visitors = $visitors;
    }

    /**
     * Generate proxy code.
     *
     * @return string
     */
    private function generateProxyCode(): string
    {
        // 将源代码解析成AST
        $ast = $this->parser->parse(file_get_contents($this->file->getPathname()));
        // 向遍历器添加节点访问器
        foreach ($this->visitors as $visitor) {
            $this->traverser->addVisitor($visitor);
        }
        // 遍历器遍历源代码AST，因为上一步中自定义了节点访问器，因此每一个节点都将被处理
        $nodes = $this->traverser->traverse($ast);
        // 将遍历器处理后的AST最终输出成代理后代码
        return $this->printer->prettyPrintFile($nodes);
    }

    /**
     * Proxy file path.
     *
     * @return string
     */
    private function proxyFilepath(): string
    {
        return sprintf(
            '%s' . DIRECTORY_SEPARATOR . '%s',
            AopConfig::instance()->getStorageDir(),
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
        $temPath = $proxyFile . '.' . uniqid();
        file_put_contents($temPath, $this->generateProxyCode());
        // todo: if file put fail, throw exception.
        rename($temPath, $proxyFile);
        return $proxyFile;
    }
}
