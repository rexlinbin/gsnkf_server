<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: ChargeRaffle.test.php 117042 2014-06-24 10:34:06Z TiantianZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/test/ChargeRaffle.test.php $
 * @author $Author: TiantianZhang $(zhangtiantian@babeltime.com)
 * @date $Date: 2014-06-24 10:34:06 +0000 (Tue, 24 Jun 2014) $
 * @version $Revision: 117042 $
 * @brief 
 *  
 **/
class ChargeRaffleTest extends PHPUnit_Framework_TestCase
{
    private static $orderId = NULL;
    private static $maxChargeGold = 5000;
    private static $actStartTime = NULL;
    private static $actEndTime = NULL;
    
    public static function setUpBeforeClass()
    {
        if(EnActivity::isOpen(ActivityName::CHARGERAFFLE) == FALSE)
        {
            echo "chargeraffle act is not open.please open act\n";
            throw new FakeException("chargeraffle act is not open");
        }
        self::$actStartTime = ChargeRaffleLogic::getActStartTime();
        self::$actEndTime = ChargeRaffleLogic::getActEndTime();
        self::$orderId = Util::getTime();
    }
    
    
    private function createUser()
    {
        $pid = time();
        $str = strval($pid);
        $uname = substr($str, strlen($str) - UserConf::MAX_USER_NAME_LEN);
        
        $ret = UserLogic::createUser($pid, 1, $uname);
        
        if($ret['ret'] != 'ok')
        {
            echo "create use failed\n";
            exit();
        }
        Logger::trace('create user ret %s.',$ret);
        return $ret['uid'];
    }
    
    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     *
     */
    protected function setUp()
    {
        
    }
    
    /**
     * Tears down the fixture, for example, close a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
        
    }
    
    /**
     * This method is called after the last test of this test class is run.
     *
     * @since Method available since Release 3.4.0
     */
    public static function tearDownAfterClass()
    {
        
    }
    
    /**
     * 测试getInfo信息是否准确   奖励是否准确发到奖励中心
     * 1.玩家没有充值  getInfo是否准确
     * 2.玩家充值了  没有任何操作 
     * 3.玩家前几天充值了   抽奖了也领取首冲奖励了    
     */
    public function test_getinfo()
    {
        echo "...........................................\n";
        $uid = $this->createUser();
        echo "test_getinfo.the test user is $uid \n";
        RPCContext::getInstance()->resetSession();
        EnUser::release();
        CData::$QUERY_CACHE = NULL;
        RPCContext::getInstance()->setSession(UserDef::SESSION_KEY_UID, $uid);
        MyChargeRaffle::release();
        $inst = new ChargeRaffle();
        //1.刚刚创建的用户  没有任何充值
        echo "test_getinfo_testcase_1.just created user.no any charge order.raffle info check \n";
        $raffleInfo = $inst->getInfo();
        for($i=ChargeRaffleDef::MIN_RAFFLE_CLASS;$i<=ChargeRaffleDef::MAX_RAFFLE_CLASS;$i++)
        {
            $raffleNum = self::getRaffleNum($raffleInfo, $i);
            echo "test_getinfo_testcase_1. check rafflenum_$i\n";
            $this->assertTrue(($raffleNum == 0),'test_getinfo_testcase_1 failed');
        }
        echo "test_getinfo_testcase_1. check reward status\n";
        $status = $raffleInfo[ChargeRaffleDef::EXTRAFIELD_REWARD_STATUS];
        $this->assertTrue(( $status== ChargeRaffleDef::REWARDSTATUS_NOREWARD),
                'test_getinfo_testcase_1 check reward status failed.status is '.$status);
        
        $addGold = array();
        //2.今天充值   
        echo "test_getinfo_testcase_2.add gold order for today.check rafflenum and rewardstatus\n";
        $time = Util::getTime();
        $addGoldTmp = $this->addGoldOrderByDay($time, $uid);
        $addGold[ChargeRaffleLogic::getDayBreak($time)] = $addGoldTmp;
        RPCContext::getInstance()->resetSession();
        EnUser::release();
        CData::$QUERY_CACHE = NULL;
        RPCContext::getInstance()->setSession(UserDef::SESSION_KEY_UID, $uid);
        MyChargeRaffle::release();
        $arrRaffleNum = ChargeRaffleLogic::getRaffleNumByCharge($addGoldTmp);
        $raffleInfo = $inst->getInfo();
        for($i=ChargeRaffleDef::MIN_RAFFLE_CLASS;$i<=ChargeRaffleDef::MAX_RAFFLE_CLASS;$i++)
        {
            $raffleNum = self::getRaffleNum($raffleInfo, $i);
            echo "test_getinfo_testcase_2. check rafflenum_$i\n";
            $this->assertTrue(($raffleNum == $arrRaffleNum[$i]),'test_getinfo_testcase_2 failed');
        }
        echo "test_getinfo_testcase_2. check reward status\n";
        $status = $raffleInfo[ChargeRaffleDef::EXTRAFIELD_REWARD_STATUS];
        $this->assertTrue(( $status == ChargeRaffleDef::REWARDSTATUS_HASREWARD),
                'test_getinfo_testcase_2 check reward status failed.status is '.$status);
    
        //3.今天之前也充值了 
        echo "test_getinfo_testcase_3.add gold for days before today.then raffle.check rafflenum and reward center\n";
        RPCContext::getInstance()->resetSession();
        EnUser::release();
        CData::$QUERY_CACHE = NULL;
        RPCContext::getInstance()->setSession(UserDef::SESSION_KEY_UID, $uid);
        MyChargeRaffle::release();
        $curTime = self::$actStartTime;
        $arrAllRaffleNum = $arrRaffleNum;
        $rewardNum = 0;
        while(Util::isSameDay($curTime) == FALSE)
        {
            $goldTmp = $this->addGoldOrderByDay($curTime, $uid);
            if($goldTmp > 0)
            {
                $rewardNum++;
            }
            $addGold[ChargeRaffleLogic::getDayBreak($curTime)] = $goldTmp;
            $arrRaffleNum = ChargeRaffleLogic::getRaffleNumByCharge($goldTmp);
            foreach($arrRaffleNum as $index => $num)
            {
                if(!isset($arrAllRaffleNum[$index]))
                {
                    $arrAllRaffleNum[$index] = 0;
                }
                $arrAllRaffleNum[$index] += $num;
            }
            $curTime = $curTime + SECONDS_OF_DAY;
        }
        $dbInfo = ChargeRaffleDao::getRaffleInfo($uid);
        $dbInfo[ChargeRaffleDef::TBLFIELD_LASTRFRTIME] = self::$actStartTime;
        $dbInfo[ChargeRaffleDef::TBLFIELD_REWARDTIME] = 0;
        $startTime = self::$actStartTime;
        $goldCharge = $addGold[ChargeRaffleLogic::getDayBreak($startTime)];
        $raffleNum = ChargeRaffleLogic::getRaffleNumByCharge($goldCharge);
        $arrRaffledNum = array();
        $actStartTime = self::$actStartTime;
        echo "set lastrfrtime $actStartTime rewardtime 0\n";
        foreach($raffleNum as $index => $num)
        {
            if($num > 0)
            {
                echo "raffle $index once\n";
                $dbInfo[ChargeRaffleDef::TBLFIELD_VA_INFO]
                    [ChargeRaffleDef::TBLFIELD_RAFFLENUM][$index]++;
                $arrRaffledNum[$index] = 1;
                break;
            }
        }
        $preRewardNum = self::getRewardNum($uid);
        ChargeRaffleDao::saveRaffleInfo($dbInfo);
        EnUser::release();
        CData::$QUERY_CACHE = NULL;
        RPCContext::getInstance()->setSession(UserDef::SESSION_KEY_UID, $uid);
        MyChargeRaffle::release();
        $raffleInfo = $inst->getInfo();
        for($i=ChargeRaffleDef::MIN_RAFFLE_CLASS;$i<=ChargeRaffleDef::MAX_RAFFLE_CLASS;$i++)
        {
            $raffleNum = self::getRaffleNum($raffleInfo, $i);
            echo "test_getinfo_testcase_3. check rafflenum_$i\n";
            if(isset($arrRaffledNum[$i]))
            {
                $rightNum = $arrAllRaffleNum[$i] - $arrRaffledNum[$i];
            }
            else
            {
                $rightNum = $arrAllRaffleNum[$i];
            }
            $this->assertTrue(($raffleNum == $rightNum),
                    "test_getinfo_testcase_3 failed.should be $rightNum but is $raffleNum");
        }
        CData::$QUERY_CACHE = NULL;
        $afterRewardNum = self::getRewardNum($uid);
        echo "test_getinfo_testcase_3.check reward num\n";
        $this->assertTrue(($preRewardNum+$rewardNum==$afterRewardNum),'reward num error');
    }
    
    private static function getRewardNum($uid)
    {
        $data = new CData ();
        $ret = $data->selectCount()->from ( RewardDef::SQL_TABLE )
                    ->where( RewardDef::SQL_UID , '=', $uid)
                    ->where( RewardDef::SQL_SOURCE , '=', RewardSource::CHARGE_RAFFLE )
                    ->query();
        return intval( $ret[0]['count'] );
    }
    
    public static function getRaffleNum($raffleInfo,$index)
    {
        return $raffleInfo[constant('ChargeRaffleDef::EXTRAFIELD_CANRAFFLENUM'.$index)];
    }
    
    public function addGoldOrderByDay($time,$uid)
    {
        $goldNum = rand(0, self::$maxChargeGold);
        $dayBreak = ChargeRaffleLogic::getDayBreak($time);
        $dayLight = $dayBreak+SECONDS_OF_DAY-1;
        $randTime = rand($dayBreak,$dayLight);
        self::$orderId++;
        $bbpayField = array(
                'order_id' => self::$orderId, 
                'uid' => $uid,
                'gold_num'=>$goldNum, 
                'gold_ext'=>0, 
                'status'=>1,
                'mtime'=>$randTime, 
                'level'=>1,
                'qid'=>$uid,
                'order_type'=>OrderType::NORMAL_ORDER,
                );
        $bbpayData = new CData();
        $bbpayData->insertInto(User4BBpayDao::tblBBpay)->values($bbpayField)->query();
        echo "add gold order.time $randTime goldnum $goldNum\n";
        return $goldNum;
    }
    
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */