require(['jquery','mage/url'], function ($,urlBuilder) {
    $k = $.noConflict();
    $k(document).ready(function () {
        $k(".arp-products-custome-block .item.product-item")
                .mouseover(function () {
                   $k( this ).find( ".amquickview-hover" ).show();
                })
                .mouseout(function () {
                    $k( this ).find( ".amquickview-hover" ).hide();
            });
        $k(".amquickview-link").click(function() {
            var rule_id = $k(this).attr('data-rule-id');
            $k.ajax({
                url: urlBuilder.build('advancerelated/catalog/quickViews/rule_id/' + rule_id),
                type: "GET"
            });
        });
        $k(".arp-products-custome-block .action.tocart.primary").click(function() {
            var rule_id = $k(this).attr('data-rule-id');
            $k.ajax({
                url: urlBuilder.build('advancerelated/catalog/clicksUser/rule_id/' + rule_id),
                type: "GET"
            });
        });
    });
});