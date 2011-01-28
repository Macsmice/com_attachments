<?php
class ComFilesModelImages extends KModelState
{
	protected $_original;
	protected $_src;
	protected $_info;
	protected $_base_path;
	
	public function __construct(KConfig $config)
	{
		// Check if the GD Library is installed
		if (!function_exists('gd_info')) {
		    return false;
		}
		parent::__construct($config);
		
		foreach($config as $key => $value) {
			$this->{'_'.$key} = $value;
		}

		$this->_setImage($config);
		
		$this->_info = getimagesize($this->_base_path.$this->_original);
		
		$this->_state->append(array(
			'mime' => 'image/jpeg'
		));
	}

	protected function _initialize(KConfig $config)
    {
    	$config->append(array(
    		'original'   		=> '',
    		'file_path' 	=> 'media/com_files/raw/',
    		'base_path' 	=> JPATH_SITE.'/'
    	));
	  	
    	parent::_initialize($config);
    }
	
	protected function _setImage(KConfig $config)
	{
		$original	= $this->_base_path.$this->_original;
		$this->_info = getimagesize($this->_base_path.$this->_original);
		
		switch($this->_info['mime'])
		{
			case 'image/gif':
				$this->_src = imagecreatefromgif($original);
				break;
			case 'image/png':
				$this->_src = imagecreatefrompng($original);
				break;
			case 'image/jpg':
			case 'image/jpeg':
				$this->_src = imagecreatefromjpeg($original);
				break;
		}
	}
	
	/**
	 * Displays the image
	 * 
	 */
	public function display($config = array())
	{
		$config = new KConfig($config);
		$config->append(array(
			'destroy'	=> false,
			'mime'		=> $this->_state->mime
		));

		$mime = explode('/', $this->_state->mime);
		$mime = $mime[1];
		$function = "image$mime";
		
		header("Content-type:$config->mime");
		$function($this->_src);
		
		if($config->destroy) {
			$this->destroy();
		}

	}
	
	/**
	 * Gets the image
	 * 
	 */
	public function getImage()
	{
		return $this->_src;
	}
	
	/**
	 * Destroy the image to save the memory. Do this after all operations are complete.
	 * 
	 * return void
	 */
	public function destroy() {
		 imagedestroy($this->_src);
	}
	
		
	public function resize($config = array())
	{
		$config = new KConfig($config);
		$config->append(array(
			'width'		=> 50,
			'height'	=> 50,
		));
				
		$dst_x = 0;
		$dst_y = 0;
		$src_x = 0;
		$src_y = 0;
		$dst_w = $config->width;
		$dst_h = $config->height;
		$src_w = $this->_info[0];
		$src_h = $this->_info[0];
		
		$canvas = imagecreatetruecolor($config->width, $config->height);
		imagecopyresampled($canvas, $this->_src, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h);
		$this->_src = $canvas;
		
		return $this;
	}
}