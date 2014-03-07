<div class="modal-header">
<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
<h4>Add</h4>
</div>
<div class="wizard" id="{$name}">
  <ul class="steps">
    {foreach $steps as $step}
    <li data-target="#{$name}_{$step@index + 1}"{if $step@index == 0} class="active"{/if}><span class="badge badge-info">{$step@index + 1}</span>{$step@key}<span class="chevron"></span></li>
    {/foreach}
  </ul>
</div>
<div class="row-divider"></div>
<form role="form" class="form-horizontal" id="wizard_form">
  <div class="step-content">
   {foreach $steps as $step}
   <div class="step-pane{if $step@index == 0} active{/if}" id="{$name}_{$step@index + 1}">
     {foreach $step['default'] as $component}
       {$formElements[$component['name']]['html']}
     {/foreach}
   </div>
   {/foreach}
  </div>
<div class="modal-footer">
  <button class="btn btn-default btn-prev" disabled>{t}Prev{/t}</button>
  <button class="btn btn-default btn-next" data-last="{t}Finish{/t}" id="wizard_submit">{t}Next{/t}</button>
</div>
</form>
<script>
$(function() {
  $("#wizard_submit").click(function (event) {
    if ($(this).text() != "{t}Finish{/t}") {
      return true;
    }
    $.ajax({
      url: "{url_for url=$validateUrl}",
      type: "POST",
      dataType: 'json',
      data: $("#wizard_form").serializeArray(),
      context: document.body
    })
    .success(function(data, status, jqxhr) {
      alertClose();
      if (data.success) {
        {if isset($formRedirect) && $formRedirect}
          window.location='{url_for url=$formRedirectRoute}';
        {else}
          alertMessage("The object has been successfully saved", "alert-success");
        {/if}
      } else {
        alertMessage("An error occured", "alert-danger");
      }
    });
    return false;
    });
    {$customJs}
});
</script>