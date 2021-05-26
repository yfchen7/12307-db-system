  -- 第一步选出所有的换成可能
  with transfer_table (
      day1, day2,
      s1c, s2c, s3c, s4c,
      s1s, s2s, s3s, s4s,
      s1_at, s1_dt, s2_at, 
      s3_at, s3_dt, s4_at,
    s1_id, s2_id, s3_id, s4_id, t1_id, t2_id
    ) as
      (
       SELECT
          R1.r_day,
          R2.r_day,
          SP1.sp_count,
          SP2.sp_count,
          SP3.sp_count,
          SP4.sp_count,
          SP1.sp_seq,
          SP2.sp_seq,
          SP3.sp_seq,
          SP4.sp_seq,
          SP1.sp_arrivetime,
          SP1.sp_departtime,
          SP2.sp_arrivetime,
          SP3.sp_arrivetime,
          SP3.sp_departtime,
          SP4.sp_arrivetime,
          S1.s_stationid,
          S2.s_stationid,
          S3.s_stationid,
          S4.s_stationid,
          T1.t_trainid,
          T2.t_trainid
      FROM
          city as C1,
          city as C2,
          city as C3,
          station as S1,
          station as S2,
          station as S3,
          station as S4,
          train as T1,
          train as T2,
          stop as SP1,
          stop as SP2,
          stop as SP3,
          stop as SP4,
          runday as R1,
          runday as R2
      WHERE
          -- A start
          C1.c_name='$scity' and
          S1.s_cityid=C1.c_cityid and
          -- B end
          C3.c_name='$ecity' and
          S4.s_cityid=C3.c_cityid and
          -- C transfer
          S2.s_cityid = C2.c_cityid and
          S3.s_cityid = C2.c_cityid and
          SP1.sp_stationid=S1.s_stationid and
          SP2.sp_stationid=S2.s_stationid and
          SP3.sp_stationid=S3.s_stationid and
          SP4.sp_stationid=S4.s_stationid and
          SP1.sp_departtime>'$stime' and
          SP1.sp_trainid=T1.t_trainid and
          SP2.sp_trainid=T1.t_trainid and
          SP3.sp_trainid = T2.t_trainid and
          SP4.sp_trainid = T2.t_trainid and
          T1.t_trainid != T2.t_trainid and
          (
              SP2.sp_count>SP1.sp_count
              or
              (
                  SP2.sp_count=SP1.sp_count and
                  SP1.sp_arrivetime<SP2.sp_arrivetime
              )
          )and
          (
              SP4.sp_count>SP3.sp_count
              or
              (
                  SP4.sp_count=SP3.sp_count and
                  SP3.sp_arrivetime<SP4.sp_arrivetime
              )
          )and
          -- date
          (
              (
                  R1.r_day + SP1.sp_count = '$sday' and
                  SP1.sp_arrivetime <= SP1.sp_departtime 
              )or(
                  SP1.sp_arrivetime > SP1.sp_departtime and
                  R1.r_day + SP1.sp_count + 1 = '$sday'
              )
          )
          and
          (
              (
  
                  SP2.sp_stationid=SP3.sp_stationid and
                  (
                      R2.r_day+SP3.sp_count+SP3.sp_departtime-
                      (R1.r_day+SP2.sp_count+SP2.sp_arrivetime)<=interval '4 hour' and
                      R2.r_day+SP3.sp_count+SP3.sp_departtime-
                      (R1.r_day+SP2.sp_count+SP2.sp_arrivetime)>=interval '1 hour'
                  )
              )
              or
              (
                  SP2.sp_stationid!=SP3.sp_stationid and
                  (
                      R2.r_day+SP3.sp_count+SP3.sp_departtime-
                      (R1.r_day+SP2.sp_count+SP2.sp_arrivetime)<=interval '4 hour' and
                      R2.r_day+SP3.sp_count+SP3.sp_departtime-
                      (R1.r_day+SP2.sp_count+SP2.sp_arrivetime)>=interval '2 hour'
                  )
              )
          )
      ORDER BY
          T1.t_trainid, 
          T2.t_trainid
      ),
  -- 第二步计算换乘的总价格
   tmp(
      day1, day2, c1,c2,c3,c4,
      seq1,seq2,seq3,seq4,
      at1, dt1, at2,
      at3, dt3, at4,
      t1,t2,s1,s2,s3,s4,
      p1,p2,seat1,seat2,
      total_price
  )as
  (
  SELECT
      transfer_table.day1,
      transfer_table.day2,
      transfer_table.s1c,
      transfer_table.s2c,
      transfer_table.s3c,
      transfer_table.s4c,
      transfer_table.s1s,
      transfer_table.s2s,
      transfer_table.s3s,
      transfer_table.s4s,
      transfer_table.s1_at,
      transfer_table.s1_dt,
      transfer_table.s2_at,
      transfer_table.s3_at,
      transfer_table.s3_dt,
      transfer_table.s4_at,
      transfer_table.t1_id,
      transfer_table.t2_id,
      transfer_table.s1_id,
      transfer_table.s2_id,
      transfer_table.s3_id,
      transfer_table.s4_id,
      P2.p_price-P1.p_price as price1,
      P4.p_price-P3.p_price as price2,
      P1.p_seattype,
      P3.p_seattype,
      P2.p_price-P1.p_price+P4.p_price-P3.p_price as price
  FROM
      price as P1,
      price as P2,
      price as P3,
      price as P4,
      transfer_table
  WHERE
      P1.p_stationid=transfer_table.s1_id and
      P2.p_stationid=transfer_table.s2_id and
      P1.p_trainid=transfer_table.t1_id and
      P2.p_trainid=transfer_table.t1_id and
      p1.p_seattype = P2.p_seattype and 
      P3.p_stationid=transfer_table.s3_id and
      P4.p_stationid=transfer_table.s4_id and
      P3.p_trainid=transfer_table.t2_id and
      P4.p_trainid=transfer_table.t2_id and
      P3.p_seattype = P4.p_seattype
  ORDER BY
      price
  LIMIT 160
  ),
  -- 第三步查询是否有余票，计算总时间
  final(
      total_price,d1,d2,
      s1,s2,t1,
      at1,dt1,at2,seat1,p1,sl1,
      s3,s4,t2,
      dt3,at4,seat2,p2,sl2,total_time
  )as
  (
  SELECT
      tmp.total_price as price,
      tmp.day1 as d1,
      tmp.day2 as d2,
      S1.s_name as station1,
      S2.s_name as station2,
      T1.t_trainno as train1,
      tmp.at1 as arrivetime1,
      to_date('$sday', 'yyyy-MM-dd') + tmp.dt1 as departtime1,
      tmp.day1 + tmp.c2 + tmp.at2 as arrivetime2,
      tmp.seat1 as seattype1,
      tmp.p1 as price1,
      min(SL1.sl_seatleft) as seatleft1,
      S3.s_name as station3,
      S4.s_name as station4,
      T2.t_trainno as train2,
  
      (case when tmp.at3<=tmp.dt3 then tmp.day2 + tmp.c3 + tmp.dt3
            else tmp.day2 + 1 + tmp.c3 + tmp.dt3 end)  as departtime3,
      tmp.day2 + tmp.c4 + tmp.at4 as arrivetime4,
      tmp.seat2 as seattype2,
      tmp.p2 as price2,
      min(SL2.sl_seatleft) as seatleft2,
      (case when tmp.at1<=tmp.dt1 then (tmp.day2+tmp.c4+tmp.at4)-(tmp.day1+tmp.c1+tmp.dt1)
            else (tmp.day2+tmp.c4+tmp.at4)-(tmp.day1+tmp.c1+1+tmp.dt1) end) as total_time
  FROM
      station as S1,
      station as S2,
      station as S3,
      station as S4,
      train as T1,
      train as T2,
      stop as SP1,
      stop as SP2,
      seatleft as SL1,
      seatleft as SL2,
      tmp
  WHERE
      S1.s_stationid=tmp.s1 and
      S2.s_stationid=tmp.s2 and
      S3.s_stationid=tmp.s3 and
      S4.s_stationid=tmp.s4 and
      T1.t_trainid=tmp.t1 and
      T2.t_trainid=tmp.t2 and
      SL1.sl_trainid=tmp.t1 and
      SL2.sl_trainid=tmp.t2 and
      SL1.sl_stationid=SP1.sp_stationid and
      SP1.sp_trainid=tmp.t1 and 
      SP2.sp_trainid=tmp.t2 and 
      SL1.sl_day=tmp.day1 and
      SL2.sl_day=tmp.day2 and
      SP1.sp_seq>=tmp.seq1 and
      SP1.sp_seq<tmp.seq2 and
      SL2.sl_stationid=SP2.sp_stationid and
      SP2.sp_seq>=tmp.seq3 and
      SP2.sp_seq<tmp.seq4
  GROUP BY
      day1, day2,
      price,
      station1,
      station2,
      train1,
      arrivetime1,
      departtime1,
      arrivetime2,
      seattype1,
      price1,
      station3,
      station4,
      train2,
      departtime3,
      arrivetime4,
      seattype2,
      price2,
      total_time
  ORDER BY
      price
  )
  ,
  
  SNLT(
      d1,d2,
      s1,s2,t1,
      dt1,at2,
      s3,s4,t2,
      dt3,at4,total_time,
      total_price
  )as
  (
  SELECT
      d1,d2,
      s1,s2,t1,
      dt1,at2,
      s3,s4,t2,
      dt3,at4,total_time,
      min(final.total_price) filter(where final.sl1>0 and final.sl2>0) as price
      
  FROM 
      -- NLT
      final
  GROUP BY
      d1,d2,
      s1,s2,t1,
      dt1,at2,
      s3,s4,t2,
      dt3,at4,total_time
  ORDER BY
      price
  LIMIT 10
  ),
  -- 第四步对上面的组合进一步查询两次换乘车辆的详细信息
  LT(
      s1,s2,t1,seat1,dt1,at2,sl1,s3,s4,t2,seat2,dt3,at4,sl2,p1,p2,price,total_time
  )as
  (
  SELECT
      
      SNLT.s1,
      SNLT.s2,
      SNLT.t1,
      SL1.sl_seattype as seattype1,
      SNLT.dt1 as dptime1,
      SNLT.at2,
      min(SL1.sl_seatleft) as seatleft1,
      SNLT.s3,
      SNLT.s4,
      SNLT.t2,
      SL2.sl_seattype as seattype2,
      SNLT.dt3,
      SNLT.at4,
      min(SL2.sl_seatleft) as seatleft2,
      P2.p_price - P1.p_price as price1,
      P4.p_price - P3.p_price as price2,
      SNLT.total_price as price,
      SNLT.total_time as total_time
  FROM
      SNLT,
      station as S1,
      station as S2,
      station as S3,
      station as S4,
      train as T1,
      train as T2,
      stop as SP1,
      stop as SP2,
      stop as SP3,
      stop as SP4,
      stop as SP5,
      stop as SP6,
      seatleft as SL1,
      seatleft as SL2,
      price as P1,
      price as P2,
      price as P3,
      price as P4
  WHERE
      S1.s_name = SNLT.s1 and
      S2.s_name = SNLT.s2 and
      S3.s_name = SNLT.s3 and
      S4.s_name = SNLT.s4 and
      T1.t_trainno = SNLT.t1 and
      T2.t_trainno = SNLT.t2 and
      SL1.sl_day = SNLT.d1 and
      SL2.sl_day = SNLT.d2 and
      SP1.sp_trainid = T1.t_trainid and
      SP2.sp_trainid = T1.t_trainid and
      SP3.sp_trainid = T1.t_trainid and
      SP4.sp_trainid = T2.t_trainid and
      SP5.sp_trainid = T2.t_trainid and
      SP6.sp_trainid = T2.t_trainid and
      SP1.sp_stationid = S1.s_stationid and
      SP2.sp_stationid = SL1.sl_stationid and
      SP3.sp_stationid = S2.s_stationid and
      SP4.sp_stationid = S3.s_stationid and
      SP5.sp_stationid = SL2.sl_stationid and
      SP6.sp_stationid = S4.s_stationid and
      SL1.sl_trainid = T1.t_trainid and
      SL2.sl_trainid = T2.t_trainid and
      P1.p_trainid = T1.t_trainid and
      P2.p_trainid = T1.t_trainid and
      P3.p_trainid = T2.t_trainid and
      P4.p_trainid = T2.t_trainid and
      P1.p_stationid = S1.s_stationid and
      P2.p_stationid = S2.s_stationid and
      P3.p_stationid = S3.s_stationid and
      P4.p_stationid = S4.s_stationid and
      P1.p_seattype = P2.p_seattype and
      P1.p_seattype = SL1.sl_seattype and
      P3.p_seattype = P4.p_seattype and
      P3.p_seattype = SL2.sl_seattype and
      SP2.sp_seq >= SP1.sp_seq and
      SP2.sp_seq < SP3.sp_seq and
      SP5.sp_seq >= SP4.sp_seq and
      SP5.sp_seq < SP6.sp_seq
  GROUP BY
      SNLT.s1,
      SNLT.s2,
      SNLT.t1,
      seattype1,
      SNLT.dt1,
      SNLT.at2,
      SNLT.s3,
      SNLT.s4,
      SNLT.t2,
      seattype2,
      SNLT.dt3,
      SNLT.at4,
      total_time,
      price,
      price1,
      price2
  ORDER BY
      price
  )
  -- 对详细信息进行归并（座位信息变为多列），选出最终的10个车辆、经停站站点组合
  SELECT
      LT.t1,
      LT.s1,
      LT.s2,
      LT.dt1,
      LT.at2,
      max(LT.sl1) filter(where LT.seat1='硬座') as yz_left,
      max(LT.sl1) filter(where LT.seat1='软座') as rz_left,
      max(LT.sl1) filter(where LT.seat1='硬卧上') as yws_left,
      max(LT.sl1) filter(where LT.seat1='硬卧中') as ywz_left,
      max(LT.sl1) filter(where LT.seat1='硬卧下') as ywx_left,
      max(LT.sl1) filter(where LT.seat1='软卧上') as rws_left,
      max(LT.sl1) filter(where LT.seat1='软卧下') as rwx_left,
      max(LT.p1) filter(where LT.seat1='硬座') as yz_price,
      max(LT.p1) filter(where LT.seat1='软座') as rz_price,
      max(LT.p1) filter(where LT.seat1='硬卧上') as yws_price,
      max(LT.p1) filter(where LT.seat1='硬卧中') as ywz_price,
      max(LT.p1) filter(where LT.seat1='硬卧下') as ywx_price,
      max(LT.p1) filter(where LT.seat1='软卧上') as rws_price,
      max(LT.p1) filter(where LT.seat1='软卧下') as rwx_price,
      LT.price,

      LT.total_time,
      LT.t2,
      LT.s3,
      LT.s4,
      LT.dt3,
      LT.at4,
      max(LT.sl2) filter(where LT.seat2='硬座') as yz_left,
      max(LT.sl2) filter(where LT.seat2='软座') as rz_left,
      max(LT.sl2) filter(where LT.seat2='硬卧上') as yws_left,
      max(LT.sl2) filter(where LT.seat2='硬卧中') as ywz_left,
      max(LT.sl2) filter(where LT.seat2='硬卧下') as ywx_left,
      max(LT.sl2) filter(where LT.seat2='软卧上') as rws_left,
      max(LT.sl2) filter(where LT.seat2='软卧下') as rwx_left,
      max(LT.p2) filter(where LT.seat2='硬座') as yz_price,
      max(LT.p2) filter(where LT.seat2='软座') as rz_price,
      max(LT.p2) filter(where LT.seat2='硬卧上') as yws_price,
      max(LT.p2) filter(where LT.seat2='硬卧中') as ywz_price,
      max(LT.p2) filter(where LT.seat2='硬卧下') as ywx_price,
      max(LT.p2) filter(where LT.seat2='软卧上') as rws_price,
      max(LT.p2) filter(where LT.seat2='软卧下') as rwx_price
 
  FROM
      LT
  GROUP BY
      LT.s1,
      LT.s2,
      LT.t1,
      LT.dt1,
      LT.at2,
      LT.s3,
      LT.s4,
      LT.t2,
      LT.dt3,
      LT.at4,
      LT.price,
      LT.total_time
  ORDER BY
      LT.price,
      LT.total_time,
      LT.dt1
  LIMIT
      10
  ;