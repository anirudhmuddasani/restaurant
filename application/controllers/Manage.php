<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Manage extends Cpanel_Controller
{
	protected static $upload_config 	= array();
	const MAX_IMAGES_ALLOWED 			= 5;
	public function __construct() {	
		parent::__construct();
		$this->load->library('form_validation');
		$this->load->library("pagination");
		$this->load->helper('url');
		$this->load->model('manage_model');
		$this->load->model('order_model');
		$this->data['message'] 			= $this->session->flashdata('message');
		$this->data['message_type'] 	= $this->session->flashdata('message_type');
	}
	
	public function index($page = NULL) {
		if ($this->ion_auth->in_group(2)) {
			$this->data['processing_odr_cashier']	= $this->order_model->processing_odr_cashier();
		} else if ($this->ion_auth->in_group(3)) {
			$tableDtil						= $this->order_model->get_available_tables();
			$loggedUser 					= $this->ion_auth->user()->row();
			foreach ($tableDtil as $key => $table) {
				$checkOrder					= $this->order_model->check_table($table['id'], $loggedUser->id);
				//echo "<pre>"; print_r($checkOrder);
				if (!empty($checkOrder)) {
					if (($checkOrder->user_id !== $loggedUser->id) && ($table['id'] == $checkOrder->table_id)) {
					unset($tableDtil[$key]); }
				}
			}
			$this->data['table_dtil']		= $tableDtil;
		}

		
		if ($page) {
			$this->render($page);
		} else  {
			$this->render('dashboard');
		}
		
		}
		
	#manage menu attributes here 
	function menu_attribute() {
		$allAttributes		= _DB_data($this->tables['attributes'],null, null, null, array('entity_id', 'desc')); 
		$this->add_data(compact('allAttributes'));
		$this->render('manage-menu-attributes');
	}

	#load add page 
	function add_attribute() {
			$attribute_name		= '';
			$attribute_status	= '';
		$this->add_data(compact('attribute_name', 'attribute_status'));
		$this->render('add-menu-attributes');
	}
	
	#load edit attributes
	function edit_attribute($attId	= NULL) {
		if ($attId) {
			$get_data					= _DB_get_record($this->tables['attributes'], array('entity_id' => $attId));
			$attribute_name				= '';
			$attribute_status			= '';
			$this->add_data(compact('get_data', 'attribute_name', 'attribute_status'));
			$this->render('add-menu-attributes');
			
		} else {
			$this->session->set_flashdata('message', "Oops! something went wrong. Try again later.");
			$this->session->set_flashdata('message_type', 'danger');
			redirect('manage/menu_attribute', 'refresh');
		}
	}
	
	#add or edit menu attributes
	function submit_attribute() {
		$editId					= $this->input->post('edit_id', true);
		if (!$editId) {
		$this->form_validation->set_rules('attribute_name','Attribute Name','trim|required|is_unique[attributes.attribute_name]');
		} else {
			$this->form_validation->set_rules('attribute_name','Attribute Name','trim|required');
		}
		$this->form_validation->set_rules('attribute_status','Attribute Status','trim|required');
		$attribute_name			= $this->input->post('attribute_name', true);
		$attribute_status		= $this->input->post('attribute_status', true);
		if ($this->form_validation->run() == true) {
		 	
			if ($editId) {
			$update				= _DB_update($this->tables['attributes'], array('attribute_name' => $attribute_name, 'status' => $attribute_status), array('entity_id' => $editId));
				if ($update) {
				 $this->session->set_flashdata('message', "Attribute '<strong>$attribute_name</strong>' has been updated successfully.");
				$this->session->set_flashdata('message_type', 'success');
				redirect('manage/menu_attribute', 'refresh');
			} else {
				 $this->session->set_flashdata('message', "Oops! Something went wrong. Try again later");
				 $this->session->set_flashdata('message_type', 'danger');
				 redirect('manage/menu_attribute', 'refresh');
			}
			} else {
				$insert				= _DB_insert($this->tables['attributes'], array('attribute_name' => $attribute_name, 'status' => $attribute_status));
				if ($insert) {
				 $this->session->set_flashdata('message', "Attribute '<strong>$attribute_name</strong>' has been Added successfully.");
				$this->session->set_flashdata('message_type', 'success');
				redirect('manage/menu_attribute', 'refresh');
			} else {
				 $this->session->set_flashdata('message', "Oops! Something went wrong. Try again later");
				 $this->session->set_flashdata('message_type', 'danger');
				 redirect('manage/menu_attribute', 'refresh');
			}
			}
			
			} else {
				 $this->session->set_flashdata('message', validation_errors());
				 $this->session->set_flashdata('message_type', 'danger'); 
			}
			$this->add_data(compact('attribute_name', 'attribute_status'));
			redirect('manage/add_attribute', 'refresh');
	}
	
	#list menu categories
	function menu_categories() {
		$allCategories				= $this->manage_model->get_categories();
		$this->data['categories']	= $allCategories;
		$this->render('manage-menu-categories');
	}
	
	#add new category
	function add_category() {
		$this->data['attributes']		= _DB_data($this->tables['attributes'], array('status' => 1), null, null, null);
		$this->render('add-menu-category');
	}
	
	#sumbit category
	function submit_category() {
		$dateTime				= date('Y-m-d H:i:s');		
		$editId					= $this->input->post('edit_id', true);
		if (!$editId) {
		$this->form_validation->set_rules('category_name','Category Name','trim|required|is_unique[category_entity.entity_name]');
		} else {
			$this->form_validation->set_rules('category_name','Category Name','trim|required');
		}
		$this->form_validation->set_rules('attribute_name','Attribute','trim|required');
		$this->form_validation->set_rules('category_status','Category Status','trim|required');
		$category_name			= $this->input->post('category_name', true);
		$attribute_id			= $this->input->post('attribute_name', true);
		$category_status		= $this->input->post('category_status', true);
		if ($this->form_validation->run() == true) {
		 	
			if ($editId) {
			$update				= _DB_update($this->tables['category_entity'], array('attribute_id' => $attribute_id, 'entity_name' => $category_name,  'updated_at' => $dateTime, 'status' => $category_status), array('entity_id' => $editId));
				if ($update) {
				 $this->session->set_flashdata('message', "Category '<strong>$category_name</strong>' has been updated successfully.");
				$this->session->set_flashdata('message_type', 'success');
				redirect('manage/menu_categories', 'refresh');
			} else {
				 $this->session->set_flashdata('message', "Oops! Something went wrong. Try again later");
				 $this->session->set_flashdata('message_type', 'danger');
				 redirect('manage/menu_categories', 'refresh');
			}
			} else {
				$insert				= _DB_insert($this->tables['category_entity'], array('attribute_id' => $attribute_id, 'entity_name' => $category_name, 'created_at' => $dateTime, 'status' => $category_status));
				if ($insert) {
				 $this->session->set_flashdata('message', "Category '<strong>$category_name</strong>' has been Added successfully.");
				$this->session->set_flashdata('message_type', 'success');
				redirect('manage/menu_categories', 'refresh');
			} else {
				 $this->session->set_flashdata('message', "Oops! Something went wrong. Try again later");
				 $this->session->set_flashdata('message_type', 'danger');
				 redirect('manage/menu_categories', 'refresh');
			}
			}
			
			} else {
				 $this->session->set_flashdata('message', validation_errors());
				 $this->session->set_flashdata('message_type', 'danger'); 
			}
			$this->add_data(compact('attribute_name', 'attribute_status'));
			redirect('manage/add_category', 'refresh');
	}
	
	#load edit category
	function edit_category($catId	= NULL) {
		if ($catId) {
			
			$get_data					= _DB_get_record($this->tables['category_entity'], array('entity_id' => $catId));
			$attributes					= _DB_data($this->tables['attributes'], array('status' => 1), null, null, null);
			$this->add_data(compact('get_data', 'attributes'));
			$this->render('add-menu-category');
			
		} else {
			$this->session->set_flashdata('message', "Oops! something went wrong. Try again later.");
			$this->session->set_flashdata('message_type', 'danger');
			redirect('manage/menu_categories', 'refresh');
		}
	}

	#list table categories
	function table_categories() {
		//$allCategories				= $this->manage_model->get_table_categories();
		$allCategories					= _DB_data($this->tables['table_category'], null, null, null, array('id','desc'));

		$this->data['categories']	= $allCategories;
		$this->render('manage-table-categories');
	}

	#add new table category
	function add_table_category() {
		
		$this->render('add-table-category');
	}

	#sumbit table category
	function submit_table_category() {
		$dateTime				= date('Y-m-d H:i:s');		
		$editId					= $this->input->post('edit_id', true);
		if (!$editId) {
		$this->form_validation->set_rules('category_name','Category Name','trim|required|is_unique[table_category.name]');
		} else {
			$this->form_validation->set_rules('category_name','Category Name','trim|required');
		}
		$this->form_validation->set_rules('category_status','Category Status','trim|required');
		$category_name			= $this->input->post('category_name', true);		
		$category_status		= $this->input->post('category_status', true);
		if ($this->form_validation->run() == true) {
		 	
			if ($editId) {
			$update				= _DB_update($this->tables['table_category'], array('name' => $category_name,  'updated_at' => $dateTime, 'status' => $category_status), array('id' => $editId));
				if ($update) {
				 $this->session->set_flashdata('message', "Category '<strong>$category_name</strong>' has been updated successfully.");
				$this->session->set_flashdata('message_type', 'success');
				redirect('manage/table_categories', 'refresh');
			} else {
				 $this->session->set_flashdata('message', "Oops! Something went wrong. Try again later");
				 $this->session->set_flashdata('message_type', 'danger');
				 redirect('manage/table_categories', 'refresh');
			}
			} else {
				$insert				= _DB_insert($this->tables['table_category'], array('name' => $category_name, 'created_at' => $dateTime, 'status' => $category_status));
				if ($insert) {
				 $this->session->set_flashdata('message', "Category '<strong>$category_name</strong>' has been Added successfully.");
				$this->session->set_flashdata('message_type', 'success');
				redirect('manage/table_categories', 'refresh');
				} else {
					 $this->session->set_flashdata('message', "Oops! Something went wrong. Try again later");
					 $this->session->set_flashdata('message_type', 'danger');
					 redirect('manage/table_categories', 'refresh');
				}
			}
			
		} else {
			 $this->session->set_flashdata('message', validation_errors());
			 $this->session->set_flashdata('message_type', 'danger'); 
		}		
		redirect('manage/add_table_category', 'refresh');
	}
	

	#load edit tabel category
	function edit_table_category($catId	= NULL) {
		if ($catId) {
			
			$get_data					= _DB_get_record($this->tables['table_category'], array('id' => $catId));			
			$this->add_data(compact('get_data'));
			$this->render('add-table-category');
			
		} else {
			$this->session->set_flashdata('message', "Oops! something went wrong. Try again later.");
			$this->session->set_flashdata('message_type', 'danger');
			redirect('manage/table_categories', 'refresh');
		}
	}

	#list table details
	function table_details() {
		$allTabels				= $this->manage_model->get_tables();
		$this->data['tables']	= $allTabels;
		$this->render('manage-table-details');
	}
	
	#add new table details
	function add_table_details() {
		$this->data['category']		= _DB_data($this->tables['table_category'], array('status' => 1), null, null, null);
		$this->render('add-table-details');
	}
	
	#sumbit table details
	function submit_table_details() {
		$dateTime				= date('Y-m-d H:i:s');		
		$editId					= $this->input->post('edit_id', true);
		if (!$editId) {
		$this->form_validation->set_rules('table_number','Table Number','trim|required|is_unique[table_details.table_number]');
		} else {
			$this->form_validation->set_rules('table_number','Table Number','trim|required');
		}
		$this->form_validation->set_rules('capacity','Capacity','trim|required');
		$this->form_validation->set_rules('table_category','Table Category','trim|required');
		$this->form_validation->set_rules('table_status','Status','trim|required');
		$table_number			= $this->input->post('table_number', true);
		$capacity				= $this->input->post('capacity', true);
		$table_category			= $this->input->post('table_category', true);
		$table_status			= $this->input->post('table_status', true);
		if ($this->form_validation->run() == true) {
		 	
			if ($editId) {
			$update				= _DB_update($this->tables['table_details'], array('table_cat_id' => $table_category,'table_number' => $table_number, 'capacity' => $capacity,  'updated_at' => $dateTime, 'status' => $table_status), array('id' => $editId));
				if ($update) {
				 $this->session->set_flashdata('message', "Table '<strong>$table_number</strong>' has been updated successfully.");
				$this->session->set_flashdata('message_type', 'success');
				redirect('manage/table_details', 'refresh');
			} else {
				 $this->session->set_flashdata('message', "Oops! Something went wrong. Try again later");
				 $this->session->set_flashdata('message_type', 'danger');
				 redirect('manage/table_details', 'refresh');
			}
			} else {
				$insert				= _DB_insert($this->tables['table_details'], array('table_cat_id' => $table_category, 'table_number' => $table_number, 'capacity' => $capacity, 'created_at' => $dateTime, 'status' => $table_status));
				if ($insert) {
				 $this->session->set_flashdata('message', "Table '<strong>$table_number</strong>' has been Added successfully.");
				$this->session->set_flashdata('message_type', 'success');
				redirect('manage/table_details', 'refresh');
			} else {
				 $this->session->set_flashdata('message', "Oops! Something went wrong. Try again later");
				 $this->session->set_flashdata('message_type', 'danger');
				 redirect('manage/table_details', 'refresh');
			}
			}
			
			} else {
				 $this->session->set_flashdata('message', validation_errors());
				 $this->session->set_flashdata('message_type', 'danger'); 
			}
			
			redirect('manage/add_table_details', 'refresh');
	}
	
	#load edit table details
	function edit_table_details($tabId	= NULL) {
		if ($tabId) {
			
			$get_data					= _DB_get_record($this->tables['table_details'], array('id' => $tabId));
			$category					= _DB_data($this->tables['table_category'], array('status' => 1), null, null, null);
			$this->add_data(compact('get_data', 'category'));
			$this->render('add-table-details');
		} else {
		$this->session->set_flashdata('message', "Oops! something went wrong. Try again later.");
		$this->session->set_flashdata('message_type', 'danger');
		redirect('manage/table_details', 'refresh');
		}
	}

	#list menus
	function manage_menu() {
		$this->data['menus']		= $this->manage_model->get_menus();
		$this->render('manage-menus');
	}
	
	#add new menu
	function add_menu() {
		$this->data['categories']	= _DB_data($this->tables['category_entity'], array('status' => 1), null, null, null);
		$this->data['price_types']	= _DB_data($this->tables['menu_entity_price_type'], array('status' => 1), null, null, null);
		$this->data['tax_class']	= _DB_data($this->tables['tax_entity'], array('status' => 1), null, null, null);
		$this->render('add-menu');
	}
	
	#insert menu
	function submit_menu() {
		$dateTime					= date('Y-m-d H:i:s');	
		$editId						= $this->input->post('edit_id', true);
		if (!$editId) {
			$this->form_validation->set_rules('menu_name','Menu Name','trim|required|is_unique[menu_entity.menu_name]');
		} else {
			$this->form_validation->set_rules('menu_name','Menu Name','trim|required');
		}
		$this->form_validation->set_rules('category','Category','trim|required');
		if ($this->form_validation->run() == true) {
				$menuName			= $this->input->post('menu_name', true);
				$category			= $this->input->post('category', true);
				$taxClass			= $this->input->post('tax_class', true);
				$status				= $this->input->post('menu_status', true);
				$ingredients		= $this->input->post('ingredients', true);
				$price_types		= _DB_data($this->tables['menu_entity_price_type'], array('status' => 1), null, null, null);
				if (!$editId) {
				$insert				= _DB_insert($this->tables['menu_entity'], array('category_id' => $category, 'menu_name' => $menuName, 'tax_class' => $taxClass, 'created_at' => $dateTime, 'updated_at' => $dateTime, 'status' => $status));
				if ($insert) {
					$menuId			= _DB_insert_id();
					if (!empty($price_types)) {
						foreach ($price_types as $types) {
							$pricetyp	= $this->input->post('price_type_'.$types['entity_id'], true);
							$priceAmt	=  $this->input->post('price_amt_'.$types['entity_id'], true);
							if ($pricetyp) {
								_DB_insert($this->tables['menu_entity_price'], array('menu_id' => $menuId, 'price_type' => $pricetyp, 'price_amount' => $priceAmt));
							}
						}
					}
					if (!empty($ingredients)) {
						foreach ($ingredients as $inds) {
							_DB_insert($this->tables['menu_entity_ingredients'], array('menu_id' => $menuId, 'ingredient_name' => $inds));
						}
					}
					$this->session->set_flashdata('message', "Menu has been addedd successfully");
					$this->session->set_flashdata('message_type', 'success');
					redirect('manage/manage_menu', 'refresh');
				} else {
					$this->session->set_flashdata('message', "Oops something went wrong try again later");
					$this->session->set_flashdata('message_type', 'danger');
					redirect('manage/add_menu', 'refresh');
				}
		} else { 
			$update				= _DB_update($this->tables['menu_entity'], array('category_id' => $category, 'menu_name' => $menuName, 'tax_class' => $taxClass, 'updated_at' => $dateTime, 'status' => $status), array('entity_id' => $editId));
				if ($update) {
					#update ingredients
					if (!empty($ingredients)) {
						if (!empty($price_types)) {
						foreach ($price_types as $types) {
							$pricetyp	= $this->input->post('price_type_'.$types['entity_id'], true);
							$priceAmt	=  $this->input->post('price_amt_'.$types['entity_id'], true);
							if ($pricetyp) {
								$checkPrice		= _DB_get_record($this->tables['menu_entity_price'], array('menu_id' => $editId, 'price_type' => $pricetyp));
								if (empty($checkPrice)) {
								_DB_insert($this->tables['menu_entity_price'], array('menu_id' => $editId, 'price_type' => $pricetyp, 'price_amount' => $priceAmt));
								} else {
									_DB_update($this->tables['menu_entity_price'], array('price_type' => $pricetyp, 'price_amount' => $priceAmt), array('menu_id' => $editId, 'price_type' => $pricetyp));
								}
							}
						}
					}
						$getAllIngredients		= _DB_data($this->tables['menu_entity_ingredients'], null, null, null, null);
						$tmp					= array();
						foreach ($getAllIngredients as $allIngredients) {
							$tmp[]				= $allIngredients['ingredient_id'];
						}
						foreach ($ingredients as $key => $inds) {
							if (in_array($key, $tmp)) {
							_DB_update($this->tables['menu_entity_ingredients'], array( 'ingredient_name' => $inds), array('ingredient_id' => $key, 'menu_id' => $editId));
							} else {
								_DB_insert($this->tables['menu_entity_ingredients'], array('menu_id' => $editId, 'ingredient_name' => $inds));
							}
						}
					}
					
					$this->session->set_flashdata('message', "Menu has been updated successfully");
					$this->session->set_flashdata('message_type', 'success');
					redirect('manage/manage_menu', 'refresh');
				} else {
					$this->session->set_flashdata('message', "Oops something went wrong try again later");
					$this->session->set_flashdata('message_type', 'danger');
					redirect('manage/add_menu', 'refresh');
				}
		}
			 } else {
			 		$this->session->set_flashdata('message', validation_errors());
					$this->session->set_flashdata('message_type', 'danger');
					redirect('manage/add_menu', 'refresh');
			 }
		
	}
	
	#edit menu
	function edit_menu($menu = NULL) {
		
		if ($menu) {
			$get_data					= _DB_get_record($this->tables['menu_entity'], array('entity_id' => $menu));
			$categories					= _DB_data($this->tables['category_entity'], array('status' => 1), null, null, null);
			$ingredients				= _DB_data($this->tables['menu_entity_ingredients'], array('menu_id' => $menu), null, null, null);
			$this->data['price_types']	= _DB_data($this->tables['menu_entity_price_type'], array('status' => 1), null, null, null);
			$this->data['tax_class']	= _DB_data($this->tables['tax_entity'], array('status' => 1), null, null, null);
			$priceList					= _DB_data($this->tables['menu_entity_price'], array('menu_id' => $menu), null, null, null);
			$price_list 				= array();
			foreach ($priceList as $price) {
				$price_list[$price['price_type']]	= $price['price_amount'];
			}
			$this->add_data(compact('get_data', 'categories', 'ingredients', 'price_list'));
			$this->render('add-menu');
			
		} else {
			$this->session->set_flashdata('message', "Oops! something went wrong. Try again later.");
			$this->session->set_flashdata('message_type', 'danger');
			redirect('manage/manage_menu', 'refresh');
		}
	
	}

	#get user details
	public function get_userDetails($userId  = null){

		$get_data 	= $this->manage_model->get_user_details();
		$get_groups = _DB_data($this->tables['groups'], null, null, null, null);

		// print_r($get_groups);
		// die();
		$userData="";
		foreach ($get_data as $rows) {
			$userData .='<tr class="odd gradeX" >
			<td>'. stripslashes($rows['first_name']).'</td>
			<td>'. stripslashes($rows['user_type']).'</td>
			<td>'. stripslashes($rows['username']).'</td>
            <td>'. stripslashes($rows['email']).'</td>					
			';
			if(($rows['id'] != 1) && ($rows['username'] != 'administrator')){
				$userData.='
				<td><a href="#" class="btn btn-warning btn-xs" onclick="edit_userDetails('.$rows['id'].')""><i class="fa fa-edit"></i> Edit</a></td>
				<td id="td_id_'.$rows['id'].'" ><a href="#" class="btn btn-warning btn-xs btn_delete" onclick="deleteUser('.$rows['id'].')"><i class="fa fa-edit"></i> Delete</a></td>';
			}
		$userData.='</tr>';			
		}		
		$data = array('userData'=>$userData,
					  'userGroups' => $get_groups);
		print_r(json_encode($data));				
	}

	#load edit user_details
	public function loadEditUserDetails($userId=null){

		
		$get_data 	= $this->manage_model->get_user_details($userId);
		$get_groups = _DB_data($this->tables['groups'], null, null, null, null);

		
		$userData="";
		foreach ($get_data as $rows) {			
		
		$userData.='
				<div class="row" id="editsuccessMessages" style="display:none">
					<div class="col-lg-12 col-sm-offset-12">
		            	<div class="alert alert-danger" ></div>
		          	</div>					
				</div>
				
				<!-- <form id="registerForm"> -->
				<div class="row">
					<div class="col-md-6">
						<div class="form-group">
							<input type="hidden" class="form-control" placeholder="First Name" name="edituser_id" id="edituser_id" value="'.$rows['id'].'"  />
						</div>
					</div>					
				</div>
				<div class="row">
					<div class="col-md-6">
						<div class="form-group">
							<input type="text" class="form-control" placeholder="First Name" name="editfirstName" id="editfirstName" value="'.$rows['first_name'].'" required />
						</div>
					</div>
					<div class="col-md-6">
						<div class="form-group">
							<input type="text" class="form-control" placeholder="Last Name" name="editlastName" id="editlastName" value="'.$rows['last_name'].'" required />
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-md-6">
						<div class="form-group">
							<input type="text" class="form-control" placeholder="User Name" name="edituserName" id="edituserName" value="'.$rows['username'].'" required />
						</div>
					</div>
					<div class="col-md-6">
						<div class="form-group">
							<input type="number" class="form-control" placeholder="Phone" name="editphone" id="editphone" value="'.$rows['phone'].'"   required />
						</div>
					</div>
				</div>
				
				<div class="row">
					<div class="col-md-12">
						<div class="form-group">
							<input type="email" class="form-control" placeholder="Email" name="editemail" id="editemail" value="'.$rows['email'].'" required />
						</div>
						<div class="form-group">
							<textarea class="form-control" placeholder="Address" rows="5" id="editaddress" name="editaddress" required>'.$rows['company'].'</textarea>
						</div>
					</div>
				</div>
				
				<div class="row">
					<div class="col-md-6">
						<div class="form-group">
							<label for="category"> User Type</label>
							<select class="form-control" id="edituserType" name="edituserType" >
								';

								$options = '<option value="0">Select A Type</option>';

				             	foreach($get_groups as $group_row) {
				             	  if($group_row['id'] != 1){
				             	  (!empty($rows['user_type']) && $rows['user_type']===$group_row['name']) ? $selected=' selected="selected" ' : $selected='';			            
				             	  	$options.= '<option '.$selected. ' value="' . $group_row["id"] . '">' . $group_row["name"] . '</option>';
				             	  }	
				                  
				             	 }
				              	$closeSelect = '</select>';

					$userData.=$options.$closeSelect;
					$userData.='</select>
						</div>
					</div>
					<div class="col-md-6">
						<div class="form-group">
							
						</div>
					</div>
				</div>
			';
			}
		$data = array('userData'=>$userData);
		print_r(json_encode($data));


	}

	#load edit userprofile details of logined user
	public function loadeditUserProfile(){

		$userId = $this->ion_auth->get_user_id();		
		$get_data 	= $this->manage_model->get_user_details($userId);		
		$editUserData="";
		foreach ($get_data as $rows) {			
		
		$editUserData.='
				<div class="row" id="editProfilesuccessMessage" style="display:none">
					<div class="col-lg-12 col-sm-offset-12">
		            	<div class="alert alert-danger" ></div>
		          	</div>					
				</div>
				
				<!-- <form id="registerForm"> -->
				<div class="row">
					<div class="col-md-6">
						<div class="form-group">
							<input type="hidden" class="form-control" placeholder="First Name" name="editProfileuser_id" id="editProfileuser_id" value="'.$rows['id'].'"  />
						</div>
					</div>					
				</div>
				<div class="row">
					<div class="col-md-6">
						<div class="form-group">
							<input type="text" class="form-control" placeholder="First Name" name="editProfilefirstName" id="editProfilefirstName" value="'.$rows['first_name'].'" required />
						</div>
					</div>
					<div class="col-md-6">
						<div class="form-group">
							<input type="text" class="form-control" placeholder="Last Name" name="editProfilelastName" id="editProfilelastName" value="'.$rows['last_name'].'" required />
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-md-6">
						<div class="form-group">
							<input type="text" class="form-control" placeholder="User Name" name="editProfileuserName" id="editProfileuserName" value="'.$rows['username'].'" required />
						</div>
					</div>
					<div class="col-md-6">
						<div class="form-group">
							<input type="number" class="form-control" placeholder="Phone" name="editProfilephone" id="editProfilephone" value="'.$rows['phone'].'"   required />
						</div>
					</div>
				</div>				
				<div class="row">
					<div class="col-md-12">
						<div class="form-group">
							<input type="email" class="form-control" placeholder="Email" name="editProfileemail" id="editProfileemail" value="'.$rows['email'].'" required />
						</div>
						<div class="form-group">
							<textarea class="form-control" placeholder="Address" rows="5" id="editProfileaddress" name="editProfileaddress" required>'.$rows['company'].'</textarea>
						</div>
					</div>
				</div>		
				
			';
			}
		$data = array('editUserData'=>$editUserData);
		print_r(json_encode($data));
	}

	#edit user_details
	public function edit_user($flag=null){
		if ($this->ion_auth->logged_in()) { // Check whether the user is already logged-in 
			
		$this->load->library('form_validation');
		$this->form_validation->set_rules('userName', 'user name', 'trim|required|min_length[5]|max_length[15]|alpha');		
		$this->form_validation->set_rules('email', 'email address', 'trim|required|valid_email');
		$this->form_validation->set_rules('phone', 'Phone Number', 'required|integer|min_length[10]|max_length[12]');
		$this->form_validation->set_rules('firstName', 'First name', 'required');
		$this->form_validation->set_rules('lastName', 'Last name', 'required');
		$this->form_validation->set_rules('address', 'Address', 'required');
		if(empty($flag)){
			$this->form_validation->set_rules('userType', 'user type', 'required');
		}		
		if ($this->form_validation->run() == true) {
			
			$user_id 			= $this->input->post('user_id');
			$userName			= $this->input->post('userName');
			// $password			= $this->input->post('password');
			// $confpassword 		= $this->input->post('confpassword');
			$email 				= $this->input->post('email');
			$fisrtName			= $this->input->post('firstName');
			$lastName			= $this->input->post('lastName');
			if(empty($flag)){
				$userGroup			= $this->input->post('userType');
			}
			
			$phone 				= $this->input->post('phone');
			$address 			= $this->input->post('address');
			$additionalData 	= array(
								'first_name' => $fisrtName,
								'last_name' => $lastName,
								'company'	=> $address,
								'phone' 	=> $phone
								);
			if(empty($flag)){
				$group 				= array($userGroup); // Sets user.

				if($userGroup == 2){
					$userType = 'cashier';
				} elseif($userGroup == 3){
					$userType = 'waiter';
				}
			}		

			$userUpdate = _DB_update($this->tables['users'], array('first_name' => $fisrtName, 'last_name' => $lastName, 'username' => $userName, 'email' => $email,  'company' => $address, 'phone' => $phone), array('id' => $user_id));

			if(empty($flag)){
				$userUpdateGroup = _DB_update($this->tables['users_groups'], array('group_id' => $userGroup), array('id' => $user_id));
			}			

			if ($userUpdate && empty($flag)) {
				$userData="";				
				$userData .='<tr class="odd gradeX" >
					<td>'. stripslashes($fisrtName).'</td>
					<td>'. stripslashes($userType).'</td>
					<td>'. stripslashes($userName).'</td>
		            <td>'. stripslashes($email).'</td>					
					<td><a href="#" class="btn btn-warning btn-xs" onclick="edit_userDetails('.$user_id.')"><i class="fa fa-edit"></i> Edit</a></td>';
					if($user_id != 1){
						$userData.='<td id="td_id_'.$user_id.'" ><a href="#" class="btn btn-warning btn-xs btn_delete" onclick="deleteUser('.$user_id.')"><i class="fa fa-edit"></i> Delete</a></td>';
					}
				$userData.='</tr>';
				$data 	= array('message' => 'Data updated successfully',
								'message_type' => 'success',
								'userData'  => $userData
								);
				print_r(json_encode($data));
				
			} else if($userUpdate && !empty($flag)){
				$data 	= array('message' => 'Data updated successfully',
								'message_type' => 'success'
								);
				print_r(json_encode($data));

			} else {
				$data 	= array('message' => 'error',
								'message_type' => 'danger'
								);
				print_r(json_encode($data));
			}
				
			} else {
				$data 	= array('message' => validation_errors(),
								'message_type' => 'danger'
								);
				print_r(json_encode($data));
			}			
		}
	}
	
	#load system config 
	function system_config() {
		$this->data['price_type']	= _DB_data($this->tables['menu_entity_price_type'], null, null, null, array('entity_id', 'desc'));
		$this->data['tax_classes']	= _DB_data($this->tables['tax_entity'], null, null, null, array('entity_id', 'desc'));
		$this->render('system');
	}
	
	#load add price type option
	function add_price_type() {
		$this->render('add-price-type');
	}
	
	#submit price type
	function submit_price_type() {
		$editId						= $this->input->post('edit_id', true);
		if ($editId) {
			$this->form_validation->set_rules('price_type','Price Type Name','trim|required');
		} else {
			$this->form_validation->set_rules('price_type','Price Type Name','trim|required|is_unique[menu_entity_price_type.type_name]');
		}
		$this->form_validation->set_rules('status','Price Type Status','trim|required');
		$priceType					= $this->input->post('price_type', true);
		$status						= $this->input->post('status', true);
		if ($this->form_validation->run() == true) {
			if ($editId) {
				$update				=  _DB_update($this->tables['menu_entity_price_type'], array('type_name' => $priceType, 'status' => $status), array('entity_id' => $editId));
				if ($update) {
					$this->session->set_flashdata('message', "Price type has been updated.");
					$this->session->set_flashdata('message_type', 'success');
					redirect('manage/system_config', 'refresh');
				} else {
					$this->session->set_flashdata('message', "Oops something went wrong. Try again later");
					$this->session->set_flashdata('message_type', 'danger');
					redirect('manage/system_config', 'refresh');
				}
			} else {
				$insert				= _DB_insert($this->tables['menu_entity_price_type'], array('type_name' => $priceType, 'status' => $status));
				if ($insert) {
					$this->session->set_flashdata('message', "Price type has been added.");
					$this->session->set_flashdata('message_type', 'success');
					redirect('manage/system_config', 'refresh');
				
				} else {
					$this->session->set_flashdata('message', "Oops! Something went wrong. Try again later.");
					$this->session->set_flashdata('message_type', 'danger');
					redirect('manage/add_price_type', 'refresh');
				}
			}
		} else {
			$this->session->set_flashdata('message', validation_errors());
			$this->session->set_flashdata('message_type', 'danger');
			redirect('manage/add_price_type', 'refresh');
		}
	}
	
	#load edit price type
	function edit_price_type($editId = NULL) {
		if ($editId) {
			$get_data				= _DB_get_record($this->tables['menu_entity_price_type'], array('entity_id' => $editId));
			$this->data['get_data']	= $get_data;
			$this->render('add-price-type');
		} else {
		$this->session->set_flashdata('message', "Oops! Something went wrong. Try again later.");
		$this->session->set_flashdata('message_type', 'danger');
		redirect('manage/system_config', 'refresh');
		}
	}
	
	#load add tax class
	function add_tax() {
		$this->render('add-tax-class');
	}
	
	#submit tax class
	function submit_tax_class() {
		$editId						= $this->input->post('edit_id', true);
		if ($editId) {
			$this->form_validation->set_rules('tax_class','Tax Class Name','trim|required');
			$this->form_validation->set_rules('tax_rate','Tax Rate','trim|required');
		} else {
			$this->form_validation->set_rules('tax_class','Tax Class Name','trim|required|is_unique[tax_entity.tax_class]');
			$this->form_validation->set_rules('tax_rate','Tax Rate','trim|required|is_unique[tax_entity.tax_rate]');
		}
		$this->form_validation->set_rules('status','Price Status','trim|required');
		$taxClass					= $this->input->post('tax_class', true);
		$taxRate					= $this->input->post('tax_rate', true);
		$status						= $this->input->post('status', true);
		if ($this->form_validation->run() == true) {
			if ($editId) {
				$update				=  _DB_update($this->tables['tax_entity'], array('tax_class' => $taxClass, 'tax_rate' => $taxRate, 'status' => $status), array('entity_id' => $editId));
				if ($update) {
					$this->session->set_flashdata('message', "Tax class has been updated.");
					$this->session->set_flashdata('message_type', 'success');
					redirect('manage/system_config', 'refresh');
				} else {
					$this->session->set_flashdata('message', "Oops something went wrong. Try again later");
					$this->session->set_flashdata('message_type', 'danger');
					redirect('manage/system_config', 'refresh');
				}
			} else {
				$insert				= _DB_insert($this->tables['tax_entity'], array('tax_class' => $taxClass, 'tax_rate' => $taxRate, 'status' => $status));
				if ($insert) {
					$this->session->set_flashdata('message', "Tax calss has been added.");
					$this->session->set_flashdata('message_type', 'success');
					redirect('manage/system_config', 'refresh');
				
				} else {
					$this->session->set_flashdata('message', "Oops! Something went wrong. Try again later.");
					$this->session->set_flashdata('message_type', 'danger');
					redirect('manage/add_tax', 'refresh');
				}
			}
		} else {
			$this->session->set_flashdata('message', validation_errors());
			$this->session->set_flashdata('message_type', 'danger');
			redirect('manage/add_tax', 'refresh');
		}
	}
	
	#load tax edit window
	function edit_tax($editId = NULL) {
		if ($editId) {
			$get_data				= _DB_get_record($this->tables['tax_entity'], array('entity_id' => $editId));
			$this->data['get_data']	= $get_data;
			$this->render('add-tax-class');
		} else {
		$this->session->set_flashdata('message', "Oops! Something went wrong. Try again later.");
		$this->session->set_flashdata('message_type', 'danger');
		redirect('manage/system_config', 'refresh');
		}
	}
	
	#while click on table button for order and view(Load) order page
	function order_desk($tableId = NULL,$orderType=NULL) {
		$loggedUser 				= $this->ion_auth->user()->row();
		$this->data['table_id']		= $tableId;
		$this->data['order_type']	= $orderType;
		if ($tableId) {	
			$checkOrder				= $this->order_model->check_table($tableId, $loggedUser->id);
			if (!empty($checkOrder) && ($checkOrder->user_id !== $loggedUser->id) && ($tableId == $checkOrder->table_id)) {
				redirect('manage/index', 'refresh');
			} else {
				//check whether pending order is there or what
				$this->data['table_detail']		= _DB_get_record($this->tables['table_details'], array('id' => $tableId));
				$this->data['processing_odr']	= $this->order_model->processing_orders($loggedUser->id, $tableId);
				$this->data['menu_category']	= _DB_data($this->tables['category_entity'], array('status' => 1));
				$this->data['menu_details']		= $this->order_model->get_active_menus();
				$this->data['price_cat_dtil']	= _DB_data($this->tables['menu_entity_price_type'], array('status' => 1));
				$this->render('order-desk');
			}
		} else {
			$this->session->set_flashdata('message', "Oops! Something went wrong. Try again later.");
			$this->session->set_flashdata('message_type', 'danger');
			redirect('manage/index', 'refresh');
		}
	}
	
	#function to create new order
	function create_order() {
		$dateTime					= date('Y-m-d H:i:s');
		$loggedUser 				= $this->ion_auth->user()->row();
		$tableID					= $this->input->post('table_id',true);
		if (!empty($loggedUser) && $tableID) {
			$getMaxOrderId			= $this->order_model->max_increment_id('order_entity');
			if ($getMaxOrderId->increment_id) {
			 	$incrementId		= $getMaxOrderId->increment_id+1;
			 } else {
			 	$incrementId		= 10001;
			 }
			 $status				= 'pending';
			 $insertOrder			= _DB_insert($this->tables['order_entity'], array('status' => $status, 'table_id' => $tableID, 'user_id' => $loggedUser->id, 'increment_id' => $incrementId, ' 	created_at' => $dateTime, 'updated_at' => $dateTime));
			if ($insertOrder) {
				$lastOrder			= _DB_insert_id();
				$getMaxKotId		= $this->order_model->max_increment_id('kot_entity');
				if ($getMaxKotId->increment_id) {
			 	$kotIncrementId		= $getMaxKotId->increment_id+1;
			 } else {
			 	$kotIncrementId		= 10001;
			 }
			
				$createKot			= _DB_insert($this->tables['kot_entity'], array('status' => $status, 'table_id' => $tableID, 'order_id' => $lastOrder, 'increment_id' => $kotIncrementId, 'created_at' => $dateTime, 'updated_at' => $dateTime));
				if ($createKot) {
					$lastKotId		= _DB_insert_id();
				} else {
					$lastKotId		= 0;
				}
				echo '<h1><span class="subscript">ORDER NO</span> "'.$incrementId.'"</h1> <input type="hidden" id="order-id" value="'.$lastOrder.'"><input type="hidden" id="kot-id" value="'.$lastKotId.'">';
			} else {
				echo "Sorry! Something went wrong. Try again later";
			}
		}
	}
	
	#get kot details by id
	function get_kot_details() {
		$kotId			= $this->input->post('kot_id', true);
		if ($kotId) {
			$kotDetails	= _DB_get_record($this->tables['kot_entity'], array('entity_id' => $kotId));
			echo $kotDetails['increment_id'];
		}
	}
	
	#to confirm menu from customer
	function confirm_menu($orderType = NULL) {
		$dateTime					= date('Y-m-d H:i:s');
		$order_id					= $this->input->post('order_id', true);
		$menu_id					= $this->input->post('menu_id', true);
		$price_type					= $this->input->post('price_type', true);
		$kot_id						= $this->input->post('kot_id', true); 
		$kot_flag					= $this->input->post('flag', true); 
		
		if ($order_id && $menu_id && $price_type) {
			$getPrice				= _DB_get_record($this->tables['menu_entity_price'], array('menu_id' => $menu_id, 'price_type' => $price_type));
			
			$typeDtil				=  _DB_get_record($this->tables['menu_entity_price_type'], array('entity_id' => $price_type));
			
			$menuDtil				= _DB_get_record($this->tables['menu_entity'], array('entity_id' => $menu_id));
			#if tax class is enabled
			if ($menuDtil['tax_class'] > 0) {
				$getTax				= _DB_get_record($this->tables['tax_entity'], array('entity_id' => $menuDtil['tax_class']));
				$taxPercent			= $getTax['tax_rate'];
				$price				= ($getPrice['price_amount']*100)/(100+$taxPercent);
				$taxAmount			= $getPrice['price_amount']-$price;
			} else {
				$taxPercent			= 0;
				$price				= $getPrice['price_amount'];
				$taxAmount			= 0;
				
			}
			$price_incld_tax		= $getPrice['price_amount'];
			$MenuName				= $menuDtil['menu_name']." (".$typeDtil['type_name'].")";
			$order_dtil				= _DB_get_record($this->tables['order_entity'], array('entity_id' => $order_id));
			if ($order_dtil['table_id'] == 1) {
					$order_type 	= "Parcel";
				} else {
					$order_type 	= 'Table';
				}
			
			$checkMenu				= _DB_get_record($this->tables['order_entity_items'], array('order_id' => $order_id, 'is_kot' => 0, 'menu_id' => $menu_id, 'price_type' => $price_type));	
			if (empty($checkMenu) && $kot_flag != 2 ){
				$qty				= 1;
				$row_total			= $price*$qty;
				$row_total_incld_tax= $price_incld_tax*$qty;
				$insertMenu			= _DB_insert($this->tables['order_entity_items'], array('order_id' => $order_id, 'is_kot' => 0, 'menu_id' => $menu_id, 'order_type' => $order_type, 'price_type' => $price_type, 'name' => $MenuName, 'qty_ordered' => $qty, 'price' => $price, 'tax_percent' => $taxPercent, 'tax_amount' => $taxAmount,  'row_total' => $row_total, 'price_incld_tax' => $price_incld_tax, 'row_total_incld_tax' => $row_total_incld_tax, 'created_at' => $dateTime, 'updated_at' => $dateTime));
				if ($insertMenu) {
					$insertKOT		= _DB_insert($this->tables['kot_entity_items'], array('kot_id' => $kot_id, 'is_kot' => 0, 'menu_id' => $menu_id, 'order_type' => $order_type, 'price_type' => $price_type, 'name' => $MenuName, 'qty_ordered' => $qty, 'created_at' => $dateTime, 'updated_at' => $dateTime));
					$this->data['order_id']		= $order_id;
					$this->data['kot_details']	= $this->order_model->kot_details($kot_id);
					$this->render('ajax/kot_details');
				}
			} else if(!empty($checkMenu) && $kot_flag != 2) {
				if($kot_flag==1){
					$qty				= $checkMenu['qty_ordered']-1;

				}else{
					$qty				= $checkMenu['qty_ordered']+1;

				}				
				$row_total				= $price*$qty;
				$row_total_incld_tax	= $price_incld_tax*$qty;
				$taxAmount				= $taxAmount*$qty;
				$updateMenu				= _DB_update($this->tables['order_entity_items'], array('qty_ordered' => $qty, 'tax_amount' => $taxAmount, 'row_total' => $row_total, 'row_total_incld_tax' => $row_total_incld_tax, 'updated_at' => $dateTime), array('item_id' => $checkMenu['item_id']));
				$checkKOT				= _DB_get_record($this->tables['kot_entity_items'],  array('kot_id' => $kot_id, 'is_kot' => 0, 'menu_id' => $menu_id, 'price_type' => $price_type));
				if (!empty($checkKOT)) {
					$updateKOT			= _DB_update($this->tables['kot_entity_items'], array('qty_ordered' => $qty, 'updated_at' => $dateTime), array('item_id' => $checkKOT['item_id']));
				}
				if ($updateMenu) {
					$this->data['order_id']		= $order_id;
					$this->data['kot_details']	= $this->order_model->kot_details($kot_id);
					$this->render('ajax/kot_details');
				}
			} else if(!empty($checkMenu) && $kot_flag == 2){

				$deleteMenu			= _DB_delete($this->tables['order_entity_items'], array('item_id' => $checkMenu['item_id']));
				$checkKOT			= _DB_get_record($this->tables['kot_entity_items'],  array('kot_id' => $kot_id, 'is_kot' => 0, 'menu_id' => $menu_id, 'price_type' => $price_type));
				if (!empty($checkKOT)) {
					$deleteKOT		= _DB_delete($this->tables['kot_entity_items'], array('item_id' => $checkKOT['item_id']));
				}
				if ($deleteMenu) {
					$this->data['order_id']		= $order_id;
					$this->data['kot_details']	= $this->order_model->kot_details($kot_id);					
					if(empty($this->data['kot_details'][0]['kot_id'])){
						echo "null";

					} else {
						$this->render('ajax/kot_details');
					}
					
				}
 			}
			
		}
	}
	
	#print KOT and Change the order and kot status
	function print_kot($orderType = NULL) {
		$dateTime					= date('Y-m-d H:i:s');
		$order_id					= $this->input->post('order_id', true);		
		$kot_id						= $this->input->post('kot_id', true); 
	if ($order_id && $kot_id) {
		$updateTotal				= $this->order_model->sum_of_order($order_id);
		$kotTotal					= $this->order_model->sum_of_kot($kot_id);
		$updateOrderEntity			= _DB_update($this->tables['order_entity'], array('status' => 'processing', 'grand_total' => $updateTotal->row_incld_tax, 'subtotal' => $updateTotal->row_total,'total_qty_ordered' => $updateTotal->qty_ordered, 'tax_amount' => $updateTotal->tax_amount, 'updated_at' => $dateTime), array('entity_id' => $order_id));
		$updateKOTEntity			= _DB_update($this->tables['kot_entity'], array('status' => 'processing', 'qty_ordered' => $kotTotal->qty_ordered, 'Updated_at' => $dateTime), array('entity_id' => $kot_id));
		
		$this->data['kot_details']	= $this->order_model->kot_details($kot_id,1);
					
		$updateOrder				= _DB_update($this->tables['order_entity_items'], array('is_kot' => 1, 'updated_at' => $dateTime), array('order_id' => $order_id));

		$updateKOT					= _DB_update($this->tables['kot_entity_items'], array('is_kot' => 1, 'updated_at' => $dateTime), array('kot_id' => $kot_id));


		
		if ($updateOrder && $updateOrderEntity && $updateKOT ) {
			$this->data['order_id']	= $order_id;
			//$this->data['kot_details']	= $this->order_model->kot_details($kot_id);
			$this->render('ajax/print_kot');
		}
	}
	}

	#function to get refresh kot section
	function refresh_kot() {
		$kotId							= $this->input->post('kot_id', true);
		if ($kotId) {
			$this->data['kot_details']	= $this->order_model->kot_details($kotId);
			$this->render('ajax/kot_details');
		}
	}
	
	#function for compleate order before close from waiter
	function compleate_order() {
		$orderId						= $this->input->post('order_id', true);
		if ($orderId) {
			_DB_delete($this->tables['order_entity_items'], array('order_id' => $orderId, 'is_kot' => 0));
			$getKot						= _DB_get_record($this->tables['kot_entity'], array('order_id' => $orderId));
			_DB_delete($this->tables['kot_entity_items'], array('kot_id' => $getKot['entity_id'], 'is_kot' => 0));
			$this->data['order_detail']	= _DB_get_record($this->tables['order_entity'], array('entity_id' => $orderId));
			$this->data['order_item']	= _DB_data($this->tables['order_entity_items'], array('order_id' => $orderId, 'is_kot' => 1));	
			$this->render('ajax/bill-confirm');
		}
	}
	
	function create_bill($orderId = NULL){ 
		$dateTime						= date('Y-m-d H:i:s');
		/*$order_id						= $this->input->post('order_id', true);
		$updateOrderEntity				= _DB_update($this->tables['order_entity'], array('status' => 'closed', 'updated_at' => $dateTime), array('entity_id' => $order_id));
		$this->data['order_id']			= $order_id;
		$this->data['bill_details']		= $this->order_model->bill_details($order_id);
		$this->render('ajax/print_bill');*/
		if ($orderId) {
			$checkOrder					= _DB_get_record($this->tables['order_entity'], array('entity_id' => $orderId,'is_bill' => 1));
			if (!empty($checkOrder)) {
				$user 					= $this->ion_auth->user()->row();
				$orderDetails			= $checkOrder;
				$orderitems				= _DB_data($this->tables['order_entity_items'], array('order_id' => $orderId));
				
				$getMaxOrderId			= $this->order_model->max_increment_id('bill_entity');
			if ($getMaxOrderId->increment_id) {
			 	$incrementId			= $getMaxOrderId->increment_id+1;
			 } else {
			 	$incrementId			= 10001;
			 }
				$insertBill				= _DB_insert($this->tables['bill_entity'], array('status' => 'closed', 'order_id' => $orderId, 'user_id' => $user->id, 'increment_id' => $incrementId, 'grand_total' => $orderDetails['grand_total'], 'subtotal' => $orderDetails['subtotal'], 'tax_amount' => $orderDetails['tax_amount'], 'tax_percent' => $orderDetails['tax_percent'], 'total_paid' => $orderDetails['grand_total'], 'discount_amount' => $orderDetails['discount_amount'], 'delivery_charge' => $orderDetails['delivery_charge'], 'total_qty_ordered' => $orderDetails['total_qty_ordered'], 'created_at' => $dateTime, ' 	updated_at' => $dateTime));
				if ($insertBill) {
					$billId				= _DB_insert_id();		
					foreach ($orderitems as $items) {
						_DB_insert($this->tables['bill_entity_items'], array('bill_id' => $billId, 'menu_id' => $items['menu_id'], 'order_type' => $items['order_type'], 'name' => $items['name'], 'qty_ordered' => $items['qty_ordered'],  'name' => $items['name'], 'price' => $items['price'], 'tax_percent' => $items['tax_percent'], 'tax_amount' => $items['tax_amount'], 'row_total' => $items['row_total'], 'price_incld_tax' => $items['price_incld_tax'], 'row_total_incld_tax' => $items['row_total_incld_tax'], 'created_at' => $dateTime, 'updated_at' => $dateTime));
					}
					_DB_update($this->tables['order_entity'], array('status' => 'closed', 'is_bill' => 2, 'total_paid' => $orderDetails['grand_total']), array('entity_id' => $orderId));
					$this->data['order_id']			= $orderId;
					$this->data['bill_id']			= $billId;
					$this->render('invoice');
				} else {
					$this->session->set_flashdata('message', "Oops! Something went wrong. Try again later.");
					$this->session->set_flashdata('message_type', 'danger');
					redirect('manage/index', 'refresh');
				}
			} else {
			$this->session->set_flashdata('message', "Oops! Something went wrong. Try again later.");
			$this->session->set_flashdata('message_type', 'danger');
			redirect('manage/index', 'refresh');
			}
		} else {
			$this->session->set_flashdata('message', "Oops! Something went wrong. Try again later.");
			$this->session->set_flashdata('message_type', 'danger');
			redirect('manage/index', 'refresh');
		}


	}
	
	#manage processing and pending orders
	function manage_pending_order($orderId = NULL){
	if ($orderId) { 
		$loggedUser 				= $this->ion_auth->user()->row();
		$getOrder					= _DB_get_record($this->tables['order_entity'], array('entity_id' => $orderId));
		$tableId					= $getOrder['table_id'];
		$orderType					= '';
		$this->data['table_id']		= $tableId;
		$this->data['order_type']	= $orderType;
		$this->data['order_id']		= $orderId;
		$this->data['get_order']	= _DB_get_record($this->tables['order_entity'], array('entity_id' => $orderId));
		$this->data['order_items']	= _DB_data($this->tables['order_entity_items'], array('order_id' => $orderId), null, null, null );
		$this->data['get_kot']		= $kotDtil	= _DB_get_record($this->tables['kot_entity'], array('order_id' => $orderId));
		$this->data['kot_details']	= _DB_data($this->tables['kot_entity_items'], array('kot_id' => $kotDtil['entity_id']), null, null, null );
		$this->data['pending_kot']	= _DB_get_count($this->tables['kot_entity_items'], array('kot_id' => $kotDtil['entity_id'], 'is_kot' => 0)); 
		if ($tableId) {	
			$checkOrder				= $this->order_model->check_table($tableId, $loggedUser->id);
			if (!empty($checkOrder) && ($checkOrder->user_id !== $loggedUser->id) && ($tableId == $checkOrder->table_id)) {
				$this->session->set_flashdata('message', "Oops! Something went wrong. Try again later.");
				$this->session->set_flashdata('message_type', 'danger');
				redirect('manage/index', 'refresh');
			} else {
				//check whether pending order is there or what
				$this->data['table_detail']		= _DB_get_record($this->tables['table_details'], array('id' => $tableId));
				$this->data['processing_odr']	= $this->order_model->processing_orders($loggedUser->id, $tableId);
				$this->data['menu_category']	= _DB_data($this->tables['category_entity'], array('status' => 1));
				$this->data['menu_details']		= $this->order_model->get_active_menus();
				$this->data['price_cat_dtil']	= _DB_data($this->tables['menu_entity_price_type'], array('status' => 1));
				$this->render('order-desk');
			}
		} else {
			$this->session->set_flashdata('message', "Oops! Something went wrong. Try again later.");
			$this->session->set_flashdata('message_type', 'danger');
			redirect('manage/index', 'refresh');
		}
	
		
	} else {
		$this->session->set_flashdata('message', "Oops! Something went wrong. Try again later.");
		$this->session->set_flashdata('message_type', 'danger');
		redirect('manage/index', 'refresh');
	}

	}
	
	#compleate an order 
	function close_order($orderId	= NULL) {
		$dateTime					= date('Y-m-d H:i:s');
		if ($orderId) {
			$getOrder				= _DB_get_record($this->tables['order_entity'], array('entity_id' => $orderId, 'is_bill' => 0));
			$getOrderItem			= _DB_data($this->tables['order_entity_items'], array('order_id' => $orderId, 'is_kot' => 1));
			#check whether system tax is there or not. if it is there then add that percent to grand total
			$systemTax				= 	_DB_get_record($this->tables['system_config'], array('config_code' => 'system-tax'));
			if (!empty($systemTax) && $systemTax['value'] != 1) {
				$system_tax_per		= _DB_get_record($this->tables['tax_entity'], array('entity_id' => $systemTax['value']));
				if ($system_tax_per['tax_rate'] > 0) {
				$percentAmount		= ($system_tax_per['tax_rate']/100)*$getOrder['grand_total'];
				} else {
					$percentAmount	= 0;
				}
				$tax_percent		= $system_tax_per['tax_rate'];
				$grand_total		= $getOrder['grand_total']+$percentAmount;
			} else {
				$grand_total		= $getOrder['grand_total'];
				$tax_percent		= 0;
				
				$percentAmount		= $getOrder['tax_amount'];
			}
			if (!empty($getOrder) && !empty($getOrderItem)) { 
				$updateOrder		= _DB_update($this->tables['order_entity'], array('status' => 'complete' , 'is_bill' => 1, 'grand_total' => $grand_total, 'tax_amount' => $percentAmount, 'tax_percent' => $tax_percent,  'updated_at' => $dateTime), array('entity_id' => $orderId));
				$updateKot			= _DB_update($this->tables['kot_entity'], array('status' => 'complete', 'Updated_at' => $dateTime), array('order_id' => $orderId));
				if ($updateOrder && $updateKot) {
					$this->session->set_flashdata('message', "ORDER ".$getOrder['increment_id']." has been compleated");
					$this->session->set_flashdata('message_type', 'success');
				} else {
					$this->session->set_flashdata('message', "Oops! Something went wrong. Try again later.");
					$this->session->set_flashdata('message_type', 'danger');
				}
				redirect('manage/index', 'refresh');
			} else {
				$this->session->set_flashdata('message', "Oops! Something went wrong. Try again later.");
				$this->session->set_flashdata('message_type', 'danger');
				redirect('manage/index', 'refresh');
			}
		}
	}
	
	
	#report start
	#bill report
	function bill_report() {
		$this->render('bill-report');
	}

	# Display list of completed previous bills 
	function previous_bill($value='') {
		$this->data['completed_odr_cashier']	= $this->order_model->completed_odr_cashier();
		$this->render('previous_bills');

		
	}

	# Display completed previous bills 
	function show_bill($orderId = NULL) {
		$this->data['order_id']					= $orderId;		
		$this->render('invoice');
	}
	
	#ajax call for list bill report
	function bill_report_by_date() {
		 $periodStart							= $this->input->post('periodstart', true);
		 $periodEnd								= $this->input->post('periodend', true);
		 $period								= $this->input->post('period', true);
		 $reportDetails							= $this->order_model->bill_report($periodStart, $periodEnd, $period);
		 $totalBill								= $this->order_model->total_bill_report($periodStart, $periodEnd);
		$this->data['report_details']			= $reportDetails;
		$this->data['total_bill']				= $totalBill;
		$this->render('ajax/bill-report');
	}	

	#tax report
	function tax_report() {
		$this->render('tax-report');
	}
	#ajax call for list tax reports
	function tax_report_by_date() {
		 $periodStart							= $this->input->post('periodstart', true);
		 $periodEnd								= $this->input->post('periodend', true);
		 $period								= $this->input->post('period', true);
		 $reportDetails							= $this->order_model->tax_report($periodStart, $periodEnd, $period);
		 $totalTax								= $this->order_model->total_tax_report($periodStart, $periodEnd);
		$this->data['report_details']			= $reportDetails;
		$this->data['total_tax']				= $totalTax;
		$this->render('ajax/tax-report');
	}	

	#order report
	function order_report() {
		$this->render('order-report');
	}
	#ajax call for list order reports
	function order_report_by_date() {
		 $periodStart							= $this->input->post('periodstart', true);
		 $periodEnd								= $this->input->post('periodend', true);
		 $period								= $this->input->post('period', true);
		 $reportDetails							= $this->order_model->order_report($periodStart, $periodEnd, $period);
		 $totalOrder							= $this->order_model->total_order_report($periodStart, $periodEnd);
		 // echo "<pre>";
		 // print_r($reportDetails);
		 // die();
		$this->data['report_details']			= $reportDetails;
		$this->data['total_order']				= $totalOrder;
		$this->render('ajax/order-report');
	}

        
        #parcel order report
	function parcel_order_report() {
		$this->render('parcel-order-report');
	}
	#ajax call for list parcel order reports
	function parcel_order_report_by_date() {
		 $periodStart							= $this->input->post('periodstart', true);
		 $periodEnd								= $this->input->post('periodend', true);
		 $period								= $this->input->post('period', true);
		 $reportDetails							= $this->order_model->parcel_order_report($periodStart, $periodEnd, $period);
		 $totalParcelOrder						= $this->order_model->total_parcel_order_report($periodStart, $periodEnd);
		 // echo "<pre>";
		 // print_r($reportDetails);
		 // print_r($totalParcelOrder);

		 // die();
		$this->data['report_details']			= $reportDetails;
		$this->data['total_parcel_order']		= $totalParcelOrder;
		$this->render('ajax/parcel-order-report');
	}
        
        #table order report
	function table_order_report() {
		$this->render('table-order-report');
	}
	#ajax call for list table order reports
	function table_order_report_by_date() {
		 $periodStart							= $this->input->post('periodstart', true);
		 $periodEnd								= $this->input->post('periodend', true);
		 $period								= $this->input->post('period', true);
		 $reportDetails							= $this->order_model->table_order_report($periodStart, $periodEnd, $period);
		 $totalTableOrder						= $this->order_model->total_table_order_report($periodStart, $periodEnd);
		 // echo "<pre>";
		 // print_r($reportDetails);
		 // die();
		$this->data['report_details']			= $reportDetails;
		$this->data['total_table_order']		= $totalTableOrder;
		$this->render('ajax/table-order-report');
	}

	#sales report
	function sales_report() {
		$this->render('sales-report');
	}
	#ajax call for list sales reports
	function sales_report_by_date() {
		 $periodStart							= $this->input->post('periodstart', true);
		 $periodEnd								= $this->input->post('periodend', true);
		 $period								= $this->input->post('period', true);
		 $reportDetails							= $this->order_model->sales_report($periodStart, $periodEnd, $period);
		 $totalSales							= $this->order_model->total_sales_report($periodStart, $periodEnd);

		$salesReportArray = array();

		foreach ($reportDetails['sales_names'] as $row){	
			$salesReportArray[$row['name']] 					=	array();
			foreach ($reportDetails['sales_dates'] as $rowDate) {
				foreach ($reportDetails['sales_report'] as $rowReport) {
					if ($rowDate['datetime'] == $rowReport['datetime'] && $row['name'] == $rowReport['name']) {
						$salesReportArray[$row['name']][$rowDate['datetime']]	=   $rowReport['row_total_bill_items'];
					} 
				}
			}

			foreach ($reportDetails['sales_row_total'] as $rowTotal) {
				if ($row['name'] == $rowTotal['name']) {

					$salesReportArray[$row['name']]['total'] 			=	$rowTotal['row_total'];
				}
			}
				
		} 
		
		foreach ($reportDetails['sales_dates'] as $rowDate) {
			foreach ($reportDetails['sales_tax'] as $rowTax) {
				if ($rowDate['datetime'] == $rowTax['datetime']) {
					$salesReportArray['sales_tax'][$rowDate['datetime']] 			=	$rowTax['tax_amount_bill_items'];							
				}
			}
		}

		$salesReportArray['sales_tax']['total'] 			=	$totalSales->tax_amount_bill_items;		

		foreach ($reportDetails['sales_dates'] as $rowDate) {
			foreach ($reportDetails['sales_total'] as $rowTotal) {
				foreach ($reportDetails['sales_tax'] as $rowTax) {
					if (($rowDate['datetime'] == $rowTotal['datetime']) && ($rowDate['datetime'] == $rowTax['datetime'])) {
						$salesReportArray['Total'][$rowDate['datetime']] 			=	$rowTotal['row_total_bill_items'] + $rowTax['tax_amount_bill_items'];							
					}
				}
			}

		}
		$salesReportArray['Total']['total'] 			=	$totalSales->grand_total_bill_items;

		$reportDetails['sales_report'] = $salesReportArray;

		// echo "<pre>";
		// print_r($reportDetails);
		// die();
				
		$this->data['report_details']			= $reportDetails;
		$this->data['total_sales_order']		= $totalSales;
		$this->render('ajax/sales-report');
	}
	
	
	#ajax function for manage sytem tax
	function manage_system_tax() {
		 $taxClass								= $this->input->post('tax_class', true);
		 if ($taxClass) {
			 $updateTax							= _DB_update($this->tables['system_config'], array('value' => $taxClass), array('config_code' => 'system-tax'));
			 if ($updateTax) {
				 echo "Tax class has been updated successfully";
			 } else {
			 	echo "Ooops! Something went wrong try again later.";
			 }
		}
	}
}