<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: EnActPayBack.class.php 233790 2016-03-21 10:38:16Z GuohaoZheng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/actpayback/EnActPayBack.class.php $
 * @author $Author: GuohaoZheng $(zhengguohao@babeltime.com)
 * @date $Date: 2016-03-21 10:38:16 +0000 (Mon, 21 Mar 2016) $
 * @version $Revision: 233790 $
 * @brief 
 *  
 **/
class EnActPayBack
{
    public static function readActPayBackCSV($arrData)
    {
        $zero = 0;
        
        $arrIndex = array(
            'id' => $zero++,
            'start_time' => $zero++,
            'end_time' => $zero++,
            'need_open_time' => $zero++,
            'reward' => $zero++,
            'time' => $zero++,
            'pid' => $zero++,
        );
        
        $arrConf = array();
        
        foreach ($arrData as $data)
        {
            if (empty($data) || empty($data[0]))
            {
                break;
            }
            
            $conf = array();
            
            foreach ($arrIndex as $key => $index)
            {
                switch ($key)
                {
                    case 'start_time':
                    case 'end_time':
                    case 'need_open_time':
                        $conf[$key] = intval( strtotime( $data[$index] ) );
                        break;
                    case 'pid':
                        $conf[$key] = array_map('intval', Util::str2Array( $data[$index] , ','));
                        break;
                    case 'reward':
                        $tmpArrReward = Util::str2Array( $data[$index], ',');
                        foreach ($tmpArrReward as $reward)
                        {
                            $conf[$key][] = array_map( 'intval', Util::str2Array( $reward, '|') );
                        }
                        break;
                    default:
                        $conf[$key] = intval( $data[$index] );
                }
            }
            
            //和策划讲的，补偿里不会有乘以等级的东西，所以解析的时候就放这了，不然还得在Logic里面写
            $conf['reward'] = RewardUtil::format3DtoCenter( $conf['reward'] );
            $conf['reward']['extra'] = array('id' => $conf['id']);
            
            $rid = $conf['id'] + ActPayBackDef::REWARD_ID_BASE;
            
            if ($rid < ActPayBackDef::REWARD_ID_BASE || $rid >= RewardDef::RID_DIVISION)
            {
                throw new ConfigException("@cehua: wrong rid %d. please check");
            }
            
            $arrConf[ $rid ] = $conf;
        }
        
        return $arrConf;
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */