{strip}
{if $addDoc}
{assign var="headerSection" value="Add Document" scope="global"}
{else}
{assign var="headerSection" value="Edit Document" scope="global"}
{/if}
{assign var="title" value=$headerSection scope="global"}
{include file="assets/page-header.tpl"}
{assign var="backURL" value="course-documents/" scope="global"}
{assign var="backText" value="Back to documents" scope="global"}
{include file="assets/back-button.tpl"}
<div class="card border-primary" id="editgroup">
    <div class="card-header bg-primary font-weight-bold">{$headerSection}</div>
    <div class="card-body pb-0">
        {if $doc_groups}
            <form method="post" action="" enctype="multipart/form-data" class="form-horizontal">
                <div class="form-group row">
                    <label for="document" class="col-md-3 control-label"><span class="text-danger">*</span> Document</label>
                    <div class="col-md-9 form-inline">
                        <input type="file" name="document" id="document" />
                    </div>
                </div>
                <div class="form-group row{if $error && !$doc_group} has-error{/if}">
                    <label for="doc_title" class="col-md-3 control-label"><span class="text-danger">*</span> Group:</label>
                    <div class="col-md-9">
                    <select name="doc_group" id="doc_group" class="form-control">
                        {foreach $doc_groups as $group}
                            <option value="{$group.id}"{if $group.id == $item.group_id} selected="selected"{/if}>{$group.name}</option>
                        {/foreach}
                    </select>
                    </div>
                </div>
                <div class="form-group row{if $error && !$doc_title} has-error{/if}">
                    <label for="link_text" class="col-md-3 control-label"><span class="text-danger">*</span> Document Title:</label>
                    <div class="col-md-9"><input type="text" name="link_text" id="link_text" value="{if $smarty.post.link_text}{$smarty.post.link_text}{else}{$item.link_text}{/if}" size="9" placeholder="Document title (text to display)" class="form-control" /></div>
                </div>
                <div class="form-group row">
                    <label for="doc_desc" class="col-md-3 control-label">Description:</label>
                    <div class="col-md-9"><textarea name="additional[description]" id="doc_desc" cols="3" placeholder="Document description" class="form-control">{if $smarty.post.doc_desc}{$smarty.post.doc_desc}{else}{$item.description}{/if}</textarea></div>
                </div>
                <div class="form-group row">
                    <div class="col-md-9 offset-md-3"><label class="sr-only" for="submitbtn">Submit</label><input name="submitform" id="submitbtn" class="btn btn-success" type="submit" value="{$headerSection}" /></div>
                </div>
            </form>
        {else}
            Please make sure a document group exists before you can add a new document
        {/if}
    </div>
</div>
{assign var="footerBtn" value="true" scope="global"}
{include file="assets/back-button.tpl"}
{/strip}