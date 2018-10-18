<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: EnFlop.class.php 208518 2015-11-10 09:51:50Z ShijieHan $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/reward/flop/EnFlop.class.php $
 * @author $Author: ShijieHan $(tianming@babeltime.com)
 * @date $Date: 2015-11-10 09:51:50 +0000 (Tue, 10 Nov 2015) $
 * @version $Revision: 208518 $
 * @brief 
 *  
 **/
class EnFlop
{
	/**
	 * 翻牌
	 * 
	 * @param int $uid 用户id
	 * @param int $robUid 被掠夺用户id，为0是NPC
	 * @param int $flopId 翻牌id
	 * @param bool $ifUpdateFrag 是否立刻更新宝物碎片
	 * @throws FakeException
	 * @return array  1真2假，翻牌结果包含: 掠夺, 银币, 金币, 将魂, 物品, 武将, 宝物碎片
	 * <code>
	 * {
	 * 		'client':
	 * 		{
	 * 			'real':						7种之一
	 * 			{	
	 * 				'rob' => $num			掠夺，在抽中掠夺时表示银币数量，没抽中掠夺时数量为0
	 * 				'silver' => $num		银币，数量
	 * 				'gold' => $num			金币，数量
	 * 				'soul' => $num			将魂，数量
	 * 				'item':					物品
	 * 				{
	 * 					'id':int			物品id
	 * 					'num:int			数量
	 * 				}
	 * 				'hero':					武将
	 * 				{
	 * 					'id':int			武将id
	 * 					'num:int			数量
	 * 				}
	 * 				'treasFrag':			宝物碎片
	 * 				{
	 * 					'id':int			物品id
	 * 					'num:int			数量
	 * 				}
	 * 			} 				
	 * 			'show1':					同上
	 * 			'show2':					同上
	 * 		}
	 * 		'server':$robLose				掠夺方被扣银币
	 * }
	 * </code>
	 * @see 没有更新user和bag, 如果有相应掉落则需要调用者更新user和bag
	 */
	public static function flop($uid, $robUid, $flopId, $ifUpdateFrag=true)
	{
		Logger::trace('EnFlop::flop Start.');
		
		//参数检查
		if(empty($flopId))
		{
			throw new FakeException('Err para, flopId:%d!', $flopId);
		}
		if (!isset(btstore_get()->FLOP[$flopId])) 
		{
			throw new FakeException('flop template id:%d is not exist!', $flopId);
		}
		$conf = btstore_get()->FLOP[$flopId]->toArray();
		
		//step1， 不放回抽样，随机出3个掉落
		$keys = Util::noBackSample($conf[FlopDef::FLOP_DROP_ARRAY], 3);
		
		//step2, 取出这三个掉落, 不放回抽样，再随机出一个掉落
		$dropAry = array();
		foreach ($keys as $key)
		{
			$dropAry[$key] = $conf[FlopDef::FLOP_DROP_ARRAY][$key];
		}
		$keys = Util::noBackSample($dropAry, 1);
		
		//当达到一定次数时，就使用特殊掉落表
		$user = EnUser::getUserObj($uid);
		$user->addFlopNum(1);
		$dropNum = $user->getFlopNum();
		$dropSpecial = $conf[FlopDef::FLOP_DROP_SPECIAL];
		if (isset($dropSpecial[$dropNum])) 
		{
			$dropAry[$keys[0]] = array(
					'type' => 2,
					'dropId' => $dropSpecial[$dropNum],
			);
		}
		Logger::trace('flop drop ary is:%s, real drop is %d', $dropAry, $keys[0]);
		
		//step3, 将3个掉落进行掉落, 并给用户发奖
		$robLose = 0;
		$dropRet = array();
		foreach ($dropAry as $key => $drop)
		{
			//掠夺类型
			if ($drop['type'] == 1) 
			{
				$dropRet[$key] = array('rob' => 0);
				if ($key == $keys[0]) 
				{
					$min = 1 - $conf[FlopDef::FLOP_RAND_NUM] / UNIT_BASE;
					$max = 1 + $conf[FlopDef::FLOP_RAND_NUM] / UNIT_BASE;
					$randRate = rand($min * UNIT_BASE, $max * UNIT_BASE);
					//给用户发银币
					$userLevel = $user->getLevel();
					$robGain = intval(max($conf[FlopDef::FLOP_ROB_MIN], $userLevel) * $conf[FlopDef::FLOP_ROB_SUC] * $randRate / UNIT_BASE);
					$user->addSilver($robGain);
					$dropRet[$key]['rob'] = $robGain;
					Logger::trace('rob gain:%d, rand rate:%d', $robGain, $randRate);
					//被掠夺者非NPC
					if ($robUid != 0) 
					{
						$robUser = EnUser::getUserObj($robUid);
						$robUserLevel = $robUser->getLevel();
						$robUserSilver = $robUser->getSilver();
						$robLose = min(intval(max($conf[FlopDef::FLOP_ROB_MIN], $robUserLevel) * $conf[FlopDef::FLOP_ROB_FAIL] * $randRate / UNIT_BASE), $robUserSilver);
						Logger::trace('rob lose:%d', $robLose);
						RPCContext::getInstance()->executeTask($robUid, 'flop.robUserByOther', array($robUid, $robLose), false);
					}
				}
			}
			//金币类型
			elseif ($drop['type'] == 3)
			{
				$dropRet[$key] = array('gold' => $conf[FlopDef::FLOP_DROP_GOLD]);
				if ($key == $keys[0])
				{
					$user->addGold($conf[FlopDef::FLOP_DROP_GOLD], StatisticsDef::ST_FUNCKEY_FLOP_GOLD);
				}
			}
			//掉落类型
			else 
			{
				$ret = array();
				if ($key == $keys[0])
				{
					$ret = EnUser::drop($uid, array($drop['dropId']), false, true, false, true, array(), $ifUpdateFrag);
				}
				else 
				{
					$dropGot = Drop::dropMixed($drop['dropId']);
					foreach($dropGot as $type => $value)
					{
						if(!isset(DropDef::$DROP_TYPE_TO_STRTYPE[$type]))
						{
							throw new FakeException('no such type drop');
						}
						$keyStr = DropDef::$DROP_TYPE_TO_STRTYPE[$type];
						$ret[$keyStr] = $value;
						if (DropDef::DROP_TYPE_STR_SILVER == $keyStr
						|| DropDef::DROP_TYPE_STR_SOUL == $keyStr) 
						{
							$ret[$keyStr] = $value[0];
						}
					}
				}
				if (count($ret) > 1) 
				{
					throw new ConfigException('drop id:%d does not drop only one thing', $drop['dropId']);
				}
				$dropType = key($ret);
				if (empty($ret[$dropType])) 
				{
					throw new ConfigException('drop id:%d does drop nothing', $drop['dropId']);
				}
				switch ($dropType)
				{
					case DropDef::DROP_TYPE_STR_ITEM:
					case DropDef::DROP_TYPE_STR_HERO:
					case DropDef::DROP_TYPE_STR_TREASFRAG:
						$dropRet[$key][$dropType] = array('id' => key($ret[$dropType]), 'num' => current($ret[$dropType]));
						break;
					case DropDef::DROP_TYPE_STR_SILVER:
					case DropDef::DROP_TYPE_STR_SOUL:
						$dropRet[$key][$dropType] = $ret[$dropType];
						break;
				}
			}
		}
		
		//将掉落信息映射到返回值
		$flopInfo = array(
				'real' => $dropRet[$keys[0]],
		);
		unset($dropRet[$keys[0]]);
		$flopInfo['show1'] = current($dropRet);
		$flopInfo['show2'] = next($dropRet);
		
		Logger::trace('EnFlop::flop End.');
		return array(
				'client' => $flopInfo,
				'server' => $robLose,
		);
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */