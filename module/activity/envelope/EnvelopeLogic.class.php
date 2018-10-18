<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: EnvelopeLogic.class.php 235298 2016-03-28 11:32:21Z GuohaoZheng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/envelope/EnvelopeLogic.class.php $
 * @author $Author: GuohaoZheng $(zhengguohao@babeltime.com)
 * @date $Date: 2016-03-28 11:32:21 +0000 (Mon, 28 Mar 2016) $
 * @version $Revision: 235298 $
 * @brief 
 *  
 **/
class EnvelopeLogic
{
    public static function getInfo($uid, $type)
    {
        self::checkAct($uid);
        
        if ( !in_array($type, EnvelopeDef::$ENVELOPE_LIST_TYPE) )
        {
            throw new FakeException('err list type. type:%d.',$type);
        }
        
        $canSendInfo = self::getCanSend($uid);
        
        $canSendTotal = $canSendInfo['canSendTotal'];
        $canSendToday = $canSendInfo['canSendToday'];
        
        $startTime = self::getActStartTime();
        $endTime = self::getActEndTime();
        
        $conf = self::getActData();
        $rowsNum = $conf[EnvelopeDef::MAX_MSG_NUM];
        
        $ret = array();
        if (EnvelopeDef::ENVELOPE_LIST_TYPE_USER == $type)
        {
            $arrEnvelopeField = EnvelopeDef::$ALL_ENVELOPE_FIELD;
            $arrSelfSendList  = EnvelopeDao::getSendListByUid($uid, $arrEnvelopeField, $startTime, $endTime, 0, $rowsNum);
            
            foreach ($arrSelfSendList as $value)
            {
                if ( ( Util::getTime() > $value[EnvelopeDef::SQL_ENVELOPE_SEND_TIME] + $conf[EnvelopeDef::RECLAIM_TIME] + EnvelopeDef::TIMER_OFFSET )
                    && empty($value[EnvelopeDef::SQL_ENVELOPE_BACK_TIME])
                    && !empty($value[EnvelopeDef::SQL_ENVELOPE_LEFT_NUM]) )
                {
                    Logger::warning("user:%d has timer undo or failed.", $uid);
                    RPCContext::getInstance()->executeTask($uid, 'envelope.rewardUser', array($uid));
                    break;
                }
            }
            
            $arrEnvelopeUserField = EnvelopeDef::$ALL_ENVELOPE_USER_FIELD;
            $arrSelfRecvList  = EnvelopeDao::getEnvelopeUserRecvListByUid($uid, $arrEnvelopeUserField, $startTime, $endTime, 0, $rowsNum);
            
            $arrSelfRecvList = Util::arrayIndex($arrSelfRecvList, EnvelopeDef::SQL_ENVELOPE_USER_EID);
            
            $arrRecvEidList = array();
            
            foreach ($arrSelfRecvList as $value)
            {
                $arrRecvEidList[] = $value[EnvelopeDef::SQL_ENVELOPE_USER_EID];
            }
            
            $arrSendListByRecvEid = EnvelopeDao::getArrEnvelopeInfo($arrRecvEidList, $arrEnvelopeField);
            
            foreach ($arrSendListByRecvEid as $key => $value)
            {
                if (isset($arrSelfRecvList[$value[EnvelopeDef::SQL_ENVELOPE_EID]]))
                {
                    //此处是因为涉及用时间排序的问题，为了和下面一致，玩家领别人红包的时间赋值给了这个红包的发送时间，注意此歧义
                    //前端后来改成需显示回收，所以此处数据已然不对，但对于我所领别人的不会显示回收，故而暂时不改
                    $arrSendListByRecvEid[$key][EnvelopeDef::SQL_ENVELOPE_SEND_TIME] = $arrSelfRecvList[$value[EnvelopeDef::SQL_ENVELOPE_EID]][EnvelopeDef::SQL_ENVELOPE_USER_RECV_TIME];
                    $arrSendListByRecvEid[$key]['gold'] = $arrSelfRecvList[$value[EnvelopeDef::SQL_ENVELOPE_EID]][EnvelopeDef::SQL_ENVELOPE_USER_RECV_GOLD];
                }
            }
            
            $ret = array_merge($arrSelfSendList, $arrSendListByRecvEid);
        }
        else 
        {
            $arrScale = array();
            
            if (EnvelopeDef::ENVELOPE_LIST_TYPE_ALL == $type)
            {
                $arrScale[] = 0;
            }
            
            $member = GuildMemberObj::getInstance($uid);
            $guildId = $member->getGuildId();
            
            if ( !empty($guildId) )
            {
                $arrScale[] = $guildId;
            }
            
            $arrField = EnvelopeDef::$ALL_ENVELOPE_FIELD;
            
            $unrecvEnvelopeList = array();
            if ( !empty($arrScale) )
            {
                $arrWhere = array(
                    array(EnvelopeDef::SQL_ENVELOPE_SCALE, 'IN', $arrScale),
                    array(EnvelopeDef::SQL_ENVELOPE_SEND_TIME, 'BETWEEN', array($startTime, $endTime)),
                    array(EnvelopeDef::SQL_ENVELOPE_BACK_TIME, '=', 0),
                    array(EnvelopeDef::SQL_ENVELOPE_LEFT_NUM, '>', 0),
                );
                
                $unrecvEnvelopeList = EnvelopeDao::getEnvelopeList($arrWhere, $arrField, 0, $rowsNum);
            }
            
            $ret = $unrecvEnvelopeList;
            
            if (count($unrecvEnvelopeList) < $rowsNum)
            {
                $needRowsNum = $rowsNum - count($unrecvEnvelopeList);
                
                $arrDoneByRecv = array();
                $arrDoneByRecl = array();
                
                if (!empty($arrScale))
                {
                    $arrDoneByRecvWhere = array(
                        array(EnvelopeDef::SQL_ENVELOPE_SCALE, 'IN', $arrScale),
                        array(EnvelopeDef::SQL_ENVELOPE_SEND_TIME, 'BETWEEN', array($startTime, $endTime)),
                        array(EnvelopeDef::SQL_ENVELOPE_LEFT_NUM, '=', 0),
                    );
                    $arrDoneByReclWhere = array(
                        array(EnvelopeDef::SQL_ENVELOPE_SCALE, 'IN', $arrScale),
                        array(EnvelopeDef::SQL_ENVELOPE_SEND_TIME, 'BETWEEN', array($startTime, $endTime)),
                        array(EnvelopeDef::SQL_ENVELOPE_BACK_TIME, '!=', 0),
                    );
                    
                    $arrDoneByRecv = EnvelopeDao::getEnvelopeList($arrDoneByRecvWhere, $arrField, 0, $needRowsNum);
                    $arrDoneByRecl = EnvelopeDao::getEnvelopeList($arrDoneByReclWhere, $arrField, 0, $needRowsNum);
                }
                
                $recvedEnvelopeList = array_merge($arrDoneByRecv, $arrDoneByRecl);
                
                $ret = array_merge($ret, $recvedEnvelopeList);
            }
        }
        
        $arrEid = array();
        $arrSendTime = array();
        foreach ($ret as $key => $value)
        {
            $arrEid[$key] = $value[EnvelopeDef::SQL_ENVELOPE_EID];
            $arrSendTime[$key] = $value[EnvelopeDef::SQL_ENVELOPE_SEND_TIME];
        }
        
        array_multisort($arrSendTime, SORT_DESC, $arrEid, SORT_DESC, $ret);
        $ret = array_slice($ret, 0, $rowsNum);
        
        $arrUid = array();
        foreach ($ret as $value)
        {
            if (!in_array($value[EnvelopeDef::SQL_ENVELOPE_SENDER_UID], $arrUid))
            {
                $arrUid[] = $value[EnvelopeDef::SQL_ENVELOPE_SENDER_UID];
            }
        }
        
        $arrUserBasicInfo = self::getArrUserBasicInfo($arrUid, array('uname'));
        
        $rankList = array();
        foreach ($ret as $key => $value)
        {
            $uid = $value[EnvelopeDef::SQL_ENVELOPE_SENDER_UID];
            
            $gold = isset($value['gold']) ? $value['gold'] : 0;
        
            $rankList[] = array(
                'uid' => $uid,
                'uname' => $arrUserBasicInfo[$uid]['uname'],
                'eid' => $value[EnvelopeDef::SQL_ENVELOPE_EID],
                'left' => $value[EnvelopeDef::SQL_ENVELOPE_LEFT_NUM],
                'sendTime' => $value[EnvelopeDef::SQL_ENVELOPE_SEND_TIME],
                'gold' => $gold,
            );
        }
        
        return array(
            'canSendTotal' => $canSendTotal,
            'canSendToday' => $canSendToday,
            'rankList' => $rankList,
        );
    }
    
    public static function getSingleInfo($uid, $eid)
    {
        $envelopeInfo = EnvelopeDao::getSingleEnvelopeInfo($eid, EnvelopeDef::$ALL_ENVELOPE_FIELD);
        
        if ( empty($envelopeInfo) )
        {
            throw new FakeException('wrong eid:%d.', $eid);
        }
        
        $eid = $envelopeInfo[EnvelopeDef::SQL_ENVELOPE_EID];
        $senderUid = $envelopeInfo[EnvelopeDef::SQL_ENVELOPE_SENDER_UID];
        
        $arrEnvelopeUserList = EnvelopeDao::getEnvelopeUserListByEid($eid, EnvelopeDef::$ALL_ENVELOPE_USER_FIELD);
        
        $arrUid = array();
        
        if ( !in_array($senderUid, $arrUid) )
        {
            $arrUid[] = $senderUid;
        }
        
        foreach ($arrEnvelopeUserList as $key => $envelope)
        {
            if ( !in_array($envelope[EnvelopeDef::SQL_ENVELOPE_USER_RECV_UID], $arrUid) )
            {
                $arrUid[] = $envelope[EnvelopeDef::SQL_ENVELOPE_USER_RECV_UID];
            }
        }
        
        $arrUserInfo = self::getArrUserBasicInfo($arrUid, array('htid','uname', 'dress'));
        
        $rankList = array();
        foreach ($arrEnvelopeUserList as $key => $envelope)
        {
            $rankList[$key]['uid'] = $envelope[EnvelopeDef::SQL_ENVELOPE_USER_RECV_UID];
            $rankList[$key]['uname'] = $arrUserInfo[$envelope[EnvelopeDef::SQL_ENVELOPE_USER_RECV_UID]]['uname'];
            $rankList[$key]['htid'] = $arrUserInfo[$envelope[EnvelopeDef::SQL_ENVELOPE_USER_RECV_UID]]['htid'];
            $rankList[$key]['dressInfo'] = $arrUserInfo[$envelope[EnvelopeDef::SQL_ENVELOPE_USER_RECV_UID]]['dress'];
            $rankList[$key]['gold'] = $envelope[EnvelopeDef::SQL_ENVELOPE_USER_RECV_GOLD];
        }
        
        $ret = array(
            'uid' => $senderUid,
            'uname' => $arrUserInfo[$senderUid]['uname'],
            'htid' => $arrUserInfo[$senderUid]['htid'],
            'dressInfo' => $arrUserInfo[$senderUid]['dress'],
            'shareNum' => count( $envelopeInfo[EnvelopeDef::SQL_ENVELOPE_VA_DATA]['envelopeInfo']),
            'leftNum' => $envelopeInfo[EnvelopeDef::SQL_ENVELOPE_LEFT_NUM],
            'sendTime' => $envelopeInfo[EnvelopeDef::SQL_ENVELOPE_SEND_TIME],
            'msg' => $envelopeInfo[EnvelopeDef::SQL_ENVELOPE_VA_DATA]['msg'],
            'rankList' => $rankList,
        );
        
        return $ret;
    }
    
