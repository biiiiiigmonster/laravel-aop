English | [中文](./README-CN.md)

<div align="center">

# PHP8 Attribute

<p>
    <a href="https://github.com/biiiiiigmonster/php8-aop/blob/master/LICENSE"><img src="https://img.shields.io/badge/license-MIT-7389D8.svg?style=flat" ></a>
    <a href="https://github.com/biiiiiigmonster/php8-aop/releases" ><img src="https://img.shields.io/github/release/biiiiiigmonster/php8-aop.svg?color=4099DE" /></a> 
    <a href="https://packagist.org/packages/biiiiiigmonster/php8-aop"><img src="https://img.shields.io/packagist/dt/biiiiiigmonster/php8-aop.svg?color=" /></a> 
    <a><img src="https://img.shields.io/badge/php-8.0+-59a9f8.svg?style=flat" /></a> 
</p>

</div>



# Environment

- PHP >= 8
- laravel >= 8


# Installation

```bash
composer require biiiiiigmonster/php8-aop
```

# Introduce
feature
1.支持切入点切入和注解切入；
2.切面支持仅限于class中非静态public&protected方法；
3.执行顺序(洋葱模型):
2.1 前置类方法priority越大越先执行，后置类方法priority越大越后执行；
2.2 priority相同时注解切面要外层与切入点切面；
2.3 切面内方法执行顺序为：Before > Around(kernel) > After > AfterReturning；
dev
AfterThrowing handle
# License
[MIT](./LICENSE)
