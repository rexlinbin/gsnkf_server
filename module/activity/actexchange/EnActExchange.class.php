<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $$Id: EnActExchange.class.php 148905 2014-12-24 13:30:37Z ShijieHan $$
 * 
 **************************************************************************/

 /**
 * @file $$HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/actexchange/EnActExchange.class.php $$
 * @author $$Author: ShijieHan $$(hoping@babeltime.com)
 * @date $$Date: 2014-12-24 13:30:37 +0000 (Wed, 24 Dec 2014) $$
 * @version $$Revision: 148905 $$
 * @brief 
 *  
 **/
class EnActExchange
{
    public static function readActExchangeCSV($arr, $version, $startTime, $endTime, $needOpenTime)
    {

        if( ActivityConf::$STRICT_CHECK_CONF 
        	&& ! Util::isInCross()
        	&& EnActivity::isOpen(ActivityName::ACT_EXCHANGE))
        {
            $confData = EnActivity::getConfByName(ActivityName::ACT_EXCHANGE);
            if($confData['start_time'] != $startTime)
            {
                throw new ConfigException('start_time cannot change');
            }
        }
        $ZERO = 0;
        $field_names = array(
            ActExchangeDef::ACTEXCHANGE_ID => $ZERO,
            ActExchangeDef::ACTEXCHANGE_NAME => ++$ZERO,
            ActExchangeDef::ACTEXCHANGE_MATERIA_QUANTITY => ++$ZERO,
            ActExchangeDef::ACTEXCHANGE_MATERIAL_1 => ++$ZERO,
            ActExchangeDef::ACTEXCHANGE_MATERIAL_2 => ++$ZERO,
            ActExchangeDef::ACTEXCHANGE_MATERIAL_3 => ++$ZERO,
            ActExchangeDef::ACTEXCHANGE_MATERIAL_4 => ++$ZERO,
            ActExchangeDef::ACTEXCHANGE_MATERIAL_5 => ++$ZERO,
            ActExchangeDef::ACTEXCHANGE_TARGET_ITEMS => ++$ZERO,
            ActExchangeDef::ACTEXCHANGE_CHANGE_NUM => ++$ZERO,
            ActExchangeDef::ACTEXCHANGE_REFRESH_TIME => ++$ZERO,
            ActExchangeDef::ACTEXCHANGE_CONVERSION_FORMULA => ++$ZERO,
            ActExchangeDef::ACTEXCHANGE_REWARD_NORMAL => ++$ZERO,
            ActExchangeDef::ACTEXCHANGE_GOLD => ++$ZERO,
            ActExchangeDef::ACTEXCHANGE_LEVEL => ++$ZERO,
            ActExchangeDef::ACTEXCHANGE_GOLD_TOP => ++$ZERO,

            ActExchangeDef::ACTEXCHANGE_ITEM_VIEW => ++$ZERO,
            ActExchangeDef::ACTEXCHANGE_VIEW_NAME => ++$ZERO,
            ActExchangeDef::ACTEXCHANGE_ISREFRESH => ++$ZERO,
            ActExchangeDef::ACTEXCHANGE_SDCJ    =>  ++$ZERO,
            ActExchangeDef::ACTEXCHANGE_SMSD    =>  ++$ZERO,
            ActExchangeDef::ACTEXCHANGE_SMSR    =>  ++$ZERO,

            ActExchangeDef::ACTEXCHANGE_LHZL    =>  ++$ZERO,
        );

        //兼容老版本 20140901
        if(count($arr[0]) <= 22)
        {
            Logger::debug('compat old version before 20140901 and after 20140813');
            unset($field_names[ActExchangeDef::ACTEXCHANGE_LHZL]);
        }
        
        //兼容老版本 20140813 wuqilin
        if( count( $arr[0] ) <= 18 )
        {
        	Logger::debug('compat the first version');
        	unset( $field_names[ActExchangeDef::ACTEXCHANGE_ISREFRESH] );
        	unset( $field_names[ActExchangeDef::ACTEXCHANGE_SDCJ] );
        	unset( $field_names[ActExchangeDef::ACTEXCHANGE_SMSD] );
        	unset( $field_names[ActExchangeDef::ACTEXCHANGE_SMSR] );
        }
        

        $arrConf = array();
        $conf = array();
        foreach($arr as $data)
        {
            if(empty($data))
            {
                break;
            }
            $conf = array();
            foreach($field_names as $key => $val)
            {
                switch($key)
                {
                    case ActExchangeDef::ACTEXCHANGE_ITEM_VIEW:
                    case ActExchangeDef::ACTEXCHANGE_VIEW_NAME:
                    case ActExchangeDef::ACTEXCHANGE_ISREFRESH:
                        break;
                    case ActExchangeDef::ACTEXCHANGE_MATERIAL_1:
                    case ActExchangeDef::ACTEXCHANGE_MATERIAL_2:
                    case ActExchangeDef::ACTEXCHANGE_MATERIAL_3:
                    case ActExchangeDef::ACTEXCHANGE_MATERIAL_4:
                    case ActExchangeDef::ACTEXCHANGE_MATERIAL_5:
                    case ActExchangeDef::ACTEXCHANGE_TARGET_ITEMS:
                        $tmp = Util::str2Array($data[$val], ',');
                        foreach($tmp as $k => $v)
                        {
                            $tmp2 = Util::array2Int(Util::str2Array($v, '|'));
                            if($tmp2[0] == ActExchangeDef::ACTEXCHANGE_SPEND_TYPE_GOLD) {
                                $conf[$key][$k] = array(MallDef::MALL_EXCHANGE_GOLD => $tmp2[3], ActExchangeDef::ACTEXCHANGE_SPEND_FIELD_WEIGHT => $tmp2[1]);
                            }
                            if($tmp2[0] == ActExchangeDef::ACTEXCHANGE_SPEND_TYPE_ITEM)
                            {
                                $conf[$key][$k] = array(MallDef::MALL_EXCHANGE_ITEM => array($tmp2[2]=>$tmp2[3]), ActExchangeDef::ACTEXCHANGE_SPEND_FIELD_WEIGHT => $tmp2[1]);
                            }
                            if($tmp2[0] == ActExchangeDef::ACTEXCHANGE_SPEND_TYPE_DROP)
                            {
                                $conf[$key][$k] = array(MallDef::MALL_EXCHANGE_DROP => $tmp2[2], ActExchangeDef::ACTEXCHANGE_SPEND_FIELD_WEIGHT => $tmp2[1]);
                            }
                        }
                        break;
                    case ActExchangeDef::ACTEXCHANGE_CONVERSION_FORMULA:
                        $tmp = Util::str2Array($data[$val], ',');
                        foreach($tmp as $k => $v)
                        {
                            $conf[$key][$k] = Util::array2Int(Util::str2Array($v, '|'));
                        }
                        break;
                    case ActExchangeDef::ACTEXCHANGE_GOLD:
                        $conf[$key] = Util::array2Int(Util::str2Array($data[$val], '|'));
                        break;
                    case ActExchangeDef::ACTEXCHANGE_NAME:
                        $conf[$key] = $data[$val];
                    case ActExchangeDef::ACTEXCHANGE_SDCJ:
                    case ActExchangeDef::ACTEXCHANGE_SMSD:
                    case ActExchangeDef::ACTEXCHANGE_SMSR:
                    case ActExchangeDef::ACTEXCHANGE_LHZL:
                        $conf[$key] = Util::array2Int(Util::str2Array($data[$val], ','));
                        break;
                    default:
                        $conf[$key] = intval($data[$val]);
                }
                $arrConf[$conf[ActExchangeDef::ACTEXCHANGE_ID]] = $conf;
            }
        }

        if(empty($arrConf))
        {
            $arrConf = array('dummy' => true);
        }

        return $arrConf;
    }

