<div class="image_manager">
<style type="text/css">
.image_manager {
	position:absolute;
	z-index:10001;
	margin-top:-25px;
	margin-left:20px;
}
.image_manager table {
	min-width:inherit !important;
	width: auto !important;
	border:2px solid #CCC;
	background:#FFF;
}
.image_manager table td {
	border:none;
	vertical-align:middle;
	padding:5px !important;
}
.image_manager table td input[type=text] {
	width:150px !important;
	margin:0px !important;
}
.image_manager table td select {
	width:157px !important;
	margin:0px !important;
}
</style>
 <table class="ui-corner-all" cellpadding="0" cellspacing="0">
  <tfoot>
   <tr>
    <td class="left"><input type="text" id="image_manager_keyword" /></td>
    <td class="center"><a onclick="$('.image_manager').remove();" style="cursor:pointer;font-size:20px;">&times;</a></td>
   </tr>
   <tr>
    <td class="left">
     <select id="image_manager_directory" onchange="$('#image_manager_fixed').removeAttr('checked');">
      <?php foreach ($directories as $directory) { ?>
      <option value="<?php echo $directory; ?>"><?php echo $directory; ?></option>
      <?php } ?>
     </select>
    </td>
    <td class="center"><input id="image_manager_fixed" type="checkbox" title="<?php echo $text_fix; ?>" /></td>
   </tr>
  </tfoot>
 </table>
<script type="text/javascript"><!--
$(document).ready(function() {
	$('#image_manager_keyword').focus().bind('keypress', function(e) {
		if (e.keyCode == 13) { return false; }
		if (e.keyCode == 27) { $('.image_manager').remove(); }
	});
	
	if (image_directory) {
		$('#image_manager_fixed').prop('checked', true);
		$('#image_manager_directory option[value=\'' + image_directory + '\']').prop('selected', true);
	}
	
	$('#image_manager_keyword').autocomplete({
		source: function(request, response) {
			var data = '&directory=' + $('#image_manager_directory').val() + '&keyword=' + encodeURIComponent (request)
			
			xhr = $.ajax({type:'GET', dataType:'json', url:'index.php?route=batch_editor/data/getImageManager&token=' + token, data:data,
				success: function(json) {
					response($.map(json, function(item) {
						return {
							category: '<img style="margin-top:4px;" src="' + item['img'] + '" />',
							label: item['file'],
							value: item['file']
						}
					}));
				}
			});
		},
		'select': function(item) {
			var value = $('#image_manager_directory').val() + item['value'];
			var parent = $('#image_manager_keyword').parents('.image:first');
			
			parent.find('input[data-field=\'image\']').val(value).trigger('change');
			
			if (value) {
				xhr = $.ajax({type:'POST', dataType:'html', data:'image=' + value, url:'index.php?route=batch_editor/tool/imageResize&token=' + token,
					success: function(src) {
						parent.find('img').attr('src', src);
					}
				});
			}
			
			$('.image_manager').remove();
		}
	});
	
	$('#image_manager_fixed').bind('change', function() {
		if ($(this).prop('checked')) {
			image_directory = $('#image_manager_directory').val();
		} else {
			image_directory = false;
		}
	});
});
//--></script>
</div>