# Thinker 框架核心

Thinker 框架核心，框架不预置数据库操作、验证码等等，使用Composer引入到项目中即可使用

# 内置模板的使用

```
{if 1==1}
    {:echo 1}
{elseif 2==2}
    {{2}}
{else}
    {fetch $as as $k=>$v}
        {import $v}
    {fetch}
{if}
```