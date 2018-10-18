<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: checkCopyBase.php 69184 2013-10-16 10:30:42Z TiantianZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/script/tools/checkCopyBase.php $
 * @author $Author: TiantianZhang $(zhangtiantian@babeltime.com)
 * @date $Date: 2013-10-16 10:30:42 +0000 (Wed, 16 Oct 2013) $
 * @version $Revision: 69184 $
 * @brief 
 *  
 **/
class CheckCopyBase extends BaseScript
{
	/* (non-PHPdoc)
     * @see BaseScript::executeScript()
     */
    protected function executeScript ($arrOption)
    {
        // TODO Auto-generated method stub
        // TODO Auto-generated method stub
        $usage    =    "usage::btscript game001 checkCopyBase.php check|fix uid"."\n";
        // TODO Auto-generated method stub
        if(empty($arrOption) || ($arrOption[0] == 'help') || (count($arrOption) < 2))
        {
            echo 'invalid parameter :'.$usage;
            return;
        }
        $uid    =    intval($arrOption[1]);
        $operation    =    $arrOption[0];
        if(empty($uid))
        {
            echo 'invalid uid :'.$usage;
            return;
        }
        if($operation != 'fix' && ($operation != 'check'))
        {
            echo 'invalid operation :'.$usage;
            return;
        }
        $fix    =    false;
        if($operation == 'fix')
        {
            $fix = true;
        }
        $proxy = new ServerProxy();
        $proxy->closeUser($uid);
        sleep(1);
        RPCContext::getInstance()->setSession(UserDef::SESSION_KEY_UID, $uid);
        $copyList    =    NCopyDAO::getAllCopies($uid);
        $deleteCopy = array();
        $deleteBase =    array();
        $deleteLevel = array();
        foreach($copyList as $copyId => $copyInfo)
        {
            $delete = false;
            $modify = false;
            if(!isset(btstore_get()->COPY[$copyId]))
            {
                $deleteCopy[] = $copyId; 
                $copyInfo['status'] = DataDef::DELETED;
                $delete = true;
            }
            if($delete == FALSE)
            {
                foreach($copyInfo['va_copy_info']['progress'] as $baseId => $baseStatus)
                {
                    if(!isset(btstore_get()->BASE[$baseId]))
                    {
                        $deleteBase[] = $baseId;
                        unset($copyInfo['va_copy_info']['progress'][$baseId]);
                        $modify = true;
                        continue;
                    }
                    for($baseLv = 1;$baseLv <= BaseLevel::HARD;$baseLv++)
                    {
                        $lvName = CopyConf::$BASE_LEVEL_INDEX[$baseLv];
                        if(!isset(btstore_get()->BASE[$baseId][$lvName]))
                        {
                            if($copyInfo['va_copy_info']['progress'][$baseId] >= $baseLv+2)
                            {
                                $copyInfo['va_copy_info']['progress'][$baseId] = $baseLv +1;
                                $deleteLevel[$baseId][] = $baseLv;
                                $modify = true;
                                break;
                            }
                        }
                    }
                }
            }
            if($fix == TRUE && ($delete || $modify))
            {
                NCopyDAO::saveCopy($copyInfo);
            }
        }
        echo "deletecopy :\n";
        var_dump($deleteCopy);
        echo "deletebase :\n";
        var_dump($deleteBase);
        echo "deletelevel :\n";
        var_dump($deleteLevel);
        if($fix == TRUE)
        {
            $copyInst    =    new     NCopy();
            $allCopy = $copyInst->getCopyList();
            var_dump($allCopy);
        }
    }

    
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */