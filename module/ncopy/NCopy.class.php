<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: NCopy.class.php 155785 2015-01-28 12:50:57Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/ncopy/NCopy.class.php $
 * @author $Author: BaoguoMeng $(zhangtiantian@babeltime.com)
 * @date $Date: 2015-01-28 12:50:57 +0000 (Wed, 28 Jan 2015) $
 * @version $Revision: 155785 $
 * @brief 
 *  
 **/
class NCopy implements INCopy
{
    private $uid = NULL;
    public function __construct()
    {
        $this->uid = RPCContext::getInstance()->getUid();
    }

	public function getCopyList($simple=FALSE)
	{
	    $simple = intval($simple);
		Logger::info('NCopy.getCopyList start.param simple %s',$simple);
		$copyList = NCopyLogic::getCopyList();
		$sweepInfo = NCopyLogic::getSweepInfo($this->uid);
		if($simple)
		{
		    $copyList = self::simplifyCopyList($copyList);
		}
		else
		{
		    $copyList = NCopyLogic::resetCopyInfo($copyList);
		}
		Logger::debug('NCopy.getCopylist end.Result:%s.',$copyList);
		return array(
		        'copy_list'=>$copyList,
		        USER_COPY_FIELD::SWEEP_CD=>$sweepInfo[USER_COPY_FIELD::SWEEP_CD],
		        USER_COPY_FIELD::CLEAR_SWEEP_NUM => $sweepInfo[USER_COPY_FIELD::CLEAR_SWEEP_NUM]
		        );
	}
	
	public static function simplifyCopyList($arrCopyInfo)
	{
	    $arrSimpleCopy = array();
	    foreach($arrCopyInfo as $copyId => $copyInfo)
	    {
	        Logger::trace('simplifyCopyList pre is %s',$copyInfo);
	        unset($copyInfo[NORMAL_COPY_FIELD::UID]);
	        unset($copyInfo[NORMAL_COPY_FIELD::COPYID]);
	        unset($copyInfo[NORMAL_COPY_FIELD::REFRESH_ATKNUM_TIME]);
	        $maxPrizedNum = NCopyLogic::getMaxPrizedNum($copyId);
	        if(empty($maxPrizedNum)
	                || $copyInfo[NORMAL_COPY_FIELD::PRIZEDNUM] >= $maxPrizedNum)
	        {
	            unset($copyInfo[NORMAL_COPY_FIELD::PRIZEDNUM]);
	        }
	        $maxCopyScore = NCopyLogic::getMaxCopyScore($copyId);
	        Logger::trace('simplifyCopyList maxscore is %d copyscore is %d',$maxCopyScore,$copyInfo[NORMAL_COPY_FIELD::SCORE]);
	        if($copyInfo[NORMAL_COPY_FIELD::SCORE] >= $maxCopyScore)
	        {
	            unset($copyInfo[NORMAL_COPY_FIELD::SCORE]);
	        }
	        if(NCopyLogic::isCopyPassed($copyId, $copyInfo, BaseLevel::HARD))
	        {
	            unset($copyInfo[NORMAL_COPY_FIELD::VA_COPY_INFO][NORMAL_COPY_FIELD::VA_PROGRESS]);
	        }
	        if(empty($copyInfo[NORMAL_COPY_FIELD::VA_COPY_INFO][NORMAL_COPY_FIELD::VA_DEFEAT_NUM]))
	        {
	            unset($copyInfo[NORMAL_COPY_FIELD::VA_COPY_INFO][NORMAL_COPY_FIELD::VA_DEFEAT_NUM]);
	        }
	        if(empty($copyInfo[NORMAL_COPY_FIELD::VA_COPY_INFO][NORMAL_COPY_FIELD::VA_RESET_NUM]))
	        {
	            unset($copyInfo[NORMAL_COPY_FIELD::VA_COPY_INFO][NORMAL_COPY_FIELD::VA_RESET_NUM]);
	        }
	        if(empty($copyInfo[NORMAL_COPY_FIELD::VA_COPY_INFO]))
	        {
	            unset($copyInfo[NORMAL_COPY_FIELD::VA_COPY_INFO]);
	        }
	        $arrSimpleCopy[$copyId] = $copyInfo;
	        Logger::trace('simplifyCopyList pre is %s',$copyInfo);
	    }
	    return $arrSimpleCopy;
	}

	
	public function enterBaseLevel($copy_id,$base_id,$level)
	{
		Logger::debug('NCopy.enterBaseLevel start.The params copyid:%d,base_id:%d,level:%d.',$copy_id,$base_id,$level);
		$copy_id = intval($copy_id);
		$base_id = intval($base_id);
		$level = intval($level);
		if(empty($copy_id) || empty($base_id) || ($level < 0))
		{
			throw new FakeException('invalid params.copy_id:%s,base_id:%s,level:%s.',$copy_id,$base_id,$level);
		}
		$ret = NCopyLogic::enterBaseLevel($copy_id, $base_id, $level);
		Logger::debug('NCopy.enterBaseLevel end.Result:%s.',$ret);
		return $ret;
	}
	
	
	
	public function doBattle($copy_id,$base_id,$level,$army_id,$fmt=array(),$herolist=array())
	{
		Logger::debug('NCopy.doBattle start.The params copyid:%d,baseid:%d,level:%d,armyid:%d,$fmt:%s,heroList:%s',
					$copy_id,$base_id,$level,$army_id,$fmt,$herolist);
		$copy_id = intval($copy_id);
		$base_id = intval($base_id);
		$level = intval($level);
		$army_id = intval($army_id);
		if(empty($copy_id) || empty($base_id) || ($level < 0) || empty($army_id))
		{
			throw new FakeException('invalid params.copyid:%s,baseid:%s,level:%s,armyid:%s,heroList:%s',
					$copy_id,$base_id,$level,$army_id,$herolist);
		}
		if(!is_array($fmt)  || (!is_array($herolist)))
		{
		    throw new FakeException('error params.fmt %s.herolist %s.',$fmt,$herolist);
		}
		$ret = NCopyLogic::doBattle($copy_id, $base_id, $level, $army_id ,$fmt,$herolist);
		Logger::debug('NCopy.doBattle end.Result:%s.',$ret);
		return $ret;
	}
	
	
	public function reFight($copy_id,$base_id,$level)
	{
		Logger::debug('NCopy.reFight start.The params copyid:%d,baseid:%d,level:%d.',$copy_id,$base_id,$level);
		$copy_id = intval($copy_id);
		$base_id = intval($base_id);
		$level = intval($level);
		if(empty($copy_id) || empty($base_id) || ($level < 0))
		{
			throw new FakeException('invalid param.copyid:%s,baseid:%s,level:%s.',
					$copy_id,$base_id,$level);
		}
		$ret = CopyUtil::reFight($copy_id, $base_id, $level);
		Logger::debug('NCopy.reFight end.Result:',$ret);
		return $ret;
	}
	
	
	public function leaveBaseLevel($copy_id,$base_id,$level)
	{		
		Logger::debug('NCopy.leaveBaseLevel start.The params copyid:%d,baseid:%d,level:%d.',$copy_id,$base_id,$level);
		$copy_id = intval($copy_id);
		$base_id = intval($base_id);
		$level = intval($level);
		if(empty($copy_id) || empty($base_id) || ($level < 0))
		{
			throw new FakeException('invalid params copyid:%s,baseid:%s,level:%s.',$copy_id,$base_id,$level);
		}
		CopyUtil::leaveBaseLevel($copy_id, $base_id, $level);	
		Logger::debug('NCopy.leaveBaseLevel end.Result:ok.');	
		return 'ok';
	}
	
	public function leaveNCopy($copyId)
	{
		Logger::debug('NCopy.leaveNCopyModule start.');
		$copyId = intval($copyId);
		if(empty($copyId))
		{
		    throw new FakeException('error param.copyid:%s.',$copyId);
		}
		$copyIdInSession = RPCContext::getInstance()->getSession(CopySessionName::COPYID);
		if($copyIdInSession != $copyId)
		{
			Logger::warning('now user is not in copy %s.',$copyId);
		}
		AtkInfo::getInstance()->delAtkInfo();
		RPCContext::getInstance()->unsetSession(CopySessionName::COPYID);
		Logger::debug('NCopy.leaveNCopyModule end.');
	}
	
	public function getPrize($copy_id,$caseID)
	{
		Logger::debug('NCopy.getPrize start.The params copyid:%s,caseID:%s.',$copy_id,$caseID);
		$copy_id = intval($copy_id);
		$caseId = intval($caseID);
		if(empty($copy_id) ||($caseID < 0))
		{
			throw new FakeException('invalid params copyid:%s,caseID:%s.',$copy_id,$caseID);
		}
		$reward = NCopyLogic::getPrize($copy_id, $caseID);
		Logger::debug('NCopy.getPrize end.Result:%s.',$reward);
		return 'ok';
	}
	
	
	public function getBaseDefeatInfo($base_id,$level)
	{
		Logger::debug('NCopy.getBaseDefeatInfo start.The params baseid:%d,level:%d.',$base_id,$level);
		$base_id = intval($base_id);
		$level = intval($level);
		if(empty($base_id) || ($level < 0))
		{
			throw new FakeException('invalid params baseid:%s,level:%s.',$base_id,$level);
		}
		$baseDefeat = array();
		$baseDefeat['replay'] = CopyUtil::getReplayList($base_id, $level);
		$baseDefeat['rank'] = CopyUtil::getPreBaseAttackPlayer($base_id, $level);
		Logger::debug('NCopy.getBaseDefeatInfo end.Result:%s.',$baseDefeat);
		return $baseDefeat;
	}
	
	
	public function getCopyRank($copy_id)
	{
		Logger::debug('NCopy.getCopyRank start.The params copyid:%d.',$copy_id);
		$copy_id = intval($copy_id);
		if(empty($copy_id))
		{
			throw new FakeException('invalid params copyid:%s.',$copy_id);
		}
		$copyRank = CopyUtil::getPreCopyPassPlayer($copy_id);
		Logger::debug('NCopy.getCopyRank end.Result:%s.',$copyRank);
		return $copyRank;
	}
	
	
	public function reviveCard($base_id,$base_level,$card_id)
	{		
		Logger::debug('NCopy.reviveCard start.The params baseid:%s,level:%s,cardid:%s.',$base_id,$base_level,$card_id);
		$base_id = intval($base_id);
		$base_level = intval($base_level);
		$card_id = intval($card_id);
		if(empty($base_id) || ($base_level < 0) || empty($card_id))
		{
			throw new FakeException('invalid params baseid:%s,level:%s,cardid:%s.',$base_id,$base_level,$card_id);
		}
		$ret = CopyUtil::reviveCard($base_id, $base_level, $card_id);
		Logger::debug('NCopy.reviveCard end.Result:%s.',$ret);
		return $ret;
	}
	public function getAtkInfoOnEnterGame()
	{
		Logger::debug('NCopy.getAtkInfoOnEnterGame start.');
		$ret	=	NCopyLogic::getAtkInfoOnEnterGame();
		Logger::debug('NCopy.getAtkInfoOnEnterGame end.Result:%s.',$ret);
		return $ret;
	}
	public function sweep($copyId,$baseId,$baseLv,$num)
	{
	    Logger::debug('NCopy.sweep start.params copyid %d.baseId %d,baselv %d,num %d',$copyId,$baseId,$baseLv,$num);
	    $copyId = intval($copyId);
	    $baseId = intval($baseId);
	    $baseLv = intval($baseLv);
	    $num = intval($num);
	    if(empty($copyId) || empty($baseId) || empty($baseLv) || empty($num))
	    {
	        throw new FakeException('error param.params copyid %d.baseId %d,baselv %d,num %d',$copyId,$baseId,$baseLv,$num);
	    }
	    if($num > CopyConf::$MAX_SWEEP_NUM)
	    {
	        throw new FakeException('can not sweep so many times %s.',$num);
	    }
	    $ret = NCopyLogic::sweep($copyId, $baseId, $baseLv, $num);
	    Logger::debug('NCopy.sweep end.%s',$ret);
	    return $ret;
	}
	
	public function clearSweepCd()
	{
	    Logger::trace('ncopy.clearSweepcd start');
	    if(NCopyLogic::isDuringSweepCD($this->uid) == FALSE)
	    {
	        throw new FakeException('now is not in sweep cd.can not clear sweepcd.');
	    }
	    $clearNum = NCopyLogic::getClearSweepNum($this->uid);
	    $userObj = EnUser::getUserObj($this->uid);
	    $config = btstore_get()->NORMAL_CONFIG[NormalConfigDef::CONFIG_ID_CLEAR_SWEEPCD];
	    $needGold = $config['init_gold'] + $clearNum * $config['inc_gold'];
	    if($needGold > $config['max_gold'])
	    {
	        $needGold = $config['max_gold'];
	    }
	    if($userObj->subGold($needGold, StatisticsDef::ST_FUNCKEY_COPY_CLEAR_SWEEPCD) == FALSE)
	    {
	        throw new FakeException('sub gold failed.');
	    }
	    NCopyLogic::clearSweepCd($this->uid);
	    $userObj->update();
	    NCopyLogic::saveUserCopy();
	    return 'ok';
	}
	
	public function resetAtkNum($baseId,$spendType=CopyDef::RESET_BASE_SPEND_TYPE_GOLD)
	{
	    Logger::trace('resetAtkNum start.params:baseid %d.',$baseId);
	    $baseId = intval($baseId);
	    if(empty($baseId))
	    {
	        throw new FakeException('error param;baseid %s.',$baseId);
	    }
	    $ret = NCopyLogic::resetAtkNum($baseId,$spendType);
	    Logger::trace('resetAtkNum end.ret %s.',$ret);
	    return $ret;	    
	}
	/**
	 * 根据副本得分获取副本排行榜
	 * @return array
	 */
	public function getUserRankByCopy($rankNum)
	{
	    if($rankNum > CData::MAX_FETCH_SIZE)
	    {
	        throw new FakeException('can not get so many rank.ranknum is %d',$rankNum);
	    }
	    $guid = RPCContext::getInstance()->getUid();
	    $copyRank = NCopyDAO::getCopyRank($rankNum);
	    $arrUid = array();
	    $guserRank = array();
	    foreach($copyRank as $index => $userCopy)
	    {
	        $uid = $userCopy['uid'];
	        $arrUid[] = $uid;
	        if($uid == $guid)
	        {
	            $guserRank['rank'] = $index+1;
	            $guserRank['score'] = $userCopy['score'];
	        }
	    }
	    $arrUser = EnUser::getArrUserBasicInfo($arrUid,array('uname','htid','dress','level','vip','fight_force'));
	    foreach($copyRank as $index => $userCopy)
	    {
	        $uid = $userCopy['uid'];
	        $rank = $index+1;
	        $copyRank[$index]['rank'] = $rank;
	        $copyRank[$index]['uname'] = $arrUser[$uid]['uname'];
	        $copyRank[$index]['level'] = $arrUser[$uid]['level'];
	        $copyRank[$index]['htid'] = $arrUser[$uid]['htid'];
	        $copyRank[$index]['dress'] = $arrUser[$uid]['dress'];
	        $copyRank[$index]['vip'] = $arrUser[$uid]['vip'];
	        $copyRank[$index]['fight_force'] = $arrUser[$uid]['fight_force'];
	    }
	    if(empty($guserRank))
	    {
	        $userCopy = NCopyLogic::getUserCopy($guid);
	        $guserRank['score'] = $userCopy['score'];
	        $guserRank['rank'] = NCopyDAO::getCopyRankOfUser($guid, $userCopy['score'], $userCopy['last_score_time']);;
	    }
	    return array(
	            'rank_list'=>$copyRank,
	            'user_rank'=>$guserRank,
	            );
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
