<?php

return array(

    'hash' => false,

    'weights' => array(
        'alidayu' => 1,
        'tencentyun' => 2,
    ),

    'alias' => array(
        'alidayu' => 'Godruoyi\PrettySms\Gateways\Alidayu',
        'tencentyun' => 'Godruoyi\PrettySms\Gateways\Tencentyun',
    ),

    'active' => array(
        'alidayu',
        'tencentyun'
    ),

    'configs' => array(

        'alidayu' => array(
            'appKey' => '',
            'secretKey' => '',
            'sign_name' => '大渝网',
        ),

        'tencent' => array(
            //业务大类id
            'sAppClass'   => 'TENCENT',
            //业务子类id
            'sAppID'      => 'NORMAL',
            //业务代码
            'sAppSubID'   => '-DYWYZDX',
            //双方约定的密钥秘钥
            'key'         => '',
        ),

        'tencentyun' => array(
            'appId' => '',
            'sign_name' => '大渝网',
            'appKey' => '',
        )
    ),

    /**
     * -----------------------------------------------------------------
     * 全局中间件，所有代理商都会通过这些中间件检测
     * -----------------------------------------------------------------
     */

    'middleware' => array(
    ),

    /**
     * -----------------------------------------------------------------
     * 指定代理中间件，只检测指定的代理
     * -----------------------------------------------------------------
     */

    'middlewareAlias' => array(
        'alidayu' => array(
            'Godruoyi\PrettySms\Middleware\AlidayuMiddleware',
        ),
        // 'tencentyun' => 'Godruoyi\PrettySms\Middleware\TencentyunMiddleware',
    ),
);