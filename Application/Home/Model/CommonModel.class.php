<?php

namespace Home\Model;


class CommonModel
{

    public $db1 = null;
    public $db2 = null;

    public function __construct()
    {

        $this->db1 = M(null, null, 'SOURCE');
        $this->db2 = M(null, null, 'TARGET');
        //$this->db1->execute("set sql_mode='STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'");
        //$this->db2->execute("set sql_mode='STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'");

        echo "初始内存: " . memory_get_usage() . "B \r\n";

    }

    public function __destruct()
    {
        echo "使用内存: " . memory_get_usage() . "B \r\n";
        echo "峰值内存: " . memory_get_peak_usage() . "B \r\n";
    }
}