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
 * @author		Babs Gosgens <babsgosgens@gmail.com>
 * @category	Koowa
 * @package     Koowa_Database
 * @subpackage 	Behavior
 */
class ComAttachmentsDatabaseBehaviorUploadable extends KDatabaseBehaviorAbstract
{
	/**
	 * Separator character / string to use for replacing non alphabetic
	 * characters in generated filename. Default is '-'.
	 *
	 * @var	string
	 */
	protected $_files;
	protected $_file_path;
	protected $_max_size;
	protected $_allowed_mime;
	protected $_separator;
	protected $_filename;
	protected $_store;
	protected $_store_full_path;

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
    		'file_path' 		=> 'tmp/',
    		'max_size'		=> 4194304,
	    	'allowed_mime' 		=> array(
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
    		'separator' 		=> '-',
    		'filename'  		=> '',
		'store'			=> true,
    		'store_full_path'	=> true
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

		$methods = parent::getMixableMethods($mixer);
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
		//exit;
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
		//exit;
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
		$filename = $this->_createFilename($context);
		//$filename = JFile::makeSafe($file['name']);

		// Create the directory if it doesn't yet exist
		$filepath = JPATH_SITE.'/'.$this->_file_path;
		if(!JFolder::exists($filepath)) {
			JFolder::create($filepath);
		}

		// Set write permissions
		JPath::setPermissions($filepath);

		// Move the file
		JFile::copy($file->get('tmp_name'), $filepath.$filename);
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

		//Create the filter
		$filter = KService::get('koowa:filter.filename', $config);
		return $filter;
	}

	/**
	 * Create the actual filename
	 *
	 * @return string The sanitized filename
	 */
	protected function _createFilename(KCommandContext $context)
	{
		//Create the slug filter
		$column    = $context->current_fileindex;
		$file	   = $this->_files->get($column);
		$filename  = $this->_filename ? $this->_filename : $file->get('name');
		$filepath  = JPATH_SITE.'/'.$this->_file_path;

		// Seperate the file name and the extension
		$extension = JFile::getExt($filename);
		$filename  = JFile::stripExt($filename);

		$filename  = $this->_createFilter($context)->sanitize($filename);

		// Check for duplicate file names and rename by prepending a number
		if(JFile::exists($filepath.$filename.'.'.$extension)) {
			$i = 1;
			while(JFile::exists($filepath.$filename.'-'.$i.$extension)) {
				$i++;
			}
			$filename = $filename.'-'.$i;
		}

		// Append the correct extension
		$filename = $filename.'.'.$extension;

		// Store the filepath into the database
		$row = $context->data;
		if($this->_store && isset($row->$column)) {
			$row->$column = $filename;
		}

		return $filename;
	}
}