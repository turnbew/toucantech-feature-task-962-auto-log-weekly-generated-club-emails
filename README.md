TASK'S DATE: 17.11.2017 - FINISHED: 18.01.2017

TASK'S LEVEL: MEDIUM - hard

TASK SHORT DESCRIPTION: 962 (Auto log the weekly auto generated clubs notification emails in the activity tracker with some statistics on their results (e.g. opens/ clicks/ bounces/ fails etc.) and and tag all recipients.)

GITHUB REPOSITORY CODE: feature/task-962-auto-log-weekly-generated-club-emails

ORIGINAL WORK: https://github.com/BusinessBecause/network-site/tree/feature/task-962-auto-log-weekly-generated-club-emails

	
	To call cron file: http://test14.toucantech.com/cron/****** 

	set cron: crontab -e on testserver
	
	HELPER - to set back changes in database
	
	Delete records from all of the affected tables

	DELETE FROM default_newsletters_lists WHERE id > 260;
	DELETE FROM default_newsletters_newsletters WHERE id > 260;
	DELETE FROM default_newsletters_newsletters_lists WHERE lists_id > 260;
	DELETE FROM default_newsletters_queue WHERE list_id > 260;
	DELETE FROM default_newsletters_subscribers_lists WHERE lists_id > 260;
	DELETE FROM default_activity_tracker WHERE id > 310;
	DELETE FROM default_activity_tracker_user_association WHERE activity_id > 310;


CHANGES:

	//-------------------- FOR TEST
		
			\network-site\addons\default\modules\network_settings\controllers\email_templates.php
		
			//Inside function index
				
					/*
						!!!!!! TEST TASK 962 - WEEKLY FRIDAY NEWSLETTER
					*/			
					require_once __DIR__ . '/../../clubs/events.php';					
					$events_obj = new Events_clubs();					
					$events_obj->send_newsletter();
					echo "Logging Weekly newsletters test is done. Don't forget, "; 
					echo "you need to change commit, news ...etc, because changes activate sending newsletter.";
					echo "<br>";
					echo "<STRONG>IMPORTANT!!!!</STRONG> BEFORE MERGE, WE NEED TO TAKE OUT SOME RESTRICTION!";
					
						
					/*
						CALL URL: base_uri/admin-portal/email-center/system-emails
						
						!!!!!! END TEST TASK 962 - WEEKLY FRIDAY NEWSLETTER
					*/	
	
	
		\network-site\system\cms\libraries\Events.php .... //It is not necessary - optional!!!
			
			CHANGED CODE: 
				
				//inside function trigger
			
				FROM: 
				
					if (is_callable($listener))
					{
						$calls[] = call_user_func($listener, $data);
					}
					
				TO:
				
					if (is_callable($listener))
					{
						if ($event == 'email') {
							$calls[] = call_user_func($listener, $data);
						}
					}
					
	//----------------------------- END TEST
	
	

	IN FILES: 

		\network-site\addons\default\modules\clubs\events.php
		
			CHANGED function send_newsletter
			
				COMMENTED OUT: 
				
				/*
					This is needed when timezone branch will be merged 
					
					$this->ci->load->helper('our_timezone_helper');
				*/

				
				FROM:

					public function send_newsletter()
					{
						$this->ci->load->model('clubs/club_m');
						$this->ci->load->model('clubs/club_member_m');
						$this->ci->load->model('news/news_m');
						$this->ci->load->model('comments/comment_m');
						$this->ci->load->model('tags/tags_m');
						$this->ci->load->model('bbusers/bbuser_m');
						$this->ci->load->model('bbusers/profiles_work_m');
						$this->ci->load->model('bbusers/profiles_education_m');
						$this->ci->load->helper('url_helper');
						$date_limit = new DateTime();
						$date_limit->sub(new DateInterval('P7D'));
						$clubs = $this->ci->club_m->get_list();
						foreach ($clubs as $club) {
							$new_data = false;
							$news_style = "";
							$hide_announcements = "";
							$club_name = $club->title;
							$base_url = base_url();
							$club_link = "{$base_url}clubs/view/{$club->slug}";
							$club_members = count($this->ci->club_member_m->get_members($club->id));
							$admins = count($this->ci->tags_m->get_admins_for_club($club->id));
							$club_admins = $admins !== 1 ?
								"Administrators: {$admins}" : "Administrator: {$admins}";
							$articles = count($this->ci->news_m->get_posts_for_club($club->id));
							$club_articles = $articles === 1 ? "Article: 1" : "Articles: {$articles}";
							$club_image = isset($club->image) ? "<img src='{$base_url}uploads/default/clubs/logos/{$club->image}' style='width: 200px;' />'" : "";
							$club_news = $this->ci->news_m->get_recent_clubs_news(1, $club->id);
							if (count($club_news) === 0) $news_style = "display: none;";
							else {
								$news_date = new DateTime();
								$news_date->setTimestamp($club_news[0]->created_on);
								if ($news_date > $date_limit) {
									$new_data = true;
									$news_style = "width: 50%;";
									$news_link = "{$base_url}news/" . $club_news[0]->category->slug . "/" . $club_news[0]->id . "/" . $club_news[0]->slug;
									$club_news_title = "<a href='{$news_link}'>" . $club_news[0]->title . "</a>";
									$cut_position = strpos($club_news[0]->intro, " ", 100);
									if ($cut_position !== false)
										$club_news_body = substr($club_news[0]->intro, 0, $cut_position) . "...<a href='{$base_url}{$news_link}'>more</a>";
									else $club_news_body = $club_news[0]->intro;
									$news_author = "<a href='{$base_url}profile/{$club_news[0]->author->username}/'>{$club_news[0]->author->display_name}</a>";
									$news_image = "{$base_url}uploads/default/news/images/{$club_news[0]->image}";
								} else $news_style = "display: none;";
							}
							$comments = $this->ci->comment_m->get_recent_club_announcements($club->title);
							$club_announcements = array();
							$announcement_link = "{$base_url}clubs/announcements/{$club->slug}";
							$i = 1;
							$announcements = false;
							foreach ($comments as $comment) {
								unset($cut_position);
								$cut_position =  strlen($comment->comment)>=100 ? strpos($comment->comment, " ", 100) : false;
								$user = $this->ci->bbuser_m->get($comment->user_id);
								$club_announcements["image{$i}"] = "{$base_url}uploads/default/users/images/{$user->image}";
								if ($cut_position !== false)
									$club_announcements["message{$i}"] = substr($comment->comment, 0, $cut_position) .
										"...<a href='{$announcement_link}'>more</a>";
								else $club_announcements["message{$i}"] = $comment->comment;
								$club_announcements["posted_by{$i}"] = "<a href='{$base_url}profile/{$comment->username}'>" .
									$comment->display_name . "</a>";
								$announcement_date = new DateTime();
								$announcement_date->setTimestamp($comment->created_on);
								$announcements = $announcements || ($announcement_date > $date_limit);
								$i++;
							}
							if (count($comments) === 1) $club_announcements["hide_second"] = "display: none;";
							if ($announcements === false) $hide_announcements = "display: none;";
							$new_data = $new_data || $announcements;
							$recent_members = $this->ci->club_member_m->get_members($club->id);
							$new_members = array();
							$i = 1;
							foreach ($recent_members as $member) {
								if (isset($member->image)) {
									$new_members["display_name{$i}"] = "<a href='{$base_url}profile/{$member->username}'>{$member->display_name}</a>";
									$new_members["details{$i}"] = ucfirst($member->location_city);
									$edu = $this->ci->profiles_education_m->get_most_recent($member->user_id);
									$work = $this->ci->profiles_work_m->get_most_recent($member->user_id);
									if (isset($work))
										$new_members["details{$i}"] .= "<br/>" . $work->fullname . " " . $work->position;
									else if (isset($edu))
										$new_members["details{$i}"] .= "<br/>{$edu->fullname} {$edu->subject} ({$edu->level})";
									$new_members["image{$i}"] = isset($member->image) ?
										"{$base_url}uploads/default/users/images/{$member->image}" : "{$base_url}/addons/default/themes/businessbecause/img/users_default_thumb.png";
									$join_date = new DateTime();
									$join_date->setTimestamp($member->date_created);
									$i++;
								}
								if ($i === 4) break;
							}
							$hide_last_member = "";
							$hide_middle_member = "";
							if ($i === 3)
								$hide_last_member = "display: none;";
							if ($i === 2) {
								$hide_middle_member = "display: none;";
								$hide_last_member = "display: none;";
							}
							$members = $this->ci->club_member_m->get_members($club->id);
							if ($new_data)
								foreach ($members as $member) {
									$name = $member->display_name;
									Events::trigger('email', array('slug' => 'club_newsletter',
										'to' => $member->email,
										'email_banner' => $this->get_school_banner(),
										'club_link' => $club_link,
										'club_name' => $club_name,
										'display_name' => $name,
										'community_name' => $this->ci->settings->site_name,
										'news_link' => $club_news_title,
										'news_summary' => $club_news_body,
										'news_author' => $news_author,
										'club_members' => $club_members,
										'club_administrator' => $club_admins,
										'club_articles' => $club_articles,
										'announcement1_summary' => $club_announcements["message1"],
										'announcement2_summary' => $club_announcements["message2"],
										'announcement1_author' => $club_announcements["posted_by1"],
										'announcement2_author' => $club_announcements["posted_by2"],
										'member1_display_name' => $new_members["display_name1"],
										'member1_location' => $new_members["details1"],
										'member2_display_name' => $new_members["display_name2"],
										'member2_location' => $new_members["details2"],
										'member3_display_name' => $new_members["display_name3"],
										'member3_location' => $new_members["details3"],
										'club_image' => $club_image,
										'news_image' => $news_image,
										'member1_image' => $new_members["image1"],
										'member2_image' => $new_members["image2"],
										'member3_image' => $new_members["image3"],
										'announcement1_image' => $club_announcements["image1"],
										'announcement2_image' => $club_announcements["image2"],
										'hide_announcements' => $hide_announcements,
										'news_style' => $news_style
									));
								}
							if ($new_data)
								Events::trigger('email', array('slug' => 'club_newsletter',
									'to' => 'andrei@pelicanconnect.com',
									'email_banner' => $this->get_school_banner(),
									'club_link' => $club_link,
									'club_name' => $club_name,
									'display_name' => "Email Previewer",
									'community_name' => $this->ci->settings->site_name,
									'news_link' => $club_news_title,
									'news_summary' => $club_news_body,
									'news_author' => $news_author,
									'club_members' => $club_members,
									'club_administrator' => $club_admins,
									'club_articles' => $club_articles,
									'announcement1_summary' => $club_announcements["message1"],
									'announcement2_summary' => $club_announcements["message2"],
									'announcement1_author' => $club_announcements["posted_by1"],
									'announcement2_author' => $club_announcements["posted_by2"],
									'member1_display_name' => $new_members["display_name1"],
									'member1_location' => $new_members["details1"],
									'member2_display_name' => $new_members["display_name2"],
									'member2_location' => $new_members["details2"],
									'member3_display_name' => $new_members["display_name3"],
									'member3_location' => $new_members["details3"],
									'club_image' => $club_image,
									'news_image' => $news_image,
									'member1_image' => $new_members["image1"],
									'member2_image' => $new_members["image2"],
									'member3_image' => $new_members["image3"],
									'announcement1_image' => $club_announcements["image1"],
									'announcement2_image' => $club_announcements["image2"],
									'hide_announcements' => $hide_announcements,
									'news_style' => $news_style,
									'hide_second_announcement' => $club_announcements["hide_second"],
									'hide_last_member' => $hide_last_member,
									'hide_middle_member' => $hide_middle_member
								));
						}
					} //END function send_newsletter (this is the original)					
					
					

				TO: 

					public function send_newsletter()
					{
						$this->ci->load->model('clubs/club_m');
						$this->ci->load->model('clubs/club_member_m');
						$this->ci->load->model('news/news_m');
						$this->ci->load->model('comments/comment_m');
						$this->ci->load->model('tags/tags_m');
						$this->ci->load->model('bbusers/bbuser_m');
						$this->ci->load->model('bbusers/profiles_work_m');
						$this->ci->load->model('bbusers/profiles_education_m');
						$this->ci->load->helper('url_helper');
						$this->ci->load->model('templates/email_templates_m');
						
						$this->tracking_load_models();
						
						$date_limit = new DateTime();
						$date_limit->sub(new DateInterval('P7D'));
						$clubs = $this->ci->club_m->get_list();
						foreach ($clubs as $club) 
						{
							$new_data = false;
							$news_style = "";
							$hide_announcements = "";
							$club_name = $club->title;
							$base_url = base_url();
							$club_link = "{$base_url}clubs/view/{$club->slug}";
							$admins = count($this->ci->tags_m->get_admins_for_club($club->id));
							$club_admins = $admins !== 1 ?
								"Administrators: {$admins}" : "Administrator: {$admins}";
							$articles = count($this->ci->news_m->get_posts_for_club($club->id));
							$club_articles = $articles === 1 ? "Article: 1" : "Articles: {$articles}";
							$club_image = isset($club->image) ? "<img src='{$base_url}uploads/default/clubs/logos/{$club->image}' style='width: 200px;' />'" : "";
							$club_news = $this->ci->news_m->get_recent_clubs_news(1, $club->id);
							
							$new_members = array();
							$club_announcements = array();
							
				/* TEST data - Begin  
								$club_news_title = "";
								$club_news_body = "";
								$news_author = "";
								$club_members = "";
								$club_admins = "";
								$club_articles = "";
								$club_announcements["message1"] = "";
								$club_announcements["message2"] = "";
								$club_announcements["posted_by1"] = "";
								$club_announcements["posted_by2"] = "";
								$new_members["display_name1"] = "";
								$new_members["details1"] = "";
								$new_members["display_name2"] = "";
								$new_members["details2"] = "";
								$new_members["display_name3"] = "";
								$new_members["details3"] = "";
								$club_image = "";
								$news_image = "";
								$new_members["image1"] = "";
								$new_members["image2"] = "";
								$new_members["image3"] = "";
								$club_announcements["image1"] = "";
								$club_announcements["image2"] = "";
								$hide_announcements = "";
								$news_style = "";					
				TEST data - End */
					
							if (count($club_news) === 0) {
								$news_style = "display: none;";
							}
							else {
								$news_date = new DateTime();
								$news_date->setTimestamp($club_news[0]->created_on);
								if ($news_date > $date_limit) {
									$new_data = true;
									$news_style = "width: 50%;";
									$news_link = "{$base_url}news/" . $club_news[0]->category->slug . "/" . $club_news[0]->id . "/" . $club_news[0]->slug;
									$club_news_title = "<a href='{$news_link}'>" . $club_news[0]->title . "</a>";
									$cut_position = strpos($club_news[0]->intro, " ", 100);
									if ($cut_position !== false)
										$club_news_body = substr($club_news[0]->intro, 0, $cut_position) . "...<a href='{$base_url}{$news_link}'>more</a>";
									else $club_news_body = $club_news[0]->intro;
									$news_author = "<a href='{$base_url}profile/{$club_news[0]->author->username}/'>{$club_news[0]->author->display_name}</a>";
									$news_image = "{$base_url}uploads/default/news/images/{$club_news[0]->image}";
								} else $news_style = "display: none;";
							}
							
							$comments = $this->ci->comment_m->get_recent_club_announcements($club->title);
							$announcement_link = "{$base_url}clubs/announcements/{$club->slug}";
							$i = 1;
							$announcements = false;
							foreach ($comments as $comment) {
								unset($cut_position);
								$cut_position =  strlen($comment->comment)>=100 ? strpos($comment->comment, " ", 100) : false;
								$user = $this->ci->bbuser_m->get($comment->user_id);
								$club_announcements["image{$i}"] = "{$base_url}uploads/default/users/images/{$user->image}";
								if ($cut_position !== false)
									$club_announcements["message{$i}"] = substr($comment->comment, 0, $cut_position) .
										"...<a href='{$announcement_link}'>more</a>";
								else $club_announcements["message{$i}"] = $comment->comment;
								$club_announcements["posted_by{$i}"] = "<a href='{$base_url}profile/{$comment->username}'>" .
									$comment->display_name . "</a>";
								$announcement_date = new DateTime();
								$announcement_date->setTimestamp($comment->created_on);
								$announcements = $announcements || ($announcement_date > $date_limit);
								$i++;
							}
							if (count($comments) === 1) $club_announcements["hide_second"] = "display: none;";
							if ($announcements === false) $hide_announcements = "display: none;";
							$new_data = $new_data || $announcements;
							
							$members = $recent_members = $this->ci->club_member_m->get_members($club->id);
							$club_members = count($members);
							$i = 1;
							foreach ($recent_members as $member) {
								if (isset($member->image)) {
									$new_members["display_name{$i}"] = "<a href='{$base_url}profile/{$member->username}'>{$member->display_name}</a>";
									$new_members["details{$i}"] = ucfirst($member->location_city);
									$edu = $this->ci->profiles_education_m->get_most_recent($member->user_id);
									$work = $this->ci->profiles_work_m->get_most_recent($member->user_id);
									if (isset($work))
										$new_members["details{$i}"] .= "<br/>" . $work->fullname . " " . $work->position;
									else if (isset($edu))
										$new_members["details{$i}"] .= "<br/>{$edu->fullname} {$edu->subject} ({$edu->level})";
									$new_members["image{$i}"] = isset($member->image) ?
										"{$base_url}uploads/default/users/images/{$member->image}" : "{$base_url}/addons/default/themes/businessbecause/img/users_default_thumb.png";
									$join_date = new DateTime();
									$join_date->setTimestamp($member->date_created);
									$i++;
								}
								if ($i === 4) break;
							}
							$hide_last_member = "";
							$hide_middle_member = "";
							if ($i === 3)
								$hide_last_member = "display: none;";
							if ($i === 2) {
								$hide_middle_member = "display: none;";
								$hide_last_member = "display: none;";
							}


				/* TEST 	if ($new_data = true)	*/ 
				/* ORIG */	if ($new_data === true) 
							{		
								$list_id = $this->tracking_create_mailing_list('Weekly newsletter ' . $club_name);
								
								$activity_id = $this->tracking_create_activity('Weekly newsletter<br>' . $club_name . '<br>' . date('Y-m-d H:i:s'));

								$queue = array();
								
								$data = array(
										'slug' => 'club_newsletter',
										'email_banner' => $this->get_school_banner(),
										'club_link' => $club_link,
										'club_name' => $club_name,
										'community_name' => $this->ci->settings->site_name,
										'news_link' => $club_news_title,
										'news_summary' => $club_news_body,
										'news_author' => $news_author,
										'club_members' => $club_members,
										'club_administrator' => $club_admins,
										'club_articles' => $club_articles,
										'announcement1_summary' => $club_announcements["message1"],
										'announcement2_summary' => $club_announcements["message2"],
										'announcement1_author' => $club_announcements["posted_by1"],
										'announcement2_author' => $club_announcements["posted_by2"],
										'member1_display_name' => $new_members["display_name1"],
										'member1_location' => $new_members["details1"],
										'member2_display_name' => $new_members["display_name2"],
										'member2_location' => $new_members["details2"],
										'member3_display_name' => $new_members["display_name3"],
										'member3_location' => $new_members["details3"],
										'club_image' => $club_image,
										'news_image' => $news_image,
										'member1_image' => $new_members["image1"],
										'member2_image' => $new_members["image2"],
										'member3_image' => $new_members["image3"],
										'announcement1_image' => $club_announcements["image1"],
										'announcement2_image' => $club_announcements["image2"],
										'hide_announcements' => $hide_announcements,
										'news_style' => $news_style
									);				
								
								$counter = 1;
								
								foreach ($members as $member) 
								{
				/* TEST         	$data['to'] = 'lajos@toucantech.com'; */  
				/* ORIG */			$data['to'] = $member->email;  	
									$data['display_name'] = $member->display_name;

									$subscriber_id = $this->tracking_create_or_update_subscriber($member);
									
									if ($counter == 1) 
									{				
										$hit = $this->ci->email_templates_m->get_templates('club_newsletter');
										$html = $this->ci->parser->parse_string($hit['en']->body, $data, true);
										
										$this->tracking_process_links($html);
										
										$newsletter_id = $this->tracking_create_newsletter('Weekly newsletter ' . $club_name, $html, $list_id);
										
										$this->tracking_assign_newsletter_with_activity($newsletter_id, $activity_id);
										
										$this->tracking_assign_newsletter_with_list($newsletter_id, $list_id);
									}
										
									if ($subscriber_id == 25) 
									{
										$this->tracking_assign_user_with_activity($member->user_id, $activity_id);
										
										$this->tracking_assign_subscriber_with_list($subscriber_id, $list_id, $newsletter_id, $queue);
										
										//Switch these trigger functions off - will set up everything in a queue and the cron will do the rest
				/* TEST 				if ($counter == 1) {Events::trigger('email', $data);}	*/	
				/* ORIG 				Events::trigger('email', $data); */	
									}
										
									$counter++;	
									
								} // END foreach
								
								if ( ! empty($queue) ) 
								{
									batch_insert_update('newsletters_queue', $queue);
									
									$this->tracking_scheduling_email($newsletter_id);
								}
								
							}//END if ($new_data === true) 	
						}//END foreach ($clubs as $club) 
					}//END function send_newsletter()
						
		
			ADDED NEW FUNCTIONS: 
			
				public function tracking_load_models()
				{
					$this->ci->load->model('newsletters/list_m');
					$this->ci->load->model('newsletters/subscriber_m');
					$this->ci->load->model('newsletters/subscriber_lists_m');
					$this->ci->load->model('newsletters/newsletter_lists_m');
					$this->ci->load->model('newsletters/newsletter_m');
					$this->ci->load->model('network_settings/activity_tracker_m');
					$this->ci->load->helper('newsletters/newsletters');
					$this->ci->load->library('newsletters/phpQuery');
				}
				
				
				
				public function tracking_create_mailing_list($list_name)
				{
					$date_str = date("Y-m-d H:i:s");
					
					$data = array(
						'created'           => $date_str,
						'updated'           => null,
						'created_by'        => 1,
						'ordering_count'    => 1,
						'str_id'            => rand_string(10),
						'parent_id'         => '0',
						'name'              => $list_name . " - " . $date_str,
						'description'       => $list_name . " - " . $date_str,
					);
					
					return $this->ci->list_m->insert($data);
				}	
				
				
				
				public function tracking_create_newsletter($subject, $html_body, $list_id)
				{
					$datetime = date("Y-m-d H:i:s");
					
					$newsletter_html = '<!DOCTYPE PUBLIC PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
										<html>
											<head>
												<meta content="text/html; charset=utf-8" http-equiv="Content-Type" />
												<title>Event newsletter</title>
											</head>
											<body>' 
												. $html_body . 
									   '	</body>
										</html>';
					
					$newsletter_data = array(
						'created' => $datetime,
						'created_by' => 1, 
						'ordering_count' => 1,
						'str_id' => rand_string(10),
						'newsletter_status' => 4,
						'send_to' => "*" . $list_id . "*",
						'step' => 'results',
						'name' => $subject,
						'message_subject' => $subject,
						'from_name' => Settings::get('institution_name'),
						'from_email' => Settings::get('contact_email'),
						'confirmation_email' => Settings::get('contact_email'),
						'html' => $newsletter_html,
						'content' => $html_body,
						'track_opens' => 1,
						'track_clicks' => 1,
						'destination_lists' => $list_id,
						'send_on' => $datetime,						
					);
					
					return $this->ci->newsletter_m->insert($newsletter_data);
				}	

				
				
				public function tracking_create_or_update_subscriber($member)
				{
					$subscriber = $this->ci->subscriber_m->db->where('email', $member->email)->get('newsletters_subscribers')->row();
								
					if ($subscriber) 
					{
						$subscriber_id = $subscriber->id;

						// We'll update only if we have attendee with record
						// We don't want annonymous guest data to mess with data from records
						if ($subscriber_id)
						{
							$subscriber_data = array(
								'updated'       => date('Y-m-d H:i:s'),
								'created_by'    => 1,
								'first_name'    => $member->first_name,
								'last_name'     => $member->last_name,
							);

							$this->ci->subscriber_m->update($subscriber_id, $subscriber_data);
						}
					} 
					else
					{
						$subscriber_data = array(
							'created'           => date('Y-m-d H:i:s'),
							'updated'           => null,
							'created_by'        => 1, // If attendee don't have a record, we'll go with null
							'ordering_count'    => 1,
							'str_id'            => rand_string(10),
							'subscriber_status' => 1,
							'active'            => 1,
							'email'             => $member->email,
							'first_name'        => $member->first_name,
							'last_name'         => $member->last_name,
						);

						$subscriber_id = $this->ci->subscriber_m->insert($subscriber_data);
					}
					
					return $subscriber_id;
				}//END function tracking_create_or_update_subscriber
				
				
				
				public function tracking_create_activity($activity_name)
				{
					$activity_data = array(
						'updated' => time(), 
						'date' => time(), 
						'type' => '0', 
						'name' => $activity_name, 
						'assigned_to' => 1, 
						'completed' => 1			
					);		
					
					return $this->ci->activity_tracker_m->insert($activity_data);
				}



				public function tracking_assign_user_with_activity($user_id, $activity_id) 
				{
					$this->ci->activity_tracker_m->assign_user_to_activity($activity_id, $user_id);
				}	



				public function tracking_assign_subscriber_with_list($subscriber_id, $list_id, $newsletter_id, &$subs_queue) 
				{
					if (! $this->ci->db->where('subscribers_id', $subscriber_id)->where('lists_id', $list_id)->get('newsletters_subscribers_lists')->row())
					{
						$assign_data = array(
							'subscribers_id'    => $subscriber_id,
							'lists_id'          => $list_id,
							'disabled'          => 0,
						);
						
						$this->ci->subscriber_lists_m->insert($assign_data);
					}

					$subs_queue[] = array(
						'created'           => date("Y-m-d H:i:s"),
						'created_by'        => 1,
						'ordering_count'    => 0,
						'str_id'            => rand_string(10),
						'subscriber_id'     => $subscriber_id,
						'list_id'           => $list_id,
						'newsletter_id'		=> $newsletter_id,			
						'queue_status'      => 0,
						'send_attempts'     => 0,
						'last_send_attempt' => NULL,
					);
				}	
				

				
				public function tracking_assign_newsletter_with_activity($newsletter_id, $activity_id) 
				{
					$this->ci->activity_tracker_m->set_logged_by($activity_id, 1);
					$this->ci->activity_tracker_m->set_email_link($activity_id, array($newsletter_id));
				}

				
				
				public function tracking_assign_newsletter_with_list($newsletter_id, $list_id) 
				{
					// Add mailing list to this newsletter
					$assign_data = array(
						'newsletters_id'    => $newsletter_id,
						'lists_id'          => $list_id,
						'disabled'          => 0,
					);
						
					//$this->newsletter_lists_m->insert($assign_data); //empty class
					
					$this->ci->db->insert($this->ci->db->dbprefix('newsletters_newsletters_lists'), $assign_data); 
				}	
				
				
				
				public function tracking_scheduling_email($newsletter_id) 
				{
					$schedule_data = array(
						'newsletter_status' => 1,
						'send_on' 			=> date('Y-m-d', time()),
						'send_later' 		=> 0,
					);
						
					$this->ci->db->update($this->ci->db->dbprefix('newsletters_newsletters'), $schedule_data, array('id' => $newsletter_id));
				}
				


				public function tracking_process_links(&$html) 
				{
					// Make the DOM doc
					$html = phpQuery::newDocumentXHTML($html, 'utf-8');

					// Loop over links
					foreach( pq('a') as $k=>$a )
					{

						//don't make any changes to mailto links
						if(strpos( pq($a)->attr('href'), 'mailto' ) !== false) {
							continue;
						}

						// Leave mailto and newsletter links alone
						if(strpos( pq($a)->attr('href'), '/newsletters/' ) === false and strpos( pq($a)->attr('href'), '{{' ) === false )
						{

							// Encode and set the href
							pq($a)->attr('href', site_url('newsletters/link/---identification_uri---/'.$k.'/'.protect_string($this->ci->encrypt->encode(pq($a)->attr('href')))));
						}
						else
						{

							// It's to the newsletters module so just add the link ID
							pq($a)->attr('href', pq($a)->attr('href').'/'.$k);
						}

						// Add data-link_id
						pq($a)->attr('data-link_id', $k);
					}	
				}	

		
		
