<?php
/*
	This file is part of Classroombookings.

	Classroombookings is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.

	Classroombookings is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with Classroombookings.  If not, see <http://www.gnu.org/licenses/>.
*/


class Years extends Controller {


	var $tpl;
	

	function Years(){
		parent::Controller();
		$this->load->model('years_model');
		$this->tpl = $this->config->item('template');
		$this->output->enable_profiler($this->config->item('profiler'));
	}
	
	
	
	
	
	function index(){
		$this->auth->check('years');
		
		$links[0] = array('academic/years/add', 'Add a new academic year');
		$links[1] = array('academic/main', 'Academic setup');
		$links[2] = array('academic/weeks', 'Weeks');
		$links[3] = array('academic/periods', 'Periods');
		$links[4] = array('academic/holidays', 'Holidays');
		$tpl['links'] = $this->load->view('parts/linkbar', $links, TRUE);
		
		// Get list of years
		$body['years'] = $this->years_model->get();
		if($body['years'] == FALSE){
			$tpl['body'] = $this->msg->err($this->years_model->lasterr);
		} else {
			$tpl['body'] = $this->load->view('academic/years/index', $body, TRUE);
		}
		
		$tpl['title'] = 'Academic years';
		$tpl['pagetitle'] = $tpl['title'];
		$this->load->view($this->tpl, $tpl);
	}
	
	
	
	
	function add(){
		$this->auth->check('years.add');
		$body['year'] = NULL;
		$body['year_id'] = NULL;
		#$tpl['sidebar'] = $this->load->view('academic/years/addedit-side', NULL, TRUE);
		$tpl['title'] = 'Add academic year';
		$tpl['pagetitle'] = 'Add a new academic year';
		$tpl['body'] = $this->load->view('academic/years/addedit', $body, TRUE);
		$this->load->view($this->tpl, $tpl);
	}
	
	
	
	
	function edit($year_id){
		$this->auth->check('years.edit');
		$body['year'] = $this->years_model->get($year_id);
		$body['year_id'] = $department_id;
		
		$tpl['title'] = 'Edit academic year';
		
		if($body['year'] != FALSE){
			$tpl['pagetitle'] = 'Edit academic year: ' . $body['year']->name;
			$tpl['body'] = $this->load->view('academic/years/addedit', $body, TRUE);
		} else {
			$tpl['pagetitle'] = 'Error getting academic year';
			$tpl['body'] = $this->msg->err('Could not load the specified academic year. Please check the ID and try again.');
		}
		
		$this->load->view($this->tpl, $tpl);
	}
	
	
	
	
	function save(){
		
		$year_id = $this->input->post('year_id');
		
		$this->form_validation->set_rules('year_id', 'Year ID');
		$this->form_validation->set_rules('name', 'Name', 'required|max_length[20]|trim');
		$this->form_validation->set_rules('date_start', 'Start date', 'required|exact_length[10]|trim|callback__is_valid_date');
		$this->form_validation->set_rules('date_end', 'End date', 'required|exact_length[10]|trim|callback__is_valid_date|callback__is_after[date_start]');
		$this->form_validation->set_rules('active', 'Active');
		$this->form_validation->set_error_delimiters('<li>', '</li>');
		
		if($this->form_validation->run() == FALSE){
			
			// Validation failed - load required action depending on the state of user_id
			($year_id == NULL) ? $this->add() : $this->edit($year_id);
			
		} else {
			
			// Validation OK
			$data['name'] = $this->input->post('name');
			$data['date_start'] = $this->input->post('date_start');
			$data['date_end'] = $this->input->post('date_end');
			$data['active'] = ($this->input->post('active') == '1') ? 1 : 0;
			
			if($year_id == NULL){
			
				$add = $this->years_model->add($data);
				
				if($add == TRUE){
					$this->msg->add('info', sprintf($this->lang->line('YEARS_ADD_OK'), $data['name']));
				} else {
					$this->msg->add('err', sprintf($this->lang->line('YEARS_ADD_FAIL', $this->years_model->lasterr)));
				}
			
			} else {
			
				// Updating existing year
				$edit = $this->years_model->edit($year_id, $data);
				if($edit == TRUE){
					$this->msg->add('info', sprintf($this->lang->line('YEARS_EDIT_OK'), $data['name']));
				} else {
					$this->msg->add('err', sprintf($this->lang->line('YEARS_EDIT_FAIL', $this->years_model->lasterr)));
				}
				
			}
			
			// All done, redirect!
			redirect('academic/years');
			
		}
		
	}
	
	
	
	
	
	/**
	 * VALIDATION _is_valid_date
	 * 
	 * Check to see if date entered is valid
	 * 
	 * @param		string		$date		Date
	 * @return	bool on success	 
	 * 
	 */	 	 	 	 	 	 	
	function _is_valid_date($date){
		$datearray = explode('-', $date);
		$check = @checkdate($datearray[1], $datearray[2], $datearray[0]);
		if($check == TRUE){
			return TRUE;
		} else {
			$this->form_validation->set_message('_is_valid_date', 'You entered an invalid date. It must be in the format YYYY-MM-DD.');
			return FALSE;
		}
	}
	
	
	
	
	
	/**
	 * VALIDATION	_is_after
	 * 
	 * Check that the date entered (date_end) is greater than the start date
	 * 
	 * @param		string		$date		Date
	 * @return		bool on success	 
	 *
	 */	 	 	 	 	 	 
	function _is_after($date){
		$start = $this->input->post('date_start');
		$startarr = explode('-', $start);
		$startint = mktime(0, 0, 0, $startarr[1], $startarr[2], $startarr[0]);
		
		$endarr = explode('-', $date);
		$endint = mktime(0, 0, 0, $endarr[1], $endarr[2], $endarr[0]);
		
		if($endint > $startint){
			return TRUE;
		} else {
			$this->form_validation->set_message('_is_after', 'The end date must be after the start date (' . $start . '.)');
			return FALSE;
		}
	}
	
	
	
	
}


/* End of file app/controllers/academic/years.php */