<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $$Id: OlympicUtil.class.php 159789 2015-03-03 03:42:38Z ShijieHan $$
 * 
 **************************************************************************/

 /**
 * @file $$HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/olympic/OlympicUtil.class.php $$
 * @author $$Author: ShijieHan $$(ShijieHan@babeltime.com)
 * @date $$Date: 2015-03-03 03:42:38 +0000 (Tue, 03 Mar 2015) $$
 * @version $$Revision: 159789 $$
 * @brief 
 *  
 **/
class OlympicUtil
{
    /**
     * 根据顺序获取两个对战的人
     *
     * @param  $info array						数据库里面的32个人信息
     * @param  $start	int					对战数组里面需要查找的开始位置
     * @param  $offset	int					对战数组里面需要查找的跨度
     * @param  $curIndex int						需要获取的名次
     * @return
     */
    public static function getEnemy($info, $start, $offset, $curIndex)
    {
        Logger::debug("GetEnemy para is start : %d, offset : %d.", $start, $offset);
        $ret = array();
        for ($i = 0; $i < $offset; ++$i)
        {
            if (empty($info[$i + $start]))
            {
                continue;
            }
            foreach ($info as $key => $v)
            {
                if ($key == $i+$start && $v[OlympicRankDef::FIELD_FINAL_RANK] == $curIndex)
                {
                    // 记录跟最小值相同的人
                    $ret[] = $v;
                }
            }
        }
        if (count($ret) > 2)
        {
            Logger::fatal("Can not 2 people fight info : %s, start : %d, offset : %d!",
                $info, $start, $offset);
            throw new Exception('fake');
        }
        // 只有一个人, 轮空, 需要特别对待, 赋一个特殊的值，这样进入比赛的时候，就会得不到数据，直接占位
        else if (count($ret) == 1)
        {
            $ret[1][OlympicRankDef::FIELD_UID] = 0;
            $ret[1][OlympicRankDef::FIELD_SIGNUP_INDEX] = 0;
        }
        Logger::debug("GetEnemy ret is %s.", $ret);
        return $ret;
    }


    public static function fight($userInfo1, $userInfo2, $stageId)
    {
        $ret = false;
        // 执行一场战斗操作
        for ($i = 0; $i < 5; ++$i)
        {
            try
            {
                // 为了防止错误，多尝试几次
                $ret = self::doFight($userInfo1, $userInfo2, $stageId);
                break;
            }
            catch (Exception $e)
            {
                // 该干啥依旧干啥
                Logger::warning('Fight exeception:%s', $e->getMessage());
            }
        }
        // 如果五次都没执行成功
        if ($ret === false)
        {
            Logger::fatal("Execute fight fake!");
            throw new Exception('fake');
        }
    }

    /**
     * 执行一场 PvP
     *
     * @param int $curUserID					当前人的用户ID
     * @param int $index						报名位置
     * @param bool $isFinal						是否是决赛 (失败的时候，对方是否需要更新数据库)
     * @throws Exception
     */
    public static function doFight($userInfo1, $userInfo2, $stageId)
    {
        $nextRank = OlympicDef::$next[$stageId];
        $rankInst = OlympicRank::getInstance();
        $uid1 = $userInfo1[UserOlympicDef::FIELD_UID];
        $uid2 = $userInfo2[UserOlympicDef::FIELD_UID];
        if(empty($uid2))
        {
            $rankInst->setRank($uid1, $nextRank);
            $rankInst->save();
            return;
        }
        $userObj1 = Enuser::getUserObj($uid1);
        $userObj2 = Enuser::getUserObj($uid2);

        $battleFormation1 = self::getOlympicBattleFormation($uid1);
        $battleFormation2 = self::getOlympicBattleFormation($uid2);

        if($battleFormation1['fightForce'] >= $battleFormation2['fightForce'])
        {
            $attackerObj = $userObj1;
            $defenderObj = $userObj2;
            $attackerBattleFormation = $battleFormation1;
            $defenderBattleFormation = $battleFormation2;
        }
        else
        {
            $attackerObj = $userObj2;
            $defenderObj = $userObj1;
            $attackerBattleFormation = $battleFormation2;
            $defenderBattleFormation = $battleFormation1;
        }

        $atkRet = EnBattle::doHero($attackerBattleFormation,
                $defenderBattleFormation
        );
        $appraisal = $atkRet['server']['appraisal'];
        $isSuc = BattleDef::$APPRAISAL[$atkRet['server']['appraisal']] <= BattleDef::$APPRAISAL['D'];
        $brid = $atkRet['server']['brid'];
        if($isSuc)
        {
            $rankInst->setRank($attackerObj->getUid(), $nextRank);
        }
        else
        {
            $rankInst->setRank($defenderObj->getUid(), $nextRank);
        }
        Logger::info('atkUid:%d, defUid:%d, appraisal:%s, nextRank:%d', 
        			$attackerObj->getUid(), $defenderObj->getUid(), $atkRet['server']['appraisal'], $nextRank);
        $logInst = OlympicLog::getInstance($stageId);
        $attackerName = $attackerObj->getUname();
        $defenderName = $defenderObj->getUname();
        $logInfo = array(
                'attacker' => $attackerObj->getUid(),
                'defender' => $defenderObj->getUid(),
                'brid' => $brid,
                'result' => $appraisal
                );
        $logInst->addLog($logInfo);
        $logInfo['attackerName'] = $attackerName;
        $logInfo['defenderName'] = $defenderName;
        $logInfo['stage'] = $stageId;
        OlympicLogic::sendFilterMsgNow(PushInterfaceDef::OLYMPIC_BATTLE_RECORD, array($logInfo));
        $logInst->save();
        $rankInst->save();
    }

    public static function getOlympicBattleFormation($uid)
    {
        $last_champion = OlympicLogic::getLastChampion();
        $userVaBf = EnUser::getUserObj($uid)->getBattleFormation();
        if($last_champion != $uid)
        {
            return $userVaBf;
        }

        $winCont = OlympicLogic::getWinCont();  //连胜次数
        $effectiveChange = btstore_get()->CHALLENGE[OlympicDef::CHALLENGE_DEFAULT_ID][ChallengeCsvDef::EFFECTIVE_CHANGE];   //战斗力改变属性ID组
        $reduceEffective = btstore_get()->CHALLENGE[OlympicDef::CHALLENGE_DEFAULT_ID][ChallengeCsvDef::REDUCE_EFFECTIVE];  //连胜对应减少战斗力值
        foreach($userVaBf['arrHero'] as $pos => $heroInfo)
        {
            foreach($effectiveChange as $key => $val)
            {
                if($winCont > count($reduceEffective))
                {
                    $winCont = count($reduceEffective);
                }
                if(!isset($userVaBf['arrHero'][$pos][$val]))
                {
                    $userVaBf['arrHero'][$pos][$val] = 0;
                }
                $userVaBf['arrHero'][$pos][$val] -= $reduceEffective[$winCont - 1];
            }
        }

        return $userVaBf;
    }

}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */