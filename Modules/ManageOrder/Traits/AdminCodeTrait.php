<?php

namespace Modules\ManageOrder\Traits;

use Nwidart\Modules\Facades\Module;

trait AdminCodeTrait
{
    public static function runAdminScript()
    {
        self::addActualTotalCode();
    }

    /**
     * Add Code ItemCategory.php
     * @return []
     */
    private static function addActualTotalCode()
    {
        $file = base_path('app/Http/Controllers/OrderController.php');
        $replacement = '/*addActualTotalCode*/ 
        $newOrder->actual_total = $orderTotal;
        $newOrder->actual_payment_mode = $request[\'method\'];
        /*endaddActualTotalCode*/';

        if(strpos(file_get_contents($file),'/*addActualTotalCode*/') === false) {
            self::replaceContentByText($file,  $replacement, '$newOrder->total = $orderTotal;');
        }
    }

}