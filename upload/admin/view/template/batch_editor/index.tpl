<?php echo $header; ?>
<link type="text/css" rel="stylesheet" href="view/batch_editor/stylesheet/common.css" />
<link type="text/css" rel="stylesheet" href="view/batch_editor/stylesheet/index.css" />
<link type="text/css" rel="stylesheet" href="view/batch_editor/javascript/colorbox/colorbox.css" />

<script type="text/javascript" src="view/batch_editor/javascript/common.js"></script>
<script type="text/javascript" src="view/batch_editor/javascript/colorbox/jquery.colorbox-min.js"></script>

<script type="text/javascript"><!--
var text = '';
var text_no_results = '<?php echo $text_no_results; ?>';
var token = '<?php echo $token; ?>';
var no_image = '<?php echo $no_image; ?>';
var current_filter = 0;
var current_tool = '';
var current_product_id = 'filter';
--></script>
<style type="text/css">
/*<?php foreach ($languages as $language) { ?>*/
.language_<?php echo $language['language_id']; ?> {
	background-image:url('view/image/flags/<?php echo $language["image"]; ?>');
	background-position:top left;
	background-repeat:no-repeat;
}
/*<?php } ?>*/
</style>
<?php echo $column_left; ?>
<div id="content">
 <div class="page-header">
  <div class="container-fluid">
   <div class="pull-right">
    <a onclick="location = '<?php echo $url_setting; ?>';" data-toggle="tooltip" title="<?php echo $button_setting; ?>" class="btn btn-primary"><i class="fa fa-wrench"></i></a>
    <a onclick="clearCache();" data-toggle="tooltip" title="<?php echo $button_clear_cache; ?>" class="btn btn-danger"><i class="fa fa-eraser"></i></a>
    <a onclick="location = '<?php echo $url_cancel; ?>';" data-toggle="tooltip" title="<?php echo $button_cancel; ?>" class="btn btn-default"><i class="fa fa-reply"></i></a>
   </div>
   <h1><?php echo $heading_title; ?></h1>
   <ul class="breadcrumb">
    <?php foreach ($breadcrumbs as $breadcrumb) { ?>
    <li>
    <a href="<?php echo $breadcrumb['href']; ?>"><?php echo $breadcrumb['text']; ?></a>
    </li>
    <?php } ?>
   </ul>
  </div>
 </div>
 <div class="container-fluid">
  <div class="panel panel-default">
   <div class="panel-heading">
    <h3 class="panel-title"><i class="fa fa-list"></i>
     <?php echo $heading_title; ?></h3>
   </div>
   <div class="panel-body">
    <div style="margin-bottom:10px;">
     <label><input id="batch_edit" type="checkbox" /><em class="current_filter"></em></label><br />
     <label><input id="product_update" type="checkbox" /><em><?php echo $text_product_update; ?></em></label>
    </div>
    <ul class="nav nav-tabs">
     <li class="active"><a href="#tab-empty" data-toggle="tab">&nbsp;-&nbsp;</a></li>
     <li><a href="#tab-filter" data-toggle="tab"><?php echo $button_filter; ?></a></li>
     <li><a href="#tab-general" data-toggle="tab"><?php echo $tab_general; ?></a></li>
     <li><a href="#tab-link" data-toggle="tab"><?php echo $tab_link; ?></a></li>
     <li><a href="#tab-tool" data-toggle="tab"><?php echo $tab_tool; ?></a></li>
    </ul>
    <div class="tab-content">
     <!-- start empty -->
     <div class="tab-pane active" id="tab-empty">
      <div class="alert alert-info text-center"><?php echo $notice_html_editor; ?></div>
      <table class="be-list">
       <tfoot>
        <tr>
         <td class="left" width="50%"><a class="btn btn-success btn-sm" onclick="getFormProductAdd();" title="<?php echo $button_insert; ?>" data-toggle="tooltip"><i class="fa fa-plus"></i></a>
          &nbsp;&nbsp;&nbsp;
          <a class="btn btn-danger btn-sm" onclick="productAddCopyDelete('delete');" title="<?php echo $button_remove; ?>" data-toggle="tooltip"><i class="fa fa-trash-o"></i></a></td>
         <td class="right" width="50%"><?php echo $text_quantity_copies; ?>
          <input id="quantity" type="text" value="<?php echo $quantity_copies_products; ?>" size="3" style="vertical-align:middle;" />
          &nbsp;
          <a class="btn btn-primary btn-sm" onclick="productAddCopyDelete('copy');" title="<?php echo $button_copy; ?>" data-toggle="tooltip"><i class="fa fa-copy"></i></a>
         </td>
        </tr>
       </tfoot>
      </table>
     </div>
     <!-- end empty -->
     
     <!-- start filter -->
     <div class="tab-pane" id="tab-filter">
      <form id="form-filter">
       <table class="be-list" style="margin-bottom:0px;">
        <?php if ($filter_product) { ?>
        <thead>
         <tr class="filter">
          <td class="left" colspan="3"><?php echo $tab_general; ?></td>
         </tr>
        </thead>
        <tbody id="table-product">
         <tr class="filter">
          <td class="center" width="20"><a onclick="addField('product');" class="btn btn-success btn-xs" title="<?php echo $button_insert; ?>"><i class="fa fa-plus"></i></a></td>
          <td class="center" colspan="2"></td>
         </tr>
        </tbody>
        <?php } ?>
        <?php if ($filter_product_description) { ?>
        <thead>
         <tr class="filter">
          <td class="left" colspan="3"><?php echo $text_description; ?></td>
         </tr>
        </thead>
        <tbody id="table-product_description">
         <tr class="filter">
          <td class="center" width="20"><a onclick="addField('product_description');" class="btn btn-success btn-xs" title="<?php echo $button_insert; ?>"><i class="fa fa-plus"></i></a></td>
          <td class="center" colspan="2"></td>
         </tr>
        </tbody>
        <?php } ?>
        <?php if ($filter_additional) { ?>
        <thead>
         <tr class="filter">
          <td class="left" colspan="3"><?php echo $text_additional; ?></td>
         </tr>
        </thead>
        <tbody id="table-additional">
         <tr class="filter">
          <td class="center" width="20"><a onclick="addField('additional');" class="btn btn-success btn-xs" title="<?php echo $button_insert; ?>"><i class="fa fa-plus"></i></a></td>
          <td class="center" colspan="2"></td>
         </tr>
        </tbody>
        <?php } ?>
       </table>
      </form>
      <table class="be-list" style="margin-top:0px; border-top:none;">
       <tbody>
        <tr class="filter">
         <td class="left"><?php foreach ($filter_link as $link) { ?>
          <div id="dialog-<?php echo $link; ?>" style="display:none;"></div>
          <a class="filter_link_<?php echo $link; ?>" onclick="getLinkFilter('<?php echo $link; ?>');"><?php echo ${'text_' . $link}; ?></a>
          <?php } ?>
         </td>
         <td class="center" width="15%">
          <div class="btn-group" id="filter_column">
           <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown"> <?php echo $text_column; ?> <b class="label label-success"></b> <span class="caret"></span> </button>
           <ul class="dropdown-menu" onclick="event.stopPropagation();">
            <div class="be-scrollbox">
             <?php $count_column = 0; ?>
             <?php $filter_column = array (); ?>
             <?php foreach ($setting as $field => $data) { ?>
             <?php if ($field != 'description') { ?>
             <div>
              <label>
               <?php if ($field == 'name') { ?>
               <input type="checkbox" name="filter_column[]" value="<?php echo $field; ?>" checked="checked" disabled="disabled" />
               <?php $count_column++; ?>
               <?php $filter_column[] = $field; ?>
               <?php } else { ?>
               <?php if (isset ($data['enable']['product'])) { ?>
               <input type="checkbox" name="filter_column[]" value="<?php echo $field; ?>" checked="checked" />
               <?php $count_column++; ?>
               <?php $filter_column[] = $field; ?>
               <?php } else { ?>
               <input type="checkbox" name="filter_column[]" value="<?php echo $field; ?>" />
               <?php } ?>
               <?php } ?>
               <?php echo ${'text_' . $field}; ?>
              </label>
             </div>
             <?php } ?>
             <?php } ?>
            </div>
           </ul>
          </div>
         </td>
         <td class="center" width="15%">
          <form id="form_filter_language">
           <b><?php echo $text_language_description; ?></b><br />
           <?php foreach ($languages as $language) { ?>
           <?php if ($language['language_id'] == $language_id) { ?>
           <label>
            <input type="radio" name="filter_language_id" value="<?php echo $language['language_id']; ?>" checked="checked" />
           </label>
           <?php } else { ?>
           <label>
            <input type="radio" name="filter_language_id" value="<?php echo $language['language_id']; ?>" />
           </label>
           <?php } ?>
           <img src="view/image/flags/<?php echo $language['image']; ?>" alt="<?php echo $language['name']; ?>" title="<?php echo $language['name']; ?>" />
           <?php } ?>
          </form>
         </td>
         <td class="center" width="15%">
          <form id="form_filter_limit">
           <b><?php echo $text_limit; ?></b><br />
           <select name="filter_limit" onchange="getProduct('&page=1');">
            <?php foreach ($option['limit'] as $limit) { ?>
            <option value="<?php echo $limit; ?>"><?php echo $limit; ?></option>
            <?php } ?>
           </select>
          </form>
         </td>
        </tr>
        <tr class="filter">
         <td class="center" colspan="4">
          <a class="btn btn-primary" onclick="setFilter(0);"><?php echo $button_filter; ?></a>
          <a class="btn btn-danger" onclick="resetFilter();"><?php echo $button_reset; ?></a>
         </td>
        </tr>
       </tbody>
      </table>
    <script type="text/javascript"><!--//
	$(document).ready(function() {
		$('#tab-filter #form-filter').bind('keypress', function(e) {
			if (e.keyCode == 13) { setFilter(0); }
		});
		
		$('form').bind('keypress', function(e) {
			if (e.keyCode == 13) { return false; }
		});
		
		$('#tab-filter #filter_column .dropdown-toggle b').html('<?php echo $count_column; ?>');
		
		$('#tab-filter #filter_column input[type=\'checkbox\']').bind('click', function(e) {
			var count = $('#filter_column input[type=\'checkbox\']:checked').length;
			
			if (count > 0) {
				$('#filter_column .dropdown-toggle b').removeClass('label-danger').addClass('label-success').html(count);
			} else {
				$('#filter_column .dropdown-toggle b').removeClass('label-success').addClass('label-danger').html(count);
			}
		});
	});
	
	function addField(table) {
		var html = '';
		
		html += '<tbody class="temp"><tr class="">';
		html += '<td class="center"><a onclick="$(this).parents(\'tbody\').remove();" class="btn btn-danger btn-xs" title="<?php echo $button_remove; ?>"><i class="fa fa-minus"></i></a></td>';
		html += '<td class="left" width="1%">';
		html += '<select onchange="getField(this);">';
		html += '<option value=""></option>';
		
		if (table == 'product') {
			<!--<?php foreach ($filter_product as $field) { ?>-->
			html += '<option value="product|<?php echo $field; ?>"><?php echo ${"text_" . $field}; ?></option>';
			<!--<?php } ?>-->
		}
		
		if (table == 'product_description') {
			<!--<?php foreach ($filter_product_description as $field) { ?>-->
			html += '<option value="product_description|<?php echo $field; ?>"><?php echo ${"text_" . $field}; ?></option>';
			<!--<?php } ?>-->
		}
		
		if (table == 'additional') {
			<!--<?php foreach ($filter_additional as $table=>$fields) { ?>-->
			<!--<?php foreach ($fields as $field=>$text) { ?>-->
			html += '<option value="<?php echo $table; ?>|<?php echo $field; ?>"><?php echo $text; ?></option>';
			<!--<?php } ?>-->
			<!--<?php } ?>-->
		}
		
		html += '</select>';
		html += '</td>';
		html += '<td class="left"></td>';
		html += '</tr></tbody>';
		
		$('#tab-filter #form-filter #table-' + table + '').before(html);
	}
	
	function getField(this_) {
		var data = $(this_).val();
		var field_box = $(this_).parents('tr').children('td:last');
		
		if (data) {
			xhr = $.ajax({type:'POST', dataType:'html', data:'data=' + data, url:'index.php?route=batch_editor/index/getField&token=' + token,
				beforeSend: function() { field_box.html('<i class="fa fa-spinner fa-spin"></i>'); },
				success: function(html) {
					field_box.html(html);
					creatDateTime();
				}
			});
		} else {
			field_box.html('');
		}
	}
	
	function filter() {
		getProduct('&page=1');
	}
	
	function getLinkFilter(link_) {
		if ($('#dialog-' + link_).html()) {
			$('#dialog-' + link_).modal('show');
		} else {
			var link_box = creatDialog('dialog-' + link_);
			
			xhr = $.ajax({type:'POST', dataType:'html', data:'link=' + link_ + '&product_id=-1', url:'index.php?route=batch_editor/index/getLink&token=' + token,
				beforeSend: function() { creatOverlayLoad(true); },
				success: function(html) {
					link_box.find('.modal-body').html(html);
					link_box.modal('show');
					creatOverlayLoad(false);
				}
			});
		}
	}
	
	function setLinkFilter(link_) {
		$('#dialog-' + link_).modal('hide');
		$('#tab-filter a.filter_link_' + link_).addClass('link_select');
	}
	
	function delLinkFilter(link_) {
		$('#dialog-' + link_).html('').modal('hide');
		$('#tab-filter a.filter_link_' + link_).removeClass('link_select');
	}
	
	function getFilterUrl() {
		var url = 'filter_language_id=' + getLanguageId();
		
		var data = getSelectedUrl(0);
		
		if (data) { url += data; }
		
		$('#filter_column input[type=\'checkbox\']:checked').each(function(index, element) {
			url += '&' + encodeURIComponent($(element).attr('name')) + '=' + encodeURIComponent($(element).val());
		});
		
		data = $('#product_box .pagination b').html();
		
		if (data) { url += '&page=' + data; }
		
		data = $('select[name=\'filter_limit\']').val();
		
		if (data) { url += '&limit=' + data; }
		
		if ($('#product_box .sort a.asc').attr('href')) {
			url += '&sort=' + $('#product_box .sort a.asc').attr('href') + '&order=ASC';
		} else if ($('#product_box .sort a.desc').attr('href')) {
			url += '&sort=' + $('#product_box .sort a.desc').attr('href') + '&order=DESC';
		} else {
			url += '&sort=pd.name&order=ASC';
		}
		
		if (current_filter == 0) {
			data = $('#tab-filter #form-filter').serialize();
			
			if (data) { url += '&' + data; }
			
			<!--<?php foreach ($filter_link as $link) { ?>-->
			data = $('#form-<?php echo $link; ?>-1').serialize();
			
			if (data) { url += '&' + data;}
			<!--<?php } ?>-->
		} else {
			$('#product .filter input').each(function(index, element) {
				var name = $(element).attr('name');
				var value = $(element).val();
				
				if (name && value) {
					url += '&' + name + '=' + encodeURIComponent(value);
				}
			});
			
			$('#product .filter select').each(function(index, element) {
				var name = $(element).attr('name');
				var value = $(element).val();
				
				if (name && value != '*') {
					url += '&' + name + '=' + encodeURIComponent(value);
				}
			});
		}
		
		return url;
	}
	
	function getFilterTextareaRange(this_) {
		var td = $(this_).parents('td:first').next('td:first');
		
		if ($(this_).val() == 'range') {
			td.children('[name*=\'[value]\']').attr('disabled', 'disabled').css({'display':'none'});
			td.children('span').css({'display':'inline'});
			td.find('[name*=\'[value_min]\'], [name*=\'[value_max]\']').removeAttr('disabled');
		} else {
			td.find('[name*=\'[value_min]\'], [name*=\'[value_max]\']').attr('disabled', 'disabled');
			td.children('span').css({'display':'none'});
			td.children('[name*=\'[value]\']').removeAttr('disabled').css({'display':'inline'});
		}
	}
	
	function checkFilterDuplicate(this_) {
		if ($(this_).prop('checked')) {
			$(this_).parents('tbody:first').find('input[type=\'text\'], select, textarea').attr('disabled', 'disabled');
		} else {
			$(this_).parents('tbody:first').find('input[type=\'text\'], select, textarea').removeAttr('disabled');
		}
	}
	
	function setTextFilter() {
		if (current_filter == 0) {
			$('.current_filter').html('<?php echo $text_batch_edit; ?> - <?php echo $text_current_main; ?>');
		} else {
			$('.current_filter').html('<?php echo $text_batch_edit; ?> - <?php echo $text_current_quickly; ?>');
		}
	}
	
	function setFilter(current) {
		current_filter = current;
		setTextFilter();
		filter();
	}
	
	function resetFilter() {
		$('#tab-filter select option').removeAttr('selected');
		
		$('#tab-filter #form-filter tbody.temp').remove();
		
		<!--<?php foreach ($filter_link as $link) { ?>-->
		$('#dialog-<?php echo $link; ?>').html('');
		<!--<?php } ?>-->
		
		$('#tab-filter a[class^=\'filter_link_\']').removeClass('link_select');
		
		$('#tab-filter #filter_column input[type=\'checkbox\']').prop('checked', false);
		
		filter_column.forEach(function(value) {
			$('#tab-filter #filter_column input[type=\'checkbox\'][value=\'' + value + '\']').prop('checked', true);
		});
		
		$('#tab-filter #filter_column .dropdown-toggle b').html('(' + filter_column.length + ')');
		
		setFilter(0);
	}
	
	function resetQuickFilter() {
		$('#product .filter input').each(function(index, element) {
			$(element).val('');
		});
		
		$('#product .filter select').each(function(index, element) {
			$(element).children('option').removeAttr('selected');
		});
		
		setFilter(1);
	}
	//--></script>
     </div>
     <!-- end filter -->
     
     <!-- start main -->
     <div class="tab-pane" id="tab-general">
      <div class="select_box">
       <select onchange="$('.form-hidden').hide(); $('#form-product-' + $(this).val()).fadeIn(500);">
        <option value=""><?php echo $text_none; ?></option>
        <?php foreach ($edit_field as $field => $parameter) { ?>
        <option value="<?php echo $field; ?>"><?php echo ${'text_' . $field}; ?></option>
        <?php } ?>
       </select>
      </div>
      <?php foreach ($edit_field as $field => $parameter) { ?>
      <?php if ($field == 'description') { ?>
      <form class="form-hidden" id="form-product-description">
       <table class="be-list">
        <tfoot>
         <tr>
          <td class="left" width="50%"><textarea name="product_description" style="width:98%; height:150px;"></textarea></td>
          <td class="left"><a class="btn btn-success" onclick="editProduct(0, '<?php echo $field; ?>');"><?php echo $text_edit; ?></a></td>
         </tr>
        </tfoot>
       </table>
      </form>
      <?php } else { ?>
      <form class="form-hidden" id="form-product-<?php echo $field; ?>">
       <table class="be-list">
        <tfoot>
         <tr>
          <?php if ($field == 'image') { ?>
          <td class="center" width="1%">
           <div class="image">
            <img src="<?php echo $no_image; ?>" height="<?php echo $option['image']['height']; ?>" width="<?php echo $option['image']['width']; ?>" />
            <input data-table="product" data-field="image" data-product_id="0" type="hidden" name="product_image" />
            <a class="btn btn-success btn-xs" onclick="getImageManager(this)" title="<?php echo $text_path; ?>"><i class="fa fa-upload"></i></a>&nbsp;<a class="btn btn-danger btn-xs" title="<?php echo $text_clear; ?>" onclick="$(this).parent().find('img, input').prop({'src':no_image, 'value':''});"><i class="fa fa-trash-o"></i></a>
           </div>
          </td>
          <?php } else { ?>
          <td class="left" width="30%"><?php if (isset ($list[$field])) { ?>
           <select name="product_<?php echo $field; ?>">
            <?php if (isset ($list[$field]['zero'])) { ?>
            <option value="0"><?php echo $text_none; ?></option>
            <?php } ?>
            <?php foreach ($parameter['list'] as $value) { ?>
            <option value="<?php echo $value[$field]; ?>"><?php echo $value['name']; ?></option>
            <?php } ?>
           </select>
           <?php } else { ?>
           <?php if ($parameter['type'] == 'tinyint') { ?>
           <?php if ($field == 'status') { ?>
           <select name="product_<?php echo $field; ?>">
            <option value="0"><?php echo $text_disabled; ?></option>
            <option value="1"><?php echo $text_enabled; ?></option>
           </select>
           <?php } else { ?>
           <select name="product_<?php echo $field; ?>">
            <option value="0"><?php echo $text_no; ?></option>
            <option value="1"><?php echo $text_yes; ?></option>
           </select>
           <?php } ?>
           <?php } else { ?>
           <?php if (isset ($parameter['calc'])) { ?>
           <select name="calculate">
            <?php foreach ($calculate as $value) { ?>
            <option value="<?php echo $value['action']; ?>"><?php echo $value['name']; ?></option>
            <?php } ?>
           </select>
           &nbsp;&nbsp;&nbsp;
           <?php } ?>
           <?php $class = ''; ?>
           <?php if ($parameter['type'] == 'date' || $parameter['type'] == 'datetime') { ?>
           <?php $class = $parameter['type']; ?>
           <?php } ?>
           <input class="<?php echo $class; ?>" type="text" name="product_<?php echo $field; ?>" />
           <?php } ?>
           <?php } ?></td>
          <?php } ?>
          <td class="left"><a class="btn btn-success" onclick="editProduct(0, '<?php echo $field; ?>');"><?php echo $text_edit; ?></a></td>
         </tr>
        </tfoot>
       </table>
      </form>
      <?php } ?>
      <?php } ?>
     </div>
     <!-- end main -->
     
     <!-- start link -->
     <div class="tab-pane" id="tab-link">
      <div class="select_box">
       <select onchange="getLink(0, $(this).val());">
        <option value=""><?php echo $text_none; ?></option>
        <?php foreach ($edit_link as $link) { ?>
        <option value="<?php echo $link; ?>"><?php echo ${'text_' . $link}; ?></option>
        <?php } ?>
       </select>
      </div>
      <div id="link-box">
      </div>
     </div>
     <!-- end link -->
     
     <!-- start tool -->
     <div class="tab-pane" id="tab-tool">
      <div class="select_box">
       <select onchange="getTool(0, $(this).val());">
        <option value=""><?php echo $text_none; ?></option>
        <option value="option_price"><?php echo $text_option_price; ?></option>
        <option value="seo_generator"><?php echo $text_seo_generator; ?></option>
        <option value="search_replace"><?php echo $text_search_replace; ?></option>
        <option value="image_google"><?php echo $text_image_google; ?></option>
        <option value="image_google_auto"><?php echo $text_image_google_auto; ?></option>
        <option value="yandex_translate"><?php echo $text_yandex_translate; ?></option>
        <option value="rounding_numbers"><?php echo $text_rounding_numbers; ?></option>
        <option value="lost_image"><?php echo $text_lost_image; ?></option>
       </select>
      </div>
      <div id="tool-box">
      </div>
     </div>
     <!-- end tool -->
    </div>
    <div id="product_box"></div>
    <div id="javascript"></div>
   </div>
  </div>
 </div>
 <a class="btn btn-default go_up shadow" onclick="$('html, body').animate({scrollTop:0},800);" title="<?php echo $text_up; ?>"><i class="fa fa-arrow-up fa-3x"></i></a>
<script type="text/javascript"><!--
var quick_edit = 0;
var image_directory = false;
var filter_column = ['<?php echo implode ("','", $filter_column); ?>'];
var column = [];
var batch_edit = [];
var status = 0;

function autocompleteProductCopyData(product_id) {
	$('#product-copy-data-product_name-' + product_id).autocomplete({
		delay: 0,
		source: function(request, response) {
			xhr = $.ajax({dataType:'json', url:'index.php?route=catalog/product/autocomplete&token=' + token + '&filter_name=' +  encodeURIComponent(request),
				success: function(json) {
					response($.map(json, function(item) {
						return { label:item['name'], value:item['product_id'] }
					}));
				}
			});
		},
		'select': function(item) {
			$('#product-copy-data-product_name-' + product_id).val(item['label']);
			$('#product-copy-data-product_id-' + product_id).val(item['value']);
			
			return false;
		}
	});
}

function copyProductData(product_id, link_) {
	var data = 'copy_product_id=' + $('#product-copy-data-product_id-' + product_id).val() + '&link=' + link_;
	
	if (product_id > 0) {
		data += '&selected[]=' + product_id;
	} else {
		data += getSelectedUrl(0);
	}
	
	xhr = $.ajax({type:'POST', dataType:'json', data:data, url:'index.php?route=batch_editor/index/productCopyData&token=' + token,
		beforeSend: function() { creatOverlayLoad(true); },
		success: function(json) {
			if (json['success']) {
				if (product_id > 0) {
					getLink(product_id, link_);
				} else {
					creatOverlayLoad(false);
				}
				
				<?php if ($option['counter']) { ?>
				getProductCount(product_id, link_);
				<?php } ?>
				if (enableField('price')) {
					if (link_ == 'special') { getProductPrice(product_id, 'product_special'); }
					if (link_ == 'discount') { getProductPrice(product_id, 'product_discount'); }
				}
				if (link_ == 'description') { getProductDescription(product_id, 'description'); }
				
				<!--<?php if ($option['column_categories']) { ?>-->
				if (link_ == 'category') { getLinkToColumn(product_id, 'product_to_category') };
				<!--<?php } ?>-->
				
				<!--<?php if ($option['column_attributes']) { ?>-->
				if (link_ == 'attribute') { getLinkToColumn(product_id, 'product_attribute') };
				<!--<?php } ?>-->
				
				<!--<?php if ($option['column_options']) { ?>-->
				if (link_ == 'option') { getLinkToColumn(product_id, 'product_option') };
				<!--<?php } ?>-->
				
				getProductDateModified(product_id);
			} else {
				creatOverlayLoad(false);
			}
			creatMessage(json);
		}
	});
}

