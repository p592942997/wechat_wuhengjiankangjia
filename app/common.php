<?php
// 应用公共文件
use think\Db;

/**
 * 字节数Byte转换为KB、MB、GB、TB
 * @param int $size
 * @return string
 */
function xn_file_size($size){
    $units = array('B', 'KB', 'MB', 'GB', 'TB', 'PB');
    for ($i = 0; $size >= 1024 && $i < 5; $i++) $size /= 1024;
    return round($size, 2) . $units[$i];
}

/**
 * 驼峰命名转下划线命名
 * @param $camelCaps
 * @param string $separator
 * @return string
 */
function xn_uncamelize($camelCaps,$separator='_')
{
    return strtolower(preg_replace('/([a-z])([A-Z])/', "$1" . $separator . "$2", $camelCaps));
}

/**
 * 密码加密函数
 * @param $password
 * @return string
 */
function xn_encrypt($password)
{
    $salt = 'xiaoniu_admin';
    return md5(md5($password.$salt));

//    $salt = '444229';
//    return md5(md5($password).$salt);
}

/**
 * 管理员操作日志
 * @param $remark
 */
function xn_add_admin_log($remark)
{
    $data = [
        'admin_id' => session('admin_auth.id'),
        'url' => request()->url(true),
        'ip' => request()->ip(),
        'remark' => $remark,
        'method' =>request()->method(),
        'param' => json_encode(request()->param()),
        'create_time' => time()
    ];
    \app\common\model\AdminLog::insert($data);
}

/**
 * 获取自定义config/cfg目录下的配置
 * 用法： xn_cfg('base') 或 xn_cfg('base.website') 不支持无限极
 * @param string|null $name
 * @param null $default
 * @return array
 */
function xn_cfg($name)
{
    if (false === strpos($name, '.')) {
        $name = strtolower($name);
        $config  = \think\facade\Config::load('cfg/'.$name, $name);
        return $config ?? [];
    }
    $name_arr    = explode('.', $name);
    $name_arr[0] = strtolower($name_arr[0]);
    $filename = $name_arr[0];
    $config  = \think\facade\Config::load('cfg/'.$filename, $filename);
    return $config[$name_arr[1]] ?? [];
}

/**
 * 根目录物理路径
 * @return string
 */
function xn_root()
{
    return app()->getRootPath() . 'public';
}

/**
 * 构建图片上传HTML 单图
 * @param string $value
 * @param string $file_name
 * @param null $water 是否添加水印 null-系统配置设定 1-添加水印 0-不添加水印
 * @param null $thumb 生成缩略图，传入宽高，用英文逗号隔开，如：200,200（仅对本地存储方式生效，七牛、oss存储方式建议使用服务商提供的图片接口）
 * @return string
 */
function xn_upload_one($value,$file_name,$water=null,$thumb=null)
{
$html=<<<php
    <div class="xn-upload-box">
        <div class="t layui-col-md12 layui-col-space10">
            <input type="hidden" name="{$file_name}" class="layui-input xn-images" value="{$value}">
            <div class="layui-col-md4">
                <div type="button" class="layui-btn webuploader-container" id="{$file_name}" data-water="{$water}" data-thumb="{$thumb}" style="width: 113px;"><i class="layui-icon layui-icon-picture"></i>上传图片</div>
                <div type="button" class="layui-btn chooseImage" data-num="1"><i class="layui-icon layui-icon-table"></i>选择图片</div>
            </div>
        </div>
        <ul class="upload-ul clearfix">
            <span class="imagelist"></span>
        </ul>
        <script>$('#{$file_name}').uploadOne();</script>
    </div>
php;
    return $html;
}

/**
 * 构建图片上传HTML 多图
 * @param string $value
 * @param string $file_name
 * @param null $water 是否添加水印 null-系统配置设定 1-添加水印 0-不添加水印
 * @param null $thumb 生成缩略图，传入宽高，用英文逗号隔开，如：200,200（仅对本地存储方式生效，七牛、oss存储方式建议使用服务商提供的图片接口）
 * @return string
 */
function xn_upload_multi($value,$file_name,$water=null,$thumb=null)
{
    $html=<<<php
    <div class="xn-upload-box">
        <div class="t layui-col-md12 layui-col-space10">
            <div class="layui-col-md8">
                <input type="text" name="{$file_name}" class="layui-input xn-images" value="{$value}">
            </div>
            <div class="layui-col-md4">
                <div type="button" class="layui-btn webuploader-container" id="{$file_name}" data-water="{$water}" data-thumb="{$thumb}" style="width: 113px;"><i class="layui-icon layui-icon-picture"></i>上传图片</div>
                <div type="button" class="layui-btn chooseImage"><i class="layui-icon layui-icon-table"></i>选择图片</div>
            </div>
        </div>
        <ul class="upload-ul clearfix">
            <span class="imagelist"></span>
        </ul>
        <script>$('#{$file_name}').upload();</script>
    </div>
php;
    return $html;
}

//生成hash
function make_hash($module_name,$id){

    $time = time();
    $ip = $_SERVER['REMOTE_ADDR'];
    $randNum = rand(1,1000);
    return md5($module_name.$id.$time.$ip.$randNum);
}


/**
 * 查询产品图片
 * $type:product-产品本身图片，product_describe - 产品介绍图片
 */
function product_img($company_id,$type,$product_hash,$limit="",$field="file_url"){
    $img_data = Db::name('index_attachment')
        ->where('file_type','img')
        ->where('module',$type)
        ->where('module_hash',$product_hash)
        ->where('status',1)
        ->limit($limit)
        ->field($field)
        ->order('file_id asc')
        ->select();
    $img_arr = [];
    if(!empty($img_data)){  //查找上传的图片
        foreach ($img_data as $k => $v) {
            $img_arr[] = config('wxprogramme.access_domain') . $v['file_url'];
        }
    }else{  //查找默认图
        $img_data = Db::name('shop_'.$type.'_default_img')
            ->where('company_id',$company_id)
            ->where('status',1)
            ->order('img_order asc')
            ->select();
        if(!empty($img_data)){
            foreach ($img_data as $k=>$v){
                $img_arr[] = config('wxprogramme.access_domain').$v['img_url'];
            }
        }else{  //直接返回config默认图
            if($type == 'product_describe'){
                $product_info = Db::name('wms_product')
                    ->where('product_hash',$product_hash)
                    ->field('product_external_img')
                    ->find();
                if(!empty($product_info['product_external_img'])){  //有外部图片地址
                    $img_arr = [];
                }else{
                    $img_arr = ['http://upyun.yuximi.com/imi_jxs/images/zanwu.png'];
                }
            }else{
                $img_arr = ['http://upyun.yuximi.com/imi_jxs/images/zanwu.png'];
            }
        }
    }
    return $img_arr;
}