set names utf8;

create table t_guildtask( 
    uid int unsigned not null comment '用户ID',
	reset_time int unsigned not null comment '重置时间',
	ref_num int unsigned not null comment '刷任务的次数',
    task_num int unsigned not null comment '今天军团任务干了几次了',
    forgive_time int unsigned not null comment '放弃任务的时间',
    
    
    va_guildtask blob not null comment 'array(task:array($pos=> array($id,$num,$status))))',
    primary key(uid)
)engine = InnoDb default charset utf8;
