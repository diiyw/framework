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
            case "library":
                $this->createNewLibrary($input, $output);
                break;
        }
    }

    public function createNewModel(InputInterface $input, OutputInterface $output)
    {
        $helper = $this->getHelper('question');
        // 模块名称
        $question = new Question('Please enter module name (default:home):', 'home');
        $module = $helper->ask($input, $output, $question);
        $const = $module . "\\" . ucfirst($module) . "Const";
        $constFile = $const . ".php";
        if (!file_exists($constFile)) {
            $output->writeln("<error>Database config file not found</error>");
            return;
        }
        require_once $constFile;
        // 需要重建的模型
        $question = new Question('Please enter model name:');
        $model = $helper->ask($input, $output, $question);
        if (empty($model)) {
            $output->writeln("<error>Model name must specified</error>");
        }
        $question = new Question('Please enter config name (default:default):', 'default');
        $conn = $helper->ask($input, $output, $question);
        $dbConfig = $const::DB_CONFIG;
        $dbConfig[$conn]["tables"] = explode(",", $model);
        $this->createModelFiles($module, $dbConfig);
    }

    /**
     *  重建模型库
     * @param InputInterface $input
     * @param OutputInterface $output
     * @throws \Exception
     */
    public function createNewLibrary(InputInterface $input, OutputInterface $output)
    {
        $helper = $this->getHelper('question');
        // 模块名称
        $question = new Question('Please enter module name (default:home):', 'home');
        $module = $helper->ask($input, $output, $question);
        $const = $module . "\\" . ucfirst($module) . "Const";
        $constFile = $const . ".php";
        if (!file_exists($constFile)) {
            $output->writeln("<error>Database config file not found</error>");
            return;
        }
        require_once $constFile;
        $question = new Question('Please enter model name (default:""):', '');
        $tables = $helper->ask($input, $output, $question);
        if (empty($tables)) {
            $dbConfig = $const::DB_CONFIG;
            $tables = $dbConfig[$conn]["tables"];
        } else {
            $tables = explode(",", $tables);
        }
        $question = new Question('Please enter config name (default:default):', 'default');
        $conn = $helper->ask($input, $output, $question);
        $this->createLibraryFiles($module, join(",", $tables));
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
        $this->createModuleFiles($module);
        // 创建过滤器文件
        $this->createFilterFiles($module);
        $const = $this->buildConstFile($module, $dbName, $dbHost, $dbUser, $dbPwd, $dbTables);
        // 创建模型
        $this->createModelFiles($module, $const::DB_CONFIG);
        // 创建库文件
        $this->createLibraryFiles($module, $dbTables);
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
    public function buildConstFile($module, $db, $host, $user, $pwd, $tables)
    {
        $tables = "\"" . str_replace(",", "\",\"", $tables) . "\"";
        $tables = $tables == "\"\"" ? "" : $tables;
        $fModule = ucfirst($module);
        $const = <<<DB
<?php
namespace $module;

class {$fModule}Const
{
    
    const DB_CONFIG = array(
        "default" => [
            "dsn" => "mysql:dbname=$db;host=$host;charset=utf8",
            "user" => "$user",
            "password" => "$pwd",
            "tables" => [$tables],
        ],
    );
    
    const ROUTERS = [];
    
    const PAGE_LIMIT = 10;
}
DB;
        $constClass = $module . "\\" . $fModule . "Const";
        file_put_contents($constClass . ".php", $const);
        require_once $constClass . ".php";
        return $constClass;
    }

    /**
     * 创建模块基础文件
     * @param $module
     * @param $dbConfig
     */
    public function createModuleFiles($module)
    {
        $name = $module;
        // 创建目录
        @mkdir($name . "/controller", 0777, true);
        $module = ucfirst($name);
        $controller = <<<CONTROLLER
<?php
namespace $name\controller;

use thinker\Controller;

class Index extends Controller
{

    public function view()
    {
        \$this->view->display("$module\index");
    }
}
CONTROLLER;

        // 创建文件
        $lower = strtolower($module);
        $file = "/controller/Index.php";
        if (file_exists($lower . $file)) {
            return;
        }
        file_put_contents($name . $file, $controller);
    }

    /**
     * 新建库文件
     * @param $module
     * @param $tables
     */
    public function createLibraryFiles($module, $tables)
    {
        $tables = explode(",", $tables);
        if (empty($tables)) {
            return;
        }
        // 创建模型库
        foreach ($tables as $table) {
            $modelPath = explode("_", $table);
            $name = ucfirst(array_pop($modelPath));
            $lowerName = strtolower($name);
            $modelName = $name . "Model";
            $modelPath = array_splice($modelPath, 1);
            // namespace
            $ns = $this->getNamespace(strtolower($module), $modelPath);
            $namespace = "namespace " . $ns . ";\n";
            $path = join("/", $modelPath);
            $libraryPath = $module . "/" . $path;
            $module = ucfirst($module);
            @mkdir($libraryPath, 0777, true);
            $moduleLibrary = <<<LIB
<?php
$namespace

class {$name}Lib
{
  
    /**
     * 添加
     * @param \$formData
     * @return bool|int|mixed|string
     */
    public function add$name(\$formData)
    {
        \$model = new {$name}Model();
        return \$model->insert(\$formData);
    }
    
    /**
     * 更新
     * @param \$formData
     * @param \${$lowerName}Id
     * @return bool|\PDOStatement
     */
    public function update$name(\$formData, \${$lowerName}Id)
    {
        \$model = new {$name}Model();
        \$model->where([
            "{$lowerName}_id" => \${$lowerName}Id,
        ]);
        return \$model->update(\$formData);
    }
    
     /**
     * 获取单条
     * @param \${$lowerName}Id
     * @return array|mixed
     */
    public function get$name(\${$lowerName}Id)
    {
        \$model = new {$name}Model();
        \$model->where([
            "{$lowerName}_id" => \${$lowerName}Id,
        ]);
        return \$model->findOne();
    }
    
     /**
     * 获取列表
     * @param \$formData
     * @param string \$columns
     * @return array
     */
    public function get{$name}List(\$formData, \$columns = "*")
    {
        \$model = new {$name}Model();
        \$model->setWhere(\$formData);
        return \$model->findList(\$columns, null, \$formData["page"], {$module}Const::PAGE_LIMIT);
    }
}
LIB;
            $file = $libraryPath . "/" . $name . "Lib.php";
            if (file_exists($file)) {
                rename($file, $file . ".bakup.php");
            }
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
        if (empty($tables)) {
            return;
        }
        // 创建所有模型
        foreach ($tables as $table) {
            $modelPath = explode("_", $table);
            $name = ucfirst(array_pop($modelPath));
            $lowerName = strtolower($name);
            $modelName = $name . "Model";
            $modelPath = array_splice($modelPath, 1);
            // namespace
            $namespace = $this->getNamespace($module, $modelPath) . ";\n";
            $path = join("/", $modelPath);
            $modelPath = $module . "/" . $path;
            // 模型目录创建
            @mkdir($modelPath, 0777, true);
            $result = $model->query("SHOW FULL COLUMNS FROM $table");
            $columns = $result->fetchAll();
            $properties = "";
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
                    $field = $model->camelize($column["Field"]);
                    $properties .= $comment . "    public $" . $field . ";\n";
                    // 模型库
                    $fieldName = $column["Field"];
                    $formField = $model->uncamelize(str_replace($module, "", $fieldName));
                    if (stripos($column["Type"], "int") !== false) {
                        $conditions .= <<<CONDITION
                        
            "{$fieldName}[<>]" => empty(\$formData["b_{$formField}"]) ? null : \$formData["b_{$formField}"],
            "{$fieldName}[=]" => empty(\$formData["e_{$formField}"]) ? null : \$formData["e_{$formField}"],
            "{$fieldName}[>]" => empty(\$formData["mt_{$formField}"]) ? null : \$formData["mt_{$formField}"],
            "{$fieldName}[>=]" => empty(\$formData["me_{$formField}"]) ? null : \$formData["me_{$formField}"],
            "{$fieldName}[<=]" => empty(\$formData["le_{$formField}"]) ? null : \$formData["le_{$formField}"],
            "{$fieldName}[<]" => empty(\$formData["lt_{$formField}"]) ? null : \$formData["lt_{$formField}"],
CONDITION;
                    }
                    if (stripos($column["Type"], "varchar") !== false) {
                        $conditions .= <<<CONDITION
                        
            "{$fieldName}[=]" => empty(\$formData["e_{$formField}"]) ? null : \$formData["e_{$formField}"],
            "{$fieldName}[?=]" => empty(\$formData["lf_{$formField}"]) ? null : \$formData["lf_{$formField}"],
            "{$fieldName}[=?]" => empty(\$formData["rf_{$formField}"]) ? null : \$formData["rf_{$formField}"],
            "{$fieldName}[??]" => empty(\$formData["ff_{$formField}"]) ? null : \$formData["ff_{$formField}"],
CONDITION;
                    }
                    if ($column["Type"] == "datetime") {
                        $conditions .= <<<CONDITION
                        
            "{$fieldName}[=]" => empty(\$formData["e_{$formField}"]) ? null : \$formData["e_{$formField}"],
            "{$fieldName}[<>]" => empty(\$formData["b_{$formField}"]) ? null : \$formData["b_{$formField}"],
CONDITION;
                    }
                }
            }
            $conditions = trim($conditions);
            $modelContent = <<<MODEL
<?php
namespace $namespace
use thinker\Model;

class $modelName extends Model
{
    $properties
    protected \$primaryKey = "$primaryKey";
    
    /**
     * 条件构造
     * @param \$formData
     */
    public function setWhere(\$formData)
    {
        \$this->where([
            $conditions
        ]);
    }
}
MODEL;
            $file = $modelPath . "/" . $modelName . ".php";
            if (file_exists($file)) {
                rename($file, $file . ".bakup.php");
            }
            file_put_contents($file, $modelContent);
        }
    }

    /**
     * 创建默认过滤器
     */
    public function createFilterFiles($module)
    {
        $filter = <<<FILTER
<?php

namespace $module\controller;

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
        file_put_contents($module . "/controller/IndexFilter.php", $filter);
    }

    private function getNamespace($module, $modelPath)
    {
        $namespace = $module;
        $var_namespace = "";
        if (!empty($modelPath)) {
            $var_namespace = join("\\", $modelPath);
            $namespace .= "\\" . $var_namespace;
        }
        return $namespace;
    }
}