$J(function(){
    var csvData;
    var xhr;
    totalRows  = 0;
    var currentRow = 1;
    var fileUri;
    var refreshIntervalId;
    $J('.choose-image').click(function(){
        $J('#importUploader').trigger('click');
    });
    $J('#importfile').change(function(){
        var file = $J(this).val();
        var filename = file.replace(/^.*[\\\/]/, '');

        $J('.choose-image-content .file-text').html(filename);
        $J('.choose-image-text').fadeOut(function(){
            $J('.choose-image-content').fadeIn();
        });
        $J('#start_upload').removeClass('disabled');
    });
    $J('#start_upload').click(function(){
        if (!$J(this).hasClass('disabled')) {
            $J('.ui-state-highlight p span.text_msg, .ui-state-error p span.text_msg').html('');
            $J('.ui-state-highlight, .ui-state-error').hide();

            fileName = $J('#fileName').val();
            var ext = fileName.split('.').pop();

            if (ext == 'csv') {
                return true;
            } else {
                $J('.ui-state-error p span.text_msg').html('Please choose a csv to upload');
                $J('.ui-state-error').fadeIn('slow');
                $J('html, body').animate({
                    scrollTop: $J(".ui-state-error").offset().top
                }, 2000);
            }
        }
        return false;
    });

    $J(".reNext a").click(function(){
        currentRow++;

        if (currentRow == totalRows) {
            $J(".reNext").hide();
        }
        $J.ajax({
            url : "index.php?cmd=Crm&act=settings&tpl=interface&subTpl=getCsvRecord&currentRow="+currentRow,
            type : "post",
            data : $J('#frmImport, #frmImport2').serialize(),
            dataType:  'json',
            success: function(json) {
                var newcsvData   = $J.parseJSON($J.base64Decode(json.contactData));
                if (currentRow <= totalRows) {
                    $J("table#mapCSVColumn tbody tr").each(function(index){
                        $J(this).find("td").eq(2).html(newcsvData[currentRow][index]);
                    });
                    $J(".rePrevious").show();
                }
            }
        });
    });

    $J(".rePrevious a").click(function(){
        currentRow--;

        if (currentRow == 1) {
            $J(".rePrevious").hide();
        }
        $J.ajax({
            url : "index.php?cmd=Crm&act=settings&tpl=interface&subTpl=getCsvRecord&currentRow="+currentRow,
            type : "post",
            data : $J('#frmImport, #frmImport2').serialize(),
            dataType:  'json',
            success: function(json) {
                var newcsvData   = $J.parseJSON($J.base64Decode(json.contactData));
                if (currentRow >= 1) {
                    $J("table#mapCSVColumn tbody tr").each(function(index){
                        $J(this).find("td").eq(2).html(newcsvData[currentRow][index]);
                    });
                    $J(".reNext").show();
                }
            }
        });
    });

    $J('#frmImport').bind('submit', function(e) {
        e.preventDefault(); // <-- important
        $J(this).ajaxSubmit({
            dataType:  'json',
            beforeSend: function() {
                $J('.import_step1 .actions').hide();
                $J('.import_step1 .ajax_loading').show();
            },
            success: function(json) {
                var responseData = $J.parseJSON($J.base64Decode(json.data));
                csvData   = $J.parseJSON($J.base64Decode(json.contactData));
                totalRows = json.totalRows;
                fileUri   = json.fileUri;
                $J('#fileUri').val(json.fileUri);
                $J(".rePrevious, .reNext").hide();
                if (totalRows > currentRow) {
                    $J(".reNext").show();
                }
                var stepTwoArea = $J("#mapCSVColumn tbody");

                stepTwoArea.html("");

                $J("#csvColumnTemplate").tmpl(responseData, {
                    renderSelectBox: function(rowIndex, csvColumn) {
                        var columnSelector = $J("#crm_contact_option_base").clone();

                        columnSelector.attr("id", $J.vprintf( "columnSelector_%d", [ rowIndex ] ));
                        columnSelector.find('option:contains("'+csvColumn+'")').attr('selected','selected');
                        columnSelector.show();

                        return $J("<div>").append(columnSelector).html();

                    }
                }).appendTo(stepTwoArea);

                var selectBox = $J("#mapCSVColumn select");

                selectBox.live("change", function() {
                    var curSelector = $J(this);

                    if (curSelector.find("option:first").is(":selected")) {
                        curSelector.parents("td").addClass("not_match");
                        curSelector.parents("tr").removeClass("matched");
                    } else {
                        curSelector.parents("tr").addClass("matched");
                        curSelector.parents("td").removeClass("not_match");
                    }
                    if ($J("#mapCSVColumn tr.matched").length) {
                        $J("#import_data").removeClass("disabled");
                        $J('#import_data').removeAttr('disabled');
                    } else {
                        $J("#import_data").addClass("disabled");
                        $J('#import_data').attr('disabled', 'disabled');
                    }
                });

                selectBox.change();

                $J('#step1').fadeOut(function(){
                    $J('#step2').fadeIn('slow');
                });

            }
        });

        // !!! Important !!!
        // always return false to prevent standard browser submit and page navigation
        return false;
    });
    $J('#frmImport2').bind('submit', function(e) {
        e.preventDefault(); // <-- important
        csvImport($J(this));
        // !!! Important !!!
        // always return false to prevent standard browser submit and page navigation
        return false;
    });

    // Initialize the csv import process and show progress bar with complete percentage
    function csvImport(elm) {
        var sendRequest = true;
        xhr = $J.ajax({
            type : 'post',
            data : $J('#frmImport, #frmImport2').serialize(),
            url  : elm.attr('action'),
            dataType : 'json',
            beforeSend : function() {
                $J('.import_step2 .actions').hide();
                $J('.import_step2 .ajax_loading').show();
                $J('#step2').fadeOut(function(){
                    $J('#step3').fadeIn('slow');
                });
            },
            success : function(data) {
                var totalRecord = totalRows + 1;
                var percent = (data.processedRows / totalRecord) * 100;
                progress(percent, $J('#progressBar'));
                $J('#progressDetails .processed').text(data.processedRows);
                $J('#progressDetails .total').text(totalRecord);
                $J('#progressDetails .imported').text(data.importedRows);
                $J('#progressDetails .skiped').text(data.skippedRows);
                $J('#progressDetails').show();
                if ((data.status == 'success') && (data.processedRows < totalRecord)) {
                    csvImport($J('#frmImport2'));
                } else {
                    $J('#importMsg').text(data.message);
                    $J('#cancelled').addClass('disabled');
                    $J('#Done').removeClass('disabled');
                    $J('#cancelled').attr('disabled', 'disabled');
                    $J('#Done').removeAttr('disabled');
                    $J('#progressBar').hide(2000);
                }
            }
        });
    }

    $J.ajax({
        dataType: "json",
        url     : "index.php?cmd=Crm&act=settings&tpl=interface&subTpl=importoptions",
        success : function loadImportOptions(data) {
            $J("#columnSelectorTemplate").tmpl(data).appendTo("#crm_contact_option_base");
            $J("#crm_contact_option_base").find("option[name=header]").each(function() {
                var curItem = $J(this);

                curItem.nextUntil('option[name=header]').wrapAll($J("<optgroup>").attr("label", curItem.val()));

                curItem.remove();
            });
        }
    });
    $J('#cancelled').live('click', function(){
        xhr.abort();
        $J('#cancelled').addClass('disabled');
        $J('#Done').removeClass('disabled');
        $J('#cancelled').attr('disabled', 'disabled');
        $J('#Done').removeAttr('disabled');
    });
});

// Set width to a progress bar html element
function progress(percent, element) {
    percent = Math.round(percent);
    if (percent >= 1) {
        var div = element.find('div');
        div.css('width', percent + '%');
        div.html(percent + '%&nbsp;');
    }
}