    public static function send($uid, $scale, $goldNum, $divNum, $msg)
    {
        self::checkAct($uid);
        
        self::checkSend($uid, $scale, $goldNum, $divNum, $msg);
        
        $msg = TrieFilter::mb_replace($msg);
        
        $divInfo = self::divGold($goldNum, $divNum);
        
        $member = GuildMemberObj::getInstance($uid);
        $guildId = $member->getGuildId();
        
        $iScale = ( EnvelopeDef::ENVELOPE_SCALE_GUILD == $scale ) ? $guildId : 0;
        
        $envelopeInfo = array(
            EnvelopeDef::SQL_ENVELOPE_SENDER_UID => $uid,
            EnvelopeDef::SQL_ENVELOPE_SCALE => $iScale,
            EnvelopeDef::SQL_ENVELOPE_SEND_TIME => Util::getTime(),
            EnvelopeDef::SQL_ENVELOPE_SUM_GOLD_NUM => $goldNum,
            EnvelopeDef::SQL_ENVELOPE_LEFT_NUM => $divNum,
            EnvelopeDef::SQL_ENVELOPE_BACK_TIME => 0,
            EnvelopeDef::SQL_ENVELOPE_VA_DATA => array(
                'envelopeInfo' => $divInfo,
                'msg' => $msg,
            ),
        );
        
        EnvelopeDao::addNewEnvelope($envelopeInfo);
        
        if (EnvelopeDef::ENVELOPE_SCALE_WHOLE_GROUP == $scale)
        {
            RPCContext::getInstance()->sendMsg(array(0), PushInterfaceDef::ENVELOPE_SEND_WHOLE_GROUP, array());
        }
        elseif (EnvelopeDef::ENVELOPE_SCALE_GUILD == $scale && !empty($guildId))
        {
            RPCContext::getInstance()->sendFilterMessage('guild', $guildId, PushInterfaceDef::ENVELOPE_SEND_GUILD, array());
        }
        else 
        {
            throw new FakeException('unknown scale %d.', $scale);
        }
        
        $taskName = 'envelope.rewardUser';
        
        $conf = self::getActData();
        
        $lastTime = $conf[EnvelopeDef::RECLAIM_TIME];
        
        TimerTask::addTask($uid, Util::getTime() + $lastTime + EnvelopeDef::TIMER_OFFSET, $taskName, array($uid));
        
        return 'ok';
    }
    
