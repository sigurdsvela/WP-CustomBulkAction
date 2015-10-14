<?php
/*
    Copyright © Sigurd Berg Svela 2015

    Autoloads the classes in this folder.

    Whenever a class in this folder is referenced, this
    function will automagically include it, so files in this
    folder, never needs to be included.
*/

spl_autoload_register(function ($class) {
    $namespace = "CustomBulkAction\\";
    
    //If the class being loaded starts with the namespace
    //this autoloader handles, then.....
    if (strpos($class, $namespace) === 0) {
        //Get the path from the fully classified class name
        $path = str_replace("\\", "/", $class);
        $path = substr($path, strlen("CustomBulkAction"));
        $path = __DIR__ . "/src/" . $path;
        $path = $path . ".php";

        require_once $path;
    }
});
