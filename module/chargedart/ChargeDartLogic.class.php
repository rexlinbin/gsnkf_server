<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: ChargeDartLogic.class.php 243604 2016-05-19 11:04:37Z ShuoLiu $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/chargedart/ChargeDartLogic.class.php $
 * @author $Author: ShuoLiu $(hoping@babeltime.com)
 * @date $Date: 2016-05-19 11:04:37 +0000 (Thu, 19 May 2016) $
 * @version $Revision: 243604 $
 * @brief 
 *  
 **/
class ChargeDartLogic
{
    public static function enterChargeDart($uid)
    {
        //修复timer
        self::fixChargeDart($uid);
        
        $userChargeDartObj = MyChargeDart::getInstance($uid);
        $allInfo = $userChargeDartObj->getAllInfo();
        
        //取出镖车信息
        $chargeDartInfo = $userChargeDartObj->getChargeDartInfo();
        
        $return = array(
            'have_charge_dart' => !empty($chargeDartInfo),
            'shipping_num' => $allInfo[ChargeDartDef::SQL_SHIPPING_NUM],
            'rest_ship_num' => self::getRestShipNum($uid),
            'rob_num' => $allInfo[ChargeDartDef::SQL_ROB_NUM],
            'rest_rob_num' => self::getRestRobNum($uid),
            'assistance_num' => $allInfo[ChargeDartDef::SQL_ASSISTANCE_NUM],
            'rest_assistance_num' => self::getRestAssistNum($uid),
        );
        
        //当前没有镖车，用默认的区域和页数
        if(empty($chargeDartInfo))
        {
            $return['stage_id'] = ChargeDartDef::DEFAULT_MAX_STAGE;
            $return['page_id'] = 1;
            $pageInfo = self::getOnePageInfo(ChargeDartDef::DEFAULT_MAX_STAGE, 1);
        }
        //当前有镖车，则传回玩家所在的区域和页数
        else {
            $return['stage_id'] = $chargeDartInfo[ChargeDartDef::SQL_STAGE_ID];
            $return['page_id'] = $chargeDartInfo[ChargeDartDef::SQL_PAGE_ID];
            $pageInfo = self::getOnePageInfo($return['stage_id'], $return['page_id']);
        }
        
        $return['page_info'] = $pageInfo['page_info'];
        
        Logger::debug("enterChargeDart get return is %s",$return);
        
        return $return;
        
    }
    
    public static function getOnePageInfo($stageId, $pageId, $time = 0)
    {
        if(0 == $time)
        {
            $time = Util::getTime();
        }
        //每个镖车的持续时间
        $lastTime = intval(btstore_get()->CHARGEDART_RULE[ChargeDartDef::CSV_LAST_TIME]);
        
        $pageInfo = ChargeDartDao::getUserInfoByPage($stageId, $pageId, $time-$lastTime);
        
        $arrUid = array();
        //循环取出玩家姓名和公会名字等信息
        foreach ($pageInfo as $key => $value)
        {
            $arrUid[] = $value[ChargeDartDef::SQL_UID];
        }

        $arrUserInfo = EnUser::getArrUserBasicInfo($arrUid,array('uid','uname','guild_id'));
        
        $arrGuildId = array();
        foreach($arrUid as $uid)
        {
            $arrGuildId[] = $arrUserInfo[$uid]['guild_id'];
        }
        $dbRet = GuildDao::getArrGuild($arrGuildId, array(GuildDef::GUILD_NAME,GuildDef::GUILD_ID));
        $arrGuildInfo = Util::arrayIndex($dbRet, GuildDef::GUILD_ID);
        
        foreach ($pageInfo as $key => $value)
        {
            $pageInfo[$key]['uname'] = $arrUserInfo[$value[ChargeDartDef::SQL_UID]]['uname'];
            $pageInfo[$key]['guild_id'] = $arrUserInfo[$value[ChargeDartDef::SQL_UID]]['guild_id'];
            $guildId = $arrUserInfo[$value[ChargeDartDef::SQL_UID]]['guild_id'];
            if(!empty($guildId) && (isset($arrGuildInfo[$guildId])))
            {
                $pageInfo[$key]['guild_name'] = $arrGuildInfo[$guildId][GuildDef::GUILD_NAME];
            }
        }
        
        Logger::debug("getOnePageInfo stageId[%d] pageId[%d] return is %s",$stageId,$pageId,$pageInfo);
        return array('page_info' => $pageInfo);
    }
    
    public static function getChargeDartInfo($uid,$targetUid)
    {
        $targetUserChargeDartObj = MyChargeDart::getInstance($targetUid);
        $targetUserObj = EnUser::getUserObj($targetUid);
        $targetAllInfo = $targetUserChargeDartObj->getAllInfo();
        $targetGuildId = $targetUserObj->getGuildId();
        
        $return = array(
            'uid' => $targetUid,
            'uname' => $targetUserObj->getUname(),
            'level' => $targetUserObj->getLevel(),
            'begin_time' => 0,
            'be_robbed_num' => 0,
            'have_assistance' => 0,
            'have_look_success' => 0,
            'stage_id' => 0,
            'page_id' => 0,
        );
        
        if(!empty($targetGuildId))
        {
            $guildObj = GuildObj::getInstance($targetGuildId);
            $guildName = $guildObj->getGuildName();
            $return['guild_name'] = $guildName;
        }
        
        $chargeDartInfo = $targetUserChargeDartObj->getChargeDartInfo();
        if(!empty($chargeDartInfo))
        {
            //看看玩家是否瞭望过这个车
            $beginTime = $chargeDartInfo[ChargeDartDef::SQL_BEGIN_TIME];
            $lookInfo = self::haveLooked($uid, $targetUid, $beginTime);
            $robInfo = self::haveRobbed($uid, $targetUid, $beginTime);
            
            $return['begin_time'] = $beginTime;
            $return['stage_id'] = $chargeDartInfo[ChargeDartDef::SQL_STAGE_ID];
            $return['page_id'] = $chargeDartInfo[ChargeDartDef::SQL_PAGE_ID];
            $return['have_look_success'] = $lookInfo;
            $return['have_rob_success'] = $robInfo;
            $return['be_robbed_num'] = $chargeDartInfo[ChargeDartDef::SQL_BE_ROBBED_NUM];
            $return['have_assistance'] = ($chargeDartInfo[ChargeDartDef::SQL_ASSISTANCE_UID]==0)?0:1;
            $return['assistance_uid'] = $chargeDartInfo[ChargeDartDef::SQL_ASSISTANCE_UID];
        }
        
        if ($uid == $targetUid && !empty($targetAllInfo[ChargeDartDef::SQL_ASSISTANCE_UID]))
        {
            $return['assist_uname'] = EnUser::getUserObj($targetAllInfo[ChargeDartDef::SQL_ASSISTANCE_UID])->getUname();
        }
        
        Logger::debug("getChargeDartInfo get return is %s",$return);
        
        return $return;
    }
    
    public static function ChargeDartLook($uid, $targetUid)
    {   
        $targetUserChargeDartObj = MyChargeDart::getInstance($targetUid);
        $targetUserInfo = $targetUserChargeDartObj->getAllInfo();
        
        $targetUserObj = EnUser::getUserObj($targetUid);
        $assistUid = $targetUserInfo[ChargeDartDef::SQL_ASSISTANCE_UID];
        
        $return[0] = array(
            'uid' => $targetUid,
            'uname' => $targetUserObj->getUname(),
            'level' => $targetUserObj->getLevel(),
            'fight_force' => $targetUserObj->getFightForce(),
            'have_rage' => $targetUserChargeDartObj->userHaveRage(),
            'utid' => $targetUserObj->getUtid(),
            'htid' => HeroUtil::getHtidByHid($targetUserObj->getMasterHid()),
            'guild_id' => $targetUserObj->getGuildId(),
        );
        $guildId = $targetUserObj->getGuildId();
        if (!empty($guildId))
        {
            $targetGuildObj = GuildObj::getInstance($targetUserObj->getGuildId());
            $return[0]['guild_name'] = $targetGuildObj->getGuildName();
        }
        
        if ( !empty($assistUid) )
        {
            $assistUserObj = EnUser::getUserObj($assistUid);
            $return[1] = array(
                'uid' => $assistUid,
                'uname' => $assistUserObj->getUname(),
                'level' => $assistUserObj->getLevel(),
                'fight_force' => $assistUserObj->getFightForce(),
                'have_rage' => $targetUserChargeDartObj->assistHaveRage(),
                'utid' => $assistUserObj->getUtid(),
                'htid' => HeroUtil::getHtidByHid($assistUserObj->getMasterHid()),
                'guild_id' => $assistUserObj->getGuildId(),
            );
            $guildId = $assistUserObj->getGuildId();
            if (!empty($guildId))
            {
                $assistGuildObj = GuildObj::getInstance($assistUserObj->getGuildId());
                $return[1]['guild_name'] = $assistGuildObj->getGuildName();
            }
        }
        
        //如果自己瞭望自己,协助者瞭望自己，直接返回
        if($uid == $targetUid || $uid == $assistUid)
        {
            return $return;
        }
        
        $targetChargeDartInfo = $targetUserChargeDartObj->getChargeDartInfo();
        //检查target有没有车
        if(empty($targetChargeDartInfo))
        {
            throw new FakeException("This user:%d doesn't have chargedart!Can not look",$targetUid);
        }
        
        
        //看看玩家是否瞭望过这个车
        $beginTime = $targetChargeDartInfo[ChargeDartDef::SQL_BEGIN_TIME];
        $lookInfo = self::haveLooked($uid, $targetUid, $beginTime);
        
        if ( 1 == $lookInfo)
        {
            //瞭望过，则打个日志，直接返回
            Logger::info("The user has already looked this chargedart of user:%d",$targetUid);
            return $return;
        }
        
        //瞭望花费
        $costGold = intval(btstore_get()->CHARGEDART_RULE[ChargeDartDef::CSV_LOOK_COST]);
        $userObj = EnUser::getUserObj($uid);
        //扣钱
        if (!$userObj->subGold($costGold, StatisticsDef::ST_FUNCKEY_CHARGEDART_LOOK_COST))
        {
            throw new FakeException("User doesn't have enough gold!");
        }
        
        //记录瞭望信息
        $beRobbedNum = $targetChargeDartInfo[ChargeDartDef::SQL_BE_ROBBED_NUM];
        ChargeDartDao::saveRecord($uid, $targetUid, ChargeDartDef::TYPE_LOOK, $beRobbedNum, $targetChargeDartInfo[ChargeDartDef::SQL_STAGE_ID]);
        
        $userObj->update();
        
        //返回信息
        Logger::info("The user is looking this chargedart of user:%d",$targetUid);
        return $return;
    }
    
    public static function rob($uid, $rage, $targetUid)
    {
        //玩家剩余掠夺次数检查
        if (self::getRestRobNum($uid) <= 0)
        {
            throw new FakeException("This user doesn't have enough rob num");
        }
        
        //得到锁
        $locker = new Locker();
        $locker->lock(self::getRobLockKey($targetUid));
        try {
            //被掠夺者是否有车的检查
            $targetUserChargeDarObj = MyChargeDart::getInstance($targetUid);
            $targetAllInfo = $targetUserChargeDarObj->getAllInfo();
            $targetChargeDartInfo = $targetUserChargeDarObj->getChargeDartInfo();
            if(empty($targetChargeDartInfo))
            {
                throw new FakeException("This user:%d doesn't have chargedart!Can not rob",$targetUid);
            }
        
            if ($uid == $targetAllInfo[ChargeDartDef::SQL_ASSISTANCE_UID])
            {
                throw new FakeException("This user is the assit of this chargedart[%d]!Can not rob",$targetUid);
            }
        
            //打过这辆车的检查
            if (self::haveRobbed($uid, $targetUid, $targetChargeDartInfo[ChargeDartDef::SQL_BEGIN_TIME]))
            {
                throw new FakeException("This user have rob targetuser %d!Can not rob again",$targetUid);
            }
        
            $userObj = EnUser::getUserObj($uid);
            //如果攻击方开启狂怒，则减去金币
            if ($rage)
            {
                $rageCost = intval(btstore_get()->CHARGEDART_RULE[ChargeDartDef::CSV_RAGE_COST][1]);
                if (!$userObj->subGold($rageCost, StatisticsDef::ST_FUNCKEY_CHARGEDART_ROB_RAGE_COST))
                {
                    throw new FakeException("This user doesn't have enough gold to open rage!");
                }
            }
        }
        catch(Exception $e)
        {
            //释放锁
            $locker->unlock(self::getRobLockKey($targetUid));
            throw new FakeException("do battle err:%s",$e);
        }
        
        //该车已经被掠夺次数
        $beRobbedNum = $targetChargeDartInfo[ChargeDartDef::SQL_BE_ROBBED_NUM];
        //每车最大被掠夺次数
        $maxRobedNum = intval(btstore_get()->CHARGEDART_RULE[ChargeDartDef::CSV_CAN_ROB_NUM]);
        
        //没有剩余被掠夺次数了
        if ($beRobbedNum >= $maxRobedNum)
        {
            //释放锁
            $locker->unlock(self::getRobLockKey($targetUid));
            Logger::info("This targetuser's:%d chargedart doesn't have any be robbed num!info is %s",$targetUid,$targetChargeDartInfo);
            return 'noBeRobbedNum';//TODO 和前端约定返回字段，标记这个车没有被掠夺次数
        }
        
        try {
            //拉出两个人的信息，然后战斗
            $ret = self::doBattle($uid, $rage, $targetUid);
        
            //如果成功了，则增加被掠夺者的被掠夺次数
            $success = $ret['success'];
            $atkRet1 = $ret['atkRet1'];
            $atkRet2 = $ret['atkRet2'];
            $brid1 = empty($atkRet1)?0:$atkRet1['server']['brid'];
            $brid2 = empty($atkRet2)?0:$atkRet2['server']['brid'];
            if ($success)
            {
                $targetUserChargeDarObj->addBeRobbedNum();
                $targetUserChargeDarObj->save();
                $beRobbedNum ++;
            }
            else {
                Logger::info("User rob targetuser:[%d] failed!",$targetUid);
            }
        
        }
        catch(Exception $e)
        {
            //释放锁
            $locker->unlock(self::getRobLockKey($targetUid));
            throw new FakeException("do battle err:%s",$e);
        }
        //释放锁
        $locker->unlock(self::getRobLockKey($targetUid));
        
        //发奖之类的
        if($success)
        {
            $stageId = $targetChargeDartInfo[ChargeDartDef::SQL_STAGE_ID];
            $targetLevel = EnUser::getUserObj($targetUid)->getLevel();
            $beginTime = $targetChargeDartInfo[ChargeDartDef::SQL_BEGIN_TIME];
            
            //给掠夺者发奖
            list($reward3D,$isDouble)= self::getReward($stageId, $targetLevel, 2, $beginTime, $userObj->getLevel(), 0);
            $quality = intval(btstore_get()->CHARGEDART_ITEMS[$stageId][ChargeDartDef::CSV_QUALITY]);
            RewardUtil::reward3DtoCenter($uid, $reward3D, RewardSource::CHARGEDART_REWARD_ROB,array('stageId'=>$stageId));
            
            //通知前端
            $newInfo = array(
                'stage_id' => $stageId,
                'page_id' => $targetChargeDartInfo[ChargeDartDef::SQL_PAGE_ID],
                'uid' => $targetUid,
                'uname' => EnUser::getUserObj($targetUid)->getUname(),
                'rob_uid' => $uid,
                'rob_uname' => $userObj->getUname(),
                'be_robbed_num' => $beRobbedNum,
            );
            RPCContext::getInstance()->sendFilterMessage('arena',SPECIAL_ARENA_ID::CHARGEDART, PushInterfaceDef::CHARGEDART_SEND_BEROBBED, $newInfo);
            
            //获得单次被掠夺的损失的收益 = 没有等级保护的单次掠夺收益，用于发邮件
            list($reward3DReduce,$isDouble)= self::getReward($stageId, $targetLevel, 2, $beginTime, 0, 0);
            //邮件，掠夺者
            MailTemplate::sendChargeDartRobSuccess($uid, array('uid'=>$targetUid,'uname'=>EnUser::getUserObj($targetUid)->getUname(),'utid'=>EnUser::getUserObj($targetUid)->getUtid()),
                $quality, $reward3D[0], array($brid1,$brid2));
            //邮件，被掠夺者
            MailTemplate::sendChargeDartBeRobSuccess($targetUid, array('uid'=>$uid,'uname'=>$userObj->getUname(),'utid'=>$userObj->getUtid()),
                $quality, $reward3DReduce[0], array($brid1,$brid2));
            //TODO 打日志
            Logger::info("User rob targetuser:[%d] success,targetuser's begintime is in double reward time:[%d],user's reward is [%s]",$targetUid,$isDouble,$reward3D);
        }
        
        //不管是否成功增加自己的掠夺次数
        $userChargeDartObj = MyChargeDart::getInstance($uid);
        $userChargeDartObj->addRobNum();
        
        //存战报
        $vaInfo = array(ChargeDartDef::SQL_BRID1=>$brid1,ChargeDartDef::SQL_BRID2=>$brid2,'isDouble'=>isset($isDouble)?$isDouble:0,);
        ChargeDartDao::saveRecord($uid, $targetUid, ChargeDartDef::TYPE_BATTLE, 
            $beRobbedNum, $targetChargeDartInfo[ChargeDartDef::SQL_STAGE_ID], $success?1:0, $vaInfo);
        
        //更新数据库
        $userChargeDartObj->save();
        $userObj->update();
        
        
        //返回值，注意先算协助者的，再算主人的，即1=>协助者，2=>主人
        $returnUserInfo = array(1=>array('resetHpPrecent' => 0),2=>array('resetHpPrecent' => 0));
        if (!empty($atkRet1))
        {
            $hp = 0;
            $costHp = 0;
            foreach ( $atkRet1['server']['team2']  as $key => $value )
            {
                $hp += $value['hp'];
                $costHp += $value['costHp'];
            }
            $returnUserInfo[1]['resetHpPrecent'] = intval($hp/($hp+$costHp)*100);
            $userInfo = $targetUserChargeDarObj->getAllInfo();
            $assistUserObj = EnUser::getUserObj($userInfo[ChargeDartDef::SQL_ASSISTANCE_UID]);
            $returnUserInfo[1]['uname'] = $assistUserObj->getUname();
            $returnUserInfo[1]['level'] = $assistUserObj->getLevel();
            $returnUserInfo[1]['htid'] = HeroUtil::getHtidByHid($assistUserObj->getMasterHid());
            $guildId = $assistUserObj->getGuildId();
            if(!empty($guildId))
            {
                $returnUserInfo[1]['guild_name'] = GuildObj::getInstance($assistUserObj->getGuildId())->getGuildName();
            }
        }
        $ret['atkRet1'] = empty($atkRet1)?array():array(
            'fightRet' => $atkRet1['client'],
            'appraisal' => $atkRet1['server']['appraisal'],
        );
        
        $targetUserObj = EnUser::getUserObj($targetUid);
        $returnUserInfo[2] = array(
            'uname' => $targetUserObj->getUname(),
            'level' => $targetUserObj->getLevel(),
            'htid' => HeroUtil::getHtidByHid($targetUserObj->getMasterHid()),
            'resetHpPrecent' => 100,
        );
        $guildId = $targetUserObj->getGuildId();
        if(!empty($guildId))
        {
            $returnUserInfo[2]['guild_name'] = GuildObj::getInstance($targetUserObj->getGuildId())->getGuildName();
        }
        if (!empty($atkRet2))
        {
            $hp = 0;
            $costHp = 0;
            foreach ( $atkRet2['server']['team2']  as $key => $value )
            {
                $hp += $value['hp'];
                $costHp += $value['costHp'];
            }
            $returnUserInfo[2]['resetHpPrecent'] = intval($hp/($hp+$costHp)*100);
        }
        $ret['atkRet2'] = empty($atkRet2)?array():array(
            'fightRet' => $atkRet2['client'],
            'appraisal' => $atkRet2['server']['appraisal'],
        );
        $ret['userInfo'] = $returnUserInfo;
        
        Logger::debug("user [%d] rob user [%d],return is [%s]",$uid,$targetUid,$ret);
        //返回战报信息
        return $ret;//TODO
        
    }
    
    public static function enterShipPage($uid)
    {
        $userChargeDartObj = MyChargeDart::getInstance($uid);
        $userInfo = $userChargeDartObj->getAllInfo();
        
        $chargeDartInfo = $userChargeDartObj->getChargeDartInfo();
        
        //当前有镖车则不能进入运送界面（刷新区域，邀请协助的界面）
        if (!empty($chargeDartInfo))
        {
            throw new FakeException("This user has already had a chargedart now!can not enter ship page!");
        }
        
        
        $assistanceUid = $userInfo[ChargeDartDef::SQL_ASSISTANCE_UID];
        $return = array(
            'stage_id' => ($userInfo[ChargeDartDef::SQL_STAGE_ID]==0)?1:$userInfo[ChargeDartDef::SQL_STAGE_ID],
            'refresh_num' => $userInfo[ChargeDartDef::SQL_REFRESH_NUM],
            'has_invited' => $userInfo[ChargeDartDef::SQL_HAS_INVITED],
            'assistance_uid' => $assistanceUid,
        );
        
        if ( !empty($assistanceUid) )
        {
            $assistanceUserObj = EnUser::getUserObj($assistanceUid);
            $return['assistance_uname'] = $assistanceUserObj->getUname();
            $return['assistance_level'] = $assistanceUserObj->getLevel();
            $guildId = $assistanceUserObj->getGuildId();
            if (!empty($guildId))
            {
                $assistanceGuild = GuildObj::getInstance($assistanceUserObj->getGuildId());
                $return['assistance_guildname'] = $assistanceGuild->getGuildName();
            }
        }
        
        return $return;
        
    }
    
    public static function refreshStage($uid)
    {
        $userChargeDartObj = MyChargeDart::getInstance($uid);
        $userInfo = $userChargeDartObj->getAllInfo();
        $userObj = EnUser::getUserObj($uid);
        
        $chargeDartInfo = $userChargeDartObj->getChargeDartInfo();
        
        //如果已经刷新到最大区域了，不让刷新了
        if ($userInfo[ChargeDartDef::SQL_STAGE_ID] >= ChargeDartDef::DEFAULT_MAX_STAGE)
        {
            throw new FakeException("The user has already refresh to the max stage id !");
        }
        
        //有协助者或者邀请了不让刷新 这条已经删掉，有协助者或者邀请了仍旧可以刷新
        /*if (!empty($userChargeDartObj->getInviteSomeOneFlag()))
        {
            throw new FakeException("The user has already invite some one!can not refresh!");
        }*/
        
        //当前有镖车则不能进入运送界面（刷新区域，邀请协助的界面）
        if (!empty($chargeDartInfo))
        {
            throw new FakeException("This user has already had a chargedart now!can not enter ship page!");
        }
        
        //刷新次数和消耗金币检查
        $cost = 0;
        $freeRefreshNum = btstore_get()->CHARGEDART_RULE[ChargeDartDef::CSV_FREE_REFRESH];
        $goldRefreshNum = btstore_get()->CHARGEDART_RULE[ChargeDartDef::CSV_GOLD_REFRESH];
        $useNum = true;
        $bagObj = BagManager::getInstance()->getBag($uid);
        if($userInfo[ChargeDartDef::SQL_REFRESH_NUM] >= $freeRefreshNum)
        {
            $costAry = btstore_get()->CHARGEDART_RULE[ChargeDartDef::CSV_REFRESH_ITEM]->toArray();
            $itemTemplateIdCost = $costAry[0];
            $itemTemplateIdNum = $costAry[1];
            
            if($userInfo[ChargeDartDef::SQL_REFRESH_NUM]-$freeRefreshNum >= $goldRefreshNum)
            {
                throw new FakeException("This user doesn't have any refresh num,no free ,no gold!");
            }
            $costRefreshAry = btstore_get()->CHARGEDART_RULE[ChargeDartDef::CSV_COST_REFRESH]->toArray();
            
            foreach ($costRefreshAry as $key => $value)
            {
                if ($userInfo[ChargeDartDef::SQL_REFRESH_NUM] - $freeRefreshNum + 1 >= $key)
                {
                    $cost = intval($value);
                }
            }
            //运送次数不足则先减物品
            if(!$bagObj->deleteItembyTemplateID($itemTemplateIdCost,$itemTemplateIdNum))
            {
                //物品不足则消耗金币
                if (!$userObj -> subGold($cost, StatisticsDef::ST_FUNCKEY_CHARGEDART_REFRESH_BY_GOLD))
                {
                    throw new FakeException("User doesn't have enough gold!");
                }
            }
            else{
                $useNum = false;
            }
        }
        
        
        //初始刷新
        if ($userInfo[ChargeDartDef::SQL_STAGE_ID] == 0)
        {
            $refreshPro = btstore_get()->CHARGEDART_RULE[ChargeDartDef::CSV_FIRST_REFRESH_PRO];
            //处理一下
            foreach ($refreshPro as $key => $value)
            {
                $rollary[$key] = array("weight"=>$value);
            }
            //不放回抽样,抽一次
            $rollret = Util::noBackSample($rollary,1);
            
            $nextStage = $rollret[0];
        }
        else if ($userInfo[ChargeDartDef::SQL_STAGE_ID] < ChargeDartDef::DEFAULT_MAX_STAGE)
        {
            $refreshPro = btstore_get()->CHARGEDART_RULE[ChargeDartDef::CSV_REFRESH_UPGRADE_PRO][$userInfo[ChargeDartDef::SQL_STAGE_ID]];
            //处理一下权重
            $rollary[1] = array("weight"=>$refreshPro);
            $rollary[0] = array("weight"=>ChargeDartDef::DEFAULT_MAX_WEIGHT-$refreshPro);
            //不放回抽样,抽一次
            $rollret = Util::noBackSample($rollary,1);
            
            $nextStage = ($rollret[0] == 1)?$userInfo[ChargeDartDef::SQL_STAGE_ID]+1:$userInfo[ChargeDartDef::SQL_STAGE_ID];
            
            //暗格
            $darkCheck = intval(btstore_get()->CHARGEDART_RULE[ChargeDartDef::CSV_REFRESH_DARK_CHECK]);
            $stageRefreshNum = $userInfo[ChargeDartDef::SQL_STAGE_REFRESH_NUM];
            if ($stageRefreshNum + 1 >= $darkCheck && $nextStage == $userInfo[ChargeDartDef::SQL_STAGE_ID])
            {
                //触发暗格
                $nextStage ++;
                Logger::info("the user refresh num of this stage has already be the darkcheck %d,his stageid grow up and darkcheck clear!",$darkCheck);
                $userChargeDartObj->clearDarkCheck();
            }
            else if ($nextStage != $userInfo[ChargeDartDef::SQL_STAGE_ID]){
                $userChargeDartObj->clearDarkCheck();
            }
            else {
                $userChargeDartObj->addDarkCheck();
            }
        }
        if($useNum)
        {
            $userChargeDartObj->addRereshNum();
        }
        $userChargeDartObj->changeStage($nextStage);
        
        $userChargeDartObj->save();
        $userObj->update();
        $bagObj->update();
        
        
        Logger::info("This user refresh stageid success,last stageid is [%d],now is [%d],and cost is [%d],refresh num is [%d]",$userInfo[ChargeDartDef::SQL_STAGE_ID],$nextStage,$cost,$userInfo[ChargeDartDef::SQL_REFRESH_NUM]+1);
        return array('stage_id' => $nextStage);
    }
    
    public static function inviteFriend($uid, $targetUid)
    {
        //判断是否是好友   
        if(!FriendLogic::isFriend($uid, $targetUid))
        {
            throw new FakeException("This user %d is not a friend of user %d",$targetUid,$uid);
        }
        
        //判断好友是否有剩余协助次数
        if (0 == self::getRestAssistNum($targetUid))
        {
            Logger::info("The targetuser %d doesn't have any assist num!",$targetUid);
            return 'noAssistNum';
        }
        
        //推送给好友
        $userChargeDartObj = MyChargeDart::getInstance($uid);
        $userInfo = $userChargeDartObj->getAllInfo();
        //有人接受了就不让邀请了
        if ($userInfo[ChargeDartDef::SQL_ASSISTANCE_UID] != 0)
        {
            Logger::info("Some one[%d] already accept this uesr's invite!",$userInfo[ChargeDartDef::SQL_ASSISTANCE_UID]);
            return 'someoneAccept';
        }
        
        $userObj = EnUser::getUserObj($uid);
        $saveFlag = $userChargeDartObj->getInviteSomeOneFlag();
        if(empty($saveFlag ))
        {
            //主要是为了防止出现这种情况：玩家A邀请B，B收到后一直没有接受，然后A发车了，并且结束了，这时候B再去接受的时候，无法判断B接受的状态是玩家A之前的车还是又开的新车
            //这里用一个随机的数来做标识，只有在玩家接收时，传入的flag和后端存的一致时才可以正常接受
            $flag = rand(10000, 100000);
            $userChargeDartObj->inviteSomeOne($flag);
        }
        
        $arrArg = array(
            'uid' => $uid,
            'uname' => $userObj->getUname(),
            'utid' => $userObj->getUtid(),
            'level' => $userObj->getLevel(),
            'fight_force' => $userObj->getFightForce(),
            'guild_id' => $userObj->getGuildId(),
            'stage_id' => ($userInfo[ChargeDartDef::SQL_STAGE_ID] == 0)?1:$userInfo[ChargeDartDef::SQL_STAGE_ID],
            'master_hid' => $userObj->getMasterHid(),
            'htid' => HeroUtil::getHtidByHid($userObj->getMasterHid()),
            'flag' => $userChargeDartObj->getInviteSomeOneFlag(),
        );
        if(!empty($arrArg['guild_id']))
        {
            $guildObj = GuildObj::getInstance($arrArg['guild_id']);
            $arrArg['guild_name'] = $guildObj->getGuildName();
        }
        RPCContext::getInstance()->sendMsg(array($targetUid), PushInterfaceDef::CHARGEDART_INVITE_FRIENDY, $arrArg);
        
        $userChargeDartObj->save();
        
        Logger::info("This user invite his friend [%d],and the flag is [%d]",$targetUid,$userChargeDartObj->getInviteSomeOneFlag());
        return true;
    }
    
    public static function acceptInvite($uid, $targetUid, $flag)
    {
        
        //得到锁
        $locker = new Locker();
        $locker->lock(self::getRobLockKey($targetUid));
        
        try {
            
            $targetUserChargeDartObj = MyChargeDart::getInstance($targetUid);
            $userChargeDartObj = MyChargeDart::getInstance($uid);
            $chargeDartInfo = $targetUserChargeDartObj->getChargeDartInfo();
            if(!empty($chargeDartInfo))
            {
                //有车了，则不能再加入了
                Logger::warning("This user's chargedart has already began!Can not join!",$flag);
                return 'hasBegan';
            }
            
            if ($targetUserChargeDartObj->getInviteSomeOneFlag() != intval($flag) || empty($flag))
            {
                Logger::warning("This invite info [flag:%s] is out time!Can not join!",$flag);
                return 'outTime';
            }   
        
            if (self::getRestAssistNum($uid) <= 0)
            {
                throw new FakeException("no assist num!");
            }

            $targetUserInfo = $targetUserChargeDartObj->getAllInfo();
            
            if (!empty($targetUserInfo[ChargeDartDef::SQL_ASSISTANCE_UID]))
            {
                Logger::warning("This chargedart of user %d has a assistUser!Can not join!",$targetUid);
                $locker->unlock(self::getRobLockKey($targetUid));
                return 'noPosition';
            }
            
            //设置协助者
            $targetUserChargeDartObj->setAssistUid($uid);
            //增加协助次数
            $userChargeDartObj->addAssistNum();
            
            $userChargeDartObj->save();
            $targetUserChargeDartObj->save();
            
            $arrCfgs = array($uid);
            RPCContext::getInstance()->sendMsg(array($targetUid), PushInterfaceDef::CHARGEDART_ACCEPT_INVITE, $arrCfgs);
            
        }
        catch(Exception $e)
        {
            $locker->unlock(self::getRobLockKey($targetUid));
            Logger::warning("acceptInvite err!The info is %s",$e);
            return false;
        }
        
        $locker->unlock(self::getRobLockKey($targetUid));
        
        Logger::info("This user accept his friend [%d] invite,and the flag is [%d]",$targetUid,$flag);
        return true;
    }
    
    public static function beginShipping($uid)
    {
        $userChargeDartObj = MyChargeDart::getInstance($uid);
        $userInfo = $userChargeDartObj->getAllInfo();
        $userObj = EnUser::getUserObj($uid);
        
        $chargeDartInfo = $userChargeDartObj->getChargeDartInfo();
        
        //当前有镖车则不能再次押镖
        if (!empty($chargeDartInfo))
        {
            throw new FakeException("This user has already had a chargedart now!can not begin another!");
        }
        
        $bagObj = BagManager::getInstance()->getBag($uid);
        $useItem = false;
        //运送次数不足不让运送
        if (self::getRestShipNum($uid) <= 0)
        {
            $costAry = btstore_get()->CHARGEDART_RULE[ChargeDartDef::CSV_SHIP_ITEM]->toArray();
            $itemTemplateIdCost = $costAry[0];
            $itemTemplateIdNum = $costAry[1];
            
            //运送次数不足则减物品
            if(!$bagObj->deleteItembyTemplateID($itemTemplateIdCost, $itemTemplateIdNum))
            {
                //剩余运送次数和物品都不足则报错
                throw new FakeException("user doesn't have enough ship num rest!");
            }
            $useItem = true;
        }
        
        
        $stageId = (0 == $userInfo[ChargeDartDef::SQL_STAGE_ID])?1:$userInfo[ChargeDartDef::SQL_STAGE_ID];
        //发车间隔
        $intervalTime = intval(btstore_get()->CHARGEDART_RULE[ChargeDartDef::CSV_INTERVAL]);
        //去拿在哪条路
        $beginInfo = ChargeDartDao::getFirstFreeRoad(Util::getTime()-$intervalTime, $stageId);
        
        //如果在road表中找不到插入的地方
        if(empty($beginInfo))
        {
            Logger::warning("No road!");
            return 'noRoad';//TODO和前端约定
        }
        
        $pageId = $beginInfo[ChargeDartDef::SQL_PAGE_ID];
        $roadId = $beginInfo[ChargeDartDef::SQL_ROAD_ID];
        $time = $beginInfo[ChargeDartDef::SQL_PREVIOUS_TIME];
        $beginTime = Util::getTime();
        //尝试插入新的到道路表
        if (!ChargeDartDao::changeRoadTime($stageId, $pageId, $roadId, $time, $beginTime))
        {
            Logger::warning("The road info has changed !");
            return 'hasChanged';//TODO和前端约定
        }
        
        //设定timer
        $lastTime = intval(btstore_get()->CHARGEDART_RULE[ChargeDartDef::CSV_LAST_TIME]);
        $tid = TimerTask::addTask($uid, $beginTime+$lastTime, 'chargedart.__sendReward', array($uid,$beginTime));
        if (!$useItem)
        {
        	$userChargeDartObj->addShipNum();
        }
        
        $userChargeDartObj->beginChargeDart($pageId, $roadId, $tid);
        
        $userChargeDartObj->save();
        $bagObj->update();
        
        $beginInfo[ChargeDartDef::SQL_STAGE_ID] = $stageId;
        unset($beginInfo[ChargeDartDef::SQL_PREVIOUS_TIME]);
        //TODO 通知前端
        $guildId = $userObj->getGuildId();
        $newInfo = array(
            'uid' => $uid,
            'uname' => $userObj->getUname(),
            'be_robbed_num' => 0,
            'begin_time' => $beginTime,
        );
        if(!empty($guildId))
        {
            $guildObj = GuildObj::getInstance($guildId);
            $newInfo['guild_name'] = $guildObj->getGuildName();
        }
        $newInfo = array_merge($beginInfo, $newInfo);
        RPCContext::getInstance()->sendFilterMessage('arena',SPECIAL_ARENA_ID::CHARGEDART,PushInterfaceDef::CHARGEDART_SEND_SHIP,$newInfo);

        
        Logger::info("This user begin shipping,and his chargedart info is [%s]",$userChargeDartObj->getChargeDartInfo());
        
        
        return $beginInfo;
    }
    
    public static function openRage($uid, $type)
    {
        if ($type != 0 && $type != 1)
        {
            throw new FakeException("arg type err!can not be [%d]",$type);
        }
        
        $userChargeDartObj = MyChargeDart::getInstance($uid);
        $userInfo = $userChargeDartObj->getAllInfo();
        $userObj = EnUser::getUserObj($uid);
        
        $rageCost = intval(btstore_get()->CHARGEDART_RULE[ChargeDartDef::CSV_RAGE_COST][0]);
        
        if ($type == 0)//给自己
        {
            //看看自己有没有狂怒
            if ($userChargeDartObj->userHaveRage())
            {
                throw new FakeException("User has already open rage to himself!can not open again by type %d",$type);
            }
            if (!$userObj->subGold($rageCost, StatisticsDef::ST_FUNCKEY_CHARGEDART_OPEN_RAGE_COST))
            {
                throw new FakeException("This user doesn't have enough gold to open rage!");
            }
            
            $userChargeDartObj->openUserRage();
        }
        else if($type == 1) //给协助者
        {
            //看看自己有没有狂怒
            if ($userChargeDartObj->assistHaveRage())
            {
                throw new FakeException("Assist has already open rage!can not open again by type %d",$type);
            }
            if (!$userObj->subGold($rageCost, StatisticsDef::ST_FUNCKEY_CHARGEDART_OPEN_RAGE_COST))
            {
                throw new FakeException("This user doesn't have enough gold to open rage!");
            }

            $userChargeDartObj->openAssistRage();
        }
        
        $userChargeDartObj->save();
        $userObj->update();
        
        
        Logger::info("This user open rage for [%d] success,and cost [%d]",$type,$rageCost);
        return true;
    }
    
    public static function finishByGold($uid)
    {
        $userChargeDartObj = MyChargeDart::getInstance($uid);
        $userInfo = $userChargeDartObj->getAllInfo();
        $userObj = EnUser::getUserObj($uid);
        
        $chargeDartInfo = $userChargeDartObj->getChargeDartInfo();
        
        //当前没有镖车则不能疾行
        if (empty($chargeDartInfo))
        {
            throw new FakeException("This user has not have a chargedart now!can not finish right now!");
        }
        
        $costAry = btstore_get()->CHARGEDART_RULE[ChargeDartDef::CSV_FAST_COST]->toArray();
        
        $bagObj = BagManager::getInstance()->getBag($uid);
        
        $itemTemplateIdCost = $costAry[0];
        $goldCost = $costAry[1];
        
        //消耗数量写死么？TODO
        if(!$bagObj->deleteItembyTemplateID($itemTemplateIdCost, 1))
        {
            //消耗的物品不足，则消耗金币
            if (!$userObj->subGold($goldCost, StatisticsDef::ST_FUNCKEY_CHARGEDART_FINISH_BY_GOLD))
            {
                throw new FakeException("This user doesn't have enough item and gold to finish!");
            }
        }
        
        self::__sendReward($uid, $chargeDartInfo[ChargeDartDef::SQL_BEGIN_TIME]);
        
        $bagObj->update();
        $userObj->update();
        
        //TODO 通知前端
        $finishInfo = array(
            'stage_id' => $chargeDartInfo[ChargeDartDef::SQL_STAGE_ID],
            'page_id' => $chargeDartInfo[ChargeDartDef::SQL_PAGE_ID],
            'road_id' => $userInfo[ChargeDartDef::SQL_ROAD_ID],
            'uid' => $uid,
        );
        RPCContext::getInstance()->sendFilterMessage('arena',SPECIAL_ARENA_ID::CHARGEDART,PushInterfaceDef::CHARGEDART_FINISH_BYGOLD,$finishInfo);
        
        Logger::info("This user has finished his chargedart by gold!his chargedart info is [%s]",$chargeDartInfo);
        
        return true;
    }
    
    public static function __sendReward($uid,$beginTime)
    {
        $userObj = EnUser::getUserObj($uid);
        $userChargeDartObj = MyChargeDart::getInstance($uid);
        $userInfo = $userChargeDartObj->getAllInfo();
        $beginTime = intval($beginTime);
        
        if($userInfo[ChargeDartDef::SQL_BEGIN_TIME] == $beginTime)
        {
            //得到锁
            $locker = new Locker();
            $locker->lock(self::getRobLockKey($uid));
            
            try {
                //清除镖车信息
                $userChargeDartObj->clearChargeDartInfo();
                $userChargeDartObj->save();
            }
            catch(Exception $e)
            {
                $locker->unlock(self::getRobLockKey($uid));
                throw new FakeException("clear chargedart info err!info is [%s]",$e);
            }
            
            $locker->unlock(self::getRobLockKey($uid));
            
            $stageId = $userInfo[ChargeDartDef::SQL_STAGE_ID];
            $quality = intval(btstore_get()->CHARGEDART_ITEMS[$stageId][ChargeDartDef::CSV_QUALITY]);
            $beRobbedNum = $userInfo[ChargeDartDef::SQL_BE_ROBBED_NUM];
            //TODO 发奖
            
            
            
            //先发主人的
            list($reward3D1,$isDouble) = self::getReward($stageId, $userObj->getLevel(), 0, $beginTime, 0, $beRobbedNum);
            RewardUtil::reward3DtoCenter($uid, $reward3D1, RewardSource::CHARGEDART_REWARD_USER,array('stageId'=>$stageId));
            
            //获得损失的收益，发邮件用，损失的收益=不被掠夺的收益-实际获得的收益
            list($reward3D,$isDouble) = self::getReward($stageId, $userObj->getLevel(), 0, $beginTime, 0, 0);
            foreach ($reward3D[0] as $key => $value)
            {
                $reward3D[0][$key][2] -=  $reward3D1[0][$key][2];
            }
            MailTemplate::sendChargeDartUserFinish($uid, $reward3D1[0], $reward3D[0]);
            
            //再发协助者的
            if (!empty($userInfo[ChargeDartDef::SQL_ASSISTANCE_UID]))
            {
                $assistUserObj = EnUser::getUserObj($userInfo[ChargeDartDef::SQL_ASSISTANCE_UID]);
                list($reward3D2,$isDouble) = self::getReward($stageId, $userObj->getLevel(), 1, $beginTime,0, $beRobbedNum);
                RewardUtil::reward3DtoCenter($userInfo[ChargeDartDef::SQL_ASSISTANCE_UID], $reward3D2, RewardSource::CHARGEDART_REWARD_ASSIST,array('stageId'=>$stageId));
                
                MailTemplate::sendChargeDartAssistFinish($userInfo[ChargeDartDef::SQL_ASSISTANCE_UID], array('uid'=>$uid,'uname'=>$userObj->getUname(),'utid'=>$userObj->getUtid()), $reward3D2[0]);
            }
            
            //达阵也要存下
            ChargeDartDao::saveRecord($uid, $userInfo[ChargeDartDef::SQL_ASSISTANCE_UID],
                ChargeDartDef::TYPE_FINISH, $userInfo[ChargeDartDef::SQL_BE_ROBBED_NUM], $stageId, 1,
                array('isDouble'=>$isDouble));
            
            //TODO 打日志
            Logger::info("The user[%d] and his assist friend[%d] finish chargedart,and his begintime[%d] is in doubletime[%d],his chargedart has been robbed[%d] times,they reward are:user[%s],assist[%s]",
                $uid,$userInfo[ChargeDartDef::SQL_ASSISTANCE_UID],$beginTime,$isDouble,$userInfo[ChargeDartDef::SQL_BE_ROBBED_NUM],$reward3D1,isset($reward3D2)?$reward3D2:array());
        }
        
    }
    
    public static function buyRobNum($uid, $num = 1)
    {
        $num = intval($num);
        if ($num <= 0)
        {
            throw new FakeException("num can not smaller than or equal with zero!");
        }
        
        $userChargeDartObj = MyChargeDart::getInstance($uid);
        $userInfo = $userChargeDartObj->getAllInfo();
        $userObj = EnUser::getUserObj($uid);
        $buyRobNum = $userInfo[ChargeDartDef::SQL_BUY_ROB_NUM];
        
        $maxBuyRobNum = intval(btstore_get()->CHARGEDART_RULE[ChargeDartDef::CSV_GOLD_ROB_NUM]);
        $costAry = btstore_get()->CHARGEDART_RULE[ChargeDartDef::CSV_COST_ROB_NUM];
        
        if($buyRobNum >= $maxBuyRobNum)
        {
            throw new FakeException("user doesn't have any gold rob num to buy!");
        }
        
        $cost = 0;
        for ($i = 1;$i <= $num;$i++)
        {
            foreach ($costAry as $key => $value)
            {
                if ($buyRobNum + $i >= $key)
                {
                    $costPer = $value;
                }
            }  
            $cost += $costPer;
        }
        
        if (!$userObj->subGold($cost, StatisticsDef::ST_FUNCKEY_CHARGEDART_BUY_ROB))
        {
            throw new FakeException("This user doesn't have enough gold!");
        }
        $userObj->update();
        
        $userChargeDartObj->addBuyRobNum($num);
        
        $userChargeDartObj->save();
        
        Logger::info("This user buyRobNum:[%d] success,and totalcost [%d],his last buyRobNum is [%d],and now is [%d]",$num,$cost,$buyRobNum,$buyRobNum + $num);
        return true;
    }
    
    public static function buyShipNum($uid, $num = 1)
    {
        $num = intval($num);
        if ($num <= 0)
        {
            throw new FakeException("num can not smaller than or equal with zero!");
        }
        
        $userChargeDartObj = MyChargeDart::getInstance($uid);
        $userInfo = $userChargeDartObj->getAllInfo();
        $userObj = EnUser::getUserObj($uid);
        $buyShipNum = $userInfo[ChargeDartDef::SQL_BUY_SHIPPING_NUM];
        
        $maxBuyShipNum = intval(btstore_get()->CHARGEDART_RULE[ChargeDartDef::CSV_GOLD_SHIP_NUM]);
        $costAry = btstore_get()->CHARGEDART_RULE[ChargeDartDef::CSV_COST_SHIP_NUM];
        
        if($buyShipNum + $num > $maxBuyShipNum)
        {
            throw new FakeException("user doesn't have any gold ship num to buy!");
        }
        
        $cost = 0;
        for ($i = 1;$i <= $num;$i++)
        {
            foreach ($costAry as $key => $value)    
            {
                if ($buyShipNum + $i >= $key)
                {
                    $costPer = $value;
                }
            }
            $cost += $costPer;
        }
        
        if (!$userObj->subGold($cost, StatisticsDef::ST_FUNCKEY_CHARGEDART_BUY_SHIP))
        {
            throw new FakeException("This user doesn't have enough gold!");
        }
        $userObj->update();
        
        $userChargeDartObj->addBuyShipNum($num);
        
        $userChargeDartObj->save();
        
        Logger::info("This user buyShipNum:[%d] success,and totalcost [%d],his last buyShipNum is [%d],and now is [%d]",$num,$cost,$buyShipNum,$buyShipNum+$num);
        return true;
    }
    
    public static function buyAssistanceNum($uid, $num = 1)
    {
        $num = intval($num);
        if ($num <= 0)
        {
            throw new FakeException("num can not smaller than or equal with zero!");
        }
        
        $userChargeDartObj = MyChargeDart::getInstance($uid);
        $userInfo = $userChargeDartObj->getAllInfo();
        $userObj = EnUser::getUserObj($uid);
        $buyAssistNum = $userInfo[ChargeDartDef::SQL_BUY_ASSISTANCE_NUM];
        
        $maxBuyAssistNum = intval(btstore_get()->CHARGEDART_RULE[ChargeDartDef::CSV_GOLD_ASSIT_NUM]);
        $costAry = btstore_get()->CHARGEDART_RULE[ChargeDartDef::CSV_COST_ASSIT_NUM];
        
        if($buyAssistNum + $num > $maxBuyAssistNum)
        {
            throw new FakeException("user doesn't have any gold assist num to buy!");
        }
        
        $cost = 0;
        for ($i = 1;$i <= $num;$i++)
        {
            foreach ($costAry as $key => $value)
            {
                if ($buyAssistNum + $i >= $key)
                {
                    $costPer = $value;
                }
            }
            $cost += $costPer;
        }
        
        
        if (!$userObj->subGold($cost, StatisticsDef::ST_FUNCKEY_CHARGEDART_BUY_ASSIST))
        {
            throw new FakeException("This user doesn't have enough gold!");
        }
        $userObj->update();
        
        $userChargeDartObj->addBuyAssistNum($num);
        
        $userChargeDartObj->save();
        
        Logger::info("This user buyAssistanceNum:[%d] success,and totalcost [%d],his last buyAssistanceNum is [%d],and now is [%d]",$num,$cost,$buyAssistNum,$buyAssistNum+$num);
        return true;
    }
    
    
    /*public static function getThisChargeDartInfo($targetUid)
    {
        
    }*/
    
    public static function getStageInfo($stageId)
    {
        $stageId = intval($stageId);
        $config = btstore_get()->CHARGEDART_ITEMS->toArray();
        if (!array_key_exists($stageId, $config))
        {
            throw new FakeException("The stageid:%d or config:%s is err!",$stageId,$config);
        }
        
        $data = ChargeDartDao::getBattleRecordByStage($stageId);
        
        //循环处理一下，获得角色名字
        foreach ($data as $key => $value)
        {
            $uid = $value[ChargeDartDef::SQL_UID];
            $uname = EnUser::getUserObj($uid)->getUname();
            $beUid = $value[ChargeDartDef::SQL_BE_UID];
            $beUname = EnUser::getUserObj($beUid)->getUname();
            
            $data[$key]['uname'] = $uname;
            $data[$key]['beUname'] = $beUname;
            $data[$key]['brid1'] = isset($value[ChargeDartDef::SQL_VA_INFO][ChargeDartDef::SQL_BRID1])?$value[ChargeDartDef::SQL_VA_INFO][ChargeDartDef::SQL_BRID1]:0;
            $data[$key]['brid2'] = isset($value[ChargeDartDef::SQL_VA_INFO][ChargeDartDef::SQL_BRID2])?$value[ChargeDartDef::SQL_VA_INFO][ChargeDartDef::SQL_BRID2]:0;
            $data[$key]['isDouble'] = isset($value[ChargeDartDef::SQL_VA_INFO]['isDouble'])?$value[ChargeDartDef::SQL_VA_INFO]['isDouble']:0;
            unset($data[$key][ChargeDartDef::SQL_VA_INFO]);
        }
        
        return $data;
    }
    
    public static function getAllMyInfo($uid)
    {
        $info = ChargeDartDao::getAllRecordByUid($uid);
        
        if (empty($info))
        {
            return $info;
        }
        
        //按照时间排序，留100个
        $time = array();
        foreach ($info as $key => $value)
        {
            $time[$key] = $value[ChargeDartDef::SQL_TIME];
            
            $uid = $value[ChargeDartDef::SQL_UID];
            $uname = EnUser::getUserObj($uid)->getUname();
            $beUid = $value[ChargeDartDef::SQL_BE_UID];
            if (!empty($beUid))
            {
                $beUname = EnUser::getUserObj($beUid)->getUname();
                $info[$key]['beUname'] = $beUname;
            }
            $info[$key]['uname'] = $uname;
            
            $info[$key]['brid1'] = isset($value[ChargeDartDef::SQL_VA_INFO][ChargeDartDef::SQL_BRID1])?$value[ChargeDartDef::SQL_VA_INFO][ChargeDartDef::SQL_BRID1]:0;
            $info[$key]['brid2'] = isset($value[ChargeDartDef::SQL_VA_INFO][ChargeDartDef::SQL_BRID2])?$value[ChargeDartDef::SQL_VA_INFO][ChargeDartDef::SQL_BRID2]:0;
            $info[$key]['isDouble'] = isset($value[ChargeDartDef::SQL_VA_INFO]['isDouble'])?$value[ChargeDartDef::SQL_VA_INFO]['isDouble']:0;
            unset($info[$key][ChargeDartDef::SQL_VA_INFO]);
            
        }
        
        array_multisort($time,SORT_DESC,$info);
        
        return $info;
        
    }
    
    public static function fixChargeDart($uid)
    {
        $userChargeDartObj = MyChargeDart::getInstance($uid);
        $allInfo = $userChargeDartObj->getAllInfo();
        
        $lastTime = intval(btstore_get()->CHARGEDART_RULE[ChargeDartDef::CSV_LAST_TIME]);
        if(!empty($allInfo[ChargeDartDef::SQL_BEGIN_TIME]) && Util::getTime() > $lastTime + $allInfo[ChargeDartDef::SQL_BEGIN_TIME])
        {
            $tid = $allInfo[ChargeDartDef::SQL_TID];
            try
            {
                if (!EnTimer::checkTask($tid, 'chargedart.__sendReward'))
                {
                    EnTimer::resetTask($tid);
                }
            }
            catch (Exception $e)
            {
                //nothing
            }
        }
    }
    
    
    private static function doBattle($uid, $rage, $targetUid)
    {
        $attributeGrow = btstore_get()->CHARGEDART_RULE[ChargeDartDef::CSV_RAGE_GROW]->toArray();
        
        $targetUserChargeDartObj = MyChargeDart::getInstance($targetUid);
        $targetChargeDartInfo = $targetUserChargeDartObj->getChargeDartInfo();
        
        if(empty($targetChargeDartInfo))
        {
            Logger::info("The state of target user:%d has changed!",$targetUid);
            return array();
        }
        
        $assistUid = $targetChargeDartInfo[ChargeDartDef::SQL_ASSISTANCE_UID];
        
        $user = EnUser::getUserObj($uid);
        $userFmt = $user->getBattleFormation();
        $userFF = $user->getFightForce();
        //玩家,掠夺方的血量要继承
        foreach ( $userFmt[ 'arrHero' ]  as $key => $value )
        {
            $userHp[$key] = $value[PropertyKey::MAX_HP];
        }
        if ($rage)
        {
            $userFmt = self::addRageBuff($userFmt, $attributeGrow);
        }
        
        $atkRet1 = array();
        $isSuccess1 = true;
        //先和协助者打
        if( !empty($assistUid) )
        {
            $assistUser = EnUser::getUserObj($assistUid);
            $assistFmt = $assistUser->getBattleFormation();
            $assistFF = $assistUser->getFightForce();
            
            //战力高的先手
            $atkType = EnBattle::setFirstAtk(0, $userFF >= $assistFF);
            
            //属性加成
            if ($targetUserChargeDartObj->assistHaveRage())
            {
                $assistFmt = self::addRageBuff($assistFmt, $attributeGrow);
            }
            $atkRet1 = EnBattle::doHero($userFmt, $assistFmt, $atkType);
            
            //更新玩家。掠夺方的血量
            foreach ( $atkRet1['server']['team1']  as $key => $value )
            {
                $userHp[$key] = $value['hp'];
            }
            $isSuccess1 = BattleDef::$APPRAISAL[$atkRet1['server']['appraisal']] <= BattleDef::$APPRAISAL['D'];
        }
        
        $atkRet2 = array();
        $isSuccess2 = false;
        //打过协助者了或者没有协助者，才能打主人
        if (($isSuccess1 && !empty($assistUid)) || empty($assistUid)) 
        {
            //再和主人打
            $tragetUser = EnUser::getUserObj($targetUid);
            $targetFmt = $tragetUser->getBattleFormation();
            $targetFF = $tragetUser->getFightForce();
            //玩家,掠夺方的血量要继承
            foreach ( $userFmt[ 'arrHero' ]  as $key => $value )
            {
                $userFmt[ 'arrHero' ][$key][PropertyKey::CURR_HP] = $userHp[$key];
            }
            //战力高的先手
            $atkType = EnBattle::setFirstAtk(0, $userFF >= $targetFF);
            
            //属性加成
            if ($targetUserChargeDartObj->userHaveRage())
            {
                $targetFmt = self::addRageBuff($targetFmt, $attributeGrow);
            }
            $atkRet2 = EnBattle::doHero($userFmt, $targetFmt, $atkType);
            
            $isSuccess2 = BattleDef::$APPRAISAL[$atkRet2['server']['appraisal']] <= BattleDef::$APPRAISAL['D'];
        }
        
        return array(
            'success' => ($isSuccess1 && $isSuccess2)?1:0,
            'atkRet1' => $atkRet1,
            'atkRet2' => $atkRet2,
        );
    }
    
    //用来隔天检查使用，尽量每次操作都要调用一次
    private static function checkIsOtherDay($uid)
    {
        $userChargeDartObj = MyChargeDart::getInstance($uid);
        
        $cmp_time = $userChargeDartObj->getCmpTime();
        
        //隔天检查
        if ($cmp_time > 0 && !Util::isSameDay($cmp_time))
        {
            $userChargeDartObj->changeByCrossDay();
            $userChargeDartObj->save();
        }
    }
    
    //对于一个时间内的车，判断某个人是否瞭望过
    private static function haveLooked($uid, $targetUid, $time)
    {
        $lookCount = ChargeDartDao::getLookRecord($uid, $targetUid, $time);
        return empty($lookCount)?0:1;
    }
    
    //对于一个时间内的车，判断某个人是否成功抢夺过
    private static function haveRobbed($uid, $targetUid, $time)
    {
        $robCount = ChargeDartDao::getBattleRecord($uid, $targetUid, $time);
        return empty($robCount)?0:1;
    }

    //获得剩余运送次数
    private static function getRestShipNum($uid)
    {
        $userChargeDartObj = MyChargeDart::getInstance($uid);
        $userInfo = $userChargeDartObj->getAllInfo();
        $freeNum = btstore_get()->CHARGEDART_RULE[ChargeDartDef::CSV_FREE_SHIP_NUM];
        
        return ($userInfo[ChargeDartDef::SQL_BUY_SHIPPING_NUM] + $freeNum - $userInfo[ChargeDartDef::SQL_SHIPPING_NUM]);
    }
    
    private static function getRestRobNum($uid)
    {
        $userChargeDartObj = MyChargeDart::getInstance($uid);
        $userInfo = $userChargeDartObj->getAllInfo();
        $freeNum = btstore_get()->CHARGEDART_RULE[ChargeDartDef::CSV_FREE_ROB_NUM];
    
        return ($userInfo[ChargeDartDef::SQL_BUY_ROB_NUM] + $freeNum - $userInfo[ChargeDartDef::SQL_ROB_NUM]);
    }
    
    private static function getRestAssistNum($uid)
    {
        $userChargeDartObj = MyChargeDart::getInstance($uid);
        $userInfo = $userChargeDartObj->getAllInfo();
        $freeNum = btstore_get()->CHARGEDART_RULE[ChargeDartDef::CSV_FREE_ASSIT_NUM];
        
        return ($userInfo[ChargeDartDef::SQL_BUY_ASSISTANCE_NUM] + $freeNum - $userInfo[ChargeDartDef::SQL_ASSISTANCE_NUM]);
    }
    
    /**
     * 获得配置表的奖励
     * @param unknown $stage_id
     * @param unknown $level
     * @param unknown $who 0主人，1协助者，2掠夺者
     * @param number $beRobbedNum
     * 注意：因为有向下取整的操作，所以要先双倍主人奖励之后再乘以玩家获得的百分比再向下取整
     */
    private static function getReward($stage_id,$level,$who,$beginTime,$userLevel = 0,$beRobbedNum = 0)
    {
        //取出奖励
        $reward4D = btstore_get()->CHARGEDART_ITEMS[$stage_id][ChargeDartDef::CSV_REWARD]->toArray();
        $robRedoction = intval(btstore_get()->CHARGEDART_ITEMS[$stage_id][ChargeDartDef::CSV_ROB_REWARD]);
        $assistRewardPro = intval(btstore_get()->CHARGEDART_ITEMS[$stage_id][ChargeDartDef::CSV_ASSIT_REWARD]);
        $levelProtect = btstore_get()->CHARGEDART_RULE[ChargeDartDef::CSV_LEVEL_PROTECT]->toArray();
        $specialReward4D = btstore_get()->CHARGEDART_ITEMS[$stage_id][ChargeDartDef::CSV_SPECIAL_REWARD]->toArray();
        $specialRewardOnce4D = btstore_get()->CHARGEDART_ITEMS[$stage_id][ChargeDartDef::CSV_SPECIAL_ONCE_REWARD]->toArray();
        $levelRedoction = 0;
        //按等级取奖励
        foreach ($reward4D as $l => $_3D)
        {
            if($level >= $l)
            {
                $reward3D = $_3D;
            }
        }
        
        //按等级取奖励 特殊奖励
        $specialReward3D = array();
        foreach ($specialReward4D as $l => $_3D)
        {
        	if($level >= $l)
        	{
        		$specialReward3D = $_3D;
        	}
        }
        //按等级取奖励 特殊奖励 单次
        $specialRewardOnce3D = array();
        foreach ($specialRewardOnce4D as $l => $_3D)
        {
        	if($level >= $l)
        	{
        		$specialRewardOnce3D = $_3D;
        	}
        }
        
        //在双倍时间内，则双倍下
        $isDouble = self::isDoubleReward($beginTime);
        if ($isDouble)
        {
            foreach ($reward3D as $key => $value)
            {
                $reward3D[$key][2] = $value[2]*2;
            }
        }
        
        switch ($who)
        {
            case 0:
                foreach ($reward3D as $key => $value)
                {
                    $reward3D[$key][2] = $reward3D[$key][2] - intval($reward3D[$key][2]*$beRobbedNum*$robRedoction/ChargeDartDef::DEFAULT_MAX_WEIGHT);
                }
                //2016年5月11日17:56:22 新加的特殊奖励
                foreach ($specialReward3D as $key => $value)
                {
                	if(isset($specialRewardOnce3D[$key]) && $specialReward3D[$key][1] ==  $specialRewardOnce3D[$key][1])
                	{
                		$specialReward3D[$key][2] -= $specialRewardOnce3D[$key][2] * $beRobbedNum;
                	}
                }
                $reward3D = array_merge($reward3D,$specialReward3D);
                break;
            case 1:
                foreach ($reward3D as $key => $value)
                {
                    $restReward = $reward3D[$key][2] - intval($reward3D[$key][2]*$beRobbedNum*$robRedoction/ChargeDartDef::DEFAULT_MAX_WEIGHT);
                    $reward3D[$key][2] = intval($restReward*($assistRewardPro/ChargeDartDef::DEFAULT_MAX_WEIGHT));
                }
                break;
            case 2:
                //等级保护的检查 2016年5月12日13:39 策划说要改，改成5-9 500 10-14 600...
                $diffLevel = $userLevel - $level;
                $levelRedoction = 0;
                //配置表的内容是array(5=>500,10=>600,15=>700...)
                //计算方法是，1-5 500，6-10 600 XXX   以这个为准5-9 500 10-14 600...
                /*if ($diffLevel > 0)
                {
                    foreach ($levelProtect as $key => $value)
                    {
                        $levelRedoction = $value;
                        if ($diffLevel <= $key)
                        {
                            break;
                        }
                    }
                }*/
                //以这个为准5-9 500 10-14 600...
                foreach ($levelProtect as $key => $value)
                {
                	if ($diffLevel >= $key)
                	{
                		$levelRedoction = $value;
                	}
                }
                foreach ($reward3D as $key => $value)
                {
                    $reward3D[$key][2] = intval($reward3D[$key][2]*
                        ($robRedoction/ChargeDartDef::DEFAULT_MAX_WEIGHT)*
                        (1-$levelRedoction/ChargeDartDef::DEFAULT_MAX_WEIGHT));
                }
                //2016年5月11日17:56:22 新加的特殊奖励
                $reward3D = array_merge($reward3D,$specialRewardOnce3D);
                
                break;
        }
        
        //检查一遍
        foreach ($reward3D as $key => $value)
        {
            if ($value[2] <= 0)
            {
                unset($reward3D[$key]);
            }
        }
        if(empty($reward3D))
        {
            Logger::warning("The reward:[%d] in config is err:[%s],isDouble[%d],robRedoction[%d],levelRedoction[%d],assistRewardPro[%d]",
                $who,$reward3D,$isDouble,$robRedoction,$levelRedoction,$assistRewardPro);
        }
        
        
        return array(array($reward3D),$isDouble);
    }
    
    //判断是否是在双倍时间内，并返回双倍后的奖励
    private static function isDoubleReward($beginTime)
    {
        $doubleTime = btstore_get()->CHARGEDART_RULE[ChargeDartDef::CSV_DOUBLE_TIME]->toArray();
        
        $time0 = strtotime(date("Y-m-d"));
        $diffSecond = $beginTime-$time0;
        $isDouble = 0;
        
        foreach ($doubleTime as $value)
        {
            if ($diffSecond >= $value[0] && $diffSecond <= $value[1])
            {
                $isDouble = 1;
            }
        }
        
        return $isDouble;
    }
    
    private static function addRageBuff($battleInfo,$attributeGrow)
    {
        if (empty($attributeGrow))
        {
            Logger::fatal("config err!the rage grow is empty!");
            return $battleInfo;
        }
        
        $newAttribute = HeroUtil::adaptAttr($attributeGrow);
        
        foreach ($battleInfo['arrHero'] as $key => $value)
        {
            foreach ($newAttribute as $k => $v)
            {
                if (isset($value[$k]))
                {
                    $battleInfo['arrHero'][$key][$k] += $v;
                }
                else {
                    $battleInfo['arrHero'][$key][$k] = $v;
                }
            }
        }
        
        return $battleInfo;
    }
    
    private static function getRobLockKey($targetUid)
    {
        return 'ChargeDartRob'.$targetUid;
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */