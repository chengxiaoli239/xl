<?php

namespace backend\service\statics\yl;

/**
 * 五字复式遗漏：从 0-9 选择 5 个数字，共 C(10, 5)=252 组。
 * 开奖前四位每一位都属于所选集合即命中，数字不要求全部出现。
 */
class FiveComboYlService extends ThreeComboYlService
{
    public const TYPE = 7;
    public const ROW_VAL = 'five_combo_fs';
    public const COMBINATION_LENGTH = 5;
}
