# Irfan's Debug

## Installation

Install the latest version with

```sh
composer require irfantoor/debug
```

Requires PHP 7.0 or newer.

## Usage

You can enable debugging while coding your application, a short, concise and to
the point, error description and trace is dumped in case of any exception. You
can enable the debugging as:

```php
<?php
require "path/to/vendor/autoload.php";
use IrfanTOOR\Debug;
Debug::enable(2); # 2 is debug level

...
# You can use it to dump data etc.

Debug::dump($_SERVER);
Debug::dump($request);
Debug::dump($response->getHeaders());
```

Try including and enabling it in the starting index.php or bootstrap.php file so
that it can detect any errors in the succeeding files.


Debug has a dependency on IrfanTOOR\\Console for dumping the results on the
console.
