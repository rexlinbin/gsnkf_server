<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: MysqlPressureTest.php 220318 2016-01-08 07:02:38Z TiantianZhang $
 * 
 **************************************************************************/
require_once "/home/pirate/zhtt/VipStatistic/MysqlQueryService.php";
 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/test/MysqlPressureTest.php $
 * @author $Author: TiantianZhang $(zhangtiantian@babeltime.com)
 * @date $Date: 2016-01-08 07:02:38 +0000 (Fri, 08 Jan 2016) $
 * @version $Revision: 220318 $
 * @brief 
 *  
 **/
class MysqlPressureTest extends BaseScript
{
    public static $PROCESS_NUM = 16;
    public static $PROCESS_EXECUTETIME = 60;//每个进程执行时间
    public static $minTeamRoomId = NULL;
    public static $maxTeamRoomid = NULL;
    public static $minServerId = NULL;
    public static $maxServerId = NULL;
    public static $minPid = NULL;
    public static $maxPid = NULL;
    public static $minUid = NULL;
    public static $maxUid = NULL;
    public static $DBHOST = '192.168.1.131';
    public static $DBNAME = 'pirate_countrywar_mix';
    public static $TABLENAME = 't_countrywar_cross_user';
    public static $sqlRatio = array(
                'doAuditionCountryWarSql' => 1,
//                 'doFinalCountryWarSql' => 1,
//                 'doUpdateCountryWarSql' => 2,
//                 'doGetInfoByServerIdPidSql' => 5
                );
    public static $sqlNumOnce = 10;
    public static $keySql = 'doFinalCountryWarSql';
	/* (non-PHPdoc)
     * @see BaseScript::executeScript()
     */
    protected function executeScript ($arrOption)
    {
        // TODO Auto-generated method stub
        self::$minTeamRoomId = self::getMinTeamRoomId();
        self::$maxTeamRoomid = self::getMaxTeamRoomId();
        self::$minServerId = self::getMinServerId();
        self::$maxServerId = self::getMaxServerId();
        self::$minPid = self::getMinPid();
        self::$maxPid = self::getMaxPid();
        self::$minUid = self::getMinUid();
        self::$maxUid = self::getMaxUid();
        MysqlQueryService::close();
        if(isset($arrOption[0]))
        {
            self::$PROCESS_NUM = intval($arrOption[0]);
        }
        $arrArgs = array(
                'minTeamRoomId' => self::$minTeamRoomId,
                'maxTeamRoomId' => self::$maxTeamRoomid,
                'minServerId' => self::$minServerId,
                'maxServerId' => self::$maxServerId,
                'minPid' => self::$minPid,
                'maxPid' => self::$maxPid,
                'maxUid' => self::$maxUid,
                'minUid' => self::$minUid
                );
        $eg = new ExecutionGroup();
        for($i=1;$i<=self::$PROCESS_NUM;$i++)
        {
            $eg->addExecution('MysqlPressureTest::doAllSql', array($i,$arrArgs) );
        }
        $ret = $eg->execute();
        if( !empty($ret) )
        {
            Logger::fatal('there some processfaield:');
            foreach( $ret as $value )
            {
                Logger::fatal('batch:%s', $value);
            }
        }
        self::statisticResult();
        MysqlQueryService::close();
    }
    
    public static function doAllSql($processId,$arrExtraArgs)
    {
        $sqlExecuteNum = 0;
        $arrCostTime = array();
        $startTime = time();
        try
        {
            while(true)
            {
                foreach(self::$sqlRatio as $functionName => $executeNum)
                {
                    call_user_func_array("self::".$functionName,array($executeNum * self::$sqlNumOnce,$arrExtraArgs));
                }
                $sqlExecuteNum++;
                $curTime = time();
                if($curTime - $startTime >= self::$PROCESS_EXECUTETIME)
                {
                    break;
                }
            }
        }
        catch(Exception $e)
        {
            Logger::fatal('doAllSql fail');
        }
        $content = "";
        $costTime = time()-$startTime;
        foreach(self::$sqlRatio as $executeMethod => $num)
        {
//             if($executeMethod != self::$keySql)
//             {
//                 continue;
//             }
            $executeSqlNumPerSecond = intval($sqlExecuteNum*$num*self::$sqlNumOnce/$costTime);
            $content = $content.$executeMethod.",".$executeSqlNumPerSecond."\n";
        }
        self::writeExecuteResultToTmpFile($processId, $content);
    }
    
    public static function doAllSqlOnce($arrArgs)
    {
        foreach(self::$sqlRatio as $functionName => $executeNum)
        {
            call_user_func_array("self::".$functionName,array($executeNum,$arrArgs));
        }
    }
    
    public static function doAuditionCountryWarSql($num,$arrArgs)
    {
        $minTeamRoomId = $arrArgs['minTeamRoomId'];
        $maxTeamRoomId = $arrArgs['maxTeamRoomId'];
        $signTime = strtotime('20151130');
        for($i=0;$i<$num;$i++)
        {
            $teamRoomId = rand($minTeamRoomId,$maxTeamRoomId);
            $countryId = rand(1,4);
            $sql = "select pid,server_id,uuid,uname,htid,fight_force,vip,level,audition_point, ".
                    "final_point from ".self::$TABLENAME." where team_room_id = $teamRoomId".
                    " and country_id = $countryId and audition_point > 0 and sign_time >= $signTime".
                    " order by audition_point DESC,audition_point_time,uuid limit 0,20;";
            $ret = MysqlQueryService::dbGet(self::$DBHOST, self::$DBNAME, $sql);
        }
    }
    
    public static function doFinalCountryWarSql($num,$arrArgs)
    {
        $minTeamRoomId = $arrArgs['minTeamRoomId'];
        $maxTeamRoomId = $arrArgs['maxTeamRoomId'];
        $signTime = strtotime('20151130');
        for($i=0;$i<$num;$i++)
        {
            $teamRoomId = rand($minTeamRoomId,$maxTeamRoomId);
            $countryId = rand(1,4);
            $sql = "select pid,server_id,uuid,uname,htid,fight_force,vip,level,audition_point, ".
                    "final_point from ".self::$TABLENAME." where team_room_id = $teamRoomId".
                    " and final_qualify > 0 and sign_time >= $signTime".
                    " order by final_point DESC,final_point_time,uuid limit 0,80;";
            $ret = MysqlQueryService::dbGet(self::$DBHOST, self::$DBNAME, $sql);
        }
    }
    
    public static function doUpdateCountryWarSql($num,$arrArgs)
    {
        $minTeamRoomId = $arrArgs['minTeamRoomId'];
        $maxTeamRoomId = $arrArgs['maxTeamRoomId'];
        $signTime = strtotime('20151130');
        $offset = rand(0, 100-$num);
        $teamRoomId = rand($minTeamRoomId,$maxTeamRoomId);
        $sql = "select pid,server_id,sign_time from ".self::$TABLENAME.
        " where team_room_id = $teamRoomId".
        " and sign_time >= $signTime".
        " order by final_point DESC,final_point_time,uuid limit $offset,$num;";
        $ret = MysqlQueryService::dbGet(self::$DBHOST, self::$DBNAME, $sql);
        foreach($ret as $userInfo)
        {
            $userInfo = Util::array2Int($userInfo);
            $sql = "update ".self::$TABLENAME." set sign_time = ".
                    rand($userInfo['sign_time'],Util::getTime())." where pid = ".$userInfo['pid'].
                    " and server_id = ".$userInfo['server_id'].";";
            MysqlQueryService::dbQuery(self::$DBHOST, self::$DBNAME, $sql);
        }
    }
    
    public static function doGetInfoByServerIdPidSql($num,$arrArgs)
    {
        $minServerId = $arrArgs['minServerId'];
        $maxServerId = $arrArgs['maxServerId'];
        $minPid = $arrArgs['minPid'];
        $maxPid = $arrArgs['maxPid'];
        for($i=0;$i<$num;$i++)
        {
            $serverId = rand($minServerId, $maxServerId);
            $pid = rand($minServerId, $maxServerId);
            $sql = "select pid,server_id,uuid,sign_time,team_room_id,".
                    "country_id,side,final_qualify,uname,htid,fight_force,vip,level,".
                    "fans_num,cocoin_num,copoint_num,recover_percent,audition_point,".
                    "audition_point_time,final_point,final_point_time,".
                    "audition_inspireatk_num,audition_inspiredfd_num,".
                    "finaltion_inspireatk_num,finaltion_inspiredfd_num,".
                    "update_time from t_countrywar_cross_user where ".
                    "server_id = $serverId and pid = $pid;";
            $ret = MysqlQueryService::dbGet(self::$DBHOST, self::$DBNAME, $sql);
        }
    }
    
    public static function doGetUserSql($num,$arrArgs)
    {
        $minUid = $arrArgs['minUid'];
        $maxUid = $arrArgs['maxUid'];
        for($i=0;$i<$num;$i++)
        {
            $uid = rand($minUid, $maxUid);
            $sql = "select uid,level from t_40020001_user where uid = $uid";
            $ret = MysqlQueryService::dbGet(self::$DBHOST, 'pirate_vipstatistic', $sql);
        }
    }
    
    public static function getMinTeamRoomId()
    {
        $sql = "select min(team_room_id) from ".self::$TABLENAME.";";
        $ret = MysqlQueryService::dbGet(self::$DBHOST, self::$DBNAME, $sql);
        return intval($ret[0]['min(team_room_id)']);
    }
    
    public static function getMaxTeamRoomId()
    {
        $sql = "select max(team_room_id) from ".self::$TABLENAME.";";
        $ret = MysqlQueryService::dbGet(self::$DBHOST, self::$DBNAME, $sql);
        return intval($ret[0]['max(team_room_id)']);
    }
    
    public static function getMinServerId()
    {
        $sql = "select min(server_id) from ".self::$TABLENAME.";";
        $ret = MysqlQueryService::dbGet(self::$DBHOST, self::$DBNAME, $sql);
        return intval($ret[0]['min(server_id)']);
    }
    
    public static function getMaxServerId()
    {
        $sql = "select max(server_id) from ".self::$TABLENAME.";";
        $ret = MysqlQueryService::dbGet(self::$DBHOST, self::$DBNAME, $sql);
        return intval($ret[0]['max(server_id)']);
    }
    
    public static function getMinPid()
    {
        $sql = "select min(pid) from ".self::$TABLENAME.";";
        $ret = MysqlQueryService::dbGet(self::$DBHOST, self::$DBNAME, $sql);
        return intval($ret[0]['min(pid)']);
    }
    
    public static function getMaxPid()
    {
        $sql = "select max(pid) from ".self::$TABLENAME.";";
        $ret = MysqlQueryService::dbGet(self::$DBHOST, self::$DBNAME, $sql);
        return intval($ret[0]['max(pid)']);
    }
    
    public static function getMinUid()
    {
        $sql = "select min(uid) from t_40020001_user;";
        $ret = MysqlQueryService::dbGet(self::$DBHOST, 'pirate_vipstatistic', $sql);
        return intval($ret[0]['min(uid)']);
    }
    
    public static function getMaxUid()
    {
        $sql = "select max(uid) from t_40020001_user;";
        $ret = MysqlQueryService::dbGet(self::$DBHOST, 'pirate_vipstatistic', $sql);
        return intval($ret[0]['max(uid)']);
    }
    
    public static function writeExecuteResultToTmpFile($processId,$content)
    {
        $dirPath = "/tmp/pressuretest";
        if(is_dir($dirPath) == FALSE)
        {
            mkdir($dirPath,0777);
        }
        $filePath = $dirPath."/$processId";
        $file = fopen( $filePath, 'w+' );
        fputs ( $file, $content );
        fclose($file);
    }

    
    public static function statisticResult()
    {
        $dirPath = "/tmp/pressuretest";
        $arrResult = array();
        for($i=1;$i<=self::$PROCESS_NUM;$i++)
        {
            $filePath = $dirPath."/$i";
            $file = fopen ( $filePath, 'r' );
            if (empty ( $file ))
            {
                throw new FakeException( "open file:%s failed\n", $filePath );
            }
            while(TRUE)
            {
                $line = fgets ( $file );
                $line = trim ( $line );
                if (empty ( $line ))
                {
                    break;
                }
                $arr = explode(",", $line);
                if(empty($arr))
                {
                    break;
                }
                $method = $arr[0];
                $executeNum = intval($arr[1]);
                $arrResult[$method][] = $executeNum;
            }
        }
        foreach($arrResult as $method => $executeInfo)
        {
            $count = count($executeInfo);
            $sumNum = 0;
            foreach($executeInfo as $num)
            {
                $sumNum += $num;
            }
            $msg = sprintf("method %s processnum %d executenum %d per second sumnum %d\n",
            $method,$count,intval($sumNum/$count),$sumNum);
            echo $msg;
        }
    }
    
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */