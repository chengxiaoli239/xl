<?php
namespace backend\service;
use backend\models\Num4Type;
use backend\models\SscKjData;
use backend\models\StaticProfits;
use backend\models\SystemConfig;
use backend\models\UserSysPlans;
use backend\service\numbers\DynamicFilterService;
use common\helpers\Code;
use common\kj\ssc\Lucky5;
use common\service\ssc\filterCode\FenLiShu;
use common\tools\KjDataGet;
use common\tools\Tool_Common;
use yii\helpers\ArrayHelper;
use  yii;
use yii\helpers\Json;
use backend\service\numbers\NumCodeService;

class NumService extends BaseService {
    const DW_POSES = [1, 2, 3, 4];
    # 小单
    const DOUBLE_TYPE_XD = [0, 1, 2, 3, 4, 5, 7, 9];
    # 小双
    const DOUBLE_TYPE_XS = [0, 1, 2, 3, 4, 6, 8];
    # 大单
    const DOUBLE_TYPE_DD = [1, 3, 5, 6, 7, 8, 9];
    # 大双
    const DOUBLE_TYPE_DS = [0, 2, 4, 5, 6, 7, 8, 9];
    # 两数位置
    const TWO_NUM_POS = [[1,2], [1,3], [1,4], [2,3], [2,4], [3,4]];

    const EXCLUDE = 1; # 除
    const OBTAIN = 2; # 取

    # 配数，除、取
    const PEI_SHU_EXCLUDE = 1; # 除
    const PEI_SHU_OBTAIN = 2; # 取
    # 对数，除、取
    const LOG_EXCLUDE = 1; # 除
    const LOG_OBTAIN = 2; # 取
    # 合分，除、取
    const HE_FEN_EXCLUDE = 1; # 除
    const HE_FEN_OBTAIN = 2; # 取
    # 筛选位置：单，除、取
    const POS_ODD_EXCLUDE = 1; # 除
    const POS_ODD_OBTAIN = 2; # 取
    # 筛选位置：双，除、取
    const POS_EVEN_EXCLUDE = 1; # 除
    const POS_EVEN_OBTAIN = 2; # 取
    # 筛选位置：大，除、取
    const POS_BIG_EXCLUDE = 1; # 除
    const POS_BIG_OBTAIN = 2; # 取
    # 筛选位置：小，除、取
    const POS_SMALL_EXCLUDE = 1; # 除
    const POS_SMALL_OBTAIN = 2; # 取

    # 0123路
    const CODES_0_LINE = ['0','2', '5', '8'];
    const CODES_1_LINE = ['1', '4', '7'];
    const CODES_2_LINE = ['2', '5', '8'];
    const CODES_3_LINE = ['3', '6', '9'];

    public static $MIN_CODES = ['0', '1', '2', '3', '4'];
    public static $MAX_CODES = ['5', '6', '7', '8', '9'];
    public static $SINGLE_CODES = ['1', '3', '5', '7', '9'];
    public static $DOUBLE_CODES = ['0', '2', '4', '6', '8'];
    public static $ALL_POSES = ['1', '2', '3', '4'];
    public static $ALL_CODES = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
    public static $playway_to_code_type = [
        1 => 2,
        2 => 3,
        3 => 4,
    ];

    # 大小类型
    public static $type_dx_datas = [
        1 => '4大',  # 625组
        2 => '3大1小', # 2500组
        3 => '2大2小', # 3750组
        4 => '1大3小', # 2500 组
        5 => '4小', # 625组

        6 => '3大',
        7 => '2大1小',
        8 => '1大2小',
        9 => '3小',

        10 => '2大',
        11 => '1大1小',
        12 => '2小',
    ];

    # 三个位置一直过滤期数
    const BEFORE_3X_QS = 100;

    public static $pos_to_desc = [1=>'千', 2=>'百', 3=>'十', 4=>'个'];
    public static $pos_to_desc1 = [1=>'千', 2=>'百', 3=>'十', 4=>'个', 5=>'五'];
    public static $filter_dynamic_types = [
        1=>'1小1大，剔除前期号码至少2个上奖',
        #2=>'1小1大，剔除前期号码至少3个上奖',
        3=>'头尾去除期号最后两位相加',
        4=>'头去除期号最后两位相加',
        5=>'尾去除期号最后两位相加',
        6=>'头尾相加不等于期号后两位相加',
        7=>'过滤前200期开过号码的全转(四定)',
        #8=>'千十相加不等于期号后两位相加(四定)',
        #9=>'随机9000组(四定)',
        #10=>'过滤最近2880组(四定)',
        #11=>'过滤最近200期开过2次以上号码的全转(四定)',
        #12=>'过滤后4最近2880组(四定)',
        #13=>'过滤最近1w期重复2次以上直码(四定)',
        14=>'过滤1235最近2880组(四定)',
        15=>'过滤1245最近2880组(四定)',
        16=>'过滤1345最近2880组(四定)',
        17=>'取前四最近8000组(四定)',
        18=>'取后四最近8000组(四定)',
        19=>'过滤前期两个位置一样的所有号码',
        20=>'过滤最近3200组(四定)',
        21=>'过滤后4最近3200组(四定)',
        22=>'过滤1235最近3200组(四定)',
        23=>'过滤1245最近3200组(四定)',
        24=>'过滤1345最近3200组(四定)',
        25=>'过滤前80期开过号码的全转(四定)',
        27=>'过滤前四1152组号码(四定)',
        28=>'过滤1234期号尾号一致3000组历史直码',
        29=>'剔除前期号码至少1个上奖',
        30=>'剔除前期号码至少2个上奖',
        #31=>'剔除前100期123位一致的直码 ',
        #32=>'剔除前100期124位一致的直码 ',
        #33=>'剔除前100期134位一致的直码 ',
        #34=>'剔除前100期234位一致的直码 ',
        35=>'剔除历史期号一致的号码全倒 ',
        #36=>'过滤1235期号尾号一致1000组历史直码 ',
        #37=>'过滤1245期号尾号一致1000组历史直码 ',
        #38=>'过滤1345期号尾号一致1000组历史直码 ',
        #39=>'过滤2345期号尾号一致1000组历史直码 ',
        40=>'过滤1234大小类型一致近1500组直码 ',
        41=>'过滤1234前期大小或单双类型分别都不一致号码 ',
        42=>'过滤1234最近两大两小1000组直码 ',
        43=>'过滤1234最近两单两双1000组直码 ',
        44=>'过滤1234前期大小类型都不一致号码 ',
        45=>'过滤1234前期单双类型都不一致号码 ',
        #46=>'剔除历史1234期号一致的号码 ',
        #47=>'剔除历史1235期号一致的号码 ',
        #48=>'剔除历史1245期号一致的号码 ',
        #49=>'剔除历史1345期号一致的号码 ',
        #50=>'剔除历史2345期号一致的号码 ',
        51=>'过滤1234最近两大两小200组直码 ',
        52=>'过滤1234最近两单两双200组直码 ',
        53=>'过滤1234最近两大两小500组直码 ',
        54=>'过滤1234最近两单两双500组直码 ',
        55=>'过滤1234最近500组直码 ',
        56=>'过滤1234最近300组直码 ',
        57=>'过滤前50期开过号码的全转(四定)',
        58=>'过滤2345位500组直码',
        59=>'过滤2345位300组直码',
        60=>'取1234位置0123同路[或]',
        61=>'杀上期千位码 ',
        62=>'杀上期百位码 ',
        63=>'杀上期十位码 ',
        64=>'杀上期个位码 ',
        65=>'杀期号尾数码 ',
        66=>'取千位最近9个码 ',
        67=>'取百位最近9个码 ',
        68=>'取十位最近9个码 ',
        69=>'取个位最近9个码 ',
        70=>'取1234位置0123路同路最多两位',
        71=>'过滤1234位置同单双类型+双重', # |对数
        72=>'过滤1234位置同大小类型+双重', # |对数
        73=>'过滤2345位80期开过号码全转(四定)',

        74=>'杀上期同位置号码+三兄(四定)',
        #75=>'杀同位置冷码+三兄(四定)',

        76=>'杀同位置大小加配上期号码(四定)',
        77=>'杀同位置单双加配上期号码(四定)',

        78=>'过滤昨日同期[千百-十个]跨度(四定)',
        79=>'过滤昨日同期[千-百十个]跨度(四定)',
        80=>'过滤昨日同期[千百十-个]跨度(四定)',
        81=>'过滤前期[千-百]位置跨度(四定)',
        82=>'过滤前期[百-十]位置跨度(四定)',
        83=>'过滤前期[十-个]位置跨度(四定)',

        84=>'过滤近一期同尾号[千-百]位置跨度(四定)',
        85=>'过滤近一期同尾号[百-十]位置跨度(四定)',
        86=>'过滤近一期同尾号[十-个]位置跨度(四定)',

        87=>'过滤昨天同期号[千]位双重',

        88=>'过滤[千]位一致近5天号码(四定)',
        94=>'过滤[百]位一致近5天号码(四定)',
        95=>'过滤[十]位一致近5天号码(四定)',
        96=>'过滤[个]位一致近5天号码(四定)',

        89=>'过滤[千]位号码及合分(四定)',
        90=>'过滤[百]位号码及合分(四定)',
        91=>'过滤[十]位号码及合分(四定)',
        92=>'过滤[个]位号码及合分(四定)',

        93=>'头尾剔除上期和值后一位号码(四定)',
        97=>'过滤上期每两个号码及对数(四定)',
        98=>'过滤昨日同期每两个号码及对数(四定)',
        102=>'过滤345位三分离号码(四定)',
        103=>'过滤123位三分离号码(四定)',
        104=>'过滤234位三分离号码(四定)',
        105=>'过滤125位三分离号码(四定)',
        106=>'过滤145位三分离号码(四定)',

        107=>'过滤124位三分离号码(四定)',
        108=>'过滤134位三分离号码(四定)',
        109=>'过滤135位三分离号码(四定)',
        110=>'过滤235位三分离号码(四定)',
        111=>'过滤245位三分离号码(四定)',

        112=>'杀同位置大小加配上期两位同位置号码(四定)',
        113=>'杀同位置单双加配上期两位同位置号码(四定)',

        114=>'过滤千位最近1个冷码+三兄弟 ',
        115=>'过滤百位最近1个冷码+三兄弟 ',
        116=>'过滤十位最近1个冷码+三兄弟 ',
        117=>'过滤个位最近1个冷码+三兄弟 ',

        118=>'过滤千位最近1个冷码+两单两双+对数 ',
        119=>'过滤百位最近1个冷码+两单两双+对数 ',
        120=>'过滤十位最近1个冷码+两单两双+对数 ',
        121=>'过滤个位最近1个冷码+两单两双+对数 ',

        122=>'过滤千位最近1个冷码+两大两小+双重 ',
        123=>'过滤百位最近1个冷码+两大两小+双重 ',
        124=>'过滤十位最近1个冷码+两大两小+双重 ',
        125=>'过滤个位最近1个冷码+两大两小+双重 ',

        126=>'过滤千位最近1个冷码+合分 ',
        127=>'过滤百位最近1个冷码+合分 ',
        128=>'过滤十位最近1个冷码+合分 ',
        129=>'过滤个位最近1个冷码+合分 ',

        130=>'过滤千位+其它位置合分千 ',
        131=>'过滤百位+其它位置合分百 ',
        132=>'过滤十位+其它位置合分十 ',
        133=>'过滤个位+其它位置合分个 ',

        134=>'过滤千位且单双 ',
        135=>'过滤百位且单双 ',
        136=>'过滤十位且单双 ',
        137=>'过滤个位且单双 ',
        138=>'过滤千位且大小 ',
        139=>'过滤百位且大小 ',
        140=>'过滤十位且大小 ',
        141=>'过滤个位且大小 ',

        142=>'过滤上期每两个号码及双重(四定)', # 同97、98
        143=>'过滤昨日同期每两个号码及双重(四定)', # 同97、98
        144=>'过滤前天同期每两个号码及双重(四定)',
        145=>'过滤大前天同期每两个号码及双重(四定)',

        146=>'过滤上期同合分及双重(四定)', # 同97、98
        147=>'胆码2跨1-2个(四定)', # 0的2跨是2、1的2跨就只是3、8的2跨是6、9的2跨是7
        148=>'随机对数1对、前三合分9个(四定)', # 0的2跨是2、1的2跨就只是3、8的2跨是6、9的2跨是7
        149=>'随机除对数1对(四定)', #
        230=>'随机除两数同上1', #
        238=>'随机除两数同上2', #
        150=>'随机取前三合分9个(四定)', # 0的2跨是2、1的2跨就只是3、8的2跨是6、9的2跨是7
        151=>'配数单双互排除及该位置号码(四定)',
        152=>'0123路配数-除-千位X',
        153=>'0123路配数-除-百位X',
        154=>'0123路配数-除-十位X',
        155=>'0123路配数-除-个位X',
        #156=>'两数合分-除-上期千百位置',
        #157=>'两数合分-除-上期千十位置',
        #158=>'两数合分-除-上期千个位置',
        #159=>'两数合分-除-上期百十位置',
        #160=>'两数合分-除-上期百个位置',
        #161=>'两数合分-除-上期十个位置',
        162=>'过滤12位置各1个冷码 ',
        163=>'过滤13位置各1个冷码 ',
        164=>'过滤14位置各1个冷码 ',
        165=>'过滤15位置各1个冷码 ',
        166=>'过滤23位置各1个冷码 ',
        167=>'过滤24位置各1个冷码 ',
        168=>'过滤25位置各1个冷码 ',
        169=>'过滤34位置各1个冷码 ',
        170=>'过滤35位置各1个冷码 ',
        171=>'过滤45位置各1个冷码 ',

        172=>'过滤12位置各1个最多遗漏 ',
        173=>'过滤13位置各1个最多遗漏 ',
        174=>'过滤14位置各1个最多遗漏 ',
        175=>'过滤15位置各1个最多遗漏 ',
        176=>'过滤23位置各1个最多遗漏 ',
        177=>'过滤24位置各1个最多遗漏 ',
        178=>'过滤25位置各1个最多遗漏 ',
        179=>'过滤34位置各1个最多遗漏 ',
        180=>'过滤35位置各1个最多遗漏 ',
        181=>'过滤45位置各1个最多遗漏 ',

        182=>'过滤1234最近三大一小200组直码 ',
        183=>'过滤1234最近一大三小200组直码 ',
        184=>'过滤1234最近一单三双200组直码 ',
        185=>'过滤1234最近三单一双200组直码 ',

        186=>'过滤1位最近1个冷码 ',
        187=>'过滤2位最近1个冷码 ',
        188=>'过滤3位最近1个冷码 ',
        189=>'过滤4位最近1个冷码 ',

        190=>'过滤123位合分',
        191=>'过滤124位合分',
        192=>'过滤134位合分',
        193=>'过滤234位合分',

        194=>'取上期4个码(不重复)必须上1个',
        195=>'取上上期4个码(不重复)必须上1个',
        196=>'过滤前400期开过号码的全转',
        197=>'过滤前500期开过号码的全转',
        #198=>'取前四最近9990组(四定)',
        #199=>'取后四最近9990组(四定)',
        #200=>'取前四最近9999组(四定)',
        #201=>'取后四最近9999组(四定)',

        202=>'过滤12位合分',
        203=>'过滤13位合分',
        204=>'过滤14位合分',
        205=>'过滤23位合分',
        206=>'过滤24位合分',
        207=>'过滤34位合分',

        235=>'过滤近1天直码 ',
        208=>'过滤近3天直码 ',
        209=>'过滤近5天直码 ',
        210=>'过滤近7天直码 ',
        211=>'1(1234)位近4个码最多上2个',
        212=>'2(1234)位近4个码最多上2个',
        213=>'3(1234)位近4个码最多上2个',
        214=>'4(1234)位近4个码最多上2个',

        215=>'定1234位上期147上2个则下期至少上1个',
        216=>'定123位上期147上2个则下期123位至少上1个',
        217=>'定124位上期147上2个则下期124位至少上1个',
        218=>'定134位上期147上2个则下期134位至少上1个',
        219=>'定234位上期147上2个则下期234位至少上1个',

        220=>'定1234位上期258上2个则下期至少上1个',
        221=>'定123位上期258上2个则下期123位至少上1个',
        222=>'定124位上期258上2个则下期124位至少上1个',
        223=>'定134位上期258上2个则下期134位至少上1个',
        224=>'定234位上期258上2个则下期234位至少上1个',

        225=>'定1234位上期369上2个则下期至少上1个',
        226=>'定123位上期369上2个则下期123位至少上1个',
        227=>'定124位上期369上2个则下期124位至少上1个',
        228=>'定134位上期369上2个则下期134位至少上1个',
        229=>'定234位上期369上2个则下期234位至少上1个',

        231=>'1(1234)位近4个码最多上1个',
        232=>'2(1234)位近4个码最多上1个',
        233=>'3(1234)位近4个码最多上1个',
        234=>'4(1234)位近4个码最多上1个',

        236=>'过滤2345位30期开过号码全转(四定)',

        //26=>'过滤往前第1期同位置号码(四定6561组)',
        237=>'过滤往前第1期同位置号码(四定6561组)',
    ];

    public static array $playwayDynamicTypes = [
        # 二定
        1 => [
            235, // 过滤近1天直码
            208, // 过滤近3天直码
            209, // 过滤近5天直码
            210, // 过滤近7天直码
            211, // '1(1234)位近4个码最多上2个',
            212, // '2(1234)位近4个码最多上2个',
            213, // '3(1234)位近4个码最多上2个',
            214, // '4(1234)位近4个码最多上2个',
            231, // '1(1234)位近4个码最多上1个',
            232, // '2(1234)位近4个码最多上1个',
            233, // '3(1234)位近4个码最多上1个',
            234, // '4(1234)位近4个码最多上1个',
        ],
        # 三定
        2 => [
            235, // 过滤近1天直码
            208, // 过滤近3天直码
            209, // 过滤近5天直码
            210, // 过滤近7天直码
            211, // '1(1234)位近4个码最多上2个',
            212, // '2(1234)位近4个码最多上2个',
            213, // '3(1234)位近4个码最多上2个',
            214, // '4(1234)位近4个码最多上2个',
            231, // '1(1234)位近4个码最多上1个',
            232, // '2(1234)位近4个码最多上1个',
            233, // '3(1234)位近4个码最多上1个',
            234, // '4(1234)位近4个码最多上1个',

            216, // '定123位上期147上2个则下期123位至少上1个',
            217, // '定124位上期147上2个则下期124位至少上1个',
            218, // '定134位上期147上2个则下期134位至少上1个',
            219, // '定234位上期147上2个则下期234位至少上1个',

            221, // '定123位上期258上2个则下期123位至少上1个',
            222, // '定124位上期258上2个则下期124位至少上1个',
            223, // '定134位上期258上2个则下期134位至少上1个',
            224, // '定234位上期258上2个则下期234位至少上1个',

            226, // '定123位上期369上2个则下期123位至少上1个',
            227, // '定124位上期369上2个则下期124位至少上1个',
            228, // '定134位上期369上2个则下期134位至少上1个',
            229, // '定234位上期369上2个则下期234位至少上1个',
            230, // 随机除两数同上1
            238, // 随机除两数同上2
        ],
        # 四定，与二三定不一样，是指定 ------ 剔除
        3 => [
            216, // '定123位上期147上2个则下期123位至少上1个',
            217, // '定124位上期147上2个则下期124位至少上1个',
            218, // '定134位上期147上2个则下期134位至少上1个',
            219, // '定234位上期147上2个则下期234位至少上1个',

            221, // '定123位上期258上2个则下期123位至少上1个',
            222, // '定124位上期258上2个则下期124位至少上1个',
            223, // '定134位上期258上2个则下期134位至少上1个',
            224, // '定234位上期258上2个则下期234位至少上1个',

            226, // '定123位上期369上2个则下期123位至少上1个',
            227, // '定124位上期369上2个则下期124位至少上1个',
            228, // '定134位上期369上2个则下期134位至少上1个',
            229, // '定234位上期369上2个则下期234位至少上1个',
        ],
    ];

    const TYPE_POSITIONS = [
        162=>[1,2],
        163=>[1,3],
        164=>[1,4],
        165=>[1,5],
        166=>[2,3],
        167=>[2,4],
        168=>[2,5],
        169=>[3,4],
        170=>[3,5],
        171=>[4,5],
    ];
    # 冷热码类型对应位置
    const TYPE_LR_POSITIONS = [
        172=>[1,2],
        173=>[1,3],
        174=>[1,4],
        175=>[1,5],
        176=>[2,3],
        177=>[2,4],
        178=>[2,5],
        179=>[3,4],
        180=>[3,5],
        181=>[4,5],
    ];
    # 合分类型对应位置
    const TYPE_HF_POSITIONS = [
        190=>[1,2,3],
        191=>[1,2,4],
        192=>[1,3,4],
        193=>[2,3,4],

        202=>[1,2],
        203=>[1,3],
        204=>[1,4],
        205=>[2,3],
        206=>[2,4],
        207=>[3,4],
    ];

    /**
     * 过滤playway对应的动态类型
     * @param $playWay
     * @return array|string[]
     */
    public static function getDynamicType($playWay): array
    {
        $types = NumService::$filter_dynamic_types;
        $playWayTypes = [];
        if(in_array($playWay, [1, 2])){
            // 二、三定指定类型
            foreach (self::$playwayDynamicTypes[$playWay] as $playWayDynamicType){
                if(array_key_exists($playWayDynamicType, $types)){
                    $playWayTypes[$playWayDynamicType] = $types[$playWayDynamicType];
                }
            }
            return $playWayTypes;
        }else{
            // 四定指定剔除
            foreach (self::$playwayDynamicTypes[$playWay] as $playWayDynamicType){
                if(isset($types[$playWayDynamicType])){
                    unset($types[$playWayDynamicType]);
                }
            }
        }

        return $types;
    }

    /**
     * @desc 获取系统模拟投注四定和值
     * @param $counts - 获取几组和值
     * @param string $qihao
     * @param int $type  1倒序剔除2随机剔除
     * @return array
     */
    public static function getSystemTzHz($counts = 5, string $qihao = '190329036', int $type = 1): array
    {
        $HeZhis = SystemConfig::findOne(['key'=>'system_tz_hz'])->value;
        $HeZhiArr = explode(',', $HeZhis);

        $orderByArr = [
            1 => ['id'=>SORT_DESC],
            2 => ['RAND()'=>SORT_DESC],
        ];
        $SscKjDatas = SscKjData::find()->select(['codes_4Nums_hz'])->where('qihao<'.$qihao)->orderBy($orderByArr[1])->limit(20)->asArray()->all();
        $hzsArr = ArrayHelper::getColumn($SscKjDatas, 'codes_4Nums_hz');
        if($type == 2){
            shuffle($hzsArr);
        }
        //p([$hzsArr]);
        foreach ($hzsArr as $k=>$hz){
            foreach ($HeZhiArr as $key=>$heZhi){
                if($hz == $heZhi){
                    unset($HeZhiArr[$key]);
                    if(count($HeZhiArr) == $counts){
                        return $HeZhiArr;
                    }
                }
            }
        }
        return $HeZhiArr;
    }

    /**
     * @desc 根据号码str和个数，返回数组
     * @param $codes_str - 号码，例如：1234
     * @param int $nums - 个数，比如：2
     * @return array - [12,13,14,23,24,34]
     */
    public static function getCodesArrByNum($codes_str, $nums=2){
        $len = strlen($codes_str);

        $codesArr = [];
        for($i=0; $i<$len; $i++){
            if($nums<=1){ # 1个号码
                $codesArr[] = $codes_str[$i];
            }elseif($nums>=2){
                for($j=1; $j<$len; $j++){
                    if($j<=$i) continue;
                    if($nums == 2){ # 两个号码
                        $codesArr[] = $codes_str[$i].$codes_str[$j];
                    }elseif($nums >= 3){
                        if($nums == 3){ # 3个号码
                            for ($k=2; $k<$len; $k++){
                                if($k<=$j) continue;
                                $codesArr[] = $codes_str[$i].$codes_str[$j].$codes_str[$k];
                            }
                        }else{
                            for ($k=2; $k<$len; $k++){
                                if($k<=$j) continue;
                                for ($l=3; $l<$len; $l++){ # 4个号码
                                    if($l<=$k) continue;
                                    $codesArr[] = $codes_str[$i].$codes_str[$j].$codes_str[$k].$codes_str[$l];
                                }
                            }
                        }
                    }
                }
            }
        }

        return $codesArr;
    }

    /**
     * @desc 排除多少期内的码，利润统计
     * @param string $qihao
     * @param int $nums
     * @param string $type
     * @return array
     */
    public static function getRemoveCodes($qihao = '', $nums = 2000, $type = 'ssc'){
        $codes = [];
        $m = \Yii::$app->cache;
        $zjKey ='ZJ_REMOVEE_0_'.$qihao;
        if($r = $m->get($zjKey)) return $r;
        if(!$qihao) return ['status'=>300, 'msg'=>'期号不能为空'];
        $nums = $nums + 230;
        switch ($type){
            case 'ssc':
                //$mkey = 'CACHE_REMOVE_CODES_'.$type;
                $mkey = self::getRemoveMkey($qihao, $nums, $type);
                $SscKjDatas = SscKjData::find()->select(['LEFT(code_str, 7) AS code_str'])->where('qihao<'.$qihao)->limit($nums)->orderBy(['id'=>SORT_DESC])->asArray()->all();
                $codes = ArrayHelper::getColumn($SscKjDatas, 'code_str');
                foreach ($codes as $key=>$code){
                    $mckey = $mkey.'_'.$code;
                    $m->set($mckey, 1, 10*60);
                }

                $all4NumCodes = Num4Type::find()->select(['code AS code_str'])->where(['code_type'=>4])->orderBy(['id'=>SORT_DESC])->asArray()->all();
                $nums4Codes = ArrayHelper::getColumn($all4NumCodes, 'code_str');
                $allCodes = [];
                foreach ($nums4Codes as $num){
                    $mckey = $mkey.'_'.$num;
                    if($m->get($mckey)) continue;
                    $allCodes[] = $num;
                }
                $kjCodes = SscKjData::find()->select(['qihao', 'LEFT(code_str, 7) AS code_str'])->where(['qihao'=>$qihao])->limit(1)->asArray()->one();
                if(in_array($kjCodes['code_str'], $allCodes)){
                    $r = 1;
                    $m->set($zjKey, $r, 10*60);
                }else{
                    $r = 2;
                }

                $m->set($zjKey, $r, 10*60);
                return $r;

                break;
            case 'pl5':
                break;
            default:;
        }


        return $codes;
    }

    /**
     * @desc 获取期号key
     * @param $qihao
     * @param $nums
     * @param $type
     * @return string
     */
    public static function getRemoveMkey($qihao, $nums, $type){
        $mkey = 'CACHE_REMOVE_CODES_5_'.$type.'_'.$qihao;

        return $mkey;
    }

    /**
     * @desc 获取两兄弟和值号码
     * @return array
     */
    public static function get2bCodeArr(){
        $m = \Yii::$app->cache;
        $mkey = 'CODES_2B_CODES_MCKEY_0';
        //if($codesArr = $m->get($mkey)) return $codesArr;
        # 和值范围 start
        //$rst = NumService::getSystemTzHz($nums, $data['qihao'], 1); # 剔除(有随机和倒序) 和值
        //$HeZhis = [8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26];
        $HeZhis = SystemConfig::findOne(['key'=>'system_tz_hz'])->value;
        $heZhiArr = explode(',', $HeZhis);


        # 是否随机过滤和值号码
        $filter_hz_code_status = SystemConfig::findOne(['key'=>'filter_hz_code_status'])->value;
        $filter_hz_nums = SystemConfig::findOne(['key'=>'filter_hz_nums'])->value;
        if($filter_hz_code_status){
            /* 随机剔除 */
            for ($i=1; $i<=$filter_hz_nums; $i++){
                $v = rand($heZhiArr[0], end($heZhiArr));
                $rndKey = array_search($v, $heZhiArr);
                unset($heZhiArr[$rndKey]);
            }
        }

        //return $heZhiArr;
        //if($data['qihao'] == '190401023') p([$nums, $data, $rst]);
        $where = ['codes_hz'=>$heZhiArr, 'type_2b'=>1, 'type_3'=>0, 'type_4b'=>0, 'code_type'=>4];
        $Num4Types = Num4Type::find()->where($where);
        $codes = $Num4Types->asArray()->all();
        $codesArr = ArrayHelper::getColumn($codes, 'code');
        # 和值范围 end
        $m->set($mkey, $codesArr, 7*24*3600);

        return $codesArr;
    }

    /**
     * @desc 取两兄弟后剔除最近出现的多少期号码
     * @param int $lottery_type
     */
    public static function filterLaterCodesAnd2bcode($lottery_type = 5, $qihao = '190516056', $nums = 1000){

        //$filter_hz_code_status = SystemConfig::findOne(['key'=>'filter_hz_code_status'])->value;
        $filterCodes = self::getRecentlyCodes($lottery_type, $qihao, $nums); # 最近 $nums 期号码

        $codesArr = self::get2bCodeArr();
        //p(count($codesArr));
        foreach ($filterCodes as $filterCode){
            $filterKey = array_search($filterCode, $codesArr);
            if($filterKey !== false){
                $tmpCode[$filterKey] = $codesArr[$filterKey];
                unset($codesArr[$filterKey]);
            }
        }
        //p($tmpCode);

        return $codesArr;
    }

    /**
     * @desc 最近出现的号码
     * @param int $lottery_type
     * @param int $nums
     * @return array
     */
    public static function getRecentlyCodes(int $lottery_type = 5, $qihao = '190516056', int $nums = 500): array
    {
        $limit = $nums + ceil($nums * 0.3);
        $SscKjDatas = SscKjData::find()->where(['AND', ['=', 'lottery_type', $lottery_type], ['<', 'qihao', $qihao]])->orderBy(['id'=>SORT_DESC])->limit($limit)->all();
        $codesArr = [];
        foreach ($SscKjDatas as $SscKjData){
            $codesArr[] = substr($SscKjData->code_str, 0,7);
            //$codesArr = array_unique($codesArr);

            //if(count($codesArr) == $nums) break;
        }

        return $codesArr;
    }


    /**
     * @desc 根据四定单双1122返回号码组合
     * @param $codesArr
     */
    public static function getCodesByDs($codesArr){

        $codesData = [];
        foreach ($codesArr as $codes){
            $code = $codes[0] % 2 == 0 ? '02468' : '13579';
            $code .= $codes[1] % 2 == 0 ? ',02468' : ',13579';
            $code .= $codes[2] % 2 == 0 ? ',02468' : ',13579';
            $code .= $codes[3] % 2 == 0 ? ',02468' : ',13579';
            $codesData[] = $code;
        }

        return $codesData;
    }

    /**
     * 指定号码数组获取跨度数组
     * @param array $codes
     * @param int $kd
     * @return array
     */
    public static function getKuduCodes(array $codes=[], int $kd=2): array
    {

        $kdCodes = [];
        foreach ($codes as $code){
            $code_d = $code - $kd;
            $code_p = $code + $kd;
            foreach ([$code_d, $code_p] as $c){
                if($c>-1 && $c<10){
                    $kdCodes[] = $c;
                }
            }
        }

        return array_values(array_unique($kdCodes));
    }

    /**
     * @desc 上奖 - 返回匹配含号码的组合 -- 已完成 2019-04-22
     * @param array $codesArr ['123', '456']
     * @param int $type 0除1取
     * @param int $code_type 1一定2二定3三定4四定5五位二定
     * @return array
     */
    public static function getCodesArise(array $codesArr = [], int $type = 1, int $code_type = 4): array
    {

        $codes4Arr = [];
        # 去除双重数字
        foreach ($codesArr as $key=>$codes){
            $len = strlen($codes);
            if($len == 1){
                $codesArrTmp = NumService::getAllCombination1($codes, $type, $code_type);
                # 一个码
            }elseif ($len == 2){
                # 两个码
                $codesArrTmp = NumService::getAllCombination2($codes, $type, $code_type);
            }elseif ($len == 3){
                # 三个码
                $codesArrTmp = NumService::getAllCombination3($codes, $type, $code_type);
            }elseif ($len == 4){
                # 四个码 - 全倒
                $codesArrTmp = NumService::getAllCombination4($codes, $type, $code_type);
            }elseif($len > 4){
                # 大于四个码
                $codesArrTmp = NumService::getAllCombination4p($codes, $type, $code_type);
            }
            $codes4Arr = array_merge($codes4Arr, $codesArrTmp);
        }
        if(in_array($code_type, [4, 5])){
            $codes4Arr = array_unique($codes4Arr);
        }

        return $codes4Arr;
    }

    /**
     * @desc 1个号码返回组号码组合 - 全倒
     * @param $codes 格式：1或者2
     * @param $type 0除1取
     * @return array ['1,2,3,4', '1,1,2,3', '1,1,1,2']
     */
    public static function getAllCombination1($codes, $type = 1, $code_type = 4){
        if(strlen($codes) != 1) return [];

        $op = ($type == 1) ? 'LIKE' : 'NOT LIKE';
        $where = ['AND', [$op, 'code', $codes], ['=', 'code_type', $code_type]];
        //p($where);
        $Num4Types = Num4Type::find()->select(['code'])->where($where)->asArray()->all();
        $codesArr = ArrayHelper::getColumn($Num4Types, 'code');

        return array_unique($codesArr);
    }

    /**
     * @desc 2个号码返回组号码组合 - 全倒
     * @param $codes - 格式：11或者12
     * @param $type 0除1取
     * @param $code_type 2二字定3三定4四定5五位二定
     * @return array ['1,2,3,4', '1,1,2,3', '1,1,1,2']
     */
    public static function getAllCombination2($codes, $type = 1, $code_type = 4){
        if(strlen($codes) != 2) return [];

        if($code_type == 2){ # 二定
            $tmpCodesArr = NumService::getCodesTwo([$codes[0], $codes[1]]); # 格式：[['1','2', 'X', 'X'], ['1', 'X', '2', 'X']] ..
            $codesArr = [];
            foreach ($tmpCodesArr as $tmpCodes){
                $codesArr[] = implode(',', $tmpCodes);
            }
        }elseif($code_type==3) { # 三定
            if ($type == 1) {
                $where = [
                    'AND',
                    ['=', 'code_type', 3],
                    [
                        'OR',
                        ['LIKE', 'code', '%' . $codes[0] . ',' . $codes[1] . '%', false],
                        ['LIKE', 'code', '%' . $codes[0] . '%' . $codes[1] . '%', false],

                        ['LIKE', 'code', '%' . $codes[1] . ',' . $codes[0] . '%', false],
                        ['LIKE', 'code', '%' . $codes[1] . '%' . $codes[0] . '%', false],
                    ]
                ];
            }
            $Num4Types = Num4Type::find()->select(['code'])->where($where)->asArray()->all();
            $codesArr = ArrayHelper::getColumn($Num4Types, 'code');

        }elseif($code_type == 5){ # 五位二定
            $codesArr = NumService::getTwo5ByTwoNums([$codes[0], $codes[1]]); # 格式：[['1','X','X','X','2'],['X','1','X','X','2'], ['X','X','1','X','2'],['X','X','X','1','2']] ..
        }elseif($code_type == 4){
            if ($type == 1) { # 取
                $where = [
                    'AND',
                    ['=', 'code_type', 4],
                    [
                        'OR',
                        ['LIKE', 'code', '%' . $codes[0] . ',' . $codes[1] . '%', false],
                        ['LIKE', 'code', '%' . $codes[0] . '%' . $codes[1] . '%', false],

                        ['LIKE', 'code', '%' . $codes[1] . ',' . $codes[0] . '%', false],
                        ['LIKE', 'code', '%' . $codes[1] . '%' . $codes[0] . '%', false],
                    ]
                ];
            } else { # 除
                if ($codes[0] == $codes[1]) { # 双重
                    $where = [
                        'AND',
                        ['NOT LIKE', 'code', $codes[0] . ',' . $codes[1] . ',%,%', false],
                        ['NOT LIKE', 'code', $codes[0] . ',%,' . $codes[1] . ',%', false],
                        ['NOT LIKE', 'code', $codes[0] . ',%,%,' . $codes[1], false],
                        ['NOT LIKE', 'code', '%,' . $codes[0] . ',' . $codes[1] . ',%', false],
                        ['NOT LIKE', 'code', '%,' . $codes[0] . ',%,' . $codes[1], false],
                        ['NOT LIKE', 'code', '%,%,' . $codes[0] . ',' . $codes[1], false],
                        ['=', 'code_type', 4],
                    ];
                } else {
                    $where = [
                        'AND',
                        ['NOT LIKE', 'code', '%' . $codes[0] . '%', false],
                        ['NOT LIKE', 'code', '%' . $codes[1] . '%', false],
                        ['=', 'code_type', 4],
                    ];
                }
            }
            $Num4Types = Num4Type::find()->select(['code'])->where($where)->asArray()->all();
            $codesArr = ArrayHelper::getColumn($Num4Types, 'code');
        }

        if($code_type == 4){
            $datas = array_unique($codesArr);
        }else{
            $datas = $codesArr;
        }
        return $datas;
    }

    /**
     * @desc 3个号码返回组号码组合 - 全倒
     * @param $codes - 格式：111或者112或者123
     * @param $type 0除1取
     * @return array ['1,2,3,4', '1,1,2,3', '1,1,1,2']
     */
    public static function getAllCombination3($codes, $type = 1, $code_type = 4){
        if(strlen($codes) != 3) return [];

        $codesArr = [];
        if($code_type == 5){ # 五位二定
            $twoNums = NumService::getTwoNums($codes);
            foreach ($twoNums as $twoNum){
                $codesArr = NumService::getTwo5ByTwoNums([$twoNum[0], $twoNum[1]]);
            }
        }elseif($code_type == 2){ # 二定 - 在处理 12,13,23
            $codesArr = NumService::get2DingWeiByCodes($codes);
        }elseif($code_type == 3){ # 三定
            if($type == 1) {
                $where = [
                    'AND',
                    ['=', 'code_type', 3],
                    ['LIKE', 'code', '%'.$codes[0].'%', false],
                    ['LIKE', 'code', '%'.$codes[1].'%', false],
                    ['LIKE', 'code', '%'.$codes[2].'%', false],
                ];
            }
            $Num4Types = Num4Type::find()->select(['code'])->where($where)->asArray()->all();
            $codesArr = ArrayHelper::getColumn($Num4Types, 'code');
        }elseif($code_type == 4){
            $op = $type == 1 ? 'OR' : 'AND';
            $like_op = ($type == 1) ? 'LIKE' : 'NOT LIKE';
            if($type == 1){
                $where = [
                    'AND',
                    ['=', 'code_type', 4],
                    [
                        $op,
                        [$like_op, 'code', '%'.$codes[0].','.$codes[1].','.$codes[2].'%', false],
                        [$like_op, 'code', $codes[0].'%'.$codes[1].','.$codes[2], false],
                        [$like_op, 'code', $codes[0].','.$codes[1].'%'.$codes[2], false],

                        [$like_op, 'code', '%'.$codes[0].','.$codes[2].','.$codes[1].'%', false],
                        [$like_op, 'code', $codes[0].'%'.$codes[2].','.$codes[1], false],
                        [$like_op, 'code', $codes[0].','.$codes[2].'%'.$codes[1], false],

                        [$like_op, 'code', '%'.$codes[1].','.$codes[0].','.$codes[2].'%', false],
                        [$like_op, 'code', $codes[1].'%'.$codes[0].','.$codes[2], false],
                        [$like_op, 'code', $codes[1].','.$codes[0].'%'.$codes[2], false],

                        [$like_op, 'code', '%'.$codes[1].','.$codes[2].','.$codes[0].'%', false],
                        [$like_op, 'code', $codes[1].'%'.$codes[2].','.$codes[0], false],
                        [$like_op, 'code', $codes[1].','.$codes[2].'%'.$codes[0], false],

                        [$like_op, 'code', '%'.$codes[2].','.$codes[0].','.$codes[1].'%', false],
                        [$like_op, 'code', $codes[2].'%'.$codes[0].','.$codes[1], false],
                        [$like_op, 'code', $codes[2].','.$codes[0].'%'.$codes[1], false],

                        [$like_op, 'code', '%'.$codes[2].','.$codes[1].','.$codes[0].'%', false],
                        [$like_op, 'code', $codes[2].'%'.$codes[1].','.$codes[0], false],
                        [$like_op, 'code', $codes[2].','.$codes[1].'%'.$codes[0], false],
                    ]
                ];
            }else{
                $where = [
                    'AND',
                    ['NOT LIKE', 'code', '%'.$codes[0].'%', false],
                    ['NOT LIKE', 'code', '%'.$codes[1].'%', false],
                    ['NOT LIKE', 'code', '%'.$codes[2].'%', false],
                    ['=', 'code_type', 4],
                ];
            }
            //p($where);
            $Num4Types = Num4Type::find()->select(['code'])->where($where)->asArray()->all();
            $codesArr = ArrayHelper::getColumn($Num4Types, 'code');
        }

        return array_unique($codesArr);
    }

    /**
     * @desc 4个号码返回24组号码组合
     * @param string $codes 格式：1123或者1112或者1122或者1234
     * @param int $type 0除1取
     * @return array ['1,2,3,4', '1,1,2,3', '1,1,1,2']
     */
    public static function getAllCombination4($codes, $type = 1, $code_type = 4){
        if(strlen($codes) != 4) return [];
        if($code_type == 5) { # 五位二定
            $twoNums = NumService::getTwoNums($codes);
            foreach ($twoNums as $twoNum) {
                $codesArr = NumService::getTwo5ByTwoNums([$twoNum[0], $twoNum[1]]);
            }
        }elseif($code_type==2) { # 四个位置组合：12，13，14，23，24，34
            $codesArr = NumService::get2DingWeiByCodes($codes);
        }elseif ($code_type==3){
            $codesArr = NumService::get3DingWeiByCodes($codes);
        }else{
            if($type == 1){
                $codesArr = [
                    $codes[0].','.$codes[1].','.$codes[2].','.$codes[3],
                    $codes[0].','.$codes[1].','.$codes[3].','.$codes[2],
                    $codes[0].','.$codes[2].','.$codes[1].','.$codes[3],
                    $codes[0].','.$codes[2].','.$codes[3].','.$codes[1],
                    $codes[0].','.$codes[3].','.$codes[1].','.$codes[2],
                    $codes[0].','.$codes[3].','.$codes[2].','.$codes[1],

                    $codes[1].','.$codes[0].','.$codes[2].','.$codes[3],
                    $codes[1].','.$codes[0].','.$codes[3].','.$codes[2],
                    $codes[1].','.$codes[2].','.$codes[0].','.$codes[3],
                    $codes[1].','.$codes[2].','.$codes[3].','.$codes[0],
                    $codes[1].','.$codes[3].','.$codes[0].','.$codes[2],
                    $codes[1].','.$codes[3].','.$codes[2].','.$codes[0],

                    $codes[2].','.$codes[0].','.$codes[1].','.$codes[3],
                    $codes[2].','.$codes[0].','.$codes[3].','.$codes[1],
                    $codes[2].','.$codes[1].','.$codes[0].','.$codes[3],
                    $codes[2].','.$codes[1].','.$codes[3].','.$codes[0],
                    $codes[2].','.$codes[3].','.$codes[0].','.$codes[1],
                    $codes[2].','.$codes[3].','.$codes[1].','.$codes[0],

                    $codes[3].','.$codes[0].','.$codes[1].','.$codes[2],
                    $codes[3].','.$codes[0].','.$codes[2].','.$codes[1],
                    $codes[3].','.$codes[1].','.$codes[0].','.$codes[2],
                    $codes[3].','.$codes[1].','.$codes[2].','.$codes[0],
                    $codes[3].','.$codes[2].','.$codes[0].','.$codes[1],
                    $codes[3].','.$codes[2].','.$codes[1].','.$codes[0],
                ];
            }else{
                $where = [
                    'AND',
                    ['NOT LIKE', 'code', '%'.$codes[0].'%', false],
                    ['NOT LIKE', 'code', '%'.$codes[1].'%', false],
                    ['NOT LIKE', 'code', '%'.$codes[2].'%', false],
                    ['NOT LIKE', 'code', '%'.$codes[3].'%', false],
                    ['=', 'code_type', 4],
                ];

                $Num4Types = Num4Type::find()->select(['code'])->where($where)->asArray()->all();
                $codesArr = ArrayHelper::getColumn($Num4Types, 'code');
            }
        }

        return array_unique($codesArr);
    }

    /**
     * @desc 获取二定位 by codes
     * @param string $codes  123或1234或12345
     * @return array array ['1,2,X,X', '1,X,2,X', '1,X,X,2']
     */
    public static function get2DingWeiByCodes($codes=''){
        $where = [
            'AND',
            ['=', 'code_type', 2],
        ];
        $tmpWhere = ['OR'];
        $len = strlen($codes);
        for($i=0; $i<$len; $i++){
            for($j=$i+1; $j<$len; $j++){
                $tmpWhere = array_merge($tmpWhere, [['LIKE', 'code', '%'.$codes[$i].'%'.$codes[$j].'%', false]]);
                $tmpWhere = array_merge($tmpWhere, [['LIKE', 'code', '%'.$codes[$j].'%'.$codes[$i].'%', false]]);
            }
        }
        $where = array_merge($where, [$tmpWhere]);
        $Num4Types = Num4Type::find()->select(['code'])->where($where)->asArray()->all();
        $codesArr = ArrayHelper::getColumn($Num4Types, 'code');

        return $codesArr;
    }

    /**
     * @desc 获取二定位 by codes
     * @param string $codes  123或1234或12345
     * @return array array ['1,2,X,X', '1,X,2,X', '1,X,X,2']
     */
    public static function get3DingWeiByCodes($codes=''){
        $where = [
            'AND',
            ['=', 'code_type', 3],
        ];
        $tmpWhere = ['OR'];
        $len = strlen($codes);
        for($i=0; $i<$len; $i++){
            for($j=$i+1; $j<$len; $j++){
                if($j<=$i) continue;
                for($k=$i+2; $k<$len; $k++){
                    if($k<=$j) continue;
                    $tmpWhere = array_merge($tmpWhere, [['LIKE', 'code', '%'.$codes[$i].'%'.$codes[$j].'%'.$codes[$k].'%', false]]);
                    $tmpWhere = array_merge($tmpWhere, [['LIKE', 'code', '%'.$codes[$i].'%'.$codes[$k].'%'.$codes[$j].'%', false]]);

                    $tmpWhere = array_merge($tmpWhere, [['LIKE', 'code', '%'.$codes[$j].'%'.$codes[$i].'%'.$codes[$k].'%', false]]);
                    $tmpWhere = array_merge($tmpWhere, [['LIKE', 'code', '%'.$codes[$j].'%'.$codes[$k].'%'.$codes[$i].'%', false]]);

                    $tmpWhere = array_merge($tmpWhere, [['LIKE', 'code', '%'.$codes[$k].'%'.$codes[$i].'%'.$codes[$j].'%', false]]);
                    $tmpWhere = array_merge($tmpWhere, [['LIKE', 'code', '%'.$codes[$k].'%'.$codes[$j].'%'.$codes[$i].'%', false]]);
                }
            }
        }
        $where = array_merge($where, [$tmpWhere]);
        $Num4Types = Num4Type::find()->select(['code'])->where($where)->asArray()->all();
        $codesArr = ArrayHelper::getColumn($Num4Types, 'code');

        return $codesArr;
    }

    /**
     * @desc 大于4个号码返回四定号码组合号码
     * @param $codes - 格式：11234或者11123或者11223或者12345或者123456
     * @return array ['1,2,3,4', '1,1,2,3', '1,1,1,2']
     */
    public static function getAllCombination4p($codes, $codeSplit = '', $code_type = 4){
        if(strlen($codes)<5) return [];
        if($code_type == 5) { # 五位二定
            $codesArr = [];
            $twoNums = NumService::getTwoNums($codes);
            foreach ($twoNums as $twoNum) {
                $codesArr = array_merge($codesArr, NumService::getTwo5ByTwoNums($twoNum[0], $twoNum[1]));
            }
        }elseif ($code_type == 2){
            $codesArr = NumService::get2DingWeiByCodes($codes);
        }elseif ($code_type == 3){
            $codesArr = NumService::get3DingWeiByCodes($codes);
        }elseif ($code_type == 4){
            $tmpArr = [];
            $len = strlen($codes);
            for ($i = 0; $i < $len; $i++) {
                $tmpArr[] = $codes[$i]; // [1,2,3,4,5,6]
            }
            $tmpCodesArr = []; // ['1234', '2345', '4567'....]
            for($i=0; $i<$len-3; $i++){
                for($j=$i+1; $j<$len; $j++){
                    for($k=$j+1; $k<$len; $k++){
                        for($l=$k+1; $l<$len; $l++){
                            $tmpStr = $tmpArr[$i].$tmpArr[$j].$tmpArr[$k].$tmpArr[$l];
                            $tmpCodesArr[] = $tmpStr;
                        }
                    }

                }
            }
            $codesArr = [];
            foreach ($tmpCodesArr as $k => $v) {
                $codesArr = array_merge($codesArr, NumService::getAllCombination4($v, $type=1, $code_type));
            }
            $codesArr = array_unique($codesArr);
        }else {
            $tmpArr = [];
            $len = strlen($codes);
            for ($i = 0; $i < $len; $i++) {
                $tmpArr[] = $codes[$i];
            }
            $tmpArr1 = $tmpArr2 = $tmpArr3 = $tmpArr4 = $tmpArr;

            # 第1步：循环获取二字组合
            $codes2Arr = [];
            $codes3Arr = [];
            $codes4Arr = [];
            foreach ($tmpArr1 as $k1 => $v1) {
                $fen = floor(count($tmpArr1) / 2);
                if ($k1 + 1 > $fen) break;
                foreach ($tmpArr2 as $k2 => $v2) {
                    if ($k2 <= $k1) continue;
                    $codes2Str = $v1 . $codeSplit . $v2;
                    $codes2Arr[] = $codes2Str;
                    foreach ($tmpArr3 as $k3 => $v3) {
                        if ($k3 == $k1 OR $k3 == $k2) continue;
                        $codes3Str = $codes2Str . $codeSplit . $v3;
                        $codes3Arr[] = $codes3Str;
                        foreach ($tmpArr4 as $k4 => $v4) {
                            if ($k4 == $k1 OR $k4 == $k2 OR $k4 == $k3) continue;
                            $codes4Str = $codes3Str . $codeSplit . $v4;
                            $tmp = [$codes4Str[0], $codes4Str[1], $codes4Str[2], $codes4Str[3],];
                            asort($tmp);
                            $codes4Arr[] = $tmp;
                        }
                    }
                }
            }
            //p($codes4Arr);
            //$codes4Arr = array_unique($codes4Arr);
            $tmpCodesArr = [];
            //p($codes4Arr);
            foreach ($codes4Arr as $k => $v) {
                $tmpCodesArr[] = implode('', $v);
            }
            $tmpCodesArr = array_unique($tmpCodesArr);
            $codesArr = [];
            foreach ($tmpCodesArr as $k => $v) {
                $codesArr = array_merge($codesArr, NumService::getAllCombination4($v, $type = 1, $code_type));
            }
        }

        return $codesArr;
    }

    /**
     * @desc
     * @param string $code1
     * @param string $code2
     * @return array ['1,X,X,X,2', 'X,1,X,X,2', 'X,X,1,X,2', 'X,X,X,1,2', '2,X,X,X,1', 'X,2,X,X,1', 'X,X,2,X,1', 'X,X,X,2,1']
     */
    public static function getTwo5ByTwoNums($code1 = '', $code2 = ''){
        $codesArr = [];

        $tmpCodesArr = NumService::getCodesTwo5([$code1, $code2]);
        foreach ($tmpCodesArr as $tmpCodes){
            $codesArr[] = implode(',', $tmpCodes);
        }

        return $codesArr;
    }

    /**
     * @param $codes
     */
    public static function getTwoNums($codes){
        $codesArr = [];
        if(strlen($codes) == 1){
            $codesArr[] = [$codes, $codes];
        }elseif (strlen($codes) == 2){
            $codesArr = [[$codes[0], $codes[1]]];
        }elseif(strlen($codes) == 3){
            $codesArr = [[$codes[0], $codes[1]], [$codes[0], $codes[2]], [$codes[1], $codes[2]]];
        }else{
            $codesArr = [];
            $len = strlen($codes);
            for($i=0; $i<$len; $i++){ # 2345678
                for ($j=$i+1; $j<$len; $j++){
                    $codesArr = array_merge($codesArr, [ [$codes[$i], $codes[$j]] ]);
                }
            }
        }

        return $codesArr;
    }

    /**
     * @desc 快选功能过滤
     * @param $codes_hz array
     * @param int $code_type 1一定2二定3三定4四定
     * @return array
     */
    public static function getCodesKuaiXuan($codes_hz, $code_type = 4, $codes=[], $lottery_type='') {
        //p([$codes_hz, $code_type]);
        if(empty($codes_hz)) return [];

        $where = ['AND', ['=', 'code_type', $code_type]];
        # 双重:type_2、三重:type_3、四重:type_4、双双重:type_22、两兄弟:type_2b、三兄弟:type_3b、四兄弟:type_4b
        # 1、双重
        if(isset($codes_hz['type_2'])){
            $where = array_merge($where, [['=', 'type_2', $codes_hz['type_2']]]);
        }
        # 2、三重
        if(isset($codes_hz['type_3'])){
            $where = array_merge($where, [['=', 'type_3', $codes_hz['type_3']]]);
        }
        # 3、四重
        if(isset($codes_hz['type_4'])){
            $where = array_merge($where, [['=', 'type_4', $codes_hz['type_4']]]);
        }
        # 4、双双重
        if(isset($codes_hz['type_22'])){
            $where = array_merge($where, [['=', 'type_22', $codes_hz['type_22']]]);
        }
        # 5.0、两兄弟
        if(isset($codes_hz['type_2b'])){
            $where = array_merge($where, [['=', 'type_2b', $codes_hz['type_2b']]]);
        }
        # 5.1、双两兄弟
        if(isset($codes_hz['type_22b'])){
            $where = array_merge($where, [['=', 'type_22b', $codes_hz['type_22b']]]);
        }
        # 6、三兄弟
        if(isset($codes_hz['type_3b'])){
            $where = array_merge($where, [['=', 'type_3b', $codes_hz['type_3b']]]);
        }
        # 7、四兄弟
        if(isset($codes_hz['type_4b'])){
            $where = array_merge($where, [['=', 'type_4b', $codes_hz['type_4b']]]);
        }

        # 和值
        if(isset($codes_hz['hz'])){
            $where = array_merge($where, [ ['IN', 'codes_hz', $codes_hz['hz']] ]);
            //$query->andWhere($andWhere);
        }
        # tz_type:28 和值-取
        if(!empty($codes_hz['get_hzs'])){
            $where = array_merge($where, [ ['IN', 'codes_hz', $codes_hz['get_hzs']] ]);
        }
        # tz_type:28 和值-除
        if(!empty($codes_hz['remove_hzs'])){
            $where = array_merge($where, [ ['NOT IN', 'codes_hz', $codes_hz['remove_hzs']] ]);
        }

        if(in_array($code_type, [2, 3]) && !empty($codes_hz['fixed_sel_pos'])){
            $fixed_sel_poses = explode(',', $codes_hz['fixed_sel_pos']);
            foreach($fixed_sel_poses as $f_pos){
                $where[] = ['=', 'code_'.$f_pos, 'X'];
            }
        }

        $where = NumService::getFixedPostionWhere($codes_hz, $where, $code_type);  # 定位置
        $where = NumService::getExcludeCodesWhere($codes_hz, $where, $code_type);  # 排除
        $where = NumService::getHeFenWhere($codes_hz, $where, $code_type);  # 定位合分
        $where = NumService::getFuShiWhere($codes_hz, $where, $code_type);  # 复式：三定位，复式“取”数：123、四定位，复式“取”数：123
        $where = NumService::getPeiShuWhere($codes_hz, $where, $code_type);  # 配数
        $where = NumService::getLogWhere($codes_hz, $where, $code_type);  # 对数
        $where = NumService::getPosOddWhere($codes_hz, $where);  # 筛选位置：单
        $where = NumService::getPosEvenWhere($codes_hz, $where);  # 筛选位置：双
        $where = NumService::getPosBigWhere($codes_hz, $where);  # 筛选位置：大
        $where = NumService::getPosSmallWhere($codes_hz, $where);  # 筛选位置：小
        $where = NumService::getFenLiShuWhere($codes_hz, $where, $code_type);  # 分离数
        $where = NumService::hsAndCfTwoFoneWhere($codes_hz, $where, $code_type);  # 两合上1

        ####################################  走移 start  ##################################
        # 123千走456各1元 - 未完成待续
        if(!empty($codes_hz['zou_yi'])){
            $poses = ['1', '2', '3', '4'];
            $fix_poses = [];
            $no_fix_poses = [];
            foreach ($poses as $ps){
                $len_zouyi = strlen($codes_hz['zou_yi']);
                if(isset($codes_hz['p'.$ps]) && !empty($codes_hz['p'.$ps])){ # 定位位置
                    $fix_poses[] = $ps;
                }else{ # 走移位置
                    $no_fix_poses[] = $ps;
                }
            }
            $zouyi_codes = $codes_hz['zou_yi']; # 走移号码
            $fix_pos_counts = count($fix_poses);
            //p(['code_desc'=>$codes_hz, 'no_fix_poses'=>$no_fix_poses, 'fix_poses'=>$fix_poses, 'code_type'=>$code_type, 'fix_pos_counts'=>$fix_pos_counts, 'zouyi_codes'=>$zouyi_codes]);
            if($code_type == 2){ # 二定
                if($fix_pos_counts == 1){ # 已经定一个位 - 剩余一个未定位
                    $where_tmp_zouyi = ['OR'];
                    for ($z=0; $z<$len_zouyi; $z++){
                        foreach ($no_fix_poses as $no_fix_zouyi_pose){
                            $where_tmp_zouyi = array_merge($where_tmp_zouyi, [['=', 'code_'.$no_fix_zouyi_pose, $zouyi_codes[$z]]]);
                        }
                    }
                    $where = array_merge($where, [$where_tmp_zouyi]);
                }
            }elseif ($code_type == 3){ # 二定
                if($fix_pos_counts == 1){ # 已经定一个位
                    $where_tmp_zouyi = ['OR'];
                    for ($z=0; $z<$len_zouyi; $z++){
                        for ($zz=$z+1; $zz<$len_zouyi; $zz++) {
                            for($y=0; $y<count($no_fix_poses); $y++){
                                for($yy=$y+1; $yy<count($no_fix_poses); $yy++){
                                    $where_tmp_zouyi = array_merge($where_tmp_zouyi, [
                                        [ 'AND',
                                            ['OR',
                                                ['AND',
                                                    ['=', 'code_' . $no_fix_poses[$y], $zouyi_codes[$z]],
                                                    ['=', 'code_' . $no_fix_poses[$yy], $zouyi_codes[$zz]],
                                                ],
                                                ['AND',
                                                    ['=', 'code_' . $no_fix_poses[$yy], $zouyi_codes[$z]],
                                                    ['=', 'code_' . $no_fix_poses[$y], $zouyi_codes[$zz]],
                                                ],
                                            ]
                                        ]
                                    ]);
                                }
                            }
                        }
                    }
                }elseif ($fix_pos_counts == 2){ # 已经定两个位 - 剩余一个未定位 - 已校验
                    $where_tmp_zouyi = ['OR'];
                    for ($z=0; $z<$len_zouyi; $z++){
                        foreach ($no_fix_poses as $no_fix_zouyi_pose){
                            $where_tmp_zouyi = array_merge($where_tmp_zouyi, [['=', 'code_'.$no_fix_zouyi_pose, $zouyi_codes[$z]]]);
                        }
                    }
                }
                $where = array_merge($where, [$where_tmp_zouyi]);
            }
        }
        //p(['code_type'=>$code_type, 'fix_pos_counts'=>$fix_pos_counts, 'fix_poses'=>$fix_poses, 'no_fix_poses'=>$no_fix_poses, 'where'=>$where]);
        ####################################  走移 end  ##################################

        # 不定位合分(1两数、2三数) - 三定
        //if($code_type == 3 && !empty($codes_hz['no_fix_hefen']) && !empty($codes_hz['no_fix_hefen_pos'])){
        $newHefens = [];
        if(!empty($codes_hz['no_fix_hefen2']) && $codes_hz['no_fix_hefen_pos_2'] == 1){
            $newHefens[] = ['no_fix_hefen'=>$codes_hz['no_fix_hefen2'], 'no_fix_hefen_pos'=>$codes_hz['no_fix_hefen_pos_2']];
        }
        if(!empty($codes_hz['no_fix_hefen3']) && $codes_hz['no_fix_hefen_pos_3'] == 2){
            $newHefens[] = ['no_fix_hefen'=>$codes_hz['no_fix_hefen3'], 'no_fix_hefen_pos'=>$codes_hz['no_fix_hefen_pos_3']];
        }
        # 新
        if(!empty($codes_hz['no_fix_hefen']) && !empty($codes_hz['no_fix_hefen_pos'])) { # no_fix_hefen:不定位合分值、no_fix_hefen_pos:1两数2三数
            $newHefens[] = ['no_fix_hefen'=>$codes_hz['no_fix_hefen'], 'no_fix_hefen_pos'=>$codes_hz['no_fix_hefen_pos']];
        }
        foreach ($newHefens as $newHefen){
            $noFixHefen = $newHefen['no_fix_hefen'];
            $noFixHefenPos = $newHefen['no_fix_hefen_pos'];
            if(!empty($noFixHefen) && !empty($noFixHefenPos)){ # no_fix_hefen:不定位合分值、no_fix_hefen_pos:1两数2三数
                /**
                 * 1、处理合分值，例如：149转换成：
                 * 二定：1、11、4、14、9
                 * 三定：1、11、21、4、14、24、9、19、29
                 * 四定：1、11、21、31、4、14、24、9、19、29
                 */
                $no_fix_lenHefen = strlen($noFixHefen);
                $codes_no_fix_hefen = [];
                for ($i=0; $i<$no_fix_lenHefen;$i++){
                    if($noFixHefenPos == 1){
                        # 1、两数合分
                        if($noFixHefen[$i]<=8){
                            $no_fix_hefenArr = [$noFixHefen[$i], $noFixHefen[$i] + 10];
                        }else{
                            $no_fix_hefenArr = [$noFixHefen[$i]];
                        }
                    }elseif($noFixHefenPos == 2){
                        # 1、三数合分
                        if($noFixHefen[$i]<=7){
                            $no_fix_hefenArr = [$noFixHefen[$i], $noFixHefen[$i] + 10, $noFixHefen[$i] + 20];
                        }else{
                            $no_fix_hefenArr = [$noFixHefen[$i], $noFixHefen[$i] + 10];
                        }
                    }
                    $codes_no_fix_hefen = array_merge($codes_no_fix_hefen, $no_fix_hefenArr);
                }
                //p($codes_no_fix_hefen);

                /**
                 * 2、组合where条件
                 */
                if($noFixHefenPos == 1){ # 两数合分 ----------- 不定位合分
                    $tmp_no_fix_hefen = ['OR'];
                    $poss = [[1,2], [1,3], [1,4],[2,3],[2,4],[3,4]];
                    if(in_array($code_type, [2, 3])){ # 三定
                        foreach ($poss as $pos){ # ['IN', SUM(code_1 + code2),[1,11,21]]
                            $son_where = [
                                ['AND',
                                    ['IN', '(`code_'.$pos[0].'` + `code_'.$pos[1].'`)', $codes_no_fix_hefen],
                                    ['<>', '`code_'.$pos[0].'`', 'X'],
                                    ['<>', '`code_'.$pos[1].'`', 'X']
                                ]
                            ];
                            $tmp_no_fix_hefen = array_merge($tmp_no_fix_hefen, $son_where);
                        }
                    }elseif($code_type == 2){ # 二定

                    }elseif($code_type == 4){ # 四定
                        foreach ($poss as $pos){ # ['IN', SUM(code_1 + code2),[1,11,21]]
                            $son_where = [ ['IN', '(`code_'.$pos[0].'` + `code_'.$pos[1].'`)', $codes_no_fix_hefen] ];
                            $tmp_no_fix_hefen = array_merge($tmp_no_fix_hefen, $son_where);
                        }
                    }
                }elseif($noFixHefenPos == 2){ # 三数合分 ----------- 不定位合分
                    if($code_type == 3) { # 三定
                        $tmp_no_fix_hefen = ['IN', 'codes_hz', $codes_no_fix_hefen];
                    }elseif ($code_type == 4){ # 四定
                        $tmp_no_fix_hefen = ['OR'];
                        $poss = [[1,2,3], [1,2,4], [1,3,4],[2,3,4]];
                        foreach ($poss as $pos){ # ['IN', SUM(code_1 + code2),[1,11,21]]
                            $son_where = [ ['IN', '(`code_'.$pos[0].'` + `code_'.$pos[1].'` + `code_'.$pos[2].'`)', $codes_no_fix_hefen] ];
                            $tmp_no_fix_hefen = array_merge($tmp_no_fix_hefen, $son_where);
                        }
                    }
                }
                $where = array_merge($where, [$tmp_no_fix_hefen]);
            }
        }

        # 合分 - 四定，例如：合分：147，转化成和值：1、11、21、31、4、14、14、34、7、17、27
        if($code_type == 4 && !empty($codes_hz['xhefen'])){
            $lenHefen = strlen($codes_hz['xhefen']);
            $codes_hefen = [];
            for ($i=0; $i<$lenHefen; $i++){
                if($codes_hz['xhefen'][$i]<=6){
                    $hefenArr = [$codes_hz['xhefen'][$i], $codes_hz['xhefen'][$i] + 10, $codes_hz['xhefen'][$i] + 20, $codes_hz['xhefen'][$i] + 30];
                }else{
                    $hefenArr = [$codes_hz['xhefen'][$i], $codes_hz['xhefen'][$i] + 10, $codes_hz['xhefen'][$i] + 20];
                }
                $codes_hefen = array_merge($codes_hefen, $hefenArr);
            }
            $where = array_merge($where, [ ['IN', 'codes_hz', $codes_hefen] ]);
        }

        # 单双类型：1122，1212，2222 等，总共16种
        if(!empty($codes_hz['type_ds_details'])){
            $where = array_merge($where, [['IN', 'type_ds', $codes_hz['type_ds_details']]]);
        }

        # 三定、四定 "含" 除、取
        if(in_array($code_type, [3,4]) && !empty(trim($codes_hz['arise_in'])) && in_array($codes_hz['arise_in_sel'], [1, 2])){
            $lenAriseIn = strlen($codes_hz['arise_in']); # 含的个数
            $tmpAriseInType = $codes_hz['arise_in_sel'];
            if($tmpAriseInType == 1){ # 除
                $op = 'AND';
                $sel_type = '<>';
            }elseif($tmpAriseInType == 2){ # 取
                $op = 'OR';
                $sel_type = '=';
            }
            if($lenAriseIn>1){
                $tmpAriseIn = [$op];
                for($i=0; $i<$lenAriseIn; $i++){
                    $tmpAriseIn = array_merge($tmpAriseIn, [
                        [
                            $op,
                            [$sel_type, 'code_1', $codes_hz['arise_in'][$i] ], [$sel_type, 'code_2', $codes_hz['arise_in'][$i] ],
                            [$sel_type, 'code_3', $codes_hz['arise_in'][$i] ], [$sel_type, 'code_4', $codes_hz['arise_in'][$i] ]
                        ]
                    ]);
                }
                $where = array_merge($where, [$tmpAriseIn]);
            }else{
                $where = array_merge($where, [
                    [ $op,
                        [$sel_type, 'code_1', $codes_hz['arise_in'] ], [$sel_type, 'code_2', $codes_hz['arise_in'] ],
                        [$sel_type, 'code_3', $codes_hz['arise_in'] ], [$sel_type, 'code_4', $codes_hz['arise_in'] ]
                    ]
                ]);
            }
        }else if($code_type==2 && isset($codes_hz['arise_in']) && in_array($codes_hz['arise_in_sel'], [1, 2])){
            # 二定含，除、取
            $len = strlen($codes_hz['arise_in']);
            $hanWhere = ['and'];
            $hanNums = [];
            for ($i=0; $i<$len; $i++){
                $hanNums[] = $codes_hz['arise_in'][$i];
            }
            $hanWhere[] = ['REGEXP', 'CONCAT(code_1, code_2, code_3, code_4)', implode('|', $hanNums)];
            if($codes_hz['arise_in_sel']==NumService::EXCLUDE){
                $where = array_merge($where, [['NOT', $hanWhere]]);
            }else{
                $where[] = $hanWhere;
            }
        }

        # 第1位
        #if(isset($codes_hz['p1']) && $codes_hz['p1'] !== ''){
        #    $p1_codes = self::getCodesArrByStr($codes_hz['p1']);
        #    $where = array_merge($where, [ ['IN', 'code_1', $p1_codes] ]);
        #}
        if(isset($codes_hz['p1_0']) && $codes_hz['p1_0'] !== ''){
            $p1_codes = self::getCodesArrByStr($codes_hz['p1_0']);
            $where = array_merge($where, [ ['NOT IN', 'code_1', $p1_codes] ]);
        }

        # 第2位
        #if(isset($codes_hz['p2']) && $codes_hz['p2'] !== ''){
        #    $p2_codes = self::getCodesArrByStr($codes_hz['p2']);
        #    $where = array_merge($where, [ ['IN', 'code_2', $p2_codes] ]);
        #}
        if(isset($codes_hz['p2_0']) && $codes_hz['p2_0'] !== ''){
            $p2_codes = self::getCodesArrByStr($codes_hz['p2_0']);
            $where = array_merge($where, [ ['NOT IN', 'code_2', $p2_codes] ]);
        }

        # 第3位
        #if(isset($codes_hz['p3']) && $codes_hz['p3'] !== ''){
        #    $p3_codes = self::getCodesArrByStr($codes_hz['p3']);
        #    $where = array_merge($where, [ ['IN', 'code_3', $p3_codes] ]);
        #}
        if(isset($codes_hz['p3_0']) && $codes_hz['p3_0'] !== ''){
            $p3_codes = self::getCodesArrByStr($codes_hz['p3_0']);
            $where = array_merge($where, [ ['NOT IN', 'code_3', $p3_codes] ]);
        }

        # 第4位
        #if(isset($codes_hz['p4']) && $codes_hz['p4'] !== ''){
        #    $p4_codes = self::getCodesArrByStr($codes_hz['p4']);
        #    $where = array_merge($where, [ ['IN', 'code_4', $p4_codes] ]);
        #}
        if(isset($codes_hz['p4_0']) && $codes_hz['p4_0'] !== ''){
            $p4_codes = self::getCodesArrByStr($codes_hz['p4_0']);
            $where = array_merge($where, [ ['NOT IN', 'code_4', $p4_codes] ]);
        }

        # 第5位
        if(isset($codes_hz['p5']) && $codes_hz['p5'] !== ''){
            $p5_codes = self::getCodesArrByStr($codes_hz['p5']);
            $where = array_merge($where, [ ['IN', 'code_5', $p5_codes] ]);
        }
        if(isset($codes_hz['p5_0']) && $codes_hz['p5_0'] !== ''){
            $p5_codes = self::getCodesArrByStr($codes_hz['p5_0']);
            $where = array_merge($where, [ ['NOT IN', 'code_5', $p5_codes] ]);
        }

        # 同时选择取、除四单四双
        if(isset($codes_hz['type_4ds'])){
            if(is_array($codes_hz['type_4ds']) AND !empty($codes_hz['type_4ds'])){
                $where = array_merge($where, [['IN', 'type_4ds', $codes_hz['type_4ds']]]);
            }
        }

        # tz_type:38 三现:双重+两兄弟 1
        if(isset($codes_hz['type_3n_2b']) && $codes_hz['type_3n_2b'] !== ''){
            $where = array_merge($where, [['=', 'type_3n_2b', (int)$codes_hz['type_3n_2b']]]);
        }

        # tz_type:28 类型取 或  类型除
        if(!empty($codes_hz['get_types']) OR !empty($codes_hz['remove_types'])){
            $TypeKeys = UserSysPlansService::getCodeTypeKeys();

            # 类型取 或 get_types
            if(!empty($codes_hz['get_types'])){
                $where_get_types = ['OR'];
                foreach ($codes_hz['get_types'] as $get_type){
                    $where_get_types[] = ['=', $TypeKeys[$get_type], 1];
                }
                $where = array_merge($where, [$where_get_types]);
            }

            # 类型除 并 remove_types
            if(!empty($codes_hz['remove_types'])){
                $where_remove_types = ['AND'];
                foreach ($codes_hz['remove_types'] as $remove_type){
                    $where_remove_types[] = ['=', $TypeKeys[$remove_type], 0];
                }
                $where = array_merge($where, [$where_remove_types]);
            }
        }

        # 双对数
        if(isset($codes_hz['type_2log'])){
            $where = array_merge($where, [['=', 'type_2log', $codes_hz['type_2log']]]);
        }

        # 对数
        if(isset($codes_hz['type_log'])){
            $where = array_merge($where, [['=', 'type_log', $codes_hz['type_log']]]);
        }

        # 上奖字段
        $tmpAriseLen = strlen($codes_hz['arise']);
        $tmpAriseArr = [];
        for ($i=0; $i<$tmpAriseLen; $i++){
            $tmpAriseArr[] = $codes_hz['arise'][$i];
        }

        //p([$where, $codes_hz]);
        //$get = \Yii::$app->request->get(); if($get['t'] == 1)p($where);
        $codesArr = [];
        if($code_type == 4){
        }elseif ($code_type == 3){
        }elseif ($code_type == 5){ # 五位二定
            $tmpArisewhere = ['OR'];
            if($codes_hz['arise']){
                $tmpArisewhere = array_merge($tmpArisewhere, [ ['IN', 'code_1', $tmpAriseArr] ]);
                $tmpArisewhere = array_merge($tmpArisewhere, [ ['IN', 'code_2', $tmpAriseArr] ]);
                $tmpArisewhere = array_merge($tmpArisewhere, [ ['IN', 'code_3', $tmpAriseArr] ]);
                $tmpArisewhere = array_merge($tmpArisewhere, [ ['IN', 'code_4', $tmpAriseArr] ]);
                $where = array_merge($where, [$tmpArisewhere]);
            }
        }
        $query = Num4Type::find()->where($where);

        //p([$codes_hz['arb_pos_isbaohan'], $codes_hz['arb_pos_nums'], $codes_hz['arb_pos_codes']],0);
        ############################ 任意位置包含、排除 start ###################################
        if(isset($codes_hz['arb_pos_isbaohan'])){
            $arb_pos_nums = $codes_hz['arb_pos_nums']; # 个数
            $arb_pos_codes = $codes_hz['arb_pos_codes']; # 号码
            if($codes_hz['arb_pos_isbaohan'] == 1){ # 任意位置包含
                $arb_pos_asises = NumService::getCodesArrByNum($arb_pos_codes, $arb_pos_nums);
                $arb_codesArr = self::getCodesArise($arb_pos_asises, $type = 1, $code_type); # 比如：$arb_pos_nums=2, arb_pos_asises= ['12','34','45'] # 每个元素为两个号码
                $query->andWhere(['IN', 'code', $arb_codesArr]);
            }elseif($codes_hz['arb_pos_isbaohan'] === 0){ # 排除

            }
        }
        ########################### 任意位置包含、排除 start ###################################
        //p(['codes_hz'=>$codes_hz]);

        ###################################################### filters过滤参数开始05.24 ######################################################
        # 1、排除前x期 05.24
        if(isset($codes_hz['filters']['is_filter']) && in_array($code_type, [2, 3, 4]) && $codes_hz['filters']['is_filter'] == 1){
            $filters = $codes_hz['filters'];
            if(!empty($codes)){
                $filter_poses = NumService::getFilterPosByCode($codes[0]); # 根据导入的号码判断要过滤的位置
                if(!empty($filter_poses)){
                    foreach ($filter_poses as $pos){
                        $query->andWhere(['<>', 'code_'.$pos, 'X']);
                    }
                }
                if($lottery_type && !empty($filters['filter_xQ_before'])){
                    $qihao = HN0898Service::getCurrentQihao($lottery_type);
                    $index_id = SscKjData::find()->where(['AND', ['=', 'qihao', $qihao], ['=','lottery_type', $lottery_type]])->limit(1)->asArray()->one()['index_id'];
                    $filter_index_ids = [];
                    if(!empty($filters['filter_xQ_before'])){ # 1,2;4~6 前x期
                        $tmp_filter_index_Arrs = explode(';', $filters['filter_xQ_before']);
                        foreach ($tmp_filter_index_Arrs as $tmp_filter_index_Arr){
                            if(strpos($tmp_filter_index_Arr, ',') !== false){ # 1,2
                                $tmp_filter_index_Arr2 = explode(',', $tmp_filter_index_Arr);
                                foreach ($tmp_filter_index_Arr2 as $tmp_index){
                                    $filter_index_ids[] = $index_id - $tmp_index + 1;
                                }
                            }elseif(strpos($tmp_filter_index_Arr, '~') !== false){ # 4~6
                                $tmp_filter_index_Arr2 = explode('~', $tmp_filter_index_Arr);
                                if(empty($tmp_filter_index_Arr2) OR count($tmp_filter_index_Arr2)<2) continue;
                                sort($tmp_filter_index_Arr2); # 正序
                                for ($i=$tmp_filter_index_Arr2[0]; $i<=end($tmp_filter_index_Arr2); $i++){
                                    $filter_index_ids[] = $index_id - $i + 1;
                                }
                            }else{
                                if(is_string($tmp_filter_index_Arr)){
                                    $tmp_filter_index_Arr = (int)$tmp_filter_index_Arr;
                                }
                                $filter_index_ids[] = $index_id - $tmp_filter_index_Arr + 1;
                            }
                        }
                        //p(['index_id'=>$index_id, $filters, $filter_index_ids]);
                        if(!empty($filter_index_ids)){ # 过滤期的index_id
                            $SscKjDatas = SscKjData::find()->where(['AND', ['IN', 'index_id', $filter_index_ids], ['=', 'lottery_type', $lottery_type]])->orderBy(['id'=>SORT_DESC])->asArray()->all();
                            //p(['SscKjDatas'=>$SscKjDatas, 'filters'=>$filters, 'filter_index_ids'=>$filter_index_ids]);
                            foreach ($SscKjDatas as $sscKjData){
                                if(!empty($filters['filter_pos1'])) { # 特殊过滤
                                    $tmp_filter1_where = ['OR',];
                                    $filter_pos1 = $filters['filter_pos1'];
                                    # pos1
                                    foreach ($filter_poses as $k=>$pos) {
                                        $tmp_filter1_where[] = ['<>', 'code_' . $pos, $sscKjData['code' . $filter_pos1[$k]]];
                                    }
                                    $query->andWhere($tmp_filter1_where);
                                }

                                # pos2
                                if(!empty($filters['filter_pos2'])){ # 特殊过滤
                                    $tmp_filter2_where = ['OR'];
                                    $filter_pos2 = $filters['filter_pos2'];
                                    sort($filter_pos2);

                                    # 现在
                                    foreach ($filter_poses as $k=>$pos){
                                        $tmp_filter2_where[] = ['<>', 'code_'.$pos, $sscKjData['code'.$filter_pos2[$k]]];
                                    }
                                    $query->andWhere($tmp_filter2_where);
                                }
                            }
                        }
                    }
                }
                //Tool_Common::log('/codes/'.__FUNCTION__.'_filter_xQ_before', 'INFO', '过滤前x期', ['code_hz'=>$codes_hz, 'where'=>$where]);
                //$query->andWhere(['IN', 'code', $codes]);
            }
        }

        # 2、排除前x天同期 05.25
        if(isset($codes_hz['filter_dates']['is_filter_date']) && in_array($code_type, [2, 3, 4]) && $codes_hz['filter_dates']['is_filter_date'] == 1){
            $filter_dates = $codes_hz['filter_dates'];
            if(!empty($codes)){
                $filter_poses = NumService::getFilterPosByCode($codes[0]); # 根据导入的号码判断要过滤的位置
                if(!empty($filter_poses)){
                    foreach ($filter_poses as $pos){
                        $query->andWhere(['<>', 'code_'.$pos, 'X']);
                    }
                }
                if($lottery_type && !empty($filter_dates['filter_xD_before'])){
                    $qihao = HN0898Service::getQihao($lottery_type);
                    $sub_qihao = substr($qihao, -3, 3); # 短期号
                    //p([$qihao, $sub_qihao]);
                    # 以下待修改 05-24 20点40分
                    //$index_date = SscKjData::find()->where(['AND', ['=', 'qihao', $qihao], ['=','lottery_type', $lottery_type]])->asArray()->one()['date'];
                    $index_date = date('Y-m-d');
                    $filter_index_dates = [];
                    if(!empty($filter_dates['filter_xD_before'])){ # 1,2;4~6 # 前x天同期
                        $tmp_filter_index_Arrs = explode(';', $filter_dates['filter_xD_before']);
                        foreach ($tmp_filter_index_Arrs as $tmp_filter_index_Arr){
                            //p($tmp_filter_index_Arr);
                            if(strpos($tmp_filter_index_Arr, ',') !== false){ # 1,2
                                $tmp_filter_index_Arr2 = explode(',', $tmp_filter_index_Arr);
                                foreach ($tmp_filter_index_Arr2 as $tmp_index){
                                    $filter_index_dates[] = date('Y-m-d', (strtotime($index_date) - $tmp_index*86400));
                                }
                            }elseif(strpos($tmp_filter_index_Arr, '~') !== false){ # 4~6
                                $tmp_filter_index_Arr2 = explode('~', $tmp_filter_index_Arr);
                                if(empty($tmp_filter_index_Arr2) OR count($tmp_filter_index_Arr2)<2) continue;
                                sort($tmp_filter_index_Arr2); # 正序
                                for ($i=$tmp_filter_index_Arr2[0]; $i<=end($tmp_filter_index_Arr2); $i++){
                                    $filter_index_dates[] = date('Y-m-d', (strtotime($index_date) - $i*86400));
                                }
                            }else{
                                if(is_string($tmp_filter_index_Arr)){
                                    $tmp_filter_index_Arr = (int)$tmp_filter_index_Arr;
                                }
                                $filter_index_dates[] = date('Y-m-d', (strtotime($index_date) - $tmp_filter_index_Arr*86400)); # $tmp_filter_index_Arr 为整数
                            }
                        }
                        //p($filter_index_dates);
                        if(!empty($filter_index_dates)){ # 过滤期的index_id
                            $where_index_date = ['AND', ['IN', 'date', $filter_index_dates], ['=', 'lottery_type', $lottery_type], ['LIKE', 'qihao', '%'.$sub_qihao, false]];
                            $SscKjDatas = SscKjData::find()->select(['qihao','date','kj_code','code1','code2','code3','code4'])->where($where_index_date)->asArray()->all();
                            //p(['SscKjDatas'=>$SscKjDatas, 'filter_dates'=>$filter_dates]);
                            foreach ($SscKjDatas as $sscKjData){
                                $tmp_filter1_where = ['OR', ];
                                $filter_pos1 = $filter_dates['filter_date_pos1'];
                                # pos1
                                foreach ($filter_poses as $k=>$pos){
                                    $tmp_filter1_where[] = ['<>', 'code_' . $pos, $sscKjData['code' . $filter_pos1[$k]]];
                                }
                                $query->andWhere($tmp_filter1_where);

                                # pos2
                                if(!empty($filter_dates['filter_date_pos2'])){ # 特殊过滤
                                    $tmp_filter2_where = ['OR'];
                                    $filter_pos2 = $filter_dates['filter_date_pos2'];
                                    sort($filter_pos2);

                                    # 现在
                                    foreach ($filter_poses as $k=>$pos){
                                        $tmp_filter2_where[] = ['<>', 'code_'.$pos, $sscKjData['code'.$filter_pos2[$k]]];
                                    }
                                    $query->andWhere($tmp_filter2_where);
                                }
                            }
                        }
                    }
                }
            }
        }

        # 3、排除期期号定位 05.25
        if(isset($codes_hz['filter_qihaos']['is_filter_qihao']) && in_array($code_type, [2, 3, 4]) && $codes_hz['filter_qihaos']['is_filter_qihao'] == 1){
            $filter_qihaos = $codes_hz['filter_qihaos'];
            $is_filter_qihao = $filter_qihaos['is_filter_qihao'];
            if(!empty($codes)){
                $filter_poses = NumService::getFilterPosByCode($codes[0]); # 根据导入的号码判断要过滤的位置
                if(!empty($filter_poses)){
                    foreach ($filter_poses as $pos){
                        $query->andWhere(['<>', 'code_'.$pos, 'X']);
                    }
                }
                $cnt = count($filter_poses);
                $fcnt = 0 - $cnt;
                if($is_filter_qihao && $cnt>0){
                    $qihao = HN0898Service::getQihao($lottery_type);
                    $sub_qihao = (string)substr($qihao, $fcnt, $cnt); # 短期号 156期，如果二定：56  三定：156
                    //p([$qihao, $sub_qihao, $fcnt, $cnt],0);
                    $tmp_filter2_where = ['OR', ];

                    foreach ($filter_poses as $n=>$pos){ # [1,2]:1千2百、[3,4]:3十4个
                        $tmp_filter2_where[] = ['<>', 'code_'.$pos, $sub_qihao[$n]];
                    }
                    //p(['filter_poses'=>$filter_poses, 'tmp_filter2_where'=>$tmp_filter2_where]);
                    $query->andWhere($tmp_filter2_where);
                }
            }
        }

        //(!empty($codes)) && $query->andWhere(['OR',['IN',  'code', $codes], ['IN', 'code_str', $codes]]);
        (!empty($codes)) && $query->andWhere(['IN', "REPLACE(`code`, ',', '')", $codes]);
        ###################################################### filters过滤参数结束05.24 ######################################################

        //$sql = $query->createCommand()->getRawSql();p($sql);
        $Num4Types = $query->asArray()->orderBy(['code'=>SORT_ASC])->all();
        $codesArr = ArrayHelper::getColumn($Num4Types, 'code');
        //p(['where'=>$where, 'index_id'=>$index_id, 'filter_index_ids'=>$filter_index_ids, 'filters'=>$filters, 'code'=>$codes, 'end'=>$codesArr]);
        //p(['where'=>$where, 'codes_hz'=>$codes_hz, 'codesArr'=>$codesArr]);

         # 上奖
        //if(isset($codes_hz['arise']) && !empty($codes_hz['arise'])){
        if(in_array($code_type, [2,3,4]) && isset($codes_hz['arise'])){
            $asises = explode(',', $codes_hz['arise']);
            $codesArr_arise = self::getCodesArise($asises, $type = 1, $code_type);
            //p(['code_type'=>$code_type, 'where'=>$where, 'codes_hz'=>$codes_hz, 'codesArr_arise'=>$codesArr_arise, 'codesArr'=>$codesArr]);
            if(in_array($code_type, [2,3,4])) {
                $codesArr = array_intersect($codesArr, $codesArr_arise); # 函数用于比较两个(或更多个)数组的键值,并返回交集
            }else{
                $codesArr = $codesArr_arise;
            }
        }

        # tz_type:28 上奖取
        if(isset($codes_hz['get_arises'])){
            $codes_hz['arise'] = explode(',', $codes_hz['get_arises']);
            $codesArr_arise = self::getCodesArise([$codes_hz['arise']], $type = 1);
            $codesArr = array_intersect($codesArr, $codesArr_arise);
        }

        # tz_type:28 上奖除
        if(isset($codes_hz['remove_arises'])){ # remove_arises
            $codes_hz['remove_arises'] = explode(',', $codes_hz['remove_arises']);
            $codesArr_arise = self::getCodesArise($codes_hz['remove_arises'], $type = 0);
            //p([count($codesArr_arise), $codes_hz['remove_arises'], $codesArr_arise]);
            $codesArr = array_intersect($codesArr, $codesArr_arise);
        }

        if($code_type == 4){
            $datas = array_unique($codesArr);
        }else{
            $datas = $codesArr;
        }
        //p(count($datas));

        return $datas;
    }

    /**
     * sige位置补全号码,不够数量补 0-9
     * @param $ps_datas
     * @return array
     */
    public static function fillArrayNums($ps_datas=[], $code_type=4){
        // 原始数组
        $originalArray = $ps_datas;
        $posFillCount = $code_type - count($originalArray); # 几定位 则填充几个配数
        if($code_type != 4){
            // 固定的补全元素定位补全配数
            $originalArray = array_merge($originalArray, array_fill(0, $posFillCount, $fixedPosElement='0123456789'));
        }

        // 期望的数组元素数量
        $expectedCount = 4;
        $fixedElement = $code_type==4?'0123456789':'X';  # 补齐四位数需要补的号码

        // 计算需要补全的元素数量
        $fillCount = $expectedCount - count($originalArray);

        // 补全数组
        $ps_datas = array_merge($originalArray, array_fill(0, $fillCount, $fixedElement));

        return $ps_datas;
    }

    /**
     * 给定数组，返回所有排列组合
     * @param $array
     * @return array|array[]
     */
    public static function getPermutations($array) {
        $results = [[]];
        foreach ($array as $element) {
            $tmp = [];
            foreach ($results as $result) {
                $count = count($result);
                for ($i = 0; $i <= $count; $i++) {
                    $copy = $result;
                    array_splice($copy, $i, 0, $element);
                    $tmp[] = $copy;
                }
            }
            $results = $tmp;
        }
        return $results;
    }

    /**
     * 配数条件
     * @param array $codes_hz
     * @param array $where
     * @return array
     */
    private static function getPeiShuWhere($codes_hz=[], &$where=[], $code_type=4){
        if(!isset($codes_hz['ps_sel']) OR !in_array($codes_hz['ps_sel'], [NumService::PEI_SHU_EXCLUDE, NumService::PEI_SHU_OBTAIN])){
            return $where;
        }
        $codes_hz = self::initPeiShu($codes_hz);
        if($code_type==2){
            $ps_datas = [1=>$codes_hz['ps_1'], 2=>$codes_hz['ps_2']];
        }elseif($code_type==3){
            $ps_datas = [1=>$codes_hz['ps_1'], 2=>$codes_hz['ps_2'], 3=>$codes_hz['ps_3']];
        }else{
            $ps_datas = [1=>$codes_hz['ps_1'], 2=>$codes_hz['ps_2'], 3=>$codes_hz['ps_3'], 4=>$codes_hz['ps_4']];
        }
        if(empty($ps_datas) or empty($codes_hz['ps_sel'])){
            return $where;
        }

        # 全转配数
        #$ps_datas = NumService::fillArrayNums($ps_datas, $code_type);
        # 固定位置
        $fixed_sel_pos = [];
        if(isset($codes_hz['fixed_sel_pos']) && $codes_hz['fixed_sel_pos']){
            $fixed_sel_pos = explode(',', $codes_hz['fixed_sel_pos']);
        }
        $not_fixed_pos = array_values(array_diff([1,2,3,4], $fixed_sel_pos)); # 不定位置
        //p(['where'=>$where, 'codes_hz'=>$codes_hz, 'ps_datas'=>$ps_datas, 'fixed_sel_pos'=>$fixed_sel_pos, 'not_fixed_pos'=>$not_fixed_pos], 0);

        $allPsWhere = ['AND'];
        foreach ($fixed_sel_pos as $fixed_pos){
            if($code_type ==4){
                $allPsWhere[] = ['IN', 'code_'.$fixed_pos, NumService::getNumsArr($ps_datas[$fixed_pos])];
                unset($ps_datas[$fixed_pos]);
            }else{
                $where[] = ['=', 'code_'.$fixed_pos, 'X'];
            }
        }
        $psFilterWhere = ['OR'];
        if($code_type == 4){
            $all_ps_datas = NumService::getPermutations($ps_datas);
            #p(['all_ps_datas'=>$all_ps_datas]);
            # 配数取，条件组装
            foreach ($all_ps_datas as $ps_data){
                #p(['ps_data'=>$ps_data, 'not_fixed_pos'=>$not_fixed_pos]);
                $psFilterSubWhere = ['AND'];
                foreach ($ps_data as $p=>$ps){
                    $psFilterSubWhere[] = ['IN', 'code_'.$not_fixed_pos[$p], NumService::getNumsArr($ps)];
                }
                $psFilterWhere[] = $psFilterSubWhere;
            }
        }else{
            $all_pos = NumService::getCombination($not_fixed_pos, $code_type);
            #p(['all_pos'=>$all_pos], 0);
            foreach ($all_pos as $pos){
                $psFilterSubWhere = ['AND'];
                $pos_datas = $ps_datas;
                #p(['pos_datas'=>$pos_datas, 'pos'=>$pos]);
                foreach ($pos as $pps){
                    $tmpCode = array_shift($pos_datas);
                    $psFilterSubWhere[] = ['IN', 'code_'.$pps, NumService::getNumsArr($tmpCode)];
                }
                $psFilterWhere[] = $psFilterSubWhere;
            }
            //p(['all_pos'=>$all_pos], 0);
        }

        $allPsWhere[] = $psFilterWhere;

        #p([$codes_hz, $where, $ps_datas], 0);
        if($codes_hz['ps_sel'] == NumService::PEI_SHU_EXCLUDE){
            # 配数除，条件组装
            $where = array_merge($where, [['NOT', $allPsWhere]]);
        }elseif($codes_hz['ps_sel'] == NumService::PEI_SHU_OBTAIN){
            # 配数取，条件组装
            $where[] = $allPsWhere;
        }
        //p($where);

        return $where;
    }

    /**
     * 对数条件
     * @param array $codes_hz
     * @param array $where
     * @return array
     */
    private static function getLogWhere($codes_hz=[], &$where=[], $code_type=4){
        if(!isset($codes_hz['log_sel']) OR !in_array($codes_hz['log_sel'], [NumService::LOG_EXCLUDE, NumService::LOG_OBTAIN])){
            return $where;
        }

        if(empty($codes_hz['log_1']) OR empty($codes_hz['log_1']) OR empty($codes_hz['log_1']) or empty($codes_hz['log_sel'])){
            return $where;
        }
        $all_log_datas = array_filter([trim($codes_hz['log_1']), trim($codes_hz['log_2']), trim($codes_hz['log_3'])]);

        # 对数取，条件组装
        $dsFilterWhere = ['OR'];
        foreach ($all_log_datas as $ds_data){
            #p(['ps_data'=>$ps_data, 'not_fixed_pos'=>$not_fixed_pos]);
            $dsTmpWhere = ['AND'];
            $dsTmpWhere[] = ['REGEXP', 'CONCAT(code_1,code_2,code_3, code_4)', $ds_data[0]];
            $dsTmpWhere[] = ['REGEXP', 'CONCAT(code_1,code_2,code_3, code_4)', $ds_data[1]];
            $dsFilterWhere[] = $dsTmpWhere;
        }

        #p([$codes_hz, $where, $ps_datas], 0);
        if($codes_hz['log_sel'] == NumService::LOG_EXCLUDE){
            # 配数除，条件组装
            $where = array_merge($where, [['NOT', $dsFilterWhere]]);
        }elseif($codes_hz['log_sel'] == NumService::LOG_OBTAIN){
            # 配数取，条件组装
            $where[] = $dsFilterWhere;
        }
        #p($where);

        return $where;
    }

    public static function getCombination($not_fixed_pos, $num) {
        $results = [[]];
        for ($i = 0; $i < $num; $i++) {
            $tmp = [];
            foreach ($results as $result) {
                foreach ($not_fixed_pos as $pos) {
                    if (!in_array($pos, $result)) {
                        $copy = $result;
                        $copy[] = $pos;
                        $tmp[] = $copy;
                    }
                }
            }
            $results = $tmp;
        }
        return $results;
    }

    /**
     * @param array $codes_hz
     * @return array
     */
    public static function initPeiShu($codes_hz=[], $code_type=4){
        if(!isset($codes_hz['ps_sel']) OR !in_array($codes_hz['ps_sel'], [NumService::PEI_SHU_EXCLUDE, NumService::PEI_SHU_OBTAIN])){
            return $codes_hz;
        }
        switch($code_type){
            case 4:
                $codes_hz['ps_1'] = ($codes_hz['ps_1'] OR $codes_hz['ps_1'] === '0' ) ? $codes_hz['ps_1'] : '0123456789';
                $codes_hz['ps_2'] = ($codes_hz['ps_2'] OR $codes_hz['ps_2'] === '0' ) ? $codes_hz['ps_2'] : '0123456789';
                $codes_hz['ps_3'] = ($codes_hz['ps_3'] OR $codes_hz['ps_3'] === '0' ) ? $codes_hz['ps_3'] : '0123456789';
                $codes_hz['ps_4'] = ($codes_hz['ps_4'] OR $codes_hz['ps_4'] === '0' ) ? $codes_hz['ps_4'] : '0123456789';
                /*
                $codes_hz['ps_1'] = NumService::getNumsArr($codes_hz['ps_1'] ? : '0123456789');
                $codes_hz['ps_2'] = NumService::getNumsArr($codes_hz['ps_2'] ? : '0123456789');
                $codes_hz['ps_3'] = NumService::getNumsArr($codes_hz['ps_3'] ? : '0123456789');
                $codes_hz['ps_4'] = NumService::getNumsArr($codes_hz['ps_4'] ? : '0123456789');
                */
            break;
        }

        return $codes_hz;
    }

    /**
     * 定位合分
     * @param $codes_hz
     * @param $where
     * @param $code_type
     * @return array
     */
    public static function getHeFenWhereBak($codes_hz, $where, $code_type){
        # 定位合分
        if($code_type == 3 && isset($codes_hz['hefen_pos1']) && isset($codes_hz['hefen1']) && !empty($codes_hz['hefen_pos']) && !empty($codes_hz['hefen1'])){
            # 三定
            $poss = explode(',', $codes_hz['hefen_pos1']);
            $lenHefen = strlen($codes_hz['hefen1']);
            $hf_codes_hzs = [];
            for ($i=0; $i<$lenHefen; $i++){
                if($codes_hz['hefen1'][$i]<=7){
                    $hefenArr = [$codes_hz['hefen1'][$i], $codes_hz['hefen1'][$i] + 10, $codes_hz['hefen'][$i] + 20];
                }else{
                    $hefenArr = [$codes_hz['hefen1'][$i], $codes_hz['hefen1'][$i] + 10];
                }
                $hf_codes_hzs = array_merge($hf_codes_hzs, $hefenArr);
            }
            $codes_str = '';
            foreach ($poss as $pos){
                $codes_str .= '`code_'.$pos.'`' . ' +';
                $where = array_merge($where, [['<>', 'code_'.$pos, 'X']]);
            }
            $codes_str = rtrim(trim($codes_str), '+');
            $where = array_merge($where, [ ['IN', '('.$codes_str.')', $hf_codes_hzs ] ]);
            //$query->andWhere($andWhere);
        }if($code_type == 4 && isset($codes_hz['hefen_pos1']) && isset($codes_hz['hefen1']) && !empty($codes_hz['hefen_pos1']) && !empty($codes_hz['hefen1'])){
            # 四定
            $poss = explode(',', $codes_hz['hefen_pos1']);
            $lenPos = count($poss);
            $hf_codes_hzs = self::getHezhisByHefen($codes_hz['hefen1'], $lenPos);

            $codes_str = '';
            foreach ($poss as $pos){
                $codes_str .= '`code_'.$pos.'`' . ' + ';
                $where = array_merge($where, [['<>', 'code_'.$pos, 'X']]);
            }
            $codes_str = rtrim(trim($codes_str), '+');
            $where = array_merge($where, [ ['IN', '('.$codes_str.')', $hf_codes_hzs ] ]);
            //p([$poss, $codes_hz['hefen'], $hf_codes_hzs]);
        }

        return $where;
    }

    /**
     * 字符串转换成数字
     * @param string $nums_str
     * @return array
     */
    public static function getNumsArr($nums_str=''){
        $ps_len = strlen($nums_str); # 配数2
        $nArr = [];
        for ($ps_count=0; $ps_count<$ps_len; $ps_count++){
            $nArr[] = $nums_str[$ps_count];
        }

        return $nArr;
    }

    /**
     * 筛选位置：单
     * @param array $codes_hz
     * @param array $where
     * @return array
     */
    private static function getPosOddWhere($codes_hz=[], &$where=[]){
        if(empty($codes_hz['odd_pos'])){
            return $where;
        }
        $poses = explode(',', $codes_hz['odd_pos']);
        if($codes_hz['odd_sel'] == NumService::POS_ODD_EXCLUDE){
            # 除
            $posWhere = ['AND'];
            foreach ($poses as $pos){
                $posWhere[] = ['IN', 'code_'.$pos, NumService::$SINGLE_CODES];
            }

            $where[] = ['NOT', $posWhere];
        }elseif ($codes_hz['odd_sel'] == NumService::POS_ODD_OBTAIN){
            # 取
            foreach ($poses as $pos){
                $where[] = ['IN', 'code_'.$pos, NumService::$SINGLE_CODES];
            }
        }

        return $where;
    }

    /**
     * 筛选位置：双
     * @param array $codes_hz
     * @param array $where
     * @return array
     */
    private static function getPosEvenWhere($codes_hz=[], &$where=[]){
        if(empty($codes_hz['even_pos'])){
            return $where;
        }
        $poses = explode(',', $codes_hz['even_pos']);
        if($codes_hz['even_sel'] == NumService::POS_ODD_EXCLUDE){
            # 除
            $posWhere = ['AND'];
            foreach ($poses as $pos){
                $posWhere[] = ['IN', 'code_'.$pos, NumService::$DOUBLE_CODES];
            }

            $where[] = ['NOT', $posWhere];
        }elseif ($codes_hz['even_sel'] == NumService::POS_ODD_OBTAIN){
            # 取
            foreach ($poses as $pos){
                $where[] = ['IN', 'code_'.$pos, NumService::$DOUBLE_CODES];
            }
        }
        #p($where);

        return $where;
    }

    /**
     * 筛选位置：大
     * @param array $codes_hz
     * @param array $where
     * @return array
     */
    private static function getPosBigWhere($codes_hz=[], &$where=[]){
        #p($codes_hz, 0);
        if(empty($codes_hz['big_pos'])){
            return $where;
        }
        $poses = explode(',', $codes_hz['big_pos']);
        if($codes_hz['big_sel'] == NumService::POS_BIG_EXCLUDE){
            # 除
            $posWhere = ['AND'];
            foreach ($poses as $pos){
                $posWhere[] = ['IN', 'code_'.$pos, NumService::$MAX_CODES];
            }

            $where[] = ['NOT', $posWhere];
        }elseif ($codes_hz['big_sel'] == NumService::POS_BIG_OBTAIN){
            # 取
            foreach ($poses as $pos){
                $where[] = ['IN', 'code_'.$pos, NumService::$MAX_CODES];
            }
        }
        #p($where);

        return $where;
    }

    /**
     * 筛选位置：大
     * @param array $codes_hz
     * @param array $where
     * @return array
     */
    private static function getPosSmallWhere($codes_hz=[], &$where=[]){
        #p($codes_hz, 0);
        if(empty($codes_hz['small_pos'])){
            return $where;
        }
        $poses = explode(',', $codes_hz['small_pos']);
        if($codes_hz['small_sel'] == NumService::POS_SMALL_EXCLUDE){
            # 除
            $posWhere = ['AND'];
            foreach ($poses as $pos){
                $posWhere[] = ['IN', 'code_'.$pos, NumService::$MIN_CODES];
            }

            $where[] = ['NOT', $posWhere];
        }elseif ($codes_hz['small_sel'] == NumService::POS_SMALL_OBTAIN){
            # 取
            foreach ($poses as $pos){
                $where[] = ['IN', 'code_'.$pos, NumService::$MIN_CODES];
            }
        }
        #p($where);

        return $where;
    }

    /**
     * 分离数
     * @param array $codes_hz
     * @param array $where
     * @param int $code_type
     * @return array
     */
    private static function getFenLiShuWhere(array $codes_hz=[], array &$where=[], int $code_type=4): array
    {
        #p($codes_hz, 0);
        if(empty($codes_hz['fenli_shu'])){
            return $where;
        }
        //p($codes_hz['fenli_shu']);
        foreach ($codes_hz['fenli_shu'] as $fls){
            $type = $fls['type']; // todo FenLiShu::TYPE_FLS_OPTIONS;
            $code = $fls['code']; # 号码：比如：23456
            $len = strlen($code);
            if($len<2){
                return $where;
            }
            switch ($type){
                case FenLiShu::TYPE_ABCD:
                    $codesArr = NumService::getCodesArise([$code], $type=1, $code_type);
                    $where[] = ['NOT IN', 'code', $codesArr];
                    break;
                case FenLiShu::TYPE_ABCX:
                    if(false && $code_type==3){
                        $codesArr = NumService::getCodesArise([$code]);
                        //p($codesArr);
                    }else{
                        $codesArr = \backend\service\numbers\CodeGenerateService::getCode($code, $len<=2?2:3);//p(['codesArr'=>$codesArr]);
                        if($len==2){
                            $where[] = ['NOT IN', 'CONCAT(code_1,code_2)', $codesArr];
                            $where[] = ['NOT IN', 'CONCAT(code_1,code_3)', $codesArr];
                            $where[] = ['NOT IN', 'CONCAT(code_2,code_3)', $codesArr];
                        }else{
                            $where[] = ['NOT IN', 'CONCAT(code_1,code_2,code_3)', $codesArr];
                        }
                    }
                    //p([$code, $len, 'codeArr'=>$codesArr]);
                    break;
                case FenLiShu::TYPE_ABXD:
                    $codesArr = \backend\service\numbers\CodeGenerateService::getCode($code, $len<=2?2:3);//p(['codesArr'=>$codesArr]);
                    if($len==2){
                        $where[] = ['NOT IN', 'CONCAT(code_1,code_2)', $codesArr];
                        $where[] = ['NOT IN', 'CONCAT(code_1,code_4)', $codesArr];
                        $where[] = ['NOT IN', 'CONCAT(code_2,code_4)', $codesArr];
                    }else {
                        $where[] = ['NOT IN', 'CONCAT(code_1,code_2,code_4)', $codesArr];
                    }
                    break;
                case FenLiShu::TYPE_AXCD:
                    $codesArr = \backend\service\numbers\CodeGenerateService::getCode($code, $len<=2?2:3);//p(['codesArr'=>$codesArr]);
                    if($len==2){
                        $where[] = ['NOT IN', 'CONCAT(code_1,code_3)', $codesArr];
                        $where[] = ['NOT IN', 'CONCAT(code_1,code_4)', $codesArr];
                        $where[] = ['NOT IN', 'CONCAT(code_3,code_4)', $codesArr];
                    }else {
                        $where[] = ['NOT IN', 'CONCAT(code_1,code_3,code_4)', $codesArr];
                    }
                    break;
                case FenLiShu::TYPE_XBCD:
                    $codesArr = \backend\service\numbers\CodeGenerateService::getCode($code, $len<=2?2:3);//p(['codesArr'=>$codesArr]);
                    if($len==2){
                        $where[] = ['NOT IN', 'CONCAT(code_2,code_3)', $codesArr];
                        $where[] = ['NOT IN', 'CONCAT(code_2,code_4)', $codesArr];
                        $where[] = ['NOT IN', 'CONCAT(code_3,code_4)', $codesArr];
                    }else {
                        $where[] = ['NOT IN', 'CONCAT(code_2,code_3,code_4)', $codesArr];
                    }
                    break;
                case FenLiShu::TYPE_AXXD:
                    $codesArr = \backend\service\numbers\CodeGenerateService::getCode($code, 2);//p(['codesArr'=>$codesArr]);
                    $where[] = ['NOT IN', 'CONCAT(code_1,code_4)', $codesArr];
                    break;
                case FenLiShu::TYPE_XBCX:
                    $codesArr = \backend\service\numbers\CodeGenerateService::getCode($code, 2);//p(['codesArr'=>$codesArr]);
                    $where[] = ['NOT IN', 'CONCAT(code_2,code_3)', $codesArr];
                    break;
                case FenLiShu::TYPE_ABXX:
                    $codesArr = \backend\service\numbers\CodeGenerateService::getCode($code, 2);//p(['codesArr'=>$codesArr]);
                    $where[] = ['NOT IN', 'CONCAT(code_1,code_2)', $codesArr];
                    break;
                case FenLiShu::TYPE_AXCX:
                    $codesArr = \backend\service\numbers\CodeGenerateService::getCode($code, 2);//p(['codesArr'=>$codesArr]);
                    $where[] = ['NOT IN', 'CONCAT(code_1,code_3)', $codesArr];
                    break;
                case FenLiShu::TYPE_XBXD:
                    $codesArr = \backend\service\numbers\CodeGenerateService::getCode($code, 2);//p(['codesArr'=>$codesArr]);
                    $where[] = ['NOT IN', 'CONCAT(code_2,code_4)', $codesArr];
                    break;
                case FenLiShu::TYPE_XXCD:
                    $codesArr = \backend\service\numbers\CodeGenerateService::getCode($code, 2);//p(['codesArr'=>$codesArr]);
                    $where[] = ['NOT IN', 'CONCAT(code_3,code_4)', $codesArr];
                    break;
                default:
                    break;
            }
        }
        //p([$codes_hz['fenli_shu'], $where, $codesArr]);

        return $where;
    }

    /**
     * @desc 获取过滤位置 by code 目前注意针对导入之后再过滤的情况
     * @param string $code
     * @param string $split
     * @return array
     */
    public static function getFilterPosByCode($code='', $split=','){
        $codeArr = explode($split, $code);
        $poses = [];
        foreach ($codeArr as $k=>$n){
            if($n != 'X') $poses[] = $k+1;
        }
        return $poses;
    }

    /**
     * @param $hefens 1234  合分值:34569
     * @param int $lenHefen 位置个数
     * @param $code_type 1一定2二定3三定4四定
     * @return array
     */
    public static function getHezhisByHefen($hefens, $lenPos = 4, $code_type = 4){
        $hezhis = [];
        //p([$hefens, $lenPos],0);

        $lenHefen = strlen($hefens);
        for ($i=0; $i<$lenHefen; $i++){
            $hefensZhi = $hefens[$i];
            if($lenPos == 4){
                if($hefensZhi<=6){
                    $hefenArr = [$hefensZhi, $hefensZhi + 10, $hefensZhi + 20, $hefensZhi + 30];
                }else{
                    $hefenArr = [$hefensZhi, $hefensZhi + 10, $hefensZhi + 20];
                }
            }elseif ($lenPos == 3){
                if($hefensZhi<=7){
                    $hefenArr = [$hefensZhi, $hefensZhi + 10, $hefensZhi + 20];
                }else{
                    $hefenArr = [$hefensZhi, $hefensZhi + 10];
                }
            }elseif ($lenPos == 2){
                if($hefensZhi<=8){
                    $hefenArr = [$hefensZhi, $hefens[$i] + 10];
                }else{
                    $hefenArr = [$hefensZhi];
                }
            }
            $hezhis = array_merge($hezhis, $hefenArr);
        }

        return $hezhis;
    }

    /**
     * 定位置：千、百、十、个
     * @param $codes_hz
     * @param $where
     * @param $code_type
     * @return array
     */
    public static function getFixedPostionWhere($codes_hz, &$where, $code_type=4){
        $codes_hz = \backend\service\NumService::getHefenInitData($codes_hz, $code_type);
        if(
            (empty($codes_hz['p1']) && empty($codes_hz['p2']) && empty($codes_hz['p3']) && empty($codes_hz['p4'])) &&
            (!isset($codes_hz['p1']) && !isset($codes_hz['p2']) && !isset($codes_hz['p3']) && !isset($codes_hz['p4']))
        ){
            return $where;
        }

        $fixedPosWhere = ['AND'];
        # 第1位
        if(isset($codes_hz['p1']) && $codes_hz['p1'] !== ''){
            $p1_codes = self::getCodesArrByStr($codes_hz['p1']);
            $fixedPosWhere = array_merge($fixedPosWhere, [ ['IN', 'code_1', $p1_codes] ]);
        }

        # 第2位
        if(isset($codes_hz['p2']) && $codes_hz['p2'] !== ''){
            $p2_codes = self::getCodesArrByStr($codes_hz['p2']);
            $fixedPosWhere = array_merge($fixedPosWhere, [ ['IN', 'code_2', $p2_codes] ]);
        }

        # 第3位
        if(isset($codes_hz['p3']) && $codes_hz['p3'] !== ''){
            $p3_codes = self::getCodesArrByStr($codes_hz['p3']);
            $fixedPosWhere = array_merge($fixedPosWhere, [ ['IN', 'code_3', $p3_codes] ]);
        }

        # 第4位
        if(isset($codes_hz['p4']) && $codes_hz['p4'] !== ''){
            $p4_codes = self::getCodesArrByStr($codes_hz['p4']);
            $fixedPosWhere = array_merge($fixedPosWhere, [ ['IN', 'code_4', $p4_codes] ]);
        }

        # 定位合分
        if($codes_hz['fixed_pos_sel'] == NumService::HE_FEN_EXCLUDE){
            # 配数除，条件组装
            $where = array_merge($where, [['NOT', $fixedPosWhere]]);
        }else{
            # 配数取，条件组装
            $where[] = $fixedPosWhere;
        }
        //p($where);

        return $where;
    }

    /**
     * 定位合分
     * @param $codes_hz
     * @param $where
     * @param $code_type
     * @return array
     */
    public static function getExcludeCodesWhere($codes_hz, &$where, $code_type=4){
        $codes_hz = \backend\service\NumService::getHefenInitData($codes_hz, $code_type);
        if($codes_hz['exclude_codes'] !==0 && $codes_hz['exclude_codes'] !=='0' && empty($codes_hz['exclude_codes'])){
            return $where;
        }

        $allExcludeWhere = ['AND'];
        $exclude_codes = [];
        $exclude_str_codes = trim($codes_hz['exclude_codes']);
        for($i=0; $i<strlen($exclude_str_codes); $i++){
            $exclude_codes[] = $exclude_str_codes[$i];
        }
        foreach (NumService::DW_POSES as $pos){
            $allExcludeWhere[] = ['NOT IN', 'code_'.$pos, $exclude_codes];
        }

        $where[] = $allExcludeWhere;

        return $where;
    }

    /**
     * 定位合分
     * @param $codes_hz
     * @param $where
     * @param $code_type
     * @return array
     */
    public static function getHeFenWhere($codes_hz, &$where, $code_type=4){
        $codes_hz = \backend\service\NumService::getHefenInitData($codes_hz, $code_type);
        if($codes_hz['hfDatas'] !==0 && $codes_hz['hfDatas'] !=='0' && empty($codes_hz['hfDatas'])){
            return $where;
        }

        $allHfWhere = ['AND'];
        foreach ($codes_hz['hfDatas'] as $hfData){
            $allHfSubWhere = ['OR'];
            $positions_str = '(`code_'.implode('` + `code_', $hfData['pos']).'`)';
            foreach ($hfData['hefens'] as $hefen){
                $allHfSubWhere[] = ['IN', $positions_str, $hefen];
            }
            if($code_type != 4){
                foreach ($hfData['pos'] as $p){
                    $allHfWhere[] = ['<>', 'code_'.$p, 'X'];
                }
            }
            $allHfWhere[] = $allHfSubWhere;
        }

        # 定位合分
        if($codes_hz['fixed_pos_hefen_sel'] == NumService::HE_FEN_EXCLUDE){
            # 配数除，条件组装
            $where = array_merge($where, [['NOT', $allHfWhere]]);
        }elseif($codes_hz['fixed_pos_hefen_sel'] == NumService::HE_FEN_OBTAIN){
            # 配数取，条件组装
            $where[] = $allHfWhere;
        }

        return $where;
    }

    /**
     * 两合上1
     * @param $codes_hz
     * @param $where
     * @param $code_type
     * @return array
     */
    public static function hsAndCfTwoFoneWhere($codes_hz, &$where, $code_type=4): array
    {
        if(empty($codes_hz['hsAndCf_twoFone'])){
            return $where;
        }

        $hCode = $codes_hz['hsAndCf_twoFone'];
        $hCodeArr = Code::codeStringToArray($hCode); # 合分数组
        $hfArr = [];
        foreach ($hCodeArr as $filterNum){
            $hfArr = array_merge($hfArr, [$filterNum, $filterNum+10, $filterNum+20, $filterNum+30]);
        }
        //p([$params, $historyKjData, $hCode, $hCodeArr, $hfArr]);
        $cfWhere = ['OR'];

        # 两数合
        $orWhere1 = ['OR'];
        foreach (NumService::TWO_NUM_POS as $poss){
            $orWhere1[] = ['IN', "(`code_".$poss[0]."` + `code_".$poss[1]."`)", $hfArr];
        }
        $cfWhere[] = $orWhere1;

        $orWhere2 = [
            'OR',
            ['IN', "code_1", $hCodeArr],
            ['IN', "code_2", $hCodeArr],
            ['IN', "code_3", $hCodeArr],
            ['IN', "code_4", $hCodeArr],
        ];
        $cfWhere[] = $orWhere2;
        $where[] = $cfWhere;

        return $where;
    }

    /**
     * 复式
     * @param $codes_hz
     * @param $where
     * @param $code_type
     * @return array
     */
    public static function getFuShiWhere($codes_hz, &$where, $code_type=4){
        if($codes_hz['fushiCodes'] !==0 && $codes_hz['fushiCodes'] !=='0' && empty($codes_hz['fushiCodes'])){
            return $where;
        }

        $allFsWhere = ['AND'];
        $fsCodes = [];
        for($i=0; $i<strlen($codes_hz['fushiCodes']); $i++){
            $fsCodes[] = $codes_hz['fushiCodes'][$i];
        }
        $fsCodes[] = 'X';
        $allFsWhere[] = ['IN', 'code_1', $fsCodes];
        $allFsWhere[] = ['IN', 'code_2', $fsCodes];
        $allFsWhere[] = ['IN', 'code_3', $fsCodes];
        $allFsWhere[] = ['IN', 'code_4', $fsCodes];

        # 定位合分
        if($codes_hz['fushi_sel'] == NumService::EXCLUDE){
            # 复式除，条件组装
            $where = array_merge($where, [['NOT', $allFsWhere]]);
        }elseif($codes_hz['fushi_sel'] == NumService::OBTAIN){
            # 取，条件组装
            $where[] = $allFsWhere;
        }

        return $where;
    }

    /**
     * @param array $codes_hz
     * @param int $code_type
     */
    private static function getHefenInitData(array $codes_hz=[], $code_type=4): array
    {
        $hfData = [];
        if(!empty($codes_hz['hefen_pos1']) && isset($codes_hz['hefen1'])){
            $hfData[] = ['pos'=>explode(',', $codes_hz['hefen_pos1']), 'hefens'=>NumService::getHefens($codes_hz['hefen1'])];
            unset($codes_hz['hefen_pos1'], $codes_hz['hefen1']);
        }
        if(!empty($codes_hz['hefen_pos2']) && isset($codes_hz['hefen2'])){
            $hfData[] = ['pos'=>explode(',', $codes_hz['hefen_pos2']), 'hefens'=>NumService::getHefens($codes_hz['hefen2'])];
            unset($codes_hz['hefen_pos2'], $codes_hz['hefen2']);
        }
        if(!empty($codes_hz['hefen_pos3']) && isset($codes_hz['hefen3'])){
            $hfData[] = ['pos'=>explode(',', $codes_hz['hefen_pos3']), 'hefens'=>NumService::getHefens($codes_hz['hefen3'])];
            unset($codes_hz['hefen_pos3'], $codes_hz['hefen3']);
        }
        if(!empty($codes_hz['hefen_pos4']) && isset($codes_hz['hefen4'])){
            $hfData[] = ['pos'=>explode(',', $codes_hz['hefen_pos4']), 'hefens'=>NumService::getHefens($codes_hz['hefen4'])];
            unset($codes_hz['hefen_pos4'], $codes_hz['hefen4']);
        }
        $codes_hz['hfDatas'] = $hfData;

        return $codes_hz;
    }

    private static function getHefens($hefen_str='', $code_type=4){
        $hefenArr = [];
        if(empty($hefen_str) && $hefen_str !== '0' && $hefen_str !==0){
            return $hefenArr;
        }
        for($i=0; $i<strlen($hefen_str); $i++){
            $hf = (int)$hefen_str[$i];
            $hefenArr[] = [$hf, $hf+10, $hf+20, $hf+30];
        }

        return $hefenArr;
    }

    /**
     * @desc 根据codestr转换为array
     * @param $codes_str 34567
     * @return array [3,4,5,6,7]
     */
    public static function getCodesArrByStr($codes_str){
        $strlen = strlen($codes_str);
        $codes_Arr = [];
        for ($i=0; $i<$strlen; $i++){
            $codes_Arr[] = (string)$codes_str[$i];
        }

        return $codes_Arr;
    }

    /**
     * @desc 快选计划描述转换
     * @param $hz_Arr
     * @return string
     */
    public static function getDescByKuaixuan($hz_Arr, $plan_id=''){
        //p($hz_Arr,0);
        # 双重:type_2、三重:type_3、四重:type_4、双双重:type_22、两兄弟:type_2b、三兄弟:type_3b、四兄弟:type_4b
        //$desc = '[快选] ';
        $desc = '';
        $desc_detail = '';
        $filter0 = []; # 除
        $filter1 = []; # 取
        $filter2 = []; # 和值
        $filter3 = []; # 上奖
        $filter4 = []; # 上奖除
        $filter5 = []; # 和值除
        $filter6 = []; # 类型取
        $filter7 = []; # 类型除
        $filter8 = []; # 合分取
        $filter9 = []; # 合分除
        $filter10 = []; # 号码组
        $filter11 = []; # 定位 含

        $UserSysPlans = '';
        if(!empty($plan_id)){
            $UserSysPlans = UserSysPlans::findOne($plan_id);
        }
        # {"get_types":["1","2"],"remove_types":["4","5"],"get_hzs":["7","8","10"],"remove_hzs":["12","13","14"],"get_arises":"123","remove_arises":"456"}
        # 0.1、上奖取
        if(isset($hz_Arr['arise']) OR isset($hz_Arr['get_arises'])){
            if(isset($hz_Arr['get_arises'])) $hz_Arr['arise'] = $hz_Arr['get_arises'];
            if(isset($hz_Arr['arise'])) $filter3['arise'] = $hz_Arr['arise'];// else $filter0['arise'] = 0;
        }
        # 0.2、上奖除 - 新
        if(isset($hz_Arr['remove_arises'])){
            $filter4['remove_arises'] = $hz_Arr['remove_arises'];// else $filter0['arise'] = 0;
        }

        # 1、双重
        if(isset($hz_Arr['type_2'])){
            if($hz_Arr['type_2'] == 1) $filter1['type_2'] = 1; else $filter0['type_2'] = 0;
        }
        # 2、三重
        if(isset($hz_Arr['type_3'])){
            if($hz_Arr['type_3'] == 1) $filter1['type_3'] = 1; else $filter0['type_3'] = 0;
        }
        # 3、四重
        if(isset($hz_Arr['type_4'])){
            if($hz_Arr['type_4'] == 1) $filter1['type_4'] = 1; else $filter0['type_4'] = 0;
        }
        # 4、双双重
        if(isset($hz_Arr['type_22'])){
            if($hz_Arr['type_22'] == 1) $filter1['type_22'] = 1; else $filter0['type_22'] = 0;
        }
        # 5、两兄弟
        if(isset($hz_Arr['type_2b'])){
            if($hz_Arr['type_2b'] == 1) $filter1['type_2b'] = 1; else $filter0['type_2b'] = 0;
        }
        # 6、三兄弟
        if(isset($hz_Arr['type_3b'])){
            if($hz_Arr['type_3b'] == 1) $filter1['type_3b'] = 1; else $filter0['type_3b'] = 0;
        }
        # 7.1、四兄弟
        if(isset($hz_Arr['type_4b'])){
            if($hz_Arr['type_4b'] == 1) $filter1['type_4b'] = 1; else $filter0['type_4b'] = 0;
        }
        # 7.2.1、对数
        if(isset($hz_Arr['type_log'])){
            if($hz_Arr['type_log'] == 1) $filter1['type_log'] = 1; else $filter0['type_log'] = 0;
        }
        # 7.2.2、双对数
        if(isset($hz_Arr['type_2log'])){
            if($hz_Arr['type_2log'] == 1) $filter1['type_2log'] = 1; else $filter0['type_2log'] = 0;
        }
        # 7.3、三现:双重+两兄
        if(isset($hz_Arr['type_3n_2b'])){
            if($hz_Arr['type_3n_2b'] == 1) $filter1['type_3n_2b'] = 1; else $filter0['type_3n_2b'] = 0;
        }

        # 8.1、和值
        if((isset($hz_Arr['hz']) && !empty($hz_Arr['hz'])) OR (isset($hz_Arr['get_hzs']) && !empty($hz_Arr['get_hzs']))){
            if(isset($hz_Arr['get_hzs'])) $hz_Arr['hz'] = $hz_Arr['get_hzs'];
            $filter2['hz'] = implode(',',$hz_Arr['hz']);
        }
        # 8.2、和值除 - 新
        if(isset($hz_Arr['remove_hzs']) && !empty($hz_Arr['remove_hzs'])){
            $filter5['hz'] = implode(',',$hz_Arr['remove_hzs']);
        }

        # 9、四单
        if(isset($hz_Arr['type_4d'])){
            if($hz_Arr['type_4d'] == 1) $filter1['type_4d'] = 1; else $filter0['type_4d'] = 0;
        }
        # 10、四双
        if(isset($hz_Arr['type_4s'])){
            if($hz_Arr['type_4s'] == 1) $filter1['type_4s'] = 1; else $filter0['type_4s'] = 0;
        }
        # 11.1、类型取 - 新  双重、三重、四重、双双重
        if(isset($hz_Arr['get_types'])){
            $filter6['get_types'] = $hz_Arr['get_types'];// else $filter0['arise'] = 0;
        }
        # 14.1、单双类型取
        if(!empty($hz_Arr['type_4ds'])){
            $filter6['type_4ds'] = $hz_Arr['type_4ds'];
        }

        # 11.2、类型除 - 新
        if(isset($hz_Arr['remove_types'])){
            $filter7['remove_types'] = $hz_Arr['remove_types'];
        }
        # 12.1、号码组1
        if(isset($hz_Arr['code1'])){
            $filter10['code1'] = $hz_Arr['code1'];
        }
        # 12.1、号码组2
        if(isset($hz_Arr['code2'])){
            $filter10['code2'] = $hz_Arr['code2'];
        }
        # 12.1、当前号码组
        if(isset($hz_Arr['status_val'])){
            $filter10['status_val'] = $hz_Arr['status_val'];
        }
        # 13 二、三、四定 含
        if(isset($hz_Arr['arise_in_sel']) && isset($hz_Arr['arise_in'])){
            $filter11 = ['sel'=>$hz_Arr['arise_in_sel'], 'val'=>$hz_Arr['arise_in']];
        }

        # 当前遗漏
        //if(isset($hz_Arr['current_miss'])){
        if(isset($hz_Arr['bet_while_miss'])){
            $desc .= '[遗漏:'.$hz_Arr['bet_while_miss'].'投,当前:'.(int)$hz_Arr['current_miss'].'] ';
        }

        # 合分 - 三定
        if(isset($hz_Arr['hefen_pos']) && isset($hz_Arr['hefen'])){
            if(isset($hz_Arr['hefen'])) $filter8['hefen'] = 1; else $filter9['hefen'] = 0;
        }
        # 合分 - 四定
        if(isset($hz_Arr['hefen'])){
            if(isset($hz_Arr['hefen'])) $filter8['hefen'] = 1; else $filter9['hefen'] = 0;
        }
        if(!empty($filter10['code1'])){
            $desc .= '组1:'.$filter10['code1'].' ';
        }
        if(!empty($filter10['code2'])){
            $desc .= '组2:'.$filter10['code2'].' ';
        }
        if(!empty($filter10['status_val'])){
            $desc .= '当前:组'.$filter10['status_val'].' ';
        }

        # 导入方式，号码轮换号码组
        if(!empty($hz_Arr['change_per'])){
            $desc .= '组:'.(int)$hz_Arr['turn_key'].'. ';
        }

        # 号码
        if(isset($hz_Arr['codes'])){
            $desc .= '号码:'.$hz_Arr['codes'];
        }

        $typesArr = self::getNameByCodesType();
        #和值取
        if(!empty($filter2['hz'])){
            //$desc .= '和值:'.yii\helpers\BaseStringHelper::truncate($filter2['hz'],10).' ';
            $desc .= '和值:'.$filter2['hz'].' ';
        }
        # 和值除
        if(!empty($filter5['hz'])){
            //$desc .= '和值:'.yii\helpers\BaseStringHelper::truncate($filter2['hz'],10).' ';
            $desc .= '和值除:'.$filter5['hz'].' ';
        }
        # 14.2、单双类型 - 1122,2121,2222 等
        if(!empty($hz_Arr['type_ds_details'])){
            $desc .= '类型:'.implode(',',$hz_Arr['type_ds_details']);
        }
        if(!empty($filter1)){
            $desc .= '取:';
            foreach ($filter1 as $key1=>$v1){
                if($key1 == 'type_4ds'){
                    $desc .= $typesArr[$key1.'_'.$v1].'、';
                }else{
                    $desc .= $typesArr[$key1].'、';
                }
            }
            $desc = trim($desc, '、').' ';
        }

        if(isset($hz_Arr['p1']) && $hz_Arr['p1'] !== ''){
            $desc .= '千'.$hz_Arr['p1'];
        }
        if(isset($hz_Arr['p2']) && $hz_Arr['p2'] !== ''){
            $desc .= ' 百'.$hz_Arr['p2'];
        }
        if(isset($hz_Arr['p3']) && $hz_Arr['p3'] !== ''){
            $desc .= ' 十'.$hz_Arr['p3'];
        }
        if(isset($hz_Arr['p4']) && $hz_Arr['p4'] !== ''){
            $desc .= ' 个'.$hz_Arr['p4'];
        }
        if(isset($hz_Arr['p5']) && $hz_Arr['p5'] !== ''){
            $desc .= ' 五'.$hz_Arr['p5'];
        }
        # 配数
        if(isset($hz_Arr['ps_sel']) && $hz_Arr['ps_sel']){
            $desc .= $hz_Arr['ps_sel']==NumService::PEI_SHU_OBTAIN ? ' 配数取:' : '配数除:';
            if(isset($hz_Arr['ps_1']) && $hz_Arr['ps_1'] !== ''){
                $desc .= '配数1:'.$hz_Arr['ps_1'];
            }
            if(isset($hz_Arr['ps_2']) && $hz_Arr['ps_2'] !== ''){
                $desc .= '配数2:'.$hz_Arr['ps_2'];
            }
            if(isset($hz_Arr['ps_3']) && $hz_Arr['ps_3'] !== ''){
                $desc .= '配数3:'.$hz_Arr['ps_3'];
            }
            if(isset($hz_Arr['ps_4']) && $hz_Arr['ps_4'] !== ''){
                $desc .= '配数4:'.$hz_Arr['ps_4'];
            }
        }
        if(!empty($hz_Arr['fixed_sel_pos'])){
            $desc .= ' 定位置:'.$hz_Arr['fixed_sel_pos'];
        }

        # 对数 除、取
        if(isset($hz_Arr['log_sel']) && $hz_Arr['log_sel']){
            $desc .= $hz_Arr['log_sel']==NumService::PEI_SHU_OBTAIN ? ' 对数取:' : '对数除:';
            if(isset($hz_Arr['log_1']) && $hz_Arr['log_1'] !== ''){
                $desc .= '对数1:'.$hz_Arr['log_1'];
            }
            if(isset($hz_Arr['log_2']) && $hz_Arr['log_2'] !== ''){
                $desc .= '对数2:'.$hz_Arr['log_2'];
            }
            if(isset($hz_Arr['log_3']) && $hz_Arr['log_3'] !== ''){
                $desc .= '对数3:'.$hz_Arr['log_3'];
            }
        }

        # 筛选位置：单
        if(!empty($hz_Arr['odd_sel']) && $hz_Arr['odd_pos']){
            $desc .= $hz_Arr['odd_sel']==NumService::POS_ODD_OBTAIN ? ' 取单:' : '除单:';
            $desc .= $hz_Arr['odd_pos'].'位';
        }
        # 筛选位置：双
        if(!empty($hz_Arr['even_sel']) && $hz_Arr['even_pos']){
            $desc .= $hz_Arr['even_sel']==NumService::POS_ODD_OBTAIN ? ' 取双:' : '除双:';
            $desc .= $hz_Arr['even_pos'].'位';
        }
        # 筛选位置：大
        if(!empty($hz_Arr['big_sel']) && $hz_Arr['big_pos']){
            $desc .= $hz_Arr['big_sel']==NumService::POS_BIG_OBTAIN ? ' 取大:' : '除大:';
            $desc .= $hz_Arr['big_pos'].'位';
        }
        # 筛选位置：双
        if(!empty($hz_Arr['small_sel']) && $hz_Arr['small_pos']){
            $desc .= $hz_Arr['small_sel']==NumService::POS_ODD_OBTAIN ? ' 取小:' : '除小:';
            $desc .= $hz_Arr['small_pos'].'位';
        }
        # 分离数
        if(!empty($hz_Arr['fenli_shu'])){
            $desc .= ' 分离:';
            foreach ($hz_Arr['fenli_shu'] as $fls){
                $desc .= FenLiShu::TYPE_FLS_OPTIONS[$fls['type']].'_'.$fls['code'].';';
            }
        }

        # 不定位合分:两数、三数
        if(isset($hz_Arr['no_fix_hefen_pos']) && isset($hz_Arr['no_fix_hefen'])){ # no_fix_hefen_pos=1:两数、no_fix_hefen_pos=2:三数
            if($hz_Arr['no_fix_hefen_pos'] == 2){
                $desc .= ' 三不定合:'.$hz_Arr['no_fix_hefen'];
            }else{
                $desc .= ' 两不定合:'.$hz_Arr['no_fix_hefen'];
            }
        }

        if(!empty($filter8)){
            if(isset($hz_Arr['hefen_pos'])){
                $desc .= ' 合分取[位:'.$hz_Arr['hefen_pos'] . ' 合分:'.$hz_Arr['hefen'].']';
            }else{
                $desc .= ' 合分取[位:1,2,3,4 '. '合分:'.$hz_Arr['hefen'].']';
            }
        }

        if(!empty($hz_Arr['arise_in'])){
            $desc .= ' 含:'.$hz_Arr['arise_in'];
        }
        if(!empty($hz_Arr['exclude_codes'])){
            $desc .= ' 排除:'.$hz_Arr['exclude_codes'];
        }

        if(!empty($filter11)){
            $desc .= ' 定位含:';
            $desc .= $filter11['sel'] == 1 ? '除' : '取';
            $desc .= $filter11['val'];
        }

        # 上奖取
        if(!empty($filter3)){
            $desc .= ' 上奖:';
            foreach ($filter3 as $key3=>$v3){
                $desc .= $v3.',';
            }
            $desc = trim($desc, ',').' ';
        }
        # 上奖除
        if(!empty($filter4)){
            $desc .= '上奖除:';
            foreach ($filter4 as $key4=>$v4){
                $desc .= $v4.'、';
            }
            $desc = trim($desc, ',').' ';
        }

        if(!empty($filter0)){
            $desc .= ' 除:';
            foreach ($filter0 as $key0=>$v0){
                $desc .= $typesArr[$key0].',';
            }
            $desc = trim($desc, ',').' ';
        }

        if($UserSysPlans && in_array($UserSysPlans->plan_type, UserSysPlans::$A_x_arise_B_y_arise_bet_B_types)){ # A出x次B出y次投B
            # {"arise_A_times":3,"arise_B_times":1,"current_arise_A_times":4,"filters":[],"filter_dates":[],"filter_qihaos":[]}
            $yl_desc = $hz_Arr['current_yl_desc'];// = 'A-A-A-B-A';
            if(!$hz_Arr['current_yl_desc']){
                $yl_desc = '---';
            }
            $desc .= '[A出:'.$hz_Arr['arise_A_times'].'次当前:'.(int)$hz_Arr['current_arise_A_times'].'次&B出:'.$hz_Arr['arise_B_times'].'次,遗漏:'.$yl_desc.']';
        }

        if($UserSysPlans && in_array($UserSysPlans->plan_type, [SscDataService::PLAN_TYPE_AREA_SINGLES_BET, SscDataService::PLAN_TYPE_LOSS_MONEY_BET_SINGLES])){
            if(!empty($hz_Arr['area_loss_start'])){
                $type_desc = '【条件:亏'.$hz_Arr['area_loss_start'].'元起投】';
                $type_desc .= (in_array($hz_Arr['betStatus'], [SscDataService::PLAN_BET_STATUS_INIT, SscDataService::PLAN_BET_STATUS_WAIT]))? ',当前'.($hz_Arr['current_area_profits']??0.00).'元' : $hz_Arr['start_loss'].'触发启动';
                $type_desc .= ' '.($hz_Arr['area_msg']??'');
            }else{
                $type_desc = '【条件:'.$hz_Arr['area_all_qishus'].'漏'.$hz_Arr['area_yl_qishus'].'期';
                $type_desc .= ($hz_Arr['areaBetStatus']==0)? ',当前'.($hz_Arr['area_arise_qishus']??0.00).'期' : '';
            }

            $type_desc .= '【区间止盈:'.$hz_Arr['area_profits'].'止损:'.$hz_Arr['area_loss'].'】';

            $type_desc .= isset($hz_Arr['current_area_profits']) ? '，当前区间盈利:'.number_format($hz_Arr['current_area_profits'], 2) : ''; # 当前遗漏
            $type_desc .= ($hz_Arr['betStatus']==1 OR $hz_Arr['areaBetStatus']==1) ? " 起投期:".($hz_Arr['start_qihao']??$hz_Arr['filters']['start_qihao']) : '，监控开始日期：'.$hz_Arr['filters']['start_qihao']; # 当前遗漏

            $desc = $type_desc . ' '. $desc;
        }
        # 类型取
        if(!empty($filter6) OR !empty($filter7)){
            $codeTypes1 = UserSysPlansService::getCodeTypes($flag = 1);
            $codeTypes2 = UserSysPlansService::getCodeTypes($flag = 2);
            //p([$hz_Arr['get_types'], $filter6['get_types']]);
            if(!empty($filter6)){
                $desc .= '类型取:';
                if(!empty($filter6['get_types'])){
                    foreach ($filter6['get_types'] as $key6=>$v6){
                        $desc .= $codeTypes1[$v6].',';
                    }
                }
                if(!empty($filter6['type_4ds'])){
                    foreach ($filter6['type_4ds'] as $key6=>$v6){
                        $desc .= $codeTypes2[$v6].',';
                    }
                }
                $desc = trim($desc, ',').' ';
            }
            # 上奖除
            if(!empty($filter7)){
                $desc .= '类型除:';
                foreach ($filter7['remove_types'] as $key7=>$v7){
                    $desc .= $codeTypes1[$v7].',';
                }
                $desc = trim($desc, ',').'  ';
            }
        }
        if(isset($hz_Arr['history_max_miss'])) {
            $history_max_miss = $hz_Arr['history_max_miss'];
            $desc .= '最大遗漏:'.$history_max_miss.' ';
        }

        # 批量模拟过滤
        if(isset($hz_Arr['filters']['filter_type']) && $hz_Arr['filters']['filter_type']){
            $filters_data = $hz_Arr['filters'];
            $filter_poses = $filters_data['filter_poses'] ? : [1,2,3,4];
            $desc .= '过滤同位前'.(int)$filters_data['filter_nums'].'期，过滤位置：'.implode(',',NumService::getDescStrByPoses($filter_poses)).''.'，模拟最近'.(int)$filters_data['test_period_days'].'天数据 ';
        }
        if(isset($hz_Arr['change_turn_pos']) && $hz_Arr['change_turn_pos']>0){
            $desc .= '轮换位置:'.NumService::$pos_to_desc[$hz_Arr['change_turn_pos']]; # 位置的号码数决定下次轮换第几组
        }

        # 动态过滤
        if(isset($hz_Arr['is_filter_dynamic']) && $hz_Arr['is_filter_dynamic'] && !empty($hz_Arr['filter_dynamic_types'])){
            $desc .= "动态过滤:";
            foreach ($hz_Arr['filter_dynamic_types'] as $filter_dynamic_type){
                $desc .= NumService::$filter_dynamic_types[$filter_dynamic_type].'、';
            }
            $desc = rtrim($desc, '、');
        }
        if(!empty($UserSysPlans) && $UserSysPlans->buy_type == 0){
            $desc .= ' 【反买】';
        }

        return $desc;
    }

    /**
     * @desc 前xx期号码
     * @param int $num
     * @param int $lottery_type
     * @return array
     */
    public static function getBeforeKjCodesFromSite($num=0, $lottery_type=DEFAULT_LOTTERY_TYPE){

        $data = [];
        if($lottery_type == DEFAULT_LOTTERY_TYPE){
            $data = Lucky5::getBeforeKjCodesFromSite($num);
        }else{

        }
        return $data;
    }

    /**
     * 动态过滤
     * @param object
     * @return array
     */
    public static function getBeforeKjCodesDynamic(object $plan, $filter_dynamic_types=[]){
        $lottery_type = $plan->lottery_type;
        $playway = $plan->playway;
        $query = Num4Type::find()->select(['code', 'code_type'])
            ->andWhere(['=', 'code_type', $playway+1]);
        $NumTypes = $query->asArray()->all();
        $allCodes = ArrayHelper::getColumn($NumTypes, 'code');
        $hzArr = yii\helpers\Json::decode($plan->hz_Arr);
        $filter_dynamic_types = $filter_dynamic_types ? :$hzArr['filter_dynamic_types'];

        $codesArr = $allCodes;
        foreach ($filter_dynamic_types as $filter_dynamic_type){
            switch ($filter_dynamic_type){
                case 1: # 至少1小1大、排除前一期号码剩余号码至少上2个码
                    $codes = NumCodeService::getBeforeKjCodesDynamic1($plan, $lottery_type, $cNum=2);
                    break;
                case 2: # 至少1小1大、排除前一期号码剩余号码至少上3个码
                    $codes = NumCodeService::getBeforeKjCodesDynamic1($plan, $lottery_type, $cNum=3);
                    break;
                case 3: # 头尾去除当期期号最后两位相加
                    $codes = NumCodeService::getBeforeKjCodesDynamic3($plan, $lottery_type);
                    break;
                case 4: # 头去除当期期号最后两位相加
                    $codes = NumCodeService::getBeforeKjCodesDynamic4($plan, $lottery_type);
                    break;
                case 5: # 尾去除当期期号最后两位相加
                    $codes = NumCodeService::getBeforeKjCodesDynamic5($plan, $lottery_type);
                    break;
                case 6: # 头尾相加不等于期号最后两位相加
                    $codes = NumCodeService::getBeforeKjCodesDynamic6($plan, $lottery_type);
                    break;
                case 7: # 过滤前200期开过号码的全转
                    $codes = NumCodeService::getBeforeKjCodesDynamic7($plan);
                    break;
                case 8: # 头尾相加不等于期号最后两位相加(四定)
                    $codes = NumCodeService::getBeforeKjCodesDynamic8($plan);
                    break;
                case 9: # 随机9000组(四定)
                    $codes = NumCodeService::getBeforeKjCodesDynamic9($plan);
                    break;
                case 10: # 过滤最近2880组(四定)，不够往前搜集 前四，与12 后4类似
                    $codes = NumCodeService::getBeforeKjCodesDynamic14($plan, $lottery_type, $positions=[1,2,3,4], $num=2880);
                    break;
                case 11: # 过滤前200期开过2次以上号码的全转(四定)
                    $codes = NumCodeService::getBeforeKjCodesDynamic11($plan, $lottery_type);
                    break;
                case 12: # 过滤后4最近2880组(四定)，不够往后搜集
                    $codes = NumCodeService::getBeforeKjCodesDynamic14($plan, $lottery_type, $positions=[2,3,4,5]);
                    break;
                case 13: # 过滤最近10000期重复2次以上的直码(四定)
                    $codes = NumCodeService::getBeforeKjCodesDynamic13($plan, $lottery_type);
                    break;
                case 14: # 过滤掉1,2,3,5最近2880组(四定)，不够往后搜集
                    $codes = NumCodeService::getBeforeKjCodesDynamic14($plan, $lottery_type, $positions=[1,2,3,5]);
                    break;
                case 15: # 过滤掉1,2,4,5最近2880组(四定)，不够往后搜集
                    $codes = NumCodeService::getBeforeKjCodesDynamic14($plan, $lottery_type, $positions=[1,2,4,5]);
                    break;
                case 16: # 过滤掉1,3,4,5最近2880组(四定)，不够往后搜集
                    $codes = NumCodeService::getBeforeKjCodesDynamic14($plan, $lottery_type, $positions=[1,3,4,5]);
                    break;
                case 17: # 取前四最近8000组号码
                    $codes = NumCodeService::getBeforeKjCodesDynamic17($plan, $lottery_type);
                    break;
                case 18: # 取后四最近8000组号码
                    $codes = NumCodeService::getBeforeKjCodesDynamic18($plan, $lottery_type);
                    break;
                case 19: # 过滤两个位置一样的所有号码
                    $codes = NumCodeService::getBeforeKjCodesDynamic19($plan, $lottery_type);
                    break;
                case 20: # 过滤最近3200组(四定)，不够往后搜集 前四，与12 后4类似
                    $codes = NumCodeService::getBeforeKjCodesDynamic14($plan, $lottery_type, $positions=[1,2,3,4], $num=3200);
                    break;
                case 21: # 过滤后4最近3200组(四定)，不够往后搜集
                    #$codes = NumCodeService::getBeforeKjCodesDynamic12($plan, $lottery_type, $num=2000);
                    $codes = NumCodeService::getBeforeKjCodesDynamic14($plan, $lottery_type, $positions=[2,3,4,5], $num=3200);
                    break;
                case 22: # 过滤1,2,3,5最近3200组(四定)，不够往后搜集
                    $codes = NumCodeService::getBeforeKjCodesDynamic14($plan, $lottery_type, $positions=[1,2,3,5], $num=3200);
                    break;
                case 23: # 过滤1,2,4,5最近3200组(四定)，不够往后搜集
                    $codes = NumCodeService::getBeforeKjCodesDynamic14($plan, $lottery_type, $positions=[1,2,4,5], $num=3200);
                    break;
                case 24: # 过滤1,3,4,5最近3200组(四定)，不够往后搜集
                    $codes = NumCodeService::getBeforeKjCodesDynamic14($plan, $lottery_type, $positions=[1,3,4,5], $num=3200);
                    break;
                case 25: # 过滤前80期开过号码的全转
                    $codes = NumCodeService::getBeforeKjCodesDynamic7($plan, $positions=[1,2,3,4], $num=80);
                    break;
                case 26: # 去除上期同位置 9 * 9 * 9 * 9 = 81 * 81 = 6561 组
                    $codes = NumCodeService::getBeforeKjCodesDynamic26($plan, $lottery_type);
                    break;
                case 27: # 过滤前4最近1152组(四定)，不够往后搜集
                    $codes = NumCodeService::getBeforeKjCodesDynamic14($plan, $lottery_type, $positions=[1,2,3,4], $num=1152);
                    break;
                case 28: # 过滤期号尾号一致历史直码(四定) - 1234
                    $codes = NumCodeService::getBeforeKjCodesDynamic28($plan, $positions=[1,2,3,4], 3000);
                    break;
                case 29: # 排除前一期号码剩余号码至少上1个码
                    $codes = NumCodeService::getBeforeKjCodesDynamic29($plan, $cNum=1);
                    break;
                case 30: # 排除前一期号码剩余号码至少上2个码
                    $codes = NumCodeService::getBeforeKjCodesDynamic29($plan, $cNum=2);
                    break;
                case 31: # 剔除前100期123位一致的直码
                    $codes = NumCodeService::getBeforeKjCodesDynamic31($plan, $positions=[1,2,3], $num=self::BEFORE_3X_QS);
                    break;
                case 32: # 剔除前100期124位一致的直码
                    $codes = NumCodeService::getBeforeKjCodesDynamic31($plan, $positions=[1,2,4], $num=self::BEFORE_3X_QS);
                    break;
                case 33: # 剔除前100期134位一致的直码
                    $codes = NumCodeService::getBeforeKjCodesDynamic31($plan, $positions=[1,3,4], $num=self::BEFORE_3X_QS);
                    break;
                case 34: # 剔除前100期234位一致的直码
                    $codes = NumCodeService::getBeforeKjCodesDynamic31($plan, $positions=[2,3,4], $num=self::BEFORE_3X_QS);
                    break;
                case 35: # 过滤期号一致历史号码全倒(四定)
                    $codes = NumCodeService::getBeforeKjCodesDynamic35($plan);
                    break;
                case 36: # 过滤1235期号尾号一致历史直码(四定)
                    $codes = NumCodeService::getBeforeKjCodesDynamic28($plan, $positions=[1,2,3,5]);
                    break;
                case 37: # 过滤1245期号尾号一致历史直码(四定)
                    $codes = NumCodeService::getBeforeKjCodesDynamic28($plan, $positions=[1,2,4,5]);
                    break;
                case 38: # 过滤1345期号尾号一致历史直码(四定)
                    $codes = NumCodeService::getBeforeKjCodesDynamic28($plan, $positions=[1,3,4,5]);
                    break;
                case 39: # 过滤2345期号尾号一致历史直码(四定)
                    $codes = NumCodeService::getBeforeKjCodesDynamic28($plan, $positions=[2,3,4,5]);
                    break;
                case 40: # 过滤1234大小类型一致近1500组直码(四定)
                    $codes = NumCodeService::getBeforeKjCodesDynamic36($plan, $positions=[1,2,3,4]);
                    break;
                case 41: # 过滤1234前期大小或单双类型分别都不一致号码(四定)
                    $codes = NumCodeService::getBeforeKjCodesDynamic41($plan, $filter_type=1);
                    break;
                case 42: # 过滤最近x组大小类型(四定)
                    $codes = NumCodeService::getBeforeKjCodesDynamic42($plan, $type_field='type_dx', $type_val=3, $positions=[1,2,3,4], $filterNums=1000); #
                    break;
                case 43: # 过滤最近x组单双类型(四定)
                    $codes = NumCodeService::getBeforeKjCodesDynamic42($plan, $type_field='type_4ds', $type_val=3, $positions=[1,2,3,4], $filterNums=1000); #
                    break;
                case 44: # 过滤1234前期大小类型都不一致号码(四定)
                    $codes = NumCodeService::getBeforeKjCodesDynamic41($plan, $filter_type=2);
                    break;
                case 45: # 过滤1234前期单双类型都不一致号码(四定)
                    $codes = NumCodeService::getBeforeKjCodesDynamic41($plan, $filter_type=3);
                    break;
                case 46: # 过滤1234期号一致历史直码(四定)
                    $codes = NumCodeService::getBeforeKjCodesDynamic43($plan, $positions=[1,2,3,4]);
                    break;
                case 47: # 过滤1235期号一致历史直码(四定)
                    $codes = NumCodeService::getBeforeKjCodesDynamic43($plan, $positions=[1,2,3,5]);
                    break;
                case 48: # 过滤1245期号一致历史直码(四定)
                    $codes = NumCodeService::getBeforeKjCodesDynamic43($plan, $positions=[1,2,4,5]);
                    break;
                case 49: # 过滤1345期号一致历史直码(四定)
                    $codes = NumCodeService::getBeforeKjCodesDynamic43($plan, $positions=[1,3,4,5]);
                    break;
                case 50: # 过滤2345期号一致历史直码(四定)
                    $codes = NumCodeService::getBeforeKjCodesDynamic43($plan, $positions=[2,3,4,5]);
                    break;
                case 51: # 过滤最近x组大小类型(四定)
                    $codes = NumCodeService::getBeforeKjCodesDynamic42($plan, $type_field='type_dx', $type_val=3, $positions=[1,2,3,4], $filterNums=200); #
                    break;
                case 52: # 过滤最近x组单双类型(四定)
                    $codes = NumCodeService::getBeforeKjCodesDynamic42($plan, $type_field='type_4ds', $type_val=3, $positions=[1,2,3,4], $filterNums=200); #
                    break;
                case 53: # 过滤最近x组大小类型(四定)
                    $codes = NumCodeService::getBeforeKjCodesDynamic42($plan, $type_field='type_dx', $type_val=3, $positions=[1,2,3,4], $filterNums=500); #
                    break;
                case 54: # 过滤最近x组单双类型(四定)
                    $codes = NumCodeService::getBeforeKjCodesDynamic42($plan, $type_field='type_4ds', $type_val=3, $positions=[1,2,3,4], $filterNums=500); #
                    break;
                case 55: # 过滤最近500组(四定)，不够往后搜集
                    $codes = NumCodeService::getBeforeKjCodesDynamic14($plan, $lottery_type, $positions=[1,2,3,4], $filterNums=500);
                    break;
                case 56: # 过滤最近300组(四定)，不够往后搜集
                    $codes = NumCodeService::getBeforeKjCodesDynamic14($plan, $lottery_type, $positions=[1,2,3,4], $filterNums=300);
                    break;
                case 57: # 过滤前50期开过号码的全转
                    $codes = NumCodeService::getBeforeKjCodesDynamic7($plan, $positions=[1,2,3,4], $num=50);
                    break;
                case 58: # 过滤最近2345位500组(四定)，不够往后搜集
                    $codes = NumCodeService::getBeforeKjCodesDynamic14($plan, $lottery_type, $positions=[2,3,4,5], $filterNums=500);
                    break;
                case 59: # 过滤最近2345位300组(四定)，不够往后搜集
                    $codes = NumCodeService::getBeforeKjCodesDynamic14($plan, $lottery_type, $positions=[2,3,4,5], $filterNums=300);
                    break;
                case 60: # 取1234位置0123路[或]
                    $codes = NumCodeService::getBeforeKjCodesDynamic60($plan, $positions=[1,2,3,4], $lottery_type);
                    break;
                case 61: # 杀上期千位码
                    $codes = NumCodeService::getBeforeKjCodesDynamic61($plan, $positions=[1]);
                    break;
                case 62: # 杀上期百位码
                    $codes = NumCodeService::getBeforeKjCodesDynamic61($plan, $positions=[2]);
                    break;
                case 63: # 杀上期十位码
                    $codes = NumCodeService::getBeforeKjCodesDynamic61($plan, $positions=[3]);
                    break;
                case 64: # 杀上期个位码
                    $codes = NumCodeService::getBeforeKjCodesDynamic61($plan, $positions=[4]);
                    break;
                case 65: # 杀期号尾数位码
                    $codes = NumCodeService::getBeforeKjCodesDynamic61($plan, $positions=['q']);
                    break;
                case 66: # 取千位最近x个码
                    $codes = NumCodeService::getBeforeKjCodesDynamic62($plan, $positions=[1], $cNum=9);
                    break;
                case 67: # 取百位最近x个码
                    $codes = NumCodeService::getBeforeKjCodesDynamic62($plan, $positions=[2], $cNum=9);
                    break;
                case 68: # 取个位最近x个码
                    $codes = NumCodeService::getBeforeKjCodesDynamic62($plan, $positions=[3], $cNum=9);
                    break;
                case 69: # 取个位最近x个码
                    $codes = NumCodeService::getBeforeKjCodesDynamic62($plan, $positions=[4], $cNum=9);
                    break;
                case 70: # 取个位最近x个码
                    $codes = NumCodeService::getBeforeKjCodesDynamic63($plan, $positions=[1,2,3,4]);
                    break;
                case 71: # 过滤同单双类型+双重
                    $codes = NumCodeService::getBeforeKjCodesDynamic64($plan, $type_field='type_ds', $positions=[1,2,3,4], $filterNums=500); #
                    break;
                case 72: # 过滤同大小类型+双重
                    $codes = NumCodeService::getBeforeKjCodesDynamic64($plan, $type_field='type_4dx', $positions=[1,2,3,4], $filterNums=500); #
                    break;
                case 73: # 过滤最近2345位50组全倒(四定)，不够往后搜集
                    $codes = NumCodeService::getBeforeKjCodesDynamic7($plan, $positions=[2,3,4,5], $num=80);
                    break;
                case 74: # 杀上期同位置号码+三兄(四定)
                    $codes = NumCodeService::getBeforeKjCodesDynamic74($plan, $positions=[1,2,3,4]);
                    break;
                case 75: # 杀同位置冷码+三兄(四定)
                    $codes = NumCodeService::getBeforeKjCodesDynamic74($plan, $positions=[1,2,3,4], $c_type='type_3b', $type=2);
                    break;
                case 76: # 滤最近x组大小加配上期号码类型(四定)
                    $codes = NumCodeService::getBeforeKjCodesDynamic76($plan, $type_field='type_4dx', $positions=[1,2,3,4], $filterNums=1000);
                    break;
                case 77: # 滤最近x组大小加配上期号码类型(四定)
                    $codes = NumCodeService::getBeforeKjCodesDynamic76($plan, $type_field='type_ds', $positions=[1,2,3,4], $filterNums=1000);
                    break;
                case 78: # 过滤昨日同期[千百-十个]位置跨度(四定)
                    $codes = NumCodeService::getBeforeKjCodesDynamic78($plan, $positions1=[1,2], $positions2=[3,4]);
                    break;
                case 79: # 过滤昨日同期[千-百十个]位置跨度(四定)
                    $codes = NumCodeService::getBeforeKjCodesDynamic78($plan, $positions1=[1], $positions2=[2,3,4]);
                    break;
                case 80: # 过滤昨日同期[千百十-个]位置跨度(四定)
                    $codes = NumCodeService::getBeforeKjCodesDynamic78($plan, $positions1=[1,2,3], $positions2=[4]);
                    break;
                case 81: # 过滤前期[千-百]位置跨度(四定)
                    $codes = NumCodeService::getBeforeKjCodesDynamic78($plan, $positions1=[1], $positions2=[2], $beforeType=2);
                    break;
                case 82: # 过滤前期[百-十]位置跨度(四定)
                    $codes = NumCodeService::getBeforeKjCodesDynamic78($plan, $positions1=[2], $positions2=[3], $beforeType=2);
                    break;
                case 83: # 过滤前期[十-个]位置跨度(四定)
                    $codes = NumCodeService::getBeforeKjCodesDynamic78($plan, $positions1=[3], $positions2=[4], $beforeType=2);
                    break;
                case 84: # 过滤近一期同尾号[千-百]位置跨度(四定)
                    $codes = NumCodeService::getBeforeKjCodesDynamic78($plan, $positions1=[1], $positions2=[2], $beforeType=3);
                    break;
                case 85: # 过滤近一期同尾号[百-十]位置跨度(四定)
                    $codes = NumCodeService::getBeforeKjCodesDynamic78($plan, $positions1=[2], $positions2=[3], $beforeType=3);
                    break;
                case 86: # 过滤近一期同尾号[十-个]位置跨度(四定)
                    $codes = NumCodeService::getBeforeKjCodesDynamic78($plan, $positions1=[3], $positions2=[4], $beforeType=3);
                    break;
                case 87: # 过滤昨天同期号[千]位双重(四定)
                    $codes = NumCodeService::getBeforeKjCodesDynamic79($plan, $positions=[1]);
                    break;
                case 88: # 过滤[千]位一致号码近5天(四定)
                    $codes = NumCodeService::getBeforeKjCodesDynamic80($plan, $positions=[1], $dateNum=5);
                    break;
                case 94: # 过滤[百]位一致近5天号码(四定)
                    $codes = NumCodeService::getBeforeKjCodesDynamic80($plan, $positions=[2], $dateNum=5);
                    break;
                case 95: # 过滤[十]位号码及对数近5天(四定)
                    $codes = NumCodeService::getBeforeKjCodesDynamic80($plan, $positions=[3], $dateNum=5);
                    break;
                case 96: # 过滤[个]位一直近5天号码(四定)
                    $codes = NumCodeService::getBeforeKjCodesDynamic80($plan, $positions=[4], $dateNum=5);
                    break;
                case 89: # 过滤[千]位号码及合分(四定)
                    $codes = NumCodeService::getBeforeKjCodesDynamic81($plan, $positions=[1]);
                    break;
                case 90: # 过滤[百]位号码及合分(四定)
                    $codes = NumCodeService::getBeforeKjCodesDynamic81($plan, $positions=[2]);
                    break;
                case 91: # 过滤[十]位号码及合分(四定)
                    $codes = NumCodeService::getBeforeKjCodesDynamic81($plan, $positions=[3]);
                    break;
                case 92: # 过滤[个]位号码及合分(四定)
                    $codes = NumCodeService::getBeforeKjCodesDynamic81($plan, $positions=[4]);
                    break;
                case 93: # 头尾剔除上期和值后一位号码(四定)
                    $codes = NumCodeService::getBeforeKjCodesDynamic82($plan);
                    break;
                case 97: # 过滤上期每两个号码及对数(四定)
                    $codes = NumCodeService::getBeforeKjCodesDynamic83($plan, $dateNum=0);
                    break;
                case 98: # 过滤昨日同期每两个号码及对数(四定)
                    $codes = NumCodeService::getBeforeKjCodesDynamic83($plan, $dateNum=1);
                    break;
                case 102: # 过滤345三分离号码(四定)
                    $codes = NumCodeService::getBeforeKjCodesDynamic102($plan, $positions=[3,4,5]);
                    break;
                case 103: # 过滤123三分离号码(四定)
                    $codes = NumCodeService::getBeforeKjCodesDynamic102($plan, $positions=[1,2,3]);
                    break;
                case 104: # 过滤234三分离号码(四定)
                    $codes = NumCodeService::getBeforeKjCodesDynamic102($plan, $positions=[2,3,4]);
                    break;
                case 105: # 过滤125三分离号码(四定)
                    $codes = NumCodeService::getBeforeKjCodesDynamic102($plan, $positions=[1,2,5]);
                    break;
                case 106: # 过滤145三分离号码(四定)
                    $codes = NumCodeService::getBeforeKjCodesDynamic102($plan, $positions=[1,4,5]);
                    break;
                case 107: # 过滤124三分离号码(四定)
                    $codes = NumCodeService::getBeforeKjCodesDynamic102($plan, $positions=[1,2,4]);
                    break;
                case 108: # 过滤134三分离号码(四定)
                    $codes = NumCodeService::getBeforeKjCodesDynamic102($plan, $positions=[1,3,4]);
                    break;
                case 109: # 过滤135三分离号码(四定)
                    $codes = NumCodeService::getBeforeKjCodesDynamic102($plan, $positions=[1,3,5]);
                    break;
                case 110: # 过滤235三分离号码(四定)
                    $codes = NumCodeService::getBeforeKjCodesDynamic102($plan, $positions=[2,3,5]);
                    break;
                case 111: # 过滤245三分离号码(四定)
                    $codes = NumCodeService::getBeforeKjCodesDynamic102($plan, $positions=[2,4,5]);
                    break;
                case 112: # 杀同位置大小加配上期两位同位置号码(四定)
                    $codes = NumCodeService::getBeforeKjCodesDynamic112($plan, $type_field='type_4dx');
                    break;
                case 113: # 杀同位置大小加配上期两位同位置号码(四定)
                    $codes = NumCodeService::getBeforeKjCodesDynamic112($plan, $type_field='type_ds');
                    break;
                case 114: # 过滤千位最近1个冷码+三兄弟
                    $codes = NumCodeService::getBeforeKjCodesDynamic114($plan, $type_field='type_3b', $type_val=1, $positions=1, $type_log=''); #
                    break;
                case 115: # 过滤百位最近1个冷码+三兄弟
                    $codes = NumCodeService::getBeforeKjCodesDynamic114($plan, $type_field='type_3b', $type_val=1, $positions=2, $type_log=''); #
                    break;
                case 116: # 过滤十位最近1个冷码+三兄弟
                    $codes = NumCodeService::getBeforeKjCodesDynamic114($plan, $type_field='type_3b', $type_val=1, $positions=3, $type_log=''); #
                    break;
                case 117: # 过滤个位最近1个冷码+三兄弟
                    $codes = NumCodeService::getBeforeKjCodesDynamic114($plan, $type_field='type_3b', $type_val=1, $positions=4, $type_log=''); #
                    break;
                case 118: # 过滤千位最近1个冷码+两单两双+对数
                    $codes = NumCodeService::getBeforeKjCodesDynamic114($plan, $type_field='type_4ds', $type_val=3, $positions=1, $type_log=1); #
                    break;
                case 119: # 过滤百位最近1个冷码+两单两双+对数
                    $codes = NumCodeService::getBeforeKjCodesDynamic114($plan, $type_field='type_4ds', $type_val=3, $positions=2, $type_log=1); #
                    break;
                case 120: # 过滤十位最近1个冷码+两单两双+对数
                    $codes = NumCodeService::getBeforeKjCodesDynamic114($plan, $type_field='type_4ds', $type_val=3, $positions=3, $type_log=1); #
                    break;
                case 121: # 过滤个位最近1个冷码+两单两双+对数
                    $codes = NumCodeService::getBeforeKjCodesDynamic114($plan, $type_field='type_4ds', $type_val=3, $positions=4, $type_log=1); #
                    break;
                case 122: # 过滤千位最近1个冷码+两大两小+对数
                    $codes = NumCodeService::getBeforeKjCodesDynamic114($plan, $type_field='type_dx', $type_val=3, $positions=1, $type_log=2); #
                    break;
                case 123: # 过滤百位最近1个冷码+两大两小+对数
                    $codes = NumCodeService::getBeforeKjCodesDynamic114($plan, $type_field='type_dx', $type_val=3, $positions=2, $type_log=2); #
                    break;
                case 124: # 过滤十位最近1个冷码+两大两小+对数
                    $codes = NumCodeService::getBeforeKjCodesDynamic114($plan, $type_field='type_dx', $type_val=3, $positions=3, $type_log=2); #
                    break;
                case 125: # 过滤个位最近1个冷码+两大两小+对数
                    $codes = NumCodeService::getBeforeKjCodesDynamic114($plan, $type_field='type_dx', $type_val=3, $positions=4, $type_log=2); #
                    break;
                case 126: # 过滤千位最近1个冷码+两大两小+对数
                    $codes = NumCodeService::getBeforeKjCodesDynamic115($plan, $positions=1); #
                    break;
                case 127: # 过滤百位最近1个冷码+两大两小+对数
                    $codes = NumCodeService::getBeforeKjCodesDynamic115($plan, $positions=2); #
                    break;
                case 128: # 过滤十位最近1个冷码+两大两小+对数
                    $codes = NumCodeService::getBeforeKjCodesDynamic115($plan, $positions=3); #
                    break;
                case 129: # 过滤个位最近1个冷码+两大两小+对数
                    $codes = NumCodeService::getBeforeKjCodesDynamic115($plan, $positions=4); #
                    break;
                case 130: # 过滤千位+其它位置合分千
                    $codes = NumCodeService::getBeforeKjCodesDynamic116($plan, $positions=1); #
                    break;
                case 131: # 过滤百位+其它位置合分百
                    $codes = NumCodeService::getBeforeKjCodesDynamic116($plan, $positions=2); #
                    break;
                case 132: # 过滤十位+其它位置合分十
                    $codes = NumCodeService::getBeforeKjCodesDynamic116($plan, $positions=3); #
                    break;
                case 133: # 过滤个位+其它位置合分个
                    $codes = NumCodeService::getBeforeKjCodesDynamic116($plan, $positions=4); #
                    break;
                case 134: # 过滤千位且全双
                    $codes = NumCodeService::getBeforeKjCodesDynamic116($plan, $positions=1, $filterTypes=['type_4ds']); #
                    break;
                case 135: # 过滤百位且全双
                    $codes = NumCodeService::getBeforeKjCodesDynamic116($plan, $positions=2, $filterTypes=['type_4ds']); #
                    break;
                case 136: # 过滤十位且全双
                    $codes = NumCodeService::getBeforeKjCodesDynamic116($plan, $positions=3, $filterTypes=['type_4ds']); #
                    break;
                case 137: # 过滤个位且全双
                    $codes = NumCodeService::getBeforeKjCodesDynamic116($plan, $positions=4, $filterTypes=['type_4ds']); #
                    break;
                case 138: # 过滤千位且全小
                    $codes = NumCodeService::getBeforeKjCodesDynamic116($plan, $positions=1, $filterTypes=['type_dx']); #
                    break;
                case 139: # 过滤百位且全小
                    $codes = NumCodeService::getBeforeKjCodesDynamic116($plan, $positions=2, $filterTypes=['type_dx']); #
                    break;
                case 140: # 过滤十位且全小
                    $codes = NumCodeService::getBeforeKjCodesDynamic116($plan, $positions=3, $filterTypes=['type_dx']); #
                    break;
                case 141: # 过滤个位且全小
                    $codes = NumCodeService::getBeforeKjCodesDynamic116($plan, $positions=4, $filterTypes=['type_dx']); #
                    break;
                case 142: # 过滤上期每两个号码及双重(四定)
                    $codes = NumCodeService::getBeforeKjCodesDynamic83($plan, $dateNum=0, $d_type=2);
                    break;
                case 143: # 过滤昨日同期每两个号码及双重(四定)
                    $codes = NumCodeService::getBeforeKjCodesDynamic83($plan, $dateNum=1, $d_type=2);
                    break;
                case 144: # 过滤前天同期每两个号码及双重(四定)
                    $codes = NumCodeService::getBeforeKjCodesDynamic83($plan, $dateNum=2, $d_type=2);
                    break;
                case 146: # 过滤上期同值及双重(四定)
                    $codes = NumCodeService::getBeforeKjCodesDynamic118($plan, $dateNum=0, $d_type=2);
                    break;
                case 147: # 胆码2跨1-2个(四定)# 0的2跨是2、1的2跨就只是3、8的2跨是6、9的2跨是7
                    $codes = NumCodeService::getBeforeKjCodesDynamic119($plan, $kd=2, $kdNumType=1);
                    break;
                case 148: # 随机对数1对、前三合分9个(四定)
                    $codes = NumCodeService::getBeforeKjCodesDynamic120($plan, $randType=0);
                    break;
                case 149: # 随机对数1对
                    $codes = NumCodeService::getBeforeKjCodesDynamic120($plan, $randType=1);
                    break;
                case 150: # 随机合分9个(四定)
                    $codes = NumCodeService::getBeforeKjCodesDynamic120($plan, $randType=2);
                    break;
                case 151: # 配数单双互排除及该位置号码(四定)
                    $codes = NumCodeService::getBeforeKjCodesDynamic121($plan, $positions=[1,2,3,4]);
                    break;
                case 152: # 123路配数-除-千位X
                    $codes = NumCodeService::getBeforeKjCodesDynamic122($plan, $positions=[1]);
                    break;
                case 153: # 123路配数-除-百位X
                    $codes = NumCodeService::getBeforeKjCodesDynamic122($plan, $positions=[2]);
                    break;
                case 154: # 123路配数-除-十位X
                    $codes = NumCodeService::getBeforeKjCodesDynamic122($plan, $positions=[3]);
                    break;
                case 155: # 123路配数-除-个位X
                    $codes = NumCodeService::getBeforeKjCodesDynamic122($plan, $positions=[4]);
                    break;
                case 156: # 两数合分-除-上期千百位置
                    $codes = NumCodeService::getBeforeKjCodesDynamic123($plan, $positions=[1,2]);
                    break;
                case 162: # 过滤两个位置的各一个冷码
                case 163:
                case 164:
                case 165:
                case 166:
                case 167:
                case 168:
                case 169:
                case 170:
                case 171:
                    $codes = NumCodeService::getBeforeKjCodesDynamic124($plan, self::TYPE_POSITIONS[$filter_dynamic_type]);
                    break;
                case 172:
                case 173:
                case 174:
                case 175:
                case 176:
                case 177:
                case 178:
                case 179:
                case 180:
                case 181:
                    $codes = NumCodeService::getBeforeKjCodesDynamic124($plan, self::TYPE_LR_POSITIONS[$filter_dynamic_type], $type=NumCodeService::CODE_LR_TYPE_YL);
                    break;
                case 182: # 过滤最近x组三大一小类型(四定)
                    $codes = NumCodeService::getBeforeKjCodesDynamic42($plan, $type_field='type_dx', $type_val=2, $positions=[1,2,3,4], $filterNums=200); #
                    break;
                case 183: # 过滤最近x组一大三小类型(四定)
                    $codes = NumCodeService::getBeforeKjCodesDynamic42($plan, $type_field='type_dx', $type_val=4, $positions=[1,2,3,4], $filterNums=200); #
                    break;
                case 184: # 过滤最近x组一单三双类型(四定)
                    $codes = NumCodeService::getBeforeKjCodesDynamic42($plan, $type_field='type_4ds', $type_val=4, $positions=[1,2,3,4], $filterNums=200); #
                    break;
                case 185: # 过滤最近x组三单一双类型(四定)
                    $codes = NumCodeService::getBeforeKjCodesDynamic42($plan, $type_field='type_4ds', $type_val=5, $positions=[1,2,3,4], $filterNums=200); #
                    break;
                case 186:
                    $codes = NumCodeService::getBeforeKjCodesDynamic125($plan, $pos=1, $cNum=1);
                    break;
                case 187:
                    $codes = NumCodeService::getBeforeKjCodesDynamic125($plan, $pos=2, $cNum=1);
                    break;
                case 188:
                    $codes = NumCodeService::getBeforeKjCodesDynamic125($plan, $pos=3, $cNum=1);
                    break;
                case 189:
                    $codes = NumCodeService::getBeforeKjCodesDynamic125($plan, $pos=4, $cNum=1);
                    break;
                case 190:
                case 191:
                case 192:
                case 193:

                case 202:
                case 203:
                case 204:
                case 205:
                case 206:
                case 207:
                    $codes = NumCodeService::getBeforeKjCodesDynamic126($plan, self::TYPE_HF_POSITIONS[$filter_dynamic_type]);
                    break;
                case 194:
                    $codes = NumCodeService::getBeforeKjCodesDynamic127($plan);
                    break;
                case 195:
                    $codes = NumCodeService::getBeforeKjCodesDynamic127($plan, $qiHaoType=2);
                    break;
                case 196: # 过滤前400期开过号码的全转
                    $codes = NumCodeService::getBeforeKjCodesDynamic7($plan, $positions=[1,2,3,4], $num=400);
                    break;
                case 197: # 过滤前500期开过号码的全转
                    $codes = NumCodeService::getBeforeKjCodesDynamic7($plan, $positions=[1,2,3,4], $num=500);
                    break;
                case 198: # 取前四最近999组号码
                    $codes = NumCodeService::getBeforeKjCodesDynamic17($plan, $lottery_type, 9990);
                    break;
                case 199: # 取后四最近9990组号码
                    $codes = NumCodeService::getBeforeKjCodesDynamic18($plan, $lottery_type, 9990);
                    break;
                case 200: # 取前四最近9999组号码
                    $codes = NumCodeService::getBeforeKjCodesDynamic17($plan, $lottery_type, 9999);
                    break;
                case 201: # 取后四最近9999组号码
                    $codes = NumCodeService::getBeforeKjCodesDynamic18($plan, $lottery_type, 9999);
                    break;
                case 235: # 过滤近1天直码
                case 208: # 过滤近3天直码
                case 209: # 过滤近5天直码
                case 210: # 过滤近7天直码
                    $nums = [ 208 => 3, 209 => 5, 210 => 7, 235=>1];
                    $params = [
                        ['type'=>2, 'params'=>['x'=>$nums[$filter_dynamic_type]]]
                    ]; # 动态过滤2，对应的\backend\service\numbers\DynamicFilterService::DYNAMIC_FILTER_TYPES
                    $codes = DynamicFilterService::getFilterDynamic2($plan, $params);
                    break;
                case 211: # x(1234)位近y个码最多上z个
                case 212: # x(1234)位近y个码最多上z个
                case 213: # x(1234)位近y个码最多上z个
                case 214: # x(1234)位近y个码最多上z个
                    $posData = [ 211=>1, 212=>2, 213=>3, 214=>4];
                    $params = [
                        ['type'=>3, 'params'=>['x'=>$posData[$filter_dynamic_type], 'y'=>4, 'z'=>2]]
                    ]; # 动态过滤2，对应的\backend\service\numbers\DynamicFilterService::DYNAMIC_FILTER_TYPES
                    $codes = DynamicFilterService::getFilterDynamic2($plan, $params);
                    break;
                case 231: # x(1234)位近y个码最多上z个
                case 232: # x(1234)位近y个码最多上z个
                case 233: # x(1234)位近y个码最多上z个
                case 234: # x(1234)位近y个码最多上z个
                    $posData = [ 231=>1, 232=>2, 233=>3, 234=>4];
                    $params = [
                        ['type'=>3, 'params'=>['x'=>$posData[$filter_dynamic_type], 'y'=>4, 'z'=>1]]
                    ]; # 动态过滤2，对应的\backend\service\numbers\DynamicFilterService::DYNAMIC_FILTER_TYPES
                    $codes = DynamicFilterService::getFilterDynamic2($plan, $params);
                    break;
                case 215: # 定1234位号码147上2个则下期至少上1个
                case 216: # 定123位号码147上2个则下期至少上1个
                case 217: # 定124位号码147上2个则下期至少上1个
                case 218: # 定134位号码147上2个则下期至少上1个
                case 219: # 定234位号码147上2个则下期至少上1个
                    $posData = [215=>'1234', 216=>'123', 217=>'124', 218=>'134', 219=>'234'];
                    $params = [
                        ['type'=>4, 'params'=>['x'=>$posData[$filter_dynamic_type], 'y'=>'147', 'z'=>1]]
                    ]; # 动态过滤2，对应的\backend\service\numbers\DynamicFilterService::DYNAMIC_FILTER_TYPES
                    $codes = DynamicFilterService::getFilterDynamic2($plan, $params);
                    break;
                case 220: # 定1234位号码258上2个则下期至少上1个
                case 221: # 定123位号码258上2个则下期至少上1个
                case 222: # 定124位号码258上2个则下期至少上1个
                case 223: # 定134位号码258上2个则下期至少上1个
                case 224: # 定234位号码258上2个则下期至少上1个
                $posData = [220=>'1234', 221=>'123', 222=>'124', 223=>'134', 224=>'234'];
                    $params = [
                        ['type'=>4, 'params'=>['x'=>$posData[$filter_dynamic_type], 'y'=>'258', 'z'=>1]]
                    ]; # 动态过滤2，对应的\backend\service\numbers\DynamicFilterService::DYNAMIC_FILTER_TYPES
                    $codes = DynamicFilterService::getFilterDynamic2($plan, $params);
                    break;
                case 225: # 定1234位号码369上2个则下期至少上1个
                case 226: # 定123位号码3692个则下期至少上1个
                case 227: # 定124位号码3692个则下期至少上1个
                case 228: # 定134位号码3692个则下期至少上1个
                case 229: # 定234位号码3692个则下期至少上1个
                    $posData = [225=>'1234', 226=>'123', 227=>'124', 228=>'134', 229=>'234'];
                    $params = [
                        ['type'=>4, 'params'=>['x'=>$posData[$filter_dynamic_type], 'y'=>'369', 'z'=>1]]
                    ]; # 动态过滤2，对应的\backend\service\numbers\DynamicFilterService::DYNAMIC_FILTER_TYPES
                    $codes = DynamicFilterService::getFilterDynamic2($plan, $params);
                    break;
                case 230: # 随机过滤两数同上
                case 238: # 随机过滤两数同上
                    $randType = [230=>3, 238=>4][$filter_dynamic_type];
                    $codes = NumCodeService::getBeforeKjCodesDynamic120($plan, $randType);
                    break;
                case 236: # 过滤最近x期号码全转
                    $numData = [236=>30];
                    $params = [
                        ['type'=>5, 'params'=>['x'=>$numData[$filter_dynamic_type]]]
                    ];
                    $codes = DynamicFilterService::getFilterDynamic2($plan, $params);
                    break;
                case 237: # 去除前第x期同位置 9 * 9 * 9 * 9 = 81 * 81 = 6561 组
                    $qiData = [237=>1];
                    $params = [
                        ['type'=>6, 'params'=>['x'=>$qiData[$filter_dynamic_type]]]
                    ];
                    $codes = DynamicFilterService::getFilterDynamic2($plan, $params);
                    break;
            }
            if(empty($codes)){
                $codes = $codesArr;
            }
            $codesArr = array_intersect($codesArr, $codes);
        }
        #p(['counts'=>count($codesArr), 'codesArr'=>$codesArr]);

        return $codesArr;
    }

    /**
     * 获取某个彩种最近x个号码
     * @param $pos
     * @param int $num
     * @param int $lottery_type
     * @return array
     */
    public static function getPosLatelyCode($pos, $num=9, $lottery_type=DEFAULT_LOTTERY_TYPE){

        $pos_field = 'code'.$pos;
        // 执行子查询以获取要排除的记录的 ID
        $excludedIds = SscKjData::find()
            ->select([$pos_field])
            ->where(['lottery_type' => $lottery_type])
            ->orderBy(['id' => SORT_DESC])
            ->limit(50)
            ->asArray()
            ->column();
        // 对结果进行处理，确保至少包含9个不同的 code1 值
        //p($excludedIds, 0);
        $selectedCodes = [];
        foreach ($excludedIds as $code) {
            //p([$code, $selectedCodes, count($selectedCodes)], 0);
            if (!in_array($code, $selectedCodes)) {
                if (count($selectedCodes) >= $num) {
                    //break; // 已经选够了9个不同的 code1 值
                    return $selectedCodes;
                }
                $selectedCodes[] = $code;
                $selectedCodes = array_unique($selectedCodes);
            }
        }
        return $selectedCodes;
    }

    public static function get4Len($positions=[1,2,3]){
        $len = count($positions);
        $positions_l = [];
        if($len==1){
            $positions_l = ['%', '%', '%'];
        }elseif ($len==2){
            $positions_l = ['%', '%'];
        }elseif ($len==3){
            $positions_l = ['%'];
        }

        return array_merge($positions, $positions_l);
    }

    /**
     * 0123路号码获取
     * @param string $code
     * @return string[]
     */
    public static function getCodeLine1($code=''){
        if($code==0){
            $lineCodes = NumService::CODES_0_LINE;
            #$lineCodes = NumService::CODES_2_LINE;
        }elseif (in_array($code, NumService::CODES_1_LINE)){
            $lineCodes = NumService::CODES_1_LINE;
        }elseif (in_array($code, NumService::CODES_2_LINE)){
            #$lineCodes = NumService::CODES_2_LINE;
            $lineCodes = NumService::CODES_0_LINE;
            #$lineCodes = ['2', '5', '8'];
        }elseif (in_array($code, NumService::CODES_3_LINE)){
            $lineCodes = NumService::CODES_3_LINE;
        }

        return $lineCodes;
    }

    /**
     * 0123路号码获取
     * @param string $code
     * @return string[]
     */
    public static function getCodeLine2($code=''): array
    {
        if($code==0){
            #$lineCodes = NumService::CODES_0_LINE;
            $lineCodes = ['0'];
        }elseif (in_array($code, NumService::CODES_1_LINE)){
            $lineCodes = NumService::CODES_1_LINE;
        }elseif (in_array($code, NumService::CODES_2_LINE)){
            $lineCodes = NumService::CODES_2_LINE;
        }elseif (in_array($code, NumService::CODES_3_LINE)){
            $lineCodes = NumService::CODES_3_LINE;
        }

        return $lineCodes;
    }

    /**
     * @desc 位置 -> str
     * @param $poses
     * @return array
     */
    public static function getDescStrByPoses($poses){
        $strArr = [];
        foreach ($poses as $pos){
            $strArr[] = (NumService::$pos_to_desc)[$pos];
        }
        return $strArr;
    }

    /**
     * @desc 返回筛选名称
     * @param string $type
     * @return array|mixed
     */
    public static function getNameByCodesType($type = ''){
        # {"get_types":["1","2"],"remove_types":["4","5"],"get_hzs":["7","8","10"],"remove_hzs":["12","13","14"],"get_arises":"123","remove_arises":"456"}
        $typeArr = [
            'type_2'=>'双重',
            'type_3'=>'三重',
            'type_4'=>'四重',
            'type_22'=>'双双',
            'type_2b'=>'两兄',
            'type_3b'=>'三兄',
            'type_3n_2b'=>'三现[双重+两兄]',
            'type_4b'=>'四兄',
            'type_log'=>'对数',
            'type_2log'=>'双对数',
            'type_4d'=>'四单',
            'type_4s'=>'四双',
            'arise'=>'上奖',
            'remove_arises'=>'上奖除',
            'get_types'=>'类型取',
            'remove_types'=>'类型除',
            //'hefen_pos'=>'合分位',
            'hefen'=>'合分',
        ];

        if(isset($typeArr[$type])) return $typeArr[$type];

        return $typeArr;
    }

    /**
     * @desc 去除最近多少期号码 - 四定
     * @param array $code_hz
     * @return array
     */
    public static function getNotLatelyCodes($code_hz = ['lately_start'=>0, 'lately_end'=>400], $lottery_type = DEFAULT_LOTTERY_TYPE){

        $last = SscKjData::find()->where(['lottery_type'=>$lottery_type])->select(['last_id'=>'index_id'])->orderBy(['id'=>SORT_DESC])->asArray()->limit(1)->one();

        $startIndexId = $last['last_id'] - $code_hz['lately_end'];
        $endIndexId = $last['last_id'] - $code_hz['lately_start'];
        $where = ['AND', ['>=', 'index_id', $startIndexId], ['<=', 'index_id', $endIndexId], ['=', 'lottery_type', $lottery_type]];
        $areaKjCodes = SscKjData::find()->select(['qihao', 'code_4n', 'type_4', 'type_3', 'type_2'])->where($where)->asArray()->all();
        $code_4ns = ArrayHelper::getColumn($areaKjCodes, 'code_4n');

        $latelyCodes = SscDataService::getAriseCodes($code_4ns); # 缓存开奖号码四定组合 ,最近开奖号码全倒
        $latelyCodes = array_unique($latelyCodes);
        //p(count($latelyCodes));

        $codes = Num4Type::find()->where(['AND', ['=', 'code_type', 4], ['>', 'id', 0], ['not in', 'code', $latelyCodes]])->asArray()->all();

        $codesArr = ArrayHelper::getColumn($codes, 'code');

        return $codesArr;
    }

    /**
     * @desc 获取二字定的号码
     * @param array $codeArr
     * @return array
     */
    public static function getCodesTwo($codes = [1, 2]){
        if(count($codes) != 2 && count($codes) != 3) return ['status'=>300, 'msg'=>'号码错误'];

        if(count($codes) == 2){
            $datas = [
                [$codes[0], $codes[1], 'X', 'X'],
                [$codes[1], $codes[0], 'X', 'X'],
                [$codes[0], 'X', $codes[1], 'X'],
                [$codes[1], 'X', $codes[0], 'X'],
                [$codes[0], 'X', 'X', $codes[1]],
                [$codes[1], 'X', 'X', $codes[0]],
                ['X', $codes[0], $codes[1], 'X'],
                ['X', $codes[1], $codes[0], 'X'],
                ['X', $codes[0], 'X', $codes[1]],
                ['X', $codes[1], 'X', $codes[0]],
                ['X', 'X', $codes[0], $codes[1]],
                ['X', 'X', $codes[1], $codes[0]],
            ];
        }else{
            $datas = [
                [$codes[0], $codes[1], $codes[2], 'X'],
                [$codes[0], $codes[2], $codes[1], 'X'],
                [$codes[1], $codes[0], $codes[2], 'X'],
                [$codes[1], $codes[2], $codes[0], 'X'],
                [$codes[2], $codes[0], $codes[1], 'X'],
                [$codes[2], $codes[1], $codes[0], 'X'],

                [$codes[0], $codes[1], 'X', $codes[2]],
                [$codes[0], $codes[2], 'X', $codes[1]],
                [$codes[1], $codes[0], 'X', $codes[2]],
                [$codes[1], $codes[2], 'X', $codes[0]],
                [$codes[2], $codes[0], 'X', $codes[1]],
                [$codes[2], $codes[1], 'X', $codes[0]],

                [$codes[0], 'X', $codes[1], $codes[2]],
                [$codes[0], 'X', $codes[2], $codes[1]],
                [$codes[1], 'X', $codes[0], $codes[2]],
                [$codes[1], 'X', $codes[2], $codes[0]],
                [$codes[2], 'X', $codes[0], $codes[1]],
                [$codes[2], 'X', $codes[1], $codes[0]],

                ['X', $codes[0], $codes[1], $codes[2]],
                ['X', $codes[0], $codes[2], $codes[1]],
                ['X', $codes[1], $codes[0], $codes[2]],
                ['X', $codes[1], $codes[2], $codes[0]],
                ['X', $codes[2], $codes[0], $codes[1]],
                ['X', $codes[2], $codes[1], $codes[0]],
            ];
        }

        return $datas;
    }

    /**
     * @desc 返回五位二定
     * @param array $codes
     * @return array
     */
    public static function getCodesTwo5($codes = [1, 2]){
        if(count($codes) != 2) return ['status'=>300, 'msg'=>'号码错误'];

        if($codes[0] == $codes[1]){
            $datas = [
                [$codes[0],'X','X','X', $codes[1]],

                ['X',$codes[0],'X','X', $codes[1]],

                ['X','X',$codes[0],'X', $codes[1]],

                ['X','X','X',$codes[0], $codes[1]],
            ];
        }else{
            $datas = [
                [$codes[0],'X','X','X', $codes[1]],
                [$codes[1],'X','X','X', $codes[0]],

                ['X',$codes[0],'X','X', $codes[1]],
                ['X',$codes[1],'X','X', $codes[0]],

                ['X','X',$codes[0],'X', $codes[1]],
                ['X','X',$codes[1],'X', $codes[0]],

                ['X','X','X',$codes[0], $codes[1]],
                ['X','X','X',$codes[1], $codes[0]],
            ];
        }

        return $datas;
    }

    /**
     * @desc 删除数组指定值的元素使用array_keys搜索指定的值再循环unset
     * @param $arr
     * @param $value
     * @return mixed
     */
    public static function delByValue($arr, $value){
        if(empty($arr) OR empty($value)) return [];
        $keys = array_keys($arr, $value);
        if(!empty($keys)){
            foreach ($keys as $key) {
                unset($arr[$key]);
            }
        }
        return array_values($arr);
    }

    /**
     * @desc 转换处理  头：千、尾：个
     * @param $codes_desc
     * @return mixed
     */
    public static function opChangeDesc($codes_desc){

        $codes_desc = str_replace('头', '千', $codes_desc);
        $codes_desc = str_replace('尾', '个', $codes_desc);

        $codes_desc = str_replace('二字定', '二定', $codes_desc);
        $codes_desc = str_replace('两定', '二定', $codes_desc);
        $codes_desc = str_replace('两字定', '二定', $codes_desc);

        $codes_desc = str_replace('三字定', '三定', $codes_desc);
        $codes_desc = str_replace('四字定', '四定', $codes_desc);

        return $codes_desc;
    }

    /**
     * @desc 快译描述转换成位置号码，支持一二三四定
     * @param $codes_desc - 千12345百12345十67890
     * @return array ['p1'=>12345, 'p2'=>12345, 'p3'=>67890]
     */
    public static function getCodesHzByDesc($codes_desc='', &$msg=''){
        //echo $codes_desc.'<br>';
        $data = [];
        if(!$codes_desc) return $data;

        $codes_desc = NumService::opChangeDesc($codes_desc); # 替换通用位置名词 头->千、尾->个

        $code_type = NumService::getCodeTypeByDesc($codes_desc, $positions); # 获取定位类型

        $data = NumService::getHzsByDesc($codes_desc, $data);

        # 获取位置号码除、取
        $data = NumService::getPosCodes($codes_desc, $data); # p1、p1_0

        //p(['code_type'=>$code_type, 'pos'=>$positions, 'data'=>$data]);
        # 筛选逻辑包括两数合、三数合、跑=移、值范围、取双重、除双重、取三重、除三重、取双双重、除双双重、取二兄弟、除二兄弟、
        # 取千单、 除千单、取千大、除千大、取百单、除百单、取百大、除百大、取十单、除十单、取十大、除十大、取个单、除个单、取个大、除个大

        $data = NumService::getCodeType($codes_desc, $data);# 号码类型：type_2、type_3、type_22、type_4 等

        $data = NumService::getCodeTypeDw($codes_desc, $data); # 定位：23568头尾 、千1234百4567十7890
        //p([$codes_desc, $data]);

        $data = NumService::getCodeTypeDao($codes_desc, $data); # 倒类型

        $data = NumService::getCodeTypeFixPosHeFen($codes_desc, $data); # 不定位

        $data = NumService::getSingleByDesc($codes_desc, $data);# 获取倍数

        # 走移、 现  暂未完成
        $data = NumService::getCodeTypeZouYi($codes_desc, $data); # 走移

        $data['code_type'] = $code_type;
        if($code_type == 2 && $data['single']<1){
            $msg = '二定最少1元';
        }

        return $data;
    }

    /**
     * @desc 根据code描述获取号码
     * @param $codes_desc
     * @param  $code_type 号码类型：1字定2二字定3三字定4四字定
     * @return array
     */
    public static function getCodesByDesc($codes_descs, $code_type = ''){
        $codesArr = [];
        $codes_descArr = explode(',', $codes_descs);

        foreach ($codes_descArr as $codes_desc){
            $codes_hz = NumService::getCodesHzByDesc($codes_desc);
            //p($codes_hz);
            if(empty($code_type)) $code_type = $codes_hz['code_type'];
            $tmpCodesArr = NumService::getCodesKuaiXuan($codes_hz, $code_type);
            $logArr = ['codes_desc'=>$codes_desc, 'codes_hz'=>$codes_hz, 'tmpCodesArr'=>$tmpCodesArr, 'counts'=>count($tmpCodesArr)];
            Tool_Common::log('/getCodes/'.__FUNCTION__, 'INFO', '根据code描述获取号码', $logArr);
            if($tmpCodesArr) $codesArr = array_merge($codesArr, $tmpCodesArr);
        }

        $codesArr = array_unique($codesArr);

        return $codesArr;
    }

    /**
     * @desc 根据描述判断号码类型：
     * @param $codes_desc - 四字定千1234百4567十7890个2468、三字定千1234百6789十1357、二定千02468百13579、千2345十5678个0289
     * @return int code_type 1一字定2二字定3三字定4四字定
     */
    public static function getCodeTypeByDesc($codes_desc, &$positions = []){
        $code_type = 0;

        # 1、出现：XX定、X定，则首先判断
        if(strpos($codes_desc, '一定') !== false OR strpos($codes_desc, '一字定') !== false){
            $code_type = 1;
        }elseif (strpos($codes_desc, '二定') !== false OR strpos($codes_desc, '二字定') !== false){
            $code_type = 2;
        }elseif (strpos($codes_desc, '三定') !== false OR strpos($codes_desc, '三字定') !== false){
            $code_type = 3;
        }elseif (strpos($codes_desc, '四定') !== false OR strpos($codes_desc, '四字定') !== false){
            $code_type = 4;
        }

        # 2、不出现：XX定、X定，则判断：千百十个出现次数来判断定位类型
        $num = 0;
        $types = ['千', '百', '十', '个', '五'];
        foreach ($types as $type){
            if(strpos($codes_desc, $type) !== false){
                $num = $num + 1;
                $positions[] = $type; # 记录：千、百、十、个、五
            }
        }

        if($code_type == 0){
            $code_type = $num;
        }

        return $code_type;
    }

    /**
     * @param $codes_desc 值范围15-35、值15,17,18
     * @param $data
     * @return mixed
     */
    public static function getHzsByDesc($codes_desc, &$data){

        if(!$data['hz']) $data['hz'] = [];
        if(preg_match("/值范围\d+\-\d+/", $codes_desc, $returns) OR preg_match("/值\d+\-\d+/", $codes_desc, $returns)){
            $str = str_replace('值范围', '', $returns[0]);
            $str = str_replace('值', '', $str);
            if(strpos($str, '-') !== false){
                # 和值区间
                $zhi_scopes = explode('-', $str);
                if(count($zhi_scopes) == 1){
                    $min_zhi = $max_zhi = $zhi_scopes[0];
                }else{
                    $min_zhi = array_shift($zhi_scopes);
                    $max_zhi = end($zhi_scopes);
                }
                for ($i=$min_zhi; $i<=$max_zhi; $i++){
                    $data['hz'][] = $i;
                }

            }
        }elseif (preg_match("/值范围\d+\,\d+/", $codes_desc, $returns) OR preg_match("/值\d+\,\d+/", $codes_desc, $returns)){
            $str = str_replace('值范围', '', $returns[0]);
            $str = str_replace('值', '', $str);
            $data['hz'] = explode(',', $str);
        }
        if(empty($data['hz'])) unset($data['hz']);

        return $data;
    }

    /**
     * @desc 获取位置的号码
     * @param $codes_desc
     * @param $data
     * @return mixed
     */
    public static function getPosCodes($codes_desc, &$data){
        if(empty($data)) $data = [];

        $get_types = [
            '取千大'=>['p1'=>'56789'],'取千小'=>['p1'=>'01234'],'取千单'=>['p1'=>'13579'],'取千双'=>['p1'=>'02468'],'除千大'=>['p1_0'=>'56789'],'除千小'=>['p1_0'=>'01234'],'除千单'=>['p1_0'=>'13579'],'除千双'=>['p1_0'=>'02468'],
            '取百大'=>['p2'=>'56789'],'取百小'=>['p2'=>'01234'],'取百单'=>['p2'=>'13579'],'取百双'=>['p2'=>'02468'],'除百大'=>['p2_0'=>'56789'],'除百小'=>['p2_0'=>'01234'],'除百单'=>['p2_0'=>'13579'],'除百双'=>['p2_0'=>'02468'],
            '取十大'=>['p3'=>'56789'],'取十小'=>['p3'=>'01234'],'取十单'=>['p3'=>'13579'],'取十双'=>['p3'=>'02468'],'除十大'=>['p3_0'=>'56789'],'除十小'=>['p3_0'=>'01234'],'除十单'=>['p3_0'=>'13579'],'除十双'=>['p3_0'=>'02468'],
            '取个大'=>['p4'=>'56789'],'取个小'=>['p4'=>'01234'],'取个单'=>['p4'=>'13579'],'取个双'=>['p4'=>'02468'],'除个大'=>['p4_0'=>'56789'],'除个小'=>['p4_0'=>'01234'],'除个单'=>['p4_0'=>'13579'],'除个双'=>['p4_0'=>'02468'],
            '取五大'=>['p5'=>'56789'],'取五小'=>['p5'=>'01234'],'取五单'=>['p5'=>'13579'],'取五双'=>['p5'=>'02468'],'除五大'=>['p5_0'=>'56789'],'除五小'=>['p5_0'=>'01234'],'除五单'=>['p5_0'=>'13579'],'除五双'=>['p5_0'=>'02468'],
        ];
        foreach ($get_types as $key=>$get_type){
            if(strpos($codes_desc, $key) !== false){
                $data = array_merge($data, $get_types[$key]);
            }
        }

        $get_num_types = [
            '取千'=>'p1','除千'=>'p1_0',
            '取百'=>'p2','除百'=>'p2_0',
            '取十'=>'p3','除十'=>'p3_0',
            '取个'=>'p4','除个'=>'p4_0',
            '取五'=>'p5','除五'=>'p5_0',
        ];
        foreach ($get_num_types as $get_num_type=>$val){
            if(strpos($codes_desc, $get_num_type) !== false){
                preg_match("/".$get_num_type."\d+/", $codes_desc, $matches);
                $match_codes = str_replace($get_num_type, '', $matches[0]);
                $data = array_merge($data, [$val=>$match_codes]);
            }
        }

        return $data;
    }

    /**
     * @desc 号码类型筛选
     * @param $codes_desc
     * @param $data
     * @return mixed
     */
    public static function getCodeType($codes_desc, &$data){

        # 双重:type_2
        if(strpos($codes_desc, '除双重') !== false){
            $data['type_2'] = 0;
        }
        if(strpos($codes_desc, '取双重') !== false){
            $data['type_2'] = 1;
        }

        # 三重:type_3
        if(strpos($codes_desc, '除三重') !== false){
            $data['type_3'] = 0;
        }
        if(strpos($codes_desc, '取三重') !== false){
            $data['type_3'] = 1;
        }
        # 四重:type_4
        if(strpos($codes_desc, '除四重') !== false){
            $data['type_4'] = 0;
        }
        if(strpos($codes_desc, '取四重') !== false){
            $data['type_4'] = 1;
        }

        # 双双重、两双重：type_22
        if(strpos($codes_desc, '除双双重') !== false OR strpos($codes_desc, '除两双重') !== false){
            $data['type_22'] = 0;
        }
        if(strpos($codes_desc, '取双双重') !== false OR strpos($codes_desc, '取两双重') !== false){
            $data['type_22'] = 1;
        }

        # 二兄弟、两兄弟、兄弟
        if(strpos($codes_desc, '取二兄弟') !== false OR strpos($codes_desc, '取兄弟') !== false OR strpos($codes_desc, '取两兄弟') !== false){
            $data['type_2b'] = 1;
        }
        if(strpos($codes_desc, '除二兄弟') !== false OR strpos($codes_desc, '除兄弟') !== false OR strpos($codes_desc, '除两兄弟') !== false){
            $data['type_2b'] = 0;
        }

        return $data;
    }

    /**
     * @desc 获取一定号码
     * @param $codes_hz ['p1'=>'123', 'p2'=>'234', 'p3'=>'3267', 'p4'=>'5678', 'p5'=>'8095']
     * @return array
     */
    public static function getOneFixedCode($codes_hz){
        $codeArr = [];
        //$str = implode(',', $codes_hz);
        $poss = ['p1', 'p2', 'p3', 'p4', 'p5'];
        foreach ($poss as $pos){
            if(!isset($codes_hz[$pos]) OR empty($codes_hz[$pos])){
                $codeArr[$pos] = '';
            }else{
                $codeArr[$pos] = $codes_hz[$pos];
            }
        }

        return [implode(',', $codeArr)];
    }

    /**
     * @desc 例如:23456头尾各1、千百十456789各0.1、头尾23456各1、千百十456789各0.1
     * @param $codes_desc
     * @param array $data
     * @return array
     */
    public static function getCodeTypeDw($codes_desc, &$data = []){

        $posData = [ # 支持不同汉族类型的位置翻译成数据表字段
            '千百十'=>['p1', 'p2', 'p3'], '千百个'=>['p1', 'p2', 'p4'], '千十个'=>['p1', 'p3', 'p4'], '百十个'=>['p2', 'p3', 'p4'],
            '千百'=>['p1', 'p2'], '千十'=>['p1', 'p3'], '千个'=>['p1', 'p4'], '百十'=>['p2', 'p3'], '百个'=>['p2','p4'], '十个'=>['p3','p4'],
            '千'=>['p1'], '百'=>['p2'],'十'=>['p3'], '个'=>['p4'], '五'=>['p5'],
        ];

        $pds = [ # 文字跟上面的数组一一对应
            '千百十'=>['千', '百', '十'], '千百个'=>['千', '百', '个'], '千十个'=>['千', '十', '个'], '百十个'=>['百', '十', '个'],
            '千百'=>['千', '百'], '千十'=>['千', '十'],'千个'=>['千', '个'], '百十'=>['百', '十'], '百个'=>['百', '个'], '十个'=>['十', '个'],
            '千'=>['千'], '百'=>['百'],'十'=>['十'], '个'=>['个'], '五'=>['五'],
        ];

        $is_num_head = 0;
        preg_match("/^[0-9]+/", $codes_desc, $is_num_head_matches);  # 匹配是否数字开头
        if(!empty($is_num_head_matches[0])){
            $is_num_head = 1;
        }

        //p([$is_num_head, $codes_desc, $is_num_head_matches]);
        //p($codes_desc);
        $hasOp = [];
        foreach ($posData as $key=>$poss){
            //if(in_array($key, $hasOp)) break;
            if(strpos($codes_desc, $key) !== false) {
                $hasOp = array_merge($hasOp, $pds[$key]);

                if($is_num_head){
                    preg_match("/[0-9]+".$key."/", $codes_desc, $matches2);  # 023468头尾、 123百567千 数字开头
                }else{
                    preg_match("/".$key."[0-9]+/", $codes_desc, $matches1);  # 头百尾23456、头尾，中文开头
                    /* 新改造，暂时注释掉
                    if(empty($matches1[0])){
                        preg_match("/".$key."[0-9]+/", $codes_desc, $matches1);  # 头百尾23456、头尾
                    }
                    preg_match("/^[0-9]+".$key."/", $codes_desc, $matches2);  # 023468头尾
                    if(empty($matches2[0])){
                        preg_match("/".$key."[0-9]+/", $codes_desc, $matches2);  # 头百尾23456、头尾
                    }
                    */
                }

                $matches = max($matches1[0], $matches2[0]);
                //p(['matches1'=>$matches1, 'matches2'=>$matches2, 'matches'=>$matches, $poss]);
                $nums = str_replace($key, '', $matches);
                foreach ($poss as $pos){
                    //p([$key, $pos, $nums, $matches], 0);
                    $data[$pos] = $nums;
                }
                if(count($poss)>1) return $data;
            }
        }

        return $data;
    }

    /**
     * @desc 例如:234倒两各1、456倒三定各1、2345倒四定各1
     * @param $codes_desc
     * @param array $data
     * @return array
     */
    public static function getCodeTypeDao($codes_desc, &$data = []){

        if(strpos($codes_desc, '倒二定') !== false){
            $data['code_type'] = 2;
        }
        if(strpos($codes_desc, '倒三定') !== false){
            $data['code_type'] = 3;
        }
        if(strpos($codes_desc, '倒四定') !== false){
            $data['code_type'] = 4;
        }

        preg_match("/^[0-9]+倒/", $codes_desc, $matches2);  # 023468倒x定
        if(empty($matches2[0])){
            preg_match("/倒[0-9]+/", $codes_desc, $matches2);  # 头百尾23456、头尾
        }
        if(!empty($matches2[0])){
            $codes = str_replace('倒', '', $matches2[0]);
            $data['arise'] = $codes;
        }

        return $data;
    }

    /**
     * @desc 例如:234千走456两定各1、234千走456三定各1、234千走456三定各1
     * @param $codes_desc
     * @param array $data
     * @return array
     */
    public static function getCodeTypeZouYi($codes_desc, &$data = []){

        $codes_desc = str_replace('走移', '走', $codes_desc);

        if(strpos($codes_desc, '走') !== false){
            $data['code_type'] = 2;
        }

        preg_match("/走[0-9]+/", $codes_desc, $matches2);  # 走345
        if(!empty($matches2[0])){
            $codes = str_replace('走', '', $matches2[0]);
            $data['zou_yi'] = $codes;
        }

        return $data;
    }

    /**
     * @desc 例如:千12345百12345十67890合分2345各0.1 - 暂支持不定位合分
     * @param $codes_desc
     * @param array $data
     * @return array
     */
    public static function getCodeTypeFixPosHeFen($codes_desc, &$data = []){
        # no_fix_hefen:不定位合分值、no_fix_hefen_pos:1两数2三数

        if(strpos($codes_desc, '三数合分') !== false OR strpos($codes_desc, '三数合') !== false){
            $data['no_fix_hefen_pos'] = 2;
            preg_match("/三数合分[0-9]+/", $codes_desc, $matches2);  # 023468倒x定
            $codes = str_replace('三数合分', '', $matches2[0]);
            $codes = str_replace('三数合', '', $codes);
        }else{
            if(strpos($codes_desc, '合分') !== false){# 默认两数合分
                $data['no_fix_hefen_pos'] = 1;
                preg_match("/合分[0-9]+/", $codes_desc, $matches2);  # 023468倒x定
                $codes = str_replace('两数合分', '', $matches2[0]);
                $codes = str_replace('两数合', '', $codes);
                $codes = str_replace('合分', '', $codes);
            }

        }
        if(!empty($codes)){
            $data['no_fix_hefen'] = $codes;
        }

        return $data;
    }

    /**
     * @desc 获取倍数
     * @param $codes_desc
     * @param array $data
     * @return array
     */
    public static function getSingleByDesc($codes_desc, &$data = []){

        if(strpos($codes_desc, '各') !== false){
            preg_match("/各([0-9].)?(\d)+/", $codes_desc, $matches);
            $data['single'] = str_replace('各', '', $matches[0]);
        }

        return $data;
    }

    /**
     * @param $desc
     * @return float
     */
    public static function getNeedMoneyByDesc($desc){
        $money = 10.00;

        return $money;
    }

    /**
     * @desc 按计划id做利润数据统计
     * @return array
     */
    public static function staticPlansProfits($limit = 1000){
        $rst = ['status'=>200, 'msg'=>'操作成功'];

        $m = \Yii::$app->cache;
        $where = ['OR', ['AND', ['=', 'account', 'admin'], ['=', 'status', 1]]];
        $planids = explode(',', SystemConfig::findOne(['key'=>'system_static_plan_ids'])->value);
        foreach ($planids as $planid){
            $where = array_merge($where, [['=', 'id', $planid]]);
        }

        $plans = UserSysPlans::find()->where($where)->all();

        $time = time();
        foreach ($plans as $plan){
            $mkey = 'staticPlansProfits_plan_'.$plan->id;
            if(!$last_id = $m->get($mkey)){
                $last_id = 0;
            }
            $lottery_type = $plan->lottery_type;
            $where = ['AND', ['=', 'lottery_type', $lottery_type], ['>=', 'date', '2018-01-01']];
            $last_qihao = NumService::getLastStaticProfitsQihao($lottery_type, $plan->id);
            if($last_qihao){
                $where = array_merge($where, [['>', 'qihao', $last_qihao]]);
            }else{
                $where = array_merge($where, [['>', 'id', $last_id]]);
            }
            $plan_mkey = 'plan_id_mkey_'.$plan->id;
            if(!$codesStrs = $m->get($plan_mkey)){
                $codesStrs = BetService::getPlansAllCodesType1($plan->tz_type, $plan->buy_type, $plan->hz_Arr, $plan->id);
                $m->set($plan_mkey, $codesStrs, 5 * 60);
            }
            $count = count(explode('@', $codesStrs));
            $bet_money = $count * $plan->single;

            $SscKjDatas = SscKjData::find()->where($where)->limit($limit)->all();
            foreach ($SscKjDatas as $SscKjData){
                $qihao = $SscKjData->qihao;
                # 存在记录则 continue
                $where = ['AND', ['=', 'qihao', $qihao], ['=', 'plan_id', $plan->id]];
                if($StaticProfits = StaticProfits::find()->where($where)->limit(1)->one()){
                    continue;
                }

                # static_profits 表
                $where = ['AND', ['=', 'plan_id', $plan->id], ['=', 'qihao', $SscKjData->qihao], ['=', 'lottery_type', $lottery_type]];
                if($StaticProfits = StaticProfits::find()->where($where)->limit(1)->one()){
                    continue;
                }

                $kjCode = substr($SscKjData->code_str, 0, 7);
                if(strpos($codesStrs, $kjCode) !== false){
                    $flag = true;
                    # 中奖
                    $zjBouns = 9950 * $plan->single;
                    $profits = $zjBouns - $bet_money;# 中奖金额 - 投注金额
                }else{
                    $flag = false;
                    $zjBouns = 0;
                    $profits = $zjBouns - $bet_money;# 中奖金额 - 投注金额
                }

                $StaticProfits = new StaticProfits();
                $cut_profits = $profits + NumService::getCutProfits($qihao, $plan->id, $lottery_type); # 截至当前期利润
                $setDatas = [
                    'plan_id' => $plan->id,
                    'uid' => $plan->uid,
                    'static_time' => substr($SscKjData->date,0,7),
                    'playway' => $plan->playway,
                    'qihao' => $qihao,
                    'kj_code' => $SscKjData->code_str,
                    'tz_money' => $bet_money,
                    'profits' => $profits,
                    'zj_bouns' => $zjBouns,
                    'lottery_type' => $lottery_type,
                    'cut_profits' => substr($SscKjData->date,8) == '01' ? $profits : $cut_profits,
                    'tz_time' => (string)$time,
                    'created_at' => $time,
                    'updated_at' => $time,
                ];
                $StaticProfits->setAttributes($setDatas);
                $r = $StaticProfits->save();
                if(!$r){
                    p($StaticProfits->getErrors());
                }
                $rst['data'][$qihao]['rst'] = $r;
                $rst['data'][$qihao]['profits'] = $profits;
                $rst['data'][$qihao]['bet_money'] = $bet_money;
                $rst['data'][$qihao]['kj_codes'] = $kjCode;
                $rst['data'][$qihao]['cut_profits'] = $cut_profits;
                //$rst['data'][$qihao]['codesStrs'] = $codesStrs;
                $rst['data'][$qihao]['flag'] = (int)$flag;

                //p(['flag'=>(int)$flag, 'kjCode'=>$kjCode, 'profits'=>$profits, 'codesArr'=>$codesStrs,  /*$SscKjData->attributes*/]);
            }
        }
        Tool_Common::log('staticPlansProfits', 'INFO', '数据统计', $rst);

        return $rst;
    }

    /**
     * @desc 获取最后统计的期号
     * @param $lottery_type
     * @return mixed
     */
    public static function getLastStaticProfitsQihao($lottery_type, $plan_id = ''){

        $where = ['AND', ['=', 'plan_id', $plan_id], ['=', 'lottery_type', $lottery_type]];
        $StaticProfits = StaticProfits::find()->where($where)->orderBy(['qihao'=>SORT_DESC])->limit(1)->one();
        $last_qihao = $StaticProfits->qihao;
        if(!$last_qihao){
            $last_qihao = '';
        }
        //$m = \Yii::$app->cache;
        //$pkey = NumService::buildLastStaticProfitsKey($plan_id, $lottery_type);
        //$m->set($pkey, $last_qihao, 3600);

        return $last_qihao;
    }

    /**
     * @desc 返回统计期号key
     * @return string
     */
    public static function buildLastStaticProfitsKey($plan_id, $lottery_type = DEFAULT_LOTTERY_TYPE){
        $pkey = 'mkey_staticPlansProfits_0_'.$plan_id.'_'.$lottery_type;

        return $pkey;
    }

    /**
     * @desc 获取截止上一期的利润
     * @param $qihao
     * @param $plan_id
     * @param int $lottery_type
     * @return float
     */
    public static function getCutProfits($qihao, $plan_id, $lottery_type = DEFAULT_LOTTERY_TYPE){
        $profits = 0.00;

        $where = ['AND', ['<', 'qihao', $qihao], ['=', 'plan_id', $plan_id], ['=', 'lottery_type', $lottery_type]];
        $StaticProfits = StaticProfits::find()->where($where)->orderBy(['id'=>SORT_DESC])->limit(1)->one();
        if($StaticProfits){
            $profits = $StaticProfits->cut_profits;
        }

        return $profits;
    }

    /**
     * @desc 获取过滤的号码
     * @param int $filter_type
     * @param array $filters
     * @return array
     */
    public static function getCodesByCodesHz($filters=[], object $plan, $lottery_type=DEFAULT_LOTTERY_TYPE){
        $lottery_type = $filters['lottery_type'] ? : $lottery_type; # 彩种
        $filter_type = $filters['filter_type']; # 三四定类型中，过滤类型:默认类型1同期2历史数据
        $filter_poses = $filters['filter_poses']; # 过滤位置
        $playway = $filters['playway'] ? : 3; # 默认四定
        $code_type = NumService::$playway_to_code_type[$filters['playway']] ? : 4; # 默认四定

        $filter = NumService::getFilterTypeDatas($playway)[$filter_type];
        //$start_qihao = $filters['current_qihao'] ? : HN0898Service::getCurrentQihao($lottery_type); # 针对那一期过滤，默认为：当前期号
        $start_qihao = NumService::getPlanBetCurrentQihao($plan, $lottery_type);
        //p(['start_qihao'=>$start_qihao, 'filter'=>$filter]);
        $query = Num4Type::find()->select(['code']);
        $where = ['AND', ['=', 'code_type', $code_type] , '1=1'];
        $query->where($where);
        if($filter['type'] == 1){ # 过滤前x期号码 type 一般情况下等于 filter_type
            $limit = $filters['filter_nums'] ? : ($filter['nums'] ?: 1);
            $filter_codes_where = ['AND', ['<', 'qihao', $start_qihao], ['=', 'lottery_type', $lottery_type]];
            $SscKjDatas = SscKjData::find()->where($filter_codes_where)->orderBy(['id'=>SORT_DESC])->limit($limit)->all();
            $filter_tmp_where = ['OR'];
            foreach ($filter_poses as $pos){
                $code_pos = 'code'.$pos;
                foreach ($SscKjDatas as $SscKjData){
                    $filter_tmp_where[] = ['=', 'code_'.$pos, $SscKjData->$code_pos];
                }
            }
            $diff_poses = array_diff(NumService::$ALL_POSES, $filter_poses);
            foreach ($diff_poses as $pos){
                $where[] = ['OR', ['=', 'code_'.$pos, 'X'], ['=', 'code_'.$pos, '']];
            }

            //p(array_merge($where, [$filter_tmp_where]));
            $query->where(array_merge($where, [$filter_tmp_where]));
        }
        $Num4Type = $query->asArray()->all();
        $filter_codes = ArrayHelper::getColumn($Num4Type, 'code');

        return $filter_codes;
    }

    /**
     * @desc 获取当前计划即将下注的期号：正常下注则获取当前时间对应的期号，模拟下注则根据下注记录获取即将期号
     * @param string $filter_start_qihao
     * @param int $lottery_type
     * @return float|int|mixed|string
     */
    public static function getPlanBetCurrentQihao(object $plan, $lottery_type=DEFAULT_LOTTERY_TYPE) {
        try {
            $hzArr = yii\helpers\Json::decode($plan->hz_Arr, true);
            $current_kj_qihao = $hzArr['filters']['current_kj_qihao'];
            if(empty($current_kj_qihao)){
                if($plan->is_batch_simulate == 1){
                    $current_kj_qihao = $hzArr['filters']['start_qihao'];
                }else{
                    $current_kj_qihao = HN0898Service::getCurrentQihao($lottery_type);
                }
            }
            $next_qihao = KjDataGet::getNextQihaoByQihao($current_kj_qihao, $lottery_type);

            if(empty($next_qihao)){
                $next_qihao = HN0898Service::getQihao($lottery_type); # 针对哪一期过滤，默认为：当前期号
            }
        }catch (\Exception $exception){
            Tool_Common::log('/bet/'.__FUNCTION__, 'ERR', '模拟投注计划-异常', ['plan_id'=>$plan->id, 'lottery_type' => $lottery_type, 'next_qihao'=>$next_qihao, 'err_msg'=>$exception->getMessage()]);
            throw new \Exception($exception->getMessage(), $exception->getCode());
        }
        Tool_Common::log('/bet/'.__FUNCTION__, 'ERR', '模拟投注计划', ['plan_id'=>$plan->id, 'lottery_type' => $lottery_type, 'next_qihao'=>$next_qihao]);

        return $next_qihao;
    }

    public static function getHasOpenEndQihao($lotter_type=DEFAULT_LOTTERY_TYPE){
        $m = \Yii::$app->cache;
        $mkey = 'getHasOpenEndQihao_x0_'.$lotter_type;
        $endQihao = $m->get($mkey);
        if(empty($endQihao)){
            $endQihao = SscKjData::find()->where(['lottery_type'=>$lotter_type])->orderBy(['id'=>SORT_DESC])->limit(1)->one()->qihao;
            $m->set($mkey, $endQihao, 30);
        }

        return $endQihao;
    }

    /**
     * @desc 给定天数天数
     * @param int $day_nums
     * @param int $lottery_type
     * @return float|int|string
     */
    public static function getQihaoByDaysBefore($day_nums=7, $lottery_type=DEFAULT_LOTTERY_TYPE){
        $kj_times = date('Y-m-d', time() - $day_nums * 86400);

        $where = ['AND', ['>=', 'date', $kj_times], ['=', 'lottery_type', $lottery_type]];
        $SscKjData = SscKjData::find()->where($where)->orderBy(['id'=>SORT_ASC])->limit(1)->one();
        if(!empty($SscKjData)){
            $qihao = $SscKjData->qihao;
        }else{
            $qihao = HN0898Service::getQihao($lottery_type); # 针对哪一期过滤，默认为：当前期号
        }

        return $qihao;
    }

    /**
     * @desc 过滤条件类型
     * @param int $playway
     * @return array[]|\array[][]
     */
    public static function getFilterTypeDatas($playway=3){
        $filter_type_datas = [
            1 => [ # 二定
                1 => [ 'type'=>1,  'desc'=>'过滤上前x期同位置同号码'],
            ],
            2 => [ # 三定
                1 => [ 'type'=>1,  'desc'=>'过滤上前x期同位置同号码'],
            ],
            3 => [ # 四定
                1 => [ 'type'=>1,  'desc'=>'过滤上前x期同位置同号码'],
                2 => [ 'type'=>2,  'desc'=>'四个号码不同时，下一期排查当前号码并且取兄弟'],
            ],
        ];

        if(isset($filter_type_datas[$playway])) return $filter_type_datas[$playway];

        return $filter_type_datas;
    }

    /**
     * @desc 模拟过滤的号码类型
     * @return string[]
     */
    public static function get_code_filter_types(){
        return [
            '' => '-请选择-',
            //1 => '同位置前x期',
            2 => '四定过滤类型2', # 四个号码不同时，下一期排查当前号码并且取兄弟
            3 => '四定过滤类型3', # 四个号码不同时，排除同位置同类型号码
        ];
    }

    /**
     * str => type
     * @param string $str
     * @return array|mixed
     */
    public static function getType4dx($str='2大2小'){
        $datas = NumService::$type_dx_datas;
        $indexDatas = array_flip($datas);

        if(isset($indexDatas[$str])) return $indexDatas[$str];

        return $indexDatas;
    }

    /**
     * type => type_str
     * @param int $type
     * @return string|string[]
     */
    public static function getType4dxStr($type=1){
        $datas = NumService::$type_dx_datas;
        if(isset($datas[$type])) return $datas[$type];

        return $datas;
    }

    /**
     * 获取大小类型号码
     * @param $num
     * @return int[]
     */
    public static function getDxTypeByCode($num): array
    {
        if(in_array($num, NumService::$MIN_CODES)){
            # 小
            $codes = NumService::$MIN_CODES;
        }elseif (in_array($num, NumService::$MAX_CODES)){
            # 大
            $codes = NumService::$MAX_CODES;
        }

        return $codes;
    }

    /**
     * 获取大小类型号码 - 反向
     * @param $num
     * @return int[]
     */
    public static function getDxTypeFanByCode($num){
        if(in_array($num, NumService::$MIN_CODES)){
            # 大
            $codes = NumService::$MAX_CODES;
        }elseif (in_array($num, NumService::$MAX_CODES)){
            # 小
            $codes = NumService::$MIN_CODES;
        }

        return $codes;
    }

    /**
     * 获取单双类型号码 - 反向
     * @param $num
     * @return int[]
     */
    public static function getDsTypeFanByCode($num): array
    {
        if(in_array($num, NumService::$SINGLE_CODES)){
            # 双
            $codes = NumService::$DOUBLE_CODES;
        }elseif (in_array($num, NumService::$DOUBLE_CODES)){
            # 单
            $codes = NumService::$SINGLE_CODES;
        }

        return $codes;
    }

    /**
     * 获取单双类型号码
     * @param $num
     * @return int[]
     */
    public static function getDsTypeByCode($num){
        if(in_array($num, NumService::$SINGLE_CODES)){
            # 单
            $codes = NumService::$SINGLE_CODES;
        }elseif (in_array($num, NumService::$DOUBLE_CODES)){
            # 双
            $codes = NumService::$DOUBLE_CODES;
        }

        return $codes;
    }
}
