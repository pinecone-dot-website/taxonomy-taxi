<?php
/*
*	builds nested drop down selects in wp-admin/edit.php
*	Version: .56
*/
class Walker_Taxo_Taxi extends Walker{
	
	public $db_fields = array( 'parent' => 'parent', 'id' => 'term_id' );

	public function start_el( &$output, $category, $depth ){
		
		if( $category->taxonomy == 'post_format' )
			$output .= '<option value="'.$category->slug.'" '.$category->selected.'>'.
						str_repeat("&nbsp;&nbsp;&nbsp;&nbsp;", $depth).' '.ucwords(substr($category->name, 12)).' '.
						'</option>';
		else
			$output .= '<option value="'.$category->slug.'" '.$category->selected.'>'.
						str_repeat("&nbsp;&nbsp;&nbsp;&nbsp;", $depth).' '.$category->name.' '.
						'</option>';
	}
}