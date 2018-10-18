<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: DesactTest.class.php 203480 2015-10-20 12:17:12Z GuohaoZheng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/world/desact/test/DesactTest.class.php $
 * @author $Author: GuohaoZheng $(zhengguohao@babeltime.com)
 * @date $Date: 2015-10-20 12:17:12 +0000 (Tue, 20 Oct 2015) $
 * @version $Revision: 203480 $
 * @brief 
 *  
 **/
class DesactTest extends PHPUnit_Framework_TestCase
{
    private static $pid;
    private static $uid;
    
    public static function setUpBeforeClass()
    {
        self::$pid = IdGenerator::nextId('uid');
        $utid = 1;
        $uname = strval(self::$pid);
        $ret = UserLogic::createUser(self::$pid, $utid, $uname);
    
        if ($ret['ret'] != 'ok')
        {
            echo "create user failed \n";
            exit();
        }
    
        self::$uid = $ret['uid'];
    }
    
    protected function setUp()
    {
        parent::setUp();
        RPCContext::getInstance()->setSession('global.uid', self::$uid);
    }
    
    protected function tearDown()
    {
        parent::tearDown();
    }
    
    public function test_getInfo()
    {
        $uid = RPCContext::getInstance()->getUid();
        
        $arrGotDesact = DesactLogic::getInfo($uid);
        
        $arrGotConf = empty($arrGotDesact['config']) ? array() : $arrGotDesact['config'];
        $arrGotInfo = empty($arrGotDesact['taskInfo']) ? array() : $arrGotDesact['taskInfo'];
        
        $arrRealConfList = DesactDao::getLastCrossConfig(array('sess','update_time','version','va_config'));
        
        $arrRealConf = empty($arrRealConfList[0]) ? array() : $arrRealConfList[0];
        
        if (empty($arrRealConf) || empty($arrRealConf['va_config']['config']))
        {
            $this->assertEmpty($arrGotConf);
            $this->assertEmpty($arrGotInfo);
            exit();
        }
        else 
        {
            $arrRealInfo = DesactDao::getDesactUser($uid, array('uid','update_time','va_data'));
            
            $tid = 0;
            $curConf = array();
            $startTime = intval( strtotime( date( "Y-m-d", $arrRealConf['update_time'] ) ) );
            
            foreach ($arrRealConf['va_config']['config'] as $key => $value)
            {
                $endTime = $startTime + $value[DesactDef::LAST_DAY] * SECONDS_OF_DAY - 1;
                
                $now = Util::getTime();
                if ($startTime <= $now && $endTime >= $now)
                {
                    $tid = $value[DesactDef::ID];
                    $curConf = $value;
                    break;
                }
                
                $startTime += $value[DesactDef::LAST_DAY] * SECONDS_OF_DAY;
            }
            
            $num = empty($arrRealInfo[$tid]['num']) ? 0 : $arrRealInfo[$tid]['num'];
            $rewarded = empty($arrRealInfo[$tid]['rewarded']) ? array() : array_keys($arrRealInfo[$tid]['rewarded']);
            
            $now = Util::getTime();
            $arrRealInfo = array();
            if ($startTime <= $now && $now <= $endTime )
            {
                $arrRealInfo = array('num'=>$num, 'rewarded'=>$rewarded);
            }
            
            $this->assertEquals($arrRealInfo, $arrGotInfo);
        }
        
    }
    
    public function test_doTask()
    {
        $uid = RPCContext::getInstance()->getUid();
        
        $arrGotDesact = DesactLogic::getInfo($uid);
        
        $arrGotConf = empty($arrGotDesact['config']) ? array() : $arrGotDesact['config'];
        $arrGotInfo = empty($arrGotDesact['taskInfo']) ? array() : $arrGotDesact['taskInfo'];
        
        if (empty($arrGotConf))
        {
            var_dump('conf is empty,return.');
            return ;
        }
        
        $tid = $arrGotConf['id'];
        
        $ret = EnDesact::doDesact($uid + 1, $tid, 1);
        $this->assertEmpty($ret);
        
        $ret = EnDesact::doDesact($uid, 10000, 1);
        $this->assertEmpty($ret);
        
        $ret = EnDesact::doDesact($uid, $tid, 0);
        $this->assertEmpty($ret);
        
        $ret = EnDesact::doDesact($uid, $tid, 1);
        $this->assertEquals('ok', $ret);
        
        $arrNewGot = DesactLogic::getInfo($uid);
        $arrNewInfo = empty($arrNewGot['taskInfo']) ? array() : $arrNewGot['taskInfo'];
        
        $this->assertEquals($arrGotInfo['num'] + 1, $arrNewInfo['num']);
        $this->assertEquals($arrGotInfo['rewarded'], $arrNewInfo['rewarded']);
    }
    
    public function test_gainReward()
    {
        $uid = RPCContext::getInstance()->getUid();
        
        $arrGotDesact = DesactLogic::getInfo($uid);
        
        $arrGotConf = empty($arrGotDesact['config']) ? array() : $arrGotDesact['config'];
        $arrGotInfo = empty($arrGotDesact['taskInfo']) ? array() : $arrGotDesact['taskInfo'];
        
        if (empty($arrGotConf))
        {
            var_dump('conf is empty,return.');
            return ;
        }
        
        $tid = $arrGotConf['id'];
        $num = $arrGotConf['reward'][0]['num'];
        
        EnDesact::doDesact($uid, $tid, $num);
        
        $arrGotDesact = DesactLogic::getInfo($uid);
        
        $arrGotConf = empty($arrGotDesact['config']) ? array() : $arrGotDesact['config'];
        $arrGotInfo = empty($arrGotDesact['taskInfo']) ? array() : $arrGotDesact['taskInfo'];
        
        foreach ($arrGotInfo['rewarded'] as $key => $value)
        {
            if ($value['num'] > $arrGotInfo['num'])
            {
                try {
                    DesactLogic::gainReward($uid, $key);
                    $this->assertTrue(0);
                }
                catch (Exception $e)
                {
                    $this->assertEquals('fake', $e->getMessage());
                }
            }
            
            if (in_array($key, $arrGotInfo['rewarded']))
            {
                try {
                    DesactLogic::gainReward($uid, $key);
                    $this->assertTrue(0);
                }
                catch (Exception $e)
                {
                    $this->assertEquals('fake', $e->getMessage());
                }
            }
        }
        
        foreach ($arrGotInfo['rewarded'] as $key => $value)
        {
            if ($value['num'] <= $arrGotInfo['num'] && !in_array($key, $arrGotInfo['rewarded']))
            {
                DesactLogic::gainReward($uid, $key);
                break;
            }
        }
    }
    
    public function test_random()
    {   
        $conf = EnActivity::getConfByName(ActivityName::DESACT);
        
        $now = Util::getTime();
        $seed = intval( strtotime( "Y-m-d", $now ) );
        
        $crossConf = DesactDao::getLastCrossConfig(array('sess','update_time','version','va_config'));
        
        $lastTid = 0;
        
        if (!empty($crossConf['va_config']['config']))
        {
            $lastConf = end($crossConf['va_config']['config']);
            $lastTid = $lastConf[DesactDef::ID];
        }
        
        $arrGotRandData = DesactLogic::getRandList($conf['data'], $seed, $lastTid);
        
        $randList = DesactLogic::randBySeed($conf['data'], $seed);
        
        $arrRealRandData = self::adjustRankList($conf['data'], $randList, $seed, $lastTid);
        
        $this->assertEquals($arrRealRandData, $arrGotRandData);
        
    }
    
    public static function adjustRankList($arrData, $randList, $startTime, $lastTid)
    {
        $firstTid = $randList[0];
    
        if (!empty($lastTid) && $lastTid == $firstTid)
        {
            $randList[0] = $randList[1];
            $randList[1] = $firstTid;
        }
    
        $arrRandData = array();
        foreach ($randList as $key => $tid)
        {
            $arrRandData[$key] = $arrData[$tid];
        }
         
        if (in_array(DesactDef::COMPETE, $randList))
        {
            $confCompete = $arrData[DesactDef::COMPETE];
            $competeDay = $confCompete[DesactDef::LAST_DAY];
    
            $keyCompete = 0;
    
            if (FALSE == DesactLogic::isConfValid($arrRandData, $startTime, $lastTid, $competeDay))
            {
                $tmpReplaceList = array();
                foreach ($arrRandData as $key => $value)
                {
                    if ( ($value[DesactDef::ID] != $lastTid) && ($value[DesactDef::LAST_DAY] == $confCompete[DesactDef::LAST_DAY]) && $value[DesactDef::ID] != DesactDef::COMPETE )
                    {
                        $tmpReplaceList[$key] = $value;
                    }
    
                    if (DesactDef::COMPETE == $value[DesactDef::ID])
                    {
                        $keyCompete = $key;
                    }
                }
    
                if (DesactDef::COMPETE == $lastTid && in_array(0, array_keys($tmpReplaceList)))
                {
                    unset($tmpReplaceList[0]);
                }
    
                $arrKeyReplace = Util::noBackSample($tmpReplaceList, 1, DesactDef::LAST_DAY);
                $keyReplace = $arrKeyReplace[0];
                $confReplace = $arrRandData[$keyReplace];
    
                
            }
        }
    
        return $arrRandData;
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */