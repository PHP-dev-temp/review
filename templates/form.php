<?php
// Review form
$is_product=false;
if ($results[0]['product_id']){
    $is_product = true;
    $product = new WC_Product($results[0]['product_id']);
    $image = $product->get_image();
}
?>
<div class="review" data-ajaxurl="<?php echo admin_url('admin-ajax.php');?>">
    <div id="start-popup" class="hide-popup table-outer">
        <div class="table-inner">
            <div class="table-content inner-content">
                <div class="popup-title"><?php echo ($is_product)?$results[0]['product_name']:'Review';?>
                </div>
                <div class="popup-image"><?php echo ($is_product)?$image:'';?></div>
                <div class="stars">
                    <span data-star="1">★</span>
                    <span data-star="2">★</span>
                    <span data-star="3">★</span>
                    <span data-star="4">★</span>
                    <span data-star="5">★</span>
                    <input type="hidden" name="rating" id="rev_rating" value="5" />
                </div>
                <div class="review-summary">
                    <textarea name="review-summary" id="review-summary" maxlength="120" placeholder="Write your review here..." /></textarea>
                </div>
                <div class="buttons">
                    <div class="button-popup" id="save-review" data-permalink="<?php echo home_url(); ?>#send-bundle">Save review</div>
                </div>
                <input type="hidden" name="key" id="rev_key" value="<?php echo $key;?>" />
                <input type="hidden" name="title" id="rev_title" value="<?php echo $results[0]['customer_name'];?>" />
                <input type="hidden" name="product_name" id="rev_product_name" value="<?php echo ($is_product)?$results[0]['product_name']:'';?>" />
                <input type="hidden" name="product_id" id="rev_product_id" value="<?php echo $results[0]['product_id'];?>" />
                <input type="hidden" name="image_id" id="rev_image_id" value="<?php echo ($is_product)?get_post_thumbnail_id($results[0]['product_id']):''; ?>" />
            </div>
        </div>
    </div>
</div>
