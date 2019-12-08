<?php

namespace Home\Model;

use Think\Exception;

class DataSynchronizationModel extends CommonModel
{

    public $db1 = null;
    public $db2 = null;

    public function run()
    {
        try {
            $db1Tables = $this->db1->query('show tables');
            $db1Tables = array_column($db1Tables, array_keys($db1Tables[0])[0]);
            $db2Tables = $this->db2->query('show tables');
            $db2Tables = array_column($db2Tables, array_keys($db2Tables[0])[0]);


            foreach ($db1Tables as $tableName) {

                $offset = 1;//当前第几次读取
                $limit = 5000;//一次读取多少条
                $taskList = [];//sql任务列表
                $thisTableExecSuccessNum = 0;//当前表执行成功总条数
                $thisTableExecErrorNum = 0;//当前表执行失败总条数

                //获取主键
                $tablePk = $this->db1->query(" SELECT table_name, column_name FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE t WHERE t.table_schema = '" . C('SOURCE')['db_name'] . "' AND table_name = '{$tableName}'; ")[0]['column_name'];

                get:
                if ($tablePk) {
                    $db1Data = array_column($this->getData($this->db1, $tableName, $tablePk, null, $offset, $limit), null, $tablePk);
                    //有主键的情况 查询第二张表不需要传入limit
                    $db2Data = array_column($this->getData($this->db2, $tableName, $tablePk, array_keys($db1Data)), null, $tablePk);
                } else {
                    $db1Data = $this->getData($this->db1, $tableName, null, null, $offset, $limit);
                    //没有主键执行添加
                    //$db2Data = $this->getData($this->db2, $tableName, null, null, $offset, $limit);
                    $db2Data = [];
                }
                $db1DataCount = count($db1Data);//当前数据条数

                foreach ($db1Data as $pkValue => $value) {
                    if (!$tablePk || !$db2Data[$pkValue]) { //如果不存在
                        $valueStr = '';
                        foreach ($value as $colVal) {
                            $valueStr .= ($colVal || is_numeric($colVal) ? "'{$colVal}'" : ($colVal === null ? "null" : "''")) . ',';
                        }

                        $taskList['insert'][] = "insert into {$tableName} (" . implode(',', array_keys($value)) . ") value(" . rtrim($valueStr, ',') . ");";
                        unset($db1Data[$pkValue]);
                        continue;
                    }
                    if ($value != $db2Data[$pkValue]) {//如果不相等
                        $sql = "update {$tableName} set ";
                        foreach ($value as $col => $colVal) {
                            $sql .= "{$col}=" . ($colVal || is_numeric($colVal) ? "'{$colVal}'" : ($colVal === null ? "null" : "''")) . ",";
                        }
                        $sql = rtrim($sql, ',') . " where {$tablePk}='{$pkValue}'";
                        $taskList['update'][] = $sql;
                        unset($db1Data[$pkValue]);
                        unset($db2Data[$pkValue]);
                    }
                }

                if ($db1DataCount == $limit) {//如果数量够 继续读取数据
                    echo "{$tableName} {$offset} * 5000条数据对比完成 \n\r";
                    $execRes = $this->execTaskList($taskList);
                    $taskList = [];//清空sql任务列表
                    echo "{$tableName} {$offset} * 5000条数据更新完成 \n\r";
                    $thisTableExecSuccessNum += $execRes['success'];
                    $thisTableExecErrorNum += $execRes['error'];
                    $offset++;
                    goto get;
                }

                //不够数量的
                echo "{$tableName} {$offset} * {$db1DataCount}条数据对比完成 \n\r";
                $execRes = $this->execTaskList($taskList);
                echo "{$tableName} {$offset} * {$db1DataCount}条数据更新完成 \n\r";
                $thisTableExecSuccessNum += $execRes['success'];
                $thisTableExecErrorNum += $execRes['error'];

                echo "{$tableName} 一共执行成功: {$thisTableExecSuccessNum}条 失败:{$thisTableExecErrorNum} 条 \r\n";

                //db1里面没有的数据
                //$db1DataNull[$tableName] = $db2Data;
            }

            $res['code'] = 0;
        } catch (Exception $exception) {
            $res['code'] = 1;
            $res['message'] = $exception->getMessage();
        }
        return $res;

    }

    public function getData($db, $tableName, $pk = null, $in = [], $offset = 0, $limit = 0)
    {
        $sql = "select * from {$tableName} ";
        if ($pk && $in) {
            $sql .= " where {$pk} in (" . implode(',', $in) . ")";
        }
        if ($offset && $limit) {
            $sql .= " limit " . ($offset - 1) * $limit . "," . $limit;
        }
        return $db->query($sql);
    }

    public function execTaskList($taskList)
    {
        $totalSuccess = 0;
        $totalError = 0;
        foreach ($taskList as $tableSqlList) {
            $success = 0;
            $error = 0;

            $this->db2->startTrans();
            foreach ($tableSqlList as $value) {
                try {
                    $status = $this->db2->execute($value);
                } catch (Exception $exception) {
                    $status = false;
                }
                if ($status) {
                    //echo "执行成功:{$value} \r\n";
                    //$success++;
                    $totalSuccess++;
                } else {
                    //$error++;
                    $totalError++;
                    echo "执行失败:{$value} \r\n";
                }
            }
            $this->db2->commit();
            /*echo "执行成功:{$success} 条 执行失败:{$error} 条 \r\n";*/

        }
        return ['success' => $totalSuccess, 'error' => $totalError];
    }

}