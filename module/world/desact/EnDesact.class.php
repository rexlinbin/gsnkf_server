<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: EnDesact.class.php 203496 2015-10-20 13:53:35Z GuohaoZheng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/world/desact/EnDesact.class.php $
 * @author $Author: GuohaoZheng $(zhengguohao@babeltime.com)
 * @date $Date: 2015-10-20 13:53:35 +0000 (Tue, 20 Oct 2015) $
 * @version $Revision: 203496 $
 * @brief 
 *  
 **/
class EnDesact
{
    public static function readDesactCSV($arrData, $version, $startTime)
    {
        $ZERO = 0;
        
        $confIndex = array(
            DesactDef::ID => $ZERO,
            DesactDef::LAST_DAY => ++$ZERO,
            DesactDef::IS_OPEN => ++$ZERO,
            DesactDef::REWARD => ++$ZERO,
            DesactDef::DESCRIPTION => ++$ZERO,
            DesactDef::MISSION_NAME => ++$ZERO,
            DesactDef::MISSION_TIPS => ++$ZERO,
        );
        
        $arrConfList = array();
        
        foreach ($arrData as $data)
        {
            if (empty($data) || empty($data[0]))
            {
                break;
            }
            
            if (intval($data[$confIndex[DesactDef::IS_OPEN]]) != DesactDef::MISSION_OPEN)
            {
                continue;
            }
            
            $conf = array();
            
            foreach ($confIndex as $key => $index)
            {
                switch ($key)
                {
                    case DesactDef::REWARD:
                        $conf[$key] = array();
                        $arrDesactConf = Util::str2Array($data[$index], ',');
                        
                        foreach ($arrDesactConf as $key2 => $index2)
                        {
                            $arrTmp = array_map('intval', Util::str2Array($index2, '|'));
                            
                            $conf[$key][$arrTmp[0]][] = array(
                                $arrTmp[1],
                                $arrTmp[2],
                                $arrTmp[3],
                            );
                        }
                        break;
                        
                    case DesactDef::DESCRIPTION:
                    case DesactDef::MISSION_NAME:
                    case DesactDef::MISSION_TIPS:
                        $conf[$key] = $data[$index];
                        break;
                        
                    default:
                        $conf[$key] = intval($data[$index]);
                        break;
                }
            }
            
            $adjustConf = $conf[DesactDef::REWARD];
            
            ksort($adjustConf);
            
            $conf[DesactDef::REWARD] = array();
            
            foreach ($adjustConf as $key => $value)
            {
                $conf[DesactDef::REWARD][] = array(
                    'num' => $key,
                    'reward' => $value,
                );
            }
            
            unset($conf[DesactDef::IS_OPEN]);
            
            $arrConfList[$conf[DesactDef::ID]] = $conf;
        }
        
        if (count($arrConfList) <= 1)
        {
            throw new ConfigException('@cehua:desact num should be more than 1.');
        }
        
        if (isset($arrConfList[DesactDef::COMPETE]))
        {
            if (count($arrConfList) < 3)
            {
                throw new ConfigException('@cehua:desact contains compete. task num can not be less than 3.');
            }
            
            if ($arrConfList[DesactDef::COMPETE][DesactDef::LAST_DAY] >= 3)
            {
                throw new ConfigException('@cehua:desact compete can not last more than 2 days.');
            }
            
            $count = 0;
            $competeDay = $arrConfList[DesactDef::COMPETE][DesactDef::LAST_DAY];
            foreach ($arrConfList as $key => $value)
            {
                if ($key != DesactDef::COMPETE && $competeDay == $value[DesactDef::LAST_DAY])
                {
                    $count++;
                }
            }
            
            if ( $count < 2 )
            {
                throw new ConfigException('@cehua: at least another two tasks have same last day with compete.');
            }
        } 
        
        return $arrConfList;
    }
    
    public static function doDesact($uid, $type, $num)
    {
        $guid = RPCContext::getInstance()->getUid();
        if( $uid != $guid )
        {
            Logger::fatal( 'invalid uid:%s', $uid );
            return ;
        }
        
        if (!in_array($type, DesactDef::$ARR_TASK_TYPE))
        {
            Logger::fatal('unsupported desact task type:%d.',$type);
            return ;
        }
        
        if ($num <= 0)
        {
            Logger::fatal('do desact task.num must more than 0. num:%d.',$num);
            return ;
        }
        
        $ret = DesactLogic::doDesact($uid, $type, $num);
        
        return $ret;
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */