<?php

namespace LayerData;

class LayerData 
{
    public static function get_mustache_contents($str, $function)
    {
        $self = $this;

        $regex = '# \{\{([a-zA-Z0-9.]+)\}\} #x';

        return preg_replace_callback($regex, $function, $str);
    }

    public $__layers = [];

    public function length($data = array())
    {
        return count($this->__layers);
    }

    public function add($data = array())
    {
        $this->__layers[] = new Layer($data);
    }

    public function remove_last()
    {
        array_pop($this->__layers);
    }

    public function get($key)
    {
        $first = strstr($key . ".", ".", true);

        $c = "";

        for ($i = count($this->__layers) - 1; $i >= 0; $i--) {
            if (is_object($this->__layers[$i])) {
                if ($this->__layers[$i]->exists($first)) {
                    return $this->__layers[$i]->get($key);
                }
            }
        }

        return null;
    }

    public function str_replace($str)
    {
        $self = $this;

        $str = self::get_mustache_contents($str, function ($m) use ($self) {
            return $self->get($m[1]);
        });

        return $str;
    }

    public function all()
    {
        $all = [];

        foreach ($this->__layers as $k => $layer) {
            $all[] = $layer->data;
        };

        return call_user_func_array("array_merge", $all);
    }

    public function current()
    {
        return end($this->__layers);
    }

    public function latest()
    {
        if (!$this->__layers) {return null;}

        $i = ($l = count($this->__layers) - 1) - 1;

        return array_key_exists($i, $this->__layers) ? $this->__layers[$i] : null;
    }

    public function last($i = 0)
    {
        if (!$this->__layers) {
            $this->__layers[] = new Layer();
        }

        if (!$i) {
            return end($this->__layers);
        }

        if (!count($this->__layers)) {return;}

        $l = count($this->__layers) - 1;

        return $this->__layers[$l - $i];
    }

    public function set($key, $value)
    {
        $this->last()->set($key, $value);

        return $this;
    }
}

class Layer
{
    public $data = [];

    public function __construct($data = array())
    {
        $this->data = $data;
    }

    public function data(array $data = array())
    {
        $this->data = $data;
    }

    public function get($path)
    {
        $keys = explode(".", $path);

        $root = &$this->data;

        try {
            foreach ($keys as $key) {
                if (is_array($root)) {
                    if (!isset($root[$key])) {
                        return null;
                    }

                    $root = &$root[$key];
                } elseif (is_object($root)) {
                    if (!isset($root->{$key})) {
                        return null;
                    }

                    $root = &$root->{$key};
                } else {
                    return null;
                }
            };
        } catch (Exception $e) {
            return "[" . $e->getMessage() . "]";
        };

        return $root;
    }

    public function exists($key)
    {
        return array_key_exists($key, $this->data);
    }

    public function __get($key)
    {
        if (is_array($this->data)) {
            if (isset($this->data[$key])) {
                return $this->data[$key];
            }

        } elseif (is_object($this->data)) {
            if (isset($this->data->{$key})) {
                return $this->data->{$key};
            }

        }
    }

    public function merge($data)
    {
        if (is_object($this->data)) {
            $d = $this->data;

            foreach ($data as $k => $v) {
                $d->{$k} = $v;
            }

        } else if (is_array($this->data)) {
            $this->data = array_merge($this->data, $data);
        }

    }

    public function __set($key, $value)
    {
        $this->data[$key] = $value;
    }

    public function set($key, $value)
    {
        $this->data[$key] = $value;
    }
}
