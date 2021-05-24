SELECT
    S1.s_name,--始发站
    S2.s_name,--终点站
    t_trainno,
    SP1.sp_departtime,--从始发站离开时间
    SP2.sp_arrivetime,--到达终点站时间
    P2.p_price-P1.p_price as price,
    P2.p_seattype,
    sl_seatleft
    --(SP2.sp_arrivetime-SP1.sp_departtime+SP2.sp_count-SP1.sp_count) as total_time
FROM    
    city as C1,
    city as C2,
    station as S1,
    station as S2,
    train,
    stop as SP1,
    stop as SP2,
    price as P1,
    price as P2,
    seatleft
WHERE 
    C1.c_name='太原' and
    C2.c_name='灵石' and
    C1.c_cityid=S1.s_cityid and
    C2.c_cityid=S2.s_cityid and
    SP1.sp_stationid=S1.s_stationid and
    SP2.sp_stationid=S2.s_stationid and
    SP1.sp_trainid=SP2.sp_trainid and
    (
        SP2.sp_count>SP1.sp_count
        or
        (
            SP2.sp_count=SP1.sp_count and
            SP1.sp_departtime<SP2.sp_arrivetime
        )
    )and
    sl_stationid=S1.s_stationid and
    sl_trainid=SP1.sp_trainid and
    sl_seattype=P2.p_seattype and
    P1.p_seattype=P2.p_seattype and
    sl_seatleft>0 and
    P1.p_stationid=S1.s_stationid and
    P2.p_stationid=S2.s_stationid and
    P1.p_trainid=sl_trainid and
    P2.p_trainid=sl_trainid and
    t_trainid=SP1.sp_trainid and
    sl_day='2020.10.1'
ORDER BY
    price;

