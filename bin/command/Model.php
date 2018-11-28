<?php

namespace command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use thinker\Container;

class Model extends Command
{
    public function configure()
    {
        $this->setName("model")->setDescription("Create module models")
            ->addArgument("name", InputArgument::REQUIRED, "Model name");
        $this->addOption(
            "config",
            "c",
            InputOption::VALUE_REQUIRED,
            "Database configure"
        );
        $this->addOption(
            "database",
            "db",
            InputOption::VALUE_REQUIRED,
            "Database",
            "default"
        );
    }

    // 新建模型
    public function execute(InputInterface $input, OutputInterface $output)
    {
        // 加载配置
        $config = $input->getOption("config");
        if (!file_exists($config)) {
            throw new \InvalidArgumentException('The database config must be specified');
        }
        Container::set("dbConfig", include_once $config);
        $module = $input->getArgument("name");
        // 创建模型
        $model = new \thinker\Model("default");
        $tables = Container::load("dbConfig")["default"]["tables"];
        // 创建所有模型
        foreach ($tables as $table) {
            $modelPath = explode("_", $table);
            $name = ucfirst(array_pop($modelPath));
            $modelName = $name . "Model";
            $modelPath = array_splice($modelPath, 1);
            // namespace
            $namespace = "";
            $var_namespace = "";
            if (!empty($modelPath)) {
                $var_namespace = join("\\", $modelPath);
                $namespace = "\nnamespace " . $var_namespace . ";\n";
                $var_namespace .= "\\";
            }
            $path = join("/", $modelPath);
            $modelPath = $module . "/model/" . $path;
            // 模型目录创建
            @mkdir($modelPath, 0777, true);
            $result = $model->query("SHOW FULL COLUMNS FROM $table");
            $columns = $result->fetchAll();
            $properties = "";
            $modelFields = [];
            $conditions = "";
            if (!empty($columns)) {
                foreach ($columns as $k => $column) {
                    // 模型
                    if ($column["Key"] == "PRI") {
                        $primaryKey = $column["Field"];
                        $column["Comment"] = "主键";
                    }
                    $comment = "
    /**
     * {$column["Comment"]}
     * @var {$column["Type"]}
     * @default {$column["Default"]}
     */\n";
                    $field = $this->convertUnderline(lcfirst($column["Field"]));
                    $properties .= $comment . "    public $" . $field . ";\n";
                    $modelFields[] = "            \"{$column["Field"]}\" => \$this->$field,";
                    // 模型库
                    $fieldName = $column["Field"];
                    $formField = $this->convertUnderline(ltrim(str_replace($module, "", $fieldName), "_"));
                    if (stripos($column["Type"], "int") !== false) {
                        $conditions .= "\${$formField}Start = \$request->get(\"b_{$formField}_start\");
        \${$formField}End = \$request->get(\"b_{$formField}_end\");
        if (\${$formField}End && \${$formField}Start) {
            \$this->between([\"$fieldName\" => [\${$formField}Start, \${$formField}End]]);
        }
        \$$formField = \$request->get(\"p_$formField\");
        if (\${$formField}) {
            \$this->where([
                \"$fieldName\" => [\"=\", \${$formField}]
            ]);
        }
        \${$formField}More = \$request->get(\"m_$formField\");
        if (\${$formField}More) {
            \$this->where([
                \"$fieldName\" => [\">\", \${$formField}More]
            ]);
        }
        \${$formField}Mq = \$request->get(\"mq_$formField\");
        if (\${$formField}Mq) {
            \$this->where([
                \"$fieldName\" => [\">=\", \${$formField}Mq]
            ]);
        }
        \${$formField}Less = \$request->get(\"l_$formField\");
        if (\${$formField}Less) {
            \$this->where([
                \"$fieldName\" => [\"<\", \${$formField}Less]
            ]);
        }
        \${$formField}Lq = \$request->get(\"lq_$formField\");
        if (\${$formField}Lq) {
            \$this->where([
                \"$fieldName\" => [\"<=\", \${$formField}Lq]
            ]);
        }
        ";
                    }
                    if (stripos($column["Type"], "varchar") !== false) {
                        $conditions .= "\$$formField = \$request->get(\"p_$formField\");
        if (\${$formField}) {
            \$this->where([
                \"$fieldName\" => [\"=\", \${$formField}]
            ]);
        }
        \$$formField = \$request->get(\"f_$formField\");
        if (\${$formField}) {
            \$this->where([
                \"$fieldName\" => [\"LIKE\", \"%\" . \${$formField} . \"%\"]
            ]);
        }
        ";
                    }
                    if ($column["Type"] == "datetime") {
                        $conditions .= "\${$formField}Start = \$request->get(\"b_{$formField}_start\");
        \${$formField}End = \$request->get(\"b_{$formField}_end\");
        if (\${$formField}End && \${$formField}Start) {
            \$this->between([\"$fieldName\" => [\${$formField}Start, \${$formField}End]]);
        }
        ";
                    }
                }
            }
            $conditions = trim($conditions);
            $modelFields = join("\n", $modelFields);
            $modelContent = <<<MODEL
<?php
$namespace
use thinker\Model;
use thinker\Request;

class $modelName extends Model
{
    $properties
    protected \$_primaryKey = "$primaryKey";
    
    public function toArray()
    {
        return [
$modelFields
        ];
    }
    
    /**
     * 获取多条记录
     * @param Request \$request
     * @return array|mixed
     */
    public function getList(Request \$request)
    {
        \$this->filterParams(\$request);
        \$page = \$request->get("page",1);
        \$this->page(\$page);
        return \$this->select();
    }

    /**
     * 插入一条记录
     * @return int
     */
    public function create()
    {
        return \$this->insert();
    }

    /**
     * 获取单条记录
     * @param Request \$request
     * @return array|mixed
     */
    public function getLast(Request \$request)
    {
        \$this->filterParams(\$request);
        return \$this->first();
    }
    
    /**
     * 条件构造
     * @param Request \$request
     */
    private function filterParams(Request \$request)
    {
        $conditions
    }
}

MODEL;
            file_put_contents($modelPath . "/" . $modelName . ".php", $modelContent);
        }
    }

    // 下划线变量转驼峰命名
    private function convertUnderline($str)
    {
        $str = preg_replace_callback('/([-_]+([a-z]{1}))/i', function ($matches) {
            return strtoupper($matches[2]);
        }, $str);
        return $str;
    }
}