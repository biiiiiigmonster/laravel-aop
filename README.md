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
1.支持织入(切点)和引入(注解)；
2.切面支持仅限于class中非静态public&protected方法；
3.执行顺序(洋葱模型)：前置类方法order越小越先执行，后置类方法order越大越后执行；
fix
 BeforeAdvice 在方法前切入。
 After Advice 在方法后切入，抛出异常时也会切入。
 AfterReturningAdvice 在方法返回后切入，抛出异常则不会切入。
 AfterThrowingAdvice 在方法抛出异常时切入。
 Around Advice 在方法执行前后切入，可以中断或忽略原有流程的执行。
todo
1.支持代理缓存
2.完善config配置信息
3.readme文档编写
4.release 1.0.0
# License
[MIT](./LICENSE)
