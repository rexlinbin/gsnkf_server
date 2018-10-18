<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $$Id: EnTopupReward.class.php 128207 2014-08-20 10:25:19Z wuqilin $$
 * 
 **************************************************************************/

 /**
 * @file $$HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/topupreward/EnTopupReward.class.php $$
 * @author $$Author: wuqilin $$(ShijieHan@babeltime.com)
 * @date $$Date: 2014-08-20 10:25:19 +0000 (Wed, 20 Aug 2014) $$
 * @version $$Revision: 128207 $$
 * @brief 
 *  
 **/
class EnTopupReward
{
    public static function readContinuePayCsv($arr, $version, $startTime, $endTime, $needOpenTime )
    {
        if(ActivityConf::$STRICT_CHECK_CONF 
        	&& ! Util::isInCross()
        	&& EnActivity::isOpen(ActivityName::TOPUPREWARD))
        {
            $confData = EnActivity::getConfByName(ActivityName::TOPUPREWARD);
            if($confData['start_time'] != $startTime)
            {
                throw new ConfigException('start_time cannot change');
            }
        }

        $ZERO = 0;
        $field_names = array(
            ContinuePayCsv::ID => $ZERO,
            ContinuePayCsv::OPENID => ++$ZERO,
            ContinuePayCsv::PAYNUM => ++$ZERO,
            ContinuePayCsv::PAYREWARD => ++$ZERO,
            ContinuePayCsv::ACTIVITYEXPLAIN => ++$ZERO,
        );

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
                    case ContinuePayCsv::OPENID:
                    case ContinuePayCsv::ACTIVITYEXPLAIN:
                        break;
                    case ContinuePayCsv::PAYREWARD:
                        $tmp = Util::str2Array($data[$val], ',');
                        foreach($tmp as $k => $v)
                        {
                            $conf[$key][$k] = Util::array2Int(Util::str2Array($v, '|'));
                        }
                        break;
                    default:
                        $conf[$key] = intval($data[$val]);
                }
                $arrConf[$conf[GroupOnDef::ID]] = $conf;
            }
        }

        if(empty($arrConf))
        {
            $arrConf = array('dummy' => true);
        }

        return $arrConf;
    }

    public static function loginToGetReward()
    {
        $uid = RPCContext::getInstance()->getUid();
        RPCContext::getInstance()->executeTask($uid,
            'topupreward.rewardUserOnLogin', array($uid));
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */