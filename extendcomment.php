<?php
/*
Plugin Name: Extend Comment
Version: 1.0
Plugin URI: 
Description: 
Author: ExitDev3
Author URI: 
*/

add_filter('comment_form_default_fields','custom_fields');
function custom_fields($fields) {
		$commenter = wp_get_current_commenter();
		$req = get_option( 'require_name_email' );
		$aria_req = ( $req ? " aria-required='true'" : '' );

		$fields[ 'author' ] = '
		<div class="specialist-form__top">
		<div class="specialist-form__form-name  ">'.
			( $req ? '<span class="required">*</span>' : '' ).
			'<input id="author" name="author" class="first__row-form" type="text" placeholder="שם"' . $aria_req . ' />
			<div class="specialist-form__img-wrap">
                                <img src="https://insolar.exit-tech.com/wp-content/themes/insolar-theme/assets/images/form__input-icon1.svg" alt="">
                            </div>
			</div>';
		$fields[ 'email' ] = '';
		$fields[ 'url' ] = '';
		$fields[ 'phone' ] = '<div class="specialist-form__form-tel  ">'.
			'<input id="phone" name="phone" class="phone__number" required type="text" placeholder="טלפון"/>
			<div class="specialist-form__img-wrap">
                                <img src="https://insolar.exit-tech.com/wp-content/themes/insolar-theme/assets/images/form__input-icon2.svg" alt="">
                            </div>
			</div></div>';
	return $fields;
}

// Add fields after default fields above the comment box, always visible

add_action( 'comment_form_logged_in_after', 'additional_fields' );
add_action( 'comment_form_after_fields', 'additional_fields' );



function additional_fields () {
	echo '<div dir="rtl" class="comment-form-rating"><span>';
	$arr = array( 5, 4, 3, 2, 1);
		foreach ($arr as $value) {
	echo '
	<input class="rating__comment" type="radio" name="rating" id="' . $value . '" value="' . $value . '"/><label id="' . $value . '" for="' . $value . '"></label>
	';
	}
	echo'</span></div>';

}

// Save the comment meta data along with comment

add_action( 'comment_post', 'save_comment_meta_data' );
function save_comment_meta_data( $comment_id ) {
	if ( ( isset( $_POST['phone'] ) ) && ( $_POST['phone'] != '') )
	$phone = wp_filter_nohtml_kses($_POST['phone']);
	add_comment_meta( $comment_id, 'phone', $phone );

	if ( ( isset( $_POST['rating'] ) ) && ( $_POST['rating'] != '') )
	$rating = wp_filter_nohtml_kses($_POST['rating']);
	add_comment_meta( $comment_id, 'rating', $rating );
}


// Add the filter to check if the comment meta data has been filled or not

add_filter( 'preprocess_comment', 'verify_comment_meta_data' );
function verify_comment_meta_data( $commentdata ) {
	if ( ! isset( $_POST['rating'] ) )
	wp_die( __( 'Error: You did not add your rating. Hit the BACK button of your Web browser and resubmit your comment with rating.' ) );
	return $commentdata;
}

//Add an edit option in comment edit screen  

add_action( 'add_meta_boxes_comment', 'extend_comment_add_meta_box' );
function extend_comment_add_meta_box() {
    add_meta_box( 'title', __( 'Comment Metadata - Extend Comment' ), 'extend_comment_meta_box', 'comment', 'normal', 'high' );
}
 
function extend_comment_meta_box ( $comment ) {
    $phone = get_comment_meta( $comment->comment_ID, 'phone', true );
    $title = get_comment_meta( $comment->comment_ID, 'title', true );
    $rating = get_comment_meta( $comment->comment_ID, 'rating', true );
    wp_nonce_field( 'extend_comment_update', 'extend_comment_update', false );
    ?>
    <p>
        <label for="phone"><?php _e( 'Phone' ); ?></label>
        <input type="text" name="phone" value="<?php echo esc_attr( $phone ); ?>" class="widefat" />
    </p>

    <p>
        <label for="rating"><?php _e( 'Rating: ' ); ?></label>
			<span class="commentratingbox">
			<?php for( $i=1; $i <= 5; $i++ ) {
				echo '<span class="commentrating"><input type="radio" name="rating" id="rating" value="'. $i .'"';
				if ( $rating == $i ) echo ' checked="checked"';
				echo ' />'. $i .' </span>'; 
				}
			?>
			</span>
    </p>
    <?php
}

// Update comment meta data from comment edit screen 

add_action( 'edit_comment', 'extend_comment_edit_metafields' );
function extend_comment_edit_metafields( $comment_id ) {
    if( ! isset( $_POST['extend_comment_update'] ) || ! wp_verify_nonce( $_POST['extend_comment_update'], 'extend_comment_update' ) ) return;

	if ( ( isset( $_POST['phone'] ) ) && ( $_POST['phone'] != '') ) : 
	$phone = wp_filter_nohtml_kses($_POST['phone']);
	update_comment_meta( $comment_id, 'phone', $phone );
	else :
	delete_comment_meta( $comment_id, 'phone');
	endif;


	if ( ( isset( $_POST['rating'] ) ) && ( $_POST['rating'] != '') ):
	$rating = wp_filter_nohtml_kses($_POST['rating']);
	update_comment_meta( $comment_id, 'rating', $rating );
	else :
	delete_comment_meta( $comment_id, 'rating');
	endif;
}

// Add the comment meta (saved earlier) to the comment text 
// You can also output the comment meta values directly in comments template  

add_filter( 'comment_text', 'modify_comment');
function modify_comment( $text ){

	$plugin_url_path = WP_PLUGIN_URL;

	if( $commenttitle = get_comment_meta( get_comment_ID(), 'title', true ) ) {
		$commenttitle = '<strong>' . esc_attr( $commenttitle ) . '</strong><br/>';
		$text = $commenttitle . $text;
	} 

	if( $commentrating = get_comment_meta( get_comment_ID(), 'rating', true ) ) {
		$commentrating = '<div class="comment-rating">	<img src="'. $plugin_url_path .
		'/extendcomment/images/'. $commentrating . 'star.png"/></div>' ;
		$text = $text . $commentrating;
	
		return $text;		
	} 
	else {
		return $text;		
	}	

}

function ci_comment_rating_get_average_ratings( $id ) {
	$comments = get_approved_comments( $id );
	if ( $comments ) {
		$i = 0;
		$total = 0;
		foreach( $comments as $comment ){
			$rate = get_comment_meta( $comment->comment_ID, 'rating', true );
			if( isset( $rate ) && '' !== $rate ) {
				$i++;
				$total += $rate;
			}
		}
		if ( 0 === $i ) {
			return false;
		} else {
			return round( $total / $i, 1 );
		}
	} else {
		return false;
	}
}

add_filter( 'the_title', 'ci_comment_rating_display_average_rating' );
function ci_comment_rating_display_average_rating( $content ) {
	global $post;
	if ( false === ci_comment_rating_get_average_ratings( $post->ID ) ) {
		return $content;
	}
	// $stars   = '';
	$average = ci_comment_rating_get_average_ratings( $post->ID );

	for ( $i = 1; $i <= $average + 1; $i++ ) {

		if ( 0 === $average ) {
			continue;
		}
		$plugin_url_path = WP_PLUGIN_URL;

	}
	$averageNew =  number_format(floor($average));

	$custom_content  = '<div class="average-rating"><img src="'. $plugin_url_path .'/extendcomment/images/'. $averageNew . '-star.png"/><span class="person__rait"><strong>'. $averageNew  . '</strong>'. '/5</span></div>';
	$custom_content .= $content;
	return $custom_content;
}