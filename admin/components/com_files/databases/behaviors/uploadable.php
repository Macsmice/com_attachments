<?php
/**
 * @version 	$Id: uploadable.php $
 * @category	Koowa
 * @package		Koowa_Database
 * @subpackage 	Behavior
 * @copyright	Copyright (C) 2007 - 2010 Johan Janssens. All rights reserved.
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 */

/**
 * Database Uploadable Behavior
 *
 * @author		Babs Gšsgens <babsgosgens@gmail.com>
 * @category	Koowa
 * @package     Koowa_Database
 * @subpackage 	Behavior
 */
class ComFilesDatabaseBehaviorUploadable extends KDatabaseBehaviorAbstract
{
	/**
	 * Separator character / string to use for replacing non alphabetic
	 * characters in generated filename. Default is '-'.
	 *
	 * @var	string
	 */
	protected $_separator;

	/**
	 * Constructor.
	 *
	 * @param 	object 	An optional KConfig object with configuration options
	 */
	public function __construct( KConfig $config = null)
	{
		parent::__construct($config);

		foreach($config as $key => $value) {
			$this->{'_'.$key} = $value;
		}		
	}

	/**
     * Initializes the options for the object
     *
     * Called from {@link __construct()} as a first step of object instantiation.
     *
     * @param 	object 	An optional KConfig object with configuration options
     * @return void
     */
	protected function _initialize(KConfig $config)
    {
    	/*
    	 * TO DO
    	 * 
    	 * Get (some of) these values through component's parameters
    	 */
    	$config->append(array(
    		'files'   		=> KRequest::get('files', 'raw'),
    		'file_path' 	=> 'media/com_files/raw/',
    		'max_size'		=> 4194304,
    		'allowed_mime' 	=> array(
				    			'image/jpeg', 
				    			'image/jpg', 
				    			'image/png', 
				    			'image/gif', 
				    			'application/pdf',
    							'application/zip', 
				    			'application/msword', 
				    			'application/vnd.ms-excel', 
				    			'application/octet-stream'
    							),
    		'separator' 	=> '-',
    		'filename'  	=> '',
			'store'			=> true
    	));

    	parent::_initialize($config);
    }

	/**
	 * Get the methods that are available for mixin based
	 *
	 * This function conditionaly mixes the behavior. Only if the mixer
	 * has a 'filename' property the behavior will be mixed in.
	 *
	 * @param object The mixer requesting the mixable methods.
	 * @return array An array of methods
	 */
	public function getMixableMethods(KObject $mixer = null)
	{
		$methods	= array();
		$column		= $this->_column;
		
		if(isset($mixer->$column)) {
			$methods = parent::getMixableMethods($mixer);
		}

		return $methods;
	}

	/**
	 * Update the filename
	 *
	 * @return void
	 */
	protected function _beforeTableUpdate(KCommandContext $context)
	{
		foreach($this->_files AS $column => $file)
		{
			if(isset($file['size']) && $file['size'] > 0) {
				$context->append(array('current_fileindex'=>$column));
				$this->_moveFile($context);
			}
			else {
				continue;
			}
		}
	}
	
	protected function _beforeTableInsert(KCommandContext $context)
	{
		foreach($this->_files AS $column => $file)
		{
			if(isset($file['size']) && $file['size'] > 0) {
				$context->append(array('current_fileindex'=>$column));
				$this->_moveFile($context);
			}
			else {
				continue;
			}
		}
	}
	
	
	/**
	 * Move the file
	 * 
	 * @param KCommandContext $context
	 * 
	 * @return true
	 */
	protected function _moveFile($context)
	{
		jimport('joomla.filesystem.file');
		jimport('joomla.filesystem.folder');
		
		$file = $this->_files->get($context->current_fileindex);
		var_dump($file);

		// Check if the uploaded file's mime type matches one of the allowed types
		if(!in_array($file['type'], $this->_allowed_mime->toArray())) {
			// Return error
			return false;
		}

		// Check if the uploaded file's size is within the maximum allowed filesize
		if($file['size'] > $this->_max_size) {
			// Return error
			return false;
		}
		
		// Sanitize the file name
		$sanitized = $this->_createFilename($context);
		
		// Create the directory if it doesn't yet exist
		$filepath = JPATH_SITE.'/'.$this->_file_path;
		JFolder::create($filepath);
		
		// Move the file
		JFile::copy($file->get('tmp_name'), $filepath.$sanitized);
	}
	
	/**
	 * Create a sluggable filter
	 *
	 * @return void
	 */
	protected function _createFilter(KCommandContext $context)
	{
		$config = array();
		$config['separator'] = $this->_separator;
		$column = $context->current_fileindex;
				
		// Make sure the filename fits the column's character length if it needs to be stored into a database
		if($this->_store) {
			$config['length'] = $context->caller->getColumn($column)->length;
		}
		
		//Create the filter
		$filter = KFactory::tmp('lib.koowa.filter.filename', $config);
		return $filter;
	}
	
	/**
	 * Create the actual filename
	 *
	 * @return string The sanitized filename
	 */
	protected function _createFilename(KCommandContext $context)
	{
		$row   = $context->data;
		
		//Create the slug filter
		$column   = $context->current_fileindex;
		$filter   = $this->_createFilter($context);
		$file	  = $this->_files->get($column);
		$filename = $this->_filename ? $this->_filename : $file->get('name');
		$filepath = JPATH_SITE.'/'.$this->_file_path;
		
		$sanitized = $filter->sanitize($file['name']);

		// Check for duplicate file names and rename by prepending a number
		$i=null;
		if (JFile::exists($filepath.$sanitized)) {
			$i=0; 
			do { $sanitized = $i++.'-'.$sanitized; } while (JFile::exists($filepath.$sanitized));	
		}
		
		// Store the filepath into the database
		if($this->_store && isset($row->$column)) {
			$row->$column = $sanitized;
		}
		
		return $sanitized;
	}
}