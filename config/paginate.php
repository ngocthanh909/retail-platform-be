<?php

$pageSize = $_GET['page_size'] ?? 25;

return [
    'store_list' => $pageSize ?? 25,
    'category' => $pageSize ?? 25,
    'product' => $pageSize ?? 25,
    'order' => $pageSize ?? 25,
    'notification' => $pageSize ?? 25,
    'paginate' => $pageSize ?? 25,
    'category_product' => $pageSize ?? 25
];
