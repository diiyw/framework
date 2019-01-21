<?php

namespace thinker {

    class View
    {
        protected $labels = [];

        protected $path;

        protected $theme = "default";

        protected $cache;

        public $ext = ".phtml";

        /**
         * View constructor.
         */
        public function __construct()
        {
            $this->cache = App::$publicPath . "/cache";
            $this->path = App::$rootPath . "/views";
            //注册模板标签
            $this->labels = [
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
                "@{import:([a-zA-Z0-9\$_\/]+)}@" => function ($match) {
                    $file = $this->path . DS .
                        $this->theme . DS .
                        $match[1] . $this->ext;
                    if (file_exists($file)) {
                        $cache = strtolower($this->cache . DS .
                            $this->theme . DS .
                            $match[1] . $this->ext);
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
            foreach ($this->labels as $pattern => $callback) {
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
         * @param string $action
         * @param array $vars
         */
        public function display($action = "", array $vars = [])
        {

            if (empty($action)) {
                $action = strtolower(App::$controller);
            }
            $file = $this->theme . DS . strtolower(App::$module) . DS . $action . $this->ext;
            $source = $this->path . DS . $file;
            $cache = $this->cache . DS . $file;
            if (file_exists($source)) {
                $this->compile($cache, $source);
                if (is_file($cache)) {
                    extract($vars);
                    require_once $cache;
                }
            }
        }

        /**
         * 添加自定义标签
         * @param $label
         * @param callable $callback
         */
        public function register($label, callable $callback)
        {
            $this->labels[$label] = $callback;
        }

        /**
         * 方便模板调用库
         * @param $lib
         */
        public function lib($lib)
        {
            $lib = str_replace(".", "\\", $lib);
            return new $lib;
        }
    }
}

