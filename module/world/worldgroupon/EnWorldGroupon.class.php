<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $$Id$$
 * 
 **************************************************************************/

 /**
 * @file $$HeadURL$$
 * @author $$Author$$(ShijieHan@babeltime.com)
 * @date $$Date$$
 * @version $$Revision$$
 * @brief 
 *  
 **/
class EnWorldGroupon
{
    public static function readWorldGrouponCsv($arr, $version, $startTime, $endTime, $needOpenTime)
    {
        if(ActivityConf::$STRICT_CHECK_CONF
            && ! Util::isInCross()
            && EnActivity::isOpen(ActivityName::WORLDGROUPON))
        {
            $confData = EnActivity::getConfByName(ActivityName::WORLDGROUPON);
            if($confData['start_time'] != $startTime)
            {
                throw new ConfigException('start_time cannot change');
            }
        }

        $index = 0;
        $field_names = array(
            WorldGrouponCsvDef::ID => $index++,
            WorldGrouponCsvDef::DAY => $index++,
            WorldGrouponCsvDef::ITEM => $index++,
            WorldGrouponCsvDef::PRICE => $index++,
            WorldGrouponCsvDef::DISCOUNT => $index++,
            WorldGrouponCsvDef::NUM => $index++,
            WorldGrouponCsvDef::RETURN_RATE => $index++,
            WorldGrouponCsvDef::POINT_REWARD => $index++,
            WorldGrouponCsvDef::TIME_CFG => $index++,
            WorldGrouponCsvDef::COUPON_USE_RATE => $index++,
            WorldGrouponCsvDef::NAME => $index++,
            WorldGrouponCsvDef::NEED_DAY => ($index += 3) - 1,
        );

        $arrConf = array();
        $extra = array();
        foreach($arr as $line)
        {
            if(empty($line))
            {
                break;
            }

            $conf = array();
            foreach($field_names as $key => $index)
            {
                switch($key)
                {
                    case WorldGrouponCsvDef::DAY:
                        $conf[$key] = Util::array2Int(Util::str2Array($line[$index], ','));
                        break;
                    case WorldGrouponCsvDef::ITEM:
                        $arrTmp = Util::str2Array($line[$index], ',');
                        foreach($arrTmp as $k => $v)
                        {
                            $conf[$key][$k] = Util::array2Int(Util::str2Array($v, '|'));
                        }
                        break;
                    case WorldGrouponCsvDef::DISCOUNT:
                    case WorldGrouponCsvDef::POINT_REWARD:
                        $arrTmp = Util::str2Array($line[$index], ',');
                        if(empty($arrTmp))
                        {
                            $conf[$key] = array();
                        }
                        else
                        {
                            foreach($arrTmp as $k => $v)
                            {
                                $tmp = Util::array2Int(Util::str2Array($v, '|'));
                                if($key == WorldGrouponCsvDef::POINT_REWARD)
                                {
                                    $conf[$key][$tmp[0]][] = array($tmp[1], $tmp[2], $tmp[3]);
                                }
                                else
                                {
                                    $conf[$key][$tmp[0]] = $tmp[1];
                                }
                            }
                        }
                        break;
                    case WorldGrouponCsvDef::TIME_CFG:
                        $conf[$key] = Util::array2Int(Util::str2Array($line[$index], '|'));
                        break;
                    case WorldGrouponCsvDef::NAME:
                        $conf[$key] = $line[$index];
                        break;
                    default:
                        $conf[$key] = intval($line[$index]);
                        break;
                }
            }
            foreach($conf[WorldGrouponCsvDef::DAY] as $day)
            {
                $extra[WorldGrouponCsvDef::DAY][$day][] = $conf[WorldGrouponCsvDef::ID];
            }
            unset($conf[WorldGrouponCsvDef::DAY]);
            if(!empty($conf[WorldGrouponCsvDef::POINT_REWARD]))
            {
                $extra[WorldGrouponCsvDef::POINT_REWARD] = $conf[WorldGrouponCsvDef::POINT_REWARD];
                unset($conf[WorldGrouponCsvDef::POINT_REWARD]);
            }
            if(!empty($conf[WorldGrouponCsvDef::TIME_CFG]))
            {
                $extra[WorldGrouponCsvDef::TIME_CFG] = $conf[WorldGrouponCsvDef::TIME_CFG];
                unset($conf[WorldGrouponCsvDef::TIME_CFG]);
            }
            if(!empty($conf[WorldGrouponCsvDef::NEED_DAY]))
            {
                $extra[WorldGrouponCsvDef::NEED_DAY] = $conf[WorldGrouponCsvDef::NEED_DAY];
                unset($conf[WorldGrouponCsvDef::NEED_DAY]);
            }
            $arrConf[WorldGrouponCsvDef::EXTRA] = $extra;
            $arrConf[WorldGrouponCsvDef::ARR_GOOD][$conf[WorldGrouponCsvDef::ID]] = $conf;
        }

        if(empty($arrConf))
        {
            $arrConf = array('dummy' => true);
        }

        return $arrConf;
    }

}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */