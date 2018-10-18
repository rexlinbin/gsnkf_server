<?php
/***************************************************************************
 *
 * Copyright (c) 2014 babeltime.com, Inc. All Rights Reserved
 * $Id: HCopyManager.class.php 134277 2014-09-24 06:53:20Z TiantianZhang $
 *
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/hcopy/HCopyManager.class.php $
 * @author $Author: TiantianZhang $(huangqiang@babeltime.com)
 * @date $Date: 2014-09-24 06:53:20 +0000 (Wed, 24 Sep 2014) $
 * @version $Revision: 134277 $
 * @brief
 *
 **/

class HCopyManager
{
	private $uid;
	private $copyId;
	private $level;
	private $require_htid;
	private $max_finish_num;
	private $cur_copy;
	private $origin_cur_copy;
	
	private $conf;
	public function __construct($uid, $copyId, $level)
	{
		if(!isset(btstore_get()->HEROCOPY[$copyId]))
			throw new FakeException("copyid:$copyId is not hero copy");

		$this->conf = btstore_get()->HEROCOPY[$copyId];
		$this->require_htid = $this->conf[HCopyDef::REQUIRE_HTID];
		$this->max_finish_num = $this->conf[HCopyDef::MAX_FINISH_NUM];
		
		Logger::debug("HCopy copyInfo. conf:%s", $this->conf);
		
		$this->uid = $uid;
		$this->copyId = $copyId;
		$this->level = $level;
		$sdata = RPCContext::getInstance()->getSession(HCopyDef::KEY_PROCESS_COPYS);
		if(isset($sdata)
		 && $sdata[HCopyDef::FILED_UID] == $uid
		 && $sdata[HCopyDef::FILED_COPYID] == $copyId
		 && $sdata[HCopyDef::FILED_LEVEL] == $level)
			$this->cur_copy = $this->origin_cur_copy = $sdata;
		else 
		{
			$infos = HCopyDao::get($uid, $copyId, $level);
			list($minPassLevel, $copyInfo) = $this->statPassLevels($infos, $level);
// 			if($minPassLevel < $level - 1)
// 				throw new FakeException("should pass lower level:%d", $level - 1);
			if(isset($copyInfo))
			{
				if($copyInfo[HCopyDef::FILED_COPYID] != $copyId)
					throw new FakeException("err. copyId unmatch. require:$copyId, but:%d", $copyInfo[HCopyDef::FILED_COPYID]);
				$this->cur_copy = $this->origin_cur_copy = 
					array(HCopyDef::FILED_UID => $uid)
					+ $copyInfo;
			}
			else 
				$this->cur_copy = $this->origin_cur_copy = $this->newCopy(0);
		}
	}
	
	public function statPassLevels($infos, $level) 
	{
		/*
		 * 代码有点奇怪，解释一下.
		 * 因为策划要求完成完成低等级的列传副本后才能完成高等级的副本,
		 * 策划同时也保证,对同一武将即使配了不同的副本id,这些副本的等级肯定
		 * 互不相同,
		 * 所以找出这个武将下所有已能关的等级小于$level的副本数,
		 * 如果数目 >= $level - 1 ($level取值1,2,3), 说明低等级的副本全部已
		 * 通关.
		 */
		$passLevelNum = 0;
		$copyInfo = null;
		$conf = btstore_get()->HEROCOPY;
		foreach($infos as $_ => $info) {
			$copyId = $info[HCopyDef::FILED_COPYID];
			if($conf[$copyId][HCopyDef::REQUIRE_HTID] != $this->require_htid) continue;
			$iLevel = $info[HCopyDef::FILED_LEVEL];
			if($iLevel < $level) {
				++$passLevelNum;
			} else if($iLevel == $level) {
				$copyInfo = $info;
			}
		}
		return array($passLevelNum, $copyInfo);
	}

	public function getUid()
	{
		return $this->uid;
	}

	public function getBattleType()
	{
		return BattleType::HCOPY;
	}

	public function save()
	{
        Logger::debug("hcopy save. uid:%d", $this->getUid());
		if($this->origin_cur_copy !== $this->cur_copy && $this->uid == RPCContext::getInstance()->getUid())
		{
            Logger::trace("save cur_copy");
			RPCContext::getInstance()->setSession(HCopyDef::KEY_PROCESS_COPYS, $this->cur_copy);
			$this->origin_cur_copy = $this->cur_copy;
			HCopyDao::put($this->cur_copy);
		}
	}

	public function  getCopyFinishNum()
	{
		return $this->cur_copy[HCopyDef::FILED_FINISH_NUM];
	}

	public function  setCopyFinishNum($num)
	{
		$this->cur_copy[HCopyDef::FILED_FINISH_NUM] = $num;
	}

	public function  newCopy($finish_num)
	{
		Logger::debug("uid:%d newCopy:%d level:%d", $this->uid, $this->copyId, $this->level);
		return array(
				HCopyDef::FILED_UID => $this->uid,
				HCopyDef::FILED_COPYID => $this->copyId,
				HCopyDef::FILED_LEVEL => $this->level,
				HCopyDef::FILED_FINISH_NUM => $finish_num,
				HCopyDef::FILED_COPY_INFO => array(
					HCopyDef::VAR_PROGRESS => array($this->conf[HCopyDef::ARR_BASEID][0] => BaseStatus::CANATTACK),
				),
		);

	}

	/**
	 * 取副本信息
	 */
	public function getCopyInfo()
	{
		return $this->cur_copy;
	}

	public function resetCopyInfo()
	{
		$this->cur_copy = $this->newCopy($this->getCopyFinishNum());
	}

	public function enterBaseLevel($baseId)
	{
		if(!$this->isTemplateHeroInFormation())
			return "formation";
		if(!$this->isExecuteEnough($baseId))
			return "execution";
		if($this->isMaxPassCopy())
			return "maxpassnum";
		$this->checkAttack($baseId);

		AtkInfo::getInstance()->initAtkInfo($this->copyId, $baseId, $this->level);
		AtkInfo::getInstance()->saveAtkInfo();
        return 'ok';
	}

	public function doBattle($baseId, $armyId, $fmt, $herolist)
	{
		$this->checkAttack($baseId);
		$this->checkAttack2($baseId, $armyId);
		return BaseDefeat::doBattle($this->getBattleType(), $armyId, $baseId, $fmt, $this->level, ($this->level == BaseLevel::NPC), $herolist);
	}

	public function leaveBaseLevel($baseId)
	{
		AtkInfo::getInstance()->delAtkInfo();
		RPCContext::getInstance()->unsetSession(CopySessionName::COPYID);
        return 'ok';
	}

	/**
	 * 是否是副本的最后一个据点
	 */
	public function isLastBase($baseId)
	{
        $arrBaseid = $this->conf[HCopyDef::ARR_BASEID]->toArray();
        Logger::debug("copyid:$this->copyId, baseid:$baseId, arrBaseid:%s", $arrBaseid);
		return $baseId == end($arrBaseid);
	}

	/**
	 *  检查是否完成据点相应难度的任务
	 */
	public function isPassBase($baseId)
	{
		return $this->cur_copy[HCopyDef::FILED_COPY_INFO][HCopyDef::VAR_PROGRESS][$baseId] >= $this->level + 2;
	}

	/**
	 * 更新据点状态
	 */
	public function updateBase($baseId, $status)
	{
		if(!isset($this->cur_copy[HCopyDef::FILED_COPY_INFO][HCopyDef::VAR_PROGRESS][$baseId]) 
			|| $this->cur_copy[HCopyDef::FILED_COPY_INFO][HCopyDef::VAR_PROGRESS][$baseId] < $status)
			$this->cur_copy[HCopyDef::FILED_COPY_INFO][HCopyDef::VAR_PROGRESS][$baseId] = $status;
	}

	/**
	 *  开启下一个据点
	 */
	public function enableNextBase($baseId)
	{
		$arrBaseid = btstore_get()->HEROCOPY[$this->copyId][HCopyDef::ARR_BASEID]->toArray();
		$index = array_search($baseId, $arrBaseid);
		if($index === false)
			throw new FakeException("HCopy enableNextBase. uid:%d copyid:%d baseid:%d not in copy!",
				$this->uid, $this->copyId, $baseId);
		++$index;
		$newBaseId = $arrBaseid[$index];
		$this->updateBase($newBaseId, BaseStatus::CANATTACK);
	}

	/**
	 *  通关了某副本
	 */
	public function passCopy()
	{
		$newPassNum = $this->getCopyFinishNum() + 1;
		$this->setCopyFinishNum($newPassNum);
		$this->resetCopyInfo();
		Logger::trace("HCopy passCopy. uid:%d copyid:%d htid:%d finish_num:%d",
			$this->uid, $this->copyId, $this->require_htid, $newPassNum);
	}


	/**
	 *  检查副本合法性。能否进入此副本，据点与相应等级是否存在。
	 */
	public function checkBase($baseId)
	{
		Logger::debug("HCopy checkBase. uid:%d copyId:%d baseId:%d baseLv:%d",
		 	$this->uid, $this->copyId, $baseId, $this->level);

		if(!in_array($baseId, $this->conf[HCopyDef::ARR_BASEID]->toArray()))
			throw new FakeException("baseId:$baseId is not in copyid:$this->copyId");
		$lvName = CopyConf::$BASE_LEVEL_INDEX[$this->level];
		if(!isset(btstore_get()->BASE[$baseId][$lvName]))
			throw new FakeException("baseLev:$this->level is not in copyid:$this->copyId, baseId:$baseId");
	}

	public function isTemplateHeroInFormation()
	{
		return EnFormation::isBaseHtidInFormation($this->require_htid, $this->uid);
	}

	public function isMaxPassCopy()
	{
		return $this->getCopyFinishNum() >= $this->max_finish_num;
	}

	public function isExecuteEnough($baseId)
	{
		$baseConf = btstore_get()->BASE[$baseId];
		$lvName = CopyConf::$BASE_LEVEL_INDEX[$this->level];
		if(!isset($baseConf[$lvName]))
			throw new FakeException("uid:%d copyid:%d baseid:%d, level:%d not find",
					$this->uid, $this->copyId, $baseId, $this->level);

		$need_power = intval($baseConf[$lvName][$lvName.'_need_power']);
		$user = EnUser::getUserObj();
		return $need_power <= $user->getCurExecution();
	}

	/**
	 * 检查能否攻打此据点。是否attackable, 体力是否足够，所需英雄是否在阵型内
	 */
	public function checkAttack($baseId)
	{
		Logger::debug("HCopy checkAttack. copyid:%d baseid:%d baseLv:%d",
		$this->copyId, $baseId, $this->level);
		$conf = $this->conf;

		if(!isset($this->cur_copy[HCopyDef::FILED_COPY_INFO][HCopyDef::VAR_PROGRESS][$baseId])
		 	|| $this->cur_copy[HCopyDef::FILED_COPY_INFO][HCopyDef::VAR_PROGRESS][$baseId] < BaseStatus::CANATTACK)
			throw new FakeException("uid:%d copyid:%d baseid:%d cann't attack",
				$this->uid, $this->copyId, $baseId);

		$baseConf = btstore_get()->BASE[$baseId];
		$lvName = CopyConf::$BASE_LEVEL_INDEX[$this->level];
		if(!isset($baseConf[$lvName]))
			throw new FakeException("uid:%d copyid:%d baseid:%d, level:%d not find",
				$this->uid, $this->copyId, $baseId, $this->level);
		if(!CopyUtil::checkFightCdTime())
			throw new FakeException('can not fight,fightcd %s now %s.not cool down.',
				EnUser::getUserObj()->getFightCdTime(), Util::getTime());
	}

	/**
	 *  检查敌军是否合法及战斗冷却。
	 */
	public function checkAttack2($baseId, $armyId)
	{
		if(CopyUtil::isArmyinBase($baseId, $this->level, $armyId) == FALSE)
		{
			throw new FakeException('this army %s is not in base with baseid %s baseLevel %s.',$armyId,$baseId,$this->level);
		}
		if(CopyUtil::checkDefeatPreArmy($armyId) == false)
		{
			throw new FakeException('can not atk army %d in baseid %d baselevel %d',$armyId,$baseId,$this->level);
		}

		$atkInfo = AtkInfo::getInstance()->getAtkInfo();
		if(empty($atkInfo) || ($atkInfo[ATK_INFO_FIELDS::COPYID]!=$this->copyId) || ($atkInfo[ATK_INFO_FIELDS::BASEID]!=$baseId) || ($atkInfo[ATK_INFO_FIELDS::BASELEVEL]!=$this->level))
		{
			throw new FakeException('no corresponding attackinfo in session.');
		}
	}
	
}

