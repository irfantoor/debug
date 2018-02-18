<?php

require dirname(__DIR__) .'/vendor/autoload.php';

use IrfanTOOR\Debug;

Debug::enable(2);
Debug::dump($_SERVER);
