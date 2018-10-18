<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: MyDestiny.class.php 81752 2013-12-19 07:43:05Z TiantianZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/destiny/MyDestiny.class.php $
 * @author $Author: TiantianZhang $(zhangtiantian@babeltime.com)
 * @date $Date: 2013-12-19 07:43:05 +0000 (Thu, 19 Dec 2013) $
 * @version $Revision: 81752 $
 * @brief 
 *  
 **/
class MyDestiny
{
    private $uid;
    private $buffer = array();
    private $destinyInfo = array();
    
    public function __construct($uid)
    {
        $this->uid = $uid;
        if(empty($this->uid))
        {
            throw new FakeException('this.uid is empty.can not get destiny info');
        }
        $info = DestinyDao::getDestinyInfo($this->uid, DestinyDef::$ARR_SELECT_FIELD);
        if(empty($info))
        {
            $info = $this->initDestinyInfo();
        }
        $this->destinyInfo = $info;
        $this->buffer = $this->destinyInfo;
    }
    
    private function initDestinyInfo()
    {
        $destinyInfo = array(
                DestinyDef::TBL_FIELD_UID => $this->uid,
                DestinyDef::TBL_FIELD_CUR_DESTINY => 0,
                DestinyDef::TBL_FIELD_VA_DESTINY => array()
                );
        DestinyDao::insertDestinyInfo($destinyInfo);
        return $destinyInfo;
    }
    
    public function getDestinyInfo()
    {
        return $this->destinyInfo;
    }
    
    public function activateDestiny($destinyId)
    {
        $this->destinyInfo[DestinyDef::TBL_FIELD_CUR_DESTINY] = $destinyId;
    }
    
    public function getCurDestinyId()
    {
        return $this->destinyInfo[DestinyDef::TBL_FIELD_CUR_DESTINY];
    }
    
    public function save()
    {
        if($this->destinyInfo != $this->buffer)
        {
            DestinyDao::updateDestinyInfo($this->uid, $this->destinyInfo);
            $this->buffer = $this->destinyInfo;
        }
    }
    
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */