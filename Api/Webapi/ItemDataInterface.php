<?php declare(strict_types=1);

namespace Abeta\PunchOut\Api\Webapi;

interface ItemDataInterface
{
    /**
     * @return mixed
     * @api
     */
    public function export();
}