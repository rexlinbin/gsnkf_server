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
class WorldGrouponLogic
{

    public static function getInfo($uid, $type)
    {
        $confObj = WorldGrouponConfObj::getInstance();
        $stage = $confObj->getStage();

        if($stage == WorldGrouponDef::STAGE_INVALID)
        {
            throw new FakeException("invalid stage:[%s]", $stage);
        }

        $ret = array();
        $ret["stage"] = $stage;
        $ret["buy_start_time"] = $confObj->getBuyBgnTime();
        $ret["buy_end_time"] = $confObj->getBuyEndTime();
        if($stage == WorldGrouponDef::STAGE_TEAM || $stage == WorldGrouponDef::STAGE_REWARD)
        {
            return $ret;
        }

        $serverId = Util::getServerIdOfConnection();
        $teamId = WorldGrouponUtil::getTeamIdByServerId($serverId);
        if(empty($teamId))
        {
            Logger::warning('not in team');
            return array("stage" => WorldGrouponDef::STAGE_INVALID);
        }

        $crossInfoObj = WorldGrouponCrossInfo::getInstance();
        $crossInfo = $crossInfoObj->getTeamInfo4Front($teamId);
        $ret["crossInfo"] = $crossInfo;

        if($type == 0)
        {
            $userInfoObj = WorldGrouponInnerUser::getInstance($uid);
            $point = $userInfoObj->getPoint();
            $coupon = $userInfoObj->getCoupon();
            $his = $userInfoObj->getHis();
            foreach($his as $index => $eachHis)
            {
                $his[$index] = self::convertHisForFront($eachHis);
            }
            $pointReward = $userInfoObj->getPointReward();
            $ret["userInfo"] = array(
                "point" => $point,
                "coupon" => $coupon,
                "his" => $his,
                "pointReward" => $pointReward,
            );
            $userInfoObj->update();
        }

        $crossInfoObj->update();
        return $ret;
    }

    public static function buy($uid, $goodId, $num)
    {
        $serverId = Util::getServerIdOfConnection();
        $teamId = WorldGrouponUtil::getTeamIdByServerId($serverId);
        if(empty($teamId))
        {
            throw new FakeException("your server not in any team.");
        }

        $confObj = WorldGrouponConfObj::getInstance();
        $stage = $confObj->getStage();
        if($stage != WorldGrouponDef::STAGE_BUY)
        {
            throw new FakeException("not buy stage");
        }

        $dayOfActivity = $confObj->getDayOfActivity();
        if($dayOfActivity < 0)
        {
            throw new InterException("not buy stage");
        }
        $extraConf = $confObj->getExtraConf();
        if(!empty($extraConf[WorldGrouponCsvDef::DAY][$dayOfActivity + 1]))
        {
            $arrGoodOfToday = $extraConf[WorldGrouponCsvDef::DAY][$dayOfActivity + 1];
            Logger::trace("WorldGrouponLogic::buy dayOfActivity:[%d] arrGoodOfToday:[%s]", $dayOfActivity, $arrGoodOfToday);
            if(!in_array($goodId, $arrGoodOfToday))
            {
                throw new FakeException("goodId:[%d] not sale today arrGoodOfToday:[%s]", $goodId, $arrGoodOfToday);
            }
        }
        else
        {
            throw new FakeException("nothing to sale today");
        }

        $userInfoObj = WorldGrouponInnerUser::getInstance($uid);
        $hasBuyNumToday = $userInfoObj->calGoodBuyNumToday($goodId);
        $goodConf = $confObj->getGoodConfById($goodId);
        $buyNumLimit = $goodConf[WorldGrouponCsvDef::NUM];
        if($hasBuyNumToday + $num > $buyNumLimit)
        {
            throw new FakeException("buyNumLimit:[%d] reached hasBuyNumToday:[%d] num:[%d]", $buyNumLimit, $hasBuyNumToday, $num);
        }

        $bag = BagManager::getInstance()->getBag($uid);
        if($bag->isFull())
        {
            throw new FakeException("bag is full");
        }

        $crossInfoObj = WorldGrouponCrossInfo::getInstance();
        $goodNum = $crossInfoObj->getGoodNum($teamId, $goodId);
        $forgeNum = $crossInfoObj->getForgeNum($teamId, $goodId);
        $finalNum = $goodNum + $forgeNum;
        Logger::trace("goodNum:[%d] forgeNum:[%d] finalNum:[%d]", $goodNum, $forgeNum, $finalNum);
        $curPrice = $crossInfoObj->getCurPriceOfGoodId($teamId, $goodId);

        /**
         * 优先使用购物券，其次使用金币,赠送积分和的数量等于购买时价格 当前可用券 = 当前物品价格 * 用券比例
         */
        $curCoupon = $userInfoObj->getCoupon();
        $costGold = 0;
        $costCoupon = 0;
        $tmpUsefulCoupon = intval($curPrice * $num * $goodConf[WorldGrouponCsvDef::COUPON_USE_RATE] / UNIT_BASE);
        $usefulCoupon = min($curCoupon, $tmpUsefulCoupon);
        Logger::trace("curCoupon:[%d] tmpUsefulCoupon:[%d] usefulCoupon:[%d]", $curCoupon, $tmpUsefulCoupon, $usefulCoupon);
        if($usefulCoupon - $curPrice * $num >= 0)
        {
            $costCoupon = $curPrice * $num;
        }
        else
        {
            $costCoupon = $usefulCoupon;
            $costGold = $curPrice * $num - $costCoupon;
        }

        if($costCoupon > 0)
        {
            $userInfoObj->subCoupon($costCoupon);
            Logger::info("WorldGrouponLogic buy subCoupon:[%d]", $costCoupon);
        }
        $userObj = EnUser::getUserObj($uid);
        if($costGold > 0)
        {
            if($userObj->subGold($costGold, StatisticsDef::ST_FUNCKEY_WORLD_GROUPON_BUY) == false)
            {
                throw new FakeException("WorldGrouponLogic buy subGold:[%d] failed", $costGold);
            }
        }
        /**
         * 发物品，加积分，加团购券
         */
        $userInfoObj->addPoint($curPrice * $num);
        $addCoupon = intval($curPrice * $num * $goodConf[WorldGrouponCsvDef::RETURN_RATE] / UNIT_BASE);
        $userInfoObj->addCoupon($addCoupon);
        Logger::info("WorldGrouponLogic buy reward[%s], addPoint:[%d] addCoupon[%d]",
            $goodConf[WorldGrouponCsvDef::ITEM], $curPrice * $num, $addCoupon);
        /**
         * 加团购总数量
         */
        $crossInfoObj->buy($teamId, $goodId, $num);
        $userInfoObj->buy();

        $hisInfo = array(
            WorldGrouponSqlDef::GOOD_ID_IN_VA_INFO => $goodId,
            WorldGrouponSqlDef::NUM_IN_VA_INFO => $num,
            WorldGrouponSqlDef::GOLD_IN_VA_INFO => $costGold,
            WorldGrouponSqlDef::COUPON_IN_VA_INFO => $costCoupon,
            WorldGrouponSqlDef::BUY_TIME_IN_VA_INFO => Util::getTime(),
        );

        /**
         * 先扣钱 防止没扣钱就标记购买
         */
        $userObj->update();
        $userInfoObj->addHis($hisInfo);
        $userInfoObj->update();

        $itemConf = $goodConf[WorldGrouponCsvDef::ITEM];
        foreach($itemConf as $index => $oneItemConf)
        {
            $itemConf[$index][2] = $num * $oneItemConf[2];
        }

        Logger::trace("itemConf:%s", $itemConf);
        RewardUtil::reward3DArr($uid, $itemConf, StatisticsDef::ST_FUNCKEY_WORLD_GROUPON_BUY_REWARD);
        $userObj->update();
        $bag->update();
        $crossInfoObj->update();
        
        // 跨服团购积分统计 - $point传的是当前总积分，这个积分是每天重置的
        $pintNum = $userInfoObj->getPoint();
        EnFestivalAct::notify($uid, FestivalActDef::TASK_WORLD_GROUPON_POINT, $pintNum);
        
        return self::convertHisForFront($hisInfo);
    }

    public static function recReward($uid, $rewardId)
    {
        $serverId = Util::getServerIdOfConnection();
        $teamId = WorldGrouponUtil::getTeamIdByServerId($serverId);
        if(empty($teamId))
        {
            throw new FakeException("your server not in any team.");
        }

        $confObj = WorldGrouponConfObj::getInstance();
        $stage = $confObj->getStage();
        if($stage != WorldGrouponDef::STAGE_BUY)
        {
            throw new FakeException("not buy stage");
        }

        $bag = BagManager::getInstance()->getBag($uid);
        if($bag->isFull())
        {
            throw new FakeException("bag is full");
        }

        $userInfoObj = WorldGrouponInnerUser::getInstance($uid);
        $curPoint = $userInfoObj->getPoint();
        if($curPoint < $rewardId)
        {
            throw new FakeException("WorldGrouponLogic recReward curPoint:[%d] not enough need:[%d]", $curPoint, $rewardId);
        }
        if($userInfoObj->ifPointRewardReceivied($rewardId) == true)
        {
            throw new FakeException("WorldGrouponLogic recReward rewardId:[%d] has been received", $rewardId);
        }

        /**
         * 发奖励，做领奖记录
         */
        $extraConf = $confObj->getExtraConf();
        $reward = $extraConf[WorldGrouponCsvDef::POINT_REWARD][$rewardId];

        $userObj = EnUser::getUserObj($uid);

        $userInfoObj->recReward($rewardId);
        $userInfoObj->update();
        RewardUtil::reward3DArr($uid, $reward, StatisticsDef::ST_FUNCKEY_WORLD_GROUPON_REC_REWARD);
        $userObj->update();
        $bag->update();

        return "ok";
    }

    public static function forgeGoodNum($teamId, $goodId, $forgeNum)
    {
        WorldGrouponDao::updCrossInfo4Plat($teamId, $goodId, $forgeNum);
    }

    public static function getTeamInfo4Plat()
    {
        $crossInfo4Plat = WorldGrouponDao::getCrossInfo4Plat();
        $confObj = WorldGrouponConfObj::getInstance();
        $ret = array();
        /**
         * 给平台拼上物品名称，策划要求
         */
        foreach($crossInfo4Plat as $teamInfo)
        {
            $teamId = $teamInfo[WorldGrouponSqlDef::TBL_FIELD_TEAM_ID];
            $goodId = $teamInfo[WorldGrouponSqlDef::TBL_FIELD_GOOD_ID];
            $tmpGoodConf = $confObj->getGoodConfById($goodId);
            $teamInfo[WorldGrouponCsvDef::NAME] = $tmpGoodConf[WorldGrouponCsvDef::NAME];
            $ret[$teamId][$goodId] = $teamInfo;
        }
        return $ret;
    }

    /**
     * 将购买历史记录,给前端转一下map,应前端要求
     * @param $his
     * @return array
     */
    private static function convertHisForFront($his)
    {
        return array_combine(WorldGrouponDef::$ARR_FOR_FRONT, $his);
    }

}

class WorldGrouponScriptLogic
{
    /**
     * 同属配置的分组数据，将没有在配置中的服务器自动分组
     *
     * @param boolean $commit
     * @throws InterException
     */
    public static function syncAllTeamFromPlat2Cross($commit = TRUE)
    {
        // 是否处在分组阶段
        $confObj = WorldGrouponConfObj::getInstance(WorldGrouponField::CROSS);
        if ($confObj->getStage() != WorldGrouponDef::STAGE_TEAM)
        {
            Logger::warning('WORLD_GROUPON SYNC_ALL_TEAM : not in stage team, can not sync.');
            return;
        }

        // 得到配置的分组数据和所有服务器信息
        $arrCfgTeamInfo = array();
        $startTime = $confObj->getPeriodBgnTime();
        $arrMyTeamInfo = WorldGrouponUtil::getAllTeamInfo();
        ksort($arrMyTeamInfo);
        $allServerInfo = ServerInfoManager::getInstance()->getAllServerInfo();
        ksort($allServerInfo);
        Logger::info('WORLD_GROUPON SYNC_ALL_TEAM : all my team info[%s]', $arrMyTeamInfo);
        Logger::info('WORLD_GROUPON SYNC_ALL_TEAM : all server info[%s]', $allServerInfo);

        if (!empty($arrMyTeamInfo))
        {
            Logger::warning('WORLD_GROUPON SYNC_ALL_TEAM : already have valid team[%s], return', $arrMyTeamInfo);
            return ;
        }

        // 找到配置的当前最大分组teamId
        $curMaxTeamId = 0;
        $orginMaxTeamId = $curMaxTeamId;

        // 得到需要自动分组的服务器
        $tmpAllServerInfo = $allServerInfo;
        Logger::info('WORLD_GROUPON SYNC_ALL_TEAM : all new server info[%s]', $tmpAllServerInfo);

        // 去掉开服日期不符合要求的
        $needOpenDuration = $confObj->getNeedOpenDays();
        foreach ($tmpAllServerInfo as $aServerId => $aInfo)
        {
            $aOpenTime = $aInfo['open_time'];
            $referTime = $startTime;
            $betweenDays = intval((strtotime(date("Y-m-d", $referTime)) - strtotime(date("Y-m-d", $aOpenTime))) / SECONDS_OF_DAY);

            //去掉开服时间在活动开始时间之后的
            if ($betweenDays < $needOpenDuration)
            {
                unset($tmpAllServerInfo[$aServerId]);
                Logger::info('WORLD_GROUPON SYNC_ALL_TEAM : server id[%d] skip, open time[%s], start time[%s].', $aServerId, date("Y-m-d", $aOpenTime), date("Y-m-d", $startTime));
            }
        }
        Logger::info('WORLD_GROUPON SYNC_ALL_TEAM : all new server info after open days filter[%s]', $tmpAllServerInfo);

        // 将剩余的服务器自动分组，合服的要在同一个组里
        if (!empty($tmpAllServerInfo))
        {
            // 处理合服的情况，db -> array(serverId...)
            $arrDb2Info = array();
            foreach ($tmpAllServerInfo as $aServerId => $aInfo)
            {
                if (!isset($arrDb2Info[$aInfo['db_name']]))
                {
                    $arrDb2Info[$aInfo['db_name']] = array();
                }
                $arrDb2Info[$aInfo['db_name']][] = $aServerId;
            }
            Logger::info('WORLD_GROUPON SYNC_ALL_TEAM : db 2 info of new server[%s]', $arrDb2Info);

            // 处理正常的分组
            $minCount = defined('PlatformConfig::WORLD_GROUPON_TEAM_MIN_COUNT') ? PlatformConfig::WORLD_GROUPON_TEAM_MIN_COUNT : 48;
            $maxCount = defined('PlatformConfig::WORLD_GROUPON_TEAM_MAX_COUNT') ? PlatformConfig::WORLD_GROUPON_TEAM_MAX_COUNT : 52;
            Logger::info('WORLD_GROUPON SYNC_ALL_TEAM : min server count[%d], max server count[%d]', $minCount, $maxCount);

            $curServerCount = 0;
            $curTeamNeedCount = mt_rand($minCount, $maxCount);
            $curTeamId = ++$curMaxTeamId;
            Logger::info('WORLD_GROUPON SYNC_ALL_TEAM : generate new team[%d], new team server count[%d]', $curTeamId, $curTeamNeedCount);
            $arrExclude = array();
            foreach ($tmpAllServerInfo as $aServerId => $aInfo)
            {
                if (in_array($aServerId, $arrExclude))
                {
                    continue;
                }

                if ($curServerCount >= $curTeamNeedCount)
                {
                    $curServerCount = 0;
                    $curTeamNeedCount = mt_rand($minCount, $maxCount);
                    $curTeamId = ++$curMaxTeamId;
                    Logger::info('WORLD_GROUPON SYNC_ALL_TEAM : generate new team[%d], new team server count[%d]', $curTeamId, $curTeamNeedCount);
                }

                $arrCfgTeamInfo[$curTeamId][] = $aServerId;
                Logger::info('WORLD_GROUPON SYNC_ALL_TEAM : generate new team[%d], add a normal server[%d]', $curTeamId, $aServerId);
                foreach ($arrDb2Info[$aInfo['db_name']] as $aMergeServerId)
                {
                    if ($aMergeServerId == $aServerId)
                    {
                        continue;
                    }
                    $arrCfgTeamInfo[$curTeamId][] = $aMergeServerId;
                    $arrExclude[] = $aMergeServerId;
                    Logger::info('WORLD_GROUPON SYNC_ALL_TEAM : generate new team[%d], add a merge server[%d]', $curTeamId, $aMergeServerId);
                }
                ++$curServerCount;
            }

            // 处理当最后一个分组个数没有达到最低个数的情况，就直接塞到最后一组吧
            if ($curServerCount < $minCount)
            {
                if (isset($arrCfgTeamInfo[$curTeamId - 1]))
                {
                    $arrCfgTeamInfo[$curTeamId - 1] = array_merge($arrCfgTeamInfo[$curTeamId - 1], $arrCfgTeamInfo[$curTeamId]);
                    unset($arrCfgTeamInfo[$curTeamId]);
                    Logger::info('SYNC_ALL_TEAM : cur team[%d] count[%d] less than min[%d], add to last', $curTeamId, $curServerCount, $minCount);
                }
            }
        }

        ksort($arrCfgTeamInfo);
        Logger::info('WORLD_GROUPON SYNC_ALL_TEAM : final team info[%s]', $arrCfgTeamInfo);

        // 更新跨服库分组信息
        foreach ($arrCfgTeamInfo as $aTeamId => $arrServerId)
        {
            foreach ($arrServerId as $aServerId)
            {
                if (!isset($allServerInfo[$aServerId]))
                {
                    Logger::fatal('WORLD_GROUPON SYNC_ALL_TEAM : no server info of teamId[%d], serverId[%d], skip.', $aTeamId, $aServerId);
                }
                else
                {
                    if ($commit)
                    {
                        $arrField = array
                        (
                            WorldGrouponSqlDef::TBL_FIELD_TEAM_ID => $aTeamId,
                            WorldGrouponSqlDef::TBL_FIELD_SERVER_ID => $aServerId,
                            WorldGrouponSqlDef::TBL_FIELD_UPDATE_TIME => $startTime + 1,
                        );
                        WorldGrouponDao::insertTeamInfo($arrField);
                    }
                    Logger::info('WORLD_GROUPON SYNC_ALL_TEAM : sync teamdId[%d] server[%d] success.', $aTeamId, $aServerId);
                }
            }
        }
        Logger::info('WORLD_GROUPON SYNC_ALL_TEAM : sync team info from plat to cross done');
    }

    /**
     * 补发团购金币差价和最后一天未领取的积分奖励
     */
    public static function reward($commit = TRUE, $group)
    {
        // 是否处在分组阶段
        $confObj = WorldGrouponConfObj::getInstance(WorldGrouponField::CROSS);
        if ($confObj->getStage() != WorldGrouponDef::STAGE_REWARD)
        {
            Logger::warning('WORLD_GROUPON reward : not in stage reward, can not reward.');
            return;
        }

        $serverId = 0;
        $allTeamId = WorldGrouponUtil::getAllTeamId();
        Logger::info("WorldGrouponScriptLogic reward start. serverId:[%d]", $allTeamId);

        $crossInfo = WorldGrouponCrossInfo::getInstance();

        $allUserInfo = WorldGrouponDao::selectAllInnerUserInfo();
        foreach($allUserInfo as $userInfo)
        {
            $uid = $userInfo[WorldGrouponSqlDef::TBL_FIELD_UID];
            try
            {
                $innerUserInfo = WorldGrouponInnerUser::getInstance($uid);
                $his = $innerUserInfo->getHis();
                $rewardTime = $innerUserInfo->getRewardTime();
                if($rewardTime > $confObj->getActivityStartTime())
                {
                    Logger::warning("WorldGrouponScriptLogic reward:: reward have been send to uid:[%d] rewardTime:[%d]", $uid, $rewardTime);
                    continue;
                }

                /**
                 * 返还金币差价
                 */
                $userObj = EnUser::getUserObj($uid);
                $serverId = $userObj->getServerId();
                if(!isset($allTeamId[$serverId]))
                {
                    throw new InterException(" WorldGrouponScriptLogic reward:: not data of serverId:[%d] ", $serverId);
                }
                $teamId = $allTeamId[$serverId][WorldGrouponSqlDef::TBL_FIELD_TEAM_ID];
                $totalGold = 0;

                /**
                 * 用于缓存某个商品的价格
                 * [teamId => array[goodId => price]]
                 */
                $arrPrice = array();

                foreach($his as $index => $eachHisInfo)
                {
                    $goodId = $eachHisInfo[WorldGrouponSqlDef::GOOD_ID_IN_VA_INFO];  //物品Id
                    $gold = $eachHisInfo[WorldGrouponSqlDef::GOLD_IN_VA_INFO];   //花费金币
                    $num = $eachHisInfo[WorldGrouponSqlDef::NUM_IN_VA_INFO];   //数量

                    if(isset($arrPrice[$teamId][$goodId]))
                    {
                        $curPrice = $arrPrice[$teamId][$goodId];
                    }
                    else
                    {
                        $curPrice = $crossInfo->getCurPriceOfGoodId($teamId, $goodId);
                        $arrPrice[$teamId][$goodId] = $curPrice;
                    }

                    if($gold - $curPrice * $num > 0)
                    {
                        $totalGold += $gold - $curPrice * $num;
                    }
                }
                Logger::info("WorldGrouponScriptLogic reward uid:[%d] totalGold:[%d] his[%s]", $uid, $totalGold, $his);

                /**
                 * 返还最后一天积分奖励
                 */
                /*Logger::info("WorldGrouponScriptLogic reward uid:[%d] reissuePointReward", $uid);
                $innerUserInfo->reissuePointReward();*/
                /**
                 * 记录金币差价发奖时间
                 */
                $innerUserInfo->setRewardTime();

                if($commit)
                {
                    $innerUserInfo->update();
                    if($totalGold > 0)
                    {
                        //发送奖励到奖励中心
                        RewardLogic::sendReward($uid, RewardSource::WORLD_GROUPON_PURCHASE_GOLD, array(RewardType::GOLD => $totalGold));
                    }
                }
            }
            catch(Exception $e)
            {
                Logger::fatal("WorldGrouponScriptLogic reward exception when uid:[%d] exception:[%s] trace:[%s] ",
                    $uid, $e->getMessage(), $e->getTraceAsString());
            }

        }

        Logger::info("WorldGrouponScriptLogic reward ok");
    }

}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */