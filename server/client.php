<?php

namespace server;

use src\Client;

require(__DIR__.'/../vendor/autoload.php');

$client = new Client();

$client->add('cats', ['name' => 'Felix 1', 'breed' => 'Persian', 'color' => 'Black', 'owner' => 'John Doe'], 1);
$client->add('cats', ['name' => 'Felix 4', 'breed' => 'Persian', 'color' => 'Black', 'owner' => 'John Doe'], 2);
$client->add('cats', ['name' => 'Felix 5', 'breed' => 'Persian', 'color' => 'Black', 'owner' => 'John Doe'], 2);
$client->add('cats', ['name' => 'Felix 6', 'breed' => 'Persian', 'color' => 'Black', 'owner' => 'John Doe'], 6);
$client->add('cats', ['name' => 'Felix 2', 'breed' => 'Persian', 'color' => 'Black', 'owner' => 'John Doe'], 1);
$client->add('cats', ['name' => 'Felix 3', 'breed' => 'Persian', 'color' => 'Black', 'owner' => 'John Doe'], 1);
