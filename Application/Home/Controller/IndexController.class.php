<?php

namespace Home\Controller;

use Home\Model\DataSynchronizationModel;
use Home\Model\StructuralSynchronizationModel;
use Think\Controller;

class IndexController extends Controller
{
    public function index()
    {

    }

    //同步表 表结构
    public function StructuralSynchronization()
    {
        var_dump((new StructuralSynchronizationModel())->run());
    }

    //同步数据
    public function dataSynchronization(){
        var_dump((new DataSynchronizationModel())->run());

    }

}