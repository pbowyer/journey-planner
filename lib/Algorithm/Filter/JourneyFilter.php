<?php
/**
 * Created by PhpStorm.
 * User: linus
 * Date: 21/06/16
 * Time: 16:35
 */

namespace JourneyPlanner\Lib\Algorithm\Filter;


interface JourneyFilter {

    public function filter(array $results);

}