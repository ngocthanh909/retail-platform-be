<?php

use Laravel\Sanctum\Sanctum;

return [
    'store_list' => request()->page_size ?? 25,
    'category' => request()->page_size ?? 25,
    'product' => request()->page_size ?? 25,
    'order' => request()->page_size ?? 25,
    'notification' => request()->page_size ?? 25,
    'paginate' => request()->page_size ?? 25,
    'category_product' => request()->page_size ?? 25
];
