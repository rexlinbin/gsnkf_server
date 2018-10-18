<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: EnRobTomb.class.php 202938 2015-10-17 10:46:51Z wuqilin $
 * 
 **************************************************************************/
 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/robtomb/EnRobTomb.class.php $
 * @author $Author: wuqilin $(zhangtiantian@babeltime.com)
 * @date $Date: 2015-10-17 10:46:51 +0000 (Sat, 17 Oct 2015) $
 * @version $Revision: 202938 $
 * @brief 
 *  
 **/
class EnRobTomb
{
    public static function readRobTombCSV($arrData)
    {
        $ZERO = 0;
        $arrConfKey = array(
                'id' => $ZERO,
                'act_img'=>++$ZERO,
                'act_profile'=>++$ZERO,
                'show_items1'=>++$ZERO,
                'show_items2'=>++$ZERO,
                'show_items3'=>++$ZERO,
                'show_items4'=>++$ZERO,
                'show_items5'=>++$ZERO,
                RobTombDef::BTSTORE_ROB_NEED_GOLD=>++$ZERO,
                RobTombDef::BTSTORE_ROB_NEED_LEVEL=>++$ZERO,
                RobTombDef::BTSTORE_FREE_DROP_ID=>++$ZERO,
                RobTombDef::BTSTORE_GOLD_DROP_ID=>++$ZERO,
                RobTombDef::BTSTORE_ACCUM_NUM=>++$ZERO,
                RobTombDef::BTSTORE_ACCUM_DROP_ID=>++$ZERO,
                RobTombDef::BTSTORE_DROPID_LIMIT=>++$ZERO,
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
                    case 'show_items1':
                    case 'show_items2':
                    case 'show_items3':
                    case 'show_items4':
                    case 'show_items5':
                        break;
                    case RobTombDef::BTSTORE_ACCUM_NUM:
                        $conf[$key] = array_map('intval', Util::str2Array($data[$index], ','));
                        break;
                    case RobTombDef::BTSTORE_DROPID_LIMIT:
                        $arrDropConf = Util::str2Array($data[$index], ',');
                        $conf[$key] = array();
                        foreach($arrDropConf as $index => $dropConf)
                        {
                            $dropLimit = array_map('intval', Util::str2Array($dropConf, '|'));
                            if(count($dropLimit) != 2)
                            {
                                throw new FakeException('wrong conf in field item_limit');
                            }
                            if(isset($conf[$key][$dropLimit[0]]))
                            {
                                throw new FakeException('dumplicated itemid.');
                            }
                            $conf[$key][$dropLimit[0]] = $dropLimit[1];
                        }
                        break;
                    case RobTombDef::BTSTORE_FREE_DROP_ID:
                    case RobTombDef::BTSTORE_GOLD_DROP_ID:
                    case RobTombDef::BTSTORE_ACCUM_DROP_ID:
                        $arrDropConf = Util::str2Array($data[$index], ',');
                        $conf[$key] = array();
                        foreach($arrDropConf as $index => $dropConf)
                        {
                            $arrDropInfo = array_map('intval', Util::str2Array($dropConf, '|'));
                            if(count($arrDropInfo) != 2)
                            {
                                throw new FakeException('wrong conf in field item_limit');
                            }
                            $conf[$key][$arrDropInfo[0]] = array(
                                    'templateId'=>$arrDropInfo[0],
                                    'weight'=>$arrDropInfo[1],
                                    'num'=>1);
                        }
                        break;
                    default:
                        $conf[$key] = intval($data[$index]);
                }
            }
            foreach($conf[RobTombDef::BTSTORE_ACCUM_NUM] as $index => $num)
            {
                if(isset($conf[RobTombDef::BTSTORE_ACCUM_NUM][$index-1]))
                {
                    $conf[RobTombDef::BTSTORE_ACCUM_NUM][$index] = $num + $conf[RobTombDef::BTSTORE_ACCUM_NUM][$index-1];
                }
                $conf[RobTombDef::BTSTORE_LAST_INC_ACCUMNUM] = $num;
            }
            if(!empty($conf[RobTombDef::BTSTORE_ACCUM_NUM]))
            {
                $conf[RobTombDef::BTSTORE_LAST_ACCUMNUM] = $conf[RobTombDef::BTSTORE_ACCUM_NUM][count($conf[RobTombDef::BTSTORE_ACCUM_NUM])-1];
            }
            else
            {
                Logger::warning('empty accum_num.');
            }
            if(!empty($conf[RobTombDef::BTSTORE_DROPID_LIMIT]))
            {
                $accumDropId = $conf[RobTombDef::BTSTORE_ACCUM_DROP_ID];
                $freeDropId = $conf[RobTombDef::BTSTORE_FREE_DROP_ID];
                $goldDropId = $conf[RobTombDef::BTSTORE_GOLD_DROP_ID];
                $dropLimit = $conf[RobTombDef::BTSTORE_DROPID_LIMIT];
                if(self::checkDropOnBlackList($accumDropId , $dropLimit) == FALSE)
                {
                    throw new FakeException('roblimit %s has all item that accumdropid %d has',$dropLimit,$accumDropId);
                }
                if(self::checkDropOnBlackList($freeDropId , $dropLimit) == FALSE)
                {
                    throw new FakeException('roblimit %s has all item that freedropid %d has',$dropLimit,$freeDropId);
                }
                if(self::checkDropOnBlackList($goldDropId , $dropLimit) == FALSE)
                {
                    throw new FakeException('roblimit %s has all item that golddropid %d has',$dropLimit,$goldDropId);
                }
            }
            else 
            {
                Logger::warning('empty drop limit');
            }
            if(!empty($confList))
            {
                Logger::warning('what is wrong with chen.the row num is lagger than 1');
                break;
            }
            $confList = $conf;
        }
        return $confList;
    }
    
    private static function checkDropOnBlackList($arrDropId,$dropLimit)
    {
        foreach($arrDropId as $index => $dropInfo)
        {
            $dropId = $dropInfo['templateId'];
            if(!isset($dropLimit[$dropId]))
            {
                return TRUE;
            }
        }
        return FALSE;
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */