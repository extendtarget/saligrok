<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ApprovePaymentHistory extends Model
{
    public function order()
    {
        return $this->belongsTo('App\Order');
    }

    public function user()
    {
        return $this->belongsTo('App\User');
    }
    public function zone()
    {
        return $this->belongsTo('App\Zone');
    }
}
