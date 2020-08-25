{strip}
<h3 class="text-center">Confirm Delete</h3>
<p class="text-center">Are you sure you wish to delete the <strong>{$item.title}</strong> item?</p>
<div class="text-center">
<form method="post" action="" class="form-inline">
    <a href="/student/learning/{$courseInfo.url}/reading-list/" title="No, return to reading list" class="btn btn-success">No, return to reading list</a> &nbsp; 
    <input type="submit" name="confirmdelete" id="confirmdelete" class="btn btn-danger" value="Yes, delete item" />
</form>
</div>
{/strip}