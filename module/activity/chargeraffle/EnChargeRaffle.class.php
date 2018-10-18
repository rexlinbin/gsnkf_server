<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: EnChargeRaffle.class.php 259698 2016-08-31 08:07:55Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/chargeraffle/EnChargeRaffle.class.php $
 * @author $Author: BaoguoMeng $(zhangtiantian@babeltime.com)
 * @date $Date: 2016-08-31 08:07:55 +0000 (Wed, 31 Aug 2016) $
 * @version $Revision: 259698 $
 * @brief 
 *  
 **/
class EnChargeRaffle
{
    public static function readChargeRaffleCSV($arrData)
    {
        if(!FrameworkConfig::DEBUG && ! Util::isInCross() )
        {
            $actEndTime = ChargeRaffleLogic::getActEndTime();
            if(Util::getDaysBetween($actEndTime) <= 1)
            {
                throw new ConfigException('can not set conf.actendtime is %d.gap of the act must max than 1day',
                        $actEndTime);
            }
        }
        $ZERO = 0;
        $arrConfKey = array(
                'id' => $ZERO,
                'raffleMaxNum' => ++$ZERO,//每日获得限制次数
                'profile'=>++$ZERO,//活动描述
                'needCharge'=>++$ZERO,//充值金币
                'reward'=>++$ZERO,//每日首次充值奖励
                'arrDrop1'=>++$ZERO,//
                'specailDrop1'=>++$ZERO,
                'dropShow1'=>++$ZERO,
                'arrDrop2'=>++$ZERO,
                'specailDrop2'=>++$ZERO,
                'dropShow2'=>++$ZERO,
                'arrDrop3'=>++$ZERO,
                'specailDrop3'=>++$ZERO,
                'dropShow3'=>++$ZERO,
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
                    case 'profile':
                        break;
                    case 'raffleMaxNum':
                    case 'needCharge':
                        $arrTmp = Util::array2Int(Util::str2Array($data[$index], ','));
                        foreach($arrTmp as $index => $value)
                        {
                            $conf[$key][$index+1] = $value;
                        }
                        break;
                    case 'arrDrop1':
                    case 'arrDrop2':
                    case 'arrDrop3':
                        $arrDropConf = array();
                        $arrDrop = Util::str2Array($data[$index], ',');
                        foreach($arrDrop as $dropInfo)
                        {
                            $arrInfo = Util::array2Int(Util::str2Array($dropInfo,'|'));
                            if(count($arrInfo) != 2)
                            {
                                trigger_error('invalid conf.drop field is not 2');
                            }
                            $arrDropConf[$arrInfo[0]] = array(
                                    'weight'=>$arrInfo[1]
                                    );
                        }
                        $conf[$key] = $arrDropConf;
                        break;
                    case 'specailDrop1':
                    case 'specailDrop2':
                    case 'specailDrop3':
                        $arrDropConf = array();
                        $arrDrop = Util::str2Array($data[$index], ',');
                        foreach($arrDrop as $dropInfo)
                        {
                            $arrInfo = Util::array2Int(Util::str2Array($dropInfo,'|'));
                            if(count($arrInfo) != 2)
                            {
                                trigger_error('invalid conf.specialdrop field is not 2');
                            }
                            $arrDropConf[$arrInfo[0]] = $arrInfo[1];
                        }
                        $conf[$key] = $arrDropConf;
                        break;
                    case 'dropShow1':
                    case 'dropShow2':
                    case 'dropShow3':
                        $arrDropConf = array();
                        $arrDrop = Util::str2Array($data[$index], ',');
                        foreach($arrDrop as $dropInfo)
                        {
                            $arrInfo = Util::array2Int(Util::str2Array($dropInfo,'|'));
                            if(count($arrInfo) != 3)
                            {
                                trigger_error('invalid conf.dropShow field is not 3');
                            }
                            $arrDropConf[$arrInfo[0]][$arrInfo[1]] = $arrInfo[2];
                        }
                        $conf[$key] = $arrDropConf;
                        break;
                    case 'reward':
                        $tmpReward = array();
                        $originalReward = array();
                        $arrReward = Util::str2Array($data[$index], ',');
                        foreach($arrReward as $rewardInfo)
                        {
                            $arrInfo = Util::array2Int(Util::str2Array($rewardInfo, '|'));
                            if(count($arrInfo) != 3)
                            {
                                trigger_error('error config in reward field.');
                            }
                            $type = $arrInfo[0];
                            $tmplId = $arrInfo[1];
                            $num = $arrInfo[2];
                            switch($type)
                            {
                                case RewardConfType::SILVER:
                                case RewardConfType::SOUL:
                                case RewardConfType::JEWEL:
                                case RewardConfType::GOLD:
                                case RewardConfType::EXECUTION:
                                case RewardConfType::STAMINA:
                                case RewardConfType::SILVER_MUL_LEVEL:
                                case RewardConfType::SOUL_MUL_LEVEL:
                                case RewardConfType::EXP_MUL_LEVEL:
                                    $tmpReward[$type] = $num;
                                    $originalReward[] = array('type'=>$type,'val'=>$num);
                                    break;
                                case RewardConfType::HERO_MULTI:
                                case RewardConfType::ITEM_MULTI:
                                case RewardConfType::TREASURE_FRAG_MULTI:
                                    if(!isset($tmpReward[$type][$tmplId]))
                                    {
                                        $tmpReward[$type][$tmplId] = 0;
                                    }
                                    $tmpReward[$type][$tmplId] += $num;
                                    $originalReward[] = array('type'=>$type,'val'=>array(array($tmplId,$num)));
                                    break;
                                case RewardConfType::HERO:
                                    if(!isset($tmpReward[RewardConfType::HERO_MULTI][$tmplId]))
                                    {
                                        $tmpReward[RewardConfType::HERO_MULTI][$tmplId] = 0;
                                    }
                                    $tmpReward[RewardConfType::HERO_MULTI][$tmplId] += $num;
                                    $originalReward[] = array('type'=>RewardConfType::HERO_MULTI,'val'=>array(array($tmplId,$num)));
                                    break;
                                case RewardConfType::ITEM:
                                    if(!isset($tmpReward[RewardConfType::ITEM_MULTI][$tmplId]))
                                    {
                                        $tmpReward[RewardConfType::ITEM_MULTI][$tmplId] = 0;
                                    }
                                    $tmpReward[RewardConfType::ITEM_MULTI][$tmplId] += $num;
                                    $originalReward[] = array('type'=>RewardConfType::ITEM_MULTI,'val'=>array(array($tmplId,$num)));
                                    break;
                            }
                        }
                        $conf['originalReward'] = $originalReward;
                        $conf[$key] = $tmpReward;
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
        self::checkDrop($confList);
        return $confList;
    }
    
    
    /**
     * // 掉落物品
	const DROP_TYPE_ITEM = 0;	
	// 掉落武将		
	const DROP_TYPE_HERO = 1;
	// 掉落银币
	const DROP_TYPE_SILVER = 2;
	// 混合掉落
	const DROP_TYPE_MIXED = 3;
	// 掉落将魂
	const DROP_TYPE_SOUL = 4;
	// 掉落宝物碎片
	const DROP_TYPE_TREASFRAG = 5;
     * @param unknown_type $conf
     */
    public static function checkDrop($conf)
    {
        for($i=ChargeRaffleDef::MIN_RAFFLE_CLASS;$i<=ChargeRaffleDef::MAX_RAFFLE_CLASS;$i++)
        {
            $arrShow = $conf['dropShow'.$i];
            $arrDropId = array();
            $arrDropInfo = $conf['arrDrop'.$i];
            $arrSpecailDropInfo = $conf['specailDrop'.$i];
            foreach($arrDropInfo as $dropId => $weight)
            {
                $arrDropId[] = $dropId;
            }
            foreach($arrSpecailDropInfo as $raffleNum => $dropId)
            {
                $arrDropId[] = $dropId;
            }
            $arrDropItem = self::getArrDropInfo($arrDropId);
            $arrShowItem = array();
            foreach($arrShow as $itemType => $arrItem)
            {
                foreach($arrItem as $tmplId => $itemNum)
                {
                    $arrShowItem[$itemType][$tmplId][$itemNum] = TRUE;
                }
            }
            foreach($arrDropItem as $itemType => $arrDrop)
            {
                foreach($arrDrop as $tmplId => $arrNum)
                {
                    foreach($arrNum as $num => $status)
                    {
                        if(!isset($arrShowItem[$itemType][$tmplId][$num]))
                        {
                            throw new ConfigException('config error.drop item %d which is not in show list.index %d',$tmplId,$i);
                        }
                    }
                }
            }
        }
        Logger::info('EnChargeRaffle check success');
    }
    
    public static function getArrDropInfo($arrDropId)
    {
        $arrRet = array();
        foreach($arrDropId as $dropId)
        {
            $ret = self::getDropInfo($dropId);
            foreach($ret as $type => $arrDrop)
            {
                $itemType = NULL;
                if($type == DropDef::DROP_TYPE_ITEM)
                {
                    $itemType = RewardConfType::ITEM_MULTI;
                }
                else if($type == DropDef::DROP_TYPE_ITEM)
                {
                    $itemType = RewardConfType::TREASURE_FRAG_MULTI;
                }
                foreach($arrDrop as $tmplId => $arrNum)
                {
                    foreach($arrNum as $num => $status)
                    {
                        $arrRet[$itemType][$tmplId][$num] = TRUE;
                    }
                }
            }
        }
        return $arrRet;
    }
    
    
    private static function getDropInfo($dropId)
    {
        Logger::trace('Drop::getDropInfo is Start, dropId:%d', $dropId);
    
        //无效掉落表id
        if (empty(btstore_get()->DROP_ITEM[$dropId]))
        {
            throw new FakeException('drop id:%d is not exist!', $dropId);
        }
        $drop = btstore_get()->DROP_ITEM[$dropId];
    
        $arrRet = array();
        $dropType = $drop[DropDef::DROP_TYPE];
        if (DropDef::DROP_TYPE_MIXED == $dropType)
        {
            foreach ($drop[DropDef::DROP_LIST] as $key => $value)
            {
                $ret = self::getDropInfo($value[DropDef::DROP_ITEM_TEMPLATE]);
                foreach ($ret as $type => $arrDrop)
                {
                    foreach($arrDrop as $tmplId => $arrNum)
                    {
                        foreach($arrNum as $num => $status)
                        {
                            $arrRet[$type][$tmplId][$num] = TRUE;
                        }
                    }
                }
            }
        }
        else if (DropDef::DROP_TYPE_ITEM == $dropType 
                || ($dropType == DropDef::DROP_TYPE_TREASFRAG))
        {
            foreach ($drop[DropDef::DROP_LIST] as $key => $value)
            {
                $tmplId = $value[DropDef::DROP_ITEM_TEMPLATE];
                $num = $value[DropDef::DROP_ITEM_NUM];
                $arrRet[$dropType][$tmplId][$num] = TRUE;
            }
        }
        else
        {
            throw new ConfigException('now charge raffle drop only support item and treasure fragment');
        }
        Logger::trace('Drop::getDropInfo is End.');
        return $arrRet;
    }
    
    
    public static function loginToGetReward()
    {
        $uid = RPCContext::getInstance()->getUid();
        RPCContext::getInstance()->executeTask($uid,
                'chargeraffle.rewardUserOnLogin', array($uid));
    }
    
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */