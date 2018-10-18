<?php
/***************************************************************************
 *
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: EnFestivalact.class.php 244766 2016-05-30 11:45:17Z LeiZhang $
 *
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/trunk/card/rpcfw/module/activity/festivalact/EnFestivalact.class.php $
 * @author $Author: LeiZhang $(zhangtiantian@babeltime.com)
 * @date $Date: 2016-05-30 19:45:17 +0800 (星期一, 30 五月 2016) $
 * @version $Revision: 244766 $
 * @brief
 *
 **/
class EnFestivalAct
{
    /**
     * 需要注意的是限时兑换是两个季度不区分的 并且在最后一天也可以兑换的。所以两个季度的兑换配置要一模一样。
     */
    public static function readFestivalActCSV($arrData, $version, $start_time, $end_time)
    {
        Logger::debug('readFestivalActCSV start. arrData:%s.', $arrData);
        // 活动期间不能修改数据 - 因为配置里涉及充值的档位领奖问题
        if (!FrameworkConfig::DEBUG && !Util::isInCross() && EnActivity::isOpen(ActivityName::FESTIVAL_ACT))
        {
            throw new FakeException('festivalAct is open, cannot modify!');
        }
        $csvIndex = 0;
        $confIndex = array(
            FestivalActDef::ID => $csvIndex,
            FestivalActDef::START_TIME => $csvIndex+=9,
            FestivalActDef::END_TIME => $csvIndex+=1,
            FestivalActDef::MISSION => $csvIndex+=2,
        );

        $confList  = array();

        // 遍历的时候就记录下限时兑换的任务ID组
        $beforExchargeArr = array();
        foreach ($arrData as $data)
        {
            // 为空判断
            if (empty($data) || empty($data[0]))
            {
                break;
            }

            // 没配时间。。
            if (!isset($data[$confIndex[FestivalActDef::START_TIME]]) || !isset($data[$confIndex[FestivalActDef::END_TIME]]))
            {
                throw new ConfigException('no act start and end %s.',$data);
            }

            $conf = array();

            foreach ( $confIndex as $key => $index )
            {
                switch ($key)
                {
                    // 时间要转换成时间戳
                    case FestivalActDef::START_TIME:
                    case FestivalActDef::END_TIME:
                        $time = strtotime($data[$index]);
                        $conf[$key] = $time;
                        break;
                    // 对于任务 要合起来
                    case FestivalActDef::MISSION:
                        $str = $data[$index] . '|' . $data[$index + 2] . '|'
                             . $data[$index + 4] . '|' . $data[$index + 6] . '|' . $data[$index + 7];
                        $conf[$key] = Util::str2Array($str,'|');
                        // 限时兑换是两个季度不区分的 并且在最后一天也可以兑换的。所以每个个季度的兑换配置要一模一样。
                        self::checkExchargeConf($data[$index + 7], $beforExchargeArr);
                        break;
                    default:
                        $conf[$key] = intval($data[$index]);
                }
            }
            $confList[$conf[FestivalActDef::ID]] = $conf;
            // 不用再多存一遍ID啦
            unset($confList[$conf[FestivalActDef::ID]][FestivalActDef::ID]);
        }
        // 读取活动配置的时候，确认下每一季的开始时间，结束时候，是否正确，是否连续，是否预留了一天的领奖时间
        self::checkActivityPeriodAndTime($confList, $start_time, $end_time);
        Logger::debug('readFestivalActCSV for end:%s.', $confList);
        return $confList;
    }

    /**
     * 需要注意的是奖励里面不能有用户和背包之外的need
     */
    public static function readRewardCSV($arrData)
    {
        Logger::debug('readRewardCSV start. arrData:%s.', $arrData);
        // 活动期间不能修改数据 - 因为配置里涉及充值的档位领奖问题
        if (!FrameworkConfig::DEBUG && !Util::isInCross() && EnActivity::isOpen(ActivityName::FESTIVAL_ACT))
        {
            throw new FakeException('festivalAct is open, cannot modify!');
        }
        $csvIndex = 0;
        $confIndex = array(
            FestivalActDef::MID => $csvIndex,
            FestivalActDef::BIGTYPE => $csvIndex+=1,
            FestivalActDef::TYPE_ID => $csvIndex+=2,
        );

        $confList  = array();

        foreach ($arrData as $data)
        {
            // 为空判断
            if (empty($data) || empty($data[0]))
            {
                break;
            }

            // 任务ID不能重复呀。。。
            if (isset($confList[$data[$confIndex[FestivalActDef::MID]]]))
            {
                throw new ConfigException('mission id: %s is repeat.', $data[$confIndex[FestivalActDef::MID]]);
            }

            $conf = array();

            // 先确定ID
            $conf[FestivalActDef::MID] = intval($data[$confIndex[FestivalActDef::MID]]);
            // 大类
            $conf[FestivalActDef::BIGTYPE] = intval($data[$confIndex[FestivalActDef::BIGTYPE]]);
            // 小类需要根据大类确定
            switch ($conf[FestivalActDef::BIGTYPE])
            {
                case FestivalActDef::ACT_TYPE_TASK:
                    // 必须有小类 这里检查一下
                    if (!isset($data[$confIndex[FestivalActDef::TYPE_ID]])
                        || $data[$confIndex[FestivalActDef::TYPE_ID]] <= 0)
                    {
                        throw new ConfigException('mission id: %s is not exist type_id.', $conf[FestivalActDef::MID]);
                    }
                    $conf[FestivalActDef::TYPE_ID] = intval($data[$confIndex[FestivalActDef::TYPE_ID]]);
                    $conf[FestivalActDef::NEED] = intval($data[$confIndex[FestivalActDef::TYPE_ID] + 1]);
                    $conf[FestivalActDef::DAY_RESET] = intval($data[$confIndex[FestivalActDef::TYPE_ID] + 2]);
                    $conf[FestivalActDef::GET] = self::reward2Array($data[$confIndex[FestivalActDef::TYPE_ID] + 3]);
                    break;
                case FestivalActDef::ACT_TYPE_DISCOUNT:
                    $need = array_map('intval', Util::str2Array($data[$confIndex[FestivalActDef::TYPE_ID] + 5], '|'));
                    $conf[FestivalActDef::NEED] = $need[1];
                    $conf[FestivalActDef::GET] = self::reward2Array($data[$confIndex[FestivalActDef::TYPE_ID] + 4]);
                    $conf[FestivalActDef::NUM] = intval($data[$confIndex[FestivalActDef::TYPE_ID] + 6]);
                    break;
                case FestivalActDef::ACT_TYPE_EXCHANGE:
                    $conf[FestivalActDef::NEED] = self::reward2Array($data[$confIndex[FestivalActDef::TYPE_ID] + 8]);
                    // 配置里减的部分不能有用户和背包以外的
                    self::checkNeedConf($conf[FestivalActDef::NEED]);
                    $conf[FestivalActDef::GET] = self::reward2Array($data[$confIndex[FestivalActDef::TYPE_ID] + 7]);
                    $conf[FestivalActDef::NUM] = intval($data[$confIndex[FestivalActDef::TYPE_ID] + 9]);
                    break;
                case FestivalActDef::ACT_TYPE_CHARGE:
                    $conf[FestivalActDef::NEED] = intval($data[$confIndex[FestivalActDef::TYPE_ID] + 11]);
                    $conf[FestivalActDef::GET] = self::reward2Array($data[$confIndex[FestivalActDef::TYPE_ID] + 12]);
                    $conf[FestivalActDef::NUM] = intval($data[$confIndex[FestivalActDef::TYPE_ID] + 13]);
                    break;
            }
            $confList[$conf[FestivalActDef::MID]] = $conf;
            // 不用再多存一遍ID啦
            unset($confList[$conf[FestivalActDef::MID]][FestivalActDef::MID]);
        }
        Logger::debug('readRewardCSV for end:%s.', $confList);
        return $confList;
    }

    /**
     * need不能有
     */
    private static function checkNeedConf($need)
    {
        foreach ($need as $info)
        {
            $type = $info[0];
            // 这个回头定义个常量吧
            switch ($type)
            {
                case RewardConfType::SILVER:
                case RewardConfType::SOUL:
                case RewardConfType::GOLD:
                case RewardConfType::EXECUTION:
                case RewardConfType::STAMINA:
                case RewardConfType::JEWEL:
                case RewardConfType::PRESTIGE:
                case RewardConfType::ITEM_MULTI:
                case RewardConfType::HORNOR:
                    break;
                default:
                    throw new FakeException("checkNeedConf err, invalid type:%d", $type);
            }
        }
    }

    /**
     * 检查每个季度的限制兑换活动是不是一模一样的
     * $exchargeArr 某个季度的限时兑换配置
     */
    private static function checkExchargeConf($curConf, &$beforeExcharge)
    {
        Logger::debug('checkExchargeConf start curConf:%s, beforeExcharge:%s.', $curConf, $beforeExcharge);
        // 把限时兑换的需求格式化下
        $exchargeArr = Util::str2Array($curConf);
        Logger::debug('checkExchargeConf exchargeArr:%s.', $exchargeArr);
        // 限时兑换每季必须有
        if (empty($exchargeArr))
        {
            throw new FakeException('excharge conf is empty!!!');
        }
        if (!empty($beforeExcharge))
        {
            // 限时兑换是两个季度不区分的 并且在最后一天也可以兑换的。所以每个个季度的兑换配置要一模一样。
            if ($exchargeArr != $beforeExcharge)
            {
                throw new FakeException('checkExchargeConf excharge no same.');
            }
        }
        else
        {
            $beforeExcharge = $exchargeArr;
        }
    }


    /**
     * 读取活动配置的时候，确认下每一季的开始时间，结束时候，是否正确，是否连续，是否预留了一天的领奖时间
     * 1.确认季度首尾在活动期间
     * 2.确认季度是首尾相连的
     */
    private static function checkActivityPeriodAndTime($conf, $start_time, $end_time)
    {
        // 上一季的结束时间
        $beforeEnd = $start_time;
        // 最后的结束时间应该按照策划说的多出一天
        if (FrameworkConfig::DEBUG)
        {
            $end_time -= FestivalActDef::TEST_REWARD_TIME;
        }
        else
        {
            $end_time -= FestivalActDef::REWARD_TIME;
        }
        $end = 0;
        foreach ($conf as $periodID => $periodInfo)
        {
            $start = $periodInfo[FestivalActDef::START_TIME];
            $end = $periodInfo[FestivalActDef::END_TIME];
            if ($start < $beforeEnd)
            {
                throw new FakeException('checkActivityPeriodAndTime period:%s start:%s < beforeEnd:%s.', $periodID, $start, $beforeEnd);
            }
            // 两个季度间都是头尾完全衔接的
            if ($periodID > 1 && $beforeEnd + 1 != $start)
            {
                throw new FakeException('checkActivityPeriodAndTime period:%s beforeEnd:%s + 1 != start:%s.', $periodID, $beforeEnd, $start);
            }
            if ($end > $end_time)
            {
                throw new FakeException('checkActivityPeriodAndTime period:%s end:%s > end_time:%s.', $periodID, $end, $end_time);
            }
            // 然后在检查下个季度前要先把当前季度的结束时间赋值给beforeEnd
            $beforeEnd = $end;
        }
    }

    private static function reward2Array($str)
    {
        $ret = array();
        $arr = Util::str2Array($str, ',');
        foreach ( $arr as $data )
        {
             $ret[] = array_map('intval', Util::str2Array($data, '|'));
        }
        return $ret;
    }

    public static function notify($uid, $type_id, $num)
    {
        try
        {
            Logger::debug('EnFestivalAct notify uid:%s, type_id:%s, num:%s.', $uid, $type_id, $num);
            // 先判断下活动开没开
            $session = RPCContext::getInstance()->getSession(FestivalActDef::SESSI);
            //整体的情况是这样的，session里存了最近的一条夏日狂欢活动的时间数据（开始、结束时间），通过isopen来判定活动是否开启：两种情况1.始终时间不满足2.needopentime不满足，
            //这两种情况下session中的实际内容都是空的，但是始终时间都是真实的，对于两种情况来说都是只要在下次活动的开始
            //时间时再更新一把session就可以，另外为了满足多条连续的问题，在活动结束时间的时候也要再刷一把，综上，除了正常的有效时间刷新之外，只要满足settime beween start and endtime,就不需要刷新
            $curTime = Util::getTime();
            if (empty($session) //没有设置过
                || (($session[FestivalActSessionField::SET_TIME] + FestivalActDef::VALIDITY_SECONDS) >= $curTime) //过了有效时间
                || (($session[FestivalActSessionField::START_TIME] >= $session[FestivalActSessionField::SET_TIME]) && ($curTime >= $session[FestivalActSessionField::START_TIME]))
                || (($session[FestivalActSessionField::END_TIME] >= $session[FestivalActSessionField::SET_TIME]) && ($curTime >= $session[FestivalActSessionField::END_TIME])))
            {
                $session = FestivalActConf::refrehSess();
            }
            if (empty($session))
            {
                return ;
                Logger::warning('FestivalAct notify sess no data');
            }
            Logger::debug('EnFestivalAct notify session:%s.', $session);
            // 看看在不在活动期间
            // 提前一天结束活动，最后一天用于领奖
            $rewardTime = FestivalActDef::REWARD_TIME;
            if (FrameworkConfig::DEBUG)
            {
            	$rewardTime = FestivalActDef::TEST_REWARD_TIME;
            }
            if ($curTime >= $session[FestivalActSessionField::START_TIME]
                && $curTime <= ($session[FestivalActSessionField::END_TIME] - $rewardTime))
            {
                FestivalActLogic::__notify($uid, $type_id, $num);
            }
            else
            {
                Logger::debug('festivalact not open.');
                return ;
            }
        }
        catch (Exception $e)
        {
		   Logger::fatal('failed to notify for uid:%d, type_id:%s, num:%s.', $uid, $type_id, $num);
	    }
    }



}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
