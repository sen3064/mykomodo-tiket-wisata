<?php

use Illuminate\Support\Facades\Auth;

if(!function_exists('getAdminFee')){
    function getAdminFee($price){
        $en = intval($price);
        $tot = 0;
        if($en>=0 && $en<=25000){
            $tot = $en*(8/100);
        }
        if($en>=25001 && $en<=50000){
            $tot = $en*(7/100);
        }
        if($en>=50001 && $en<=75000){
            $tot = $en*(6/100);
        }
        if($en>=75001 && $en<=100000){
            $tot = $en*(5/100);
        }
        if($en>=100001 && $en<=200000){
            $tot = $en*(3/100);
        }
        if($en>=200001 && $en<=300000){
            $tot = $en*(2.5/100);
        }
        if($en>=300001 && $en<=500000){
            $tot = $en*(1.6/100);
        }
        if($en>=500001 && $en<=700000){
            $tot = $en*(1.5/100);
        }
        if($en>=700001){
            $tot = $en*(1.25/100);
        }
        return $tot;
    }
}