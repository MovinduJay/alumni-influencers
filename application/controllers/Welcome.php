<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Welcome extends MY_Controller
{
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


