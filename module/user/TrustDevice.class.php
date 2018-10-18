<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: TrustDevice.class.php 214216 2015-12-07 05:37:05Z wuqilin $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/user/TrustDevice.class.php $
 * @author $Author: wuqilin $(zhangtiantian@babeltime.com)
 * @date $Date: 2015-12-07 05:37:05 +0000 (Mon, 07 Dec 2015) $
 * @version $Revision: 214216 $
 * @brief 
 *  
 **/
class TrustDevice
{
    
    const TBLNAME = 't_trust_device';
    const SESSION_KEY = 'trustdevice.info';
    
    const TASK_CONSUME_EXEC = 1;//消耗体力
    const TASK_CONSUME_STAM = 2;//消耗耐力
    const TASK_DAILY_SIGN = 3;//每日签到
    const TASK_LOVE_FRIEND = 4;//赠送耐力
    const TASK_ATK_GOLDTREE = 5;//攻打摇钱树
    const TASK_ATK_EXPTREA = 6;//打经验宝物
    const TASK_RECEIVE_REWARD = 7;//领取奖励
    const TASK_CONSUME_GOLD = 8;//消耗金币
    const TASK_DIVINE = 9;//占星
    const TASK_GUILD_REWARD = 10;//拜关公
    
    private static $TASK_LIST = array(
            self::TASK_CONSUME_EXEC,
            self::TASK_CONSUME_STAM,
            self::TASK_DAILY_SIGN,
            self::TASK_LOVE_FRIEND,
            self::TASK_ATK_GOLDTREE,
            self::TASK_ATK_EXPTREA,
            self::TASK_RECEIVE_REWARD,
            self::TASK_CONSUME_GOLD,
            self::TASK_DIVINE,
            self::TASK_GUILD_REWARD
            );
    //任务完成需要的操作次数
    private static $DONE_TASK_NEED_NUM = array(
            self::TASK_CONSUME_EXEC => 20,
            );    
    //信任设备向平台推送消息的次数上限
    const SENDMSG_TOWEB_LIMITNUM = 5;
    //信任设备个数   不同等级信任设备的个数不同
    private static $TRUST_DEVICENUM = array(
            10 => 1,//小于等于10级只能有一个信任设备
            UserConf::MAX_LEVEL => 50,
    );
    //设备达成信任需要的完成任务个数
    private static $TRUST_DEVICE_NEED_TASKNUM = array(
            10 => 1,
            20 => 2,
            30 => 3,
            40 => 5,
            UserConf::MAX_LEVEL => 6,
            );
    
    const SQL_FIELD_DONETASK_LIST = 'doneList';
    const SQL_FIELD_TASKINFO = 'taskInfo';
    const SQL_FIELD_SENDMSGNUM = 'sendMsgNum';
    
    public static function getInfoFromDb($uid)
    {
        $data = new CData();
        $ret = $data->select(array('uid','va_info'))
                    ->from(self::TBLNAME)
                    ->where(array('uid','=',$uid))
                    ->query();
        if(empty($ret))
        {
            return array();
        }
        return $ret[0];
    }
    
    public static function updateInfoToDb($uid,$info)
    {
        $data = new CData();
        $data->insertOrUpdate(self::TBLNAME)
             ->values($info)
             ->query();
    }
    
    public static function getTaskInfo($uid)
    {
        $taskOnDeviceInfo = RPCContext::getInstance()->getSession(self::SESSION_KEY);
        if(empty($taskOnDeviceInfo))
        {
            $taskOnDeviceInfo = self::getInfoFromDb($uid);
            if(empty($taskOnDeviceInfo))
            {
                $taskOnDeviceInfo = array(
                        'uid' => $uid,
                        'va_info'=>array()
                );
            }
            if(RPCContext::getInstance()->getUid() == $uid)
            {
                RPCContext::getInstance()->setSession(self::SESSION_KEY, $taskOnDeviceInfo);
            }
        }
        return $taskOnDeviceInfo;
    }
    
    public static function fixTaskInfo($taskInfo, $maxDoneNum, $maxInfoNum)
    {

    	$doneList = empty($taskInfo['va_info'][self::SQL_FIELD_DONETASK_LIST]) ? array(): $taskInfo['va_info'][self::SQL_FIELD_DONETASK_LIST];
    	if ( count( $doneList  ) > $maxDoneNum )
    	{
    		$offset = count( $doneList ) - $maxDoneNum;
    		$taskInfo['va_info'][self::SQL_FIELD_DONETASK_LIST] = array_slice( $doneList, $offset);
    		Logger::warning('slice done list. num:%d, maxNum:%d, after:%s', count($doneList), $maxDoneNum, $taskInfo['va_info']);
    	}
    	
    	$infoList = empty($taskInfo['va_info'][self::SQL_FIELD_TASKINFO]) ? array(): $taskInfo['va_info'][self::SQL_FIELD_TASKINFO];
    	if ( count( $infoList  ) > $maxInfoNum )
    	{
    		$offset = count( $infoList ) - $maxInfoNum;
    		$taskInfo['va_info'][self::SQL_FIELD_TASKINFO] = array_slice( $infoList, $offset);
    		Logger::warning('slice info list. num:%d, maxNum:%d, after:%s', count($infoList), $maxInfoNum, $taskInfo['va_info']);
    	}
    	return $taskInfo;
    }
    
    public static function updateTaskInfo($uid, $taskInfo ,$bufferInfo)
    {
        if($taskInfo == $bufferInfo)
        {
            return;
        }
        self::updateInfoToDb($uid, $taskInfo);
        if(RPCContext::getInstance()->getUid() == $uid)
        {
            RPCContext::getInstance()->setSession(self::SESSION_KEY, $taskInfo);
        }
    }
    
    public static function doneTask($uid, $taskId)
    {
        Logger::trace('doneTask %s',func_get_args());
        if ( !defined('PlatformConfig::TRUST_DEVICE_OPEN')
                || PlatformConfig::TRUST_DEVICE_OPEN <= 0 )
        {
            return;
        }
        if(empty($taskId) || empty($uid) )
        {
        	Logger::info('doneTask error params %s',func_get_args());
        	return;
        }
        if($uid != RPCContext::getInstance()->getUid())
        {
            return;
        }
        if(FALSE == in_array($taskId, self::$TASK_LIST))
        {
            Logger::warning('invalid taskid %d',$taskId);
            return;
        }
        $deviceId = RPCContext::getInstance()->getSession('global.bindid');
        if ( empty($deviceId) )
        {
        	Logger::warning('not found bindid. please check');
        	return;
        }
        if( is_numeric($deviceId))
        {
        	$deviceId = '_'.$deviceId;//对于像8341686382738575这样数字幸字符串，amf处理有问题。加个字符绕开这个问题
        	Logger::info('found numeric deviceId:%s', $deviceId);
        }
        $levelSection = self::getCurSectionLevel($uid);
        $taskOnDeviceInfo = self::getTaskInfo($uid);
        if(isset($taskOnDeviceInfo['va_info'][self::SQL_FIELD_SENDMSGNUM][$deviceId][$levelSection])
                && $taskOnDeviceInfo['va_info'][self::SQL_FIELD_SENDMSGNUM][$deviceId][$levelSection] >= self::SENDMSG_TOWEB_LIMITNUM)
        {
            Logger::debug('device is trusted.sendmsg num is %d',$taskOnDeviceInfo['va_info'][self::SQL_FIELD_SENDMSGNUM][$deviceId]);
            return;
        }
        Logger::trace('executeTask trustdevice.updateTaskInfoOnDevice');
        RPCContext::getInstance()->executeTask($uid, 
                'trustdevice.updateTaskInfoOnDevice', 
                array($uid, $taskId, $deviceId), FALSE);
    }
    
    public function updateTaskInfoOnDevice($uid, $taskId, $deviceId)
    {
        if(empty($taskId) || empty($uid) || empty($deviceId))
        {
            Logger::warning('updateTaskInfoOnDevice error params %s',func_get_args());
            return;
        }
        $taskOnDeviceInfo = self::getTaskInfo($uid);
        $bufferInfo = $taskOnDeviceInfo;
        //此设备推送信任信息的次数已经达到上限
        $levelSection = self::getCurSectionLevel($uid);
        if(isset($taskOnDeviceInfo['va_info'][self::SQL_FIELD_SENDMSGNUM][$deviceId][$levelSection])
                && $taskOnDeviceInfo['va_info'][self::SQL_FIELD_SENDMSGNUM][$deviceId][$levelSection] >= self::SENDMSG_TOWEB_LIMITNUM)
        {
            Logger::debug('done task %d on device %d is trust device.sendmsg num %d to limit.',
                    $taskId,$deviceId,$taskOnDeviceInfo['va_info'][self::SQL_FIELD_SENDMSGNUM][$deviceId]);
            return;
        }
        //信任设备已经达到上限
        $trustDeviceNumLimit = self::getTrustDeviceNumLimit($uid);
        if(isset($taskOnDeviceInfo['va_info'][self::SQL_FIELD_SENDMSGNUM])
                && count($taskOnDeviceInfo['va_info'][self::SQL_FIELD_SENDMSGNUM]) >= $trustDeviceNumLimit)
        {
            if( !isset($taskOnDeviceInfo['va_info'][self::SQL_FIELD_SENDMSGNUM][$deviceId]) )
            {
                return;
            }
        }
        
        //怕数据太大，处理一下过大的数据
        $taskOnDeviceInfo = self::fixTaskInfo($taskOnDeviceInfo, 100, 100);
        
        if(!isset($taskOnDeviceInfo['va_info'][self::SQL_FIELD_DONETASK_LIST][$deviceId])
                || !in_array($taskId, $taskOnDeviceInfo['va_info'][self::SQL_FIELD_DONETASK_LIST][$deviceId]))
        {
            if(isset(self::$DONE_TASK_NEED_NUM[$taskId]))
            {
                if(!isset($taskOnDeviceInfo['va_info'][self::SQL_FIELD_TASKINFO][$deviceId][$taskId]))
                {
                    $taskOnDeviceInfo['va_info'][self::SQL_FIELD_TASKINFO][$deviceId][$taskId] = 0;
                }
                $taskOnDeviceInfo['va_info'][self::SQL_FIELD_TASKINFO][$deviceId][$taskId] += 1;
                if($taskOnDeviceInfo['va_info'][self::SQL_FIELD_TASKINFO][$deviceId][$taskId] >= self::$DONE_TASK_NEED_NUM[$taskId])
                {
                    $taskOnDeviceInfo['va_info'][self::SQL_FIELD_DONETASK_LIST][$deviceId][] = $taskId;
                }
            }
            else
            {
                $taskOnDeviceInfo['va_info'][self::SQL_FIELD_DONETASK_LIST][$deviceId][] = $taskId;
            }
        }
        
        //此设备还不能达到信任的条件
        if(!self::isTrustDevice($uid, $deviceId, $taskOnDeviceInfo))
        {
            Logger::debug('done task %d on device %s but can not trust.taskinfo %s',$taskId,$deviceId,$taskOnDeviceInfo);
            self::updateTaskInfo($uid, $taskOnDeviceInfo, $bufferInfo);
            return;
        }        
        $trueDeviceId = $deviceId;
        if( $deviceId[0] == '_' )
        {
        	//将篡改过的deviceId修复，下面要发给平台
        	$trueDeviceId = substr($deviceId, 1);
        	Logger::info('found numeric deviceId:%s, trueDevice:%s', $deviceId, $trueDeviceId);
        }
        //推送消息给web
        $userObj = EnUser::getUserObj($uid);
        try
        {
            $platfrom = ApiManager::getApi ();
            $argv = array (
                    'pid' => $userObj->getPid(),
                    'serverKey' => Util::getServerId (),
                    'bind' => $trueDeviceId,
                    'level' => $levelSection,
            );
            $ret = $platfrom->users ( 'addTrustDevice', $argv );
            if($ret != 'ok')
            {
                throw new SysException('call api addTrustDevice failed. ret:%s', $ret);
            }
        }
        catch (Exception $e)
        {
            Logger::warning('addTrustDevice to platform failed. pid:%s, bindId:%s', $userObj->getPid(), $deviceId);
            return;
        }
        
        if(!isset($taskOnDeviceInfo['va_info'][self::SQL_FIELD_SENDMSGNUM][$deviceId][$levelSection]))
        {
            $taskOnDeviceInfo['va_info'][self::SQL_FIELD_SENDMSGNUM][$deviceId][$levelSection] = 0;
        }
        $taskOnDeviceInfo['va_info'][self::SQL_FIELD_SENDMSGNUM][$deviceId][$levelSection] += 1;
        
        self::updateTaskInfo($uid, $taskOnDeviceInfo, $bufferInfo);
        
        Logger::info('add trust device. pid:%d, uid:%d, device:%s, level:%s, sendNum:%d', 
        			$userObj->getPid(), $userObj->getUid(), $deviceId, $levelSection,
                $taskOnDeviceInfo['va_info'][self::SQL_FIELD_SENDMSGNUM][$deviceId][$levelSection]);
        
    }
    
    public static function isTrustDevice($uid, $deviceId, $taskOnDeviceInfo)
    {
        $userObj = EnUser::getUserObj($uid);
        $userLv = $userObj->getLevel();
        if(!isset($taskOnDeviceInfo['va_info'][self::SQL_FIELD_DONETASK_LIST][$deviceId]))
        {
            return FALSE;
        }
        $doneTaskNum = count(array_intersect($taskOnDeviceInfo['va_info'][self::SQL_FIELD_DONETASK_LIST][$deviceId],
                 self::$TASK_LIST));
        $needTaskNum = PHP_INT_MAX;
        foreach(self::$TRUST_DEVICE_NEED_TASKNUM as $level => $needNum)
        {
            if($userLv <= $level)
            {
                $needTaskNum = $needNum;
                break;
            }
        }
        if($doneTaskNum >= $needTaskNum)
        {
            return TRUE;
        }
        return FALSE;
    }
    
    public static function getTrustDeviceNumLimit($uid)
    {
        $userLv = EnUser::getUserObj($uid)->getLevel();
        $trustDeviceNumLimit = 0;
        foreach(self::$TRUST_DEVICENUM as $level => $num)
        {
            if($userLv <= $level)
            {
                $trustDeviceNumLimit = $num;
                break;
            }
        }
        return $trustDeviceNumLimit;
    }
    
    public static function getCurSectionLevel($uid)
    {
        $userLv = EnUser::getUserObj($uid)->getLevel();
        foreach(self::$TRUST_DEVICE_NEED_TASKNUM as $level => $taskNum)
        {
            if($userLv <= $level)
            {
                return $level;
            }
        }
    }
    
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */