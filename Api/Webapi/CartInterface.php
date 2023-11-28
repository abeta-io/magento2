<?php declare(strict_types=1);

namespace Abeta\PunchOut\Api\Webapi;

interface CartInterface
{
    /**
     * @return mixed
     * @api
     */
    public function export();
}
