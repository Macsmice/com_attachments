<?php
class ComFilesModelImages extends KModelState
{
	public function __construct(KConfig $config)
	{
		parent::__construct($config);
		
		// Set the state
		foreach($config as $key => $value) {
			$this->_state->insert($key, $value);
		}
		
		var_dump($this->_state->image_file);
	}

	protected function _initialize(KConfig $config)
    {
    	$config->append(array(
    		'image_file'   		=> KRequest::get('files', 'raw'),
    		'file_path'			=> '',
    		'remove_original' 	=> true,
    		'updateable' 		=> true,
    		'allowed_mimetypes'	=> array('image/jpeg', 'image/jpg', 'image/png', 'image/gif'),
    		'sizes' 			=> array(),
    		'file_length' 			=> null,
    		'unique'			=> null
	  	));
	  	
    	parent::_initialize($config);
    }
	
	public function resize()
	{
		var_dump($this->_state->image_file);
		break;
	}
	
	public function getItem()
	{
  		echo $file = JPATH_BASE.$this->_state->image_file;
  		
		switch ($config->original['mime']) {
			case 'image/gif';
				$clone = imagecreatefromgif($file);
				break;
			case 'image/jpeg';
			case 'image/pjpeg';
				$clone = imagecreatefromjpeg($file);
				break;
			case 'image/png';
				$clone = imagecreatefrompng($file);
				break;
		}
		
		$canvas = imagecreatetruecolor( $config->target['width'], $config->target['height'] );
		
		// Fill the new image with the original image
		if(imagecopyresampled($canvas, $clone, ceil($config->coords[0]), ceil($config->coords[1]), ceil($config->coords[2]), ceil($config->coords[3]), floor($config->target['width']), floor($config->target['height']), $config->original['width'], $config->original['height'])) {
			return $canvas;
		}
		
	}
}