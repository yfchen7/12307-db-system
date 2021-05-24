--总订单数
SELECT
    count(*)
FROM    
    orders;
--总票价
SELECT
    sum(o_price)
FROM
    orders;
--热点车次排序
SELECT
    t_trainno,
    count(*) as sum
FROM
    train,
    orders
WHERE
    t_trainid=o_trainid
group by 
    t_trainno
order by 
    sum;
--注册用户列表
SELECT
    u_userid,
    u_realid,
    u_username,
    u_realname,
    u_ccard,
    u_phone
FROM
    usr;
--查看用户订单
SELECT  
    o_orderid,
    t_trainno,
    o_status,
    o_seattype，
    o_price,
    (o_travelday+o_departtime) as departtime,
    (o_travelday+SP2.sp_count-SP1.sp_count+o_arrivetime) as arrivetime, 
    S1.s_name,
    S2.s_name
FROM 
    orders,
    stop as SP1,
    stop as SP2,
    train,
    station as S1,
    station as S2
WHERE
    o_userid=[USERID] and
    o_trainid=t_trainid and
    SP1.sp_trainid=o_trainid and
    SP1.sp_stationid=o_departstation and
    SP2.sp_trainid=o_trainid and
    SP2.sp_stationid=o_arrivestation and
    S1.s_stationid=o_departstation and
    S2.s_stationid=o_arrivestation
