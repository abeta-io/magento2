<?php declare(strict_types=1);

namespace Abeta\PunchOut\Logger\Handler;

use Magento\Framework\Logger\Handler\Base;
use Monolog\Logger;

class Error extends Base
{
    protected $loggerType = Logger::ERROR;
    protected $fileName = '/var/log/abeta-error.log';
}
