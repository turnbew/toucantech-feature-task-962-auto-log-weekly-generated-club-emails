TASK'S DATE: 17.11.2017 - FINISHED: 21.11.2017 (MEDIUM)  

TASK SHORT DESCRIPTION: 962 (Auto log the weekly auto generated clubs notification emails in the activity tracker with some statistics on their results (e.g. opens/ clicks/ bounces/ fails etc.) and and tag all recipients.)

GITHUB REPOSITORY CODE: feature/task-962-auto-log-weekly-generated-club-emails

ORIGINAL WORK: https://github.com/BusinessBecause/network-site/tree/feature/task-962-auto-log-weekly-generated-club-emails

CHANGES

	TO TEST: 
	
		JUST FOR TEST TEMPORARLY
		
		C:\toucantech.dev\network-site\addons\default\modules\network_settings\controllers\email_templates.php
		
			//Inside function index
			
				require_once __DIR__ . '/../../clubs/events.php';					
				$events_obj = new Events_clubs();					
				$events_obj->send_newsletter();
				echo "Logging Weekly newsletters test is done. Don't forget, "; 
				echo "you need to change commit, news ...etc, because changes activate sending newsletter.";
		
		\network-site\addons\default\modules\network_settings\controllers\email_center.php
			
			PUT the next code inside function index (but this code can be put anywhere almost - just check the path to the events.php file)
			
				\network-site\addons\default\modules\network_settings\controllers\email_center.php
				
						require_once __DIR__ . '/../../clubs/events.php';					
						$events_obj = new Events_clubs();					
						$events_obj->send_newsletter();
						
					
		\network-site\system\cms\libraries\Events.php
			
			CHANGE the code inside function trigger
			
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

	IN FILES: 

		\network-site\addons\default\modules\clubs\events.php
		
			ADDED CODE: inside function send_newsletter
			
				//$this->ci->load->helper('our_timezone_helper');
				$this->ci->load->model('templates/email_templates_m');
				
				......
				
				
				//Log weekly newsletters by clubs in 3 steps
				//1. step: save the weekly newsletter to the newsletters_newsletters table
				$timestamp = time();
				$datetime = date("Y-m-d H:i:s", $timestamp);
				$hit = $this->ci->email_templates_m->get_templates('club_newsletter');
				$html = $this->ci->parser->parse_string($hit['en']->body, $data, true);
				$newsletter_data = array(
					'created' => $datetime,
					'created_by' => 1, 
					'ordering_count' => 1,
					'str_id' => rand_string(10),
					'newsletter_status' => 4,
					'send_to' => "*" . $club_members . "*",
					'step' => 'results',
					'name' => 'Weekly ' . $club_name . ' newsletter',
					'message_subject' => $club_name .  ' weekly newsletter', 
					'from_name' => Settings::get('institution_name'),
					'from_email' => Settings::get('contact_email'),
					'html' => $html,
					'track_opens' => 1,
					'track_clicks' => 1,
					'send_on' => $datetime,	
					'send_on_timezone' => default_timezone,
					'confirmation_email' => Settings::get('contact_email')
				);
				$this->ci->db->insert('newsletters_newsletters', $newsletter_data);
				$newsletter_id = $this->ci->db->insert_id();
				
				//2. step save the action into the activity_tracker table
				$activity_data = array(
					'updated' => $timestamp, 
					'date' => $timestamp, 
					'type' => '0', 
					'name' => 'Weekly newsletter<br>' . $club_name . '<br>' . $datetime, 
					'assigned_to' => 1, 
					'completed' => 1			
				);			
				$this->ci->db->insert('activity_tracker', $activity_data);
				$activity_id = $this->ci->db->insert_id();
				
				//3. step: link the activity to the newsletter (for more statistics) - activity_tracker_link table
				$tracker_link_data = array(
					'activity_id' => $activity_id,
					'email_id' => $newsletter_id, 
					'created_on' => $timestamp
				);
				$this->ci->db->insert('activity_tracker_link', $tracker_link_data);
		
			DELETED CODE: 
			
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
		
