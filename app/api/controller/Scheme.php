<?php
/**
 * Created by PhpStorm.
 * User: yuxi
 * Date: 2021-02-04
 * Time: 9:13
 */
namespace app\api\controller;
use app\BaseController;
use tools\jwt\Token;  //封装命名空间\类
use think\facade\Db;
use think\facade\Request;
use app\common\model\OsUser;


class Scheme extends BaseController
{
    protected $user_info;
    protected $param;

    public function __construct(\think\Request $request = null)
    {
        parent::__construct($request);
        $this->param = $this->request->param();
        $this->user_info = Db::name('index_vip_custom')
            ->where('vip_custom_phone',$this->param['mobile'])
            ->where('status',1)
            ->find();
    }

    //方案列表
    public function scheme_list(){
        halt(1);
        $scheme_data = Db::name('crm_product_scheme')
            ->where('status',1)
            ->where('vip_custom_hash',$this->user_info['vip_custom_hash'])
            ->field('scheme_name,create_date,designer_uid,check_status,project_address,scheme_hash,demand_content,demand_status')
            ->select();
        foreach ($scheme_data as $k=>$v){
            $scheme_data[$k]['create_date'] = date('Y-m-d',$v['create_date']);
            $scheme_data[$k]['designer'] = Db::name('os_user')->where('uid',$v['designer_uid'])->value('nickanme');
        }
        return  json(['code' => 1, 'data' => ['scheme_data'=>$scheme_data], 'msg' => 'ok']);
    }

    //设计师详情页
    public function designer_info(){
        $scheme_info = Db::name('crm_product_scheme')->where('scheme_hash',$this->param['scheme_hash'])->find();
        $designer_info = Db::name('os_user_center')->where('uid',$scheme_info['designer_uid'])->field('phoneNum,email,nickname,uid,avatar')->find();
        $designer_info['avatar'] = config('wxprogramme.access_domain').$designer_info['avatar'];

        return  json(['code' => 1, 'data' => ['designer_info'=>$designer_info], 'msg' => 'ok']);
    }

    //客户需求详情
    public function demand_view(){
        $scheme_info = Db::name('crm_product_scheme')->where('scheme_hash',$this->param['scheme_hash'])->find();
        $demand_info = $scheme_info['demand_content'];

        return  json(['code' => 1, 'data' => ['demand_info'=>$demand_info], 'msg' => 'ok']);
    }

    //方案配置详情
    public function scheme_view(){
        $hash = $this->param['scheme_hash'];
        //方案基本信息
        $scheme_info = Db::name('crm_product_scheme')
            ->where('scheme_hash',$hash)
            ->find();
        //方案详情
        $scheme_match_data = Db::name('crm_product_scheme_match_detail')
            ->where('scheme_hash',$hash)
            ->where('product_hash_str','<>','')
            ->where('product_hash_str is not null')
            ->where('status',1)
            ->select();
        //地暖用的
        $dinuan_param_data = [
            [
                'param_name' => 'louceng',
                'param_text' => '楼层',
            ],
            [
                'param_name' => 'mianji',
                'param_text' => '面积',
            ],
            [
                'param_name' => 'all_mianji',
                'param_text' => '总面积',
            ],
            [
                'param_name' => 'fangxing',
                'param_text' => '房型',
            ],
            [
                'param_name' => 'result',
                'param_text' => '结果',
            ],
        ];

        $return_data = [];
        $all_market_money = 0;
        $all_sale_money = 0;
        $all_install_money = 0;
        foreach ($scheme_match_data as $k=>$v){
            //系列
            $temp_data = [];
            $temp_data['series_name'] = Db::name('crm_product_scheme_match_class')
                ->where('product_class_hash',$v['series'])
                ->value('product_class_name');
            $temp_data['series_price'] = $v['series_price'];
            //方案参数
            $class_param_data = Db::name('crm_product_scheme_match_class_input_param')
                ->where('product_class_hash',$v['series'])
                ->where('status',1)
                ->where('is_show',1)
                ->select();
            $temp_data['product_data'] = [];
            //产品
            $product_hash_arr = explode(',',$v['product_hash_str']);
            //循环查类别，参数，图片，名称，零售价，折扣价，数量，单位，该产品总金额
            $market_price_arr = explode(',',$v['market_price_str']);
            $sale_price_arr = explode(',',$v['sale_price_str']);
            $install_money_arr = explode(',',$v['install_price_str']);
            $product_remark_arr = explode(',',$v['product_remark_str']);
            $product_num_arr = explode(',',$v['product_num_str']);
            $input_param_arr = json_decode($v['select_content'],true);
            foreach ($product_hash_arr as $kk=>$vv){
                $select_content = [];
                if(!empty($vv) && $vv != ' '){
                    if($v['series'] == '460c3c5f9548ace66d0a5bc878db878e'){   //地暖的单独来
                        foreach ($input_param_arr[$kk] as $kkk=>$vvv){
                            foreach ($dinuan_param_data as $kkkk=>$vvvv){
                                if($kkk == $vvvv['param_name']){
                                    $select_content[$vvvv['param_text']] = $vvv;
                                }
                            }
                        }
                    }else{
                        foreach ($input_param_arr[$kk] as $kkk=>$vvv){
                            foreach ($class_param_data as $kkkk=>$vvvv){
                                if($kkk == $vvvv['param_name']){
                                    if($vvvv['param_text'] == '面积㎡'){
                                        $select_content[$vvvv['param_text']] = $vvv . '㎡';
                                    }elseif($vvvv['param_text'] == '高度m'){
                                        $select_content[$vvvv['param_text']] = $vvv . 'm';
                                    }else{
                                        $select_content[$vvvv['param_text']] = $vvv;
                                    }
                                }
                            }
                        }
                    }

                    $product_info = Db::name('wms_product')
                        ->where('product_hash',$vv)
                        ->field('product_name,product_class_id,product_unit,product_hash')
                        ->find();
                    $temp_data['product_data'][] = [
                        'product_class_name' => Db::name('wms_product_class')
                            ->where('product_class_id',$product_info['product_class_id'])
                            ->value('product_class_name'),
                        'input_param' => $select_content,
                        'product_img' => product_img(20,'product',$vv,1)[0],
                        'product_name' => $product_info['product_name'],
                        'market_price' => $market_price_arr[$kk],
                        'sale_price' => $sale_price_arr[$kk],
                        'product_num' => $product_num_arr[$kk],
                        'install_price' => $install_money_arr[$kk],
                        'product_remark' => $product_remark_arr[$kk],
                        'product_unit' => $product_info['product_unit'],
                        'product_all_money' => round($product_num_arr[$kk] * $sale_price_arr[$kk],2)
                    ];
                    //价格相关
                    $all_market_money += round($product_num_arr[$kk] * $market_price_arr[$kk],2);
                    $all_sale_money += round($product_num_arr[$kk] * $sale_price_arr[$kk],2);
                    $all_install_money += round($product_num_arr[$kk] * $install_money_arr[$kk],2);
                }
            }
            $return_data[] = $temp_data;
        }
        $all_money = $all_sale_money + $all_install_money;
        $price_data = [
//            'all_market_money' => $all_market_money,
//            'all_sale_money' => $all_sale_money,
//            'all_install_money' => $all_install_money,
//            'all_money' => $all_money,
            'market_price_all' => $scheme_info['market_price_all'],
            'sale_price_all' => $scheme_info['sale_price_all'],
            'discount_price_all' => $scheme_info['discount_price_all'],
            'discount_rate' => $scheme_info['discount_rate'],
            'install_rate' => $scheme_info['install_rate'],
            'install_price_all' => $scheme_info['install_price_all'],
            'install_other_price' => $scheme_info['install_other_price'],
        ];
        //客户等信息
        $custom_data = [
            'scheme_name' => $scheme_info['scheme_name'],
            'custom_name' => $scheme_info['custom_name'],
            'contact_name' => $scheme_info['contact_name'],
            'contact_phone' => $scheme_info['contact_phone'],
            'expected_date' => $scheme_info['expected_date'],
            'delivery_date' => $scheme_info['delivery_date'],
            'pay_way_name' => $scheme_info['pay_way_name'],
            'product_remark' => $scheme_info['product_remark'],
            'project_address' => $scheme_info['project_address'],
            'project_remark' => $scheme_info['project_remark'],
            'youzhujia_url' => $scheme_info['youzhujia_url'],
            'order_hash' => $scheme_info['order_hash'],
            'make_order_status' => $scheme_info['make_order_status'],
            'install_money' => $scheme_info['install_money'],
        ];


        return json(['code'=>1,'data'=>['return_data'=>$return_data,'price_data'=>$price_data,'custom_data'=>$custom_data],'msg'=>'ok']);
    }



}