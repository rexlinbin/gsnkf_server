<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $$Id: EnStepCounter.class.php 136576 2014-10-17 06:09:20Z ShijieHan $$
 * 
 **************************************************************************/

 /**
 * @file $$HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/stepcounter/EnStepCounter.class.php $$
 * @author $$Author: ShijieHan $$(ShijieHan@babeltime.com)
 * @date $$Date: 2014-10-17 06:09:20 +0000 (Fri, 17 Oct 2014) $$
 * @version $$Revision: 136576 $$
 * @brief 
 *  
 **/
class EnStepCounter
{
    public static function readStepRewardCsv($arr, $version, $startTime, $endTime, $needOpenTime)
    {
        if(ActivityConf::$STRICT_CHECK_CONF
            && !Util::isInCross()
            && EnActivity::isOpen(ActivityName::STEPCOUNTER))
        {
            $confData = EnActivity::getConfByName(ActivityName::STEPCOUNTER);
            if($confData['start_time'] != $startTime)
            {
                throw new ConfigException('start_time cannot change');
            }
        }

        $ZERO = 0;
        $field_names = array(
            StepCounterDef::ID => $ZERO,
            StepCounterDef::REWARDS => $ZERO+3,
        );

        $arrConf = array();
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
                    case StepCounterDef::ID:
                        $conf[$key] = intval($data[$val]);
                        break;
                    case StepCounterDef::REWARDS:
                        $tmp = Util::str2Array($data[$val], ',');
                        foreach($tmp as $k => $v)
                        {
                            $conf[$key][$k] = Util::array2Int(Util::str2Array($v, '|'));
                        }
                        break;
                }
            }
            $arrConf[$conf[StepCounterDef::ID]] = $conf;
        }

        if(empty($arrConf))
        {
            $arrConf = array('dummy' => true);
        }

        return $arrConf;
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */