<?php
class ComAttachmentsModelImages extends KModelDefault
{
	protected $_base_path;
	protected $_file_path;
	protected $_original;
	protected $_original_info;
	protected $_src;
	protected $_state;

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
			->insert('width',	'int')
			->insert('height',	'int')
			->insert('mime',	'string')
		;

		$this->_setImage();
	}

	protected function _initialize(KConfig $config)
    {
    	$config->append(array(
    		'file_path' 	=> 'tmp/',
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
		$original = $this->_base_path.$this->_file_path.$this->_original;

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
			'mime'	=> $this->_state->mime ? $this->_state->mime : $this->_original_info['mime'],
			'path'		=> 'tmp/',
			'name'		=> $filename
		));

        var_dump($config);

		// Define the file path and name
        $file = $config->path.$config->name.$this->_getExt($config->mime);

        // Make sure the path is writable
        if(!JFolder::exists($config->path)) {
            JFolder::create($config->path);
        }

		// Save the image

		/*
		 * To do:
		 *
		 * if the destination size and mime are equal to the original, than don't save the image and just return the original
		 */
		$method = $this->getImageMethod($config->mime);

		switch($config->mime)
		{
			case 'image/png':
				$compression = 0;
				$saved = $method($this->_src, $this->_base_path.$file, $compression);
				break;
			case 'image/jpg':
			case 'image/jpeg':
				$quality = 100;
				$saved = $method($this->_src, $this->_base_path.$file, $quality);
				break;
			case 'image/gif':
			default:
				$saved = $method($this->_src, $this->_base_path.$file);
				break;

		}

		if($saved) {

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
	public function displayToTag($config = array())
	{

		$config = new KConfig($config);
		$config->append(array(
			'destroy'	=> true,
			'mime'	=> $this->_state->mime ? $this->_state->mime : $this->_original_info['mime'],
		));

		// Get the methodname
		$mime = $this->_state->mime ? $this->_state->mime : $this->_original_info['mime'];
		$function = $this->getImageMethod($config->mime);

		// Output the data to buffer
		ob_start();
		header("Content-type:{$config->mime}");
		$function($this->_src);
		$src = ob_get_contents();
		ob_end_clean();

		if($config->destroy) {
			$this->destroy();
		}

		// Encode the data
		$base64_data = base64_encode($src);

		$html = '';
		$html .= '<img src="data:'.$config->mime.';base64,{'.$base64_data.'}" />';

		echo $html;
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

		 return $this;
	}

	public function resize($config = array())
	{
		$coords = $this->_getCoordinates();

		// Don't do anything if there's no resizing needed
		if($coords['dst_w']==$coords['src_w'] && $coords['dst_h']==$coords['src_h']) {
			return $this;
		}

		$canvas = imagecreatetruecolor($coords['dst_w'], $coords['dst_h']);

		// Retain transparency for gif and png images, adapted from: http://mediumexposure.com/smart-image-resizing-while-preserving-transparency-php-and-gd-library/
		if($this->_original_info['mime'] == 'image/gif' || $this->_original_info['mime'] == 'image/png') {

			$transparancy_idx = imagecolortransparent($this->_src);

			if($this->_original_info['mime'] == 'image/png') {
				imagealphablending($canvas, false);
				$transparancy_clr = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
				imagefill($canvas, 0, 0, imagecolorallocatealpha($canvas, 0, 0, 0, 127));
				imagesavealpha($canvas, true);
			}
			else {
        		$transparancy_clr    = imagecolorsforindex($this->_src, $transparancy_idx);
		        $transparancy_idx    = imagecolorallocate($canvas, $transparancy_clr['red'], $transparancy_clr['green'], $transparancy_clr['blue']);
				imagefill($canvas, 0, 0, $transparancy_idx);
				imagecolortransparent($canvas, $transparancy_idx);
			}
		}

		imagecopyresampled(
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

		// Fallback for missing destination width
		if(!$dst_w && $dst_h) {
			$dst_w = floor($this->calculateWidth($dst_h));
		}

		// Fallback for missing destination height
		if($dst_w && !$dst_h) {
			$dst_h = floor($this->calculateHeight($dst_w));
		}

		// If no width or height don't set any crop coordinates and return original size;
		if(!$dst_w && !$dst_h) {
			return array('dst_x'=>0, 'dst_y'=>0, 'src_x'=>0, 'src_y'=>0, 'dst_w'=>$src_w, 'dst_h'=>$src_h, 'src_w'=>$src_w, 'src_h'=>$src_h);
		}

		$dst_x = 0;
		$dst_y = 0;
		$src_x = 0;
		$src_y = 0;

		$dst_prop = $this->getProportion($dst_w, $dst_h);
		$src_prop = $this->getProportion($src_w, $src_h);

		// Image is wider, calculate the height of the image before cropping
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

    public function delete($path)
    {
         if( JFile::exists($path) ) {
            JFile::delete($path);
        }
    }
}