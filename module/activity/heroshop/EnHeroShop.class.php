<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: EnHeroShop.class.php 259698 2016-08-31 08:07:55Z BaoguoMeng $
 * 
 **************************************************************************/
 
 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/heroshop/EnHeroShop.class.php $
 * @author $Author: BaoguoMeng $(zhangtiantian@babeltime.com)
 * @date $Date: 2016-08-31 08:07:55 +0000 (Wed, 31 Aug 2016) $
 * @version $Revision: 259698 $
 * @brief 
 *  
 **/
class EnHeroShop
{
    public static function readHeroShopCSV($arrData)
    {
        $ZERO = 0;
        $arrConfKey = array(
                HeroShopBtstore::BT_FIELD_ID => $ZERO,
                'act_img'=>++$ZERO,
                'act_profile'=>++$ZERO,
                HeroShopBtstore::BT_FREE_GETSCORE=>++$ZERO,
                HeroShopBtstore::BT_GOLD_GETSCORE=>++$ZERO,
                HeroShopBtstore::BT_GOLDBUY_NEEDGOLD=>++$ZERO,
                HeroShopBtstore::BT_FREE_BUY_CD=>++$ZERO,
                HeroShopBtstore::BT_REWARDTBL_ID => ++$ZERO,
                HeroShopBtstore::BT_PRESCORE_GET_FREENUM=>++$ZERO,
                HeroShopBtstore::BT_FREE_BUY_SHOP_ID=>++$ZERO,
                HeroShopBtstore::BT_GOLD_BUY_SHOP_ID=>++$ZERO,
                'goods_list'=>++$ZERO,
                HeroShopBtstore::BT_ACT_CLOSE_DELAY=>++$ZERO
                );
        $confList = array();
        foreach ($arrData as $data)
        {
            if ( empty($data) || empty($data[0]) )
            {
                break;
            }
            $conf = array();
            foreach ( $arrConfKey as $key => $index )
            {
                switch($key)
                {
                    case 'act_img':
                    case 'act_profile':
                    case 'goods_list':
                        break;
                    default:
                        $conf[$key] = intval($data[$index]);
                }
            }
            if(!empty($confList))
            {
                Logger::warning('what is wrong with chen.the row num is lagger than 1');
            }
            $confList = $conf;
        }
        return $confList;
    }
    
    public static function readRewardCSV($arrData)
    {
        $ZERO = 0;
        $arrConfKey = array(
                'id' => $ZERO,
                'score_lv1'=>++$ZERO,
                'score_reward1'=>++$ZERO,
                'score_lv2'=>++$ZERO,
                'score_reward2'=>++$ZERO,
                'score_lv3'=>++$ZERO,
                'score_reward3'=>++$ZERO,
                'score_lv4'=>++$ZERO,
                'score_reward4'=>++$ZERO,
                'score_lv5'=>++$ZERO,
                'score_reward5'=>++$ZERO,
                'rank_lv_size'=>++$ZERO,
                'rank_lv1'=>++$ZERO,
                'rank_reward1'=>++$ZERO,
                'rank_score1'=>++$ZERO,
                'rank_lv2'=>++$ZERO,
                'rank_reward2'=>++$ZERO,
                'rank_score2'=>++$ZERO,
                'rank_lv3'=>++$ZERO,
                'rank_reward3'=>++$ZERO,
                'rank_score3'=>++$ZERO,
                'rank_lv4'=>++$ZERO,
                'rank_reward4'=>++$ZERO,
                'rank_score4'=>++$ZERO,
                'rank_lv5'=>++$ZERO,
                'rank_reward5'=>++$ZERO,
                'rank_score5'=>++$ZERO,
        );
        $confList = array();
        foreach ($arrData as $data)
        {
            if ( empty($data) || empty($data[0]) )
            {
                break;
            }
            $conf = array();
            foreach ( $arrConfKey as $key => $index )
            {
                if($key === 'id' || ($key === 'rank_lv_size') 
                        || (strpos($key,'score_lv') === 0) 
                        || (strpos($key, 'rank_lv') === 0) 
                        || (strpos($key, 'rank_score') === 0))
                {
                    $conf[$key] = intval($data[$index]);
                }
                else
                {
                    $tmp = self::str2Array($data[$index], ',');
                    foreach($tmp as $tmpIndex => $rewardConf)
                    {
                        $reward = array_map('intval', self::str2Array($rewardConf, '|'));
                        if(count($reward) != 3)
                        {
                            trigger_error('reward conf field  num must be 3,for example 2|0|1000.but this conf is '.$rewardConf);
                        }
                        $type = $reward[0];
                        $rewardId = $reward[1];
                        $rewardNum = $reward[2];
                        switch($type)
                        {
                            case RewardConfType::SILVER:
                            case RewardConfType::GOLD:
                            case RewardConfType::SOUL:
                            case RewardConfType::STAMINA:
                            case RewardConfType::EXECUTION:
                            case RewardConfType::SILVER_MUL_LEVEL:
                            case RewardConfType::SOUL_MUL_LEVEL:
                            case RewardConfType::EXP_MUL_LEVEL:
                            case RewardConfType::JEWEL:
                                if(!empty($rewardId))
                                {
                                    throw new ConfigException('error config.the rewardid should be 0. '.$data[$index].' for type '.$type);
                                }
                                if(!isset($conf[$key][$type]))
                                {
                                    $conf[$key][$type] = 0;
                                }
                                $conf[$key][$type] += $rewardNum;
                                break;
                            case RewardConfType::HERO:
                                if(!isset(btstore_get()->HEROES[$rewardId]))
                                {
                                    throw new ConfigException('no such hero with htid %d',$rewardId);
                                }
                                if(!isset($conf[$key][$type][$rewardId]))
                                {
                                    $conf[$key][$type][$rewardId] = 0;
                                }
                                $conf[$key][$type][$rewardId] += $rewardNum;
                                break;
                            case RewardConfType::ITEM:
                            case RewardConfType::ITEM_MULTI:
                                if(!isset(btstore_get()->ITEMS[$rewardId]))
                                {
                                    throw new ConfigException('no such item with item tmpl id %d',$rewardId);
                                }
                                if(!isset($conf[$key][RewardConfType::ITEM][$rewardId]))
                                {
                                    $conf[$key][RewardConfType::ITEM][$rewardId] = 0;
                                }
                                $conf[$key][RewardConfType::ITEM][$rewardId] += $rewardNum;
                                break;
                        }
                    }
                }
            }
            $scoreLvReward = array();
            $rankLvReward = array();
            $newConf = array();
            foreach($conf as $key => $value)
            {
                $id = $conf['id'];
                if(strpos($key,'score_lv') === 0)
                {
                    if(empty($value))
                    {
                        continue;
                    }
                    $scoreLv = substr($key, strlen('score_lv'));
                    $newConf['score_lv'][$value] = $conf['score_reward'.$scoreLv];
                }
                else if(strpos($key, 'rank_lv') === 0 && ($key != 'rank_lv_size'))
                {
                    if(empty($value))
                    {
                        continue;
                    }
                    $rankLv = substr($key, strlen('rank_lv'));
                    $newConf['rank_lv'][$value]['score'] = $conf['rank_score'.$rankLv];
                    $newConf['rank_lv'][$value]['reward'] = $conf['rank_reward'.$rankLv];
                }
            }
            if(count($newConf['rank_lv']) != $conf['rank_lv_size'])
            {
                trigger_error('rank_lv_size not equal to the size of ranl_lv conf.');
            }
            $newConf['min_score'] = $conf['score_lv1'];
            $newConf['max_rank'] = $conf['rank_lv'.$conf['rank_lv_size']];
            $newConf['id'] = $id;
            $confList[$conf['id']] = $newConf;
        }
        return $confList;
    }
    
    private static function str2Array($str, $delimiter = ',')
    {
        if(  trim($str) == '' )
        {
            return array();
        }
        return explode($delimiter, $str);
    }
    
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */