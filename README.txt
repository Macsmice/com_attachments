Example usage:

KService::get('com://admin/attachments.model.images', array('original'=>$row->logo, 'file_path'=>'media/com_files/raw/'))
    ->set('width',200)
    ->resize()
    ->save(array('path'=>'media/com_files/raw/display/'));
    ;

Saves a proportionally sized image (width 200px) in the directory media/com_files/raw/display/ under the original's file name


KService::get('com://admin/attachments.model.images', array('original'=>$row->logo, 'file_path'=>'media/com_files/raw/'))
    ->set('width',200)
    ->set('height',200)
    ->set('mime','image/jpg')
    ->resize()
    ->save(array('path'=>'media/com_files/raw/display/', 'name'=>'mycroppedimage'));
    ;

Saves a proportionally sized and cropped jpeg image (200px x 200px) in the directory media/com_files/raw/display/ under the name mycroppedimage.jpg


KService::get('com://admin/attachments.model.images', array('original'=>$row->logo, 'file_path'=>'media/com_files/raw/'))
    ->set('width',200)
    ->set('height',200)
    ->set('mime','image/jpg')
    ->resize()
    ->displayToBrowser();
    ;

Outputs a proportionally sized and cropped jpeg image (200px x 200px) tothe browser (for whatever it's worth). This method is alpha and not yet tested, so no guarantees ;)