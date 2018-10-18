set names utf8;
create table if not exists t_mission_inner_user
(
        uid 				int unsigned not null comment 'uid',
        fame 				int unsigned not null comment '当前轮次的名望',		
		donate_item_num 	int unsigned not null comment '贡献个数',
		spec_mission_fame 	int unsigned not null comment '做任务得到的名望',
        update_time 		int unsigned not null comment '用来刷新的',
		rankreward_time 	int unsigned not null comment '排行榜奖励领取时间',
		dayreward_time 		int unsigned not null comment '每日奖励领取时间',
		
		va_mission blob not null comment '任务进度等 array(missionId => array(num => int, ext = array()))',
		
        primary key(uid)
)engine = InnoDb default charset utf8;
