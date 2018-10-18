<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: TowerTest.php 88844 2014-01-26 02:40:18Z TiantianZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/tower/test/TowerTest.php $
 * @author $Author: TiantianZhang $(zhangtiantian@babeltime.com)
 * @date $Date: 2014-01-26 02:40:18 +0000 (Sun, 26 Jan 2014) $
 * @version $Revision: 88844 $
 * @brief 
 *  
 **/
class TowerTest extends PHPUnit_Framework_TestCase
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
        self::$uid = $ret['uid'];
        EnUser::release();
        RPCContext::getInstance()->unsetSession(UserDef::SESSION_KEY_USER);
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
     * 1.第一次进入爬塔系统，数据的初始化
     */
    public function testGetTowerInfo1()
    {
        echo __METHOD__." start,data init test.\n";
        $towerInfo = TowerDAO::getTowerInfo(self::$uid, TOWERTBL_FIELD::$TBL_TOWER_ALL_FIELD);
        $this->assertTrue(empty($towerInfo),'the user not enter tower.towerinfo should be NULL');
        $tower = new Tower();
        $towerInfo2Front = $tower->getTowerInfo();
        $towerInfo = TowerDAO::getTowerInfo(self::$uid, TOWERTBL_FIELD::$TBL_TOWER_ALL_FIELD);
        $this->assertTrue(!empty($towerInfo),'the user first enter tower.should be initialised.');
        $this->assertTrue(($towerInfo2Front == $towerInfo),'towerinfo2Front should be equal to towerinfoindb');
        $this->assertTrue(($towerInfo[TOWERTBL_FIELD::MAX_LEVEL] == 0),'init max level is not 0');
        $this->assertTrue(($towerInfo[TOWERTBL_FIELD::CURRENT_LEVEL] == TowerDef::FIRST_TOWER_LEVEL_ID),'init current level is not first tower level '.TowerDef::FIRST_TOWER_LEVEL_ID);
    }
    
    /**
     *  2.每天刷新爬塔系统的重置次数、失败次数、金币购买次数
     */
    public function testGetTowerInfo2()
    {
        echo "testGetTowerInfo2 start\n";
        $dayTime = 86400;
        $towerInfo = MyTower::getInstance(self::$uid)->getTowerInfo();
        $towerInfo[TOWERTBL_FIELD::CAN_FAIL_NUM] = rand(0,TowerLogic::getDailyFailNum());
        $towerInfo[TOWERTBL_FIELD::RESET_NUM] = rand(0,TowerLogic::getDailyResetNum());
        $towerInfo[TOWERTBL_FIELD::GOLD_BUY_NUM] = rand(0,TowerLogic::getDailyGoldBuyNum(self::$uid));
        $towerInfo[TOWERTBL_FIELD::LAST_REFRESH_TIME] = Util::getTime()-$dayTime;
        TowerDAO::save(self::$uid, $towerInfo);
        $tower = new Tower();
        $towerInfo2Front = $tower->getTowerInfo();
        $this->assertTrue(($towerInfo != $towerInfo2Front),'not refresh towerinfo.please check');
        $towerInfo = TowerDAO::getTowerInfo(self::$uid, TOWERTBL_FIELD::$TBL_TOWER_ALL_FIELD);
        $this->assertTrue(($towerInfo2Front == $towerInfo),'towerinfo2Front should be equal to towerinfoindb');
        $this->assertTrue($towerInfo[TOWERTBL_FIELD::CAN_FAIL_NUM]==TowerLogic::getDailyFailNum(),
                'refresh daily fail num fail.please check.now is '.$towerInfo[TOWERTBL_FIELD::CAN_FAIL_NUM].' should be '.TowerLogic::getDailyFailNum());
        $this->assertTrue($towerInfo[TOWERTBL_FIELD::RESET_NUM] == TowerLogic::getDailyResetNum(),
                'refresh daily reset num fail.now is '.$towerInfo[TOWERTBL_FIELD::RESET_NUM].' should be '.TowerLogic::getDailyResetNum());
        $this->assertTrue($towerInfo[TOWERTBL_FIELD::GOLD_BUY_NUM] == 0,
                'refresh goldbuy num fail.now is '.$towerInfo[TOWERTBL_FIELD::GOLD_BUY_NUM]. 'should be 0');
        $this->assertTrue(($towerInfo[TOWERTBL_FIELD::LAST_REFRESH_TIME]) <= Util::getTime() 
                && ($towerInfo[TOWERTBL_FIELD::LAST_REFRESH_TIME] > (Util::getTime() - $dayTime)),
                'refresh last_refresh_time fail.');
    }
     /**
      *  3.爬塔数据中有扫荡信息，并且扫荡结束了  1）奖励信息是否发到奖励中心。2）cur_level是否正确
      */
    public function testGetTowerInfo3()
    {
        echo "testGetTowerInfo3 start\n";
        $towerInfo = MyTower::getInstance(self::$uid)->getTowerInfo();
        $maxLv = $this->getTopLevel();
        $curLv = TowerDef::FIRST_TOWER_LEVEL_ID;
        $towerInfo[TOWERTBL_FIELD::MAX_LEVEL] = $maxLv;
        $towerInfo[TOWERTBL_FIELD::CURRENT_LEVEL] = $curLv;
        MyTower::getInstance(self::$uid)->release();
        CData::$QUERY_CACHE = NULL;
        TowerDAO::save(self::$uid, $towerInfo);
        $endLv = rand($curLv+1, $maxLv);
        $lvGap = $this->getLvGap($curLv, $endLv);
        $sweepNeedTime = $lvGap * TowerLogic::getSweepGap();
        $tower = new Tower();
        echo "start sweep start level ".$curLv." end level ".$endLv.
        " max level is ".$maxLv."\n";
        $tower->sweep($curLv, $endLv);
        $sleepTime = $sweepNeedTime+1;
        echo "time gap is ".$sleepTime."\n";
        for($i=0;$i<=$sleepTime;$i++)
        {
            sleep(1);
            echo "sleep ".$i."\n";
        }
        CData::$QUERY_CACHE = NULL;
        $towerInfo4Front = MyTower::getInstance(self::$uid)->getTowerInfo();
        $towerInfo = TowerDAO::getTowerInfo(self::$uid, TOWERTBL_FIELD::$TBL_TOWER_ALL_FIELD);
        $this->assertTrue(($towerInfo4Front == $towerInfo),'towerinfo4front '.var_export($towerInfo4Front,true).' not equal to towerinfo '.var_export($towerInfo,true));
        $this->assertTrue(empty($towerInfo[TOWERTBL_FIELD::VA_TOWER_INFO][TOWERTBL_FIELD::VA_TOWER_SWEEPINFO]),
                'sweep info is not null.sweepinfo is '.var_export($towerInfo[TOWERTBL_FIELD::VA_TOWER_INFO][TOWERTBL_FIELD::VA_TOWER_SWEEPINFO],TRUE));
        $data = new CData();
        $ret = $data->selectCount()
                    ->from('t_reward')
                    ->where(array('source','=',RewardSource::TOWER_SWEEP))
                    ->query();
        $this->assertTrue(($ret[0]['count'] == 1),'reward center has no reward.reward count is '.$ret[0]['count']);
    }
    
    private function getLvGap($startLv,$endLv)
    {
        $lvConf = btstore_get()->TOWERLEVEL;
        $lvNum = 0;
        foreach($lvConf as $level => $conf)
        {
            if($level >= $startLv && ($level <= $endLv))
            {
                $lvNum++;
            }
        }   
        return $lvNum;
    }
    
    private function getTopLevel()
    {
        $lvConf = btstore_get()->TOWERLEVEL;
        $curLv = TowerDef::FIRST_TOWER_LEVEL_ID;
        while(TRUE)
        {
            if(empty($curLv))
            {
                break;
            }
            $nextLv = $lvConf[$curLv]['pass_open_lv'];
            if(empty($nextLv))
            {
                return $curLv;
            }
            $curLv = $nextLv;
        }
        return $curLv;
    }
    /**
     * 1.有失败次数时不能购买
     * 2.购买金币数目以及上限
     * 3.购买次数限制
     */
    public function testBuyDefeatNum1()
    {
        //初始化新玩家的爬塔信息
        $towerInfo = MyTower::getInstance(self::$uid)->getTowerInfo();
        //1.初始失败次数不是0,购买之后会抛异常
        $tower = new Tower();
        try
        {
            $tower->buyDefeatNum();
            $this->fail('has fail num.but buying fail num successfully.please check.');
        }
        catch(Exception $e)
        {
            echo "has fail num to buy fail num.fail message is ".$e->getMessage()."\n";
        }
        MyTower::release();
        //2.购买金币数目以及上限
        $userObj = EnUser::getUserObj(self::$uid);
        $userObj->addGold(1000000000, StatisticsDef::ST_FUNCKEY_COPY_GETPRIZE);
        $userObj->update();
        EnUser::release(self::$uid);
        $buyNumLimit = TowerLogic::getDailyGoldBuyNum(self::$uid);
        $initGold = TowerLogic::getBuyFailInitGold();
        $incGold = TowerLogic::getBuyFailIncGold();
        $goldLimit = TowerLogic::getBuyFailGoldLimit();
        for($totalBuyNum=1;$totalBuyNum<=$buyNumLimit;$totalBuyNum++)
        {
            $preGoldNum = EnUser::getUserObj(self::$uid)->getGold();
            EnUser::release(self::$uid);
            $towerInfo = MyTower::getInstance(self::$uid)->getTowerInfo();
            $towerInfo[TOWERTBL_FIELD::CAN_FAIL_NUM] = 0;
            TowerDAO::save(self::$uid, $towerInfo);
            MyTower::release();
            CData::$QUERY_CACHE = NULL;
            RPCContext::getInstance()->resetSession();
            $spend = $tower->buyDefeatNum();
            $actual = ($totalBuyNum - 1) * $incGold + $initGold;
            if($actual > $goldLimit)
            {
                $actual = $goldLimit;
            }
            $this->assertTrue($actual == $spend,
                    'buynum '.$totalBuyNum.' init gold '.$initGold.' inc gold '
                    .$incGold.' gold limit '.$goldLimit.' actual spend '.$actual.' return spend '.$spend);
            $afterGoldNum = EnUser::getUserObj(self::$uid)->getGold();
            EnUser::release(self::$uid);
            $this->assertTrue(($spend == ($preGoldNum - $afterGoldNum)),'sub gold fail.');
        }
        MyTower::release();
        CData::$QUERY_CACHE = NULL;
        RPCContext::getInstance()->resetSession();
        try 
        {
            $tower->buyDefeatNum();
            $this->fail('no fail buy num.but buy successfully.');
        }
        catch (Exception $e)
        {
            echo 'has no buy num.fail message is '.$e->getMessage()."\n";
        }
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */