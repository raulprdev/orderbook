<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Commission rate
    |--------------------------------------------------------------------------
    |
    | Trading fee charged on the buyer side, expressed in basis points.
    | 1 basis point = 1/10_000 = 0.01%. So 150 basis points = 1.5%.
    |
    */

    'commission_basis_points' => (int) env('COMMISSION_BASIS_POINTS', 150),

];
