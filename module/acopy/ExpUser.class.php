<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: ExpUser.class.php 197890 2015-09-10 09:47:03Z TiantianZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/acopy/ExpUser.class.php $
 * @author $Author: TiantianZhang $(zhengguohao@babeltime.com)
 * @date $Date: 2015-09-10 09:47:03 +0000 (Thu, 10 Sep 2015) $
 * @version $Revision: 197890 $
 * @brief 
 *  
 **/
class ExpUser extends ActBaseObj
{
	public static function getPassReward($baseId)
	{
		$reward = array();
		
		$dropId = btstore_get()->EXPUSER[$baseId][EXP_USER_FIELD::DROP_ID];
		
		$dropInfo = array();
		
		$drop = Drop::dropMixed($dropId);
		$items = $drop[0];
		
		$itemInst = ItemManager::getInstance();
		foreach ($items as $itemTplId => $itemNum)
		{
			$dropInfo = array_merge($dropInfo, $itemInst->addItem($itemTplId, $itemNum));
		}
		
		if (empty($dropInfo[DropDef::DROP_TYPE_ITEM]))
		{
			return array();
		}
		
		$reward['item'] = $dropInfo;
		
		return $reward;
	}
	
	public static function doneExpUser($atkRet, $baseId)
	{
        $actObj = MyACopy::getInstance()->getActivityCopyObj(ACT_COPY_TYPE::EXPUSER_COPYID);
        
        Logger::trace('ExpUser doneBattle atkRet %s.',$atkRet);
        $newBaseId = 0;
        if($atkRet['pass'])
        {
            if( $actObj->subCanDefeatNum() == FALSE)
            {
                throw new FakeException('not enough defeatnum.now is %d',$actObj->getCanDefeatNum());
            }
            
            $actObj->updateBaseId($baseId);
            
            EnActive::addTask(ActiveDef::ACOPY);
            EnWeal::addKaPoints(KaDef::ACOPY);
            $uid = RPCContext::getInstance()->getUid();
            EnMission::doMission($uid, MissionType::ACOPY);
            $expUserConf = btstore_get()->EXPUSER;
            $level = EnUser::getUserObj()->getLevel();
            $copyInfo = $actObj->getCopyInfo();
            $maxHasPass = $copyInfo[NORMAL_COPY_FIELD::VA_COPY_INFO][ACT_COPY_FIELD::VA_EXP_USER_BASE_ID];
            
           	$newBaseId = $maxHasPass;
            if (isset($expUserConf[$maxHasPass+1]) && $level >= $expUserConf[$maxHasPass+1][EXP_USER_FIELD::BASE_LEVEL])
	        {
	        	$newBaseId = $maxHasPass + 1;
	        }
            
        }
        MyACopy::getInstance()->save();
       	
        return $newBaseId;
	}
	
	public function updateBaseId($baseId)
	{
		if (empty($this->copyInfo[NORMAL_COPY_FIELD::VA_COPY_INFO][ACT_COPY_FIELD::VA_EXP_USER_BASE_ID]))
		{
			$this->copyInfo[NORMAL_COPY_FIELD::VA_COPY_INFO][ACT_COPY_FIELD::VA_EXP_USER_BASE_ID] = $baseId;
		}
		else if ($baseId > $this->copyInfo[NORMAL_COPY_FIELD::VA_COPY_INFO][ACT_COPY_FIELD::VA_EXP_USER_BASE_ID])
		{
			$this->copyInfo[NORMAL_COPY_FIELD::VA_COPY_INFO][ACT_COPY_FIELD::VA_EXP_USER_BASE_ID] = $baseId;
		}
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */