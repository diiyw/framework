# Thinker 框架核心

Thinker 框架核心，使用Composer引入到项目中即可使用
# 快速开发新模块

```bash
cd app/moudules
vendor/bin/thinker create
```
根据提示输入，会自动创建基本的模块库和模型文件

# 数据库命名规范

模块+"_"+字段名，如`user_created`,`user_id`,也可以是`user_finance_id`

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