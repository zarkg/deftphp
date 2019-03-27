<?php

namespace nuclear;

require '../kernel/library/nuclear/Loader.php';
Loader::register();

$a = function ($name) {
    return 'hello' . $name;
};

