<?php
/**
 * Copyright (c) 2016.
 * *
 *  * Created by PhpStorm.
 *  * User: Edo
 *  * Date: 10/3/2016
 *  * Time: 10:44 PM
 *
 */
namespace App\Modules\Resources\Models;

use File;

/**
 * Class BackendTh
 * @package App\Modules\Developers
 */
class BackendTh
{

    /**
     * @var mixed|null
     */
    private $path = null;
    /**
     * @var string
     */
    private $config = 'config.json';
    /**
     * @var array
     */
    private $all = array();

    /**
     * @var string
     */
    private static $default_theme = 'default_123456';
    /**
     * @var
     */
    protected $attributes;
    /**
     * @var
     */
    protected $original;
    /**
     * @var null
     */
    protected static $_instance = null;
    /**
     * @var array
     */
    public $before = [];

    /**
     * BackendTh constructor.
     */
    public function __construct()
    {
        $this->path = \Config::get('paths.backend_themes');
    }

    /**
     * @param null $path
     * @return null
     */
    public static function get($path = null)
    {

        if (self::$_instance === null) {
            self::$_instance = (new static);
        }

        static::$_instance->attributes = json_decode(File::get($path), true); // TODO: Change the autogenerated stub
        static::$_instance->original = json_decode(File::get($path), true); // TODO: Change the autogenerated stub
        return static::$_instance;
    }

    /**
     * @return $this|array
     */
    public function getAll(){
        if (File::exists($this->path)) {
            $directories = $dirs = File::directories($this->path);
            if(! empty($directories)){
                foreach($directories as $directory){
                    if (File::isDirectory($directory)) {//sub type
                        if (File::exists($directory . '/' . $this->config)) {
                            $config = json_decode(File::get($directory . '/' . $this->config),true);
                            if($config){
                                $theme = new $this;
                                $conf = $directory . '/' . $this->config;
                                $theme->attributes = json_decode(File::get($conf), true); // TODO: Change the autogenerated stub
                                $theme->original = json_decode(File::get($conf), true); // TODO: Change the autogenerated stub
                                $theme->path = $directory;
                                $theme->view = $config['folder'].'.'.$config['layout'];
                                $config['path'] = $directory;
                                $this->all[] = $theme;
                            }
                        }
                    }
                }

                $this->before = collect($this->all);
                return $this;
            }
        }
        return array();
    }

    /**
     * @param $key
     * @param $value
     * @return $this
     */
    public function scopeWhere($key, $value)
    {

        $array = [];
        if (!count($this->before)) {
            $all = $this->getAll()->run();

        } else {
            $all = $this->before;
        }
        foreach ($all as $befores) {
            $conf = $befores->toArray();
            if ($conf) {
                if (isset($conf[$key]) && $conf[$key] == $value) $array[] = $befores;
            }
        }
        $this->before = collect($array);
        return $this;
    }

    /**
     * @param $key
     * @param $value
     * @return array
     */
    public function stWhere($key, $value)
    {

        if (!empty($this->before)) {
            return $this->scopeWhere($key, $value);
        }

        $this->getAll();
        $array = array();
        foreach ($this->before as $static) {
            if (isset($static->toArray()[$key]) && $static->toArray()[$key] == $value)
                $array[] = $static;
        }
        $this->before = collect($array);
        return $this;
    }

    /**
     * @param $slug
     * @return mixed
     */
    public static function find($slug)
    {
        $tpl = null;
        $instance = new static;
        $instance->getAll();
        foreach ($instance->before as $static) {
            $arr = $static->toArray();
            if (isset($arr['slug']) && $arr['slug'] == $slug) {
                $tpl = $static;
            }
        }
        $instance->before = $tpl;
        return $instance->run();
    }

    /**
     * @return bool
     */
    public function delete()
    {
        return File::deleteDirectory($this->path);
    }

    /**
     * @return array
     */
    public function run()
    {
        return $this->before;
    }

    /**
     * @return mixed
     */
    public function first()
    {
        if (isset($this->before)) return $this->before[0];
        return collect([]);
    }

    /**
     * @param $name
     * @param $arguments
     */
    public function __call($name, $arguments)
    {
        if ($name === 'where') {
            return call_user_func_array([$this, 'stWhere'], $arguments);
        }
        if ($name === 'lists') {
            return call_user_func_array([$this, 'listing'], $arguments);
        }

        if ($name === 'all') {
            return call_user_func_array([$this, 'getAll'], $arguments);
        }

        if ($name === 'setActive') {
            return call_user_func_array([$this, 'setActive'], $arguments);
        }

        if ($name === 'getLayout') {
            return call_user_func_array([$this, 'getLayoutString'], $arguments);
        }

        if ($name === 'isActive') {
            return call_user_func_array([$this, 'isActive'], $arguments);
        }

    }

    /**
     * @param $key
     * @param null $value
     * @return array
     */
    protected function listing($key, $value = null)
    {
        $lusts = array();
        if (!$this->getAttributes()) {
            $tpl = $this->getAll();
            foreach ($tpl->before as $template) {
                $lusts[$template->toArray()[$key]] = $template->toArray()[$value];
            }
            return $lusts;
        }
        foreach ($this->before as $template) {
            $lusts[$template->toArray()[$key]] = $template->toArray()[$value];
        }
        return $lusts;
    }

    /**
     * @return mixed
     */
    protected function getActive()
    {
       if(\Config::get('activeThem'))return $this->find(\Config::get('activeThem')) ;
        $storage_path = storage_path('app/themes.json');
        if(File::exists(storage_path('app/themes.json'))){
            $storage = json_decode(File::get(storage_path('app/themes.json')),true);
            $theme = new $this;
            $conf = storage_path('app/themes.json');
            $theme->attributes = json_decode(File::get($conf), true); // TODO: Change the autogenerated stub
            $theme->original = json_decode(File::get($conf), true); // TODO: Change the autogenerated stub
            $theme->path = storage_path('app');
            $theme->view = $storage['folder'].'.'.$storage['layout'];
            $this->before = collect($theme);
            return $theme;
        }
    }

    /**
     * @return bool
     */
    protected function isActive(){
        $active = static::getActive();
        if($active->slug == $this->slug){
            return true;
        }else{
            return false;
        }
    }

    /**
     * @return null
     */
    protected function getLayoutString(){
        return $this->view;
    }

    /**
     *
     */
    protected function getCheckTheme(){
        $storage_path = storage_path('app/themes.json');
        if(! File::exists(storage_path('app/themes.json'))){
            self::find(self::$default_theme)->setJsonFile();
        }
    }

    /**
     * @return bool|int
     */
    public function setActive(){
        $storage_path = storage_path('app/themes.json');
        if(File::exists(storage_path('app/themes.json'))){
            if(isset($this->slug)){
              return File::put(storage_path('app/themes.json'),json_encode($this->toArray(),true));
            }
        }

        return false;
    }

    /**
     * @return bool
     */
    public static function setDefault(){
        $default = self::find(self::$default_theme);
        if($default){
            return $default->setActive();
        }

        return false;
    }


    /**
     * @return int
     */
    public function setJsonFile(){
        $storage_path = storage_path('app/themes.json');
        if(! File::exists(storage_path('app/themes.json'))){
            if(isset($this->slug)){
                return File::put(storage_path('app/themes.json'),json_encode($this->toArray(),true));
            }
        }
    }

    /**
     * @param $name
     * @return null
     */
    public function __get($name)
    {
        $result = isset($this->toArray()[$name]) ? $this->toArray()[$name] : false;
        return $result;
    }

    /**
     * @param $name
     * @return bool
     */
    public function __isset($name)
    {
        $result = isset($this->toArray()[$name]) ? true : false;
        return $result;
    }

    /**
     * @return mixed
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * @return mixed
     */
    public function toArray()
    {
        if (isset($this->attributes)) return $this->attributes;
        return false;
    }

    /**
     * @param $attributes
     */
    public function setAttributes($key, $value)
    {
        $attributes = $this->attributes;
        $attributes[$key] = $value;
        $this->attributes = $attributes;
        return $this;
    }

    /**
     * @return null
     */
    public static function instance()
    {
        if (!static::$_instance) {
            static::$_instance = new static;
        }
        return static::$_instance;
    }

    /**
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public static function __callStatic($name, $arguments)
    {
        $instance = new static;
        if ($name === 'where') {
            return call_user_func_array([$instance, 'stWhere'], $arguments);
        }
        if ($name === 'all') {
            return call_user_func_array([$instance, 'getAll'], $arguments);
        }
        if ($name === 'lists') {
            return call_user_func_array([$instance, 'listing'], $arguments);
        }
        if ($name === 'active') {
            return call_user_func_array([$instance, 'getActive'], $arguments);
        }
        if ($name === 'setActive') {
            return call_user_func_array([$instance, 'setActive'], $arguments);
        }
        if ($name === 'getCheckTheme') {
            return call_user_func_array([$instance, 'getCheckTheme'], $arguments);
        }
        if ($name === 'setJsonFile') {
            return call_user_func_array([$instance, 'setJsonFile'], $arguments);
        }
    }

    /**
     * @return $this
     */
    public function save($data)
    {
        $attr = $this->getAttributes();
        $attr['settings']['data'][$data['role']] = $data;
        File::put($this->path. '/' . 'config.json',json_encode($attr,true));
        return $this;
    }

    public function generateCss($css){
        $styleFile = base_path($this->path . '/css/'.$this->css);
        if(File::exists($styleFile)){
            $data = File::get($styleFile);
            $data .= $css;
            File::put($styleFile,$data);
        }

        return $this;
    }
}