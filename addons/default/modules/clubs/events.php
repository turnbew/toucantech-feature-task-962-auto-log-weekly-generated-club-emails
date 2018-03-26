<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Events_clubs
{

    protected $ci;

    public function __construct()
    {
        Events::register('profile_view', array($this, 'add_profile_clubs'));
        Events::register('profile_edit', array($this, 'profile_club_form'));
        Events::register('profile_save', array($this, 'save_profile_clubs'));
        Events::register('profile_delete', array($this, 'delete_profile_club'));

        // add a path for our assets
        Asset::add_path('clubs', ADDONPATH . 'modules/clubs/');

        $this->ci = ci();
        Events::register('cron_process_friday', array($this, 'send_newsletter'));
    }

    /**
     * Validation for club members
     */
    private $membership_validation = array(
        array(
            'field' => 'club_id',
            'label' => 'Club',
            'rules' => 'required'
        ),
        array(
            'field' => 'rel_type',
            'label' => 'lang:club_member_role_label',
            'rules' => 'required'
        ),
        array(
            'field' => 'club_join_month',
            'label' => 'lang:club_member_month_joined_label',
            'rules' => 'required'
        ),
        array(
            'field' => 'club_join_year',
            'label' => 'lang:club_member_year_joined_label',
            'rules' => 'required'
        ),
        array(
            'field' => 'club_until_month',
            'label' => 'Month left',
            'rules' => 'required'
        ),
        array(
            'field' => 'club_until_year',
            'label' => 'Year left',
            'rules' => 'required'
        ),
    );

    /**
     * Save profile clubs
     * This handles form validation then hands over to 'create_club_membership' or 'edit_club_membership'
     */
    public function save_profile_clubs($args)
    {
        $part = $args['part'];
        $profile = $args['profile'];
        $user_id = $args['id'];

        if ($part == 'clubs' || $part == 'edit_club') {

            // ensure we have the permissions to do this
            $this->ci->users->may_edit($user_id) or show_404();

            // our default validation
            $validation = $this->membership_validation;

            $this->ci->load->model('clubs/club_member_m');
            $this->ci->lang->load('clubs/clubs');

            // set our validation rules
            $this->ci->form_validation->set_rules($validation);

            if ($this->ci->form_validation->run()) {
                $club_id = $this->ci->input->post('club_id');
                $join_month = $this->ci->input->post('club_join_month');
                $join_year = $this->ci->input->post('club_join_year');
                $until_month = $this->ci->input->post('club_until_month');
                $until_year = $this->ci->input->post('club_until_year');
                $member_type = $this->ci->input->post('rel_type');

                // make the date_from
                $date_from = mktime(0, 0, 0, (int) $join_month, 1, (int) $join_year);

                // make our date_to
                $date_to = mktime(0, 0, 0, (int) $until_month, 1, (int) $until_year);

                $post_data = array(
                    'user_id' => $user_id,
                    'club_id' => $club_id,
                    'member_type' => $member_type,
                    'active' => 1,
                    'date_from' => $date_from,
                    'date_to' => $date_to,
                );

                // our default response
                $response = array(
                    'status' => 'error',
                    'messages' => array('Sorry, we encountered some problems.'),
                );

                if ($club_membership_id = $this->ci->input->post('club_membership_id')) {
                    $response = $this->edit_club_membership($club_membership_id, $post_data);
                } else {
                    $response = $this->create_club_membership($post_data);
                }

                // check if the edit/create was a success
                if ($response['status'] == 'success') {
                    redirect('profile/' . $user_id . '/clubs', 'location');
                } else {
                    $this->ci->template->build_json($response);
                    return true;
                }
            } else {
                $response = array(
                    'status' => 'error',
                    'messages' => $this->ci->form_validation->error_array(),
                );
                $this->ci->template->build_json($response);
            }

            // we've handled this event
            return true;
        }
    }

    /**
     * The user is adding a new club membership
     *
     * @param $post_data array The submitted post data
     * @return $response array Our response, ready to be json encoded
     */
    private function create_club_membership($post_data)
    {
        $response = array(
            'status' => 'error',
            'messages' => array('There was a problem while joining this club'),
        );

        // check if we're a member of this club already
        $is_member = $this->ci->club_member_m->is_member($this->ci->current_user->id, $post_data['club_id']);

        if ($is_member) {
            $response = array(
                'status' => 'error',
                'messages' => array('You are already a member of this club'),
            );
        } else {
            // add date_created information
            $post_data['date_created'] = now();

            if ($this->ci->club_member_m->insert($post_data)) {
                $response = array(
                    'status' => 'success',
                    'messages' => array('Joined the club'),
                );
            }
        }

        return $response;
    }

    /**
     * The user is editing a current club membership
     *
     * @param $id int The club membership id
     * @param $post_data array The submitted post data
     * @return $response array Our response, ready to be json encoded
     */
    private function edit_club_membership($id, $post_data)
    {
        $response = array(
            'status' => 'error',
            'messages' => array('There was a problem while editing this club membership'),
        );

        // get the current membership
        $membership = $this->ci->club_member_m->get($id);

        if ($membership) {

            $update = true;

            // check if the club id has changed
            if ($membership->club_id !== $post_data['club_id']) {

                // check we're not already a member of that club
                $already_member = $this->ci->club_member_m->is_member($this->ci->current_user->id, $post_data['club_id']);

                if ($already_member) {
                    $response['messages'] = array('You are already a member of that club');
                    $update = false;
                }
            }

            if ($update) {
                if ($updated = $this->ci->club_member_m->update($id, $post_data)) {
                    $response = array(
                        'status' => 'success',
                        'messages' => array('Your club membership was updated successfully'),
                    );
                }
            }
        }

        return $response;
    }

    /**
     * Provide an edit form for editing clubs
     */
    public function profile_club_form($args)
    {
        $profile = $args['profile'];
        $part = $args['part'];
        $user_id = $args['id'];
        $club_membership_id = $args['part_id'];

        if ($part == 'clubs' || $part == 'edit_club') {
            // ensure we have the permissions to be here
            $this->ci->users->may_edit($user_id) or show_404();

            $this->ci->load->model('clubs/club_m');
            $this->ci->load->model('clubs/club_member_m');
            $this->ci->load->library('options');
            $this->ci->lang->load('clubs/clubs');

            // data for our form
            $club_form_data = array(
                'id' => false,
                'club_id' => set_value('club_id'),
                'rel_type' => set_value('rel_type') ?: 'president',
                'club_join_month' => set_value('club_join_month'),
                'club_join_year' => set_value('club_join_year'),
                'club_until_month' => set_value('club_until_month'),
                'club_until_year' => set_value('club_until_year'),
            );

            // if we're editing then we can use real information
            if ($part == 'edit_club') {
                $club_membership = $this->ci->club_member_m->get($club_membership_id);

                // map our club to one we can edit
                $club_form_data = array(
                    'id' => $club_membership_id,
                    'club_id' => $club_membership->club_id,
                    'rel_type' => $club_membership->member_type,
                    'club_join_month' => date('n', $club_membership->date_from),
                    'club_join_year' => date('Y', $club_membership->date_from),
                    'club_until_month' => date('n', $club_membership->date_to),
                    'club_until_year' => date('Y', $club_membership->date_to),
                );
            }

            // we can only join some clubs
            $joinable_clubs = $this->ci->club_m->get_joinable_clubs($this->ci->current_user->id);
            $joinable_clubs_dropdown = array();

            if ($joinable_clubs) {
                foreach ($joinable_clubs as $club) {
                    if (!isset($joinable_clubs_dropdown[$club->school_name])) {
                        $joinable_clubs_dropdown[$club->school_name] = array();
                    }
                    $joinable_clubs_dropdown[$club->school_name][$club->id] = $club->fullname;
                }
            }

            $data = array(
                'club_membership' => (object)$club_form_data,
                'member_types' => $this->ci->club_member_m->member_types,
                'clubs' => $joinable_clubs_dropdown,
            );

            // load our edit form
            $form = $this->ci->load->view('clubs/profile/edit', $data, true);

            // pass our template to the edit frame
            $this->ci->template
                ->enable_parser(false)
                ->set_layout('')
                ->set('profile', $profile)
                ->set('part', 'clubs')
                ->set('uploadify_head', $this->ci->image_upload->get_head_new())
                ->set('form', $form)
                ->build('profile/edit_frame.php');

            // we've handled this event
            return true;
        }
    }

    /**
     * Remove a club membership from a users profile
     */
    public function delete_profile_club($args)
    {
        $user_id = $args['user_id'];
        $part = $args['part'];

        if ($part == 'clubs') {
            $deleted = false;

            // our default response
            $response = array(
                'status' => 'error',
                'messages' => array('Sorry, we encountered some problems while removing this club membership.'),
            );

            // try deleting the membership
            if ($id = $this->ci->input->post('club_membership_id')) {
                $this->ci->load->model('clubs/club_member_m');
                $deleted = $this->ci->club_member_m->delete($id);
            }

            // check if the edit/create was a success
            if ($deleted) {
                redirect('profile/' . $user_id . '/clubs', 'location');
            } else {
                $this->ci->template->build_json($response);
                return true;
            }
        }

        return true;
    }

    /**
     * Add clubs information to a users profile
     */
    public function add_profile_clubs($args)
    {
        $this->ci->load->model('clubs/club_m');
        $this->ci->load->model('clubs/club_member_m');

        // get the clubs we're a member of
        $profile = $args['profile'];
        $club_memberships = $this->ci->club_member_m->get_club_memberships($profile['id']);

        // profile clubs view
        $profile_clubs = $this->ci->load->view('clubs/profile/view', array(
            'club_memberships' => $club_memberships,
            'club_member_types' => $this->ci->club_member_m->member_types,
        ), true);

        // add the profile clubs view to the profile page
        $this->ci->template->set('profile_clubs', $profile_clubs);
    }

    /**
     * Sends out a weekly newsletter with information about club activity
     */

    public function get_school_banner(){
        $tt_custom_logo_4 = Settings::get('theme_custom_logo4');//clubs email banner

        if(!empty($tt_custom_logo_4)) {
            return "<img src='" . site_url("/uploads/default/files/" . $tt_custom_logo_4) . "'/>";
        } else {
            $site_name = ci()->theme->options->site;
            return "<img src='" . site_url("/addons/default/themes/toucantechV2/img/sites/{$site_name}/header.png") . "'/>";
        }

    }

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
		/*
			This is needed when timezone branch will be merged 
			
			$this->ci->load->helper('our_timezone_helper');
		*/
		$this->ci->load->model('templates/email_templates_m');
		
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
            $club_members = count($this->ci->club_member_m->get_members($club->id));
            $admins = count($this->ci->tags_m->get_admins_for_club($club->id));
            $club_admins = $admins !== 1 ?
                "Administrators: {$admins}" : "Administrator: {$admins}";
            $articles = count($this->ci->news_m->get_posts_for_club($club->id));
            $club_articles = $articles === 1 ? "Article: 1" : "Articles: {$articles}";
            $club_image = isset($club->image) ? "<img src='{$base_url}uploads/default/clubs/logos/{$club->image}' style='width: 200px;' />'" : "";
            $club_news = $this->ci->news_m->get_recent_clubs_news(1, $club->id);
            
			$new_members = array();
			$club_announcements = array();
			
/* TEST - Begingin  */
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
/* TEST END */
	
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
            
			$recent_members = $this->ci->club_member_m->get_members($club->id);
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
/* TEST */ 	if ($new_data = true)
/* ORIG */	//if ($new_data === true) 
			{		
		
/* TEST */		$counter = 1;
                foreach ($members as $member) 
				{
					/*
						Before sendin do next: 
						
						 // Check if subscriber with given email already exists
						 // You need to load subscriber_m
						$subscriber = $this->subscriber_m->db->where('email', $member->email)->get('newsletters_subscribers')->row();
					
						if ($subscriber) 
						{
							// Get id
							$subscriber_id = $subscriber->id;

							// We'll update only if we have attendee with record
							// We don't want annonymous guest data to mess with data from records
							if ($user_id)
							{
								// Update with current details
								$subscriber = array(
									'updated'       => $date_str,
									'created_by'    => $user_id,
									'first_name'    => $member->first_name,
									'last_name'     => $member->last_name,
								);

								$this->subscriber_m->update($subscriber_id, $subscriber);
							}
						} 
						else
						{
							$subscriber = array(
								'created'           => $date_str,
								'updated'           => null,
								// If attendee don't have a record, we'll go with null
								'created_by'        => $user_id ? $user_id : null,
								'ordering_count'    => 1,
								'str_id'            => rand_string(10),
								'subscriber_status' => 1,
								'active'            => 1,
								'email'             => $email,
								'first_name'        => $first_name,
								'last_name'         => $last_name,
							);

							$subscriber_id = $this->subscriber_m->insert($subscriber);
						}
					
					*/
					
					print_r ($member);
					exit;
					
					$name = $member->display_name;
					$data = array('slug' => 'club_newsletter',
/* TEST */              'to' => 'lajos@toucantech.com', 
/* ORIG */              //'to' => $member->email,
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
                    );
/* TEST */			if ($counter == 1) {Events::trigger('email', $data); $counter ++;}		
/* ORIG */			//Events::trigger('email', $data);
				}
				
				
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
					/*
						This is needed when timezone branch will be merged
						
						'send_on_timezone' => default_timezone,
					*/
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
			}	
		}
    }
	
	
	
}//End class Events_clubs

/* End of file events.php */