$J.fn.exists = function(){
    return this.length>0;
}
$J.dropdownToggle({
    dropdownID: 'EmailCategoriesPanel',
    switcherSelector: '.email_list a',
    noActiveSwitcherSelector: '#websiteAndSocialProfilesContainer .input_with_type a.social_profile_category',
    addTop: 0,
    addLeft: 0
});
$J.dropdownToggle({
    dropdownID: 'WebsiteCategoriesPanel',
    switcherSelector: '.website_list a.category',
    addTop: 0,
    addLeft: 0
});
$J.dropdownToggle({
    dropdownID: 'SocialCategoriesPanel',
    switcherSelector: '.social_list a.category',
    addTop: 0,
    addLeft: 0
});
$J.dropdownToggle({
    dropdownID: 'PhoneCategoriesPanel',
    switcherSelector: '.phone_list a',
    noActiveSwitcherSelector: '#websiteAndSocialProfilesContainer .input_with_type a.social_profile_category',
    addTop: 0,
    addLeft: 0
});


function showEmailCategoriesPanel(switcherUI) {
    $J("#EmailCategoriesPanel a.dropDownItem").unbind('click').click(function() {
        _changeBaseCategory(switcherUI, $J(this).text(), $J(this).attr("category"));
    });
}
function showsocialProfilesPanel(switcherUI) {
    $J("#SocialCategoriesPanel a.dropDownItem").unbind('click').click(function() {
        _changeSocialCategory(switcherUI, $J(this).text(), $J(this).attr("category"));
    });
}
function showwebsiteProfilesPanel(switcherUI) {
    $J("#WebsiteCategoriesPanel a.dropDownItem").unbind('click').click(function() {
        _changeWebsiteProfile(switcherUI, $J(this).text(), $J(this).attr("category"));
    });
}
function showphoneCategoriesPanel(switcherUI) {
    $J("#PhoneCategoriesPanel a.dropDownItem").unbind('click').click(function() {
        _changePhoneCategory(switcherUI, $J(this).text(), $J(this).attr("category"));
    });
}
var _changePhoneCategory = function(Obj, text, category) {
    $J(Obj).text(text);
    var $inputObj = $J(Obj).closest('.phone_list').find('input');
    var parts = $inputObj.attr('name').split('_');
    parts[2] = category;
    $inputObj.attr('name', parts.join('_'));
    $J("#PhoneCategoriesPanel").hide();
};
var _changeWebsiteProfile = function(Obj, text, category) {
    $J(Obj).text(text);
    var $inputObj = $J(Obj).closest('.website_list').find('input');
    var parts = $inputObj.attr('name').split('_');
    parts[2] = category;
    $inputObj.attr('name', parts.join('_'));
    $J("#WebsiteCategoriesPanel").hide();
};

var _changeSocialCategory = function(Obj, text, category) {
    $J(Obj).text(text);
    var $inputObj = $J(Obj).closest('.social_list').find('input');
    var parts = $inputObj.attr('name').split('_');
    parts[2] = category;
    $inputObj.attr('name', parts.join('_'));
    $J("#SocialCategoriesPanel").hide();
};
var _changeBaseCategory = function(Obj, text, category) {
    $J(Obj).text(text);
    var $inputObj = $J(Obj).closest('.email_list').find('input');
    var parts = $inputObj.attr('name').split('_');
    parts[2] = category;
    $inputObj.attr('name', parts.join('_'));
    $J("#EmailCategoriesPanel").hide();
};
var resetAddAddressLink = function() {
    $J('#addressContainer').children('div:not(:first)').find(".crm_addNewLink_address").hide();
    $J('#addressContainer').children('div:last').find(".crm_addNewLink_address").show();
}
var resetAddressPrimaryInputs = function() {
    $J("#addressContainer .is_primary_address:not(:first)").each(function(){
        if ($J(this).hasClass("primary_field_address")) primary = 1;
        else primary = 0;

        var $inputObj = $J(this).closest('.address_list_container').find('.contactAddress');
        var parts = $inputObj.attr('name').split('_');
        parts[3] = primary;

        $inputObj.attr('name', parts.join('_'));
    });
}
cx.ready(function(){
    fn = function(objUser) {
        $J.getJSON( 'index.php?cmd=Crm&act=checkAccountId&id='+objUser.id+'&email='+objUser.name, function( data ) {
            $J.each(data, function(key, val) {
                if (val.show) {
                    $J("#contact_email").val(val.email);
                    $J('#send_account_notification').prop('checked', false);
                    $J(".showAccountDetail").hide();
                } else {
                    $J(".showAccountDetail").hide();
                }
                if (val.sendLoginCheck) {
                    $J(".showAccountDetail").show();
                    $J('#send_account_notification').prop('checked', true);
                }
                if (val.setDefaultUser || val.id ) {
                    $J("#contactId").val(val.id);
                    $J("#contact_email").val(val.email);
                    $J(".live-search-user-title").text(val.setDefaultUser);
                }
            });
        });
    }
    cx.bind("userSelected", fn, "user/live-search/contactId");
    cx.bind("userCleared", function() { $J("#contact_email").val(''); $J(".showAccountDetail").hide(); }, "user/live-search/contactId");
}, true);

$J(document).ready(function() {

    $J("#assigned_memberships").chosen().change(function(){
        $assGrp  = $J(this);
        $mainGrp = $J("#main_membership");
        var selectedVal = $mainGrp.val();

        $J(".main-membership-holder").hide();
        //$mainGrp.hide();
        $mainGrp.find("option").remove();
        var first = true;
        ($assGrp.find("option")).each(function(){
            if (!first) {
                $J(".main-membership-holder").show();
            }

            if (this.selected) {
                elm = $J(this).clone();
                if (selectedVal == elm.val()) {
                    elm.attr("selected", "selected");
                }
                $mainGrp.append(elm);
            }

            first = false;
        });
        $J("#main_membership").trigger("liszt:updated");
        $J('#assigned_memberships .chzn-container').mousedown();
    });

    $J("#main_membership").chosen();
    $J(".chzn-select-deselect").chosen({
        allow_single_deselect:false
    });
    //    $J("#advanced").hide();
    $J(".crm-AddressAdd").live("click", function(){
        newElm   = $J("#addressContainer div:first").clone().attr("style", "");
        emailCount++;
        Addr    = "contactAddress_"+emailCount+"_1_0";
        City    = "contactAddress_"+emailCount+"_2_0";
        State   = "contactAddress_"+emailCount+"_3_0";
        Zip     = "contactAddress_"+emailCount+"_4_0";
        Country = "contactAddress_"+emailCount+"_5_0";
        Type    = "contactAddress_"+emailCount+"_6_0";

        newElm.find(".contactAddress").attr("name", Addr);
        newElm.find(".contactCity").attr("name", City);
        newElm.find(".contactState").attr("name", State);
        newElm.find(".contactZip").attr("name", Zip);
        newElm.find(".cCountry").attr("name", Country);
        newElm.find(".contactType").attr("name", Type);

        $J(this).closest("#addressContainer").append(newElm);
        resetAddAddressLink();
    });
    $J(".is_primary_address").live("click", function() {
        $J("#addressContainer .is_primary_address:not(:first)").removeClass("primary_field_address");
        $J("#addressContainer .is_primary_address:not(:first)").removeClass("not_primary_field_address");
        $J(this).addClass("primary_field_address");
        $J("#addressContainer .is_primary_address:not(.primary_field_address)").addClass("not_primary_field_address");

        resetAddressPrimaryInputs();
    });
    $J(".crm_deleteLink_address").live("click", function() {
        count = $J('#addressContainer > div').length;
        if (count <= 2) return;

        $J(this).closest(".address_list_container").remove();
        resetAddAddressLink();

        if ($J("#addressContainer .primary_field_address").length < 1) {
            $J("#addressContainer a.is_primary_address:visible").eq(0).removeClass("not_primary_field_address");
            $J("#addressContainer a.is_primary_address:visible").eq(0).addClass("primary_field_address");
        }
        resetAddressPrimaryInputs();
    });
    var cache = {},
    lastXhr;
    $J( "#company" ).autocomplete({
        minLength: 1,
        source: function( request, response ) {
            var term = request.term;
            if ( term in cache ) {
                response( cache[ term ] );
                return;
            }

            lastXhr = $J.getJSON( "./index.php?cmd=Crm&act=getcustomers", request, function( data, status, xhr ) {
                cache[ term ] = data;
                if ( xhr === lastXhr ) {
                    response( data );
                }
            });
        },
        select: function(event, ui) {
            $J('#companyId').val(ui.item.id);
            resetCustomerType();
        }
    });
    $J(".crm-addNewLink").live("click", function(){
        elm = $J(this).attr("name");
        count = $J('#'+elm+'Container > div').length;
        emailCount++;

        switch (elm) {
            case 'website':
                if (contactType == 'company')
                    elmName     = "contact"+elm+"_"+emailCount+'_3_0';
                else
                    elmName     = "contact"+elm+"_"+emailCount+'_1_0';
                break;
            case 'email':
                if (contactType == 'company')
                    elmName     = "contact"+elm+"_"+emailCount+'_1_0';
                else
                    elmName     = "contact"+elm+"_"+emailCount+'_0_0';
                break;
            case 'social':
                elmName     = "contact"+elm+"_"+emailCount+'_4_0';
                break;
            default:
                elmName     = "contact"+elm+"_"+emailCount+'_1_0';
                break;
        }

        var newElem = $J('#'+elm+'Container div:first').clone().attr('style', '');
        newElem.find("."+elm+"_list input").attr("name", elmName);
        $J('#'+elm+'Container').append(newElem);
        resetAddEmailLink(elm);
    });
    $J(".is_primary").live("click", function() {
        elm = $J(this).attr("name");
        $J("#"+elm+"Container .is_primary:not(:first)").removeClass("primary_field");
        $J("#"+elm+"Container .is_primary:not(:first)").removeClass("not_primary_field");
        $J(this).addClass("primary_field");
        $J("#"+elm+"Container .is_primary:not(.primary_field)").addClass("not_primary_field");

        $J("#"+elm+"Container .is_primary:not(:first)").each(function(){
            if ($J(this).hasClass("primary_field")) primary = 1;
            else primary = 0;

            var $inputObj = $J(this).closest('.'+elm+'_list_container').find('.'+elm+'_list').find('input');
            var parts = $inputObj.attr('name').split('_');
            elmName     = parts[3] = primary;

            $inputObj.attr('name', parts.join('_'));
        });
    });
    $J(".crm-deleteLink").live("click", function() {
        elm = $J(this).attr("name");
        count = $J('#'+elm+'Container > div').length;
        if (count <= 2) return;

        $J(this).closest("div."+elm+"_list_container").remove();
        resetAddEmailLink(elm);
    });
    $J(".contactAddress, .contactCity, .contactState, .contactZip, .selectCustomer").live('focus', function(){
        $J(this).removeClass("crm-watermark");
    });
    $J(".contactAddress, .contactCity, .contactState, .contactZip, .selectCustomer").live('blur', function(){
        if ($J(this).val() == '') {
            $J(this).addClass("crm-watermark");
        } else {
            $J(this).removeClass("crm-watermark");
        }
    });
    resetAddEmailLink('email');
    resetAddEmailLink('phone');
    resetAddEmailLink('website');
    resetAddEmailLink('social');
    resetAddAddressLink();
    $J(".contactAddress, .contactCity, .contactState, .contactZip, .selectCustomer").trigger("blur");
});

function resetAddEmailLink(elm) {
    $J('#'+elm+'Container').children('div:not(:first)').find(".crm-addNewLink").hide();
    $J('#'+elm+'Container').children('div:last').find(".crm-addNewLink").show();

    if ($J("#"+elm+"Container .primary_field").length < 1) {
        $J("#"+elm+"Container a.is_primary:visible").eq(0).removeClass("not_primary_field");
        $J("#"+elm+"Container a.is_primary:visible").eq(0).addClass("primary_field");
        resetInputNames(elm);
    }
}
function resetInputNames(elm) {
    $J(".email_list_container:visible").each(function(){
        category = $J(this).find(".email_list a").text();
        cat = $J(".dropDownContent a:contains("+category+")").attr("category");
    });
}
function isSpecialChars(s)
{
    var i;
    for (i = 0; i < s.length; i++)
    {
        // Check that current character special chars.
        if (iChars.indexOf(s.charAt(i)) == -1) {
            return false;
        }
    }
    // All characters are special chars.
    return true;
}
function isInteger(s)
{
    var i;
    for (i = 0; i < s.length; i++)
    {
        // Check that current character is number.
        var c = s.charAt(i);
        if (((c < "0") || (c > "9"))) return false;
    }
    // All characters are numbers.
    return true;
}
function isSpecialInteger(s) {
    var i;
    for (i = 0; i < s.length; i++)
    {
        var c = s.charAt(i);
        // Check that current character is number or specialChars.
        if (isNaN(c) && iChars.indexOf(s.charAt(i)) == -1) {
            return false;
        }
    }
    // All characters are numbers and specialChars.
    return true;
}
function trim(sString)
{
    while (sString.substring(0,1) == ' ')
    {
        sString = sString.substring(1, sString.length);
    }
    while (sString.substring(sString.length-1, sString.length) == ' ')
    {
        sString = sString.substring(0,sString.length-1);
    }
    return sString;
}
function checkaddContact() {

    $J("input:not(.mInput) , select").css("border","1px solid #0A50A1");
    var errors = new Array();
    var errChk;
    function checkEmail(myForm) {
        if (/^\w+([\.-]?\w+)*@\w+([\.-]?\w+)*(\.\w{2,4})+$/.test(myForm)){
            return (true)
        }
        return (false)
    }
    contact_id = $J("#customer_id").val();
    contact_gender = $J("#contact_gender").val();
    if(($J("#contact_gender").exists()) && $J.trim(contact_gender) == "") {
        errChk = 1;
        $J("#contact_gender").css("border", "1px solid red");
    }

    customerId = $J("#customerId").val();
    customerType = $J("#customer_type").val();
    currency = $J("#currency").val();
    var condition = true;
    if (contactType == 'contact') {
        if ($J("#companyId").val() == '' || $J("#companyId").val() == 0) {
            condition = true;
        } else{
            condition = false;
        }
    }
    if (condition) {
        if(($J("#customer_type").exists() && $J("#customer_type").is(":visible")) && $J.trim(customerType) == "") {
            errChk = 1;
            $J("#customer_type").css("border", "1px solid red");
        }

        if(($J("#currency").exists()) && $J.trim(currency) == "") {
            errChk = 1;
            $J("#currency").css("border", "1px solid red");
            $J("#currency").val("");
        }
    }

    companyName = $J("#companyName").val();
    if($J("#companyName").exists() && $J.trim(companyName) == "") {
        errChk = 1;
        $J("#companyName").css("border", "1px solid red");
        $J("#companyName").val("");
    }


    /*if($J("#contact_name").exists() && $J.trim($J("#contact_name").val()) == "") {
        errChk = 1;
        $J("#contact_name").css("border", "1px solid red");
    }*/
    if($J("#family_name").exists() && $J.trim($J("#family_name").val()) == "") {
        errChk = 1;
        $J("#family_name").css("border", "1px solid red");
    }
    if ($J("#contactId").val() == 0) {
        if ($J("#contact_email").exists()) {
            emailField = $J("#contact_email");
            if(emailField.hasClass('mantatory') && checkEmail(emailField.val()) == false) {
                errChk = 1;
                emailField.css("border", "1px solid red");
            }
        }
    }

    if (contact_id == 0) {
        if($J("#contact_password").exists() && $J("#contact_password").is(":visible") && $J("#contact_password").hasClass('mantatory') && $J.trim($J("#contact_password").val()) == "") {
            errChk = 1;
            $J("#contact_password").css("border", "1px solid red");
        }
    }
    if (contactType == 'company') {
        $J("div.email_list input:visible").each(function (){
            $J(this).parent().css("border", "1px solid #0A50A1");
            if ($J.trim($J(this).val()) == "") {
                errChk = 1;
                $J(this).parent().css("border", "1px solid red");
            } else if (checkEmail($J(this).val()) == false) {
                errChk = 1;
                $J(this).val("");
                $J(this).parent().css("border", "1px solid red");
            }
        });
    }

    if($J("#language").exists() && $J.trim($J("#language").val()) == "") {
        errChk = 1;
        $J("#language").css("border", "1px solid red");
    }
    if ( errors.length >= 1 || errChk == 1) {
        if ( errors.length >= 1 && errChk == 1) {
            errString = errors.join('<br />');
            $J('#newContact').html(cx.variables.get('TXT_CRM_MANDATORY_FIELDS_NOT_FILLED_OUT', 'modifyContact')+'<br />'+errString);
        } else if (  errors.length >= 1) {
            errString = errors.join('<br />');
            $J('#newContact').html(errString);
        } else {
            $J('#newContact').html(cx.variables.get('TXT_CRM_MANDATORY_FIELDS_NOT_FILLED_OUT', 'modifyContact'));
        }
        $J('#newContact').css("display","block");
        return false;
    } else {
        $J('#newContact').html('');
        $J('#newContact').css("display","none");
        return true;
    }
}
$J(function() {
    $J("select").change(function () {
        $J(this).css("border","1px solid #0A50A1");
    });
    $J("input:not(.mInput, .default)").keyup(function () {
        $J(this).css("border","1px solid #0A50A1");
    });
    $J("input.mInput").keyup(function () {
        $J(this).parent().css("border","1px solid #0A50A1");
    });

    var cache = {},
    lastXhr;
    $J( "#selectContact" ).autocomplete({
        minLength: 0,
        source: function( request, response ) {
            var term = request.term;
            if ( term in cache ) {
                source = [];
                $J.each(cache[ term ], function(index, value){
                    if ($J.inArray(value.id, assignedId) == -1) {
                        source.push(value);
                    }
                });
                response( source );
                if (!source || source.length == 0) {
                    $J(".newContactSelecter").show("fast");
                } else {
                    $J(".newContactSelecter").hide("fast");
                }
                return;
            }

            lastXhr = $J.getJSON( "./index.php?cmd=Crm&act=getlinkcontacts&"+$J.now(), request, function( data, status, xhr ) {
                source = [];
                $J.each(data, function(index, value){
                    if ($J.inArray(value.id, assignedId) == -1) {
                        source.push(value);
                    }
                });
                cache[ term ] = source;
                if ( xhr === lastXhr ) {
                    response( source );
                }
                if (!source || source.length == 0) {
                    $J(".newContactSelecter").show("fast");
                } else {
                    $J(".newContactSelecter").hide("fast");
                }
            });
        },
        select: function(event, ui) {
            //alert(ui.item.id);
            assignedId.push(ui.item.id);
            $J.get("index.php?cmd=Crm&act=addcontact&id="+ui.item.id+"&customerid="+$J('#customer_id').val(), function(data) {
                $J("table#contacts").append(data);
                $J(".exContactSelect #selectContact").val("");
            //                $J("table#contacts tfoot").hide("fast");
            //                $J("#contactContainer .exContactSelect").hide("fast");
            });
        }
    });
    $J("#selectContact, .input-addcontact").live('focus', function() {
        $J(this).removeClass("crm-watermark");
    });
    $J("#selectContact, .input-addcontact").live('blur', function() {
        if ($J(this).val() == '') {
            $J(this).addClass("crm-watermark");
        } else {
            $J(this).removeClass("crm-watermark");
        }
    });
    $J("#addContact").click(function(){
        if ($J(this).closest("table").find("tfoot").find("div#contactContainer > div:visible").length == 0) {
            $J("#selectContact").val("");
            $J(this).closest("table").find("tfoot").show("fast");
            $J("#contactContainer .exContactSelect").show("fast");
        }

        $J(this).closest("table").find("tbody").show("fast");

        $J("#contacts .header-description").removeClass("header-expand");
        $J("#contacts .header-description").addClass("header-collapse");
    });
    $J(".header-description").click(function() {
        if ($J(this).hasClass("header-expand")) {
            $J(this).closest("table").find("tbody").show("fast");
            //$J(this).closest("table").find("tfoot").show();
            $J(this).removeClass("header-expand");
            $J(this).addClass("header-collapse");
        } else if ($J(this).hasClass("header-collapse")) {
            $J(this).closest("table").find("tbody").hide("fast");
            $J(this).closest("table").find("tfoot").hide("fast");
            $J(this).addClass("header-expand");
            $J(this).removeClass("header-collapse");
        }

    });
    $J('a[class^="removeContact_"]').live("click", function(){
        id = $J(this).attr("class").split('_')[1];

        $J.get("index.php?cmd=Crm&act=addcontact&tpl=delete&id="+id, function(data) {
            $J("a.removeContact_"+id).closest('tr').fadeOut("slow");
            $J("a.removeContact_"+id).closest('tr').remove();
            assignedId = $J.grep(assignedId, function(value) {
                return value != id;
            });
        });
    });
    $J("a.addNewContact").click(function() {
        $J('.exContactSelect').hide();
        $J('.newContactSelecter').hide();
        $J(".newContactFrom").show();
        resetNewContactForm();
        $J("#firstname").val($J("#selectContact").val());
        $J("#firstname").removeClass("crm-watermark");
    });
    $J("#contactCancel").click(function() {
        $J('.exContactSelect').show();
        $J('.newContactSelecter').show();
        $J(".newContactFrom").hide();
        resetNewContactForm();
    });
    $J("#contactSave").click(function(){
        if (!checkNewContactValidation())
            return ;
        $J.ajax( {
            type: "POST",
            url: "./index.php?cmd=Crm&act=addcontact&tpl=add",
            data: $J(".input-addcontact").serialize()+"&customer_id"+$J('#customer_id').val(),
            success: function( data ) {
                $J("table#contacts").append(data);
                $J(".newContactFrom").hide();
                $J("table#contacts tfoot").show("fast");
                $J("#contactContainer .exContactSelect").show("fast");
                $J(".exContactSelect #selectContact").val("");
            }
        } );
        resetNewContactForm()
    });

    var loading_indicator = '<div class="loading-indicator">' +
    '<div class="loading-content">' +
    '<span class="loading-text">Loading...</span>' +
    '</div>' +
    '</div>';
    $J('body').ajaxStart(function()
    {
        $J('body').append(loading_indicator);
    });

    $J('body').ajaxStop(function()
    {
        $J('.loading-indicator').remove();
    });
    $J(".delete-customer").click(function(){
        $J("#companyId").val("0");
        $J("#company").val("");
        $J(".selectCustomer").trigger("blur");
        resetCustomerType();
    });
    resetCustomerType();
    setTableRow("profile-info");

    $J('.address_list_container input, .address_list_container textarea').live('focusin', function(){
        $J(this).data('holder',$J(this).attr('placeholder'));
        $J(this).attr('placeholder','');
    });
    $J('.address_list_container input, .address_list_container textarea').live('focusout',function(){
        $J(this).attr('placeholder',$J(this).data('holder'));
    });
});
function resetCustomerType() {
    if ($J("#companyId").val() == '' || $J("#companyId").val() == 0) {
        $J(".customerTypeRow").show();
    } else {
        $J(".customerTypeRow").hide();
    }
}
function resetNewContactForm() {
    $J(".input-addcontact:not(#customer_id)").val('');
}

