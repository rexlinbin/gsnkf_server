<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: AddGoldTest.php 67067 2013-09-29 07:52:13Z TiantianZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/user/test/AddGoldTest.php $
 * @author $Author: TiantianZhang $(zhangtiantian@babeltime.com)
 * @date $Date: 2013-09-29 07:52:13 +0000 (Sun, 29 Sep 2013) $
 * @version $Revision: 67067 $
 * @brief 
 *  
 **/
class AddGoldTest extends PHPUnit_Framework_TestCase
{
    private static $uid;
    private static $orderId=1;
    /**
     * This method is called before the first test of this test class is run.
     *
     * @since Method available since Release 3.4.0
     */
    public static function setUpBeforeClass()
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
        self::$uid = $ret['uid'];
        self::$orderId = time();
    
    }
    
    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     *
     */
    protected function setUp()
    {
        RPCContext::getInstance ()->setSession ( UserDef::SESSION_KEY_UID, self::$uid );
    
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
    
    public function testPay()
    {
        $this->firstPay();
        $this->payNotFirst();
    }
    
    public function firstPay()
    {
        $payBack = btstore_get()->FIRSTPAY_REWARD['pay_back']->toArray();
        $reward = btstore_get()->FIRSTPAY_REWARD['reward']->toArray();
        $addGold = array_rand($payBack);
        Logger::trace('golds %s .addGold %s.',$payBack,$addGold);
        $backGold = $payBack[$addGold];
        //取出奖励的数组
        $arrField = array(
                RewardDef::SQL_RID,
                RewardDef::SQL_SOURCE,
                RewardDef::SQL_SEND_TIME,
                RewardDef::SQL_VA_REWARD
        );
        $preReward = Util::arrayIndex(RewardDao::getByUid(self::$uid, $arrField ), RewardDef::SQL_RID);
        $userObj = EnUser::getUserObj();
        $preGold = $userObj->getGold();
        $preChargeGold = User4BBpayDao::getSumGoldByUid ( self::$uid );
        $goldExt = rand(0, $addGold);
        $orderId = self::$orderId;
        self::$orderId++;
        $proxy = new ServerProxy();
        $ret = $proxy->addGold(self::$uid, $orderId, $addGold, $goldExt);
        Logger::trace('addGold ret %s.',$ret);
        sleep(3);
        EnUser::release(self::$uid);
        RPCContext::getInstance()->unsetSession(UserDef::SESSION_KEY_USER);
        CData::$QUERY_CACHE = NULL;
        $userObj = EnUser::getUserObj(self::$uid);
        $afterReward = Util::arrayIndex(RewardDao::getByUid(self::$uid, $arrField ), RewardDef::SQL_RID);
        $afterGold = $userObj->getGold();
        $afterChargeGold = User4BBpayDao::getSumGoldByUid ( self::$uid );
        $this->assertTrue(($preGold + $addGold + $backGold + $goldExt == $afterGold),'goldnum is wronly.'.$preGold." ".$addGold." ".$backGold." ".$goldExt." ".$afterGold);
        $this->assertTrue(($preChargeGold + $addGold == $afterChargeGold),'chargegold num is wrong.'.$preChargeGold." ".$addGold." ".$afterChargeGold);
        $this->assertTrue((count($preReward)+1 == count($afterReward)),"prereward + 1 != afterreward");
        $getReward = array();
        foreach($afterReward as $rid => $rewardInfo)
        {
            if(!isset($preReward[$rid]))
            {
                $getReward = $rewardInfo[RewardDef::SQL_VA_REWARD];
                break;
            }
        }
        Logger::trace('reward compare.reward %s.getreward %s.',$reward,$getReward);
        $this->assertTrue(($reward == $getReward), "getreward error please check log");
        $user = new User();
        $orderinfo =  $user->getOrder($orderId, 
                array('order_id','uid','gold_num','gold_ext','order_type','status')); 
        Logger::trace('order info is %s.',$orderinfo);      
    }
    
    public function payNotFirst()
    {
        $user = new User();
        $payBackCfg = btstore_get()->PAY_BACK->toArray();
//         $golds = array_keys($payBackCfg);
        $addGold = array_rand($payBackCfg);
        $backGold = $payBackCfg[$addGold];
        $orderId = self::$orderId;
        self::$orderId++;
        $userObj = EnUser::getUserObj();
        $preGold = $userObj->getGold();
        $preChargeGold = User4BBpayDao::getSumGoldByUid ( self::$uid );
        $goldExt = rand(0, $addGold);
        $user->addGold4BBpay(self::$uid, $orderId, $addGold, $goldExt);
        EnUser::release(self::$uid);
        RPCContext::getInstance()->unsetSession(UserDef::SESSION_KEY_USER);
        sleep(3);
        $userObj = EnUser::getUserObj(self::$uid);
        $afterGold = $userObj->getGold();
        $afterChargeGold = User4BBpayDao::getSumGoldByUid ( self::$uid );
        $this->assertTrue(($preGold + $addGold + $backGold + $goldExt == $afterGold),'goldnum is wronly.'.$preGold." ".$addGold." ".$backGold." ".$goldExt." ".$afterGold);
        $this->assertTrue(($preChargeGold + $addGold == $afterChargeGold),'chargegold num is wrong.'.$preChargeGold." ".$addGold." ".$afterChargeGold);
        
        $orderinfo =  $user->getOrder($orderId,
                array('order_id','uid','gold_num','gold_ext','order_type','status'));
        Logger::trace('order info is %s.',$orderinfo);
    }
    
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */