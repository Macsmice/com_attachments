<?php
class ComFilesModelImages extends KModelDefault
{
	protected $_file_path;
	protected $_base_path;
	protected $_original;
	protected $_original_info;
	protected $_src;
	
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

		$this->_state
// 			->insert('width',	'int',		$this->_original_info[0])
// 			->insert('height',	'int',		$this->_original_info[1])
// 			->insert('mime',	'string',	$this->_original_info['mime'])
			->insert('width',	'int')
			->insert('height',	'int')
			->insert('mime',	'string')
		;
		
		$this->_setImage();		
	}

	protected function _initialize(KConfig $config)
    {
    	$config->append(array(
    		'file_path' 	=> 'media/com_files/raw/',
    		'base_path' 	=> JPATH_SITE.'/'
    	))->append(array(
    		'original_info'	=> getimagesize($config->base_path.$config->file_path.$config->original),
    	));
	  	
    	parent::_initialize($config);
    }
	
	/**
	 * Creates the image resource from its original
	 * 
	 * return void
	 */
	protected function _setImage()
	{
		$original = JUri::root().$this->_file_path.$this->_original;

		switch($this->_original_info['mime']) {
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
	public function displayToBrowser($destroy=false)
	{
		$function = $this->getImageMethod((string) $this->_state->mime);
		
		$mime = (string) $this->_state->mime;
		header("Content-type:$mime");
		$function($this->_src);
		
		if($destroy) {
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
		$file = $config->path.$config->name.$this->_getExt($this->_state->mime);

		// Save the image
		$method = $this->getImageMethod($this->_state->mime);
		if($method($this->_src, $this->_base_path.$file)) {
		
			if($config->destroy) {
				$this->destroy();
			}
			
			return JUri::root().$file;
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
	public function displayToTag(boolean $destroy)
	{
		// Get the methodname
		$function = $this->getImageMethod($this->_state->mime);
		
		// Output the data to buffer
		ob_start();
		
		header("Content-type:{$this->_state->mime}");

		$function($this->_src);
		
		$src = ob_get_contents();
		ob_end_clean();
		
		// Encode the data
		$base64_data = base64_encode($src);
		
		$html = '';
		$html .= '<img src="data:'.$this->_state->mime.';base64,{'.$base64_data.'}" />';
		
		echo $html;
		
		if($destroy) {
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
			default:
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
		$coords = $this->_getCoordinates();
				
		$canvas = imagecreatetruecolor($coords['dst_w'], $coords['dst_h']);
		imagecopyresized(
			$canvas, 
			$this->_src, 
			$coords['dst_x'], 
			$coords['dst_y'], 
			$coords['src_x'], 
			$coords['src_y'], 
			$coords['dst_w'], 
			$coords['dst_h'], 
			$coords['src_w'], 
			$coords['src_h']
			);
		$this->_src = $canvas;
		
		return $this;
	}

	public function getProportion($width, $height)
	{
		return $width / $height;
	}
	
	public Function getOrientation($proportion)
	{
		switch($proportion) {
			case $proportion>1:
				return 'landscape';
			case $proportion<1:
				return 'portrait';
			case $proportion==1:
			default:
				return 'square';
		}
	}
	
	protected function _getCoordinates()
	{
		$dst_w = $this->_state->width;
		$dst_h = $this->_state->height;
		$src_w = $this->_original_info[0];
		$src_h = $this->_original_info[1];
				
		$dst_x = 0;
		$dst_y = 0;
		$src_x = 0;
		$src_y = 0;

		$dst_prop = $this->getProportion($dst_w, $dst_h);
		$src_prop = $this->getProportion($src_w, $src_h);
		
				
		// Image is wider, 
		// calculate the height of the image before cropping
		if($dst_prop > $src_prop) {

			$src_crop_h = $src_w/$dst_prop;
			$src_cutoff = $src_h - $src_crop_h;			
			$src_h = floor($src_crop_h);
			
			// Crop center vertically
			if($this->getOrientation($src_prop) == 'landscape') {
				$src_y = floor($src_cutoff/2);
			}
			
			// Crop 25% from top
			// This can be parametrized
			if($this->getOrientation($src_prop) == 'portrait') {
				$src_y = floor($src_cutoff/4);
			}
		}
		
		// Image is heigher, calculate the width of the image before cropping
		if($dst_prop < $src_prop) {
		
			$src_crop_w = $src_h*$dst_prop;
			$src_cutoff = $src_w - $src_crop_w;			
			
			// Crop center horizontally
			$src_x = floor($src_cutoff/2);
			$src_w = floor($src_crop_w);
		}		
		
		return array('dst_x'=>$dst_x, 'dst_y'=>$dst_y, 'src_x'=>$src_x, 'src_y'=>$src_y, 'dst_w'=>$dst_w, 'dst_h'=>$dst_h, 'src_w'=>$src_w, 'src_h'=>$src_h);
	}
	
	public function calculateHeight($dst_w)
	{
		$src_w = $this->_original_info[0];
		$src_h = $this->_original_info[1];

		return $dst_w / $src_w * $src_h;
	}
	
	public function calculateWidth($dst_h)
	{
		$src_w = $this->_original_info[0];
		$src_h = $this->_original_info[1];
		
		return $dst_h / $src_h * $src_w;
	}
}