    public static function open($uid, $eid)
    {
        self::checkAct($uid);
        
        $arrField = EnvelopeDef::$ALL_ENVELOPE_USER_FIELD;
        
        $envelopeInfo = EnvelopeDao::getEnvelopeUserInfoByUidAndEid($uid, $eid, $arrField);
        
        if (!empty($envelopeInfo))
        {
            throw new FakeException('user:%d has recieved eid:%d.', $uid, $eid);
        }
        
        $addKey = EnvelopeDef::MC_RECV_OVER.$eid;
        
        $value = McClient::get($addKey);
        
        if (!empty($value))
        {
            return 0;
        }
        
        $conf = self::getActData();
        
        $lockKey = EnvelopeDef::LOCK_ENVELOPE.$eid;
        
        $locker = new Locker();
        
        $locker->lock($lockKey);
        
        try
        {
            $arrField = EnvelopeDef::$ALL_ENVELOPE_FIELD;
            
            $envelopeInfo = EnvelopeDao::getSingleEnvelopeInfo($eid, $arrField);
            
            if ( empty($envelopeInfo[EnvelopeDef::SQL_ENVELOPE_LEFT_NUM]) 
                || ( Util::getTime() >= $envelopeInfo[EnvelopeDef::SQL_ENVELOPE_SEND_TIME] + $conf[EnvelopeDef::RECLAIM_TIME] ) )
            {
                return 0;
            }
            
            $recvIndex = $envelopeInfo[EnvelopeDef::SQL_ENVELOPE_LEFT_NUM] - 1;
            
            $arrGoldList = $envelopeInfo[EnvelopeDef::SQL_ENVELOPE_VA_DATA]['envelopeInfo'];
            
            if ( !in_array($recvIndex, array_keys($arrGoldList)) )
            {
                throw new InterException('something wrong. recvIndex:%d, arrGoldList:%s.',$recvIndex,$arrGoldList);
            }
            
            if (0 == $recvIndex)
            {
                McClient::add($addKey, array(1), SECONDS_OF_DAY);
            }
            
            $arrUpdate = array(
                EnvelopeDef::SQL_ENVELOPE_LEFT_NUM => $recvIndex,
            );
            
            EnvelopeDao::updateEnvelope($eid, $arrUpdate);
            
            $locker->unlock($lockKey);
        }
        catch(Exception $e)
        {
            $locker->unlock($lockKey);
            throw $e;
        }
        
        $recvGold = $arrGoldList[$recvIndex];
        
        $arrNewRecv = array(
            EnvelopeDef::SQL_ENVELOPE_USER_RECV_UID => $uid,
            EnvelopeDef::SQL_ENVELOPE_USER_EID => $eid,
            EnvelopeDef::SQL_ENVELOPE_USER_RECV_TIME => Util::getTime(),
            EnvelopeDef::SQL_ENVELOPE_USER_RECV_INDEX => $recvIndex,
            EnvelopeDef::SQL_ENVELOPE_USER_RECV_GOLD => $recvGold,
        );
        
        EnvelopeDao::addNewEnvelopeUser($arrNewRecv);
        
        $userObj = EnUser::getUserObj($uid);
        
        if ( FALSE == $userObj->addGold($recvGold, StatisticsDef::ST_FUNCKEY_ENVELOPE_RECV_GET) )
        {
            throw new InterException('add gold failed. uid:%d, gold:%d.', $uid, $recvGold);
        }
        
        $userObj->update();
        
        return $recvGold;
    }
    
    public static function divGold($goldNum, $divNum)
    {
        if ($goldNum < $divNum)
        {
            throw new FakeException('goldNum must more than divNum.goldNum:%d, $divNum:%d.',$goldNum, $divNum);
        }
        
        $minGoldEveryoneTheory = intval($goldNum / $divNum / EnvelopeDef::MIN_GOLD_EVERYONE_ARGS);
        
        $minGoldEveryone = ( $minGoldEveryoneTheory <= EnvelopeDef::MIN_GOLD_EVERYONE_GOLD ) ? EnvelopeDef::MIN_GOLD_EVERYONE_GOLD : $minGoldEveryoneTheory;
        
        $needDivNum = $goldNum - $divNum * $minGoldEveryone;
        
        if ($needDivNum < 0)
        {
            throw new FakeException('not enough for baodi. goldNum:%d, divNum:%d, needDivNum:%d.',$goldNum,$divNum,$needDivNum);
        }
        
        $arrRandGoldList = self::divGoldContainZero($needDivNum, $divNum);
        
        $arrRetGoldList = array();
        
        for ($i = 0; $i < $divNum; $i++)
        {
            $randNum = isset($arrRandGoldList[$i]) ? $arrRandGoldList[$i] : 0;

            $arrRetGoldList[$i] = $minGoldEveryone + $randNum;
        }
        
        shuffle($arrRetGoldList);
        
        return $arrRetGoldList;
    }
    
    //分金币，允许为0，应符合正态分布
    public static function divGoldContainZero($goldNum, $divNum)
    {
        $bakGoldNum = $goldNum;
        
        $arrWeightList = self::getNBWeightList($goldNum, $divNum);
        
        $sum = array_sum( array_keys($arrWeightList) );
        
        $randKeys = Util::backSample($arrWeightList, $divNum);
        
        $arrGoldList = array();
        
        for ($i = 0; $i < $divNum - 1; $i++)
        {
            $tmpRand = 0;
            
            if ($goldNum > 0)
            {
                $tmpRand = intval( $bakGoldNum * $randKeys[$i] / $sum );
                
                if ($tmpRand >= $goldNum)
                {
                    $tmpRand = $goldNum;
                }
                
                $goldNum -= $tmpRand;
            }
            
            $arrGoldList[] = $tmpRand;
        }
        
        $arrGoldList[] = $goldNum;
        
        return $arrGoldList;
    }
    
    public static function getNBWeightList($goldNum, $divNum)
    {
        $arrRange = EnvelopeDef::$ENVELOPE_RANGE;
        $delt = ($arrRange[1] - $arrRange[0] ) / $divNum;
        
        $u = ( $arrRange[1] - $arrRange[0] ) / 2;
        $o = $u;
        
        $arrWeightList = array();
        for( $i = 0; $i < $divNum; $i++)
        {
            $x = rand($arrRange[0] + $i * $delt, $arrRange[0] + ($i+1)*$delt );
            
            $arrWeightList[$x]['weight'] = intval( self::getNBWeight($u, $o, $x) * 100000000 );
        }
        
        return $arrWeightList;
    }
    
    public static function getNBWeight($u, $o, $x)
    {
        $fx = ( 1 / ( sqrt(2 * M_PI) * $o ) ) *  pow(M_E, - ($x - $u) * ($x - $u) / (2 * $o * $o) );
        return $fx;
    }
    
    public static function checkSend($uid, $scale, $goldNum, $divNum, $msg)
    {
        self::checkAct($uid);
        
        if ( !in_array($scale, EnvelopeDef::$ENVELOPE_SCALE) )
        {
            throw new FakeException('invalid scale: %d.',$scale);
        }
        
        if ( is_string($msg) && strlen($msg) != 0
            && mb_strlen($msg, FrameworkConfig::ENCODING) > EnvelopeDef::MAX_MSG_LENGTH)
        {
            throw new FakeException('msg:%s must be shorter than %d', $msg, EnvelopeDef::MAX_MSG_LENGTH);
        }
        
        $member = GuildMemberObj::getInstance($uid);
        $guildId = $member->getGuildId();
        
        if (EnvelopeDef::ENVELOPE_SCALE_GUILD == $scale && empty($guildId))
        {
            throw new FakeException('user:%d no guild, can not send guild envelope.',$uid);
        }
        
        $conf = self::getActData();
        
        $minGoldNum = isset($conf[EnvelopeDef::MIN_GOLD_LIMIT]) ? $conf[EnvelopeDef::MIN_GOLD_LIMIT] : 0;
        $maxDivNum = isset($conf[EnvelopeDef::MAX_NUM_LIMIT]) ? $conf[EnvelopeDef::MAX_NUM_LIMIT] : PHP_INT_MAX;
        $dayMaxNum = isset($conf[EnvelopeDef::DAY_MAX_GOLD_LIMIT]) ? $conf[EnvelopeDef::DAY_MAX_GOLD_LIMIT] : PHP_INT_MAX;
        
        if ( $goldNum <= 0 || $divNum <= 0 || $goldNum < $minGoldNum || $goldNum > $dayMaxNum || $divNum > $maxDivNum)
        {
            throw new FakeException('invalid goldNum:%d or divNum:%d. minGoldNum:%d, dayMaxNum, maxDivNum:%d.', $goldNum, $divNum, $minGoldNum, $dayMaxNum, $maxDivNum);
        }
        
        $userObj = EnUser::getUserObj($uid);
        $userGold = $userObj->getGold();
        
        if ($goldNum > $userGold)
        {
            throw new FakeException('no enough gold. goldNum:%d, userGold:%d.', $goldNum, $userGold);
        }
        
        $canSend = self::getCanSend($uid);
        $canSendToday = $canSend['canSendToday'];
        
        if ($goldNum > $canSendToday)
        {
            throw new FakeException('beyond limit. goldNum:%d, canSendToday:%d.', $goldNum, $canSendToday);
        }
        
        if (FALSE == $userObj->subGold($goldNum, StatisticsDef::ST_FUNCKEY_ENVELOPE_SEND_COST, FALSE))
        {
            throw new FakeException('sub gold failed! goldNum:%d.', $goldNum);
        }
        
        $userObj->update();
    }
    
    public static function checkAct($uid)
    {
        if (FALSE == EnActivity::isOpen(ActivityName::ENVELOPE))
        {
            throw new FakeException('act envelope is not open.');
        }
        
        $userObj = EnUser::getUserObj($uid);
        $level = $userObj->getLevel();
        
        $conf = self::getActData();
        $needLevel = $conf[EnvelopeDef::NEED_LEVEL];
        
        if ($level < $needLevel)
        {
            throw new FakeException('level not enough. user:%d level:%d,need level:%d.',$uid,$level,$needLevel);
        }
    }
    
    public static function getSelfSendList($uid, $startTime, $endTime, $offset, $limit)
    {
        $arrField = EnvelopeDef::$ALL_ENVELOPE_FIELD;
        
        $arrSendList = EnvelopeDao::getSendListByUid($uid, $arrField, $startTime, $endTime, $offset, $limit);
        
        return $arrSendList;
    }
    
    public static function getSelfRecvList($uid, $startTime, $endTime, $offset, $limit)
    {
        $arrField = EnvelopeDef::$ALL_ENVELOPE_USER_FIELD;
        
        $arrRecvList = EnvelopeDao::getEnvelopeUserRecvListByUid($uid, $arrField, $startTime, $endTime, $offset, $limit);
        
        return $arrRecvList;
    }
    
    public static function getArrUserBasicInfo($arrUid, $arrField, $db='')
    {
        $num = count($arrUid);
        
        $arrUidList = array();
        
        $offset = 0;
        
        $limit = DataDef::MAX_FETCH;
        
        do 
        {
            $arrUidList[] = array_slice($arrUid, $offset, $limit);
            $offset += $limit;
            
        }while( $offset < $num );
        
        $ret = array();
        foreach ($arrUidList as $value)
        {
            $tmpRetUserInfo = EnUser::getArrUserBasicInfo($value, $arrField, $db);
            
            $ret = array_merge($ret, $tmpRetUserInfo);
        }
        
        $ret = Util::arrayIndex($ret, EnvelopeDef::SQL_ENVELOPE_SENDER_UID);
        
        return $ret;
    }
    
    public static function getCanSend($uid)
    {
        $conf = self::getActData();
        
        $startTime = self::getActStartTime();
        $endTime = self::getActEndTime();
        
        $zeroStartTime = intval( strtotime( date("Y-m-d", $startTime) ) );
        $zeroEndTime = intval( strtotime( date( "Y-m-d", $endTime ) ) );
        
        $conf = self::getActData();
        
        $canSendTotal = 0;
        $canSendToday = 0;
        
        $arrEnvelopeField = EnvelopeDef::$ALL_ENVELOPE_FIELD;
        $arrUserSendList = EnvelopeDao::getSendListByUid($uid, $arrEnvelopeField, $startTime, $endTime, 0, PHP_INT_MAX);
        
        $now = Util::getTime();
        
        $zeroToday = intval( strtotime( date( "Y-m-d", $now ) ) );
        $maxTimeToday = $zeroToday + SECONDS_OF_DAY - 1;
        
        $hasSentTotal = 0;
        $hasSentToday = 0;
        foreach ($arrUserSendList as $value)
        {
            $num = 0;
            
            if ( empty($value[EnvelopeDef::SQL_ENVELOPE_BACK_TIME]) )
            {
                $num = $value[EnvelopeDef::SQL_ENVELOPE_SUM_GOLD_NUM];
            }
            else 
            {
                $leftNum = $value[EnvelopeDef::SQL_ENVELOPE_LEFT_NUM];
                
                $backNum = 0;
                for ( $i =0; $i < $leftNum; $i++)
                {
                    $backNum += $value[EnvelopeDef::SQL_ENVELOPE_VA_DATA]['envelopeInfo'][$i];
                }
                
                $num = $value[EnvelopeDef::SQL_ENVELOPE_SUM_GOLD_NUM] - $backNum;
            }
            
            $hasSentTotal += $num;
            
            $sendTime = $value[EnvelopeDef::SQL_ENVELOPE_SEND_TIME];
            if ($sendTime >= $zeroToday && $sendTime <= $maxTimeToday)
            {
                $hasSentToday += $num;
            }
        }
        
        $sumChargeNum = self::getChargeDuringAct($uid);
        
        $canSendTotal = $sumChargeNum - $hasSentTotal;
        
        $dayMaxNumToday = $conf[EnvelopeDef::DAY_MAX_GOLD_LIMIT];
        
        $limitToday = $dayMaxNumToday - $hasSentToday;
        
        $canSendToday = ($canSendTotal >= $limitToday) ? $limitToday : $canSendTotal;
        
        //理论上canSendToday和canSendTotal 不会为负数，为了防止改配置，加个检查
        $canSendTotal = ($canSendTotal < 0) ? 0 : $canSendTotal;
        $canSendToday = ($canSendToday < 0) ? 0 : $canSendToday;
        
        return array(
            'canSendTotal' => $canSendTotal,
            'canSendToday' => $canSendToday,
        );
    }
    
    public static function getChargeDuringAct($uid)
    {
        $startTime = self::getActStartTime();
        $endTime = self::getActEndTime();
        
        $zeroStartTime = intval( strtotime( date("Y-m-d", $startTime) ) );
        
        $sumChargeNum = EnUser::getRechargeGoldByTime($zeroStartTime, $endTime, $uid, TRUE);
        
        return $sumChargeNum;
    }
    
    public static function rewardUser($uid)
    {
        $conf = self::getActData();
        
        if (empty($conf))
        {
            Logger::warning("no conf for envelope due time reward user.");
            return ;
        }
        
        $startTime = self::getActStartTime();
        $endTime = self::getActEndTime();
        
        $arrUserSendList = EnvelopeDao::getSendListByUid($uid, EnvelopeDef::$ALL_ENVELOPE_FIELD, $startTime, $endTime, 0, PHP_INT_MAX);
        
        $backNum = 0;
        $arrNeedUdtEidList = array();
        foreach ($arrUserSendList as $value)
        {
            if ($value[EnvelopeDef::SQL_ENVELOPE_LEFT_NUM] > 0 
                && 0 == $value[EnvelopeDef::SQL_ENVELOPE_BACK_TIME]
                && $value[EnvelopeDef::SQL_ENVELOPE_SEND_TIME] + $conf[EnvelopeDef::RECLAIM_TIME] < Util::getTime() )
            {
                
                $arrNeedUdtEidList[] = $value[EnvelopeDef::SQL_ENVELOPE_EID];
                
                for ($i = 0; $i < $value[EnvelopeDef::SQL_ENVELOPE_LEFT_NUM]; $i++)
                {
                    $backNum += $value[EnvelopeDef::SQL_ENVELOPE_VA_DATA]['envelopeInfo'][$i];
                }
                
                if (count($arrNeedUdtEidList) == 100 )
                {
                    break;
                }
            }
        }
        
        if (!empty($backNum))
        {
            $arrUdt = array(
                EnvelopeDef::SQL_ENVELOPE_BACK_TIME => Util::getTime()
            );
            
            EnvelopeDao::updateArrEnvelope($arrNeedUdtEidList, $arrUdt);
            
            $reward = array( array(array(RewardConfType::GOLD, 0,$backNum)) );
            RewardUtil::reward3DtoCenter($uid, $reward, RewardSource::RED_ENVELOPE_RECYCLE_REWARD);
            Logger::info("due time reward user:%d end. back gold:%d.", $uid, $backNum);
        }
    }
    
    public static function getActStartTime()
    {
        $conf = EnActivity::getConfByName(ActivityName::ENVELOPE);
        return $conf['start_time'];
    }
    
    public static function getActEndTime()
    {
        $conf = EnActivity::getConfByName(ActivityName::ENVELOPE);
        return $conf['end_time'];
    }
    
    public static function getActData()
    {
        $conf = EnActivity::getConfByName(ActivityName::ENVELOPE);
        
        return $conf['data'];
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */