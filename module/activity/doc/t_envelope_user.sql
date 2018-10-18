set names utf8;

create table if not exists t_envelope_user
( 
    uid            int unsigned not null comment '发送者uid',
    eid            int unsigned not null comment '红包id',
    recv_time      int unsigned not null comment '拆红包的时间',
    recv_index     int unsigned not null comment '领取的第几份',
    recv_gold      int unsigned not null comment '领取的金币数',
    
    primary key(uid, eid),
    index envelope_eid(eid),
    index envelope_recv(recv_time)
)engine = InnoDb default charset utf8;