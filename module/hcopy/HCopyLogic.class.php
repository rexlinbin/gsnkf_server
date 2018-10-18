<?php
/***************************************************************************
 *
 * Copyright (c) 2014 babeltime.com, Inc. All Rights Reserved
 * $Id: HCopyLogic.class.php 110771 2014-05-24 12:10:50Z QiangHuang $
 *
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/hcopy/HCopyLogic.class.php $
 * @author $Author: QiangHuang $(huangqiang@babeltime.com)
 * @date $Date: 2014-05-24 12:10:50 +0000 (Sat, 24 May 2014) $
 * @version $Revision: 110771 $
 * @brief
 *
 **/

class HCopyLogic
{
	
	public static function getAllCopyInfos($uid) 
	{
		$infos =  HCopyDao::getAllFinishInfos($uid);
		$ret = array();
		foreach ($infos as $_ => $info) 
		{
			$copyId = $info[HCopyDef::FILED_COPYID];
			$level = $info[HCopyDef::FILED_LEVEL];
			$finish_num = $info[HCopyDef::FILED_FINISH_NUM];
			if(!isset(btstore_get()->HEROCOPY[$copyId])) {
				Logger::warning("user:$uid, unknown hcopyid:$copyId");
				continue;
			}
			$conf = btstore_get()->HEROCOPY[$copyId];
			$htid = $conf[HCopyDef::REQUIRE_HTID];
			if(!isset($ret[$htid]))
				$ret[$htid] = array();
			if(!isset($ret[$htid][$copyId]))
				$ret[$htid][$copyId] = array();
			$ret[$htid][$copyId][$level] = $finish_num;
		}
		return array( 'err' => 'ok', 'infos' => $ret);
	}

	public static function reviveCard($uid, $base_id,$base_level,$card_id)
	{
		Logger::debug('HCopy.reviveCard start.The params baseid:%d,level:%d,cardid:%d.',$base_id,$base_level,$card_id);
		if(empty($base_id) || ($base_level < 0) || empty($card_id))
		{
			throw new FakeException('invalid params baseid:%d,level:%d,cardid:%d.',$base_id,$base_level,$card_id);
		}
		$ret = CopyUtil::reviveCard($base_id, $base_level, $card_id);
		Logger::debug('HCopy.reviveCard end.Result:%s.',$ret);
		return $ret;
	}

	public static function doneBattle($atkRet)
	{
		$uid = $atkRet['uid1'];
		$pass = $atkRet['pass'];
		$copyId = AtkInfo::getInstance()->getCopyId();
		$baseId = AtkInfo::getInstance()->getBaseId();
		$baseLv = AtkInfo::getInstance()->getBaseLv();
		$newCopyorBase	= array();
		$newCopyorBase['pass'] = $pass;
		$man = HCopy::getManager($uid, $copyId, $baseLv);
		if($pass)
		{
			Logger::trace("HCopy uid:%d copyid:%d baseId:%d baseLv:%d pass",
				$uid, $copyId, $baseId, $baseLv);
			
			// 如果没完成过此据点此级别, 则更新此据点的完成级别
			// 同时开启下一个据点。并且返回最新的副本信息。
			if(!$man->isPassBase($baseId))
			{
				Logger::trace("HCopy doneBattle pass new base. uid:%d copyid:%d baseid:%d baseLv:%d",
					$uid, $copyId, $baseId, $baseLv);
				$man->updateBase($baseId, $baseLv + 2);
				if(!$man->isLastBase($baseId))
				{
					$man->enableNextBase($baseId);
					Logger::trace("HCopy doneBattle. not last base. uid:%d copyid:%d baseid:%d baseLv:%d",
						$uid, $copyId, $baseId, $baseLv);
				}
				else
				{
					$man->passCopy();
					Logger::trace("HCopy doneBattle. pass copy. uid:%d copyid:%d baseid:%d baseLv:%d",
						$uid, $copyId, $baseId, $baseLv);
				}
			}
		}
		else
		{
			Logger::trace("HCopy uid:%d copyid:%d baseId:%d baseLv:%d not pass",
				$uid, $copyId, $baseId, $baseLv);
		}
		$newCopyorBase["hero_copy"] = $man->getCopyInfo();

		$man->save();
		AtkInfo::getInstance()->saveAtkInfo();
		EnUser::getUserObj()->update();
		BagManager::getInstance()->getBag()->update();
		MyStar::getInstance($uid)->update();

		Logger::trace('after attack. uid:%d copyid:%d baseid:%s baselv:%s.newcopyorbase %s.',
		 	$uid, $copyId, $baseId, $baseLv, $newCopyorBase);
		return $newCopyorBase;
	}

}

