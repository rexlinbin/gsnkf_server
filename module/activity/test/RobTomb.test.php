<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: RobTomb.test.php 88416 2014-01-22 12:53:09Z TiantianZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/test/RobTomb.test.php $
 * @author $Author: TiantianZhang $(zhangtiantian@babeltime.com)
 * @date $Date: 2014-01-22 12:53:09 +0000 (Wed, 22 Jan 2014) $
 * @version $Revision: 88416 $
 * @brief 
 *  
 **/
class RobTombTest extends PHPUnit_Framework_TestCase
{
    private static $uid;
    private static $pid;
    /**
     * This method is called before the first test of this test class is run.
     *
     * @since Method available since Release 3.4.0
     */
    public static function setUpBeforeClass()
    {
        self::$pid = time();
    }
    
    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     *
     */
    protected function setUp()
    {
        
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
        UserDao::updateUser($ret['uid'], array('level'=>RobTombLogic::getActNeedLevel()));
        EnUser::release();
        RPCContext::getInstance()->unsetSession(UserDef::SESSION_KEY_USER);
        return $ret['uid'];
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
    
    private function getRobInfoByUid($uid)
    {
        CData::$QUERY_CACHE = NULL;
        $robInfo = RobTombDao::getRobInfo($uid, RobTombDef::$ALL_TBL_FIELD);
        return $robInfo;
    }
    
    private function checkRobInfoOnActOpen($robInfo)
    {
        if($robInfo[RobTombDef::SQL_ACCUM_FREE_NUM] != 0 || 
                ($robInfo[RobTombDef::SQL_ACCUM_GOLD_NUM] != 0) ||
                ($robInfo[RobTombDef::SQL_TODAY_FREE_NUM] != 0) ||
                ($robInfo[RobTombDef::SQL_TODAY_GOLD_NUM] != 0) ||
                (empty($robInfo[RobTombDef::SQL_VA_ROB_TOMB]) == FALSE) ||
                (Util::isSameDay($robInfo[RobTombDef::SQL_LAST_RFR_TIME]) == FALSE))
        {
            return FALSE;
        }
        return TRUE;
    }
    
    private function checkRobInfoOnNewDay($robInfo)
    {
        if(($robInfo[RobTombDef::SQL_TODAY_FREE_NUM] != 0) ||
                ($robInfo[RobTombDef::SQL_TODAY_GOLD_NUM] != 0) ||
                (Util::isSameDay($robInfo[RobTombDef::SQL_LAST_RFR_TIME]) == FALSE))
        {
            return FALSE;
        }
        return TRUE;
    }
    /**
     * 1.初始化用户挖宝信息
     * 2.每天刷新挖宝信息
     * 3.第一个活动结束之后，开启新的活动  初始化用户挖宝数据
     */
    public function testGetRobInfo()
    {
        echo __METHOD__." start \n";
        self::$uid = $this->createUser();
        RPCContext::getInstance()->setSession(UserDef::SESSION_KEY_UID, self::$uid);
        $robInfo = $this->getRobInfoByUid(self::$uid);
        if(!empty($robInfo))
        {
            Logger::fatal('new created user.robinfo is %s.',$robInfo);
            $this->assertTrue(FALSE,'new created user.robinfo in db is not null');
        }
        $this->assertTrue((empty($robInfo)),'new created user.but the robinfo is not null');
        $robInst = new RobTomb();
        $robInst->getMyRobInfo();
        $robInfo = $this->getRobInfoByUid(self::$uid);
        $checkRet = $this->checkRobInfoOnActOpen($robInfo);
        $this->assertTrue(($checkRet),'check robinfo on act open failed.');
        $robInfo[RobTombDef::SQL_LAST_RFR_TIME] = Util::getTime() - 24*60*60;
        RobTombDao::updateRobInfo(self::$uid, $robInfo);
        MyRobTomb::release();
        $robInst = new RobTomb();
        $robInst->getMyRobInfo();
        $robInfo = $this->getRobInfoByUid(self::$uid);
        $checkRet = $this->checkRobInfoOnNewDay($robInfo);
        $this->assertTrue(($checkRet),'check robinfo on new day failed.');
        $actStartTime = RobTombLogic::getActStartTime();
        $robInfo[RobTombDef::SQL_LAST_RFR_TIME] = $actStartTime - 60*60;
        RobTombDao::updateRobInfo(self::$uid, $robInfo);
        MyRobTomb::release();
        $robInst = new RobTomb();
        $robInst->getMyRobInfo();
        $robInfo = $this->getRobInfoByUid(self::$uid);
        $checkRet = $this->checkRobInfoOnActOpen($robInfo);
        $this->assertTrue(($checkRet),'check robinfo on act open failed.');
    }
    /**
     * 1.等级限制
     * 2.背包满了不能挖宝
     */
    public function testRob1()
    {
        echo __METHOD__." start this act has level limit and bag full limit\n";
        EnUser::release();
        RPCContext::getInstance()->unsetSession(UserDef::SESSION_KEY_USER);
        CData::$QUERY_CACHE = NULL;
        self::$uid = $this->createUser();
        $console = new Console();
        RPCContext::getInstance()->setSession(UserDef::SESSION_KEY_UID, self::$uid);
        $console->gold(UserConf::GOLD_MAX);
        //等级
        echo "now user level is 1\n";
        $robInst = new RobTomb();
        $canRob = (EnUser::getUserObj(self::$uid)->getLevel() >= RobTombLogic::getActNeedLevel());
        try 
        {
            $robInst->rob(1, RobTombDef::ROB_TYPE_GOLD);
            $this->assertTrue($canRob,'can not rob.but rob successfully.');
        }
        catch(Exception $e)
        {
            $this->assertTrue(!$canRob,'can rob.but fail to rob.');
        }
        echo "user level is maxer than ".RobTombLogic::getActNeedLevel()."\n";
        $console->level(RobTombLogic::getActNeedLevel()+rand(1, 10));
        EnUser::release();
        $canRob = (EnUser::getUserObj(self::$uid)->getLevel() >= RobTombLogic::getActNeedLevel());
        try
        {
            $robInst->rob(1, RobTombDef::ROB_TYPE_GOLD);
            $this->assertTrue($canRob,'can not rob.but rob successfully.');
        }
        catch(Exception $e)
        {
            $this->assertTrue(!$canRob,'can rob.but fail to rob.');
        }
        //背包清空
        $bag = BagManager::getInstance()->getBag(self::$uid);
        if($bag->isFull())
        {
            $bagInst = new Bag();
            $arrBagInfo = $bagInst->bagInfo();
            unset($arrBagInfo['gridStart']);
            unset($arrBagInfo['gridMaxNum']);
            foreach($arrBagInfo as $bagName => $arrItemInfo)
            {
                foreach($arrItemInfo as $gid => $itemInfo)
                {
                    $bag->decreaseItem($itemInfo['item_id'], $itemInfo['item_num']);
                }
            }
            $bag->update();
        }
        echo "clear bag done\n";
        CData::$QUERY_CACHE = NULL;
        MyRobTomb::release();
        try
        {
            $robInst->rob(1, RobTombDef::ROB_TYPE_GOLD);
        }
        catch(Exception $e)
        {
            $this->assertTrue(FALSE,'bag is not full,can rob.but fail to rob.');
        }
        $arrItemConf = btstore_get()->ITEMS->toArray();
        $arrItemId = array_keys($arrItemConf);
        while($bag->isFull() == FALSE)
        {
            $key = array_rand($arrItemId);
            $itemTmplId = $arrItemId[$key];
            try{
                $bag->addItemByTemplateID($itemTmplId, 1);
            }
            catch (Exception $e)
            {
                Logger::warning('addItem failed.');
            }
            
        }
        echo "now the bag is full\n";
        CData::$QUERY_CACHE = NULL;
        EnUser::release();
        MyRobTomb::release();
        MyRobTomb::release();
        try
        {
            $robInst->rob(1, RobTombDef::ROB_TYPE_GOLD);
            $this->assertTrue(FALSE,'bag is full.can not rob.but rob sucessfully.');
        }
        catch(Exception $e)
        {
        }
    }
    
    private function randItem()
    {
        $arrItemConf = btstore_get()->ITEMS->toArray();
        $arrItemId = array_keys($arrItemConf);
        $key = array_rand($arrItemId);
        return $arrItemId[$key];
    }
    /**
     * 次数测试  不同VIP次数限制不同
     * 1.免费次数    a.免费挖宝之后  金币不变化    b.免费挖宝之后  免费次数增加     c.有免费次数时，不能使用金币挖宝一次
     * 2.金币次数    a.金币挖宝之后  金币扣除        b.金币挖宝之后  金币次数增加    
     */
    public function testRob2()
    {
        echo __METHOD__." start different VIP has different rob num\n";
        //验证五个VIP等级  挖宝次数测试
        $arrVip = array();
        for($i=0;$i<5;$i++)
        {
            EnUser::release();
            self::$uid = $this->createUser();
            RPCContext::getInstance()->setSession(UserDef::SESSION_KEY_UID, self::$uid);
            RPCContext::getInstance()->unsetSession(UserDef::SESSION_KEY_USER);
            $console = new Console();
            $console->gold(UserConf::GOLD_MAX);
            while(TRUE)
            {
                $vip = rand(0,10);
                if(in_array($vip, $arrVip) == FALSE)
                {
                    $arrVip[] = $vip;
                    break;
                }
            }
            echo "now vip is ".$vip."\n";
            $console = new Console();
            $console->vip($vip);
            $bag = BagManager::getInstance()->getBag();
            $robInst = new RobTomb();
            //有免费挖宝次数
            if(RobTombLogic::hasRobNum(self::$uid, RobTombDef::ROB_TYPE_FREE))
            {
                try {
                    if($bag->isFull())
                    {
                        $console->clearBag();
                    }
                    $robInst->rob(1, RobTombDef::ROB_TYPE_GOLD);
                    $this->assertTrue(FALSE,'has free rob num.can not rob by gold.');
                }
                catch(Exception $e)
                {
                    
                }
                if(RobTombLogic::hasRobNum(self::$uid, RobTombDef::ROB_TYPE_GOLD, 10))
                {
                    $this->checkGoldSpendAndRobNum(RobTombDef::ROB_TYPE_GOLD, 10);
                }
            }
            while(RobTombLogic::hasRobNum(self::$uid, RobTombDef::ROB_TYPE_FREE))
            {
                $this->checkGoldSpendAndRobNum(RobTombDef::ROB_TYPE_FREE, 1);
            }
            while(RobTombLogic::hasRobNum(self::$uid, RobTombDef::ROB_TYPE_GOLD))
            {
                $this->checkGoldSpendAndRobNum(RobTombDef::ROB_TYPE_GOLD, 1);
            }
        }
        
        
    }
    
    private function checkGoldSpendAndRobNum($robType,$num)
    {
        EnUser::release();
        RPCContext::getInstance()->unsetSession(UserDef::SESSION_KEY_USER);
        MyRobTomb::release();
        CData::$QUERY_CACHE = NULL;
        $preGoldNum = EnUser::getUserObj()->getGold();
        if($robType == RobTombDef::ROB_TYPE_FREE)
        {
            $preRobNum = MyRobTomb::getInstance()->getFreeRobNum();
        }
        else if($robType == RobTombDef::ROB_TYPE_GOLD)
        {
            $preRobNum = MyRobTomb::getInstance()->getGoldRobNum();
        }
        $robInst = new RobTomb();
        $console = new Console();
        $bag = BagManager::getInstance()->getBag();
        if($bag->isFull())
        {
            $console->clearBag();
        }
        $robInst->rob($num, $robType);
        EnUser::release();
        RPCContext::getInstance()->unsetSession(UserDef::SESSION_KEY_USER);
        MyRobTomb::release();
        CData::$QUERY_CACHE = NULL;
        $afterGoldNum = EnUser::getUserObj()->getGold();
        if($robType == RobTombDef::ROB_TYPE_FREE)
        {
            $afterRobNum = MyRobTomb::getInstance()->getFreeRobNum();
            $this->assertTrue(($preGoldNum == $afterGoldNum),'free rob spend no gold.but gold num is not equal.');
            $this->assertTrue(($preRobNum+$num==$afterRobNum),'rob free.but prerobnum +num!=afterrobnum');
        }
        else if($robType == RobTombDef::ROB_TYPE_GOLD)
        {
            $afterRobNum = MyRobTomb::getInstance()->getGoldRobNum();
            $this->assertTrue(($preGoldNum - RobTombLogic::getRobNeedGold()*$num == $afterGoldNum),'gold rob spend gold.but gold num is right.');
            $this->assertTrue(($preRobNum+$num==$afterRobNum),'rob gold.but prerobnum +num!=afterrobnum pre num '.$preGoldNum." robnum is ".$num.";afternum is ".$afterGoldNum);
        }
    }
    
    /**
     * 掉落物品测试
     * 1.累积掉落      每次到达累积次数  必掉累积掉落表中的物品
     */
    public function testRob3()
    {
        echo __METHOD__." start test accum drop item is in accum_drop_list\n";
        self::$uid = $this->createUser();
        RPCContext::getInstance()->setSession(UserDef::SESSION_KEY_UID, self::$uid);
        $console = new Console();
        $console->gold(UserConf::GOLD_MAX);
        $arrAccumNum = RobTombLogic::getArrAccumRobNum();
        $maxAccumNum = $arrAccumNum[count($arrAccumNum)-1];
        $robInst = new RobTomb();
        $bag = BagManager::getInstance()->getBag();
        for($i=1;$i<=$maxAccumNum;$i++)
        {
            if(RobTombLogic::hasRobNum(self::$uid, RobTombDef::ROB_TYPE_GOLD) == FALSE)
            {
                $this->resetGoldNum();
            }
            if($bag->isFull())
            {
                $console->clearBag();
            }
            $robRet = $robInst->rob(1, RobTombDef::ROB_TYPE_GOLD);
            $allDropGot = $this->getArrDropInfo(RobTombLogic::getArrAccumDropId());
            if(in_array($i, $arrAccumNum) == TRUE)
            {
                foreach($robRet as $dropStrType => $dropInfo)
                {
                    $dropType = DropDef::DROP_TYPE_ITEM;
                    switch($dropStrType)
                    {
                        case DropDef::DROP_TYPE_STR_HERO:
                            $dropType = DropDef::DROP_TYPE_HERO;
                            break;
                        case DropDef::DROP_TYPE_STR_ITEM:
                            $dropType = DropDef::DROP_TYPE_ITEM;
                            break;
                        case DropDef::DROP_TYPE_STR_TREASFRAG:
                            $dropType = DropDef::DROP_TYPE_TREASFRAG;
                            break;
                    }
                    $accumGoldRobNum = MyRobTomb::getInstance(self::$uid)->getAccumGoldRobNum();
                    foreach($dropInfo as $tmplId => $tmplNum)
                    {
                        $this->assertTrue(in_array($tmplId, $allDropGot[$dropType]),
                                'rob num '.$i.'accum goldrob num is '. $accumGoldRobNum.' droped type '.$dropStrType.'item:'.$tmplId.
                                ' not in specail drop tbl;'.var_export($allDropGot[$dropType],true));
                        echo "gold rob num is ".$i.";drop item is ".$tmplId.".droptype is $dropStrType \n";
                    }
                }
            }
        }
    }
    
    private function getArrDropInfo($arrDrop)
    {
        $arrDropGot = array();
        foreach($arrDrop as $dropId => $info)
        {
            $dropGot = Drop::getDropInfo($dropId);
            foreach($dropGot as $dropType => $dropInfo)
            {
                if(!isset($arrDropGot[$dropType]))
                {
                    $arrDropGot[$dropType] = array();
                }
                $arrDropGot[$dropType] = array_merge($arrDropGot[$dropType],$dropInfo);
                array_unique($arrDropGot[$dropType]);
            }
        }
        return $arrDropGot;
    }
    
    private function resetGoldNum()
    {
        $robInst = new RobTomb();
        $robInfo = $robInst->getMyRobInfo();
        $robInfo[RobTombDef::SQL_TODAY_GOLD_NUM] = 0;
        $robInfo[RobTombDef::SQL_TODAY_FREE_NUM] = RobTombLogic::getFreeNumByUid(self::$uid);
        RobTombDao::updateRobInfo(self::$uid, $robInfo);
        MyRobTomb::release();
        CData::$QUERY_CACHE = NULL;
    }
    
    /**
     * 物品数目限制
     * 1.挖宝很多次   （判断黑名单中的物品到达上限之后，能否还此物品）
     */
    public function testRob4()
    {
        $ret = EnActivity::getConfByName(ActivityName::ROB_TOMB);
        self::$uid = $this->createUser();
        RPCContext::getInstance()->setSession(UserDef::SESSION_KEY_UID, self::$uid);
        $console = new Console();
        $console->gold(UserConf::GOLD_MAX);
        $robInst = new RobTomb();
        $ret = EnActivity::getConfByName(ActivityName::ROB_TOMB);
        $bag = BagManager::getInstance()->getBag();
        $robInfo = $robInst->getMyRobInfo();
        $robInfo[RobTombDef::SQL_VA_ROB_TOMB][RobTombDef::SQL_VA_ROB_BLACKLIST] = RobTombLogic::getArrDropLimit();
        RobTombDao::updateRobInfo(self::$uid, $robInfo);
        CData::$QUERY_CACHE = NULL;
        MyRobTomb::release();
        echo "now black list is full.start to check if the item in black list can drop\n";
        $checkNum = 100;
        for($i=0;$i<$checkNum;$i++)
        {
            echo "check num ".$i."\n";
            if(RobTombLogic::hasRobNum(self::$uid, RobTombDef::ROB_TYPE_GOLD) == FALSE)
            {
                $this->resetGoldNum();
            }
            if($bag->isFull())
            {
                $console->clearBag();
            }
            $robRet = $robInst->rob(1, RobTombDef::ROB_TYPE_GOLD);
            $dropLimit = RobTombLogic::getArrDropLimit();
            $allDropGot = $this->getArrDropInfo($dropLimit);
            foreach($robRet as $dropStrType => $dropInfo)
            {
                $dropType = DropDef::DROP_TYPE_ITEM;
                switch($dropStrType)
                {
                    case DropDef::DROP_TYPE_STR_HERO:
                        $dropType = DropDef::DROP_TYPE_HERO;
                        break;
                    case DropDef::DROP_TYPE_STR_ITEM:
                        $dropType = DropDef::DROP_TYPE_ITEM;
                        break;
                    case DropDef::DROP_TYPE_STR_TREASFRAG:
                        $dropType = DropDef::DROP_TYPE_TREASFRAG;
                        break;
                    default:
                        throw new FakeException('drop type is %d',$dropStrType);
                }
                $accumGoldRobNum = MyRobTomb::getInstance(self::$uid)->getAccumGoldRobNum();
                foreach($dropInfo as $tmplId => $tmplNum)
                {
                    if(isset($allDropGot[$dropType]))
                    {
                        $this->assertTrue(!in_array($tmplId, $allDropGot[$dropType]),
                                'itemid '.$tmplId.' is not dropped by dropid which is in blacklist');
                        echo "gold rob num is ".$i.";drop item is ".$tmplId.".droptype is $dropStrType \n";
                        if(in_array($tmplId, $allDropGot[$dropType]) == FALSE)
                        {
                            echo "not drop item in blacklist\n";
                        }
                    }
                    else 
                    {
                         echo "no such type $dropStrType in blacklist \n";   
                    }
                }
            }
        }
    }
    
    /**
     * 确认黑名单是否已经填满
     */
    private function checkFullBlackList()
    {
        $ret = EnActivity::getConfByName(ActivityName::ROB_TOMB);
        $robLimit = $ret['data'][RobTombDef::BTSTORE_ITEM_NUM_LIMIT];
        $blackList = MyRobTomb::getInstance(self::$uid)->getBlackList();
        foreach($robLimit as $itemId => $itemNum)
        {
            if(!isset($blackList[$itemId]))
            {
                return FALSE;
            }
            if($blackList[$itemId] < $itemNum)
            {
                return FALSE;
            }
            if($blackList[$itemId] > $itemNum)
            {
                throw new FakeException('error.blacklist more than limit.');
            }
        }
        return TRUE;
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */