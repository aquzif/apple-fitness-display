<?php

namespace App\Utils;

use App\Models\Workout;

class WorkoutUtils
{

    public static function getBurntCalories($workout) {

        $caloriesBurnt = 0;

        foreach ($workout['statistics'] as $s){
            if($s['type'] === 'HKQuantityTypeIdentifierActiveEnergyBurned'){
                $caloriesBurnt += $s['sum'];
            }
        }

        return $caloriesBurnt;

    }

    public static function getAllCalories($workout) {
        $caloriesAll = 0;

        foreach ($workout['statistics'] as $s){
            if($s['type'] === 'HKQuantityTypeIdentifierActiveEnergyBurned'
                || $s['type'] === 'HKQuantityTypeIdentifierBasalEnergyBurned'
            ){
                $caloriesAll += $s['sum'];
            }
        }

        return $caloriesAll;

    }

}
