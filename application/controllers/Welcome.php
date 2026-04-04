<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Welcome Controller
 *
 * Default landing page for the Alumni Influencers Platform.
 * Shows today's featured alumni and links to register/login.
 */
class Welcome extends MY_Controller
{
	/**
	 * Index Page - Landing page
	 */
	public function index()
	{
		$this->load->model('Bid_model');

		$data = array(
			'title'          => 'Welcome',
			'featured_today' => $this->Bid_model->get_featured_today()
		);

		$this->load->view('layouts/header', $data);
		$this->load->view('welcome_message', $data);
		$this->load->view('layouts/footer');
	}
}
