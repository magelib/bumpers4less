require([
    "jquery",
], function ($) {
//<![CDATA[ 
    /*$(document).ready(function () {
        
         function depenedField(blockPage){
            console.log(blockPage);
            if(blockPage == ''){
                $('.displayblock').hide();
                $('.block_page_product_tree').show();  
            }
            if(blockPage == 1){
                $('.displayblock').hide();
                $('.block_position_product').show();
                $('.block_page_product_tree').show();  
            }
            if(blockPage == 2){
                $('.displayblock').hide();
                $('.block_position_shoppingcart').show();  
                $('.block_page_product_tree').show(); 
            }
            if(blockPage == 3){
                $('.displayblock').hide();
                $('.block_position_category').show();  
                $('.block_page_category_tree').show();
            }
            if(blockPage == 4){
                $('.displayblock').hide();  
                $('.block_page_product_tree').show(); 
            }
            if(blockPage == 5){
                $('.displayblock').hide();
                $('.block_position_cms').show();
                $('.block_page_cms_tree').show(); 
            }
        }
        function depenedFieldTwo(blockLayout) {
            if(blockLayout == 1){
                $('.number-of-rows').closest( "fieldset" ).show();  
            }else{
                $('.number-of-rows').closest( "fieldset" ).hide();  
            }
            if(blockLayout == 'undefined'){
                $('.number-of-rows').closest( "fieldset" ).hide();  
            }
        }
        
        $(document).on('change','.block_page_depend select',function() {
            var blockPage = $(this).val();
            depenedField(blockPage);     
        });
        $(document).on('change','.blocklayout select',function() {
            var blockrowLayout = $(this).val();
            if(blockrowLayout == 1){
                $('.number-of-rows').closest( "fieldset" ).show();  
            }else{
                $('.number-of-rows').closest( "fieldset" ).hide();  
            }
        });
        setTimeout(function(){
            depenedField($('.block_page_depend select').val());  
            depenedFieldTwo($('.blocklayout select').val()); 
        }, 5000);        
    }); */
//]]>
});