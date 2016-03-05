<?php
/*
 +--------------------------------------------------------------------------
 |   WeCenter [#RELEASE_VERSION#]
 |   ========================================
 |   by WeCenter Software
 |   ? 2011 - 2014 WeCenter. All Rights Reserved
 |   http://www.wecenter.com
 |   ========================================
 |   Support: WeCenter@qq.com
 |
 +---------------------------------------------------------------------------
 */

define ( 'IN_AJAX', TRUE );

if (! defined ( 'IN_ANWSION' )) {
	die ();
}

class ajax extends AWS_CONTROLLER {

	private   $answer_info_g ;
	public static $aa = "";

	public function get_access_rule() {
		$rule_action ['rule_type'] = 'white'; //����,�����еļ��  'white'����,��������ļ��


		$rule_action ['actions'] = array ('check_username', 'check_email', 'register_process', 'login_process', 'register_agreement', 'send_valid_mail', 'valid_email_active', 'request_find_password', 'find_password_modify', 'weixin_login_process', 'list_question', 'add_question', 'get_answer_list', 'add_answer', 'focus', 'get_comment_list', 'get_focus_users', 'add_comment', 'get_focus_topic', 'get_friend_users', 'get_fans_users', 'get_questioned_question', 'get_question_ansered_answer', 'user_actions', 'get_current_user_info', 'list_notification', 'get_user_focus_question', 'get_inbox_dialog_list', 'get_favor_tag', 'get_search_users', 'get_search_questions', 'save_invite' ,'send_inboxmessage','get_message_list','add_favor_tag','is_already_focuse','add_favor_tag','update_answer_in_favor_tag','get_answer_comments','save_answer_comment','change_focus','user_follow_check','get_answer_info','get_category_questions','privacy','privacy_email_setting','privacy_notification_setting','profile_setting','upload_answer_img','avatar_upload_2','upload_question_img','list_category','get_search_category','question_answer_rate', 'answer_vote','question_thanks', 'get_question_thanks','register_process2','get_answer_thanks','get_answer_vote','list_question_page');

		return $rule_action;
	}

	public function setup() {
		HTTP::no_cache_header ();
	}
	
	
	
	public function register_process2_action()
	{
		$tem = $_POST['user_name'].$_POST['password'].$_POST['email'];
		
		
		if ($this->model('account')->check_username($_POST['user_name']))
		{
			// H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('用户名已经存在')));
			
			echo json_encode ( $array = array ("reason" => "user exsit", "login_result" => "failed" ) );
			
			return;
		}

		$uid = $this->model('account')->user_register($_POST['user_name'], $_POST['password'], $_POST['email']);

		if ($_POST['email'] == $invitation['invitation_email'])
		{
			$this->model('active')->set_user_email_valid_by_uid($uid);

			$this->model('active')->active_user_by_uid($uid);
		}

		if (isset($_POST['sex']))
		{
			$update_data['sex'] = intval($_POST['sex']);

			// 更新主表
			$this->model('account')->update_users_fields($update_data, $uid);

		}

	
		if ($follow_users['uid'])
		{
			$this->model('follow')->user_follow_add($uid, $follow_users['uid']);
			$this->model('follow')->user_follow_add($follow_users['uid'], $uid);

			$this->model('integral')->process($follow_users['uid'], 'INVITE', get_setting('integral_system_config_invite'), '邀请注册: ' . $_POST['user_name'], $follow_users['uid']);
		}

		if (get_setting('register_valid_type') == 'N' OR (get_setting('register_valid_type') == 'email' AND get_setting('register_type') == 'invite'))
		{
			$this->model('active')->active_user_by_uid($uid);
		}

		$user_info = $this->model('account')->get_user_info_by_uid($uid);

		if (get_setting('register_valid_type') == 'N' OR $user_info['group_id'] != 3 OR $_POST['email'] == $invitation['invitation_email'])
		{
			//failed run here
			$this->model('account')->setcookie_login($user_info['uid'], $user_info['user_name'], $_POST['password'], $user_info['salt']);

			/*if (!$_POST['_is_mobile'])
			{
				H::ajax_json_output(AWS_APP::RSM(array(
					'url' => get_js_url('/home/first_login-TRUE')
				), 1, null));
			}*/
			
			echo json_encode ( $array = array ("reason" => "email conflict", "login_result" => "failed" ) );
		}
		else
		{
		/*	AWS_APP::session()->valid_email = $user_info['email'];

			$this->model('active')->new_valid_email($uid);

			if (!$_POST['_is_mobile'])
			{
				H::ajax_json_output(AWS_APP::RSM(array(
					'url' => get_js_url('/account/valid_email/')
				), 1, null));
			}*/
			//sucess run here 
				echo json_encode ( $array = array ("user_id" =>  $user_info['uid'], "login_result" => "sucess" ) );
		}	
	
	}

	public function get_answer_vote_action()
	{
		$vote_info = $this->model('answer')->get_answer_vote_status($_POST['answer_id'],$_POST['current_uid']);

		if ($vote_info)
		{
			echo json_encode ( $array = array ("vote_info" => $vote_info, "alread_vote" => "yes" ) );
		} 
		else 
		{
			echo json_encode ( $array = array ("alread_vote" => "no" ) );
		}
		
	}
	
	
	public function get_answer_thanks_action()
	{
		$answer_info = $this->model('answer')->get_answer_by_id($_POST['answer_id']);

		
		if ($_POST['type'] == 'thanks' AND $this->model('answer')->user_rated('thanks', $_POST['answer_id'], $_POST['current_uid']))
		{
			echo json_encode ( $array = array ("value" => "already_thanks" ) );
			// H::ajax_json_output(AWS_APP::RSM(null, - 1, AWS_APP::lang()->_t('已感谢过该回复, 请不要重复感谢')));
		}
		
		echo json_encode ( $array = array ("value" => "no_thanks" ) );
		
	}
	

	
	public function get_question_thanks_action()
	{	
		/*$temp = $_POST['question_id']. "##".$_POST['current_uid'];
		echo json_encode ( $array = array ("value" => $temp ) );*/
		
		if ($question_thanks =  $this->model('question')->get_question_thanks(intval($_POST['question_id']), intval($_POST['current_uid'])))
		{
			//$this->delete('question_thanks', "id = " . $question_thanks['id']);
			echo json_encode ( $array = array ("value" => "true" ) );
			//return false;
		}
		else
			echo json_encode ( $array = array ("value" => "false" ) );
			
		
	}
	
	public function question_thanks_action()
	{
		if (!$question_info = $this->model('question')->get_question_info_by_id($_POST['question_id']))
		{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('问题不存在')));
		}

		if ($this->model('question')->question_thanks($_POST['question_id'], $_POST['current_uid'], $_POST['user_name']))
		{
			$this->model('notify')->send($_POST['current_uid'], $question_info['published_uid'], notify_class::TYPE_QUESTION_THANK, notify_class::CATEGORY_QUESTION, $_POST['question_id'], array(
				'question_id' => intval($_POST['question_id']),
				'from_uid' =>intval( $_POST['current_uid'])
			));

			/*H::ajax_json_output(AWS_APP::RSM(array(
				'action' => 'add'
			), 1, null));*/
			
			echo json_encode ( $array = array ("value" => "add" ) );
		}
		else
		{
		/*	H::ajax_json_output(AWS_APP::RSM(array(
				'action' => 'remove'
			), 1, null));*/
			
			echo json_encode ( $array = array ("value" => "remove" ) );
		}
	}
	
	public function answer_vote_action()
	{
		$answer_info = $this->model('answer')->get_answer_by_id($_POST['answer_id']);
		
		$users_info = $this->model ( 'test' )->get_user_info_by_uid ($_POST['current_uid'],true );

		if (! in_array($_POST['value'], array(
			- 1,
			1
		)))
		{
			H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('投票数据错误, 无法进行投票')));
		}

		$reputation_factor = $this->model('account')->get_user_group_by_id($users_info['reputation_group'], 'reputation_factor');

		$this->model('answer')->change_answer_vote($_POST['answer_id'], $_POST['value'], $_POST['current_uid'], $reputation_factor);

		// H::ajax_json_output(AWS_APP::RSM(null, 1, null));
		if ($_POST['value'] == 1)
			echo json_encode ( $array = array ("value" => "agree" ) );
		else if ($_POST['value'] == -1)
			echo json_encode ( $array = array ("value" => "disagree" ) );
	}
	
	public function question_answer_rate_action()
	{
		$answer_info = $this->model('answer')->get_answer_by_id($_POST['answer_id']);

		
		if ($_POST['type'] == 'thanks' AND $this->model('answer')->user_rated('thanks', $_POST['answer_id'], $_POST['current_uid']))
		{
			echo json_encode ( $array = array ("value" => "already_thanks" ) );
			// H::ajax_json_output(AWS_APP::RSM(null, - 1, AWS_APP::lang()->_t('已感谢过该回复, 请不要重复感谢')));
		}

		if ($this->model('answer')->user_rate($_POST['type'], $_POST['answer_id'], $_POST['current_uid'], $_POST['user_name']))
		{
			if ($answer_info['uid'] != $_POST['current_uid'])
			{
				$this->model('notify')->send($_POST['current_uid'], $answer_info['uid'], notify_class::TYPE_ANSWER_THANK, notify_class::CATEGORY_QUESTION, $answer_info['question_id'], array(
					'question_id' => $answer_info['question_id'],
					'from_uid' => $_POST['current_uid'],
					'item_id' => $answer_info['answer_id']
				));
			}
			
			echo json_encode ( $array = array ("value" => "add" ) );

			/*H::ajax_json_output(AWS_APP::RSM(array(
				'action' => 'add'
			), 1, null));*/
		}
		else
		{
			/*H::ajax_json_output(AWS_APP::RSM(array(
				'action' => 'remove'
			), 1, null));*/
			
			echo json_encode ( $array = array ("value" => "remove" ) );
		}
	}
	
	
	public function get_search_category_action() {
			//$where = "`user_name` LIKE ".$_GET['user_name_search'];
			$where = array ();
			$where = "title LIKE '%" . $this->model ( 'test' )->quote ( $_GET ['title_search'] ) . "%'";
			//$where = "title LIKE '%" . $_GET ['title_search']. "%'";
			$cagegorys = $this->model ( 'test' )->get_category_list ( $where );

			if ($cagegorys)
				echo json_encode ( $array = array ("value" => $cagegorys ) );
			else
				echo json_encode ( $array = array ("novalue" => $cagegorys ) );

		}

	public function list_category_action() {
		//echo "this login_process_action";

		/*$questionContent = isset($_POST['questionContent']) ? trim($_POST['questionContent']) : 'test content';
			$questionDetail = isset($_POST['questionDetail']) ? trim($_POST['questionDetail']) : 'test detail';
			$question_id = $this->model('publish')->publish_question($questionContent, $questionDetail, $_POST['category_id'], $this->user_id, $_POST['topics'], $_POST['anonymous'], $_POST['attach_access_key'], $_POST['ask_user_id'], $this->user_info['permission']['create_topic']);*/
		$data = $this->model ( 'test' )->get_all_category ();
	
		echo json_encode ( $array = array ("value" => $data ) );

	}


	public function avatar_upload_2_action()
	{
		AWS_APP::upload()->initialize(array(
			'allowed_types' => 'jpg,jpeg,png,gif',
			'upload_path' => get_setting('upload_dir') . '/avatar/' . $this->model('account')->get_avatar($_GET['user_id'], '', 1),
			'is_image' => TRUE,
			'max_size' => get_setting('upload_avatar_size_limit'),
			'file_name' => $this->model('account')->get_avatar($_GET['user_id'], '', 2),
			'encrypt_name' => FALSE
		))->do_upload('uploadedfile');

		if (! $upload_data = AWS_APP::upload()->data())
		{
			echo json_encode ( $array = array ("value" => "failed") );
			// die("{'error':'上传失败, 请与管理员联系'}");
		}

		if ($upload_data['is_image'] == 1)
		{
			foreach(AWS_APP::config()->get('image')->avatar_thumbnail AS $key => $val)
			{
				$thumb_file[$key] = $upload_data['file_path'] . $this->model('account')->get_avatar($_GET['user_id'], $key, 2);

				AWS_APP::image()->initialize(array(
					'quality' => 90,
					'source_image' => $upload_data['full_path'],
					'new_image' => $thumb_file[$key],
					'width' => $val['w'],
					'height' => $val['h']
				))->resize();
			}
		}

		$update_data['avatar_file'] = $this->model('account')->get_avatar($_GET['user_id'], null, 1) . basename($thumb_file['min']);

		// 更新主表
		$this->model('account')->update_users_fields($update_data, $_GET['user_id']);

		if (!$this->model('integral')->fetch_log($_GET['user_id'], 'UPLOAD_AVATAR'))
		{
			$this->model('integral')->process($_GET['user_id'], 'UPLOAD_AVATAR', round((get_setting('integral_system_config_profile') * 0.2)), '上传头像');
		}

		echo json_encode ( $array = array ("value" => "sucess") );
		/*echo htmlspecialchars(json_encode(array(
			'success' => true,
			'thumb' => get_setting('upload_url') . '/avatar/' . $this->model('account')->get_avatar($_GET['user_id'], null, 1) . basename($thumb_file['max'])
		)), ENT_NOQUOTES);*/
	}

	public function profile_setting_action()
	{
		if ($_POST['user_name'] and $this->model('account')->check_username($_POST['user_name']) )
		{
			// H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('已经存在相同的姓名, 请重新填写')));
		}
		$update_data['user_name'] = $_POST['user_name'];

		if ($this->model('account')->check_email($_POST['user_email']))
		{
			//	H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('邮箱已经存在, 请使用新的邮箱')));
		}
		$update_data['email'] = $_POST['user_email'];

		$update_data['sex'] = intval($_POST['user_sex']);
		// $update_data['signature'] = $_POST['signature'];

		$update_attrib_data['signature'] = htmlspecialchars(trim($_POST['signature']));

		// 更新主表
		$this->model('account')->update_users_fields($update_data, $_POST['current_uid']);
			
		$this->model('account')->update_users_attrib_fields($update_attrib_data, $_POST['current_uid']);
			
		//echo json_encode ( $array = array ("value" => $update_data) );
	}

	public function privacy_email_setting_action()
	{


		$email_settings = array(
			'FOLLOW_ME' => 'N',
			'QUESTION_INVITE' => 'N',
			'NEW_ANSWER' => 'N',
			'NEW_MESSAGE' => 'N',
			'QUESTION_MOD' => 'N',
		);

		/*if ($_POST['email_settings'])
		 {
			foreach ($_POST['email_settings'] AS $key => $val)
			{
			unset($email_settings[$val]);
			}
			}*/

		$email_settings_keys = trim ( $_POST ['email_settings']);
		$keys_arry = explode(",", $email_settings_keys);

		foreach ($keys_arry as  $val)
		{
			unset($email_settings[$val]);
				
		}


		$this->model('account')->update_users_fields(array(
			'email_settings' => serialize($email_settings),
			
		), $_POST['current_uid']);


		echo json_encode ( $array = array ("value" => "helloword") );

	}


	public function privacy_notification_setting_action()
	{
		$update_keys = trim ( $_POST ['update_keys']);
		// $update_keys = $_GET ['update_keys'];
		// $update_keys = trim ( $_GET ['update_keys']);

		//trim($_POST['questionContent']) : 'test

		/*if ($notify_actions = $this->model('notify')->notify_action_details)
		 {
			$notification_setting = array();

			foreach ($notify_actions as $key => $val)
			{
			if (! isset($_POST['notification_settings'][$key]) AND $val['user_setting'])
			{
			$notification_setting[] = intval($key);
			}
			}
			}*/
		// $update_keys = "101,102,103,104";
		$keys_arry = explode(",",$update_keys);



		foreach ($keys_arry as  $val)
		{
			$notification_setting[] = intval($val);
				
		}

		$this->model('account')->update_notification_setting_fields($notification_setting, $_POST['current_uid']);

		//echo json_encode ( $array = array ("value" =>  "helloword") );
	}



	public function privacy_action()
	{
		$this->crumb(AWS_APP::lang()->_t('隐私/提醒'), '/account/setting/privacy');

		//TPL::assign('notification_settings', $this->model('account')->get_notification_setting_by_uid($this->user_id));
		//TPL::assign('notify_actions', $this->model('notify')->notify_action_details);

		$notification_settings = $this->model('account')->get_notification_setting_by_uid($_GET['current_uid']);
		$notify_actions = $this->model('notify')->notify_action_details;

		/*	echo json_encode ( $array = array ("value" => $this->model('account')->get_notification_setting_by_uid($_GET['current_uid'])) );
		 echo json_encode ( $array = array ("value" => $this->model('notify')->notify_action_details) );

		 echo json_encode ( $array = array ("value" =>  $this->user_info) );

		 */

		foreach($notify_actions as $key => $val)
		{
				
			if ($val['user_setting']) {
				$notify_setting_data[$key] = $val;
				if (!in_array($key, $notification_settings['data']) OR !$notification_settings['data'])
				{
					$notify_setting_data[$key]['choise'] = true;
				}
				else
				{
					$notify_setting_data[$key]['choise'] = false;
				}
			}

		}
		// echo json_encode ( $array = array ("value" => $notify_actions) );

		echo json_encode ( $array = array ("value" =>  $notify_setting_data) );


	}


	public function get_category_questions_action() {

		if (isset ( $_POST ['name'] )) {

		} else {
			/*$questionContent = isset($_POST['questionContent']) ? trim($_POST['questionContent']) : 'test content';
			 $questionDetail = isset($_POST['questionDetail']) ? trim($_POST['questionDetail']) : 'test detail';
			 $question_id = $this->model('publish')->publish_question($questionContent, $questionDetail, $_POST['category_id'], $this->user_id, $_POST['topics'], $_POST['anonymous'], $_POST['attach_access_key'], $_POST['ask_user_id'], $this->user_info['permission']['create_topic']);*/
			$data = $this->model ( 'test' )->get_question_by_categoryid($_GET['category_id'],$_GET['order_flag']);
				
			echo json_encode ( $array = array ("value" => $data ) );

		}


	}


	public function user_follow_check_action()
	{
		$result = $this->model('follow')->user_follow_check($_GET['current_uid'],$_GET['will_foucus_uid']);
		echo json_encode ( $array = array ("value" => $result ) );
	}


	public function	change_focus_action()
	{

		$result = $this->model('follow')->update_user_followe($_GET['current_uid'],$_GET['will_foucus_uid']);

			
		echo json_encode ( $array = array ("value" => $result ) );
	}

	public function save_answer_comment_action()
	{

		$answer_info = $this->model('answer')->get_answer_by_id($_GET['answer_id']);
		$question_info = $this->model('question')->get_question_info_by_id($answer_info['question_id']);



		$this->model('answer')->insert_answer_comment($_GET['answer_id'], $_GET['current_uid'], $_GET['message']);

	}

	public function get_answer_comments_action()
	{
		$comments = $this->model('answer')->get_answer_comments($_GET['answer_id']);

		$user_infos = $this->model('account')->get_user_info_by_uids(fetch_array_value($comments, 'uid'));

		foreach ($comments as $key => $val)
		{
			$comments[$key]['message'] = FORMAT::parse_links($this->model('question')->parse_at_user($comments[$key]['message']));
			$comments[$key]['user_name'] = $user_infos[$val['uid']]['user_name'];
			$comments[$key]['url_token'] = $user_infos[$val['uid']]['url_token'];
			$comments[$key]['avatar_file'] = $user_infos[$val['uid']]['avatar_file'];
	
			$comments[$key]['time'] = date_friendly($val['time']);
				
		}

		//$answer_info = $this->model('answer')->get_answer_by_id($_GET['answer_id']);

		echo json_encode ( $array = array ("value" => $comments ) );

		/*TPL::assign('question', $this->model('question')->get_question_info_by_id($answer_info['question_id']));

		TPL::assign('comments', $comments);

		if (is_mobile())
		{
		TPL::output("m/ajax/question_comments");
		}
		else
		{
		TPL::output("question/ajax/comments");
		}*/
	}


	public function update_answer_in_favor_tag_action()
	{
		$update_list = $_POST['update_list'];
		/*

		foreach ( $update_list as $key => $val ) {

		$this->model('test')->add_favorite($val['item_id'], $val['type'], $val['uid']);

		$this->model('test')->update_favorite_tag($val['item_id'], $val['type'], $val['title'],$val['discription'],$val['uid']);


		}
		*/
		echo json_encode ( $array = array ("value" => $update_list ) );
	}



	public function add_favor_tag_action()
	{
		if ($_POST['action'] == "add")
		{
			// echo json_encode ( $array = array ("value" => "@@ from serer :add " ) );
			$this->model('test')->add_favorite($_POST['item_id'], $_POST['item_type'], $_POST['user_id']);

				
			$this->model('test')->update_favorite_tag($_POST['item_id'], $_POST['item_type'], $_POST['tag_title'],$_POST['tag_discription'],$_POST['user_id']);
			echo json_encode ( $array = array ("value" => "add" ) );
		}
		else if ($_POST['action'] == "remove")
		{
			//  echo json_encode ( $array = array ("value" => "@@ from serer :remove " ) );

				
			//	public function remove_favorite_item($item_id, $item_type, $uid)
			$this->model('test')->remove_favorite_item($_POST['item_id'], $_POST['item_type'], $_POST['user_id']);

			// public function remove_favorite_tag($item_id, $item_type, $tag, $uid)
			$this->model('test')->remove_favorite_tag($_POST['item_id'], $_POST['item_type'], $_POST['tag_title'],$_POST['user_id']);
			echo json_encode ( $array = array ("value" => "remove" ) );
		}
			
			
	}

	public function get_message_list_action()
	{
		$dialog_item  = $this->model('message')->get_dialog_by_user($_GET['sender_uid'], $_GET['recipient_uid']);
			
		$message  = $this->model('test')->get_message_by_dialog_id($dialog_item['id']);
			
			

		foreach ( $message as $key => $val ) {

			$message [$key] ['add_time'] = 	date_friendly( $val ['add_time']);


		}
			
		echo json_encode ( $array = array ("value" => $message ) );
	}

	public function send_inboxmessage_action()
	{
		/*
		 if ($recipient_user['inbox_recv'])
		 {
			if (! $this->model('message')->check_permission($recipient_user['uid'], $this->user_id))
			{
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('对方设置了只有 Ta 关注的人才能给 Ta 发送私信')));
			}
			}
			*/


		$this->model('message')->send_message($_POST['current_uid'], $_POST['user_talk_to_uid'], $_POST['message']);

		//  $this->model('message')->send_message($_GET['current_uid'], $_GET['user_talk_to_uid'], $_GET['message']);

	}

	public function save_invite_action() {
		$is_question_del_not_exsit = 0;
		$is_invite_user_not_exsit = 0;
		$ask_self_reply = 0;
		$invite_user_already_answered = 0;
		$ask_publish_user = 0;
		$invite_user_already_invited = 0;
		$invite_user_already_been_invited = 0;

		if (! $question_info = $this->model ( 'question' )->get_question_info_by_id ( $_POST ['question_id'] )) {
			//H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('问题不存在或已被删除')));
			$is_question_del_not_exsit = 1;
		}

		if (! $invite_user_info = $this->model ( 'account' )->get_user_info_by_uid ( $_POST ['invite_uid'] )) {
			//H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('用户不存在')));
			$is_invite_user_not_exsit = 1;
		}

		if ($invite_user_info ['uid'] == $_POST ['current_uid']) {
			//H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('不能邀请自己回复问题')));
			$ask_self_reply = 1;
		}

		if ($this->user_info ['integral'] < 0 and get_setting ( 'integral_system_enabled' ) == 'Y') {
			//H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你的剩余积分已经不足以进行此操作')));
		}

		if ($this->model ( 'answer' )->has_answer_by_uid ( $_POST ['question_id'], $invite_user_info ['uid'] )) {
			//H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('该用户已经回答过该问题')));
			$invite_user_already_answered = 1;
		}

		if ($question_info ['published_uid'] == $invite_user_info ['uid']) {
			//H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('不能邀请问题的发起者回答问题')));
			$ask_publish_user = 1;
		}

		if ($this->model ( 'question' )->has_question_invite ( $_POST ['question_id'], $invite_user_info ['uid'] )) {
			//H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('该用户已接受过邀请')));
			$invite_user_already_invited = 1;
		}

		if ($this->model ( 'question' )->has_question_invite ( $_POST ['question_id'], $invite_user_info ['uid'], $_POST ['current_uid'] )) {
			//H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('已邀请过该用户')));
			$invite_user_already_been_invited = 1;
		}


		$this->model ( 'question' )->add_invite ( $_POST ['question_id'], $_POST ['current_uid'], $invite_user_info ['uid'] );
			
		$this->model ( 'account' )->update_question_invite_count ( $invite_user_info ['uid'] );
			
		// need to modify ,current the self invite self also can send the notification
		// if ($is_question_del_not_exsit == 0 AND $is_invite_user_not_exsit == 0 	AND $ask_self_reply = 0 AND $invite_user_already_answered = 0 AND $ask_publish_user = 0  AND $invite_user_already_answered = 0  AND $invite_user_already_been_invited = 0 )
		{
			$notification_id = $this->model ( 'notify' )->send ( $_POST ['current_uid'], $invite_user_info ['uid'], notify_class::TYPE_INVITE_QUESTION, notify_class::CATEGORY_QUESTION, intval ( $_POST ['question_id'] ), array ('from_uid' => $_POST ['current_uid'], 'question_id' => intval ( $_POST ['question_id'] ) ) );
				
			$this->model ( 'email' )->action_email ( 'QUESTION_INVITE', $_POST ['uid'], get_js_url ( '/question/' . $question_info ['question_id'] . '?notification_id-' . $notification_id ), array ('user_name' => $_POST ['user_name'], 'question_title' => $question_info ['question_content'] ) );
		}

		echo json_encode ( $array = array ("is_question_del_not_exsit" => $is_question_del_not_exsit, "is_invite_user_not_exsit" => $is_invite_user_not_exsit, "ask_self_reply" => $ask_self_reply, "invite_user_already_answered" => $invite_user_already_answered, "ask_publish_user" => $ask_publish_user, "invite_user_already_invited" => $invite_user_already_invited, "invite_user_already_been_invited" => $invite_user_already_been_invited ) );
		// H::ajax_json_output(AWS_APP::RSM(null, 1, null));


	}

	public function save_invite_temp_for_get_action() {
		// echo json_encode ( $array = array ("value" => $_GET['question_id']."##".$_GET['current_uid']."###".$_GET['user_name']."##".$_GET['invite_uid'] ) );


		if (! $question_info = $this->model ( 'question' )->get_question_info_by_id ( $_GET ['question_id'] )) {
			// H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('问题不存在或已被删除')));
		}

		if (! $invite_user_info = $this->model ( 'account' )->get_user_info_by_uid ( $_GET ['invite_uid'] )) {
			//H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('用户不存在')));
		}

		if ($invite_user_info ['uid'] == $_GET ['current_uid']) {
			// H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('不能邀请自己回复问题')));
		}
		/*
		 if ($this->user_info['integral'] < 0 and get_setting('integral_system_enabled') == 'Y')
		 {
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你的剩余积分已经不足以进行此操作')));
			}*/

		if ($this->model ( 'answer' )->has_answer_by_uid ( $_GET ['question_id'], $invite_user_info ['uid'] )) {
			// H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('该用户已经回答过该问题')));
		}

		if ($question_info ['published_uid'] == $invite_user_info ['uid']) {
			// H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('不能邀请问题的发起者回答问题')));
		}

		if ($this->model ( 'question' )->has_question_invite ( $_GET ['question_id'], $invite_user_info ['uid'] )) {
			// this would cause something worong by anxiang.xiao 201508-05
			// H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('该用户已接受过邀请')));
		}

		if ($this->model ( 'question' )->has_question_invite ( $_GET ['question_id'], $invite_user_info ['uid'], $_GET ['current_uid'] )) {
			// H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('已邀请过该用户')));
		}

		$this->model ( 'question' )->add_invite ( $_GET ['question_id'], $_GET ['current_uid'], $invite_user_info ['uid'] );

		$this->model ( 'account' )->update_question_invite_count ( $invite_user_info ['uid'] );

		/*if ($weixin_user = $this->model('openid_weixin_weixin')->get_user_info_by_uid($invite_user_info['uid']) AND $invite_user_info['weixin_settings']['QUESTION_INVITE'] != 'N')
		 {
			$this->model('weixin')->send_text_message($weixin_user['openid'], "有会员在问题 [" . $question_info['question_content'] . "] 邀请了你进行回答", $this->model('openid_weixin_weixin')->redirect_url('/m/question/' . $question_info['question_id']));
			}*/

		$notification_id = $this->model ( 'notify' )->send ( $_GET ['current_uid'], $invite_user_info ['uid'], notify_class::TYPE_INVITE_QUESTION, notify_class::CATEGORY_QUESTION, intval ( $_GET ['question_id'] ), array ('from_uid' => $_GET ['current_uid'], 'question_id' => intval ( $_GET ['question_id'] ) ) );

		$this->model ( 'email' )->action_email ( 'QUESTION_INVITE', $_GET ['invite_uid'], get_js_url ( '/question/' . $question_info ['question_id'] . '?notification_id-' . $notification_id ), array ('user_name' => $_GET ['user_name'], 'question_title' => $question_info ['question_content'] ) );

		// H::ajax_json_output(AWS_APP::RSM(null, 1, null));


	}

	public function func_temp() {
		if (! $question_info = $this->model ( 'question' )->get_question_info_by_id ( $_GET ['question_id'] )) {
			// H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('问题不存在或已被删除')));
		}

		if (! $invite_user_info = $this->model ( 'account' )->get_user_info_by_uid ( $_GET ['invite_uid'] )) {
			H::ajax_json_output ( AWS_APP::RSM ( null, '-1', AWS_APP::lang ()->_t ( '用户不存在' ) ) );
		}

		if ($invite_user_info ['uid'] == $_GET ['current_uid']) {
			H::ajax_json_output ( AWS_APP::RSM ( null, '-1', AWS_APP::lang ()->_t ( '不能邀请自己回复问题' ) ) );
		}
		/*
		 if ($this->user_info['integral'] < 0 and get_setting('integral_system_enabled') == 'Y')
		 {
			H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('你的剩余积分已经不足以进行此操作')));
			}*/

		if ($this->model ( 'answer' )->has_answer_by_uid ( $_GET ['question_id'], $invite_user_info ['uid'] )) {
			H::ajax_json_output ( AWS_APP::RSM ( null, '-1', AWS_APP::lang ()->_t ( '该用户已经回答过该问题' ) ) );
		}

		if ($question_info ['published_uid'] == $invite_user_info ['uid']) {
			H::ajax_json_output ( AWS_APP::RSM ( null, '-1', AWS_APP::lang ()->_t ( '不能邀请问题的发起者回答问题' ) ) );
		}

		if ($this->model ( 'question' )->has_question_invite ( $_GET ['question_id'], $invite_user_info ['uid'] )) {
			H::ajax_json_output ( AWS_APP::RSM ( null, '-1', AWS_APP::lang ()->_t ( '该用户已接受过邀请' ) ) );
		}

		if ($this->model ( 'question' )->has_question_invite ( $_GET ['question_id'], $invite_user_info ['uid'], $_GET ['current_uid'] )) {
			H::ajax_json_output ( AWS_APP::RSM ( null, '-1', AWS_APP::lang ()->_t ( '已邀请过该用户' ) ) );
		}

		$this->model ( 'question' )->add_invite ( $_GET ['question_id'], $_GET ['current_uid'], $invite_user_info ['uid'] );

		$this->model ( 'account' )->update_question_invite_count ( $invite_user_info ['uid'] );

		/*if ($weixin_user = $this->model('openid_weixin_weixin')->get_user_info_by_uid($invite_user_info['uid']) AND $invite_user_info['weixin_settings']['QUESTION_INVITE'] != 'N')
		 {
			$this->model('weixin')->send_text_message($weixin_user['openid'], "有会员在问题 [" . $question_info['question_content'] . "] 邀请了你进行回答", $this->model('openid_weixin_weixin')->redirect_url('/m/question/' . $question_info['question_id']));
			}*/

		/*$notification_id = $this->model('notify')->send($_GET['current_uid'], $invite_user_info['uid'], notify_class::TYPE_INVITE_QUESTION, notify_class::CATEGORY_QUESTION, intval($_GET['question_id']), array(
			'from_uid' => $_GET['current_uid'],
			'question_id' => intval($_GET['question_id'])
			));

			$this->model('email')->action_email('QUESTION_INVITE', $_GET['invite_uid'], get_js_url('/question/' . $question_info['question_id'] . '?notification_id-' . $notification_id), array(
			'user_name' => $_GET['user_name'],
			'question_title' => $question_info['question_content'],
			));

			H::ajax_json_output(AWS_APP::RSM(null, 1, null));*/	}

		public function get_search_users_action2() {
			/*if ($_POST['action'] == 'search')
			 {
			 foreach ($_POST as $key => $val)
			 {
			 if (in_array($key, array('user_name', 'email')))
			 {
			 $val = rawurlencode($val);
			 }

			 $param[] = $key . '-' . $val;
			 }

			 H::ajax_json_output(AWS_APP::RSM(array(
			 'url' => get_js_url('/admin/user/list/' . implode('__', $param))
			 ), 1, null));
			 }*/

			$where = array ();

			if ($_GET ['type'] == 'forbidden') {
				$where [] = 'forbidden = 1';
			}

			if ($_GET ['user_name']) {
				$where [] = "user_name LIKE '%" . $this->model ( 'people' )->quote ( $_GET ['user_name'] ) . "%'";
			}

			if ($_GET ['email']) {
				$where [] = "email = '" . $this->model ( 'people' )->quote ( $_GET ['email'] ) . "'";
			}

			if ($_GET ['group_id']) {
				$where [] = 'group_id = ' . intval ( $_GET ['group_id'] );
			}

			if ($_GET ['ip'] and preg_match ( '/(\d{1,3}\.){3}(\d{1,3}|\*)/', $_GET ['ip'] )) {
				if (substr ( $_GET ['ip'], - 2, 2 ) == '.*') {
					$ip_base = ip2long ( str_replace ( '.*', '.0', $_GET ['ip'] ) );

					if ($ip_base) {
						$where [] = 'last_ip BETWEEN ' . $ip_base . ' AND ' . ($ip_base + 255);
					}
				} else {
					$ip_base = ip2long ( $_GET ['ip'] );

					if ($ip_base) {
						$where [] = 'last_ip = ' . $ip_base;
					}
				}
			}

			if ($_GET ['integral_min']) {
				$where [] = 'integral >= ' . intval ( $_GET ['integral_min'] );
			}

			if ($_GET ['integral_max']) {
				$where [] = 'integral <= ' . intval ( $_GET ['integral_max'] );
			}

			if ($_GET ['reputation_min']) {
				$where [] = 'reputation >= ' . intval ( $_GET ['reputation_min'] );
			}

			if ($_GET ['reputation_max']) {
				$where [] = 'reputation <= ' . intval ( $_GET ['reputation_max'] );
			}

			if ($_GET ['job_id']) {
				$where [] = 'job_id = ' . intval ( $_GET ['job_id'] );
			}

			if ($_GET ['province']) {
				$where [] = "province = '" . $this->model ( 'people' )->quote ( $_GET ['province'] ) . "'";
			}

			if ($_GET ['city']) {
				$where [] = "city = '" . $this->model ( 'people' )->quote ( $_GET ['city'] ) . "'";
			}

			$user_list = $this->model ( 'people' )->fetch_page ( 'users', implode ( ' AND ', $where ), 'uid DESC', $_GET ['page'], $this->per_page );

			$total_rows = $this->model ( 'people' )->found_rows ();

			$url_param = array ();

			foreach ( $_GET as $key => $val ) {
				if (! in_array ( $key, array ('app', 'c', 'act', 'page' ) )) {
					$url_param [] = $key . '-' . $val;
				}
			}

			TPL::assign ( 'pagination', AWS_APP::pagination ()->initialize ( array ('base_url' => get_js_url ( '/admin/user/list/' ) . implode ( '__', $url_param ), 'total_rows' => $total_rows, 'per_page' => $this->per_page ) )->create_links () );

			$this->crumb ( AWS_APP::lang ()->_t ( '会员列表' ), "admin/user/list/" );

			TPL::assign ( 'mem_group', $this->model ( 'account' )->get_user_group_list ( 1 ) );
			TPL::assign ( 'system_group', $this->model ( 'account' )->get_user_group_list ( 0 ) );
			TPL::assign ( 'job_list', $this->model ( 'work' )->get_jobs_list () );
			TPL::assign ( 'total_rows', $total_rows );
			TPL::assign ( 'list', $user_list );
			TPL::assign ( 'menu_list', $this->model ( 'admin' )->fetch_menu_list ( 402 ) );

			TPL::output ( 'admin/user/list' );
		}

		public function get_search_users_action() {
			//$where = "`user_name` LIKE ".$_GET['user_name_search'];
			$where = array ();
			$where = "user_name LIKE '%" . $this->model ( 'people' )->quote ( $_GET ['user_name_search'] ) . "%'";
			$user = $this->model ( 'test' )->get_users_list ( $where,1000,true );

			if ($user)
			echo json_encode ( $array = array ("value" => $user ) );
			else
			echo json_encode ( $array = array ("novalue" => $user ) );

		}

		// get my fava tag by anxiang.xiao 20150802
		public function get_favor_tag_action() {

			$user_favor_tags = $this->model ( 'favorite' )->get_favorite_tags ( $_GET ['user_id'] );

			foreach ( $user_favor_tags as $key => $val ) {
				$user_favor_tags [$key] ['is_aready_add'] = 0;
				if ($action_list = $this->model ( 'favorite' )->get_item_list ( $val ['title'], $_GET ['user_id'], calc_page_limit ( $_GET ['page'], get_setting ( 'contents_per_page' ) ) )) {

					foreach ( $action_list as $key1 => $val2 ) {
						$item_ids [] = $val2 ['item_id'];
					}

					foreach ( $action_list as $key3 => $val3 ) {
						if ($val3['item_id'] == $_GET ['answer_id'])
						{
							$user_favor_tags [$key] ['is_aready_add'] = 1;
							break;
						}
							
					}
					$user_favor_tags [$key] ['favor_item'] = $action_list;
					$user_favor_tags [$key] ['favor_item_count'] = sizeof ( $action_list );

					// TPL::assign('list', $action_list);
				} else {
					if (! $_GET ['page'] or $_GET ['page'] == 1) {
						//	$this->model('favorite')->remove_favorite_tag(null, null, $_GET['tag'], $this->user_id);
						$user_favor_tags [$key] ['favor_item_count'] = 0;
						$this->model ( 'favorite' )->remove_favorite_tag ( null, null, $_GET ['tag'], $_GET ['user_id'] );
					}
				}

			}


			echo json_encode ( $array = array ("value" => $user_favor_tags ) );
		
		}

		// get inbox dialog list by anxiang.xiao 20150801


		public function get_inbox_dialog_list_action() {

			// $this->model('account')->update_inbox_unread($this->user_id);
			$this->model ( 'account' )->update_inbox_unread ( $_GET ['user_id'] );

			// if ($inbox_dialog = $this->model('message')->get_inbox_message($_GET['page'], get_setting('contents_per_page'), $this->user_id))
			if ($inbox_dialog = $this->model ( 'message' )->get_inbox_message ( $_GET ['page'], get_setting ( 'contents_per_page' ), $_GET ['user_id'] )) {
				$inbox_total_rows = $this->model ( 'message' )->found_rows ();
					
				foreach ( $inbox_dialog as $key => $val ) {
					$dialog_ids [] = $val ['id'];

					// if ($this->user_id == $val['recipient_uid'])
					if ($_GET ['user_id'] == $val ['recipient_uid']) {
						$inbox_dialog_uids [] = $val ['sender_uid'];
					} else {
						$inbox_dialog_uids [] = $val ['recipient_uid'];
					}
				}
			}

			if ($inbox_dialog_uids) {
				if ($users_info_query = $this->model ( 'account' )->get_user_info_by_uids ( $inbox_dialog_uids )) {
					foreach ( $users_info_query as $user ) {
						$users_info [$user ['uid']] = $user;
					}
				}
			}

			if ($dialog_ids) {
				$last_message = $this->model ( 'message' )->get_last_messages ( $dialog_ids );
			}

			if ($inbox_dialog) {
				foreach ( $inbox_dialog as $key => $value ) {
					// if ($value['recipient_uid'] == $this->user_id AND $value['recipient_count']) // 当前处于接收用户
					if ($value ['recipient_uid'] == $_GET ['user_id'] and $value ['recipient_count']) // 当前处于接收用户
					{
						$data [$key] ['user_name'] = $users_info [$value ['sender_uid']] ['user_name'];
						$data [$key] ['url_token'] = $users_info [$value ['sender_uid']] ['url_token'];
							
						$data [$key] ['unread'] = $value ['recipient_unread'];
						$data [$key] ['count'] = $value ['recipient_count'];
							
						$data [$key] ['uid'] = $value ['sender_uid'];
					} // 				else // 当前处于发送用户
					if ($value ['sender_uid'] == $_GET ['user_id'] and $value ['sender_count']) // 当前处于发送用户
					{
						$data [$key] ['user_name'] = $users_info [$value ['recipient_uid']] ['user_name'];
						$data [$key] ['url_token'] = $users_info [$value ['recipient_uid']] ['url_token'];
							
						$data [$key] ['unread'] = $value ['sender_unread'];
						$data [$key] ['count'] = $value ['sender_count'];
						$data [$key] ['uid'] = $value ['recipient_uid'];
					}

					$data [$key] ['last_message'] = $last_message [$value ['id']];
					$data [$key] ['update_time'] = $value ['update_time'];
					$data [$key] ['id'] = $value ['id'];

					$data [$key] ['update_time'] = date_friendly ( $data [$key] ['update_time'] );


					$userinfo = $this->model ( 'test' )->get_user_info_by_uid ($data [$key] ['uid'],true );

					$data [$key] ['talk_to_user_info'] = $userinfo;

				}
			}

			echo json_encode ( $array = array ("value" => $data ) );
			TPL::assign ( 'list', $data );

			TPL::assign ( 'pagination', AWS_APP::pagination ()->initialize ( array ('base_url' => get_js_url ( '/inbox/' ), 'total_rows' => $inbox_total_rows, 'per_page' => get_setting ( 'contents_per_page' ) ) )->create_links () );

			TPL::output ( 'inbox/index' );
		}

		// get user focus quesion add by anxiang.xiao 20150731
		public function get_user_focus_question_action() {
			if ($result = $this->model ( 'test' )->get_user_focus ( $_GET ['user_id'], (intval ( $_GET ['page'] ) * $this->per_page) . ", {$this->per_page}" )) {
				foreach ( $result as $key => $val ) {
					$question_ids [] = $val ['question_id'];
				}
					
				$topics_questions = $this->model ( 'topic' )->get_topics_by_item_ids ( $question_ids, 'question' );
					
				foreach ( $result as $key => $val ) {
					if (! $user_info_list [$val ['published_uid']]) {
						$user_info_list [$val ['published_uid']] = $this->model ( 'account' )->get_user_info_by_uid ( $val ['published_uid'], true );
					}

					$data [$key] ['user_info'] = $user_info_list [$val ['published_uid']];

					$data [$key] ['associate_type'] = 1;

					$data [$key] ['topics'] = $topics_questions [$val ['question_id']];

					$data [$key] ['link'] = get_js_url ( '/question/' . $val ['question_id'] );
					$data [$key] ['title'] = $val ['question_content'];

					$data [$key] ['question_info'] = $val;
				}
			}
			echo json_encode ( $array = array ("value" => $data ) );
		}

		/// get notification of user
		public function list_notification_action() {
			/*if ($_GET['limit'])
			 {
			 $per_page = intval($_GET['limit']);
			 }
			 else
			 {
			 $per_page = $this->per_page;
			 }
			 */
			$per_page = 1000;
			$list = $this->model ( 'notify' )->list_notification_2 ( $_GET ['user_id'], $_GET ['flag'], intval ( $_GET ['page'] ) * $per_page . ', ' . $per_page );

		 echo json_encode ( $array = array ("value" => $list ) );
		 /*
		  if (! $list and $this->user_info ['notification_unread'] != 0) {
		  $this->model ( 'account' )->update_notification_unread ( $_GET ['user_id'] );
		  }


		  foreach ( $list as $key => $val ) {
		  	
		  // $list [$key] ['message'] = strip_tags ( $list [$key] ['message'] );
		  	
		  if ($list [$key] ['user_list'] != '') {
				// $list [$key] ['extend_message'] = htmlspecialchars($list [$key] ['extend_message']);
				//$list [$key] ['user_list'] = implode ( '</p><p class="moreContent hide">', $list [$key] ['user_list'] );
				$list [$key] ['user_list'] = strip_tags ( $list [$key] ['user_list'] );
				}
				if ($list [$key] ['user_name'] != '') {
				// $list [$key] ['extend_message'] = htmlspecialchars($list [$key] ['extend_message']);
				//$list [$key] ['user_name'] = implode ( '</p><p class="moreContent hide">', $list [$key] ['user_name'] );
				$list [$key] ['user_name'] = strip_tags ( $list [$key] ['user_name'] );
				}
					
				//if ($list [$key] ['extend_message'])
				//	$list [$key] ['extend_message'] = strip_tags($list [$key] ['extend_message']);


				}
				*/
		 // echo json_encode ( $array = array ("value" => $list ) );

		 /*
		  TPL::assign('flag', $_GET['flag']);
		  TPL::assign('list', $list);

		  if ($_GET['template'] == 'header_list')
		  {
		  TPL::output("notifications/ajax/header_list");
		  }
		  else if (is_mobile())
		  {
		  TPL::output('m/ajax/notifications_list');
		  }
		  else
		  {
		  TPL::output("notifications/ajax/list");
		  }*/
		}

		///


		public function get_current_user_info_action() {

			$users_info = $this->model ( 'test' )->get_user_info_by_uid ( $_GET ['user_id'],true );


			echo json_encode ( $array = array ("value" => $users_info ) );
		}

		public function user_actions_action() {
			if ((isset ( $_GET ['perpage'] ) and intval ( $_GET ['perpage'] ) > 0)) {
				$this->per_page = intval ( $_GET ['perpage'] );
			}

			//echo json_encode ( $array = array ("value" => $_GET['actions'] ) );
			$data = $this->model ( 'test' )->get_user_actions ( $_GET ['uid'], (intval ( $_GET ['page'] ) * $this->per_page) . ", {$this->per_page}", $_GET ['actions'], $_GET ['current_uid'] );

			foreach ( $data as $key => $val ) {
					
				$data [$key] ['add_time'] = date_friendly ( $val ['add_time'], 604800, 'Y-m-d' );
				$data [$key] ['last_action_str'] = strip_tags ( $data [$key] ['last_action_str'] );
				//	$data [$key] ['last_action_str'] = $data [$key] ['last_action_str'] . $data [$key] ['add_time'];
					
				// $data [$key] ['title'] = strip_tags($data [$key] ['title']);


			}

			echo json_encode ( $array = array ("value" => $data ) );
			/*TPL::assign('list', $data);

			if (is_mobile())
			{
			$template_dir = 'm';
			}
			else
			{
			$template_dir = 'people';
			}

			if ($_GET['actions'] == '201')
			{
			//TPL::output($template_dir . '/ajax/user_actions_questions_201');
			}
			else if ($_GET['actions'] == '101')
			{
			//TPL::output($template_dir . '/ajax/user_actions_questions_101');
			}
			else
			{
			TPL::output($template_dir . '/ajax/user_actions');
			}*/
		}

		/*public function user_actions_action()
		 {


		 $data = $this->model('test')->get_user_actions($_GET['uid'], (intval($_GET['page']) * $this->per_page) . ", {$this->per_page}", $_GET['actions'], $_GET['current_uid']);

		 echo json_encode ( $array = array ("value" => $data ) );
		 }*/

		public function get_question_ansered_answer_action() {

			$question_user_answers = $this->model ( 'test' )->get_all_answer_by_uid ( $_GET ['user_id'] );

			echo json_encode ( $array = array ("value" => $question_user_answers ) );

		}

		public function get_questioned_question_action() {

			$question = $this->model ( 'test' )->get_all_question_by_puid ( $_GET ['user_id'] );

			echo json_encode ( $array = array ("value" => $question ) );

		}

		public function get_focus_topic_action() {
			$topic_ids = $this->model ( 'test' )->get_focus_topic_ids_by_uid ( $_GET ['user_id'] );

			$topic_list = $this->model ( 'test' )->get_topics_by_ids ( $topic_ids );

			echo json_encode ( $array = array ("value" => $topic_list ) );
		}

		public function get_fans_users_action() {
			if ($user_fans_info = $this->model ( 'test' )->get_user_fans ( $_GET ['user_id'], null )) {
				/*$question_info = $this->model('question')->get_question_info_by_id($_GET['question_id']);

				foreach($focus_users_info as $key => $val)
				{
				if ($val['uid'] == $question_info['published_uid'] and $question_info['anonymous'] == 1)
				{
				$focus_users[$key] = array(
				'uid' => 0,
				'user_name' => AWS_APP::lang()->_t('�����û�'),
				'avatar_file' => get_avatar_url(0, 'mid'),
				);
				}
				else
				{
				$focus_users[$key] = array(
				'uid' => $val['uid'],
				'user_name' => $val['user_name'],
				'avatar_file' => get_avatar_url($val['uid'], 'mid'),
				'url' => get_js_url('/people/' . $val['url_token'])
				);
				}
				}*/
			}

			// H::ajax_json_output($focus_users);
			echo json_encode ( $array = array ("value" => $user_fans_info ) );
		}

		public function get_friend_users_action() {
			if ($friend_user_info = $this->model ( 'test' )->get_user_friends ( $_GET ['user_id'], null )) {
				/*$question_info = $this->model('question')->get_question_info_by_id($_GET['question_id']);

				foreach($focus_users_info as $key => $val)
				{
				if ($val['uid'] == $question_info['published_uid'] and $question_info['anonymous'] == 1)
				{
				$focus_users[$key] = array(
				'uid' => 0,
				'user_name' => AWS_APP::lang()->_t('�����û�'),
				'avatar_file' => get_avatar_url(0, 'mid'),
				);
				}
				else
				{
				$focus_users[$key] = array(
				'uid' => $val['uid'],
				'user_name' => $val['user_name'],
				'avatar_file' => get_avatar_url($val['uid'], 'mid'),
				'url' => get_js_url('/people/' . $val['url_token'])
				);
				}
				}*/
			}

			// H::ajax_json_output($focus_users);
			echo json_encode ( $array = array ("value" => $friend_user_info ) );

		}

		public function get_focus_users_action() {

			if ($focus_users_info = $this->model ( 'test' )->get_focus_users_by_question ( $_GET ['question_id'], null )) {
				/*$question_info = $this->model('question')->get_question_info_by_id($_GET['question_id']);

				foreach($focus_users_info as $key => $val)
				{
				if ($val['uid'] == $question_info['published_uid'] and $question_info['anonymous'] == 1)
				{
				$focus_users[$key] = array(
				'uid' => 0,
				'user_name' => AWS_APP::lang()->_t('�����û�'),
				'avatar_file' => get_avatar_url(0, 'mid'),
				);
				}
				else
				{
				$focus_users[$key] = array(
				'uid' => $val['uid'],
				'user_name' => $val['user_name'],
				'avatar_file' => get_avatar_url($val['uid'], 'mid'),
				'url' => get_js_url('/people/' . $val['url_token'])
				);
				}
				}*/
			}

			// H::ajax_json_output($focus_users);
			echo json_encode ( $array = array ("value" => $focus_users_info ) );

		}

		public function is_already_focuse_action()
		{
			$data = $this->model ( 'test' )->has_focus_question ( $_GET['question_id'], $_GET['current_uid'] );

			if (!$data)
				echo json_encode ( $array = array ("is_focued" => 0 ) );
			else
				echo json_encode ( $array = array ("is_focued" => 1 ) );
		}

		public function focus_action() {
			/*	if (!$_POST['question_id'])
			 {
			 H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('���ⲻ����')));
			 }

			 if (! $this->model('question')->get_question_info_by_id($_POST['question_id']))
			 {
			 H::ajax_json_output(AWS_APP::RSM(null, '-1', AWS_APP::lang()->_t('���ⲻ����')));
			 }

			 H::ajax_json_output(AWS_APP::RSM(array(
			 'type' => $this->model('question')->add_focus_question($_POST['question_id'], $this->user_id)
			 ), 1, null));*/

			$question_id = trim ( $_POST ['question_id'], "\r\n\t" );
			$user_id = trim ( $_POST ['user_id'], "\r\n\t" );

			/*$question_id = trim ( $_GET ['question_id'], "\r\n\t" );
			 $user_id = trim ( $_GET ['user_id'], "\r\n\t" );*/

			$action = $this->model ( 'test' )->add_focus_question ( $question_id, $user_id );

			echo json_encode ( $array = array ("value" => $action ) );
		}

		public function add_comment_action() {

			$comment_content = trim ( $_POST ['comment_content'], "\r\n\t" );
			$question_id = trim ( $_POST ['question_id'], "\r\n\t" );
			$user_id = trim ( $_POST ['user_id'], "\r\n\t" );

			$data = $this->model ( 'test' )->insert_question_comment ( $question_id, $user_id, $comment_content );

			// $answer_info ['user_info'] = $this->user_info;
			// $answer_info ['answer_content'] = $this->model ( 'question' )->parse_at_user ( FORMAT::parse_attachs ( nl2br ( FORMAT::parse_bbcode ( $answer_info ['answer_content'] ) ) ) );
			echo json_encode ( $array = array ("value" => $data ) );
		}



		protected function prep_filename($filename)
		{
			$allowed_types = 'jpg,jpeg,png,gif';
			if (strpos($filename, '.') === FALSE)
			{
				return $filename;
			}

			$parts      = explode('.', $filename);
			$ext        = array_pop($parts);
			$filename   = array_shift($parts);

			foreach ($parts as $part)
			{
				if ( ! in_array(strtolower($part), $allowed_types))
				{
					$filename .= '.'.$part.'_';
				}
				else
				{
					$filename .= '.'.$part;
				}
			}

			$filename .= '.'.$ext;

			return $filename;
		}



		public function add_answer_action() {

			$answer_content = trim ( $_POST ['answer_content'], "\r\n\t" );
			$question_id = trim ( $_POST ['question_id'], "\r\n\t" );

			$user_id = trim ( $_POST ['user_id'], "\r\n\t" );

			$answer_id = $this->model ( 'publish' )->publish_answer ( $question_id, $answer_content, $user_id, $_POST ['anonymous'], $_POST ['attach_access_key'], $_POST ['auto_focus'] );

			$answer_info = $this->model ( 'answer' )->get_answer_by_id ( $answer_id );


			//$answer_info ['answer_content'] = $this->model ( 'question' )->parse_at_user ( FORMAT::parse_attachs2 ( nl2br ( FORMAT::parse_bbcode ( $answer_info ['answer_content'] ) ) ) );

			echo json_encode ( $array = array ("value" => $answer_id) );
		}

		//for upload img data to server by anxiang.xiao 20150828

		public function upload_answer_img_action() {
			//echo json_encode ( $array = array ("value" => "sucess answer") );
			

			/*
			 $target_path = "./upload1111/";//接收文件目录 
			 $target_path = $target_path . basename( $_FILES['uploadedfile']['name']);
			 if(move_uploaded_file($_FILES['uploadedfile']['tmp_name'], $target_path)) {
			 echo "The file ". basename( $_FILES['uploadedfile']['name']). " has been uploaded";
			 } else{
			 echo "There was an error uploading the file, please try again!" . $_FILES['uploadedfile']['error'];
			 } */

			$item_type = 'answer';
			$path = get_setting('upload_dir') . '/' . $item_type . '/' . gmdate('Ymd');
			if (!file_exists($path))
				mkdir($path);
			
			$path = $path.$this->prep_filename($_FILES['uploadedfile']['name']);

			$attach_access_key = md5(intval($_POST['user_id']).time());


			AWS_APP::upload()->initialize(array(
            'allowed_types' => get_setting('allowed_upload_types'),
            'upload_path' => get_setting('upload_dir') . '/' . $item_type . '/' . gmdate('Ymd'),
            'is_image' => FALSE,
            'max_size' => get_setting('upload_size_limit')
			));

			if (isset($_GET['uploadedfile']))
			{
				// AWS_APP::upload()->do_upload($_GET['aws_upload_file'], file_get_contents('php://input'));
			}
			else if (isset($_FILES['uploadedfile']))
			{
				AWS_APP::upload()->do_upload_2('uploadedfile');
			}

	 	if (! $upload_data = AWS_APP::upload()->data())
	 	{
	 		// die("{'error':'上传失败, 请与管理员联系'}");
	 		echo json_encode ( $array = array ("value" => "failed") );
	 	}

		else
		{
		 	// $attach_id = $this->model('publish')->add_attach_2('answer', basename( $_FILES['uploadedfile']['name']), $attach_access_key, time(), basename($path), TRUE, $_GET['answer_id']);
	
		 	$attach_id = $this->model('publish')->add_attach_2('answer',  basename( $_FILES['uploadedfile']['name']), $attach_access_key, time(), basename($upload_data['full_path']), TRUE,intval($_POST['answer_id']));
		 	// $attach_id = $this->model('publish')->add_attach($_GET['id'], $upload_data['orig_name'], $_GET['attach_access_key'], time(), basename($upload_data['full_path']), $upload_data['is_image']);
	
	
		 	$answer_info = $this->model ( 'answer' )->get_answer_by_id ( intval($_POST['answer_id']));
		 	// $answer_info['answer_content'] = str_replace("[attach]".basename( $_FILES['uploadedfile']['name'])."[/attach]", "[attach]".$attach_id."[/attach]", $answer_info['answer_content']);
		 	$answer_info['answer_content'] = str_replace("[attach]".$_FILES['uploadedfile']['name']."[/attach]", "[attach]".$attach_id."[/attach]", $answer_info['answer_content']);
		 	
		 	$this->model('answer')->update_answer_content(intval($_POST['answer_id']), $answer_info['answer_content']);
	
			echo json_encode ( $array = array ("value" => "sucess") );
		}
	}

		//for upload img data to server by anxiang.xiao 20150828

		public function upload_question_img_action() {
			
			// echo json_encode ( $array = array ("value" => "success question" ) );
	
			/*
			 $target_path = "./upload1111/";//接收文件目录 
			 $target_path = $target_path . basename( $_FILES['uploadedfile']['name']);
			 if(move_uploaded_file($_FILES['uploadedfile']['tmp_name'], $target_path)) {
			 echo "The file ". basename( $_FILES['uploadedfile']['name']). " has been uploaded";
			 } else{
			 echo "There was an error uploading the file, please try again!" . $_FILES['uploadedfile']['error'];
			 } */

			$item_type = 'questions';
			$path = get_setting('upload_dir') . '/' . $item_type . '/' . gmdate('Ymd');
			
			if (!file_exists($path))
				mkdir($path);
			$path = $path.$this->prep_filename($_FILES['uploadedfile']['name']);

			$attach_access_key = md5(intval($_POST['user_id']).time());


			AWS_APP::upload()->initialize(array(
            'allowed_types' => get_setting('allowed_upload_types'),
            'upload_path' => get_setting('upload_dir') . '/' . $item_type . '/' . gmdate('Ymd'),
            'is_image' => FALSE,
            'max_size' => get_setting('upload_size_limit')
			));

			if (isset($_GET['uploadedfile']))
			{
				// AWS_APP::upload()->do_upload($_GET['aws_upload_file'], file_get_contents('php://input'));
			}
			else if (isset($_FILES['uploadedfile']))
			{
				AWS_APP::upload()->do_upload_2('uploadedfile');
			}

	 	if (! $upload_data = AWS_APP::upload()->data())
	 	{
	 		// die("{'error':'上传失败, 请与管理员联系'}");
	 		
	 		echo json_encode ( $array = array ("value" => "failed") );
	 	}
	 	else 
	 	{
		 	// $attach_id = $this->model('publish')->add_attach_2('answer', basename( $_FILES['uploadedfile']['name']), $attach_access_key, time(), basename($path), TRUE, $_GET['answer_id']);
		 	$attach_id = $this->model('publish')->add_attach_2('question',  basename( $_FILES['uploadedfile']['name']), $attach_access_key, time(), basename($upload_data['full_path']), TRUE,intval($_POST['question_id']));
		 	// $attach_id = $this->model('publish')->add_attach($_GET['id'], $upload_data['orig_name'], $_GET['attach_access_key'], time(), basename($upload_data['full_path']), $upload_data['is_image']);
		 	// $question_info = $this->model ( 'question' )->get_question_info_by_id ( $_GET['question_id']);
		 	$question_info = $this->model ( 'question' )->get_question_by_id ( intval($_POST['question_id']));
		 	// $question_info['question_detail'] = str_replace("[attach]".basename( $_FILES['uploadedfile']['name'])."[/attach]", "[attach]".$attach_id."[/attach]", $question_info['question_detail']);
		 	$question_info['question_detail'] = str_replace("[attach]".$_FILES['uploadedfile']['name']."[/attach]", "[attach]".$attach_id."[/attach]", $question_info['question_detail']);
		 	 
		 	$this->model('question')->update_question_detail(intval($_POST['question_id']), $question_info['question_detail']);
		 	 
		 	echo json_encode ( $array = array ("value" => "success" ) );
	 	}

	 }

		public function get_comment_list_action() {
			if (isset ( $_POST ['question_id'] )) {
				$question_id = trim ( $_POST ['question_id'] );

			}
			$question_comment = $this->model ( 'test' )->get_all_comment_by_question_id ( $question_id );
			echo json_encode ( $array = array ("value" => $question_comment ) );
		}


		public function get_answer_info_action() {
			if (isset ( $_GET ['answer_id'] )) {
				$answer_id = trim ( $_GET ['answer_id'] );

			}

			$question_answer = $this->model ( 'test' )->get_answer_by_id ( $answer_id );

			// here should parse answer_content for get img url directly by anxiang.xiao 20150827
			$question_answer['answer_content'] = $this->model('question')->parse_at_user(FORMAT::parse_attachs2(nl2br(FORMAT::parse_bbcode($question_answer['answer_content']))));

			echo json_encode ( $array = array ("value" => $question_answer ) );
		}

		public function get_answer_list_action() {
			if (isset ( $_POST ['question_id'] )) {
				$question_id = trim ( $_POST ['question_id'] );

			}

			/*	if (isset ( $_GET ['question_id'] )) {
			 $question_id = trim ( $_GET ['question_id'] );

			 }*/
			$question_answer = $this->model ( 'test' )->get_all_answer_by_question_id ( $question_id );
			echo json_encode ( $array = array ("value" => $question_answer ) );
		}

		public function add_question_action() {

			$question_id = $this->model ( 'publish' )->publish_question ( $_POST ['questionContent'], $_POST ['questionDetail'], $_POST ['categoryId'], $_POST ['uid'], $_POST ['topics'], $_POST ['anonymous'], $_POST ['attach_access_key'], $_POST ['ask_user_id'], $this->user_info ['permission'] ['create_topic'] );

			echo json_encode ( $array = array ("value" => $question_id) );
		}

		public function login_process_action() {
			//echo "this login_process_action";
			if (isset ( $_POST ['name'] )) {
				$username = trim ( $_POST ['name'] );
				$passwd = trim ( $_POST ['passwd'] );
				$user_info = $this->model ( 'account' )->check_login ( $username, $passwd );
					
				if ($user_info ['user_name'] != "") {
					echo json_encode ( $array = array ("user_info" => $user_info, "login_result" => "sucess" ) );
				} else {
					echo json_encode ( $array = array ("login_result" => "failed" ) );
				}

			}

		}

		public function get_search_questions_action() {
			//echo "this login_process_action";
			if (isset ( $_POST ['name'] )) {
				$username = trim ( $_POST ['name'] );
				$passwd = trim ( $_POST ['passwd'] );
				$user_info = $this->model ( 'account' )->check_login ( $username, $passwd );
					
				echo $user_info ['user_name'];
				echo $user_info ['user_name'];
			} else {
				/*$questionContent = isset($_POST['questionContent']) ? trim($_POST['questionContent']) : 'test content';
				 $questionDetail = isset($_POST['questionDetail']) ? trim($_POST['questionDetail']) : 'test detail';
				 $question_id = $this->model('publish')->publish_question($questionContent, $questionDetail, $_POST['category_id'], $this->user_id, $_POST['topics'], $_POST['anonymous'], $_POST['attach_access_key'], $_POST['ask_user_id'], $this->user_info['permission']['create_topic']);*/
				$where = "question_content LIKE '%" . $this->model ( 'question' )->quote ( $_GET ['question_name_search'] ) . "%'";
					
				$data = $this->model ( 'test' )->get_all_search_question ( $where );
					
				if ($data)
				echo json_encode ( $array = array ("value" => $data ) );
				else
				echo json_encode ( $array = array ("novalue" => $data ) );

			}

		}
		
		
	public function list_question_page_action() {
			//echo "this login_process_action";
			if (isset ( $_POST ['name'] )) {
				$username = trim ( $_POST ['name'] );
				$passwd = trim ( $_POST ['passwd'] );
				$user_info = $this->model ( 'account' )->check_login ( $username, $passwd );
					
				echo $user_info ['user_name'];
				echo $user_info ['user_name'];
			} else {
				/*$questionContent = isset($_POST['questionContent']) ? trim($_POST['questionContent']) : 'test content';
				 $questionDetail = isset($_POST['questionDetail']) ? trim($_POST['questionDetail']) : 'test detail';
				 $question_id = $this->model('publish')->publish_question($questionContent, $questionDetail, $_POST['category_id'], $this->user_id, $_POST['topics'], $_POST['anonymous'], $_POST['attach_access_key'], $_POST['ask_user_id'], $this->user_info['permission']['create_topic']);*/
				$data = $this->model ( 'test' )->get_all_question_page ($_GET['page']);
					
				if ($data)
					echo json_encode ( $array = array ("value" => $data ) );
				else 	
					echo json_encode ( $array = array ("novalue" => $data ) );

			}

			
		}

		public function list_question_action() {
			//echo "this login_process_action";
			if (isset ( $_POST ['name'] )) {
				$username = trim ( $_POST ['name'] );
				$passwd = trim ( $_POST ['passwd'] );
				$user_info = $this->model ( 'account' )->check_login ( $username, $passwd );
					
				echo $user_info ['user_name'];
				echo $user_info ['user_name'];
			} else {
				/*$questionContent = isset($_POST['questionContent']) ? trim($_POST['questionContent']) : 'test content';
				 $questionDetail = isset($_POST['questionDetail']) ? trim($_POST['questionDetail']) : 'test detail';
				 $question_id = $this->model('publish')->publish_question($questionContent, $questionDetail, $_POST['category_id'], $this->user_id, $_POST['topics'], $_POST['anonymous'], $_POST['attach_access_key'], $_POST['ask_user_id'], $this->user_info['permission']['create_topic']);*/
				$data = $this->model ( 'test' )->get_all_question ();
					

				echo json_encode ( $array = array ("value" => $data ) );

			}

			/*if (get_setting('ucenter_enabled') == 'Y')
			 {
			 if (!$user_info = $this->model('ucenter')->login($_POST['user_name'], $_POST['password']))
			 {
				$user_info = $this->model('account')->check_login($_POST['user_name'], $_POST['password']);
				}
				}
				else
				{
				$user_info = $this->model('account')->check_login($_POST['user_name'], $_POST['password']);
				}

				if (! $user_info)
				{
				H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('��������ȷ���ʺŻ�����')));
				}
				else
				{
				if ($user_info['forbidden'] == 1)
				{
				H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('��Ǹ, ����˺��Ѿ�����ֹ��¼')));
				}

				if (get_setting('site_close') == 'Y' AND $user_info['group_id'] != 1)
				{
				H::ajax_json_output(AWS_APP::RSM(null, -1, get_setting('close_notice')));
				}

				if (get_setting('register_valid_type') == 'approval' AND $user_info['group_id'] == 3)
				{
				$url = get_js_url('/account/valid_approval/');
				}
				else
				{
				if ($_POST['net_auto_login'])
				{
				$expire = 60 * 60 * 24 * 360;
				}

				$this->model('account')->update_user_last_login($user_info['uid']);
				$this->model('account')->setcookie_logout();

				$this->model('account')->setcookie_login($user_info['uid'], $_POST['user_name'], $_POST['password'], $user_info['salt'], $expire);

				if (get_setting('register_valid_type') == 'email' AND !$user_info['valid_email'])
				{
				AWS_APP::session()->valid_email = $user_info['email'];

				$url = get_js_url('/account/valid_email/');
				}
				else if ($user_info['is_first_login'] AND !$_POST['_is_mobile'])
				{
				$url = get_js_url('/home/first_login-TRUE');
				}
				else if ($_POST['return_url'] AND !strstr($_POST['return_url'], '/logout') AND
				($_POST['_is_mobile'] AND strstr($_POST['return_url'], '/m/') OR
				strstr($_POST['return_url'], '://') AND strstr($_POST['return_url'], base_url())))
				{
				$url = strip_tags($_POST['return_url']);
				}
				else if ($_POST['_is_mobile'])
				{
				$url = get_js_url('/m/');
				}

				if (get_setting('ucenter_enabled') == 'Y')
				{
				$sync_url = get_js_url('/account/sync_login/');

				$url = ($url) ? $sync_url . 'url-' . base64_encode($url) : $sync_url;
				}
				}

				H::ajax_json_output(AWS_APP::RSM(array(
				'url' => $url
				), 1, null));
				}*/
		}

		public function check_username_action() {
			if ($this->model ( 'account' )->check_username_char ( $_GET ['username'] )) {
				H::ajax_json_output ( AWS_APP::RSM ( null, - 1, AWS_APP::lang ()->_t ( '�û����Ϲ���' ) ) );
			}

			if ($this->model ( 'account' )->check_username_sensitive_words ( $_GET ['username'] ) || $this->model ( 'account' )->check_username ( $_GET ['username'] )) {
				H::ajax_json_output ( AWS_APP::RSM ( null, - 1, AWS_APP::lang ()->_t ( '�û����ѱ�ע��' ) ) );
			}

			H::ajax_json_output ( AWS_APP::RSM ( null, 1, null ) );
		}

		public function check_email_action() {
			if (! $_GET ['email']) {
				H::ajax_json_output ( AWS_APP::RSM ( null, - 1, AWS_APP::lang ()->_t ( '�����������ַ' ) ) );
			}

			if ($this->model ( 'account' )->check_email ( $_GET ['email'] )) {
				H::ajax_json_output ( AWS_APP::RSM ( null, - 1, AWS_APP::lang ()->_t ( '�����ַ�ѱ�ʹ��' ) ) );
			}

			H::ajax_json_output ( AWS_APP::RSM ( null, 1, null ) );
		}

	public function register_process_action() {
		if (get_setting ( 'register_type' ) == 'close') {
			H::ajax_json_output ( AWS_APP::RSM ( null, - 1, AWS_APP::lang ()->_t ( '��վĿǰ�ر�ע��' ) ) );
		} else if (get_setting ( 'register_type' ) == 'invite' and ! $_POST ['icode']) {
			H::ajax_json_output ( AWS_APP::RSM ( null, - 1, AWS_APP::lang ()->_t ( '��վֻ��ͨ������ע��' ) ) );
		} else if (get_setting ( 'register_type' ) == 'weixin') {
			H::ajax_json_output ( AWS_APP::RSM ( null, - 1, AWS_APP::lang ()->_t ( '��վֻ��ͨ��΢��ע��' ) ) );
		}
		
		if ($_POST ['icode']) {
			if (! $invitation = $this->model ( 'invitation' )->check_code_available ( $_POST ['icode'] ) and $_POST ['email'] == $invitation ['invitation_email']) {
				H::ajax_json_output ( AWS_APP::RSM ( null, - 1, AWS_APP::lang ()->_t ( '��������Ч�����������䲻һ��' ) ) );
			}
		}
		
		if (trim ( $_POST ['user_name'] ) == '') {
			H::ajax_json_output ( AWS_APP::RSM ( null, - 1, AWS_APP::lang ()->_t ( '�������û���' ) ) );
		} else if ($this->model ( 'account' )->check_username ( $_POST ['user_name'] )) {
			H::ajax_json_output ( AWS_APP::RSM ( null, - 1, AWS_APP::lang ()->_t ( '�û����Ѿ�����' ) ) );
		} else if ($check_rs = $this->model ( 'account' )->check_username_char ( $_POST ['user_name'] )) {
			H::ajax_json_output ( AWS_APP::RSM ( null, - 1, AWS_APP::lang ()->_t ( '�û������Ч�ַ�' ) ) );
		} else if ($this->model ( 'account' )->check_username_sensitive_words ( $_POST ['user_name'] ) or trim ( $_POST ['user_name'] ) != $_POST ['user_name']) {
			H::ajax_json_output ( AWS_APP::RSM ( null, - 1, AWS_APP::lang ()->_t ( '�û����а���дʻ�ϵͳ������' ) ) );
		}
		
		if ($this->model ( 'account' )->check_email ( $_POST ['email'] )) {
			H::ajax_json_output ( AWS_APP::RSM ( null, - 1, AWS_APP::lang ()->_t ( 'E-Mail �Ѿ���ʹ��, ���ʽ����ȷ' ) ) );
		}
		
		if (strlen ( $_POST ['password'] ) < 6 or strlen ( $_POST ['password'] ) > 16) {
			H::ajax_json_output ( AWS_APP::RSM ( null, - 1, AWS_APP::lang ()->_t ( '���볤�Ȳ���Ϲ���' ) ) );
		}
		
		if (! $_POST ['agreement_chk']) {
			H::ajax_json_output ( AWS_APP::RSM ( null, - 1, AWS_APP::lang ()->_t ( '�����ͬ���û�Э����ܼ���' ) ) );
		}
		
		// �����֤��
		if (! AWS_APP::captcha ()->is_validate ( $_POST ['seccode_verify'] ) and get_setting ( 'register_seccode' ) == 'Y') {
			H::ajax_json_output ( AWS_APP::RSM ( null, - 1, AWS_APP::lang ()->_t ( '����д��ȷ����֤��' ) ) );
		}
		
		if (get_setting ( 'ucenter_enabled' ) == 'Y') {
			$result = $this->model ( 'ucenter' )->register ( $_POST ['user_name'], $_POST ['password'], $_POST ['email'] );
			
			if (is_array ( $result )) {
				$uid = $result ['user_info'] ['uid'];
			} else {
				H::ajax_json_output ( AWS_APP::RSM ( null, - 1, $result ) );
			}
		} else {
			$uid = $this->model ( 'account' )->user_register ( $_POST ['user_name'], $_POST ['password'], $_POST ['email'] );
		}
		
		if ($_POST ['email'] == $invitation ['invitation_email']) {
			$this->model ( 'active' )->set_user_email_valid_by_uid ( $uid );
			
			$this->model ( 'active' )->active_user_by_uid ( $uid );
		}
		
		if (isset ( $_POST ['sex'] )) {
			$update_data ['sex'] = intval ( $_POST ['sex'] );
			
			if ($_POST ['province']) {
				$update_data ['province'] = htmlspecialchars ( $_POST ['province'] );
				$update_data ['city'] = htmlspecialchars ( $_POST ['city'] );
			}
			
			if ($_POST ['job_id']) {
				$update_data ['job_id'] = intval ( $_POST ['job_id'] );
			}
			
			$update_attrib_data ['signature'] = htmlspecialchars ( $_POST ['signature'] );
			
			// �������
			$this->model ( 'account' )->update_users_fields ( $update_data, $uid );
			
			// ���´ӱ�
			$this->model ( 'account' )->update_users_attrib_fields ( $update_attrib_data, $uid );
		}
		
		$this->model ( 'account' )->setcookie_logout ();
		$this->model ( 'account' )->setsession_logout ();
		
		if ($_POST ['icode']) {
			$follow_users = $this->model ( 'invitation' )->get_invitation_by_code ( $_POST ['icode'] );
		} else if (HTTP::get_cookie ( 'fromuid' )) {
			$follow_users = $this->model ( 'account' )->get_user_info_by_uid ( HTTP::get_cookie ( 'fromuid' ) );
		}
		
		if ($follow_users ['uid']) {
			$this->model ( 'follow' )->user_follow_add ( $uid, $follow_users ['uid'] );
			$this->model ( 'follow' )->user_follow_add ( $follow_users ['uid'], $uid );
			
			$this->model ( 'integral' )->process ( $follow_users ['uid'], 'INVITE', get_setting ( 'integral_system_config_invite' ), '����ע��: ' . $_POST ['user_name'], $follow_users ['uid'] );
		}
		
		if ($_POST ['icode']) {
			$this->model ( 'invitation' )->invitation_code_active ( $_POST ['icode'], time (), fetch_ip (), $uid );
		}
		
		if (get_setting ( 'register_valid_type' ) == 'N' or (get_setting ( 'register_valid_type' ) == 'email' and get_setting ( 'register_type' ) == 'invite')) {
			$this->model ( 'active' )->active_user_by_uid ( $uid );
		}
		
		$user_info = $this->model ( 'account' )->get_user_info_by_uid ( $uid );
		
		if (get_setting ( 'register_valid_type' ) == 'N' or $user_info ['group_id'] != 3 or $_POST ['email'] == $invitation ['invitation_email']) {
			$this->model ( 'account' )->setcookie_login ( $user_info ['uid'], $user_info ['user_name'], $_POST ['password'], $user_info ['salt'] );
			
			if (! $_POST ['_is_mobile']) {
				H::ajax_json_output ( AWS_APP::RSM ( array ('url' => get_js_url ( '/home/first_login-TRUE' ) ), 1, null ) );
			}
		} else {
			AWS_APP::session ()->valid_email = $user_info ['email'];
			
			$this->model ( 'active' )->new_valid_email ( $uid );
			
			if (! $_POST ['_is_mobile']) {
				H::ajax_json_output ( AWS_APP::RSM ( array ('url' => get_js_url ( '/account/valid_email/' ) ), 1, null ) );
			}
		}
		
		if ($_POST ['_is_mobile']) {
			if ($_POST ['return_url']) {
				$user_info = $this->model ( 'account' )->get_user_info_by_uid ( $uid );
				
				$this->model ( 'account' )->setcookie_login ( $user_info ['uid'], $user_info ['user_name'], $_POST ['password'], $user_info ['salt'] );
				
				$return_url = strip_tags ( $_POST ['return_url'] );
			} else {
				$return_url = get_js_url ( '/m/' );
			}
			
			H::ajax_json_output ( AWS_APP::RSM ( array ('url' => $return_url ), 1, null ) );
		}
	}

		public function register_agreement_action() {
			H::ajax_json_output ( AWS_APP::RSM ( null, 1, nl2br ( get_setting ( 'register_agreement' ) ) ) );
		}

		public function welcome_message_template_action() {
			TPL::assign ( 'job_list', $this->model ( 'work' )->get_jobs_list () );

			TPL::output ( 'account/ajax/welcome_message_template' );
		}

		public function welcome_get_topics_action() {
			if ($topics_list = $this->model ( 'topic' )->get_topic_list ( null, 'RAND()', 8 )) {
				foreach ( $topics_list as $key => $topic ) {
					$topics_list [$key] ['has_focus'] = $this->model ( 'topic' )->has_focus_topic ( $this->user_id, $topic ['topic_id'] );
				}
			}
			TPL::assign ( 'topics_list', $topics_list );

			TPL::output ( 'account/ajax/welcome_get_topics' );
		}

		public function welcome_get_users_action() {
			if ($welcome_recommend_users = trim ( rtrim ( get_setting ( 'welcome_recommend_users' ), ',' ) )) {
				$welcome_recommend_users = explode ( ',', $welcome_recommend_users );
					
				$users_list = $this->model ( 'account' )->get_users_list ( "user_name IN('" . implode ( "','", $welcome_recommend_users ) . "')", 6, true, true, 'RAND()' );
			}

			if (! $users_list) {
				$users_list = $this->model ( 'account' )->get_activity_random_users ( 6 );
			}

			if ($users_list) {
				foreach ( $users_list as $key => $val ) {
					$users_list [$key] ['follow_check'] = $this->model ( 'follow' )->user_follow_check ( $this->user_id, $val ['uid'] );
				}
			}

			TPL::assign ( 'users_list', $users_list );

			TPL::output ( 'account/ajax/welcome_get_users' );
		}

		public function clean_first_login_action() {
			$this->model ( 'account' )->clean_first_login ( $this->user_id );

			die ( 'success' );
		}

		public function delete_draft_action() {
			if (! $_POST ['type']) {
				die ();
			}

			if ($_POST ['type'] == 'clean') {
				$this->model ( 'draft' )->clean_draft ( $this->user_id );
			} else {
				$this->model ( 'draft' )->delete_draft ( $_POST ['item_id'], $_POST ['type'], $this->user_id );
			}

			H::ajax_json_output ( AWS_APP::RSM ( null, 1, null ) );
		}

		public function save_draft_action() {
			if (! $_GET ['item_id'] or ! $_GET ['type'] or ! $_POST) {
				die ();
			}

			$this->model ( 'draft' )->save_draft ( $_GET ['item_id'], $_GET ['type'], $this->user_id, $_POST );

			H::ajax_json_output ( AWS_APP::RSM ( null, 1, AWS_APP::lang ()->_t ( '�ѱ���ݸ�, %s', date ( 'H:i:s', time () ) ) ) );
		}

		public function modify_unvalid_email_action() {
			if (! $user_info = $this->model ( 'account' )->get_user_info_by_email ( AWS_APP::session ()->valid_email )) {
				H::ajax_json_output ( AWS_APP::RSM ( null, - 1, AWS_APP::lang ()->_t ( '�û�������' ) ) );
			}

			if ($user_info ['valid_email'] == 1) {
				H::ajax_json_output ( AWS_APP::RSM ( null, - 1, AWS_APP::lang ()->_t ( '����������֤�����û��������' ) ) );
			}

			if ($this->model ( 'account' )->check_email ( $_POST ['email'] )) {
				H::ajax_json_output ( AWS_APP::RSM ( null, - 1, AWS_APP::lang ()->_t ( '�����ַ�ѱ�ʹ��' ) ) );
			}

			$this->model ( 'account' )->update_users_fields ( array ('email' => strtolower ( $_POST ['email'] ) ), $user_info ['uid'] );

			$this->model ( 'active' )->new_valid_email ( $this->user_id );

			AWS_APP::session ()->valid_email = strtolower ( $_POST ['email'] );

			H::ajax_json_output ( AWS_APP::RSM ( null, - 1, AWS_APP::lang ()->_t ( '�ʼ����ͳɹ�' ) ) );
		}

		public function send_valid_mail_action() {
			if (! $this->user_id) {
				if (H::valid_email ( AWS_APP::session ()->valid_email )) {
					$this->user_info = $this->model ( 'account' )->get_user_info_by_email ( AWS_APP::session ()->valid_email );
					$this->user_id = $this->user_info ['uid'];
				}
			}

			if (! H::valid_email ( $this->user_info ['email'] )) {
				H::ajax_json_output ( AWS_APP::RSM ( null, - 1, AWS_APP::lang ()->_t ( '����, �û�û���ṩ E-mail' ) ) );
			}

			if ($this->user_info ['valid_email'] == 1) {
				H::ajax_json_output ( AWS_APP::RSM ( null, - 1, AWS_APP::lang ()->_t ( '�û������Ѿ���֤' ) ) );
			}

			$this->model ( 'active' )->new_valid_email ( $this->user_id );

			H::ajax_json_output ( AWS_APP::RSM ( null, - 1, AWS_APP::lang ()->_t ( '�ʼ����ͳɹ�' ) ) );
		}

		public function valid_email_active_action() {
			/*if (!$active_data = $this->model('active')->get_active_code($_POST['active_code'], 'VALID_EMAIL'))
			 {
			 H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('����ʧ��, ��Ч��t��')));
			 }

			 if ($active_data['active_time'] OR $active_data['active_ip'])
			 {
			 H::ajax_json_output(AWS_APP::RSM(array(
				'url' => get_js_url('/account/login/'),
				), 1, null));
				}

				if (!$user_info = $this->model('account')->get_user_info_by_uid($active_data['uid']))
				{
				H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('����ʧ��, ��Ч��t��')));
				}

				if ($user_info['valid_email'])
				{
				H::ajax_json_output(AWS_APP::RSM(array(
				'url' => get_js_url('/account/login/'),
				), 1, null));
				}

				if ($this->model('active')->active_code_active($_POST['active_code'], 'VALID_EMAIL'))
				{
				if (AWS_APP::session()->valid_email)
				{
				unset(AWS_APP::session()->valid_email);
				}

				$this->model('active')->set_user_email_valid_by_uid($user_info['uid']);

				if (get_setting('register_valid_type') == 'email' OR get_setting('register_valid_type') == 'N')
				{
				if ($user_info['group_id'] == 3)
				{
				$this->model('active')->active_user_by_uid($user_info['uid']);
				}

				// �ʻ�����ɹ����л�Ϊ��¼״̬��ת����ҳ
				$this->model('account')->setsession_logout();
				$this->model('account')->setcookie_logout();

				$this->model('account')->update_user_last_login($user_info['uid']);

				$this->model('account')->setcookie_login($user_info['uid'], $user_info['user_name'], $user_info['password'], $user_info['salt'], null, false);
				}

				$this->model('account')->welcome_message($user_info['uid'], $user_info['user_name'], $user_info['email']);

				if (get_setting('register_valid_type') == 'email' OR get_setting('register_valid_type') == 'N')
				{
				$url = $user_info['is_first_login'] ? '/first_login-TRUE' : '/';

				H::ajax_json_output(AWS_APP::RSM(array(
				'url' => get_js_url($url)
				), 1, null));
				}
				else
				{
				H::ajax_json_output(AWS_APP::RSM(null, -1, AWS_APP::lang()->_t('����ɹ�, ��ȴ����Ա����˻�')));
				}
				}*/
		}

		public function request_find_password_action() {
			if (! H::valid_email ( $_POST ['email'] )) {
				H::ajax_json_output ( AWS_APP::RSM ( null, - 1, AWS_APP::lang ()->_t ( '����д��ȷ�������ַ' ) ) );
			}

			if (! AWS_APP::captcha ()->is_validate ( $_POST ['seccode_verify'] )) {
				H::ajax_json_output ( AWS_APP::RSM ( null, '-1', AWS_APP::lang ()->_t ( '����д��ȷ����֤��' ) ) );
			}

			if (! $user_info = $this->model ( 'account' )->get_user_info_by_email ( $_POST ['email'] )) {
				H::ajax_json_output ( AWS_APP::RSM ( null, - 1, AWS_APP::lang ()->_t ( '�����ַ������ʺŲ�����' ) ) );
			}

			$this->model ( 'active' )->new_find_password ( $user_info ['uid'] );

			AWS_APP::session ()->find_password = $user_info ['email'];

			if (is_mobile ()) {
				$url = get_js_url ( '/m/find_password_success/' );
			} else {
				$url = get_js_url ( '/account/find_password/process_success/' );
			}

			H::ajax_json_output ( AWS_APP::RSM ( array ('url' => $url ), 1, null ) );
		}

		public function find_password_modify_action() {
			/*if (!AWS_APP::captcha()->is_validate($_POST['seccode_verify']))
			 {
			 H::ajax_json_output(AWS_APP::RSM(null, -1,  AWS_APP::lang()->_t('����д��ȷ����֤��')));
			 }

			 $active_data = $this->model('active')->get_active_code($_POST['active_code'], 'FIND_PASSWORD');

			 if ($active_data)
			 {
			 if ($active_data['active_time'] OR $active_data['active_ip'])
			 {
				H::ajax_json_output(AWS_APP::RSM(null, -1,  AWS_APP::lang()->_t('t����ʧЧ���������һ�����')));
				}
				}
				else
				{
				H::ajax_json_output(AWS_APP::RSM(null, -1,  AWS_APP::lang()->_t('t����ʧЧ���������һ�����')));
				}

				if (!$_POST['password'])
				{
				H::ajax_json_output(AWS_APP::RSM(null, -1,  AWS_APP::lang()->_t('����������')));
				}

				if ($_POST['password'] != $_POST['re_password'])
				{
				H::ajax_json_output(AWS_APP::RSM(null, -1,  AWS_APP::lang()->_t('}����������벻һ��')));
				}

				if (! $uid = $this->model('active')->active_code_active($_POST['active_code'], 'FIND_PASSWORD'))
				{
				H::ajax_json_output(AWS_APP::RSM(null, -1,  AWS_APP::lang()->_t('t����ʧЧ���������һ�����')));
				}

				$user_info = $this->model('account')->get_user_info_by_uid($uid);

				$this->model('account')->update_user_password_ingore_oldpassword($_POST['password'], $uid, $user_info['salt']);

				$this->model('active')->set_user_email_valid_by_uid($user_info['uid']);

				if ($user_info['group_id'] == 3)
				{
				$this->model('active')->active_user_by_uid($user_info['uid']);
				}

				$this->model('account')->setcookie_logout();

				$this->model('account')->setsession_logout();

				unset(AWS_APP::session()->find_password);

				H::ajax_json_output(AWS_APP::RSM(array(
				'url' => get_js_url('/account/login/'),
				), 1, AWS_APP::lang()->_t('�����޸ĳɹ�, �뷵�ص�¼')));*/
		}

		public function avatar_upload_action() {
			AWS_APP::upload ()->initialize ( array ('allowed_types' => 'jpg,jpeg,png,gif', 'upload_path' => get_setting ( 'upload_dir' ) . '/avatar/' . $this->model ( 'account' )->get_avatar ( $this->user_id, '', 1 ), 'is_image' => TRUE, 'max_size' => get_setting ( 'upload_avatar_size_limit' ), 'file_name' => $this->model ( 'account' )->get_avatar ( $this->user_id, '', 2 ), 'encrypt_name' => FALSE ) )->do_upload ( 'aws_upload_file' );

			if (AWS_APP::upload ()->get_error ()) {
				switch (AWS_APP::upload ()->get_error ()) {
					default :
						die ( "{'error':'�������: " . AWS_APP::upload ()->get_error () . "'}" );
						break;

					case 'upload_invalid_filetype' :
						die ( "{'error':'�ļ�������Ч'}" );
						break;

					case 'upload_invalid_filesize' :
						die ( "{'error':'�ļ��ߴ���, �������ߴ�Ϊ " . get_setting ( 'upload_size_limit' ) . " KB'}" );
						break;
				}
			}

			if (! $upload_data = AWS_APP::upload ()->data ()) {
				die ( "{'error':'�ϴ�ʧ��, �������Աjϵ'}" );
			}

			if ($upload_data ['is_image'] == 1) {
				foreach ( AWS_APP::config ()->get ( 'image' )->avatar_thumbnail as $key => $val ) {
					$thumb_file [$key] = $upload_data ['file_path'] . $this->model ( 'account' )->get_avatar ( $this->user_id, $key, 2 );

					AWS_APP::image ()->initialize ( array ('quality' => 90, 'source_image' => $upload_data ['full_path'], 'new_image' => $thumb_file [$key], 'width' => $val ['w'], 'height' => $val ['h'] ) )->resize ();
				}
			}

			$update_data ['avatar_file'] = $this->model ( 'account' )->get_avatar ( $this->user_id, null, 1 ) . basename ( $thumb_file ['min'] );

			// �������
			$this->model ( 'account' )->update_users_fields ( $update_data, $this->user_id );

			if (! $this->model ( 'integral' )->fetch_log ( $this->user_id, 'UPLOAD_AVATAR' )) {
				$this->model ( 'integral' )->process ( $this->user_id, 'UPLOAD_AVATAR', round ( (get_setting ( 'integral_system_config_profile' ) * 0.2) ), '�ϴ�ͷ��' );
			}

			echo htmlspecialchars ( json_encode ( array ('success' => true, 'thumb' => get_setting ( 'upload_url' ) . '/avatar/' . $this->model ( 'account' )->get_avatar ( $this->user_id, null, 1 ) . basename ( $thumb_file ['max'] ) ) ), ENT_NOQUOTES );
		}

		function add_edu_action() {
			$school_name = htmlspecialchars ( $_POST ['school_name'] );
			$education_years = intval ( $_POST ['education_years'] );
			$departments = htmlspecialchars ( $_POST ['departments'] );

			if (! $_POST ['school_name']) {
				H::ajax_json_output ( AWS_APP::RSM ( null, '-1', AWS_APP::lang ()->_t ( '������ѧУ���' ) ) );
			}

			if (! $_POST ['departments']) {
				H::ajax_json_output ( AWS_APP::RSM ( null, '-1', AWS_APP::lang ()->_t ( '������Ժϵ' ) ) );
			}

			if ($_POST ['education_years'] == AWS_APP::lang ()->_t ( '��ѡ��' ) or ! $_POST ['education_years']) {
				H::ajax_json_output ( AWS_APP::RSM ( null, '-1', AWS_APP::lang ()->_t ( '��ѡ����ѧ���' ) ) );
			}

			if (preg_match ( '/\//is', $_POST ['school_name'] )) {
				H::ajax_json_output ( AWS_APP::RSM ( null, '-1', AWS_APP::lang ()->_t ( 'ѧУ��Ʋ��ܰ� /' ) ) );
			}

			if (preg_match ( '/\//is', $_POST ['departments'] )) {
				H::ajax_json_output ( AWS_APP::RSM ( null, '-1', AWS_APP::lang ()->_t ( 'Ժϵ��Ʋ��ܰ� /' ) ) );
			}

			if (get_setting ( 'auto_create_social_topics' ) == 'Y') {
				$this->model ( 'topic' )->save_topic ( $_POST ['school_name'] );
				$this->model ( 'topic' )->save_topic ( $_POST ['departments'] );
			}

			$edu_id = $this->model ( 'education' )->add_education_experience ( $this->user_id, $school_name, $education_years, $departments );

			if (! $this->model ( 'integral' )->fetch_log ( $this->user_id, 'UPDATE_EDU' )) {
				$this->model ( 'integral' )->process ( $this->user_id, 'UPDATE_EDU', round ( (get_setting ( 'integral_system_config_profile' ) * 0.2) ), AWS_APP::lang ()->_t ( '���ƽ�����' ) );
			}

			H::ajax_json_output ( AWS_APP::RSM ( array ('id' => $edu_id ), 1, null ) );

		}

		function remove_edu_action() {
			$this->model ( 'education' )->del_education_experience ( $_POST ['id'], $this->user_id );

			H::ajax_json_output ( AWS_APP::RSM ( null, 1, null ) );

		}

		function add_work_action() {
			if (! $_POST ['company_name']) {
				H::ajax_json_output ( AWS_APP::RSM ( null, '-1', AWS_APP::lang ()->_t ( '�����빫˾���' ) ) );
			}

			if (! $_POST ['job_id']) {
				H::ajax_json_output ( AWS_APP::RSM ( null, '-1', AWS_APP::lang ()->_t ( '��ѡ��ְλ' ) ) );
			}

			if (! $_POST ['start_year'] or ! $_POST ['end_year']) {
				H::ajax_json_output ( AWS_APP::RSM ( null, '-1', AWS_APP::lang ()->_t ( '��ѡ����ʱ��' ) ) );
			}

			if (preg_match ( '/\//is', $_POST ['company_name'] )) {
				H::ajax_json_output ( AWS_APP::RSM ( null, '-1', AWS_APP::lang ()->_t ( '��˾��Ʋ��ܰ� /' ) ) );
			}

			if (get_setting ( 'auto_create_social_topics' ) == 'Y') {
				$this->model ( 'topic' )->save_topic ( $_POST ['company_name'] );
			}

			$work_id = $this->model ( 'work' )->add_work_experience ( $this->user_id, $_POST ['start_year'], $_POST ['end_year'], $_POST ['company_name'], $_POST ['job_id'] );

			if (! $this->model ( 'integral' )->fetch_log ( $this->user_id, 'UPDATE_WORK' )) {
				$this->model ( 'integral' )->process ( $this->user_id, 'UPDATE_WORK', round ( (get_setting ( 'integral_system_config_profile' ) * 0.2) ), AWS_APP::lang ()->_t ( '���ƹ�����' ) );
			}

			H::ajax_json_output ( AWS_APP::RSM ( array ('id' => $work_id ), 1, null ) );
		}

		function remove_work_action() {
			$this->model ( 'work' )->del_work_experience ( $_POST ['id'], $this->user_id );

			H::ajax_json_output ( AWS_APP::RSM ( null, 1, null ) );
		}

		//�޸Ľ�����
		function edit_edu_action() {
			if (! $_POST ['school_name']) {
				H::ajax_json_output ( AWS_APP::RSM ( null, '-1', AWS_APP::lang ()->_t ( '������ѧУ���' ) ) );
			}

			if (! $_POST ['departments']) {
				H::ajax_json_output ( AWS_APP::RSM ( null, '-1', AWS_APP::lang ()->_t ( '������Ժϵ' ) ) );
			}

			if (! $_POST ['education_years']) {
				H::ajax_json_output ( AWS_APP::RSM ( null, '-1', AWS_APP::lang ()->_t ( '��ѡ����ѧ���' ) ) );
			}

			$update_data ['school_name'] = htmlspecialchars ( $_POST ['school_name'] );
			$update_data ['education_years'] = intval ( $_POST ['education_years'] );
			$update_data ['departments'] = htmlspecialchars ( $_POST ['departments'] );

			if (preg_match ( '/\//is', $_POST ['school_name'] )) {
				H::ajax_json_output ( AWS_APP::RSM ( null, '-1', AWS_APP::lang ()->_t ( 'ѧУ��Ʋ��ܰ� /' ) ) );
			}

			if (preg_match ( '/\//is', $_POST ['departments'] )) {
				H::ajax_json_output ( AWS_APP::RSM ( null, '-1', AWS_APP::lang ()->_t ( 'Ժϵ��Ʋ��ܰ� /' ) ) );
			}

			if (get_setting ( 'auto_create_social_topics' ) == 'Y') {
				$this->model ( 'topic' )->save_topic ( $_POST ['school_name'] );
				$this->model ( 'topic' )->save_topic ( $_POST ['departments'] );
			}

			$this->model ( 'education' )->update_education_experience ( $update_data, $_GET ['id'], $this->user_id );

			H::ajax_json_output ( AWS_APP::RSM ( null, 1, null ) );
		}

		//�޸Ĺ�����
		function edit_work_action() {
			if (! $_POST ['company_name']) {
				H::ajax_json_output ( AWS_APP::RSM ( null, '-1', AWS_APP::lang ()->_t ( '�����빫˾���' ) ) );
			}

			if (! $_POST ['job_id']) {
				H::ajax_json_output ( AWS_APP::RSM ( null, '-1', AWS_APP::lang ()->_t ( '��ѡ��ְλ' ) ) );
			}

			if (! $_POST ['start_year'] or ! $_POST ['end_year']) {
				H::ajax_json_output ( AWS_APP::RSM ( null, '-1', AWS_APP::lang ()->_t ( '��ѡ����ʱ��' ) ) );
			}

			$update_data ['job_id'] = intval ( $_POST ['job_id'] );
			$update_data ['company_name'] = htmlspecialchars ( $_POST ['company_name'] );

			$update_data ['start_year'] = intval ( $_POST ['start_year'] );
			$update_data ['end_year'] = intval ( $_POST ['end_year'] );

			if (preg_match ( '/\//is', $_POST ['company_name'] )) {
				H::ajax_json_output ( AWS_APP::RSM ( null, '-1', AWS_APP::lang ()->_t ( '��˾��Ʋ��ܰ� /' ) ) );
			}

			if (get_setting ( 'auto_create_social_topics' ) == 'Y') {
				$this->model ( 'topic' )->save_topic ( $_POST ['company_name'] );
			}

			$this->model ( 'work' )->update_work_experience ( $update_data, $_GET ['id'], $this->user_id );

			H::ajax_json_output ( AWS_APP::RSM ( null, 1, null ) );
		}

		public function privacy_setting_action() {
			if ($notify_actions = $this->model ( 'notify' )->notify_action_details) {
				$notification_setting = array ();
					
				foreach ( $notify_actions as $key => $val ) {
					if (! isset ( $_POST ['notification_settings'] [$key] ) and $val ['user_setting']) {
						$notification_setting [] = intval ( $key );
					}
				}
			}

			$email_settings = array ('FOLLOW_ME' => 'N', 'QUESTION_INVITE' => 'N', 'NEW_ANSWER' => 'N', 'NEW_MESSAGE' => 'N', 'QUESTION_MOD' => 'N' );

			if ($_POST ['email_settings']) {
				foreach ( $_POST ['email_settings'] as $key => $val ) {
					unset ( $email_settings [$val] );
				}
			}

			$weixin_settings = array ('AT_ME' => 'N', 'NEW_ANSWER' => 'N', 'NEW_ARTICLE_COMMENT', 'NEW_COMMENT' => 'N', 'QUESTION_INVITE' => 'N' );

			if ($_POST ['weixin_settings']) {
				foreach ( $_POST ['weixin_settings'] as $key => $val ) {
					unset ( $weixin_settings [$val] );
				}
			}

			$this->model ( 'account' )->update_users_fields ( array ('email_settings' => serialize ( $email_settings ), 'weixin_settings' => serialize ( $weixin_settings ), 'weibo_visit' => intval ( $_POST ['weibo_visit'] ), 'inbox_recv' => intval ( $_POST ['inbox_recv'] ) ), $this->user_id );

			$this->model ( 'account' )->update_notification_setting_fields ( $notification_setting, $this->user_id );

			H::ajax_json_output ( AWS_APP::RSM ( null, - 1, AWS_APP::lang ()->_t ( '��˽���ñ���ɹ�' ) ) );
		}


		public function modify_password_action() {
			if (! $_POST ['old_password']) {
				H::ajax_json_output ( AWS_APP::RSM ( null, '-1', AWS_APP::lang ()->_t ( '�����뵱ǰ����' ) ) );
			}

			if ($_POST ['password'] != $_POST ['re_password']) {
				H::ajax_json_output ( AWS_APP::RSM ( null, '-1', AWS_APP::lang ()->_t ( '��������ͬ��ȷ������' ) ) );
			}

			if (strlen ( $_POST ['password'] ) < 6 or strlen ( $_POST ['password'] ) > 16) {
				H::ajax_json_output ( AWS_APP::RSM ( null, - 1, AWS_APP::lang ()->_t ( '���볤�Ȳ���Ϲ���' ) ) );
			}

			if (get_setting ( 'ucenter_enabled' ) == 'Y') {
				if ($this->model ( 'ucenter' )->is_uc_user ( $this->user_info ['email'] )) {
					$result = $this->model ( 'ucenter' )->user_edit ( $this->user_id, $this->user_info ['user_name'], $_POST ['old_password'], $_POST ['password'] );

					if ($result !== 1) {
						H::ajax_json_output ( AWS_APP::RSM ( null, - 1, $result ) );
					}
				}
			}

			if ($this->model ( 'account' )->update_user_password ( $_POST ['old_password'], $_POST ['password'], $this->user_id, $this->user_info ['salt'] )) {
				H::ajax_json_output ( AWS_APP::RSM ( null, 1, AWS_APP::lang ()->_t ( '�����޸ĳɹ�, ���μ�������' ) ) );
			} else {
				H::ajax_json_output ( AWS_APP::RSM ( null, '-1', AWS_APP::lang ()->_t ( '��������ȷ�ĵ�ǰ����' ) ) );
			}
		}

		public function integral_log_action() {
			if ($log = $this->model ( 'integral' )->fetch_all ( 'integral_log', 'uid = ' . $this->user_id, 'time DESC', (intval ( $_GET ['page'] ) * 10) . ', 10' )) {
				foreach ( $log as $key => $val ) {
					$parse_items [$val ['id']] = array ('item_id' => $val ['item_id'], 'action' => $val ['action'] );
				}
					
				TPL::assign ( 'log', $log );
				TPL::assign ( 'log_detail', $this->model ( 'integral' )->parse_log_item ( $parse_items ) );
			}

			TPL::output ( 'account/ajax/integral_log' );
		}

		public function verify_action() {
			if ($this->is_post () and ! $this->user_info ['verified']) {
				if (trim ( $_POST ['name'] ) == '') {
					H::ajax_json_output ( AWS_APP::RSM ( null, - 1, AWS_APP::lang ()->_t ( '��������ʵ�������ҵ���' ) ) );
				}
					
				if (trim ( $_POST ['reason'] ) == '') {
					H::ajax_json_output ( AWS_APP::RSM ( null, - 1, AWS_APP::lang ()->_t ( '������������֤˵��' ) ) );
				}
					
				if ($_FILES ['attach'] ['name']) {
					AWS_APP::upload ()->initialize ( array ('allowed_types' => 'jpg,png,gif', 'upload_path' => get_setting ( 'upload_dir' ) . '/verify', 'is_image' => FALSE, 'encrypt_name' => TRUE ) )->do_upload ( 'attach' );

					if (AWS_APP::upload ()->get_error ()) {
						switch (AWS_APP::upload ()->get_error ()) {
							default :
								H::ajax_json_output ( AWS_APP::RSM ( null, '-1', AWS_APP::lang ()->_t ( '�������' ) . ': ' . AWS_APP::upload ()->get_error () ) );
								break;

							case 'upload_invalid_filetype' :
								H::ajax_json_output ( AWS_APP::RSM ( null, '-1', AWS_APP::lang ()->_t ( '�ļ�������Ч' ) ) );
								break;
						}
					}

					if (! $upload_data = AWS_APP::upload ()->data ()) {
						H::ajax_json_output ( AWS_APP::RSM ( null, '-1', AWS_APP::lang ()->_t ( '�ϴ�ʧ��, �������Աjϵ' ) ) );
					}
				}
					
				$this->model ( 'verify' )->add_apply ( $this->user_id, $_POST ['name'], $_POST ['reason'], $_POST ['type'], array ('id_code' => htmlspecialchars ( $_POST ['id_code'] ), 'contact' => htmlspecialchars ( $_POST ['contact'] ) ), basename ( $upload_data ['full_path'] ) );
					
				$recipient_uid = get_setting ( 'report_message_uid' ) ? get_setting ( 'report_message_uid' ) : 1;
					
				//$this->model('message')->send_message($this->user_id, $recipient_uid, AWS_APP::lang()->_t('���µ���֤����, ���¼��̨�鿴����: %s', get_js_url('/admin/user/verify_approval_list/')));
			}

			H::ajax_json_output ( AWS_APP::RSM ( null, 1, null ) );
		}

		public function clean_user_recommend_cache_action() {
			AWS_APP::cache ()->delete ( 'user_recommend_' . $this->user_id );
		}

		public function unbinding_weixin_action() {
			if (! $this->user_info ['email']) {
				H::ajax_json_output ( AWS_APP::RSM ( null, '-1', AWS_APP::lang ()->_t ( '��ǰ�ʺ�û�а� Email, ���������' ) ) );
			}

			if (get_setting ( 'register_type' ) == 'weixin') {
				H::ajax_json_output ( AWS_APP::RSM ( null, '-1', AWS_APP::lang ()->_t ( '��ǰϵͳ���ò��������' ) ) );
			}

			$this->model ( 'openid_weixin_weixin' )->weixin_unbind ( $this->user_id );

			H::ajax_json_output ( AWS_APP::RSM ( null, 1, null ) );
		}

		public function weixin_login_process_action() {
			if (! get_setting ( 'weixin_app_id' ) or ! get_setting ( 'weixin_app_secret' ) or get_setting ( 'weixin_account_role' ) != 'service') {
				H::ajax_json_output ( AWS_APP::RSM ( null, - 1, AWS_APP::lang ()->_t ( '��ǰ΢�Ź��ں��ݲ�֧�ִ˹���' ) ) );
			}

			if ($user_info = $this->model ( 'openid_weixin_weixin' )->weixin_login_process ( session_id () )) {
				$this->model ( 'account' )->setcookie_login ( $user_info ['uid'], $user_info ['user_name'], $user_info ['password'], $user_info ['salt'], null, false );
					
				H::ajax_json_output ( AWS_APP::RSM ( null, 1, null ) );
			}

			H::ajax_json_output ( AWS_APP::RSM ( null, - 1, null ) );
		}

		public function complete_profile_action() {
			if ($this->user_info ['email']) {
				H::ajax_json_output ( AWS_APP::RSM ( null, '-1', AWS_APP::lang ()->_t ( '��ǰ�ʺ��Ѿ���������' ) ) );
			}

			$_POST ['user_name'] = htmlspecialchars ( trim ( $_POST ['user_name'] ) );

			if ($check_result = $this->model ( 'account' )->check_username_char ( $_POST ['user_name'] )) {
				H::ajax_json_output ( AWS_APP::RSM ( null, '-1', $check_result ) );
			}

			if ($this->user_info ['user_name'] != $_POST ['user_name']) {
				if ($this->model ( 'account' )->check_username_sensitive_words ( $_GET ['username'] ) || $this->model ( 'account' )->check_username ( $_GET ['username'] )) {
					H::ajax_json_output ( AWS_APP::RSM ( null, - 1, AWS_APP::lang ()->_t ( '�û����ѱ�ע��' ) ) );
				}
			}

			$update_data ['user_name'] = $_POST ['user_name'];

			if (! H::valid_email ( $this->user_info ['email'] )) {
				if (! H::valid_email ( $_POST ['email'] )) {
					H::ajax_json_output ( AWS_APP::RSM ( null, '-1', AWS_APP::lang ()->_t ( '��������ȷ�� E-Mail ��ַ' ) ) );
				}
					
				if ($this->model ( 'account' )->check_email ( $_POST ['email'] )) {
					H::ajax_json_output ( AWS_APP::RSM ( null, '-1', AWS_APP::lang ()->_t ( '�����Ѿ�����, ��ʹ���µ�����' ) ) );
				}
					
				$update_data ['email'] = $_POST ['email'];
					
				$this->model ( 'active' )->new_valid_email ( $this->user_id, $_POST ['email'] );
			}

			$this->model ( 'account' )->update_users_fields ( $update_data, $this->user_id );

			$this->model ( 'account' )->update_user_password_ingore_oldpassword ( $_POST ['password'], $this->user_id, $this->user_info ['salt'] );

			$this->model ( 'account' )->setcookie_login ( $this->user_info ['uid'], $update_data ['user_name'], $_POST ['password'], $this->user_info ['salt'] );

			H::ajax_json_output ( AWS_APP::RSM ( null, 1, null ) );
		}
}