function listProductLink(product_id, link_, direction) {
	if (direction == 'next') {
		var next_product_id = $('#product input[name=\'selected[]\'][value=' + product_id + ']').parents('tbody:first').next('tbody').find('input[name=\'selected[]\']').val();
	} else {
		var next_product_id = $('#product input[name=\'selected[]\'][value=' + product_id + ']').parents('tbody:first').prev('tbody').find('input[name=\'selected[]\']').val();
	}
	
	if (next_product_id != undefined) {
		getLink(next_product_id, link_);
	}
}

function getImageManager($this) {
	$('.image_manager').remove();
	
	xhr = $.ajax({type:'GET', dataType:'html', url:'index.php?route=batch_editor/data/getImageManager&token=' + token,
		success: function(html) { $($this).after(html); }
	});
}

function selectRowInv() {
	$('#product tbody input[name=\'selected[]\']').each(function(index, element) {
		if (element.checked) {
			$(element).removeAttr('checked').parents('tr').removeClass('selected');
		} else {
			$(element).prop('checked', 'checked').parents('tr').addClass('selected');
		}
	});
}

function clearCache() {
	xhr = $.ajax({type:'GET', dataType:'json', url:'index.php?route=batch_editor/tool/clearCache&token=' + token,
		beforeSend: function() { creatOverlayLoad(true); },
		success: function(json) { creatMessage(json); creatOverlayLoad(false); }
	});
}

