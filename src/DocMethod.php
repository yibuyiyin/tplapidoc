<?php

namespace TplApidoc;

class DocMethod {
    protected static $_comment_string = '';

    public function doc() {
        $class = $this->_getClass();
        $obj = new \ReflectionClass($class);

        foreach($obj->getMethods() as $j) {
            self::$_comment_string = $obj->getMethod($j->name)->getdoccomment();

            $pattern = "#(@[a-zA-Z]+\s*[a-zA-Z0-9, ()_].*)#";
            preg_match_all($pattern, self::$_comment_string, $matches, PREG_PATTERN_ORDER);

            $n = array_reduce($matches[0], function($n, $v) {
                $v_r = explode(' ', $v);
                $k = $v_r[0];
                unset($v_r[0]);
                $n[$k][] = $v_r;
                return $n;
            }, ['@path'=>['class'=>$j->class, 'method'=>$j->name]]);

            $replace = [];
            foreach($n as $k => $v) {
                $func = '_'.substr($k, 1);
                $replace[$k] = call_user_func([$this, $func], $v);
            }

            if(empty($n['@name']) && empty($n['@description'])) {
                continue;
            }
            $wiki = str_replace(array_keys($replace), array_values($replace), $this->getTpl());
            $dst = $this->getDst().'/'.$replace['@name'].'.md';
            file_put_contents($dst, $wiki);
        }
    }

    /** @var string $_src */
    private $_src = '';

    public function setSrc($file) {
        if(!file_exists($file)) {
            throw new \Exception('src file not exists.', 4000004);
        }
        $this->_src = $file;
        return $this;
    }

    public function getSrc() {
        return $this->_src;
    }

    /** @var string $_dist */
    private $_dist = '';

    public function setDst($path) {
        if(!$path) {
            throw new \Exception('dist dir not exists.', 4000005);
        }
        if(!file_exists($path)) {
            mkdir($path, 0777, true);
        }
        $this->_dist = $path;
        return $this;
    }

    public function getDst() {
        return $this->_dist;
    }

    private function _getClass() {
        $file = $this->getSrc();
        $string = file_get_contents($file);
        $string = preg_replace('#.*class#ims', 'class', $string);
        $string = preg_replace('#extends\s+\w+\s+#i', '', $string);
        eval($string);
        preg_match('/class\s+(\w+)\s+[{]?/', $string, $matches);
        return $matches[1];
    }
}
