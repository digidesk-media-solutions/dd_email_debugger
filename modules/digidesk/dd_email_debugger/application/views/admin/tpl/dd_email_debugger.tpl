<!DOCTYPE html>
<html lang="de">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>E-Mail Template Debugger</title>

        <!-- Bootstrap core CSS -->
        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.4/css/bootstrap.min.css">

        <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
        <!--[if lt IE 9]>
        <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
        <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
        <![endif]-->
    </head>

    <body>
        <div class="container">
            [{assign var="oConfig" value=$oView->getConfig()}]
            [{assign var="oUser" value=$oViewConf->getUser()}]
            [{assign var="aEdit" value=$oConfig->getRequestParameter('editval')}]

            <h1 class="page-header">E-Mail Template Debugger</h1>

            <form name="myedit" enctype="multipart/form-data" id="myedit" class="form-horizontal" action="[{$oViewConf->getSelfLink()}]" method="post">
                [{$oViewConf->getHiddenSid()}]
                <input type="hidden" name="cl" value="dd_email_debugger">
                <input type="hidden" name="fnc" value="sendMail">

                <div class="form-group">
                    <label for="template" class="col-sm-2 control-label">Template</label>
                    <div class="col-sm-10">
                        <select class="form-control" name="editval[template]" id="template" required="required">
                            <option value="">Bitte wählen</option>
                            [{foreach from=$oView->getMailTemplates() key="sValue" item="sName"}]
                                <option value="[{$sValue}]"[{if $aEdit.template == $sValue}] selected="selected"[{/if}]>[{$sName}]</option>
                            [{/foreach}]
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="receiver" class="col-sm-2 control-label">Empfänger</label>
                    <div class="col-sm-10">
                        <input type="email" class="form-control" name="editval[receiver]" id="receiver" value="[{$aEdit.receiver|default:$oUser->oxuser__oxusername->value}]" placeholder="info@example.com" required="required"/>
                    </div>
                </div>

                <div class="form-group">
                    <div class="col-sm-offset-2 col-sm-10">
                        <input type="submit" class="btn btn-primary" name="editval[send]" value="E-Mail senden"/>
                        <input type="submit" class="btn btn-default" name="editval[html_preview]" value="HTML-Vorschau anzeigen"/>
                        <input type="submit" class="btn btn-default" name="editval[plain_preview]" value="Text-Vorschau anzeigen"/>
                    </div>
                </div>
            </form>

            [{if $aEdit.html_preview || $aEdit.plain_preview}]
                <hr/>
                <iframe src="[{$oViewConf->getSelfLink()}]&cl=dd_email_debugger&fnc=sendMail&editval[template]=[{$aEdit.template}]&editval[preview]=[{if $aEdit.html_preview}]html[{else}]plain[{/if}]&editval[iframe]=1" frameborder="0" style="width:100%;height:500px;" class="well well-sm"></iframe>
            [{/if}]

        </div> <!-- /container -->

        <script src="https://code.jquery.com/jquery-1.11.2.min.js"></script>
        <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.4/js/bootstrap.min.js"></script>
        <script src="[{$oViewConf->getModuleUrl('dd_email_debugger','out/src/js/scripts.js')}]"></script>
    </body>
</html>
