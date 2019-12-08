<?php
return array(
    //'配置项'=>'配置值'
    //目标
    'TARGET' => array(
        'db_type' => 'mysqli',
        'db_user' => 'zhangtianyi',
        'db_pwd' => 'zhangtianyi',
        'db_host' => '127.0.0.1',
        'db_port' => '3306',
        'db_name' => 'synchronization',
        'db_charset' => 'utf8',
    ),
    //源
    'SOURCE' => array(
        'db_type' => 'mysqli',
        'db_user' => 'zhangtianyi',
        'db_pwd' => 'zhangtianyi',
        'db_host' => '192.168.1.109',
        'db_port' => '3306',
        'db_name' => 'test_db',
        'db_charset' => 'utf8',
    ),
);