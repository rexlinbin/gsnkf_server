<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: ICountryWarInner.class.php 241169 2016-05-05 13:05:34Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/world/countrywar/ICountryWarInner.class.php $
 * @author $Author: BaoguoMeng $(zhangshiyu@babeltime.com)
 * @date $Date: 2016-05-05 13:05:34 +0000 (Thu, 05 May 2016) $
 * @version $Revision: 241169 $
 * @brief 
 *  
 *  服内接口类
 *  
 **/
interface ICountryWarInner 
{
	/**
	 * It is a long time ago, three kingdoms, 
	 * wei shu wu, stand on the east of acient asia ...
	 * The great war among them is now begin...
	 * 
	 * 						--san guo yan yi
	 * 
	 * 决赛抢夺资源平等怎么样，冠军在哪个国家算哪个赢
	 * 战斗结束后记得转积分
	 * 每个点的两个不同意义的积分有可能不一样
	 * 10:00-12:00pm 分组
	 * 开始分组不能膜拜
	 * 20:00-20:10 报名
	 * 20:10-20:15 分房间30秒倒计时
	 * 自动的时候加手动
	 * 初赛结束就不能再进了
	 * 
	 * 
	 * signForOneCountry 去掉full
	 * getCountryWarInfo forceId 的取值范围修改
	 *  去掉transferGold俩接口
 	 *  添加exchangeCocoin接口（兑换国战币）
 	 *  mySupport里删了部分字段
 	 *  
	 */
	
	
	/**
	 * 获得国战信息
	 * 
	 * @return
	 * 
	 * {
	 * 		ret => string								ok,
	 * 		teamId => int									分组id <= 0 为未分组					
	 * 		stage => string									后端当前的阶段，一下为阶段划分：
	 * 														team分组,signup报名,rangeRoom分房间,audition初赛,
	 * 														support助威,finaltion决赛,worship膜拜
	 * 		cocoin => int 									国战币
	 * 		timeConfig
	 * 		{
	 * 			teamBegin=>int								分组开始时间		
	 * 			signupBegin=>int							报名开始时间
	 * 			rangeRoomBegin=>int							分房开始时间
	 * 			auditonBegin=>int							初赛开始时间
	 * 			supportBegin=>int							助威开始时间
	 * 			finaltionBegin=>int							决赛开始时间
	 * 			worshipBegin=>int							膜拜开始时间
	 * 		}
	 * 		detail											在不同阶段返回的信息
	 * 		{
	 * 			1.team										无 
	 * 			2.signup
	 * 			  countryId							
	 * 			  signup_time								自己的报名时间
	 * 			  country_sign_num							各个国家的报名人数
	 * 			  {
	 * 				 countryId:int => count:int,			国家代号:魏1蜀2吴3群4
	 * 			  }
	 * 			3.rangeRoom									无
	 * 			4.audition									无
	 * 			5.support
	 * 			  forceInfo => 
	 * 				{ 
	 * 					forceId:int => { countryId:int,... }, 战斗群分配(对阵双方)：forceId:1|2
	 * 					... 
	 * 				} 								
	 * 			  memberInfo =>	
	 * 				[
	 * 						只给前几个，展示80个人的信息单独有接口
	 * 						{
	 * 							pid
	 * 							server_id
	 * 							uname
	 * 							htid
	 * 							vip
	 * 							level
	 * 							fight_force
	 * 							fans_num
	 * 							dress=>{}
	 * 						}
	 * 				 ]
	 * 				mySupport => 
	 * 				{
	 * 					user
	 * 					{
	 * 							pid
	 * 							server_id
	 * 							uname
	 * 							htid
	 * 							dress=>{}
	 * 					 }
	 * 					side => int
	 * 				}
	 * 			6.final										无
	 * 			7.worship									膜拜对象的信息
	 * 
	 * 			   	  worship_time
	 * 				  server_name
	 * 				  fight_force
	 * 				  uname
	 * 				  htid
	 * 			      level
	 * 				  vip
	 * 				  title
	 * 				  dress => {}

	 * 		}
	 */
	public function getCoutrywarInfo();
	
	
	/**
	 * 		timeConfig
	 * 		{
	 * 			teamBegin=>int								分组开始时间		
	 * 			signupBegin=>int							报名开始时间
	 * 			rangeRoomBegin=>int							分房开始时间
	 * 			auditonBegin=>int							初赛开始时间
	 * 			supportBegin=>int							助威开始时间
	 * 			finaltionBegin=>int							决赛开始时间
	 * 			worshipBegin=>int							膜拜开始时间
	 * 		}
	 * 		teamId: int		
	 * 		worship_time									玩家膜拜时间
	 * 
	 */
	public function getCoutrywarInfoWhenLogin();
	
	
	/**
	 * 获取决赛的参赛选手，只在助威阶段需要使用
	 * 
	 * @return
	 * {
	 * 		ret:string									ok
	 * 		memberInfo:										
	 * 		{
	 * 			contryId:int
	 * 			{
	 * 				{
	 * 					pid
	 * 					server_id
	 * 					server_name
	 * 					uname
	 * 					htid
	 * 					vip
	 * 					level
	 * 					fight_force
	 * 					fans_num
	 * 					dress=>{}
	 * 				}
	 * 			}
	 * 		}
	 * }
	 * 
	 */
	public function getFinalMembers();
	
	/**
	 * 		我的助威 要显示什么信息，等策划
	 * 
	 * @return
	 * 
	 * <code>
	 * 
	 * 		{
	 * 			user
	 * 			{
	 * 				pid
	 * 				server_id
	 * 				uname
	 * 				htid
	 * 				vip
	 * 				level
	 * 				fight_force
	 * 				dress=>{}
	 * 			}
	 * 			countryId=>int
	 * 		}
	 * 
	 * </code>
	 * 
	 */
	public function getMySupport();
	
	/**
	 * 选择一个国家并且报名
	 * @param int $countryId								国家代号
	 * @return
	 * 
	 * <code>
	 * 
	 * {
	 * 		ret=>string 								ok|fail|expired,成功|失败|时间不对
	 * 		countrySignNum									各个国家的报名人数信息
	 * 		{
	 * 			countryId:int => count:int,					国家代号:魏1蜀2吴3群4
	 * 		}
	 * }
	 * 
	 * </code>
	 * 
	 */
	public function signForOneCountry( $countryId );
	
	/**
	 * 获取登录跨服要用到的信息
	 * @return
	 * 
	 * <code>
	 * 
	 * {
	 * 		ret => string 								ok|fail|errtime,成功|失败|时间不对
	 * 		serverIp=>string								跨服服务器ip
	 * 		port=>int										端口
	 * 		token=>string									跨服服务器身份验证
	 * }
	 * 
	 * </code>
	 * 
	 */
	public function getLoginInfo();

	/**
	 * 助威某个人
	 * @param int $pid
	 * @param int $serverId
	 * 
	 * @return string									ok||expired,成功||时间不对
	 * 		
	 */
	public function supportOneUser( $pid,$serverId );
	
	/**
	 * 助威某个国家
	 * @param int $countryId
	 * @return string 									ok|expired|noone,成功|时间不对,没人报名
	 */
	public function supportFinalSide( $side );
	
	/**
	 *膜拜
	 */
	public function worship();
	
	/**
	 * 划出一部分钱来给国战用
	 * @param int $amount 划出的数量
	 * 
	 */
	public function exchangeCocoin( $amount );
	
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */