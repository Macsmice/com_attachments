<?php
class ComFilesModelImages extends KModelState
{
	protected $_original;
	protected $_src;
	protected $_info;
	protected $_file_path;
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
	 * Displays the image directly to the browser
	 * 
	 * return void
	 */
	public function displayToBrowser($config = array())
	{
		$config = new KConfig($config);
		$config->append(array(
			'destroy'	=> false,
			'mime_type'		=> $this->_state->mime
		));

		$function = $this->getImageMethod($config->mime_type);
		
		header("Content-type:$config->mime_type");
		$function($this->_src);
		
		if($config->destroy) {
			$this->destroy();
		}

	}
	
	/**
	 * Save the image to disk
	 * 
	 * return mixed Image path on success, false on failure
	 */
	public function save($config = array())
	{
		// Retrieve the original filename
		$parts = explode('/',$this->_original);
		$filename = JFile::stripExt($parts[count($parts)-1]);
		
		$config = new KConfig($config);
		$config->append(array(
			'destroy'	=> true,
			'mime_type'	=> $this->_state->mime,
			'path'		=> 'media/com_files/tmp/',
			'name'		=> $filename
		));
		
		// Define the file path and name
		$file = $config->path.$config->name.$this->_getExt($config->mime_type);

		// Save the image
		$method = $this->getImageMethod($config->mime_type);
		if($method($this->_src, $this->_base_path.$file)) {
		
			if($config->destroy) {
				$this->destroy();
			}
			
			return JUri::root().'/'.$file;
		}
		else {
		
			if($config->destroy) {
				$this->destroy();
			}
			
			return false;
		}
	}
	
	
	/**
	 * Renders the HTML image tag for output
	 * 
	 * return HTML
	 */
	public function displayToTag($config = array())
	{
		$config = new KConfig($config);
		$config->append(array(
			'destroy'	=> false,
			'mime_type'		=> $this->_state->mime
		));

		// Get the methodname
		$function = $this->getImageMethod($config->mime_type);
		
		// Output the data to buffer
		ob_start();
		
		header("Content-type:$config->mime_type");

		$function($this->_src);
		
		$src = ob_get_contents();
		ob_end_clean();
		
		// Encode the data
		$base64_data = base64_encode($src);
		
		$html = '';
		$html .= '<img src="data:'.$config->mime_type.';base64,{'.$base64_data.'}" />';
		
		echo $html;
		
		if($config->destroy) {
			$this->destroy();
		}
	}
	
	/*
	 * Returns the correct image extension
	 * 
	 * return string
	 */
	protected function _getExt($content_type)
	{
		switch($content_type) {
			case 'image/jpeg':
				$ext = '.jpg';
				break;
			case 'image/png':
				$ext = '.png';
				break;
			case 'image/gif':
				$ext = '.gif';
				break;
		}
		
		return $ext;
	}
	
	/*
	 * Returns the correct image method name
	 * 
	 * return string
	 */
	protected function getImageMethod($content_type)
	{
		switch($content_type) {
			case 'image/jpeg':
				$method = 'imagejpeg';
				break;
			case 'image/png':
				$method = 'imagepng';
				break;
			case 'image/gif':
				$method = 'imagegif';
				break;
		}
				
		return $method;
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