<?php

return [
    'scan_dir' => [app_path()],
    'ignore' => [dirname(__DIR__)],
    'storage_path' => sys_get_temp_dir(),
    'cache' => false,
];
