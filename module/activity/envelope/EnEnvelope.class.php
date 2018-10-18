<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: EnEnvelope.class.php 223688 2016-01-19 08:40:16Z GuohaoZheng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/envelope/EnEnvelope.class.php $
 * @author $Author: GuohaoZheng $(zhengguohao@babeltime.com)
 * @date $Date: 2016-01-19 08:40:16 +0000 (Tue, 19 Jan 2016) $
 * @version $Revision: 223688 $
 * @brief 
 *  
 **/
class EnEnvelope
{
    public static function readEnvelopeCSV($arrData)
    {
        $csvIndex = 0;
        $confIndex = array(
            EnvelopeDef::ID => $csvIndex,
            EnvelopeDef::NEED_LEVEL => ++$csvIndex,
            EnvelopeDef::MAX_NUM_LIMIT => ++$csvIndex,
            EnvelopeDef::MIN_GOLD_LIMIT => ++$csvIndex,
            EnvelopeDef::DAY_MAX_GOLD_LIMIT => ++$csvIndex,
            EnvelopeDef::RECLAIM_TIME => ++$csvIndex,
            EnvelopeDef::MAX_MSG_NUM => ++$csvIndex,
        );
        
        $conf = array();
        
        foreach ($arrData as $data)
        {
            if (empty($data) || empty($data[0]))
            {
                break;
            }
            
            foreach ($confIndex as $key => $index)
            {
                switch ($key)
                {
                    case EnvelopeDef::ID:
                    case EnvelopeDef::NEED_LEVEL:
                    case EnvelopeDef::MAX_NUM_LIMIT:
                    case EnvelopeDef::MIN_GOLD_LIMIT:
                    case EnvelopeDef::DAY_MAX_GOLD_LIMIT:
                    case EnvelopeDef::RECLAIM_TIME:
                    case EnvelopeDef::MAX_MSG_NUM:
                        $conf[$key] = intval($data[$index]);
                        break;
                }
            }
        }
        
        if ($conf[EnvelopeDef::MAX_NUM_LIMIT] > EnvelopeDef::MAX_ENVELOPE_DIV_NUM_BACKEND)
        {
            throw new ConfigException('single envelope divNum beyond limit.');
        }
        
        if ( ActivityConf::$STRICT_CHECK_CONF
            && !Util::isInCross()
            && TRUE == EnActivity::isOpen(ActivityName::ENVELOPE))
        {
            $curConf = EnActivity::getConfByName(ActivityName::ENVELOPE);
            
            if ($curConf['data'][EnvelopeDef::RECLAIM_TIME] < $conf[EnvelopeDef::RECLAIM_TIME])
            {
                throw new ConfigException('@cehua: act envelope is open, recl time can not be more.');
            }
        }
        
        return $conf;
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */