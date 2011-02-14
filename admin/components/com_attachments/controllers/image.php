<?php
class ComAttachmentsControllerImage extends ComDefaultControllerDefault 
{ 

	public function __construct(KConfig $config)
	{
		parent::__construct($config);

		foreach($config as $key => $value) {
			$this->set("imageConfig[$key]", $value);
		}
		
		$this->set('imageConfig', $config);
		
//		var_dump($this);
	}
	
	protected function _initialize(KConfig $config)
    {
    	$config->append(array(
    		'files'   		=> KRequest::get('files', 'raw'),
    		'fields'  		=> '',
    		'file_path'		=> '',
    		'keep_original' => true,
    		'separator' 	=> '-',
    		'updateable' 	=> true,
    		'resizeable'	=> array('image/jpeg', 'image/jpg', 'image/png', 'image/png'),
    		'sizes' 		=> array(),
    		'length' 		=> null,
    		'unique'		=> null
	  	));

    	parent::_initialize($config);
    }
	
    public function resize()
    {
    	if(!empty($this->_files)) {
    		echo 'yes';
    		$this->getModel()->resize();
    	}
    	else {
    		//return;
    	}
    	
    	var_dump($this->imageConfig);
    	var_dump(KRequest::get('files', 'raw'));
    	exit;
    }
}