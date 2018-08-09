<?php

namespace thinker;

class Router
{
    protected $rules;

    public function rewrite($pathInfo)
    {
        foreach ($this->rules as $pattern => $url) {
            $pattern = str_replace('(num)', '(\d*)', $pattern);
            $pattern = str_replace('(str)', '(\w*)', $pattern);
            $pathInfo = preg_replace('@' . $pattern . "@", $url, $pathInfo);
        }
        return $pathInfo;
    }
}