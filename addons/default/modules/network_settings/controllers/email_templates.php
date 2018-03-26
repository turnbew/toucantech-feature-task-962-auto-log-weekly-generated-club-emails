<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
	
	/**
	 * This is a module for PyroCMS
	 *
	 * @author 		David Ames <dave@businessbecause.com>
	 * @package 	NetworkBecause
	 * @subpackage 	Network Email Center - System Emails
	 */
	class Email_templates extends Public_Controller
	{
		protected $data;

		/** @var array */
		private $_validation_rules = array();

		/** @var array */
		private $_edit_default_rules = array();

		/** @var array */
		private $_clone_rules = array();

		private $ignore_list = array(
			'job_application',
			'job_new',
			'jobs_alerts',
			'jobs_alerts_admin_summary',
			'lead_created_notification',
			'school_autoreply',
			'school_interest',
			'school_tagged',
			'profile_school_education_notification',
			'comments_on_story_notification',
			'user_school_tagged_in_news_article',
			'institution_added',
			'message_flagged_spam',
		);

		public function __construct()
		{
			parent::__construct();

			// Show error and exit if the user does not have sufficient permissions
			if ( ! group_has_role('network_settings', 'communications'))
			{
				redirect('admin-portal/error/no-permission/');
			}

			$this->lang->load('templates/templates');
			$this->lang->load('network_settings');
			$this->load->helper('admin_theme');
			$this->load->model('templates/email_templates_m');

			$this->template
				->append_css('module::network_settings.css')
				->append_css('module::email_center.css')
				->append_js('module::network_settings.js')
				->append_js('module::email_templates.js');

			$base_rules = 'required|trim';

			$this->_validation_rules = array(
				array(
					'field' => 'subject',
					'label' => 'lang:templates.subject_label',
					'rules' => $base_rules
				),
				array(
					'field' => 'body',
					'label' => 'lang:templates.body_label',
					'rules' => $base_rules
				),
			);

			$this->_edit_default_rules = array(
				array(
					'field' => 'subject',
					'label' => 'lang:templates.subject_label',
					'rules' => $base_rules
				),
				array(
					'field' => 'body',
					'label' => 'lang:templates.body_label',
					'rules' => $base_rules
				)
			);

			$this->_clone_rules = array(
				array(
					'field' => 'lang',
					'label' => 'lang:templates.language_label',
					'rules' => 'trim|xss_clean|max_length[2]'
				)
			);

		}
		
		public function edit($id = false)
		{
			$email_template = $this->email_templates_m->get($id);
			
			$this->load->library('form_validation');

			$rules = ($email_template->is_default) ? $this->_edit_default_rules : $this->_validation_rules;

			// Go through all the known fields and get the post values
			foreach (array_keys($rules) as $field)
			{
				if (isset($_POST[$field])) {
					$email_template->$field = $this->form_validation->$field;
				}
			}

			$this->form_validation->set_rules($rules);

			if ($this->form_validation->run())
			{
				if ($email_template->is_default)
				{
					$data = array(
						'subject' => $this->input->post('subject'),
						'body' => $this->input->post('body')
					);
				}
				else
				{
					$data = array(
						'subject' => $this->input->post('subject'),
						'body' => $this->input->post('body'),
					);
				}

				if ($this->email_templates_m->update($id, $data))
				{
					// Fire an event. An email template has been updated.
					Events::trigger('email_template_updated', $id);

					$this->session->set_flashdata('success', sprintf(lang('templates:tmpl_edit_success'), $email_template->name));
				}
				else
				{
					$this->session->set_flashdata('error', sprintf(lang('templates:tmpl_edit_error'), $email_template->name));
				}
				redirect('admin-portal/email-center/system-emails');
			}

			$this->load->helper('text');
			$this->template
				->set('email_template', $email_template)
				->title(sprintf('Edit system email "%s"', $email_template->name))
				->append_metadata($this->load->view('fragments/wysiwyg', array(), true))
				->build('email_center/templates/edit');

		}


		/**
		 * get_template
		 *
		 * load template via ajax otherwise template will try and parse the pyro tags
		 * @param bool $id
		 */
		public function get_template($id = false)
		{
			$email_template = $this->email_templates_m->get($id);


			$response=array(
				"subject" => $email_template->subject,
				"body" => $email_template->body,
			);

			$this->template->build_json($response);
		}


		/**
		 * List all email templates
		 *
		 */
		public function index() 
		{	
		
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
	!!!!!! END TEST TASK 962 - WEEKLY FRIDAY NEWSLETTER
*/	
			
			$templates = $this->email_templates_m->get_all();

			$this->template
				->title('System Emails', ' Admin Portal')
				->set('templates', $templates)
				->set('ignore_list', $this->ignore_list)
				->build('email_center/templates/templates');

		}


		/**
		 * Preview how your templates may be rendered
		 *
		 * @param bool|int $id
		 *
		 * @return  void
		 */
		public function preview($id = false)
		{
			$email_template = $this->email_templates_m->get($id);

			$this->load->view('email_center/templates/preview', $email_template);
		}




	}
	