function editWebsiteEntry(entryId) {
    value = $J("#companyWebsite_"+entryId).val();
    name = getPromptBox(value);
    name = (name == 'null' || name == 'undefined') ? '' : name;
    cId   = $J('#customer_id').val();

    if(cId == 0){
        if ($J.trim(name) != "") {
            $J("#companyWebsite_"+entryId).val(name);
            $J("span#Website_"+entryId+" a").html(name);
        }
    }else{
        if ($J.trim(name) != "") {
            $J.ajax({
                url: "./index.php?cmd=Crm&act=modifyCustomerWebsite&cid="+cId+"&website="+name+"&webId="+entryId,
                cache: false,
                success: function(countId){
                    $J("span#Website_"+entryId+" a").html(name);
                    $J("#companyWebsite_"+entryId).val(name);
                }
            });
        }
    }
}
function deletewebsiteEntry(entryId) {
    value = $J("#companyWebsite_"+entryId).val();
    result = confirm("Please confirm to remove the website "+value+" from the customer's profile");
    cId   = $J('#customer_id').val();

    if (result == true) {
        $J.ajax({
            url: "./index.php?cmd=Crm&act=deleteCustomerWebsite&cid="+cId+"&webId="+entryId,
            cache: false,
            success: function(countId){
                $J("tr#formWebsite_"+entryId).remove();
                setTableRow("websitesTable");
            }
        });
    }
}
function checkEmail(myForm) {
    if (/^\w+([\.-]?\w+)*@\w+([\.-]?\w+)*(\.\w{2,4})+$/.test(myForm)){
        return (true)
    }
    return (false)
}
function checkNewContactValidation() {
    var errChk;
    $J(".input-addcontact").css("border", "1px solid #0A50A1");

    if($J.trim($J("#firstname").val()) == "") {
        errChk = 1;
        $J("#firstname").css("border", "1px solid red");
    }
    if($J.trim($J("#familyname").val()) == "") {
        errChk = 1;
        $J("#familyname").css("border", "1px solid red");
    }

    if($J.trim($J("#email").val()) == "" || checkEmail($J("#email").val()) == false) {
        errChk = 1;
        $J("#email").val("");
        $J("#email").css("border", "1px solid red");
    }
    if($J.trim($J("#language").val()) == "") {
        errChk = 1;
        $J("#language").css("border", "1px solid red");
    }

    if (errChk == 1) return false;
    else             return true;
}
var cusTypeDiscount = '{CUS_TYPE_DISCOUNT_ARRAY}';
var cusTypeDiscountArr = cusTypeDiscount.split(",");


$J("form#access").bind('form-pre-serialize', function() {
    var e = FCKeditorAPI.GetInstance('private_message');
    e.UpdateLinkedField();
});
function getPromptBox(value) {
    value = typeof value !== 'undefined' ? value : '';

    url = prompt("Please enter customer\'s website:",value);

    return url;
}
function editWebsiteEntry(entryId) {
    value = $J("#companyWebsite_"+entryId).val();
    name = getPromptBox(value);
    name = (name == 'null' || name == 'undefined') ? '' : name;
    cId   = $J('#customer_id').val();

    if(cId == 0){
        if ($J.trim(name) != "") {
            $J("#companyWebsite_"+entryId).val(name);
            $J("span#Website_"+entryId+" a").html(name);
        }
    }else{
        if ($J.trim(name) != "") {
            $J.ajax({
                url: "./index.php?cmd=Crm&act=modifyCustomerWebsite&cid="+cId+"&website="+name+"&webId="+entryId,
                cache: false,
                success: function(countId){
                    result = $J.parseJSON(countId);
                    if (result.errChk) {
                        alert('Website already exists');
                    } else {
                        $J("span#Website_"+entryId+" a").html(name);
                        $J("#companyWebsite_"+entryId).val(name);
                    }
                }
            });
        }
    }
}
var typewatch = function(){
    var timer = 0;
    return function(callback, ms){
        clearTimeout (timer);
        timer = setTimeout(callback, ms);
    }
}();
function checkUserNameAvailablity(customer_id, email) {
    $J.ajax({
        url        : 'index.php?cmd=Crm&act=checkuseravailablity',
        type       : 'get',
        data       : 'id='+customer_id+'&term='+$J('#contact_email').val(),
        dataType   : 'json',
        success    : function(json) {
            $J('.contact_user_container .error, .contact_user_container .success, .contact_user_container .progress').remove();
            var html = '';
            if (json['error']) {
                html = "<span class='error'><br>"+ json['error'] +"</span>";
            }
            if (json['success']) {
            //html = "<div class='success'>"+ json['success'] +"</div>";
            }

            $J('tr.contact_user_container').find('td').eq(1).append(html);
        }
    });
    $J('#contact_username').val();
}
