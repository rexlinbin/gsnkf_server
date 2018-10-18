<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: NCopyObj.class.php 99585 2014-04-12 08:49:37Z TiantianZhang $
 * 
 **************************************************************************/

/**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/ncopy/NCopyObj.class.php $
 * @author $Author: TiantianZhang $(zhangtiantian@babeltime.com)
 * @date $Date: 2014-04-12 08:49:37 +0000 (Sat, 12 Apr 2014) $
 * @version $Revision: 99585 $
 * @brief 
 * 
 **/
class NCopyObj 
{
	
	private $copy_info = array ();
	
	function __construct($uid, $copy_id, $copy_info = null) 
	{
		if (! empty ( $copy_info )) 
		{
			$this->copy_info = $copy_info;
		} 
		else
		 {
			$copy_info = NCopyDao::getCopy ( $uid, $copy_id );
			if (! empty ( $copy_info )) 
			{
				$this->copy_info = $copy_info;
			}
			else 
			{
			    throw new InterException('copyinfo is null.');
			}
		}
	}
	/**
	 * 更新副本中的据点状态
	 * @param array $progress
	 */
	public function updProgress($progress) 
	{
		$modify = false;
		foreach ( $progress as $base_id => $base_status ) 
		{
			if ($this->updBaseStatus ( $base_id, $base_status ) == true)
			 {
				$modify = true;
			}
		}
		return $modify;
	}
	/**
	 * 更新据点的状态
	 * @param int $base_status
	 */
	public function updBaseStatus($base_id, $base_status) 
	{
		$modify = false;
		if (! isset ( $this->copy_info ['va_copy_info'] ['progress'] [$base_id] )) 
		{
			$this->copy_info ['va_copy_info'] ['progress'] [$base_id] = $base_status;
			$modify = true;
		} 
		else if ($this->copy_info ['va_copy_info'] ['progress'] [$base_id] < $base_status) 
		{
			$this->copy_info ['va_copy_info'] ['progress'] [$base_id] = $base_status;
			$modify = true;
		}
		return $modify;
	}
	/**
	 * 添加副本的得分
	 * @param int $score
	 */
	public function addScore($score) 
	{
		$this->copy_info ['score'] += $score;
		return $this->copy_info ['score'];
	}
	
	public function setScore($score)
	{
		if($score>0)
		{
			$this->copy_info ['score'] = $score;
		}
		return $this->copy_info ['score'];
	}
	/**
	 * 获取副本的总得分
	 */
	public function getScore() 
	{
		return $this->copy_info ['score'];
	}
	
	public function getCopyId()
	{
	    return $this->copy_info ['copy_id'];
	}
	/**
	 * 获取据点的攻击状态
	 * @param int $base_id
	 * @return -1表示据点没有开启
	 */
	public function getStatusofBase($base_id)
	 {
		$ret = - 1;
		$progress = $this->copy_info ['va_copy_info'] ['progress'];
		if (isset ( $progress [$base_id] )) 
		{
			$ret = $progress [$base_id];
		}
		return $ret;
	}
	/**
	 * 领取宝箱的奖励
	 * @param int $caseID
	 */
	public function getPrize($caseID) {
		//更改副本领取奖励数
		$this->copy_info ['prized_num'] += CopyConf::$CASE_INDEX [$caseID];
	}
	
	public function canGetPrize($caseID) 
	{
		if(!isset(btstore_get ()->COPY [$this->copy_info ['copy_id']] ['star_arrays'] [$caseID]))
		{
			return 'nocase';
		}
		if ($this->isCaseOpen ( $caseID ) === false)
		{
			return 'notopen';
		}
		if ($this->isGetCasePrize ( $caseID ) == TRUE)
		{
			return 'beengot';
		}
		return 'ok';
	}
	/**
	 * 查看某个箱子的奖励是否领取了
	 * @param int $caseID
	 */
	private function isGetCasePrize($caseID) 
	{
		if (($this->copy_info ['prized_num'] & (CopyConf::$CASE_INDEX [$caseID])) != 0) 
		{
			return TRUE;
		}
		return FALSE;
	}
	/**
	 * 查看某个箱子的奖励是否开启
	 * @param int $caseID
	 */
	public function isCaseOpen($caseID) 
	{
		$score = $this->copy_info ['score'];
		$needScore = intval(btstore_get ()->COPY [$this->copy_info ['copy_id']] ['star_arrays'] [$caseID]);
		if ($score >= $needScore) 
		{
			return TRUE;
		}
		return FALSE;
	}
	/**
	 * 查看副本是否已经通关
	 * 所有据点通关了简单难度
	 */
	public function isCopyPassed() 
	{
		//获取副本中的最后一个据点
		$base_num = btstore_get ()->COPY [$this->copy_info ['copy_id']] ['base_num'];
		$last_base = btstore_get ()->COPY [$this->copy_info ['copy_id']] ['base'] [$base_num - 1];
		$progress = $this->copy_info ['va_copy_info'] ['progress'];
		if (isset ( $progress [$last_base] ) && ($progress [$last_base] >= BaseStatus::SIMPLEPASS)) 
		{
			return true;
		}
		return false;
	}
	public function isLastBase($base_id)
	{
		//获取副本中的最后一个据点
		$base_num = btstore_get ()->COPY [$this->copy_info ['copy_id']] ['base_num'];
		$last_base = btstore_get ()->COPY [$this->copy_info ['copy_id']] ['base'] [$base_num - 1];
		if($last_base == $base_id)
		{
			return true;
		}
		return false;
	}

	public function getCopyInfo() 
	{
		return $this->copy_info;
	}
	
	public function firstPassBaseLevel($base_id, $base_level) 
	{
		if (($base_level + 1) >= $this->copy_info ['va_copy_info'] ['progress'] [$base_id]) 
		{
			return true;
		}
		return false;
	}
	public function canEnterBaseLevel($base_id, $base_level) 
	{
		$ret = 'ok';
		$progress = $this->copy_info ['va_copy_info'] ['progress'];
		if (!isset ( $progress [$base_id] )) 
		{
			return 'nothisbase';
		}		
		//如果据点没有npc部队，而据点的当前状态是canattack 将据点的状态改成npcpass
		if (! isset ( btstore_get ()->BASE [$base_id] ['npc'] ) &&
				($progress [$base_id] == BaseStatus::CANATTACK))
		{
		    $this->updBaseStatus($base_id, BaseStatus::NPCPASS);
		}
		$baseStatus = $this->getStatusofBase($base_id);
		if($baseStatus >= BaseStatus::NPCPASS &&
				($base_level == BaseLevel::NPC))
		{
			return 'canreenternpc';
		}
		if ($baseStatus <= $base_level) 
		{
			 $ret = 'notdefeatprelevel status:'.$baseStatus;
		}
		if($this->hasDefeatNum($base_id) == FALSE)
		{
		    $ret = 'nodefeatnum';
		}
		return $ret;
	}
	/**
	 * 判断此据点的此难度级别是否可以攻击
	 * @param int $base_id
	 * @param int $level
	 */
	public function canAttack($baseId, $level) 
	{
		$progress = $this->copy_info ['va_copy_info'] ['progress'];
		if (! isset ( $progress[$baseId] )) 
		{
			throw new FakeException('this base is not open or' . ' this base is not in this copy with copyid %s,baseid %s.', $this->copy_info ['copy_id'], $baseId );
		}
		if ($progress[$baseId] == BaseStatus::CANATTACK) 
		{
			if (! isset ( btstore_get ()->BASE[$baseId] ['npc'] )) 
			{
				$progress[$baseId] = BaseStatus::NPCPASS;
				$this->copy_info ['va_copy_info'] ['progress'] = $progress;
			}
		}
		if($progress[$baseId] >= BaseStatus::NPCPASS && ($level == BaseLevel::NPC))
		{
		    throw new FakeException('copyid %s baseid %s NPC level has passed or no NPC.can not attack it.',$this->getCopyId(),$baseId);
		}
		if ($progress[$baseId] <= $level) 
		{
			throw new FakeException('can not enter this level of the base,the level is %s,' . 'the baseid is %s,the status is %s.', $level, $baseId,$progress[$baseId]);
		} 
		if($this->hasDefeatNum($baseId) == FALSE)
		{
		    throw new FakeException('copy %s base %s has no defeat num.',$this->getCopyId(),$baseId);
		}
		return 'ok';	
	}
	public function addPassedBase($base_id, $base_level) 
	{
		if (isset ( $this->copy_info ['va_copy_info'] ['progress'] [$base_id] ) 
				&& ($this->copy_info ['va_copy_info'] ['progress'] [$base_id] < $base_level + 2) 
				|| ((! isset ( $this->copy_info ['va_copy_info'] ['progress'] [$base_id] )))) 
		{
			$this->copy_info ['va_copy_info'] ['progress'] [$base_id] = $base_level + 2;
		}
		return $this->copy_info ['va_copy_info'] ['progress'] [$base_id];
	}
	
	public function subCanDefeatNum($baseId,$num=1)
	{
	    $canDefeatNum = $this->getCanDefeatNum($baseId);
	    if($canDefeatNum < $num)
	    {
	        throw new FakeException('copyid %s baseid %s has defeatnum %s is less than %s.',$this->getCopyId(),$baseId,$canDefeatNum,$num);
	    }
	    $this->copy_info[NORMAL_COPY_FIELD::VA_COPY_INFO][NORMAL_COPY_FIELD::VA_DEFEAT_NUM][$baseId] -= $num;
	}
	
	public function getCanDefeatNum($baseId)
	{
	    $this->refreshDefeatNum();
	    if(!isset($this->copy_info[NORMAL_COPY_FIELD::VA_COPY_INFO][NORMAL_COPY_FIELD::VA_DEFEAT_NUM][$baseId]))
	    {
	        $this->copy_info[NORMAL_COPY_FIELD::VA_COPY_INFO][NORMAL_COPY_FIELD::VA_DEFEAT_NUM][$baseId] =
	         intval(btstore_get()->BASE[$baseId][NORMAL_COPY_FIELD::BTSTORE_FIELD_FREE_ATK_NUM]);
	    }
	    return $this->copy_info[NORMAL_COPY_FIELD::VA_COPY_INFO][NORMAL_COPY_FIELD::VA_DEFEAT_NUM][$baseId];
	}
	
	public function getResetTimes($baseId)
	{
	    $this->refreshDefeatNum();
	    if(!isset($this->copy_info[NORMAL_COPY_FIELD::VA_COPY_INFO][NORMAL_COPY_FIELD::VA_RESET_NUM][$baseId]))
	    {
	        $this->copy_info[NORMAL_COPY_FIELD::VA_COPY_INFO][NORMAL_COPY_FIELD::VA_RESET_NUM][$baseId] = 0;
	    }
	    return $this->copy_info[NORMAL_COPY_FIELD::VA_COPY_INFO][NORMAL_COPY_FIELD::VA_RESET_NUM][$baseId];
	}
	
	public function resetBaseAtkNum($baseId)
	{
	    $this->copy_info[NORMAL_COPY_FIELD::VA_COPY_INFO][NORMAL_COPY_FIELD::VA_DEFEAT_NUM][$baseId] = 
	    intval(btstore_get()->BASE[$baseId][NORMAL_COPY_FIELD::BTSTORE_FIELD_FREE_ATK_NUM]);
	}
	
	public function addResetNum($baseId)
	{
	    $this->copy_info[NORMAL_COPY_FIELD::VA_COPY_INFO][NORMAL_COPY_FIELD::VA_RESET_NUM][$baseId] =
	    $this->getResetTimes($baseId) + 1;
	}
	
	public function hasDefeatNum($baseId)
	{
	    $canDftNum = $this->getCanDefeatNum($baseId);
	    if($canDftNum >= 1)
	    {
	        return TRUE;
	    }
	    return FALSE;
	}
	
	private function refreshDefeatNum()
	{
	    $refreshTime = $this->copy_info[NORMAL_COPY_FIELD::REFRESH_ATKNUM_TIME];
	    if(!Util::isSameDay($refreshTime))
	    {
	        $this->copy_info[NORMAL_COPY_FIELD::VA_COPY_INFO][NORMAL_COPY_FIELD::VA_DEFEAT_NUM] = array();
	        $this->copy_info[NORMAL_COPY_FIELD::VA_COPY_INFO][NORMAL_COPY_FIELD::VA_RESET_NUM] = array();
	        $this->copy_info[NORMAL_COPY_FIELD::REFRESH_ATKNUM_TIME] = Util::getTime();
	    }
	}
	
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */