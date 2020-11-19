<?php

    /*
    PrestaShop DevDocs : 
    https://devdocs.prestashop.com/1.7/modules/
    https://devdocs.prestashop.com/1.7/modules/creation/module-translation/
    */
 
    // The constant test
    if (!defined('_PS_VERSION_')) {
        exit;
    }


    // Main class
    class Tw_Uploader extends Module 
    {
        //const HOOK_RIGHT_COLUMN = 1;
	    //const HOOK_LEFT_COLUMN = 2;
	    //const HOOK_HOME = 3;
                
        // Constructor method
        public function __construct()
        {
            $this->name = 'tw_uploader';
            $this->tab = 'Tools';
            $this->version = '1.0.0';
            $this->author = 'Philippe Urban';
            $this->need_instance = 0;
            $this->ps_versions_compliancy = [
                'min' => '1.7',
                'max' => _PS_VERSION_
            ];
            $this->bootstrap = true;
     
            parent::__construct();
     
            $this->displayName = $this->l('Target PicUp');
            $this->description = $this->trans('Download your picture and enjoy !', [], 'Modules.Twmodule.Tw_module.php');
            $this->confirmUninstall = $this->trans('Are you sure you want to uninstall?', [], 'Modules.Twmodule.Tw_module.php');
     
            if (!Configuration::get('TW_UPLOADER_PAGENAME')) {
                $this->warning = $this->l('No name provided');
            }
        }
        // New translation module interface opt-in
        public function isUsingNewTranslationSystem()
        {
            return true;
        }

        // Install & uninstall methods
        public function install()
        {
            return parent::install()
            && $this->registerHook('displayHeader')
            && $this->registerHook('displayRightColumn')
            && Configuration::updateValue('TW_UPLOADER_IMG', ""); 
        }
       
        public function uninstall()
        {
            return parent::uninstall()
            && Configuration::deleteByName('TW_UPLOADER_IMG');
        }

        // Configuration Display
        public function getContent()
	    {
		    $output = null;

		    if (Tools::isSubmit('submit'.$this->name))
		    {
			   	$TW_UPLOADER_IMG = strval(Tools::getValue('TW_UPLOADER_IMG'));
			    if ($TW_UPLOADER_IMG  && !Validate::isUnixName($TW_UPLOADER_IMG))
				    $output .= $this->displayError($this->l('Invalid image name'));
				
			if (isset($_FILES['TW_UPLOADER_FILE']) && is_uploaded_file($_FILES['TW_UPLOADER_FILE']['tmp_name']))
			{				
				if ($error = ImageManager::validateUpload($_FILES['TW_UPLOADER__FILE'], (Configuration::get('PS_ATTACHMENT_MAXIMUM_SIZE') * 1024 * 1024)))
					$output .= $this->displayError($error);
				
				if (!$output)
				{
					$upload_path = $this->local_path.'views/img/';
					$pathinfo = pathinfo($_FILES['TW_UPLOADER_FILE']['name']);
					do $uniqid = sha1(microtime());
					while (file_exists($upload_path.$uniqid.'.'.$pathinfo['extension']));
					if (!copy($_FILES['TW_UPLOADER_FILE']['tmp_name'], $upload_path.$uniqid.'.'.$pathinfo['extension']))
						$output .= $this->displayError($this->l('File copy failed'));

					@unlink($_FILES['file']['tmp_name']);
					
					if ($TW_UPLOADER_IMG && file_exists($upload_path.$TW_UPLOADER_IMG))
						@unlink($upload_path.$TW_UPLOADER_IMG);
					
					$TW_MODULE_IMG = $uniqid.'.'.$pathinfo['extension'];
				}
			}

			if (!$output)
			{
				Configuration::updateValue('TW_UPLOADER_IMG', $TW_UPLOADER_IMG);
				    $output .= $this->displayConfirmation($this->l('Settings updated'));
			        }
		        }
		        return $output.$this->displayForm();
        }
        
        public function displayForm()
        {
            $default_lang = (int)Configuration::get('PS_LANG_DEFAULT');
            $fields_form[0]['form'] = array(
                'legend' => array(
                    'title' => $this->trans('Settings', [], 'Modules.Twuploader.Tw_uploader.php'),
                ),
                'input' => array(
                    array(
                        'type' => 'file',
                        'label' => $this->l(''),
                        'desc' => $this->trans('Browse and select your file.', [], 'Modules.Twuploader.Tw_uploader.php'),
                        'name' => 'TW_UPLOADER_FILE',
                        'display_image' => true
                    ),
                    array(
                        'type' => 'hidden',
                        'name' => 'TW_UPLOADER_IMG'
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                    'class' => 'button'
                )
            );
    
            $helper = new HelperForm();
            $helper->id = $this->id;
            $helper->module = $this;
            $helper->name_controller = $this->name;
            $helper->identifier = $this->identifier;
            $helper->token = Tools::getAdminTokenLite('AdminModules');
            $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;
            $helper->default_form_language = $default_lang;
            $helper->allow_employee_form_lang = $default_lang;
            $helper->languages = Language::getLanguages();
            foreach ($helper->languages as $k => $language)
                $helper->languages[$k]['is_default'] = (int)($language['id_lang'] == $default_lang);
            $helper->title = $this->displayName;
            $helper->show_toolbar = true;
            $helper->toolbar_scroll = true;
            $helper->submit_action = 'submit'.$this->name;
            $helper->toolbar_btn = array(
                'save' => array(
                    'desc' => $this->l('Save'),
                    'href' => AdminController::$currentIndex.'&configure='.$this->name.'&save'.$this->name.
                    '&token='.Tools::getAdminTokenLite('AdminModules'),
                ),
                'back' => array(
                    'href' => AdminController::$currentIndex.'&token='.Tools::getAdminTokenLite('AdminModules'),
                    'desc' => $this->l('Back to list')
                )
            );
            $TW_UPLOADER_IMG = Configuration::get('TW_UPLOADER_IMG');
            if ($TW_UPLOADER_IMG)
            {
                $helper->fields_value['TW_UPLOADER_IMG'] = $TW_UPLOADER_IMG;
                $pathinfo = pathinfo($TW_UPLOADER_IMG);
                $image = ImageManager::thumbnail($this->local_path.'views/img/'.$TW_UPLOADER_IMG, $TW_UPLOADER_IMG, 150, $pathinfo['extension'], true);
                $helper->fields_value['image'] = ($image) ? $image : false;
                $helper->fields_value['size'] = ($image) ? filesize($this->local_path.'views/img/'.$TW_UPLOADER_IMG) / 1000 : false;
            }
            return $helper->generateForm($fields_form);
        }
}