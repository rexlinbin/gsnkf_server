<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: CountryWarBaseObj.class.php 215102 2015-12-11 02:12:20Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-32-44/module/world/countrywar/CountryWarBaseObj.class.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2015-12-11 10:12:20 +0800 (五, 2015-12-11) $
 * @version $Revision: 215102 $
 * @brief 
 *  
 *  基类，因为有不同的场景（与之前field类似），让相应对象继承该类，
 *  知道自己可以在哪些场景下调用，以及现在正在哪个场景，并据此做出可能不同的处理方式
 *  另：设计上处理并发问题（db层某些字段的自增，部分请求转到特定线程执行--如创建房间）
 *  现在看基类有点鸡肋
 *  
 **/
class CountryWarBaseObj
{
	private $allowedSceneArr = NULL;
	private $curScene = NULL;
	
	function __construct( $allowedSceneArr = array() )
	{
		$sceneNum = count( $allowedSceneArr );
		if( $sceneNum > 2 || $sceneNum <= 0  )
		{
			throw new InterException( 'invalid scene: %s', $allowedSceneArr );
		}
		$this->allowedSceneArr = $allowedSceneArr;
		$curScene = CountryWarUtil::getScene();
		if( !in_array( $curScene , $this->allowedSceneArr) )
		{
			throw new InterException( 'now allowed to call this in scene: %s, allScene: %s', $curScene, $this->allowedSceneArr);
		}
		$this->curScene = $curScene;
	}
	
	protected function isInnerScene()
	{
		return CountryWarUtil::isInnerScene();
	}
	
	protected function isCrossScene()
	{
		return CountryWarUtil::isCrossScene();
	}
	
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */