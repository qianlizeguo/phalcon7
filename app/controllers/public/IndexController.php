<?php

use App\Library\Redis\BeehiveRedis;
use Redis;

class IndexController extends ControllerBase
{

    public function indexAction()
    {
        try {
            new BeehiveRedis();
        } catch (\Exception $e) {
            var_dump($e);
        }
        die;
        var_dump($this->redisUser);die;
        $info = $this->db_test->fetchOne('select 1');
        var_dump($info);die;
    }

}

