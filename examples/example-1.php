<?php

require dirname(__DIR__) .'/vendor/autoload.php';

use IrfanTOOR\Debug;

Debug::enable(1); # only this is effective as Debug::level modification is locked.
Debug::lock();
Debug::enable(2);

Debug::dump($_SERVER);
