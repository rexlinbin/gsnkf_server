<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: ReissueHeroShopReward.script.php 259698 2016-08-31 08:07:55Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/script/ReissueHeroShopReward.script.php $
 * @author $Author: BaoguoMeng $(zhangtiantian@babeltime.com)
 * @date $Date: 2016-08-31 08:07:55 +0000 (Wed, 31 Aug 2016) $
 * @version $Revision: 259698 $
 * @brief 
 *  
 **/
class ReissueHeroShopReward extends BaseScript
{
	/* (non-PHPdoc)
     * @see BaseScript::executeScript()
     */
    protected function executeScript ($arrOption)
    {
        // TODO Auto-generated method stub
        $buyHeroLogFile = $arrOption[0];
        $startTime = intval($arrOption[1]);
        $endTime = intval($arrOption[2]);
        $arrUserScore = $this->getUserScore($buyHeroLogFile);
        $rewardConf = $this->getRewardConf($startTime, $endTime);
        $maxRewardRank = 50;
        $minRewardScore = 60;
        $rank = 0;
        foreach($arrUserScore as $uid => $score)
        {
            $rank++;
            if(isset($arrOption[4]))
            {
                $testUid = intval($arrOption[4]);
                if($uid != $testUid)
                {
                    continue;
                }
            }
            if($rank > $maxRewardRank && $score < $minRewardScore)
            {
                break;
            }
            if($this->hasGotReward($uid, $endTime))
            {
                echo "user $uid has got reward\n";
                continue;
            }
            $heroShopInfo = HeroShopDao::getShopInfoByUid($uid,
                    array(HeroShopDef::SQL_FIELD_REWARD_TIME,
                            HeroShopDef::SQL_FIELD_SCORE_TIME,
                            HeroShopDef::SQL_FIELD_SCORE));
            if(empty($heroShopInfo))
            {
                echo "fatal error.user $uid has no heroshopinfo.\n";
                return;
            }
            $rewardTime = $heroShopInfo[HeroShopDef::SQL_FIELD_REWARD_TIME];
            $scoreTime = $heroShopInfo[HeroShopDef::SQL_FIELD_SCORE_TIME];
            $scoreInDb = $heroShopInfo[HeroShopDef::SQL_FIELD_SCORE];
            if($scoreTime >= $startTime   && $scoreTime <= $endTime)
            {
                if($scoreInDb != $score)
                {
                    echo "fatal error.user $uid scoreindb $scoreInDb not equal to score $score.\n";
                    return;
                }
                else
                {
                    echo "chech ok\n";
                }
            }
            $scoreReward = HeroShopLogic::getRewardByScore($score, $rewardConf);
            $rankReward = HeroShopLogic::getRewardByRank($rank, $rewardConf);
            echo sprintf("user $uid score $score rank $rank \n");
            if(!isset($arrOption[3]) || $arrOption[3] != "send")
            {
                continue;
            }
            $retScore = self::sendToRewardCenter($uid, $scoreReward, RewardSource::HERO_SHOP_INTEGRAL,$score);
            $retRank = self::sendToRewardCenter($uid, $rankReward, RewardSource::HERO_SHOP_RANK,$rank);
            Logger::info('HeroShop.rewardUser.uid %d score %d rank %d.scoreReward %s ,rankReward %s.',
                    $uid,$score,$rank,$retScore,$retRank);
        }
        echo "done\n";
    }
    
    private function getUserScore($buyHeroLogFile)
    {
        $file = fopen ( $buyHeroLogFile, 'r' );
        if (empty ( $file ))
        {
            echo sprintf ( "open file:%s failed\n", $buyHeroLogFile );
            exit ( 0 );
        }
        $arrUserScore = array();
        while ( ! feof ( $file ) )
        {
            $line = fgets ( $file );
            $line = trim ( $line );
            $arrField = explode(" ", $line);
            if(empty($arrField) || count($arrField) < 2)
            {
                continue;
            }
            $uid = intval($arrField[0]);
            $score = intval($arrField[1]);
            if(!isset($arrUserScore[$uid]))
            {
                $arrUserScore[$uid] = 0;
            }
            if($arrUserScore[$uid] < $score)
            {
                $arrUserScore[$uid] = $score;
            }
        }
        fclose($file);
        arsort($arrUserScore);
        return $arrUserScore;
    }
    
    private function getRewardConf($startTime,$endTime)
    {
        $arrRewardConf = ActivityConfDao::getByNameAndTime(ActivityName::HEROSHOP_REWARD, 
                $startTime, $endTime, ActivityDef::$ARR_CONF_FIELD);
        $heroShopConf = ActivityConfDao::getByNameAndTime(ActivityName::HERO_SHOP, 
                $startTime, $endTime, ActivityDef::$ARR_CONF_FIELD);
        $rewardTblId = $heroShopConf['va_data'][HeroShopBtstore::BT_REWARDTBL_ID];
        return $arrRewardConf['va_data'][$rewardTblId];
    }
    
    private function hasGotReward($uid,$rewardTime)
    {
        $data = new CData ();
        $ret = $data->select ( array(RewardDef::SQL_RID) )->from ( RewardDef::SQL_TABLE )
            ->where( RewardDef::SQL_UID , '=', $uid)
            ->where( RewardDef::SQL_SEND_TIME, '>', $rewardTime)
            ->where( RewardDef::SQL_SOURCE , 'IN', array(RewardSource::HERO_SHOP_RANK,RewardSource::HERO_SHOP_INTEGRAL) )
            ->query();
        if(!empty($ret))
        {
            return TRUE;
        }
        return FALSE;
    }
    
    public static function sendToRewardCenter($uid,$reward,$source,$value)
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
        EnReward::sendReward($uid, $source, $reward2Center);
        return $reward2Center;
    }

    
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */