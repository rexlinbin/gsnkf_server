<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: fixPet.php 243108 2016-05-17 06:07:36Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/test/fixPet.php $
 * @author $Author: BaoguoMeng $(mengbaoguo@babeltime.com)
 * @date $Date: 2016-05-17 06:07:36 +0000 (Tue, 17 May 2016) $
 * @version $Revision: 243108 $
 * @brief 
 *  
 **/
 
class fixPet extends BaseScript
{
	
	public static $step = 25;
	
	/* (non-PHPdoc)
	 * @see BaseScript::executeScript()
	*/
	protected function executeScript($arrOption)
	{
		if (count($arrOption) < 4) 
		{
			printf("error param\n");
			return;
		}
		
		$do = FALSE;
		if ($arrOption[0] == 'do') 
		{
			$do = TRUE;
		}
		$uid = intval($arrOption[1]);
		$petTplId = intval($arrOption[2]);
		$vadata = $arrOption[3];
		
		RPCContext::getInstance()->setSession('global.uid', $uid);
		$userObj = EnUser::getUserObj($uid);
		$bag = BagManager::getInstance()->getBag($uid);
		$petMgr = PetManager::getInstance($uid);
		
		if ($do) 
		{
			Util::kickOffUser($uid);
		}
		
		$errStoneNum = $this->getErrStoneNum($petTplId, $vadata);
		$errStoneNum = 500000;
		if ($errStoneNum <= 0) 
		{
			$msg = sprintf('deal uid:%d, invalid err stone num:%d, return', $uid, $errStoneNum);
			$this->mylog($msg);
			return ;
		}
		$msg = sprintf('deal uid:%d, err stone num:%d', $uid, $errStoneNum);
		$this->mylog($msg);
		
		///////////////////////////////////
		$stoneTemplate = 60108;
		///////////////////////////////////
		
		$haveStoneNum = $bag->getItemNumByTemplateID($stoneTemplate);
		if ($haveStoneNum >= $errStoneNum) 
		{
			$msg = sprintf('deal uid:%d, ENOUGH, have stone num:%d, sub stone num:%d, result:ok', $uid, $haveStoneNum, $errStoneNum);
			$this->mylog($msg);
			if ($do) 
			{
				$bag->deleteItembyTemplateID($stoneTemplate, $errStoneNum);
			}
		}
		else
		{
			$lackStoneNum = $errStoneNum - $haveStoneNum;
			$msg = sprintf('deal uid:%d, LACK, have stone num:%d, lack num:%d', $uid, $haveStoneNum, $lackStoneNum);
			$this->mylog($msg);
			if ($do) 
			{
				$bag->deleteItembyTemplateID($stoneTemplate, $haveStoneNum);
			}
			
			$arrPetInfo = $petMgr->getAllPet();
			$arrPetInfo = $this->sortPetByTotalValue($arrPetInfo);
			
			foreach ($arrPetInfo as $petId => $petInfo)
			{
				if (empty($petInfo['va_pet']['confirmed']))
				{
					continue;
				}
				
				$petTplId = $petInfo['pet_tmpl'];
				
				$backVaInfo = $petInfo['va_pet'];
				$vaInfo = $petInfo['va_pet'];
				
				$attrValue = $vaInfo['confirmed'];
				$attrKey = array(51,54,55,100);
				foreach ($attrKey as $aKey)
				{
					if (empty($attrValue[$aKey])) 
					{
						continue;
					}
					
					while ($attrValue[$aKey] >= self::$step) 
					{
						$totalValue = array_sum($attrValue);
						$equalStoneNum = $this->getNeedStoneNum($petTplId, $totalValue);
						$attrValue[$aKey] -= self::$step;
						$lackStoneNum -= $equalStoneNum;
						
						if ($lackStoneNum <= 0) 
						{
							break;
						}
					}
					
					if ($lackStoneNum <= 0)
					{
						break;
					}
				}
				
				$vaInfo['confirmed'] = $attrValue;
				
				if ($vaInfo != $backVaInfo)
				{
					$attrValueBefore = $backVaInfo['confirmed'];
					$attrValueAfter = $vaInfo['confirmed'];
					
					$totalBefore = array_sum($attrValueBefore);
					$totalAfter = array_sum($attrValueAfter);
					
					$msg = sprintf('deal uid:%d, petTplId:%d, petid:%d, totalBefore:%d, totalAfter:%d', $uid, $petTplId, $petId, $totalBefore, $totalAfter);
					$this->mylog($msg);
					$this->checkDiff($backVaInfo, $vaInfo);
					var_dump($backVaInfo['confirmed']);
					var_dump($vaInfo['confirmed']);
					
					if ($do)
					{
						$this->updatePetVaInfo($uid, $petId, $vaInfo);
					}
				}
				
				if ($lackStoneNum <= 0) 
				{
					break;
				}
			}
			
			if ($lackStoneNum <= 0) 
			{
				$msg = sprintf('deal uid:%d, LACK, but sub ok, result:ok', $uid);
				$this->mylog($msg);
			}
			else
			{
				$msg = sprintf('deal uid:%d, LACK, sub not ok, still lack:%d, result:lack', $uid, $lackStoneNum);
				$this->mylog($msg);
			}
		}
		
		if ($do) 
		{
			$userObj->update();
			$bag->update();
		}
	}
	
	public function checkDiff($before, $after)
	{
		foreach ($before as $key => $value)
		{
			if ($key != 'confirmed' && (!isset($after[$key]) || $value != $after[$key])) 
			{
				printf("inter err, modify other field except confirmed\n");
				exit();
			}
		}
		foreach ($after as $key => $value)
		{
			if ($key != 'confirmed' && (!isset($before[$key]) || $value != $before[$key]))
			{
				printf("inter err, modify other field except confirmed\n");
				exit();
			}
		}
	}
	
	public function mylog($format, $warning = FALSE)
	{
		printf("%s\n", $format);
		
		if ($warning) 
		{
			Logger::warning($format);
		}
		else 
		{
			Logger::info($format);
		}
		
	}
	
	public function updatePetVaInfo($uid, $petId, $vaInfo)
	{
		$arrCond = array
		(
				array('petid', '=', $petId),
				array('uid', '=', $uid),
		);
		$arrField = array('va_pet' => $vaInfo);
		$data = new CData();
		$data->update('t_pet')->set($arrField);
		foreach ($arrCond as $aCond)
		{
			$data->where($aCond);
		}
		
		$ret = $data->query();
		if ($ret['affected_rows'] == 0) 
		{
			throw new InterException('not affect');
		}
	}
	
	public function getNeedStoneNum($petTplId, $value)
	{
		if (empty(btstore_get()->PET[$petTplId]['itemNum'])) 
		{
			printf("invalid conf, pet tpl id[%d]", $petTplId);
			exit();
		}
		
		$conf = btstore_get()->PET[$petTplId]['itemNum']->toArray();
		$ret = 0;
		foreach ($conf as $key => $num)
		{
			if ($value >= $key) 
			{
				$ret = $num;
			}
		}
		return $ret;
	}
	
	public function getErrStoneNum($petTplId, $data)
	{
		$arrVa = $this->decodeAmf($data);
		if (empty($arrVa['confirmed'])) 
		{
			return 0;
		}
		
		$attrValue = $arrVa['confirmed'];
		$totalValue = array_sum($attrValue);
		
		$errStoneNum = 0;
		$currValue = 0;
		while ($currValue < $totalValue)
		{
			$errStoneNum += $this->getNeedStoneNum($petTplId, $currValue);
			$currValue += self::$step;
		}
		
		return $errStoneNum;
	}
	
	public function sortPetByTotalValue($arrPetInfo)
	{
		$ret = array();
		
		$arrPet2Value = array();
		foreach ($arrPetInfo as $petId => $petInfo)
		{
			if (empty($petInfo['va_pet']['confirmed'])) 
			{
				continue;
			}
			
			$attrValue = $petInfo['va_pet']['confirmed'];
			$totalValue = array_sum($attrValue);
			$arrPet2Value[$petId] = $totalValue;
		}
		
		asort($arrPet2Value);
		
		$ret = array();
		foreach ($arrPet2Value as $petId => $totalValue)
		{
			$ret[$petId] = $arrPetInfo[$petId];
		}
		
		return $ret;
	}
	
	public function getEqualStoneNumOneSub($attrValue, $key)
	{
		$totalValue = array_sum($attrValue);
		$stone = $this->getNeedStoneNum($totalValue);
		
		return $stone;
	}
	
	function decodeAmf($data)
	{
		$data = str_replace(array("\n", "\t", " "), "", $data);
		$data = pack('H' . strlen($data), $data);
		$data = chr(0x11) . $data;
		$arrData = amf_decode($data, 7);
		return $arrData;
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */