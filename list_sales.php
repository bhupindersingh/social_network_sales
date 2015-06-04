<?php 
date_default_timezone_set('America/Los_Angeles');
global $wpdb;
?>
<div id="message_str"></div>
<table cellspacing="0" class="wp-list-table widefat fixed posts tablesorter">
	<thead>
	<tr>
		<th style="width:300px;" class="manage-column column-order_actions" id="order_actions" scope="col">Product Name</th>
        <th style="width:200px;" class="manage-column column-order_actions" id="order_actions" scope="col">Facebook Sale</th>
        <th style="width:200px;" class="manage-column column-order_actions" id="order_actions" scope="col">Twitter Sale</th>
        <th style="width:200px;" class="manage-column column-order_actions" id="order_actions" scope="col">Direct Sale</th>
    </tr>
	</thead>
	<tbody id="the-list">
    	<?php 			
		$cnt=0;
		$message_str=$class='';	
		$cnt_counter=0;	
		foreach ($product_data['products'] as $key=>$data):
			$qty_cnt=0;
			if($cnt %2 ==0){
				$class = "alternate";
			}
			else{
				$class = "";
			}
			?>
				<tr valign="top" class="type-shop_order status-publish <?php echo $class;?>">
				<td class="order_status column-order_status"><?php echo $data['name'];?></td>
                <td class="order_title column-order_title"><?php echo $data['facebook_cnt'];?></td>                
                <td class="product_name column-order_total"><?php echo $data['twitter_cnt'];?></td>
                <td class="order_actions column-order_actions"><?php echo $data['direct_cnt'];?></td>                
        	</tr>			
           <?php $cnt++;endforeach;?>
           <?php if($cnt==0):?>
           <tr><td colspan="6" style="color:#C30;">Sorry, No record found!</td></tr>
           <?php endif;?>
		</tbody>
</table>