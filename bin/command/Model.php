<?php

namespace command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use thinker\App;
use thinker\Container;

class Model extends Command
{
    public function configure()
    {
        $this->setName("model")->setDescription("Create thinker's module models")
            ->addArgument("name", InputArgument::REQUIRED, "The model name");
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
        App::set("dbConfig", include_once $config);
        $module = $input->getArgument("name");
        // 创建模型
        $model = new \thinker\Model("default");
        $tables = App::load("dbConfig")["default"]["tables"];
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
                    $formField = ucfirst($this->convertUnderline(ltrim(str_replace($module, "", $fieldName), "_")));
                    if (stripos($column["Type"], "int") !== false) {
                        $conditions .= "\$b{$formField} = \$formData[\"b_{$formField}\"] ?? [];
        if (\$b{$formField}) {
            \$this->where([\"{$fieldName}[<>]\" =>\$b{$formField}]);
        }
        \$e$formField = \$formData[\"e_$formField\"] ?? \"\";
        if (\$e{$formField}) {
            \$this->where([
                \"{$fieldName}[=]\" => \$e$formField,
            ]);
        }
        \$mt{$formField} = \$formData[\"mt_$formField\"] ?? \"\";
        if (\$mt{$formField}) {
            \$this->where([
                \"{$fieldName}[>]\" => \${$formField},
            ]);
        }
        \$mq{$formField} = \$formData\[\"mq_$formField\"\] ?? \"\";
        if (\$mq{$formField}) {
            \$this->where([
                \"{$fieldName}[>=]\" => \$mq{$formField},
            ]);
        }
        \$l{$formField} = \$formData[\"l_$formField\"] ?? \"\";
        if (\$l{$formField}) {
            \$this->where([
                \"{$fieldName}[<]\" =>\$l{$formField},
            ]);
        }
        \$lq{$formField}Lq = \$formData[\"lq_$formField\"] ?? \"\";
        if (\$lq{$formField}) {
            \$this->where([
                \"{$fieldName}[<=]\" =>\$lq{$formField}
            ]);
        }
        ";
                    }
                    if (stripos($column["Type"], "varchar") !== false) {
                        $conditions .= "\$e$formField = \$formData[\"e_$formField\"] ?? \"\";
        if (\$e{$formField}) {
            \$this->where([
                \"{$fieldName}[=]\" => \$e{$formField},
            ]);
        }
        \$lf$formField = \$formData[\"lf_$formField\"] ?? \"\";
        if (\$lf{$formField}) {
            \$this->where([
                \"{$fieldName}[~]\" => \"%\" . \$lf{$formField},
            ]);
        }
        \$rf$formField = \$formData[\"rf_$formField\"] ?? \"\";
        if (\$rf{$formField}) {
            \$this->where([
                \"{$fieldName}[~]\" => \$rf{$formField}.\"%\",
            ]);
        }
        \$ff$formField = \$formData[\"ff_$formField\"] ?? \"\";
        if (\$rf{$formField}) {
            \$this->where([
                \"{$fieldName}[~]\" => \"%\".\$ff{$formField}.\"%\",
            ]);
        }
        ";
                    }
                    if ($column["Type"] == "datetime") {
                        $conditions .= "\$b{$formField} = \$formData[\"b_{$formField}\"] ?? \"\";
        if (\$b{$formField}) {
            \$this->where(
                [\"{$fieldName}[<>]\" => \$b{$formField}
            ]);
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
     * @param aray \$fromData
     * @return array|mixed
     */
    public function getList(\$fromData)
    {
        
    }

    /**
     * 插入一条记录
     * @param aray \$fromData
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
    public function getLast(\$fromData)
    {
        \$this->filterParams(\$request);
        return \$this->first();
    }
    
    /**
     * 条件构造
     */
    private function filterParams(\$fromData)
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