<?php
/*
 +--------------------------------------------------------------------------
 |   WeCenter [#RELEASE_VERSION#]
 |   ========================================
 |   by WeCenter Software
 |   漏 2011 - 2014 WeCenter. All Rights Reserved
 |   http://www.wecenter.com
 |   ========================================
 |   Support: WeCenter@qq.com
 |
 +---------------------------------------------------------------------------
 */

if (! defined ( 'IN_ANWSION' )) {
	die ();
}

class test_class extends AWS_MODEL {
	
	
// add for search user by name
	public function get_category_list($where, $limit = 10, $attrib = false, $exclude_self = true, $orderby = 'id DESC')
	{
		
		$result = $this->fetch_all('category', $where, $orderby, $limit);

		return $result;
	}

	public function get_all_category() {
	
		if ($result = $this->fetch_all ( 'category', "", "id DESC" )) {	
			return $result;
		}
	}

	public function remove_favorite_tag($item_id, $item_type, $tag, $uid)
	{
		if ($tag)
		{
			$where[] = "title = '" . $this->quote($tag) . "'";
		}

		if ($item_id)
		{
			$where[] = "item_id = " . intval($item_id);
		}

		$where[] = "`type` = '" . $this->quote($item_type) . "'";
		$where[] = 'uid = ' . intval($uid);

		return $this->delete('favorite_tag', implode(' AND ', $where));
	}

	public function remove_favorite_item($item_id, $item_type, $uid)
	{
		if (!$item_id OR !$item_type OR !$uid)
		{
			return false;
		}

		$this->delete('favorite', "item_id = " . intval($item_id) . " AND `type` = '" . $this->quote($item_type) . "' AND uid = " . intval($uid));
		//$this->delete('favorite_tag', "item_id = " . intval($item_id) . " AND `type` = '" . $this->quote($item_type) . "' AND uid = " . intval($uid));
	}



	public function add_favorite($item_id, $item_type, $uid)
	{
		/*if (!$item_id OR !$item_type)
		 {
			return false;
			}*/

		if (!$this->fetch_one('favorite', 'id', "type = '" . $this->quote($item_type) . "' AND item_id = " . intval($item_id) . ' AND uid = ' . intval($uid)))
		{
			return $this->insert('favorite', array(
				'item_id' => intval($item_id),
				'type' => $item_type,
				'uid' => intval($uid),
				'time' => time()
			));
				
			/*return $this->insert('favorite', array(
				'item_id' => 0,
				'type' => "user_add_dire",
				'uid' => intval($uid),
				'time' => time()
				));*/
		}
	}

	public function update_favorite_tag($item_id, $item_type, $tags,$discription, $uid)
	{
		/*	if (!$item_id OR !$tags OR !$item_type)
		 {
			return false;
			}
			*/


		if (!$this->fetch_one('favorite_tag', 'id', "item_id = " . intval($item_id) . " AND `type` = '" . $this->quote($item_type) . "' AND `title` = '" . $tag . "' AND uid = " . intval($uid)))
		{
			$this->insert('favorite_tag', array(
					'item_id' => intval($item_id),
					'type' => $item_type,
					'uid' => intval($uid),
					'title' => $tags)
			);

			/*$this->insert('favorite_tag', array(
			 'item_id' => 0,
			 'type' => "user_add_dire",
			 'uid' => intval($uid),
			 'title' => $tags,
			 'discription' => $discription)
				);*/
		}

		return true;
	}

	public function get_message_by_dialog_id($dialog_id)
	{
		if ($inbox = $this->fetch_all('inbox', 'dialog_id = ' . intval($dialog_id), 'add_time DESC'))
		{
			foreach ($inbox AS $key => $val)
			{
				$message[$val['id']] = $val;
				
				
				$message[$val['id']]['avatar_file'] = get_avatar_url($val['uid'], 'mid');
				
			}
		}

		return $message;
	}

	// add for search user by name
	public function get_users_list($where, $limit = 100, $attrib = false, $exclude_self = true, $orderby = 'uid DESC')
	{
		/*  if ($where)
		 {
		 $where = '(' . $where . ') AND forbidden = 0 AND group_id <> 3';
		 }
		 else
		 {
		 $where = 'forbidden = 0 AND group_id <> 3';
		 }

		 if ($exclude_self)
		 {
		 if ($where)
		 {
		 $where = '(' . $where . ') AND uid <> ' . AWS_APP::user()->get_info('uid');
		 }
		 else
		 {
		 $where = 'uid <> ' . AWS_APP::user()->get_info('uid');
		 }
		 }
		 */
		$result = $this->fetch_all('users', $where, $orderby, $limit);

		if ($result)
		{
			foreach ($result AS $key => $val)
			{
				unset($val['password'], $val['salt']);

				$data[$val['uid']] = $val;

				if (!$val['url_token'] AND $val['user_name'])
				{
					$data[$val['uid']]['url_token'] = urlencode($val['user_name']);
				}

				if ($val['email_settings'])
				{
					$data[$val['uid']]['email_settings'] = unserialize($val['email_settings']);
				}

				if ($val['weixin_settings'])
				{
					$data[$val['uid']]['weixin_settings'] = unserialize($val['weixin_settings']);
				}
				
				if ($val['avatar_file'])
				{
					$data[$val['uid']]['avatar_file'] = get_avatar_url($val['uid'], 'mid');
				}

				$uids[] = $val['uid'];
			}

			if ($attrib AND $uids)
			{
				if ($users_attrib = $this->fetch_all('users_attrib', 'uid IN(' . implode(',', $uids) . ')'))
				{
					foreach ($users_attrib AS $key => $val)
					{
						unset($val['id']);

						foreach ($val AS $attrib_key => $attrib_val)
						{
							$data[$val['uid']][$attrib_key] = $attrib_val;
						}
					}
				}
			}
		}

		return $data;
	}

	public function get_user_focus($uid, $limit = 10)
	{
		// should be modify for better add by anxiang.xiao 20150801
		$limit = 1000;
		if ($question_focus = $this->fetch_all('question_focus', "uid = " . intval($uid), 'question_id DESC', $limit))
		{
			foreach ($question_focus as $key => $val)
			{
				$question_ids[] = $val['question_id'];
			}
		}

		if ($question_ids)
		{
			return $this->fetch_all('question', "question_id IN(" . implode(',', $question_ids) . ")", 'add_time DESC');
		}
	}



	public function get_user_info_by_uid($uid, $attrib = false, $cache_result = true)
	{
		if (! $uid)
		{
			return false;
		}

		if ($uid == -1)
		{
			return array(
                'uid' => -1,
                'user_name' => AWS_APP::lang()->_t('[已注销]'),
			);
		}

		if ($cache_result)
		{
			static $users_info;

			if ($users_info[$uid . '_attrib'])
			{
				return $users_info[$uid . '_attrib'];
			}
			else if ($users_info[$uid])
			{
				return $users_info[$uid];
			}
		}

		if (! $user_info = $this->fetch_row('users', 'uid = ' . intval($uid)))
		{
			return false;
		}

		if ($attrib)
		{
			if ($user_attrib = $this->fetch_row('users_attrib', 'uid = ' . intval($uid)))
			{
				foreach ($user_attrib AS $key => $val)
				{
					$user_info[$key] = $val;
				}
			}
		}

		if (!$user_info['url_token'] AND $user_info['user_name'])
		{
			$user_info['url_token'] = urlencode($user_info['user_name']);
		}

		if ($user_info['email_settings'])
		{
			$user_info['email_settings'] = unserialize($user_info['email_settings']);
		}
		else
		{
			$user_info['email_settings'] = array();
		}

		if ($user_info['weixin_settings'])
		{
			$user_info['weixin_settings'] = unserialize($user_info['weixin_settings']);
		}
		else
		{
			$user_info['weixin_settings'] = array();
		}

		$users_info[$uid] = $user_info;

		if ($attrib)
		{
			unset($users_info[$uid]);

			$users_info[$uid . '_attrib'] = $user_info;
		}

		if ($user_info['avatar_file'])
		{
			$user_info['avatar_file'] = get_avatar_url($user_info['uid'], 'mid');
		}

		return $user_info;
	}


	public function get_user_actions($uid, $limit = 10, $actions = false, $this_uid = 0) {
		$actions = "101,201,105,204,401,406,501,502,503";
		$cache_key = 'user_actions_' . md5 ( $uid . $limit . $actions . $this_uid );

		if ($user_actions = AWS_APP::cache ()->get ( $cache_key )) {
			return $user_actions;
		}

		$associate_action = ACTION_LOG::ADD_QUESTION;

		if (strstr ( $actions, ',' )) {
			$associate_action = explode ( ',', $actions );
				
			array_walk_recursive ( $associate_action, 'intval_string' );
				
			$associate_action = implode ( ',', $associate_action );
		} else if ($actions) {
			$associate_action = intval ( $actions );
		}

		if (! $uid) {
			$where [] = "(associate_type = " . ACTION_LOG::CATEGORY_QUESTION . " AND associate_action IN(" . $this->quote ( $associate_action ) . "))";
		} else {
			$where [] = "(associate_type = " . ACTION_LOG::CATEGORY_QUESTION . " AND uid = " . intval ( $uid ) . " AND associate_action IN(" . $this->quote ( $associate_action ) . "))";
		}

		if ($this_uid == $uid) {
			$show_anonymous = true;
		}

		$action_list = ACTION_LOG::get_action_by_where ( implode ( $where, ' OR ' ), $limit, $show_anonymous );

		// 重组信息
		foreach ( $action_list as $key => $val ) {
			$uids [] = $val ['uid'];
				
			switch ($val ['associate_type']) {
				case ACTION_LOG::CATEGORY_QUESTION :
					if (in_array ( $val ['associate_action'], array (ACTION_LOG::ADD_ARTICLE, ACTION_LOG::ADD_COMMENT_ARTICLE ) )) {
						$article_ids [] = $val ['associate_id'];
					} else if (in_array ( $val ['associate_action'], array (ACTION_LOG::ADD_LIKE_PROJECT, ACTION_LOG::ADD_SUPPORT_PROJECT ) )) {
						$action_list_project_ids [] = $val ['associate_id'];
					} else {
						$question_ids [] = $val ['associate_id'];
					}
						
					if (in_array ( $val ['associate_action'], array (ACTION_LOG::ADD_TOPIC, ACTION_LOG::MOD_TOPIC, ACTION_LOG::MOD_TOPIC_DESCRI, ACTION_LOG::MOD_TOPIC_PIC, ACTION_LOG::DELETE_TOPIC, ACTION_LOG::ADD_TOPIC_FOCUS ) ) and $val ['associate_attached']) {
						$associate_topic_ids [] = $val ['associate_attached'];
					}
					break;
			}
		}

		if ($uids) {
			$action_list_users = $this->model ( 'account' )->get_user_info_by_uids ( $uids, true );
		}

		if ($question_ids) {
			$action_questions_info = $this->model ( 'question' )->get_question_info_by_ids ( $question_ids );
		}

		if ($associate_topic_ids) {
			$associate_topics = $this->model ( 'topic' )->get_topics_by_ids ( $associate_topic_ids );
		}

		if ($article_ids) {
			$action_articles_info = $this->model ( 'article' )->get_article_info_by_ids ( $article_ids );
		}

		if ($action_list_project_ids) {
			$project_infos = $this->model ( 'project' )->get_project_info_by_ids ( $action_list_project_ids );
		}

		foreach ( $action_list as $key => $val ) {
			$action_list [$key] ['user_info'] = $action_list_users [$val ['uid']];
				
			switch ($val ['associate_type']) {
				case ACTION_LOG::CATEGORY_QUESTION :
					switch ($val ['associate_action']) {
						case ACTION_LOG::ADD_ARTICLE :
						case ACTION_LOG::ADD_COMMENT_ARTICLE :
							$article_info = $action_articles_info [$val ['associate_id']];
								
							$action_list [$key] ['title'] = $article_info ['title'];
							$action_list [$key] ['link'] = get_js_url ( '/article/' . $article_info ['id'] );
								
							$action_list [$key] ['article_info'] = $article_info;
								
							// $action_list [$key] ['last_action_str'] = ACTION_LOG::format_action_data ( $val ['associate_action'], $val ['uid'], $action_list_users [$val ['uid']] ['user_name'] );
							$action_list [$key] ['last_action_str'] = ACTION_LOG::format_action_data ( $val ['associate_action'], $val ['uid'], "" );
							break;

						case ACTION_LOG::ADD_LIKE_PROJECT :
						case ACTION_LOG::ADD_SUPPORT_PROJECT :
							$project_info = $project_infos [$val ['associate_id']];
								
							$action_list [$key] ['title'] = $project_info ['title'];
							$action_list [$key] ['link'] = get_js_url ( '/project/' . $project_info ['id'] );
								
							$action_list [$key] ['project_info'] = $project_info;
								
							// $action_list [$key] ['last_action_str'] = ACTION_LOG::format_action_data ( $val ['associate_action'], $val ['uid'], $action_list_users [$val ['uid']] ['user_name'] );
							$action_list [$key] ['last_action_str'] = ACTION_LOG::format_action_data ( $val ['associate_action'], $val ['uid'], "");
							break;

						default :
							$question_info = $action_questions_info [$val ['associate_id']];
								
							$action_list [$key] ['title'] = $question_info ['question_content'];
							$action_list [$key] ['link'] = get_js_url ( '/question/' . $question_info ['question_id'] );
								
							if (in_array ( $val ['associate_action'], array (ACTION_LOG::ADD_TOPIC, ACTION_LOG::MOD_TOPIC, ACTION_LOG::MOD_TOPIC_DESCRI, ACTION_LOG::MOD_TOPIC_PIC, ACTION_LOG::DELETE_TOPIC, ACTION_LOG::ADD_TOPIC_FOCUS ) ) and $val ['associate_attached']) {
								$topic_info = $associate_topics [$val ['associate_attached']];
							} else {
								unset ( $topic_info );
							}
								
							if (in_array ( $val ['associate_action'], array (ACTION_LOG::ADD_QUESTION ) ) and $question_info ['has_attach']) {
								$question_info ['attachs'] = $question_attachs [$question_info ['question_id']];
							}
								
							if ($val ['uid']) {
								// $action_list [$key] ['last_action_str'] = ACTION_LOG::format_action_data ( $val ['associate_action'], $val ['uid'], $action_list_users [$val ['uid']] ['user_name'], $question_info, $topic_info );
								$action_list [$key] ['last_action_str'] = ACTION_LOG::format_action_data ( $val ['associate_action'], $val ['uid'], "", $question_info, $topic_info );

							}
								
							if (in_array ( $val ['associate_action'], array (ACTION_LOG::ANSWER_QUESTION ) ) and $question_info ['answer_count']) {
								if ($answer_list = $this->model ( 'answer' )->get_answer_by_id ( $val ['associate_attached'] )) {
									$action_list [$key] ['answer_info'] = $answer_list;
								}
							}
								
							$action_list [$key] ['question_info'] = $question_info;
								
							break;
					}
						
					break;
			}
		}

		AWS_APP::cache ()->set ( $cache_key, $action_list, get_setting ( 'cache_level_normal' ) );

		return $action_list;
	}

	public function get_all_question_by_puid($publish_uid, $limit = 10) {
		if ($list = $this->fetch_all ( 'question', 'published_uid = ' . intval ( $publish_uid ), 'add_time DESC', null )) {
			/*foreach ($list as $key => $val)
			 {
				$question_ids[] = $val['question_id'];
				}

				$question_infos = $this->get_question_info_by_ids($question_ids);

				foreach ($list as $key => $val)
				{
				$list[$key]['question_info'] = $question_infos[$val['question_id']];
				}*/
				
			return $list;
		}

	}

	/**
	 * 获取单个用户的粉丝列表
	 *
	 * @param  $friend_uid
	 * @param  $limit
	 */
	public function get_user_fans($friend_uid, $limit = 20) {
		if (! $user_fans = $this->fetch_all ( 'user_follow', 'friend_uid = ' . intval ( $friend_uid ), 'add_time DESC', $limit )) {
			return false;
		}

		foreach ( $user_fans as $key => $val ) {
			$fans_uids [$val ['fans_uid']] = $val ['fans_uid'];
		
		}

		return $this->model ( 'account' )->get_user_info_by_uids ( $fans_uids, true );
	}

	/**
	 * 获取单个用户的关注列表(我关注的人)
	 *
	 * @param  $friend_uid
	 * @param  $limit
	 */
	public function get_user_friends($fans_uid, $limit = 20) {
		if (! $user_follow = $this->fetch_all ( 'user_follow', 'fans_uid = ' . intval ( $fans_uid ), 'add_time DESC', $limit )) {
			return false;
		}

		foreach ( $user_follow as $key => $val ) {
			$friend_uids [$val ['friend_uid']] = $val ['friend_uid'];
		}

		return $this->model ( 'account' )->get_user_info_by_uids ( $friend_uids, true );
	}

	public function get_topics_by_ids($topic_ids) {
		if (! $topic_ids or ! is_array ( $topic_ids )) {
			return false;
		}

		array_walk_recursive ( $topic_ids, 'intval_string' );

		$topics = $this->fetch_all ( 'topic', 'topic_id IN(' . implode ( ',', $topic_ids ) . ')' );

		foreach ( $topics as $key => $val ) {
			if (! $val ['url_token']) {
				$val ['url_token'] = urlencode ( $val ['topic_title'] );
			}
				
			$result [$val ['topic_id']] = $val;
		}

		return $result;
	}

	public function get_focus_topic_ids_by_uid($uid) {
		if (! $uid) {
			return false;
		}

		if (! $topic_focus = $this->fetch_all ( 'topic_focus', "uid = " . intval ( $uid ) )) {
			return false;
		}

		foreach ( $topic_focus as $key => $val ) {
			$topic_ids [$val ['topic_id']] = $val ['topic_id'];
		}

		return $topic_ids;

	}

	public function update_question_comments_count($question_id) {
		$count = $this->count ( 'question_comments', 'question_id = ' . intval ( $question_id ) );

		$this->shutdown_update ( 'question', array ('comment_count' => $count ), 'question_id = ' . intval ( $question_id ) );
	}

	public function insert_question_comment($question_id, $uid, $message) {
		if (! $question_info = $this->model ( 'question' )->get_question_info_by_id ( $question_id )) {
			return false;
		}

		//$message = $this->model('question')->parse_at_user($_POST['message'], false, false, true);


		$comment_id = $this->insert ( 'question_comments', array ('uid' => intval ( $uid ), 'question_id' => intval ( $question_id ), 'message' => htmlspecialchars ( $message ), 'time' => time () ) );

		if ($question_info ['published_uid'] != $uid) {
			$this->model ( 'notify' )->send ( $uid, $question_info ['published_uid'], notify_class::TYPE_QUESTION_COMMENT, notify_class::CATEGORY_QUESTION, $question_info ['question_id'], array ('from_uid' => $uid, 'question_id' => $question_info ['question_id'], 'comment_id' => $comment_id ) );
				
			if ($weixin_user = $this->model ( 'openid_weixin_weixin' )->get_user_info_by_uid ( $question_info ['published_uid'] )) {
				$weixin_user_info = $this->model ( 'account' )->get_user_info_by_uid ( $weixin_user ['uid'] );

				if ($weixin_user_info ['weixin_settings'] ['NEW_COMMENT'] != 'N') {
					$this->model ( 'weixin' )->send_text_message ( $weixin_user ['openid'], "您的问题 [" . $question_info ['question_content'] . "] 收到了新的评论:\n\n" . strip_tags ( $message ), $this->model ( 'openid_weixin_weixin' )->redirect_url ( '/m/question/' . $question_info ['question_id'] ) );
				}
			}
		}

		if ($at_users = $this->model ( 'question' )->parse_at_user ( $message, false, true )) {
			foreach ( $at_users as $user_id ) {
				if ($user_id == $question_info ['published_uid']) {
					continue;
				}

				$this->model ( 'notify' )->send ( $uid, $user_id, notify_class::TYPE_COMMENT_AT_ME, notify_class::CATEGORY_QUESTION, $question_info ['question_id'], array ('from_uid' => $uid, 'question_id' => $question_info ['question_id'], 'comment_id' => $comment_id ) );

				if ($weixin_user = $this->model ( 'openid_weixin_weixin' )->get_user_info_by_uid ( $user_id )) {
					$weixin_user_info = $this->model ( 'account' )->get_user_info_by_uid ( $weixin_user ['uid'] );
						
					if ($weixin_user_info ['weixin_settings'] ['AT_ME'] != 'N') {
						$this->model ( 'weixin' )->send_text_message ( $weixin_user ['openid'], "有会员在问题 [" . $question_info ['question_content'] . "] 评论中提到了您", $this->model ( 'openid_weixin_weixin' )->redirect_url ( '/m/question/' . $question_info ['question_id'] ) );
					}
				}
			}
		}

		$this->update_question_comments_count ( $question_id );

		return $comment_id;
	}

	public function update_focus_count($question_id) {
		if (! $question_id) {
			return false;
		}

		return $this->update ( 'question', array ('focus_count' => $this->count ( 'question_focus', 'question_id = ' . intval ( $question_id ) ) ), 'question_id = ' . intval ( $question_id ) );
	}

	public function get_focus_users_by_question($question_id, $limit = 10) {
		if ($uids = $this->query_all ( 'SELECT DISTINCT uid FROM ' . $this->get_table ( 'question_focus' ) . ' WHERE question_id = ' . intval ( $question_id ) . ' ORDER BY focus_id DESC', null )) {
			$users_list = $this->model ( 'account' )->get_user_info_by_uids ( fetch_array_value ( $uids, 'uid' ) );
		}

		return $users_list;
	}

	public function has_focus_question($question_id, $uid) {
		if (! $uid or ! $question_id) {
			return false;
		}

		return $this->fetch_one ( 'question_focus', 'focus_id', 'question_id = ' . intval ( $question_id ) . " AND uid = " . intval ( $uid ) );
	}

	public function delete_focus_question($question_id, $uid) {
		if (! $question_id or ! $uid) {
			return false;
		}

		ACTION_LOG::delete_action_history ( 'associate_type = ' . ACTION_LOG::CATEGORY_QUESTION . ' AND associate_action = ' . ACTION_LOG::ADD_REQUESTION_FOCUS . ' AND uid = ' . intval ( $uid ) . ' AND associate_id = ' . intval ( $question_id ) );

		return $this->delete ( 'question_focus', 'question_id = ' . intval ( $question_id ) . " AND uid = " . intval ( $uid ) );
	}

	public function add_focus_question($question_id, $uid, $anonymous = 0, $save_action = true) {
		if (! $question_id or ! $uid) {
			return false;
		}

		if (! $this->has_focus_question ( $question_id, $uid )) {
			if ($this->insert ( 'question_focus', array ('question_id' => intval ( $question_id ), 'uid' => intval ( $uid ), 'add_time' => time () ) )) {
				$this->update_focus_count ( $question_id );
			}
				
			// 璁板綍鏃ュ織
			if ($save_action) {
				ACTION_LOG::save_action ( $uid, $question_id, ACTION_LOG::CATEGORY_QUESTION, ACTION_LOG::ADD_REQUESTION_FOCUS, '', '', 0, intval ( $anonymous ) );
			}
				
			return 'add';
		} else {
			// 鍑忓皯闂鍏虫敞鏁伴噺
			if ($this->delete_focus_question ( $question_id, $uid )) {
				$this->update_focus_count ( $question_id );
			}
				
			return 'remove';
		}
	}

	public function get_all_search_question($where)
	{
		if ($result = $this->fetch_all ( 'question', $where, "update_time DESC" )) {
			foreach ( $result as $key => $val ) {

				$data [$key] ['question_info'] = $val;

				$data [$key] ['question_publish_user_info'] = $this->fetch_row ( 'users', 'uid = ' . $val ['published_uid'] );

				$data [$key] ['category_info'] = $this->fetch_row ( 'category', 'id = ' . $val ['category_id'] );

				$data [$key] ['question_newest_answer_info'] = $this->fetch_row ( 'answer', 'question_id = ' . $val ['question_id'], "add_time DESC" );
				;

			}
			return $data;

		}
		return $result;
	}

	public function get_question_by_categoryid($category_id, $order_flag) {

		$order = $order_flag." DESC";
		if ($category_id)
		{
			if ($result = $this->fetch_all ( 'question', 'category_id = ' . $category_id, $order )) {
				foreach ( $result as $key => $val ) {
						
					$data [$key] ['question_info'] = $val;
						
					$data [$key] ['question_publish_user_info'] = $this->fetch_row ( 'users', 'uid = ' . $val ['published_uid'] );
						
					$data [$key] ['category_info'] = $this->fetch_row ( 'category', 'id = ' . $val ['category_id'] );
						
					$data [$key] ['question_newest_answer_info'] = $this->fetch_row ( 'answer', 'question_id = ' . $val ['question_id'], "add_time DESC" );
					;
						

				}
				return $data;

			}
		}

	}
	
	public function get_all_question_page($page) {

		// $posts_index = $this->fetch_page('posts_index', implode(' AND ', $where), $order_key, $page, $per_page);
		//if ($result = $this->fetch_all ( 'question', "", "update_time DESC" )) {
		if ($result = $this->fetch_page ( 'question', null, "update_time DESC",$page, 20 )) {	
			foreach ( $result as $key => $val ) {

				$data [$key] ['question_info'] = $val;
				
			
			   // here should parse answer_content for get img url directly by anxiang.xiao 20150827
	
				$data [$key] ['question_info'] ['question_detail'] = $this->model('question')->parse_at_user(FORMAT::parse_attachs2(nl2br(FORMAT::parse_bbcode($data [$key] ['question_info'] ['question_detail']))));
		
				$data [$key] ['question_publish_user_info'] = $this->fetch_row ( 'users', 'uid = ' . $val ['published_uid'] );

				if ($data [$key] ['question_publish_user_info']['avatar_file'])
				{
					$data [$key] ['question_publish_user_info']['avatar_file'] = get_avatar_url($data [$key] ['question_publish_user_info']['uid'], 'min');
				}


				$data [$key] ['category_info'] = $this->fetch_row ( 'category', 'id = ' . $val ['category_id'] );

				$data [$key] ['question_newest_answer_info'] = $this->fetch_row ( 'answer', 'question_id = ' . $val ['question_id'], "add_time DESC" );
				;

				/*
				 *
				 * $data[$key]['question_info'] = $val;

					if ($val['published_uid'] != 0)
					$data[$key]['question_publish_user_info'] =  $this->fetch_row('users','uid = ' .$val['published_uid'] );
					else
					$data[$key]['question_publish_user_info'] =  "{}";

					if ($val['category_id'] != 0)
					$data[$key]['category_info'] =  $this->fetch_row('category','id = ' .$val['category_id'] );
					else
					$data[$key]['category_info'] =  "{}";
					*/
					
			}
			return $data;
				
			// return  $posts_index;
		}

	}
	

	public function get_all_question() {
		// return "helloword23333333";


		if ($result = $this->fetch_all ( 'question', "", "update_time DESC" )) {
			foreach ( $result as $key => $val ) {

				$data [$key] ['question_info'] = $val;
				
			
			   // here should parse answer_content for get img url directly by anxiang.xiao 20150827
	
				$data [$key] ['question_info'] ['question_detail'] = $this->model('question')->parse_at_user(FORMAT::parse_attachs2(nl2br(FORMAT::parse_bbcode($data [$key] ['question_info'] ['question_detail']))));
		
				$data [$key] ['question_publish_user_info'] = $this->fetch_row ( 'users', 'uid = ' . $val ['published_uid'] );

				if ($data [$key] ['question_publish_user_info']['avatar_file'])
				{
					$data [$key] ['question_publish_user_info']['avatar_file'] = get_avatar_url($data [$key] ['question_publish_user_info']['uid'], 'min');
				}


				$data [$key] ['category_info'] = $this->fetch_row ( 'category', 'id = ' . $val ['category_id'] );

				$data [$key] ['question_newest_answer_info'] = $this->fetch_row ( 'answer', 'question_id = ' . $val ['question_id'], "add_time DESC" );
				;

				/*
				 *
				 * $data[$key]['question_info'] = $val;

					if ($val['published_uid'] != 0)
					$data[$key]['question_publish_user_info'] =  $this->fetch_row('users','uid = ' .$val['published_uid'] );
					else
					$data[$key]['question_publish_user_info'] =  "{}";

					if ($val['category_id'] != 0)
					$data[$key]['category_info'] =  $this->fetch_row('category','id = ' .$val['category_id'] );
					else
					$data[$key]['category_info'] =  "{}";
					*/
					
			}
			return $data;
				
			// return  $posts_index;
		}

	}

	public function get_all_comment_by_question_id($question_id) {
		$question_comment = $this->query_all ( "SELECT * FROM " . $this->get_table ( 'question_comments' ) . " WHERE question_id = " . intval ( $question_id ) );

		return $question_comment;
	}


	public function get_answer_by_id($answer_id) {

		//if ($uids = $this->query_all('SELECT DISTINCT uid FROM ' . $this->get_table('question_focus') . ' WHERE question_id = ' . intval($question_id) . ' ORDER BY focus_id DESC', null))

		$answer_data = $this->fetch_row ( 'answer', 'answer_id = ' . $answer_id );
			
		$question_data = $this->fetch_row ( 'question', 'question_id = ' . $answer_data ['question_id'] );
		$user_data = $this->fetch_row ( 'users', 'uid = ' . $answer_data ['uid'] );
		
		if ($user_data['avatar_file'])
		{
			$user_data['avatar_file'] = get_avatar_url($user_data['uid'], 'max');
		}
			
		if ($question_data)
		$answer_data ['question_info'] = $question_data;
		if ($user_data)
		$answer_data ['user_info'] = $user_data;
		
		
		




		return $answer_data;

	}

	public function get_all_answer_by_question_id($question_id) {

		//if ($uids = $this->query_all('SELECT DISTINCT uid FROM ' . $this->get_table('question_focus') . ' WHERE question_id = ' . intval($question_id) . ' ORDER BY focus_id DESC', null))
		$question_answer_list = $this->query_all ( "SELECT * FROM " . $this->get_table ( 'answer' ) . " WHERE question_id = " . intval ( $question_id ) . ' ORDER BY add_time DESC' );



		foreach ( $question_answer_list as $key => $val ) {
				
			$question_data = $this->fetch_row ( 'question', 'question_id = ' . $val ['question_id'] );
			$user_data = $this->fetch_row ( 'users', 'uid = ' . $val ['uid'] );
				
			if ($question_data)
				$question_answer_list [$key] ['question_info'] = $question_data;
			if ($user_data)
			{
				if ($user_data['avatar_file'])
				{
					$user_data['avatar_file'] = get_avatar_url($user_data['uid'], 'mid');
				}
				
				$question_answer_list [$key] ['user_info'] = $user_data;	
				
			}

		}


		return $question_answer_list;

	}

	public function get_all_answer_by_uid($uid) {

		//if ($uids = $this->query_all('SELECT DISTINCT uid FROM ' . $this->get_table('question_focus') . ' WHERE question_id = ' . intval($question_id) . ' ORDER BY focus_id DESC', null))
		$question_answer = $this->query_all ( "SELECT * FROM " . $this->get_table ( 'answer' ) . " WHERE uid = " . intval ( $uid ) . ' ORDER BY add_time DESC' );
		$user_info = $this->model ( 'test' )->get_user_info_by_uid (intval ( $uid ),true );
		
		foreach ( $question_answer as $key => $val ) {
				
			$question_answer [$key] ['question_info'] = $this->fetch_row ( 'question', 'question_id = ' . $val ['question_id'] );
				
			$question_answer [$key] ['add_time'] = date_friendly ( $val ['add_time'], 604800, 'Y-m-d' );
			
			$question_answer [$key] ['user_info'] = $user_info;

		}

		return $question_answer;

	}

}