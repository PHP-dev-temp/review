console.log("cwr loaded");
(function ($) {
    $(document).ready(function () {

        // Stars click event
        $('.stars>span').on('click', function(){
            $(this).text('★').prevAll('span').text('★');
            $(this).nextAll('span').text('☆');
            $(this).parent().find('input').val($(this).data('star'));
        });

        // Ajax save review
        $("#save-review").one("click", function(){

            $.ajax({
                type: 'POST',
                url: $('.review').data('ajaxurl'),
                data: {
                    action: 'save_review',
                    rating: $('#rev_rating').val(),
                    key: $('#rev_key').val(),
                    product_id: $('#rev_product_id').val(),
                    image_id: $('#rev_image_id').val(),
                    rev_title: $('#rev_title').val(),
                    rev_content: $('#review-summary').val(),
                    product_name: $('#rev_product_name').val()
                }
            }).done(function (result) {
                var url = $('#save-review').data('permalink');
                $('.review').find('.inner-content').html(result);
                setTimeout(function(){
                    window.location.href = url;
                }, 1000);
            });

        });
    });
})(jQuery);