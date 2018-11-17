<?php

namespace thinker {

    class Filter
    {
        protected $rules;

        private $data;

        protected $error;

        protected $message;

        /**
         * Filter constructor.
         * @param Request $request
         * @param $rules
         */
        public function __construct(Request $request, $rules)
        {
            $this->data = $request->data();
            $this->rules = $rules;
        }

        /**
         * 返回过滤后的数据
         * @return array|string
         * @throws \Exception
         */
        public function data()
        {
            $this->_filter($this->data);
            if (empty($key)) {
                return $this->data;
            }
            return empty($this->data[$key]) ? "" : $this->data[$key];
        }

        private function _filter($data)
        {
            if (!empty($this->rules)) {
                foreach ($this->rules as $key => $rule) {
                    foreach ($rule as $func => $message) {
                        $func = explode(":", $func);
                        if (!empty($func[0])) {
                            switch ($func[0]) {
                                case "required":
                                    empty($data[$key]) && $this->error($message);
                                    break;
                                case "minLen":
                                    mb_strlen($data[$key]) <= $func[1] && $this->error($message);
                                    break;
                                case "maxLen":
                                    mb_strlen($data[$key]) >= $func[1] && $this->error($message);
                                    break;
                                case "match":
                                    !preg_match('/' . $func[1] . '/', empty($data[$key]) ? "" : $data[$key]) && $this->error($message);
                                    break;
                                case "compare":
                                    $isset = isset($data[$func[1]]);
                                    if (($isset && $data[$key] != $data[$func[1]]) || (!$isset && $data[$key] != $func[1])) {
                                        $this->error($message);
                                    }
                                    break;
                                case "replace":
                                    $data[$key] = empty($data[$key]) ? $func[1] : $data[$key];
                                    break;
                                case "copy":
                                    $data[$func[1]] = empty($data[$key]) ? "" : $data[$key];
                                    break;
                                case "rename":
                                    $data[$func[1]] = empty($data[$key]) ? "" : $data[$key];
                                    unset($data[$key]);
                                    break;
                                case "join":
                                    $data[$key] = (empty($data[$key]) ? "" : $data[$key]) . $data[$func[1]];
                                    break;
                                default:
                                    if (function_exists($func[0])) {
                                        $data[$key] = $func[0](empty($data[$key]) ? "" : $data[$key]);
                                        if ($data[$key] == false) {
                                            $this->error($message);
                                        }
                                    }
                            }
                        }
                    }
                }
            }
            $this->data = $data;
            if (!empty($this->error)) {
                throw new \Exception($this->error[0]);
            }
        }

        private function error($m)
        {
            if (isset($this->message[$m])) {
                $this->error[] = $this->message[$m];
            }
        }
    }
}

