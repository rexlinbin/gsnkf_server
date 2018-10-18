<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Olympic.class.php 125747 2014-08-08 12:33:08Z ShijieHan $
 *
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/olympic/Olympic.class.php $
 * @author $Author: ShijieHan $(zhangtiantian@babeltime.com)
 * @date $Date: 2014-08-08 12:33:08 +0000 (Fri, 08 Aug 2014) $
 * @version $Revision: 125747 $
 * @brief 
 *  
 **/
class Olympic implements IOlympic
{
    private $uid = NULL;
    public function __construct()
    {
        $this->uid = RPCContext::getInstance()->getUid();
        if($this->uid != 0)
        {
            OlympicLogic::isOlympicSwitchOpen($this->uid);
        }

    }
	/* (non-PHPdoc)
     * @see IOlympic::getInfo()
     */
    public function getInfo ()
    {
        // TODO Auto-generated method stub
        Logger::trace('olympic.getInfo start');
        $ret = OlympicLogic::getInfo();
        Logger::trace('olympic.getInfo end.result %s',$ret);
        return $ret;
    }

	/* (non-PHPdoc)
     * @see IOlympic::getFightInfo()
     */
    public function getFightInfo ()
    {
        // TODO Auto-generated method stub
        Logger::trace('olympic.getFightInfo start');
        $ret = OlympicLogic::getFightInfo();
        Logger::trace('olympic.getFightInfo end.result %s',$ret);
        return $ret;
    }

	/* (non-PHPdoc)
     * @see IOlympic::signUp()
     */
    public function signUp ($index)
    {
        // TODO Auto-generated method stub
        Logger::trace('olympic.signUp start');
        $index = intval($index);
        $ret = OlympicLogic::signUp($this->uid, $index);
        Logger::trace('olympic.signUp end.result %s',$ret);
        return $ret;
    }

	/* (non-PHPdoc)
     * @see IOlympic::challenge()
     */
    public function challenge($signUpIndex)
    {
        // TODO Auto-generated method stub
        Logger::trace('olympic.challenge start');
        $signUpIndex = intval($signUpIndex);
        $ret = OlympicLogic::challenge($this->uid, $signUpIndex);
        Logger::trace('olympic.challenge end.result %s',$ret);
        return $ret;
    }

	/* (non-PHPdoc)
     * @see IOlympic::clearChanllengeCd()
     */
    public function clearChallengeCd ()
    {
        // TODO Auto-generated method stub
        Logger::trace('olympic.signUp start');
        $ret = OlympicLogic::clearChallengeCd($this->uid);
        Logger::trace('olympic.signUp end.result %s',$ret);
        return $ret;
    }

	/* (non-PHPdoc)
     * @see IOlympic::cheer()
     */
    public function cheer ($cheerUid)
    {
        // TODO Auto-generated method stub
        Logger::trace('olympic.cheer start');
        $cheerUid = intval($cheerUid);
        $ret = OlympicLogic::cheer($this->uid, $cheerUid);
        Logger::trace('olympic.cheer end.result %s',$ret);
        return $ret;
    }
    
    public function modifyUserInfoByOther($uid,$modifyInfo,$arrIntegralRecord)
    {
        if ($uid == 0)
        {
            throw new InterException( 'uid is 0' );
        }
        $guid = RPCContext::getInstance ()->getSession ( UserDef::SESSION_KEY_UID );
        if ($guid == null)
        {
            RPCContext::getInstance ()->setSession ( UserDef::SESSION_KEY_UID, $uid );
        }
        else if ($uid != $guid)
        {
            Logger::fatal ( 'modifyUserByOther error, uid:%d, guid:%d', $uid, $guid );
            return;
        }
        $userOlympicObj = UserOlympic::getInstance($uid);
        $integralAdd = 0;
        if(isset($modifyInfo[UserOlympicDef::FIELD_INTEGRAL]))
        {
            $integralAdd = $modifyInfo[UserOlympicDef::FIELD_INTEGRAL];
        }
        $integralRecord = 0;
        foreach($arrIntegralRecord as $type => $integral)
        {
            $integralRecord += $integral;
            $userOlympicObj->addIntegral($type, $integral);
        }
        if($integralAdd != $integralRecord)
        {
            throw new FakeException('modifyinfo %s integralrecord %s.integral not equal.',
                    $modifyInfo,$arrIntegralRecord);
        }
        
        foreach($modifyInfo as $key => $value)
        {
            if($key == UserOlympicDef::FIELD_CHEER_VALID_NUM)
            {
                $userOlympicObj->addCheerValidNum($value);
            }
            else if($key == UserOlympicDef::FIELD_WIN_ACCUMNUM)
            {
                $userOlympicObj->addWinNum($value);
            }
            else if($key == UserOlympicDef::FIELD_BE_CHEER_NUM)
            {
                $userOlympicObj->addBeCheerNum($value);
            }
            else
            {
                Logger::debug('inval key:%d', $key);
            }
        }
        $userOlympicObj->update();        
    }
    
    public function enterOlympic()
    {
        RPCContext::getInstance()->setSession(SPECIAL_ARENA_ID::SESSION_KEY, 
                SPECIAL_ARENA_ID::OLYMPIC);
    }
    
    public function leave()
    {
        RPCContext::getInstance()->unsetSession(SPECIAL_ARENA_ID::SESSION_KEY);
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */