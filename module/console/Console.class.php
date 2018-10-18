<?php
/**********************************************************************************************************************
 *
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Console.class.php 259816 2016-08-31 11:18:52Z YangJin $
 *
 **********************************************************************************************************************/

/**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/console/Console.class.php $
 * @author $Author: YangJin $(hoping@babeltime.com)
 * @date $Date: 2016-08-31 11:18:52 +0000 (Wed, 31 Aug 2016) $
 * @version $Revision: 259816 $
 * @brief
 *
 **/

class Console
{
	public function execute($arg)
	{
		if (! FrameworkConfig::DEBUG)
		{
			Logger::fatal ( "non debug mode found console command" );
			throw new Exception ( 'close' );
		}

		$arg = trim ( $arg );
		$arg = preg_replace('/\s\s+/', ' ', $arg);
		$arrArg = explode ( ' ', $arg );
		$command = $arrArg [0];
		array_shift ( $arrArg );
		Logger::trace("console execute %s, args:%s\n",$command,$arrArg);
		return call_user_func_array ( array ($this, $command ), $arrArg );
	}

	public function show()
	{
		$err = 'do you mean: show me the money?';
		$args = func_get_args();
		if (count($args)<3)
		{
			return $err;
		}

		if ($args[0]=='me' && $args[1]=='the' && 0!=preg_match('/^money/', $args[2]))
		{
			$user = EnUser::getUserObj();
			$user->addSilver(100*100*1000);
			$user->addGold(100*100*1000);
			$user->addExecution(100);
			$user->addStamina(100);
			$user->addSoul(1000*1000);
			$user->update();
			return 'ok';
		}
		return $err;

	}

	public function user()
	{
		$args = func_get_args();
		if (count($args) < 2)
		{
			return 'err argv.';
		}

		$uid = $args[0];
		$fun = $args[1];
		$funArgs = $args;
		array_shift($funArgs);
		array_shift($funArgs);

		$user = EnUser::getUserObj($uid);
		$ret = call_user_func_array(array($user, $fun), $funArgs);
		if (!$ret)
		{
			return $fun . ' return:' . $ret;
		}
		$user->update();
		return $ret;
	}

	public function pid()
	{
		return EnUser::getUserObj()->getPid();
	}

	public function uid()
	{
		return RPCContext::getInstance()->getUid();
	}

	public function silver($num)
	{
		$num = intval ( $num );
		$user = EnUser::getUserObj ();
		$cur = $user->getSilver();
		$user->addSilver( $num - $cur );
		$user->update ();
		return $user->getSilver();
	}

	public function clearTalent()
	{
	    $heroMng = EnUser::getUserObj()->getHeroManager();
	    $allHeroObj = $heroMng->getAllHeroObj();
	    foreach($allHeroObj as $hid => $heroObj)
	    {
	        $heroObj->setTalentInfo(HeroLogic::getInitTalentInfo());
	    }
	    EnUser::getUserObj()->update();
	}

	public function addTalent($htid,$index,$id)
	{
	    $id = intval($id);
	    $index = intval($index);
	    $heroMng = EnUser::getUserObj()->getHeroManager();
	    $allHeroObj = $heroMng->getAllHeroObj();
	    foreach($allHeroObj as $hid => $heroObj)
	    {
	        if($heroObj->getHtid() != $htid)
	        {
	            continue;
	        }
	        $talentInfo = $heroObj->getTalentInfo();
	        $talentInfo[HeroDef::VA_SUBFIELD_TALENT_CONFIRMED][$index] = $id;
	        $heroObj->setTalentInfo($talentInfo);
	    }
	    EnUser::getUserObj()->update();
	    Enuser::getUserObj()->modifyBattleData();
	}

	public function jewel($num)
	{
	    $num = intval ( $num );
	    $user = EnUser::getUserObj ();
	    $cur = $user->getJewel();
	    $user->addJewel( $num - $cur );
	    $user->update ();
	    return $user->getJewel();
	}

	public function gold($num)
	{
		$num = intval ( $num );
		$user = EnUser::getUserObj ();
		$cur = $user->getGold ();
		if( $num > $cur )
		{
			$user->addGold ( $num - $cur, 0 );
		}
		else
		{
			$user->subGold ( $cur - $num, 0 );
		}
		$user->update ();
		return $user->getGold ();
	}
	public function wm($num)
	{
		$num = intval ( $num );
		$user = EnUser::getUserObj ();
		$cur = $user->getWmNum();
		if( $num > $cur )
		{
			$user->addWmNum( $num - $cur);
		}
		else
		{
			$user->subWmNum( $cur - $num );
		}
		$user->update ();
		return $user->getWmNum();
	}
	public function jh($num)
	{
		$num = intval ( $num );
		$user = EnUser::getUserObj ();
		$cur = $user->getJH();
		if( $num > $cur )
		{
			$user->addJH( $num - $cur);
		}
		else
		{
			$user->subJH( $cur - $num );
		}
		$user->update ();
		return $user->getJH();
	}
	public function tg($num)
	{
		$num = intval ( $num );
		$user = EnUser::getUserObj ();
		$cur = $user->getTgNum();
		if( $num > $cur )
		{
			$user->addTgNum( $num - $cur);
		}
		else
		{
			$user->subTgNum( $cur - $num );
		}
		$user->update ();
		return $user->getTgNum();
	}
	public function vip($num)
	{
		$num = intval($num);
		$user = EnUser::getUserObj();
		$user->setVip($num);
		$user->update();
		return $user->getVip();
	}

	public function level($num)
	{
		if ($num > UserConf::MAX_LEVEL)
		{
			return "超过最大等级";
		}

		$num = intval ( $num );
		if ($num<=0)
		{
			return 'level must be >= 0';
		}
		$userObj = EnUser::getUserObj();

		if ($num <= $userObj->getLevel())
		{
			return 'fail';
		}

		$expTable = btstore_get()->EXP_TBL[UserConf::EXP_TABLE_ID];

		if(!isset($expTable[$num]))
		{
			return 'invalid level:'.$num;
		}

		$cur = $userObj->getAllExp ();
		$userObj->addExp( $expTable[$num] - $cur );
		$userObj->update ();
		$userObj->modifyBattleData();

		return 'ok';
	}

	public function experience($num)
	{
		$num = intval ( $num );
		$user = EnUser::getUserObj ();
		$cur = $user->getAllExp();
		$user->addExp ( $num - $cur );
		$user->update ();
		return $user->getExp ();
	}

	public function addExp($num)
	{
	    $user = EnUser::getUserObj ();
	    $user->addExp($num);
	    $user->update ();
	    return $user->getExp ();
	}

	/**
	 * execution clear 重置购买行动力限制
	 * execution view 查看后端行动力
	 * execution N  设置行动力为num
	 */
	public function execution($op)
	{
		$user = EnUser::getUserObj ();
		$uid = RPCContext::getInstance ()->getUid ();
		$arrField = array ();
		switch ($op)
		{
			case 'clear' :
				$arrField = array ('buy_execution_time' => 0 );
				break;
			case 'view':
				$cur = EnUser::getUserObj()->getCurExecution();
				return $cur;
				break;
			default :
				$arrField = array ('execution' => $op, 'execution_time'=>Util::getTime() );
				break;
		}
		UserDao::updateUser ( $uid, $arrField );
		return 'ok';
	}

	public function soul($num)
	{
	    $user = EnUser::getUserObj ();
	    $oldSoul = $user->getSoul();
	    $user->addSoul($num-$oldSoul);
	    $user->update();
	    return $user->getSoul();
	}

	public function prestige($num)
	{
	    $user = EnUser::getUserObj ();
	    $oldNum = $user->getPrestige();
	    $user->addPrestige($num-$oldNum);
	    $user->update();
	    return $user->getPrestige();
	}

	public function book($num)
	{
		$user = EnUser::getUserObj ();
		$oldNum = $user->getBookNum();
		$user->addBookNum($num-$oldNum);
		$user->update();
		return $user->getBookNum();
	}

	public function setExecution($execution)
	{
	    $uid = RPCContext::getInstance ()->getUid ();
	    $exectuion     =    intval($execution);
	    if($execution<0)
	    {
            return;
	    }
	    $arrField = array(
	            'execution' => $execution,
	            'execution_time'=>Util::getTime()
	            );
	    UserDao::updateUser ( $uid, $arrField );
	    return 'ok';
	}

	public function setStamina($stamina)
	{
	    $uid = RPCContext::getInstance ()->getUid ();
	    $stamina = intval($stamina);
	    if($stamina<0)
	    {
	        return;
	    }
	    $arrField = array(
	            'stamina' => $stamina,
	            'stamina_time'=>Util::getTime()
	    );
	    UserDao::updateUser ( $uid, $arrField );
	    return 'ok';
	}
	/**
	 * 设置耐力恢复时间为当前时间-seconds
	 * 那么seconds之后恢复耐力
	 * @param unknown_type $seconds
	 */
	public function setStaminaTime($seconds)
	{
	    $uid = RPCContext::getInstance ()->getUid ();
	    $stamina = 2;
	    $arrField = array(
	            'stamina' => $stamina,
	            'stamina_time'=>Util::getTime()-$seconds
	    );
	    UserDao::updateUser ( $uid, $arrField );
	    return 'ok';
	}

	public function fightForce()
	{
		$user = EnUser::getUserObj();
		$fightForce = $user->getFightForce();
		return $fightForce;
	}

	public function maxFightForce()
	{
		$user = EnUser::getUserObj();
		$maxFightForce = $user->getMaxFightForce();
		return $maxFightForce;
	}

	public function resetVaHero()
	{
	    $uid    =    RPCContext::getInstance()->getUid();
	    $attr    =    array('va_hero'=>array('unused'=>array()));
	    UserDao::updateUser($uid, $attr);
	}

	public function addGoldOrder($addGold,$date=0, $uid = 0)
	{
		$addGold = intval($addGold);

		$orderId = 'AAAA_00_' . strftime("%Y%m%d%H%M%S") . rand(10000, 99999);

		if ( empty( $uid ) )
		{
		    $uid = RPCContext::getInstance()->getUid();

		    $user = new User();
		    $user->addGold4BBpay($uid, $orderId, $addGold);
		    if(!empty($date))
		    {
		        $orderData = User4BBpayDao::getByOrderId($orderId, array('mtime','order_id'));
		        $date = intval( $date );
		        $dateDetail = strval( $date * 1000000 );
		        $timeStamp = strtotime( $dateDetail );
		        if($timeStamp < Util::getTime() && (Util::isSameDay($timeStamp) == FALSE))
		        {
		            $orderData['mtime'] = $timeStamp;
		            $data = new CData();
		            $data->update(User4BBpayDao::tblBBpay)
		            ->set($orderData)
		            ->where(array('order_id','LIKE',$orderId))
		            ->query();
		        }
		    }
		    $orderData = User4BBpayDao::getByOrderId($orderId, array('mtime','order_id'));
		    return $orderData;
		}
		else
		{
		    $uid = intval($uid);
		    RPCContext::getInstance()->executeTask($uid, 'user.addGold4BBpay', array($uid, $orderId, $addGold));
		    return 'ok';
		}

	}

	public function clearGoldOrder()
	{
		$uid = RPCContext::getInstance()->getUid();
		$arrItemOrder = User4BBpayDao::getArrItemOrderAllType($uid,
				array('order_id','gold_num'));
		$arrGoldOrder = User4BBpayDao::getArrOrderAllType($uid,
				array('order_id','gold_num'));
		$data = new CData();
		foreach($arrItemOrder as $orderInfo)
		{
			if($orderInfo['gold_num'] == 0)
			{
				continue;
			}
			$orderId = $orderInfo['order_id'];
			$data->update(User4BBpayDao::tblBBpayItem)
			->set(array('gold_num'=>0))
			->where(array('order_id','==',$orderId))
			->query();
		}
		foreach($arrGoldOrder as $orderInfo)
		{
			if($orderInfo['gold_num'] == 0)
			{
				continue;
			}
			$orderId = $orderInfo['order_id'];
			$data->update(User4BBpayDao::tblBBpay)
			->set(array('gold_num'=>0))
			->where(array('order_id','==',$orderId))
			->query();
		}
		$this->vip(0);
	}

	public function addVipExp($addGold)
	{
		$addGold = intval($addGold);
		if ($addGold <= 0)
		{
			return 'should > 0';
		}

		$uid = RPCContext::getInstance()->getUid();
		$user = new User();
		$user->addVipExp($uid, $addGold);

		return 'ok';
	}

	public function setOffset($offset)
    {
        if( is_numeric($offset ) )
        {
            $offset = intval($offset);
        }
        else
        {
            $offset = strtotime('2015-01-01 '.$offset) -  strtotime('2015-01-01 00:00:00');
        }
        $ret = array();
        $path = CONF_ROOT."/Framework.cfg.php";
        exec("/bin/sed -i '/const DAY_OFFSET_SECOND/{s/[0-9-]\+/$offset/;}'  $path", $ret );

        exec("/bin/grep 'const DAY_OFFSET_SECOND' $path | awk '{print \$NF}' |grep -Eo '[0-9]+'", $ret );

        $offset = intval($ret[0]);
        $dayBreak = strtotime( date('Y-m-d'));

        $timeStr = date('Y-m-d H:i:s', $dayBreak + $offset);
        $arr = explode(' ', $timeStr);
        return $arr[1];
    }

	public function setBossOffset($offset)
	{
		$offset = intval($offset);
		$ret = array();
		$path = CONF_ROOT."/gsc/game110001/Game.cfg.php";
		exec("/bin/sed -i '/const BOSS_OFFSET/{s/[0-9-]\+/$offset/;}'  $path", $ret );

		exec("/bin/grep 'const BOSS_OFFSET' $path | awk '{print \$NF}' ", $ret );

		return var_export($ret,true);
	}

	/**
	 * **********背包************************
	 */

	/**
	 * 获取背包信息
	 *
	 * @return array mixed
	 */
	public function bagInfo()
	{
		$bag = BagManager::getInstance ()->getBag ();
		$bagInfo = $bag->bagInfo();
		return $bagInfo;
	}

	/**
	 * 背包格子信息
	 *
	 * @param int $gid
	 * @return array(itemInfo)
	 */
	public function gridInfo($gid)
	{
		$bag = BagManager::getInstance ()->getBag ();
		return $bag->gridInfo($gid);
	}

	/**
	 *
	 * 清空背包
	 *
	 * @return boolean				TRUE表示移除成功, FALSE表示移除失败
	 */
	public function clearBag()
	{

		$bag = BagManager::getInstance ()->getBag ();
		if ($bag->clearBag () == FALSE)
		{
			return FALSE;
		}
		else
		{
			$bag->update ();
			return TRUE;
		}
	}

	/**
	 * 花金币开背包格子
	 *
	 * @param int $gridNum			格子数量
	 * @param int $bagType			背包类型，1装备，2道具
	 */
	public function openBag($gridNum, $bagType)
	{
		$bag = BagManager::getInstance()->getBag();
		$bag->openGridByGold($gridNum, $bagType);
		return 'ok';
	}

	/**
	 * 增加物品
	 *
	 * @param int $itemTplId		物品模板id
	 * @param int $itemNum			物品数量
	 *
	 * @return array
	 * <code>
	 * {
	 * 		$itemId
	 * }
	 * </code>
	 */
	public function addItem($itemTplId, $itemNum = 1)
	{
		if(!(ItemAttr::getItemAttr($itemTplId, ItemDef::ITEM_ATTR_NAME_STACKABLE)!= ItemDef::ITEM_CAN_NOT_STACKABLE) && ($itemNum > 500))//物品不可叠加
		{
			throw new InterException('add item too much.max num is 500,now itemNum %d.',$itemNum);
		}
		$itemTplId = intval($itemTplId);
		$itemNum = intval($itemNum);
		$bag = BagManager::getInstance()->getBag();
		$arrItemId = ItemManager::getInstance()->addItem($itemTplId, $itemNum);

		if ($bag->addItems($arrItemId) == FALSE)
		{
			return 'full';
		}
		else
		{
			$bag->update();
			return $arrItemId;
		}
	}

	/**
	 * 掉落物品
	 *
	 * @param int $dropId
	 * @param int $number
	 *
	 * @return array
	 * <code>
	 * {
	 * 		'add_success':boolean
	 * 		'bag_modify':array			背包修改
	 * 		[
	 * 			gid:itemInfo
	 * 		]
	 * }
	 * </code>
	 */
	public function dropItem($dropId, $number = 1)
	{
		$dropId = intval ( $dropId );
		$number = intval ( $number );
		if ($number <= 0)
		{
			return 'para err';
		}
		$arrDropTplId = array($dropId => $number);
		$bag = BagManager::getInstance ()->getBag ();
		if ($bag->dropItems($arrDropTplId) == false)
		{
			return 'bag full';
		}
		$bag->update ();
		return 'ok';
	}

	public function setTreasLv($level)
	{
		$uid = RPCContext::getInstance()->getUid();
		$bag = BagManager::getInstance()->getBag($uid);
		$bagInfo = $bag->bagInfo();
		$treasBag = $bagInfo[BagDef::BAG_TREAS];
		foreach ($treasBag as $gid => $itemInfo)
		{
			$itemId = $itemInfo[ItemDef::ITEM_SQL_ITEM_ID];
			$itemTplId = $itemInfo[ItemDef::ITEM_SQL_ITEM_TEMPLATE_ID];
			$item = ItemManager::getInstance()->getItem($itemId);
			if ($item->isNoAttr())
			{
				continue;
			}
			$levelLimit = $item->getLimitLevel();
			if ($level > $levelLimit)
			{
				return 'beyond max level';
			}
			$upgrade = ItemAttr::getItemAttr($itemTplId, TreasureDef::ITEM_ATTR_NAME_TREASURE_VALUE_UPGRADE);
			$exp = $upgrade[$level];
			$item->setExp($exp);
			$item->setLevel($level);
		}

		$bag->update();
		return 'ok';
	}

	/**
	 * **********普通副本************************
	 */

	public function getNCopyList()
	{
		$ncopy	=	new NCopy();
		$copyList	=	$ncopy->getCopyList();
		return $copyList;
	}

	public function getNCopyInfo($copyId)
	{
		$ncopy		=	MyNCopy::getInstance();
		$copyInfo	=	$ncopy->getCopyInfo($copyId);
		$ncopy->getCopyObj($copyId);
		return $copyInfo;
	}

	public function passNBaseLevel($baseId,$baseLevel)
	{
	    $baseId    =    intval($baseId);
		$copyId = btstore_get()->BASE[$baseId]['copyid'];
		$copyId = intval($copyId);
		if($baseLevel>3)
		{
			throw new FakeException('the base level %s is not legal.',$baseLevel);
		}
		$ncopy		=	MyNCopy::getInstance();
		$copyObj	=	$ncopy->getCopyObj($copyId);
		if(empty($copyObj))
		{
			throw new FakeException('not found copy:%d, maybe it not open', $copyId);
		}
		$baseStatus	=	intval($baseLevel)+2;
		$addScore	=	0;
		$copyInfo	=	$copyObj->getCopyInfo();
		$progress	=	$copyInfo['va_copy_info']['progress'];
		if(!isset($progress[$baseId]))
		{
			$addScore	=	$baseLevel;
		}
		else if($baseStatus>$progress[$baseId])
		{
			if($progress[$baseId]<2)
			{
				$progress[$baseId]	=	2;
			}
			$addScore	=	$baseStatus-$progress[$baseId];
		}
		$copyObj->addScore($addScore);
		$copyObj->updBaseStatus($baseId, $baseStatus);
		$ncopy->saveCopy($copyId, $copyObj->getCopyInfo());
		return $copyObj->getCopyInfo();
	}

	/**
	 * 副本的所有据点通关简单难度     如果有据点通关了普通或者困难难度   不改变其状态
	 * @param int $uid
	 * @param int $copyId
	 */
	public function passNCopy($copyId,$baseLevel = 1)
	{
	    $uid    =    RPCContext::getInstance()->getUid();
		$ncopy		=	MyNCopy::getInstance();
		$copyObj	=	$ncopy->getCopyObj($copyId);
		if(isset(btstore_get()->COPY[$copyId]) == FALSE)
		{
		    throw new FakeException('no such copy with copyid %s.',$copyId);
		}
		if(empty($copyObj))
		{
		    $preBase    =    btstore_get()->COPY[$copyId]['base_open'];
		    if(!empty($preBase))
		    {
		        $preCopy    =    btstore_get()->BASE[$preBase]['copyid'];
		        $preCopy = intval($preCopy);
		        $preCopyObj =    $ncopy->getCopyObj($preCopy);
		        if(empty($preCopyObj) || ($preCopyObj->isCopyPassed() == FALSE))
		        {
		            throw new FakeException('preCopy %s is not passed', $preCopy);
		        }
		    }
		    $va_copy_info['progress'] = array();
		    //创建新的副本对象
		    $copyObj = MyNCopy::createNewObj($uid, $copyId,$va_copy_info);
		}
		$bases		=	btstore_get()->COPY[$copyId]['base'];
		$addScore	=	0;
		$baseNum    =    0;
		$score = 0;
		foreach($bases as $baseId)
		{
			if(empty($baseId))
			{
				break;
			}
			$level = $baseLevel;
			$level = $this->getDefeatLevel($baseId, $level);
			$baseNum ++;
			$baseStatus = $level + 2;
			$copyObj->updBaseStatus($baseId, $baseStatus);
			$score += $level;
		}
		$copyObj->setScore($score);
		$score	=	btstore_get()->COPY[$copyId]['total_star'];
		$copyObj	->	addScore($addScore);
		$ncopy->saveCopy($copyId, $copyObj->getCopyInfo());
		$newSession = RPCContext::getInstance()->getSession(CopySessionName::COPYLIST);
		return $copyObj->getCopyInfo();
	}

	private function getDefeatLevel($baseId,$level)
	{
	    while(true)
	    {
	        $lvName = CopyConf::$BASE_LEVEL_INDEX[$level];
	        if(!isset(btstore_get()->BASE[$baseId][$lvName]))
	        {
	            $level--;
	        }
	        else
	        {
	            break;
	        }
	    }
	    return $level;
	}

	public function passNCopies($copyId,$baseLevel = 3)
	{
	    if(!isset(btstore_get()->COPY[$copyId]))
	    {
	        return 'nosuchcopy';
	    }
	    $ncopy		=	MyNCopy::getInstance();
	    $copyObj	=	$ncopy->getCopyObj($copyId);
	    $preCopies    =    array($copyId);
	    $preBase    =    btstore_get()->COPY[$copyId]['base_open'];
	    while (!empty($preBase))
	    {
	        $preCopy    =    btstore_get()->BASE[$preBase]['copyid'];
	        if (!empty($preCopy))
	        {
	            $preCopies[] = $preCopy;
	        }
	        $preBase    =    btstore_get()->COPY[$preCopy]['base_open'];
	    }
	    $copies    =    array_reverse($preCopies);
	    foreach($copies as $copy)
	    {
	        Logger::trace('pass copy %s.',$copy);
	        $this->passNCopy($copy,$baseLevel);
	    }
	    return $ncopy->getAllCopies();
	}

	public function getPrize($copyId,$caseID)
	{
		$ncopy		=	new NCopy();
		$prize	=	$ncopy->getPrize($copyId, $caseID);
		return $prize;
	}

	public function unDoGetPrize($copyId,$caseID)
	{
		$uid	=	RPCContext::getInstance()->getUid();
		$copyObj	=	MyNCopy::getInstance()->getCopyObj($copyId);
		$copyInfo	=	$copyObj->getCopyInfo();
		if(($copyInfo ['prized_num'] & (CopyConf::$CASE_INDEX [$caseID])) != 0)
		{
			$copyInfo['prized_num'] -= CopyConf::$CASE_INDEX [$caseID];
		}
		MyNCopy::getInstance()->saveCopy($copyId, $copyInfo);
		return $copyInfo;
	}

	public function setScore($copyId,$score)
	{
		$ncopy		=	MyNCopy::getInstance();
		$copyObj	=	$ncopy->getCopyObj($copyId);
		$copyObj->setScore($score);
		return $copyObj->getCopyInfo();
	}


	public function resetEcopy($copyId)
	{
        $afterCopy = btstore_get()->ELITECOPY[$copyId]['pass_open_next'];
        if(!empty($afterCopy))
        {
            $ncopyId = btstore_get()->ELITECOPY[$copyId]['pre_open_copy'];
            if(!empty($ncopyId))
            {
                $this->resetNCopy($ncopyId);
            }
        }
	    $ecopyInfo = MyECopy::getInstance()->getEliteCopyInfo();
        if(!isset($ecopyInfo['va_copy_info']['progress'][$copyId]))
        {
            return;
        }
        $ecopyInfo['va_copy_info']['progress'][$copyId] = EliteCopyStatus::CANATTACK;
	    $uid = RPCContext::getInstance()->getUid();
	    ECopyDAO::save($uid, $ecopyInfo);
	    MyECopy::release();
	    RPCContext::getInstance()->unsetSession(CopySessionName::ECOPYLIST);
	    $ret = MyECopy::getInstance()->getEliteCopyInfo();
	    return $ret;
	}

	private function delNCopy($copyId)
	{
		$uid	=	RPCContext::getInstance()->getUid();
		$data = new CData();
		$data->update('t_copy')
			 ->set(array('status'=>DataDef::DELETED))
			 ->where(array('copy_id','=',$copyId))
			 ->where(array('uid','=',$uid))
			 ->query();
	}

	private function getAfterEcopyByNcopy($copyId)
	{
	    $ecopy = btstore_get()->COPY[$copyId]['pass_open_elite']->toArray();
	    if(empty($ecopy))
	    {
	        return;
	    }
	    if(!empty($ecopy[0]))
	    {
	        return $ecopy[0];
	    }
	}

	/**
	 * 重置副本  副本得分是0
	 * @param int $uid
	 * @param int $copyId
	 */
	public function resetNCopy($copyId)
	{
	    $afterCopies = $this->getAfterCopy($copyId);
	    Logger::trace('afterCopies is %s.',$afterCopies);
	    $ecopyId = $this->getAfterEcopyByNcopy($copyId);
	    $delEcopy = array();
	    if(!empty($ecopyId))
	    {
	        $delEcopy[] = $ecopyId;
	    }
	    foreach($afterCopies as $index => $copy)
	    {
	       $this->delNCopy($copy);
	       $ecopyId = $this->getAfterEcopyByNcopy($copy);
	       if(!empty($ecopyId))
	       {
	           $delEcopy[] = $ecopyId;
	       }
	    }
	    RPCContext::getInstance()->unsetSession(CopySessionName::COPYLIST);
	    MyNCopy::release();
		$copyObj	=	MyNCopy::getInstance()->getCopyObj($copyId);
		$copyInfo	=	$copyObj->getCopyInfo();
		$copyInfo['score'] = 0;
		$copyInfo['prized_num'] = 0;
		$firstBase = $this->getFirstBase($copyId);
		$copyInfo['va_copy_info']['progress'] = array($firstBase=>BaseStatus::CANATTACK);
		$copyInfo[NORMAL_COPY_FIELD::VA_COPY_INFO][NORMAL_COPY_FIELD::VA_DEFEAT_NUM] =
		            array();
		MyNCopy::getInstance()->saveCopy($copyId, $copyInfo);
		$this->delEcopy($delEcopy);
		return $copyInfo;
	}

	private function delEcopy($copyIds)
	{
	    Logger::trace('delEcopy %s.',$copyIds);
	    $ecopyInfo = MyECopy::getInstance()->getEliteCopyInfo();
	    foreach($copyIds as $index => $copyId)
	    {
	        if(!isset($ecopyInfo['va_copy_info']['progress'][$copyId]))
	        {
	            continue;
	        }
	        unset($ecopyInfo['va_copy_info']['progress'][$copyId]);
	    }
	    $uid = RPCContext::getInstance()->getUid();
	    Logger::trace('after delEcopy %s.',$ecopyInfo);
	    ECopyDAO::save($uid, $ecopyInfo);
	    MyECopy::release();
	    RPCContext::getInstance()->unsetSession(CopySessionName::ECOPYLIST);
	}

	private function getAfterCopy($copyId)
	{
	    $copyList = MyNCopy::getInstance()->getAllCopies();
	    $afterCopies = array();
	    $curCopy = $copyId;
	    while(!empty($curCopy) && isset($copyList[$curCopy]))
	    {
	        $baseId = $this->getLastBase($curCopy);
	        $conf = btstore_get ()->BASE [$baseId] ['pass_open_copy']->toArray ();
	        if(empty($conf))
	        {
	            break;
	        }
	        $afterCopy = $conf[0];
	        if(!empty($afterCopy))
	        {
	            $afterCopies[] = $afterCopy;
	            $curCopy = $afterCopy;
	        }
	        else
	        {
	            break;
	        }
	    }
	    return $afterCopies;
	}

	private function getLastBase($copyId)
	{
	    $baseNum = btstore_get()->COPY[$copyId]['base_num'];
	    $baseId = btstore_get()->COPY[$copyId]['base'][$baseNum-1];
	    return $baseId;
	}

	private function getFirstBase($copyId)
	{
	    $baseId = btstore_get()->COPY[$copyId]['base'][0];
	    return $baseId;
	}

	public function resetBase($baseId)
	{
	    if(!isset(btstore_get()->BASE[$baseId]))
	    {
	        return 'err:no this base';
	    }
	    $copyId    =    intval(btstore_get()->BASE[$baseId]["copyid"]);
	    $copyObj	=	MyNCopy::getInstance()->getCopyObj($copyId);
	    if($copyObj == NULL)
	    {
	        return 'err:the copy which this base in is not passed.';
	    }
	    $copyInfo	=	$copyObj->getCopyInfo();
	    $progress    =    $copyInfo['va_copy_info']['progress'];
	    if(!isset($progress[$baseId]))
	    {
	        return 'err::this base is not passed.';
	    }
	    $subScore = ($progress[$baseId] - 2 > 0)?($progress[$baseId] - 2):0;
	    foreach($progress as $hasBaseId => $status)
	    {
	        if($hasBaseId > $baseId)
	        {
	            unset($progress[$hasBaseId]);
	            $subScore += ($progress[$hasBaseId] - 2 > 0)?($progress[$hasBaseId] - 2):0;
	        }
	    }
	    $progress[$baseId] = BaseStatus::CANATTACK;
	    $copyInfo['va_copy_info']['progress'] = $progress;
	    $copyInfo['score'] -= $subScore;
	    $uid = RPCContext::getInstance()->getUid();
	    NCopyDAO::saveCopy($copyInfo);
	    return "ok";
	}

	/**
	 * **********精英副本************************
	 */
	public function getECopyInfo()
	{
		$ecopy	=	new ECopy();
		$copyInfo	=	$ecopy->getEliteCopyInfo();
		return $copyInfo;
	}
	/**
	 * 如果功能节点（精英副本）没有开启，精英副本不能打
	 * @param unknown_type $copyId
	 * @return multitype:
	 */
	public function passECopy($copyId)
	{
	    if(!isset(btstore_get()->ELITECOPY[$copyId]))
	    {
	        return 'nosuchcopy';
	    }
		$copyInfo	=	MyECopy::getInstance()->getEliteCopyInfo();
		if(empty($copyInfo))
		{
			$ecopyInfo = MyECopy::getInstance()->addNewEliteCopy(CopyConf::$FIRST_ELITE_COPY_ID);
		}

		$ncopy    =    btstore_get()->ELITECOPY[$copyId]['pre_open_copy'];
		$this->passNCopies($ncopy,1);
		$this->passe($copyId);
		MyECopy::getInstance()->save();
		return MyECopy::getInstance()->getEliteCopyInfo();
	}

	private function passe($copyId)
	{
	    if(!isset(btstore_get()->ELITECOPY[$copyId]))
	    {
	        return;
	    }
	    if(CopyConf::$FIRST_ELITE_COPY_ID == $copyId)
	    {
	        MyECopy::getInstance()->passCopy($copyId);
	        return;
	    }
	    if(MyECopy::getInstance()->getStatusofCopy($copyId) >= EliteCopyStatus::PASS)
	    {
	        return;
	    }
	    $preCopy    =    btstore_get()->ELITECOPY[$copyId]['pre_copy'];
	    $this->passe($preCopy);
	    MyECopy::getInstance()->passCopy($copyId);
	    return;
	}

	public function resetEAtkNum()
	{
	    $copyInfo	=	MyECopy::getInstance()->getEliteCopyInfo();
	    if(empty($copyInfo))
	    {
	        throw new FakeException('no such elitecopy.');
	    }
	    $copyInfo['can_defeat_num']		= CopyConf::$CHALLANGE_TIMES;
	    $copyInfo['last_defeat_time']	= Util::getTime();
	    $uid    =    RPCContext::getInstance()->getUid();
	    ECopyDAO::save($uid, $copyInfo);
	    return $copyInfo;
	}

	public function resetAAtkNum($copyId)
	{
	    $acopyObj = MyACopy::getInstance()->getActivityCopyObj($copyId);
	    $copyInfo = $acopyObj->getCopyInfo();
	    //$copyInfo['can_defeat_num'] = $acopyObj->getAllDefeatNum();
	    $copyInfo['last_defeat_time']	= Util::getTime()-86400;
	    $uid = RPCContext::getInstance()->getUid();
	    ACopyDAO::saveActivityCopy($uid, $copyId, $copyInfo);
	    return $copyInfo;
	}

	public function setExpUserBase($baseId)
	{
		$uid = RPCContext::getInstance()->getUid();
		$copyId = ACT_COPY_TYPE::EXPUSER_COPYID;
		$myACopy = MyACopy::getInstance($uid);
		$acopyObj = $myACopy->getActivityCopyObj($copyId);
		$acopyObj->updateBaseId($baseId);
		$myACopy->save();
		return 'ok';
	}

	public function resetRfrTime($seconds)
	{
	    $uid = RPCContext::getInstance()->getUid();
	    $info = MallDao::select($uid, MallDef::MALL_TYPE_MYSTERY);
	    $info[MysteryShopDef::TBL_FIELD_VA_SYS_REFRTIME] = util::getTime()+$seconds;
	    $arrField['uid'] = $uid;
	    $arrField['mall_type'] = MallDef::MALL_TYPE_MYSTERY;
	    $arrField['va_mall'] = $info;
	    MallDao::insertOrUpdate($arrField);
	    Logger::trace('resetRfrTime %s.',$arrField);
	    return $info;
	}

	/**
	 * status的取值
	 * CANSHOW = 0;CANATTACK = 1;PASS = 2;
	 * @param int $uid
	 * @param int $copyId
	 * @param int $status
	 */
	public function setECopyStatus($copyId,$status)
	{
	    if(!isset(btstore_get()->ELITECOPY[$copyId]))
	    {
	        throw new FakeException('no such elitecopy.');
	    }
		$uid	=	RPCContext::getInstance()->getUid();
		$copyInfo	=	ECopyDAO::getEliteCopyInfo($uid);
		$copyInfo['va_copy_info']['progress'][$copyId] = intval($status);
		ECopyDAO::save($uid, $copyInfo);
		return $copyInfo;
	}

	/**
	 * **********活动副本************************
	 */
	public function getACopyList()
	{
		$acopy	=	new ACopy();
		$copyList	=	$acopy->getCopyList();
		return $copyList;
	}

	/**
	 * **********爬塔系统************************
	 */
	public function getTowerInfo()
	{
		$tower	=	new Tower();
		return $tower->getTowerInfo();
	}

	public function setResetNum($num)
	{
	    $towerInfo = MyTower::getInstance()->getTowerInfo();
	    $towerInfo[TOWERTBL_FIELD::RESET_NUM] = $num;
	    TowerDAO::save($towerInfo[TOWERTBL_FIELD::UID], $towerInfo);
	}

	public function passTowerLevel($level)
	{
	    MyTower::getInstance()->passLevel($level);
	    MyTower::getInstance()->save();
	    $uid = RPCContext::getInstance()->getUid();
	    // 通知新服活动的通关试炼塔任务
	    EnNewServerActivity::updatePassTower($uid, $level);
	}

	public function setCurLv($level)
	{
	    $towerInfo = MyTower::getInstance()->getTowerInfo();
	    $towerInfo[TOWERTBL_FIELD::CURRENT_LEVEL] = $level;

	    $maxLevel = count(btstore_get()->TOWERLEVEL->toArray());
	    if ($level < $maxLevel)
	    {
	    	$towerInfo[TOWERTBL_FIELD::VA_TOWER_INFO][TOWERTBL_FIELD::VA_TOWER_CURSTATUS] = 0;
	    }

	    TowerDAO::save($towerInfo[TOWERTBL_FIELD::UID], $towerInfo);
	}

	public function clearSpTower()
	{
	    $towerInfo = MyTower::getInstance()->getTowerInfo();
	    $towerInfo[TOWERTBL_FIELD::VA_TOWER_INFO][TOWERTBL_FIELD::VA_TOWER_SPECIALTOWER]
	            [TOWERTBL_FIELD::VA_TOWER_SPECIALTOWER_LIST] = array();
	    TowerDAO::save($towerInfo[TOWERTBL_FIELD::UID], $towerInfo);
	}

	public function resetBuySpNum()
	{
		$towerInfo = MyTower::getInstance()->getTowerInfo();
		$towerInfo[TOWERTBL_FIELD::BUY_SPECIAL_NUM] = 0;
		TowerDAO::save($towerInfo[TOWERTBL_FIELD::UID], $towerInfo);
	}

	public function openDestiny($destinyId)
	{
	    $uid = RPCContext::getInstance()->getUid();
	    $myDestiny = new MyDestiny($uid);
	    if($myDestiny->getCurDestinyId() >= $destinyId)
	    {
	        return 'opened';
	    }
	    $copyScore = MyNCopy::getInstance()->getScore();
	    $needScore = btstore_get()->DESTINY[$destinyId]['needCopyScore'];
	    if($copyScore < $needScore)
	    {
	        return 'notenoughscore';
	    }
	    $curDestinyId = $myDestiny->getCurDestinyId();
	    while($curDestinyId != $destinyId)
	    {
	        $nextDestinyId = btstore_get()->DESTINY[$curDestinyId]['afterId'];
	        DestinyLogic::activateDestiny($nextDestinyId);
	        $myDestiny = new MyDestiny($uid);
	        $curDestinyId = $myDestiny->getCurDestinyId();
	    }
	    return 'ok';
	}
	/**
	 * **********资源矿系统************************
	 */
	public function getPitsByDomain($domainId)
	{
		$pits	=	MineralDAO::getPitsByDomain($domainId);
		return $pits;
	}

	public function getPitInfo($domainId,$pitId)
	{
		$pit	=	MineralDAO::getPitById($domainId, $pitId);
		return $pit;
	}

	/**
	 *
	 * @param int $domainId
	 * @param int $pitId
	 * @param int $uid	可以取值为0 表示将此矿设置为无人占领
	 */
	public function setCaptureofPit($domainId,$pitId,$uid)
	{
		$pit	=	MineralDAO::getPitById($domainId, $pitId);
		if($pit[TblMineralField::UID]!=0)
		{
			if($pit[TblMineralField::UID] == $uid)
			{
				return $pit;
			}
			$timer	=	$pit[TblMineralField::DUETIMER];
			TimerTask::cancelTask($timer);
		}
		if($uid == 0)
		{
			$pit[TblMineralField::UID] = 0;
			$pit[TblMineralField::DUETIMER] = 0;
		}
		else
		{
			$pit[TblMineralField::UID]=$uid;
			$dueTime	=	btstore_get()->MINERAL[$this->domainId]['pits'][$this->pitId][PitArr::HARVESTTIME]+time();
			$timerId	=	TimerTask::addTask($uid, $dueTime, 'Mineral.duePit', array($uid,$domainId,$pitId,time()));
			$pit[TblMineralField::DUETIMER]	=	$timerId;
		}
		MineralDAO::savePitInfo($pit);
		return $pit;
	}

    public function setPitDue($domainId, $pitId)
    {
        $pit = MineralDAO::getPitById($domainId, $pitId);
        $uid = $pit[TblMineralField::UID];
        $timer = $pit[TblMineralField::DUETIMER];
        if($pit != 0)
        {
            MineralLogic::duePit($uid, $domainId, $pitId);
            TimerTask::cancelTask($timer);
        }
    }

    public function endPitGuard($uid)
    {
        $guard = MineralDAO::getGuardInfoByUid($uid);
        if(empty($guard))
        {
            return;
        }
        MineralLogic::endPitGuard($uid, $guard[TblMineralGuards::DOMAINID], $guard[TblMineralGuards::PITID]);
    }

	/**
	 * **********武将系统************************
	 */
	/**
	 *
	 * @param int $htid
	 * @return int hid
	 */
	public function addHero($htid,$num=1)
	{
		$htid	=	intval($htid);
		$heroMng=	Enuser::getUserObj()->getHeroManager();
		$ret = array();
		for($i=0;$i<$num;$i++)
		{
		    $hid = $heroMng->addNewHero($htid);
		    $ret[] = $hid;
		}
		Enuser::getUserObj()->update();
		return $ret;
	}


	public function addMultiHero($fromHtid,$toHtid)
	{
	    $fromHtid = intval($fromHtid);
	    $toHtid = intval($toHtid);
	    $heroMng = Enuser::getUserObj()->getHeroManager();
	    $ret = array();
	    for($i=$fromHtid;$i<=$toHtid;$i++)
	    {
    	    $hid = $heroMng->addNewHero($i);
    	    $ret[] = $hid;
    	}
    	Enuser::getUserObj()->update();
    	return $ret;
	}


	public function setLevelByHtid($htid,$level)
	{
	    $heroMng    =    Enuser::getUserObj()->getHeroManager();
	    $heroes    =    $heroMng->getAllHeroObj();
	    foreach($heroes as $hid => $heroObj)
	    {
	        if($heroObj->getHtid()!=$htid)
	        {
	            continue;
	        }
	        $this->setHeroLevel($hid, $level);
	    }
	    EnUser::getUserObj()->update();
	}

	public function setEvLvByHtid($htid,$ev,$lv)
	{
	    $heroMng    =    Enuser::getUserObj()->getHeroManager();
	    $heroes    =    $heroMng->getAllHeroObj();
	    foreach($heroes as $hid => $heroObj)
	    {
	        if($heroObj->getHtid()!=$htid)
	        {
	            continue;
	        }
	        if($ev > $heroObj->getEvolveLv())
	        {
	            $delt    =   $ev - $heroObj->getEvolveLv();
	            for($i=0;$i<$delt;$i++)
	            {
	                $heroObj->convertUp();
	            }
	        }
	        $this->setHeroLevel($hid, $lv);
	    }
	    EnUser::getUserObj()->modifyBattleData();
	    EnUser::getUserObj()->update();
	    return 'ok';
	}

	public function resetSweepCd()
	{
	    $uid = RPCContext::getInstance()->getUid();
	    $userCopy = NCopyLogic::getUserCopy($uid);
	    $userCopy[USER_COPY_FIELD::UID] = $uid;
	    $userCopy[USER_COPY_FIELD::SWEEP_CD] = 0;
	    NCopyDAO::saveUserCopyInfo($uid, $userCopy);
	}

	public function setEvLv($ev,$lv)
	{
	    $heroMng    =    Enuser::getUserObj()->getHeroManager();
	    $heroes    =    $heroMng->getAllHeroObj();
	    foreach($heroes as $hid => $heroObj)
	    {
	        if($heroObj->isMasterHero())
	        {
	            continue;
	        }
	        if($ev > $heroObj->getEvolveLv())
	        {
	            $delt    =   $ev - $heroObj->getEvolveLv();
	            for($i=0;$i<$delt;$i++)
	            {
	                $heroObj->convertUp();
	            }
	        }
	        $this->setHeroLevel($hid, $lv);
	    }
	    EnUser::getUserObj()->modifyBattleData();
	    EnUser::getUserObj()->update();
	    return 'ok';
	}

	public function setHeroLevel($hid,$level)
	{
	    $heroMng    =    Enuser::getUserObj()->getHeroManager();
	    $heroObj    =    $heroMng->getHeroObj($hid);
	    $htid    =    $heroObj->getHtid();
	    if($heroObj->getLevel() >= $level)
	    {
	        return;
	    }
	    $expTblId	= Creature::getCreatureConf($htid,CreatureAttr::EXP_ID);
	    $expTbl		= btstore_get()->EXP_TBL[$expTblId];
	    $needSoul    =    $expTbl[$level] - $heroObj->getSoul();
	    $newLv    =    $heroObj->addSoul($needSoul);
	    if($heroObj->getMaxEnforceLevel() < $newLv)
	    {
	        return 'maxenforcelevel:'.$heroObj->getMaxEnforceLevel();
	    }
	    if($newLv!=$level)
	    {
	        return 'err';
	    }
	    $heroObj->update();
	    EnUser::getUserObj()->modifyBattleData();
	    return 'ok';
	}

	public function addSoul($hid,$soul)
	{
	    $heroMng    =    Enuser::getUserObj()->getHeroManager();
	    $heroObj    =    $heroMng->getHeroObj($hid);
	    $level = $heroObj->addSoul($soul);
	    $heroObj->update();
	    return array(
	            'soul'=>$heroObj->getSoul(),
	            'level'=>$level);
	}

	public function addHeroes($htid,$num)
	{
	    $htid	=	intval($htid);
	    $heroMng=	Enuser::getUserObj()->getHeroManager();
	    $hids = $heroMng->addNewHeroes(array($htid=>$num));
	    Enuser::getUserObj()->update();
	    return $hids;
	}

	public function getUnusedHero()
	{
		$heroes	=	EnUser::getUserObj()->getAllUnusedHero();
		return $heroes;
	}

	public function getAllHero()
	{
		$heroMng	=	EnUser::getUserObj()->getHeroManager();
		$arrHeroes	=	$heroMng->getAllHero();
		return $arrHeroes;
	}

	public function getHeroInFmt()
	{
	    $heroMng	=	EnUser::getUserObj()->getHeroManager();
	    $heroes    =    $heroMng->getAllHeroObjInSquad();
	    $ret    =    array();
	    foreach($heroes as $hid => $heroObj)
	    {
	        $ret[] = array('hid'=>$hid,
	                'htid'=>$heroObj->getHtid());
	    }
	    return $ret;
	}

    public function getHeroBattleInfo($htid = 0)
	{
	    $userObj = EnUser::getUserObj();
	    $heroMng = $userObj->getHeroManager();
	    $allHero = $heroMng->getAllHero();
	    $userObj->modifyBattleData();
	    $userBtInfo = $userObj->getBattleFormation();
	    $arrHeroInFmt = $userBtInfo['arrHero'];
	    $heroId = 0;
	    $btInfo = array();
	    $arrRet = array();

	    $arrKey = array(
	    		PropertyKey::ABSOLUTE_GENERAL_ATTACK,
	    		PropertyKey::ABSOLUTE_PHYSICAL_ATTACK,
	    		PropertyKey::ABSOLUTE_MAGIC_ATTACK,
	    		PropertyKey::ABSOLUTE_PHYSICAL_DEFEND,
	    		PropertyKey::ABSOLUTE_MAGIC_DEFEND,
	    		PropertyKey::STRENGTH,
	    		PropertyKey::REIGN,
	    		PropertyKey::INTELLIGENCE,
	    );
	    foreach($arrHeroInFmt as $pos => $heroInfo)
	    {
	    	if ( $htid > 0
	    		&& $heroInfo['htid'] != $htid
	    		&& Creature::getHeroConf($heroInfo['htid'], CreatureAttr::BASE_HTID) == $htid )
	    	{
	    		continue;
	    	}
	    	$heroId = $heroInfo['hid'];
	    	$heroObj = $heroMng->getHeroObj($heroId);
	    	$info = array(
	    			'武将模板id'=>$heroInfo['htid'],
	    			'武将等级'=>$heroObj->getLevel(),
	    			'武将进阶次数'=>$heroObj->getEvolveLv(),
	    			'生命'=>$heroInfo[PropertyKey::MAX_HP],
	    			'通用攻击'=>intval($heroInfo[PropertyKey::GENERAL_ATTACK_BASE] * (1 + $heroInfo[PropertyKey::GENERAL_ATTACK_ADDITION]/UNIT_BASE)),
	    			'物理攻击'=>intval($heroInfo[PropertyKey::PHYSICAL_ATTACK_BASE] * (1 + $heroInfo[PropertyKey::PHYSICAL_ATTACK_ADDITION]/UNIT_BASE)),
	    			'法术攻击'=>intval($heroInfo[PropertyKey::MAGIC_ATTACK_BASE] * (1 + $heroInfo[PropertyKey::MAGIC_ATTACK_BASE]/UNIT_BASE)),
	    			'物理防御'=>intval($heroInfo[PropertyKey::PHYSICAL_DEFEND_BASE] * (1 + $heroInfo[PropertyKey::PHYSICAL_DEFEND_ADDITION]/UNIT_BASE)),
	    			'法术防御'=>intval($heroInfo[PropertyKey::MAGIC_DEFEND_BASE] * (1 + $heroInfo[PropertyKey::MAGIC_DEFEND_ADDITION]/UNIT_BASE)),
	    			'战斗力'=>$heroInfo[PropertyKey::FIGHT_FORCE],
	    	);
	    	foreach( $arrKey as $key )
	    	{
	    		$info[$key] = $heroInfo[$key];
	    	}

	    	$arrRet[] = $info;
	    }

	    return $arrRet;
	}

	public function getAddAttr($hid)
	{
	    $userObj    =    EnUser::getUserObj();
	    $heroObj = $userObj->getHeroManager()->getHeroObj($hid);
	    $addAttr = $heroObj->getAddAttr();
	    return $addAttr;
	}

	private function getFightForce($heroInfo)
	{
	    $fightForce    =    $heroInfo[PropertyKey::PHYSICAL_ATTACK_BASE]+
	                        $heroInfo[PropertyKey::MAGIC_ATTACK_BASE]+
	                        $heroInfo[PropertyKey::PHYSICAL_DEFEND_BASE]+
	                        $heroInfo[PropertyKey::MAGIC_DEFEND_BASE]+
	                        $heroInfo[PropertyKey::GENERAL_ATTACK_BASE];
	    $fightForce    +=   intval($heroInfo[PropertyKey::MAX_HP]/5);
	    $sanwei    =    $heroInfo[PropertyKey::INTELLIGENCE]+
	                    $heroInfo[PropertyKey::REIGN]+
	                    $heroInfo[PropertyKey::STRENGTH];
	    $fightForce    +=    ($sanwei - HeroConf::FIGHT_FORCE_PLUS_SW)/100;
	     return $fightForce;
	}

	public function delHero($hid)
	{
	    $heroMng	=	EnUser::getUserObj()->getHeroManager();
	    $heroMng->delHeroByHid($hid);
	    EnUser::getUserObj()->update();
	    return 'ok';
	}

	public function delAllHero()
	{
	    $uid = RPCContext::getInstance()->getUid();
	    $heroMng	=	EnUser::getUserObj()->getHeroManager();
	    $arrHeroes	=	$heroMng->getAllHeroObj();
	    foreach($arrHeroes as $hero)
	    {
	        $hid =  $hero->getHid();
	        if(EnFormation::isHidInFormation($hid, $uid)
	                || EnFormation::isHidInExtra($hid, $uid)
	    			|| EnFormation::isHidInAttrExtra($hid, $uid))
	        {
	            continue;
	        }
	        $hero->setEvolveLevel(0);
	        if($hero->canBeDel() == FALSE)
	        {
	            continue;
	        }
	       $heroMng->delHeroByHid($hid);
	    }
	    Enuser::getUserObj()->update();
	    return self::getAllHero();
	}

	public function delAllHeroByStar($star)
	{
	    $uid = RPCContext::getInstance()->getUid();
	    $heroMng	=	EnUser::getUserObj()->getHeroManager();
	    $arrHeroes	=	$heroMng->getAllHeroObj();
	    foreach($arrHeroes as $hero)
	    {
	        $hid =  $hero->getHid();
	        if($hero->canBeDel() == FALSE ||
	                (Creature::getHeroConf($hero->getHtid(), CreatureAttr::STAR_LEVEL) > $star))
	        {
	            continue;
	        }
	        $hero->setEvolveLevel(0);
	        $heroMng->delHeroByHid($hid);
	    }
	    Enuser::getUserObj()->update();
	    return self::getAllHero();
	}

	public function delHeroByEvLv($evLv)
	{
	    $uid = RPCContext::getInstance()->getUid();
	    $heroMng	=	EnUser::getUserObj()->getHeroManager();
	    $arrHeroes	=	$heroMng->getAllHeroObj();
	    foreach($arrHeroes as $hero)
	    {
	        $hid =  $hero->getHid();
	        if($hero->isMasterHero())
	        {
	            continue;
	        }
	        $hero->setEvolveLevel(0);
	        if($hero->canBeDel() == FALSE || ($hero->getEvolveLv() < $evLv))
	        {
	            continue;
	        }
	        $heroMng->delHeroByHid($hid);
	    }
	    Enuser::getUserObj()->update();
	    return self::getAllHero();
	}

	public function resetHero($hid)
	{
	    $heroMng	=	EnUser::getUserObj()->getHeroManager();
	    $heroInfos   =   $heroMng->getAllHero();
	    $heroInfo    =    $heroInfos[$hid];
	    $heroArms    =    $heroInfo['equip'][HeroDef::EQUIP_ARMING];
	    foreach($heroArms    as  $posId    =>    $arm)
	    {
	        $heroArms[$posId]=0;
	    }
	    $va_hero    =    array('va_hero'=>array(
	            HeroDef::EQUIP_ARMING=>$heroArms,
	            HeroDef::EQUIP_SKILL_BOOK=>array()));
	    HeroDao::update($hid, $va_hero);
	}

	public function addArming($hid,$itemId,$posId)
	{
	    $hero    =    new Hero();
	    $hero->addArming($hid, $posId, $itemId);
	    return 'ok';
	}

	public function addDress($dressTmplId)
	{
	    $bag = BagManager::getInstance()->getBag();
	    $arrItemId = $bag->getItemIdsByItemType(ItemDef::ITEM_TYPE_DRESS);
	    $hero    =    new Hero();
	    foreach($arrItemId as $itemId)
	    {
	        $item = ItemManager::getInstance()->getItem($itemId);
	        if($item->getItemTemplateID() == $dressTmplId)
	        {
	            $hero->addFashion(1, $itemId);
	            break;
	        }
	    }
	}

	public function removeAllArming($hid)
	{
	    $set    =    array('va_hero' => array(
	            HeroDef::EQUIP_ARMING => ArmDef::$ARM_NO_ARMING,
	            HeroDef::EQUIP_SKILL_BOOK => array()));
	    HeroDao::update($hid, $set);
	}

	/**
	 * **********阵型系统************************
	 */

	/**
	 * 获取阵型信息
	 */
	public function getFmt()
	{
		$fmt	=	new Formation();
		return $fmt->getFormation();
	}

	/**
	 * 初始化阵型
	 */
	public function resetFmt()
	{
		$fmt    =    array();
		$heroMng	=	Enuser::getUserObj()->getHeroManager();
		$heroObjs    =    $heroMng->getAllHeroObj();
		foreach($heroObjs as $hid=>$heroObj)
		{
			if($heroObj->isMasterHero() == true)
			{
				$fmt['va_formation'][$hid]=array('index'=>0,'pos'=>0);
				break;
			}
		}
		$uid    =    RPCContext::getInstance()->getUid();
		FormationDao::update($uid, $fmt);
	}

	/**
	 * 添加没有在阵容中的武将到阵容和阵型中
	 * @param int hid				武将id  此武将应该在阵容中不存在
	 * @param int index				阵容中的位置
	 */
	public function addHeroToFmt($hid,$index)
	{
	    $hid    =    intval($hid);
		$heroObj	=	Enuser::getUserObj()->getHeroManager()->getHeroObj($hid);
		if(empty($heroObj))
		{
			return 'no such hero with hid '.$hid;
		}
		$fmt	=	new Formation();
		$fmt->addHero($hid, $index);
		return $fmt->getFormation();
	}

	/**
	 * 从阵容和阵型中删除武将
	 * @param int $hid
	 */
	public function delHeroFromFmt($hid)
	{
		$fmtInst	=	new Formation();
		$fmtInst->delHero($hid);
		return $fmtInst->getFormation();
	}

	/**
	 * 在阵型中移动武将，更换武将的位置  如果原来的位置有武将，与此武将更换位置
	 * @param int $hid
	 * @param int $newpos
	 * @return array 当前的阵型
	 */
	public function setHeroPosInFmt($hid,$newpos)
	{
		$heroObj	=	Enuser::getUserObj()->getHeroManager()->getHeroObj($hid);
		if(empty($heroObj))
		{
			return 'no such hero with hid '.$hid;
		}
		$uid	=	RPCContext::getInstance()->getUid();
		$fmt	=	EnFormation::getFormationObj($uid)->getFormation();
		$oldpos = -1;
		foreach($fmt as $pos=>$hero)
		{
			if($hero == $hid)
			{
				$oldpos = $pos;
			}
		}
		if($oldpos == -1)
		{
			throw new FakeException('this hero %s is not in squad,can not add to formation.',$hid);
		}
		if(isset($fmt[$newpos]))
		{
			$newhid	=	$fmt[$newpos];
			$fmt[$oldpos]	=	$newhid;
		}
		else
		{
			unset($fmt[$oldpos]);
		}
		$fmt[$newpos]	=	$hid;
		$fmtInst	=	new Formation();
		$fmtInst->setFormation($fmt);
		return $fmtInst->getFormation();
	}

	/**
	 * **********铁匠铺系统************************
	 */

	/**
	 * 装备强化到指定等级
	 *
	 * @param int $itemId
	 * @param int $level
	 * @return 'ok'
	 */
	public function enforce($itemId, $level)
	{
		$item = ItemManager::getInstance()->getItem($itemId);
		if ($item == null)
		{
			return 'not-exist:'.$itemId;
		}
		$item->setLevel($level);
		ItemManager::getInstance()->update();
		EnUser::getUserObj()->modifyBattleData();
		return 'ok';
	}

	/**
	 * 将所有装备强化到指定等级
	 *
	 * @param int $level
	 * @return 'ok'
	 */
	public function enforceAll($level)
	{
	    $uid = RPCContext::getInstance()->getUid();
	    $user = EnUser::getUserObj($uid);
	    $allhero = $user->getHeroManager()->getAllHeroObjInSquad();
	    foreach ($allhero as $hero)
	    {
	    	$allarm = $hero->getEquipByType(HeroDef::EQUIP_ARMING);
	    	foreach ($allarm as $itemId)
	    	{
	    		if ($itemId == 0)
	    		{
	    			continue;
	    		}
	    		$ret = $this->enforce($itemId, $level);
	    		if ($ret != 'ok')
	    		{
	    			return $ret;
	    		}
	    	}
	    }
		$bagInfo = $this->bagInfo();
		$armBag = $bagInfo[BagDef::BAG_ARM];
		foreach ($armBag as $gid => $itemInfo)
		{
			$itemId = $itemInfo[ItemDef::ITEM_SQL_ITEM_ID];
			$ret = $this->enforce($itemId, $level);
			if ($ret != 'ok')
			{
				return $ret;
			}
		}
		EnUser::getUserObj()->modifyBattleData();
		return 'ok';
	}

    public function getMultiRecord($brid)
	{
		$ret = BattleLogic::getRecord($brid);
		$uid = RPCContext::getInstance()->getUid();
		RPCContext::getInstance()->sendMsg(array($uid), 'push.copyteam.battleResult',
		         $ret);
	}
	/**
	 * 宝物强化到指定等级
	 *
	 * @param int $itemId
	 * @param int $level
	 * @return 'ok'
	 */
	public function upgrade($itemId, $level)
	{
		$item = ItemManager::getInstance()->getItem($itemId);
		if ($item == null)
		{
			return 'not-exist';
		}
		$item->setLevel($level);
		ItemManager::getInstance()->update();
		EnUser::getUserObj()->modifyBattleData();
		return 'ok';
	}

	/**
	 * 将所有宝物强化到指定等级
	 *
	 * @param int $level
	 * @return 'ok'
	 */
	public function upgradeAll($level)
	{
		$uid = RPCContext::getInstance()->getUid();
		$user = EnUser::getUserObj($uid);
		$allhero = $user->getHeroManager()->getAllHeroObjInSquad();
		foreach ($allhero as $hero)
		{
			$alltreas = $hero->getEquipByType(HeroDef::EQUIP_TREASURE);
			foreach ($alltreas as $itemId)
			{
				if ($itemId == 0)
				{
					continue;
				}
				$ret = $this->upgrade($itemId, $level);
				if ($ret != 'ok')
				{
					return $ret;
				}
			}
		}
		$bagInfo = $this->bagInfo();
		$treasBag = $bagInfo[BagDef::BAG_TREAS];
		foreach ($treasBag as $gid => $itemInfo)
		{
			$itemId = $itemInfo[ItemDef::ITEM_SQL_ITEM_ID];
			$ret = $this->upgrade($itemId, $level);
			if ($ret != 'ok')
			{
				return $ret;
			}
		}
		return 'ok';
	}

	/**
	 * 战魂强化到指定等级
	 *
	 * @param int $itemId
	 * @param int $level
	 * @return 'ok'
	 */
	public function promote($itemId, $level)
	{
		$item = ItemManager::getInstance()->getItem($itemId);
		if ($item == null)
		{
			return 'not-exist';
		}
		$item->setLevel($level);
		ItemManager::getInstance()->update();
		EnUser::getUserObj()->modifyBattleData();
		return 'ok';
	}

	/**
	 * 将所有战魂强化到指定等级
	 *
	 * @param int $level
	 * @return 'ok'
	 */
	public function promoteAll($level)
	{
		$uid = RPCContext::getInstance()->getUid();
		$user = EnUser::getUserObj($uid);
		$allhero = $user->getHeroManager()->getAllHeroObjInSquad();
		foreach ($allhero as $hero)
		{
			$allfightsoul = $hero->getEquipByType(HeroDef::EQUIP_FIGHTSOUL);
			foreach ($allfightsoul as $itemId)
			{
				if ($itemId == 0)
				{
					continue;
				}
				$ret = $this->promote($itemId, $level);
				if ($ret != 'ok')
				{
					return $ret;
				}
			}
		}
		$bagInfo = $this->bagInfo();
		$fightsoulBag = $bagInfo[BagDef::BAG_FIGHT_SOUL];
		foreach ($fightsoulBag as $gid => $itemInfo)
		{
			$itemId = $itemInfo[ItemDef::ITEM_SQL_ITEM_ID];
			$ret = $this->promote($itemId, $level);
			if ($ret != 'ok')
			{
				return $ret;
			}
		}
		return 'ok';
	}

	public function setPotence($itemId, $PotenceValue)
	{
		$item = ItemManager::getInstance()->getItem($itemId);
		if (empty($item))
		{
			return "No-Item";
		}
		if (ItemDef::ITEM_TYPE_ARM != $item->getItemType())
		{
			return "No-Arm";
		}
		if ($item->canFixedRefresh() == false)
		{
			return "No-Refresh";
		}
		$itemText = $item->getItemText();
		if (empty($itemText[ArmDef::ITEM_ATTR_NAME_ARM_POTENCE]))
		{
			return "No-Potence";
		}
		$potenceId = $item->getFixedPotenceId();
		$armPotence = $itemText[ArmDef::ITEM_ATTR_NAME_ARM_POTENCE];
		foreach ($armPotence as $attrId => $attrValue)
		{
			$value = Potence::getPotenceAttrValue($potenceId , $attrId);
			$armPotence[$attrId] = $PotenceValue * $value;
		}
		$itemText[ArmDef::ITEM_ATTR_NAME_ARM_POTENCE] = $armPotence;
		$item->setItemText($itemText);
		$uid = RPCContext::getInstance()->getUid();
		$user = EnUser::getUserObj($uid);
		$bag = BagManager::getInstance()->getBag($uid);
		if (!$bag->isItemExist($itemId))
		{
			$user->modifyBattleData();
		}
		$bag->update();
		return 'ok';
	}

	/**
	 * **********竞技场系统************************
	 */

	/**
	 * 重置竞技场次数
	 */
	public function resetChallengeNum()
	{
		$uid	=	RPCContext::getInstance()->getUid();
		$num = btstore_get()->ARENA_PROPERTIES['challenge_free_num'];
		$arrField = array(
				'challenge_num' => $num,
				'challenge_time' => 0,
		);
		ArenaDao::update($uid, $arrField);
	}

	public function resetArenaReward($n)
	{
		$uid	=	RPCContext::getInstance()->getUid();
		$info = ArenaLogic::getInfo($uid);
		if (!isset($info['va_reward']['his']))
		{
			$info['va_reward']['his'] = array();
		}
		$info['va_reward']['his'] = ArenaLogic::refreshPosHis($uid, $info['position'], $info['va_reward']['his']);
		$i = 0;
		foreach ($info['va_reward']['his'] as $date => $his)
		{
			if ($i++ >= ArenaConf::POS_HIS - $n)
			{
				$info['va_reward']['his'][$date][1] = ArenaDef::HAVE;
			}
		}
		ArenaDao::update($uid, $info);
		return 'ok';
	}

	/**
	 * **********名将系统************************
	 */

	/**
	 * 加名将
	 *
	 * @param unknown $stid			名将id
	 * @return string ok
	 */
	public function addStar($stid)
	{
		$uid = RPCContext::getInstance()->getUid();
		EnStar::addNewStar($uid, $stid);
		$myStar = MyStar::getInstance($uid);
		$myStar->update();
	}

	/**
	 * 将某个名将升至多少级
	 * @param int $stid		名将模板id
	 * @param int $level   	等级
	 */
	public function setStarLevel($stid, $level)
	{
		$uid = RPCContext::getInstance()->getUid();
		// 获取当前用户的所有名将模板id
		$myStar = MyStar::getInstance($uid);
		$allStid = $myStar->getAllStarTid();
		if (!in_array($stid, $allStid))
		{
			return 'wrong stid';
		}
		$allSid = array_flip($allStid);
		$sid = $allSid[$stid];
		$levelId = btstore_get()->STAR[$stid][StarDef::STAR_LEVEL_ID];
		$favorLevel = btstore_get()->STAR_LEVEL[$levelId][StarDef::STAR_FAVOR_LEVEL];
		if (!isset($favorLevel[$level]))
		{
			return 'wrong level';
		}
		$exp = $favorLevel[$level];
		$myStar->setStarExp($sid, $exp);
		$myStar->setStarLevel($sid, $level);
		$myStar->update();
		return 'ok';
	}

	/**
	 * 将所有名将升至多少级
	 * @param unknown $level   	等级
	 */
	public function setStarsLevel($level)
	{
		$uid = RPCContext::getInstance()->getUid();
		$user = EnUser::getUserObj($uid);
		// 获取当前用户的所有名将模板id
		$myStar = MyStar::getInstance($uid);
		$allStid = $myStar->getAllStarTid();
		foreach ($allStid as $sid => $stid)
		{
			$favorAbility = btstore_get()->STAR[$stid][StarDef::STAR_FAVOR_ABILITY];
			$needLevel = $favorAbility[$level][1];
			if ($user->getLevel() < $needLevel)
			{
				return 'need user level';
			}
			$levelId = btstore_get()->STAR[$stid][StarDef::STAR_LEVEL_ID];
			$favorLevel = btstore_get()->STAR_LEVEL[$levelId][StarDef::STAR_FAVOR_LEVEL];
			$maxLevel = btstore_get()->STAR_LEVEL[$levelId][StarDef::STAR_MAX_LEVEL];
			if ($level > $maxLevel)
			{
				return 'beyond max level';
			}
			$exp = $favorLevel[$level];
			$myStar->setStarExp($sid, $exp);
			$myStar->setStarLevel($sid, $level);
		}

		$myStar->update();
		return 'ok';
	}

	public function resetDrawNum()
	{
		$uid = RPCContext::getInstance()->getUid();
		$user = EnUser::getUserObj($uid);
		$myStar = MyStar::getInstance($uid);
		$myStar->setDrawNum(0);
		$myStar->update();
		return 'ok';
	}

	public function setFeelLevel($stid, $level)
	{
		$maxLevel = StarLogic::getMaxFeelLevel($stid);
		if ($level >= $maxLevel)
		{
			return 'max-level';
		}
		$uid = RPCContext::getInstance()->getUid();
		$user = EnUser::getUserObj($uid);
		$myStar = MyStar::getInstance($uid);
		$allStid = $myStar->getAllStarTid();
		$sid = array_search($stid, $allStid);
		$myStar->setStarFeelLevel($sid, $level);
		$feelLevelConf = StarLogic::getFeelLevel($stid);
		$exp = $feelLevelConf[$level];
		$myStar->setStarFeelExp($sid, $exp);
		$skill = StarLogic::getSkillByFeelLevel($stid, $level);
		$myStar->setStarFeelSkill($sid, $skill);
		$myStar->update();
		$user->modifyBattleData();
		return 'ok';
	}

	/**
	 * **********商店系统************************
	 */

	/**
	 * 清空用户所有物品的购买次数
	 * @param int $type
	 * 3商店5军团商店6竞技场商店7神秘商店8神秘商人9兑换活动10比武商店11寻龙探宝商店
	 */
	public function resetBuyNum($type)
	{
		$uid = RPCContext::getInstance()->getUid();
		$exchangeInfo = MallDao::select($uid, $type);
		if (!empty($exchangeInfo[MallDef::ALL]))
		{
			foreach ($exchangeInfo[MallDef::ALL] as $exchangeId => $info)
			{
				$info['time'] -= SECONDS_OF_DAY;
				$exchangeInfo[MallDef::ALL][$exchangeId] = $info;
			}
			$arrField = array(
					MallDef::USER_ID => $uid,
					MallDef::MALL_TYPE => $type,
					MallDef::VA_MALL => $exchangeInfo,
			);
			MallDao::insertOrUpdate($arrField);
		}
		return 'ok';
	}


	public function resetLwShop()
	{
		$uid = RPCContext::getInstance()->getUid();
		$exchangeInfo = MallDao::select($uid, MallDef::MALL_TYPE_LORDWARSHOP);
		if (!empty($exchangeInfo[MallDef::ALL]))
		{
			$exchangeInfo[MallDef::ALL] = array();
			$arrField = array(
					MallDef::USER_ID => $uid,
					MallDef::MALL_TYPE => MallDef::MALL_TYPE_LORDWARSHOP,
					MallDef::VA_MALL => $exchangeInfo,
			);
			MallDao::insertOrUpdate($arrField);
		}
		return 'ok';
	}


	/**
	 * 商店设置积分
	 * @param int $num
	 * @return string
	 */
	public function setPoint($num)
	{
		$uid = RPCContext::getInstance()->getUid();
		ShopDao::update($uid, array('point' => $num));
		return 'ok';
	}

	/**
	 * 清空招将CD时间
	 * @return string
	 */
	public function clearCD()
	{
		$uid = RPCContext::getInstance()->getUid();
		$time = Util::getTime();
		$arrField = array(
				ShopDef::SILVER_RECRUIT_TIME => $time,
				ShopDef::GOLD_RECRUIT_TIME => $time,
		);
		ShopDao::update($uid, $arrField);
		return 'ok';
	}

	/**
	 * 重置在线奖励信息
	 */
	public function resetOnlineInfo()
	{
		$uid = RPCContext::getInstance()->getUid();
		$iniArr = OnlineLogic::initOnlineInfo( $uid );
		RPCContext::getInstance()->setSession( OnlineDef::SESSIONKEY , $iniArr);
		return $iniArr;
	}
	/**
	 * 更改在线时间，使得马上可以领奖
	 */
	public function modiOnlineTime( $step ,$seconds )
	{
		if ( $step < 0 || $seconds < 0 )
		{
			return 'shoule not be minus num';
		}
		$ret = OnlineLogic::getOnlineInfo();
		$uid = RPCContext::getInstance()->getUid();
		$field = array(
				'accumulate_time' 	=> $seconds ,
				'begin_time' 		=> Util::getTime(),
				'end_time'			=> OnlineCfg::INI_ENDTIME,
				'step'				=> $step,
		 );

		OnlineDao::update($uid, $field);
		$field[ 'uid' ] = $uid;
		RPCContext::getInstance()->setSession( OnlineDef::SESSIONKEY , $field);

		return $field;
	}

	/**
	 * 设置累计登陆了多少天
	 * @param int $days 天数
	 */
	public function signAcc( $days )
	{
		if ($days<0)
		{
			return 'invalid arg ';
		}

		$uid = RPCContext::getInstance()->getUid();
		$fields = array(
				'sign_time'	=> Util::getTime(),
				'sign_num'	=> $days,
				'va_sign'	=> array(),
		);
		SignDao::update($uid, $fields);
	}

	/**
	 * 设置连续签到的天数
	 * @param int $step 连续签到的天数
	 * @return string
	 */
	public function signNor( $step )
	{
		if ($step<0 )
		{
			return 'invalid arg ';
		}
		$maxDays =  NormalsignLogic::getMaxNormalDays();
		$step = $step > $maxDays ?$maxDays: $step;
		$uid = RPCContext::getInstance()->getUid();
		$fields = array(
				'sign_time'	=> Util::getTime() - 86400,
				'sign_num'	=> $step,
		);
		SignDao::updateNormal($uid, $fields);
	}

	public function resetSignActivityReward()
	{
		$uid = RPCContext::getInstance()->getUid();
		$info = SignactivityDao::getSignactivityInfo($uid, array('uid','va_acti_sign'));
		$info['va_acti_sign'] = array();
		SignactivityDao::update($uid, $info);
		return 'success';
	}

	public function sendReward()
	{
		$uid = RPCContext::getInstance()->getUid();
		$arrItemTpl = array(
				50001 => 2,
				610103 => 2,
		);
		$arrHeroTpl = array(
				10026 =>2,
				10063 =>1,
		);
		$arrTfTpl = array(
			5013011 =>22,
			5013012 =>33,
		);
		$reward = array(
				RewardType::GOLD => 10,
				RewardType::SILVER => 20,
				RewardType::SOUL => 30,
				RewardType::PRESTIGE => 123,
				RewardType::JEWEL => 50,
				RewardType::EXE => 20,
				RewardType::STAMINA => 22,
				RewardType::HORNOR => 33,
				RewardType::GUILD_CONTRI => 44,
				RewardType::COIN => 454,
				RewardType::GUILD_EXP => 55,
				RewardType::GRAIN => 666,
				RewardType::HELL_POINT => 23,
				RewardType::FS_EXP => 1000,
				RewardType::ARR_ITEM_ID => ItemManager::getInstance()->addItems($arrItemTpl),
				RewardType::ARR_ITEM_TPL => $arrItemTpl,
				RewardType::ARR_HERO_TPL => $arrHeroTpl,
				RewardType::ARR_TF_TPL => $arrTfTpl,
				RewardDef::EXT_DATA => array( 'rank' => 10, 'time' => 1403263232 ),
		);

		RewardLogic::sendReward($uid, RewardSource::ARENA_RANK, $reward);
		$reward[RewardType::ARR_ITEM_ID] = ItemManager::getInstance()->addItems($arrItemTpl);
		RewardLogic::sendReward($uid, RewardSource::ARENA_LUCKY, $reward);
		$reward[RewardType::ARR_ITEM_ID] = ItemManager::getInstance()->addItems($arrItemTpl);
		RewardLogic::sendReward($uid, RewardSource::DIVI_REMAIN, $reward);
		$rewardMineral = array( RewardType::SILVER => 4444 );
		RewardLogic::sendReward($uid, RewardSource::MINERAL, $rewardMineral);

		$rewardTopup = $reward;
		$rewardTopup[RewardType::ARR_ITEM_ID] = ItemManager::getInstance()->addItems($arrItemTpl);
		unset($rewardTopup[ RewardType::GOLD ]);
		RewardLogic::sendReward($uid, RewardSource::TOP_UP_FEED_BACK, $rewardTopup);
		$reward[RewardType::ARR_ITEM_ID] = ItemManager::getInstance()->addItems($arrItemTpl);
		RewardLogic::sendReward($uid, RewardSource::TEN_RECRUIT, $reward);

		$reward[RewardType::ARR_ITEM_ID] = ItemManager::getInstance()->addItems($arrItemTpl);
		$reward[RewardDef::TITLE] = '测试标题';
		$reward[RewardDef::MSG] = '测试消息123abc';

		$reward[RewardType::ARR_ITEM_ID] = ItemManager::getInstance()->addItems($arrItemTpl);
		RewardLogic::sendReward($uid, RewardSource::SYSTEM_GENERAL, $reward);
		$reward[RewardType::ARR_ITEM_ID] = ItemManager::getInstance()->addItems($arrItemTpl);
		RewardLogic::sendReward($uid, RewardSource::DAILY_TASK, $reward);
		$reward[RewardType::ARR_ITEM_ID] = ItemManager::getInstance()->addItems($arrItemTpl);
		RewardLogic::sendReward($uid, RewardSource::GROUPON, $reward);
		$reward[RewardType::ARR_ITEM_ID] = ItemManager::getInstance()->addItems($arrItemTpl);
		RewardLogic::sendReward($uid, RewardSource::OLYMPIC_NORMAL_RANK, $reward);
		$reward[RewardType::ARR_ITEM_ID] = ItemManager::getInstance()->addItems($arrItemTpl);
		RewardLogic::sendReward($uid, RewardSource::OLYMPIC_SECOND, $reward);
		$reward[RewardType::ARR_ITEM_ID] = ItemManager::getInstance()->addItems($arrItemTpl);
		RewardLogic::sendReward($uid, RewardSource::OLYMPIC_FIRST, $reward);
		$reward[RewardType::ARR_ITEM_ID] = ItemManager::getInstance()->addItems($arrItemTpl);
		RewardLogic::sendReward($uid, RewardSource::OLYMPIC_CHEER, $reward);
		$reward[RewardType::ARR_ITEM_ID] = ItemManager::getInstance()->addItems($arrItemTpl);
		RewardLogic::sendReward($uid, RewardSource::OLYMPIC_LUCKY, $reward);
		$reward[RewardType::ARR_ITEM_ID] = ItemManager::getInstance()->addItems($arrItemTpl);
		RewardLogic::sendReward($uid, RewardSource::OLYMPIC_SUPERLUCKY, $reward);
		$reward[RewardType::ARR_ITEM_ID] = ItemManager::getInstance()->addItems($arrItemTpl);
		RewardLogic::sendReward($uid, RewardSource::OLYMPIC_REWARDPOOL, $reward);
		$reward[RewardType::ARR_ITEM_ID] = ItemManager::getInstance()->addItems($arrItemTpl);
		$reward[RewardDef::EXT_DATA] = array('stageId'=>2);
		RewardLogic::sendReward($uid, RewardSource::CHARGEDART_REWARD_USER, $reward);
		$reward[RewardType::ARR_ITEM_ID] = ItemManager::getInstance()->addItems($arrItemTpl);
		RewardLogic::sendReward($uid, RewardSource::CHARGEDART_REWARD_ASSIST, $reward);
		$reward[RewardType::ARR_ITEM_ID] = ItemManager::getInstance()->addItems($arrItemTpl);
		RewardLogic::sendReward($uid, RewardSource::CHARGEDART_REWARD_ROB, $reward);
		$reward[RewardType::ARR_ITEM_ID] = ItemManager::getInstance()->addItems($arrItemTpl);
		RewardLogic::sendReward($uid, RewardSource::MONTHLY_CARD2, $reward);
		$reward[RewardType::ARR_ITEM_ID] = ItemManager::getInstance()->addItems($arrItemTpl);
		RewardLogic::sendReward($uid, RewardSource::SYSTEM_GENERAL_FOR_BACKEND, $reward);

		$reward = array(
				RewardType::COIN => 454,
				RewardType::GOLD => 10,
				RewardType::SILVER => 20,
				RewardType::ZG => 888,
				RewardType::HELL_POINT => 23,
				RewardType::TG => 999,
				RewardType::WM => 99,
				RewardType::JH => 99,
				RewardType::CROSS_HONOR => 100,
				RewardType::TALLY_POINT => 99,
				RewardDef::EXT_DATA => array( 'rank' => 16 ),
		);
		foreach( LordwarConf::$PROMOTION_REWARD_SOURCE as $field => $arrFieldValue )
		{
			foreach( $arrFieldValue as $teamType => $arrTeamTypeValue )
			{
				foreach( $arrTeamTypeValue as $index => $source )
				{
					$re = $reward;
					if( $index > 0 )
					{
						$re[RewardDef::EXT_DATA] = array();
					}
					RewardLogic::sendReward($uid, $source, $re);
				}
			}
		}
		RewardLogic::sendReward($uid, RewardSource::LORDWAR_SUPPORT_INNER, $reward);
		RewardLogic::sendReward($uid, RewardSource::LORDWAR_SUPPORT_CROSS, $reward);
		RewardLogic::sendReward($uid, RewardSource::REGRESS_ELITE, $reward);
		RewardLogic::sendReward($uid, RewardSource::REGRESS_INSISTENT, $reward);

		RewardLogic::sendReward($uid, RewardSource::PASS_RANK_REWARD, $reward);

		RewardLogic::sendReward($uid, RewardSource::GUILDWAR_RANK_FIRST, $reward);
		RewardLogic::sendReward($uid, RewardSource::GUILDWAR_RANK_NORMAL, $reward);
		RewardLogic::sendReward($uid, RewardSource::GUILDWAR_RANK_SECOND, $reward);
		RewardLogic::sendReward($uid, RewardSource::GUILDWAR_SUPPORT, $reward);
		RewardLogic::sendReward($uid, RewardSource::GUILDCOPY_RANK_REWARD, $reward);
		RewardLogic::sendReward($uid, RewardSource::ROULETTE_RANK_REWARD, $reward);
		RewardLogic::sendReward($uid, RewardSource::WORLD_PASS_RANK_REWARD, $reward);
		RewardLogic::sendReward($uid, RewardSource::WORLD_ARENA_POS_RANK_REWARD, $reward);
		RewardLogic::sendReward($uid, RewardSource::WORLD_ARENA_KILL_RANK_REWARD, $reward);
		RewardLogic::sendReward($uid, RewardSource::WORLD_ARENA_CONTI_RANK_REWARD, $reward);
		RewardLogic::sendReward($uid, RewardSource::TRAVEL_SHOP_PAY_BACK_GOLD, $reward);
		RewardLogic::sendReward($uid, RewardSource::WORLD_COMPETE_RANK_REWARD, $reward);
		
		$reward = array(
		    RewardType::HELL_TOWER => 50,
		);
		RewardLogic::sendReward($uid, RewardSource::HELL_TOWER_SWEEP_REWARD, $reward);
		
		$reward = array(
			RewardType::ARR_ITEM_TPL => array(UserConf::SILVER_ITEM_TEMPLATE => 10),
		);
		RewardLogic::sendReward($uid, RewardSource::SILVER_TRANS_2_ITEM, $reward);

	}
	
	public function sendSysReward($title, $msg)
	{
		$uid = RPCContext::getInstance()->getUid();
		$arrReward = array(RewardType::GOLD => 10);
		$reward = new Reward();
		return $reward->sendSystemReward($uid, $arrReward, $title, $msg);
	}

	public function reward()
	{
		$rewardArr = array();
		for( $i =1; $i<26 ;$i++ )
		{
			if( $i == 7 )
			{
				$rewardArr[] = array($i, 50001, 2);
				$rewardArr[] = array($i, 410001, 2);
			}
			elseif( $i == 13 )
			{
				$rewardArr[] = array($i, 10026, 2);
				$rewardArr[] = array($i, 10063, 2);
			}
			elseif( $i == 14 )
			{
				$rewardArr[] = array($i, 5013011, 2);
				$rewardArr[] = array($i, 5013012, 2);
			}
			elseif( $i == 6 || $i == 10 )
			{
				continue;
			}
			else
			{
				$rewardArr[] = array($i, 0, 2);
			}

		}

		$uid = RPCContext::getInstance()->getUid();
		$ret = RewardUtil::reward3DArr($uid, $rewardArr, RewardSource::ARENA_LUCKY, false, false );
		RewardUtil::updateReward($uid, $ret);
	}

	/**
	 * 发送资源矿邮件，发了9封不同种类的邮件
	 * @param int $receiverUid 接受邮件的uid
	 * @param int $occupyUid  任意另一个合法uid
	 */
	public function sendMineralMail( $receiverUid, $occupyUid )
	{
		$replayId = 44444;
		$silver = 1111;
		$gatherTime = 2222;
		$guildSilver=999;
		$iron=88;
		$userObj = EnUser::getUserObj( $occupyUid );
		$uname = $userObj->getUname();
		$utid = $userObj->getUtid();
		$occupier = array(
				'uid' => $occupyUid,
				'uname' => $uname,
				'utid' => $utid,
		);
		MailTemplate::sendMineralAttack($receiverUid, $occupier, $replayId, true);
		MailTemplate::sendMineralDefend($receiverUid, $occupier, $replayId, false,1,$gatherTime, $silver,$guildSilver,$iron );
		MailTemplate::sendMineralDefend( $receiverUid, $occupier, $replayId, true,1);
		MailTemplate::sendMineralDue($receiverUid, $silver, $gatherTime,$guildSilver,$iron);
		MailTemplate::sendMineralOccupyForce($receiverUid, $occupier, $replayId, false, 1,$gatherTime, $silver,$guildSilver,$iron);
		MailTemplate::sendMineralOccupyForce($receiverUid, $occupier, $replayId, true,1);
		MailTemplate::sendMineralOccupyForceAtk($receiverUid, $occupier, true);
		MailTemplate::sendMineralOccupyForceAtk($receiverUid, $occupier, false);

		MailTemplate::sendMineralHelper(36, $receiverUid, 121, 212,$occupyUid);
		MailTemplate::sendMineralHelper(37, $receiverUid, 121, 212,$occupyUid);
		MailTemplate::sendMineralHelper(38, $receiverUid, 121, 212, $occupyUid);
		MailTemplate::sendMineralHelper(39, $receiverUid, 121, 212, $occupyUid);
		MailTemplate::sendMineralHelper(40, $receiverUid, 121, 212, $occupyUid);
		MailTemplate::sendMineralOwner($receiverUid, $occupyUid, 4444);
		MailTemplate::sendMineralOneHour($receiverUid, $occupier, 500, $silver,$guildSilver,1,1,$iron );
	}

	public function sendFragseizeMail($receiverUid, $anotherUid)
	{
		$replayId = 201;
		$userObj = EnUser::getUserObj( $anotherUid );
		$uname = $userObj->getUname();
		$utid = $userObj->getUtid();
		$seizerInfo = array(
				'uid' => $anotherUid,
				'uname' => $uname,
				'utid' => $utid,
				);
		MailTemplate::sendFragseize($receiverUid, $seizerInfo, 5000011, $replayId);
	}

	public function sendArenaMail( $receiverUid, $occupyUid )
	{
		$arenaTurnNum = 2;
		$arenaPosition = 20;
		$soul = 2222;
		$silver = 3333;
		$gold = 50;
		$prestige = 123;
		$arrItemTpl = array(
				50001 => 2,
				410001 => 2,
		);
		$userObj = EnUser::getUserObj( $occupyUid );
		$uname = $userObj->getUname();
		$utid = $userObj->getUtid();
		$challenger = array(
				'uid' => $occupyUid,
				'uname' => $uname,
				'utid' => $utid,
		);

		MailTemplate::sendArenaAward($receiverUid, $arenaTurnNum, $arenaPosition, $soul, $silver, $prestige, 1403263232,$arrItemTpl);
		MailTemplate::sendArenaDefend($receiverUid, $challenger, true, $arenaPosition, 3333 );
		MailTemplate::sendArenaDefend($receiverUid, $challenger, false, $arenaPosition,3333 );
		MailTemplate::sendArenaLuckyAward($receiverUid, $arenaTurnNum, $arenaPosition, $gold );
		MailTemplate::sendArenaRankNotchange($receiverUid, $challenger, 121121);
		MailTemplate::sendArenaRankNotchange($receiverUid, $challenger, 121121, 123);
	}

	public function sendLordwarMail()
	{
		$receiveUid = RPCContext::getInstance()->getUid();
		$round = 7; $teamType = 1; $rank = 1;
		MailTemplate::sendLordwarRank($receiveUid, $round, $teamType, $rank);
		$round = 7; $teamType = 1; $rank = 2;
		MailTemplate::sendLordwarRank($receiveUid, $round, $teamType, $rank);
		$round = 7; $teamType = 1; $rank = 4;
		MailTemplate::sendLordwarRank($receiveUid, $round, $teamType, $rank);
		$round = 7; $teamType = 1; $rank = 32;
		MailTemplate::sendLordwarRank($receiveUid, $round, $teamType, $rank);

		$round = 7; $teamType = 2; $rank = 1;
		MailTemplate::sendLordwarRank($receiveUid, $round, $teamType, $rank);
		$round = 7; $teamType = 2; $rank = 2;
		MailTemplate::sendLordwarRank($receiveUid, $round, $teamType, $rank);
		$round = 7; $teamType = 2; $rank = 4;
		MailTemplate::sendLordwarRank($receiveUid, $round, $teamType, $rank);
		$round = 7; $teamType = 2; $rank = 32;
		MailTemplate::sendLordwarRank($receiveUid, $round, $teamType, $rank);

		$round = 13; $teamType = 1; $rank = 1;
		MailTemplate::sendLordwarRank($receiveUid, $round, $teamType, $rank);
		$round = 13; $teamType = 1; $rank = 2;
		MailTemplate::sendLordwarRank($receiveUid, $round, $teamType, $rank);
		$round = 13; $teamType = 1; $rank = 4;
		MailTemplate::sendLordwarRank($receiveUid, $round, $teamType, $rank);
		$round = 13; $teamType = 1; $rank = 32;
		MailTemplate::sendLordwarRank($receiveUid, $round, $teamType, $rank);

		$round = 13; $teamType = 2; $rank = 1;
		MailTemplate::sendLordwarRank($receiveUid, $round, $teamType, $rank);
		$round = 13; $teamType = 2; $rank = 2;
		MailTemplate::sendLordwarRank($receiveUid, $round, $teamType, $rank);
		$round = 13; $teamType = 2; $rank = 4;
		MailTemplate::sendLordwarRank($receiveUid, $round, $teamType, $rank);
		$round = 13; $teamType = 2; $rank = 32;
		MailTemplate::sendLordwarRank($receiveUid, $round, $teamType, $rank);

		$round = 7;
		MailTemplate::sendLordwarSupport($receiveUid, $round);
		$round = 13;
		MailTemplate::sendLordwarSupport($receiveUid, $round);
	}

	public function sendGuildwarMail()
	{
		$rewardArr = array(
				array(1,0,200000),
				array(3,0,500),
				array(7,60002,500),
				array(12,0,500),
		);

		$receiveUid = RPCContext::getInstance()->getUid();
		MailTemplate::sendGuildWarRankReward($receiveUid, 3, 8, $rewardArr);
		MailTemplate::sendGuildWarRankReward($receiveUid, 3, 4, $rewardArr);
		MailTemplate::sendGuildWarRankReward($receiveUid, 3, 2, $rewardArr);
		MailTemplate::sendGuildWarRankReward($receiveUid, 3, 1, $rewardArr);

		MailTemplate::sendGuildWarResult($receiveUid,  3, 8,true, '擦', '尼玛.');
		MailTemplate::sendGuildWarResult($receiveUid,  3, 8,false, '擦', '尼玛.');
		MailTemplate::sendGuildWarResult($receiveUid,  4, 4,true, '擦', '尼玛.');
		MailTemplate::sendGuildWarResult($receiveUid,  4, 4,false, '擦', '尼玛.');
		MailTemplate::sendGuildWarResult($receiveUid,  5, 2,false, '擦', '尼玛.');
		MailTemplate::sendGuildWarResult($receiveUid,  6, 1,true, '擦', '尼玛.');


		MailTemplate::sendGuildWarSupportReward($receiveUid, 3, $rewardArr);
		MailTemplate::sendGuildWarSupportReward($receiveUid, 4, $rewardArr);
		MailTemplate::sendGuildWarSupportReward($receiveUid, 5, $rewardArr);
		MailTemplate::sendGuildWarSupportReward($receiveUid, 6, $rewardArr);

	}

	public function sendGuildMail()
	{
		$replayId = 484848;
		$receiverUid = RPCContext::getInstance()->getUid();
		$kickerInfo = EnUser::getUserObj($receiverUid)->getTemplateUserInfo();
		$guildInfo = array(
			'guild_id' => 33333,
			'guild_name' => 'licong2',
		);
		$cityId = 11;
		$member_type = EnGuild::getMemberType($receiverUid);
		$rewardArr = array(
				array(1,0,200000),
				array(3,0,500),
				array(7,60002,500),
				array(12,0,500),
		);

		MailTemplate::sendGuildKick($receiverUid, $guildInfo, $kickerInfo);

		MailTemplate::sendGuildResponse($receiverUid, $guildInfo, true);
		MailTemplate::sendGuildResponse($receiverUid, $guildInfo, false);
		MailTemplate::sendGuildVersus($receiverUid, false, $kickerInfo, $replayId);
		MailTemplate::sendGuildVersus($receiverUid, true, $kickerInfo, $replayId);
		MailTemplate::sendCityWarReward($receiverUid, $cityId, $member_type);
	}

	public function sendOlympMail()
	{
		$receiverUid = RPCContext::getInstance()->getUid();
		$kickerInfo = EnUser::getUserObj($receiverUid)->getTemplateUserInfo();

		MailTemplate::sendOlympic( MailTemplateID::OLYMP_NORMAL_RANK , $receiverUid, 32 );
		MailTemplate::sendOlympic( MailTemplateID::OLYMP_SECOND , $receiverUid);
		MailTemplate::sendOlympic( MailTemplateID::OLYMP_FIRST, $receiverUid);
		MailTemplate::sendOlympic( MailTemplateID::OLYMP_CHEER, $receiverUid, 16);
		MailTemplate::sendOlympic( MailTemplateID::OLYMP_LUCKY, $receiverUid );
		MailTemplate::sendOlympic( MailTemplateID::OLYMP_SUPER_LUCKY, $receiverUid);
		MailTemplate::sendOlympicPoolBeCut($receiverUid, 2, 2000);
		MailTemplate::sendOlympicPoolCut($receiverUid, 2, 2000, $kickerInfo);
		MailTemplate::sendOlympicPoolParticipate($receiverUid, 2, 2000, $kickerInfo);

	}


	public function sendRobForceMail( $receiverUid, $occupyUid )
	{
		$silverNum = 500;
		$integralNum = 400;
		$rid = 201;
		$fragId = 5013013;
		$rank = 50;

		$userObj = EnUser::getUserObj( $occupyUid );
		$uname = $userObj->getUname();
		$utid = $userObj->getUtid();
		$challenger = array(
				'uid' => $occupyUid,
				'uname' => $uname,
				'utid' => $utid,
		);

		MailTemplate::sendArenaDefend($receiverUid, $challenger, true, $rank, 3333 );
		MailTemplate::sendArenaDefend($receiverUid, $challenger, false, $rank,3333 );
		MailTemplate::sendArenaDefend($receiverUid, $challenger, false, $rank,3333, 555 );

		MailTemplate::sendCompeteRob( $receiverUid, $challenger, 0, $integralNum, $rid);
		MailTemplate::sendCompeteRob($receiverUid, $challenger, $silverNum, $integralNum, $rid);

		MailTemplate::sendFragseize( $receiverUid, $challenger, 0, $rid, 555 );
		MailTemplate::sendFragseize( $receiverUid, $challenger, $fragId, $rid, 555 );
		MailTemplate::sendFragseize( $receiverUid, $challenger, $fragId, $rid, 0 );

		MailTemplate::sendCompeteRank($receiverUid, 20,30,40,50,60, array( 5013013 => 20 ));
	}

    public function sendGuildRobMail($robberGuildId, $lampGuildId )
    {
        $receiverUid = RPCContext::getInstance()->getUid();
        $guildRole = EnGuild::getMemberType($receiverUid);

        MailTemplate::guildrobNotice($robberGuildId, $lampGuildId, 300);
        MailTemplate::distributeGrain($receiverUid, $guildRole, 4848);
        MailTemplate::endGuildRobRobber($receiverUid, 'licongdoubi', 4848, 444, 5454);
        MailTemplate::endGuildRobLamp($receiverUid, 'licongdoubi', 4848);
    }

	public function sendFriendMail( $recieverUid, $anotherUid )
	{
		MailTemplate::sendFriend(FriendDef::APPLY , $anotherUid, $recieverUid, '老聪头，加人家为好友吧');
		MailTemplate::sendFriend(FriendDef::ADD , $anotherUid, $recieverUid, '');
		MailTemplate::sendFriend(FriendDef::REJECT , $anotherUid, $recieverUid, '');
		MailTemplate::sendFriend(FriendDef::DEL , $anotherUid, $recieverUid, '');
	}

	public function sendPlatformMail()
	{
		$uid = RPCContext::getInstance()->getUid();
		if( empty( $uid ) )
		{
			throw new FakeException( 'invalid uid' );
		}
		$proxy = new ServerProxy();
		$proxy->sendSysMail( $uid, 'platform', 'sended by console123你好');
	}

	public function sendCharge( $recieverUid, $chargeNum )
	{
		MailTemplate::sendCharge($recieverUid, $chargeNum);
	}

	public function buyCard($cardId)
	{
	    $uid = RPCContext::getInstance()->getUid();
	    $monthlyCardObj = MonthlyCardObj::getInstance($uid, $cardId);
        $monthlyCardObj->buyCard();
        $monthlyCardObj->save();
	}

	public function setMCardBuyTime($cardId, $date)
	{
	    $uid = RPCContext::getInstance()->getUid();
	    $date = intval( $date );
	    $dateDetail = strval( $date * 1000000 );
	    $timeStamp = strtotime( $dateDetail );
	    $cardInfo = DiscountCardDao::getCardInfo($uid, $cardId);
	    $cardInfo[DiscountCardDef::TBL_SQLFIELD_BUYTIME] = $timeStamp;
	    $duration = MonthlyCardLogic::getDuration($cardId);
	    $cardInfo[DiscountCardDef::TBL_SQLFIELD_DUETIME] = $timeStamp + $duration * SECONDS_OF_DAY - 1;
	    DiscountCardDao::saveCardInfo($cardInfo);
	}

	public function setMCardDueTime($cardId, $date)
	{
	    $uid = RPCContext::getInstance()->getUid();
	    $date = intval( $date );
	    $dateDetail = strval( $date * 1000000 );
	    $timeStamp = strtotime( $dateDetail ) + SECONDS_OF_DAY - 1;
	    $cardInfo = DiscountCardDao::getCardInfo($uid, $cardId);
	    $cardInfo[DiscountCardDef::TBL_SQLFIELD_DUETIME] = $timeStamp;
	    if($timeStamp < $cardInfo[DiscountCardDef::TBL_SQLFIELD_BUYTIME])
	    {
	        $cardInfo[DiscountCardDef::TBL_SQLFIELD_BUYTIME] = $timeStamp;
	    }
	    DiscountCardDao::saveCardInfo($cardInfo);
	}

	public function setMCardGetRewardTime($cardId, $date)
	{
	    $uid = RPCContext::getInstance()->getUid();
	    $date = intval( $date );
	    $dateDetail = strval( $date * 1000000 );
	    $timeStamp = strtotime( $dateDetail );
	    $cardInfo = DiscountCardDao::getCardInfo($uid, $cardId);
	    $cardInfo[DiscountCardDef::TBL_SQLFIELD_VAINFO][DiscountCardDef::TBL_SQLFIELD_SUBVA_MONTH]
	        [DiscountCardDef::TBL_SQLFIELD_MONTH_REWARDTIME] = $timeStamp;
	    DiscountCardDao::saveCardInfo($cardInfo);
	}

	public function setMCardGiftStatus($status)
	{
	    $uid = RPCContext::getInstance()->getUid();
	    $cardInfo = DiscountCardDao::getCardInfo($uid, 1);
	    if (empty($cardInfo))
	    {
	    	$cardInfo = DiscountCardDao::getCardInfo($uid, 2);
	    }
	    $cardInfo[DiscountCardDef::TBL_SQLFIELD_VAINFO][DiscountCardDef::TBL_SQLFIELD_SUBVA_MONTH]
	        [DiscountCardDef::TBL_SQLFIELD_MONTH_GIFTSTATUS] = $status;
	    DiscountCardDao::saveCardInfo($cardInfo);
	}

	public function sendVip( $recieverUid )
	{
		MailTemplate::sendVip( $recieverUid, 9 );

	}
	public function sendMsgHero( $htid )
	{
		$uid = RPCContext::getInstance()->getUid();
		$evolveLv = 5;
		if ( empty( $uid ) )
		{
			return 'invalid args';
		}
		$user = EnUser::getUserObj( $uid );
		if ( empty( $uid ) )
		{
			return 'no such user';
		}
		if ( $htid <= 0 )
		{
			return 'invalid arg';
		}

		$userInfo = array(
				'uid' 	=> 	$uid,
				'uname' => 	$user->getUname(),
				'utid'	=>	$user->getUtid(),
		 );
		ChatTemplate::sendHeroEvolve($userInfo, $htid, $evolveLv);

	}

	public function resetDivine()
	{

		$uid = RPCContext::getInstance()->getUid();
		if ( empty( $uid ) )
		{
			throw new FakeException( 'uid is empty' );
		}
		$diviObj = DivineObj::getInstance( $uid );
		$diviObj->initDivi( $uid );
		RPCContext::getInstance()->unsetSession( DivineDef::$DIVI_SESSION_KEY );
		$diviObj->release();
	}

	public function setDiviYesterday()
	{
		$uid = RPCContext::getInstance()->getUid();
		if ( empty( $uid ) )
		{
			throw new FakeException( 'uid is empty' );
		}
		$diviObj = DivineObj::getInstance( $uid );
		//$diviObj->initDivi( $uid );
		$diviInfo = $diviObj->getDiviInfo();
		$diviInfo['refresh_time'] = Util::getTime()-86400;
		DivineDao::updateDiviInfo($uid, $diviInfo);

		RPCContext::getInstance()->unsetSession( DivineDef::$DIVI_SESSION_KEY );
		$diviObj->release();
	}

	public function resetKeeper()
	{
		$uid = RPCContext::getInstance()->getUid();
		if ( empty( $uid ) )
		{
			throw new FakeException( 'uid is empty' );
		}
		$keeperObj = KeeperObj::getInstance( $uid );
		$keeperObj->initKeeper();
		RPCContext::getInstance()->unsetSession( KeeperDef::KEEPER_SESSION );
		$keeperObj->release();
	}

	public function setPetLevel( $petTid, $level )
	{
		$uid = RPCContext::getInstance()->getUid();
		$petid = 0;
		$allPet = PetLogic::getAllPet( $uid );
		foreach ( $allPet as $key => $petInfo )
		{
			if ( $petInfo[ 'pet_tmpl' ] == $petTid )
			{
				$petid = $key;
			}
		}

		if ( $petid == 0 )
		{
			throw new FakeException( 'no such pet: %d', $petid );
		}
		$petInfo = PetLogic::getPetInfo( $uid, $petid );

		$petLevel = $petInfo[ 'level' ];
		$conf = btstore_get()->PET;
		$maxLevel = $conf[ $petTid ]['maxLevel'];

		while( $petLevel < $level && $petLevel < $maxLevel )
		{
			PetLogic::addExpToPet($uid, $petid, 100);
			$petInfo = PetLogic::getPetInfo( $uid, $petid );
			$petLevel = $petInfo[ 'level' ];
		}

		$petMgr = PetManager::getInstance($uid);
		$petMgr->update();
	}

	public function addFriend( $offset, $limit )
	{
		if ( $limit > 100 )
		{
			return 'too much';
		}
		$data = new CData();
		$arrUid = $data->select( array( 'uid' ) )->from( 't_user' )->where( array( 'uid','>','0' ) )
		->where( 'status', '>', '0' )
		->limit( $offset , $limit )->query();

		$friend = new Friend();
		foreach ( $arrUid as $val )
		{
			try
			{
				$friend->addFriend( $val['uid'] );
			}
			catch ( Exception $e )
			{
				continue;
			}
		}
	}

	public function sendFriendPush()
	{
		$uid = RPCContext::getInstance()->getUid();
		EnFriend::loginNotify($uid);
		EnFriend::logoffNotify($uid);
	}

	public function sendApplyDays( $senderUid, $num )
	{
		$receUid = RPCContext::getInstance()->getUid();
		$curTime = Util::getTime();
		$oldestTime = Util::getTime()-MailConf::MAIL_LIFE_TIME;
		$count = 0;
		while ( $oldestTime < $curTime )
		{
			$mid = MailTemplate::sendFriend( 11, $senderUid, $receUid, '李聪李聪李聪李聪李聪');
			MailDao::updateMail($receUid, $mid, array( 'recv_time' => $oldestTime ));
			$count++;
			if ( $count > $num )
			{
				break;
			}
			$oldestTime += 86400;
		}
	}

	/**
	 * 开启某个功能之后推送的消息
	 *
	 * @param int $moduleIndex	功能节点id
	 */
	public function switchSendMsg($moduleIndex)
	{
		$uid = RPCContext::getInstance()->getUid();
		//发送消息给前端
		RPCContext::getInstance()->sendMsg(array($uid),
		PushInterfaceDef::SWITCH_ADD_NEW_SWITCH, array('switchId'=>$moduleIndex));
	}

	public function arenaSendMsg()
	{
		$uid = RPCContext::getInstance()->getUid();
		$atkedInfo = array(
				'uid',
				'position',
				'cur_suc',
				'max_suc',
				'va_opponents'
		);
		$ret = EnArena::getArrArena(array($uid), $atkedInfo);
		$atkedInfo = $ret[$uid];
		$atkedInfo['opponents'] = ArenaLogic::getOpponents($atkedInfo['va_opponents']);
		unset($atkedInfo['va_opponents']);
		RPCContext::getInstance()->sendMsg(array($uid), 're.arena.dataRefresh', $atkedInfo);

	}

	public function open()
	{
	    $reflection     =    new ReflectionClass('SwitchDef');
	    $switches    =    $reflection->getConstants();
	    $uid = RPCContext::getInstance()->getUid();
	    $switchObj = EnSwitch::getSwitchObj($uid);
	    foreach($switches as $switchName => $switchIndex)
	    {
	        $switchObj->addNewSwitch($switchIndex);
	    }
	    $switchObj->save();
	    return $switchObj->getSwitchInfo();
	}

	public function openSwitch($moduleIndex)
	{
	    $reflection     =    new ReflectionClass('SwitchDef');
	    $switches    =    $reflection->getConstants();
	    if(!in_array($moduleIndex, $switches))
	    {
	        throw new InterException('module %s is not in switch list.',$moduleIndex);
	    }
	    $switchObj   = EnSwitch::getSwitchObj();
	    if($switchObj->isSwitchOpen($moduleIndex) == TRUE)
	    {
	        return;
	    }
	    $switchObj->addNewSwitch($moduleIndex);
	    $switchObj->save();
	}

	public function openSwitchId($moduleIndex)
	{
	    $reflection     =    new ReflectionClass('SwitchDef');
	    $switches    =    $reflection->getConstants();
	    if(!in_array($moduleIndex, $switches))
	    {
	        throw new InterException('module %s is not in switch list.',$moduleIndex);
	    }
	    $switchObj   = EnSwitch::getSwitchObj();
	    $uid = RPCContext::getInstance()->getUid();
	    if($switchObj->isSwitchOpen($moduleIndex) == TRUE)
	    {
	        RPCContext::getInstance()->sendMsg(array($uid),
	                PushInterfaceDef::SWITCH_ADD_NEW_SWITCH,
	                array('newSwitchId'=>$moduleIndex));
	        return;
	    }
	    $switchObj->addNewSwitch($moduleIndex);
	    $switchObj->save();
	}

	public function resetGrowup()
	{
		$uid = RPCContext::getInstance()->getUid();
		if ( empty( $uid ) )
		{
			throw new FakeException( 'no uid' );
		}

		$arr = array(
				'activation_time' => 0,
				'va_grow_up' => array('already' => array()),
		);

		$data = new CData();
		$arrRet = $data->update( 't_growup' )-> where( array( 'uid','=', $uid ) )
		               ->set($arr)->query();
	}

	public function addTFrag( $fragId, $num )
	{
		$uid = RPCContext::getInstance()->getUid();
		if ( empty( $uid ) )
		{
			return 'invaid uid';
		}
		EnFragseize::addTreaFrag($uid, array( $fragId => $num ));
		FragseizeObj::release( $uid );
	}

	public function clearWhite( $seconds )
	{
		$uid = RPCContext::getInstance()->getUid();
		$inst = FragseizeObj::getInstance($uid);
		$whiteEndTime = $inst->getWhiteEndTime();
		$newWhiteTime = $whiteEndTime - $seconds > Util::getTime() ? $whiteEndTime - $seconds: Util::getTime();
		$inst->setWhiteTime( $newWhiteTime );
		$inst->updateSeizer();
	}

	public function lovedMe()
	{
		//让所有好友送我体力
		$uid = RPCContext::getInstance()->getUid();
		$friend = new Friend();
		$friendList = $friend->getFriendInfoList();
		$i = 0;
		foreach ( $friendList as $key => $info )
		{
			$friend->lovedByOther( $info['uid'] , $uid);
			$i++;
		}
	}

	public function resetReceiveNum()
	{
		$uid = RPCContext::getInstance()->getUid();
		$friendLoveInst =  FriendLoveObj::getInstance();
		$allLove = $friendLoveInst->getAllLove();

		$updateArr = array( 'reftime' => Util::getTime(), 'num' => 0 );

		FriendDao::updateAllLove( $uid , $updateArr);
		$friendLoveInst->release();
	}

	public function setFriendToMe()
	{
		//让别人可以送我
		$friend = new Friend();
		$uid = RPCContext::getInstance()->getUid();
		$allFriends = $friend->getFriendInfoList();
		foreach ( $allFriends as $key => $info )
		{
			$friendShip = FriendDao::getFriendship( $uid , $info['uid']);
			if ( !empty( $friendShip ) )
			{
				if ( $friendShip['uid'] == $uid )
				{
					$updateArrPositionA[] = $info['uid'];
				}
				else if ( $friendShip['fuid'] == $uid )
				{
					$updateArrPositionB[] = $info['uid'];
				}
			}
		}

		try {
			if(!empty( $updateArrPositionA ))
			{
				$wheresA = array(
						array('uid', '=', $uid),
						array('fuid','IN', $updateArrPositionA ),
						array( 'alove_time','>', 0  ),
				);
				$updateArrA = array(
						'alove_time' => 0,
				);
				FriendDao::setLoveTime($wheresA, $updateArrA);
			}
		}
		catch ( Exception $e )
		{
			Logger::info('nochange1');
		}

		try
		{
			if ( !empty( $updateArrPositionB ) )
			{
				$wheresB = array(
						array('fuid', '=', $uid),
						array('uid','IN', $updateArrPositionB ),
						array( 'blove_time','>', 0  ),
				);
				$updateArrB = array(
						'blove_time' => 0,
				);
				FriendDao::setLoveTime( $wheresB , $updateArrB);
			}
		}
		catch ( Exception $e )
		{
			Logger::info('nochange2');
		}

	}

	public function setMeToFriend()
	{
		//让我可以送别人
		$friend = new Friend();
		$uid = RPCContext::getInstance()->getUid();
		$allFriends = $friend->getFriendInfoList();
		foreach ( $allFriends as $key => $info )
		{
			$friendShip = FriendDao::getFriendship( $uid , $info['uid']);
			if ( !empty( $friendShip ) )
			{
				if ( $friendShip['uid'] == $uid )
				{
					$updateArrPositionB[] = $info['uid'];
				}
				else if ( $friendShip['fuid'] == $uid )
				{
					$updateArrPositionA[] = $info['uid'];
				}
			}
		}

		try
		{
			if(!empty( $updateArrPositionB ))
			{
				$wheresB = array(
						array('uid', '=', $uid),
						array('fuid','IN', $updateArrPositionB ),
						array( 'blove_time','>', 0  ),
				);
				$updateArrB = array(
						'blove_time' => 0,
				);
				FriendDao::setLoveTime($wheresB, $updateArrB);
			}
		}
		catch ( Exception $e )
		{
			Logger::info('nochange3');
		}
		try
		{
			if ( !empty( $updateArrPositionA ) )
			{
				$wheresA = array(
						array('fuid', '=', $uid),
						array('uid','IN', $updateArrPositionA ),
						array( 'alove_time','>', 0  ),
				);
				$updateArrA = array(
						'alove_time' => 0,
				);
				FriendDao::setLoveTime( $wheresA, $updateArrA);
			}
		}
		catch ( Exception $e )
		{
			Logger::info('nochange4');
		}

	}
	public function clearDestiny()
	{
	    $uid = RPCContext::getInstance()->getUid();
	    $destinyInfo = array(
	            DestinyDef::TBL_FIELD_UID => $uid,
	            DestinyDef::TBL_FIELD_CUR_DESTINY => 0,
	            DestinyDef::TBL_FIELD_VA_DESTINY => array()
	    );
	    DestinyDao::updateDestinyInfo($uid, $destinyInfo);
	    EnUser::getUserObj()->modifyBattleData();
	}

	public function joinGuild($guildId)
	{
		$uid = RPCContext::getInstance()->getUid();

		$member = GuildMemberObj::getInstance($uid);
	    $member->setGuildId($guildId);
	    $member->setMemberType(GuildMemberType::NONE);
	    $member->update();
	}

	public function setGJoin($secondsBefore)
	{
		$uid = RPCContext::getInstance()->getUid();
		$member = GuildMemberObj::getInstance($uid);
		$guildId = $member->getGuildId();
		if ($guildId)
		{
			$data = new CData();
			$arrCond = array(
					array(GuildDef::USER_ID, '=', $uid),
					array(GuildDef::GUILD_ID, '=', $guildId),
					array(GuildDef::RECORD_TYPE, '=', GuildRecordType::JOIN_GUILD),
			);
			$arrField = array(GuildDef::RECORD_ID);
			$arrRet = GuildDao::getRecord($arrCond, $arrField);
			$rid = $arrRet[0][GuildDef::RECORD_ID];
			$data->update(GuildDef::TABLE_GUILD_RECORD)
				 ->set(array(GuildDef::RECORD_TIME => Util::getTime() - $secondsBefore))
				 ->where(array(GuildDef::RECORD_ID, '=', $rid))
				 ->query();
		}
		return 'ok';
	}

	/**
	 * 军团设置等级
	 * @param int $type
	 * @param int $level
	 * @return string
	 */
	public function setGuildLevel($type, $level)
	{
		$uid = RPCContext::getInstance()->getUid();
		$user = EnUser::getUserObj($uid);
		$guildId = $user->getGuildId();
		if (empty($guildId))
		{
			return 'No-guild!';
		}

		$guildInfo = GuildDao::selectGuild($guildId);
		$guildLevel = $guildInfo[GuildDef::GUILD_LEVEL];
		$confname = GuildDef::$TYPE_TO_CONFNAME[$type];
		$conf = btstore_get()->$confname;
		//取各种建筑的最大等级限制等，
		switch ($type)
		{
			case GuildDef::GUILD:
				$expId = $conf[GuildDef::GUILD_EXP_ID];
				$maxLevel = $conf[GuildDef::GUILD_MAX_LEVEL];
				break;
			case GuildDef::TEMPLE:
			case GuildDef::STORE:
			case GuildDef::COPY:
			case GuildDef::TASK:
			case GuildDef::BARN:
				$expId = $conf[GuildDef::GUILD_EXP_ID];
				$maxLevel = ceil($guildLevel * $conf[GuildDef::GUILD_LEVEL_RATIO] / 100);
				break;
		}
		if ($level > $maxLevel)
		{
			return 'Max-level!';
		}

		$expTbl = btstore_get()->EXP_TBL[$expId]->toArray();
		$currLevel = GuildConf::$GUILD_BUILD_DEFAULT[$type][GuildDef::LEVEL];
		$allExp = GuildConf::$GUILD_BUILD_DEFAULT[$type][GuildDef::ALLEXP];
		if (isset($guildInfo[GuildDef::VA_INFO][$type]))
		{
			$currLevel = $guildInfo[GuildDef::VA_INFO][$type][GuildDef::LEVEL];
			$allExp = $guildInfo[GuildDef::VA_INFO][$type][GuildDef::ALLEXP];
		}
		$expTbl[0] = 0;
		$needExp = ($level == 1) ? $expTbl[$level] : ($expTbl[$level] - $expTbl[$currLevel]);
		$arrField = array();
		if ($type == 1)
		{
			$arrField[GuildDef::GUILD_LEVEL] = $level;
			$arrField[GuildDef::UPGRADE_TIME] = Util::getTime();
		}
		$arrField[GuildDef::VA_INFO] = $guildInfo[GuildDef::VA_INFO];
		$arrField[GuildDef::VA_INFO][$type][GuildDef::LEVEL] = $level;
		$arrField[GuildDef::VA_INFO][$type][GuildDef::ALLEXP] = $allExp + $needExp;
		GuildDao::updateGuild($guildId, $arrField);
		return 'ok';
	}

	public function setBarnTime($n)
	{
		$uid = RPCContext::getInstance()->getUid();
		$user = EnUser::getUserObj($uid);
		$guildId = $user->getGuildId();
		$guild = GuildObj::getInstance($guildId, array(GuildDef::SHARE_CD, GuildDef::VA_INFO));
		$guild->addBuildTime(GuildDef::BARN, 0, Util::getTime() - SECONDS_OF_DAY * $n);
		$guild->update();
		return 'ok';
	}

	/**
	 * 军团设置军团大厅贡献值
	 * @param int $num
	 */
	public function setGuildContri($num)
	{
		$uid = RPCContext::getInstance()->getUid();
		$user = EnUser::getUserObj($uid);
		$guildId = $user->getGuildId();
		if (empty($guildId))
		{
			return 'No-guild!';
		}

		$arrField = array(GuildDef::CURR_EXP => $num);
		GuildDao::updateGuild($guildId, $arrField);
		return 'ok';
	}

	/**
	 * 军团设置商品军团购买次数
	 * @param number $goodsId
	 * @return string
	 */
	public function resetGoodsSum($goodsId = 0)
	{
		$uid = RPCContext::getInstance()->getUid();
		$user = EnUser::getUserObj($uid);
		$guildId = $user->getGuildId();
		if (empty($guildId))
		{
			return 'No-guild!';
		}
		$guildInfo = GuildDao::selectGuild($guildId);
		if (!isset($guildInfo[GuildDef::VA_INFO][GuildDef::ALL][GuildDef::GOODS]))
		{
			$guildInfo[GuildDef::VA_INFO][GuildDef::ALL][GuildDef::GOODS] = array();
		}
		$goods = $guildInfo[GuildDef::VA_INFO][GuildDef::ALL][GuildDef::GOODS];
		if (!empty($goodsId))
		{
			if (!empty($goods[$goodsId][GuildDef::SUM]))
			{
				$goods[$goodsId][GuildDef::SUM] = 0;
			}
		}
		else
		{
			foreach ($goods as $goodId => $goodInfo)
			{
				$goods[$goodId][GuildDef::SUM] = 0;
			}
		}
		$arrField = array(GuildDef::VA_INFO => $guildInfo[GuildDef::VA_INFO]);
		$arrField[GuildDef::VA_INFO][GuildDef::ALL][GuildDef::GOODS] = $goods;
		GuildDao::updateGuild($guildId, $arrField);
		return 'ok';
	}

	/**
	 * 军团设置商品个人购买次数
	 * @param number $goodsId
	 * @return string
	 */
	public function resetGoodsNum($goodsId = 0)
	{
		$uid = RPCContext::getInstance()->getUid();
		$user = EnUser::getUserObj($uid);
		$guildId = $user->getGuildId();
		if (empty($guildId))
		{
			return 'No-guild!';
		}
		$data = MallDao::select($uid, MallDef::MALL_TYPE_GUILD);
		if (!empty($data))
		{
			$allList = $data[MallDef::ALL];
			if (!empty($goodsId))
			{
				if (!empty($allList[$goodsId][MallDef::NUM]))
				{
					$allList[$goodsId][MallDef::NUM] = 0;
				}
			}
			else
			{
				foreach ($allList as $goodId => $goodInfo)
				{
					$allList[$goodId][MallDef::NUM] = 0;
				}
			}
			$arrField = array(
					MallDef::USER_ID => $uid,
					MallDef::MALL_TYPE => MallDef::MALL_TYPE_GUILD,
					MallDef::VA_MALL => $data,
			);
			$arrField[MallDef::VA_MALL][MallDef::ALL] = $allList;
			MallDao::insertOrUpdate($arrField);
		}
		return 'ok';
	}

	/**
	 * 军团设置商品购买时间
	 * @param unknown $daysBefore
	 * @param number $goodsId
	 * @return string
	 */
	public function setGoodsTime($daysBefore, $goodsId = 0)
	{
		$uid = RPCContext::getInstance()->getUid();
		$user = EnUser::getUserObj($uid);
		$guildId = $user->getGuildId();
		if (empty($guildId))
		{
			return 'No-guild!';
		}
		$time = Util::getTime() - $daysBefore * 86400;
		$conf = btstore_get()->GUILD_GOODS;
		if (!empty($goodsId))
		{
			$exchangeType = $conf[$goodsId][MallDef::MALL_EXCHANGE_TYPE];
			if (GuildDef::REFRESH_EVERYDAY == $exchangeType
			|| GuildDef::REFRESH_NERVER == $exchangeType)
			{
				$guildInfo = GuildDao::selectGuild($guildId);
				if (!isset($guildInfo[GuildDef::VA_INFO][GuildDef::ALL][GuildDef::GOODS]))
				{
					$guildInfo[GuildDef::VA_INFO][GuildDef::ALL][GuildDef::GOODS] = array();
				}
				$goods = $guildInfo[GuildDef::VA_INFO][GuildDef::ALL][GuildDef::GOODS];
				if (isset($goods[$goodsId]))
				{
					$goods[$goodsId][GuildDef::TIME] = $time;
					$arrField = array(GuildDef::VA_INFO => $guildInfo[GuildDef::VA_INFO]);
					$arrField[GuildDef::VA_INFO][GuildDef::ALL][GuildDef::GOODS] = $goods;
					GuildDao::updateGuild($guildId, $arrField);
				}
			}
			$data = MallDao::select($uid, MallDef::MALL_TYPE_GUILD);
			if (!empty($data))
			{
				$allList = $data[MallDef::ALL];
				if (isset($allList[$goodsId]))
				{
					$allList[$goodsId][MallDef::TIME] = $time;
					$arrField = array(
							MallDef::USER_ID => $uid,
							MallDef::MALL_TYPE => MallDef::MALL_TYPE_GUILD,
							MallDef::VA_MALL => $data,
					);
					$arrField[MallDef::VA_MALL][MallDef::ALL] = $allList;
					MallDao::insertOrUpdate($arrField);
				}
			}
		}
		else
		{
			$guildInfo = GuildDao::selectGuild($guildId);
			if (!isset($guildInfo[GuildDef::VA_INFO][GuildDef::ALL][GuildDef::GOODS]))
			{
				$guildInfo[GuildDef::VA_INFO][GuildDef::ALL][GuildDef::GOODS] = array();
			}
			$goods = $guildInfo[GuildDef::VA_INFO][GuildDef::ALL][GuildDef::GOODS];
			foreach ($goods as $goodId => $goodInfo)
			{
				$goods[$goodId][GuildDef::TIME] = $time;
			}
			$arrField = array(GuildDef::VA_INFO => $guildInfo[GuildDef::VA_INFO]);
			$arrField[GuildDef::VA_INFO][GuildDef::ALL][GuildDef::GOODS] = $goods;
			GuildDao::updateGuild($guildId, $arrField);
			$data = MallDao::select($uid, MallDef::MALL_TYPE_GUILD);
			if (!empty($data))
			{
				$allList = $data[MallDef::ALL];
				foreach ($allList as $goodId => $goodInfo)
				{
					$allList[$goodId][MallDef::TIME] = $time;
				}
				$arrField = array(
						MallDef::USER_ID => $uid,
						MallDef::MALL_TYPE => MallDef::MALL_TYPE_GUILD,
						MallDef::VA_MALL => $data,
				);
				$arrField[MallDef::VA_MALL][MallDef::ALL] = $allList;
				MallDao::insertOrUpdate($arrField);
			}
		}
		return 'ok';
	}

	/**
	 * 军团刷新珍品列表
	 * @return string
	 */
	public function refreshGoods()
	{
		$uid = RPCContext::getInstance()->getUid();
		$user = EnUser::getUserObj($uid);
		$guildId = $user->getGuildId();
		if (empty($guildId))
		{
			return 'No-guild!';
		}
		$guildInfo = GuildDao::selectGuild($guildId);
		$arrField = array(GuildDef::VA_INFO => $guildInfo[GuildDef::VA_INFO]);
		$arrField[GuildDef::VA_INFO][GuildDef::ALL][GuildDef::REFRESH_CD] = Util::getTime()-2;
		GuildDao::updateGuild($guildId, $arrField);
		GuildShopLogic::refreshList($uid);
		return 'ok';
	}

	/**
	 * 军团设置用户贡献值
	 * @param int $num
	 */
	public function setContri($num)
	{
		$uid = RPCContext::getInstance()->getUid();
		$arrCond = array(array(GuildDef::USER_ID, '=', $uid));
		$arrField = array(GuildDef::CONTRI_POINT => $num);
		GuildDao::updateMember($arrCond, $arrField);
		return 'ok';
	}

	/**
	 * 军团重置贡献次数，并把今天的贡献时间往前移一天
	 */
	public function resetContri()
	{
		$uid = RPCContext::getInstance()->getUid();
		$arrCond = array(array(GuildDef::USER_ID, '=', $uid));
		$arrField = array(GuildDef::CONTRI_NUM => 0);
		GuildDao::updateMember($arrCond, $arrField);

		$arrType = range(1, GuildRecordType::CONTRI_EXP);
		$now = Util::getTime();
		$date = intval(strftime("%Y%m%d", $now));
		$today = strtotime($date . " " . "00:00:00");
		$arrCond = array(
				array(GuildDef::USER_ID, '=', $uid),
				array(GuildDef::RECORD_TYPE, 'in', $arrType),
				array(GuildDef::RECORD_TIME, '>=', $today)
		);
		$arrField = array('grid', GuildDef::RECORD_TIME);
		$arrRet = GuildDao::getRecord($arrCond, $arrField);
		$data = new CData();
		foreach ($arrRet as $ret)
		{
			$data->update(GuildDef::TABLE_GUILD_RECORD)
				 ->set(array(GuildDef::RECORD_TIME => $ret[GuildDef::RECORD_TIME] - 86400))
				 ->where(array('grid', '=', $ret['grid']))
				 ->query();
		}
		return 'ok';
	}

	/**
	 * 军团重置拜关公时间
	 */
	public function resetBGG()
	{
		$uid = RPCContext::getInstance()->getUid();
		$arrCond = array(array(GuildDef::USER_ID, '=', $uid));
		$arrField = array(GuildDef::REWARD_TIME => 0);
		GuildDao::updateMember($arrCond, $arrField);
		return 'ok';
	}

	/**
	 * 军团重置cd时间
	 */
	public function resetCD()
	{
		$uid = RPCContext::getInstance()->getUid();
		$arrCond = array(array(GuildDef::USER_ID, '=', $uid));
		$arrField = array(GuildDef::REJOIN_CD => 0);
		GuildDao::updateMember($arrCond, $arrField);
		return 'ok';
	}

	/**
	 * 设置所有留言为几天前
	 * @param int $day
	 */
	public function shiftMsgs($day)
	{
		$uid = RPCContext::getInstance()->getUid();
		$guildId = GuildLogic::getGuildId($uid);
		if (empty($guildId))
		{
			return 'no-guild';
		}
		$data = new CData();
		$arrRet = $data->select(array(GuildDef::RECORD_ID, GuildDef::RECORD_TIME))
					   ->from(GuildDef::TABLE_GUILD_RECORD)
					   ->where(array(GuildDef::GUILD_ID, '=', $guildId))
					   ->where(array(GuildDef::RECORD_TYPE, '=', GuildRecordType::LEAVE_MSG))
					   ->query();
		$arrRet = Util::arrayIndexCol($arrRet, GuildDef::RECORD_ID, GuildDef::RECORD_TIME);
		foreach ($arrRet as $grid => $recordTime)
		{
			$arrField = array(GuildDef::RECORD_TIME => $recordTime - $day * 86400);
			$cond = array(GuildDef::RECORD_ID, '=', $grid);
			$data->update(GuildDef::TABLE_GUILD_RECORD)->set($arrField)->where($cond)->query();
		}

		return 'ok';
	}

	public static function resetLottery()
	{
		$uid = RPCContext::getInstance()->getUid();
		$member = GuildMemberObj::getInstance($uid);
		$member->setLotteryNum(0);
		$member->update();
		return 'ok';
	}

	public static function addMerit($num)
	{
		$uid = RPCContext::getInstance()->getUid();
		$member = GuildMemberObj::getInstance($uid,array(GuildDef::MERIT_NUM));
		$member->addMeritNum($num);
		$member->update();
		return 'ok';
	}

	public static function addZg($num)
	{
		$uid = RPCContext::getInstance()->getUid();
		$member = GuildMemberObj::getInstance($uid);
		$member->addZgNum($num);
		$member->update();
		return 'ok';
	}

	public static function addGrain($num)
	{
		$uid = RPCContext::getInstance()->getUid();
		$member = GuildMemberObj::getInstance($uid,array(GuildDef::GRAIN_NUM));
		$member->addGrainNum($num);
		$member->update();
		return 'ok';
	}

	public static function addFB($num)
	{
		$uid = RPCContext::getInstance()->getUid();
		$member = GuildMemberObj::getInstance($uid);
		$guildId = $member->getGuildId();
		$guild = GuildObj::getInstance($guildId,array(GuildDef::FIGHT_BOOK));
		$guild->addFightBook($num);
		$guild->update();
		return 'ok';
	}

	public static function addGuildGrain($num)
	{
		$uid = RPCContext::getInstance()->getUid();
		$member = GuildMemberObj::getInstance($uid);
		$guildId = $member->getGuildId();
		$guild = GuildObj::getInstance($guildId,array(GuildDef::GRAIN_NUM));
		$guild->addGrainNum($num);
		$guild->update();
		return 'ok';
	}

	public static function resetShare()
	{
		$uid = RPCContext::getInstance()->getUid();
		$member = GuildMemberObj::getInstance($uid);
		$guildId = $member->getGuildId();
		$guild = GuildObj::getInstance($guildId,array(GuildDef::SHARE_CD));
		$guild->setShareCd(0);
		$guild->update();
		return 'ok';
	}

	public static function resetAttackNum()
	{
		$uid = RPCContext::getInstance()->getUid();
		$member = GuildMemberObj::getInstance($uid);
		$guildId = $member->getGuildId();
		$guild = GuildObj::getInstance($guildId);
		$guild->setAttackNum(0);
		$guild->update();
		return 'ok';
	}

	public static function resetDefendNum()
	{
		$uid = RPCContext::getInstance()->getUid();
		$member = GuildMemberObj::getInstance($uid);
		$guildId = $member->getGuildId();
		$guild = GuildObj::getInstance($guildId);
		$guild->setDefendNum(0);
		$guild->update();
		return 'ok';
	}

	public function resetGoldRfrNum()
	{
	    $userObj = EnUser::getUserObj();
	    $guildId = $userObj->getGuildId();
	    $guild = GuildObj::getInstance($guildId, array(GuildDef::RFRNUM_RFRTIME));
	    $guild->setRefreshNum(0);
	    $guild->update();
	    return 'ok';
	}

	public function resetExpRfrNum()
	{
	    $userObj = EnUser::getUserObj();
	    $guildId = $userObj->getGuildId();
	    $guild = GuildObj::getInstance($guildId, array(GuildDef::RFRNUM_RFRTIME));
	    $guild->setRefreshNumByGuildExp(0);
	    $guild->update();
	    return 'ok';
	}

	public function setHarvestNum($num)
	{
	    $userObj = EnUser::getUserObj();
	    $uid = RPCContext::getInstance()->getUid();
	    $member = GuildMemberObj::getInstance($uid);
	    $guild = GuildObj::getInstance($userObj->getGuildId());
	    $member->refreshOwn($guild->getBuildLevel(GuildDef::BARN), $num);
	    $member->update();
	    return 'ok';
	}

	public function addFieldExp($fieldId, $exp)
	{
	    $userObj = EnUser::getUserObj();
	    $guildId = $userObj->getGuildId();
	    $guild = GuildObj::getInstance($guildId,
	            array(GuildDef::VA_INFO));
	    $guild->addFieldExp($fieldId, $exp);
	    $guild->update(TRUE);
	    return 'ok';
	}

	public function resetFields()
	{
		$userObj = EnUser::getUserObj();
		$guildId = $userObj->getGuildId();
		$guild = GuildObj::getInstance($guildId,
				array(GuildDef::VA_INFO));
		$fields = $guild->getFields();
		foreach ($fields as $fieldId => $fieldInfo)
		{
			$guild->setFieldLevel($fieldId, 0);
			$guild->setFieldExp($fieldId, 0);
		}
		$guild->update();
		return 'ok';
	}

	public function shiftFields($n)
	{
		$uid = RPCContext::getInstance()->getUid();
		$member = GuildMemberObj::getInstance($uid);
		$fields = $member->getFields();
		foreach ($fields as $fieldId => $fieldInfo)
		{
			$member->setFieldTime($fieldId, Util::getTime() - SECONDS_OF_DAY * $n);
		}
		$member->update();
		return 'ok';
	}

	public static function ruinCity($cityId)
	{
		$uid = RPCContext::getInstance()->getUid();
		$data = new CData();
		$arrRet = $data->select(array(GuildDef::USER_ID))
					   ->from(GuildDef::TABLE_GUILD_MEMBER)
					   ->where(array(GuildDef::GUILD_ID, '>', 0))
					   ->limit(0, 20)
					   ->query();
		$arrMember = Util::arrayExtract($arrRet, GuildDef::USER_ID);
		$date = intval(strftime("%Y%m%d", Util::getTime()));
		$lastDate = intval(strftime("%Y%m%d", Util::getTime() - SECONDS_OF_DAY));
		$cityInfo = CityWarDao::selectCity($cityId);
		$cityInfo[CityWarDef::VA_CITY_WAR]['ruin'][$lastDate] = $arrMember;
		$arrField = array(CityWarDef::VA_CITY_WAR => $cityInfo[CityWarDef::VA_CITY_WAR]);
		CityWarDao::updateCity($cityId, $arrField);
		return 'ok';
	}

	public function setFreeCd($seconds=10)
	{
	    $shopInst = MyHeroShop::getInstance();
	    $shopInst->setFreeCd(Util::getTime()-$seconds);
	    $shopInst->save();
	}

	public function addFreeNum($num)
	{
	    $shopInst = MyHeroShop::getInstance();
	    for($i=0;$i<$num;$i++)
	    {
	        $shopInst->addFreeNum();
	    }
	    $shopInst->save();
	}

	public function setLastLoginTime($seconds, $pid = 0)
	{
		if (!empty($pid))
		{
			$users = UserLogic::getUsers($pid);
			$uid = $users[0]['uid'];
		}
		else
		{
			$uid = RPCContext::getInstance()->getUid();
		}
	    $arrField = array(
	            'last_login_time'=>Util::getTime()-$seconds,
	            );
	    UserDao::updateUser($uid, $arrField);
	}

	public function setLastOffTime($seconds, $pid = 0)
	{
		if (!empty($pid))
		{
			$users = UserLogic::getUsers($pid);
			$uid = $users[0]['uid'];
		}
		else
		{
			$uid = RPCContext::getInstance()->getUid();
		}
		$arrField = array(
				'last_logoff_time'=>Util::getTime()-$seconds,
		);
		UserDao::updateUser($uid, $arrField);
	}

	public function refreshSuperHero( $bossId )
	{
		$superHero = BossUtil::getSuperHero( $bossId );
		$bossinfo = BossDAO::getBoss( $bossId );
		$bossinfo[ BossDef::BOSS_VA ][BossDef::SUPERHERO] = $superHero;

		BossDAO::setVaBoss($bossId, $bossinfo[ BossDef::BOSS_VA ]);
		return 'ok';
	}

	public function getSpend()
	{
		$uid = RPCContext::getInstance()->getUid();
		if ( empty( $uid ) )
		{
			return 'login first';
		}

		$data = new CData();
		$ret = $data->select( array('va_user') )->from( 't_user' )->where(array('uid','=', $uid))
		->query();

		if (empty($ret))
		{
			throw new FakeException( 'no va or no such uid: %d', $uid );
		}
		if (!isset( $ret[0]['va_user']['spend_gold'] ) || empty( $ret[0]['va_user']['spend_gold'] ))
		{
			return 'spend:0 in recent 15 days';
		}

		$spendList = $ret[0]['va_user']['spend_gold'];
		$spendListStr = '';
		foreach ( $spendList as $date => $spendNum )
		{
			$spendListStr .= "$date ".'spend:'."  $spendNum \n";
		}

		return $spendListStr;
	}

	public function setMysSysRfrDate($date)
	{
	    $date = intval( $date );
	    $dateDetail = strval( $date * 1000000 );
	    $timeStamp = strtotime( $dateDetail );
	    if ( !$timeStamp )
	    {
	        return 'invalid time format, use yyyymmdd';
	    }
	    $timeNow = Util::getTime();
	    if ( $timeStamp > $timeNow )
	    {
	        return 'do not type in future time';
	    }
	    $uid = RPCContext::getInstance()->getUid();
	    $data = MallDao::select($uid, MallDef::MALL_TYPE_MYSTERY);
	    $data[MysteryShopDef::TBL_FIELD_VA_SYS_REFRTIME] = $timeStamp;
	    $arrField = array(
	            MallDef::USER_ID => $uid,
	            MallDef::MALL_TYPE => MallDef::MALL_TYPE_MYSTERY,
	            MallDef::VA_MALL => $data,
	    );
	    MallDao::insertOrUpdate($arrField);
	}

	public function setSpend( $date, $spendNum  )
	{
		$uid = RPCContext::getInstance()->getUid();
		$user= EnUser::getUserObj($uid);
		if ( empty( $uid ) )
		{
			return 'login first';
		}

		$date = intval( $date );
		$spendNum = intval( $spendNum );
		$dateDetail = strval( $date * 1000000 );
		$timeStamp = strtotime( $dateDetail );
		if ( !$timeStamp )
		{
			return 'invalid time format, use yyyymmdd';
		}
		$timeNow = Util::getTime();
		if ( $timeStamp > $timeNow )
		{
			return 'do not type in future time';
		}

		$data = new CData();
		$ret = $data->select( array('va_user') )->from( 't_user' )->where(array('uid','=', $uid))
		->query();

		if (empty($ret))
		{
			throw new FakeException( 'no va or such uid: %d', $uid );
		}

		$spendList = array();
		if ( !isset($ret[0]['va_user']['spend_gold']) || empty( $ret[0]['va_user']['spend_gold'] ) )
		{
			//没花过钱
			$spendList[$date] = $spendNum;
		}
		else
		{
			$spendList = $ret[0]['va_user']['spend_gold'];

			//要设置的这一天已经有了
			if ( isset( $spendList[$date] ) )
			{
				$spendList[$date] = $spendNum;
			}
			else
			{
				//要设的这一天没有但是保存的数量还没到上限
				if (count( $spendList) < UserConf::SPEND_GOLD_DATE_NUM )
				{
					$spendList[ $date ] = $spendNum;
				}
				else
				{
					$first = key($spendList);
					//设置的太早了
					if ( $first > $date )
					{
						return "too earlier to set spend gold, the earliest is $first";
					}
					//已经到上限了，删掉最早的一条
					unset($spendList[$first]);
					$spendList[$date] = $spendNum;
				}
				ksort( $spendList );
			}
		}

		$ret[0]['va_user']['spend_gold'] = $spendList;

		$data->update( 't_user' )->set( array('va_user' => $ret[0]['va_user']) )
		->where(array('uid','=',$uid))->query();

		RPCContext::getInstance()->getSession(UserDef::SESSION_KEY_USER );
		RPCContext::getInstance()->unsetSession( UserDef::SESSION_KEY_USER );
		return $this->getSpend();
	}

	public function signActi( $strTime, $signedDays, $clearreward = 1 )
	{
		$setTime = strtotime( $strTime );
		if ( !$setTime )
		{
			throw new FakeException( 'invalid time format, use yyyymmddhhmmss' );
		}
		if ( $setTime > Util::getTime() )
		{
			throw new FakeException( 'do not set future time' );
		}
// 		if (EnActivity::isOpen( ActivityName::SIGN_ACTIVITY ))
// 		{
// 			$actiConf = EnActivity::getConfByName( ActivityName::SIGN_ACTIVITY );

// 		}

		$inst = new Signactivity();
		$uid = RPCContext::getInstance()->getUid();
		if ( empty( $uid ) )
		{
			throw new FakeException( 'invalid uid' );
		}

		//TODO 此处写的不严谨
		$arr = array(
			'acti_sign_time'	=> $setTime ,
			'acti_sign_num' 	=> $signedDays,
			'va_acti_sign'		=> array(),
		);

	 $ret = SignactivityDao::getSignactivityInfo($uid, array( 'uid','va_acti_sign' ));
	 if ( empty( $ret ) )
	 {
	 	$arr['uid'] = $uid;
	 	SignactivityDao::insert($uid, $arr);
	 }
	 else
	 {
	 	if ($clearreward == 0)
	 	{
	 		unset( $arr['va_acti_sign'] );
	 	}
	 	SignactivityDao::update($uid, $arr);
	 }

	}


	public function setRobFreeNum($num)
	{
	    $num = intval($num);
	    $robInfo = MyRobTomb::getInstance()->getRobInfo();
	    $robInfo[RobTombDef::SQL_TODAY_FREE_NUM] = $num;
	    $uid = RPCContext::getInstance()->getUid();
	    RobTombDao::updateRobInfo($uid, $robInfo);
	    return 'ok';
	}

	public function setTombRfrTime($date)
	{
	    $date = intval( $date );
	    $dateDetail = strval( $date * 1000000 );
	    $timeStamp = strtotime( $dateDetail );
	    if ( !$timeStamp )
	    {
	        return 'invalid time format, use yyyymmdd';
	    }
	    if($timeStamp > Util::getTime())
	    {
	        return 'invalid time. larger than now.set date is '.$date;
	    }
	    $robInfo = MyRobTomb::getInstance()->getRobInfo();
	    $robInfo[RobTombDef::SQL_LAST_RFR_TIME] = $timeStamp;
	    $uid = RPCContext::getInstance()->getUid();
	    RobTombDao::updateRobInfo($uid, $robInfo);
	    return 'ok';
	}

	public function setRobGoldNum($num)
	{
	    $num = intval($num);
	    $robInfo = MyRobTomb::getInstance()->getRobInfo();
	    $robInfo[RobTombDef::SQL_TODAY_GOLD_NUM] = $num;
	    $uid = RPCContext::getInstance()->getUid();
	    RobTombDao::updateRobInfo($uid, $robInfo);
	    return 'ok';
	}

	public function resetRobNum()
	{
	    $robInfo = MyRobTomb::getInstance()->getRobInfo();
	    $robInfo[RobTombDef::SQL_TODAY_GOLD_NUM] = 0;
	    $robInfo[RobTombDef::SQL_TODAY_FREE_NUM] = 0;
	    $uid = RPCContext::getInstance()->getUid();
	    RobTombDao::updateRobInfo($uid, $robInfo);
	    return 'ok';
	}

	public function getTeamAtkInfo($win=1)
	{
	    $uid = RPCContext::getInstance()->getUid();
	    $copyId = CopyTeamLogic::getFirstGuildCopy();
	    $max_member_num = btstore_get()->COPYTEAM[$copyId]['max_member_num'];
	    $teamList[] = $uid;
        $teamList = array();
	    $data = new CData();
	    $ret = $data->select(array('uid'))
	                ->from('t_user')
	                ->where(array('uid','>',20000))
	                ->orderBy('fight_force', !($win))
	                ->limit(0, 10)
	                ->query();
	    foreach($ret as $index => $userInfo)
	    {
	        if($userInfo['uid'] != $uid)
	        {
	            $teamList[] = $userInfo['uid'];
	        }
	        if(count($teamList) >= $max_member_num)
	        {
	            break;
	        }
	    }
	    CopyTeamLogic::startTeamAtk($copyId, $teamList, $uid);
	}


	public function getTeamPVPInfo($win=1)
	{
	    $teamList1 = array();
	    $teamList2 = array();
	    $data = new CData();
	    $max_member_num = 5;
	    $ret = $data->select(array('uid'))
            	    ->from('t_user')
            	    ->where(array('uid','>',20000))
            	    ->orderBy('fight_force', !($win))
            	    ->limit(0, 20)
            	    ->query();
	    foreach($ret as $index => $userInfo)
	    {
	         $teamList1[] = $userInfo['uid'];
	        if(count($teamList1) >= $max_member_num)
	        {
	            break;
	        }
	    }
	    foreach($ret as $index => $userInfo)
	    {
	        if(in_array($userInfo['uid'], $teamList1))
	        {
	            continue;
	        }
	        $teamList2[] = $userInfo['uid'];
	        if(count($teamList2) >= $max_member_num)
	        {
	            break;
	        }
	    }
	    $userFmt1 = array();
	    $userFmt2 = array();
	    foreach($teamList1 as $uid)
	    {
	        $userFmt1['members'][] = EnUser::getUserObj($uid)->getBattleFormation();
	    }
	    $userFmt1['members'][0]['maxWin'] = 15;

	    $teamLeader = EnUser::getUserObj($teamList1[0]);
	    $userFmt1['name'] = $teamLeader->getUname();
	    $userFmt1['level'] = $teamLeader->getLevel();
	    foreach($teamList2 as $uid)
	    {
	        $userFmt2['members'][] = EnUser::getUserObj($uid)->getBattleFormation();
	    }
	    $teamLeader = EnUser::getUserObj($teamList2[0]);
	    $userFmt2['name'] = $teamLeader->getUname();
	    $userFmt2['level'] = $teamLeader->getLevel();
	    $arrExtra = array(
        					'arrNeedResult'=> array(
        					        'simpleRecord' => self::getRoundNumPerHit(),
        					        'saveSimpleRecord' => 0),
        					'mainType' => BattleType::COPY_TEAM,
                        );
	    $atkRet = EnBattle::doMultiHero($userFmt1, $userFmt2, 3 ,3 ,$arrExtra);
	    $uid = RPCContext::getInstance()->getSession(UserDef::SESSION_KEY_UID);
	    unset($atkRet['client']);
	    RPCContext::getInstance()->sendMsg(array($uid),
	            PushInterfaceDef::COPY_TEAM_ATK_RESULT, $atkRet);
	    return $atkRet;
	}

	public static function startTeamAtk($copyId,$teamList)
	{
	    $minNum = btstore_get()->COPYTEAM[$copyId]['min_member_num'];
	    $maxNum = btstore_get()->COPYTEAM[$copyId]['max_member_num'];
	    if(count($teamList) < $minNum || (count($teamList) > $maxNum))
	    {
	        throw new FakeException('copy %d min_member_num %d max_member_num %d.teamlist is %s.',
	                $copyId,$minNum,$maxNum,$teamList);
	    }
	    $baseId = btstore_get()->COPYTEAM[$copyId]['base_id'];
	    $maxWin = btstore_get()->COPYTEAM[$copyId]['max_win_num'];
	    $armyList = CopyUtil::getArmyInBase($baseId);
	    //准备上场成员
	    $userFmt = array();
	    $enemyFmt = array();
	    foreach($teamList as $uid)
	    {
	        $userFmt['members'][] = EnUser::getUserObj($uid)->getBattleFormation();
	    }
	    foreach($armyList as $index => $armyId)
	    {
	        $enemyFmt['members'][$index] = EnFormation::getMonsterBattleFormation($armyId,BaseLevel::NORMAL);
	        $enemyFmt['members'][$index]['uid'] = $index+1;
	    }
	    $teamLeader = EnUser::getUserObj($teamList[0]);
	    $userFmt['name'] = $teamLeader->getUname();
	    $userFmt['level'] = $teamLeader->getLevel();
	    $enemyFmt['name'] = $copyId;
	    $enemyFmt['level'] = btstore_get()->COPYTEAM[$copyId]['level'];
	    Logger::trace('before doMultiHero.userfmt %s.enemyfmt %s,maxwin %d',$teamList,$armyList,$maxWin);
	    $arrExtra = array(
        					'arrNeedResult'=> array(
        					        'simpleRecord' => self::getRoundNumPerHit(),
        					        'saveSimpleRecord' => 0),
        					'mainType' => BattleType::COPY_TEAM,
                        );
	    $atkRet = EnBattle::doMultiHero($userFmt, $enemyFmt,BattleConf::MAX_ARENA_COUNT, $maxWin,$arrExtra);
	    return $atkRet;
	}

	public function passGuildCopy($copyId)
	{
	    $uid = RPCContext::getInstance()->getUid();
	    $userTeamInfo = new UserGuildTeamInfo($uid);
	    if($copyId == 0)
	    {
	        $userTeamInfo->setCurGuildCopy(CopyTeamLogic::getFirstGuildCopy());
	        $userTeamInfo->setCurPassedGuildCopy(0);
	    }
	    else
	    {
	        $nextCopy = CopyTeamLogic::getNextTeamCopy($copyId);
	        if(empty($nextCopy))
	        {
	            $nextCopy = $copyId;
	        }
	        $userTeamInfo->setCurGuildCopy($nextCopy);
	        $userTeamInfo->setCurPassedGuildCopy($copyId);
	    }
	    $userTeamInfo->saveUserTeamInfo();
	    return $userTeamInfo->getCurGuildCopy();
	}

	public function setGuildCanAtkNum($num)
	{
	    $numLimit = CopyTeamLogic::getGuildAtkNumLimit();
	    if($num > $numLimit)
	    {
	        $num = $numLimit;
	    }
	    $userTeam = new UserGuildTeamInfo(0);
	    $userTeam->setGuildCanAtkNum($num);
	    $userTeam->saveUserTeamInfo();
	}

	public function setGuildCopyRfrDate($date)
	{
	    $date = intval( $date );
	    $dateDetail = strval( $date * 1000000 );
	    $timeStamp = strtotime( $dateDetail );
	    if ( !$timeStamp )
	    {
	        return 'invalid time format, use yyyymmdd';
	    }
	    if($timeStamp > Util::getTime())
	    {
	        return 'invalid time. larger than now.set date is '.$date;
	    }
	    $uid = RPCContext::getInstance()->getUid();
	    $guildTeamInfo = array(
	            CopyTeamDef::COPYTEAM_SQLFIELD_GUILDRFRTIME => $timeStamp
	            );
	    CopyTeamDao::updateCopyTeamInfo($uid, $guildTeamInfo);
	    return 'ok';
	}

	public function setLLTime()
	{
		$uid = RPCContext::getInstance()->getUid();
		$user = EnUser::getUserObj();
		$time = Util::getTime()-86400;
		$updateArr = array(
			'last_login_time' => $time,
			'last_logoff_time' =>$time + 11,
		);

		$lastLoginTime = $user->getLastLoginTime();
		if (Util::isSameDay( $lastLoginTime ))
		{
			UserDao::updateUser($uid, $updateArr);

			$rewardUpArr = array(
					RewardDef::SQL_SEND_TIME => $time,
			);

			$data = new CData();
			$data-> update('t_reward')->set( $rewardUpArr )-> where( array('uid','=',$uid) )
			->where(RewardDef::SQL_SOURCE,'=',RewardSource::VIP_DAILY_BONUS)->query();
		}

	}

	public function resetDayTask()
	{
		$uid = RPCContext::getInstance()->getUid();
		$arrField = array(
				ActiveDef::UID => $uid,
				ActiveDef::POINT => 0,
				ActiveDef::UPDATE_TIME => 0,
				ActiveDef::VA_ACTIVE => array(),
		);
		ActiveDao::insertOrUpdate($arrField);
	}

	public function setDPoint($point)
	{
		$uid = RPCContext::getInstance()->getUid();
		$active = ActiveDao::select($uid);
		$active[ActiveDef::POINT] = $point;
		$active[ActiveDef::UPDATE_TIME] = Util::getTime();
		ActiveDao::insertOrUpdate($active);
	}

	public function setDTaskNum($taskId, $finishNum)
	{
		$uid = RPCContext::getInstance()->getUid();
		$active = ActiveDao::select($uid);
		if (false == $active)
		{
			$active = array(
					ActiveDef::UID => $uid,
					ActiveDef::POINT => 0,
					ActiveDef::LAST_POINT => 0,
					ActiveDef::UPDATE_TIME => 0,
					ActiveDef::VA_ACTIVE => array(),
			);
		}
		$active[ActiveDef::VA_ACTIVE][ActiveDef::TASK][$taskId] = $finishNum;
		$active[ActiveDef::UPDATE_TIME] = Util::getTime();
		ActiveDao::insertOrUpdate($active);
		return 'ok';
	}

	public function setDTime()
	{
		$uid = RPCContext::getInstance()->getUid();
		$active = ActiveDao::select($uid);
		$active[ActiveDef::UPDATE_TIME] = 1;
		ActiveDao::insertOrUpdate($active);
	}

	public function setGCopyRfrTime($date)
	{
	    $uid = RPCContext::getInstance()->getUid();
	    $date = intval( $date );
	    $dateDetail = strval( $date * 1000000 );
	    $timeStamp = strtotime( $dateDetail );
	    $arrField = array(
	            CopyTeamDef::COPYTEAM_SQLFIELD_GUILDRFRTIME => $timeStamp
	            );
	    CopyTeamDao::updateCopyTeamInfo($uid, $arrField);
	}

	public function setTCopyRfrTime($date)
	{
	    $uid = RPCContext::getInstance()->getUid();
	    $date = intval( $date );
	    $dateDetail = strval( $date * 1000000 );
	    $timeStamp = strtotime( $dateDetail );
	    $userTeamObj = new UserGuildTeamInfo($uid);
	    $info = $userTeamObj->getUserTeamInfo();
	    $info[CopyTeamDef::COPYTEAM_SQLFIELD_GUILDRFRTIME] = $timeStamp;
	    CopyTeamDao::updateCopyTeamInfo($uid, $info);
	}

	public function kaPoints()
	{
		EnWeal::addKaPoints(KaDef::BOSS,500);
		return 'ok';
	}

	public function setKaInfoDay($day=0)
	{
	    $time = Util::getTime() - $day * SECONDS_OF_DAY;

	    $uid = RPCContext::getInstance()->getUid();

	    $kaObj = KaObj::getInstance();
	    $kaInfo = $kaObj->getKaInfo();
	    $kaInfo['refresh_time'] = $time;

	    $updateArr = array(
	        'refresh_time' => $time,
	    );

	    KaDao::updateKaInfo($uid, $updateArr);

	    RPCContext::getInstance()->setSession( 'ka.info' , $kaInfo);

	    return 'ok';
	}

	public function addPet( $petTmpl )
	{
		EnPet::addPet(array( $petTmpl => 1 ));
	}

    public function resetBonus()
    {
    	$vipBonus = BonusManager::getInstance();
    	$vipBonus->setBonusReceTime(0);
    	$vipBonus->update();
    	return 'ok';
    }

    public function resetVipWeekGift()
    {
    	$vipBonus = BonusManager::getInstance();
    	foreach ($vipBonus->getWeekGift() as $vip => $time)
    	{
    		$vipBonus->delWeekGift($vip);
    	}
    	$vipBonus->update();
    	return 'ok';
    }

    public function setLQcTime()
    {
        $uid = RPCContext::getInstance()->getUid();
        //更新用户部分数据
        $arrField = array(
            GuildDef::PLAYWITH_TIME => 0,
            GuildDef::PLAYWITH_NUM => 0,
            GuildDef::BE_PLAYWITH_NUM => 0,
        );
        //where条件
        $arrCond = array(
            array(GuildDef::USER_ID, '=', $uid),
        );
        $ret = GuildDao::updateMember($arrCond, $arrField);
        return $ret;
    }

    public function setMerchantTime($leftTime)
    {
    	$uid = RPCContext::getInstance()->getUid();
    	$data = MallDao::select($uid, MallDef::MALL_TYPE_MYSMERCHANT);

    	if( empty($data) )
    	{
    		EnMysMerchant::trigMysMerchant($uid);
    		$data = MallDao::select($uid, MallDef::MALL_TYPE_MYSMERCHANT);
    	}

    	$data[MysMerchantDef::TBL_FIELD_VA_MERCHANT_END_TIME] = Util::getTime() + $leftTime;


    	$arrField = array(
    			MallDef::USER_ID => $uid,
    			MallDef::MALL_TYPE => MallDef::MALL_TYPE_MYSMERCHANT,
    			MallDef::VA_MALL => $data,
    	);
    	MallDao::insertOrUpdate($arrField);

    	return date('Y-m-d H:i:s', $data[MysMerchantDef::TBL_FIELD_VA_MERCHANT_END_TIME]);
    }

    public function setMerRefTime($leftTime)
    {
        $uid = RPCContext::getInstance()->getUid();
        $data = MallDao::select($uid, MallDef::MALL_TYPE_MYSMERCHANT);

        if(empty($data))
        {
            EnMysMerchant::trigMysMerchant($uid);
            $data = MallDao::select($uid, MallDef::MALL_TYPE_MYSMERCHANT);
        }

        $data[MysMerchantDef::TBL_FIELD_VA_SYS_REFRTIME] = Util::getTime() + $leftTime;

        $arrayField = array(MallDef::USER_ID => $uid,
            MallDef::MALL_TYPE => MallDef::MALL_TYPE_MYSMERCHANT,
            MallDef::VA_MALL => $data);
        MallDao::insertOrUpdate($arrayField);

        return date('Y-m-d H:i:s', $data[MysMerchantDef::TBL_FIELD_VA_SYS_REFRTIME]);
    }

    public function clrMerRefNum()
    {
        $uid = RPCContext::getInstance()->getUid();
        $data = MallDao::select($uid, MallDef::MALL_TYPE_MYSMERCHANT);

        if(empty($data))
        {
            EnMysMerchant::trigMysMerchant($uid);
            $data = MallDao::select($uid, MallDef::MALL_TYPE_MYSMERCHANT);
        }

        $data[MysMerchantDef::TBL_FIELD_VA_SYS_REFRTIME] = 0;
        $data[MysMerchantDef::TBL_FIELD_VA_REFRNUM_BYPLAYER] = 0;

        $arrayField = array(MallDef::USER_ID => $uid,
            MallDef::MALL_TYPE => MallDef::MALL_TYPE_MYSMERCHANT,
            MallDef::VA_MALL => $data);
        MallDao::insertOrUpdate($arrayField);

        return date('Y-m-d H:i:s', $data[MysMerchantDef::TBL_FIELD_VA_SYS_REFRTIME]);
    }

    public function closeMerchant()
    {
        $uid = RPCContext::getInstance()->getUid();
        $data = MallDao::select($uid, MallDef::MALL_TYPE_MYSMERCHANT);
        if(empty($data))
        {
            return;
        }
        $data[MysMerchantDef::TBL_FIELD_VA_MERCHANT_END_TIME] = 0;
        $arrField = array(
            MallDef::USER_ID => $uid,
            MallDef::MALL_TYPE => MallDef::MALL_TYPE_MYSMERCHANT,
            MallDef::VA_MALL => $data,
        );
        MallDao::insertOrUpdate($arrField);
    }

    public function getAllPet()
    {
        $uid = RPCContext::getInstance()->getUid();
        $userObj = EnUser::getUserObj($uid);
        $uname = $userObj->getUname();

        $petsInfo = PetLogic::getAllPet($uid);
        $keeperInfo = PetLogic::getKeeperInfo($uid);

        $msg = sprintf("uid:%d, uname:%s:\n", $uid, $uname);
        foreach($petsInfo as $petId => $petInfo)
        {
            $petTplId = $petInfo['pet_tmpl'];
            $petLevel = $petInfo['level'];
            $petExp = $petInfo['exp'];
            $skillPoint = $petInfo['skill_point'];
            $swallow = $petInfo['swallow'];

            $ifFight = 0;
            foreach($keeperInfo['va_keeper']['setpet'] as $onePetKeeper)
            {
                if($onePetKeeper['petid'] == $petId && isset($onePetKeeper['status']) && $onePetKeeper['status'] == 1)
                {
                    $ifFight = 1;
                }
            }

            $msg .= sprintf("petId:%d petTplId:%d petLevel:%d petExp:%d skillPoint:%d swallow:%d ifFight:%d \n",
                $petId, $petTplId, $petLevel, $petExp, $skillPoint, $swallow, $ifFight);
        }

        return $msg;

    }

    public function setFailNum($petId, $failNum)
    {
        $uid = RPCContext::getInstance()->getUid();
        $petMgr = PetManager::getInstance($uid);
        $petMgr->setFailNum($petId, $failNum);
        $petMgr->update();
    }

    public function petNormalSkill($posInSquand, $skillId )
    {
    	$petSkillConf = btstore_get()->PETSKILL;
    	$petConf = btstore_get()->PET;
    	if ( !isset( $petSkillConf[$skillId] )|| $petSkillConf[$skillId]['skillType']!= 1 )
    	{
    		echo "no normal skill id: $skillId baby";
    		return;
    	}
    	$uid = RPCContext::getInstance()->getUid();
    	$keeperInst = KeeperObj::getInstance($uid);
    	$vaKeeper = $keeperInst->getVaKeeper();
    	if ( empty( $vaKeeper['setpet'][$posInSquand]['petid'] ) )
    	{
    		echo "no pet in pos: $posInSquand";
    		return;
    	}
    	$petId = $vaKeeper['setpet'][$posInSquand]['petid'];
    	$petMgr = PetManager::getInstance($uid);
    	$petInfo = $petMgr->getOnePetInfo($petId);
    	if ( $petInfo[PetDef::SKILLPOINT ] < 1 )
    	{
    		echo "no skill point";
    		return;
    	}
    	Logger::debug('6================');
    	$petTmpl = $petInfo[PetDef::PETTMPL];
    	$normalSkill = $petInfo[PetDef::VAPET]['skillNormal'];

    	$petMgr->subSkillPoint( $petId );
    	foreach ( $normalSkill as $pos => $skillInfo )
    	{
    		if ( $petId == $skillInfo['id'] )
    		{
    			echo "already has";
    			return;
    		}
    	}
    	$added = false;
    	foreach ( $normalSkill as $pos => $skillInfo )
    	{
    		if ( empty( $skillInfo['id'] ) )
    		{
    			$petMgr->addNewNormalSkill($petId, $pos, $skillId);
    			$added = true;
    			break;
    		}
    	}
    	if ( !$added )
    	{
    		$posCountLimit = $petConf[$petTmpl]['skillSlotLimit'];
    		if ( count($normalSkill) < $posCountLimit )
    		{
    			$petMgr->openSkillSlot($petId);
    			$petMgr->addNewNormalSkill($petId, count($normalSkill), $skillId);
    		}
    		else
    		{
    			echo "skill slot reach max";
    			return;
    		}
    	}
    	Logger::debug('7================');
    	$allPet = $petMgr->getAllPet();
    	Logger::debug('allpet in console: %s', $allPet);
    	$petMgr->update();
    }
    public function petTalentSkill($posInSquand, $skillId, $lv )
    {
    	if ( $lv <= 0 )
    	{
    		echo "lv <= 0";
    		return;
    	}
    	$petSkillConf = btstore_get()->PETSKILL;
    	$petConf = btstore_get()->PET;
    	if ( !isset( $petSkillConf[$skillId] )|| $petSkillConf[$skillId]['skillType']!= 1 )
    	{
    		echo "no talent skill id: $skillId baby";
    		return;
    	}
    	$uid = RPCContext::getInstance()->getUid();
    	$keeperInst = KeeperObj::getInstance($uid);
    	$vaKeeper = $keeperInst->getVaKeeper();
    	if ( empty( $vaKeeper['setpet'][$posInSquand]['petid'] ) )
    	{
    			echo "no pet in pos: $posInSquand";
    			return;
    	}
    	Logger::debug('10================');
    	$petId = $vaKeeper['setpet'][$posInSquand]['petid'];
    	$petMgr = PetManager::getInstance($uid);
    	$petInfo = $petMgr->getOnePetInfo($petId);
    	$petInfo[PetDef::VAPET]['skillTalent'][0] = array('id' => $skillId,'level' => $lv);
    	PetDAO::updatePet($petId, $petInfo);
    	Logger::debug('11================');
    	Logger::debug('petInfo in console: %s', $petInfo);
    	$petMgr->release();
    	RPCContext::getInstance()->unsetSession( PetDef::PET_SESSION );
    }

    public function petProductSkill($posInSquand, $skillId, $lv )
    {
    	if ( $lv <= 0 )
    	{
    		echo "lv <= 0";
    		return;
    	}
    	$petSkillConf = btstore_get()->PETSKILL;
    	$petConf = btstore_get()->PET;
    	if ( !isset( $petSkillConf[$skillId] )|| $petSkillConf[$skillId]['skillType']!= 2 )
    	{
    		echo "no product skill id: $skillId baby";
    		return;
    	}
    	$uid = RPCContext::getInstance()->getUid();
    	$keeperInst = KeeperObj::getInstance($uid);
    	$vaKeeper = $keeperInst->getVaKeeper();
    	if ( empty( $vaKeeper['setpet'][$posInSquand]['petid'] ) )
    	{
    			echo "no pet in pos: $posInSquand";
    			return;
    	}
    	$petId = $vaKeeper['setpet'][$posInSquand]['petid'];
    	$petMgr = PetManager::getInstance($uid);
    	$petInfo = $petMgr->getOnePetInfo($petId);
    	$petInfo[PetDef::VAPET]['skillProduct'][0] = array('id' => $skillId,'level' => $lv);
    	PetDAO::updatePet($petId, $petInfo);
    	$petMgr->release();
    	RPCContext::getInstance()->unsetSession( PetDef::PET_SESSION );
    }

    public function setPetNormalSkillLevel($petId,$level)
    {
        $uid = RPCContext::getInstance()->getUid();
        $petMgr = PetManager::getInstance($uid);
        $petInfo = $petMgr->getOnePetInfo($petId);
        $SkillNormall = $petInfo[PetDef::VAPET]['skillNormal'];

        foreach ($SkillNormall as $index => $eachSkill)
        {
            if (empty($eachSkill['id'])) continue;
            $SkillNormall[$index]['level'] = intval($level);
        }

        $petInfo[PetDef::VAPET]['skillNormal'] = $SkillNormall;
        $set = array(PetDef::VAPET => $petInfo[PetDef::VAPET]);
        PetDAO::updatePet($petId, $set);

        return 'ok';
    }

    public function collectPetAllProduction()
    {
        $uid = RPCContext::getInstance()->getUid();

        return PetLogic::collectAllProduction($uid);
    }

    public function productTime( $posInSquand, $sec )
    {
    	$sec = intval( $sec );
    	$uid = RPCContext::getInstance()->getUid();
    	$keeperInst = KeeperObj::getInstance($uid);
    	$vaKeeper = $keeperInst->getVaKeeper();
    	if ( empty( $vaKeeper[ 'setpet' ][$posInSquand] ) )
    	{
    		return "no pet in this pos";
    	}

    	$keeperInst->setProductTime($posInSquand, Util::getTime()-$sec);
    	$keeperInst->update();
    	$keeperInst->release();
    	RPCContext::getInstance()->unsetSession( KeeperDef::KEEPER_SESSION );
    }

    public function setHCopyPassNum($copyId, $level, $num = 1)
    {
    	$uid = RPCContext::getInstance()->getUid();
    	EnHCopy::setHCopyPassNum($uid, $copyId, $level, $num);
    }

    public function addTalentToAll($talentId,$talentIndex)
    {
        if(!empty($talentId) && !isset(btstore_get()->HEROTALENT[$talentId]))
        {
            throw new FakeException('no such talentid %d',$talentId);
        }
        $uid = RPCContext::getInstance()->getUid();
        $fmtObj = EnFormation::getFormationObj($uid);
        $fmt = $fmtObj->getFormation();
        $heroMng = EnUser::getUserObj()->getHeroManager();
        foreach($fmt as $index => $hid)
        {
            $heroObj = $heroMng->getHeroObj($hid);
            $heroObj->addConfirmedTalent($talentIndex,$talentId);
        }
        EnUser::getUserObj()->modifyBattleData();
        EnUser::getUserObj()->update();
    }

    public function addTalentToMaster($talentId,$index)
    {
        if(!empty($talentId) && !isset(btstore_get()->HEROTALENT[$talentId]))
        {
            throw new FakeException('no such talentid %d',$talentId);
        }
        $userObj = EnUser::getUserObj();
        $heroMng = $userObj->getHeroManager();
        $masterHero = $heroMng->getHeroObj($userObj->getMasterHid());
        $masterHero->addConfirmedTalent($index, $talentId);
        $masterHero->confirmTalent();
        $userObj->modifyBattleData();
        $userObj->update();
    }

    public function addTalentToHtid($htid,$talentId,$talentIndex)
    {
        if(!empty($talentId) && !isset(btstore_get()->HEROTALENT[$talentId]))
        {
            throw new FakeException('no such talentid %d',$talentId);
        }
        $userObj = EnUser::getUserObj();
        $heroMng = $userObj->getHeroManager();
        $allHeroObj = $heroMng->getAllHeroObj();
        foreach($allHeroObj as $hid => $hero)
        {
            $heroObj = $heroMng->getHeroObj($hid);
            if($heroObj->getHtid() != $htid)
            {
                continue;
            }
            $heroObj->addConfirmedTalent($talentIndex,$talentId);
        }
        $userObj->modifyBattleData();
        $userObj->update();
    }

    public function clearSquadDestiny()
    {
    	$uid = RPCContext::getInstance()->getUid();
    	$user = EnUser::getUserObj($uid);
    	$arrHid = EnFormation::getArrHidInAll($uid);
    	$arrHero = $user->getHeroManager()->getArrHeroObj($arrHid);
    	foreach ($arrHero as $hero)
    	{
    		$hero->setDestiny(0);
    	}
    	$user->update();
    	return 'ok';
    }

    public function unsetHeroDestiny()
    {
    	$uid = RPCContext::getInstance()->getUid();
    	$user = EnUser::getUserObj($uid);
    	$allHero = $user->getHeroManager()->getAllHeroObj();
    	foreach ($allHero as $hero)
    	{
    		$hid = $hero->getHid();
    		$info = HeroDao::getByHid($hid, array('va_hero'));
    		if (!empty($info))
    		{
    			unset($info['va_hero']['destiny']);
    			HeroDao::update($hid, $info);
    		}
    	}

    	return 'ok';
    }

    public function refreshDiviPrize()
    {
    	$uid = RPCContext::getInstance()->getUid();
    	$diviInst = DivineObj::getInstance($uid);
    	$ret = $diviInst->getDiviInfo();
    	$ret['refresh_time'] = Util::getTime()- 86401;
    	DivineDao::updateDiviInfo($uid, $ret);
    	$diviInst->release();
    	RPCContext::getInstance()->unsetSession(DivineDef::$DIVI_SESSION_KEY );
    }

    public function resetFriendPk()
    {
    	$uid = RPCContext::getInstance()->getUid();
    	$updateArr = array(
    			'reftime' => Util::getTime(),
    			'pk_num' => 0,
    			'bepk_num' => 0,
    	);

    	FriendDao::updateAllLove($uid, $updateArr);
    	FriendLoveObj::getInstance()->release();
    	RPCContext::getInstance()->unsetSession('friend.love');



    	$updateArr2 = array(
    			'reftime_apk' => Util::getTime(),
    			'reftime_bpk' => Util::getTime(),
    			'apk_num' => 0,
    			'bpk_num' => 0,
    	);

    	$allFriend = FriendLogic::getAllFriendInfo($uid);
    	foreach ( $allFriend as $key => $info )
    	{
    		$wheres = array(
    				array('uid','IN',array(intval( $uid ),intval( $info['uid'] ))),
    				array('fuid','IN', array(intval( $uid ),intval( $info['uid'] ))),
    		);
    		//buyanjin

    		FriendDao::setSameFriendBepkNum($wheres, $updateArr2);
    	}

    	return 'ok';
    }

    public function finishAchieve($aid)
    {
    	$uid	=	RPCContext::getInstance()->getUid();
    	EnAchieve::setAchieveFinish($uid, $aid);
    }

    public function competeTime($hourNum = 24)
    {
    	$uid = RPCContext::getInstance()->getUid();
    	$arrValue = array(
    			CompeteDef::COMPETE_TIME => Util::getTime() - $hourNum * 3600
    	);
    	CompeteDao::update($uid, $arrValue);
    	return 'ok';
    }

    public function addHonor($num)
    {
    	$uid = RPCContext::getInstance()->getUid();
    	$arrValue = array(
    			CompeteDef::COMPETE_HONOR => $num
    	);
    	CompeteDao::update($uid, $arrValue);
    	return 'ok';
    }

    public function setGroupOnNum($goodid, $num)
    {
        $groupon = GroupOnDao::selectGroupOn(GroupOnConf::DEFAULT_AID);
        $data = $groupon[GroupOnDef::VADATA];
        if(isset($data[GroupOnDef::TBL_FIELD_GOODSLIST][$goodid]))
        {
            $data[GroupOnDef::TBL_FIELD_GOODSLIST][$goodid] = $num;
        }
        $arrField = array(
            GroupOnDef::AID => GroupOnConf::DEFAULT_AID,
            GroupOnDef::VADATA => $data,
        );
        GroupOnDao::iOrUGroupOn($arrField);
    }

    public function clrGroupOn()
    {
        $arrField = array(
            GroupOnDef::UID => RPCContext::getInstance()->getUid(),
            GroupOnDef::BUYTIME => 0,
            GroupOnDef::USERVADATA => array()
        );
        GroupOnDao::iOrUUsrData($arrField);
    }

    public function grouponday()
    {
    	$actConf = ActivityConfDao::getCurConfByName(ActivityName::GROUPON, ActivityDef::$ARR_CONF_FIELD);
    	if( empty($actConf) )
    	{
    		return 'not config groupon activity';
    	}
    	$now = Util::getTime();

    	$curDay = GroupOnLogic::getActivityDay($actConf['start_time'], $now);

    	if( $curDay >= GroupOnLogic::getActivityDay($actConf['start_time'], $actConf['end_time']) )
    	{
    		$msg = sprintf('grouon activity start:%s, end:%s. cant advance',
    				date('Y-m-d H:i:s', $actConf['start_time']),
        			date('Y-m-d H:i:s', $actConf['end_time']));
    		return $msg;
    	}
    	$actConf['version'] = Util::getTime();
    	$actConf['start_time'] -= 86400;
    	ActivityConfDao::insertOrUpdate($actConf);
    	ActivityConfLogic::updateMem();

    	$ret = GroupOnDao::selectGroupOn(GroupOnConf::DEFAULT_AID);
    	$data = $ret[GroupOnDef::VADATA];
    	$data[GroupOnDef::TBL_FIELD_REFRESHTIME] = Util::getTime() - 86400;
    	$arrField = array(
    			GroupOnDef::AID => GroupOnConf::DEFAULT_AID,
    			GroupOnDef::VADATA => $data,
    	);
    	GroupOnDao::iOrUGroupOn($arrField);

    	$msg = sprintf('ok. preDay:%d, curDay:%d', $curDay, $curDay+1);
    	return $msg;
    }

    public function grouponYesterday($day = 1)
    {
    	$uid = RPCContext::getInstance()->getUid();
    	$arrField = array(
    		'buy_time' => Util::getTime()-$day*86400
    	);
    	$ret = GroupOnDao::updateUserData($uid, $arrField);
    	return 'ok';
    }

    public function actexchangeYesterday($day = 1)
    {
    	$uid = RPCContext::getInstance()->getUid();
    	$data = MallDao::select($uid, MallDef::MALL_TYPE_ACTEXCHANGE);

    	if( empty( $data ) )
    	{
    		return 'not join actexchange';
    	}
    	$setTime = Util::getTime() - $day * 86400;
    	$data['refresh_time'] = $setTime;
        $data['sys_refresh_time'] = $setTime;
    	foreach($data['all'] as $id => $value)
    	{
    		if( isset( $value['time']  ) )
    		{
    			$data['all'][$id][MallDef::TIME] = $setTime;
    		}
    	}
    	/*
    	foreach( $data['goods_list'] as $id => $value )
    	{
    		$data['goods_list'][$id]['refresh_num'] = 0;
    	}*/

    	$arrField = array(
				MallDef::USER_ID => $uid,
				MallDef::MALL_TYPE => MallDef::MALL_TYPE_ACTEXCHANGE,
				MallDef::VA_MALL => $data,
		);
		MallDao::insertOrUpdate($arrField);
    	return 'ok';
    }

    public function actexchangeday()
    {
        $actConf = ActivityConfDao::getCurConfByName(ActivityName::ACT_EXCHANGE, ActivityDef::$ARR_CONF_FIELD);
        if( empty($actConf) )
        {
            return 'not config groupon activity';
        }

        $actConf['version'] = Util::getTime();
        $actConf['start_time'] -= 86400;
        ActivityConfDao::insertOrUpdate($actConf);
        ActivityConfLogic::updateMem();

        $this->actexchangeYesterday();
    }


    public function setCRRewardTime($date)
    {
        $date = intval( $date );
        $dateDetail = strval( $date * 1000000 );
        $timeStamp = strtotime( $dateDetail ) + SECONDS_OF_DAY - 1;
        $uid = RPCContext::getInstance()->getUid();
        $raffleInfo = ChargeRaffleDao::getRaffleInfo($uid);
        $raffleInfo[ChargeRaffleDef::TBLFIELD_REWARDTIME] = $timeStamp;
        $raffleInfo[ChargeRaffleDef::TBLFIELD_LASTRFRTIME] = $timeStamp;
        ChargeRaffleDao::saveRaffleInfo($raffleInfo);
    }

    public function resetRaffleNum()
    {
        $uid = RPCContext::getInstance()->getUid();
        $ret = ChargeRaffleDao::getRaffleInfo($uid);
        if(empty($ret))
        {
            return;
        }
        for($i=ChargeRaffleDef::MIN_RAFFLE_CLASS;$i<=ChargeRaffleDef::MAX_RAFFLE_CLASS;$i++)
        {
            $ret[ChargeRaffleDef::TBLFIELD_VA_INFO][ChargeRaffleDef::TBLFIELD_RAFFLENUM][$i] = 0;
        }
        ChargeRaffleDao::saveRaffleInfo($ret);
    }

    public function addTotalPoint($totalPoint)
    {
        $uid = RPCContext::getInstance()->getUid();
        DragonDao::updateTotalPoint($totalPoint, $uid);
    }

    public function resetDragon($mode=0)
    {
        $uid = RPCContext::getInstance()->getUid();
        $mgr = DragonManager::getInstance($uid);
        $mgr->resetData($mode);
        $mgr->clrResetNum();
        $mgr->save();
    }

    public function clrResetNum()
    {
        $uid = RPCContext::getInstance()->getUid();
        $arrField = array(
            TblDragonDef::RESETNUM => 0,
            TblDragonDef::FREERESETNUM => 0,
            TblDragonDef::LASTTIME => Util::getTime(),
        );
        DragonDao::update($arrField, $uid);
    }

    public function addFreeReset($num)
    {
        $uid = RPCContext::getInstance()->getUid();
        $arrField = array(
            TblDragonDef::FREERESETNUM => $num,
            TblDragonDef::LASTTIME => Util::getTime(),
        );
        DragonDao::update($arrField, $uid);
    }

    public function dragonYesterday($date = 1)
    {
        $uid = RPCContext::getInstance()->getUid();
        $arrField = array(
            TblDragonDef::LASTTIME => Util::getTime() - 86400 * $date,
        );
        DragonDao::update($arrField, $uid);
    }

    public function resetGuildTask()
    {
    	$uid = RPCContext::getInstance()->getUid();
    	$guildtask = GuildTaskObj::getInstance($uid);
    	$info = $guildtask->getTaskInfo();
    	$info[GuildTaskDef::TASK_NUM] = 0;
    	$info[GuildTaskDef::RESET_TIME] = Util::getTime() -86400;

    	GuildTaskDao::insertOrUpdate($uid, $info);
    	GuildTaskObj::release();
    }

    public function clrTopup($day=0)
    {
        $uid = RPCContext::getInstance()->getUid();
        $topupReward = TopupRewardDao::loadData($uid);
        if(empty($day))
        {
            $day = EnActivity::getActivityDay(ActivityName::TOPUPREWARD);
        }
        else
        {
            $day = $day - 1;
        }
        if(!empty($topupReward[TopupRewardDef::VA_DATA][TopupRewardDef::TOPUP][$day][TopupRewardDef::ISREC]))
        {
            $topupReward[TopupRewardDef::VA_DATA][TopupRewardDef::TOPUP][$day][TopupRewardDef::ISREC] = TopupRewardDef::ISRECNO;
        }

        TopupRewardDao::updateData($topupReward, $uid);
    }

    public function topupYesterday()
    {
        $actConf = ActivityConfDao::getCurConfByName(ActivityName::TOPUPREWARD, ActivityDef::$ARR_CONF_FIELD);
        if( empty($actConf) )
        {
            return 'not config topupreward activity';
        }
        $now = Util::getTime();

        $actConf['version'] = Util::getTime();
        $actConf['start_time'] -= 86400;
        $actConf['end_time'] -= 86400;
        ActivityConfDao::insertOrUpdate($actConf);
        ActivityConfLogic::updateMem();

    }

    public function deleteMail($mailTid = 0)
    {
    	$receiveUid = RPCContext::getInstance()->getUid();
    	$data = new CData();
    	$data->update( 't_mail')->set( array( 'deleted' => 1 ) );
    	$data->where(array( 'reciever_uid','=', $receiveUid));
    	if ( $mailTid != 0 )
    	{
    		$data->where(array( 'template_id','=',$mailTid ));
    	}
    	$data->query();
    }

    public function deleteReward( $rewardSource=0 )
    {
    	$uid = RPCContext::getInstance()->getUid();
    	$data = new CData();
    	$data->update( 't_reward' )->set( array('recv_time' => Util::getTime() - 10) )
    	->where(array('uid','=',$uid))
    	->where( array('recv_time','=',0) );
    	if ( $rewardSource != 0 )
    	{
    		$data->where(array( 'source','=',$rewardSource ));
    	}
    	$data->query();
    	return 'ok';
    }

    public function clearReward( $rewardSource=0 )
    {
    	$now = Util::getTime();
    	$uid = RPCContext::getInstance()->getUid();
    	$data = new CData();
    	$data->update( 't_reward' )->set( array('send_time' => $now - SECONDS_OF_DAY * 30, 'recv_time' => $now) )
    	->where(array('uid','=',$uid));
    	if ( $rewardSource != 0 )
    	{
    		$data->where(array( 'source','=',$rewardSource ));
    	}
    	$data->query();
    	return 'ok';
    }



    public function clrWorship()
    {
    	$serverId = Util::getServerIdOfConnection();
    	$uid = RPCContext::getInstance()->getUid();
    	$pid = LordwarLogic::getPid($uid);
    	$lordObj = LordObj::getInstance($serverId, $pid);
    	$lordObj->worship( Util::getTime() - 86400 );
    	$lordObj->update();
    	$lordObj->release($serverId, $pid);
    }

    public function diviRewardRefNum()
    {
    	$uid = RPCContext::getInstance()->getUid();
    	$diviInst = DivineObj::getInstance($uid);
    	$info = $diviInst->getDiviInfo();
    	$info['ref_prize_num'] = 0;
    	DivineDao::updateDiviInfo($uid, $info);
    	RPCContext::getInstance()->unsetSession( DivineDef::$DIVI_SESSION_KEY );
    	$diviInst->release();
    }

    public function resetMergeServer($type, $count = 0, $time = 0)
    {
    	$uid = RPCContext::getInstance()->getUid();
    	if (empty($uid))
    	{
    		throw new FakeException( 'invalid uid' );
    	}

    	$obj = MergeServerObj::getInstance($uid);

    	if ("loginReward" == $type)
    	{
    		$obj->setLoginGotGroupForConsole(array());
    	}
    	elseif ("loginInfo" == $type)
    	{
    		if ($time != 0)
    		{
    			$time = strtotime($time);
    			if (!$time)
    			{
    				throw new FakeException('invalid time format, use yyyymmddhhmmss');
    			}
	    		if ($time > Util::getTime())
	    		{
	    			throw new FakeException('do not set future time');
	    		}
    		}

    		$time = intval($time);
    		$count = intval($count);

    		$obj->setLoginCountForConsole($count);
    		$obj->setLoginTimeForConsole($time);
    	}
    	else if ("rechargeReward" == $type)
    	{
    		$obj->setRechargeGotGroupForConsole(array());
    	}
    	else if ("compensation" == $type)
    	{
    		$obj->setCompensateTime(0);
    	}
    	else
    	{
    		return "not support type";
    	}

    	$obj->update();
    }

    public function setMonsign($time,$num)
    {
    	$timeStamp = strtotime( $time );
    	if( $timeStamp == false || $num < 0 || $num > 31  )
    	{
    		echo 'nvalid time or num';
    		return;
    	}

    	$uid = RPCContext::getInstance()->getUid();
    	$signInfo = MonthSignLogic::getMonthSignInfo($uid);
    	$signInfo['sign_time'] = $timeStamp;
    	$signInfo['reward_vip'] = 0;
    	$signInfo['sign_num'] = $num;

    	$day = date( "j", $timeStamp );
    	if( $day < $num )
    	{
    		Logger::debug('invalid day: %d to set this num: %d', $day, $num);
    		echo 'invalid day to set this num';
    		return;
    	}

    	SignDao::updateMonthSign($uid, $signInfo);
    }

    public function clrStepCounter()
    {
        $uid = RPCContext::getInstance()->getUid();
        EnUser::setExtraInfo(UserExtraDef::STEP_COUNTER_TIME, Util::getTime() - 86400, $uid);
        return 'ok';
    }

    public function weekendShopYesterday()
    {
        $uid = RPCContext::getInstance()->getUid();
        $data = MallDao::select($uid, MallDef::MALL_TYPE_WEEKENDSHOP);

        if( empty( $data ) )
        {
            return 'not join weekendshop';
        }
        $setTime = Util::getTime() -  86400;
        $data[WeekendShopDef::WEEKENDSHOP_TIME] = $setTime;

        $arrField = array(
            MallDef::USER_ID => $uid,
            MallDef::MALL_TYPE => MallDef::MALL_TYPE_WEEKENDSHOP,
            MallDef::VA_MALL => $data,
        );
        MallDao::insertOrUpdate($arrField);
        return 'ok';
    }

    public function resetStartTime($time)
    {
        $time = strtotime($time);
        $actConf = ActivityConfDao::getCurConfByName(ActivityName::STEPCOUNTER, ActivityDef::$ARR_CONF_FIELD);
        if( empty($actConf) )
        {
            return 'not config steperCounter activity';
        }

        $daysTime = $actConf['end_time'] - $actConf['start_time'];
        $actConf['version'] = Util::getTime();
        $actConf['start_time'] = $time;
        $actConf['end_time'] = $time + $daysTime;
        ActivityConfDao::insertOrUpdate($actConf);
        ActivityConfLogic::updateMem();
    }

    public function setTreeLv($level)
    {
        $goldTree = MyACopy::getInstance()->getActivityCopyObj(ACT_COPY_TYPE::GOLDTREE_COPYID);
        $exp = $goldTree->getExp();
        $curLevel = $goldTree->getLevel();
        if($curLevel >= $level)
        {
            return;
        }
        $expTblId = btstore_get()->NORMAL_CONFIG[NormalConfigDef::CONFIG_ID_GOLD_TREE_EXP_TBL];
        $expTable = btstore_get()->EXP_TBL[$expTblId];
        $needExp = $expTable[$level];
        $goldTree->addExp($needExp-$exp);
        MyACopy::getInstance()->save();
    }

    public function weekendShopBeginDay($day)
    {
        if(!is_numeric($day) || strlen($day) != 6)
        {
            echo 'invalid param';
            return;
        }
        self::weekendShopYesterday();
        popen("/bin/sed -i '/const WEEKENDSHOP_STARTTIME/{s/[0-9]\+/$day/}' /home/pirate/rpcfw/def/WeekendShop.def.php", 'r');
        return 'ok';
    }

    public function openMission($offsettime = 660)
    {
    	$time = Util::getTime();
    	$time = $time + $offsettime;
    	popen("/bin/sh /home/pirate/zhangshiyu/openMission.sh $time", 'r');
    	Logger::debug('open done, time: %s', $time);
    }

    public function changeMissionLastTime( $day, $hour, $minute,$second, $sess = 0 )
    {
 /*    	$day = intval( $day );
    	$hour = intval( $hour );
    	$minute = intval( $minute );
    	$second = intval( $second ); */
    	if( $sess <= 0 )
    	{
    		popen("sh /home/pirate/zhangshiyu/changeMissionLastTime.sh $day $hour $minute $second ", 'r');
    		Logger::debug('change done: %s, %s, %s, %s', $day, $hour, $minute,$second );
    	}
    	else
    	{
    		$sess = intval( $sess );
    		popen("sh /home/pirate/zhangshiyu/changeMissionLastTime.sh $day $hour $minute $second $sess ", 'r');
    	}

    	Logger::debug('change done: %s, %s, %s, %s, %s', $day, $hour, $minute,$second, $sess );
    }

    public function setBlack( $num )
    {
    	$uid = RPCContext::getInstance()->getUid();

    	if( $num > 300 )
    	{
    		Logger::debug('too much');
    		return;
    	}

    	$data = new CData();

    	$ret = $data->select( array('uid') )->from( 't_user' )->where(array( 'uid', '>', 1500))->limit( 0, $num)
    	->query();

    	foreach ( $ret as $index => $retInfo )
    	{
    		try {
    		BlackLogic::blackYou($uid, $retInfo['uid']);
    		}
    		catch ( Exception $e )
    		{
    			continue;
    		}
    	}
    }

    public function resetRoulette()
    {
        $uid = RPCContext::getInstance()->getUid();
        $rouletteInfo = MyRoulette::getInstance($uid)->getRouletteInfo();

        $rouletteInfo[RouletteDef::SQL_TODAY_FREE_NUM] = 0;
        $rouletteInfo[RouletteDef::SQL_ACCUM_FREE_NUM] = 0;
        $rouletteInfo[RouletteDef::SQL_ACCUM_GOLD_NUM] = 0;
        $rouletteInfo[RouletteDef::SQL_ACHIEVE_INTEGERAL] = 0;
        $rouletteInfo[RouletteDef::SQL_LAST_RFR_TIME] = Util::getTime();
        $rouletteInfo[RouletteDef::SQL_VA_BOX_REWARD] = array('arrRewarded' => array());

        RouletteDao::updateRouletteInfo($uid, $rouletteInfo);

        return 'ok';
    }

    public function setRouletteYesterday()
    {
    	$uid = RPCContext::getInstance()->getUid();

    	$rouletteInfo = MyRoulette::getInstance($uid)->getRouletteInfo();

    	$rouletteInfo[RouletteDef::SQL_LAST_RFR_TIME] = Util::getTime() - SECONDS_OF_DAY - 1;

    	RouletteDao::updateRouletteInfo($uid, $rouletteInfo);

    	return 'ok';
    }

    public function setRouletteInt($num)
    {
    	$uid = RPCContext::getInstance()->getUid();

    	$rouletteInfo = MyRoulette::getInstance($uid)->getRouletteInfo();
    	$rouletteInfo[RouletteDef::SQL_ACHIEVE_INTEGERAL] += $num;

    	RouletteDao::updateRouletteInfo($uid, $rouletteInfo);

    	return 'ok';
    }

    public function setRouletteDay($day)
    {
    	$secondsDuration = ($day-1) * SECONDS_OF_DAY;
    	$startTime = Util::getTime() - $secondsDuration;

    	$startTimeStamp = intval(strtotime(date('Y-m-d', $startTime)));

    	$actConf = ActivityConfDao::getCurConfByName(ActivityName::ROULETTE, ActivityDef::$ARR_CONF_FIELD);
    	if( empty($actConf) )
    	{
    		return 'Act roulette has no config.';
    	}

    	$daysTime = $actConf['end_time'] - $actConf['start_time'];
    	$actConf['version'] = Util::getTime();
    	$actConf['start_time'] = $startTimeStamp;
    	$actConf['end_time'] = $startTimeStamp + $daysTime;
    	ActivityConfDao::insertOrUpdate($actConf);
    	ActivityConfLogic::updateMem();

    	return 'ok';
    }

    public function resetRouletteReward($index)
    {
    	$index = intval($index);
    	$uid = RPCContext::getInstance()->getUid();
    	$rouletteObj = MyRoulette::getInstance($uid);
    	$rouletteInfo = $rouletteObj->getRouletteInfo();
    	switch ($index)
    	{
    		case 1:
    			$rouletteInfo[RouletteDef::SQL_VA_BOX_REWARD]['arrRewarded'] = array();
    		case 2:
    			$rouletteInfo[RouletteDef::SQL_VA_BOX_REWARD][RouletteDef::SQL_IS_RANK_REWARDED] = 0;
    	}

    	RouletteDao::updateRouletteInfo($uid, $rouletteInfo);

    	return 'ok';
    }

    public function getDress($itemTmpId)
    {
        EnDressRoom::getNewDress($itemTmpId);
        return 'ok';
    }

    public function clrDress()
    {
        $uid = RPCContext::getInstance()->getUid();
        $bag = BagManager::getInstance()->getBag($uid);

        $arrItemTmpIdFromBag = array();
        if ( RPCContext::getInstance()->getUid() == $uid )
        {
            $bag = BagManager::getInstance()->getBag($uid);
            $arrItemTmpIdFromBag = $bag->getItemTplIdsByItemType(ItemDef::ITEM_TYPE_DRESS);
        }
        foreach($arrItemTmpIdFromBag as $tmpId)
        {
            $bag->deleteItembyTemplateID($tmpId, 1);
        }
        $userObj = EnUser::getUserObj($uid);
        $masterHid = $userObj->getMasterHid();
        $hero = $userObj->getHeroManager()->getHeroObj($masterHid);
        $itemId = $hero->getEquipByPos(BagDef::BAG_DRESS, 1);
        if ( $itemId != BagDef::ITEM_ID_NO_ITEM )
        {
            HeroLogic::removeEquip(BagDef::BAG_DRESS, $masterHid, 1);
        }

        DressRoomDao::updateData($uid, array());
        return "ok";
    }

    public function resetLimitShop()
    {
    	$uid = RPCContext::getInstance()->getUid();

    	$arrField = array(
				MallDef::USER_ID => $uid,
				MallDef::MALL_TYPE => MallDef::MALL_TYPE_LIMITSHOP,
				MallDef::VA_MALL => array(),
    	);

    	MallDao::insertOrUpdate($arrField);

    	return 'ok';
    }

    public function setLimitShopDay($day)
    {
    	$secondsDuration = ($day-1) * SECONDS_OF_DAY;
    	$startTime = Util::getTime() - $secondsDuration;

    	$startTimeStamp = intval(strtotime(date('Y-m-d', $startTime)));

    	$actConf = ActivityConfDao::getCurConfByName(ActivityName::LIMITSHOP, ActivityDef::$ARR_CONF_FIELD);
    	if( empty($actConf) )
    	{
    		return 'Act limitshop has no config.';
    	}

    	$daysTime = $actConf['end_time'] - $actConf['start_time'];
    	$actConf['version'] = Util::getTime();
    	$actConf['start_time'] = $startTimeStamp;
    	$actConf['end_time'] = $startTimeStamp + $daysTime;
    	ActivityConfDao::insertOrUpdate($actConf);
    	ActivityConfLogic::updateMem();

    	return 'ok';
    }

    public function guildRob($operatorType, $a = 0, $b = 0, $c = 0)
    {
    	if ($operatorType == "create")
    	{
	    	if (!file_exists("/home/pirate/bin/create.sh"))
	    	{
	    		return '/home/pirate/bin/create.sh not exists';
	    	}

	    	if ($b == 0)
	    	{
	    		return "guildRob create pid guildId";
	    	}

	    	$ret = system("bash /home/pirate/bin/create.sh $a $b");
	    	return $ret;
    	}
    	else if ($operatorType == "enter")
    	{
    		if (!file_exists("/home/pirate/bin/enter.sh"))
	    	{
	    		return '/home/pirate/bin/enter.sh not exists';
	    	}

	    	if ($b != "attack" && $b != "defend" && $b != "both")
	    	{
	    		return "guildRob enter robId attack|defend|both";
	    	}

	    	$ret = system("bash /home/pirate/bin/enter.sh $a $b $c");
	    	return $ret;
    	}
    	else if ($operatorType == "leave")
    	{
    		if (!file_exists("/home/pirate/bin/leave.sh"))
    		{
    			return '/home/pirate/bin/leave.sh not exists';
    		}

    		$ret = system("bash /home/pirate/bin/leave.sh $a");
    		return $ret;
    	}
    	else if ($operatorType == "getEffect")
    	{
    		$data = unserialize(file_get_contents('/home/pirate/rpcfw/data/btstore/GUILD_ROB'));
    		return GuildRobUtil::getEffectTime();
    	}
    	else if ($operatorType == "setEffect")
    	{
    		$ret = system("/bin/sed -i '/TEST_MODE/s/= 0;/= 1;/' /home/pirate/rpcfw/conf/GuildRob.cfg.php");
    		return 'ok';
    	}
    	else if ($operatorType == "resumeEffect")
    	{
    		$ret = system("/bin/sed -i '/TEST_MODE/s/= 1;/= 0;/' /home/pirate/rpcfw/conf/GuildRob.cfg.php");
    		return 'ok';
    	}
    	else
    	{
    		$ret = array();
    		$ret[] = "invalid operation! usage:";
    		$ret[] = "加机器人：		guildRob enter 发起抢粮战军团名称      attack|defend|both  人数上限";
    		$ret[] = "让机器人离开战场:	guildRob leave 发起抢粮战军团名称";
    		$ret[] = "获得抢粮战生效时间:	guildRob getEffect";
    		$ret[] = "设置抢粮战生效时间:	guildRob setEffect（php重启，会导致控制台连接断掉）";
    		$ret[] = "恢复抢粮战生效时间:	guildRob resumeEffect（php重启，会导致控制台连接断掉）";
    		return $ret;
    	}
    }

    public function resetLastDefendTime()
    {
    	$uid = RPCContext::getInstance()->getUid();
    	$member = GuildMemberObj::getInstance($uid);
    	$guildId = $member->getGuildId();

    	if (GuildRobUtil::setLastDefendTime($guildId, 0))
    	{
    		return 'ok';
    	}

    	return 'not ok';
    }

    public function resetLastAttackTime()
    {
    	$uid = RPCContext::getInstance()->getUid();
    	$member = GuildMemberObj::getInstance($uid);
    	$guildId = $member->getGuildId();

    	if (GuildRobUtil::setLastAttackTime($guildId, 0))
    	{
    		return 'ok';
    	}

    	return 'not ok';
    }

    public function resetExtra()
    {
    	$uid = RPCContext::getInstance()->getUid();
    	$formationObj = EnFormation::getFormationObj($uid);
    	$data = FormationDao::getByUid($uid);
		if (empty($data))
		{
			echo 'no need';
			return;
		}
		$data['va_formation']['extopen'] = array();
		$data['va_formation']['extra'] = array();
		$data['va_formation']['warcraft'] = array();
		$data['craft_id'] = 0;
		FormationDao::update($uid, $data);
		RPCContext::getInstance()->unsetSession( FormationDef::SESSION_KEY_FORMATION );
		echo 'ok';
    }

    public function resetAttrExtraLv()
    {
        $uid = RPCContext::getInstance()->getUid();
        $data = FormationDao::getByUid($uid);
        if(empty($data))
        {
            echo 'no data';
            return;
        }
        $data['va_formation']['attr_extra_lv'] = array();
        FormationDao::update($uid, $data);
        RPCContext::getInstance()->unsetSession( FormationDef::SESSION_KEY_FORMATION );
        echo 'ok';
    }

    public function resetRetrieve()
    {
    	$uid = RPCContext::getInstance()->getUid();
    	$retrieveObj = RetrieveObj::getInstance($uid);
    	$retrieveObj->setRetrieveTimeForConsole(RetrieveDef::BOSS, 0);
    	$retrieveObj->setRetrieveTimeForConsole(RetrieveDef::OLYMPIC, 0);
    	$info = array();
    	$retrieveObj->setSupplyInfo($info);
    	$retrieveObj->update();
    	return 'ok';
    }
    
    public function setReSupplyNum($num)
    {
        $uid = RPCContext::getInstance()->getUid();
        
        $info = array();
        for ( $i = 0; $i < $num; $i++ )
        {
            $info[] = Util::getTime() + FrameworkConfig::DAY_OFFSET_SECOND - SECONDS_OF_DAY;
        }
        
        EnUser::setExtraInfo('va_exec', $info, $uid);
        
        EnUser::getUserObj($uid)->update();
        
        return 'ok';
    }

    public function recoverHpForPass()
    {
    	$uid = RPCContext::getInstance()->getUid();
    	$passObj = PassObj::getInstance( $uid );
    	$heroInfo = $passObj->getVaParticular( PassDef::VA_HEROINFO );
    	foreach ( $heroInfo as $hid => $oneHeroInfo )
    	{
    		$heroInfo[$hid][PropertyKey::CURR_HP] = PassCfg::FULL_PERCENT;
    	}

    	$passObj->setVaParticular( PassDef::VA_HEROINFO , $heroInfo );
    	$passObj->update();
    	PassObj::releaseInstance($uid);
    }

    public function addCoin( $num )
    {
    	$uid = RPCContext::getInstance()->getUid();
    	if( empty( $uid ) || $num <= 0 )
    	{
    		throw new FakeException( 'uid empty or num <= 0' );
    	}
    	$passObj = PassObj::getInstance($uid);
    	$passObj->addCoin($num);
    	$passObj->update();
    	PassObj::releaseInstance($uid);
    	return 'ok';
    }

    public function subCoin($num)
    {
    	$uid = RPCContext::getInstance()->getUid();
    	if( empty( $uid ) || $num <= 0 )
    	{
    		throw new FakeException( 'uid empty or num <= 0' );
    	}
    	$passObj = PassObj::getInstance($uid);
    	$passObj->subCoin($num);
    	$passObj->update();
    	PassObj::releaseInstance($uid);
    	return 'ok';
    }

    public function setReinFroceLv($id, $level)
    {
        $uid = RPCContext::getInstance()->getUid();
        $bag = BagManager::getInstance()->getBag($uid);
        $arrItemId = $bag->getItemIdsByTemplateID($id);
        foreach($arrItemId as $itemId)
        {
            $item = ItemManager::getInstance()->getItem($itemId);
            $item->setReinForceLevel($level);
            $nowExp = $item->getReinForceExp();

            $initReinForceLv = ItemAttr::getItemAttr($id, GodWeaponDef::ITEM_ATTR_NAME_GOD_WEAPON_INIT_REINFORCE_LEVEL);
            //升级经验表ID
            $reinForceExpId = ItemAttr::getItemAttr($id, GodWeaponDef::ITEM_ATTR_NAME_GOD_WEAPON_REINFORCE_EXP_ID);
            $confExpTbl = btstore_get()->EXP_TBL[$reinForceExpId];
            $level = $level > 1 ? $level : 1;
            $totalExp = $confExpTbl[$level];
            $item->addReinForceExp($totalExp - $nowExp);
        }

        $bag->update();
        return 'ok';
    }


    public function setEvolveNum($id, $num)
    {
        $uid = RPCContext::getInstance()->getUid();
        $bag = BagManager::getInstance()->getBag($uid);
        $arrItemId = $bag->getItemIdsByTemplateID($id);
        foreach($arrItemId as $itemId)
        {
            $item = ItemManager::getInstance()->getItem($itemId);
            $item->setEvolveNum($num);
        }

        $bag->update();
        return 'ok';
    }

    public function setWash($itemTplId, $index, $attrId)
    {
        $uid = RPCContext::getInstance()->getUid();
        $bag = BagManager::getInstance()->getBag($uid);
        $arrItemId = $bag->getItemIdsByTemplateID($itemTplId);
        foreach($arrItemId as $itemId)
        {
            $item = ItemManager::getInstance()->getItem($itemId);
            $itemText = $item->getItemText();
            $itemText[GodWeaponDef::CONFIREMED][$index] = $attrId;
            $item->setItemText($itemText);
        }
        $bag->update();
        return 'ok';
    }

  	public function resetPass()
  	{
  		$uid = RPCContext::getInstance()->getUid();

  		$data= new CData();
  		$data->update( 't_pass' )->set( array( 'refresh_time' => Util::getTime() - 86400 ) )
  		->where( array( 'uid', '=', $uid ) )->query();
  		$user = EnUser::getUserObj( $uid );
  		$user->modifyArtificailBattleData( PassLogic::getMemKey($uid) );

  	}

	public function resetPassShop()
    {
    	$uid = RPCContext::getInstance()->getUid();

    	$arrField = array(
				MallDef::USER_ID => $uid,
				MallDef::MALL_TYPE => MallDef::MALL_TYPE_PASS,
				MallDef::VA_MALL => array(),
    	);

    	MallDao::insertOrUpdate($arrField);

    	return 'ok';
    }

    public function resetPassShopSysRfrTime($time)
    {
    	$time = strtotime(strval($time));
    	$passShop = new PassShop();
    	$time = $passShop->calcLastSysRefreshTime($time);
    	$passShop->setLastSysRefreshTimeForConsole($time);
    	$passShop->update();
    	return 'ok';
    }

    public function resetPassShopUsrRfrTime($time)
    {
    	$time = strtotime(strval($time));
    	$passShop = new PassShop();
    	$passShop->setLastUsrRefreshTimeForConsole($time);
    	$passShop->update();
    	return 'ok';
    }

    public function resetPassShopFreeRefreshNum()
    {
    	$passShop = new PassShop();
    	$passShop->resetFreeRefreshNumForConsole();
    	$passShop->update();
    	return 'ok';
    }

    public function resetPassShopLastSysRefreshTime()
    {
    	$passShop = new PassShop();
    	$passShop->resetLastUsrRefreshTimeForConsole();
    	$passShop->resetLastSysRefreshTimeForConsole();
    	$passShop->update();
    	return 'ok';
    }

    public function setPassLast()
    {
    	$uid = RPCContext::getInstance()->getUid();
    	if( empty( $uid ) )
    	{
    		throw new FakeException( 'empty uid' );
    	}

    	$this->resetPass();
    	//PassLogic::enter( $uid );
    	$baseConf = btstore_get()->PASS_BASE->toArray();
    	$firstId = key($baseConf );
    	PassLogic::getOpponentList( $uid, $firstId );
    	PassLogic::attack($uid, 1, 1);
    	$passObj = PassObj::getInstance($uid);

    	$passInfo = $passObj->getPassInfo();
    	$passInfo['pass_num'] = count( $baseConf )-1;
    	$passInfo['cur_base'] = count( $baseConf )-1;//yilai

    	$passInfo['va_pass']['chestShow'] = array('freeChest' => 1, 'goldChest' => 1);
    	$passInfo['va_pass']['buffShow'] = array_fill(0, 3, array('status' => PassDef::BUFF_STATUS_DEAL, 'buff' => array() ) );

    	PassDao::updatePassInfo($uid, $passInfo);
    	PassObj::releaseInstance($uid);
    }

    public function setBowlDay($day)
    {
    	$secondsDuration = ($day-1) * SECONDS_OF_DAY;
    	$startTime = Util::getTime() - $secondsDuration;

    	$startTimeStamp = intval(strtotime(date('Y-m-d', $startTime)));

    	$actConf = ActivityConfDao::getCurConfByName(ActivityName::BOWL, ActivityDef::$ARR_CONF_FIELD);
    	if( empty($actConf) )
    	{
    		return 'Act treasureBowl has no config.';
    	}

    	$daysTime = $actConf['end_time'] - $actConf['start_time'];
    	$actConf['version'] = Util::getTime();
    	$actConf['start_time'] = $startTimeStamp;
    	$actConf['end_time'] = $startTimeStamp + $daysTime;
    	ActivityConfDao::insertOrUpdate($actConf);
    	ActivityConfLogic::updateMem();

    	return 'ok';
    }

    public function resetBowlReward()
    {
    	$uid = RPCContext::getInstance()->getUid();

    	$myBowl = BowlObj::getInstance($uid);

    	$myBowl->resetInfo($uid);
    	$myBowl->update();

    	return 'ok';
    }

    public function setBowltime($type,$day)
    {
    	$uid = RPCContext::getInstance()->getUid();

    	$data = new CData();
    	$arrRet = $data->select(BowlDef::$BOWL_ALL_FIELDS)
    					->from('t_bowl')
    					->where(array('uid','=',$uid))
    					->query();
    	$bowlInfo = $arrRet[0];

    	$extraBowl = $bowlInfo['va_extra'];

    	if ( empty($bowlInfo['va_extra']['type'][$type]['btime']) )
    	{
    		return 'Have not bowled.';
    	}

    	$bowlInfo['va_extra']['type'][$type]['btime'] = Util::getTime() - $day*SECONDS_OF_DAY;

    	$newData = new CData();

    	$newData->update('t_bowl')->set($bowlInfo)->where(array('uid','=',$uid))->query();

    	return 'ok';
    }

    public function resetFestival()
    {
    	$uid = RPCContext::getInstance()->getUid();

    	$festivalObj = FestivalManager::getInstance($uid);

    	$arrReset = array(
    			FestivalDef::UID => $uid,
    			FestivalDef::UPDATE_TIME => Util::getTime(),
    			FestivalDef::VA_DATA => array('hasBuy' => array())
    	);

    	FestivalDao::update($uid, $arrReset);

    	return 'ok';
    }

    public function setFestivalDay($day)
    {
    	$secondsDuration = ($day-1) * SECONDS_OF_DAY;
    	$startTime = Util::getTime() - $secondsDuration;

    	$startTimeStamp = intval(strtotime(date('Y-m-d', $startTime)));

    	$actConf = ActivityConfDao::getCurConfByName(ActivityName::FESTIVAL, ActivityDef::$ARR_CONF_FIELD);
    	if( empty($actConf) )
    	{
    		return 'Act festival has no config.';
    	}

    	$daysTime = $actConf['end_time'] - $actConf['start_time'];
    	$actConf['version'] = Util::getTime();
    	$actConf['start_time'] = $startTimeStamp;
    	$actConf['end_time'] = $startTimeStamp + $daysTime;
    	ActivityConfDao::insertOrUpdate($actConf);
    	ActivityConfLogic::updateMem();

    	return 'ok';
    }

    public function clearGodBook()
    {
        $uid = RPCContext::getInstance()->getUid();

        ItemBookDao::updateGodWeaponBook($uid, array('godweapon' => array()));
        return 'ok';
    }

    public function resetWorshipTime()
    {
    	$uid = RPCContext::getInstance()->getUid();
    	$serverId = Util::getServerIdOfConnection();
    	$guildWarUserObj = GuildWarUserObj::getInstance($serverId, $uid, TRUE);
    	$guildWarUserObj->setWorshipTimeForConsole(0);
    	$guildWarUserObj->update();

    	return 'ok';
    }

    public function resetUpdateFmtTime()
    {
    	$uid = RPCContext::getInstance()->getUid();
    	$serverId = Util::getServerIdOfConnection();
    	$guildWarUserObj = GuildWarUserObj::getInstance($serverId, $uid, TRUE);
    	$guildWarUserObj->setUpdateFmtTimeForConsole(0);
    	$guildWarUserObj->update();

    	return 'ok';
    }

    public function resetBuyMaxWinTime()
    {
    	$uid = RPCContext::getInstance()->getUid();
    	$serverId = Util::getServerIdOfConnection();
    	$guildWarUserObj = GuildWarUserObj::getInstance($serverId, $uid, TRUE);
    	$guildWarUserObj->setBuyMaxWinTimeForConsole(0);
    	$guildWarUserObj->update();

    	return 'ok';
    }

    public function resetBuyMaxWinNum()
    {
    	$uid = RPCContext::getInstance()->getUid();
    	$serverId = Util::getServerIdOfConnection();
    	$guildWarUserObj = GuildWarUserObj::getInstance($serverId, $uid, TRUE);
    	$guildWarUserObj->setBuyMaxWinNumForConsole(0);
    	$guildWarUserObj->update();

    	return 'ok';
    }

public function addSSPoint($date = 0,$point = 0)
    {
    	$date = intval($date);
    	$point = intval($point);

    	$uid = RPCContext::getInstance()->getUid();
    	if ( empty( $uid ) )
    	{
    		return 'login first';
    	}

    	if (empty($date))
    	{
    		$time = Util::getTime();
    		$date = intval( strftime( "%Y%m%d", $time ) );
    	}

    	$dateDetail = strval( $date * 1000000 );
    	$timeStamp = strtotime( $dateDetail );
    	if ( !$timeStamp )
    	{
    		return 'invalid time format, use yyyymmdd';
    	}
    	$timeNow = Util::getTime();
    	if ( $timeStamp > $timeNow )
    	{
    		return 'do not type in future time';
    	}

    	$userObj = EnUser::getUserObj($uid);

    	if (FALSE == EnActivity::isOpen(ActivityName::SCORESHOP))
    	{
    		throw new FakeException('Act ScoreShop is not open.');
    	}

    	$conf = EnActivity::getConfByName(ActivityName::SCORESHOP);
    	$goldPerPoint = $conf['data'][ScoreShopDef::TO_POINT][ScoreShopDef::GOLD_EACH_POINT];
    	$num = $goldPerPoint * $point;

    	$data = new CData();
    	$ret = $data->select( array('va_user') )->from( 't_user' )->where(array('uid','=', $uid))
    	->query();

    	if (empty($ret))
    	{
    		throw new FakeException( 'no va or such uid: %d', $uid );
    	}

    	$spendList = array();
    	if ( !isset($ret[0]['va_user']['spend_gold']) || empty( $ret[0]['va_user']['spend_gold'] ) )
    	{
    		//没花过钱
    		$spendList[$date] = $num;
    	}
    	else
    	{
    		$spendList = $ret[0]['va_user']['spend_gold'];

    		//要设置的这一天已经有了
    		if ( isset( $spendList[$date] ) )
    		{
    			$spendList[$date] += $num;
    		}
    		else
    		{
    			//要设的这一天没有但是保存的数量还没到上限
    			if (count( $spendList) < UserConf::SPEND_GOLD_DATE_NUM )
    			{
    				$spendList[ $date ] = $num;
    			}
    			else
    			{
    				$first = key($spendList);
    				//设置的太早了
    				if ( $first > $date )
    				{
    					return "too earlier to set spend gold, the earliest is $first";
    				}
    				//已经到上限了，删掉最早的一条
    				unset($spendList[$first]);
    				$spendList[$date] = $num;
    			}
    			ksort( $spendList );
    		}
    	}

    	$ret[0]['va_user']['spend_gold'] = $spendList;

    	$data->update( 't_user' )->set( array('va_user' => $ret[0]['va_user']) )->where(array('uid','=',$uid))->query();

    	RPCContext::getInstance()->resetSession();

    	$scoreShop = new ScoreShop();
    	$shopInfo = $scoreShop->getShopInfo();
    	$point = $shopInfo['point'];

    	return array(
    			'ret' => 'ok',
    			'point' => $point,
    	);
    }

    public function setSSDay($day)
    {
    	$day = intval($day);

    	$secondsDuration = ($day-1) * SECONDS_OF_DAY;
    	$startTime = Util::getTime() - $secondsDuration;

    	$startTimeStamp = intval(strtotime(date('Y-m-d', $startTime)));

    	$actConf = ActivityConfDao::getCurConfByName(ActivityName::SCORESHOP, ActivityDef::$ARR_CONF_FIELD);
    	if( empty($actConf) )
    	{
    		return 'Act scoreshop has no config.';
    	}

    	$daysTime = $actConf['end_time'] - $actConf['start_time'];
    	$actConf['version'] = Util::getTime();
    	$actConf['start_time'] = $startTimeStamp;
    	$actConf['end_time'] = $startTimeStamp + $daysTime;
    	ActivityConfDao::insertOrUpdate($actConf);
    	ActivityConfLogic::updateMem();

    	return 'ok';
    }

    public function resetSS()
    {
    	$uid = RPCContext::getInstance()->getUid();

    	$arrField = array(
    			MallDef::USER_ID => $uid,
    			MallDef::MALL_TYPE => MallDef::MALL_TYPE_SCORESHOP,
    			MallDef::VA_MALL => array(),
    	);

    	MallDao::insertOrUpdate($arrField);

    	$data = new CData();
    	$ret = $data->select( array('va_user') )->from( 't_user' )->where(array('uid','=', $uid))->query();

    	if (!empty($ret))
    	{
    		if (!empty($ret[0]['va_user']['spend_gold'])
    				|| !empty($ret[0]['va_user']['spend_execution'])
    				|| !empty($ret[0]['va_user']['spend_stamina']))
    		{
    			$ret[0]['va_user']['spend_gold'] = array();
    			$ret[0]['va_user']['spend_execution'] = array();
    			$ret[0]['va_user']['spend_stamina'] = array();
    			$data->update( 't_user' )->set( array('va_user' => $ret[0]['va_user']) )->where(array('uid','=',$uid))->query();
    		}
    	}

    	RPCContext::getInstance()->resetSession();

    	return 'ok';
    }

    public function clrAthena($index=0)
    {
        $uid = RPCContext::getInstance()->getUid();

        //$fields = AthenaDao::loadData($uid);

        /**
        if(isset($fields[AthenaSql::VA_DATA][AthenaSql::DETAIL][$index]))
        {
            unset($fields[AthenaSql::VA_DATA][AthenaSql::DETAIL][$index]);
        }
        */

        $fields = array(
            AthenaSql::UID => $uid,
            AthenaSql::VA_DATA => array(
                AthenaSql::DETAIL => array(),
                AthenaSql::SPECIAL => array(),
                AthenaSql::TREE_NUM => AthenaDef::INIT_TREE_INDEX,
                AthenaSql::BUY_NUM => array(),
                AthenaSql::BUY_TIME => 0,
                AthenaSql::ARR_TALENT => array(),
            ),
        );

        AthenaDao::update($fields);
        return 'ok';
    }

    public function clrAthenaTalent()
    {
        $uid = RPCContext::getInstance()->getUid();

        $fields = AthenaDao::loadData($uid);
        if(isset($fields[AthenaSql::VA_DATA][AthenaSql::ARR_TALENT]))
        {
            unset($fields[AthenaSql::VA_DATA][AthenaSql::ARR_TALENT]);
        }

        AthenaDao::update($fields);
        return 'ok';
    }

    public function setTreeNum($num)
    {
        $uid = RPCContext::getInstance()->getUid();
        $athena = AthenaDao::loadData($uid);
        $athena[AthenaSql::VA_DATA][AthenaSql::TREE_NUM] = $num;

        AthenaDao::update($athena);
        return 'ok';
    }

    public function athenaTalent($index)
    {
        $uid = RPCContext::getInstance()->getUid();
        $athena = AthenaManager::getInstance($uid);
        $treeConf = AthenaLogic::getTreeConf();
        if(empty($treeConf[$index][AthenaCsvDef::AWAKE_ABILITY_ID]))
        {
            $msg = sprintf("no talentId this index:%d", $index);
            return $msg;
        }
        $talentId = $treeConf[$index][AthenaCsvDef::AWAKE_ABILITY_ID];
        if($athena->ifTalentExist($talentId) == false)
        {
            $athena->addTalent($talentId);
        }
        $athena->update();
    }

    public function guildcopy_resetUser()
    {
    	$uid = RPCContext::getInstance()->getUid();
    	$guildCopyUserObj = GuildCopyUserObj::getInstance($uid);
    	$guildCopyUserObj->resetForTest();
    	$guildCopyUserObj->update();
    	return 'guildcopy_resetUser ok';
    }

    public function guildcopy_resetGuild()
    {
    	$uid = RPCContext::getInstance()->getUid();
    	$guildId = EnUser::getUserObj($uid)->getGuildId();
    	$guildCopyObj = GuildCopyObj::getInstance($guildId);
    	$guildCopyObj->resetForTest();
    	$guildCopyObj->update();
    	return 'guildcopy_resetGuild ok';
    }

    public function guildcopy_addUserAtkNum($num)
    {
    	$uid = RPCContext::getInstance()->getUid();
    	$guildCopyUserObj = GuildCopyUserObj::getInstance($uid);
    	$guildCopyUserObj->addAtkNum($num);
    	$guildCopyUserObj->update();
    	return 'guildcopy_addUserAtkNum ok,curr atk num:' . $guildCopyUserObj->getAtkNum();
    }

    public function guildcopy_setUserBuyNum($num)
    {
    	$uid = RPCContext::getInstance()->getUid();
    	$guildCopyUserObj = GuildCopyUserObj::getInstance($uid);
    	$guildCopyUserObj->setBuyNumForTest($num);
    	$guildCopyUserObj->update();
    	return 'guildcopy_setUserBuyNum ok,curr buy num:' . $guildCopyUserObj->getBuyNum();
    }

    public function guildcopy_resetUserAllAttackTime()
    {
    	$uid = RPCContext::getInstance()->getUid();
    	$guildCopyUserObj = GuildCopyUserObj::getInstance($uid);
    	$guildCopyUserObj->resetRefreshTimeForTest();
    	$guildCopyUserObj->update();
    	return 'guildcopy_resetUserAllAttackTime ok';
    }

    public function guildcopy_resetUserRecvRewardTime()
    {
    	$uid = RPCContext::getInstance()->getUid();
    	$guildCopyUserObj = GuildCopyUserObj::getInstance($uid);
    	$guildCopyUserObj->resetRecvPassRewardTimeForTest();
    	$guildCopyUserObj->resetRecvBoxRewardTimeForTest();
    	$guildCopyUserObj->resetRecvRankRewardTimeForTest();
    	$guildCopyUserObj->update();
    	return 'guildcopy_resetUserRecvRewardTime ok';
    }

    public function guildcopy_setGuildMaxPassCopy($num)
    {
    	$uid = RPCContext::getInstance()->getUid();
    	$guildId = EnUser::getUserObj($uid)->getGuildId();
    	$guildCopyObj = GuildCopyObj::getInstance($guildId);
    	if ($num < 0)
    	{
    		return 'invalid num';
    	}
    	$guildCopyObj->setMaxPassCopyForTest($num);
    	$guildCopyObj->update();
    	return 'guildcopy_setGuildMaxPassCopy ok,curr max pass copy num:' . $guildCopyObj->getMaxPassCopy();
    }

    public function guildcopy_setGuildCurrCopy($num)
    {
    	$uid = RPCContext::getInstance()->getUid();
    	$guildId = EnUser::getUserObj($uid)->getGuildId();
    	$guildCopyObj = GuildCopyObj::getInstance($guildId);
    	if ($num < 0)
    	{
    		return 'invalid num';
    	}
    	$guildCopyObj->setCurrCopyForTest($num);
    	$guildCopyObj->update();
    	$this->guildcopy_resetGuildCopy();
    	return 'guildcopy_setGuildCurrCopy ok,curr copy:' . $guildCopyObj->getCurrCopy();
    }

    public function guildcopy_resetGuildAllAttackNum()
    {
    	$uid = RPCContext::getInstance()->getUid();
    	$guildId = EnUser::getUserObj($uid)->getGuildId();
    	$guildCopyObj = GuildCopyObj::getInstance($guildId);
    	$guildCopyObj->resetRefreshNumForTest();
    	$guildCopyObj->update();
    	return 'guildcopy_resetGuildAllAttackNum ok';
    }

    public function guildcopy_resetGuildBox()
    {
    	$uid = RPCContext::getInstance()->getUid();
    	$guildId = EnUser::getUserObj($uid)->getGuildId();
    	$guildCopyObj = GuildCopyObj::getInstance($guildId);
        $guildCopyObj->setBoxInfo(array());
        $guildCopyObj->update();
        return 'guildcopy_resetGuildBox ok';
    }

    public function guildcopy_resetGuildCopy()
    {
    	$uid = RPCContext::getInstance()->getUid();
    	$guildId = EnUser::getUserObj($uid)->getGuildId();
    	$guildCopyObj = GuildCopyObj::getInstance($guildId);
    	list($copyInfo, $isInit) = $guildCopyObj->getCopyInfo();
    	foreach ($copyInfo as $index => $value)
    	{
    		unset($copyInfo[$index]['hp']);
    		unset($copyInfo[$index]['max_damager']);
    	}
    	$guildCopyObj->setCopyInfo($copyInfo);
    	$guildCopyObj->resetPassTimeForTest();
    	$guildCopyObj->update();
    	return 'guildcopy_resetGuildCopy ok';
    }

    public function guildcopy_passCurrCopy()
    {
    	$uid = RPCContext::getInstance()->getUid();
    	$guildId = EnUser::getUserObj($uid)->getGuildId();
    	$guildCopyObj = GuildCopyObj::getInstance($guildId);
    	list($copyInfo, $isInit) = $guildCopyObj->getCopyInfo();
    	foreach ($copyInfo as $index => $value)
		{
			$copyInfo[$index]['hp'] = array();
		}
		$guildCopyObj->setCopyInfo($copyInfo);
		$guildCopyObj->passCurrCopy();
    	$guildCopyObj->update();
    	return 'guildcopy_passCurrCopy ok';
    }

    public function guildcopy_resetBoss()
    {
    	$uid = RPCContext::getInstance()->getUid();
    	$guildId = EnUser::getUserObj($uid)->getGuildId();
    	$guildCopyObj = GuildCopyObj::getInstance($guildId);
    	$guildCopyObj->resetGuildBoss();
    	$guildCopyObj->update();
    	return 'ok';
    }

    public function clearShare()
    {
        EnUser::setExtraField(UserExtraDef::USER_EXTRA_FIELD_SHARE_TIME, Util::getTime()-SECONDS_OF_DAY);
    }

    public function clearAllShare()
    {
        EnUser::setExtraField(UserExtraDef::USER_EXTRA_FIELD_SHARE_TIME, 0);
    }

    public function moon_setAtkNum($num)
    {
    	if ($num < 0)
    	{
    		return 'num不能是负的';
    	}

    	$moonObj = MoonObj::getInstance();
    	$moonObj->setAtkNumForConsole($num);
    	$moonObj->update();

    	return 'moon_setAtkNum ok';
    }

    public function moon_setBuyNum($num)
    {
    	if ($num < 0)
    	{
    		return 'num不能是负的';
    	}

    	$moonObj = MoonObj::getInstance();
    	$moonObj->setBuyNumForConsole($num);
    	$moonObj->update();

    	return 'moon_setBuyNum ok';
    }

    public function moon_setBuyBoxNum($num)
    {
    	if ($num < 0)
    	{
    		return 'num不能是负的';
    	}

    	$moonObj = MoonObj::getInstance();
    	$moonObj->setBuyBoxNumForConsole($num);
    	$moonObj->update();

    	return 'moon_setBuyBoxNum ok';
    }

    public function moon_setMaxPassCopy($num)
    {
    	if ($num < 0)
    	{
    		return 'num不能是负的';
    	}

    	$moonObj = MoonObj::getInstance();
    	$moonObj->setMaxPassCopyForConsole($num);
    	$moonObj->update();

    	return 'moon_setMaxPassCopy ok';
    }

    public function moon_setGridStatus($gridId, $status)
    {
    	if ($gridId <= 0 || $gridId > MoonConf::MAX_GRID_NUM)
    	{
    		return '格子编号不对';
    	}

    	if ($status != MoonGridStatus::LOCK
			&& $status != MoonGridStatus::UNLOCK
			&& $status != MoonGridStatus::DONE)
    	{
    		return '状态不对，应为1代表锁定，2代表解锁未点亮，3代表点亮';
    	}

    	$moonObj = MoonObj::getInstance();
    	$moonObj->setGridStatusForConsole($gridId, intval($status));
    	$moonObj->update();

    	return 'moon_setGridStatus ok';
    }

    public function moon_resetGridInfo()
    {
    	$moonObj = MoonObj::getInstance();
    	$moonObj->setGridInfoForConsole(MoonObj::initGridInfo($moonObj->getCurrCopy()));
    	$moonObj->update();

    	return 'moon_resetGridInfo ok';
    }

    public function moon_reset()
    {
    	$moonObj = MoonObj::getInstance();
    	$moonObj->resetForConsole();
    	$moonObj->update();

    	return 'moon_reset ok';
    }

    public function moon_resetNightMare()
    {
    	$moonObj = MoonObj::getInstance();
    	$moonObj->resetNighMareForConsole();
    	$moonObj->update();

    	return 'moon_resetNightMare atk_num,buy_num ok';
    }

    public function moon_addTgNum($num)
    {
    	EnUser::getUserObj()->addTgNum($num);
    	EnUser::getUserObj()->update();

    	return 'moon_addTgNum ok';
    }

    public function moon_addTallyPoint($num)
    {
    	EnUser::getUserObj()->addTallyPoint($num);
    	EnUser::getUserObj()->update();

    	return 'moon_addTallyPoint ok';
    }


    public function moon_setLastAttackTime($timeStr)
    {
    	$time = strtotime($timeStr);
    	$moonObj = MoonObj::getInstance();
    	$moonObj->setUpdateTimeForConsole($time);
    	$moonObj->update();

    	return 'moon_setLastAttackTime ok';
    }

    public function moon_resetShop()
    {
    	$uid = RPCContext::getInstance()->getUid();

    	$arrField = array(
    			MallDef::USER_ID => $uid,
    			MallDef::MALL_TYPE => MallDef::MALL_TYPE_TGSHOP,
    			MallDef::VA_MALL => array(),
    	);

    	MallDao::insertOrUpdate($arrField);

    	return 'moon_resetShop ok';
    }

    public function moon_resetBingfushop()
    {
    	$uid = RPCContext::getInstance()->getUid();
    	$info = MallDao::select($uid, MallDef::MALL_TYPE_BINGFU_SHOP);
    	$arrField = array(
    			MallDef::USER_ID => $uid,
    			MallDef::MALL_TYPE => MallDef::MALL_TYPE_BINGFU_SHOP,
    			MallDef::VA_MALL => array(
    					BingfuShopField::TBL_FIELD_VA_ALL => $info[BingfuShopField::TBL_FIELD_VA_ALL],
						BingfuShopField::TBL_FIELD_VA_GOODS_LIST => $info[BingfuShopField::TBL_FIELD_VA_GOODS_LIST],
						BingfuShopField::TBL_FIELD_VA_LAST_SYS_RFR_TIME => $info[BingfuShopField::TBL_FIELD_VA_LAST_SYS_RFR_TIME],
						BingfuShopField::TBL_FIELD_VA_LAST_USR_RFR_TIME => $info[BingfuShopField::TBL_FIELD_VA_LAST_USR_RFR_TIME],
    					//需要修改部分见下
						BingfuShopField::TBL_FIELD_VA_USR_RFR_NUM => 0,
						BingfuShopField::TBL_FIELD_VA_FREE_RFR_NUM => 0,
    			),
    	);

    	MallDao::insertOrUpdate($arrField);
    	return 'moon_resetBingfushop ok';
    }
    public function moon_resetShopSysRfrTime($time)
    {
    	$time = strtotime(strval($time));
    	$moonShop = new MoonShop();
    	$time = $moonShop->calcLastSysRefreshTime($time);
    	$moonShop->setLastSysRefreshTimeForConsole($time);
    	$moonShop->update();
    	return 'moon_resetShopSysRfrTime ok';
    }

    public function moon_resetShopUsrRfrTime($time)
    {
    	$time = strtotime(strval($time));
    	$moonShop = new MoonShop();
    	$moonShop->setLastUsrRefreshTimeForConsole($time);
    	$moonShop->update();
    	return 'moon_resetShopUsrRfrTime ok';
    }

    public function resetMoonShopFreeRefreshNum()
    {
    	$moonShop = new MoonShop();
    	$moonShop->resetFreeRefreshNumForConsole();
    	$moonShop->update();
    	return 'ok';
    }

    public function resetMoonShopLastSysRefreshTime()
    {
    	$moonShop = new MoonShop();
    	$moonShop->resetLastSysRefreshTimeForConsole();
    	$moonShop->resetLastUsrRefreshTimeForConsole();
    	$moonShop->update();
    	$moonObj = MoonObj::getInstance();
    	$moonObj->setBuyBoxNumForConsole(0);
    	$moonObj->update();
    	return 'ok';
    }

    public function screen($num, $robId = 0, $type = ChatConfig::SCREEN_TYPE_ROBL)
    {
    	$data = new CData();
    	$offset = rand(0,1000);
    	$users = array();
    	    do
    		{
    			if( $num <= 100 )
    			{
    				$partUsers = $data->select( array('uid') )->from('t_user')->where(array('uid','>',2000))->limit($offset, $num)->query();
    				$users = array_merge( $users, $partUsers );
    				break;
    			}
    			else
    			{
    				$partUsers = $data->select( array('uid') )->from('t_user')->where(array('uid','>',2000))->limit($offset, 100)->query();
    				if( empty($partUsers) )
    				{
    					break;
    				}
    				$users = array_merge( $users, $partUsers );
    				if( count($partUsers) < 100 )
    				{
    						break;
    				}
    				$num-=count( $partUsers );
    				$offset+=count($partUsers);

    			}
    		}
    		while(true);
    		$orUid = RPCContext::getInstance()->getUid();
    		foreach ($users as $index => $ret)
    		{
    			RPCContext::getInstance()->resetSession();
    			EnUser::release($ret['uid']);

    			RPCContext::getInstance()->setSession( UserDef::SESSION_KEY_UID, $ret['uid'] );
    			$user = EnUser::getUserObj($ret['uid']);
    			$hid = $user->getHeroManager()->getMasterHeroObj()->getHid();

    			$chat = new Chat();
    			$chat->sendScreen("我勒个ca!? 牛腻甭。。。", $type, $robId, "255,0,0");

    		}
    		RPCContext::getInstance()->resetSession();
    		RPCContext::getInstance()->setSession(UserDef::SESSION_KEY_UID, $orUid);
    		EnUser::release();
    }

    public function worldpass_setAtkNum($num)
    {
    	if ($num < 0)
    	{
    		return 'num不能是负的';
    	}

    	$uid = RPCContext::getInstance()->getUid();
    	$serverId = Util::getServerIdOfConnection();
    	$pid = WorldPassUtil::getPid($uid);
    	$worldPassInnerUserObj = WorldPassInnerUserObj::getInstance($serverId, $pid, $uid);
    	$worldPassInnerUserObj->setAtkNumForConsole($num);
    	$worldPassInnerUserObj->update();

    	return 'worldpass_setAtkNum ok';
    }

    public function worldpass_setBuyNum($num)
    {
    	if ($num < 0)
    	{
    		return 'num不能是负的';
    	}

    	$uid = RPCContext::getInstance()->getUid();
    	$serverId = Util::getServerIdOfConnection();
    	$pid = WorldPassUtil::getPid($uid);
    	$worldPassInnerUserObj = WorldPassInnerUserObj::getInstance($serverId, $pid, $uid);
    	$worldPassInnerUserObj->setBuyAtkNumForConsole($num);
    	$worldPassInnerUserObj->update();

    	return 'worldpass_setBuyNum ok';
    }

    public function worldpass_setRfrNum($num)
    {
    	if ($num < 0)
    	{
    		return 'num不能是负的';
    	}

    	$uid = RPCContext::getInstance()->getUid();
    	$serverId = Util::getServerIdOfConnection();
    	$pid = WorldPassUtil::getPid($uid);
    	$worldPassInnerUserObj = WorldPassInnerUserObj::getInstance($serverId, $pid, $uid);
    	$worldPassInnerUserObj->setRefreshNumForConsole($num);
    	$worldPassInnerUserObj->update();

    	return 'worldpass_setRfrNum ok';
    }

    public function worldpass_setCurrPoint($num)
    {
    	if ($num < 0)
    	{
    		return 'num不能是负的';
    	}

    	$uid = RPCContext::getInstance()->getUid();
    	$serverId = Util::getServerIdOfConnection();
    	$pid = WorldPassUtil::getPid($uid);
    	$worldPassInnerUserObj = WorldPassInnerUserObj::getInstance($serverId, $pid, $uid);
    	$worldPassInnerUserObj->setCurrPointForConsole($num);
    	$worldPassInnerUserObj->update();

    	return 'worldpass_setCurrPoint ok';
    }

	public function worldpass_setHellPoint($num)
    {
    	if ($num < 0)
    	{
    		return 'num不能是负的';
    	}

    	$uid = RPCContext::getInstance()->getUid();
    	$serverId = Util::getServerIdOfConnection();
    	$pid = WorldPassUtil::getPid($uid);
    	$worldPassInnerUserObj = WorldPassInnerUserObj::getInstance($serverId, $pid, $uid);
    	$worldPassInnerUserObj->setHellPointForConsole($num);
    	$worldPassInnerUserObj->update();

    	return 'worldpass_setHellPoint ok';
    }

    public function worldpass_resetBuyNum()
    {
    	$uid = RPCContext::getInstance()->getUid();
    	$exchangeInfo = MallDao::select($uid, MallDef::MALL_TYPE_WORLDPASS_SHOP);
    	if (!empty($exchangeInfo[MallDef::ALL]))
    	{
    		foreach ($exchangeInfo[MallDef::ALL] as $exchangeId => $info)
    		{
    			$info['time'] -= 7 * SECONDS_OF_DAY;
    			$exchangeInfo[MallDef::ALL][$exchangeId] = $info;
    		}
    		$arrField = array(
    				MallDef::USER_ID => $uid,
    				MallDef::MALL_TYPE => MallDef::MALL_TYPE_WORLDPASS_SHOP,
    				MallDef::VA_MALL => $exchangeInfo,
    		);
    		MallDao::insertOrUpdate($arrField);
    	}
    	return 'worldpass_resetBuyNum ok';
    }

    public function worldpass_resetPassedStage()
    {
    	$uid = RPCContext::getInstance()->getUid();
    	$serverId = Util::getServerIdOfConnection();
    	$pid = WorldPassUtil::getPid($uid);
    	$worldPassInnerUserObj = WorldPassInnerUserObj::getInstance($serverId, $pid, $uid);
    	$worldPassInnerUserObj->setPassedStageForConsole(0);
    	$worldPassInnerUserObj->setFormationForConsole(array());
    	$worldPassInnerUserObj->setCurrPointForConsole(0);
    	$worldPassInnerUserObj->update();

    	return 'worldpass_resetPassedStage ok';
    }

    public function clrPill()
    {
        $uid = RPCContext::getInstance()->getUid();
        $ret = HeroDao::getArrHeroeByUid($uid, HeroDef::$HERO_FIELDS);
        $ret = Util::arrayIndex($ret, "hid");
        foreach($ret as $hid => $heroInfo)
        {
            if(empty($heroInfo['va_hero']['pill']))
            {
                continue;
            }
            unset($ret[$hid]['va_hero']['pill']);
        }
        HeroDao::batchUpdate($ret);
    }

    public function clrUnion()
    {
    	$uid = RPCContext::getInstance()->getUid();
    	$union = UnionDao::select($uid);
    	$union[UnionDef::FIELD_VA_FATE][UnionDef::LISTS] = array();
    	$union[UnionDef::FIELD_VA_LOYAL][UnionDef::LISTS] = array();
    	$union[UnionDef::FIELD_VA_MARTIAL][UnionDef::LISTS] = array();
    	UnionDao::insertOrUpdate($union);
    	return 'ok';
    }

    public function worldarena_clearProtectTime()
    {
    	$uid = RPCContext::getInstance()->getUid();
    	$serverId = Util::getServerIdOfConnection();
    	$pid = WorldArenaUtil::getPid($uid);
    	$teamId = WorldArenaUtil::getTeamIdByServerId($serverId);

    	$myCrossObj = WorldArenaCrossUserObj::getInstance($serverId, $pid, $uid, $teamId, FALSE);
    	$myCrossObj->setProtectTimeForConsole(0);
    	$myCrossObj->update();

    	return 'worldarena_clearProtectTime ok';
    }

    public function worldarena_clearUpdateFmtTime()
    {
    	$uid = RPCContext::getInstance()->getUid();
    	$serverId = Util::getServerIdOfConnection();
    	$pid = WorldArenaUtil::getPid($uid);
    	$teamId = WorldArenaUtil::getTeamIdByServerId($serverId);

    	$myInnerObj = WorldArenaInnerUserObj::getInstance($serverId, $pid, $uid, FALSE);
    	$myInnerObj->setUpdateFmtTimeForConsole(0);
    	$myInnerObj->update();

    	return 'worldarena_clearUpdateFmtTime ok';
    }

    public function worldarena_setGoldResetNum($num)
    {
    	if ($num < 0)
    	{
    		return 'num不能是负的';
    	}

    	$uid = RPCContext::getInstance()->getUid();
    	$serverId = Util::getServerIdOfConnection();
    	$pid = WorldArenaUtil::getPid($uid);
    	$teamId = WorldArenaUtil::getTeamIdByServerId($serverId);

    	$myInnerObj = WorldArenaInnerUserObj::getInstance($serverId, $pid, $uid, FALSE);
    	$myInnerObj->setGoldResetNumForConsole($num);
    	$myInnerObj->update();

    	return 'worldarena_setGoldResetNum ok';
    }

    public function worldarena_setSilverResetNum($num)
    {
    	if ($num < 0)
    	{
    		return 'num不能是负的';
    	}

    	$uid = RPCContext::getInstance()->getUid();
    	$serverId = Util::getServerIdOfConnection();
    	$pid = WorldArenaUtil::getPid($uid);
    	$teamId = WorldArenaUtil::getTeamIdByServerId($serverId);

    	$myInnerObj = WorldArenaInnerUserObj::getInstance($serverId, $pid, $uid, FALSE);
    	$myInnerObj->setSilverResetNumForConsole($num);
    	$myInnerObj->update();

    	return 'worldarena_setSilverResetNum ok';
    }

    public function worldarena_setAtkedNum($num)
    {
    	if ($num < 0)
    	{
    		return 'num不能是负的';
    	}

    	$uid = RPCContext::getInstance()->getUid();
    	$serverId = Util::getServerIdOfConnection();
    	$pid = WorldArenaUtil::getPid($uid);
    	$teamId = WorldArenaUtil::getTeamIdByServerId($serverId);

    	$myInnerObj = WorldArenaInnerUserObj::getInstance($serverId, $pid, $uid, FALSE);
    	$myInnerObj->setAtkedNumForConsole($num);
    	$myInnerObj->update();

    	return 'worldarena_setAtkedNum ok';
    }

    public function worldarena_setBuyAtkNum($num)
    {
    	if ($num < 0)
    	{
    		return 'num不能是负的';
    	}

    	$uid = RPCContext::getInstance()->getUid();
    	$serverId = Util::getServerIdOfConnection();
    	$pid = WorldArenaUtil::getPid($uid);
    	$teamId = WorldArenaUtil::getTeamIdByServerId($serverId);

    	$myInnerObj = WorldArenaInnerUserObj::getInstance($serverId, $pid, $uid, FALSE);
    	$myInnerObj->setBuyAtkNumForConsole($num);
    	$myInnerObj->update();

    	return 'worldarena_setBuyAtkNum ok';
    }

    public function worldarena_setKillNum($num)
    {
    	if ($num < 0)
    	{
    		return 'num不能是负的';
    	}

    	$uid = RPCContext::getInstance()->getUid();
    	$serverId = Util::getServerIdOfConnection();
    	$pid = WorldArenaUtil::getPid($uid);
    	$teamId = WorldArenaUtil::getTeamIdByServerId($serverId);

    	$myCrossObj = WorldArenaCrossUserObj::getInstance($serverId, $pid, $uid, $teamId, FALSE);
    	$myCrossObj->setKillNumForConsole($num);
    	$myCrossObj->update();

    	return 'worldarena_setKillNum ok';
    }

    public function worldarena_setCurContiNum($num)
    {
    	if ($num < 0)
    	{
    		return 'num不能是负的';
    	}

    	$uid = RPCContext::getInstance()->getUid();
    	$serverId = Util::getServerIdOfConnection();
    	$pid = WorldArenaUtil::getPid($uid);
    	$teamId = WorldArenaUtil::getTeamIdByServerId($serverId);

    	$myCrossObj = WorldArenaCrossUserObj::getInstance($serverId, $pid, $uid, $teamId, FALSE);
    	$myCrossObj->setCurContiNumForConsole($num);
    	if ($num > $myCrossObj->getMaxContiNum())
    	{
    		$myCrossObj->setMaxContiNumForConsole($num);
    	}
    	$myCrossObj->update();

    	return 'worldarena_setCurContiNum ok';
    }

    public function worldarena_setMaxContiNum($num)
    {
    	if ($num < 0)
    	{
    		return 'num不能是负的';
    	}

    	$uid = RPCContext::getInstance()->getUid();
    	$serverId = Util::getServerIdOfConnection();
    	$pid = WorldArenaUtil::getPid($uid);
    	$teamId = WorldArenaUtil::getTeamIdByServerId($serverId);

    	$myCrossObj = WorldArenaCrossUserObj::getInstance($serverId, $pid, $uid, $teamId, FALSE);
    	$myCrossObj->setMaxContiNumForConsole($num);
    	if ($num < $myCrossObj->getCurContiNum())
    	{
    		$myCrossObj->setCurContiNumForConsole($num);
    	}
    	$myCrossObj->update();

    	return 'worldarena_setMaxContiNum ok';
    }

    public function worldgroupon_clear_user()
    {
        $uid = RPCContext::getInstance()->getUid();
        WorldGrouponDao::updInnerUserInfo(
            array(
                WorldGrouponSqlDef::TBL_FIELD_UID => $uid,
                WorldGrouponSqlDef::TBL_FIELD_POINT => 0,
                WorldGrouponSqlDef::TBL_FIELD_COUPON => 0,
                WorldGrouponSqlDef::TBL_FIELD_OPTIME => Util::getTime(),
                WorldGrouponSqlDef::TBL_FIELD_REWARD_TIME => 0,
                WorldGrouponSqlDef::TBL_FIELD_VA_INFO => array(),
            )
        );

        return 'ok';
    }

    public function world_groupon_yesterday($day = 1)
    {
        $uid = RPCContext::getInstance()->getUid();

        $arrField = WorldGrouponDao::getInnerUserInfo($uid);
        if(!empty($arrField[WorldGrouponSqlDef::TBL_FIELD_VA_INFO][WorldGrouponSqlDef::HIS_IN_VA_INFO]))
        {
            foreach($arrField[WorldGrouponSqlDef::TBL_FIELD_VA_INFO][WorldGrouponSqlDef::HIS_IN_VA_INFO] as $key => $eachHis)
            {
                $eachHis[WorldGrouponSqlDef::BUY_TIME_IN_VA_INFO] -= $day*86400;
                $arrField[WorldGrouponSqlDef::TBL_FIELD_VA_INFO][WorldGrouponSqlDef::HIS_IN_VA_INFO][$key] = $eachHis;
            }
        }
        $arrField[WorldGrouponSqlDef::TBL_FIELD_OPTIME] = Util::getTime() - $day*86400;
        $ret = WorldGrouponDao::updInnerUserInfo($arrField);

        return 'ok';
    }

    public function world_groupon_whole_yesterday()
    {
        $actConf = ActivityConfDao::getCurConfByName(ActivityName::WORLDGROUPON, ActivityDef::$ARR_CONF_FIELD);
        if( empty($actConf) )
        {
            return 'not config worldgroupon activity';
        }
        $now = Util::getTime();

        $actConf['version'] = Util::getTime();
        $actConf['start_time'] -= 86400;
        ActivityConfDao::insertOrUpdate($actConf);
        ActivityConfLogic::updateMem();

        return 'ok';
    }

	public function world_groupon_team()
	{
		//1获取当前时间距离刚刚过去的小时的秒
		/**
        $now = Util::getTime();
		$minu = date('i', $now);
		$sec = ($minu + 2) * 60;
		//2改写WorldGrouponConf时间配置
		system("sed -i 's/0;/1;/' /home/pirate/rpcfw/conf/WorldGrouponConf.cfg.php");
		system("sed -i 's/array(/[0-9]\+,/array($sec,' /home/pirate/rpcfw/conf/WorldGrouponConf.cfg.php");
		//3执行分组脚本
		if(!file_exists("/home/pirate/game/worldgroupon/team.sh"))
		{
			return "/home/pirate/game/worldgroupon/team.sh not exists";
		}
        */
		system("ssh 192.168.1.122 ' sh /home/pirate/game/worldgroupon/team.sh ' ");

		return 'ok';
	}

	public function world_groupon_reward()
	{
		system("ssh 192.168.1.121 ' sh /home/pirate/game/worldgroupon/reward.sh ' ");

		return 'ok';
	}

    public function ClearBlackshop($id)//清空限时折扣宝箱记录
    {
    	$uid = RPCContext::getInstance()->getUid();
    	$obj= new BlackshopManage($uid);
    	$obj->loadData();
    	$obj->setExchangeNum($id, 0);
    	$obj->updateData();
    	return 'ok';
    }
    public function setBlackshopDay($day)//调整黑市时间
    {

    	$secondsDuration = ($day-1) * SECONDS_OF_DAY;
    	$startTime = Util::getTime() - $secondsDuration;

    	$startTimeStamp = intval(strtotime(date('Y-m-d', $startTime)));

    	$actConf = ActivityConfDao::getCurConfByName(ActivityName::BLACKSHOP, ActivityDef::$ARR_CONF_FIELD);
    	if( empty($actConf) )
    	{
    		return 'Act blackshop has no config.';
    	}
    	$daysTime = $actConf['end_time'] - $actConf['start_time'];
    	$actConf['version'] = Util::getTime();
    	$actConf['start_time'] = $startTimeStamp;
    	$actConf['end_time'] = $startTimeStamp + $daysTime;
    	ActivityConfDao::insertOrUpdate($actConf);
    	ActivityConfLogic::updateMem();
    	$conf = EnActivity::getConfByName(ActivityName::BLACKSHOP);
    	$realConf = $conf['data'];//获取配置
    	foreach($realConf as $id => $val)
    	{
    		if(isset($val[MallDef::MALL_EXCHANGE_TYPE]) && $val[MallDef::MALL_EXCHANGE_TYPE]==MallDef::REFRESH_EVERYDAY)
    		{
    			$this->ClearBlackshop($id);
    		}
    	}
    	return 'ok';
    }

	public function addFameNum($num)
	{
		$userObj = EnUser::getUserObj();
		$userObj->addFameNum($num);
		$userObj->update();
		return "ok";
	}

	public function tsScore($num)
	{
		$uid = RPCContext::getInstance()->getUid();
		$tsUser = TravelShopUserObj::getInstance($uid);
		$tsUser->setScore($num);
		$tsUser->update();
	}

	public function tsAddScore($num)
	{
		$uid = RPCContext::getInstance()->getUid();
		$tsUser = TravelShopUserObj::getInstance($uid);
		$tsUser->addScore($num);
		$tsUser->update();
	}

	public function tsSetLastBuyTime($timeStr)
	{
		$uid = RPCContext::getInstance()->getUid();
		$tsUser = TravelShopUserObj::getInstance($uid);
		$time = strtotime($timeStr);
		$tsUser->setRefreshTime($time);
		$tsUser->update();
	}

	public function tsSum($num)
	{
		$ts = TravelShopObj::getInstance();
		$ts->setSum($num);
		$ts->update();
	}

	public function tsReset()
	{
		$uid = RPCContext::getInstance()->getUid();
		$tsUser = TravelShopUserObj::getInstance($uid);
		$tsUser->init($uid);
		$tsUser->update();
	}

	public function setActivityDay($actName, $day)
	{
	    $secondsDuration = ($day-1) * SECONDS_OF_DAY;
	    $startTime = Util::getTime() - $secondsDuration;

	    $startTimeStamp = intval(strtotime(date('Y-m-d', $startTime)));

	    $actConf = ActivityConfDao::getCurConfByName($actName, ActivityDef::$ARR_CONF_FIELD);
	    if( empty($actConf) )
	    {
	        return 'Act '.$actName.' has no config.';
	    }

	    $daysTime = $actConf['end_time'] - $actConf['start_time'];
	    $actConf['version'] = Util::getTime();
	    $actConf['start_time'] = $startTimeStamp;
	    $actConf['end_time'] = $startTimeStamp + $daysTime;
	    ActivityConfDao::insertOrUpdate($actConf);
	    ActivityConfLogic::updateMem();

	    return 'ok';
	}

	public function setMissionBuyYesterday($day = 1)
	{
		$uid = RPCContext::getInstance()->getUid();
		$data = MallDao::select($uid, MallDef::MALL_TYPE_MISSION_SHOP);

		if( empty( $data ) )
		{
			return 'not join actexchange';
		}
		$setTime = Util::getTime() - $day * 86400;

		foreach($data['all'] as $id => $value) {
			if (isset($value['time'])) {
				$data['all'][$id][MallDef::TIME] = $setTime;
			}
		}

		$arrField = array(
			MallDef::USER_ID => $uid,
			MallDef::MALL_TYPE => MallDef::MALL_TYPE_MISSION_SHOP,
			MallDef::VA_MALL => $data,
		);
		MallDao::insertOrUpdate($arrField);
		return 'ok';

	}

	public function worldcompete_setAtkNum($num)
	{
		if ($num < 0)
		{
			return 'num不能是负的';
		}

		$uid = RPCContext::getInstance()->getUid();
		$serverId = Util::getServerIdOfConnection();
		$pid = WorldCompeteUtil::getPid($uid);
		$worldCompeteInnerUserObj = WorldCompeteInnerUserObj::getInstance($serverId, $pid, $uid);
		$worldCompeteInnerUserObj->setAtkNumForConsole($num);
		$worldCompeteInnerUserObj->update();

		return 'worldcompete_setAtkNum ok';
	}

	public function worldcompete_setSucNum($num)
	{
		if ($num < 0)
		{
			return 'num不能是负的';
		}

		$uid = RPCContext::getInstance()->getUid();
		$serverId = Util::getServerIdOfConnection();
		$pid = WorldCompeteUtil::getPid($uid);
		$worldCompeteInnerUserObj = WorldCompeteInnerUserObj::getInstance($serverId, $pid, $uid);
		$worldCompeteInnerUserObj->setSucNumForConsole($num);
		$worldCompeteInnerUserObj->update();

		return 'worldcompete_setSucNum ok';
	}

	public function worldcompete_setBuyAtkNum($num)
	{
		if ($num < 0)
		{
			return 'num不能是负的';
		}

		$uid = RPCContext::getInstance()->getUid();
		$serverId = Util::getServerIdOfConnection();
		$pid = WorldCompeteUtil::getPid($uid);
		$worldCompeteInnerUserObj = WorldCompeteInnerUserObj::getInstance($serverId, $pid, $uid);
		$worldCompeteInnerUserObj->setBuyAtkNumForConsole($num);
		$worldCompeteInnerUserObj->update();

		return 'worldcompete_setBuyAtkNum ok';
	}

	public function worldcompete_setRfrNum($num)
	{
		if ($num < 0)
		{
			return 'num不能是负的';
		}

		$uid = RPCContext::getInstance()->getUid();
		$serverId = Util::getServerIdOfConnection();
		$pid = WorldCompeteUtil::getPid($uid);
		$worldCompeteInnerUserObj = WorldCompeteInnerUserObj::getInstance($serverId, $pid, $uid);
		$worldCompeteInnerUserObj->setRefreshNumForConsole($num);
		$worldCompeteInnerUserObj->update();

		return 'worldcompete_setRfrNum ok';
	}

	public function worldcompete_setWorshipNum($num)
	{
		if ($num < 0 && $num > WorldCompeteConf::WORSHIP_LIMIT)
		{
			return 'num不能是负的且膜拜次数不能超过2次';
		}

		$uid = RPCContext::getInstance()->getUid();
		$serverId = Util::getServerIdOfConnection();
		$pid = WorldCompeteUtil::getPid($uid);
		$worldCompeteInnerUserObj = WorldCompeteInnerUserObj::getInstance($serverId, $pid, $uid);
		$worldCompeteInnerUserObj->setWorshipNumForConsole($num);
		$worldCompeteInnerUserObj->update();

		return 'worldcompete_setWorshipNum ok';
	}

	public function worldcompete_setMaxHonor($num)
	{
		if ($num < 0)
		{
			return 'num不能是负的';
		}

		$uid = RPCContext::getInstance()->getUid();
		$serverId = Util::getServerIdOfConnection();
		$pid = WorldCompeteUtil::getPid($uid);
		$worldCompeteInnerUserObj = WorldCompeteInnerUserObj::getInstance($serverId, $pid, $uid);
		$worldCompeteInnerUserObj->setMaxHonorForConsole($num);
		$worldCompeteInnerUserObj->update();
		$teamId = WorldCompeteUtil::getTeamIdByServerId($serverId);
		$worldCompeteCrossUserObj = WorldCompeteCrossUserObj::getInstance($serverId, $pid, $uid, $teamId, true);
		$worldCompeteCrossUserObj->setMaxHonorForConsole($num);
		$worldCompeteCrossUserObj->update();

		return 'worldcompete_setMaxHonor ok';
	}

	public function worldcompete_setCrossHonor($num)
	{
		if ($num < 0)
		{
			return 'num不能是负的';
		}

		$uid = RPCContext::getInstance()->getUid();
		$serverId = Util::getServerIdOfConnection();
		$pid = WorldCompeteUtil::getPid($uid);
		$worldCompeteInnerUserObj = WorldCompeteInnerUserObj::getInstance($serverId, $pid, $uid);
		$worldCompeteInnerUserObj->setCrossHonorForConsole($num);
		$worldCompeteInnerUserObj->update();

		return 'worldcompete_setCrossHonor ok';
	}

	public function worldcompete_resetPrize()
	{
		$uid = RPCContext::getInstance()->getUid();
		$serverId = Util::getServerIdOfConnection();
		$pid = WorldCompeteUtil::getPid($uid);
		$worldCompeteInnerUserObj = WorldCompeteInnerUserObj::getInstance($serverId, $pid, $uid);
		$worldCompeteInnerUserObj->resetPrizeForConsole();
		$worldCompeteInnerUserObj->update();

		return 'worldcompete_resetPrize ok';
	}

	public function worldcompete_resetTime()
	{
		$uid = RPCContext::getInstance()->getUid();
		$serverId = Util::getServerIdOfConnection();
		$pid = WorldCompeteUtil::getPid($uid);
		$arrUpdate[WorldCompeteInnerUserField::FIELD_UPDATE_TIME] = Util::getTime() - SECONDS_OF_DAY;
		$arrCond = array
		(
				array(WorldCompeteInnerUserField::FIELD_SERVER_ID, '=', $serverId),
				array(WorldCompeteInnerUserField::FIELD_PID, '=', $pid),
		);
		WorldCompeteDao::updateInnerUser($arrCond, $arrUpdate);

		return 'worldcompete_resetTime ok';
	}

	public function worldcompete_resetBuyNum()
	{
		$uid = RPCContext::getInstance()->getUid();
		$exchangeInfo = MallDao::select($uid, MallDef::MALL_TYPE_WORLDCOMPETE_SHOP);
		if (!empty($exchangeInfo[MallDef::ALL]))
		{
			foreach ($exchangeInfo[MallDef::ALL] as $exchangeId => $info)
			{
				$info['time'] -= 7 * SECONDS_OF_DAY;
				$exchangeInfo[MallDef::ALL][$exchangeId] = $info;
			}
			$arrField = array(
					MallDef::USER_ID => $uid,
					MallDef::MALL_TYPE => MallDef::MALL_TYPE_WORLDCOMPETE_SHOP,
					MallDef::VA_MALL => $exchangeInfo,
			);
			MallDao::insertOrUpdate($arrField);
		}
		return 'worldcompete_resetBuyNum ok';
	}

	public function setDesactDay($day)
	{
	    if ($day <= 0)
	    {
	        return 'invalid args';
	    }

	    $confList = DesactDao::getLastCrossConfig(array('sess', 'update_time', 'version', 'va_config'));

	    if (empty($confList) || empty($confList[0]))
	    {
	        return 'no conf in cross db';
	    }

	    $conf = $confList[0];

	    $conf[DesactCrossDef::SQL_UPDATE_TIME] = Util::getTime() - ($day-1) * SECONDS_OF_DAY;

	    DesactDao::updateCrossConfig($conf[DesactCrossDef::SQL_SESS], $conf);

	    return 'ok';
	}

	public function setDesactNum($id, $num)
	{
	    $uid = RPCContext::getInstance()->getUid();

	    $info = DesactDao::getDesactUser($uid, DesactDef::$ARR_INNER_DESACT_FIELDS);

	    $info['uid'] = $uid;
	    $info[DesactDef::SQL_UPDATE_TIME] = Util::getTime();

	    if (empty($info['va_data']))
	    {
	        $info['va_data'] = array('taskInfo'=>array());
	    }

	    if (empty($info['va_data']['taskInfo'][$id]))
	    {
	        $info['va_data']['taskInfo'][$id] = array('num'=>$num);
	    }
	    else
	    {
	        $info['va_data']['taskInfo'][$id]['num'] = $num;
	    }

	    DesactDao::updateDesact($uid, $info);

	    return 'ok';
	}

	public function clearDesact($id=0)
	{
	    $uid = RPCContext::getInstance()->getUid();

	    $info = DesactDao::getDesactUser($uid, DesactDef::$ARR_INNER_DESACT_FIELDS);

	    if (empty($id))
	    {
	        $info['update_time'] = 0;
	    }

	    if (!empty($info['va_data']['taskInfo'][$id]['rewarded']))
	    {
	        $info['va_data']['taskInfo'][$id]['rewarded'] = array();
	    }

	    DesactDao::updateDesact($uid, $info);

	    return 'ok';
	}

    // "欢乐签到"相关
	public function resetHappySign()
	{
		if (!FrameworkConfig::DEBUG)
		{
			return 'fail,rpcfw not in debug pattern';
		}
		if (!EnActivity::isOpen(ActivityName::HAPPYSIGN))
		{
			return 'fail, activity:happySign is not opened';
		}
		$uid = RPCContext::getInstance()->getUid();
		$arrSelect = HappySignDao::getInfo($uid);
		$arrSelect[HappySignDef::VA_REWARD] = array();
		$arrSelect[HappySignDef::LOGIN_NUM] = 0;
		HappySignDao::update($uid, $arrSelect);
		return 'success';
	}

	public function setHappySignDays($days)
	{
		if (!FrameworkConfig::DEBUG)
		{
			return 'fail,rpcfw not in debug pattern';
		}
		if (!EnActivity::isOpen(ActivityName::HAPPYSIGN))
		{
			return 'fail, activity:happySign is not opened';
		}
		$uid = RPCContext::getInstance()->getUid();
		$arrSelect = HappySignDao::getInfo($uid);
		$arrSelect[HappySignDef::LOGIN_NUM] = $days;
		HappySignDao::update($uid, $arrSelect);
		return 'success';
	}

	public function setHappySignTime($date, $time)
	{
		if (!FrameworkConfig::DEBUG)
		{
			return 'fail,rpcfw not in debug pattern';
		}
		if (!EnActivity::isOpen(ActivityName::HAPPYSIGN))
		{
			return 'fail, activity:happySign is not opened';
		}
		$setTime = strtotime($date.$time);
		if (!$setTime)
		{
			throw new FakeException( 'invalid time format, please use yyyymmdd hhmmss' );
		}
		$uid = RPCContext::getInstance()->getUid();
		$arrSelect = HappySignDao::getInfo($uid);
		$arrSelect[HappySignDef::FIRST_LOGIN_TIME] = $setTime;
		HappySignDao::update($uid, $arrSelect);
		return 'success';
	}

	// "充值送礼"相关
	public function resetRechargeGift()
	{
		if (!FrameworkConfig::DEBUG)
		{
			return 'fail,rpcfw not in debug pattern';
		}
		if (!EnActivity::isOpen(ActivityName::RECHARGEGIFT))
		{
			return 'fail, activity rechargeGift is not opened';
		}
		$uid = RPCContext::getInstance()->getUid();
		$arrSelect = RechargeGiftDao::getAllInfo($uid);
		$arrSelect[RechargeGiftDef::VA_REWARD] = array();
		RechargeGiftDao::update($uid, $arrSelect);
		return 'success';
	}

	public function setRGUpdateTime($date, $time)
	{
		if (!FrameworkConfig::DEBUG)
		{
			return 'fail,rpcfw not in debug pattern';
		}
		if (!EnActivity::isOpen(ActivityName::RECHARGEGIFT))
		{
			return 'fail, activity rechargeGift is not opened';
		}
		$setTime = strtotime($date.$time);
		if (!$setTime)
		{
			throw new FakeException( 'invalid time format, please use yyyymmdd hhmmss' );
		}
		$uid = RPCContext::getInstance()->getUid();
		$arrSelect = RechargeGiftDao::getAllInfo($uid);
		$arrSelect[RechargeGiftDef::UPDATE_TIME] = $setTime;
		RechargeGiftDao::update($uid, $arrSelect);
		return 'success';
	}

	public function resetFsReborn()
	{
		$uid = RPCContext::getInstance()->getUid();
		$fs = FsRebornObj::getInstance($uid);
		$fs->addNum(-$fs->getNum());
		$fs->update();
		return 'ok';
	}

	/** 国战相关控制台开始 **/
	// 开启国战
	public function openCw()
	{
		popen("ssh 192.168.1.121 '/bin/sh /home/pirate/bin/setCountry.sh'", 'r');
	}
	// 增加国战积分
	public function addCopoint($copoint)
	{
		$serverId = Util::getServerId();
		$uid = RPCContext::getInstance()->getUid();
		$pid = EnUser::getUserObj($uid)->getPid();
		$CWCUObj = CountryWarCrossUser::getInstance($serverId, $pid);
		$CWCUObj->addCopoint($copoint);
		$CWCUObj->update();
		return 'add success';
	}
	// 设置国战商店商品的上次购买时间为一周前
	public function resetCWShopWeekGoodsBuyNum()
	{
		$uid = RPCContext::getInstance()->getUid();
        $data = MallDao::select($uid, MallDef::MALL_TYPE_COUNTRYWAR_SHOP);

        if( empty( $data ) )
        {
            return 'not join countryWar shop';
        }
        foreach($data[MallDef::ALL] as $goodId => $goodValue)
        {
            $goodValue[MallDef::TIME] -= SECONDS_OF_DAY * 7;
        }

        $arrField = array(
            MallDef::USER_ID => $uid,
            MallDef::MALL_TYPE => MallDef::MALL_TYPE_COUNTRYWAR_SHOP,
            MallDef::VA_MALL => $data,
        );
        MallDao::insertOrUpdate($arrField);
        return 'success';
    }

	// 国设置国战商店商品的上次购买时间为一天前
	public function resetCWShopDayGoodsBuyNum()
	{
		$uid = RPCContext::getInstance()->getUid();
		$CWShopObj = CountryWarShopManager::getInstance($uid);
		$goodsArr = $CWShopObj->getInfo();
		foreach ($goodsArr as $goodsId => $data)
		{
			$buyTime = $data[MallDef::TIME];
			$buyTime -= SECONDS_OF_DAY;
			$CWShopObj->setExchangeTime($goodsId, $buyTime);
		}
		$CWShopObj->update();
		return 'success';
	}
	// 增加国战初赛积分
	public function addCwAuditionPoints($num)
	{
		$serverId = Util::getServerId();
		$uid = RPCContext::getInstance()->getUid();
		$pid = EnUser::getUserObj($uid)->getPid();
		$crossUser = CountryWarCrossUser::getInstance($serverId, $pid);
		$crossUser->addAuditionPoint($num);
		$crossUser->update();
	}
	/** 国战相关控制台结束 **/

	// 首充重置
	public function delLastOrder($num=1)
	{
	    $uid = RPCContext::getInstance()->getUid();

	    $table = 't_bbpay_gold';

	    $arrField = array(
	        'order_id','uid','gold_num','gold_ext'
	    );

	    $data = new CData();

	    $arrOrder = $data->select($arrField)
	                   ->from($table)
	                   ->where(array('uid', '=', $uid))
	                   ->orderBy('mtime', FALSE)
	                   ->limit(0, $num)
	                   ->query();

	    $data = new CData();

	    $arrValue = array('uid'=>1);

	    foreach ($arrOrder as $order)
	    {
	        $data->update($table)
	               ->set($arrValue)
	               ->where('order_id','==',$order['order_id'])
	               ->where('uid', '=', $uid)
	               ->query();
	    }

	    return 'ok';
	}

	public function clearChargeInfo($gold = 0)
	{
	    $uid = RPCContext::getInstance()->getUid();

	    $userObj = EnUser::getUserObj($uid);

	    $arrChargeInfo = $userObj->getChargeInfo();

	    $data = new CData();
	    if ( empty( $gold ) )
	    {
	        $arrChargeInfo = array();
	    }
	    else
	    {
	        if ( !isset( $arrChargeInfo[$gold] ) )
	        {
	            return 'no charge info';
	        }
	        else
	        {
	            unset( $arrChargeInfo[$gold] );
	        }
	    }

	    $arrUdt = array(
	        'va_charge_info' => $arrChargeInfo,
	    );

	    $data->update('t_user')
    	    ->set($arrUdt)
    	    ->where( array( 'uid', '=', $uid ) )
    	    ->query();

	    $userObj->setFields($arrUdt);
	    $userObj->updateSession();

	    return 'ok';
	}

	public function  resetOneMall($mall_type)
	{
		$uid = RPCContext::getInstance()->getUid();

		$arrField = array(
				MallDef::USER_ID => $uid,
				MallDef::MALL_TYPE => $mall_type,
				MallDef::VA_MALL => array(),
		);

		MallDao::insertOrUpdate($arrField);

		return 'resetShop ok';
	}

	public function clearTallyBook()
	{
		$uid = RPCContext::getInstance()->getUid();

		ItemBookDao::updateTallyBook($uid, array('tally' => array()));
		RPCContext::getInstance()->setSession(ShowDef::TALLY_SESSION, array());

		return 'ok';
	}

    public function clrMasterTalent()
    {
        $uid = RPCContext::getInstance()->getUid();
        $userObj = EnUser::getUserObj($uid);
        $masterHid = $userObj->getMasterHid();

        $heroInfo = HeroDao::getByHid($masterHid, HeroDef::$HERO_FIELDS);
        if(isset($heroInfo['va_hero'][HeroDef::VA_FIELD_MATER_TALENT]))
        {
            unset($heroInfo['va_hero'][HeroDef::VA_FIELD_MATER_TALENT]);
        }

        HeroDao::update($masterHid, $heroInfo);
    }

    public function clrOneRechargeAll()
    {
    	$uid = RPCContext::getInstance()->getUid();
    	$conf =  EnActivity::getConfByName(ActivityName::ONERECHARGE);
    	$startTime = $conf['start_time'];
    	$data = new CData();
    	$goldOrder = $data->select(array('order_id','gold_num','mtime'))
					    	->from('t_bbpay_gold')
					    	->where('uid', '=', $uid)
					    	->where('mtime', 'BETWEEN', array($startTime, Util::getTime()))
					    	->query();

    	foreach ($goldOrder as $index => $order)
    	{
    		$arrField = array(
    				'order_id' => $order['order_id'],
    				'gold_num' => 0,
    				'mtime' => $order['mtime'],
    		);
    		$data->update('t_bbpay_gold')->set($arrField)->where(array('order_id', '==', $order['order_id']))->query();
    	}


    	$itemOrder = $data->select(array('order_id','gold_num','mtime'))
					    	->from('t_bbpay_item')
					    	->where('uid', '=', $uid)
					    	->where('mtime', 'BETWEEN', array($startTime, Util::getTime()))
					    	->query();
    	foreach ($itemOrder as $index => $order)
    	{
    		$arrField = array(
    				'order_id' => $order['order_id'],
    				'gold_num' => 0,
    				'mtime' => $order['mtime'],
    		);
    		$data->update('t_bbpay_item')->set($arrField)->where(array('order_id', '==', $order['order_id']))->query();
    	}
    	$fields = OneRechargeDao::getInfo($uid);
    	$fields[OneRechargeDef::VA_INFO][OneRechargeDef::VA_REWARD] = array();
    	OneRechargeDao::update($uid, $fields);

    	return 'success';
    }

    public function clrOneRechargeReward()
    {
    	$uid = RPCContext::getInstance()->getUid();
    	$fields = OneRechargeDao::getInfo($uid);
    	$fields[OneRechargeDef::VA_INFO][OneRechargeDef::VA_REWARD] = array();
    	OneRechargeDao::update($uid, $fields);
    	return 'success';
    }

    //木牛流马控制台
    public function beginshipping($stageId,$pageId = 0,$roadId = 0)
    {
        $uid = RPCContext::getInstance()->getUid();
        $userChargeDartObj = MyChargeDart::getInstance($uid);
        $userChargeDartObj->changeStage($stageId);
        if($stageId <= 0 || $stageId > 4)
        {
            return 'stageid err!';
        }
        $chargeDartInfo = $userChargeDartObj->havingChargeDart();
        //当前有镖车则不能再次押镖
        if (!empty($chargeDartInfo))
        {
            return 'AlreadyHaveChargeDart!';
        }
        $intervalTime = intval(btstore_get()->CHARGEDART_RULE[ChargeDartDef::CSV_INTERVAL]);
        if ($pageId <= 0 || $pageId > 100 || $roadId <= 0 || $roadId > 3)
        {
            $beginInfo = ChargeDartDao::getFirstFreeRoad(Util::getTime()-$intervalTime, $stageId);
            $pageId = $beginInfo[ChargeDartDef::SQL_PAGE_ID];
            $roadId = $beginInfo[ChargeDartDef::SQL_ROAD_ID];
        }

        //设定timer
        $beginTime = Util::getTime();
        $lastTime = intval(btstore_get()->CHARGEDART_RULE[ChargeDartDef::CSV_LAST_TIME]);
        $tid = TimerTask::addTask($uid, $beginTime+$lastTime, 'chargedart.__sendReward', array($uid,$beginTime));

        $userChargeDartObj->beginChargeDart($pageId, $roadId, $tid);

        $userChargeDartObj->save();
        return 'ok';
    }

    public function clearAllUserNum()
    {
        $uid = RPCContext::getInstance()->getUid();
        $userChargeDartObj = MyChargeDart::getInstance($uid);
        $userChargeDartObj->changeByCrossDay();
        $userChargeDartObj->save();
        return 'ok';
    }

    public function clearChargedartInfo()
    {
        $uid = RPCContext::getInstance()->getUid();
        $userChargeDartObj = MyChargeDart::getInstance($uid);
        $userChargeDartObj->clearChargeDartInfo();
        $userChargeDartObj->save();
        return 'ok';
    }

    public function setDarkCheck($num)
    {
        if ($num < 0 || $num > intval(btstore_get()->CHARGEDART_RULE[ChargeDartDef::CSV_REFRESH_DARK_CHECK]))
        {
            return 'arg err!';
        }
        $uid = RPCContext::getInstance()->getUid();
        $userChargeDartObj = MyChargeDart::getInstance($uid);
        $userChargeDartObj->clearChargeDartInfo();
        for ($i = 0;$i < $num;$i ++)
        {
            $userChargeDartObj->addDarkCheck();
        }
        $userChargeDartObj->save();
        return 'ok';
    }

    public function addSomeChargeDart()
    {
        $uids = func_get_args();
        if(empty($uids))
        {
            $uids = array(20897,20197,20513,62175,108493,63896);
        }

        foreach ($uids as $uid)
        {
            $userChargeDartObj = MyChargeDart::getInstance($uid);
            $userChargeDartObj->clearChargeDartInfo();
            $userChargeDartObj->save();
            ChargeDartLogic::beginShipping($uid);
        }

        return 'ok';
    }

    public function stuffOnePageWhitChargeDart($stageId, $pageId, $roadnum)
    {
        $nowTime = Util::getTime();
        $lastTime = intval(btstore_get()->CHARGEDART_RULE[ChargeDartDef::CSV_LAST_TIME]);
        $intervalTime = intval(btstore_get()->CHARGEDART_RULE[ChargeDartDef::CSV_INTERVAL]);

        $uids = array(
            20295,20425,20711,
            21849,22349,22515,
            22936,23265,23384,
            23448,23665,23774,
            24735,26940,27325,
            27656,28991,30283,
            30811,31286,31437,
            36493,36544,37704,
            37801,39942,40183,
            40435,40681,42959,
            43644,45993,46181,
            46308,46832,47680,
            47989,49131,49432,
        );

        for ($i = 0;$i < $lastTime/$intervalTime; $i ++)
        {
            for ($j = 0;$j < $roadnum;$j ++)
            {
                $uid = $uids[$i*$roadnum+$j];
                $beginTime = $nowTime - $i * $intervalTime;
                $tid = TimerTask::addTask($uid, $beginTime+$lastTime, 'chargedart.__sendReward', array($uid,$beginTime));
                $userChargeDartObj = MyChargeDart::getInstance($uid);
                $userChargeDartObj->clearChargeDartInfo();
                $userChargeDartObj->changeStage($stageId);
                $userChargeDartObj->beginChargeDart($pageId, $j+1, $tid, $beginTime);
                $userChargeDartObj->save();
            }
        }

        for ($i = 1;$i <= $roadnum;$i++)
        {
            $data = new CData();

            $arrFeild = array(
                ChargeDartDef::SQL_PREVIOUS_TIME => $nowTime,
            );

            $data->update('t_charge_dart_road')->set($arrFeild)
            ->where(array(ChargeDartDef::SQL_STAGE_ID,'=',$stageId))
            ->where(array(ChargeDartDef::SQL_PAGE_ID,'=',$pageId))
            ->where(array(ChargeDartDef::SQL_ROAD_ID,'=',$i));

            $arrRet = $data->query();
        }

        return 'ok';
    }

    public function setOpenServerDay($YMD, $time)
    {
    	$openSeverYMD =  $YMD;
    	$openSeverTime = $time;
    	$group = RPCContext::getInstance()->getFramework()->getGroup();
    	popen("/bin/sed -i '/SERVER_OPEN_YMD/{s/[0-9-]\+/$openSeverYMD/;}' /home/pirate/rpcfw/conf/gsc/$group/Game.cfg.php", 'r');
    	popen("/bin/sed -i '/SERVER_OPEN_TIME/{s/[0-9-]\+/$openSeverTime/;}' /home/pirate/rpcfw/conf/gsc/$group/Game.cfg.php", 'r');
    	return "set open server time success.";
    }

    public function setNewServerTaskStatus($taskId, $console)
    {
    	$uid = RPCContext::getInstance()->getUid();
    	$manager = NewServerActivityManager::getInstance($uid);
    	$status = NewServerActivityDef::WAIT;

    	switch ($console)
    	{
    		case 1:
    			$status = NewServerActivityDef::COMPLETE;
    			break;
    		case 2:
    			$status = NewServerActivityDef::REWARDED;
    			break;
    	}
    	$manager->setTaskStatus($taskId, $status);
    	$manager->save();
    }

    public function resetNewServerTask()
    {
    	$data = new CData();
    	$uid = EnUser::getUserObj()->getUid();
    	$ret = NewServerActivityDao::getData($uid);
    	if(empty($ret))
    	{
    		return 'data is empty, dont need reset';
    	}
    	$arrField = array(
    			NewServerActivitySqlDef::UID => $uid,
    			NewServerActivitySqlDef::VA_INFO => array(),
    	);
    	$ret = $data->update(NewServerActivitySqlDef::T_NEW_SERVER_ACT)
			    	->set($arrField)
			    	->where(array(NewServerActivitySqlDef::UID, '=', $uid))
			    	->query();
    	if ( $ret[DataDef::AFFECTED_ROWS] == 0 )
    	{
    		return 'fail';
    	}
    	return 'success';
    }

    public function resetNewServerGoods()
    {
    	$data = new CData();
    	$uid = EnUser::getUserObj()->getUid();
    	$ret = NewServerActivityDao::getData($uid);
    	if(empty($ret))
    	{
    		return 'data is empty, dont need reset';
    	}
    	$arrField = array(
    			NewServerActivitySqlDef::UID => $uid,
    			NewServerActivitySqlDef::VA_GOODS => array(),
    	);
    	$ret = $data->update(NewServerActivitySqlDef::T_NEW_SERVER_ACT)
			    	->set($arrField)
			    	->where(array(NewServerActivitySqlDef::UID, '=', $uid))
			    	->query();
    	if ( $ret[DataDef::AFFECTED_ROWS] == 0 )
    	{
    		return 'fail';
    	}

    	for ($day = 1; $day <= 7; ++$day)
    	{
    		$arrField = array(
    				NewServerActivitySqlDef::DAY => $day,
    				NewServerActivitySqlDef::BUY_NUM => 0,
    		);

    		$ret = $data->insertOrUpdate(NewServerActivitySqlDef::T_NEW_SERVER_GOODS)
			    		->where(array(NewServerActivitySqlDef::DAY, '=', $day))
			    		->values($arrField)
			    		->query();
    		if ( $ret[DataDef::AFFECTED_ROWS] == 0 )
    		{
    			return 'fail';
    		}
    	}

    	return 'success';
    }

    public function resetFestivalAct()
    {
        $uid = EnUser::getUserObj()->getUid();
        $ret = FestivalActDao::select($uid, FestivalActDef::$ALL_TABLE_FIELD);
        if(empty($ret))
        {
            return 'data is empty, dont need reset';
        }
        $arrField = array(
	        FestivalDef::UID => $uid,
	        FestivalDef::UPDATE_TIME => Util::getTime(),
	        FestivalDef::VA_DATA => array(),
	    );
        $ret =  FestivalActDao::update($uid, $arrField);
        return 'success';
    }

	public function notifyByTypeId($typeId, $num)
	{
		$uid = EnUser::getUserObj()->getUid();
		EnFestivalAct::notify($uid, $typeId, $num);
		return 'ok';
	}

    public function clearElvesOccupyInfo()
    {
    	$uid=RPCContext::getInstance()->getUid();
    	MineralElvesLogic::clearOccupyInfo($uid);
    	return 'ok';
    }
    
    public function clearChariotInfo()
    {
    	$uid=RPCContext::getInstance()->getUid();
    	//读背包里面的战车数据
    	$bag=BagManager::getInstance()->getBag($uid);
    	$arrItemId = $bag->getItemIdsByItemType(ItemDef::ITEM_TYPE_CHARIOT);
    	foreach ($arrItemId as $itemId)
    	{
    		$bag->deleteItem($itemId);
    	}
    	$userObj=EnUser::getUserObj($uid);
    	$masterHeroObj=$userObj->getHeroManager()->getMasterHeroObj();
    	$chariotInfo=$masterHeroObj->getEquipByType(HeroDef::EQUIP_CHARIOT);
    	foreach ($chariotInfo as $pos=>$id)
    	{
    		ChariotLogic::unequip($pos, $id, $uid);
    	}
    	$arrItemId = $bag->getItemIdsByItemType(ItemDef::ITEM_TYPE_CHARIOT);
    	foreach ($arrItemId as $itemId)
    	{
    		$bag->deleteItem($itemId);
    	}
    	$chariotBook=array();
    	ItemBookDao::updateChariotBook($uid, array('chariot' => $chariotBook));
    	RPCContext::getInstance()->setSession(ShowDef::CHARIOT_SESSION, $chariotBook);
    	
    	$userObj->update();
    	$bag->update();
    	return 'ok';
    }
    
    public function addTowerNum($num)
    {
        $num = intval( $num );
        $uid = RPCContext::getInstance()->getUid();
        
        $userObj = EnUser::getUserObj($uid);
        $userObj->addTowerNum($num);
        $userObj->update();
        
        return 'ok';
    }
    
    public function setHellResetNum($num)
    {
        $num = intval( $num );
        
        $towerInfo = MyTower::getInstance()->getTowerInfo();
        $towerInfo[TOWERTBL_FIELD::RESET_HELL] = $num;
        TowerDAO::save($towerInfo[TOWERTBL_FIELD::UID], $towerInfo);
    }
    
    public function passHellTowerLevel($level)
    {
        MyTower::getInstance()->passHellLevel($level);
        MyTower::getInstance()->save();
    }
    
    public function setHellCurLv($level)
    {
        $towerInfo = MyTower::getInstance()->getTowerInfo();
        $towerInfo[TOWERTBL_FIELD::CUR_HELL] = $level;
        
        $maxLevel = count(btstore_get()->HELL_TOWER_LEVEL->toArray());
        if ($level < $maxLevel)
        {
            $towerInfo[TOWERTBL_FIELD::VA_TOWER_INFO][TOWERTBL_FIELD::VA_TOWER_HELL_STATUS] = 0;
        }
        
        TowerDAO::save($towerInfo[TOWERTBL_FIELD::UID], $towerInfo);
    }
    
    public function setHellFailNum($num)
    {
        $num = intval( $num );
        
        $towerInfo = MyTower::getInstance()->getTowerInfo();
        $towerInfo[TOWERTBL_FIELD::CAN_FAIL_HELL] = $num;
        TowerDAO::save($towerInfo[TOWERTBL_FIELD::UID], $towerInfo);
    }
    
    public function addSevensPoint($num)
    {
    	$uid = RPCContext::getInstance()->getUid();
    	$sl = SevensLotteryObj::getInstance($uid);
    	$sl->addPoint($num);
    	$sl->update();
    	return 'ok';
    }
    
    public function resetSevensNum()
    {
    	$uid = RPCContext::getInstance()->getUid();
    	$sl = SevensLotteryObj::getInstance($uid);
    	$sl->setNum(0);
    	$sl->update();
    	return 'ok';
    }
    
    public function addSevensLucky($num)
    {
    	$uid = RPCContext::getInstance()->getUid();
    	$sl = SevensLotteryObj::getInstance($uid);
    	$sl->addLucky($num);
    	$sl->update();
    	return 'ok';
    }
    
    public function resetSevensShop()
    {
    	$uid = RPCContext::getInstance()->getUid();
    
    	$arrField = array(
    			MallDef::USER_ID => $uid,
    			MallDef::MALL_TYPE => MallDef::MALL_TYPE_SEVENS_SHOP,
    			MallDef::VA_MALL => array(),
    	);
    
    	MallDao::insertOrUpdate($arrField);
    
    	return 'ok';
    }

    public function welcomebackSetActivity($status)
    {
    	$wObj = WelcomebackObj::getInstance();
    	switch ($status)
    	{
    		case 'open':
    			if ($wObj->isOpen())
    				return 'err, already open';
    			$wObj->initData();
    			break;
    		case 'close':
    			if (!$wObj->isOpen())
    				return 'err, already close';
    			$wObj->setEndTime(Util::getTime());
    			break;
    		case 'resetAll':
    			if (!$wObj->isOpen())
    				return 'err, activity is close';
    			$this->welcomebackSetActivity('resetGift');
    			$this->welcomebackSetActivity('resetTask');
    			$this->welcomebackSetActivity('resetRecharge');
    			$this->welcomebackSetActivity('resetShop');
    			break;
    		case 'resetGift':
    			if (!$wObj->isOpen())
    				return 'err, activity is close';
    			$gifts = $wObj->getGiftInfo();
    			foreach ($gifts as $id => $value)
    				$wObj->setGiftUnGained($id);
    			break;
    		case 'resetTask':
    			if (!$wObj->isOpen())
    				return 'err, activity is close';
    			$tasks = $wObj->getTaskInfo();
    			foreach ($tasks as $id => $task)
    				$wObj->setTaskFinishedTimes($id, 0);
    			break;
    		case 'resetRecharge':
    			if (!$wObj->isOpen())
    				return 'err, activity is close';
    			$recharges = $wObj->getRechargeInfo();
    			foreach ($recharges as $id => $recharge)
    			{
    				$wObj->setRechargeHadReward($id, 0, -1);
    				$wObj->setRechargeToReward($id, 0);
    			}
    			$wObj->setRechargeUpdateTime(Util::getTime());
    			break;
    		case 'resetShop':
    			if (!$wObj->isOpen())
    				return 'err, activity is close';
    			$shops = $wObj->getShopInfo();
    			foreach ($shops as $id => $value)
    				$wObj->setShopBuyTimes($id, 0);
    			break;
    		default:
    			return 'not support';
    	}
    	$wObj->update();
    	return 'ok';
    }
    
    public function welcomebackInfo()
    {
    	$wObj = WelcomebackObj::getInstance();
    	if ($wObj->isOpen())
    		return $wObj->getInfo();
    	else
    		return 'activity is not open, cant get info';
    }
    
    public function welcomebackOpen($offlineTime)
    {
    	$offlineTimeStamp = strtotime($offlineTime);//时间戳
    	$wObj = WelcomebackObj::getInstance();
    	if ($wObj->isOpen())
    		return 'err, already open';
    	$wObj->initData();
    	$wObj->setOfflineTime($offlineTimeStamp);
    	$wObj->update();
    	return 'ok';
    }
    
    public function welcomebackSetEndTime($endTime)
    {
    	$endTimeStamp = strtotime($endTime);//时间戳
    	$wObj = WelcomebackObj::getInstance();
    	if (!$wObj->isOpen())
    		return 'err, activity is close';
    	$wObj->setEndTime($endTimeStamp);
    	$wObj->update();
    	return 'ok';
    }
    
    public function welcomebackDoTask($taskTypeId, $num)
    {
    	$welcomebackObj = WelcomebackObj::getInstance();
		$welcomebackObj->updateTask($taskTypeId, $num);
		return 'ok';
    }
    
    public function welcomebackGainReward($id, $selectId)
    {
    	$uid = EnUser::getUserObj()->getUid();
    	WelcomebackLogic::gainReward($uid, $id, $selectId);
    	return 'ok';
    }
    
	public function help()
	{
		return '显示帮助信息：help
		user UID FUNCTION ARGV 后端自测用的指令
		uid: 查看uid
		pid: 查看pid
		silver N: 设置银币
		gold N: 设置金币
		vip N: 设置vip等级
		level N: 设置等级
		experience N: 设置经验
		addExp N:添加经验
		soul N:设置将魂
		prestige N:设置声望值
		jewel N:设置魂玉值
		tg N:设置天工令
		wm N:设置争霸令
		jh N:设置武将精华值
		book N:设置赤卷天书值

		execution clear: 重置购买体力限制
		execution view：查看后端体力
		execution N：设置体力
		setExectuion N:设置行动力
		setStamina N：设置耐力

		addGoldOrder gold_num date=0   充值,第二个参数是设置充值时间   如果没有传就默认是今天
		addVipExp vip_num 用于增加vip经验，不增加金币

		fightforce 战斗力值
		maxFightForce 最大战斗力值
		setLastLoginTime 参数：N,$pid(用户账号,默认为0是当前用户)  设置某个用户上次登录时间为当前时间之前N秒,
		setLastOffTime  参数：N,$pid(用户账号,默认为0是当前用户) 	设置某个用户上次离线时间为当前时间之前N秒
		setOpenServerDay	重置开服时间   格式：日期   时间(时分秒)	例如：setOpenServerDay 20150824 113000

		背包:
		bagInfo			获得背包信息
		gridInfo 		参数：$gid(格子id) 	得到某个格子的物品信息
		clearBag		清空背包
		openBag			参数：$gridNum(格子数量),$bagType(1装备2道具)	  花金币开背包格子
		addItem 		参数：$itemTplId(物品id),$itemNum(物品数量)	加物品,不可叠加物品一次最多加500个
		dropItem 		参数：$dropId(掉落表id),$number(次数)	调用掉落表
		setTreasLv		参数：$level(宝物等级) 设置所有宝物等级

		普通副本模块：
		getNCopyList											获取所有的普通副本列表
		passNBaseLevel	参数：$baseId,$baseLevel			                      通关据点的某个难度级别
		passNCopy		参数：$copyId								通关副本的所有据点的普通难度即通关副本
		passNCopies     参数：$copyId,$baseLevel=3                                         通关副本之前的所有副本的某难度，开启此副本  baselevel的取值1,2,3 1表示通关简单难度，2表示通关普通难度，3表示通关困难难度，默认是3
		getPrize		参数：$copyId,$caseID						获取副本的箱子奖励
		unDoGetPrize	参数：$copyId,$caseID						将某个已经领取的箱子奖励更改为未领取
		resetNCopy		参数：$copyId								重置副本,删除之后的所有副本  将副本的状态更改为初始状态（只有第一个据点可攻击）
		resetECopy      参数：$ecopyId                            重置精英副本状态为可攻击，删除此副本之后的所有精英副本以及相关普通副本
		resetBase       参数：$baseId                            重置据点 将据点状态改成可攻击
		resetEAtkNum                                            重置精英副本攻击次数
		resetSweepCd                                            重置扫荡时间

		精英副本模块：
		getECopyInfo	参数：									获取玩家的精英副本信息
		passECopy		参数：$copyId								通关某个精英副本
		setECopyStatus	参数：$copyId,$status						$status的取值0可显示1可攻击2通关 更改某个精英副本的状态

		活动副本模块：
		getACopyList				获取所有的活动副本信息
		resetAAtkNum     参数：copyid                重置某个活动副本的攻击次数
		setTreeLv        参数：level      设置摇钱树的等级
		setExpUserBase   参数：baseId     设置主角经验副本已通关的最大据点

		爬塔系统模块：
		getTowerInfo				获取用户的爬塔信息
		setResetNum    参数：N    设置重置次数
        passTowerLevel    参数：N    通关某个塔层
        setCurLv            参数：N    设置当前塔层

		资源模块：
		getPitsByDomain				获取某个区域的所有资源矿信息
		getPitInfo					获取某个矿坑的信息
		setCaptureofPit				设置资源矿的占有者  并更新timer
		setPitDue 参数:$domainId 资源区id $pitId 矿坑id  让某个矿坑立刻到期
		endPitGuard 参数:$uid协助军uid 让协助军立刻到期

		武将模块：
		addHero			参数：htid						添加武将
		getAllHero					获取所有的武将
		delHero  		参数：hid
		setHeroLevel   	参数：$hid,$level
		addSoul        	参数：$hid,$soul            给武将添加将魂
		setEvLvByHtid   参数：$htid,$ev,$lv        设置武将的转生等级和等级
		setEvLv         参数：$ev,$lv                设置所有武将（除主角）的转生等级和等级
		delAllHero                                    删除所有不在阵上的武将
		delHeroByEvLv   参数：$evLv                    删除所有进阶等级大于等于某个值的武将
		addMultiHero    参数：fromHtid toHtid        添加htid连续的武将    如：addMultiHero 10001 10010是添加htid从10001到10010的10个武将
        addTalentToHtid 参数：htid talentId talentIndex    给所有模板id是htid的武将在特定位置上设置觉醒能力
		clearSquadDestiny 重置阵上所有武将的天命

		阵型模块：
		getFmt			获取阵型
		resetFmt()		初始化阵型
		addHeroToFmt	参数：hid(武将id),index(阵容中武将的位置)	  	添加武将到阵容中
		delHeroFromFmt	参数：hid(武将id)	删除阵型中的武将
		setHeroPosInFmt	参数：hid(武将id),newpos(新的位置) 在阵型中移动武将的位置，如果原来的位置有武将，与此武将更换位置

		铁匠铺模块
		enforce 		参数：$itemId(物品id), $level(等级)  强化装备到指定等级
		enforceAll		参数：$level(等级)  将所有装备强化至指定等级
		setPotence		参数：$itemId(物品id), $value(属性值) 设置装备的所有属性为某个值
		upgrade			参数：$itemId(物品id), $level(等级)  强化宝物到指定等级
		upgradeAll		参数：$level(等级)  将所有宝物强化至指定等级

		战魂模块
		promote 		参数：$itemId(物品id), $level(等级)  强化战魂到指定等级
		promoteAll		参数：$level(等级)  将所有战魂强化至指定等级

		竞技场模块
		resetChallengeNum	初始化竞技次数
		resetArenaReward 参数：$n(天数) 重置前N天的竞技场奖励

		名将模块
		addStar			参数: stid(名将id)	加名将
		setStarLevel	参数：stid(名将id) level(等级) 设置名将等级
		setStarsLevel	参数：level(等级) 设置所有名将等级
		setHCopyPassNum    参数  copyId(副本id) copyLevel(副本难度) num(通关次数，默认为1) 设置名将列传通关次数
		setFeelLevel    参数：stid(名将id) level(等级) 设置名将感悟等级

		商店模块
		resetBuyNum		参数：$type 通过将上次购买时间设置到昨天，清空用户所有物品的购买次数 (3商店5军团商店6竞技场商店7神秘商店8神秘商人9兑换活动10比武商店11寻龙探宝商店;12周末商店;13军团粮仓商店;14限时商店;15神兵商店;16积分商店;17战功商店;23跨服比武商店;27试炼梦魇商店)
		setPoint		参数：$num 设置积分值
		clearCD			清除招将CD时间

		在线和签到：
		resetOnlineInfo
		modiOnlineTime	参数：M n			modiOnlineTime 3 60 将在线奖励设为已经领取了三次 并把自己的累积时间置为 60秒
		signAcc 		参数： N 累计的天数
		signNor			参数: N 连续的天数
		resetSignActivityReward		重置累计登陆活动的已领取奖励

		宠物模块

		添加宠物的普通技能
		petNormalSkill  参数: A B  A为宠物在阵上的位置，B为要添加的普通技能
		添加宠物的特殊技能
		petProductSkill 参数： A B C ABC依次为，宠物在阵上的位置，技能id，技能等级，使用该技能需要大退后生效
		添加宠物天赋技能
		petTalentSkill  参数：同添加宠物特殊技能
		设置宠物生产技能的时间
		productTime		参数： A B A为宠物的在阵上的位置（从0开始） B为宠物B秒之前上阵（比如 生产周期为200秒， 则B>200 即可立即领取）
		查看所有宠物
		getAllPet
		设置宠物学习技能连续失败次数
        setFailNum 参数: petId failNum 宠物id 失败次数
		setPetNormalSkillLevel $petId $level 设置所有普通技能的等级

		奖励中心
		sendReward							发送奖励（现在为竞技场排名奖励）
		sendSysReward 参数 title msg			发送系统奖励给玩家
				
		占星模块
		resetDivine							占星数据重置

		功能节点：
		open   开启所有的功能节点

		成长计划：
		resetGrowup							重置成长计划

		夺宝：
		addTFrag		参数：fragId	num		宝物碎片Id和数量 添加宝物碎片
		clearWhite		参数：N				需要减少的秒数，可以为非常大，此时会将免战结束时间减到当前时间

		神秘商店
		resetRfrTime    参数：seconds         重置神秘商店的系统刷新时间为当前时间之后多少秒

		好友
		addFriend offset limit 添加一批好友
		lovedMe			让所有好友发给我自己
		resetReceiveNum  重置领取次数
		setFriendToMe    让所有我的好友可以送我
		setMeToFriend    让我可以送给所有的好友
		resetFriendPk	  清除好友pk的三个数量，自己pk的次数，被pk的次数，对同一玩家pk的次数

		军团模块
		setGuildLevel	参数：$type(类型:1军团大厅,2关公殿,3商城,4副本,5任务,6粮仓), $level(等级) 设置军团建筑物等级
		setGuildContri	参数：$num(贡献值) 设置军团贡献值
		setContri		参数：$num(贡献值) 设置用户贡献值
		resetGoodsSum   参数：$goodsId(商品id，默认为0代表全部商品id) 重置商品的军团购买次数为0
		resetGoodsNum   参数：$goodsId(商品id，默认为0代表全部商品id) 重置商品的个人购买次数为0
		setGoodsTime	参数：$daysBefore(几天前), $goodsId(商品id，默认为0代表全部商品id) 设置商品的上次购买时间
		refreshGoods	刷新珍品列表
		resetContri     重置用户贡献时间
		resetBGG		重置用户拜关公时间
		resetCD			重置用户操作冷却时间
		setLQcTime      抹除切磋和被切磋记录，可以再次切磋
		shiftMsgs		参数：$day(天数) 设置所有留言为N天前
		resetLottery	重置抽奖次数
		addMerit		参数：$num(功勋值) 增加用户功勋值
		addZg			参数：$num(战功值) 增加用户战功值
		addGrain		参数：$num(粮草值) 增加用户粮草值
		addFB			参数：$num(战书值) 增加军团战书值
		addGuildGrain	参数：$num(粮草值) 增加军团粮草值
		resetShare		重置分粮冷却时间
		resetAttackNum  重置军团抢粮次数
		resetDefendNum  重置军团被抢次数
		resetGoldRfrNum 重置大丰收次数
        resetExpRfrNum  重置小丰收次数
        setHarvestNum   参数：$num(采集次数)  设置所有粮田的采集次数
        addFieldExp     参数：$fieldId(粮田ID),$exp(经验) 添加单个粮田的经验
		resetFields		重置军团所有粮田的等级和经验为0
		shiftFields		参数：$n(天数) 设置用户所有粮田采集时间为N天前
		setGJoin		参数：$num(秒数)，设置用户加入军团的时间为$num秒之前
		getMultiRecord  参数：$brid 获取组队战的推送

		卡包活动：
		setFreeCd     参数:seconds            将冷却时间设置为当前时间-seconds
		addFreeNum    参数：N                    加免费抽将次数N次

		活动签到			参数： yyyymmddhhmmss num 0/1 signActi 20140121010101    3   0/1
		三个参数的意义是 1.设置后一次登录的时间 2.已经登录的次数 3.要不要清除已经领取的奖励（0为不要清除 1为清除）

		挖宝活动
		setRobFreeNum N    设置当天的免费挖宝次数
		setRobGoldNum N    设置当天的金币挖宝次数
		resetRobNum        重置当天的金币、免费挖宝次数

		副本组队：
		passGuildCopy N        通关公会组队副本N
		setGuildCanAtkNum N     设置此玩家的可以组队次数
		setGCopyRfrTime date     设置公会组队次数上一次刷新时间   20140331

		每日任务
		resetDayTask   重置每日任务
		setDPoint   参数：$point 设置积分值
		setDTaskNum	参数:$taskId(任务id), $finishNum(任务完成数量) 设置任务完成进度
		setDTime	设置每日任务的操作时间为昨天

		vip福利：
		setLLTime				设置最后一次登录时间为昨天，再次登录即可领奖
        resetBonus              抹除vip福利领取记录，可以再次领取
		resetVipWeekGift		重置vip每周礼包购买记录

		翻卡加积分：
		kaPoints 翻牌加500积分
		setKaInfoDay $day 推活动数据，距离当前多少天

		神秘商人：
		setMerchantTime  leftSeconds 设置神秘商人剩余时间，如果没有开启神秘商人，会顺便开启
		setMerRefTime   leftSeconds 设置神秘商人下一次系统刷新时间，如果没有开启神秘商人，会顺便开启
		clrMerRefNum    神秘商人刷新次数清零
		closeMerchant   关闭神秘商人（可用于关闭已永久召唤的神秘商人）

		成就系统:
		finishAchieve aid 设置某成就为完成状态

		比武：
		competetime hourNum[24] 将比武时间设置成当前时间-hourNum小时。默认24小时，即修改上次比武时间为昨天这个时间
		addHonor 参数:$num 加荣誉值

		团购活动:
        setGroupOnNum 参数:$goodid商品id $num 团购总数
        clrGroupOn 清空玩家团购数据
		grouponday 将活动向前移动一天
		grouponYesterday [day=1]将自己参与团购的时间改成昨天。如果加参数2，即改成前天

		兑换活动：
		actexchangeYesterday 将自己参与兑换活动的时间改成昨天
		actexchangeday 兑换活动像前移动一天

		月卡：
		buyCard cardId(1小月卡,2大月卡) 购买一个月卡
		setMCardBuyTime cardId(1小月卡,2大月卡) date 设置月卡购买时间 date的格式是20140612
		setMCardDueTime cardId(1小月卡,2大月卡) date 设置月卡N到期时间  date的格式是20140612
		setMCardGetRewardTime cardId(1小月卡,2大月卡) date 设置最后一次领取月卡N奖励的时间  date的格式是20140612
		setMCardGiftStatus status 设置月卡大礼包的领取状态  状态取值是 1:没有大礼包  2:有大礼包，并且没有领取  3:已经领取了大礼包

		充值抽奖
		setCRRewardTime date 设置充值抽奖中每日首冲奖励领取时间
		resetRaffleNum 重置今天抽奖次数

		寻龙探宝
		addTotalPoint 参数:$totalPoint int  增加总积分（积分兑换--使用总积分兑换）
		resetDragon 参数:$mode 模式0普通模式 1试炼模式 重置寻龙探宝（点这里不需要花金币）
		clrResetNum 清零当天已重置次数和免费重置次数
		dragonYesterday 参数:date 将自己参与寻龙的时间改成昨天。
		addFreeReset 参数:num 设置免费重置次数

		充值大放送
        clrTopup 参数:day(当前活动天数，从1开始，默认是当天) int 重置某天的领取状体 可以继续领取
        topupYesterday 将充值大放送活动向前推一天

		删除奖励中心的奖励
		deleteReward N 参数:发奖励的模块， 不填的话默认删除所有奖励

		重置占星奖励刷新次数
		diviRewardRefNum

                        合服活动数据重置
		resetMergeServer 四种使用方法
		resetMergeServer loginReward     清空已经领取的连续登陆奖励
		resetMergeServer loginInfo       清空用户的登陆信息，上次登录时间和已经登录次数都为0
		resetMergeServer loginInfo 2 20141015105050     设置 上次登录时间为20141015105050和已经登录次数为2
		resetMergeServer rechargeReward  清空已经领取的充值返还奖励
		resetMergeServer compensation    重置领取补偿时间为0

		计步活动
		clrStepCounter 清除当天领奖状态
		resetStartTime $day 格式：20141017 重置计步活动的开始日期

		周末商店
		weekendShopYesterday 将参加活动时间置为昨天 将重置今天的商店，购买次数等
		weekendShopBeginDay $day 格式：20141017 设置周末商店开始时间， 用于改变当前是活动第几周

		天命
		openDestiny destinyId    激活天命到指定的天命id

		军团任务
		resetGuildTask

		积分轮盘活动
		resetRoulette  重置此号积分轮盘所有数据
		setRouletteYesterday  设置积分轮盘上次刷新时间为昨天
		setRouletteInt        增加积分  $num
		setRouletteDay        设置当前是积分轮盘第几天  $day
		resetRouletteReward   重置领奖   $index  1是积分宝箱 2是排名奖励

		时装屋
        getDress $itemTmpId 时装id(物品模板id) 添加时装屋新获得时装
        clrDress 清空时装屋时装

		限时商店活动
		resetLimitShop           重置限时商店购买次数
		setLimitShopDay  $day    设置当前为限时商店活动第几天

		军团抢粮战
		guildRob		 		使用方式如下
    		加机器人：			guildRob enter 发起抢粮战军团名称      attack|defend|both   人数上限
    		让机器人离开战场:		guildRob leave 发起抢粮战军团名称
			获得抢粮战生效时间:	guildRob getEffect
    		设置抢粮战生效时间:	guildRob setEffect
    		恢复抢粮战生效时间:	guildRob resumeEffect
		resetLastDefendTime 		  重置所在军团的最后一次防守时间为0
		resetLastAttackTime 		  重置所在军团的最后一次抢粮时间为0

		资源追回
		resetRetrieve           清空玩家资源追回记录
		setReSupplyNum $num     设置可找回烧鸡的次数

	             神兵系统
	    setReinFroceLv $id $level  设置强化等级，$godWeaponId为神兵模板Id $level为等级
	    setEvolveNum $id $num  设置进化次数，$godWeaponId为神兵模板Id $num为进化次数
        setWash $itemTplId物品模板id $index位置 $attrId属性id 给某类神兵itemTplId某个位置index洗练出某个属性attrId

		过关斩将
		addCoin $num   增加神兵令
		subCoin $num   减少神兵令
		resetPassShop  重置神兵商店所有信息
		resetPassShopSysRfrTime yyyymmddhhmmss 重置系统刷新时间为离这个时间最尽的系统刷新时间
	    resetPassShopUsrRfrTime yyyymmddhhmmss 重置玩家自己刷新时间为设置的时间
		resetPassShopFreeRefreshNum 重置神兵商店免費刷新時間
		resetPassShopLastSysRefreshTime 重置神兵商店系统刷新时间
		resetPass 将参与时间置为昨天
		recoverHpForPass 复活过关斩将中的所有英雄

		聚宝盆
		setBowlDay $day  设置当前为活动第几天
		resetBowlReward  重置领奖信息和宝箱（已充值的信息没变，需要清空可找后端调）
		setBowltime      设置聚宝时间是几天前      $type 哪个档（1、2、3）      $day 几天前（0的话是今天）

		节日活动
		resetFestival    重置节日活动购买次数
		setFestivalDay   设置当前是活动的第几天    $day

		神兵录
		clearGodBook 清空神兵录
		clearTallyBook 清空兵符录

		军团跨服战
		resetWorshipTime	重置膜拜时间
		resetUpdateFmtTime	重置更新战斗力时间
		resetBuyMaxWinTime	重置购买连胜次数的时间
		resetBuyMaxWinNum	重置已经购买连胜的次数

		积分商城
		addSSPoint          参数：$date(YYYYMMDD)    $point   增加积分商城可用积分(其实是给某天加消费金币累积)
		setSSDay            参数：$day     设置当前为积分商城活动的第几天
		resetSS                          重置积分商城所有信息（购买次数、花费积分和玩家消费金币累积信息）

		getHeroBattleInfo   参数：htid  获取阵上某个武将的战斗信息数据

        主角星魂
        clrAthena $index 清空主角某一层的星魂数据 index 从1开始
        setTreeNum 设置最大开启树 参数：num 最大开启树
        athenaTalent 开启某层的天赋 参数: index 层数
        clrMasterTalent 清掉玩家装备的主角天赋
        clrAthenaTalent 清掉星魂开启的可装备的天赋

	 	军团副本之军团相关
		guildcopy_resetGuild						重置玩家所在军团的军团副本为初始化时候的状态，！！！！军团数据烂了的时候用这个！！！！
		guildcopy_setGuildMaxPassCopy [num]			设置军团已经通关的最大副本Id
		guildcopy_setGuildCurrCopy [num]			设置军团今天的要攻打的副本Id,里面会重置军团副本信息，相当于调用了一个guildcopy_resetGuildCopy
		guildcopy_resetGuildAllAttackNum			重置军团全团突击次数为0，当一个军团全团突击次数达到上限时候使用
		guildcopy_resetGuildBox						重置军团的宝箱为原始状态，也就是谁也没领过。
		guildcopy_resetGuildCopy					重置军团当前副本为初始状态，也就是一个人也没攻打过的状态
		guildcopy_passCurrCopy						设置玩家所在军团的当天军团副本通关
		guildcopy_resetBoss							重置军团BOSS
		军团副本之玩家相关
		guildcopy_resetUser							重置玩家的军团副本攻打状态，！！！！玩家数据烂了的时候用这个！！！！
		guildcopy_addUserAtkNum [num]				增加玩家今天的攻击次数
		guildcopy_setUserBuyNum [num]				设置玩家已经购买攻击的次数
		guildcopy_resetUserAllAttackTime			重置玩家今天全团突击时间，如果玩家今天已经突击过，还想再突击，调用此。
		guildcopy_resetUserRecvRewardTime			重置玩家领取奖励的时间，包括阳光普照奖，宝箱，和排名奖。今天已经领取过，但是还想领，调用此。


		分享
		clearAllShare            清除所有分享
		clearShare                清除今天的分享

		水月之境（宝物副本）
		moon_setAtkNum num							设置玩家的攻击次数
		moon_setBuyNum num							设置玩家的购买次数
		moon_setBuyBoxNum num						设置玩家购买天工阁里宝箱的次数
		moon_setMaxPassCopy num						设置玩家的最大通关副本Id
		moon_setGridStatus gridId status			设置某一个格子的状态，三种状态（1代表锁定，2代表解锁未点亮，3代表点亮）
		moon_resetGridInfo							重置当前副本的所有格子为初始化状态
		moon_reset									重置所有，一键回到解放前
		moon_resetNightMare                         重置梦魇攻击次数和购买次数
		moon_addTgNum num							增加玩家的天工令
		moon_addTallyPoint  num                     增加玩家兵符积分
		moon_setLastAttackTime	20141015105050		设置上次攻打的时间
		moon_resetShop  							重置天工阁所有信息
		moon_resetBingfushop                        重置兵符商店玩家刷新次数和系统刷新次数
		moon_resetShopSysRfrTime yyyymmddhhmmss 	重置系统刷新时间为离这个时间最尽的系统刷新时间
	    moon_resetShopUsrRfrTime yyyymmddhhmmss 	重置玩家自己刷新时间为设置的时间
	    resetMoonShopFreeRefreshNum 				重置符印商店免費刷新時間
		resetMoonShopLastSysRefreshTime 			重置符印商店系统刷新时间


		跨服炼狱挑战
		worldpass_setAtkNum num 					设置闯关次数
		worldpass_setBuyNum num 					设置购买次数
		worldpass_setRfrNum num 					设置刷新次数
		worldpass_setCurrPoint num 					设置当前积分
		worldpass_setHellPoint num 					设置炼狱令
		worldpass_resetBuyNum						设置炼狱商店上次购买时间为一周前
		worldpass_resetPassedStage					重新开始这次闯关

		丹药
		clrPill                    清空已吃丹药

		聚义厅
		clrUnion		清空镶嵌记录

		跨服竞技场（巅峰对决）
		worldarena_clearProtectTime 				清除保护时间
		worldarena_clearUpdateFmtTime 				清除更新战斗信息冷却时间
		worldarena_setGoldResetNum 					设置金币重置次数
		worldarena_setSilverResetNum 				设置银币重置次数
		worldarena_setAtkedNum 						设置已经挑战次数
		worldarena_setBuyAtkNum 					设置已经购买次数
		worldarena_setKillNum 						设置击杀数
		worldarena_setCurContiNum 					设置当前连杀数
		worldarena_setMaxContiNum 					设置最大连杀数

		跨服团购
		worldgroupon_clear_user                     清除玩家个人数据
		world_groupon_yesterday                     自己参加活动时间向前推一天
		world_groupon_whole_yesterday				将活动开始时间往前推一天
		world_groupon_team							活动分组--开活动的步骤:先上传配置,然后执行这个分组脚本
		world_groupon_reward						发奖--活动结束,想发奖的时候跑
		跨服团购测试流程
		1  策划上传活动
		2 调用worldgroupon_clear_user清空玩家的所有购买数据
		3 调用world_groupon_team分组，分组完毕后默认1分钟后进入购买阶段
		4 进入了购买阶段后，正常购买商品
		5 调用web后台设置虚拟购买人数（如有必要的话）
		6 调用world_groupon_reward进行发奖。


		悬赏榜商店
		addFameNum num									增加名望
		setMissionBuyYesterday						购买时间往前推一天

		云游商人
		tsScore num 	修改进度值
		tsAddScore num  增加进度值
		tsSum	num 	修改总购买人次
		tsLimit	seconds 充值倒计时秒数
		tsReset			重置用户数据
		tsSetLastBuyTime 20141015105050  设置上次购买时间

		跨服比武
		worldcompete_setAtkNum num 					设置比武次数
		worldcompete_setSucNum num 					设置胜利次数
		worldcompete_setBuyAtkNum num 				设置购买比武次数
		worldcompete_setRfrNum num 					设置刷新次数
		worldcompete_setWorshipNum num 				设置膜拜次数
		worldcompete_setMaxHonor num 				设置跨服荣誉，排名用
		worldcompete_setCrossHonor num 				设置跨服荣誉，购买用
		worldcompete_resetPrize						清空已领胜场奖励
		worldcompete_resetBuyNum					设置跨服比武商店上次购买时间为一周前

		新类型福利活动/限时成就（类型：1、普通副本胜利；2、竞技场胜利；3、夺宝；4、开箱子；5、比武）
		setDesactDay $day        设置当前是活动第几天
		setDesactNum $id $num    设置某个任务完成的次数
		clearDesact  $id         充值某任务的奖励,写0的话清所有数据。

		欢乐签到
		resetHappySign			     重置“欢乐签到”活动已经领取过的奖励id记录和登陆天数
		setHappySignDays  $num	     设置“欢乐签到”活动期间内已经登录过(也就是签到过)的天数
		setHappySignTime  $date(日期) $time(时间:hhmmss)	设置“欢乐签到”活动的最近一次的签到时间   例如：setHappySignTime 20151019 123000

		充值送礼
		resetRechargeGift		     重置“充值送礼”活动已经领取过的奖励id记录
		setRGUpdateTime 		    设置“充值送礼”活动的最近一次的数据库更新时间   例如：setRGUpdateTime 20151019 123000

		战魂重生
		resetFsReborn 重置战魂重生次数

		黑市兑换活动
		setBlackshopDay  设置当前是黑市兑换活动的第几天，并重置所有兑换次数    $day
		ClearBlackshop   重置黑市兑换某条兑换次数       $id

		国战
		openCw()			开启国战
		addCopoint($copoint)	增加国战积分 $copoint
		resetCWShopWeekGoodsBuyNum()	设置国战商店商品的上次购买时间为一周前
		resetCWShopDayGoodsBuyNum()		设置国战商店商品的上次购买时间为一天前
		addCwAuditionPoints($num)			增加国战初赛积分

		首充重置
		delLastOrder     $num   删除最后几次的充值（数量默认为1，只删充值订单金币并没有扣）
		clearChargeInfo  $gold  清除首充的充值记录（gold为金币档位， 填0的话清除所有）

		单充回馈
		clrOneRechargeReward 只清除“单充回馈”所有的已领奖信息
		clrOneRechargeAll 清除“单充回馈”所有的已领奖信息和充值信息

		新服活动
		设置新服活动（“开服7天乐”）某个任务的状态
		setNewServerTaskStatus	$taskId:任务id $status：0表示未完成  1表示完成未领奖励  2表示已领奖
		resetNewServerTask		会重置完新服活动所有已完成与已领奖任务为未完成
		resetNewServerGoods		会重置完新服活动所有购买的商品记录

		活动狂欢
		resetFestivalAct		重置所有活动记录
		notifyByTypeId $typeId $num 直接用命令完成任务（不可以和实际操作一起用）
									$typeId 任务的typeId $num 完成的次数

		活动
		setActivityDay  $actName $day  设置当前是某个活动的第几天,名字填英文名
                		         --- 活动名称对应：
		                         ---   spend             ： 消费累计
		                         ---   topupFund         ： 充值回馈
		                         ---   arenaDoubleReward ： 竞技场双倍奖励
		                         ---   heroShop          ： 限时神将
		                         ---   robTomb           ： 皇陵探宝
		                         ---   actExchange       ： 物品兑换活动
		                         ---   groupon           ： 团购活动
		                         ---   chargeRaffle      ： 充值抽奖活动
		                         ---   topupReward       ： 充值大放送
		                         ---   stepCounter       ： 计步活动
		                         ---   roulette          ： 积分轮盘
		                         ---   limitShop         ： 限时商店
		                         ---   treasureBowl      ： 聚宝盆活动
		                         ---   festival          ： 节日活动（新福利活动）
		                         ---   scoreShop         ： 积分商城
		                         ---   travelShop        ： 云游商人
		                         ---   blackshop         ： 黑市兑换活动
								 ---   happySign         ： 欢乐签到
								 ---   rechargeGift      ： 充值送礼
								 ---   onerecharge		   ： 单充回馈
								 ---   signActivity		 : 累积登陆
								 ---   happySign		 : 欢乐签到

        助战位等级重置
        resetAttrExtraLv 重置助战位等级

	商店mall子类
	resetOneMall   $mall_type 重置某一类型商店所有信息
	mall_type为整数型,eg:
						19 个人跨服战商店
   						20 跨服闯关大赛商店
    					21 悬赏榜商店
    					22 黑市兑换商店
						23   跨服比武商店
    					24  国战商店
    					25  兵符商店（水月之境新增）

		    木牛流马
		    beginshipping $stageid $pageid $roadid
		                          --stageid 目前来看取值是1-4
		                          --pageid 取值1-100
		                          --roadid 取值1-3
		                          --如果pageid和roadid都不为0的话，则直接按照输入的pageid和roadid来放镖车，否则自动选择镖车位置
		    clearAllUserNum 清除当前玩家的所有隔天刷新的数据
		    clearChargedartInfo 强制清除玩家的镖车信息，不会发奖
		    setDarkCheck $num 设置暗格次数，目前的配置是0-10
		    addSomeChargeDart $uid $uid2 $uid3 ...... 输入已存在的uid，强制进行镖车，不输入数据的话，使用这几个uid：20897,20197,20513,62175,108493,63896
		    stuffOnePageWhitChargeDart $stageId $pageId $roadnum 强制在某一页按照间隔的时间插满镖车，注意一共提供21个61级玩家，同一时间只支持一页，每次会先清除那些玩家的镖车信息

			矿精灵
			clearElvesOccupyInfo  清楚玩家占领宝藏的信息

			战车
			clearChariotInfo 清除玩家战车的信息,没有参数
		    
		            试炼梦魇
		    addTowerNum $num            加试炼币
		    setHellResetNum $num        设置可重置次数
		    passHellTowerLevel $level   通关某个塔层
		    setHellCurLv $level         设置当前塔层
		    setHellFailNum $num         设置可失败次数
			
			七星台
			addSevensPoint $num 加积分
			resetSevensNum 重置招募次数
			addSevensLucky $num 加幸运值
			resetSevensShop 重置商店
				
			老玩家回归
			welcomebackOpen        $offlineTime  开启活动并指定活动所需的玩家上次离线时间，参数格式：20160829003000（表示2016年08月29日00点30分00秒）
			welcomebackSetEndTime  $endTime      设置活动结束时间，参数格式：20160829003000（表示2016年08月29日00点30分00秒）
			welcomebackInfo		  			            获取玩家的活动信息
			welcomebackSetActivity $status			 
						支持的指令-- open	 	  	   开启活动。如果是一个刚建好的号，需要退出一次游戏，再调用该命令。
								-- close		   关闭活动。
								-- resetGift	   将礼包都设置为未领取
								-- resetTask	   将任务都设置为未完成未领取
								-- resetRecharge 将单充都设置为未完成未领取
								-- resetShop	   将折扣物品都设置为未购买
								-- resetAll		   将上述四种同时重置
			welcomebackDoTask      $taskTypeId $num		直接完成某一类型任务数次。例如完成竞技场挑战3次：welcomebackDoTask 106 3。
						任务id有：102		成功完成普通副本
								103		成功完成精英副本
								104		进行占星
								105		进行夺宝
								106		进行竞技场挑战
								107		占领或协助资源矿	
			welcomebackGainReward  $id    $select     领取奖励。$id任务或礼包id，$select选取领取的东西，0全领，1领第一个...必须符合配表
				';
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */