<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $$Id$$
 * 
 **************************************************************************/

 /**
 * @file $$HeadURL$$
 * @author $$Author$$(hoping@babeltime.com)
 * @date $$Date$$
 * @version $$Revision$$
 * @brief 
 *  
 **/
class EnGroupOn
{

    public static function readGroupOnCsv($arr, $version, $startTime, $endTime, $needOpenTime )
    {
        if(ActivityConf::$STRICT_CHECK_CONF 
        	&& ! Util::isInCross()
        	&& EnActivity::isOpen(ActivityName::GROUPON))
        {
            $confData = EnActivity::getConfByName(ActivityName::GROUPON);
            if($confData['start_time'] != $startTime)
            {
                throw new ConfigException('start_time cannot change');
            }
        }
        if(ActivityConf::$STRICT_CHECK_CONF
            && ! Util::isInCross()
            && $endTime - strtotime ( date ( "Y-m-d ", $endTime ) )!= 3600)
        {
            throw new ConfigException('end_time:%d, %d not one clock', $endTime, strtotime ( date ( "Y-m-d ", $endTime ) ));
        }

        $ZERO = 0;
        $field_names = array(
            GroupOnDef::ID => $ZERO,
            GroupOnDef::PRICE => ++$ZERO,
            GroupOnDef::VIP => ++$ZERO,
            GroupOnDef::ORIPEICE => ++$ZERO,
            GroupOnDef::ICON => ++$ZERO,
            GroupOnDef::NAME=> ++$ZERO,
            GroupOnDef::QUALITY => ++$ZERO,
            GroupOnDef::GOOD => ++$ZERO,
            GroupOnDef::NUMTOP => ++$ZERO,
            GroupOnDef::NUMOFREWARD1 => ++$ZERO,
            GroupOnDef::REWARD1 => ($ZERO+=3),
            GroupOnDef::NUMOFREWARD2 => ++$ZERO,
            GroupOnDef::REWARD2 => ($ZERO+=3),
            GroupOnDef::NUMOFREWARD3 => ++$ZERO,
            GroupOnDef::REWARD3 => ($ZERO+=3),
            GroupOnDef::NUMOFREWARD4 => ++$ZERO,
            GroupOnDef::REWARD4 => ($ZERO+=3),
            GroupOnDef::NUMOFREWARD5 => ++$ZERO,
            GroupOnDef::REWARD5 => ($ZERO+=3),
            GroupOnDef::NUMOFREWARD6 => ++$ZERO,
            GroupOnDef::REWARD6 => ($ZERO+=3),
            GroupOnDef::NUMOFREWARD7 => ++$ZERO,
            GroupOnDef::REWARD7 => ($ZERO+=3),
            GroupOnDef::NUMOFREWARD8 => ++$ZERO,
            GroupOnDef::REWARD8 => ($ZERO+=3),
            GroupOnDef::NUMOFREWARD9 => ++$ZERO,
            GroupOnDef::REWARD9 => ($ZERO+=3),
            GroupOnDef::NUMOFREWARD10 => ++$ZERO,
            GroupOnDef::REWARD10 => ($ZERO+=3),
            GroupOnDef::NUMOFREWARD11 => ++$ZERO,
            GroupOnDef::REWARD11 => ($ZERO+=3),
            GroupOnDef::NUMOFREWARD12 => ++$ZERO,
            GroupOnDef::REWARD12 => ($ZERO+=3),
            GroupOnDef::NUMOFREWARD13 => ++$ZERO,
            GroupOnDef::REWARD13 => ($ZERO+=3),
            GroupOnDef::NUMOFREWARD14 => ++$ZERO,
            GroupOnDef::REWARD14 => ($ZERO+=3),
            GroupOnDef::NUMOFREWARD15 => ++$ZERO,
            GroupOnDef::REWARD15 => ($ZERO+=3),
            GroupOnDef::NUMOFREWARD16 => ++$ZERO,
            GroupOnDef::REWARD16 => ($ZERO+=3),
            GroupOnDef::NUMOFREWARD17 => ++$ZERO,
            GroupOnDef::REWARD17 => ($ZERO+=3),
            GroupOnDef::NUMOFREWARD18 => ++$ZERO,
            GroupOnDef::REWARD18 => ($ZERO+=3),
            GroupOnDef::NUMOFREWARD19 => ++$ZERO,
            GroupOnDef::REWARD19 => ($ZERO+=3),
            GroupOnDef::NUMOFREWARD20 => ++$ZERO,
            GroupOnDef::REWARD20 => ($ZERO+=3),
            GroupOnDef::GROUPONIDS => ++$ZERO,
            GroupOnDef::REFRESHTIME => ++$ZERO,
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
                    case GroupOnDef::GOOD:
                    case GroupOnDef::REWARD1:
                    case GroupOnDef::REWARD2:
                    case GroupOnDef::REWARD3:
                    case GroupOnDef::REWARD4:
                    case GroupOnDef::REWARD5:
                    case GroupOnDef::REWARD6:
                    case GroupOnDef::REWARD7:
                    case GroupOnDef::REWARD8:
                    case GroupOnDef::REWARD9:
                    case GroupOnDef::REWARD10:
                    case GroupOnDef::REWARD11:
                    case GroupOnDef::REWARD12:
                    case GroupOnDef::REWARD13:
                    case GroupOnDef::REWARD14:
                    case GroupOnDef::REWARD15:
                    case GroupOnDef::REWARD16:
                    case GroupOnDef::REWARD17:
                    case GroupOnDef::REWARD18:
                    case GroupOnDef::REWARD19:
                    case GroupOnDef::REWARD20:
                        $tmp = Util::str2Array($data[$val], ',');
                        foreach($tmp as $k => $v)
                        {
                            $conf[$key][$k] = Util::array2Int(Util::str2Array($v, '|'));
                        }
                        break;
                    case GroupOnDef::GROUPONIDS:
                        $tmp = Util::str2Array($data[$val], ',');
                        foreach($tmp as $k => $v)
                        {
                            $conf[$key][$k] = Util::array2Int(Util::str2Array($v, '|'));
                        }
                        break;
                    default:
                        if($key != GroupOnDef::NUMTOP && stristr($key, GroupOnDef::NUM))
                        {
                            if(!empty($data[$val]))
                            {
                                $conf[$key] = intval($data[$val]);
                            }
                        }
                        else
                        {
                            $conf[$key] = intval($data[$val]);
                        }

                }
                if(stristr($key, GroupOnDef::REWARD) != null && !empty($conf[$key]))
                {
                    $len = strlen(GroupOnDef::REWARD);
                    $index = substr($key, $len);
                    if($index - 1 < 0)
                    {
                        throw new ConfigException("invalid reward:%s", $key);
                    }
                    $conf[GroupOnDef::REWARD][$index - 1] = $conf[$key];
                    unset($conf[$key]);
                }
                if($key != GroupOnDef::NUMTOP && stristr($key, GroupOnDef::NUM) && !empty($conf[$key]))
                {
                    $len = strlen(GroupOnDef::NUM);
                    $index = substr($key, $len);
                    if($index - 1 < 0)
                    {
                        throw new ConfigException("invalid num:%s for reward", $key);
                    }
                    $conf[GroupOnDef::NUM][$index - 1] = $conf[$key];
                    unset($conf[$key]);
                }

            }
            if($conf[GroupOnDef::NUMTOP] != count($conf[GroupOnDef::NUM]))
            {
                throw new ConfigException("invalid config numtop:%d conf[num]:%s", $conf[GroupOnDef::NUMTOP], $conf[GroupOnDef::NUM]);
            }

            $arrConf[$conf[GroupOnDef::ID]] = $conf;
        }

        if(empty($arrConf))
        {
            $arrConf = array('dummy' => true);
        }

        return $arrConf;
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */