<?php
namespace common\tools;
/**
 * 数组操作
 * @openskyli
 */
class UtilArray
{
    /**
     * 获取分页 limit 对象
     * @param int|null $page
     * @param int $limit
     * @return array [type]
     */
    public static function getLimit(?int $page = 0, int $limit = 0  ): array
    {
        if ( empty( $page) ) 
        {
           $page = \Yii::$app->request->getIsPost()?\Yii::$app->request->post('page',1):\Yii::$app->request->get('page',1);
        }
        if ( empty( $limit ) ) {
           $limit = \Yii::$app->request->getIsPost()?\Yii::$app->request->post('limit',20):\Yii::$app->request->get('limit',20);
        }
        $page = ($page - 1) < 0 ? 0 : $page -1 ;


        return [ $page * $limit, $limit];
    }
    /**
     * 以数组某个字段作为键名返回
     * @param  array  $lData 
     * @param  [type] $field 
     * @return array        
     */
    public static function getFieldKey( array $lData, $field ): array 
    {
        $l_data = array();
        foreach ($lData as $key => $value) {
            if (isset( $value[$field] ) ) {
                $l_data[$value[$field]] = $value;
            }
        }
        return $l_data;

    }

    /**
     * 计算
     * @param  [type] $aAmounts [description]
     * @return float [type]           [description]
     */
    public static function getFloatSum( $aAmounts ): float
    {
        $amount = 0;
        foreach ($aAmounts as $key => $value) {
            $amount = floatval( $value )  + $amount;
        }
        return round($amount,2);
    }

    /**
     * 以数组某个字段作为键名返回
     * @param  array  $lData 
     * @param  [type] $field 
     * @return array        
     */
    public static function getFieldKeys( array $lData, $field ): array 
    {
        $l_data = array();
        foreach ($lData as $key => $value) {
            if (isset( $value[$field] ) ) {
                if ( empty( $l_data[$value[$field]] ) ) 
                {
                    $l_data[$value[$field]] =[];
                }
                $l_data[$value[$field]][] = $value;
            }
        }
        return $l_data;

    }
    public static function autoFilterField( $headers, $rows ): array
    {

        $data = [];
        foreach ($rows as $key => $row) {
            $item = [];
            foreach ($headers as $field => $field_name) 
            {
               $item[$field] = $row[ $field ]??'';
            }
            $data[] = $item;
        }
       
        return $data;
    }
    
    public static function autoFilterNumKeyField( $headers, $rows , $text_fields=[]): array
    {

        $data = [];
        foreach ($rows as $key => $row) {
            $item = [];
            foreach ($headers as $field => $field_name) {
               if(in_array($field, $text_fields)){
                   $item[] = $row[ $field ] ? $row[ $field ]."\t" : "";
               }else{
                   $item[] = $row[ $field ] ? $row[ $field ] : "";
               }
               
            }
            $data[] = $item;
        }
        unset($rows);
       
        return $data;
    }

    /**
     * 返回左边有独有的数据
     * @param array $leftArr [description]
     * @param array $rightArr [description]
     * @return array [type]           [description]
     */
    public static function getLOnlyDiff( array $leftArr, array $rightArr )
    {
        return array_diff( $leftArr, $rightArr );
    }

    /**
     * 返回右边有独有的数据
     * @param array $leftArr [description]
     * @param array $rightArr [description]
     * @return array [type]
     */
    public static function getROnlyDiff( array $leftArr, array $rightArr ): array
    {
        return array_diff($rightArr,$leftArr);
    }

    /**
     * @author 
     * 获取静态选项信息
     * 如如获取状态信息
     * $mVal参数不传时获取全部数据(值=>名称)：array(
     *     1 => '正常',
     *     2 => '停用',
     *     ...
     * )
     * $mVal参数传空字符串时获取全部值: array( 1, 2, ... )
     * $mVal参数传数字时返回对应的名称,没有对应信息时返回空字符串
     * $mVal参数传名称时返回对应的数字,没有对应信息时返回-1
     * @param array $sField 关键字段
     * @param mixed $mVal
     */
    public static function getS( $aS=[],  string $sField,  $mVal = null )
    {
        $a_static = $aS[$sField]??[];


        if ( $mVal === null ) {
            return $a_static;
        } elseif ( $mVal === '' ) {
            return array_keys( $a_static );
        } elseif ( is_numeric( $mVal ) || is_string( $mVal ) ) {
            return $a_static[$mVal] ?? '';
        } else {
            return array_flip( $a_static )[$mVal] ?? -1;
        }
    }

    public static function getSS(string $sField,$v): array
    {
        $data = self::getS($sField);
        $l_data = [];

        foreach ($data as $key => $value) {
            $l_data[] = [
              'name'=>$value,
              'value'=>$key,
              'checked'=>$key==$v?true:false
            ];
        }
        return $l_data;
    }

    /**
     * 快速获取数据
     * @param [type] $aPrams [description]
     */
    public static function MT( $aParams=[] )
    {
        // $aParams = [
        //     'model' =>GoodsSku
        //     'pk' => 'id',
        //     'fields' => array(
        //        'id'=>'goods_id',
        //        'name'=>'goods_name',
        //     ),
        //     'data' => array(),
        // ];

        if (is_string($aParams['pk'])) {
            $aParams['pk'] = array($aParams['pk'] => $aParams['pk']);
        }
        $from_pk = array_keys($aParams['pk'])[0];
        $to_pk = array_values($aParams['pk'])[0];
        $l_pks = array_unique(array_column($aParams['data'], $from_pk));

        if (empty($l_pks)) {
            return $aParams['data'];
        }
        $aParams['fields'][] = $to_pk;

        $l_data = $aParams['model']::find()->where([
            $to_pk => $l_pks
        ])->select($aParams['fields'])
            ->asArray()
            ->all();


        $l_data = self::getFieldKey($l_data, $to_pk);

        foreach ($aParams['data'] as $key => $value) {
            $item = $l_data[$value[$from_pk]] ?? [];
            if (!empty($item)) {
                unset($item[$to_pk]);
            }
            $aParams['data'][$key] = array_merge($value, $item);
        }


        return $aParams['data'];
    }


    /**
     * 通过主键获取
     * @param  array  $lPks   主键ID
     * @param  string $fields 字段
     * @param  string $isPkField 是否使用主键做健名返回
     * @return array
     */
    public function getByPks( $db,  string $tableName, $pkField='id',array $lPks,  $fields=null, $isPkField=false ): array
    {
           $l_pks =[];

           $lPks = array_filter($lPks);

           if( empty( $lPks ) )
           {
              return [];
           }

           foreach ($lPks as $pk)
           {
              $l_pks[] = self::getCacheKey( $tableName, $pk );
           }
           $data = [];

           $l_data=[];


           if( !empty($l_data) ){
              foreach ($l_data as $k => $v) {
                 $l_data[$k] = json_decode($v,true);
              }
           }

           $l_hash_pk = array_column($l_data,$pkField)??[];
           $l_empty_pk = self::getLOnlyDiff( $lPks, $l_hash_pk );

           if( !empty( $l_empty_pk ) ){
               $l_select = M($tableName)
               ->where([
                   $pkField=>array( 'in', $l_empty_pk )
               ])->select();

               $l_select = $query->where([
                   $pkField=>$l_empty_pk
               ])->select('*')
               ->asArray()
               ->all();

               if( !empty($l_select) )
               {
                   foreach ($l_select as $key => $value) {
                      // S( self::getCacheKey( $tableName, $value[ $pkField ] ), $value, 86400 );
                   }
                   $l_data = array_merge_recursive($l_data,$l_select);
               }

           }

           if( !empty( $fields ) ){

               $a_fields = self::getFields($fields);


               foreach ($l_data as $key => $value)
               {

                   $item = [];

                   $item[$pkField] = $value[$pkField];

                   foreach ($value as $k_field => $v) {

                       if( isset( $a_fields[$k_field] )){

                          $item[ $a_fields[$k_field] ] = $v;
                       }
                   }
                   $data[]=$item;
               }

           }else{

               $data = $l_data;
           }



           $data = self::getFieldKey( $data, $pkField );
           if( !empty( $fields ) && empty( $a_fields[$pkField] ) )
           {
               foreach ($data as $key => $value) {
                  unset( $data[$key][$pkField] );
               }
           }

           if( $isPkField==true ){
             return $data;
           }

           $l_data = [];

           foreach ($lPks as $pk) {

              $l_data[]= $data[$pk];

           }
           return $l_data;
    }


    

    public static function getFields( $sFields )
    {
            $sFields = str_replace(PHP_EOL, '', $sFields);

            $fields = explode(',', $sFields );

            $a_fields = [];
            foreach ($fields as $field) {
                $field = trim($field);

                $a_alias = explode(" ",$field);

                if( count( $a_alias )>1 ){
                   $a_fields[trim($a_alias[0])] = trim($a_alias[count($a_alias)-1]);
                }else{
                   $a_fields[$field] = $field;
                }

            }
            return $a_fields;
    }

    /**
     * 缓存键名
     * @param  string $tableName 表名
     * @param  int    $pkValue    键值
     * @return string
     */
    public static function getCacheKey( string $tableName, int $pkValue )
    {
           return join(':',[
             'find',
              $tableName,
              $pkValue
           ]);
    }

    /**
     * 格式化金额
     * @param  [type] $money [description]
     * @return [type]        [description]
     */
    public static function formatMoney( $money )
    {
        return sprintf("%1\$.2f",$money);
    }

}
