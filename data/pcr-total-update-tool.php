<?php


const LATEST_PCR_TOTAL  = 435315;
const LATEST_PCR_YMD    = '2021-05-09';
const LATEST_POSI_SUM   = 24250;

const PCR_TOTAL_RATIO   = 'pcr-total_ratio.txt';
const PCR_TOTAL_JSON    = 'pcr-total.json';
const PCR_WEEKLY_JSON   = 'pcr-weekly.json';
const PER_DAY_JSON      = 'total-per-day.json';


$pcr_array_by_ratio = make_pcr_array_by_ratio();
update_pcr_weekly_json_by_tool( $pcr_array_by_ratio );

$pcr_array_total    = make_pcr_total($pcr_array_by_ratio);
update_pcr_total_json_by_tool( $pcr_array_total );


exit;


function make_pcr_total($pcr_array_by_ratio)
{
    $pcr_array_by_ratio[] =
        [
            LATEST_PCR_YMD,
            LATEST_POSI_SUM,
            LATEST_PCR_TOTAL - LATEST_POSI_SUM
        ];

    $end_key = count($pcr_array_by_ratio)-2;

    // [51] => Array
    //     (
    //         [0] => 2021-04-19_2021-04-25
    //         [1] => 638
    //         [2] => 12037
    //     )
    //
    // [52] => Array
    //     (
    //         [0] => 2021-04-25
    //         [1] => 22929
    //         [2] => 395092
    //     )


    for( $i=$end_key; $i>=0; $i--)
    {
        $start_end_ymd  = $pcr_array_by_ratio[$i][0];
        $positive       = $pcr_array_by_ratio[$i][1];
        $negative       = $pcr_array_by_ratio[$i][2];

        $start_ymd = substr( $start_end_ymd, 0, 10);
        $current_ymd = date('Y-m-d', strtotime($start_ymd."-1 day"));

        $pcr_array_by_ratio[$i] =
            [
                $current_ymd,
                $pcr_array_by_ratio[$i+1][1] - $positive,
                $pcr_array_by_ratio[$i+1][2] - $negative
            ];


    }

    return $pcr_array_by_ratio;
}


function update_pcr_total_json_by_tool( $pcr_array )
{
    // {
    //     "date": "2021-04-25",
    //     "labels": [
    //         "2020-04-26",
    //         "2021-04-25"
    //     ],
    //     "datasets": [
    //         {
    //             "label": "陽性",
    //             "data": [
    //                 327,
    //                 22929
    //             ]
    //         },
    //         {
    //             "label": "陰性",
    //             "data": [
    //                 2454,
    //                 385092
    //             ]
    //         }
    //     ]
    // }

    $pcr_array_for_json['date'] = 'place holder';

    $pcr_array_for_json['datasets'][0]['label'] = '陽性';
    $pcr_array_for_json['datasets'][1]['label'] = '陰性';


    $end_key = count($pcr_array)-1;

    for( $i=0; $i<=$end_key; $i++)
    {
        $ymd        = $pcr_array[$i][0];
        $postive    = $pcr_array[$i][1];
        $negative   = $pcr_array[$i][2];

        $pcr_array_for_json['labels'][]              = $ymd;
        $pcr_array_for_json['datasets'][0]['data'][] = $postive;
        $pcr_array_for_json['datasets'][1]['data'][] = $negative;
    }

    $pcr_array_for_json['date'] = $pcr_array[$end_key][0];

    arr2writeJson( $pcr_array_for_json, PCR_TOTAL_JSON );
}


function update_pcr_weekly_json_by_tool( $pcr_array )
{

    // {
    //     "date": "2021-04-25",
    //     "labels": [
    //         "2020-04-27_2020-05-03",
    //                 :
    //         "2021-04-19_2021-04-25"
    //     ],
    //     "datasets": [
    //         {
    //             "label": "陽性",
    //             "data": [
    //                 74,
    //                  :
    //                 638
    //             ]
    //         },
    //         {
    //             "label": "陰性",
    //             "data": [
    //                 518,
    //                  :
    //                 132
    //             ]
    //         }
    //     ]
    // }

    $pcr_array_for_json['date'] = 'place holder';

    $pcr_array_for_json['datasets'][0]['label'] = '陽性';
    $pcr_array_for_json['datasets'][1]['label'] = '陰性';

    foreach( $pcr_array as $item_array )
    {
        $day_start_end = $item_array[0];
        $postive       = $item_array[1];
        $negative      = $item_array[2];

        $pcr_array_for_json['labels'][]              = $day_start_end;
        $pcr_array_for_json['datasets'][0]['data'][] = $postive;
        $pcr_array_for_json['datasets'][1]['data'][] = $negative;
    }

    $pcr_array_for_json['date'] = substr( $day_start_end, 11, 10);

    arr2writeJson( $pcr_array_for_json, PCR_WEEKLY_JSON );

}



//
//
//
function make_pcr_array_by_ratio()
{
    // [51] => 2021-04-19_2021-04-25,5.3
    $pcr_week_ratio_array = file( PCR_TOTAL_RATIO );

    foreach( $pcr_week_ratio_array as $pcr_week_ratio )
    {
        // 2021-04-19_2021-04-25 5.3
        list( $day_start_end, $ratio  ) = explode( ',', $pcr_week_ratio);
        list( $start_day,     $end_day) = explode( '_', $day_start_end);

        $positive_period_total = sum_positive_number_period( $start_day, $end_day );
        $negative_period_total = $positive_period_total * (100/$ratio);

        $negative_positive_array[] =
            [
                $day_start_end,
                $positive_period_total,
                (int)$negative_period_total
            ];
    }

    return $negative_positive_array;
}




//
//
//
function sum_positive_number_period( $start_ymd, $end_ymd)
{
    for( $i=0; $i<1000; $i++ )
    {
        $current_ymd = date('Y-m-d', strtotime($start_ymd."+{$i} day"));

        $sum += sum_positive_number_a_date( $current_ymd );

        if( $current_ymd == $end_ymd )
            break;
    }

    return $sum;
}



//
// 一日分だけ. $ymd = 2021-05-07
//
function sum_positive_number_a_date( $ymd )
{
    // 1回だけ読み込む
    static $positive_per_day_array;

    if( !isset($positive_per_day_array) )
        $positive_per_day_array = jsonUrl2array( PER_DAY_JSON );

    //      :
    // [2021-05-07] => 444
    // [2021-05-08] => 445
    //      :
    $ymd_key_num_arrary = array_flip($positive_per_day_array['labels']);
    $key = $ymd_key_num_arrary[$ymd];

    // echo $positive_per_day_array['datasets'][0]['data'][71];
    foreach( $positive_per_day_array['datasets'] as $positive_array )
        $sum += $positive_array['data'][$key];

    return $sum;
}



// $PcrTotalJson = jsonUrl2array(PCR_TOTAL_JSON);
// $PcrWeekJson  = jsonUrl2array(PCR_WEEKLY_JSON);
// arr2writeJson($PcrWeekJson, PCR_WEEKLY_JSON);



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
