<?php 

 /*ignore_user_abort(TRUE);
 set_time_limit(0); 
 System::load_sys_fun("send");
 System::load_sys_fun("user");*/
 
 class blockpay extends SystemAction {
	public function __construct() {			
		header("Last-Modified: " . gmdate( "D, d M Y H:i:s" ) . "GMT" ); 
		header("Cache-Control: no-cache, must-revalidate" ); 
		header("Pragma:no-cache");

		$this->db = System::load_sys_class("model");
	}
	
	public function getResult(){
		$return = array();
		$return['code'] = 0;
		//订单号
		$dingdancode = stripslashes($_POST['dingdancode']);
		//支付金额
		$money = stripslashes($_POST['money']);
		// 支付结果
		$success = stripslashes($_POST['status']);
		if(!$success){
			$return['msg'] = '支付失败！';
			echo json_encode($return);
			exit;
		}

		if(empty($dingdancode) || empty($money) || empty($success)){
			$return['msg'] = '参数错误！';
			echo json_encode($return);
			exit;
		}

		//$data = json_decode($data_json,true);
		$data['dingdancode'] = $dingdancode;
		$data['money'] = $money;

		

		
		//$data = array("money"=> 2,"dingdancode"=> "C14836930590154595");
  
		if($data){
			$aa = $this->pay_success($data);
			echo $aa;
			exit;
		}else{
			$return['msg'] = '支付失败1！';
			echo json_encode($return);
			exit;
		}
		
	}

	private function pay_success($data){

		$return['code'] = 0;
		if(empty($data)){
			$return['msg'] = '支付失败2！';
			echo json_encode($return);
			exit;
		}
		$out_trade_no = $data['dingdancode'];
		$total_fee_t = $data['money'];
		$this->db->Autocommit_start();
		$dingdaninfo = $this->db->GetOne("select * from `@#_member_addmoney_record` where `code` = '$out_trade_no' and `money` = '$total_fee_t' and `status` = '未付款' for update");
		if(!$dingdaninfo){
			$return['msg'] = '支付失败3！';
			echo json_encode($return);
			exit;
		}	
		$time = time();				
		$up_q1 = $this->db->Query("UPDATE `@#_member_addmoney_record` SET `pay_type` = 'blockchain', `status` = '已付款' where `id` = '$dingdaninfo[id]' and `code` = '$dingdaninfo[code]'");
		$up_q2 = $this->db->Query("UPDATE `@#_member` SET `money` = `money` + $total_fee_t where (`uid` = '$dingdaninfo[uid]')");				
		$up_q3 = $this->db->Query("INSERT INTO `@#_member_account` (`uid`, `type`, `pay`, `content`, `money`, `time`) VALUES ('$dingdaninfo[uid]', '1', '账户', '充值', '$total_fee_t', '$time')");
		
		if($up_q1 && $up_q2 && $up_q3){
			$this->db->Autocommit_commit();
		}else{
			$this->db->Autocommit_rollback();
			$return['msg'] = '支付失败4！';
			echo json_encode($return);
			exit;
		}

		if(empty($dingdaninfo['scookies'])){
			$return['msg'] = '支付失败5！';
			echo json_encode($return);
			exit;
		}			
		
		$uid = $dingdaninfo['uid'];
		$fufen_d = $dingdaninfo['score'];
		if($fufen_d){
			$fufen_cfg = System::load_app_config("user_fufen",'','member');	
			$fufen = intval($fufen_d);			
			if($fufen_cfg['fufen_yuan']){				
				$fufen = intval($fufen / $fufen_cfg['fufen_yuan']);
				$fufen = $fufen * $fufen_cfg['fufen_yuan'];
			}
		}else{
			$fufen = 0;
		}
	
		$scookies = unserialize($dingdaninfo['scookies']);
		$pay = System::load_app_class('pay','pay');
		$pay->fufen = $fufen;
		$pay->scookie = $scookies;	
		$ok = $pay->init($uid,'blockchain','go_record');	//云购商品	
		
		if($ok != 'ok'){
			_setcookie('Cartlist',NULL);
			$return['msg'] = '支付失败6！';
			echo json_encode($return);
			exit;		
		}			
		// 没有增加积分  没有购买记录 不知道是否中奖 pay_bag-pay.class.php
		$check = $pay->go_pay_block(1);
		if($check){
			$this->db->Query("UPDATE `@#_member_addmoney_record` SET `scookies` = '1' where `code` = '$out_trade_no' and `status` = '已付款'");
			//_setcookie('Cartlist',NULL);
			$return['code'] = 1;
			$return['msg'] = '支付成功7！';
			echo json_encode($return);
			exit;
		}else{
			$return['msg'] = '支付失败8！';
			echo json_encode($return);
			exit;
		}
	}
	
 }

?>