function creatJoystick() {
	$('.joystick').remove();
	
	if ($('#product').outerWidth() > $('#product_box').outerWidth()) {
		var html = '<div class="joystick shadow"><a data-direction="left" class="btn btn-default"><i class="fa fa-arrow-left fa-3x"></i></a>&nbsp;&nbsp;&nbsp;<a data-direction="right" class="btn btn-default"><i class="fa fa-arrow-right fa-3x"></i></a></div>';
		
		$('#product_box').prepend(html);
		
		$('.joystick').hover(
			function() { $(this).animate({'opacity':'1.0'}, 300); },
			function() { $(this).animate({'opacity':'0.5'}, 300); }
		);
		
		$('.joystick a').click(function() {
			var this_ = $(this);
			var width = $('#product_box').outerWidth();
			var scroll_left = $('#product_box').scrollLeft();
			
			if (this_.attr('data-direction') == 'left') {
				$('#product_box').animate({'scrollLeft':(scroll_left - width)}, 700);
			} else {
				$('#product_box').animate({'scrollLeft':(scroll_left + width)}, 700);
			}
		});
	}
}

function productAddCopyDelete(action) {
	var data = '';
	if (action == 'add') {
		data = $('#productAdd').serialize() + '&action=add';
	}
	if (action == 'copy') {
		data = 'action=copy&quantity=' + $('#tab-empty #quantity').val() + getSelectedUrl(0);
	}
	if (action == 'delete') {
		if (!confirm('<?php echo $button_remove; ?>?')) { return false; }
		data = 'action=delete' + getSelectedUrl(0);
	}
	xhr = $.ajax({type:'POST', dataType:'json', data:data, url:'index.php?route=batch_editor/index/productAddCopyDelete&token=' + token,
		beforeSend: function() { creatOverlayLoad(true); },
		success: function(json) {
			if (json['success'] && (action == 'delete' || action == 'copy')) {
				getProduct('&page=' + $('#product_box .pagination b').html());
			} else {
				creatOverlayLoad(false);
			}
			creatMessage(json);
		}
	});
}

function getFormProductAdd() {
	var form_box = creatDialog('dialogLink');
	
	xhr = $.ajax({type:'GET', dataType:'html', url:'index.php?route=batch_editor/index/getFormProductAdd&token=' + token,
		beforeSend: function() { creatOverlayLoad(true); },
		success: function(html) {
			form_box.find('.modal-body').html(html);
			form_box.modal('show');
			
			creatOverlayLoad(false);
		}
	});
}

function getProduct(data) {
	xhr = $.ajax({type:'POST', dataType:'html', data:getFilterUrl() + data, url:'index.php?route=batch_editor/index/getProduct&token=' + token,
		beforeSend: function() { creatOverlayLoad(true); $('#product_box').fadeOut('fast'); },
		success: function(html) {
			$('#product_box').html(html).fadeIn('slow');
			
			<!--<?php if ($option['column_categories']) { ?>-->
			getLinkToColumn(-1, 'product_to_category');
			<!--<?php } ?>-->
			<!--<?php if ($option['column_attributes']) { ?>-->
			getLinkToColumn(-1, 'product_attribute');
			<!--<?php } ?>-->
			<!--<?php if ($option['column_options']) { ?>-->
			getLinkToColumn(-1, 'product_option');
			<!--<?php } ?>-->
			
			if (enableField('price')) {
				getProductPrice(-1, 'product_special');
				getProductPrice(-1, 'product_discount');
			}
			
			<?php if ($option['counter']) { ?>
			getProductCount(-1, 0);
			<?php } else { ?>
			creatOverlayLoad(false);
			<?php } ?>
			
			creatJoystick();
			creatProductClone();
		}
	});
}

function getLink(product_id, link_) {
	$('.modal-backdrop').remove();
	$('.bootstrap-datetimepicker-widget').remove();
	
	if (product_id) {
		var link_box = creatDialog('dialogLink');
	} else {
		var link_box = $('#tab-link #link-box');
	}
	
	if (link_) {
		var data = 'link=' + link_ + '&product_id=' + product_id + '&language_id=' + getLanguageId();
		
		xhr = $.ajax({type:'POST', dataType:'html', data:data, url:'index.php?route=batch_editor/index/getLink&token=' + token,
			beforeSend: function() { creatOverlayLoad(true); },
			success: function(html) {
				if (product_id) {
					link_box.find('.modal-body').html(html);
					link_box.modal('show');
				} else {
					link_box.html(html);
				}
				creatOverlayLoad(false);
			}
		});
	} else { link_box.html(''); }
}

function getTool(product_id, tool) {
	if (product_id > 0) {
		var tool_box = creatDialog('dialogTool');
	} else {
		var tool_box = $('#tab-tool #tool-box');
	}
	
	if (tool) {
		var data = 'product_id=' + product_id + '&tool=' + tool + '&language_id=' + getLanguageId();
		
		xhr = $.ajax({type:'POST', dataType:'html', data:data, url:'index.php?route=batch_editor/tool/getTool&token=' + token,
			beforeSend: function() { creatOverlayLoad(true); },
			success: function(html) {
				if (product_id) {
					tool_box.find('.modal-body').html(html);
					tool_box.modal('show');
				} else {
					tool_box.html(html);
				}
				creatOverlayLoad(false);
			}
		});
	} else {
		tool_box.html('');
	}
}

function getProductPrice(product_id, price) {
	var data = '&price=' + price + getSelectedUrl(product_id);
	
	xhr = $.ajax({type:'POST', dataType:'json', data:data, url:'index.php?route=batch_editor/data/getProductPrice&token=' + token,
		success: function(json) {
			$.each(json, function(product_id, value) {
				if (value) {
					var span = $('#product span.input-price-' + product_id);
					var percent = -(100 - ((value * 100) / span.html())).toFixed(2);
					if (!isFinite(percent)) { percent = 0; }
					value += '<div>[' + percent + '%]</div>';
				}
				$('#product .em-' + price + '-' + product_id).html(value);
			});
		}
	});
}

function getProductCount(product_id, link_) {
	var data = '&links[]=' + link_ + getSelectedUrl(product_id);
	
	xhr = $.ajax({type:'POST', dataType:'json', data:data, url:'index.php?route=batch_editor/data/getProductCount&token=' + token,
		success: function(json) {
			$.each(json, function(product_id, data) { $.each(data, function(link_, value) { $('.' + link_ + '_count_' + product_id).html('(' + value + ')'); }); });
			
			if (link_ == 0) {
				$('#overlay, #messageLoad').fadeOut('fast');
			}
			
			creatProductClone();
		}
	});
}

function getProductDateModified(products) {
	var data = '';
	if (products) {
		data += '&products[]=' + products;
	} else {
		$('#product_box input[name=\'selected[]\']').each(function(index, element) {
			data += '&products[]=' + $(element).val();
		});
	}
	xhr = $.ajax({type:'POST', dataType:'json', data:data, url:'index.php?route=batch_editor/data/getProductDateModified&token=' + token,
		success: function(json) {
			$.each(json, function(product_id, value) { $('em.date_modified_' + product_id).html(value); });
		}
	});
}

function getProductData(product_id, field) {
	var data = 'field=' + field + '&language_id=' + getLanguageId() + getSelectedUrl(product_id);
	
	xhr = $.ajax({type:'POST', dataType:'json', data:data, url:'index.php?route=batch_editor/data/getProductData&token=' + token,
		success: function(json) {
			$.each(json, function(product_id, value) {
				if (field == 'image') {
					$('#product .image input[data-product_id=\'' + product_id + '\']').val(value['image']).parents('div:first').find('img').attr('src', value['thumb']);
				} else {
					$('#product span[class*=-' + field + '-' + product_id + ']').fadeOut().html(value).css({'border':'1px solid green', 'border-radius':'2px'}).fadeIn(700);
					
					validateField(field, product_id, value);
					
					if (field == 'price') {
						getProductPrice(0, 'product_special');
						getProductPrice(0, 'product_discount');
					}
				}
			});
			
			creatProductClone();
		}
	});
}

function getProductDescription(selected, link_) {
	var data = 'language_id=' + getLanguageId() + getSelectedUrl(selected);
	
	xhr = $.ajax({type:'POST', dataType:'json', data:data, url:'index.php?route=batch_editor/data/getProductDescription&token=' + token,
		success: function(json) {
			$.each(json, function(product_id, data) {
				$.each(data, function(field, value) {
					var insert = false;
					
					if (enableField(field)) {
						if (link_ == 'seo_generator' || link_ == 'search_replace' || link_ == 'yandex_translate') {
							if ($('#tab-tool #form-' + link_ + selected + ' input[value=\'' + field + '\']').prop('checked')) { insert = true; }
						} else {
							insert = true;
						}
					}
					
					if (insert) {
						validateField(field, product_id, value);
						$('#product span[class*=-' + field + '-' + product_id + ']').fadeOut().html(value).css({'border':'1px solid green', 'border-radius':'2px'}).fadeIn();
					}
				});
			});
			
			creatProductClone();
		}
	});
}

function getLinkToColumn(product_id, link_) {
	var data = 'link=' + link_ + getSelectedUrl(product_id);
	
	xhr = $.ajax({type:'POST', dataType:'json', data:data, url:'index.php?route=batch_editor/data/getLinkToColumn&token=' + token,
		success: function(json) {
			$.each(json, function(product_id, html) {
				$('#product_box td.column-' + link_ + '-' + product_id + ' div').html(html);
			});
			
			creatJoystick();
			creatProductClone();
		}
	});
}

function BatchEditPause(this_) {
	if (status == 1) {
		status = 0;
		$(this_).html('<?php echo $text_continue; ?>');
	} else {
		status = 1;
		$(this_).html('<?php echo $text_pause; ?>');
		BatchEditStart();
	}
}

function BatchEditStop() {
	xhrAbort();
	status = 0;
	batch_edit = [];
	
	if (current_product_id == 'filter') {
		getProduct('&page=1');
	} else {
		if (current_tool == 'image_google_auto') {
			getProductData(0, 'image');
			<?php if ($option['counter']) { ?>
			getProductCount(0, 'image');
			<?php } ?>
			getProductDateModified(0);
			creatProductClone();
		}
		
		creatOverlayLoad(false);
	}
}

function BatchEditStart() {
	xhr = $.ajax({type:'POST', dataType:'json', data:batch_edit['data'], url:batch_edit['url'],
		success: function(json) {
			if (json['success']) {
				if (json['value']) { setDataToForm(0, json['value']); }
				
				var total = parseInt ($('#messageLoad .progress-bar').attr('aria-valuemax'));
				var value = parseInt ($('#messageLoad .progress-bar').attr('aria-valuenow')) + json['count'];
				var width = parseInt ((value * 100) / total);
				
				$('#messageLoad .current_count').html(value);
				$('#messageLoad .progress-bar').attr('aria-valuenow', value).html(width + '%').css({'width':width + '%'});
				
				if (status == 1) { setTimeout (function() { BatchEditStart(); }, 500); }
			} else {
				if ((json['warning'] && json['count']) || json['attention']) {
					creatOverlayLoad(false);
					creatMessage(json);
				} else {
					status = 0;
					$('#messageLoad .progress').remove();
					$('#messageLoad .button_pause').remove();
					$('#messageLoad .button_stop').html('&times;');
					$('#messageLoad').prepend('<div class="alert alert-success"><?php echo $success_edit_product; ?></div>');
				}
			}
		}
	});
}

function setDataToForm(product_id, data) {
	var box = $('#form-' + current_tool + product_id + ' .data');
	
	if (data instanceof Array && box.is('table')) {
		$.each(data, function (index, array) {
			var html = '<tbody>';
			
			$.each(array, function (field, value) { html += '<td class="left">' + value + '</td>'; });
			
			html += '</tbody>';
			
			box.append(html);
		});
	}
}

function setEditProductId(data, url) {
	if (current_tool == 'image_google_auto' && $('#batch_edit').prop('checked') == false) {
		current_product_id = 'array';
		
		var data_ = getSelectedUrl(0) + '&type=array';
	} else {
		current_product_id = 'filter';
		
		var data_ = getFilterUrl() + '&type=filter';
		
		if (!confirm ($('.current_filter').html())) {
			return false;
		}
	}
	
	xhr = $.ajax({type:'POST', dataType:'json', data:data_, url:'index.php?route=batch_editor/index/setEditProductId&token=' + token,
		beforeSend: function() {
			creatOverlayLoad(true);
		},
		success: function(json) {
			if (json['count'] > 0) {
				var html = '';
				
				html += '<div class="progress"><div class="progress-bar progress-bar-success" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="' + json['count'] + '"></div></div>';
				
				html += '<div style="margin-top:0px;">';
				html += ' <b style="color:#FFF;"><span class="current_count">0</span> <?php echo $text_from; ?> ' + json['count'] + '</b><br />';
				html += ' <span class="btn btn-primary button_pause" onclick="BatchEditPause(this);"><?php echo $text_pause; ?></span>';
				html += ' <span class="btn btn-danger button_stop" onclick="BatchEditStop();"><?php echo $text_stop; ?></span>';
				html += '</div>';
				
				$('#messageLoad').html(html);
				
				batch_edit = {'data':data + '&batch_edit=1', 'url':url};
				status = 1;
				
				BatchEditStart();
			} else {
				creatMessage({'warning':'<?php echo $error_empty_product; ?>'});
				creatOverlayLoad(false);
			}
		}
	});
}

function editProduct(product_id, field) {
	var data = 'field=' + field + '&' + $('#form-product-' + field).serialize() + '&language_id=' + getLanguageId();
	var url = 'index.php?route=batch_editor/index/editProduct&token=' + token;
	
	if ($('#batch_edit').prop('checked')) {
		setEditProductId(data, url);
		return false;
	}
	
	data += getSelectedUrl(product_id);
	
	xhr = $.ajax({type:'POST', dataType:'json', data:data, url:url,
		beforeSend:function() { creatOverlayLoad(true); },
		success: function(json) {
			if ($('#product_update').prop('checked')) {
				if (json['success']) {
					getProduct('');
				} else {
					creatOverlayLoad(false);
				}
			} else {
				if (json['success']) {
					if (enableField(field)) { getProductData(0, field); }
					getProductDateModified(0);
				}
				creatOverlayLoad(false);
			}
			creatMessage(json);
			creatJoystick();
		}
	});
}

function editLink(link_, action, product_id) {
	var data = 'link=' + link_ + '&action=' + action + '&' + $('#form-' + link_ + product_id).serialize();
	var url = 'index.php?route=batch_editor/index/editLink&token=' + token;
	
	if ($('#batch_edit').prop('checked') && product_id == 0) {
		setEditProductId(data, url);
		return false;
	}
	
	data += getSelectedUrl(product_id);
	
	xhr = $.ajax({type:'POST', dataType:'json', data:data, url:url,
		beforeSend: function() { creatOverlayLoad(true); },
		success: function(json) {
			if ($('#product_update').prop('checked') && product_id == 0) {
					if (json['success']) {
						getProduct('');
					} else {
						creatOverlayLoad(false);
					}
			} else {
				if (json['success']) {
					<?php if ($option['counter']) { ?>
					getProductCount(product_id, link_);
					<?php } ?>
					if (enableField('price')) {
						if (link_ == 'special') { getProductPrice(product_id, 'product_special'); }
						if (link_ == 'discount') { getProductPrice(product_id, 'product_discount'); }
					}
					if (link_ == 'description') { getProductDescription(product_id, 'description'); }
					
					<!--<?php if ($option['column_categories']) { ?>-->
					if (link_ == 'category') { getLinkToColumn(product_id, 'product_to_category') };
					<!--<?php } ?>-->
					
					<!--<?php if ($option['column_attributes']) { ?>-->
					if (link_ == 'attribute') { getLinkToColumn(product_id, 'product_attribute') };
					<!--<?php } ?>-->
					
					<!--<?php if ($option['column_options']) { ?>-->
					if (link_ == 'option') { getLinkToColumn(product_id, 'product_option') };
					<!--<?php } ?>-->
					
					getProductDateModified(product_id);
				}
				creatOverlayLoad(false);
			}
			creatMessage(json);
			creatJoystick();
		}
	});
}

function editTool(product_id, tool, action) {
	current_tool = tool;
	
	var data = 'tool=' + tool + '&action=' + action + '&' + $('#form-' + tool + product_id).serialize();
	var url = 'index.php?route=batch_editor/tool/editTool&token=' + token;
	
	if (($('#batch_edit').prop('checked') && product_id == 0) || tool == 'image_google_auto') {
		setEditProductId(data, url);
		return false;
	}
	
	data += getSelectedUrl(product_id);
	
	xhr = $.ajax({type:'POST', dataType:'json', data:data, url:url,
		beforeSend: function() { creatOverlayLoad(true); },
		success: function(json) {
			if ($('#product_update').prop('checked') && product_id == 0) {
				if (json['success']) {
					getProduct('');
				} else {
					creatOverlayLoad(false);
				}
			} else {
				if (json['success']) {
					if (tool == 'image_google') {
						getProductData(product_id, 'image');
						getProductCount(product_id, 'image');
					}
					if (tool == 'seo_generator' || tool == 'search_replace') {
						getProductDescription(product_id, tool);
						
						$.each($('#form-' + tool + product_id + ' input[name=\'' + tool + '[apply_to][p][]\']:checked'), function(index, element) {
							getProductData(product_id, $(element).val());
						});
					}
					if (tool == 'yandex_translate') {
						getProductDescription(product_id, tool);
					}
					if (tool == 'rounding_numbers') {
						$.each($('#form-' + tool + product_id + ' input[name=\'' + tool + '[apply_to][product][]\']:checked'), function(index, element) {
							getProductData(product_id, $(element).val());
						});
					}
					if (tool == 'lost_image') {
						getProductCount(0, 'image');
					}
					if (json['value']) { setDataToForm(product_id, json['value']); }
					getProductDateModified(0);
				}
				creatOverlayLoad(false);
			}
			creatMessage(json);
			creatJoystick();
		}
	});
}

function relatedToProduct(product_id) {
	var data = 'link=related&action=add&selected[]=' + product_id;
	$('#product input[name=\'selected[]\']:checked').each(function(index, element) { data += '&related[]=' + $(element).val(); });
	
	xhr = $.ajax({type:'POST', dataType:'json', data:data, url:'index.php?route=batch_editor/index/editLink&token=' + token,
		beforeSend:function() { creatOverlayLoad(true); },
		success: function(json) {
			if (json['success']) {
				getProductCount(product_id, 'related');
				getProductDateModified(product_id);
				<?php if ($option['related']['add'] == 2) { ?>
				getProductCount(0, 'related');
				getProductDateModified(0);
				<?php } ?>
			}
			creatMessage(json);
			creatOverlayLoad(false);
		}
	});
}

function relatedToForm(form) {
	var count = 0;
	
	$('#product input[name=\'selected[]\']:checked').each(function(index, element) {
		var product_id = $(element).val();
		var product_name = $('#product span.input-name-' + product_id).html();
		
		$('#' + form + ' #related' + product_id).remove();
		$('#' + form + ' #related').append('<div id="related' + product_id + '"><a onclick="$(this).parent(\'div\').remove();" class="btn btn-danger btn-xs" title="<?php echo $button_remove; ?>"><i class="fa fa-minus"></i></a>&nbsp;&nbsp;&nbsp;' + product_name + '<input type="hidden" name="related[]" value="' + product_id + '" /></div>');
		
		count++;
	});
	
	if (count == 0) {
		creatMessage({'warning':'<?php echo $error_empty_product; ?>'});
	}
}

function htmlspecialchars(html) {
	html = html.replace(/&/g, "&amp;");
	html = html.replace(/"/g, "&quot;");
	html = html.replace(/</g, "&lt;");
	html = html.replace(/>/g, "&gt;");
	return html;
}

function validateField(field, id, value) {
	if (field == 'status') {
		if (value == 0 || value == '<?php echo $text_disabled; ?>') {
			$('#product .' + 'td_' + field + id).removeClass('enabled').addClass('disabled');
		} else {
			$('#product .' + 'td_' + field + id).removeClass('disabled').addClass('enabled');
		}
	} else if (field == 'quantity') {
		if (value <= 0) {
			$('#product .' + 'td_' + field + id).removeClass('quantity').addClass('quantity_0');
		} else {
			$('#product .' + 'td_' + field + id).removeClass('quantity_0').addClass('quantity');
		}
	} else {
		if (!value) {
			$('#product .' + 'td_' + field + id).addClass('attention');
		} else {
			$('#product .' + 'td_' + field + id).removeClass('attention');
		}
	}
}

function enableField(field) {
	return ($('#product thead input[name=\'' + field + '-visible\']').val()) ? true : false;
}

function saveTemplate(template, prefix) {
	xhr = $.ajax({type:'POST', dataType:'json', data:$('#form-' + template + prefix).serialize() + '&template=' + template, url:'index.php?route=batch_editor/template/saveTemplate&token=' + token,
		beforeSend: function() { creatOverlayLoad(true); },
		success:function(json) { creatMessage(json); creatOverlayLoad(false); }
	});
}

function getTemplates(template, product_id) {
	var template_box = creatDialog('dialogTemplate');
	
	xhr = $.ajax({type:'POST', dataType:'html', data:'template=' + template + '&product_id=' + product_id, url:'index.php?route=batch_editor/template/getTemplates&token=' + token,
		success:function(html) {
			$('#dialogTemplate').find('.modal-body').html(html);
			$('#dialogTemplate').modal('show').on('hidden.bs.modal', function(e) {
				if ($('.modal.in').length) {
					$(document.body).addClass('modal-open');
				}
			});
		}
	});
}

function getSelectedUrl(product_id) {
	var data = '';
	
	if (product_id > 0) {
		data += '&selected[]=' + product_id;
	} else if (product_id == 0) {
		$('#product_box input[name=\'selected[]\']:checked').each(function(index, element) { data += '&selected[]=' + $(element).val(); });
	} else {
		$('#product_box input[name=\'selected[]\']').each(function(index, element) { data += '&selected[]=' + $(element).val(); });
	}
	
	return data;
}

function getLanguageId() {
	return $('#tab-filter input[name=\'filter_language_id\']:checked').val();
}

function creatDateTime() {
	$('.date').datetimepicker({
		pickTime: false,
		format: 'YYYY-MM-DD'
	});
	
	$('.time').datetimepicker({
		pickDate: false,
		format: 'HH:mm'
	});
	
	$('.datetime').datetimepicker({
		pickDate: true,
		pickTime: true,
		format: 'YYYY-MM-DD HH:mm:ss'
	});
}

function selectRowAll(_this) {
	$('#product tbody input[name=\'selected[]\']').prop('checked', _this.checked);
	
	if (_this.checked) {
		$('#product tbody tr').addClass('selected');
	} else {
		$('#product tbody tr').removeClass('selected');
	}
}

function CKEditorClose(status) {
	if (status) {
		$('.CKEditorCurrent').val($('#CKEditorTextarea').code());
	}
	
	$('#CKEditorDialog').modal('hide');
	$('.CKEditorCurrent').removeClass('CKEditorCurrent');
}

$(document).ready(function() {
	$('#product_box').delegate('.pagination a', 'click', function(e) {
		getProduct('&page=' + $(this).attr('href'));
		
		return false;
	});
	
	$('#product_box').delegate('#product thead .sort a, #product-clone thead .sort a', 'click', function(e) {
		var data = '&sort=' + $(this).attr('href');
		if ($(this).attr('class') == 'asc') {
			data += '&order=DESC';
		} else {
			data += '&order=ASC';
		}
		getProduct(data);
		return false;
	});
	
	$('#product_box').delegate('#product tbody input, #product tbody select', 'keypress', function(e) {
		if (e.keyCode == 13) {
			$(this).trigger('blur');
			return false;
		}
	});
	
	$('#product_box').delegate('#product tbody input[name=\'selected[]\']', 'change', function(e) {
		if (this.checked) {
			$(this).parents('tr:first').addClass('selected');
		} else {
			$(this).parents('tr:first').removeClass('selected');
		}
	});
	
	$('#product_box').delegate('#product tbody span', 'click', function(e) {
		if (quick_edit) {
			return false;
		}
		
		var this_class = $(this).attr('class');
		var arr = this_class.match(/^([a-z]*)\-([a-z0-9\_]*)\-([0-9]*)$/);
		var type = arr[1];
		var field = arr[2];
		text = $(this).html().replace(/"/g, "&quot;");
		
		if (type == 'select') {
			xhr = $.ajax({type:'GET', dataType:'html', data:'id=' + arr[3] + '&name=' + encodeURIComponent(text) + '&field=' + field, url:'index.php?route=batch_editor/data/loadList&token=' + token,
				success: function(html) {
					$('#product .' + this_class).replaceWith(html);
					$('#product select.' + this_class).focus();
				}
			});
		} else if (type == 'textarea') {
			$('#product .' + this_class).replaceWith('<textarea class="' + this_class + '" rows="2">' + text + '</textarea>');
			$('#product textarea.' + this_class).focus();
		} else {
			$('#product .' + this_class).replaceWith('<input type="text" class="' + this_class + '" value="' + text + '" />');
			$('#product input.' + this_class).focus();
		}
		
		creatProductClone();
	});
	
	$('#product_box').delegate('#product tbody input, #product tbody textarea, #product tbody select', 'blur', function(e) {
		var this_class = $(this).attr('class');
		
		if (this_class) {
			var arr = this_class.match(/^([a-z]*)\-([a-z0-9\_]*)\-([0-9]*)$/);
		} else {
			return false;
		}
		
		if (arr == null) {
			return false;
		}
		
		var html = '<span class="' + this_class + '">';
		var type = arr[1];
		var field = arr[2];
		var product_id = arr[3];
		
		if (type == 'select') {
			var text_edit = $('#product .' + this_class + ' :selected').text();
		} else {
			var text_edit = $(this).val();
		}
		
		text_edit = htmlspecialchars(text_edit);
		
		if (text != text_edit) {
			quick_edit = 1;
			
			var value = encodeURIComponent($(this).val());
			
			$(this).attr('disabled', 'disabled');
			
			xhr = $.ajax({type:'POST', dataType:'json', data:'selected[]=' + product_id + '&product_' + field + '=' + value + '&field=' + field + '&language_id=' + getLanguageId(), url:'index.php?route=batch_editor/index/editProduct&token=' + token,
				error: function() {
					quick_edit = 0;
					
					$('#product .' + this_class).replaceWith('<span class="' + this_class + '" style="background:red; border-radius:3px;">' + text + '</span>');
					
					alert('<?php echo $error_server; ?>');
				},
				success: function(json) {
					quick_edit = 0;
					
					if (json['warning']) {
						$('#product .' + this_class).replaceWith(html + text + '</span>');
						
						creatMessage(json);
					} else {
						validateField(field, product_id, json['value']);
						if (type == 'select') {
							$('#product .' + this_class).replaceWith(html + text_edit + '</span>');
						} else {
							$('#product .' + this_class).replaceWith(html + json['value'] + '</span>');
							
							if (field == 'price') {
								getProductPrice(product_id, 'product_special');
								getProductPrice(product_id, 'product_discount');
							}
						}
						$('#product .' + this_class).css({'border':'1px solid green', 'border-radius':'2px'});
						
						getProductDateModified(product_id);
					}
					
					creatJoystick();
					creatProductClone();
				}
			});
		} else {
			$(this).replaceWith(html + text_edit + '</span>');
			
			creatProductClone();
		}
	});
	
	$('#product_box').delegate('#product tbody .image input[data-field=\'image\']', 'change', function(e) {
		var image = $(this).parents('.image:first').find('img');
		var value = $(this).val();
		var product_id = $(this).attr('data-product_id');
		
		xhr = $.ajax({type:'POST', dataType:'json', url:'index.php?route=batch_editor/index/editProduct&token=' + token, data:'selected[]=' + product_id + '&product_image=' + value + '&field=image',
			success: function(json) {
				if (json['warning']) { creatMessage(json); }
			}
		});
	});
	
	$('#form-copy input[name=\'product_copy\']').bind('keypress', function(e) {
		if (e.keyCode == 13) { return false; }
	});
	
	$(document).delegate('.image img', 'click', function(e) {
		$('#modal-image').remove();
		$('.image_manager').remove();
		$('#current-image-input').removeAttr('id');
		
		var thumb = $(this);
		var input = thumb.parents('div:first').find('input');
		
		input.attr('id', 'current-image-input');
		
		xhr = $.ajax({dataType:'html', url:'index.php?route=common/filemanager&token=' + token + '&target=' + encodeURIComponent('current-image-input'),
			success: function(html) {
				$('body').append('<div id="modal-image" class="modal">' + html + '</div>');
				
				$('#modal-image').modal('show').on('hidden.bs.modal', function(e) {
					xhr = $.ajax({type:'POST', dataType:'html', data:'image=' + input.val(), url:'index.php?route=batch_editor/tool/imageResize&token=' + token,
						success: function(src) {
							thumb.attr('src', src);
						}
					});
					
					$('#modal-image').remove();
					
					input.trigger('change');
					
					if ($('.modal.in').length) {
						$(document.body).addClass('modal-open');
					}
				});
			}
		});
	});
	
	$(document).delegate('textarea', 'dblclick', function(e) {
		$('.CKEditorCurrent').removeClass('CKEditorCurrent');
		$('#CKEditorDialog').remove();
		
		$(this).addClass('CKEditorCurrent');
		var dialog = creatDialog('CKEditorDialog');
		
		dialog.find('.modal-body').html('<textarea id="CKEditorTextarea">' + $(this).val() + '</textarea>');
		dialog.find('.modal-footer').html('<a class="btn btn-success" onclick="CKEditorClose(true);">+</a><a class="btn btn-danger" onclick="CKEditorClose(false);">&times;</a>');
		dialog.modal('show').on('hidden.bs.modal', function(e) {
			if ($('.modal.in').length) {
				$(document.body).addClass('modal-open');
			}
		});
		
		$('#CKEditorTextarea').summernote({height:300, focus:true});
	});
	
	<?php if ($success) { ?>
	creatMessage({'success':'<?php echo $success; ?>'});
	<?php } ?>
	
	getProduct('');
	setTextFilter();
	creatDateTime();
	
	jQuery.event.props.push('dataTransfer');
});

$.ajaxSetup({
	error: function() { alert('<?php echo $error_server; ?>'); }
});
//--></script>
</div>
<?php echo $footer; ?>
