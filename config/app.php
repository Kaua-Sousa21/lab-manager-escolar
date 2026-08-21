<?php

declare(strict_types=1);

define('APP_NAME', getenv('APP_NAME') ?: 'LabManager Escolar');
define('APP_TIMEZONE', getenv('APP_TIMEZONE') ?: 'America/Fortaleza');
define('SESSION_IDLE_TIMEOUT', 7200);

date_default_timezone_set(APP_TIMEZONE);
