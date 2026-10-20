<?php

namespace backend\service\statics\yl;

/** 二字复式：45组不重复数字对，前四位均属于所选数字即可命中。 */
class TwoComboYlService extends ThreeComboYlService
{
    public const TYPE = 12;
    public const ROW_VAL = 'two_combo_fs';
    public const COMBINATION_LENGTH = 2;
}
