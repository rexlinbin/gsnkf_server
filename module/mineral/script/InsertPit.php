<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: InsertPit.php 117161 2014-06-25 07:10:10Z TiantianZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/mineral/script/InsertPit.php $
 * @author $Author: TiantianZhang $(zhangtiantian@babeltime.com)
 * @date $Date: 2014-06-25 07:10:10 +0000 (Wed, 25 Jun 2014) $
 * @version $Revision: 117161 $
 * @brief 
 *  
 **/
class InsertPit extends BaseScript
{
	/* (non-PHPdoc)
     * @see BaseScript::executeScript()
     */
    protected function executeScript ($arrOption)
    {
        // TODO Auto-generated method stub
        $data = new CData();
        $ret = $data->select(TblMineralField::$FIELDS)
                    ->from('t_mineral')
                    ->where(array(TblMineralField::DOMAINID,'>',0))
                    ->query();
        if(empty($ret))
        {
            echo "NO PIT IN DB.\n";
            return;
        }
        $arrPit = array();
        foreach($ret as $pitInfo)
        {
            $domainId = $pitInfo[TblMineralField::DOMAINID];
            $pitId = $pitInfo[TblMineralField::PITID];
            $arrPit[$domainId][$pitId] = $pitInfo;
        }
        $goldType = MineralType::GOLD;
        $arrConfPit = btstore_get()->MINERAL;
        $arrNewPit = array();
        $arrUpdatePit = array();
        foreach($arrConfPit as $domainId => $domainInfo)
        {
            $confType = $domainInfo['domain_type'];
            foreach($domainInfo['pits'] as $pitId => $pitInfo)
            {
                $pitType = $domainInfo['type'][$pitId];
                if(!isset($arrPit[$domainId][$pitId]))
                {
                    $arrNewPit[$confType][$domainId][$pitId] = $pitType;
                }
                else if($pitType != $arrPit[$domainId][$pitId][TblMineralField::PITTYPE] ||
                        ($confType != $arrPit[$domainId][$pitId][TblMineralField::DOMAINTYPE]))
                {
                    $arrUpdatePit[$confType][$domainId][$pitId] = array(
                            $pitType,
                            $arrPit[$domainId][$pitId][TblMineralField::DOMAINTYPE],
                            $arrPit[$domainId][$pitId][TblMineralField::PITTYPE]
                            );
                }
            }
        }
        if(empty($arrNewPit) && (empty($arrUpdatePit)))
        {
            echo "NO NEW PIT TO INSERT OR UPDATE\n";
            return;
        }
        echo "ALL NEW PIT IS:\n";
        foreach($arrNewPit as $domainType => $arrDomain)
        {
            echo "----------------------------\n";
            echo "ALL NEW　PIT WITH DOMAIN TYPE $domainType :\n";
            foreach($arrDomain as $domainId => $arrPit)
            {
                foreach($arrPit as $pitId => $pitType)
                {
                    echo "$domainId  $pitId  $pitType \n";
                }
            }
        }
        echo "ALL UPDATE PIT IS:\n";
        foreach($arrUpdatePit as $domainType => $arrDomain)
        {
            echo "----------------------------\n";
            echo "ALL UPDATE　PIT WITH DOMAIN TYPE $domainType :\n";
            foreach($arrDomain as $domainId => $arrPit)
            {
                foreach($arrPit as $pitId => $pitInfo)
                {
                    echo "$domainId  $pitId $domainType ($pitInfo[1]) $pitInfo[0] ($pitInfo[2])  \n";
                }    
            }
        }
        
        if(isset($arrOption[0]) && ($arrOption[0] == 'insert'))
        {
            self::addArrNewPit($arrNewPit);
            self::updateArrPit($arrUpdatePit);
        }
    }
    
    public static function updateArrPit($arrUpdatePit)
    {
        foreach($arrUpdatePit as $domainType => $arrDomain)
        {
            echo "----------------------------\n";
            echo "UPDATE ALL　PIT WITH DOMAIN TYPE $domainType :\n";
            foreach($arrDomain as $domainId => $arrPit)
            {
                foreach($arrPit as $pitId => $pitInfo)
                {
                    $pitType = $pitInfo[0];
                    echo "UPDATE $domainId  $pitId $domainType ($pitInfo[1]) $pitInfo[0] ($pitInfo[2])\n";
                    MineralDAO::updatePitInfo(
                            array(TblMineralField::DOMAINTYPE=>$domainType,TblMineralField::PITTYPE=>$pitType), 
                            $domainId, $pitId);
                }
            }
        }
    }
    
    public static function addArrNewPit($arrNewPit)
    {
        foreach($arrNewPit as $domainType => $arrDomain)
        {
            echo "----------------------------\n";
            echo "INSERT ALL NEW　PIT WITH DOMAIN TYPE $domainType :\n";
            foreach($arrDomain as $domainId => $arrPit)
            {
                foreach($arrPit as $pitId => $pitType)
                {
                    echo "INSERT $domainId  $pitId  $pitType \n";
                    self::insertNewPit($domainId, $pitId, $domainType, $pitType);
                }
            }
        }
    }
    
    public static function insertNewPit($domainId,$pitId,$domainType,$pitType)
    {
        $pitInfo = array(
                TblMineralField::UID => 0,
                TblMineralField::DOMAINID => $domainId,
                TblMineralField::PITID => $pitId,
                TblMineralField::DOMAINTYPE => $domainType,
                TblMineralField::PITTYPE => $pitType,
                TblMineralField::DELAYTIMES => 0,
                TblMineralField::DUETIMER => 0,
                TblMineralField::OCCUPYTIME => 0,
                TblMineralField::TOTALGUARDSTIME => 0
                );
        MineralDAO::insertPitInfo($pitInfo);
        Logger::info('insert new pit domain %d pit %d domaintype %d pittype %d',
                $domainId,$pitId,$domainType,$pitType);
    }

    
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */