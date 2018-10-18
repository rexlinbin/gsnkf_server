<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $$Id: DragonManager.class.php 218503 2015-12-29 10:32:55Z BaoguoMeng $$
 *
 **************************************************************************/

 /**
 * @file $$HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/dragon/DragonManager.class.php $$
 * @author $$Author: BaoguoMeng $$(hoping@babeltime.com)
 * @date $$Date: 2015-12-29 10:32:55 +0000 (Tue, 29 Dec 2015) $$
 * @version $$Revision: 218503 $$
 * @brief 
 *  
 **/

/**
 * $dragonInfo array
 * [
 *  uid:int 玩家uid
 *  last_time:int 玩家上次寻龙探宝时间
 *  act:int 剩余行动力
 *  resetnum:int 当天已重置次数
 *  hp_pool:int 血池
 *  point:int 积分
 *  total_point:int 总积分
 *  floor:int 层
 *  posid:int 玩家当前坐标
 *  va_data:array 当前地图
 *      [
 *          'arrhp'=>array(), 0=>array(10000, 1), 1=>array(20000, 0)...array(事件id,状态0已走过，1未走过)
 *      ]
 *  va_ff:array 战斗相关
 * ]
 * Class DragonManager
 */
class DragonManager
{
    private static $uid = 0;
    private $posid = 0;
    private $dragonInfo = NULL;
    private $dragonBuffer = NULL;
    private static $instance = NULL;

    private function __construct($uid)
    {
        if(empty($uid))
        {
            $uid = RPCContext::getInstance()->getUid();
        }
        self::$uid = $uid;
        if(EnSwitch::isSwitchOpen(SwitchDef::DRAGON) == false)
        {
            throw new FakeException('user:%d does not open the dragon', $uid);
        }
        $this->initData();
        $this->clrResetNum();
        $this->rfrOnceMaxPoint();
    }

    public static function getInstance($uid)
    {
        if(self::$uid != 0 && self::$uid != $uid)
        {
            throw new FakeException(' invalid uid:%d, self::uid:%d ', $uid, self::$uid);
        }
        if(empty(self::$instance))
        {
            self::$instance = new self($uid);
        }
        return self::$instance;
    }

    public static function release($uid)
    {
    	self::$instance = NULL;
    }

    private function initData()
    {
        $this->checkUid();
        $dragonInfo = DragonDao::loadData(self::$uid);  //dao
        Logger::trace('get dragonInfo from db, dragonInfo:%s', $dragonInfo);
        if(empty($dragonInfo))
        {
            $dragonInfo = $this->resetData();
            Logger::trace('init dragonInfo data, dragonInfo:%d', $dragonInfo);
            DragonDao::insert($dragonInfo);
            Logger::trace('insert dragonInfo:%s', $dragonInfo);
        }
       
        $this->dragonInfo = $dragonInfo;
        $this->dragonBuffer = $dragonInfo;
        
        if(empty($dragonInfo[TblDragonDef::VA_BF]))
        {
        	$dragonInfo = $this->resetData();
        	Logger::info('empty VA_BF. reset data');
        }
    }

    public function resetData($mode=DragonDef::DEFAULT_MODE)
    {
        $vip = EnUser::getUserObj(self::$uid)->getVip();
        $floor = ($mode == DragonDef::DEFAULT_MODE) ? DragonDef::INIT_FLOOR : DragonDef::TRIAL_FLOOR;
        $dragonInfo = array(
            TblDragonDef::UID => self::$uid,
            TblDragonDef::LASTTIME => Util::getTime(),
            TblDragonDef::ACT => btstore_get()->DRAGON[$floor][DragonCsvDef::INITACT],
            TblDragonDef::BUY_ACT_NUM => DragonDef::INIT_BUYACTNUM,
            TblDragonDef::FREE_AI_NUM => btstore_get()->VIP[$vip]['aiExploreFreeNum'],
            TblDragonDef::BUY_HP_NUM => DragonDef::INIT_BUYHPNUM,
            TblDragonDef::POINT => DragonDef::INIT_POINT,
            TblDragonDef::HP_POOL => $this->initHpPool(),
            TblDragonDef::FLOOR => $floor,
            TblDragonDef::POSID => $this->initActorPos($floor),
            TblDragonDef::HASMOVE => DragonDef::HASMOVENO,
            TblDragonDef::VA_DATA => $this->initVaData($floor),
            TblDragonDef::VA_BF => $this->initVaBf(),
        );
        if(isset($this->dragonInfo[TblDragonDef::TOTAL_POINT]))
        {
            $dragonInfo[TblDragonDef::TOTAL_POINT] = $this->dragonInfo[TblDragonDef::TOTAL_POINT];
        }
        else
        {
            $dragonInfo[TblDragonDef::TOTAL_POINT] = DragonDef::INIT_TOTAL_POINT;
        }

        //重置次数
        if(isset($this->dragonInfo[TblDragonDef::RESETNUM]))
        {
            $dragonInfo[TblDragonDef::RESETNUM] = $this->dragonInfo[TblDragonDef::RESETNUM];
        }
        else
        {
            $dragonInfo[TblDragonDef::RESETNUM] = DragonDef::INIT_RESETNUM;
        }

        //免费重置次数
        if(isset($this->dragonInfo[TblDragonDef::FREERESETNUM]))
        {
            $dragonInfo[TblDragonDef::FREERESETNUM] = $this->dragonInfo[TblDragonDef::FREERESETNUM];
        }
        else
        {
            $dragonInfo[TblDragonDef::FREERESETNUM] = DragonDef::INIT_FREERESETNUM;
        }

        //新增加的字段 1模式
        $dragonInfo[TblDragonDef::MODE] = $mode;
        //新增加的字段 2历史单次寻龙最大积分
        if(!empty($this->dragonInfo[TblDragonDef::ONCE_MAX_POINT]))
        {
            $dragonInfo[TblDragonDef::ONCE_MAX_POINT] = $this->dragonInfo[TblDragonDef::ONCE_MAX_POINT];
        }
        else
        {
            $dragonInfo[TblDragonDef::ONCE_MAX_POINT] = $this->dragonInfo[TblDragonDef::POINT];
        }

        $this->dragonInfo = $dragonInfo;

        return $dragonInfo;
    }

    private function initMapOfTheFloor($floor)
    {
        //起点
        $arrActor = btstore_get()->DRAGON[$floor][DragonCsvDef::ACTERPOS]->toArray();
        //当前事件=起点事件
        $curEvent = array('id'=>$arrActor[$this->posid]['id'], 'data'=>array(), 'point'=>0, 'other'=>array()); //入口事件

        $map = array();
        $usedPos = array();
        $usedPos[] = $this->posid;

        //起点
        $map[$this->posid] = array('eid'=>$arrActor[$this->posid]['id'], 'status'=>DragonDef::$INIT_EVENT_STATUS);

        //宝箱
        for($i = 1; $i <= DragonDef::BOXNUM; $i++)
        {
            $box = btstore_get()->DRAGON[$floor]["box". "$i". "pos"]->toArray();
            $boxid = array();
            for($j = 0; $j < count($box); $j++)
            {
                $boxid = Util::noBackSample($box, 1, 'w');
                if(empty($boxid))
                {
                    break;
                }
                if(isset($boxid[0]))
                {
                    if(!in_array($boxid[0], $usedPos))
                    {
                        $usedPos[] = $boxid[0];
                        $map[$boxid[0]] = array('eid'=>$box[$boxid[0]]['id'], 'status'=>DragonDef::$INIT_EVENT_STATUS);
                        break;
                    }
                }
            }
        }

        //事件
        $height = btstore_get()->DRAGON[$floor][DragonCsvDef::HEIGHT];
        $width = btstore_get()->DRAGON[$floor][DragonCsvDef::WIDTH];
        for($i = 0; $i < $height * $width; $i++)
        {
            if(in_array($i, $usedPos))
            {
                continue;
            }
            $j = $i + 1;
            $events = btstore_get()->DRAGON[$floor]["pos" . "$j" . "event"]->toArray();
            $eventid = Util::noBackSample($events, 1, 'w');
            $map[$i] = array('eid'=>$events[$eventid[0]]['id'], 'status'=>DragonDef::$INIT_EVENT_STATUS);
        }
        ksort($map);
        Logger::trace('init map:%s', $map);
        return array($map, $curEvent);
    }

    private function initHpPool()
    {
        $hpPool = $this->getInitMaxHpPool();
        $hpPool *= btstore_get()->DRAGON[DragonDef::INIT_FLOOR][DragonCsvDef::INITHP] / 100;
        return $hpPool;
    }

    private function initActorPos($floor)
    {
        $actorTmp = btstore_get()->DRAGON[$floor][DragonCsvDef::ACTERPOS]->toArray();
        $pos = Util::noBackSample($actorTmp, 1, 'w');
        $posid = $pos[0];
        $this->posid = $posid;
        Logger::trace('initActorPos posid:%d', $this->posid);
        return $posid;
    }

    private function initVaData($floor=DragonDef::INIT_FLOOR)
    {
        $va_data = array();
        $va_data[DragonDef::ARRHP] = $this->getArrHeroHp();
        $va_data[DragonDef::ARRADDTION] = array();  //武力加成等
        $map = $this->initMapOfTheFloor($floor);
        $va_data[DragonDef::CUREVENT] = $map[1];
        $va_data[DragonDef::MAP][$floor - 1] = $map[0];
        return $va_data;
    }

    /**
     * 重置寻龙试炼
     * 虽然有很多地方和reset相似，但是
     * 考虑到这个和普通寻龙是平级的关系，而且保持老代码兼容不太容易，故而新写一个
     */
    public function resetTrial($uid)
    {
        $openDragonMinPoint = btstore_get()->NORMAL_CONFIG[NormalConfigDef::CONFIG_ID_OPEN_DRAGON_MIN_POINT];
        if($this->dragonInfo[TblDragonDef::ONCE_MAX_POINT] < $openDragonMinPoint)
        {
            throw new FakeException('once_Max_Point:%d has not reach openDragonMinPoint:%d',
                $this->dragonInfo[TblDragonDef::ONCE_MAX_POINT], $openDragonMinPoint);
        }
        $this->dealResetNum($uid);
        $this->resetData(DragonDef::TRIAL_MODE);
        return $this->getMap();
    }

    private function dealResetNum($uid)
    {
        $freeResetNum = $this->dragonInfo[TblDragonDef::FREERESETNUM];
        $bag = BagManager::getInstance()->getBag($uid);
        $resetItemConf = btstore_get()->NORMAL_CONFIG[NormalConfigDef::CONFIG_ID_DRAGON_RESET_ITEM]->toArray();
        $resetItem = key($resetItemConf);

        if($freeResetNum > 0)
        {
            $this->subFreeResetNum();
        }
        else if($bag->getItemNumByTemplateID($resetItem) > 0)
        {
            if($bag->deleteItembyTemplateID($resetItem, $resetItemConf[$resetItem]) == false)
            {
                throw new FakeException("delResetItem failed item:%d num:%d", $resetItem, $resetItemConf[$resetItem]);
            }
        }
        else
        {
            $vip = EnUser::getUserObj($uid)->getVip();
            $resetNum = $this->dragonInfo[TblDragonDef::RESETNUM];
            $resetNumLimit = btstore_get()->VIP[$vip]['exploreLongNum'];
            if($resetNum >= $resetNumLimit)
            {
                throw new FakeException('your reset num:%d has reached limit num:%d', $resetNum, $resetNumLimit);
            }

            $needGold = btstore_get()->DRAGON[$this->dragonInfo[TblDragonDef::FLOOR]][DragonCsvDef::RESETPAY];
            if($uid != self::$uid)
            {
                throw new FakeException('uid:%d is not equal with self::uid:%d', $uid, self::$uid);
            }
            $userObj = EnUser::getUserObj();
            if($userObj->subGold($needGold, StatisticsDef::ST_FUNCKEY_DRAGON_RESET) == false)
            {
                throw new FakeException('Dragon reset act subGold failed');
            }

            $this->updResetNum($resetNum+1);
        }
        $bag->update();
    }

    private function initVaBf()
    {
        $userBf = $this->getUserBf();
        Logger::trace('userBf:%s', $userBf);
        return $userBf;
    }

    public function clrResetNum()
    {
        if(!Util::isSameDay($this->getLastTime()))
        {
            $this->updResetNum(DragonDef::INIT_RESETNUM);
            $this->updFreeAiNum();  //一键寻龙免行动力次数
            $this->incRreeResetNum();
            $this->updDragonTime();
            Logger::trace('clrResetNum lastTime:%d, thisTime:%d', $this->getLastTime(), Util::getTime());
        }
    }

    public function updResetNum($resetNum)
    {
        $this->dragonInfo[TblDragonDef::RESETNUM] = $resetNum;
    }

    private function updFreeAiNum()
    {
        $vip = EnUser::getUserObj(self::$uid)->getVip();
        $this->dragonInfo[TblDragonDef::FREE_AI_NUM] = btstore_get()->VIP[$vip]['aiExploreFreeNum'];
    }

    public function incRreeResetNum()
    {
        $vip = EnUser::getUserObj(self::$uid)->getVip();
        if($this->dragonInfo[TblDragonDef::FREERESETNUM] >= btstore_get()->VIP[$vip]['maxExploreLongNum'])
        {
            return;
        }
        $this->dragonInfo[TblDragonDef::FREERESETNUM] += 1;
    }

    public function subFreeResetNum()
    {
        $this->dragonInfo[TblDragonDef::FREERESETNUM] -= 1;
    }
    
    public function getFreeResetNum()
    {
    	return $this->dragonInfo[TblDragonDef::FREERESETNUM];
    }

    /**
     * 当前阵容+继承血量hp
     * @return mixed
     * @throws InterException
     */
    public function getVaBf($delDeadBody = false)
    {
        $userVaBf = $this->dragonInfo[TblDragonDef::VA_BF];

        $arrHeroHp = $this->getArrHeroHpFromDragonInfo();
        $arrAddtion = $this->getAddtionOfVaData();
        if(empty($arrHeroHp))
        {
            throw new InterException('arrHeroHp is empty!');
        }

        foreach($userVaBf['arrHero'] as $pos => $heroInfo)
        {
            $hid = $heroInfo[PropertyKey::HID];
            if( $delDeadBody && $arrHeroHp[$hid] < 1 )
            {
            	unset( $userVaBf['arrHero'][$pos] );
            	continue;
            }
            $userVaBf['arrHero'][$pos][PropertyKey::CURR_HP] = $arrHeroHp[$hid];
            foreach($arrAddtion as $key => $addtion)
            {
                $userVaBf['arrHero'][$pos][$key] += $addtion;
            }
        }
        return $userVaBf;
    }

    public function getCurPos()
    {
        return $this->dragonInfo[TblDragonDef::POSID];
    }

    public function updDragonTime()
    {
        $this->dragonInfo[TblDragonDef::LASTTIME] = Util::getTime();
    }

    /**
     * 上次探宝时间
     */
    private function getLastTime()
    {
        return $this->dragonInfo[TblDragonDef::LASTTIME];
    }

    public function save()
    {
        $this->checkUid();
        if($this->dragonInfo != $this->dragonBuffer)
        {
            DragonDao::update($this->dragonInfo, self::$uid);
            Logger::trace('update dragonInfo:%s', $this->dragonInfo);
        }
        return true;
    }

    private function checkUid()
    {
        if(empty(self::$uid))
        {
            throw new InterException('the uid is empty!');
        }
        return true;
    }

    private function checkPos($posid)
    {
        if($this->dragonInfo[TblDragonDef::POSID] != $posid)
        {
            throw new FakeException('invalid posid%d, current posid:%d', $posid, $this->dragonInfo[TblDragonDef::POSID]);
        }
    }

    private function getUserBf()
    {
        $this->checkUid();
        return EnUser::getUserObj(self::$uid)->getBattleFormation();
    }

    private function getArrHeroHp()
    {
        $arrHeroHp = array();
        $userBf = $this->getUserBf();
        foreach($userBf['arrHero'] as $pos => $heroInfo)
        {
            $hid = $heroInfo[PropertyKey::HID];
            $hp = $heroInfo[PropertyKey::MAX_HP];
            $arrHeroHp[$hid] = $hp;
        }
        Logger::trace('arrHeroHp:%s', $arrHeroHp);
        return $arrHeroHp;
    }

    private function getArrHeroHpFromDragonInfo()
    {
        if(isset($this->dragonInfo[TblDragonDef::VA_DATA]['arrhp']))
        {
            return $this->dragonInfo[TblDragonDef::VA_DATA]['arrhp'];
        }
        return array();
    }

    public function getMap()
    {
        $ret = array();
        $curEventOfVaData = $this->getCurEventOfVaData();
        $other = array();
        if(isset($curEventOfVaData['other']))
        {
            $other = $curEventOfVaData['other'];
        }
        $event = $this->getEvent($this->getCurPos());
        if(isset($event[DragonEventCsvDef::CONDITION][0]) && $event[DragonEventCsvDef::CONDITION][0] == DragonDef::EVENT_TYPE_FB)
        {
            $other = array('fb' => $this->getFbEnemyInfo($curEventOfVaData['data']));
        }

        $ret['map'] = $this->dragonInfo[TblDragonDef::VA_DATA][DragonDef::MAP][$this->dragonInfo[TblDragonDef::FLOOR]-1];
        //避免前端amf解码问题
        foreach($ret['map'] as $key => $value )
        {
        	if( isset($value['status']) )
        	{
        		$value['status']['dummy'] = 0;
        		unset($value['status']['dummy']);
        		$ret['map'][$key] = $value;
        	}
        }
        $ret['posid'] = $this->getCurPos();
        $ret['mode'] = $this->dragonInfo[TblDragonDef::MODE];
        $ret['floor'] = $this->dragonInfo[TblDragonDef::FLOOR];
        $ret['hppool'] = $this->dragonInfo[TblDragonDef::HP_POOL];
        $ret['resetnum'] = $this->dragonInfo[TblDragonDef::RESETNUM];
        $ret['free_reset_num'] = $this->dragonInfo[TblDragonDef::FREERESETNUM];
        $ret['free_ai_num'] = $this->dragonInfo[TblDragonDef::FREE_AI_NUM];
        $ret['buyactnum'] = $this->dragonInfo[TblDragonDef::BUY_ACT_NUM];
        $ret['buyhpnum'] = $this->dragonInfo[TblDragonDef::BUY_HP_NUM];
        $ret['act'] = $this->dragonInfo[TblDragonDef::ACT];
        $ret['point'] = $this->dragonInfo[TblDragonDef::POINT];
        $ret['total_point'] = $this->dragonInfo[TblDragonDef::TOTAL_POINT];
        $ret['once_max_point'] = $this->dragonInfo[TblDragonDef::ONCE_MAX_POINT];
        $ret['hasmove'] = $this->dragonInfo[TblDragonDef::HASMOVE];
        $ret['movedata'] = $other;
        return $ret;
    }

    /**
     * 1.位置是否可走 2.当前位置是否是出口 3.是否有行动力 4.是否还有血 5.是否是墙 6.换位特殊情况 7.寻龙试炼模式，不应该配下一层事件
     * @param int $posid 要走到的位置id
     * @param bool $isEventTypeHW 是否是换位事件
     * @return bool
     * @throws InterException
     * @throws FakeException
     */
    public function canMove($posid, $isEventTypeHW = false)
    {
        if($posid < DragonDef::MIN_POSID || $posid > DragonDef::MAX_POSID)
        {
            throw new FakeException('illegal posid:%d', $posid);
        }
        $curPosid = $this->getCurPos();

        if(!$isEventTypeHW)
        {
            if(abs($curPosid - $posid) != 1
                && abs($curPosid - $posid) != btstore_get()->DRAGON[$this->dragonInfo[TblDragonDef::FLOOR]][DragonCsvDef::HEIGHT]
            )
            {
                throw new FakeException('posid:%d can not reached, current curPosid:%d, height:%d', $posid, $curPosid, btstore_get()->DRAGON[$this->dragonInfo[TblDragonDef::FLOOR]][DragonCsvDef::HEIGHT]);
            }
        }

        $curEvent = $this->getOriginalEvent($curPosid);
        $curEventType = $curEvent[DragonEventCsvDef::CONDITION][0];
        if($curEventType == DragonDef::EVENT_TYPE_EXIT)
        {
            throw new FakeException(' curEventType is exit, you can not move ' );
        }

        $event = $this->getOriginalEvent($posid);
        $eventType = $event[DragonEventCsvDef::CONDITION][0];

        //寻龙试炼模式不能有下一层事件
        if($this->dragonInfo[TblDragonDef::MODE] == DragonDef::TRIAL_MODE && $eventType == DragonDef::EVENT_TYPE_XC)
        {
            throw new FakeException('error config@cehua, trial mode should not contain next floor event.');
        }

        if( $eventType == DragonDef::EVENT_TYPE_WALL)
        {
            throw new FakeException(' posid:%d can not reached, eventtype is %d ', $posid, $eventType);
        }

        if($this->dragonInfo[TblDragonDef::ACT] <= 0)
        {
            throw new FakeException(' no act. current act is %d ', $this->dragonInfo[TblDragonDef::ACT]);
        }

        $arrHeroHp = $this->getArrHeroHpFromDragonInfo();
        if(empty($arrHeroHp))
        {
            throw new InterException(' arrHeroHp is empty ');
        }
        $totalHp = 0;
        foreach($arrHeroHp as $hid => $hp)
        {
            $totalHp += $hp;
        }
        if(empty($totalHp))
        {
            throw new FakeException('have no hp');
        }

        return true;
    }

    //加一个 是否是已经走过的点校验
    public function canAutoMove($posid)
    {
        $event = $this->getEvent($posid);
        if(!empty($event))
        {
            throw new FakeException(' this posid:%d has not triggered or bombed ', $posid);
        }
        $ret = $this->canMove($posid);
        return $ret;
    }

    //todo 1触发事件 2改变地图状态 3积分 4返回
    public function moveOneStep($posid)
    {
        $this->checkUid();
        $event = $this->getEvent($posid);
        if(empty($event))
        {
            $this->dragonInfo[TblDragonDef::POSID] = $posid;
            return;
        }

        //玩家是否移动过（用于判断 是否可以一键寻宝判断）改为已移动过
        if($this->dragonInfo[TblDragonDef::HASMOVE] == DragonDef::HASMOVENO)
        {
            $this->dragonInfo[TblDragonDef::HASMOVE] = DragonDef::HASMOVEYES;
        }

        $eventId = $event[DragonEventCsvDef::ID];
        $eventType = $event[DragonEventCsvDef::CONDITION][0];
        Logger::trace('dragon moveOneStep current Posid:%d, target posid:%d, eventId:%d, eventType:%d', $this->getCurPos(), $posid, $eventId, $eventType);
        $other = array();
        switch($eventType)
        {
            case DragonDef::EVENT_TYPE_XB:
                $dropRet = EnUser::drop(self::$uid, array($event[DragonEventCsvDef::CONDITION][1]));
                $other = array('drop' => $dropRet);
                Logger::trace('event type xb, dropid:%d, drop:%s', $event[DragonEventCsvDef::CONDITION][1], $other);
                $this->updCurEventOfVaData($eventId, array($event[DragonEventCsvDef::CONDITION][1]), $other);
                break;
            case DragonDef::EVENT_TYPE_SJ:
                $sjId = $event[DragonEventCsvDef::CONDITION][1];
                $other = array('sj' => $sjId);
                $this->updCurEventOfVaData($eventId, $sjId, $other);
                break;
            case DragonDef::EVENT_TYPE_FB:
                $fb_level = $event[DragonEventCsvDef::CONDITION][1];
                $enemyUid = EnArena::getUid(self::$uid, DragonDef::$FB_LEVELS[$fb_level -1][0], DragonDef::$FB_LEVELS[$fb_level -1][1]);
                if(empty($enemyUid))
                {
                    throw new InterException(' enemyUid is empty');
                }
                $this->updCurEventOfVaData($eventId, $enemyUid);
                $other = array('fb' => $this->getFbEnemyInfo($enemyUid));
                break;
            case DragonDef::EVENT_TYPE_HX:
                $addHp = intval($this->getMaxHpPool() * $event[DragonEventCsvDef::CONDITION][1] / 100);
                $this->dragonInfo[TblDragonDef::HP_POOL] += $addHp;
                $other = array('hp' => $addHp);
                $this->updCurEventOfVaData($eventId, array(), $other);
                //自动回血
                $this->regeneratesHp();
                break;
            case DragonDef::EVENT_TYPE_XY:
                $this->updAddtionOfVaData(PropertyKey::GENERAL_ATTACK_ADDITION, $event[DragonEventCsvDef::CONDITION][1]);
                break;
            case DragonDef::EVENT_TYPE_DJ:
                $this->dragonInfo[TblDragonDef::ACT] += $event[DragonEventCsvDef::CONDITION][1];
                Logger::trace('event_type_dj add act:%d', $event[DragonEventCsvDef::CONDITION][1]);
                $other = array('act' => $event[DragonEventCsvDef::CONDITION][1]);
                $this->updCurEventOfVaData($eventId, array(), $other);
                break;
            case DragonDef::EVENT_TYPE_DW:
                $subHp = intval($this->getMaxHpPool() * $event[DragonEventCsvDef::CONDITION][1] / 100);
                if($this->dragonInfo[TblDragonDef::HP_POOL] >= $subHp)
                {
                    $this->dragonInfo[TblDragonDef::HP_POOL] -= $subHp;
                    $other = array('hp' => - $subHp );
                    $this->updCurEventOfVaData($eventId, array(), $other);
                }
                break;
            case DragonDef::EVENT_TYPE_DT:
                $this->updCurEventOfVaData($eventId, array(), array());
                break;
            case DragonDef::EVENT_TYPE_ZL:
                $this->doEventTypeZl($event[DragonEventCsvDef::CONDITION][1]);
                break;
            case DragonDef::EVENT_TYPE_HW:
                //标记当前当前位置
               /* $this->dragonInfo[TblDragonDef::VA_DATA][DragonDef::MAP][$this->dragonInfo[TblDragonDef::FLOOR]-1][$posid]['status'][DragonDef::EVENT_STATUS_ISPASS] = 1;
                $this->updEventTriggerStatus($posid);
                $posid = $event[DragonEventCsvDef::CONDITION][1] -1; //换位到的位置*/
                break;
            case DragonDef::EVENT_TYPE_ZD:
                $this->doEventTypeZd($posid, $event[DragonEventCsvDef::CONDITION][1]);
                break;
            case DragonDef::EVENT_TYPE_HH:
                $this->dragonInfo[TblDragonDef::ACT] -= $event[DragonEventCsvDef::CONDITION][1];
                Logger::trace('event_type_hh decrease act:%d', $event[DragonEventCsvDef::CONDITION][1]);
                $other = array('act' => -$event[DragonEventCsvDef::CONDITION][1]);
                $this->updCurEventOfVaData($eventId, array(), $other);
                break;
            case DragonDef::EVENT_TYPE_JM:
                $this->dragonInfo[TblDragonDef::POINT] += $this->getPointOfVaData();
                $this->dragonInfo[TblDragonDef::TOTAL_POINT] += $this->getPointOfVaData();
                Logger::trace('event_type_jm add point:%d', $this->getPointOfVaData());
                $other = array('point' => $this->getPointOfVaData());
                $this->updCurEventOfVaData($eventId, array(), $other);
                break;
            case DragonDef::EVENT_TYPE_TL:
                $this->dragonInfo[TblDragonDef::POINT] += $event[DragonEventCsvDef::CONDITION][1];
                $this->dragonInfo[TblDragonDef::TOTAL_POINT] += $event[DragonEventCsvDef::CONDITION][1];
                $other = array('point' => $event[DragonEventCsvDef::CONDITION][1]);
                $this->updCurEventOfVaData($eventId, array(), $other);
                break;
            case DragonDef::EVENT_TYPE_TQ:
                $this->dragonInfo[TblDragonDef::POINT] -= $event[DragonEventCsvDef::CONDITION][1];
                $this->dragonInfo[TblDragonDef::TOTAL_POINT] -= $event[DragonEventCsvDef::CONDITION][1];
                $other = array('point' => -$event[DragonEventCsvDef::CONDITION][1]);
                $this->updCurEventOfVaData($eventId, array(), $other);
                break;
            case DragonDef::EVENT_TYPE_RK:

                break;
            case DragonDef::EVENT_TYPE_XC:
                $floor = $this->dragonInfo[TblDragonDef::FLOOR] + 1;
                if($floor > DragonDef::MAX_FLOOR)
                {
                    throw new FakeException('do not have the floor:%d', $floor);
                }
                $this->nextFloor($floor);
                $posid = $this->posid;
                break;
            case DragonDef::EVENT_TYPE_EXIT:
                $dropRet = EnUser::drop(self::$uid, array($event[DragonEventCsvDef::CONDITION][1]));
                $other = array('drop' => $dropRet);
                Logger::trace('event type exit, dropid:%d, drop:%s', $event[DragonEventCsvDef::CONDITION][1], $other);
                $this->updCurEventOfVaData($eventId, array($event[DragonEventCsvDef::CONDITION][1]), $other);
                break;
            case DragonDef::EVENT_TYPE_WALL:

                break;
            /**
             * 寻龙试炼新增的三个事件:商人、捐献、试炼
             */
            case DragonDef::EVENT_TYPE_SR:
                $other = array('bought' => array()); //已买
                $this->updCurEventOfVaData($eventId, array(), $other);
                break;
            case DragonDef::EVENT_TYPE_JX:
                $other = array('conNum' => 0);  //捐献次数
                $this->updCurEventOfVaData($eventId, array(), $other);
                break;
            case DragonDef::EVENT_TYPE_SL:
                if($this->dragonInfo[TblDragonDef::MODE] == DragonDef::DEFAULT_MODE)
                {
                    throw new FakeException('error config @cehua boss should not exist in default mode.');
                }
                $other = array('defeated' => -1);  //打败的bossid, -1表示没打过
                $this->updCurEventOfVaData($eventId, array(), $other);
                break;
            default:
                break;
        }

        //标记位置和地图状态
        $this->dragonInfo[TblDragonDef::POSID] = $posid;
        $this->dragonInfo[TblDragonDef::VA_DATA][DragonDef::MAP][$this->dragonInfo[TblDragonDef::FLOOR]-1][$posid]['status'][DragonDef::EVENT_STATUS_ISPASS] = 1;

        //直接给积分的事件
        if(!in_array($eventType, DragonDef::$EVENT_TYPE_NO_IMMEDIATE_POINT))
        {
            $this->updPoint($event[DragonEventCsvDef::POINT][0][1]);
            $this->updTotalPoint($event[DragonEventCsvDef::POINT][0][1]);
            $this->updPointOfVaData($event[DragonEventCsvDef::POINT][0][1]);
            Logger::trace('updatePoint point%d', $event[DragonEventCsvDef::POINT][0][1]);
            if($eventType != DragonDef::EVENT_TYPE_HW
                && $eventType != DragonDef::EVENT_TYPE_SR
                && $eventType != DragonDef::EVENT_TYPE_JX
                && $eventType != DragonDef::EVENT_TYPE_SL
            )
            {
                $this->updEventTriggerStatus($this->getCurPos());
            }
        }

        //行动力
        $costAct = $event[DragonEventCsvDef::COSTACT];
        $curAct = $this->dragonInfo[TblDragonDef::ACT];
        $realCostAct = $curAct - $costAct >= 0 ? $costAct : $curAct;
        $this->dragonInfo[TblDragonDef::ACT] -= $realCostAct;
        Logger::trace('decrease point:%d', $realCostAct);

        //刷新单次寻龙最高积分
        $this->rfrOnceMaxPoint();

        $ret = array();
        $ret['eid'] = $event[DragonEventCsvDef::ID];
        $ret['hppool'] = $this->dragonInfo[TblDragonDef::HP_POOL];
        $ret['act'] = $this->dragonInfo[TblDragonDef::ACT];
        $ret['point'] = $this->dragonInfo[TblDragonDef::POINT];
        $ret['total_point'] = $this->dragonInfo[TblDragonDef::TOTAL_POINT];
        $ret['once_max_point'] = $this->dragonInfo[TblDragonDef::ONCE_MAX_POINT];
        $ret['other'] = $other;

        return $ret;
    }

    public function move($posid)
    {
        $ret = array();
        $i = 1;
        $height = btstore_get()->DRAGON[DragonDef::INIT_FLOOR][DragonCsvDef::HEIGHT];
        $width = btstore_get()->DRAGON[DragonDef::INIT_FLOOR][DragonCsvDef::WIDTH];

        while(true)
        {
            if($i >= $height * $width)
            {
                throw new InterException('dragon event peizhi error @cehua ');
            }
            $this->canMove($posid, true);
            $ret = $this->moveOneStep($posid);
            $event = $this->getEvent($this->getCurPos());
            if(empty($event))
            {
                break;
            }
            $eventType = $event[DragonEventCsvDef::CONDITION][0];
            if($eventType != DragonDef::EVENT_TYPE_HW)
            {
                break;
            }
            $this->updEventTriggerStatus($this->getCurPos());
            if($posid == $event[DragonEventCsvDef::CONDITION][1] -1) //跳转后还是当前
            {
                break;
            }
            $posid = $event[DragonEventCsvDef::CONDITION][1] -1; //策划配的位置从1开始
            Logger::trace(' move EventTypeHW target posid:%d ', $posid);
            $i++;
        }

        return $ret;
    }

    /**
     * 处理指路
     * @param $type
     * @param $posid
     */
    public function doEventTypeZl($posid)
    {
        if($posid == 0)
        {
            $height = btstore_get()->DRAGON[DragonDef::INIT_FLOOR][DragonCsvDef::HEIGHT];
            $width = btstore_get()->DRAGON[DragonDef::INIT_FLOOR][DragonCsvDef::WIDTH];
            for($i = 0; $i < $height * $width; $i++)
            {
                $this->dragonInfo[TblDragonDef::VA_DATA][DragonDef::MAP][$this->dragonInfo[TblDragonDef::FLOOR]-1][$i]['status'][DragonDef::EVENT_STATUS_ISFOG] = 1;
            }
            Logger::trace(' doEventTypeZl all posid');
        }
        else
        {
            $this->dragonInfo[TblDragonDef::VA_DATA][DragonDef::MAP][$this->dragonInfo[TblDragonDef::FLOOR]-1][$posid-1]['status'][DragonDef::EVENT_STATUS_ISFOG] = 1;
            Logger::trace(' doEventTypeZl posid:%d', $posid);
        }
    }

    public function doEventTypeZd($posid, $bombNum)
    {
        $width = btstore_get()->DRAGON[DragonDef::INIT_FLOOR][DragonCsvDef::WIDTH];
        $height = btstore_get()->DRAGON[DragonDef::INIT_FLOOR][DragonCsvDef::HEIGHT];
        switch($bombNum)
        {
            case DragonDef::BOMB_NUM_4:
                $bombPos = $posid - $height;
                if($posid >= $height)
                {
                    $this->dragonInfo[TblDragonDef::VA_DATA][DragonDef::MAP][$this->dragonInfo[TblDragonDef::FLOOR]-1][$bombPos]['status'][DragonDef::EVENT_STATUS_ISBOMB] = 1;
                }
            case DragonDef::BOMB_NUM_3:
                $bombPos = $posid + 1;
                if(($posid + 1)%$height != 0) //不是该列的最后一个点
                {
                    $this->dragonInfo[TblDragonDef::VA_DATA][DragonDef::MAP][$this->dragonInfo[TblDragonDef::FLOOR]-1][$bombPos]['status'][DragonDef::EVENT_STATUS_ISBOMB] = 1;
                }
            case DragonDef::BOMB_NUM_2:
                $bombPos = $posid + $height;
                if($posid <= ($width-1)*$height)
                {
                    $this->dragonInfo[TblDragonDef::VA_DATA][DragonDef::MAP][$this->dragonInfo[TblDragonDef::FLOOR]-1][$bombPos]['status'][DragonDef::EVENT_STATUS_ISBOMB] = 1;
                }
            case DragonDef::BOMB_NUM_1:
                $bombPos = $posid - 1;
                if($posid%$height != 0) //不是该列的第一个点
                {
                    $this->dragonInfo[TblDragonDef::VA_DATA][DragonDef::MAP][$this->dragonInfo[TblDragonDef::FLOOR]-1][$bombPos]['status'][DragonDef::EVENT_STATUS_ISBOMB] = 1;
                }
                break;
        }
    }

    /**
     * @param $curEventId
     * @param $curEventData array 存当前事件的一些状态信息
     * @param $other array 用来存放险遇 等其他数据
     */
    private function updCurEventOfVaData($curEventId, $curEventData, $other = array())
    {
        $this->dragonInfo[TblDragonDef::VA_DATA][DragonDef::CUREVENT]['id'] = $curEventId;
        $this->dragonInfo[TblDragonDef::VA_DATA][DragonDef::CUREVENT]['data'] = $curEventData;
        $this->dragonInfo[TblDragonDef::VA_DATA][DragonDef::CUREVENT]['other'] = $other;
    }

    /**
     * curEvent array[id, data, other, point]
     */

    /**
     * 本次事件的得分
     * @param $point
     */
    private function updPointOfVaData($point)
    {
        $this->dragonInfo[TblDragonDef::VA_DATA][DragonDef::CUREVENT]['point'] = $point;
    }

    private function getPointOfVaData()
    {
        return $this->dragonInfo[TblDragonDef::VA_DATA][DragonDef::CUREVENT]['point'];
    }

    private function getCurEventOfVaData()
    {
        return $this->dragonInfo[TblDragonDef::VA_DATA][DragonDef::CUREVENT];
    }

    private function updAddtionOfVaData($key, $addtion)
    {
        $this->dragonInfo[TblDragonDef::VA_DATA][DragonDef::ARRADDTION][$key] = $addtion;
    }

    private function getAddtionOfVaData()
    {
        return $this->dragonInfo[TblDragonDef::VA_DATA][DragonDef::ARRADDTION];
    }

    public function getEvent($posid)
    {
        $curEventId = $this->dragonInfo[TblDragonDef::VA_DATA][DragonDef::MAP][$this->dragonInfo[TblDragonDef::FLOOR]-1]
                        [$posid]['eid'];
        $event = btstore_get()->DRAGONEVENT[$curEventId];
        $eventStatus = $this->dragonInfo[TblDragonDef::VA_DATA][DragonDef::MAP][$this->dragonInfo[TblDragonDef::FLOOR]-1][$posid]['status'];
        if( $eventStatus[DragonDef::EVENT_STATUS_ISBOMB] == 1 || $eventStatus[DragonDef::EVENT_STATUS_ISTRIGGER] == 1)
        {
            return array();
        }
        return $event;
    }

    public function getOriginalEvent($posid)
    {
        $curEventId = $this->dragonInfo[TblDragonDef::VA_DATA][DragonDef::MAP][$this->dragonInfo[TblDragonDef::FLOOR]-1]
                        [$posid]['eid'];
        return btstore_get()->DRAGONEVENT[$curEventId];
    }


    /**
     * 内部接口
     * @param $eventId
     * @param $arrEventType
     * @return mixed
     * @throws InterException
     * @throws FakeException
     */
    public function checkEvent($eventId, $arrEventType)
    {
        $curEvent = $this->getCurEventOfVaData();
        if(!isset($curEvent['id']))
        {
            throw new InterException('curEvent in va_data is empty!');
        }

        if($curEvent['id'] != $eventId)
        {
            throw new FakeException('invalid eventId:%d, curEventId in va_data is %d', $eventId, $curEvent['id']);
        }

        if(!in_array(btstore_get()->DRAGONEVENT[$eventId][DragonEventCsvDef::CONDITION][0], $arrEventType) )
        {
            throw new FakeException('');
        }

        return $curEvent;
    }

    public function doublePrize($eventId)
    {
        $this->checkUid();
        $curEvent = $this->checkEvent($eventId, array(DragonDef::EVENT_TYPE_XB));

        EnUser::drop(self::$uid, array($curEvent['data']));
        $this->updCurEventOfVaData(NULL, NULL);
        $this->updEventTriggerStatus($this->getCurPos());
        return true;
    }

    public function fight($eventId)
    {
        $this->checkUid();
        $user = EnUser::getUserObj(self::$uid);
        $curEvent = $this->checkEvent($eventId, array(DragonDef::EVENT_TYPE_SJ, DragonDef::EVENT_TYPE_FB));

        $btFmt = $this->getVaBf(true);
        if(empty($btFmt['arrHero']))
        {
            throw new FakeException(' btFmt is empty, all the heros is dead. ');
        }

        //获取战斗类型
        $btType		= 0;
        $callback	= array();
        $winCon		= array();
        $extraInfo	= array('type' => BattleType::DRAGON);
        $enemyBtFmt = array();
        if(btstore_get()->DRAGONEVENT[$eventId][DragonEventCsvDef::CONDITION][0] == DragonDef::EVENT_TYPE_SJ)
        {
            $enemyBtFmt	=	EnFormation::getMonsterBattleFormation($curEvent['data']);
            $btType		= btstore_get()->ARMY[$curEvent['data']]['fight_type'];
            $winCon    =    CopyUtil::getVictoryConditions($curEvent['data']);
        }
        else if(btstore_get()->DRAGONEVENT[$eventId][DragonEventCsvDef::CONDITION][0] == DragonDef::EVENT_TYPE_FB)
        {
            $enemyUid = $curEvent['data'];
            if(ArenaLogic::isNpc($enemyUid))
            {
                $enemy = new NpcUser($enemyUid);
            }
            else
            {
                $enemy = EnUser::getUserObj($enemyUid);
            }
            $enemyBtFmt = $enemy->getBattleFormation();
            $btType = EnBattle::setFirstAtk(0, $user->getFightForce() >= $enemy->getFightForce());
        }
        $atkRet	= EnBattle::doHero($btFmt,$enemyBtFmt,$btType,$callback,$winCon,$extraInfo);
        //$isSuc = BattleDef::$APPRAISAL[$atkRet['server']['appraisal']] <= BattleDef::$APPRAISAL['D'];
        switch($atkRet['server']['appraisal'])
        {
            case 'SSS':
                $point = btstore_get()->DRAGONEVENT[$eventId][DragonEventCsvDef::POINT][0][1];
                break;
            case 'SS':
                $point = btstore_get()->DRAGONEVENT[$eventId][DragonEventCsvDef::POINT][1][1];
                break;
            case 'S':
                $point = btstore_get()->DRAGONEVENT[$eventId][DragonEventCsvDef::POINT][2][1];
                break;
            case 'A':
                $point = btstore_get()->DRAGONEVENT[$eventId][DragonEventCsvDef::POINT][3][1];
                break;
            case 'B':
                $point = btstore_get()->DRAGONEVENT[$eventId][DragonEventCsvDef::POINT][4][1];
                break;
            default:
                $point = btstore_get()->DRAGONEVENT[$eventId][DragonEventCsvDef::POINT][5][1];
                break;
        }
        $this->updPoint($point);
        $this->updTotalPoint($point);
        $this->updPointOfVaData($point);

        $this->updArrHpAfterFight($atkRet);
        //自动回血
        $this->regeneratesHp();

        //去除加成
        $this->clrAddtion();
        $this->updEventTriggerStatus($this->getCurPos());
        return array('atkRet' => $atkRet, 'hppool' => $this->dragonInfo[TblDragonDef::HP_POOL], 'arrhp' => $this->dragonInfo[TblDragonDef::VA_DATA]['arrhp']);
    }

    public function updArrHpAfterFight($atkRet)
    {
        $arrHeroHp = $this->getArrHeroHpFromDragonInfo();
        if(empty($arrHeroHp))
        {
            throw new InterException('arrHeroHp is empty!');
        }
        foreach($atkRet['server']['team1'] as $pos => $heroInfo)
        {
            $hid = $heroInfo[PropertyKey::HID];
            if(isset($arrHeroHp[$hid]))
            {
                $arrHeroHp[$hid] = $heroInfo['hp'];
            }
        }
        $this->dragonInfo[TblDragonDef::VA_DATA]['arrhp'] = $arrHeroHp;
    }

    /**
     * 自动回血
     */
    public function regeneratesHp()
    {
        $userBf = $this->dragonInfo[TblDragonDef::VA_BF];   //userBf 用来记录
        $arrHeroMaxHp = array();
        foreach($userBf['arrHero'] as $pos => $heroInfo)
        {
            $hid = $heroInfo[PropertyKey::HID];
            $hp = $heroInfo[PropertyKey::MAX_HP];
            $arrHeroMaxHp[$hid] = $hp;
        }

        $arrHeroHp = $this->getArrHeroHpFromDragonInfo();
        if(empty($arrHeroHp))
        {
            throw new InterException('arrHeroHp is empty!');
        }
        $hpPool = $this->dragonInfo[TblDragonDef::HP_POOL];

        foreach($arrHeroHp as $hid => $hp)
        {
            if($hpPool <= 0)
            {
                break;
            }
            if(!isset($arrHeroMaxHp[$hid]))
            {
                throw new FakeException(' hid:%d not equal, maybe change zhenxing ', $hid);
            }
            $needHp = min(array($hpPool, $arrHeroMaxHp[$hid] - $arrHeroHp[$hid]));
            $arrHeroHp[$hid] += $needHp;
            $hpPool -= $needHp;
        }

        $this->dragonInfo[TblDragonDef::HP_POOL] = $hpPool;
        $this->dragonInfo[TblDragonDef::VA_DATA]['arrhp'] = $arrHeroHp;
    }

    private function clrAddtion()
    {
        $this->dragonInfo[TblDragonDef::VA_DATA][DragonDef::ARRADDTION] = array();
    }

    public function reset($uid)
    {
        $this->checkUid();
        $this->dealResetNum($uid);

        $this->resetData();
        return $this->getMap();
    }

    private function nextFloor($floor)
    {
        $this->checkUid();
        $this->initActorPos($floor);
        $this->dragonInfo[TblDragonDef::POSID] = $this->posid;
        $this->dragonInfo[TblDragonDef::FLOOR] = $floor;
        $map = $this->initMapOfTheFloor($floor);
        $this->dragonInfo[TblDragonDef::VA_DATA][DragonDef::CUREVENT] = $map[1];
        $this->dragonInfo[TblDragonDef::VA_DATA][DragonDef::MAP][$floor-1] = $map[0];
        //新加的
        $this->dragonInfo[TblDragonDef::HASMOVE] = DragonDef::HASMOVENO;
    }

    public function subTotalPoint($subPoint)
    {
        if($this->dragonInfo[TblDragonDef::TOTAL_POINT] < $subPoint)
        {
            return false;
        }
        $this->dragonInfo[TblDragonDef::TOTAL_POINT] -= $subPoint;
        return true;
    }

    public function updEventTriggerStatus($posid)
    {
        $this->dragonInfo[TblDragonDef::VA_DATA][DragonDef::MAP][$this->dragonInfo[TblDragonDef::FLOOR]-1]
                [$posid]['status'][DragonDef::EVENT_STATUS_ISTRIGGER] = 1;
        Logger::trace('updEventTriggerStatus to status 1 posid:%d', $posid);
        return true;
    }

    public function skip($posid)
    {
        $this->checkPos($posid);
        $event = $this->getEvent($posid);
        if(empty($event))
        {
            return;
        }
        $eventId = $event[DragonEventCsvDef::ID];
        if(in_array($event[DragonEventCsvDef::CONDITION][0], array(DragonDef::EVENT_TYPE_SJ, DragonDef::EVENT_TYPE_FB)))
        {
            $this->updPoint(btstore_get()->DRAGONEVENT[$eventId][DragonEventCsvDef::POINT][6][1]);
            $this->updTotalPoint(btstore_get()->DRAGONEVENT[$eventId][DragonEventCsvDef::POINT][6][1]);
            $this->updPointOfVaData(btstore_get()->DRAGONEVENT[$eventId][DragonEventCsvDef::POINT][6][1]);
        }
        $this->updEventTriggerStatus($posid);
    }

    public function updPoint($addPoint)
    {
        $this->dragonInfo[TblDragonDef::POINT] += $addPoint;
    }

    public function updTotalPoint($addPoint)
    {
        $this->dragonInfo[TblDragonDef::TOTAL_POINT] += $addPoint;
        EnAchieve::updateDragonPoint(self::$uid, $this->dragonInfo[TblDragonDef::TOTAL_POINT]);
    }

    public function answer($eventId, $answer)
    {
        $curEvent = $this->checkEvent($eventId, array(DragonDef::EVENT_TYPE_DT));
        $questionId = btstore_get()->DRAGONEVENT[$eventId][DragonEventCsvDef::CONDITION][1];
        $ret = false;
        if(btstore_get()->DRAGONANSWER[$questionId]['answer'] == $answer)
        {
            $this->updPoint(btstore_get()->DRAGONEVENT[$eventId][DragonEventCsvDef::POINT][0][1]);
            $this->updTotalPoint(btstore_get()->DRAGONEVENT[$eventId][DragonEventCsvDef::POINT][0][1]);
            $this->updPointOfVaData(btstore_get()->DRAGONEVENT[$eventId][DragonEventCsvDef::POINT][0][1]);
            $ret = true;
        }
        else
        {
            $this->updPoint(btstore_get()->DRAGONEVENT[$eventId][DragonEventCsvDef::POINT][1][1]);
            $this->updTotalPoint(btstore_get()->DRAGONEVENT[$eventId][DragonEventCsvDef::POINT][1][1]);
            $this->updPointOfVaData(btstore_get()->DRAGONEVENT[$eventId][DragonEventCsvDef::POINT][1][1]);
        }
        $this->updEventTriggerStatus($this->getCurPos());
        return $ret;
    }

    public function bribe($eventId)
    {
        $curEvent = $this->checkEvent($eventId, array(DragonDef::EVENT_TYPE_SJ, DragonDef::EVENT_TYPE_FB));
        $userObj = EnUser::getUserObj(self::$uid);
        $needGold = btstore_get()->DRAGONEVENT[$eventId][DragonEventCsvDef::ONKEYPAY];
        if($userObj->subGold($needGold, StatisticsDef::ST_FUNCKEY_DRAGON_BRIBE) == false)
        {
            throw new FakeException('Dragon bribe subGold failed');
        }
        $this->updPoint(btstore_get()->DRAGONEVENT[$eventId][DragonEventCsvDef::POINT][7][1]);
        $this->updTotalPoint(btstore_get()->DRAGONEVENT[$eventId][DragonEventCsvDef::POINT][7][1]);
        $this->updPointOfVaData(btstore_get()->DRAGONEVENT[$eventId][DragonEventCsvDef::POINT][7][1]);
        $this->updEventTriggerStatus($this->getCurPos());
        return true;
    }

    public function oneKey($eventId)
    {
        $curEvent = $this->checkEvent($eventId, array(DragonDef::EVENT_TYPE_DT));
        $userObj = EnUser::getUserObj(self::$uid);
        $needGold = btstore_get()->DRAGONEVENT[$eventId][DragonEventCsvDef::ONKEYPAY];
        if($userObj->subGold($needGold, StatisticsDef::ST_FUNCKEY_DRAGON_ONEKEY) == false)
        {
            throw new FakeException('Dragon onekey subGold failed');
        }
        $this->updPoint(btstore_get()->DRAGONEVENT[$eventId][DragonEventCsvDef::POINT][0][1]);
        $this->updTotalPoint(btstore_get()->DRAGONEVENT[$eventId][DragonEventCsvDef::POINT][0][1]);
        $this->updPointOfVaData(btstore_get()->DRAGONEVENT[$eventId][DragonEventCsvDef::POINT][0][1]);
        $this->updEventTriggerStatus($this->getCurPos());
        return true;
    }

    /**
     * @param $index
     * @param $num 连续购买次数
     * @return mixed
     * @throws FakeException
     */
    public function buyAct($index, $num)
    {
        $userObj = EnUser::getUserObj(self::$uid);
        $actPay = btstore_get()->DRAGON[$this->dragonInfo[TblDragonDef::FLOOR]][DragonCsvDef::ACTPAY][$index];
        $addActPay = btstore_get()->DRAGON[$this->dragonInfo[TblDragonDef::FLOOR]][DragonCsvDef::ADDACT];//行动力购买上线组
        $buyActNum = $this->dragonInfo[TblDragonDef::BUY_ACT_NUM];
        $buyActNumLimit = btstore_get()->VIP[$userObj->getVip()]['exploreLongActNum'];
        if($buyActNum + $num > $buyActNumLimit)
        {
            throw new FakeException('buy act num:%d, limit num:%d, num:%d', $buyActNum, $buyActNumLimit, $num);
        }
        $needGold = min(($actPay[0] + $addActPay[0] * $buyActNum), $addActPay[1]);
        Logger::trace('actPay:%s, addActPay:%s', $actPay, $addActPay);
        for($i = 1; $i < $num; $i++)
        {
            $needGold += min(($actPay[0] + $addActPay[0] * ($buyActNum + $i)), $addActPay[1]);
        }
        Logger::trace('buyAct num:%d, needGold:%d', $num, $needGold);
        if($userObj->subGold($needGold, StatisticsDef::ST_FUNCKEY_DRAGON_BUY_ACT) == false)
        {
            throw new FakeException('Dragon buyAct subGold failed');
        }
        $this->dragonInfo[TblDragonDef::ACT] += $actPay[1] * $num;
        $this->dragonInfo[TblDragonDef::BUY_ACT_NUM] += $num;
        return $this->dragonInfo[TblDragonDef::ACT];
    }

    public function buyHp($index)
    {
        $userObj = EnUser::getUserObj(self::$uid);
        $hpPay = btstore_get()->DRAGON[$this->dragonInfo[TblDragonDef::FLOOR]][DragonCsvDef::HPPAY][$index];
        $addHpPay = btstore_get()->DRAGON[$this->dragonInfo[TblDragonDef::FLOOR]][DragonCsvDef::ADDHP];
        $buyHpNum = $this->dragonInfo[TblDragonDef::BUY_HP_NUM];
        $needGold = min(($hpPay[0] + $addHpPay[0] * $buyHpNum), $addHpPay[1]);
        Logger::trace('buyHp needGold:%d', $needGold);
        if($userObj->subGold($needGold, StatisticsDef::ST_FUNCKEY_DRAGON_BUY_HP) == false)
        {
            throw new FakeException('Dragon buyHp subGold failed');
        }
        $this->dragonInfo[TblDragonDef::HP_POOL] += intval($this->getMaxHpPool() * $hpPay[1] / 100);
        $this->dragonInfo[TblDragonDef::BUY_HP_NUM] += 1;

        //卖完血 自动回血
        $this->regeneratesHp();
        return $this->dragonInfo[TblDragonDef::HP_POOL];
    }

    public function getInitMaxHpPool()
    {
        $userBf = $this->getUserBf();
        $hpPool = 0;
        foreach($userBf['arrHero'] as $pos => $heroInfo)
        {
            $hpPool += $heroInfo[PropertyKey::MAX_HP];
            Logger::trace('hpPool + hp:%d', $heroInfo[PropertyKey::MAX_HP]);
        }
        return $hpPool;
    }

    public function getMaxHpPool()
    {
        $userBf = $this->dragonInfo[TblDragonDef::VA_BF];
        $hpPool = 0;
        foreach($userBf['arrHero'] as $pos => $heroInfo)
        {
            $hpPool += $heroInfo[PropertyKey::MAX_HP];
            Logger::trace('hpPool + hp:%d', $heroInfo[PropertyKey::MAX_HP]);
        }
        return $hpPool;
    }
    
    
    public function getFbEnemyInfo($enemyUid)
    {
    	if( empty($enemyUid) )
    	{
    		throw new InterException('invalid enemyUid:%s', $enemyUid);
    	} 
    	$enemyInfo = array();
        $isNpc = ArenaLogic::isNpc($enemyUid);
    	if(ArenaLogic::isNpc($enemyUid))
    	{
    		$armyId = ArenaLogic::getNpcArmyId($enemyUid);
    		$squad = EnFormation::getMonsterSquad( $armyId );
    		$enemyInfo = array(
                    'isNpc' => $isNpc,
    				'uid' => $enemyUid,
    				'utid' => ArenaLogic::getNpcUtid($enemyUid),
    				'level'=> ArenaLogic::getNpcLevel(),
    				'vip' => ArenaLogic::getNpcVip(),
    				'uname' => ArenaLogic::getNpcName($enemyUid),
    				'htid' => array_slice($squad, 0, 4),
    				'armyId' => $armyId,
    		);
    	}
    	else
    	{
    		$arrEnemy = Enuser::getArrUserBasicInfo(array($enemyUid), array('uid','utid','level','vip','uname','dress','htid'));
    		if(empty($arrEnemy[$enemyUid]))
    		{
    			throw new InterException('arrEnemy is empty. enemyUid:%d', $enemyUid);
    		}
    		$enemyInfo = $arrEnemy[$enemyUid];
    		$enemyInfo['armyId'] = 0;
            $enemyInfo['isNpc'] = $isNpc;
    	}
    	
    	return $enemyInfo;
    }

    public function autoMove($arrPosid)
    {
        if(empty($arrPosid))
        {
            throw new FakeException('arrPosid is empty, can not move');
        }
        foreach($arrPosid as $posid)
        {
            $this->canAutoMove($posid);
            $this->moveOneStep($posid);
        }
        return true;
    }

    public function canAiDo($floor, $actIndex)
    {
        //上线前改需求、只是增加了拦截条件（方便以后再改需求去掉）
        if($floor != $this->dragonInfo[TblDragonDef::FLOOR])
        {
            throw new FakeException('error floor');
        }
        if($actIndex != 3)
        {
            throw new FakeException('error actIndex');
        }

        if($this->dragonInfo[TblDragonDef::HASMOVE] == DragonDef::HASMOVEYES)
        {
            throw new FakeException(' you have moved, can not aido ');
        }
        if($floor < $this->dragonInfo[TblDragonDef::FLOOR])
        {
            throw new FakeException(' wrong param floor:%d ', $floor);
        }
        if($this->dragonInfo[TblDragonDef::MODE] == DragonDef::DEFAULT_MODE && $floor > 4)
        {
            throw new FakeException("wrong param floor:%d when mode is default_mode:%d", $floor, DragonDef::DEFAULT_MODE);
        }
        if($this->dragonInfo[TblDragonDef::MODE] == DragonDef::TRIAL_MODE && $floor != 5)
        {
            throw new FakeException("wrong param floor:%d when mode is default_mode:%d", $floor, DragonDef::DEFAULT_MODE);
        }
        $floorNum = $floor - $this->dragonInfo[TblDragonDef::FLOOR] + 1;    //一键寻龙层数,用于计算总消耗行动力
        $needAct = btstore_get()->DRAGON[$this->dragonInfo[TblDragonDef::FLOOR]][DragonCsvDef::AIEXPLORECOSTACT][$actIndex]; //每层消耗行动力
        if($this->dragonInfo[TblDragonDef::ACT] + $this->dragonInfo[TblDragonDef::FREE_AI_NUM] < $floorNum * $needAct)
        {
            throw new FakeException(' no enough act:%d + freeact:%d, needact:%d ', $this->dragonInfo[TblDragonDef::ACT],
                $this->dragonInfo[TblDragonDef::FREE_AI_NUM], $floorNum * $needAct);
        }
        return true;
    }

    /**
     * @param $actIndex int 现在策划规定actIndex必须是3
     * @return array
     * @throws InterException
     */
    public function aiDoOneFloor($actIndex)
    {
        $aiEvents = btstore_get()->DRAGON[$this->dragonInfo[TblDragonDef::FLOOR]][DragonCsvDef::AIEXPLOREEVENT]->toArray();
        $needAct = btstore_get()->DRAGON[$this->dragonInfo[TblDragonDef::FLOOR]][DragonCsvDef::AIEXPLORECOSTACT][$actIndex]; //每层消耗行动力--决定随机事件数
        $eventIds = array();
        //先取额外的宝物事件
        $bwIds = array();
        switch($actIndex)
        {
            case 3:
                $bwIds = array_merge($bwIds, btstore_get()->DRAGON[$this->dragonInfo[TblDragonDef::FLOOR]][DragonCsvDef::AIEXTRAEVENT][3]->toArray());
            case 2:
                $bwIds = array_merge($bwIds, btstore_get()->DRAGON[$this->dragonInfo[TblDragonDef::FLOOR]][DragonCsvDef::AIEXTRAEVENT][2]->toArray());
            case 1:
                $bwIds = array_merge($bwIds, btstore_get()->DRAGON[$this->dragonInfo[TblDragonDef::FLOOR]][DragonCsvDef::AIEXTRAEVENT][1]->toArray());
            case 0:
                $bwIds = array_merge($bwIds, btstore_get()->DRAGON[$this->dragonInfo[TblDragonDef::FLOOR]][DragonCsvDef::AIEXTRAEVENT][0]->toArray());
                break;
        }

        if($needAct < count($bwIds))
        {
            throw new InterException('configuration err @cehua');
        }
        $eventIds += $bwIds;
        $eventPosIds = Util::noBackSample($aiEvents, $needAct - count($bwIds), 'w');
        foreach($eventPosIds as $eventPosId)
        {
            $eventIds[] = $aiEvents[$eventPosId]['id'];
        }
        foreach($eventIds as $eventId)
        {
            $event = btstore_get()->DRAGONEVENT[$eventId];
            $aiExplorePoint = $event[DragonEventCsvDef::AIEXPLOREPOINT]; //自动寻龙积分
            $this->updPoint($aiExplorePoint);
            $this->updTotalPoint($aiExplorePoint);
        }

        //每层加一次积分
        $aiExploreRewardPoint = btstore_get()->DRAGON[$this->dragonInfo[TblDragonDef::FLOOR]][DragonCsvDef::AIEXPLOREREWARDPOINT][$actIndex];
        $this->updPoint($aiExploreRewardPoint);
        $this->updTotalPoint($aiExploreRewardPoint);
        //shuffle($eventIds);
        return $eventIds;
    }

    public function aiDo($floor, $actIndex)
    {
        if($floor - $this->dragonInfo[TblDragonDef::FLOOR] < 0)
        {
            throw new FakeException('  wrong param floor:%d ', $floor );
        }
        $floorNum = $floor - $this->dragonInfo[TblDragonDef::FLOOR] + 1;    //一键寻龙层数,用于计算总消耗行动力
        $needAct = btstore_get()->DRAGON[$this->dragonInfo[TblDragonDef::FLOOR]][DragonCsvDef::AIEXPLORECOSTACT][$actIndex]; //每层消耗行动力
        $this->dragonInfo[TblDragonDef::ACT] -=  $floorNum * $needAct;

        //忠义堂判断是否免费 免费返回1, 不免费返回0
        $isFree = EnUnion::getAddFuncByUnion(self::$uid, UnionDef::TYPE_DRAGON_AIDO_ISFREE);
        if($isFree == 0)
        {
            //扣金币
            $userObj = EnUser::getUserObj(self::$uid);
            $aiExplorePay = btstore_get()->DRAGON[$this->dragonInfo[TblDragonDef::FLOOR]][DragonCsvDef::AIEXPLOREPAY];
            if($floorNum * $needAct - $this->dragonInfo[TblDragonDef::FREE_AI_NUM] >= 0)
            {
                $needGold = ($floorNum * $needAct - $this->dragonInfo[TblDragonDef::FREE_AI_NUM]) * $aiExplorePay;
                $this->dragonInfo[TblDragonDef::FREE_AI_NUM] = 0;
            }
            else
            {
                $needGold = 0;
                $this->dragonInfo[TblDragonDef::FREE_AI_NUM] -= $floorNum * $needAct;
            }

            if($userObj->subGold($needGold, StatisticsDef::ST_FUNCKEY_DRAGON_AIDO) == false)
            {
                throw new FakeException('Dragon aiDo subGold failed');
            }
        }
        else if($isFree == 1)
        {
            Logger::info("EnUnion::getAddFuncByUnion type:UnionDef::TYPE_DRAGON_AIDO_ISFREE isFree:[%d] aiDo uid:[%d]", $isFree, self::$uid);
        }
        else
        {
            throw new InterException("EnUnion::getAddFuncByUnion type:UnionDef::TYPE_DRAGON_AIDO_ISFREE isFree:[%d] invalid value", $isFree);
        }

        $ret = array();
        $arrEventId = array();
        $arrDorpId = array();
        for($i = $this->dragonInfo[TblDragonDef::FLOOR]; $i <= $floor; $i++)
        {
            switch($actIndex)
            {
                case 3:
                    if(!empty(btstore_get()->DRAGON[$this->dragonInfo[TblDragonDef::FLOOR]][DragonCsvDef::AIEXPLOREREWARD][3]))
                    {
                        $arrDorpId[] = btstore_get()->DRAGON[$this->dragonInfo[TblDragonDef::FLOOR]][DragonCsvDef::AIEXPLOREREWARD][3];
                    }
                case 2:
                    if(!empty(btstore_get()->DRAGON[$this->dragonInfo[TblDragonDef::FLOOR]][DragonCsvDef::AIEXPLOREREWARD][2]))
                    {
                        $arrDorpId[] = btstore_get()->DRAGON[$this->dragonInfo[TblDragonDef::FLOOR]][DragonCsvDef::AIEXPLOREREWARD][2];
                    }
                case 1:
                    if(!empty(btstore_get()->DRAGON[$this->dragonInfo[TblDragonDef::FLOOR]][DragonCsvDef::AIEXPLOREREWARD][1]))
                    {
                        $arrDorpId[] = btstore_get()->DRAGON[$this->dragonInfo[TblDragonDef::FLOOR]][DragonCsvDef::AIEXPLOREREWARD][1];
                    }
                case 0:
                    if(!empty(btstore_get()->DRAGON[$this->dragonInfo[TblDragonDef::FLOOR]][DragonCsvDef::AIEXPLOREREWARD][0]))
                    {
                        $arrDorpId[] = btstore_get()->DRAGON[$this->dragonInfo[TblDragonDef::FLOOR]][DragonCsvDef::AIEXPLOREREWARD][0];
                    }
                    break;
            }
            $eventIds = $this->aiDoOneFloor($actIndex);
            $arrEventId[$i] = $eventIds;
            //一键寻龙后 自动跳到下层起点 如果是第四层 跳到终点
            if($i < DragonDef::MAX_FLOOR)
            {
                $this->nextFloor($i+1);
                $posid = $this->posid;
                $this->dragonInfo[TblDragonDef::POSID] = $posid;
            }
            else if($this->dragonInfo[TblDragonDef::MODE] == DragonDef::DEFAULT_MODE)
            {
                $posid = DragonDef::EVENT_EXIT_POSID;
                $this->dragonInfo[TblDragonDef::POSID] = $posid;
                if(!empty(btstore_get()->DRAGON[DragonDef::MAX_FLOOR][DragonCsvDef::AIEXPLOREREWARD][4]))
                {
                    $arrDorpId[] = btstore_get()->DRAGON[DragonDef::MAX_FLOOR][DragonCsvDef::AIEXPLOREREWARD][4];
                }
            }
            else if($this->dragonInfo[TblDragonDef::MODE] == DragonDef::TRIAL_MODE)
            {
                $posid = DragonDef::EVENT_EXIT_POSID;
                $this->dragonInfo[TblDragonDef::POSID] = $posid;
                $other = array('defeated' => -1);  //打败的bossid, -1表示没打过
                //寻龙试炼模式 boss的id策划不能随便改变
                $this->updCurEventOfVaData(DragonDef::TRIAL_EVENT_ID_SL, array(), $other);
            }
        }
        //奖励
        $dropRet = EnUser::drop(self::$uid, $arrDorpId);
        $other = array('drop' => $dropRet);
        if($this->dragonInfo[TblDragonDef::MODE] != DragonDef::TRIAL_MODE)
        {
            $this->updCurEventOfVaData(NULL, NULL, $other);
        }

        //忠义堂判断 是否可以获得免费积分
        $addPoint = EnUnion::getAddFuncByUnion(self::$uid, UnionDef::TYPE_DRAGON_AIDO_ADDPOINT);
        if($addPoint > 0 && $this->dragonInfo[TblDragonDef::MODE] == DragonDef::TRIAL_MODE)
        {
            Logger::info("EnUnion::getAddFuncByUnion addPoint:[%d] uid:[%d]", $addPoint, self::$uid);
            $this->updPoint($addPoint);
            $this->updTotalPoint($addPoint);
        }

        //刷新单次寻龙最高积分
        $this->rfrOnceMaxPoint();

        $ret['events'] = $arrEventId;
        $ret['drop'] = $dropRet;
        return $ret;
    }

    /**
     * @param $eventId
     * @param $goodIndex
     * @return mixed
     * @throws FakeException
     *
     */

    public function buyGood($eventId, $goodIndex)
    {
        $curEvent = $this->checkEvent($eventId, array(DragonDef::EVENT_TYPE_SR));
        $arrGoodId = btstore_get()->DRAGONEVENT[$eventId][DragonEventCsvDef::GOODSID]->toArray();
        if($goodIndex < 0 || $goodIndex > count($arrGoodId) - 1)
        {
            throw new FakeException('error param goodIndex:%d', $goodIndex);
        }
        $goodId = $arrGoodId[$goodIndex];

        $curEventOfVaData = $this->getCurEventOfVaData();
        $other = $curEventOfVaData['other'];
        if(in_array($goodIndex, $other['bought']))
        {
            throw new FakeException('the good in goodIndex:%d has bought', $goodIndex);
        }

        //扣金币
        $userObj = EnUser::getUserObj(self::$uid);
        $needGold = btstore_get()->DRAGON_EVENT_SHOP[$goodId][DragonEventShopCsvDef::NOWCOST];
        if($userObj->subGold($needGold, StatisticsDef::ST_FUNCKEY_DRAGON_AIDO) == false)
        {
            throw new FakeException('Dragon buyGood subGold failed');
        }
        //给奖品
        $reward = array(btstore_get()->DRAGON_EVENT_SHOP[$goodId][DragonEventShopCsvDef::ITEM]);
        $res = RewardUtil::reward3DArr(self::$uid, $reward, StatisticsDef::ST_FUNCKEY_TOPUP_REWARD);
        //给积分
        $incPoint = btstore_get()->DRAGON_EVENT_SHOP[$goodId][DragonEventShopCsvDef::EACHPOINT];
        $this->updPoint($incPoint);
        $this->updTotalPoint($incPoint);

        //记录当前事件的购买记录
        $other['bought'][] = $goodIndex;
        $this->updCurEventOfVaData($eventId, array(), $other);

        //改该事件的状态为已促发
        //$this->updEventTriggerStatus($this->getCurPos());
        return $other;
    }

    public function contribute($eventId, $goodId)
    {
        $curEvent = $this->checkEvent($eventId, array(DragonDef::EVENT_TYPE_JX));
        if(!in_array($goodId, btstore_get()->DRAGONEVENT[$eventId][DragonEventCsvDef::GOODSID]->toArray()))
        {
            throw new FakeException('invalid param goodId:%d, not in csv conf.', $goodId);
        }

        //本次事件已经捐献的次数
        $curEventOfVaData = $this->getCurEventOfVaData();
        $other = $curEventOfVaData['other'];
        $conNum = $other['conNum'];

        if($conNum >= btstore_get()->NORMAL_CONFIG[NormalConfigDef::CONFIG_ID_DRAGON_CONTRIBUTE_MAX_NUM])
        {
            throw new FakeException("contribute:%d num has reached max limit", $conNum);
        }

        //扣除物品
        $item = btstore_get()->DRAGON_EVENT_SHOP[$goodId][DragonEventShopCsvDef::ITEM];
        $add = btstore_get()->DRAGON_EVENT_SHOP[$goodId][DragonEventShopCsvDef::ADD]; //捐献递增数量
        $itemTmpId = $item[1];
        $itemNum = $item[2] + $conNum * $add;
        $bag = BagManager::getInstance()->getBag(self::$uid);
        $bag->deleteItembyTemplateID($itemTmpId, $itemNum);

        //给积分
        $incPoint = btstore_get()->DRAGON_EVENT_SHOP[$goodId][DragonEventShopCsvDef::EACHPOINT];
        $this->updPoint($incPoint);
        $this->updTotalPoint($incPoint);

        //记录当前事件的捐献次数
        $other['conNum'] += 1;
        $this->updCurEventOfVaData($eventId, array(), $other);

        //改该事件的状态为已促发
        //$this->updEventTriggerStatus($this->getCurPos());
        return $other;
    }

    public function fightBoss($eventId, $armyIndex, $directWin=false)
    {
        $curEvent = $this->checkEvent($eventId, array(DragonDef::EVENT_TYPE_SL));
        $arrArmyId = btstore_get()->DRAGONEVENT[$eventId][DragonEventCsvDef::ARMYID];
        if($armyIndex < 0 || $armyIndex >= count($arrArmyId))
        {
            throw new FakeException('invalid armyIndex:%d', $armyIndex);
        }
        $armyId = $arrArmyId[$armyIndex];
        //获取当前试炼事件已打败的armyId
        $curEventOfVaData = $this->getCurEventOfVaData();
        $other = $curEventOfVaData['other'];
        if($armyIndex > $other['defeated'] + 1)
        {
            throw new FakeException('can not jump:%d cur index:%d', $armyIndex, $other['defeated']);
        }
        if($armyIndex <= $other['defeated'])
        {
            throw new FakeException('have defeated:%d', $armyIndex);
        }

        //消耗行动力
        $costAct = btstore_get()->DRAGONEVENT[$eventId][DragonEventCsvDef::BOSSCOSTACT][$armyIndex];
        $curAct = $this->dragonInfo[TblDragonDef::ACT];
        $realCostAct = $curAct - $costAct >= 0 ? $costAct : $curAct;

        $ret = array();
        $isSuc = false;
        if(!$directWin)
        {
            $btFmt = $this->getVaBf(true);
            if(empty($btFmt['arrHero']))
            {
                throw new FakeException(' btFmt is empty, all the heros is dead. ');
            }

            //获取战斗类型
            $callback	= array();
            $extraInfo	= array('type' => BattleType::DRAGON);
            $enemyBtFmt	=	EnFormation::getMonsterBattleFormation($armyId);
            $btType		= btstore_get()->ARMY[$armyId]['fight_type'];
            $winCon    =    CopyUtil::getVictoryConditions($armyId);

            $atkRet	= EnBattle::doHero($btFmt,$enemyBtFmt,$btType,$callback,$winCon,$extraInfo);
            $isSuc = BattleDef::$APPRAISAL[$atkRet['server']['appraisal']] <= BattleDef::$APPRAISAL['D'];

            $this->updArrHpAfterFight($atkRet);
            //自动回血
            $this->regeneratesHp();
            //去除加成
            $this->clrAddtion();
            $ret['atkRet'] = $atkRet;
            $ret['hppool'] = $this->dragonInfo[TblDragonDef::HP_POOL];
            $ret['arrhp'] = $this->dragonInfo[TblDragonDef::VA_DATA]['arrhp'];
        }
        else
        {
            //扣金币
            $userObj = EnUser::getUserObj(self::$uid);
            $needGold = $incPoint = btstore_get()->DRAGONEVENT[$eventId][DragonEventCsvDef::GOLDBOSS][$armyIndex];
            if($userObj->subGold($needGold, StatisticsDef::ST_FUNCKEY_DRAGON_GOLD_BOSS) == false)
            {
                throw new FakeException('Dragon gold boss subGold failed');
            }
        }

        if($isSuc || $directWin)
        {
            //挑战成功或者直接胜利消耗行动力
            $this->dragonInfo[TblDragonDef::ACT] -= $realCostAct;
            //给积分
            $incPoint = btstore_get()->DRAGONEVENT[$eventId][DragonEventCsvDef::BOSSSCORE][$armyIndex];
            $this->updPoint($incPoint);
            $this->updTotalPoint($incPoint);
            //给奖励
            $dropId = btstore_get()->DRAGONEVENT[$eventId][DragonEventCsvDef::BOSSDROP][$armyIndex];
            $dropRet = EnUser::drop(self::$uid, array($dropId));
            if($armyIndex > $other['defeated'])
            {
                $other['defeated'] = $armyIndex;
            }
            $other['drop'] = $dropRet;
            $this->updCurEventOfVaData($eventId, array(), $other);
        }

        //刷新单次寻龙最高积分
        $this->rfrOnceMaxPoint();

        $ret['other'] = $other;

        //这个drop,不作为最后的弹板
        if(isset($other['drop']))
        {
            unset($other['drop']);
            $this->updCurEventOfVaData($eventId, array(), $other);
        }
        return $ret;
    }

    /**
     * 刷新单次寻龙最高积分
     */
    private function rfrOnceMaxPoint()
    {
        if($this->dragonInfo[TblDragonDef::POINT] > $this->dragonInfo[TblDragonDef::ONCE_MAX_POINT])
        {
            $this->dragonInfo[TblDragonDef::ONCE_MAX_POINT] = $this->dragonInfo[TblDragonDef::POINT];
        }
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */