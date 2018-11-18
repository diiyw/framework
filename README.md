# Thinker 框架核心

Thinker 框架核，使用Composer引入到项目中即可使用

# 环境要求
- PHP >= 7.0 
- Composer > 1.4

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