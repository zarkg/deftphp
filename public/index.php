<?php

namespace nuclear;

use Ip2Region as Ip;
use Monolog\Logger;

require '../kernel/library/nuclear/Loader.php';
Loader::register();

$ip = new Ip();
var_dump($ip->memorySearch('101.105.35.57'));

var_dump(Logger::getLevels());

Container::hi();
