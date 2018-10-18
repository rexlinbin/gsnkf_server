<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: StatisticSqlCmdCost.php 206804 2015-11-03 07:59:05Z TiantianZhang $
 * 
 **************************************************************************/

 /**
  * 
  * 按照分钟统计data请求的请求数、耗时
  * 数据源文件是dataproxy.log文件
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/test/StatisticSqlCmdCost.php $
 * @author $Author: TiantianZhang $(zhangtiantian@babeltime.com)
 * @date $Date: 2015-11-03 07:59:05 +0000 (Tue, 03 Nov 2015) $
 * @version $Revision: 206804 $
 * @brief 
 * 
 * 
 * 中间文件sqlcost sqlcmd的生成：
 * awk '{if(match($0,/logid:([0-9]+).*request cost:([0-9\.]+)/,arr)){print arr[1],arr[2]}}' dataproxy.log.2015110300 > /tmp/sqlcost
 * awk '{if(match($0,/([0-9]+ [0-9]+\:[0-9]+\:[0-9]+).*logid:([0-9]+).*sql:([A-Z]+).*t_([a-z\_]+)/,arr)){print arr[1],arr[2],arr[3],arr[4]}}' dataproxy.log.2015110300 > /tmp/sqlcmd
 *  
 *  
 **/
class StatisticSqlCmdCost extends BaseScript
{
	/* (non-PHPdoc)
     * @see BaseScript::executeScript()
     */
    private $startTime;
    private $split = 0;
    private $date;
    protected function executeScript ($arrOption)
    {
        // TODO Auto-generated method stub
        $this->date = $arrOption[0];
        $this->split = intval($arrOption[1]);
        $this->startTime = strtotime($this->date)-60;
        $sqlCmdFile = "/home/pirate/".$this->date."/sqlcmd";
        $sqlCostFile = "/home/pirate/".$this->date."/sqlcost";
        $arrSqlCmd = $this->getArrSqlCmdInfo($sqlCmdFile);
        $arrSqlCost = $this->getSqlCost($sqlCostFile);
        $arrSqlCmdCost = $this->getSqlCmdCost($arrSqlCmd, $arrSqlCost);
        $outFile = "/home/pirate/".$this->date."/sqlcost_result_".$this->split;
        $this->outputResult($arrSqlCmdCost, $outFile);
        $this->statistic($arrSqlCmdCost);
    }
    
    private function outputResult($arrResult,$outputFilePath)
    {
        if(is_file($outputFilePath))
        {
            unlink($outputFilePath);
        }
        $file = fopen($outputFilePath, 'a+');
        foreach($arrResult as $result)
        {
            fwrite($file, $result);
        }
        fclose($file);
    }
    
    public function getArrSqlCmdInfo($filePath)
    {
        $arrSqlCmd = array();
        $file = fopen ( $filePath, 'r' );
        if (empty ( $filePath ))
        {
            throw new FakeException( "open file:%s failed\n", $filePath );
        }
        while ( ! feof ( $file ) )
        {
            $line = fgets ( $file );
            $line = trim ( $line );
            if (empty ( $line ))
            {
                continue;
            }
            $arr = explode(" ", $line);
            if(count($arr) != 5)
            {
                Logger::warning('invalid data %s',$line);
                continue;
            }
            $logId = intval($arr[2]);
            $arrSqlCmd[$logId] = $line;
        }
        return $arrSqlCmd;
    }
    
