FOR PRIVACY AND CODE PROTECTING REASONS THIS IS A SIMPLIFIED VERSION OF CHANGES AND NEW FEATURES

TASK'S DATE: 17.11.2017 - FINISHED: 18.01.2017

TASK'S LEVEL: MEDIUM - hard

TASK SHORT DESCRIPTION: 962 (Auto log the weekly auto generated clubs notification emails in the activity tracker with some statistics on their results (e.g. opens/ clicks/ bounces/ fails etc.) and and tag all recipients.)
				
GITHUB REPOSITORY CODE: feature/task-962-auto-log-weekly-generated-club-emails


CHANGES:

	IN FILES: 

		events.php
		
			CHANGED function send_newsletter
			
				COMMENTED OUT: 
				
				/*
					This is needed when timezone branch will be merged 
					
					$this->ci->load->helper('our_timezone_helper');
				*/

				
				FROM:

					public function send_newsletter()
					{
							.....................
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
										.................
									));
								}
							if ($new_data)
								Events::trigger('email', array('slug' => 'club_newsletter',
									'to' => 'andrei@pelicanconnect.com',
									........
								));
						}
					} //END function send_newsletter (this is the original)					
					
					

				TO: 

					public function send_newsletter()
					{
						$this->ci->load->model('club_m');
						$this->ci->load->model('club_member_m');
						$this->ci->load->model('news_m');
						$this->ci->load->model('comment_m');
						$this->ci->load->model('tags_m');
						$this->ci->load->model('bbuser_m');
						$this->ci->load->model('profiles_work_m');
						$this->ci->load->model('profiles_education_m');
						$this->ci->load->helper('url_helper');
						$this->ci->load->model('email_templates_m');
						
						$this->tracking_load_models();
						
						$date_limit = new DateTime();
						$date_limit->sub(new DateInterval('P7D'));
						$clubs = $this->ci->club_m->get_list();
						foreach ($clubs as $club) 
						{
							.......................

				/* TEST 	if ($new_data = true)	*/ 
				/* ORIG */	if ($new_data === true) 
							{		
								$list_id = $this->tracking_create_mailing_list('Weekly newsletter ' . $club_name);
								
								$activity_id = $this->tracking_create_activity('Weekly newsletter<br>' . $club_name . '<br>' . date('Y-m-d H:i:s'));

								$queue = array();
								
								..................			
								
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
					$this->ci->load->model('list_m');
					$this->ci->load->model('subscriber_m');
					..............
				}
				
				
				
				public function tracking_create_mailing_list($list_name)
				{
					$date_str = date("Y-m-d H:i:s");
					
					$data = array(
						...............
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
						.............	
					);
					
					return $this->ci->newsletter_m->insert($newsletter_data);
				}	

				
				
				public function tracking_create_or_update_subscriber($member)
				{
					$subscriber = $this->ci->subscriber_m->db->where('email', $member->email)->get('newsletters_subscribers')->row();
								
					if ($subscriber) 
					{
						$subscriber_id = $subscriber->id;

						..............
					} 
					else
					{
						.....................
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

					...............
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

						.................
					}	
				}	

		
		
