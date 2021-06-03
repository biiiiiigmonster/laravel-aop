<?php

namespace BiiiiiigMonster\Aop;

use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter;

class Proxy
{
    private Parser $parser;
    private NodeTraverser $traverser;
    private PrettyPrinter\Standard $printer;
    /** @var NodeVisitor[] $visitors */
    private array $visitors;

    public function __construct(array $visitors = [])
    {
        // 初始化解析器
        $this->parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);
        $this->traverser = new NodeTraverser();
        $this->printer = new PrettyPrinter\Standard();
        $this->visitors = $visitors;
    }

    /**
     * add visitor.
     * @param NodeVisitor $visitor
     */
    public function addVisitor(NodeVisitor $visitor): void
    {
        $this->visitors[] = $visitor;
    }

    /**
     * generate proxy code.
     * @param string $code
     * @return string
     */
    public function generateProxyCode(string $code): string
    {
        // 将源代码解析成AST
        $ast = $this->parser->parse($code);
        // 向遍历器添加节点访问器
        foreach ($this->visitors as $visitor) {
            $this->traverser->addVisitor($visitor);
        }
        // 遍历器遍历源代码AST，因为上一步中自定义了节点访问器，因此每一个节点都将被处理
        $nodes = $this->traverser->traverse($ast);
        // 将遍历器处理后的AST最终输出成代理后代码
        return $this->printer->prettyPrintFile($nodes);
    }
}
