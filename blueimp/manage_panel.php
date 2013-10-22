<?php
echo"
    <form id=$folderid class='manage' action='server/php/?folder=$foldername' method='POST' enctype='multipart/form-data'>
        <div class='row fileupload-buttonbar'>
            <div class='span7'>
                <span class='btn btn-success fileinput-button'>
                    <i class='icon-plus icon-white'></i>
                    <span>Add files...</span>
                    <input type='file' name='files[]' multiple>
                </span>
                <button type='submit' class='btn btn-primary start'>
                    <i class='icon-upload icon-white'></i>
                    <span>Start upload</span>
                </button>
                <button type='reset' class='btn btn-warning cancel'>
                    <i class='icon-ban-circle icon-white'></i>
                    <span>Cancel upload</span>
                </button>
                <button type='button' class='btn btn-danger delete'>
                    <i class='icon-trash icon-white'></i>
                    <span>Delete</span>
                </button>
                <input type='checkbox' class='toggle'>
            </div>
            <div class='span5 fileupload-progress fade'>
                <div class='progress progress-success progress-striped active' role='progressbar' aria-valuemin='0' aria-valuemax='100'>
                    <div class='bar' style='width:0%;'></div>
                </div>
                <div class='progress-extended'>&nbsp;</div>
            </div>
        </div>
        <div class='fileupload-loading'></div>
        <br>
        <table role='presentation' class='table table-striped'><tbody class='files' data-toggle='modal-gallery' data-target='#modal-gallery'></tbody></table>
    </form>";
?>
