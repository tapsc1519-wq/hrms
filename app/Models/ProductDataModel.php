<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

abstract class ProductDataModel extends Model
{
    public function getConnectionName()
    {
        return config('database.product_connection', 'opsbridge');
    }
}
