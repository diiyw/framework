<?php

namespace command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use thinker\Container;

class Library extends Command
{
    public function configure()
    {
        $this->setName("library")->setDescription("Create module library")
            ->addArgument("name", InputArgument::REQUIRED, "library name");
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
        // 模型库创建
        $module = $input->getArgument("name");
        // 加载配置
        $config = $input->getOption("config");
        // conf
        $database = $input->getOption("database");
        if (!file_exists($config)) {
            throw new \InvalidArgumentException('The database config must be specified');
        }
        Container::set("dbConfig", include_once $config);
        $tables = Container::load("dbConfig")[$database]["tables"];
        // 创建模型库
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
            $libraryPath = $module . "/library/" . $path;
            @mkdir($libraryPath, 0777, true);
            $moduleLibrary = <<<LIB
<?php
$namespace
use thinker\Container;

class {$name}Lib
{

   /**
    * 模型
    * @var \\$var_namespace$modelName;
    */
    private \$model;
    
   /**
    * 请求对象
    * @var \\thinker\\Request;
    */
    private \$request;

    public function __construct()
    {
        \$this->model = new $modelName();
        \$this->request = Container::load("request");
    }
}
LIB;
            file_put_contents($libraryPath . "/" . $name . "Lib.php", $moduleLibrary);
        }
    }
}