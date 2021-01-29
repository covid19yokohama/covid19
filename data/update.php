<?php

#
#
# /data にある *.json を更新するスクリプト
# cron で18-24時の間、10分感覚で　"$ php update.php" が実行される
# json が更新された場合、git push -> Actions -> covid19.yokohama が更新される
# json に更新があった場合、UPDATE_AWARE_FILE にツイートする内容を書き込む
#
#

error_reporting(E_ALL & ~E_NOTICE);

date_default_timezone_set('Asia/Tokyo');

const LASTUPDATE_JSON       = 'data.json';
const KU_BAR_JSON           = 'ku-bar.json';
const KU_MAP_JSON           = 'map2.json';
const KU_STACK_JSON         = 'ku-stack.json';
const KU_PER_100K_JSON      = 'ku-per-100k.json';
const PCR_TOTAL_JSON        = 'pcr-total.json';
const PCR_WEEKLY_JSON       = 'pcr-weekly.json';
const CUMULATIVE_TOTAL_JSON = 'cumulative-total.json';
const STATUS_AGE_JSON       = 'status_age.json';
const SEVEN_DAYS_AVE_JSON   = 'seven-days.json';
const PER_DAY_JSON          = 'total-per-day.json';
const UPDATE_AWARE_FILE     = 'update.txt';

update_jsons_by_csv();
update_ku_jsons();
update_pcr_jsons();

exit;




//
// update_lastupdate
//

function update_lastupdate()
{
    $DataJson = jsonUrl2array(LASTUPDATE_JSON);
    $DataJson['lastUpdate'] = date("Y-m-d H:i");
    arr2writeJson($DataJson, LASTUPDATE_JSON);
}



function update_jsons_by_csv()
{
    // csvとjsonの最終更新日付を比較
    $CumulativeTotal = jsonUrl2array(CUMULATIVE_TOTAL_JSON);
    if( compare_with_csv_ymd( end($CumulativeTotal['labels']) ) ) //ログの日付の最終行
        return;

    # 累計
    update_cumulative_total_json();

    # 日ごと
    update_per_day_json();

    # 7日移動平均
    update_7days_ave_json();

    # 年齢別の状況
    update_status_age_json();

    make_tweet_txt_by_csv();

    update_lastupdate();

    exit;
}



function make_tweet_txt_by_csv()
{
    $PerDays = jsonUrl2array(PER_DAY_JSON);
    
    # 日付、曜日
    $end_ymd    = end($PerDays['labels']); 
    $ym         = date('n/j', strtotime($end_ymd));

    $Week       = ['日曜', '月曜', '火曜', '水曜', '木曜', '金曜', '土曜'];
    $week_num   = date('w', strtotime($end_ymd));
    $week       = $Week[$week_num];


    # 人数
    $end_ymd_key =  array_search($end_ymd, $PerDays['labels']);

    foreach( $PerDays['datasets'] as $k_num => $v_Statuses )
        $StatusNums[] = $v_Statuses['data'][$end_ymd_key];

    $status_sum = array_sum($StatusNums);


    # 7日移動平均
    $SevenDays = jsonUrl2array(SEVEN_DAYS_AVE_JSON);
    $seven_days_end_ymd_key = $end_ymd_key - 6;
    $seven_days_ave = $SevenDays['datasets'][0]['data'][$seven_days_end_ymd_key];


    # 7日移動平均順位計算
    $SevenDaysPositives = $SevenDays['datasets'][0]['data'];
    $seven_days_count   = count($SevenDaysPositives)-1;
    arsort($SevenDaysPositives);
    $seven_days_rank = calc_rank( $SevenDaysPositives, $seven_days_count); // start 20-02-24 is monday --> "11/40位"


    # 順位計算
    foreach( $PerDays['datasets'] as $k_num => $v_Statuses )
        foreach ($v_Statuses['data'] as $k => $v)
            $PerDaysSums[$k] += $v;


    $day_rank = make_rank( $PerDaysSums, $week_num, 2);

    $tweet_txt = "・{$ym} 横浜市 陽性患者の発生状況
　計：{$status_sum}人({$week} {$day_rank['rank']})  
　　死：{$StatusNums[0]}
　　重：{$StatusNums[1]}
　　無〜中：{$StatusNums[2]}
　　退：{$StatusNums[3]}
　　調：{$StatusNums[4]}

・先週{$week}：{$day_rank['before_week'][0]}人
　先々週：{$day_rank['before_week'][1]}人

・7日移動平均：{$seven_days_ave}人({$seven_days_rank}位/{$seven_days_count}日)
https://covid19.yokohama";

    file_put_contents(UPDATE_AWARE_FILE, $tweet_txt);

// "
// ・1/21 陽性患者の発生状況
// 　計：200人(木曜 11/20位)
// 　　無〜中：11人
// 　　重：100人
// 　　死：11人
// 　　調：100人

// ・7日移動平均：100人(11/11位)

// ・先週木曜：xxx人
// 　先々週　：xxx人

// https://covid19.yokohama
// "

}







# 順位作成
function make_rank( $Positives, $today_week_num, $counter_week_num )
{

    #
    # 一週間ごとの陽性者数作成
    #

    foreach( $Positives as $k_num => $v_positives )
    {
        if ( $counter_week_num == $today_week_num ) {
            $WeekPositives[] = $v_positives;
            $week_count++; // 40週
        }

        $counter_week_num++ ;

        if( $counter_week_num == 7 )
            $counter_week_num = 0;                
    }

    #
    # 順位算出
    #
    $today_key = count($WeekPositives)-1;

    $ArsortedWeekPositives = $WeekPositives;
    arsort($ArsortedWeekPositives);

    $rank = calc_rank( $ArsortedWeekPositives, $today_key );




    $weeks = count($ArsortedWeekPositives);

    // # ついでに先週・先々週の人数も・・・
    $one_week_ago = $WeekPositives[$today_key-1];
    $two_week_ago = $WeekPositives[$today_key-2];


    return array(
        'rank'          => "{$rank}位/{$weeks}週",
        'before_week'   => 
            [
                $one_week_ago,
                $two_week_ago
            ]
        );
}



function calc_rank( $Arsorted, $today_key )
{
    $count = 1;
    foreach ($Arsorted as $k_num => $v_rank)
    {
        
        # 最初は1位
        if($count == 1)
        {
            $rank = 1;
            $count++;

            if($k_num == $today_key)
                return $rank;

            continue;
        }

        # 2つ目以降
        $prev_v_rank = $Arsorted[$k_num-1];

        # 同数だったらカウントアップしない
        if ( $v_rank != $prev_v_rank) {
            $rank++;
        }

        # keyが今日だったら順位確定
        if($k_num == $today_key)
            return $rank;
    }

}





#
# 日毎の累計感染者数のJSONを更新する
#

function update_cumulative_total_json()
{
    $CumulativeTotal = jsonUrl2array(CUMULATIVE_TOTAL_JSON);

    # 集計
    $CsvAggregate = csv_aggregate();

    # 代入
    $CumulativeTotal['date']    = date('Y-m-d',fetch_csv_timestamp());
    $CumulativeTotal['labels']  = $CsvAggregate['dates'];
    $CumulativeTotal['datasets']= $CsvAggregate['cumulative_datasets'];

    arr2writeJson($CumulativeTotal, CUMULATIVE_TOTAL_JSON);

    echo __FUNCTION__."\n";

    return;

}


#
# CSVのヘッダー日付と日付を比較する
#

function compare_with_csv_ymd( $ymd )
{
    $ymd     = date('Y-m-d',strtotime($ymd));
    $csv_ymd = date('Y-m-d',fetch_csv_timestamp());
        
    if( $csv_ymd == $ymd )
    {
        echo "same: $ymd update_jsons_by_csv\n";
        return TRUE;
    }

    echo "Not same: csv $csv_ymd == json $ymd  ...  \n";
    return FALSE;
}





#
# ex: 2020-12-09 から 2021-01-10 までの連続したymdを作成
#

function make_serial_date( $start_ymd, $end_ymd )
{
    list($year, $month, $day) = explode('-', $start_ymd);

    for($i=0;true;$i++)
    {
        $ymd    = date("Y-m-d", mktime(0, 0, 0, $month, $day+$i, $year));
        $Ymds[] = $ymd;

        if( $end_ymd == $ymd )
            return $Ymds;
    }
}



// 以下を作る
// $CumulativeTotal['datasets'][0]['data'][] = $CsvData['無症状から中等症'];
// $CumulativeTotal['datasets'][1]['data'][] = $CsvData['重症'];
// $CumulativeTotal['datasets'][2]['data'][] = $CsvData['死亡'];
// $CumulativeTotal['datasets'][3]['data'][] = $CsvData['退院等'];
// $CumulativeTotal['datasets'][4]['data'][] = $CsvData['調査中'];

// 0 [無症状] => 447
// 0 [軽症] => 2938
// 0 [中等症] => 111
// 1 [重症] => 27
// 2 [死亡] => 166
// 3 [退院] => 10137
// 3 [その他] => 507
// 4 [調査中] => 412

function csv_aggregate()
{
    # cache
    static $Aggregate;

    if( $Aggregate != '' )
        return $Aggregate;


    $Csv = fetch_positive_csv();


    # 症状をグループ・数値化
    $PatientStatus = str_replace('死亡'  , '0' , $Csv['患者_状態']);
    $PatientStatus = str_replace('重症'  , '1' , $PatientStatus);
    $PatientStatus = str_replace('無症状', '2' , $PatientStatus);
    $PatientStatus = str_replace('軽症'  , '2' , $PatientStatus);
    $PatientStatus = str_replace('中等症', '2' , $PatientStatus);
    $PatientStatus = str_replace('退院'  , '3' , $PatientStatus);
    $PatientStatus = str_replace('その他', '4' , $PatientStatus);
    $PatientStatus = str_replace('調査中', '4' , $PatientStatus);


    # 年代を数値化
    $AgeNum = str_replace('非公表'  , '0'  , $Csv['患者_年代']);
    $AgeNum = str_replace('調査中'  , '1'  , $AgeNum);
    $AgeNum = str_replace('幼児'    , '2'  , $AgeNum);
    $AgeNum = str_replace('10歳未満', '3'   , $AgeNum);
    $AgeNum = str_replace('10代'    , '4'  , $AgeNum);
    $AgeNum = str_replace('20代'    , '5'  , $AgeNum);
    $AgeNum = str_replace('30代'    , '6'  , $AgeNum);
    $AgeNum = str_replace('40代'    , '7'  , $AgeNum);
    $AgeNum = str_replace('50代'    , '8'  , $AgeNum);
    $AgeNum = str_replace('60代'    , '9'  , $AgeNum);
    $AgeNum = str_replace('70代'    , '10' , $AgeNum);
    $AgeNum = str_replace('80代'    , '11' , $AgeNum);
    $AgeNum = str_replace('90代'    , '12' , $AgeNum);
    $AgeNum = str_replace('90歳以上' , '13' , $AgeNum);



    #
    # 公表日一覧を作成
    #

    $start_ymd = $Csv['公表日'][0];
    $end_ymd   = end($Csv['公表日']); 
    $AllDates  = make_serial_date( $start_ymd, $end_ymd ); 



    #
    # 日毎の症状別カウントを作成
    #

    // 以下のフォーマットにする json向け
    // 
    // Array
    // (
    //     [退院等] => Array
    //         (
    //             [0] => 1     // [0]は 2020-02-13, 1は2/23の人数
    //             [2] => 1        

    $FlippedAllDates = array_flip($AllDates);
    foreach( $PatientStatus as $k_num => $k_status )
    {
        $ymd               = $Csv['公表日'][$k_num];  # 2021-01-17　退院 
        $datasets_line_num = $FlippedAllDates[$ymd]; # 2021-01-17 は 333
        $Datasets[$k_status][$datasets_line_num]++;  # 退院の333を++
    }

    #
    # 0人を埋める for json
    #

    $end_day_num = end($FlippedAllDates);
    foreach( $Datasets as $k_status => $PatientStatusSums)
    {
        $PatientStatusSums = fill_array_key_val_zero($PatientStatusSums, $end_day_num);
        $FilledDatasets[$k_status] = $PatientStatusSums;
    }
    ksort($FilledDatasets);
    


    #
    # 累積用
    #

    $CumulativeTotal = $FilledDatasets;

    foreach( $CumulativeTotal as $k_status_num => $PatientStatusSums)
    {
        foreach( $PatientStatusSums as $k_date_num => $v_day_num )
        {
            if($k_date_num > 0)
            {
                $CumulativeTotal[$k_status_num][$k_date_num] += $CumulativeTotal[$k_status_num][$k_date_num-1];
            }
        }
    }

    # json用に'data'を挿入
    foreach( $CumulativeTotal as $k_status_num => $PatientStatusSums)
        $CumulativeTotalJson[$k_status_num]['data'] = $PatientStatusSums;

    #
    # ７日移動平均用    
    #

    # 状態ごとの累計から、合計の累計に
    foreach( $FilledDatasets[0] as $k_num => $v)
    {
        $OneFilledDatasets[$k_num] =
              $FilledDatasets[0][$k_num] // 死亡
            + $FilledDatasets[1][$k_num] // 重症 
            + $FilledDatasets[2][$k_num] //  :
            + $FilledDatasets[3][$k_num]
            + $FilledDatasets[4][$k_num];
    }

    # make 7 days ave
    foreach( $OneFilledDatasets as $k_day_num => $v_positive_num )
    {
        # skip for 7 days
        if($k_day_num<6) continue;

        $SevenDaysAve[$k_day_num] =
              $OneFilledDatasets[$k_day_num]
            + $OneFilledDatasets[$k_day_num-1]
            + $OneFilledDatasets[$k_day_num-2]
            + $OneFilledDatasets[$k_day_num-3]
            + $OneFilledDatasets[$k_day_num-4]
            + $OneFilledDatasets[$k_day_num-5]
            + $OneFilledDatasets[$k_day_num-6];

        $SevenDaysAveJson[] = round($SevenDaysAve[$k_day_num]/7); # 四捨五入
    }



    #
    # 状態の年齢別の人数
    #

    $CsvForAge             = $Csv;
    $CsvForAge['患者_状態'] = $PatientStatus;
    $CsvForAge['患者_年代'] = $AgeNum;
    $AgeJson = age_aggregate( $CsvForAge );



    $Aggregate = array(
        'dates'                => $AllDates,
        'datasets'             => $FilledDatasets,
        'age_datasets'         => $AgeJson,
        'sevendays_datasets'   => $SevenDaysAveJson,
        'cumulative_datasets'  => $CumulativeTotalJson
    );

    return $Aggregate;
}




#
#
#

function age_aggregate( $Csv )
{
    foreach( $Csv['患者_状態'] as $k_ymd => $v_status)
    {
        $age = $Csv['患者_年代'][$k_ymd];
        $AgeDatasets[$v_status][$age]++;
    }

    # 数値の無い年代番号を0にする
    foreach ($AgeDatasets as $key => $value)
        $AgeDatasets[$key] = fill_array_key_val_zero($value, 13);  

    // [12] = 90年代 は使われていない
    foreach ($AgeDatasets as $key => &$value)
    {
        $value[12] = $value[13];
        unset($value[13]);
    }

    ksort($AgeDatasets);

    return $AgeDatasets;
}




#
#
#

function fill_array_key_val_zero( $arr, $end)
{
    for($i=0; $i <= $end; $i++)
        if( $arr[$i] == '' )
            $arr[$i] = 0;

    ksort($arr); // キー昇順・維持

    return $arr;
}



#
#
#

function fetch_csv_timestamp()
{
    # cache
    static $timestamp;

    if( $timestamp != '' )
        return $timestamp;

    # ヘッダーのタイムスタンプを入手
    $timestamp = url2modified_timestamp('https://www.city.yokohama.lg.jp/city-info/koho-kocho/koho/topics/corona-data.files/141003_yokohama_covid19_patients.csv');

    return $timestamp;
}






#
# update_7days_ave_json ave
#

function update_7days_ave_json()
{
    # current data
    $SevenDaysAve = jsonUrl2array(SEVEN_DAYS_AVE_JSON);

    # 集計
    $CsvAggregate = csv_aggregate();

    # json用arrayを作成
    $SevenDaysAve['date']               = date('Y-m-d',fetch_csv_timestamp());
    $SevenDaysAve['labels']             = array_shift_repeat($CsvAggregate['dates'], 6);
    $SevenDaysAve['datasets'][0]['data']= $CsvAggregate['sevendays_datasets'];

    arr2writeJson($SevenDaysAve, SEVEN_DAYS_AVE_JSON);

    echo __FUNCTION__."\n";
}




#
#
#

function array_shift_repeat( $arr, $num )
{
    for($i=1; $i<=$num; $i++)
        array_shift($arr);

    return $arr;
}


#
# get_patient_count_key_date_arr
#

function get_patient_count_key_date_arr()
{
    # fetch_positive_csv
    $patient_arr = fetch_positive_csv();

    # count patient per day
    $patient_count_key_date_arr = array_count_values($patient_arr['公表日']);

    # stop flag
    $today_Ymd = date("Y-m-d");

    # input zero if empty in arr
    for($i=0;true;$i++)
    {
        $Ymd = date("Y-m-d", mktime(0, 0, 0, 2, 18+$i, 2020));

        # inputo 0, if emputy
        if($patient_count_key_date_arr[$Ymd]=='') $patient_count_key_date_arr[$Ymd] = 0;

        # use 7week ave
        $ymd_key_arr[] = $Ymd;

        if($today_Ymd==$Ymd) break;
    }
    ksort($patient_count_key_date_arr);

    return array($patient_count_key_date_arr, $ymd_key_arr);
}




#
# update_ku_jsons
#

function update_ku_jsons()
{
    #
    # jsonとwebの日付比較
    #

    $Ku = fetch_ku();

    $DataJson = jsonUrl2array(KU_PER_100K_JSON);

    if( $Ku['ymd'] == $DataJson['date'] )
    {
        echo "same: ${Ku['ymd']} ".__FUNCTION__."\n";
        return;
    }

    # 区別 10万人あたりの陽性患者 発生数
    update_ku_100k_json();

    # 区別 10万人あたりの陽性者人数マップ
    update_ku_map_json();

    # 区別 陽性者数の推移
    update_ku_bar_json();

    # 区別 陽性患者 発生数　積み上げグラフ  
    update_ku_stack_json();


    update_lastupdate();

    make_tweet_txt_by_ku();

    exit;
}



function make_tweet_txt_by_ku()
{
    $DataJson = jsonUrl2array(KU_STACK_JSON);


    # 日付を作る
    $end_key   = count(($DataJson['labels']))-1;

    $start_ymd = $DataJson['labels'][$end_key-1];
    $end_ymd   = $DataJson['labels'][$end_key];

    $start_md  = date("n/j", strtotime($start_ymd."+1 day")); 
    $end_md    = date("n/j", strtotime($end_ymd)); 


    # 区別の増加人数を作る
    foreach ($DataJson['datasets'] as $KuAllDays)
    {
        $Name       = $KuAllDays['label']; # ex: 戸塚区
        $Ku[$Name]  = $KuAllDays['data'][$end_key] - $KuAllDays['data'][$end_key-1]; # ex: 120-100
    }


    # 名前を省略
    unset($Ku['市外']); # tweet文字数もあり削除
    $Ku = array_flip($Ku);
    $Ku = str_replace('区', '', $Ku);

    foreach ($Ku as &$Name) {

        if( strpos($Name, '港') !== false) # みつかったら。港南区と港北区は区別
            continue;

        $Name = mb_substr($Name, 0, 1);

    }
    $Ku = array_flip($Ku);
    arsort($Ku);


    # 出力文字列作成
    foreach ($Ku as $name => $num)
        $ku_txt .= "{$name}:{$num}\n";


    # tweet
    $tweet_txt = "{$start_md}〜{$end_md}間の区別増加数更新
{$ku_txt}https://covid19.yokohama";

    file_put_contents(UPDATE_AWARE_FILE, $tweet_txt);
}



#
# update_ku_bar_json
#

function update_ku_bar_json()
{

    $Ku = fetch_ku();

    $DataJson = jsonUrl2array(KU_BAR_JSON);

    arsort($Ku['Ku']);

    # make Json array
    $DataJson['cities']['date'] = $Ku['ymd'];

    $DataJson['cities']['data']['labels'] = [];
    $DataJson['cities']['data']['datasets'][0]['data'] = [];
    foreach( $Ku['Ku'] as $k => $v )
    {
        $DataJson['cities']['data']['labels'][] = $k;
        $DataJson['cities']['data']['datasets'][0]['data'][] = $v;
    }

    # write to json file
    arr2writeJson($DataJson, KU_BAR_JSON);

    echo __FUNCTION__."\n";


        // # add twitter comment
        // $GLOBALS['twitter_comment'] .= "・区別 陽性数({$patient_ku['md']})\n";

}


#
# 区別 10万人あたりの陽性患者 発生数
#
function update_ku_100k_json()
{
    $Ku = fetch_ku();

    $DataJson = jsonUrl2array(KU_PER_100K_JSON);

    # calc positive numvber / 100k
    $OnlyKu = $Ku['Ku'];
    unset($OnlyKu['市外']);
    $OnlyKu = ku_num_divide_100k( $OnlyKu );

    # make Json array
    $DataJson['date'] = $Ku['ymd'];

    $DataJson['data']['labels'] = [];
    $DataJson['data']['datasets'][0]['data'] = [];
    foreach( $OnlyKu as $k => $v )
    {
        $DataJson['data']['labels'][] = $k;
        $DataJson['data']['datasets'][0]['data'][] = $v;
    }

    # write to json file
    arr2writeJson($DataJson, KU_PER_100K_JSON);

    echo __FUNCTION__."\n";
}



function ku_num_divide_100k( $Ku )
{
    $KuPopuration =
        [
            '中区' => 151604,
            '保土ケ谷区' => 205957,
            '南区' => 196340,
            '戸塚区' => 281078,
            '旭区' => 245170,
            '栄区' => 119810,
            '泉区' => 152005,
            '港北区' => 355840,
            '港南区' => 213860,
            '瀬谷区' => 121744,
            '磯子区' => 166752,
            '神奈川区' => 246275,
            '緑区' => 182957,
            '西区' => 104607,
            '都筑区' => 213257,
            '金沢区' => 197892,
            '青葉区' => 311361,
            '鶴見区' => 293958
        ];

    foreach( $Ku as $k_ku => $v_positive)
        $Ku[$k_ku] = round( $v_positive/$KuPopuration[$k_ku] * 100000 );

    arsort($Ku);

    return $Ku;
}




#
# update_per_day_json
#

function update_per_day_json()
{
    # current data
    $PerDays = jsonUrl2array(PER_DAY_JSON);

    # 集計
    $CsvAggregate = csv_aggregate();

    # 入力
    $PerDays['date']                = date('Y-m-d',fetch_csv_timestamp());
    $PerDays['labels']              = $CsvAggregate['dates'];
    $PerDays['datasets'][0]['data'] = $CsvAggregate['datasets'][0];
    $PerDays['datasets'][1]['data'] = $CsvAggregate['datasets'][1];
    $PerDays['datasets'][2]['data'] = $CsvAggregate['datasets'][2];
    $PerDays['datasets'][3]['data'] = $CsvAggregate['datasets'][3];
    $PerDays['datasets'][4]['data'] = $CsvAggregate['datasets'][4];

    # write to json file
    arr2writeJson($PerDays, PER_DAY_JSON);

    echo __FUNCTION__."\n";

    return;
}




//
// csvが無いのでhtmlをスクレイピングしてjsonを更新する
//

function update_pcr_jsons()
{
    $Pcr = fetch_Pcr();

    $PcrJson = jsonUrl2array(PCR_TOTAL_JSON);

    if( $PcrJson['date'] == $Pcr['ymd'] )
    {
        echo "same: ${Pcr['ymd']} ".__FUNCTION__."\n";
        return;
    }


    #
    # total.jsonを更新：累計データからPCR更新日の陽性人数を取得する
    #
    update_pcr_total_json();


    #
    # weekly.jsonを更新
    #
    update_pcr_weekly_json();



    update_lastupdate();

    make_tweet_txt_by_pcr();

    exit;


}



function make_tweet_txt_by_pcr()
{
    $DataJson = jsonUrl2array(PCR_WEEKLY_JSON);


    # 日付を作る
    $end_key   = count(($DataJson['labels']))-1;

    list($start_ymd,$end_ymd) = explode('_', $DataJson['labels'][$end_key]);

    $start_md  = date("n/j", strtotime($start_ymd)); 
    $end_md    = date("n/j", strtotime($end_ymd)); 


    # 検査数
    $posi = $DataJson['datasets'][0]['data'][$end_key]; # 陽性
    $nega = $DataJson['datasets'][1]['data'][$end_key]; # 陽性
    $total= $posi + $nega;

    $posi = number_format($posi);
    $nega = number_format($nega);
    $total = number_format($total);



    # 先週との差分
    $posi_1 = $DataJson['datasets'][0]['data'][$end_key] - $DataJson['datasets'][0]['data'][$end_key-1]; # 陽性
    $nega_1 = $DataJson['datasets'][1]['data'][$end_key] - $DataJson['datasets'][1]['data'][$end_key-1]; # 陽性
    $total_1= $posi_1 + $nega_1;

    tweet_num_format($posi_1);
    tweet_num_format($nega_1);
    tweet_num_format($total_1);

    # tweet
    $tweet_txt = "{$start_md}〜{$end_md}間のPCR検査状況を更新しました：

検査合計：{$total} (先週 {$total_1})
　陽性：{$posi} ({$posi_1})
　陰性：{$nega} ({$nega_1})
https://covid19.yokohama";

    file_put_contents(UPDATE_AWARE_FILE, $tweet_txt);

}


function tweet_num_format(&$num)
{
    if( $num >= 0)
        $prefix = '+';
    else
        $prefix = '-';

    $num = $prefix.number_format($num);
}





function update_pcr_weekly_json()
{
    $Pcr = fetch_Pcr();
    $PcrTotalJson = jsonUrl2array(PCR_TOTAL_JSON);
    $PcrWeekJson  = jsonUrl2array(PCR_WEEKLY_JSON);


    # make date labels and weekly positive numver
    foreach( $PcrTotalJson['labels'] as $k_num => $v_ym )
    {

        if( $PcrTotalJson['labels'][$k_num+1] == '' )
            break;

        # labels
        $start_ymd = date("Y-m-d", strtotime($v_ym."+1 day")); 
        $next_ymd  = $PcrTotalJson['labels'][$k_num+1];
        $Labels[]  = $start_ymd.'_'.$next_ymd;

        # positive
        $now_positive      = $PcrTotalJson['datasets'][0]['data'][$k_num];
        $next_positive     = $PcrTotalJson['datasets'][0]['data'][$k_num+1];
        $weekly_positive[] = $next_positive - $now_positive;

        # negative
        $now_negative      = $PcrTotalJson['datasets'][1]['data'][$k_num];
        $next_negative     = $PcrTotalJson['datasets'][1]['data'][$k_num+1];
        $weekly_negative[] = $next_negative - $now_negative;
    }

    $PcrWeekJson['date']                = $Pcr['ymd'];
    $PcrWeekJson['labels']              = $Labels;
    $PcrWeekJson['datasets'][0]['data'] = $weekly_positive;
    $PcrWeekJson['datasets'][1]['data'] = $weekly_negative;

    arr2writeJson($PcrWeekJson, PCR_WEEKLY_JSON);

    echo __FUNCTION__."\n";
}






function update_pcr_total_json()
{
    $Pcr = fetch_Pcr();
    $PcrJson = jsonUrl2array(PCR_TOTAL_JSON);

    $CumulativeJson     = jsonUrl2array(CUMULATIVE_TOTAL_JSON);
    $CulumitiveYmdNum   = array_flip($CumulativeJson['labels']);
    $culumitive_ymd_num = $CulumitiveYmdNum[$Pcr['ymd']];

    foreach( $CumulativeJson['datasets'] as $StatusNums)
        $pcr_ymd_positives += $StatusNums['data'][$culumitive_ymd_num];

    # make json
    $PcrJson['date']     = $Pcr['ymd'];
    $PcrJson['labels'][] = $Pcr['ymd'];
    $PcrJson['datasets'][0]['data'][] = $pcr_ymd_positives;
    $PcrJson['datasets'][1]['data'][] = $Pcr['inspect_num'] - $pcr_ymd_positives;

    arr2writeJson($PcrJson, PCR_TOTAL_JSON);

    echo __FUNCTION__."\n";
}






function fetch_Pcr()
{
    $html = fetch_yokohama_corona_html();

    # PCR検査数の更新日付を抽出. ex. $match_ym[1]=1, $match_ym[2]=10
    preg_match("|ＰＣＲ検査数（([0-9]+)月([0-9]+)日時点）|us",$html,$match_ym); 
    $ymd = md2yyyymmdd($match_ym[1],$match_ym[2]);

    # date update for json
    # 総数の数字を抽出
    preg_match("|検査実施者総数.*right\">(.*?)<|us",$html,$match_pcr_num);
    $inspect_num = $match_pcr_num[1];

    $Pcr = array( 'ymd' => $ymd, 'inspect_num' => $inspect_num);

    return $Pcr;
}




#
# 引数のmonth,dayは0ありなしどちらもOK, ex: 1,10 -> 2021-01-10
#

function md2yyyymmdd($month, $day)
{
    $now_month = date('n'); // 0なしのmonth

    # ex: 01 09 -->1 9
    $month = (int)$month;
    $day   = (int)$day;

    # 1月に去年12月のデータが更新される場合,年はyear-1となる
    if( $now_month==1 and $month==12 )
        $year = date('Y')-1;
    else
        $year = date('Y');

    // ex: 2021-01-13
    return date('Y-m-d', strtotime("${year}-${month}-${day}"));
}







#
# update_ku_map
#

function update_ku_map_json()
{
    $Ku = fetch_ku();

    $DataJson = jsonUrl2array(KU_MAP_JSON);

    # calc positive numvber / 100k
    $OnlyKu = $Ku['Ku'];
    unset($OnlyKu['市外']);
    $OnlyKuDiv100k = ku_num_divide_100k( $OnlyKu );

    # date update for data.json->patients
    $DataJson['date'] = $Ku['ymd'];
    $DataJson['data'] = $OnlyKuDiv100k;

    # write to json file
    arr2writeJson($DataJson, KU_MAP_JSON);

    # echo
    echo __FUNCTION__."\n";
}



#
# update_status_age_json
#

function update_status_age_json()
{
    $DataJson = jsonUrl2array(STATUS_AGE_JSON);

    # 集計
    $CsvAggregate = csv_aggregate();

    # 入力
    $DataJson['date']                = date('Y-m-d',fetch_csv_timestamp());
    $DataJson['datasets'][0]['data'] = $CsvAggregate['age_datasets'][0];
    $DataJson['datasets'][1]['data'] = $CsvAggregate['age_datasets'][1];
    $DataJson['datasets'][2]['data'] = $CsvAggregate['age_datasets'][2];
    $DataJson['datasets'][3]['data'] = $CsvAggregate['age_datasets'][3];
    $DataJson['datasets'][4]['data'] = $CsvAggregate['age_datasets'][4];

    # write to json file
    arr2writeJson($DataJson, STATUS_AGE_JSON);

    echo __FUNCTION__."\n";
}




//
// fetch_positive_csv
//

function fetch_positive_csv()
{
    # cache
    static $csv_data;

    if( $csv_data != '' )
        return $csv_data;

    # csv to arr
    $csv = file('https://www.city.yokohama.lg.jp/city-info/koho-kocho/koho/topics/corona-data.files/141003_yokohama_covid19_patients.csv');
    $Csv = array_map('str_getcsv', $csv);

    # array[0]の配列を添字にしてarray[1]以降を格納
    return change_array_format( $Csv );
}



#
# array[0]の配列を添字にしてarray[1]以降を格納
#

// Array
// (
//     [0] => Array
//         (
//             [0] => No
//             [1] => 全国地方自治体コード
//                  :
//         )
//     [1] => Array
//         (
//             [0] => 1
//             [1] => 141003
//                  :
//         )

// Array
// (
//     [No] => Array
//         (
//             [0] => 1
//             [1] => 2
//                  :
//         )

function change_array_format( $Csv )
{
    foreach( $Csv as $k_Cont_num => $v_Cont )
    {
        # line 0 is index
        if( $k_Cont_num == 0 ) continue;

        foreach( $v_Cont as $k_cont_num => $v_cont )
        {
            $label            = $Csv[0][$k_cont_num]; // ex: No, 全国地方自治体コード...
            $NewCsv[$label][] = $v_cont;  
        }
    }

    return $NewCsv;
}




#
#
#

function url2modified_timestamp( $url )
{
    $headers = get_headers($url);
    $date    = substr($headers[6], 15); # ex. Sat, 16 Jan 2021 10:40:32 GMT
    return     strtotime($date);
}









#
# update_ku_stack_json
#

function update_ku_stack_json()
{
    $Ku = fetch_ku();

    $DataJson = jsonUrl2array(KU_STACK_JSON);

    foreach( $DataJson['datasets'] as $k_num => $v_JsonKu )
        $DataJson['datasets'][$k_num]['data'][] = $Ku['Ku'][$v_JsonKu['label']];

    $DataJson['date']     = $Ku['ymd'];
    $DataJson['labels'][] = $Ku['ymd'];

    # write to json file
    arr2writeJson($DataJson, KU_STACK_JSON);

    echo __FUNCTION__."\n";
}




#
#  csvは無いのでスクレイピング   
#

function fetch_ku()
{
    # cache
    static $KuSet;

    if( $KuSet != '' )
        return $KuSet;


    # get html of yokohama web site
    $html = fetch_yokohama_corona_html();

    # extract date
    preg_match("|区別発生状況（患者住所地）（([0-9０-９]+)月([0-9０-９]+)日時点）|us",$html,$match_ku);
    $ymd = md2yyyymmdd( mb_convert_kana($match_ku[1], "n") , mb_convert_kana($match_ku[2], "n") ); # 全角2半角

    # extract number
    preg_match("|<span>区別発生状況（患者住所地）（(.*?)<th scope=\"row\">合計</th>|us",$html,$match_ku);
    # 全角2半角
    $match_ku[1] = mb_convert_kana($match_ku[1], "n");
    preg_match_all("|>(\d+?)人|us",$match_ku[1],$match_ku2);
    preg_match_all("|row\">(.+?)<|us",$match_ku[1],$match_ku3);
    foreach( $match_ku3[1] as $key => $val)
        $Ku[$val] = (int)$match_ku2[1][$key];

    $KuSet = array( 'ymd' => $ymd, 'Ku' => $Ku);

    return $KuSet;
}






#
#   extract_positive_array()
#

#   TODO: yyyy-mm-dd に統一する？

function extract_positive_array()
{
    # get html of yokohama web site
    $html = fetch_yokohama_corona_html();

    # last update
    preg_match("|陽性患者の状況（([0-9]+)月([0-9]+)日時点）|us",$html,$match_md);
    $TodayPositiveData['ymd'] = md2yyyymmdd($match_md[1],$match_md[2]);

    # make number
    preg_match("|<caption>陽性患者の状況</caption>(.*?)</table><br>|us",$html,$match);


    preg_match_all("|>(\d+?)人|us",$match[1],$match2);
    preg_match_all("|row\">(.+?)<|us",$match[1],$match3);

    foreach( $match3[1] as $key => $val)
        $TodayPositiveData[$val] = (int)$match2[1][$key];

    $TodayPositiveData['無症状から中等症'] = $TodayPositiveData['無症状'] + $TodayPositiveData['軽症'] + $TodayPositiveData['中等症'];

    return $TodayPositiveData;
}



#
# 横浜市のコロナサイトのhtmlを読み込む。
# 1回読んだらキャッシュしておく。
#

function fetch_yokohama_corona_html()
{
    # キャッシュしたかのフラグ用
    static $html = '';

    # まだhtmlをfetchしていなければ読み込む
    if ($html == '')
    {
        $html = file_get_contents('https://www.city.yokohama.lg.jp/city-info/koho-kocho/koho/topics/corona-data.html');
        $html = str_replace(",","",$html); # ex: 123,456 -> 123456 数字抽出のため
    }

    return $html;
}


#
# jsonUrl2array
#

function jsonUrl2array($json_url)
{
    $json  = file_get_contents($json_url);
    return json_decode($json, true);
}



#
# arr2writeJson
#

function arr2writeJson($arr,$json_url)
{
    $json = json_encode($arr, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
    file_put_contents($json_url, $json);
}

