<?php
namespace app\common\model;
use think\Model;

/**
 * 网站配置模型类
 * @author kongweihao
 *
 */
class Setting extends Model{
	//只读字段，写入以后就不允许被更新
	protected $readonly = ['id'];
	
	/**
	 * 获取网站配置信息
	 * @return array
	 */
	public function getSetting(){
		$rs = $this->find();
		return $rs;
	}
	
	/**
	 * 修改网站配置
	 * @param array $data 网站配置信息
	 * @return integer
	 */
	public function updateSetting($data){
		$findRs = $this->where('id',$data['id'])->find();
		if($findRs){
			$rs = $this->allowField($data)->where('id',$data['id'])->update($data);
			return $rs;
		}else{
			$rs = $this->allowField(true)->insert($data);
			return $rs;
		}
	}
	
	/**
	 * copyright读取器
	 */
	protected function getCopyrightDataAttr($value,$data){
		return html_entity_decode($data['copyright']);//把HTML实体转换为字符
	}
}