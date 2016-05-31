<?php


/**
 * Class Autoload
 * @package JetFire\Autoload
 */
class Autoload {

    /**
     * @var array
     */
    private $namespaces = [];
    /**
     * @var array
     */
    private $class_collection = [];
    /**
     * @var array
     */
    private $loaded_classes = [];

    /**
     * @var string
     */
    private $base_path = __DIR__;

    /**
     * @return array
     */
    public function getLoadedClass(){
        return $this->loaded_classes;
    }

    /**
     * @return array
     */
    public function getNamespaces(){
        return $this->loaded_classes;
    }

    /**
     * @return array
     */
    public function getClassCollection(){
        return $this->loaded_classes;
    }

    /**
     * @param null $base_path
     */
    public function register($base_path = null){
        if(!is_null($base_path))$this->base_path = rtrim($base_path,'/');
        spl_autoload_register(array($this,'loadClass'));
    }

    /**
     *
     */
    public function unregister()
    {
        spl_autoload_unregister(array($this, 'loadClass'));
    }

    /**
     * @param $class
     */
    private function loadClass($class){
        if(!isset($this->loaded_classes[$class])) {
            if (isset($this->class_collection[$class]) && $this->loadFile($this->class_collection[$class], $class)) return;
            elseif($this->findClass($class))return;
            else{
                $file = $this->base_path.'/'.str_replace('\\', '/', $class).'.php';
                $this->loadFile($file,$class);
                return;
            }
        }
    }

    /**
     * @param $file
     * @param $class
     * @return bool
     */
    private function loadFile($file,$class){
        if(is_file($file)){
            require $file;
            $this->loaded_classes[$class] = $file;
            return true;
        }
        return false;
    }


    /**
     * @param $class
     * @return bool
     */
    private function findClass($class){
        $prefix = $class;
        while (false !== $pos = strrpos($prefix, '\\')) {
            $prefix = substr($class, 0, $pos + 1);
            $relative_class = substr($class, $pos + 1);
            if(isset($this->namespaces[$prefix])){
                foreach($this->namespaces[$prefix] as $dir) {
                    $file = $dir . str_replace('\\', '/', $relative_class) . '.php';
                    if ($this->loadFile($file,$class)) return true;
                }
            }
            $prefix = rtrim($prefix, '\\');
        }
        return false;
    }


    /**
     * @param $prefix
     * @param $base_dirs
     * @param bool $prepend
     */
    public function addNamespace($prefix, $base_dirs, $prepend = false)
    {
        if(is_array($base_dirs))
            foreach($base_dirs as $dir)
                $this->addNamespace($prefix, $dir, $prepend);
        else{
            $prefix = trim($prefix, '\\').'\\';
            $base_dir = rtrim($base_dirs, '/').'/';
            if(!isset($this->namespaces[$prefix]))
                $this->namespaces[$prefix] = [];
            ($prepend)
                ? array_unshift($this->namespaces[$prefix], $base_dir)
                : array_push($this->namespaces[$prefix], $base_dir);
        }
    }

    /**
     * @param array $prefixes
     */
    public function setNamespaces($prefixes){
        if(is_string($prefixes))$prefixes = include $prefixes;
        if(is_array($prefixes))
            foreach($prefixes as $prefix => $base_dir)
                $this->addNamespace($prefix,$base_dir);
    }

    /**
     * @param $class
     * @param $path
     */
    public function addClass($class,$path){
        $this->class_collection[$class] = $path;
    }

    /**
     * @param array $collection
     */
    public function setClassCollection($collection){
        if(is_string($collection))$collection = include $collection;
        if(is_array($collection))
            $this->class_collection = array_merge($this->class_collection,$collection);
    }
}
