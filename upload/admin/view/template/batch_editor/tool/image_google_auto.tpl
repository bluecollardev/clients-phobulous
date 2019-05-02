<style type="text/css">
#form-image_google_auto<?php echo $product_id; ?> ol {
	padding-left:20px;
}

#form-image_google_auto<?php echo $product_id; ?> ol li {
	float:left;
	width:100%;
	height:28px;
}
</style>
<form id="form-image_google_auto<?php echo $product_id; ?>">
 <table class="be-list">
  <thead>
   <tr>
    <td class="center" width="10%"><?php echo $text_number_images; ?>:</td>
    <td class="center" width="10%"><?php echo $text_main; ?>:</td>
    <td class="left" width="55%"><?php echo $text_folder; ?> (<?php echo $text_by_priority; ?>):</td>
    <td class="center" colspan="5"><?php echo $text_optional; ?>:</td>
   </tr>
  </thead>
  <tfoot>
   <tr>
    <td class="center">
     <select name="image_google_auto[number_images]">
      <option value="1">1</option>
      <option value="2">2</option>
      <option value="3">3</option>
      <option value="4">4</option>
      <option value="5">5</option>
      <option value="6">6</option>
      <option value="7">7</option>
      <option value="8">8</option>
      <option value="9">9</option>
      <option value="10">10</option>
      <option value="11">11</option>
      <option value="12">12</option>
     </select>
    </td>
    <td class="center"><input name="image_google_auto[main_image]" type="text" value="0" size="3" /></td>
    <td class="left">
     <ol>
      <li><?php echo $text_existing; ?></li>
      <?php if ($main_category) { ?>
      <li><?php echo $text_main_category; ?> (<?php echo $text_translit; ?>)</li>
      <?php } ?>
      <li><?php echo $text_category; ?> (<?php echo $text_translit; ?>)</li>
      <li>
       <?php echo $text_folder; ?>:
       <input class="image_google_auto_directory" value="<?php echo $directory; ?>" disabled="disabled">
       <a class="btn btn-success btn-xs" onclick="$('#form-image_google_auto<?php echo $product_id; ?> input.image_google_auto_directory').after(' <input name=\'image_google_auto[directory][]\' /> <span class=\'separator\'>/</span> ');" title="<?php echo $text_add; ?>"><i class="fa fa-plus"></i></a>
       <a class="btn btn-danger btn-xs" onclick="$('#form-image_google_auto<?php echo $product_id; ?> input[name=\'image_google_auto[directory][]\']:last').remove(); $('#form-image_google_auto<?php echo $product_id; ?> span.separator:last').remove();" title="<?php echo $text_delete; ?>"><i class="fa fa-minus"></i></a>
      </li>
     </ol>
    </td>
    <td class="center">
     <select name="image_google_auto[url][as_filetype]">
      <option value="">---<?php echo $text_file_type; ?>---</option>
      <option value="jpg">JPG</option>
      <option value="png">PNG</option>
      <option value="gif">GIF</option>
      <option value="bmp">BMP</option>
     </select>
    </td>
    <td class="center">
     <select name="image_google_auto[url][imgcolor]">
      <option value="">---<?php echo $text_color; ?>---</option>
      <option style="background:black;" value="black">Black</option>
      <option style="background:blue;" value="blue">Blue</option>
      <option style="background:brown;" value="brown">Brown</option>
      <option style="background:gray;" value="gray">Gray</option>
      <option style="background:green;" value="green">Green</option>
      <option style="background:orange;" value="orange">Orange</option>
      <option style="background:pink;" value="pink">Pink</option>
      <option style="background:purple;" value="purple">Purple</option>
      <option style="background:red;" value="red">Red</option>
      <option style="background:teal;" value="teal">Teal</option>
      <option style="background:white;" value="white">White</option>
      <option style="background:yellow;" value="yellow">Yellow</option>
     </select>
    </td>
    <td class="center">
     <select name="image_google_auto[url][imgc]">
      <option value="">---<?php echo $text_colorization; ?>---</option>
      <option value="gray">Gray</option>
      <option value="color">Color</option>
     </select>
    </td>
    <td class="center">
     <select name="image_google_auto[url][imgsz]">
      <option value="">---<?php echo $text_size; ?>---</option>
      <option value="icon">Icon</option>
      <option value="small">Small</option>
      <option value="medium">Medium</option>
      <option value="large">Large</option>
      <option value="xlarge">Xlarge</option>
      <option value="xxlarge">Xxlarge</option>
      <option value="huge">Huge</option>
     </select>
    </td>
    <td class="center">
     <select name="image_google_auto[url][imgtype]">
      <option value="">---<?php echo $text_image_type; ?>---</option>
      <option value="face">Face</option>
      <option value="photo">Photo</option>
      <option value="clipart">Clipart</option>
      <option value="lineart">Lineart</option>
     </select>
    </td>
   </tr>
   <tr>
    <td class="left" colspan="8"><b><?php echo $text_keyword; ?>: <?php echo $keyword_field; ?></b></td>
   </tr>
   <tr>
    <td class="center" colspan="8"><a class="btn btn-success" onclick="editTool('<?php echo $product_id; ?>', 'image_google_auto', 'add');"><?php echo $text_add; ?></a></td>
   </tr>
  </tfoot>
 </table>
</form>
<script type="text/javascript"><!--

//--></script>