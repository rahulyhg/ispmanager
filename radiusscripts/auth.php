<?php

include "db.php";


$bw_applicable_row = getApplicableRow();

if(!count($bw_applicable_row['id'])) exit(1);

$data_limit_row = $bw_applicable_row;

if(!$bw_applicable_row['net_data_limit']) $data_limit_row = getApplicableRow(null,$with_data_limit=true);

$db->exec("UPDATE isp_user_plan_and_topup set is_effective=0 where user_id= (SELECT customer_id from isp_user where radius_username = '$username')");
$db->exec("UPDATE isp_user_plan_and_topup set is_effective=1 where id=".$data_limit_row['id']);


$if_fup='fup_';
if(($data_limit_row['download_data_consumed'] + $data_limit_row['upload_data_consumed']) < $data_limit_row['net_data_limit']){
	$if_fup='';
}else{
}

$dl_field = $if_fup.'download_limit';
$ul_field = $if_fup.'upload_limit';

// but from which row ??
// from applicable if values exists
$dl_limit = $bw_applicable_row[$dl_field];
$ul_limit = $bw_applicable_row[$ul_field];

if($dl_limit !== '') $dl_limit = $data_limit_row[$dl_field];
if($ul_limit !== '') $ul_limit = $data_limit_row[$ul_field];
// from data if not 
// if fup is null or 0 it is a reject authentication command 

$access= true;
if($dl_limit ===null && $ul_limit === null) exit(1);

if(!$access) echo "Access-Accept := Reject\n";

if($dl_limit) echo "Mikrotik-Rate-Limit := \"$ul_limit/$dl_limit\"\n";