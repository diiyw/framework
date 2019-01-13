<?php

namespace command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use thinker\App;
use thinker\Model;

class Create extends Command
{
    public function configure()
    {
        $this->setName("create")->setDescription("Create thinker framework module");
        $this->addOption("type", "t", InputArgument::OPTIONAL, "Create thinker model", "all");
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $type = $input->getOption("type");
        switch ($type) {
            case "all":
                $this->create($input, $output);
                break;
            case "model":
                $this->createNewModel($input, $output);
                break;
        }
    }

    public function createNewModel(InputInterface $input, OutputInterface $output)
    {
        $helper = $this->getHelper('question');
        // 模块名称
        $question = new Question('Please enter module name (default:home):', 'home');
        $module = $helper->ask($input, $output, $question);
        $dbConfigFile = $module . "/config/db.php";
        if (!file_exists($dbConfigFile)) {
            $output->writeln("<error>Database config file not found</error>");
            return;
        }
        // 需要重建的模型
        $question = new Question('Please enter model name:');
        $model = $helper->ask($input, $output, $question);
        if (empty($model)) {
            $output->writeln("<error>Model name must specified</error>");
        }
        $dbConfig = App::setConfig("db", include_once $dbConfigFile);
        $dbConfig["default"]["tables"] = [$model];
        $this->createModelFiles($module, $dbConfig);
    }

    // 创建
    public function create(InputInterface $input, OutputInterface $output)
    {
        $helper = $this->getHelper('question');
        // 模块名称
        $question = new Question('Please enter module name (default:home):', 'home');
        $module = $helper->ask($input, $output, $question);
        // 数据库地址
        $question = new Question('Please enter database host (default:localhost):', 'localhost');
        $dbHost = $helper->ask($input, $output, $question);
        // 数据库名称
        $question = new Question('Please enter database (default:default):', 'default');
        $dbName = $helper->ask($input, $output, $question);
        // 数据库用户名
        $question = new Question('Please enter database username (default:root):', 'root');
        $dbUser = $helper->ask($input, $output, $question);
        // 数据库密码
        $question = new Question('Please enter database password (default is empty):', '');
        $dbPwd = $helper->ask($input, $output, $question);
        // 数据库表
        $question = new Question('Please enter database tables (default is empty):', '');
        $dbTables = $helper->ask($input, $output, $question);
        // 创建模块
        $dbConfig = $this->getDbConfig($dbName, $dbHost, $dbUser, $dbPwd, $dbTables);
        $this->createModuleFiles($module, $dbConfig);
        $dbConfigFile = $module . "/config/db.php";
        // 创建模型
        // 加载配置
        $dbConfig = App::setConfig("db", include_once $dbConfigFile);
        $this->createModelFiles($module, $dbConfig);
        // 创建库文件
        $this->createLibraryFiles($module, $dbTables);
        // 创建过滤器文件
        $this->createFilterFiles($module);
    }

    /**
     * 数据库配置
     * @param $db
     * @param $host
     * @param $user
     * @param $pwd
     * @param $tables
     * @return string
     */
    public function getDbConfig($db, $host, $user, $pwd, $tables)
    {
        $tables = "\"" . str_replace(",", "\",\"", $tables) . "\"";
        return <<<DB
<?php
return array(
    "default" => [
        "dsn" => "mysql:dbname=$db;host=$host;charset=utf8",
        "user" => "$user",
        "password" => "$pwd",
        "tables" => [$tables],
    ],
);
DB;
    }

    /**
     * 创建模块基础文件
     * @param $module
     * @param $dbConfig
     */
    public function createModuleFiles($module, $dbConfig)
    {
        $name = $module;
        // 创建目录
        @mkdir($name . "/config", 0777, true);
        $module = ucfirst($name);
        $router = <<<ROUTER
<?php
return array(
            
);
ROUTER;
        $controller = <<<CONTROLLER
<?php
namespace $name;

use thinker\Controller;

class Index extends Controller
{

    public function view()
    {
        \$this->view->display();
    }
}
CONTROLLER;

        // 创建文件
        $lower = strtolower($module);
        $files = [
            "/config/router.php" => $router,
            "/Index.php" => $controller,
            "/config/db.php" => $dbConfig,
        ];
        foreach ($files as $path => $content) {
            if (file_exists($lower . $path)) {
                continue;
            }
            file_put_contents($name . $path, $content);
        }
    }

    /**
     * 新建库文件
     * @param $module
     * @param $tables
     */
    public function createLibraryFiles($module, $tables)
    {
        $tables = explode(",", $tables);
        // 创建模型库
        foreach ($tables as $table) {
            $modelPath = explode("_", $table);
            $name = ucfirst(array_pop($modelPath));
            $modelName = $name . "Model";
            $modelPath = array_splice($modelPath, 1);
            // namespace
            $namespace = "namespace " . $module . ";\n";
            $var_namespace = "";
            if (!empty($modelPath)) {
                $var_namespace = join("\\", $modelPath);
                $namespace = "\nnamespace " . $var_namespace . ";\n";
                $var_namespace .= "\\";
            }
            $path = join("/", $modelPath);
            $libraryPath = $module . "/" . $path;
            @mkdir($libraryPath, 0777, true);
            $moduleLibrary = <<<LIB
<?php
$namespace
class {$name}Lib
{

   /**
    * 模型
    * @var $modelName;
    */
    private \$model;

    public function __construct()
    {
        \$this->model = new $modelName();
    }
}
LIB;
            file_put_contents($libraryPath . "/" . $name . "Lib.php", $moduleLibrary);
        }
    }

    /**
     * 新建模型文件
     * @param $module
     * @param $dbConfigFile
     * @throws \Exception
     */
    public function createModelFiles($module, $dbConfig)
    {
        // 创建模型
        $model = new \thinker\Model($dbConfig["default"]);
        $tables = $dbConfig["default"]["tables"];
        // 创建所有模型
        foreach ($tables as $table) {
            $modelPath = explode("_", $table);
            $name = ucfirst(array_pop($modelPath));
            $modelName = $name . "Model";
            $modelPath = array_splice($modelPath, 1);
            // namespace
            $namespace = "namespace " . $module . ";\n";
            $var_namespace = "";
            if (!empty($modelPath)) {
                $var_namespace = join("\\", $modelPath);
                $namespace = "\nnamespace " . $var_namespace . ";\n";
                $var_namespace .= "\\";
            }
            $path = join("/", $modelPath);
            $modelPath = $module . "/" . $path;
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
                \"{$fieldName}[>]\" => \$mt{$formField},
            ]);
        }
        \$mq{$formField} = \$formData[\"mq_$formField\"] ?? \"\";
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
        \$lq{$formField} = \$formData[\"lq_$formField\"] ?? \"\";
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
     * 条件构造
     * @param \$formData
     */
    private function filterParams(\$formData)
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

    /**
     * 创建默认过滤器
     */
    public function createFilterFiles($module)
    {
        $filter = <<<FILTER
<?php

namespace $module;

use thinker\Filter;

class IndexFilter extends Filter
{
    public function get()
    {

    }

    public function post()
    {
        
    }

    public function put()
    {

    }

    public function delete()
    {

    }
}
FILTER;
        file_put_contents($module . "/IndexFilter.php", $filter);
    }
}