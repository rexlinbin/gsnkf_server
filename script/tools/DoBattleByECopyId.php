<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: DoBattleByECopyId.php 62645 2013-09-03 05:42:30Z TiantianZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/script/tools/DoBattleByECopyId.php $
 * @author $Author: TiantianZhang $(zhangtiantian@babeltime.com)
 * @date $Date: 2013-09-03 05:42:30 +0000 (Tue, 03 Sep 2013) $
 * @version $Revision: 62645 $
 * @brief 
 *  
 **/
class DoBattleByECopyId extends BaseScript
{
	/* (non-PHPdoc)
     * @see BaseScript::executeScript()
     */
    protected function executeScript ($arrOption)
    {
        // TODO Auto-generated method stub
        if($arrOption[0] == 'help' || (count($arrOption) != 2))
        {
            echo "usage:btscript group DoBattleByECopyId.php uid copyid\n";
            return;
        }
        $uid    =    $arrOption[0];
        $copyId =    $arrOption[1];
        RPCContext::getInstance()->setSession('global.uid', intval($uid));
        $preNCopy    =    btstore_get()->ELITECOPY[$copyId]['pre_open_copy'];
        $ncopy       = new NCopy();
        $ncopyList    =    $ncopy->getCopyList();
        if($this->isCopyPassed($ncopyList, $preNCopy) == FALSE)
        {
            echo "please attack monster in normal copy ".$preNCopy."\n";
            return;
        }
        $atkCopies    =    array($copyId);
        $preCopy    =    btstore_get()->ELITECOPY[$copyId]['pre_copy'];
        while(!empty($preCopy))
        {
            if($this->isECopyPassed($preCopy) == TRUE)
            {
                break;
            }
            $copyId = $preCopy;
            $preCopy = btstore_get()->ELITECOPY[$copyId]['pre_copy'];
            $atkCopies[] = $copyId;
        }
        $atkCopies = array_reverse($atkCopies);
        foreach ($atkCopies as $atkCopy)
        {
           $ecopy    =    new ECopy();
           $ecopy->enterCopy($atkCopy);
           $baseId    =    btstore_get()->ELITECOPY[$atkCopy]['base_id'];
           $armies    =    btstore_get()->BASE[$baseId]['simple']['simple_army_arrays']->toArray();
           foreach($armies as $army)
           {
               echo "attack monster:copyid ".$atkCopy.",army ".$army."\n";
               $atkRet    =    $ecopy->doBattle($atkCopy, $army, array());
               var_dump($atkRet);
           }
        }
    }
    
    private function isECopyPassed($copyId)
    {
        $ecopy    =    new ECopy();
        $copyInfo    =    $ecopy->getEliteCopyInfo();
        $progress    =    $copyInfo['va_copy_info']['progress'];
        if($progress[$copyId]>=EliteCopyStatus::PASS)
        {
            return TRUE;
        }
        return FALSE;
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
    
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */