<?php

namespace Home\Model;

use Think\Exception;

class StructuralSynchronizationModel extends CommonModel
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

            //同步表
            $this->synchronizationTable($db1Tables, $db2Tables);

            //同步字段
            $this->synchronizationColumn($db1Tables);


            $res['code'] = 0;
        } catch (Exception $exception) {
            $res['code'] = 1;
            $res['message'] = $exception->getMessage();
        }
        return $res;

    }

    /**创建目标缺失的表
     * @param $tableList1
     * @param $tableList2
     * @return mixed
     */
    public function synchronizationTable($tableList1, $tableList2)
    {

        $db2LackTables = array_diff($tableList1, $tableList2);
        //同步目标缺少的表
        $createTableNum = 0;//创建表的数量
        foreach ($tableList1 as $value) {
            if (in_array($value, $tableList2)) {
                echo "CREATE TABLE {$value} 表存在 跳过创建 \r\n";
                continue;
            }
            $createSql = $this->db1->query('SHOW CREATE TABLE ' . $value)[0]['create table'];
            try {
                echo "CREATE TABLE {$value} " . ($this->db2->execute($createSql) !== false ? "创建成功" : "创建失败") . " sql:{$createSql} \r\n";
            } catch (Exception $exception) {
                echo "CREATE TABLE {$value} 创建失败 sql:{$createSql} \r\n";
            }

        }

    }

    /**同步字段
     * @param array $db1Tables 源目标表
     * 不同步排序规则
     * 如果是主键则不同步
     * 如果字段名一样 类型不一样 则不同步该字段类型
     * @return mixed
     */
    public function synchronizationColumn($db1Tables)
    {
        foreach ($db1Tables as $tableName) {
            $db1TablesColumn = array_column($this->db1->query('SHOW FULL FIELDS FROM ' . $tableName), null, 'field');
            $db2TablesColumn = array_column($this->db2->query('SHOW FULL FIELDS FROM ' . $tableName), null, 'field');
            foreach ($db1TablesColumn as $field => $value) {
                $isNull = $value['null'] == 'NO' ? 'NOT NULL' : 'NULL';//是否为null
                $default = $value['default'] ? "default '{$value['default']}'" : '';//默认值
                $comment = $value['comment'] ? "comment'{$value['comment']}'" : '';//注释

                if (!$db2TablesColumn[$field]) {//如果没有这个字段则创建这个字段
                    if ($value['key'] == "PRI") {
                        echo "{$tableName} 字段: `{$field}` {$field} 是主键 跳过同步 \r\n";
                        continue;
                    }

                    try {
                        $sql = "ALTER TABLE `{$tableName}` ADD COLUMN `{$field}` {$value['type']} {$isNull}  {$default}  {$comment}";
                        echo "{$tableName} 字段: `{$field}`" . ($this->db2->execute($sql) !== false ? '创建成功' : '创建失败') . " sql:{$sql} \r\n";
                    } catch (Exception $exception) {
                        echo "{$tableName} 字段: `{$field}` 创建失败 sql:{$sql} \r\n";
                    }

                    /*if ($value['key'] == "PRI") {
                        $sql.=',DROP PRIMARY KEY,';
                        $db2PriListStr = implode(',',array_column($this->db2->query("SHOW INDEX FROM  `{$tableName}` where  Key_name = 'PRIMARY'"),'column_name'));
                        $sql .= "ADD PRIMARY KEY ({$db2PriListStr},{$field});";
                    }*/
                    continue;
                }

                if ($value['type'] == $db2TablesColumn[$field]['type'] && $value['null'] == $db2TablesColumn[$field]['null'] && $value['default'] == $db2TablesColumn[$field]['default'] && $value['comment'] == $db2TablesColumn[$field]['comment']) {
                    echo "{$tableName} 同步字段: `{$field}` {$field} 字段结构相同 跳过同步 \r\n";
                    continue;
                }

                if ($value['type'] != $db2TablesColumn[$field]['type']) {
                    echo "{$tableName} 同步字段: `{$field}` {$field} 类型不同 跳过同步 \r\n";
                    continue;
                }

                try {
                    $sql = "ALTER TABLE `{$tableName}` MODIFY COLUMN `{$field}` {$value['type']} {$isNull} {$default} {$comment}";
                    echo "{$tableName} 同步字段: `{$field}`" . ($this->db2->execute($sql) !== false ? '同步成功' : '同步失败') . " sql:{$sql} \r\n";
                } catch (Exception $exception) {
                    echo "{$tableName} 同步字段: `{$field}` 同步失败 sql:{$sql} \r\n";
                }
            }
        }

    }
}