    public function getSqlCost($filePath)
    {
        $arrSqlCost = array();
        $file = fopen ( $filePath, 'r' );
        if (empty ( $filePath ))
        {
            throw new FakeException( "open file:%s failed\n", $filePath );
        }
        while ( ! feof ( $file ) )
        {
            $line = fgets ( $file );
            $line = trim ( $line );
            if (empty ( $line ))
            {
                continue;
            }
            $arr = explode(" ", $line);
            if(count($arr) != 2)
            {
                Logger::warning('invalid data %s',$line);
                continue;
            }
            $logId = intval($arr[0]);
            $cost = $arr[1];
            $arrSqlCost[$logId] = $cost;
        }
        return $arrSqlCost;
    }

    
    public function getSqlCmdCost($arrSqlCmd,$arrSqlCost)
    {
        $split = $this->split;
        $arrSqlCmdCost = array();
        foreach($arrSqlCmd as $logId => $sqlCmd)
        {
            $arr = explode(" ", $sqlCmd);
            $date = $arr[0];
            $time = $arr[1];
            $cmdTime = strtotime($date." ".$time);
            $startTime = $this->startTime+$split*60;
            $endTime = $startTime + 60 - 1;
            if($cmdTime > $endTime || $cmdTime < $startTime)
            {
                continue;
            }
            else
            {
                echo "sqlcmd $sqlCmd is in split $split\n";
            }
            if(!isset($arrSqlCost[$logId]))
            {
                continue;
            }
            $arrSqlCmdCost[$logId] = $sqlCmd." ".$arrSqlCost[$logId]."\n";
        }
        return $arrSqlCmdCost;
    }
    
    public function statistic($arrSqlCmdCost)
    {
        $arrTableOpStatis = array();//在某个表上的不同操作数
        $arrTableStatis = array();//在某个表上的所有操作数
        $arrTableOpCost = array();
        $arrTableCost = array();
        $arrTableCostMax = array();
        $arrTableOpCostMax = array();
        foreach($arrSqlCmdCost as $logId => $sqlCmdCostInfo)
        {
            $arr = explode(" ", $sqlCmdCostInfo);
            $operation = $arr[3];
            $optable = $arr[4];
            $cost = floatval($arr[5]);
            if(!isset($arrTableStatis[$optable]))
            {
                $arrTableStatis[$optable] = 0;
            }
            $arrTableStatis[$optable] += 1;
            if(!isset($arrTableStatis['all']))
            {
                $arrTableStatis['all'] = 0;
            }
            $arrTableStatis['all'] += 1;
            if(!isset($arrTableOpStatis[$optable.$operation]))
            {
                $arrTableOpStatis[$optable.$operation] = 0;
            }
            $arrTableOpStatis[$optable.$operation] += 1;
            if(!isset($arrTableCost[$optable]))
            {
                $arrTableCost[$optable] = 0;
            }
            $arrTableCost[$optable] += $cost;
            if(!isset($arrTableCost['all']))
            {
                $arrTableCost['all'] = 0;
            }
            $arrTableCost['all'] += $cost;
            if(!isset($arrTableOpCost[$optable.$operation]))
            {
                $arrTableOpCost[$optable.$operation] = 0;
            }
            $arrTableOpCost[$optable.$operation] += $cost;
            if(!isset($arrTableCostMax[$optable]))
            {
                $arrTableCostMax[$optable] = 0;
            }
            if($cost > $arrTableCostMax[$optable])
            {
                $arrTableCostMax[$optable] = $cost;
            }
            if(!isset($arrTableCostMax['all']))
            {
                $arrTableCostMax['all'] = 0;
            }
            if($cost > $arrTableCostMax['all'])
            {
                $arrTableCostMax['all'] = $cost;
            }
            if(!isset($arrTableOpCostMax[$optable.$operation]))
            {
                $arrTableOpCostMax[$optable.$operation] = 0;
            }
            if($cost > $arrTableOpCostMax[$optable.$operation])
            {
                $arrTableOpCostMax[$optable.$operation] = $cost;
            }
        }
        $this->outputKeyValue($arrTableStatis, $arrTableCost, $arrTableCostMax, "/home/pirate/".$this->date."/table_all_".$this->split);
        $this->outputKeyValue($arrTableOpStatis, $arrTableOpCost, $arrTableOpCostMax, "/home/pirate/".$this->date."/table_op_".$this->split);
    }
    
    public function outputKeyValue($arrNum,$arrCost,$arrCostMost,$outFile)
    {
        asort($arrCost);
        $arrResult = array();
        foreach($arrCost as $key => $value)
        {
            $num = $arrNum[$key];
            $maxCost = $arrCostMost[$key];
            $arrResult[] = $key." ".$value." ".$num." ".$maxCost."\n";
        }
        $this->outputResult($arrResult, $outFile);
    }
    
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */