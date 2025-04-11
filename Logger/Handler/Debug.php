<?php declare(strict_types=1);

namespace Abeta\PunchOut\Logger\Handler;

use Magento\Framework\Logger\Handler\Base;
use Monolog\Logger;

class Debug extends Base
{
    protected $loggerType = Logger::DEBUG;
    protected $fileName = '/var/log/abeta-debug.log';
}
