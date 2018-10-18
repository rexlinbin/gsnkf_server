<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: ResetMinealTimer.php 240326 2016-04-26 11:21:07Z ShuoLiu $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/test/ResetMinealTimer.php $
 * @author $Author: ShuoLiu $(hoping@babeltime.com)
 * @date $Date: 2016-04-26 11:21:07 +0000 (Tue, 26 Apr 2016) $
 * @version $Revision: 240326 $
 * @brief 
 *  
 **/

class ResetMinealTimer extends BaseScript
{
    protected function executeScript($arrOption)
    {
        $executeMethod = 'mineral.duePit';
        $executeTime = '';
        
        //拉出所有失败的资源矿发奖timer的tid
        $data = new CData ();
        $data->select ( 'tid' )->from ( 't_timer' )
        ->where(array('execute_method','=', $executeMethod));
        if(!empty($executeTime))
        {
            $data->where(array('execute_time','>=',$executeTime));
        }
        $data->where(array('status','=',TimerStatus::FAILED));
        $arrRet = $data->query();
        
        
        if(empty($arrRet))
        {
            Logger::info("nothing to fix!");
            echo 'nothing';
        }
        
        foreach($arrRet as $key => $value)
        {
            $tid = $value['tid'];
            //查看这个tid在不在矿区的表里面
            $ret = $data->select(TblMineralField::DUETIMER)
            ->from('t_mineral')
            ->where(array(TblMineralField::DUETIMER,'=',$tid))
            ->query();
            
            if(!empty($ret))
            {
                EnTimer::resetTask($tid);
            }
            Logger::info("Reset Timer %d ok!",$tid);
        }
        
        echo 'ok';
    }
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */