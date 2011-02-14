<?php
jimport('joomla.filesystem.file');
jimport('joomla.filesystem.folder');
jimport('joomla.filesystem.path');

class ComAttachmentsHelperImage
{   
	
	public function resize($config = array())
  	{
  		$original	= array();
		$target		= array();
		
  		$original['path']		= JPATH_BASE.$config['images_path'].$config['filename'];
  		
		if(!JFile::exists($original['path'])) {
			JError::raiseWarning('', JText::sprintf('There is no image by that name (%s). The image was not resized.', $config['original']));
			return false;
		}
  		
		// Get info of source file
		$image_info				= getimagesize($original['path']);
		$original['path']		= $config['images_path'];
		$original['filename']	= $config['filename'];
		$original['width'] 		= $image_info[0];
		$original['height']		= $image_info[1];
		$original['mime']		= $image_info['mime'];
  		
		// Set target file configuration
		$target['path']			= $config['images_path'];
		$target['filename']		= $config['filename'].'_'.$config['type'];
		$target['mime']			= isset($config['mime']) ? $config['mime'] : $original['mime'];
		
		if (isset($config['width']) && !isset($config['height'])) {
			$target['width']	= $config['width'];
			$target['height']	= floor(($target['width']/$original['width'])*$original['height']);
	  	}
		else if (!isset($config['width']) && isset($config['height'])) {
			$target['height']	= $config['height'];
			$target['width'] 	= floor(($target['height']/$original['height'])*$original['width']);
		}
		else if (isset($config['width']) && isset($config['height'])) {
			$target['width']	= $config['width'];
			$target['height']	= $config['height'];
		}
		
		$config	= $this->_setConfig($original, $target);
		
		if(!$image	= $this->_createImage($config)) {
			JError::raiseWarning('', JText::_('Could not resize the image.'));
			return false;
		}
		
		if(!$this->_storeImage($image, $config)) {
			JError::raiseWarning('', JText::_('The resized image could not be saved.'));
			return false;
		}
		
  		return true;
	}
  
  	protected function _createImage(KConfig $config)
  	{
  		$file = JPATH_BASE.$config->original['file'];
  		
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
  	
  	protected function _storeImage(&$image, $config)
  	{
  		var_dump($image);
		switch ($config->target['mime']) {
			case 'image/gif';
				$mime = 'imagegif'; // Sets the proper GD method
				$ext  = '.gif';
				break;
			case 'image/jpeg';
			case 'image/pjpeg';
				$mime = 'imagejpeg'; // Sets the proper GD method
				$ext  = '.jpg';
				break;
			case 'image/png';
				$mime = 'imagepng'; // Sets the proper GD method
				$ext  = '.png';
				break;
		}
	
		// Save display image
		$file = JPATH_SITE.$config->target['file'].$ext;
	
		if ( JFile::exists($file) && !JFile::delete($file) ) {
			$this->errors[] = JText::_("Could not save the resized image. Please notify the site's system administrator.");
			return false;
		}
		
		if($mime == 'imagejpeg') {
			$saved = $mime($image, $file, 100);
		}
		else {
			$saved = $mime($img_display, $file);
		}
		
		if($saved) {		
			ImageDestroy($image);
		}
		
		return $saved;
  	}
  	
  	protected function _setRatio($config = array())
	{
		return $config['width']/$config['height'];
	}
  
	protected function _setConfig(&$original, &$target)
	{
  		// Calculate proportions
		$original['ratio']	= $this->_setRatio($original);
		$target['ratio']	= $this->_setRatio($target);

		$config = new KConfig();
		$config->append(array(
			'target' 	=> array(
							'file'		=>$target['path'].$target['filename'], 
							'mime'		=>$target['mime'],
							'width'		=>$target['width'], 
							'height'	=>$target['height']
							),
			'original'	=> array(
							'file'		=>$original['path'].$original['filename'], 
							'mime'		=>$original['mime'],
							'width'		=>$original['width'], 
							'height'	=>$original['height']
							),
			'coords'	=> array(0,0,0,0)
		));
		
		// Crop from left and right, center
		if( $target['ratio'] <= $original['ratio'] )
		{
			$config->target['width']	= $target['height']/$original['height']*$original['width'];
			$config->coords[0]			= $target['width']-$target['width'];
			$config->coords[1]			= ($target['height']-$target['height'])/2;
			$config->coords[2]			= $target['width']-$target['width'];
			$config->coords[3]			= ($target['height']-$target['height'])/2;
		}
		
		// Crop from top and bottom';
		else if ($target['ratio'] >= $original['ratio'])
		{
			$config->target['height']	= $target['width']/$original['width']*$original['height'];
			$config->coords[0]			= $target['width']-$target['width'];
			$config->coords[1]			= $target['width']-$target['width'];
			$config->coords[2]			= ($target['height']-$target['height'])/2;
			$config->coords[3]			= ($target['height']-$target['height'])/2;
		}
		
		return $config;
	
//		if ($end_ratio<=$org_ratio) {
//		  // Crop from left and right, center
//		  $height = $end_height;
//		  $end_width;
//		  $width = $end_height/$org_height*$org_width;
//		  $org_x = ($end_width-$width);
//		  $org_y = ($end_height-$height)/2;
//		  $end_x = ($end_width-$width);
//		  $end_y = ($end_height-$height)/2;
//		}
//		else if ($end_ratio>=$org_ratio) {
//		  // Crop from top and bottom';
//		  $width = $end_width;
//		  $height = $end_width/$org_width*$org_height;
//		  $org_x = ($end_width-$width);
//		  $end_x = ($end_width-$width);
//		  $org_y = ($end_height-$height)/2;
//		  $end_y = ($end_height-$height)/2;
//		}
  	
	}
}