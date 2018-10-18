<?php

/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: DoBattleNCopy.php 82982 2013-12-25 07:56:24Z TiantianZhang $
 * 
 **************************************************************************/

/**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/script/tools/DoBattleNCopy.php $
 * @author $Author: TiantianZhang $(zhangtiantian@babeltime.com)
 * @date $Date: 2013-12-25 07:56:24 +0000 (Wed, 25 Dec 2013) $
 * @version $Revision: 82982 $
 * @brief 
 * 
 **/
class DoBattleNCopy extends BaseScript
{

    /* (non-PHPdoc)
	 * @see BaseScript::executeScript()
	*/
    protected function executeScript ($arrOption)
    {
        // TODO Auto-generated method stub
        if ($arrOption[0] == 'help' || (count($arrOption) < 2))
        {
            echo "usage::btscript groupname scriptfile uid copyid [baseid baseLevel]\n";
			return;
        }
        var_dump($arrOption);
        $uid = intval($arrOption[0]);
        $copyId = intval($arrOption[1]);
        $baseId = 0;
        $baseLv = 1;
        if (isset($arrOption[2]))
        {
            $baseId = intval($arrOption[2]);
        }
        if (isset($arrOption[3]))
        {
            $baseLv = intval($arrOption[3]);
        }
        if (($uid < 0) || ($copyId < 0))
        {
            echo "invalid parameters ,please reexecute the script again\n";
            return;
        }
        RPCContext::getInstance()->setSession('global.uid', $uid);
		$ncopy = new NCopy();
        $tmp = $ncopy->getCopyList();
        $copyList = $tmp['copy_list'];
		if($this->isCopyPassed($copyList,$copyId))
		{
			echo "copy:".$copyId." is passed.\n";
			return;
		}
        $this->atkMstInCopy($copyId, $baseId, $baseLv);    
    }

    public function atkMstInCopy ($copyId, $baseId, $baseLv)
    {
        $lastCopy = $copyId;
        $ncopy = new NCopy();
        $copyListTmp = $ncopy->getCopyList();
        $copyList = $copyListTmp['copy_list'];
        $atkCopies = array();
        $preCopy = $this->getPreCopy($copyId);
        while (! empty($preCopy))
        {
            $copyId = $preCopy;
            $preCopy = $this->getPreCopy($copyId);
            $atkCopies[] = $copyId;
        }
        $atkCopies = array_reverse($atkCopies);
        foreach ($atkCopies as $atkCopy)
        {
            $ret	=	$this->attackCopy($atkCopy, $baseLv);
			if($ret == FALSE)
			{
				return;
			}
        }
        $this->attackCopy($lastCopy, $baseLv, $baseId);
    }

    private function isCopyPassed ($copyList, $copyId)
    {
        $uid = RPCContext::getInstance()->getUid();
        if (! isset($copyList[$copyId]))
        {
            return FALSE;
        }
        $copyInfo = $copyList[$copyId];
        $copyObj = new NCopyObj($uid, $copyId, $copyInfo);
        if ($copyObj->isCopyPassed())
        {
            return TRUE;
        }
        return FALSE;
    }

    private function getPreCopy ($copyId)
    {
        if ($copyId == CopyConf::$FIRST_NORMAL_COPY_ID)
        {
            return FALSE;
        }
        $preBaseId = btstore_get()->COPY[$copyId]['base_open'];
        $preCopyId = btstore_get()->BASE[$preBaseId]['copyid'];
        return $preCopyId;
    }

    
    private function isAtkBaseLv($copyInfo,$baseId,$baseLv)
    {
        if(!isset($copyInfo['va_copy_info']['progress'][$baseId]))
        {
            $copyInfo['va_copy_info']['progress'][$baseId]	=	1;
        }
        //已经通关
        if($copyInfo['va_copy_info']['progress'][$baseId] >= ($baseLv +2 ))
        {
			if($baseLv == 0)
			{
				return FALSE;
			}
            return FALSE;
        }
        //没有此难度
        if(!isset(btstore_get()->BASE[$baseId][CopyConf::$BASE_LEVEL_INDEX[$baseLv]]))
        {
            return FALSE;
        }
        return TRUE;
    }
    
    private function attackCopy ($copyId, $baseLv, $atkBaseId = 0)
    {
        echo "start to attack copy ".$copyId ." baselevel ".$baseLv."\n";
        $uid    =    RPCContext::getInstance()->getUid();
        $formation    =  EnFormation::getFormationObj($uid)->getFormation();
        $ncopy = new NCopy();
        $tmp = $ncopy->getCopyList();
        $copyList = $tmp['copy_list'];
        if (! isset($copyList[$copyId]))
        {
            $uid = RPCContext::getInstance()->getUid();
            $copyInfo = array('uid' => $uid, 'copy_id' => $copyId, 'score' => 0, 
            'prized_num' => 0, 'va_copy_info' => array('progress' =>
            array(),'defeat_num'=>array(),'reset_num'=>array()));
        } 
		else
        {
            $copyInfo = $copyList[$copyId];
        }
        $baseIds = btstore_get()->COPY[$copyId]['base'];
		var_dump($baseIds);
        foreach ($baseIds as $baseId)
        {
            if (empty($baseId) || (!empty($atkBaseId) && ($baseId > $atkBaseId)))
			{
                continue;
            }
            echo "start to attack base with baseid ".$baseId."\n";
            for ($j = 0; $j <= $baseLv; $j ++)
            {
                if ($this->isAtkBaseLv($copyInfo, $baseId, $j) === TRUE)
                {
                    $lvName = CopyConf::$BASE_LEVEL_INDEX[$j];
                    $armies = btstore_get()->BASE[$baseId][$lvName][$lvName .
                     '_army_arrays'];
                    $ret = $ncopy->enterBaseLevel($copyId, $baseId, $j);
                    if ($ret != 'ok')
                    {
                        echo "can not enter baselevel copyid " . $copyId .
                         " baseid " . $baseId . " baseLevel " . $j . "reason $ret\n";
                        return;
                    }
                    foreach ($armies as $index => $army)
                    {
                        if ($j == 0)
                        {
                            $fmt    =    array();
                            $teamId = intval(btstore_get()->ARMY[$army]['npc_team_id']);
                            // 如果没有找到这个NPC部队信息，则出错返回
                            $mstFmt = btstore_get()->TEAM[$teamId]['fmt'];
                            $hidInFmt    =    array();
                            foreach($mstFmt as $pos => $mstId)
                            {
                                if(empty($mstId))
                                {
                                    $fmt[$pos]    =    0;
                                    continue;
                                }
                                if(intval($mstId) == 1)
                                {
                                    foreach($formation as $positon => $hid)
                                    {
                                        if(in_array($hid, $hidInFmt) == TRUE)
                                        {
                                            continue;
                                        }
                                        $fmt[$pos]    =    $hid;
                                        $hidInFmt[] = $hid;
                                        break;
                                    }
                                }
                                else
                                {
                                    $fmt[$pos]    =    0;
                                }
                            }
                            $atkRet = $ncopy->doBattle($copyId, $baseId, $j, 
                            $army, array(), $fmt);
                            $atkRetStr    =    var_export($atkRet,true);
                            echo "attack ".$copyId." baseId ".$baseId." baselevel ".
                                    $j." armyid ".$army." result:".$atkRetStr."\n";
							if($atkRet['appraisal'] == 'E' || $atkRet['appraisal'] == 'F')
							{
								echo "fail\n";
								return FALSE;
							}
                        } 
                        else
                        {
                            $atkRet = $ncopy->doBattle($copyId, $baseId, $j, 
                            $army, array());
                            $atkRetStr    =    var_export($atkRet,true);
                            echo "attack ".$copyId." baseId ".$baseId." baselevel ".
                                    $j." armyid ".$army." result:".$atkRetStr."\n";
							if($atkRet['appraisal'] == 'E' || $atkRet['appraisal'] == 'F')

                            {
								echo "fail\n";
                                return FALSE;

                            }
						 }
                    }
                } 
                else
                {
                    echo "can not atk baselevel copyid " . $copyId . " baseid " .
                     $baseId . " baseLevel " . $j . "\n";
                }
            }
        }
        return TRUE;
    }

}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */

