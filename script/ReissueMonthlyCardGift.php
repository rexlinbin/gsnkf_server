<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: ReissueMonthlyCardGift.php 121915 2014-07-21 12:38:29Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/script/ReissueMonthlyCardGift.php $
 * @author $Author: MingTian $(zhangtiantian@babeltime.com)
 * @date $Date: 2014-07-21 12:38:29 +0000 (Mon, 21 Jul 2014) $
 * @version $Revision: 121915 $
 * @brief 
 *  
 **/
class ReissueMonthlyCardGift extends BaseScript
{
	/* (non-PHPdoc)
     * @see BaseScript::executeScript()
     */
    protected function executeScript ($arrOption)
    {
        // TODO Auto-generated method stub
        if(isset($arrOption[1]) && ($arrOption[1] == 'byname'))
        {
            $userInfo = UserDao::getUserByUname($arrOption[0], array('uid','uname','level'));
            echo "USERINFO OF USER $arrOption[0] IS\n";
            var_dump($userInfo);
            $uid = $userInfo['uid'];
            if(empty($uid))
            {
                return;
            }
        }
        elseif (isset($arrOption[1]) && ($arrOption[1] == 'bypid'))
        {
        	$userInfo = UserDao::getArrUserByArrPid(array($arrOption[0]), array('uid','uname','level'));
        	echo "USERINFO OF USER $arrOption[0] IS\n";
        	var_dump($userInfo[0]);
        	$uid = $userInfo[0]['uid'];
        	if(empty($uid))
        	{
        		return;
        	}
        }
        else
        {
            $uid = intval($arrOption[0]);
        }
        RPCContext::getInstance()->setSession(UserDef::SESSION_KEY_UID, $uid);
        $userObj = EnUser::getUserObj($uid);
        if(empty($userObj))
        {
            echo "NO SUCH USER $uid\n";
            return;
        }
        echo "USERINFO IS:\n";
        $userInfo = array(
                'uid'=>$userObj->getUid(),
                'uname' => $userObj->getUname(),
                'level' => $userObj->getLevel(),
                );
        var_dump($userInfo);
        $cardId = DiscountCardDef::MONTHLYCATD_ID;
        $cardInst = MonthlyCardObj::getInstance($uid, $cardId);
        $cardInfo = $cardInst->getCardInfo();
        if(empty($cardInfo))
        {
            echo "USER $uid NOT BUY ANY CARD\n";
            return;
        }
        if($cardInst->getGiftStatus() == MONTHCARD_GIFTSTATUS::HASGIFT)
        {
            echo "USER $uid HAS GIFT\n";
            return;
        }
        else if($cardInst->getGiftStatus() == MONTHCARD_GIFTSTATUS::GOTGIFT)
        {
            echo "USER $uid HAS GOT GIFT\n";
            return;
        }
        $buyTime = $cardInst->getBuyTime();
        $date = date('Y-m-d h:i:s',$buyTime);
        echo "BUY CARD TIME IS $date\n";
        var_dump($date);
        echo "USER HAS NO GIFT.CAN REISSUE GIFT\n";
        $cardInst->setGiftStatus(MONTHCARD_GIFTSTATUS::HASGIFT);
        if(isset($arrOption[1]) && ($arrOption[1] == 'reissue') ||
                (isset($arrOption[2]) && ($arrOption[2] == 'reissue')))
        {
            $cardInst->save();
            echo "DONE\n";
        }
    }

    
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */