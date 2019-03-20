<?php

require dirname(__DIR__) .'/vendor/autoload.php';

use IrfanTOOR\Debug;

Debug::enable(1);
# Debug::lock();
Debug::enable(2); # now this is effective as Debug::level can be modified

Debug::dump($_SERVER);
