<?php

require dirname(__DIR__) .'/vendor/autoload.php';

use IrfanTOOR\Debug;
Debug::enable(4);
Debug::lock();

require "example-7.php";
