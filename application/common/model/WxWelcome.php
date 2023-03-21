<?php
namespace app\common\model;
use think\Model;

/**
 * 微信关注回复内容模型
 * @author Administrator
 *
 */
class WxWelcome extends Model{
	//只读字段，写入以后就不允许被更新
	protected $readonly = ['id'];
	
	/**
	 * 获取欢迎回复信息
	 * @return array
	 */
	public function getWelcomeInfo(){
		$rs = $this->find();
		return $rs;
	}
	
	/**
	 * 更新欢迎回复信息
	 * @param array $arr 回复信息
	 * @return integer
	 */
	public function updateWelcomeInfo($arr){
		$findRs = $this->find();
		if($findRs){
			$this->where('id',$findRs->id)->delete();
		}
		
		if($arr['res_type'] == 'text'){
			$data = [
					'res_type' => $arr['res_type'],
					'content' => $arr['content'],
					'status' => isset($arr['status']) ? $arr['status'] : 2,
			];
		}else if($arr['res_type'] == 'news'){
			$data = [
					'res_type' => $arr['res_type'],
					'media_id' => $arr['media_id'],
					'status' => isset($arr['status']) ? $arr['status'] : 2,
			];
		}else if($arr['res_type'] == 'image'){
			$data = [
					'res_type' => $arr['res_type'],
					'media_id' => $arr['media_id'],
					'imgurl' => $arr['imgurl'],
					'status' => isset($arr['status']) ? $arr['status'] : 2,
			];
		}
		$rs = $this->allowField(true)->insert($data);
		return $rs;
	}
}