<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: checkFormation.php 136657 2014-10-17 10:56:58Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/script/tools/checkFormation.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2014-10-17 10:56:58 +0000 (Fri, 17 Oct 2014) $
 * @version $Revision: 136657 $
 * @brief 
 *  
 **/
class CheckFormation extends BaseScript
{
	/* (non-PHPdoc)
	 * @see BaseScript::executeScript()
	*/
	protected function executeScript ($arrOption)
	{
		if (empty($arrOption[0]) || $arrOption[0] == 'help' || (count($arrOption) < 2))
		{
			$this->usage();
			return;
		}
		
		$option = $arrOption[0];
		if ($option == 'check') 
		{
			$fix = false;
		}
		elseif ($option == 'fix')
		{
			$fix = true;
		}
		else 
		{
			echo "invalid operation!\n";
			$this->usage();
			return;
		}
		
		$uid = intval($arrOption[1]);
		if($fix)
		{
		    Util::kickOffUser($uid);
		}
		RPCContext::getInstance()->setSession(UserDef::SESSION_KEY_UID, $uid);
		$user = EnUser::getUserObj($uid);
		if(empty($user))
        {
            echo "empty user!\n";
            $this->usage();
            return;
        }
        
        $masterHid = $user->getMasterHid();
        $btConf = btstore_get()->FORMATION;
        $arrField = array(
        		'uid' => $uid,
        		'va_formation' => array(
        				'formation' => array(
        						$masterHid => array(
        								'index' => 0,
        								'pos' => $btConf['initPos']
        						)
        				),
        				'extra' => array()
        		),
        );
        
		$oldRet = FormationDao::getByUid($uid);
		$ret = $oldRet;
		if (empty($ret)) 
		{
			//用户压根就没有阵型数据！
			Logger::warning('user has no va_formation!');
			if ($fix)
			{
				FormationDao::insert($arrField);
				echo "fix user no va_formation\n";
			}
			return;
		}
		elseif (empty($ret['va_formation']))
		{
			//用户的阵型居然是空的！
			Logger::warning('user has empty va_formation!');
			if ($fix) 
			{
				FormationDao::update($uid, $arrField);
				echo "fix user empty va_formation\n";
			}
			return;
		}
		elseif (!isset($ret['va_formation']['formation']) 
				&& !isset($ret['va_formation']['extra']))
		{
			//用户的阵型依旧是老数据！修完数据结构继续修数据
			Logger::warning('user has old va_formation!');
			if ($fix)
			{
				$vaFormation = array(
						'formation' => $ret['va_formation'],
						'extra' => array()
				);
				$ret['va_formation'] = $vaFormation;
				FormationDao::update($uid, array('va_formation' => $ret['va_formation']));
				echo "fix user old va_formation\n";
			}
			return ;
		}
		elseif (!isset($ret['va_formation']['formation']) 
				&& isset($ret['va_formation']['extra']))
		{
			//用户的阵容为空，小伙伴非空
			Logger::warning('user has empty formation!');
			if ($fix)
			{
				$vaFormation = array(
						'formation' => $arrField['va_formation']['formation'],
						'extra' => $ret['va_formation']['extra']
				);
				$ret['va_formation'] = $vaFormation;
				FormationDao::update($uid, array('va_formation' => $ret['va_formation']));
				echo "fix user empty formation\n";
			}
			return ;
		}
		elseif (isset($ret['va_formation']['formation'])
				&& !isset($ret['va_formation']['extra']))
		{
			//用户的阵容非空，小伙伴为空
			Logger::warning('user has empty extra!');
			if ($fix)
			{
				$vaFormation = array(
						'formation' => $ret['va_formation']['formation'],
						'extra' => array()
				);
				$ret['va_formation'] = $vaFormation;
				FormationDao::update($uid, array('va_formation' => $ret['va_formation']));
				echo "fix user empty extra\n";
			}
			return ;
		}
		else
		{
			Logger::trace('user:%d has normal va_formation:%s', $uid, $ret['va_formation']);
		}
		
		$invalid1 = array();
		$arrHid = array();
		$arrBaseHtid = array();
		$arrIndex = array();
		$arrPos = array();
		$level = $user->getLevel();
		$myFormation = EnFormation::getFormationObj($uid);
		$squadSize = $myFormation->getSquadSize($level);
		$arrOpenPos = $myFormation->getArrOpenPos($level);
		$allHero = $user->getHeroManager()->getAllHero();
		foreach ($ret['va_formation']['formation'] as $hid => $indexPos)
		{
			//武将不存在
			if (!key_exists($hid, $allHero))
			{
				Logger::warning('hid:%d is not exist!', $hid);
				$invalid1[] = $hid;
				continue;
			}
			$arrHid[] = $hid;
			$htid = $allHero[$hid]['htid'];
			$baseHtid = HeroUtil::getBaseHtid($htid);
			//武将模板相同
			if (in_array($baseHtid, $arrBaseHtid)) 
			{
				Logger::warning('baseHtid:%d is duplicated! arrBaseHtid:%s', $baseHtid, $arrBaseHtid);
				$invalid1[] = $hid;
				continue;
			}
			$arrBaseHtid[] = $baseHtid;
			$index = $indexPos['index'];
			//index取值范围0-5
			if (in_array($index, $arrIndex) || $index >= $squadSize || $index < 0) 
			{
				Logger::warning('index:%d is not valid! squadSize:%d arrIndex:%s', $index, $squadSize, $arrIndex);
				$invalid1[] = $hid;
				continue;
			}
			$arrIndex[] = $index;
			$pos = $indexPos['pos'];
			//pos取值范围0-5
			if (in_array($pos, $arrPos) || !in_array($pos, $arrOpenPos)) 
			{
				Logger::warning('pos:%d is not valid! arrOpenPos:%s arrPos:%s', $index, $arrOpenPos, $arrPos);
				$invalid1[] = $hid;
				continue;
			}
			$arrPos[] = $pos;
		}
		if (!empty($invalid1)) 
		{
			Logger::warning('error data in formation:%s', $invalid1);
			echo "error data in formation:\n";
			print_r($invalid1);
		}
		
		$invalid2 = array();
		$extraSize = $myFormation->getExtraSize($level);
		foreach ($ret['va_formation']['extra'] as $index => $hid)
		{
			//武将不存在
			if (!key_exists($hid, $allHero))
			{
				$invalid2[$index] = $hid;
				continue;
			}
			//武将hid重复
			if (in_array($hid, $arrHid)) 
			{
				$invalid2[$index] = $hid;
				continue;
			}
			$arrHid[] = $hid;
			$htid = $allHero[$hid]['htid'];
			$baseHtid = HeroUtil::getBaseHtid($htid);
			//武将模板重复
			if (in_array($baseHtid, $arrBaseHtid))
			{
				$invalid2[$index] = $hid;
				continue;
			}
			$arrBaseHtid[] = $baseHtid;
			//index取值范围0-9
			if ($index >= $extraSize || $index < 0)
			{
				$invalid2[$index] = $hid;
				continue;
			}
		}
		if (!empty($invalid2))
		{
			Logger::warning('error data in extra:%s', $invalid2);
			echo "error data in extra:\n";
			print_r($invalid2);
		}

		if ($fix)
		{
			foreach ($invalid1 as $hid)
			{
				unset($ret['va_formation']['formation'][$hid]);
				echo "delete hero " . $hid . "\n";
			}
			foreach ($invalid2 as $index => $hid)
			{
				unset($ret['va_formation']['extra'][$index]);
				echo "delete hero " . $hid . "\n";
			}
			if (empty($ret['va_formation']['formation']))
			{
				$ret['va_formation']['formation'] = $arrField['va_formation']['formation'];
			}
			if ($ret != $oldRet)
			{
				FormationDao::update($uid, array('va_formation' => $ret['va_formation']));
				$user->modifyBattleData();
			}
			echo "fix user formation and extra\n";
		}
		echo "ok\n";
	}
	
	private function usage()
	{
		echo "usage: btscript game001 checkFormation.php check|fix uid\n";
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */