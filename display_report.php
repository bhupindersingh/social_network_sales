<link rel="stylesheet" href="//code.jquery.com/ui/1.10.4/themes/smoothness/jquery-ui.css">
<script src="//code.jquery.com/jquery-1.10.2.js"></script>
<script src="//code.jquery.com/ui/1.10.4/jquery-ui.js"></script>
<script type="text/javascript" src="<?php echo WP_PLUGIN_URL.'/social_network_sales/js/jquery.print.js';?>"></script>
<script src="<?php echo WP_PLUGIN_URL.'/social_network_sales/js/jquery.tablesorter.min.js';?>"></script>
<script src="<?php echo WP_PLUGIN_URL.'/social_network_sales/js/jquery.tablesorter.widgets.min.js';?>"></script>
<link rel="stylesheet" href="<?php echo WP_PLUGIN_URL.'/social_network_sales/css/style.css';?>">
<style>
@media only print
{	
	body {
    font-family: "Open Sans",sans-serif;
    font-size: 13px;
    line-height: 1.4em;
	}
	#message_str {
    background: none repeat scroll 0 0 #FFFFFF;
    border-left: 4px solid #007700;
    box-shadow: 0 1px 1px 0 rgba(0, 0, 0, 0.1);
    color: #007700;
    display: none;
    padding: 5px !important;
	}
	.wp-list-table{
	border: 1px solid #E5E5E5; 
	border-spacing: 0;
    clear: both;
    margin: 0;
    width: 100%;
	}
	.wp-list-table * {
    	word-wrap: break-word;
	}
	.widefat thead tr th{color: #333333;border-bottom: 1px solid #E1E1E1;}
	.widefat td, .widefat th {
		border: 1px solid #000;
		color: #555555;
	}
	.widefat td.shirt_style{
		width:220px!important;
		overflow: hidden;		
	    white-space: nowrap;
	}
	.alternate, .alt {
    	background-color: #F9F9F9;
	}
}
</style>
<div class="wrap">
	<div class="error"></div>
    <?php    echo "<h2>" . __( 'Social Network Sales Options', 'woo-nav-tab-wrapper' ) . "</h2>"; ?>
    <form id="social_sales_form" name="social_sales_form" method="post" action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI']); ?>" onsubmit="javascript:return validate_form(this);">              
        <table class="form-table">
			<tbody>
            	<tr valign="top">
					<th class="titledesc" scope="row">
					<label for="selDataBy">Pull Data By:</label>
                    </th>
                    <td class="forminp-select">
                    	<select id="selDateBy" name="selDataBy" onchange="javascript:show_options(this);">
                            <option value="">---Select Option---</option>
                        	<option value="date_range">Date Range</option>
                            <option value="period">Period</option>                            
                        </select>
                    </td>
                 </tr>
         	</tbody>
         </table>
         
         <table class="date_range" style="display:none;">
			<tbody>
                 <tr>
                 	<th class="titledesc" scope="row" style="width:29.5%">&nbsp;</th>
                    <td>
						<label for="txtStartDate">Start Date</label>&nbsp;<input type="text" id="from" name="txtStartDate" value=""/>&nbsp;
                        <label for="txtEndDate">End Date</label>&nbsp;<input type="text" id="to" name="txtEndDate" value=""/>
                    </td>
                 </tr>
             </tbody>
         </table> 
         
         <table class="period" style="display:none;width:438px;">
			<tbody>
                 <tr>
                 	<th class="titledesc" scope="row" style="width:50%">&nbsp;</th>
                    <td>
						<select id="period" name="period">
                        	<option value="">---Select Period---</option>
                            <option value="today">Today</option>
                            <option value="yesterday">Yesterday</option>
                            <option value="this-week">This Week</option>
                            <option value="last-week">Last Week</option>
                        	<option value="this-month">This Month</option>
                            <option value="last-month">Last Month</option>                            
                        </select>
                    </td>
                 </tr>
             </tbody>
         </table> 
         <div class="loader"><img src="<?php echo WP_PLUGIN_URL.'/social_network_sales/images/ajax-loader.gif';?>"/></div>
         <table class="product" width="968px">
			<tbody>
                <tr>
                 	<th class="titledesc" scope="row" style="text-align:left;"><label for="all_product">Select All Products</label></th>
                    <td>
						<input type="checkbox" name="all_products" id="all_products" value="1" onclick="javascript:dis_products(this);"/>
                    </td>
                </tr>
                <tr>
                 	<th class="titledesc" scope="row" style="text-align:left;"><label for="product">Choose Product:</label></th>
                    <td>
						<select id="product" name="product[]" multiple="multiple">
                        	<option value="">---Select Product---</option> 
                            <?php echo get_all_order_products();?>                      	                      
                        </select>                      
                    </td>
                </tr>                
             </tbody>
         </table>           
        <p class="submit">
   	    <input type="button" name="display_records" value="<?php _e('Display Records', 'woo_trdom' ) ?>" onclick="javascript:show_all_sales();"/>
        <input type="button" id="print_records" name="print_records" value="<?php _e('Print Records', 'woo_trdom' ) ?>" style="display:none;"/>
        </p>
    </form>
</div>
<div id="orders"></div>
<script language="javascript">
jQuery(document).ready(function(){
	jQuery("#selDateBy").prop('selectedIndex', 0);	
	 	jQuery( "#from" ).datepicker({
			dateFormat: "yy-mm-dd", 
			defaultDate: "+1w",
			changeMonth: true,
			numberOfMonths: 2,
			onClose: function( selectedDate ) {
				jQuery( "#to" ).datepicker( "option", "minDate", selectedDate );
			}
		});
		jQuery( "#to" ).datepicker({
			dateFormat: "yy-mm-dd", 	
			defaultDate: "+1w",
			changeMonth: true,
			maxDate: "+1w",
			numberOfMonths: 2,
			onClose: function( selectedDate ) {
				jQuery( "#from" ).datepicker( "option", "maxDate", selectedDate );
			}
	});	
	jQuery("#print_records").click(function() {		
		jQuery("#orders").print();
		return (false);
	});
});
function validate_form(obj){
	if(jQuery("#product").val()==""){
		jQuery(".error").html("Please select a product from list.");
		jQuery(".error").show();
		return false;	
	}
}
function show_options(obj){
	if(obj.value==""){
		jQuery(".date_range").hide();
		jQuery(".period").hide();
		jQuery("#period").prop('selectedIndex', 0);
		jQuery("#from").val('');jQuery("#to").val('');
	}
	if(obj.value=="date_range"){
		jQuery(".date_range").show();
		jQuery(".period").hide();
		jQuery("#period").prop('selectedIndex', 0);
	}
	if(obj.value=="period"){
		jQuery(".period").show();
		jQuery(".date_range").hide();
		jQuery("#from").val('');jQuery("#to").val('');
	}
}
function dis_products(obj){
	if(jQuery(obj).is(':checked')){			
		jQuery('#product > option').each(function () {
			if(jQuery(this).val() != ''){
            	jQuery(this).attr("selected", "selected");								
			}				
        });		
	}	
}
function show_all_sales(){
	jQuery.ajax({
	  type:'POST',
	  data:{action:'show_all_sales',form:jQuery("#social_sales_form").serialize()},
	  url: "<?php echo get_site_url();?>/wp-admin/admin-ajax.php",
	  beforeSend: function( xhr ) {
		  jQuery(".loader").show();
	  },
	  success: function(data) {
		jQuery(".loader").hide();		
		jQuery("#print_records").show();
		jQuery("#orders").html(data);				
	  }
	});	
}
</script>