    /**
     * 普通副本额外掉落表
     * @return $dropid int 掉落表id 如果活动没开 返回0
     */
    public static function getDrop()
    {
        Logger::trace("ActExchange::getDrop start");
        if(EnActivity::isOpen(ActivityName::ACT_EXCHANGE) == FALSE || MyActExchange::isActExchangeOpen() == FALSE)
        {
            return 0;
        }
        $ret = MyActExchange::getDrop();
        Logger::trace("ActExchange::getDrop end");
        return $ret;
    }

    /**
     * 商店抽将
     */
    public static function getDropForSdcj()
    {
        Logger::trace("ActExchange::getDropForSdcj start");
        if(EnActivity::isOpen(ActivityName::ACT_EXCHANGE) == FALSE || MyActExchange::isActExchangeOpen() == FALSE)
        {
            return array();
        }
        $ret = MyActExchange::getDropForSdcj();
        Logger::trace("ActExchange::getDropForSdcj end");
        return $ret;
    }

    /**
     * 神秘商店
     */
    public static function getDropForSmsd()
    {
        Logger::trace("ActExchange::getDropForSmsd start");
        if(EnActivity::isOpen(ActivityName::ACT_EXCHANGE) == FALSE || MyActExchange::isActExchangeOpen() == FALSE)
        {
            return array();
        }
        $ret = MyActExchange::getDropForSmsd();
        Logger::trace("ActExchange::getDropForSmsd end");
        return $ret;
    }

    /**
     * 神秘商人
     */
    public static function getDropForSmsr()
    {
        Logger::trace("ActExchange::getDropForSmsr start");
        if(EnActivity::isOpen(ActivityName::ACT_EXCHANGE) == FALSE || MyActExchange::isActExchangeOpen() == FALSE)
        {
            return array();
        }
        $ret = MyActExchange::getDropForSmsr();
        Logger::trace("ActExchange::getDropForSmsr end");
        return $ret;
    }

    /**
     * 猎魂招龙
     */
    public static function getDropForLhzl()
    {
        Logger::trace("ActExchange::getDropForLhzl start");
        if(EnActivity::isOpen(ActivityName::ACT_EXCHANGE) == FALSE || MyActExchange::isActExchangeOpen() == FALSE)
        {
            return array();
        }
        $actConf = MyActExchange::getActivityConf();
        if(empty($actConf[ActExchangeDef::ACTEXCHANGE_LHZL]))
        {
            return array();
        }
        Logger::trace("ActExchange::getDropForLhzl end");
        return $actConf[ActExchangeDef::ACTEXCHANGE_LHZL];
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */