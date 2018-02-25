<?php

class IndexController extends ControllerBase
{

    public function indexAction()
    {
        var_dump($this->redisUser);die;
        $info = $this->db_test->fetchOne('select 1');
        var_dump($info);die;
    }

}

