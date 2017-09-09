<?php
/** @var modX $modx */
/** @var array $sources */

$settings = array();

$tmp = array(
    'path' => array(
        'xtype' => 'textfield',
        'value' => '{core_path}middlewares/',
        'area' => 'middlewares_main',
    ),
    'global_middlewares' => array(
        'xtype' => 'textfield',
        'value' => '',
        'area' => 'middlewares_main',
    ),

);

foreach ($tmp as $k => $v) {
    /** @var modSystemSetting $setting */
    $setting = $modx->newObject('modSystemSetting');
    $setting->fromArray(array_merge(
        array(
            'key' => 'middlewares_' . $k,
            'namespace' => PKG_NAME_LOWER,
        ), $v
    ), '', true, true);

    $settings[] = $setting;
}
unset($tmp);

return $settings;
