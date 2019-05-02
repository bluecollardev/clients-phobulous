<form id="form-<?php echo $link; ?><?php echo $product_id; ?>">
 <?php if ($product_id == -1) { ?>
 <span class="be-help">
 <label><input name="none[<?php echo $link; ?>]" type="checkbox" /> <?php echo $text_not_contain; ?> > <?php echo ${'text_' . $link}; ?></label>
 </span>
 <?php if (isset ($fields['main_category'])) { ?>
 <span class="be-help">
 <label><input name="none[main_category]" type="checkbox" value="1" onclick="inputFormDisabled(this);" /> <?php echo $text_not_contain; ?> > <?php echo $field_main_category; ?></label>
 </span>
 <?php } ?>
 <br />
 <span class="be-help">
 <label>
  <input name="has[<?php echo $link; ?>]" type="checkbox" value="1" />
  <?php echo $text_strictly_selected; ?></label>
 </span>
 <span class="be-help">
 <label>
  <input name="count[<?php echo $link; ?>]" type="checkbox" value="1" />
  <?php echo $text_with_regard_number; ?></label>
 </span>
 <p></p>
 <?php } ?>
 <?php if ($product_id > 0) { ?>
 <table class="be-form">
  <tr>
   <td width="1%"><img src="<?php echo $product_image; ?>" /></td>
   <td width="99%"><h3><?php echo $product_name; ?></h3></td>
  </tr>
 </table>
 <?php } ?>
 <?php $cols = '<col width="50" />'; ?>
 <?php foreach ($fields as $field=>$setting) { ?>
 <?php if ($field != 'product_id') { ?>
 <?php if ($setting['extra'] == 'auto_increment') { ?>
 <?php $cols .= '<col width="1%" />'; ?>
 <?php } else if ($field == 'main_category') { ?>
 <?php $cols .= '<col width="20%" />'; ?>
 <?php } else { ?>
 <?php $cols .= '<col />'; ?>
 <?php } ?>
 <?php } ?>
 <?php } ?>
 <div class="be-scrollhead">
  <table class="be-list">
   <?php echo $cols; ?>
   <thead>
    <tr>
     <td class="center"></td>
     <?php foreach ($fields as $field=>$setting) { ?>
     <?php if ($field != 'product_id') { ?>
     <?php if ($setting['extra'] == 'auto_increment') { ?>
     <td class="center"><?php echo ${'field_' . $field}; ?></td>
     <?php } else if ($field == 'main_category') { ?>
     <td class="center">
      <?php echo ${'field_' . $field}; ?><br />
      <label>
       <span class="be-help">
        <?php if ($main_category) { ?>
        <input id="no_checked" type="radio" value="0" onchange="selectTableRow(this);" />
        <?php } else { ?>
        <input id="no_checked" type="radio" value="0" onchange="selectTableRow(this);" checked="checked" />
        <?php } ?>
        <?php echo $text_none; ?>
       </span>
      </label>
     </td>
     <?php } else { ?>
     <td class="left"><?php echo ${'field_' . $field}; ?></td>
     <?php } ?>
     <?php } ?>
     <?php } ?>
    </tr>
   </thead>
  </table>
 </div>
 <div class="be-scrollcontent">
  <table class="be-list">
   <?php echo $cols; ?>
   <?php $row = 0; ?>
   <?php if ($list) { ?>
   <?php foreach ($list as $key => $array) { ?>
   <tbody>
    <tr>
     <?php foreach ($fields as $field => $setting) { ?>
     <?php if ($field != 'product_id') { ?>
     <?php if ($field == 'main_category') { ?>
     <td class="center"><?php if ($array['category_id'] == $main_category) { ?>
      <input name="<?php echo $link; ?>[<?php echo $row; ?>][<?php echo $field; ?>]" type="radio" value="1" onclick="selectTableRow(this);" checked="checked" />
      <?php } else { ?>
      <input name="<?php echo $link; ?>[<?php echo $row; ?>][<?php echo $field; ?>]" type="radio" value="1" onclick="selectTableRow(this);" />
      <?php } ?></td>
     <?php } else { ?>
     <?php if (isset ($names[$array[$field]])) { ?>
     <td class="center enabled"><input name="<?php echo $link; ?>[<?php echo $row; ?>][<?php echo $field; ?>]" type="checkbox" value="<?php echo $array[$field]; ?>" onchange="checkedTableTd(this);" checked="checked" /></td>
     <?php } else { ?>
     <td class="center disabled"><input name="<?php echo $link; ?>[<?php echo $row; ?>][<?php echo $field; ?>]" type="checkbox" value="<?php echo $array[$field]; ?>" onchange="checkedTableTd(this);" /></td>
     <?php } ?>
     <td class="left"><?php if (isset ($array['name'])) { ?>
      <?php echo $array['name']; ?>
      <?php } ?></td>
     <?php } ?>
     <?php } ?>
     <?php } ?>
    </tr>
   </tbody>
   <?php $row++; ?>
   <?php } ?>
   <?php } else { ?>
   <tbody class="no_results">
    <tr>
     <td class="center" colspan="<?php echo count ($fields); ?>"><div class="alert alert-warning" align="center">
       <?php echo $text_no_results; ?>
      </div></td>
    </tr>
   </tbody>
   <?php } ?>
  </table>
 </div>
 <div class="be-scrollfoot">
  <table class="be-list">
   <tfoot>
    <tr>
     <td class="center" colspan="<?php echo count ($fields); ?>">
      <?php if ($product_id > -1) { ?>
      <input id="product-copy-data-product_name-<?php echo $product_id; ?>" type="text" />
      <input id="product-copy-data-product_id-<?php echo $product_id; ?>" type="hidden" />
      <a class="btn btn-primary btn-sm" onclick="copyProductData('<?php echo $product_id; ?>', '<?php echo $link; ?>');" title="<?php echo $button_copy; ?>" style="margin-right:50px;"><i class="fa fa-copy"></i></a>
      <?php } ?>
      <?php if ($product_id == -1) { ?>
      <a class="btn btn-primary" onclick="setLinkFilter('<?php echo $link; ?>');"><?php echo $button_add_to_filter; ?></a>
      <a class="btn btn-danger" onclick="delLinkFilter('<?php echo $link; ?>');"><?php echo $button_remove_from_filter; ?></a>
      <?php } else if ($product_id == 0) { ?>
      <a class="btn btn-primary" onclick="editLink('<?php echo $link; ?>', 'add', <?php echo $product_id; ?>);"><?php echo $button_insert_sel; ?></a>
      <a class="btn btn-primary" onclick="editLink('<?php echo $link; ?>', 'del', <?php echo $product_id; ?>);"><?php echo $button_delete_sel; ?></a>
      <a class="btn btn-primary" onclick="editLink('<?php echo $link; ?>', 'upd', <?php echo $product_id; ?>);"><?php echo $text_edit; ?></a>
      <?php } else if ($product_id > 0) { ?>
      <a class="btn btn-primary" onclick="listProductLink('<?php echo $product_id; ?>', '<?php echo $link; ?>', 'prev');" title="<?php echo $button_prev; ?>"><i class="fa fa-chevron-left"></i></a>
      <a class="btn btn-primary" onclick="listProductLink('<?php echo $product_id; ?>', '<?php echo $link; ?>', 'next');" title="<?php echo $button_next; ?>" style="margin-right:50px;"><i class="fa fa-chevron-right"></i></a>
      <a class="btn btn-success" onclick="editLink('<?php echo $link; ?>', 'upd', <?php echo $product_id; ?>);"><?php echo $button_save; ?></a>
      <a class="btn btn-danger" onclick="$('#dialogLink').modal('hide');" title="<?php echo $button_close; ?>">&times;</a>
      <?php } ?>
     </td>
    </tr>
   </tfoot>
  </table>
 </div>
</form>

<?php if (isset ($fields['main_category'])) { ?>
<script type="text/javascript"><!--
$('#form-<?php echo $link; ?><?php echo $product_id; ?> input[type=\'radio\']:checked').parents('tbody').addClass('selected');
//--></script>
<?php } ?>

<?php if ($product_id == -1) { ?>
<script type="text/javascript"><!--
$(document).ready(function() {
	$('#dialog-<?php echo $link; ?>').find('.modal-header').append('<?php echo ${"text_" . $link}; ?>');
});
//--></script>
<?php } ?>

<?php if ($product_id > 0) { ?>
<script type="text/javascript"><!--
$(document).ready(function() {
	$('#dialogLink').find('.modal-header').append('<?php echo ${"text_" . $link}; ?>');
});
//--></script>
<?php } ?>

<?php if ($product_id > -1) { ?>
<script type="text/javascript"><!--
autocompleteProductCopyData('<?php echo $product_id; ?>');
//--></script>
<?php } ?>