SELECT
    S1.s_name,
    S2.s_name,
    S3.s_name,
    S4.s_name,
    SP1.sp_departtime,
    SP2.sp_arrivetime,
    SP3.sp_departtime,
    SP4.sp_arrivetime,
    P2.p_price-P1.p_price as price1,
    P4.p_price-P3.p_price as price2,
    P1.p_seattype,
    P2.p_seattype,
    SL1.sl_seatleft,
    SL2.sl_seatleft,
    T1.t_trainno,
    T2.t_trainno,
    P2.p_price-P1.p_price+P4.p_price-P3.p_price as total_price
    --((SP2.sp_arrivetime-SP1.sp_departtime+SP2.sp_count-SP1.sp_count)+(SP4.sp_arrivetime-SP3.sp_departtime+SP4.sp_count-SP3.sp_count)) as total_time
FROM
    city as C1,
    city as C2,
    city as C3,
    city as C4,
    station as S1,
    station as S2,
    station as S3,
    station as S4,
    stop as SP1,
    stop as SP2,
    stop as SP3,
    stop as SP4,
    price as P1,
    price as P2,
    price as P3,
    price as P4,
    seatleft as SL1,
    seatleft as SL2,
    train as T1,
    train as T2,
    runday as R1
WHERE
    C1.c_name='太原' and
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
    SL1.sl_stationid=S1.s_stationid and
    SL1.sl_trainid=SP1.sp_trainid and
    SL1.sl_seattype=P1.p_seattype and
    SL1.sl_seattype=P2.p_seattype and
    SL1.sl_day='2020.10.1' and
    SL1.sl_seatleft>0 and
    P1.p_stationid=S1.s_stationid and
    P2.p_stationid=S2.s_stationid and
    P1.p_trainid=SL1.sl_trainid and
    P2.p_trainid=SL1.sl_trainid and
    T1.t_trainid=SP1.sp_trainid and
    C2.c_name=C3.c_name and
    T1.t_trainid!=T2.t_trainid and
    R1.r_trainid=T2.t_trainid and
    (
        (
            SP2.sp_stationid=SP3.sp_stationid and
            (
                R1.r_day+SP3.sp_count+SP3.sp_arrivetime-
                (to_date('2020.10.1','yyyy-MM-dd')+SP2.sp_count+SP2.sp_arrivetime)<=interval '4 hour' and
                R1.r_day+SP3.sp_count+SP3.sp_arrivetime-
                (to_date('2020.10.1','yyyy-MM-dd')+SP2.sp_count+SP2.sp_arrivetime)>=interval '1 hour'
            )
        )
        or
        (
            SP2.sp_stationid!=SP3.sp_stationid and
            (
                R1.r_day+SP3.sp_count+SP3.sp_arrivetime-
                (to_date('2020.10.1','yyyy-MM-dd')+SP2.sp_count+SP2.sp_arrivetime)<=interval '4 hour' and
                R1.r_day+SP3.sp_count+SP3.sp_arrivetime-
                (to_date('2020.10.1','yyyy-MM-dd')+SP2.sp_count+SP2.sp_arrivetime)>=interval '2 hour'
            )
        )
    )and
    C4.c_name='西安' and
    C3.c_cityid=S3.s_cityid and
    C4.c_cityid=S4.s_cityid and
    SP3.sp_stationid=S3.s_stationid and
    SP4.sp_stationid=S4.s_stationid and
    SP3.sp_trainid=SP4.sp_trainid and
    (
        SP4.sp_count>SP3.sp_count
        or
        (
            SP4.sp_count=SP3.sp_count and
            SP3.sp_departtime<SP4.sp_arrivetime
        )
    )and
    SL2.sl_stationid=S3.s_stationid and
    SL2.sl_trainid=SP3.sp_trainid and
    SL2.sl_seattype=P3.p_seattype and
    SL2.sl_seattype=P4.p_seattype and
    SL2.sl_seatleft>0 and
    P3.p_stationid=S3.s_stationid and
    P4.p_stationid=S4.s_stationid and
    P3.p_trainid=SL2.sl_trainid and
    P4.p_trainid=SL2.sl_trainid and
    T2.t_trainid=SP3.sp_trainid and
    SL2.sl_day=R1.r_day
GROUP BY
    S1.s_name,
    S2.s_name,
    S3.s_name,
    S4.s_name,
    SP1.sp_departtime,
    SP2.sp_arrivetime,
    SP3.sp_departtime,
    SP4.sp_arrivetime,
    price1,
    price2,
    P1.p_seattype,
    P2.p_seattype,
    SL1.sl_seatleft,
    SL2.sl_seatleft,
    T1.t_trainno,
    T2.t_trainno,
    total_price
ORDER BY 
    total_price
LIMIT 10;






