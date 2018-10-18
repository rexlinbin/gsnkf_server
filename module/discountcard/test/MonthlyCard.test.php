<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: MonthlyCard.test.php 116702 2014-06-23 12:24:50Z TiantianZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/discountcard/test/MonthlyCard.test.php $
 * @author $Author: TiantianZhang $(zhangtiantian@babeltime.com)
 * @date $Date: 2014-06-23 12:24:50 +0000 (Mon, 23 Jun 2014) $
 * @version $Revision: 116702 $
 * @brief 
 *  
 **/
class MonthlyCardTest extends PHPUnit_Framework_TestCase
{
    private static $orderId = NULL;
    private static $pid = NULL;
    public static function setUpBeforeClass()
    {
        self::$orderId = Util::getTime();
        self::$pid = Util::getTime();
    }
    
    
    private function createUser()
    {
        self::$pid++;
        $pid = self::$pid;
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
     * 买月卡的测试要点：
     * 第一次买过月卡  1.db信息初始化（初始化信息的准确性）  2.订单插入   3.VIP增长  4.返还金币
     */
    public function test_buycard_0()
    {
        $uid = $this->createUser();
        echo "test_buycard_0.firstly buy card. test user is $uid\n";
        RPCContext::getInstance()->setSession(UserDef::SESSION_KEY_UID, $uid);
        $inst = new MonthlyCard();
        $inst->getCardInfo();
        CData::$QUERY_CACHE = NULL;
        EnUser::release();
        $userObj = EnUser::getUserObj();
        $preDbCardInfo = DiscountCardDao::getCardInfo($uid, DiscountCardDef::MONTHLYCATD_ID);
        echo "test_buycard_0 check if init cardinfo is empty\n";
        //check cardinfo只有在购买月卡时才初始化
        $this->assertTrue((empty($preDbCardInfo)),'card info init is not empty.please check.has requested monthlycard.getCardInfo.');
        $preOrderNum = self::getItemOrderNum($uid);
        $preGoldNum = $userObj->getGold();
        $preVip = $userObj->getVip();
        $preChargeNum = User4BBpayDao::getSumGoldByUid($uid);
        $cardGold = 300;
        $goldBack = $cardGold;
        self::$orderId++;
        $inst->buyCard($uid, self::$orderId, CHARGE_TYPE::CHARGE_BUYMONTYLYCARD,
                 0, 1, $cardGold);
        CData::$QUERY_CACHE = NULL;
        EnUser::release();
        RPCContext::getInstance()->resetSession();
        RPCContext::getInstance()->setSession(UserDef::SESSION_KEY_UID, $uid);
        $userObj = EnUser::getUserObj();
        $afterGold = $userObj->getGold();
        $afterVip = $userObj->getVip();
        $afterCardInfo = DiscountCardDao::getCardInfo($uid, DiscountCardDef::MONTHLYCATD_ID);
        $afterOrderNum = self::getItemOrderNum($uid);
        echo "test_buycard_0 buy card check goldback\n";
        //check 返还金币准确
        $this->assertTrue(($afterGold==$preGoldNum+$goldBack),"buycard.add back gold error.pregoldnum $preGoldNum back $goldBack after $afterGold");
        $sumGold = $preChargeNum+$cardGold;
        $newVip = $preVip;    
        foreach (btstore_get()->VIP as $vipInfo)
        {
            if ($vipInfo['totalRecharge'] > $sumGold)
            {
                break;
            }
            else
            {
                $newVip = $vipInfo['vipLevel'];
            }
        }
        $this->assertTrue(($newVip==$afterVip),'VIP levelup error');
        $this->assertTrue((!empty($afterCardInfo)),'buy card.cardinfo is empty.error');
        echo "test_buycard_0 buy card check buytime\n";
        //check 购买时间
        $buyTime = $afterCardInfo[DiscountCardDef::TBL_SQLFIELD_BUYTIME];
        $this->assertTrue(($buyTime==Util::getTime()),
                "buytime error.please check.now is ".Util::getTime()." buytime is $buyTime");
        echo "test_buycard_0 buy card check duetime\n";
        //check 到期时间
        $dueTime =  $afterCardInfo[DiscountCardDef::TBL_SQLFIELD_DUETIME];
        $cardDay = Util::getDaysBetween($dueTime); 
        $this->assertTrue((MonthlyCardLogic::getDuration(DiscountCardDef::MONTHLYCATD_ID) == 0 - ($cardDay-1)),'duetime error');
        echo "test_buycard_0 buy card check giftstatus\n";
        //check 大礼包状态
        $giftStatus = $afterCardInfo[DiscountCardDef::TBL_SQLFIELD_VAINFO]
                [DiscountCardDef::TBL_SQLFIELD_SUBVA_MONTH][DiscountCardDef::TBL_SQLFIELD_MONTH_GIFTSTATUS];
        if (MonthlyCardLogic::inGiftTime())
        {
            $this->assertTrue(($giftStatus == MONTHCARD_GIFTSTATUS::HASGIFT),'gift status error.');
        }
        else
        {
            $this->assertTrue(($giftStatus == MONTHCARD_GIFTSTATUS::NOGIFT),'gift status error.');
        }
        echo "test_buycard_0 buy card check rewardtime\n";
        //check 领取每日奖励的时间
        $rewardTime = $afterCardInfo[DiscountCardDef::TBL_SQLFIELD_VAINFO]
            [DiscountCardDef::TBL_SQLFIELD_SUBVA_MONTH][DiscountCardDef::TBL_SQLFIELD_MONTH_REWARDTIME];
        $check = $rewardTime < Util::getTime() && (Util::isSameDay($rewardTime) == FALSE);
        $this->assertTrue(($check),"reward time $rewardTime error");
        $this->assertTrue(($preOrderNum + 1 == $afterOrderNum),'not insert one order.');
    }
    
    /**
     * 买月卡的测试要点：
     * 第二次购买月卡  （dueTime < 当前时间）  check dueTime
     * 第三次购买月卡   （dueTime > 当前时间+5） 不能购买
     * 第四次购买月卡    （dueTime <= 当前时间+5） check dueTime
     * 
     */
    public function test_buycard_1()
    {
        echo "...........................\n";
        $uid = $this->createUser();
        echo "test_buycard_1.test user is $uid .test content:buy card not firstly.test duetime.\n";
        RPCContext::getInstance()->resetSession();
        EnUser::release();
        CData::$QUERY_CACHE = NULL;
        RPCContext::getInstance()->setSession(UserDef::SESSION_KEY_UID, $uid);
        $orderId = Util::getTime();
        $inst = new MonthlyCard();
        self::$orderId++;
        $cardGold = 300;
        //第一次买
        echo "test_buycard_1 buy card 1 \n";
        $inst->buyCard($uid, self::$orderId, CHARGE_TYPE::CHARGE_BUYMONTYLYCARD, 0, 1, $cardGold);
        $console = new Console();
        $curTime = Util::getTime();
        $yesterDay = strtotime('yesterday',$curTime);
//      将月卡到期时间设置为昨天
        $console->setMCardDueTime(date('Ymd',$yesterDay));
        RPCContext::getInstance()->resetSession();
        RPCContext::getInstance()->setSession(UserDef::SESSION_KEY_UID, $uid);
        EnUser::release();
        CData::$QUERY_CACHE = NULL;
        self::$orderId++;
        //第二次买
        echo "test_buycard_1 buy card 2 \n";
        MonthlyCardObj::release();
        $inst->buyCard($uid, self::$orderId, CHARGE_TYPE::CHARGE_BUYMONTYLYCARD, 0, 1, $cardGold);
        CData::$QUERY_CACHE = NULL;
        $cardInfo = DiscountCardDao::getCardInfo($uid, DiscountCardDef::MONTHLYCATD_ID);
        $dueTime = $cardInfo[DiscountCardDef::TBL_SQLFIELD_DUETIME];
        $dayBreak = MonthlyCardLogic::getDayBreak(Util::getTime());
        $endTime = $dayBreak + MonthlyCardLogic::getDuration(DiscountCardDef::MONTHLYCATD_ID)
                    * SECONDS_OF_DAY - 1;
        $this->assertTrue(($dueTime == $endTime),'buycard.last duetime is yesterday.duetime is '.$dueTime." error");
        
        //第三次买
        echo "test_buycard_1 buy card 3 \n";
        RPCContext::getInstance()->resetSession();
        RPCContext::getInstance()->setSession(UserDef::SESSION_KEY_UID, $uid);
        EnUser::release();
        CData::$QUERY_CACHE = NULL;
        self::$orderId++;
        try
        {
            MonthlyCardObj::release();
            $inst->buyCard($uid, self::$orderId, CHARGE_TYPE::CHARGE_BUYMONTYLYCARD, 0, 1, $cardGold);
            $this->assertTrue(FALSE,'buycard. lastbuytime is today.duetime is 30days later.can not buy');
        }
        catch(Exception $e)
        {
            echo "limittime is not satified.catch exception of buycard request\n";
        }
        
        //第四次买  
        echo "test_buycard_1 buy card 4 \n";
        $dueTime = Util::getTime() + MonthlyCardLogic::getLimitTime(DiscountCardDef::MONTHLYCATD_ID) - SECONDS_OF_DAY;
        $console->setMCardDueTime(date('Ymd',$dueTime));
        RPCContext::getInstance()->resetSession();
        RPCContext::getInstance()->setSession(UserDef::SESSION_KEY_UID, $uid);
        EnUser::release();
        CData::$QUERY_CACHE = NULL;
        self::$orderId++;
        MonthlyCardObj::release();
        $inst->buyCard($uid, self::$orderId, CHARGE_TYPE::CHARGE_BUYMONTYLYCARD, 0, 1, $cardGold);
        CData::$QUERY_CACHE = NULL;
        $cardInfo = DiscountCardDao::getCardInfo($uid, DiscountCardDef::MONTHLYCATD_ID);
        $dueTime = $cardInfo[DiscountCardDef::TBL_SQLFIELD_DUETIME];
        $duration = MonthlyCardLogic::getDuration(DiscountCardDef::MONTHLYCATD_ID);
        $limitTime = MonthlyCardLogic::getLimitTime(DiscountCardDef::MONTHLYCATD_ID);
        $endTime = MonthlyCardLogic::getDayBreak(Util::getTime() + $limitTime) + $duration * SECONDS_OF_DAY - 1;
        $this->assertTrue(($dueTime==$endTime),'4 buy card.duetime error.');
    }
    
    /**
     * 
     * 1.getRewardTime 是今天之前  小于buyTime
     * 2.getRewardTime 是今天之前   大于buyTime
     * 
     */
    public function test_getcardinfo()
    {
        echo "...........................\n";
        $uid = $this->createUser();
        echo "test_getcardinfo.test user is $uid .test content is reward\n";
        $rewardSource = RewardSource::MONTHLY_CARD;
        EnUser::release();
        RPCContext::getInstance()->resetSession();
        CData::$QUERY_CACHE = NULL;
        RPCContext::getInstance()->setSession(UserDef::SESSION_KEY_UID, $uid);
        echo "test_getcardinfo buy card now\n";
        $inst = new MonthlyCard();
        $orderId = Util::getTime();
        self::$orderId++;
        $cardGold = 300;
        //买月卡
        $inst->buyCard($uid, self::$orderId, CHARGE_TYPE::CHARGE_BUYMONTYLYCARD,
                 0, 1, $cardGold);
        $console = new Console();
        
        //第一次设置购买时间和领奖时间
        $buyTime = strtotime('-5 day',Util::getTime());
        $rewardTime = strtotime('-10 day',Util::getTime());
        echo "test_getcardinfo_1 set buytime(5 days ago) $buyTime rewardtime(10 days ago) $rewardTime\n";
        $console->setMCardBuyTime(self::getDate($buyTime));
        $console->setMCardGetRewardTime(self::getDate($rewardTime));
        $preRewardNum = self::getRewardNum($uid);
        EnUser::release();
        RPCContext::getInstance()->resetSession();
        CData::$QUERY_CACHE = NULL;
        RPCContext::getInstance()->setSession(UserDef::SESSION_KEY_UID, $uid);
        echo "test_getcardinfo_1 getcardinfo to send reward to center.\n";
        MonthlyCardObj::release();
        $inst->getCardInfo();
        $afterRewardNum = self::getRewardNum($uid);
        $this->assertTrue(($preRewardNum+5==$afterRewardNum),'reward num error.should get 5 reward');
        echo "test_getcardinfo_1 test done\n";
        
        
        //第二次设置购买时间和领奖时间
        $buyTime = strtotime('-5 day',Util::getTime());
        $rewardTime = strtotime('-3 day',Util::getTime());
        echo "test_getcardinfo_2 set buytime(5 days ago) $buyTime rewardtime(3 days ago) $rewardTime\n";
        $console->setMCardBuyTime(self::getDate($buyTime));
        $console->setMCardGetRewardTime(self::getDate($rewardTime));
        $preRewardNum = self::getRewardNum($uid);
        EnUser::release();
        RPCContext::getInstance()->resetSession();
        CData::$QUERY_CACHE = NULL;
        RPCContext::getInstance()->setSession(UserDef::SESSION_KEY_UID, $uid);
        echo "test_getcardinfo_2 getcardinfo to send reward to center.\n";
        MonthlyCardObj::release();
        $inst->getCardInfo();
        $afterRewardNum = self::getRewardNum($uid);
        $this->assertTrue(($preRewardNum+2==$afterRewardNum),'reward num error.should get 2 reward.get '.($afterRewardNum-$preRewardNum));
        echo "test_getcardinfo_2 test done\n";
        
        
        
    }
    
    
    private static function getItemOrderNum($uid)
    {
        $data = new CData();
		$ret = $data->selectCount()->from(User4BBpayDao::tblBBpayItem)
		            ->where('uid', '=', $uid)
			        ->query();
		return intval( $ret[0]['count'] );
    }
    
    
    private static function getRewardNum($uid)
    {
        $data = new CData ();
        $ret = $data->selectCount()->from ( RewardDef::SQL_TABLE )
                       ->where( RewardDef::SQL_UID , '=', $uid)
                       ->where( RewardDef::SQL_SOURCE , '=', RewardSource::MONTHLY_CARD )
                       ->query();
        return intval( $ret[0]['count'] );
    }
    
    private static function getDate($time)
    {
        return date('Ymd',$time);
    }    
   
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */