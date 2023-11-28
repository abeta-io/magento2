<?php declare(strict_types=1);

namespace Abeta\PunchOut\Api\Webapi;

interface CheckoutInterface
{

    /**
     * @return mixed
     * @api
     */
    public function process();
}
