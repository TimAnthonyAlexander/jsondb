<?php

namespace server;

require(__DIR__.'/../vendor/autoload.php');

use src\Server;

$server = new Server();

$server->showQueue();
