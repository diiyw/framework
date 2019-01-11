<?php

namespace command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;

class Module extends Command
{
    public function configure()
    {
        $this->setName("module")->setDescription("Create thinker module")
            ->addArgument("name", InputArgument::REQUIRED, "The module name");
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $name = $input->getArgument("name");
        // 创建目录
        foreach (array("/config", "/model", "/filter") as $path) {
            @mkdir($name . $path, 0777, true);
        }
        $module = ucfirst($name);
        $router = <<<ROUTER
<?php
return array(
            
);
ROUTER;
        $controller = <<<CONTROLLER
<?php

use thinker\Controller;

class $module extends Controller
{

    public function index()
    {
        \$this->response->view();
    }
}
CONTROLLER;

        $db = <<<DB
<?php
return array(
    "default" => [
        "dsn" => "mysql:dbname=default;host=127.0.0.1;charset=utf8",
        "user" => "root",
        "password" => "",
        "tables" => [],
    ],
);
DB;
        // 创建文件
        $lower = strtolower($module);
        $files = [
            "/config/router.php" => $router,
            "/" . $module . ".php" => $controller,
            "/config/db.php" => $db,
        ];
        foreach ($files as $path => $content) {
            if (file_exists($lower . $path)) {
                continue;
            }
            file_put_contents($name . $path, $content);
        }
        $output->write("Module `" . $name . "` created.");
    }
}