<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: HeroShopTest.php 259698 2016-08-31 08:07:55Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/test/HeroShopTest.php $
 * @author $Author: BaoguoMeng $(zhangtiantian@babeltime.com)
 * @date $Date: 2016-08-31 08:07:55 +0000 (Wed, 31 Aug 2016) $
 * @version $Revision: 259698 $
 * @brief 
 *  
 **/
class HeroShopTest extends PHPUnit_Framework_TestCase
{
    private static $uid;
    private static $actName = 'heroShop';
    private static $actRewardName = 'heroShopReward';
    private static $rewardConf = NULL;
    private static $needUserNum = 200;
    private static $arrCheckUser = NULL;
    private static $startTime;
    private static $duringTime = 3600;
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
//         $pid = time();
//         $str = strval($pid);
//         $uname = substr($str, strlen($str) - UserConf::MAX_USER_NAME_LEN);
//         $ret = UserLogic::createUser($pid, 1, $uname);
//         if($ret['ret'] != 'ok')
//         {
//             echo "create use failed\n";
//             exit();
//         }
//         Logger::trace('create user ret %s.',$ret);
//         self::$uid = $ret['uid'];
//         RPCContext::getInstance ()->setSession ( UserDef::SESSION_KEY_UID, self::$uid );
//         RPCContext::getInstance()->resetSession();
//         CData::$QUERY_CACHE = NULL;
//         EnUser::release();
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
     * 1.活动没有开呢
     * 2.活动开了
     * 3.活动已经结束，现在在抽奖冷却时间内
     */
    public function testGetMyShopInfo1()
    {
        
    }
    /**
     * 第一个用户登录此系统
     * 1.上一轮的数据存在，获取的排名信息应该是空的
     * 2.此用户的上一轮数据存在，获取的商店信息是否能重置
     */
    public function testGetMyShopInfo2()
    {
        
    }
    /**
     * 清空memcache，第一个用户登录getMyShopInfo之后，
     * sleep(3)是否能够将新的rankinfo写入memcache
     */
    public function testGetMyShopInfo3()
    {
        
    }
    
    private function setActStartTime($startTime)
    {
        $curConf = ActivityConfDao::getCurConfByName(self::$actName, 
                array('version','name'));
        self::$startTime = $startTime;
        $data = new CData();
        $data->update('t_activity_conf')
        ->set(array('start_time'=>$startTime))
        ->where(array('version','=',$curConf['version']))
        ->where(array('name','LIKE',$curConf['name']))
        ->query();
    }
    
    private function setActEndTime($endTime)
    {
        $curConf = ActivityConfDao::getCurConfByName(self::$actName,
                array('version','name'));
        $curConf['end_time'] = $endTime;
        $data = new CData();
        $data->update('t_activity_conf')
        ->set(array('end_time'=>$endTime))
        ->where(array('version','=',$curConf['version']))
        ->where(array('name','LIKE',$curConf['name']))
        ->query();
    }
    
    
    private function setActNeedOpenTime($needOpenTime)
    {
        $curConf = ActivityConfDao::getCurConfByName(self::$actName,
                array('version','name'));
        $curConf['need_open_time'] = $needOpenTime;
        $data = new CData();
        $data->update('t_activity_conf')
        ->set(array('need_open_time'=>$needOpenTime))
        ->where(array('version','=',$curConf['version']))
        ->where(array('name','LIKE',$curConf['name']))
        ->query();
    }
    
    private function setActRewardVaData($data)
    {
        $curConf = ActivityConfDao::getCurConfByName(self::$actRewardName,
                array('version','name'));
        $curConf['va_data'] = $data;
        $data = new CData();
        $data->update('t_activity_conf')
        ->set(array('va_data'=>$data))
        ->where(array('version','=',$curConf['version']))
        ->where(array('name','LIKE',$curConf['name']))
        ->query();
    }
//     /**
//      * 累积次数抽紫将
//      */
//     public function testbuyHero1()
//     {
        
//     }
    
//     /**
//      * 不同的购买方式（free,gold_free,gold）都会增加购买次数
//      * free方式下重置FREECD
//      * gold_free方式下扣除FREENUM
//      */
//     public function testbuyHero2()
//     {
        
//     }
//     /**
//      * 抽将之后积分增加
//      * 1.原来就在前20名，导致memcache刷新，排名变化
//      * 2.原来不在前20名，挤进去前20名，导致memcache刷新，排名变化
//      * 3.原来不在前20名，现在也没有挤进去前20名，但是排名变化了，memcache不刷新
//      */
//     public function testbuyHero3()
//     {
        
//     }
//     /**
//      * 活动配置的结束时间变化，timer什么时候会更新
//      * 1.现在只有在前20名积分变化时会更新
//      */
//     public function testBuyHero4()
//     {
        
//     }
    
    /**
     * 排名奖励，积分奖励：
     * 20是有积分奖励的最小积分
     * 100是有排名奖励的最大排名
     * 1.积分大于等于20的玩家的数目小于100,排名奖励用户等于100，积分奖励用户小于100
     * 2.积分大于等于20的玩家数目大于100，排名奖励用户等于100，积分奖励用户大于100
     * 3.每个积分档次、排名档次的奖励是否正确
     * 
     */
    public function testReward()
    {
        echo __METHOD__."start\n";
        $data = new CData();
        $ret = $data->selectCount()
                    ->from('t_hero_shop')
                    ->where(array('uid','>',0))
                    ->query();
        $userNum = $ret[0]['count'];
        if($userNum < self::$needUserNum)
        {
            echo "need create user\n";
            $newUserNum = self::$needUserNum - $userNum;
            for($i=0;$i<$newUserNum;$i++)
            {
                $uid = $this->createUser();
                MyHeroShop::getInstance($uid)->getShopInfo();
                MyHeroShop::getInstance($uid)->save();
            }
        }
        //配置活动 生成奖励   重置用户积分
        $reward = $this->generateRewardInDb();
        echo "get reward in db done\n";
        $startTime = Util::getTime();
        $endTime = Util::getTime();
        $this->setActStartTime($startTime);
        $this->setActEndTime($endTime+1);
        $this->setActNeedOpenTime(Util::getTime());
        echo "reset all user in act start\n";
        $this->resetAllUserInAct(self::$startTime, $reward);
        echo "reset all user in act done\n";
        CData::$QUERY_CACHE = NULL;
        RPCContext::getInstance()->resetSession();
        //发送奖励
        //获取奖励中心发奖之前的数据
        $data = new CData();
        $preReward = $data->select(array('rid','uid','source'))
             ->from('t_reward')
             ->where(array('uid','IN',self::$arrCheckUser))
             ->where( array(RewardDef::SQL_SEND_TIME , '>=', self::$startTime) )
			 ->where( array(RewardDef::SQL_DELETE_TIME , '=', 0) )
             ->where(array('source','IN',array(RewardSource::HERO_SHOP_INTEGRAL,RewardSource::HERO_SHOP_RANK)))
             ->query();
        $arrPreUserReward = array();
        foreach($preReward as $index => $reward)
        {
            $uid = $reward['uid'];
            $source = $reward['source'];
            $rid = $reward[RewardDef::SQL_RID];
            if(!isset($arrPreUserReward[$uid][$source]))
            {
                $arrPreUserReward[$uid][$source] = array();
            }
            $arrPreUserReward[$uid][$source][$rid] = 1;
        }
        CData::$QUERY_CACHE = NULL;
        RPCContext::getInstance()->resetSession();
        MyHeroShop::release();
        EnUser::release();
        $key = ActivityConfLogic::genMcKey(ActivityName::HERO_SHOP);
        McClient::del($key);
        CData::$QUERY_CACHE = NULL;
        echo "sleep 2 seconds.than send reward to user.\n";
        sleep(2);
        //发奖
        $heroShop = new HeroShop;
        echo "start to send reward\n";
        $heroShop->rewardUser();
        CData::$QUERY_CACHE = NULL;
        RPCContext::getInstance()->resetSession();
        MyHeroShop::release();
        EnUser::release();
        $afterReward = $data->select(array('rid','uid','source','va_reward'))
                        ->from('t_reward')
                        ->where(array('uid','IN',self::$arrCheckUser))
                        ->where( array(RewardDef::SQL_SEND_TIME , '>=', self::$startTime) )
                        ->where( array(RewardDef::SQL_DELETE_TIME , '=', 0) )
                        ->where(array('source','IN',array(RewardSource::HERO_SHOP_INTEGRAL,RewardSource::HERO_SHOP_RANK)))
                        ->query();
        $arrAfterUserReward = array();
        $afterReward = Util::arrayIndex($afterReward, 'rid');
        foreach($afterReward as $index => $reward)
        {
            $uid = $reward['uid'];
            $source = $reward['source'];
            $rid = $reward[RewardDef::SQL_RID];
            if(!isset($arrAfterUserReward[$uid][$source]))
            {
                $arrAfterUserReward[$uid][$source] = array();
            }
            $arrAfterUserReward[$uid][$source][$rid] = 1;
        } 
        $rankList = $this->getRankList(self::$needUserNum*5);
        foreach($rankList as $uid => $rankInfo)
        {
            if(in_array($uid, self::$arrCheckUser))
            {
                echo "*************************************************\n";
                $rank = $rankInfo['rank'];
                $score = $rankInfo['score'];
                echo "check reward of user ".$uid.",rank:".$rank.",score:".$score."\n";
                $maxRank = self::$rewardConf['max_rank'];
                $minScore = self::$rewardConf['min_score'];
                $rankReward2Center = array();
                $scoreReward2Center = array();
                if(!isset($arrAfterUserReward[$uid][RewardSource::HERO_SHOP_RANK]))
                {
                    $arrAfterUserReward[$uid][RewardSource::HERO_SHOP_RANK] = array();
                }
                if(!isset($arrPreUserReward[$uid][RewardSource::HERO_SHOP_RANK]))
                {
                    $arrPreUserReward[$uid][RewardSource::HERO_SHOP_RANK] = array();
                }
                //有排名奖励
                if($rank <= $maxRank)
                {
                    echo 'user '.$uid.' rank:'.$rank.' have rank reward'."\n";
                    $reward = HeroShopLogic::getRewardByRank($rank, self::$rewardConf);
                    $rankReward2Center = $this->sendToRewardCenter($uid, $reward, $rank, RewardSource::HERO_SHOP_RANK);
                    if($arrAfterUserReward[$uid][RewardSource::HERO_SHOP_RANK] ==
                             $arrPreUserReward[$uid][RewardSource::HERO_SHOP_RANK])
                    {
                        echo "user ".$uid." rank:".$rank." have no rank reward.error!!!!\n";
                        return;
                    }
                    else
                    {
                        $rewardNum = 0;
                        foreach($arrAfterUserReward[$uid][RewardSource::HERO_SHOP_RANK] as $rid => $status)
                        {
                            if(!isset($arrPreUserReward[$uid][RewardSource::HERO_SHOP_RANK][$rid]))
                            {
                                $rewardNum++;
                                if($afterReward[$rid]['va_reward'] != $rankReward2Center)
                                {
                                    echo 'user '.$uid." get wrong rank rewarderror!!!\n";
                                    return;
                                }
                                if($rewardNum > 1)
                                {
                                    echo 'user '.$uid." get more than one rank rewarderror!!!\n";
                                    return;
                                }
                                var_dump($rankReward2Center);    
                            }
                        }
                    }
                }
                else
                {
                    if($arrAfterUserReward[$uid][RewardSource::HERO_SHOP_RANK] != $arrPreUserReward[$uid][RewardSource::HERO_SHOP_RANK])
                    {
                        echo "user ".$uid." rank:".$rank." should has no reward.error!!!\n";
                        return;
                    }
                }
                //有积分奖励
                if(!isset($arrAfterUserReward[$uid][RewardSource::HERO_SHOP_INTEGRAL]))
                {
                    $arrAfterUserReward[$uid][RewardSource::HERO_SHOP_INTEGRAL] = array();
                }
                if(!isset($arrPreUserReward[$uid][RewardSource::HERO_SHOP_INTEGRAL]))
                {
                    $arrPreUserReward[$uid][RewardSource::HERO_SHOP_INTEGRAL] = array();
                }
                if($score >= $minScore)
                {
                    echo 'user '.$uid.' score:'.$score.' have score reward'."\n";
                    $reward = HeroShopLogic::getRewardByScore($score, self::$rewardConf);
                    $scoreReward2Center = $this->sendToRewardCenter($uid, $reward, $score, RewardSource::HERO_SHOP_INTEGRAL);
                    if($arrAfterUserReward[$uid][RewardSource::HERO_SHOP_INTEGRAL] == $arrPreUserReward[$uid][RewardSource::HERO_SHOP_INTEGRAL])
                    {
                        echo "user ".$uid." score:".$score." should has reward.error!!!\n";
                        return;
                    }
                    else
                    {
                        $rewardNum = 0;
                        foreach($arrAfterUserReward[$uid][RewardSource::HERO_SHOP_INTEGRAL] as $rid => $status)
                        {
                            if(!isset($arrPreUserReward[$uid][RewardSource::HERO_SHOP_INTEGRAL][$rid]))
                            {
                                $rewardNum++;
                                if($afterReward[$rid]['va_reward'] != $scoreReward2Center)
                                {
                                    echo 'user '.$uid." get wrong score reward.error!!!\n";
                                    return;
                                }
                                if($rewardNum > 1)
                                {
                                    echo 'user '.$uid." get more than one score reward.error!!!\n";
                                    return;
                                }
                                var_dump($scoreReward2Center);
                            }
                        }
                    }
                }
                else
                {
                    if($arrAfterUserReward[$uid][RewardSource::HERO_SHOP_INTEGRAL] != $arrPreUserReward[$uid][RewardSource::HERO_SHOP_INTEGRAL])
                    {
                        echo "user ".$uid." score:".$score." should has no reward.error!!!\n";
                        return;
                    }
                }
                echo "check user:".$uid." done.send reward rightly.\n";
            }
        }
    }
    
    private static function sendToRewardCenter($uid,$reward,$value,$source)
    {
        $level = EnUser::getUserObj($uid)->getLevel();
        $rewardConfType2Type = array(
                RewardConfType::SILVER => RewardType::SILVER,
                RewardConfType::SILVER_MUL_LEVEL => RewardType::SILVER,
                RewardConfType::GOLD => RewardType::GOLD,
                RewardConfType::SOUL => RewardType::SOUL,
                RewardConfType::SOUL_MUL_LEVEL => RewardType::SOUL,
        		RewardConfType::EXP_MUL_LEVEL => RewardType::EXP_NUM,
                RewardConfType::JEWEL => RewardType::JEWEL,
                RewardConfType::ITEM => RewardType::ARR_ITEM_TPL,
                RewardConfType::HERO => RewardType::ARR_HERO_TPL,
                RewardConfType::EXECUTION=>RewardType::EXE,
                RewardConfType::STAMINA => RewardType::STAMINA,
        );
        $reward2Center = array();
        foreach($reward as $type => $rewardConf)
        {
            if(!isset($rewardConfType2Type[$type]))
            {
                throw new FakeException('no such reward conf type %d.reward %s.',$type,$reward);
            }
            $centerType = $rewardConfType2Type[$type];
            if($type == RewardConfType::SILVER_MUL_LEVEL ||
                    ($type == RewardConfType::SOUL_MUL_LEVEL) ||
        			($type == RewardConfType::EXP_MUL_LEVEL))
            {
                if(!isset($reward2Center[$centerType]))
                {
                    $reward2Center[$centerType] = 0;
                }
                $reward2Center[$centerType] +=  $rewardConf * $level;
            }
            else if($type == RewardConfType::SILVER ||
                    ($type == RewardConfType::SOUL))
            {
                if(!isset($reward2Center[$centerType]))
                {
                    $reward2Center[$centerType] = 0;
                }
                $reward2Center[$centerType] += $rewardConf;
            }
            else
            {
                $reward2Center[$centerType] = $rewardConf;
            }
        }
        if(empty($reward2Center))
        {
            return $reward2Center;
        }
        if($source == RewardSource::HERO_SHOP_INTEGRAL)
        {
            $reward2Center[RewardDef::EXT_DATA]['score'] = $value;
        }
        else if($source == RewardSource::HERO_SHOP_RANK)
        {
            $reward2Center[RewardDef::EXT_DATA]['rank'] = $value;
        }
        return $reward2Center;
    }
    
    
    public function getRankList($rankSize)
    {
        $data = new CData();
        $offset = 0;
        $arrRank = array();
        while(true)
        {
            $ret = $data->select(array(HeroShopDef::SQL_FIELD_UID,HeroShopDef::SQL_FIELD_SCORE,HeroShopDef::SQL_FIELD_SCORE_TIME))
                        ->from('t_hero_shop')
                        ->where(array(HeroShopDef::SQL_FIELD_SCORE,'>',0))
                        ->where(array(HeroShopDef::SQL_FIELD_SCORE_TIME,'>=',self::$startTime))
                        ->where(array(HeroShopDef::SQL_FIELD_REWARD_TIME,'<',self::$startTime))
                        ->orderBy(HeroShopDef::SQL_FIELD_SCORE, FALSE)
                        ->orderBy(HeroShopDef::SQL_FIELD_SCORE_TIME, TRUE)
                        ->orderBy(HeroShopDef::SQL_FIELD_UID, TRUE)
                        ->limit($offset,DataDef::MAX_FETCH)
                        ->query();
            $offset += DataDef::MAX_FETCH;
            $arrRank = array_merge($arrRank,$ret);
            if(count($ret) < DataDef::MAX_FETCH)
            {
                break;
            }
        }
        foreach($arrRank as $index => $userInfo)
        {
            $arrRank[$index]['rank'] = $index+1;
        }
        $arrRank = Util::arrayIndex($arrRank, 'uid');
        return $arrRank;
    }
    
    /**
     * 重置所有用户在卡包活动中的积分  积分时间 发奖时间
     */
    public function resetAllUserInAct($startTime,$reward)
    {
        $data = new CData();
        $allUser = array();
        $limit = DataDef::MAX_FETCH;
        $offset = 0;
        while(TRUE)
        {
            $tmpUser = $data->select(array(HeroShopDef::SQL_FIELD_UID))
                            ->from('t_hero_shop')
                            ->where(array(HeroShopDef::SQL_FIELD_UID,'>',0))
                            ->limit($offset, $limit)
                            ->query();
            $offset += $limit;
            $allUser = array_merge($allUser,$tmpUser);
            if(count($tmpUser) < $limit)
            {
                break;
            }
        }
        
        $score2User = array();
        $userNumInPreLv = intval(DataDef::MAX_FETCH/2) / (count($reward['score_lv'])+1);
        $scoreLv = array_keys($reward['score_lv']);
        $curLv = 0;
        $curLvNum = 0;
        $preLvScore = 0;
        $curLvScore = $scoreLv[$curLv];
        var_dump(count($allUser));
        var_dump($userNumInPreLv);
        foreach($allUser as $index => $userInfo)
        {
            if($curLvNum >= $userNumInPreLv)
            {
                $curLv = $curLv + 1;
                $curLvNum = 0;
                $preLvScore = 0;
                if(isset($scoreLv[$curLv]))
                {
                    $curLvScore = $scoreLv[$curLv];
                }
                else
                {
                    $curLvScore = $curLvScore * 2;
                }
                if(isset($scoreLv[$curLv - 1]))
                {
                    $preLvScore = $scoreLv[$curLv - 1];
                }
            }
            $uid = $userInfo[HeroShopDef::SQL_FIELD_UID];
            $userInfo[HeroShopDef::SQL_FIELD_SCORE_TIME] = $startTime;
            $userInfo[HeroShopDef::SQL_FIELD_REWARD_TIME] = 0;
            $userInfo[HeroShopDef::SQL_FIELD_SCORE] = rand($preLvScore,$curLvScore);
            $curLvNum++;
            HeroShopDao::updatePartShopInfo($uid, $userInfo);
            if(rand(0,1000) <= 500 && (count(self::$arrCheckUser) < intval(DataDef::MAX_FETCH/2)))
            {
                self::$arrCheckUser[] = $uid;
            }
            if(count(self::$arrCheckUser) >= intval(DataDef::MAX_FETCH/2))
            {
                break;
            }
        }
        var_dump(count(self::$arrCheckUser));
    }
    
    public function generateRewardInDb()
    {
        $heroShopConf = EnActivity::getConfByName(ActivityName::HERO_SHOP);
        $rewardTblId = $heroShopConf['data'][HeroShopBtstore::BT_REWARDTBL_ID];
        $arrRewardConf = EnActivity::getConfByName(ActivityName::HEROSHOP_REWARD);
        self::$rewardConf = $arrRewardConf['data'][$rewardTblId];
        return self::$rewardConf;
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */