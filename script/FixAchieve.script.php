<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: FixAchieve.script.php 125979 2014-08-11 04:13:31Z TiantianZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/script/FixAchieve.script.php $
 * @author $Author: TiantianZhang $(zhangtiantian@babeltime.com)
 * @date $Date: 2014-08-11 04:13:31 +0000 (Mon, 11 Aug 2014) $
 * @version $Revision: 125979 $
 * @brief 
 *  
 **/
class FixAchieve extends BaseScript
{
    private static $ACHIEVESTATUS_NOTACHIEVE = 0;
    private static $ACHIEVESTATUS_ACHIEVE = 1;
    private static $ACHIEVESTATUS_REWARD = 2;
    
	/* (non-PHPdoc)
     * @see BaseScript::executeScript()
     */
    protected function executeScript ($arrOption)
    {
        // TODO Auto-generated method stub
        if(count($arrOption) < 2)
        {
            self::usage();
            return;
        }
        $uid = intval($arrOption[0]);
        $achieveId = intval($arrOption[1]);
        if(empty($uid) || empty($achieveId))
        {
            self::usage();
            return;
        }
        RPCContext::getInstance()->setSession(UserDef::SESSION_KEY_UID, $uid);
        $userObj = EnUser::getUserObj($uid);
        if(empty($userObj))
        {
            echo "NO SUCH USER\n";
            self::usage();
            return;
        }
        $userInfo = array(
                'uid'=>$userObj->getUid(),
                'uname'=>$userObj->getUname(),
                'level'=>$userObj->getLevel(),
                'vip'=>$userObj->getVip(),
                );
        echo "USER INFO IS:\n";
        var_dump($userInfo);
        $fix = FALSE;
        if(isset($arrOption[2]) && ($arrOption[2] == 'fix'))
        {
            $fix = TRUE;
        }
        if($achieveId == AchieveDef::ARENA_RANK)
        {
            self::fixAchieveArenaRank($uid,$fix);
            echo "FIX ACHIEVETYPE ARENARANK DONE\n";
        }
        else
        {
            
        }
        
    }
    
    private static function fixAchieveArenaRank($uid,$fix)
    {
        //获取玩家的最高竞技场排名
        $rank = self::getBestArenaRank($uid);
        $finishNum = AchieveDef::MAX_BOSS_RANK - $rank;
        echo "USER $uid GET BEST ARENA RANK IS:$rank\n";
        $achieveType = AchieveDef::ARENA_RANK;
        $arrAchieveId = btstore_get()->ACHIEVE_SYSTEM['types'][$achieveType]->toArray();
        $achieveObj = new AchieveObj($uid);
        $arrAchieve = $achieveObj->getInfos();
        $needFix = FALSE;
        foreach($arrAchieveId as $achieveId => $achieveConfInfo)
        {
            $needNum = $achieveConfInfo['finish_num'];
            $status = self::$ACHIEVESTATUS_NOTACHIEVE;
            if(isset($arrAchieve[$achieveId]))
            {
                $status = $arrAchieve[$achieveId]['status'];
            }
            if($needNum <= $finishNum)
            {
                echo "USER $uid ACHIEVE ID $achieveId ACHIEVESTATUS IS $status\n";
                if($status == self::$ACHIEVESTATUS_NOTACHIEVE)
                {
                    echo "ERROR DATA.CAN FIX\n";
                    $needFix = TRUE;
                }
            }
            else
            {
                echo "USER $uid NOT ACHIEVE ID $achieveId ACHIEVESTATUS IS $status\n";
                if($status != self::$ACHIEVESTATUS_NOTACHIEVE)
                {
                    echo "ERROR DATA.CAN NOT FIX.PLEASE CHECK\n";
                }
            }
        }
        if($rank < AchieveDef::MAX_BOSS_RANK && $needFix && $fix)
        {
            echo "FIX ACHIEVE ARENARANK OF USER $uid\n";
            $achieveObj->updateType(AchieveDef::ARENA_RANK, 0, AchieveDef::MAX_BOSS_RANK - $rank);
            $achieveObj->commit();
        }
    }
    
    private static function getBestArenaRank($uid)
    {
        $data = new CData();
        $offset = 0;
        $limit = CData::MAX_FETCH_SIZE;
        $rank = AchieveDef::MAX_BOSS_RANK;
        while(TRUE)
        {
            $ret = $data->select(array(RewardDef::SQL_VA_REWARD))
                        ->from(RewardDef::SQL_TABLE)
                        ->where(array(RewardDef::SQL_UID,'=',$uid))
                        ->where(array(RewardDef::SQL_SOURCE,'=',RewardSource::ARENA_RANK))
                        ->limit($offset, $limit)
                        ->query();
            foreach($ret as $rewardInfo)
            {
                $rankTmp = AchieveDef::MAX_BOSS_RANK;
                if(isset($rewardInfo[RewardDef::SQL_VA_REWARD][RewardDef::EXT_DATA]['rank']))
                {
                    $rankTmp = $rewardInfo[RewardDef::SQL_VA_REWARD][RewardDef::EXT_DATA]['rank'];
                } 
                if($rankTmp < $rank)
                {
                    $rank = $rankTmp;
                }
            }
            if(count($ret) < CData::MAX_FETCH_SIZE)
            {
                break;
            }
            $offset += $limit;
        }
        return $rank;
    }

    private static function usage()
    {
        echo "USAGE:btscript game003 FixAchieve.script.php uid achieveid\n";
    }
    
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */