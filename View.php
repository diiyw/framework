<?php

namespace thinker;

class View
{
    protected $tags = [];

    protected $path;

    protected $theme = "default";

    protected $cache;

    /**
     * Request
     * @var Request
     */
    public $request;

    /**
     * View constructor.
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->cache = $request->www . "/cache";
        $this->path = $request->root . "/views";
        //注册模板标签
        $this->tags = [
            "@{if (.+?)}@" => function ($match) {
                return "<?php if($match[1]):?>";
            },
            "@{if}@" => function () {
                return "<?php endif;?>";
            },
            "@{{(.+?)}}@" => function ($match) {
                return "<?php echo $match[1]?>";
            },
            "@{:(.+?)}@" => function ($match) {
                return "<?php $match[1]?>";
            },
            "@{elseif (.+?)}@" => function ($match) {
                return "<?php elseif($match[1]):?>";
            },
            "@{else}@" => function () {
                return "<?php else: ?>";
            },
            "@{fetch (.+?)}@" => function ($match) {
                return "<?php foreach($match[1]): ?>";
            },
            "@{fetch}@" => function () {
                return "<?php endforeach;?>";
            },
            "@{import ([a-zA-Z0-9_\\/]*?)}@" => function ($match) {
                $file = $this->path . DIRECTORY_SEPARATOR .
                    $this->theme . DIRECTORY_SEPARATOR .
                    $match[1] . ".phtml";
                if (file_exists($file)) {
                    $cache = strtolower($this->cache . DIRECTORY_SEPARATOR .
                        $this->theme . DIRECTORY_SEPARATOR .
                        $match[1] . ".phtml");
                    return $this->compile($cache, $file);
                }
            },
        ];
    }

    /**
     * 设置模板名称
     * @param $theme
     */
    public function theme($theme)
    {
        $this->theme = $theme;
    }

    /**
     * 编译模板
     * @param $cache
     * @param $file
     * @return null|string|string[]
     */
    public function compile($cache, $file)
    {
        $data = file_get_contents($file);

        //标签解析
        foreach ($this->tags as $pattern => $callback) {
            $data = preg_replace_callback($pattern, $callback, $data);
        }
        $path = dirname($cache);
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }
        file_put_contents($cache, $data);
        return $data;
    }

    /**
     * 渲染模板文件
     * @param       $tpl
     * @param array $vars
     */
    public function display($tpl, array $vars = [])
    {
        $file = $this->path . DIRECTORY_SEPARATOR .
            $this->theme . DIRECTORY_SEPARATOR .
            strtolower($this->request->controller) . DIRECTORY_SEPARATOR .
            $tpl . ".phtml";
        $cache = $this->cache . DIRECTORY_SEPARATOR .
            $this->theme . DIRECTORY_SEPARATOR .
            strtolower($this->request->module) . DIRECTORY_SEPARATOR .
            strtolower($this->request->controller) . DIRECTORY_SEPARATOR .
            $tpl . ".phtml";
        if (file_exists($file)) {
            $this->compile($cache, $file);
            if (is_file($cache)) {
                extract($vars);
                require_once $cache;
            }
        }
    }

    /**
     * 添加自定义标签
     * @param string $tag
     * @param callable $callback
     */
    public function register($tag, callable $callback)
    {
        $this->tags[$tag] = $callback;
    }
}