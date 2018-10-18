<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: DoBattleTest.php 74473 2013-11-13 06:28:07Z TiantianZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/ncopy/test/DoBattleTest.php $
 * @author $Author: TiantianZhang $(zhangtiantian@babeltime.com)
 * @date $Date: 2013-11-13 06:28:07 +0000 (Wed, 13 Nov 2013) $
 * @version $Revision: 74473 $
 * @brief 
 *  
 **/
class DoBattleTest extends PHPUnit_Framework_TestCase
{
    private static $uid;
    private static $copyId = 1;
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
        RPCContext::getInstance ()->setSession ( UserDef::SESSION_KEY_UID, self::$uid );
        self::passPreCopy();
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
    
    public function testBattle()
    {
        $copyInst = new NCopy();
        $bases = btstore_get()->COPY[self::$copyId]['base']->toArray();
        $baseLvs = array(
                BaseLevel::NPC,
                BaseLevel::SIMPLE,
                BaseLevel::NORMAL,
                BaseLevel::HARD
                );
        foreach($bases as $index => $baseId)
        {
            if(empty($baseId))
            {
                continue;
            }
            foreach($baseLvs as $index => $baseLv)
            {
                $lvName = CopyConf::$BASE_LEVEL_INDEX[$baseLv];
                if(!isset(btstore_get()->BASE[$baseId][$lvName]))
                {
                    continue;
                }
                $copyInst->enterBaseLevel(self::$copyId, $baseId, $baseLv);
                $atkInfo = AtkInfo::getInstance()->getAtkInfo();
                $status = AtkInfo::getInstance()->getAtkInfoStatus();
                if($status != ATK_INFO_STATUS::START)
                {
                    throw new FakeException('enter baseLevel.atkinfo error.%s.',$atkInfo);
                }
                $armies = btstore_get()->BASE[$baseId][$lvName][$lvName.'_army_arrays']->toArray();
                foreach($armies as $index => $army)
                {
                    $ret = $this->doBattle(self::$copyId, $baseId, $baseLv, $armies, $army);
                    if($ret == 'fail')
                    {
                        echo 'fail';
                        return 'fail';
                    }
                }
            }
        }
        $ret = $copyInst->getCopyList();
        var_dump($ret);
    }
    
    private function doBattle($copyId,$baseId,$baseLevel,$armies,$army)
    {
        $userObj = EnUser::getUserObj();
        $presoul = $userObj->getSoul();
        $preexp = $userObj->getAllExp();
        $presilver = $userObj->getSilver();
        $preHeroNum = $userObj->getHeroManager()->getHeroNum();
        $preRoundNum = NCopyAtkInfo::getInstance()->getRoundNum();
        $preCostHp = NCopyAtkInfo::getInstance()->getCostHp();
        $copyInst = new NCopy();
        $heroList = array();
        if($baseLevel == BaseLevel::NPC)
        {
            $formation    =  EnFormation::getFormationObj(self::$uid)->getFormation();
            $fmt    =    array();
            $teamId = intval(btstore_get()->ARMY[$army]['npc_team_id']);
            // 如果没有找到这个NPC部队信息，则出错返回
            $mstFmt = btstore_get()->TEAM[$teamId]['fmt'];
            $hidInFmt    =    array();
            foreach($mstFmt as $pos => $mstId)
            {
                if(empty($mstId))
                {
                    $fmt[$pos]    =    0;
                    continue;
                }
                if(intval($mstId) == 1)
                {
                    foreach($formation as $positon => $hid)
                    {
                        if(in_array($hid, $hidInFmt) == TRUE)
                        {
                            continue;
                        }
                        $fmt[$pos]    =    $hid;
                        $hidInFmt[] = $hid;
                        break;
                    }
                }
                else
                {
                    $fmt[$pos]    =    0;
                }
            }
            $heroList = $fmt;
        }
        $ret = $copyInst->doBattle($copyId, $baseId, $baseLevel, $army, array(), $heroList);
        $appraisal = $ret['appraisal'];
        $fail = FALSE;
        if(BattleDef::$APPRAISAL[$appraisal] > BattleDef::$APPRAISAL['D'])
        {
            $fail = TRUE;
        }
        //检测atkInfo        //basePrg        //status
        $atkInfo = AtkInfo::getInstance()->getAtkInfo();
        $basePrg = AtkInfo::getInstance()->getBasePrg();
        if(!$fail && (!isset($basePrg[$army]) || (empty($basePrg[$army]))))
        {
            throw new FakeException('atkinfo error.dobattle ret %s.atkinfo %s.',$ret,$atkInfo);
        }
        $pass = FALSE;
        if($army == end($armies) && (!$fail))
        {
            $pass = TRUE;
        }
        $status = AtkInfo::getInstance()->getAtkInfoStatus();
        if($pass)
        {
            if($status != ATK_INFO_STATUS::PASS)
            {
                throw new FakeException('atkinfo error. dobattle ret %s.atkinfo %s.',$ret,$atkInfo);
            }
        }
        else if($fail)
        {
            if($status != ATK_INFO_STATUS::FAIL)
            {
                throw new FakeException('atkinfo error. dobattle ret %s.atkinfo %s.',$ret,$atkInfo);
            }
        }
        else
        {
            if($status != ATK_INFO_STATUS::ATTACK)
            {
                throw new FakeException('atkinfo error. dobattle ret %s.atkinfo %s.',$ret,$atkInfo);
            }
        }
        echo "attack baseId ".$baseId." baseLevel ".$baseLevel." army ".$army." result ".$appraisal."\n";
        if($fail)
        {
            return 'fail';
        }
        else
        {
            $tmp = $this->getFromBattleRet($ret);
            $round = $tmp[0];
            $costHp = $tmp[1];
            $this->assertTrue((NCopyAtkInfo::getInstance()->getRoundNum() == ($preRoundNum+$round)),'round num is not right;');
            $this->assertTrue((NCopyAtkInfo::getInstance()->getCostHp() == ($preCostHp+$costHp)),'costhp is not right');
        }
            
        if(!$pass)
        {
            return;
        }
        EnUser::release(self::$uid);
        CData::$QUERY_CACHE = NULL;
        $userObj = EnUser::getUserObj();
        $reward = $ret['reward'];
        $extraReward = $ret['extra_reward'];
        $afterSoul = $userObj->getSoul();
        $afterSilver = $userObj->getSilver();
        $afterExp = $userObj->getAllExp();
        $afterHeroNum = $userObj->getHeroManager()->getHeroNum();
        $dropHero = AtkInfo::getInstance()->getDropHero();
        $rewardSilver = 0;
        $rewardSoul = 0;
        //奖励验证
        if(isset($reward['silver']))
        {
            $rewardSilver += $reward['silver'];
        }        
        if(isset($extraReward['silver']))
        {
            $rewardSilver += $extraReward['silver'];
        }
        $this->assertTrue(($presilver+$rewardSilver == $afterSilver),
                "reward silver error. pre $presilver.add ".$rewardSilver.".after $afterSilver.");
        if(isset($reward['soul']))
        {
            $rewardSoul += $reward['soul'];
        }
        if(isset($extraReward['soul']))
        {
            $rewardSoul += $extraReward['soul'];
        }
        $this->assertTrue(($presoul+$rewardSoul == $afterSoul),
                "reward soul error. pre $presoul.add ".$rewardSoul.".after $afterSoul.");
        if(isset($reward['exp']))
        {
            $this->assertTrue(($preexp+$reward['exp'] == $afterExp),
                    "reward exp error. pre $preexp.add ".$reward['exp'].".after $afterExp.");
        }
        $extraRewardHeroNum = 0;
        if(isset($extraReward[DropDef::DROP_TYPE_STR_HERO]))
        {
            foreach($extraReward[DropDef::DROP_TYPE_STR_HERO] as $htid => $num)
            {
                $extraRewardHeroNum + $num;
            }
        }
        $this->assertTrue(($preHeroNum + count($dropHero) + $extraRewardHeroNum == $afterHeroNum),"add hero fail.preheronum $preHeroNum dropheronum ".count($dropHero). "extraRewardNum ".$extraRewardHeroNum." afterheronum $afterHeroNum");
    }
    
    private function decodeBattle($data)
    {
        $data = str_replace(array("\n", "\t", " "), "", $data);
        $data = base64_decode($data);
        $data = gzuncompress($data);
        $data = chr(0x11) . $data;
        $arrData = amf_decode($data, 7);
        return $arrData;
    }
    
    private function getFromBattleRet($ret)
    {
        $fightRet = $this->decodeBattle($ret['fightRet']);
        $battleSize = count($fightRet['battle']);
        $roundNum = $fightRet['battle'][$battleSize-1]['round'];
        $costHp = $fightRet['team1']['totalHpCost'];
        return array($roundNum,$costHp);
    } 
    
    private static function passPreCopy()
    {
        $copyId = self::$copyId;
        $baseLevel = 1;
        if(!isset(btstore_get()->COPY[$copyId]))
        {
            return 'nosuchcopy';
        }
        $ncopy		=	MyNCopy::getInstance();
        $copyObj	=	$ncopy->getCopyObj($copyId);
        $preCopies    =    array();
        $preBase    =    btstore_get()->COPY[$copyId]['base_open'];
        while (!empty($preBase))
        {
            $preCopy    =    btstore_get()->BASE[$preBase]['copyid'];
            if (!empty($preCopy))
            {
                $preCopies[] = $preCopy;
            }
            $preBase    =    btstore_get()->COPY[$preCopy]['base_open'];
        }
        $copies    =    array_reverse($preCopies);
        foreach($copies as $copy)
        {
            Logger::trace('pass copy %s.',$copy);
            self::passNCopy($copy,$baseLevel);
        }
        return $ncopy->getAllCopies();
    } 
   
    /**
     * 副本的所有据点通关简单难度     如果有据点通关了普通或者困难难度   不改变其状态
     * @param int $uid
     * @param int $copyId
     */
    private static function passNCopy($copyId,$baseLevel = 1)
    {
        $uid    =    RPCContext::getInstance()->getUid();
        $ncopy		=	MyNCopy::getInstance();
        $copyObj	=	$ncopy->getCopyObj($copyId);
        if(isset(btstore_get()->COPY[$copyId]) == FALSE)
        {
            throw new FakeException('no such copy with copyid %s.',$copyId);
        }
        if(empty($copyObj))
        {
            $preBase    =    btstore_get()->COPY[$copyId]['base_open'];
            if(!empty($preBase))
            {
                $preCopy    =    btstore_get()->BASE[$preBase]['copyid'];
                $preCopy = intval($preCopy);
                $preCopyObj =    $ncopy->getCopyObj($preCopy);
                if(empty($preCopyObj) || ($preCopyObj->isCopyPassed() == FALSE))
                {
                    throw new FakeException('preCopy %s is not passed', $preCopy);
                }
            }
            $va_copy_info['progress'] = array();
            //创建新的副本对象
            $copyObj = MyNCopy::createNewObj($uid, $copyId,$va_copy_info);
        }
        $bases		=	btstore_get()->COPY[$copyId]['base'];
        $addScore	=	0;
        $baseNum    =    0;
        $score = 0;
        foreach($bases as $baseId)
        {
            if(empty($baseId))
            {
                break;
            }
            $level = $baseLevel;
            $level = self::getDefeatLevel($baseId, $level);
            $baseNum ++;
            $baseStatus = $level + 2;
            $copyObj->updBaseStatus($baseId, $baseStatus);
            $score += $level;
        }
        $copyObj->setScore($score);
        $score	=	btstore_get()->COPY[$copyId]['total_star'];
        $copyObj	->	addScore($addScore);
        $ncopy->saveCopy($copyId, $copyObj->getCopyInfo());
        $newSession = RPCContext::getInstance()->getSession(CopySessionName::COPYLIST);
        return $copyObj->getCopyInfo();
    }
    
    private static function getDefeatLevel($baseId,$level)
    {
        while(true)
        {
            $lvName = CopyConf::$BASE_LEVEL_INDEX[$level];
            if(!isset(btstore_get()->BASE[$baseId][$lvName]))
            {
                $level--;
            }
            else
            {
                break;
            }
        }
        return $level;
    }
    
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */