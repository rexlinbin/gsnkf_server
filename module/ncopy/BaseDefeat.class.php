<?php
/***************************************************************************
 *
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: BaseDefeat.class.php 254620 2016-08-03 12:54:18Z GuohaoZheng $
 *
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/ncopy/BaseDefeat.class.php $
 * @author $Author: GuohaoZheng $(zhangtiantian@babeltime.com)
 * @date $Date: 2016-08-03 12:54:18 +0000 (Wed, 03 Aug 2016) $
 * @version $Revision: 254620 $
 * @brief
 *
 **/
/**
 * 处理据点战斗模式(以据点为单位的战斗)下的battleCallBack,doneBattle,doBattle中共有的部分
 * @author dell
 *
 */
class BaseDefeat
{
    private static $curModule = self::MODULE_INVALID;
    const MODULE_INVALID = -1;

    //不管是否输赢是否通关都有的奖励
    public static $rewardFuncOnAtk = array(
            BattleType::GOLD_TREE => "GoldTree::getSilverReward",
            );

    //打赢了(不一定通关)的卡牌奖励
    public static $HeroRewardFuncOnAtkSuc = array(
            BattleType::NCOPY => 'BaseDefeat::getHeroRewardOnAtkSuc',
            BattleType::ECOPY => 'BaseDefeat::getHeroRewardOnAtkSuc',
            );
    
    //掉落绿白卡转化为将魂的模块
    public static $DropGreenWhiteHero2Soul = array(
    		BattleType::NCOPY => true,
    		BattleType::ECOPY => true,
    );

    //通关奖励
    public static $RewardFuncOnPass = array(
            BattleType::NCOPY => "NCopyLogic::getBattleReward",
            BattleType::ECOPY => "ECopyLogic::getBattleReward",
            BattleType::EXP_HERO => "ExpHero::getPassReward",
            BattleType::EXP_TREASURE => "ExpTreasure::getPassReward",
            BattleType::DESTINY => "ADestiny::getPassReward",
            );

    //条件通关奖励（必须通关）
    public static $RewardFuncOnPassCond = array(
            BattleType::TOWER => "TowerLogic::getBattleReward",
            BattleType::SPECAIL_TOWER => "TowerLogic::getSpecailBattleReward",
            BattleType::HELL_TOWER => "TowerLogic::getHellBattleReward",
            );

    public static $moduleDoneBattle = array(
            BattleType::NCOPY=>"NCopyLogic::doneBattle",
            BattleType::ECOPY=>"ECopyLogic::doneBattle",
            BattleType::GOLD_TREE=>"ACopyObj::doneBattle",
            BattleType::EXP_TREASURE=>"ExpTreasure::doneBattle",
            BattleType::EXP_HERO=>"ExpHero::doneBattle",
            BattleType::TOWER=>"TowerLogic::doneBattle",
            BattleType::SPECAIL_TOWER => "TowerLogic::doneSpecailBattle",
    		BattleType::HCOPY => "HCopyLogic::doneBattle",
            BattleType::DESTINY => "ADestiny::doneBattle",
            BattleType::HELL_TOWER => "TowerLogic::doneHellBattle",
            );
    //有额外掉落的模块
    public static $EXTRADROP_MODULE = array(
            BattleType::ECOPY,
            BattleType::NCOPY
            );
    //有通关条件的模块
    public static $MODULE_HAS_PASS_CONDITION = array(
            BattleType::NCOPY => true,
            BattleType::TOWER => true,
            BattleType::SPECAIL_TOWER => true,
            BattleType::HELL_TOWER => TRUE,
            );

    /**
     *
     * @param array $atkRet
     * @param string $module NCopy\ECopy\Tower\ACopy
     */
    public static function battleCallBack($atkRet)
    {
        Logger::trace('BaseDefeat battleCallBack');
        $enemyId = $atkRet['uid2'];
        $copyId  = AtkInfo::getInstance()->getCopyId();
        $baseId  = AtkInfo::getInstance()->getBaseId();
        $baseLv  = AtkInfo::getInstance()->getBaseLv();

        $module  = self::getModule();
        $reward = array();
        if($module == self::MODULE_INVALID)
        {
            throw new FakeException('copyid %d is not a valid module type.please check this copy.',$copyId);
        }
        //打完不管输赢都有奖励
        if(isset(self::$rewardFuncOnAtk[$module]))
        {
            $reward = call_user_func(self::$rewardFuncOnAtk[$module],$atkRet);
        }
        if(isset(self::$MODULE_HAS_PASS_CONDITION[$module]))
        {
            NCopyAtkInfo::getInstance()->statisticOnDoneBattle($atkRet);
        }
        //输了
        if (btstore_get()->ARMY[$enemyId]['force_pass'] != CopyDef::FORCE_PASS
                &&(BattleDef::$APPRAISAL[$atkRet['appraisal']] > BattleDef::$APPRAISAL['D']))
        {
            Logger::trace('fail');
        }
        else //赢了
        {
            //卡牌掉落奖励
            if(isset(self::$HeroRewardFuncOnAtkSuc[$module]))
            {
                $reward = array_merge($reward,call_user_func(self::$HeroRewardFuncOnAtkSuc[$module],$atkRet));
                Logger::trace('HeroRewardFuncOnAtkSuc %s',$reward);
            }
            if(CopyUtil::isLastArmyofBase($baseId, $baseLv, $enemyId) == TRUE)
            {
                if(isset(self::$RewardFuncOnPass[$module]))
                {
                    $otherReward = call_user_func(self::$RewardFuncOnPass[$module],$atkRet);
                    $reward = array_merge($reward,$otherReward);
                    Logger::trace('RewardFuncOnPass %s',$reward);
                }
                if(isset(self::$RewardFuncOnPassCond[$module]))
                {
                    if(CopyUtil::passCondition($baseId, $baseLv, $atkRet) == "ok")
                    {
                        $passCondReward = call_user_func(self::$RewardFuncOnPassCond[$module],$atkRet);
                        $reward = array_merge($reward,$passCondReward);
                    }
                }
            }
        }

        return $reward;
    }

    private static function getHeroRewardOnAtkSuc($atkRet)
    {
        $reward = array();
        $reward['hero']    =    EnBattle::dropHeroOnDoneBt($atkRet);
        AtkInfo::getInstance()->addDropHero($reward['hero']);
        return $reward;
    }
    /**
     * 普通副本、精英副本、活动副本可以通过copyId获取模块类型
     * 其他模块如爬塔，它的copyid（塔层）同普通副本是重复的，所以此时只能在enterLevel时initAtkInfo中写入BattleType参数
     * @param int $copyId
     */
    private static function getModule()
    {
        return self::$curModule;
    }

    public static function getNeedExec($baseId,$baseLv,$module)
    {
        switch($module)
        {
            case BattleType::NCOPY:
            case BattleType::EXP_HERO:
            case BattleType::GOLD_TREE:
            case BattleType::EXP_TREASURE:
            case BattleType::HCOPY:
                $lvName		  	= CopyConf::$BASE_LEVEL_INDEX[$baseLv];
                return intval(btstore_get()->BASE[$baseId][$lvName][$lvName.'_need_power']);
                break;
            case BattleType::ECOPY:
                $copyId     = AtkInfo::getInstance()->getCopyId();
                return intval(btstore_get()->ELITECOPY[$copyId]['need_power']);
                break;
            case BattleType::TOWER:
                return 0;
                break;
        }
    }
    /**
     * 普通副本和精英副本的全局掉落
     * @param int $baseId
     * @param int $num  打据点次数    副本扫荡时num>1
     * @return array
     * <code>
     * [
	 *     item=>array
	 *     [
	 *         ItemTmplId=>num
	 *     ]
	 *     hero=>array
	 *     [
	 *         Htid=>num
	 *     ]
	 *     silver=>int
	 *     soul=>int
	 *     treasFrag=>array
	 *     [
	 *         TreasFragTmplId=>num
	 *     ]
	 * ]
     * <code>
     */
    public static function getExtraRewardByBaseId($baseId,$num=1)
    {
        $arrDropId = array();
        $arrDropIdCnf = btstore_get()->BASE[$baseId]['extra_droptbl_ids']->toArray();
        for($i=0;$i<$num;$i++)
        {
            $arrDropId = array_merge($arrDropId,$arrDropIdCnf);
        }
        $uid = RPCContext::getInstance()->getUid();
        $getReward = EnUser::drop($uid, $arrDropId);
        return $getReward;
    }


    public static function doneBattle($atkRet,$module)
    {
        Logger::trace('BaseDefeat doneBattle');
        $team1	= $atkRet['server']['team1'];
        $armyId	= $atkRet['server']['uid2'];
        $brid = $atkRet['server']['brid'];
        $baseId = AtkInfo::getInstance()->getBaseId();
        $baseLv = AtkInfo::getInstance()->getBaseLv();
        $user = Enuser::getUserObj();
        $atkRet['server']['pass'] = FALSE;//默认没通过据点
        $atkRet['server']['fail'] = FALSE;//默认没输
        $normalReward = array();
        $extraReward = array();
        AtkInfo::getInstance()->refreshHpInfo2Attackinfo($team1);
        if($module == BattleType::GOLD_TREE)//如果是摇钱树  强制胜利  胜利才能发奖励 (self::dobattle中根据atkinfostatus发放奖励)
        {
            $atkRet['server']['appraisal'] = 'D';
        }
        if (btstore_get()->ARMY[$armyId]['force_pass'] != CopyDef::FORCE_PASS
                &&(BattleDef::$APPRAISAL[$atkRet['server']['appraisal']] > BattleDef::$APPRAISAL['D']))
        {
            $atkRet['server']['fail'] = TRUE;
            AtkInfo::getInstance()->setAtkInfoStatus(ATK_INFO_STATUS::FAIL);
            AtkInfo::getInstance()->setBasePrgOnDefeatArmy($armyId, ATK_INFO_ARMY_STATUS::DEFEAT_FAIL);
        }
        else
        {
            AtkInfo::getInstance()->setBasePrgOnDefeatArmy($armyId, $brid);
            if(CopyUtil::isLastArmyofBase($baseId, $baseLv, $armyId) == FALSE)
            {
                AtkInfo::getInstance()->setAtkInfoStatus(ATK_INFO_STATUS::ATTACK);
            }
            else
            {
                $needExec = self::getNeedExec($baseId, $baseLv, $module);
                if($user->subExecution($needExec) == false)
                {
                    throw new FakeException('module %s.no execution to fight baseid %s. baselevel %s.need %s.now %s.',$module,$baseId,$baseLv,$needExec,$user->getCurExecution());
                }
                AtkInfo::getInstance()->setAtkInfoStatus(ATK_INFO_STATUS::PASS);
                $atkRet['server']['pass'] = TRUE;
            }
        }
        //通关了 发送奖励   把攻击前面所有部队的奖励都发了
        if($atkRet['server']['pass'])
        {
            $gotReward = $atkRet['server']['reward'];
            if(isset(self::$HeroRewardFuncOnAtkSuc[$module]))
            {
                $gotReward['hero'] = AtkInfo::getInstance()->getDropHero();
                
                // 绿白卡转化为将魂
                if (isset(self::$DropGreenWhiteHero2Soul[$module]) && self::$DropGreenWhiteHero2Soul[$module]) 
                {
                	list($gotReward['hero'], $addSoul) = CopyUtil::dropHeroByQualityFilter($gotReward['hero'], array(HERO_QUALITY::WHITE_HERO_QUALITY, HERO_QUALITY::GREEN_HERO_QUALITY));
                	if ($addSoul > 0)
                	{
                		if (!isset($gotReward['soul']))
                		{
                			$gotReward['soul'] = 0;
                		}
                		$gotReward['soul'] += $addSoul;
                		$atkRet['server']['reward']['hero'] = $gotReward['hero'];
                	}
                }
                
            }
            $normalReward	=	CopyUtil::rewardUser($gotReward);
            if(isset($atkRet['server']['reward']['hero']))
            {
                $normalReward['hero'] = $atkRet['server']['reward']['hero'];
            }
            if(in_array($module, self::$EXTRADROP_MODULE))
            {
                $extraReward = self::getExtraRewardByBaseId($baseId);
            }
        }
        else
        {
            $normalReward = $atkRet['server']['reward'];
            
            // 多波部队的时候，这是传给前端显示的，直接把符合条件的武将去掉，当多波部队打成功以后，会将这些武将转化为将魂
            if (!empty($normalReward['hero'])) 
            {
            	if (isset(self::$DropGreenWhiteHero2Soul[$module]) && self::$DropGreenWhiteHero2Soul[$module])
            	{
            		list($normalReward['hero'], $addSoul) = CopyUtil::dropHeroByQualityFilter($normalReward['hero'], array(HERO_QUALITY::WHITE_HERO_QUALITY, HERO_QUALITY::GREEN_HERO_QUALITY));
            	}
            }
        }
        $user->addFightCd(CopyConf::$FIGHT_CD_TIME);
//         $hpInfo	= AtkInfo::getInstance()->getHpInfo();
        //调用每个模块单独的doneBattle   doneBattle会判断是否有新的副本开启   并且进行各个UPDATE
        $newCorB = array();
        if(!isset(self::$moduleDoneBattle[$module]))
        {
            Logger::warning('module %d has no DoneBattle',$module);
        }
        else
        {
            $newCorB = call_user_func(self::$moduleDoneBattle[$module],$atkRet['server']);
        }
        if(!is_array($newCorB))
        {
            throw new FakeException('%s dobattle fail reason %s.',$module,$newCorB);
        }
        return array(
                'reward'=>$normalReward,'extra_reward'=>$extraReward,
                'err'=>'ok','cd' => $user->getFightCDTime()-Util::getTime(),
                'appraisal' => $atkRet['server']['appraisal'],
//                 'curHp' => $hpInfo,
                'fightRet' => $atkRet['client'],
                'newcopyorbase'=>$newCorB
        );
    }

    public static function doBattle($module,$armyId,$baseId,$fmt=array(),
            $baseLv=BaseLevel::SIMPLE, $isnpc = false, $herolist = null, $arrMonsterLv = array())
    {
        Logger::trace('BaseDefeat doBattle');
        self::$curModule = $module;
        $uid = RPCContext::getInstance()->getUid();
        $btInfo = CopyUtil::getBattleArr($armyId,$fmt,$baseLv,$uid,$isnpc,
                $herolist,$arrMonsterLv,$module);
        if(CopyUtil::checkFormation($btInfo['playerArr']['arrHero']) == FALSE)
        {
            $hpInfo = array();
            foreach($btInfo['playerArr']['arrHero'] as $heroInfo)
            {
                $hpInfo[$heroInfo[PropertyKey::HID]] = $heroInfo[PropertyKey::CURR_HP];
            }
            throw new FakeException('all hero in formation has no Hp.Hpinfo:%s',$hpInfo);
        }
        $btType = btstore_get()->ARMY[$armyId]['fight_type'];
        $callback = array("BaseDefeat","battleCallBack");
        $winCon = CopyUtil::getVictoryConditions($armyId);
        $extraInfo = CopyUtil::getExtraBtInfo($armyId, $module,$baseId);
        $atkRet = EnBattle::doHero($btInfo['playerArr'],$btInfo['enemyArr'],$btType,$callback,$winCon,$extraInfo);
        $ret = self::doneBattle($atkRet,$module);
        self::sendChatOnGetReward($ret['extra_reward'], $ret['reward']);
        return $ret;
    }

    public static function sendChatOnGetReward($extraReward,$normalReward)
    {
        if(!empty($extraReward))
        {
            $arrItem = array();
            if(isset($extraReward[DropDef::DROP_TYPE_STR_TREASFRAG]))
            {
                $arrItem = $arrItem + $extraReward[DropDef::DROP_TYPE_STR_TREASFRAG];
            }
            if(isset($extraReward[DropDef::DROP_TYPE_STR_ITEM]))
            {
                $arrItem = $arrItem + $extraReward[DropDef::DROP_TYPE_STR_ITEM];
            }
            ChatTemplate::sendGodGiveItem(EnUser::getUserObj()->getTemplateUserInfo(), $arrItem);
        }
        if(isset($normalReward['item']))
        {
            $arrItem = array();
            foreach($normalReward['item'] as $index => $itemInfo)
            {
                if(!isset($arrItem[$itemInfo[ItemDef::ITEM_SQL_ITEM_TEMPLATE_ID]]))
                {
                    $arrItem[$itemInfo[ItemDef::ITEM_SQL_ITEM_TEMPLATE_ID]] = 0;
                }
                $arrItem[$itemInfo[ItemDef::ITEM_SQL_ITEM_TEMPLATE_ID]] += $itemInfo[ItemDef::ITEM_SQL_ITEM_NUM];
            }
            ChatTemplate::sendCopyDropItem(EnUser::getUserObj()->getTemplateUserInfo(), $arrItem);
        }
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
