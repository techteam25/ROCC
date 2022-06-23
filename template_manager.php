<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8"/>
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
        <link rel="stylesheet" href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/themes/smoothness/jquery-ui.css">
        <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
        <script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>
        <script src="js/template_manager.js"></script>
        <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/dt/dt-1.10.18/datatables.min.css"/>
        <script type="text/javascript" src="https://cdn.datatables.net/v/dt/dt-1.10.18/datatables.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-modal/0.9.1/jquery.modal.min.js"></script>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery-modal/0.9.1/jquery.modal.min.css" />
    </head>
</html>
<?php include("header.html"); ?>
<button id='parentDirIcon'><a class='material-icons'>arrow_back</i></button>
<button id='uploadFileDir'><a href="#files" rel="modal:open" class='material-icons'>cloud_upload</a></button>
<button id='uploadZip'><a href="#zip" rel="modal:open" class='material-icons'>unarchive</a></button>
<button id='createDirIcon'><i class='material-icons'>create_new_folder</i></button>
<table id="directories" class="display" width="100%"></table>
<div id="files" class="modal">
    <form enctype="multipart/form-data"
          id="uploadFiles" 
          action="API/UploadFiles.php" 
          method="post">
        Select files to upload:
        <input type="file" name="file[]" multiple/>
        <input type="submit" value="Upload Files" name="submit"/>
    </form>
</div>
<div id="zip" class="modal">
    <form enctype="multipart/form-data"
          id="uploadZips" 
          action="API/UploadZip.php" 
          method="post">
        Select zip archive(s) to upload:
        <input type="file" name="file[]" accept=".ZIP" multiple/>
        <input type="submit" value="Upload Files" name="submit"/>
    </form>
</div>
