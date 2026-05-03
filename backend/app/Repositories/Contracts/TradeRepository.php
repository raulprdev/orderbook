<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Domain\Entities\Trade;

interface TradeRepository
{
    public function save(Trade $trade): Trade;
}
