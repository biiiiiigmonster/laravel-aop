English | [中文](./README-CN.md)

<div align="center">

# PHP8 Attribute

<p>
    <a href="https://github.com/biiiiiigmonster/laravel-aop/blob/master/LICENSE"><img src="https://img.shields.io/badge/license-MIT-7389D8.svg?style=flat" ></a>
    <a href="https://github.com/biiiiiigmonster/laravel-aop/releases" ><img src="https://img.shields.io/github/release/biiiiiigmonster/laravel-aop.svg?color=4099DE" /></a> 
    <a href="https://packagist.org/packages/biiiiiigmonster/laravel-aop"><img src="https://img.shields.io/packagist/dt/biiiiiigmonster/laravel-aop.svg?color=" /></a> 
    <a><img src="https://img.shields.io/badge/php-8.0+-59a9f8.svg?style=flat" /></a> 
</p>

</div>



# Environment

- PHP >= 8
- laravel >= 8


# Installation

```bash
composer require biiiiiigmonster/laravel-aop
```

# Introduce
feature
1.支持织入(切点)和引入(注解)；
2.切面支持仅限于class中非静态public&protected方法；
3.配置化代理，启动时无须扫描文件；
4.JoinPoint->process()支持参数覆盖；
顺序
 Around Advice 在方法执行前后切入(前置)，可以中断或忽略原有流程的执行。
 Before Advice 在方法前切入。
 Target Method 方法执行。
 AfterReturning Advice 在方法返回时切入，抛出异常则不会切入。可以修改返回值。
 AfterThrowing Advice 在方法抛出异常时切入。
 After Advice 在方法后切入，抛出异常时也会切入。
 Around Advice 在方法执行前后切入(后置)，可以中断或忽略原有流程的执行。抛出异常时不会执行。
todo
1.支持开启代理缓存    -- 已完成
2.完善config配置信息  -- 已完成
3.readme文档编写
4.release 1.0.0
tofix
1.引用参数还存在bug
2.代理文件中存在路径问题时替换bug
# License
[MIT](./LICENSE)
