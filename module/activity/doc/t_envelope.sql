set names utf8;

create table if not exists t_envelope
(
    eid            int unsigned auto_increment not null comment '红包id',
    uid            int unsigned not null comment '发送者uid',
    scale          int unsigned not null comment '红包范围(军团id，为0代表全服的)',
    send_time      int unsigned not null comment '发红包的时间',
    gold_num       int unsigned not null comment '发送的总金币数',
    left_num       int unsigned not null comment '剩余的份数',
    back_time      int unsigned not null comment '到期返还的时间',
    va_data        blob         not null comment '红包内容 envelopeInfo=>array(0 => num),msg=>string',
    
    primary key(eid),
    index envelope(send_time, uid)
)engine = InnoDb default charset utf8;