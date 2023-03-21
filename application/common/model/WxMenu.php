<?php
namespace app\common\model;
use think\Model;


/**
 * 自定义菜单模型
 * @author kongweihao
 *
 */
class WxMenu extends Model{
	//只读字段，写入以后就不允许被更新
	protected $readonly = ['id'];
	
	/**
	 * 获取菜单列表
	 * @return array
	 */
	public function getMenuList(){
		$rs = $this->order('sort,create_time')->paginate(18);
		return $rs;
	}
	
	/**
	 * 获取一级菜单
	 * @return array
	 */
	public function getOneLevelMenu(){
		$rs = $this->where('parent_id',0)->select();
		return $rs;
	}
	
	/**
	 * 获取单条菜单信息
	 * @param integer $menuId 菜单id
	 * @return array
	 */
	public function getMenuInfoById($menuId){
		$rs = $this->where('id',$menuId)->find();
		return $rs;
	}
	
	/**
	 * 根据key获取菜单信息
	 * @param string $key 事件key值
	 * @return array
	 */
	public function getWxMenuByKey($key){
		$rs = $this->where('key',$key)->find();
		return $rs;
	}
	
	/**
	 * 判断菜单是否有子菜单
	 * @param integer $menuId 菜单id
	 * @param mixed
	 */
	public function getHasChildrenMenu($menuId){
		$findRs = $this->where('id',$menuId)->find();
		if($findRs){
			if($findRs->parent_id == 0){
				$subFindRs = $this->where('parent_id',$menuId)->count();
				if($subFindRs){
					return true;
				}else{
					return false;
				}
			}else{
				return false;
			}
		}else{
			$this->error = '菜单不存在';
			return -1;
		}
	}
	
	/**
	 * 添加菜单
	 * @param array $arr 菜单信息
	 * @return integer
	 */
	public function addMenu($arr){
		//验证key唯一性
		if($arr['type'] == 'click' || $arr['type'] == 'c_click'){
			if(empty(trim($arr['key']))){
				$this->error = 'key不能为空';
				return -1;
			}
			$findRs = $this->where('key',$arr['key'])->find();
			if($findRs){
				$this->error = 'key已存在';
				return -1;
			}
		}
		//验证菜单个数
		if($arr['parent_id'] == 0){
			$firstLevelMenuNum = $this->where('parent_id',0)->where('button_level','button')->count();
			if($firstLevelMenuNum== 3){
				$this->error = '一级菜单最多3个';
				return -1;
			}
		}else{
			$secondLevelMenuNum = $this->where('parent_id',$arr['parent_id'])->where('button_level','sub_button')->count();
			if($secondLevelMenuNum== 5){
				$this->error = '二级菜单最多5个';
				return -1;
			}
		}
		
		//菜单级别
		if($arr['parent_id'] == 0){
			$data['button_level'] = 'button';
		}else{
			$data['button_level'] = 'sub_button';
			//添加子菜单，所属一级菜单信息要清空
			$clearData = [
					'type' => '',
					'key' => '',
					'url' => '',
					'res_type' => '',
					'media_id' => '',
					'imgurl' => ''
			];
			$this->where('id',$arr['parent_id'])->update($clearData);
		}
		
		//动作类型
		if($arr['type'] == 'view'){//跳转网页
			if(!empty($arr['url'])){
				$data['type'] = 'view';
				$data['url'] = $arr['url'];
			}
		}else if($arr['type'] == 'click'){//发送信息
			$data['type'] = 'click';
			$data['res_type'] = $arr['res_type'];//回复类型  news  image
			$data['media_id'] = $arr['media_id'];//素材id
			$data['key'] = $arr['key'];
			$data['imgurl'] = $arr['res_type']=='image' ? $data['imgurl']=$arr['imgurl'] : '';//回复的图片
		}else if($arr['type'] == 'miniprogram'){//小程序
			$data['type'] = 'miniprogram';
			$data['url'] = $arr['url'];//备用路径，不支持小程序的老版本客户端将打开本url
			$data['appid'] = $arr['appid'];//小程序appid
			$data['pagepath'] = $arr['pagepath'];//小程序页面路径
		}else if($arr['type'] == 'c_click'){//自定义
			$data['type'] = 'click';
			$data['key'] = $arr['key'];
		}
		
		$data['wx_name'] = urlencode($arr['wx_name']);
		$data['parent_id'] = $arr['parent_id'];
		$data['sort'] = $arr['sort'];
		
		$rs = $this->allowField(true)->insert($data);
		return $rs;
	}
	
	/**
	 * 更新菜单
	 * @param array $arr 菜单信息
	 * @return integer
	 */
	public function updateMenu($arr){
		//验证key唯一性
		if($arr['type'] == 'click' || $arr['type'] == 'c_click'){
			if(empty(trim($arr['key']))){
				$this->error = 'key不能为空';
				return -1;
			}
			$findRs = $this->where('id','<>',$arr['menuId'])->where('key',$arr['key'])->find();
			if($findRs){
				$this->error = 'key已存在';
				return -1;
			}
		}
		//验证菜单个数
		if($arr['parent_id'] == 0){
			$firstLevelMenuNum = $this->where('id','<>',$arr['menuId'])->where('parent_id',0)
			->where('button_level','button')->count();
			if($firstLevelMenuNum == 3){
				$this->error = '一级菜单最多3个';
				return -1;
			}
		}else{
			$secondLevelMenuNum = $this->where('id','<>',$arr['menuId'])->where('parent_id',$arr['parent_id'])
			->where('button_level','sub_button')->count();
			if($secondLevelMenuNum == 5){
				$this->error = '二级菜单最多5个';
				return -1;
			}
		}
		
		//菜单级别
		if($arr['parent_id'] == 0){
			$data['button_level'] = 'button';
		}else{
			$data['button_level'] = 'sub_button';
			//添加子菜单，所属一级菜单信息要清空
			$clearData = [
					'type' => '',
					'key' => '',
					'url' => '',
					'res_type' => '',
					'media_id' => '',
					'imgurl' => ''
			];
			$this->where('id',$arr['parent_id'])->update($clearData);
		}
		
		//动作类型
		if($arr['type'] == 'view'){//跳转网页
			if(!empty($arr['url'])){
				$data['type'] = 'view';
				$data['url'] = $arr['url'];
			}
		}else if($arr['type'] == 'click'){//发送信息
			$data['type'] = 'click';
			$data['res_type'] = $arr['res_type'];//回复类型  news  image
			$data['media_id'] = $arr['media_id'];//素材id
			$data['key'] = $arr['key'];
			$data['imgurl'] = $arr['res_type']=='image' ? $data['imgurl']=$arr['imgurl'] : '';//回复的图片
		}else if($arr['type'] == 'miniprogram'){//小程序
			$data['type'] = 'miniprogram';
			$data['url'] = $arr['url'];//备用路径，不支持小程序的老版本客户端将打开本url
			$data['appid'] = $arr['appid'];//小程序appid
			$data['pagepath'] = $arr['pagepath'];//小程序页面路径
		}else if($arr['type'] == 'c_click'){//自定义
			$data['type'] = 'click';
			$data['key'] = $arr['key'];
		}
		
		$data['wx_name'] = urlencode($arr['wx_name']);
		$data['parent_id'] = $arr['parent_id'];
		$data['sort'] = $arr['sort'];
		
		$rs = $this->allowField(true)->where('id',$arr['menuId'])->update($data);
		return $rs;
	}
	
	/**
	 * 菜单排序
	 * @param int $menuId 菜单id
	 * @param int $sortNum 排序数字
	 * @return mixed
	 */
	public function updateMenuSort($menuId,$sortNum){
		$findRs = $this->where('id',$menuId)->find();
		if($findRs){
			$rs = $this->where('id',$menuId)->update(['sort'=>$sortNum]);
			return $rs;
		}else{
			$this->error = '菜单不存在';
			return -1;
		}
	}
	
	/**
	 * 删除菜单
	 * @desc 删除一级菜单时，对应的子菜单也删除
	 * @param integer $menuId 菜单id
	 * @return integer
	 */
	public function deleteMenuById($menuId){
		$findRs = $this->where('id',$menuId)->find();
		if($findRs){
			if($findRs->parent_id != 0){
				$rs = $this->where('id',$menuId)->delete();
				return $rs;
			}else{
				$rs = $this->where('id',$menuId)->whereOr('parent_id',$menuId)->delete();
				return $rs;
			}
		}else{
			$this->error = '菜单不存在';
			return -1;
		}
	}
	
	/**
	 * wx_name获取器
	 */
	protected function getWxNameAttr($value){
		return urldecode($value);
	}
	
	/**
	 * button_level获取器
	 */
	protected function getButtonLevelDataAttr($value,$data){
		$buttonLevelData = ['button'=>'一级', 'sub_button'=>'二级'];
		return $buttonLevelData[$data['button_level']];
	}
}