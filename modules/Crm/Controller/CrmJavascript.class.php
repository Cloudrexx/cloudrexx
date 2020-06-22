<?php

/**
 * Cloudrexx
 *
 * @link      http://www.cloudrexx.com
 * @copyright Cloudrexx AG 2007-2015
 *
 * According to our dual licensing model, this program can be used either
 * under the terms of the GNU Affero General Public License, version 3,
 * or under a proprietary license.
 *
 * The texts of the GNU Affero General Public License with an additional
 * permission and of our proprietary license can be found at and
 * in the LICENSE file you have received along with this program.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * "Cloudrexx" is a registered trademark of Cloudrexx AG.
 * The licensing of the program under the AGPLv3 does not imply a
 * trademark license. Therefore any rights, title and interest in
 * our trademarks remain entirely with us.
 */

/**
 * CrmJavascript Class CRM
 *
 * @category   CrmJavascript
 * @package    cloudrexx
 * @subpackage module_crm
 * @author     SoftSolutions4U Development Team <info@softsolutions4u.com>
 * @copyright  2012 and CLOUDREXX CMS - CLOUDREXX AG
 * @license    trial license
 * @link       www.cloudrexx.com
 */

namespace Cx\Modules\Crm\Controller;

/**
 * CrmJavascript Class CRM
 *
 * @category   CrmJavascript
 * @package    cloudrexx
 * @subpackage module_crm
 * @author     SoftSolutions4U Development Team <info@softsolutions4u.com>
 * @copyright  2012 and CLOUDREXX CMS - CLOUDREXX AG
 * @license    trial license
 * @link       www.cloudrexx.com
 */

class CrmJavascript {

    /**
    * Module Name
    *
    * @access private
    * @var string
    */
    private $moduleName = 'Crm';

    /**
     * Get Add Currency Script
     *
     * @return String $javascript
     */
    function getAddCurrencyJavascript()
    {
        global $_CORELANG, $_ARRAYLANG, $objDatabase;


        $TXT_NAME_FIELD_SHOULD_HAVE_ALPHA = $_ARRAYLANG['TXT_NAME_FIELD_SHOULD_HAVE_ALPHA'];
        $TXT_CRM_ARE_YOU_SURE_TO_DELETE_THE_ENTRY =$_ARRAYLANG['TXT_CRM_ARE_YOU_SURE_TO_DELETE_THE_ENTRY'];
        $TXT_CRM_SURE_TO_DELETE_SELECTED_ENTRIES = $_ARRAYLANG['TXT_CRM_SURE_TO_DELETE_SELECTED_ENTRIES'];
        $TXT_CRM_NOTHING_SELECTED                = $_ARRAYLANG['TXT_CRM_NOTHING_SELECTED'];
        $TXT_CRM_SAME_SORTVALUE                  = $_ARRAYLANG['TXT_CRM_SAME_SORTVALUE'];
        $TXT_ENTER_SORTING                  = $_ARRAYLANG['TXT_ENTER_SORTING'];
        $TXT_SORTING_NUMERIC                  = $_ARRAYLANG['TXT_SORTING_NUMERIC'];
        $TXT_MANDATORY_ERROR                  = $_ARRAYLANG['TXT_CRM_MANDATORY_FIELDS_NOT_FILLED_OUT'];
        $MODULE_NAME                          = $this->moduleName;
        $CSRFPARAM                            = \Cx\Core\Csrf\Controller\Csrf::param();

        $javascript = <<<END
<script type="text/javascript" src="../lib/javascript/jquery.js"></script>
        <script language="JavaScript" type="text/javascript">
          var \$j  = jQuery.noConflict();
          \$j(document).ready(function () {
          \$j("input").keyup(function () {
                \$j(this).css("border","1px solid #0A50A1");
          });
          \$j("select").change(function () {
                \$j(this).css("border","1px solid #0A50A1");
          });
          });
        //<![CDATA[
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

function ltrim(s)
{
    var l=0;
    while(l < s.length && s[l] == ' ')
    {    l++; }
    return s.substring(l, s.length);
}

function rtrim(s)
{
    var r=s.length -1;
    while(r > 0 && s[r] == ' ')
    {    r-=1;    }
    return s.substring(0, r+1);
}
function IsNumeric(strString)
   //  check for valid numeric strings
   {
   var strValidChars = "0123456789";
   var strChar;
   var blnResult = true;

   if (strString.length == 0) return false;

   //  test strString consists of valid characters listed above
   for (i = 0; i < strString.length && blnResult == true; i++)
      {
      strChar = strString.charAt(i);
      if (strValidChars.indexOf(strChar) == -1)
         {
         blnResult = false;
         }
      }
   return blnResult;
   }


function checkValidations() {
     \$j("input, select").css("border","1px solid #0A50A1");
     var errors =new  Array();
     var errChk;
    var name   = document.getElementById("name").value;
    var sorting = document.getElementById("sortingNumber").value;
    if(trim(name)=="") {
        errChk = 1;
        document.getElementById("name").style.border = "1px solid red";
//        return false;
    }
//       else if(sorting == ""){
//                alert("$TXT_ENTER_SORTING");
//            document.getElementById("sortingNumber").style.border = "1px solid red";
//                document.getElementById("sortingNumber").value = "";
//                return false;
//        }
        if((trim(sorting) != "") && (IsNumeric(sorting) == false)){
            errChk = 1;
            document.getElementById("sortingNumber").style.border = "1px solid red";
            document.getElementById("sortingNumber").value = "";
//            return false;
        }

        return showErrors(errors, errChk);
}
function showErrors(errors,  errChk) {
    if ( errors.length >= 1 || errChk == 1) {
                if (errChk == 1 && errors.length >= 1) {
                    errString = errors.join('<br />');
                    \$j('#formerr').html("$TXT_MANDATORY_ERROR<br />"+errString);
                } else if (  errors.length >= 1) {
                    errString = errors.join('<br />');
                    \$j('#formerr').html(errString);
                } else {
                    \$j('#formerr').html("$TXT_MANDATORY_ERROR");
                }
       \$j('#formerr').css('display','block');
       //\$j('#formerr').html(errString);
        return false;
    } else {
        \$j('#formerr').html('');
        \$j('#formerr').css('display', 'none');
        return true;
    }
}

function checkValidationsEdit() {
     \$j("input, select").css("border","1px solid #0A50A1");
     var errors =new  Array();
     var errChk;
     var name   = document.getElementById("name").value;
     var sorting = document.getElementById("sortingNumber").value;
    if(trim(name)=="") {
        errChk = 1;
        document.getElementById("name").style.border = "1px solid red";
        //return false;
    }
      if((trim(sorting) != "") && (IsNumeric(sorting) == false)){
            errors.push("$TXT_SORTING_NUMERIC");
            document.getElementById("sortingNumber").style.border = "1px solid red";
            document.getElementById("sortingNumber").value = "";
//            return false;
        }
    return showErrors(errors, errChk);
}
function showErrors(errors,  errChk) {
    if ( errors.length >= 1 || errChk == 1) {
                if (errChk == 1 && errors.length >= 1) {
                    errString = errors.join('<br />');
                    \$j('#formerr').html("$TXT_MANDATORY_ERROR<br />"+errString);
                } else if (  errors.length >= 1) {
                    errString = errors.join('<br />');
                    \$j('#formerr').html(errString);
                } else {
                    \$j('#formerr').html("$TXT_MANDATORY_ERROR");
                }
       \$j('#formerr').css('display','block');
       //\$j('#formerr').html(errString);
        return false;
    } else {
        \$j('#formerr').html('');
        \$j('#formerr').css('display', 'none');
        return true;
    }
}

function selectMultiAction() {


    with (document.frmShowCurrencyEntries) {
                             var chks = document.getElementsByName('selectedEntriesId[]');
                             var hasChecked = false;
                             // Get the checkbox array length and iterate it to see if any of them is selected
                             for (var i = 0; i < chks.length; i++){
                                if (chks[i].checked){
                                      hasChecked = true;
                                      break;
                                }
                             }
                               if (!hasChecked) {
                                      alert("$TXT_CRM_NOTHING_SELECTED");
                                      document.frmShowCurrencyEntries.frmShowEntries_MultiAction.value=0;
                                      document.frmShowCurrencyEntries.frmShowEntries_MultiAction.focus();
                                      return false;
                               }
        switch (frmShowEntries_MultiAction.value) {

            case 'delete':
                if (confirm("$TXT_CRM_SURE_TO_DELETE_SELECTED_ENTRIES")) {
                    action='index.php?cmd=$MODULE_NAME&act=deleteCurrency&$CSRFPARAM';
                    submit();
                }
                else{
                  frmShowEntries_MultiAction.value=0;
                }

            break;
            default: //do nothing
        }
                if(frmShowEntries_MultiAction.value == "sort"){
                             var sortText = document.getElementsByName('form_pos[]');
                             var SortArray = new Array();
                             var cond=0;
                             for (var i = 0; i < sortText.length; i++){

                  if(sortText[i].value==""){
                                            alert("$TXT_ENTER_SORTING");
                                      document.frmShowCurrencyEntries.frmShowEntries_MultiAction.value=0;
                                      document.frmShowCurrencyEntries.frmShowEntries_MultiAction.focus();
                                            cond=1;
                                            return false;
                                            break;
                                       }
                    else if(IsNumeric(sortText[i].value) == false){
                            alert("$TXT_SORTING_NUMERIC");
                     document.frmShowCurrencyEntries.frmShowEntries_MultiAction.value=0;
                                         document.frmShowCurrencyEntries.frmShowEntries_MultiAction.focus();
                                            cond=1;
                                            return false;
                                            break;
                                       }

//                                  for (var j = i+1; j < sortText.length; j++){
//
//                    if(sortText[i].value==sortText[j].value){
//                                            alert("$TXT_CRM_SAME_SORTVALUE");
//                                      document.frmShowCurrencyEntries.frmShowEntries_MultiAction.value=0;
//                                      document.frmShowCurrencyEntries.frmShowEntries_MultiAction.focus();
//                                            cond=1;
//                                            return false;
//                                            break;
//                                       }
//
//                                  }
                                      if(cond == 1){
                                         break;
                                      }
                             }
                    action='index.php?cmd=$MODULE_NAME&act=settings&tpl=currency&chg=1&$CSRFPARAM';
                    submit();
                  }
                if(frmShowEntries_MultiAction.value == "activate"){
                    action='index.php?cmd=$MODULE_NAME&act=settings&tpl=currencyChangeStatus&type=activate&$CSRFPARAM';
                    submit();
                }
                if(frmShowEntries_MultiAction.value == "deactivate"){
                    action='index.php?cmd=$MODULE_NAME&act=settings&tpl=currencyChangeStatus&type=deactivate&$CSRFPARAM';
                    submit();
                }
    }
}

function showList(id) {

    document.getElementById(id).style.display="block";
    return false;
}

function deleteEntry(entryId){
            if(confirm("$TXT_CRM_ARE_YOU_SURE_TO_DELETE_THE_ENTRY"))
                 window.location.replace("index.php?cmd=$MODULE_NAME&act=deleteCurrency&$CSRFPARAM&id="+entryId);
        }
//]]>
</script>
END;
        return $javascript;
    }


    /**
     * Get Add Notes Script
     *
     * @return String $javascript
     */
    function getAddNotesJavascript()
    {
        global $_CORELANG, $_ARRAYLANG, $objDatabase;

        $TXT_NAME_FIELD_SHOULD_HAVE_ALPHA = $_ARRAYLANG['TXT_NAME_FIELD_SHOULD_HAVE_ALPHA'];
        $TXT_CRM_ARE_YOU_SURE_TO_DELETE_THE_ENTRY =$_ARRAYLANG['TXT_CRM_ARE_YOU_SURE_TO_DELETE_THE_ENTRY'];
        $TXT_CRM_SURE_TO_DELETE_SELECTED_ENTRIES = $_ARRAYLANG['TXT_CRM_SURE_TO_DELETE_SELECTED_ENTRIES'];
        $TXT_CRM_NOTHING_SELECTED                = $_ARRAYLANG['TXT_CRM_NOTHING_SELECTED'];
        $TXT_CRM_SAME_SORTVALUE                  = $_ARRAYLANG['TXT_CRM_SAME_SORTVALUE'];
        $TXT_ENTER_SORTING                  = $_ARRAYLANG['TXT_ENTER_SORTING'];
        $TXT_MANDATORY_ERROR                  = $_ARRAYLANG['TXT_CRM_MANDATORY_FIELDS_NOT_FILLED_OUT'];
        $TXT_SORTING_NUMERIC                  = $_ARRAYLANG['TXT_SORTING_NUMERIC'];
        $MODULE_NAME                          = $this->moduleName;

        $javascript = <<<END
<script type="text/javascript" src="../lib/javascript/jquery.js"></script>
        <script language="JavaScript" type="text/javascript">
          var \$j  = jQuery.noConflict();
          \$j(document).ready(function () {
          \$j("input").keyup(function () {
                \$j(this).css("border","1px solid #0A50A1");
          });
          \$j("select").change(function () {
                \$j(this).css("border","1px solid #0A50A1");
          });
          });
        //<![CDATA[
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

function ltrim(s)
{
    var l=0;
    while(l < s.length && s[l] == ' ')
    {    l++; }
    return s.substring(l, s.length);
}

function rtrim(s)
{
    var r=s.length -1;
    while(r > 0 && s[r] == ' ')
    {    r-=1;    }
    return s.substring(0, r+1);
}
function IsNumeric(strString)
   //  check for valid numeric strings
   {
   var strValidChars = "0123456789";
   var strChar;
   var blnResult = true;

   if (strString.length == 0) return false;

   //  test strString consists of valid characters listed above
   for (i = 0; i < strString.length && blnResult == true; i++)
      {
      strChar = strString.charAt(i);
      if (strValidChars.indexOf(strChar) == -1)
         {
         blnResult = false;
         }
      }
   return blnResult;
   }


function checkValidations() {
     \$j("input, select").css("border","1px solid #0A50A1");
     var errors =new  Array();
     var errChk;
    var name   = document.getElementById("name").value;
    var sorting = document.getElementById("sortingNumber").value;
    if(trim(name)=="") {
        errChk = 1;
        document.getElementById("name").style.border = "1px solid red";
//        return false;
    }
//       else if(sorting == ""){
//                alert("$TXT_ENTER_SORTING");
//            document.getElementById("sortingNumber").style.border = "1px solid red";
//                document.getElementById("sortingNumber").value = "";
//                return false;
//        }
        if((trim(sorting) != "") && (IsNumeric(sorting) == false)){
            errors.push("$TXT_SORTING_NUMERIC");
            errChk = 1;
            document.getElementById("sortingNumber").style.border = "1px solid red";
            document.getElementById("sortingNumber").value = "";
//            return false;
        }

        return showErrors(errors, errChk);
}
function showErrors(errors,  errChk) {
    if ( errors.length >= 1 || errChk == 1) {
                if (errChk == 1 && errors.length >= 1) {
                    errString = errors.join('<br />');
                    \$j('#formerr').html("$TXT_MANDATORY_ERROR<br />"+errString);
                } else if (  errors.length >= 1) {
                    errString = errors.join('<br />');
                    \$j('#formerr').html(errString);
                } else {
                    \$j('#formerr').html("$TXT_MANDATORY_ERROR");
                }
       \$j('#formerr').css('display','block');
       //\$j('#formerr').html(errString);
        return false;
    } else {
        \$j('#formerr').html('');
        \$j('#formerr').css('display', 'none');
        return true;
    }
}

function checkValidationsEdit() {
     \$j("input, select").css("border","1px solid #0A50A1");
     var errors =new  Array();
     var errChk;
     var name   = document.getElementById("name").value;
     var sorting = document.getElementById("sortingNumber").value;
    if(trim(name)=="") {
        errChk = 1;
        document.getElementById("name").style.border = "1px solid red";
        //return false;
    }
      if((trim(sorting) != "") && (IsNumeric(sorting) == false)){
            errors.push("$TXT_SORTING_NUMERIC");
            document.getElementById("sortingNumber").style.border = "1px solid red";
            document.getElementById("sortingNumber").value = "";
//            return false;
        }
    return showErrors(errors, errChk);
}
function showErrors(errors,  errChk) {
    if ( errors.length >= 1 || errChk == 1) {
                if (errChk == 1 && errors.length >= 1) {
                    errString = errors.join('<br />');
                    \$j('#formerr').html("$TXT_MANDATORY_ERROR<br />"+errString);
                } else if (  errors.length >= 1) {
                    errString = errors.join('<br />');
                    \$j('#formerr').html(errString);
                } else {
                    \$j('#formerr').html("$TXT_MANDATORY_ERROR");
                }
       \$j('#formerr').css('display','block');
       //\$j('#formerr').html(errString);
        return false;
    } else {
        \$j('#formerr').html('');
        \$j('#formerr').css('display', 'none');
        return true;
    }
}


function showList(id) {

    document.getElementById(id).style.display="block";
    return false;
}

function deleteEntry(entryId){
            if(confirm("$TXT_CRM_ARE_YOU_SURE_TO_DELETE_THE_ENTRY"))
                 window.location.replace("index.php?cmd=$MODULE_NAME&act=deleteCurrency&id="+entryId);
        }
//]]>
</script>
END;
        return $javascript;
    }

    /**
     * Get service type script
     *
     * @return String $javascript
     */
    function getServiceTypeJavascript()
    {
        global $_ARRAYLANG;

        $TXT_CRM_ENTER_NAME                        =  $_ARRAYLANG['TXT_CRM_ENTER_NAME'];
        $TXT_ENTER_SUPPORTCASES                    =  $_ARRAYLANG['TXT_ENTER_SUPPORTCASES'];
        $TXT_ENTER_PRICE                           =  $_ARRAYLANG['TXT_ENTER_PRICE'];
        $TXT_CRM_ARE_YOU_SURE_TO_DELETE_THE_ENTRY  =  $_ARRAYLANG['TXT_CRM_ARE_YOU_SURE_TO_DELETE_THE_ENTRY'];
        $TXT_CRM_SURE_TO_DELETE_SELECTED_ENTRIES   =  $_ARRAYLANG['TXT_CRM_SURE_TO_DELETE_SELECTED_ENTRIES'];
        $TXT_CRM_NOTHING_SELECTED                  =  $_ARRAYLANG['TXT_CRM_NOTHING_SELECTED'];
        $MODULE_NAME                               =  $this->moduleName;
        $javascript = <<<END

  <script type="text/javascript" src="../lib/javascript/jquery.js"></script>
  <script type="text/javascript">
      var \$j  = jQuery.noConflict();

        function isSpecialChars(s)
        {   var i;
        var iChars = "_!@#$%^&*()+=-[]\\\';,./{}|\":<>?";
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
        {   var i;
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
var iChars = "_!@#$%^&*()+=-[]\\\';,./{}|\":<>?";
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

    function checkAdditionalcontact() {

    \$j("input, select").css("border","1px solid #0A50A1");
    \$j("input").keyup(function () {
                \$j(this).css("border","1px solid #0A50A1");
          });
    var errors = new Array();
    var errChk;
        var supportcases = document.getElementById('support_cases').value;
        var service_price = document.getElementById('service_price').value;

        if((trim(supportcases) != "") && (supportcases.search(/^[0-9]+$/))) {
        errors.push("$TXT_ENTER_SUPPORTCASES");
        document.add_service_type_form.support_cases.style.border = "1px solid red";
        document.add_service_type_form.support_cases.value="";
    }

 if((trim(service_price)!= "") && (service_price.search(/^[0-9]+$/))) {
        errors.push("$TXT_ENTER_PRICE");
        document.add_service_type_form.service_price.style.border = "1px solid red";
        document.add_service_type_form.service_price.value="";
    }

       if ( errors.length >= 1 || errChk == 1 ) {
         if ( errors.length >= 1 && errChk == 1 ) {
                errString = errors.join('<br />');
            } else if (  errors.length >= 1) {
                    errString = errors.join('<br />');
                    \$j('#formerr').html(errString);
            }
              \$j('#formerr').css("display","block");
              return false;
        } else {
           \$j('#formerr').html('');
            \$j('#formerr').css("display","none");
            document.getElementById('add_service_plan_type').style.display='none';
            document.getElementById('fade').style.display='none';
            return true;
        }

    }

function deleteEntry(entryId){
            if(confirm("$TXT_CRM_ARE_YOU_SURE_TO_DELETE_THE_ENTRY"))
                 window.location.replace("index.php?cmd=$MODULE_NAME&act=deleteServiceType&delId="+entryId);
        }


function selectMultiAction() {
      with (document.frmShowCustomersEntries) {
             var chks = document.getElementsByName('selectedEntriesId[]');
             var hasChecked = false;

             for (var i = 0; i < chks.length; i++){
                if (chks[i].checked){
                      hasChecked = true;
                      break;
                }
             }

               if (!hasChecked) {
                      alert("$TXT_CRM_NOTHING_SELECTED");
                      document.frmShowCustomersEntries.frmShowEntries_MultiAction.value=0;
                      document.frmShowCustomersEntries.frmShowEntries_MultiAction.focus();
                      return false;
               }
                   switch (frmShowEntries_MultiAction.value) {
            case 'delete':
                if (confirm('$TXT_CRM_SURE_TO_DELETE_SELECTED_ENTRIES')) {
                    action='?cmd=$MODULE_NAME&act=deleteServiceType';
                    submit();
                }
                else{
                frmShowEntries_MultiAction.value=0;
                }
            break;
        }
}
}

</script>
END;

        return $javascript;
    }

    /**
     * Get Hosting type script
     *
     * @return String $javascript
     */
    function getHostingTypeJavascript()
    {
        global $_ARRAYLANG;
        $TXT_CRM_ENTER_NAME                        =  $_ARRAYLANG['TXT_CRM_ENTER_NAME'];
        $TXT_ENTER_PRICE                           =  $_ARRAYLANG['TXT_ENTER_PRICE'];
        $TXT_CRM_ARE_YOU_SURE_TO_DELETE_THE_ENTRY  =  $_ARRAYLANG['TXT_CRM_ARE_YOU_SURE_TO_DELETE_THE_ENTRY'];
        $TXT_CRM_SURE_TO_DELETE_SELECTED_ENTRIES   =  $_ARRAYLANG['TXT_CRM_SURE_TO_DELETE_SELECTED_ENTRIES'];
        $TXT_CRM_NOTHING_SELECTED                  =  $_ARRAYLANG['TXT_CRM_NOTHING_SELECTED'];
        $MODULE_NAME                               =  $this->moduleName;
        $javascript = <<<END

 <script type="text/javascript" src="../lib/javascript/jquery.js"></script>
 <script type="text/javascript">
      var \$j  = jQuery.noConflict();
      function isSpecialChars(s)
        {   var i;
        var iChars = "_!@#$%^&*()+=-[]\\\';,./{}|\":<>?";
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
        {   var i;
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
          var iChars = "_!@#$%^&*()+=-[]\\\';,./{}|\":<>?";
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

    function checkHostingcontact() {

    \$j("input, select").css("border","1px solid #0A50A1");
    \$j("input").keyup(function () {
                \$j(this).css("border","1px solid #0A50A1");
          });
    var errors = new Array();
    var errChk;

      var hosting_price = document.getElementById('hosting_price').value;


       if((trim(hosting_price) != "") && (hosting_price.search(/^[0-9]+$/))) {
        errors.push("$TXT_ENTER_PRICE");
        document.add_hosting_type_form.hosting_price.style.border = "1px solid red";
        document.add_hosting_type_form.hosting_price.value="";
    }


        if ( errors.length >= 1 || errChk == 1 ) {

            if ( errors.length >= 1 && errChk == 1 ) {
                errString = errors.join('<br />');


            } else if (  errors.length >= 1) {
                    errString = errors.join('<br />');
                    \$j('#formerr').html(errString);
            }
              \$j('#formerr').css("display","block");
              return false;
        } else {
           \$j('#formerr').html('');
            \$j('#formerr').css("display","none");
            document.getElementById('add_hostingtype').style.display='none';
            document.getElementById('fade').style.display='none';
            return true;
        }

    }

function deleteEntry(entryId){
            if(confirm("$TXT_CRM_ARE_YOU_SURE_TO_DELETE_THE_ENTRY"))
                 window.location.replace("index.php?cmd=$MODULE_NAME&act=deleteHostingType&delId="+entryId);
        }

function selectMultiAction() {


    with (document.frmShowCustomersEntries) {
             var chks = document.getElementsByName('selectedEntriesId[]');
             var hasChecked = false;

             for (var i = 0; i < chks.length; i++){
                if (chks[i].checked){
                      hasChecked = true;
                      break;
                }
             }

               if (!hasChecked) {
                      alert("$TXT_CRM_NOTHING_SELECTED");
                      document.frmShowCustomersEntries.frmShowEntries_MultiAction.value=0;
                      document.frmShowCustomersEntries.frmShowEntries_MultiAction.focus();
                      return false;
               }
switch (frmShowEntries_MultiAction.value) {
            case 'delete':
                if (confirm('$TXT_CRM_SURE_TO_DELETE_SELECTED_ENTRIES')) {
                    action='?cmd=$MODULE_NAME&act=deleteHostingType';
                    submit();
                }
                else{
                frmShowEntries_MultiAction.value=0;
                }
            break;
        }
}
}
</script>
END;

        return $javascript;
    }

    /**
     * Get customer details script
     *
     * @return String $javascript
     */
    function getShowCustomerdetailsJavascript()
    {
        global $_ARRAYLANG;
        $TXT_CRM_ARE_YOU_SURE_DELETE_ENTRIES = $_ARRAYLANG['TXT_CRM_ARE_YOU_SURE_DELETE_ENTRIES'];
        $TXT_CRM_ARE_YOU_SURE_DELETE_SELECTED_ENTRIES = $_ARRAYLANG['TXT_CRM_ARE_YOU_SURE_DELETE_SELECTED_ENTRIES'];
        $TXT_ENTER_CUSTOMERNAME                   = $_ARRAYLANG['TXT_ENTER_CUSTOMERNAME'];
        $TXT_ENTER_DOMAINNAME                         =  $_ARRAYLANG['TXT_ENTER_DOMAINNAME'];
        $TXT_ENTER_PRICE                              =  $_ARRAYLANG['TXT_ENTER_PRICE'];
        $TXT_SUPPORTTICKET                            =  $_ARRAYLANG['TXT_SUPPORTTICKET'];
        $TXT_SUPPORT_TITLE                              =  $_ARRAYLANG['TXT_SUPPORT_TITLE'];
        $MODULE_NAME                          = $this->moduleName;
        $javascript = <<<END





<script language="JavaScript" type="text/javascript">
//<![CDATA[
\$j = jQuery.noConflict();
\$j(function() {
    \$j( "#issue_date,#support_date,#registrationdate,#nextinvoice" ).datepicker({
        showWeek: true,
        firstDay: 1,
    });

    \$j('.plan_link').click(function(){
        planId_class = \$j(this).attr('id');
    planId = planId_class.split('_');
    planId = planId[2];
         \$j('#show_support_plan .support_id').html(\$j('#service_'+planId+' .service_id').text());
        \$j('#show_support_plan .support_type').html(\$j('#service_'+planId+' .service_type').text());
    \$j('#show_support_plan .support_date').html(\$j('#service_'+planId+' .service_date').text());
        \$j('#show_support_plan .support_until').html(\$j('#service_'+planId+' .service_until').text());
        \$j('#show_support_plan .support_support').html(\$j('#service_'+planId+' .serice_support').text());
        \$j('#show_support_plan .support_cases').html(\$j('#service_'+planId+' .service_cases').text());
    });
});





function deleteSupportCase(entryId,customId){
            if(confirm("$TXT_CRM_ARE_YOU_SURE_DELETE_ENTRIES"))

       window.location.replace("index.php?cmd=$MODULE_NAME&act=customers&tpl=showcustdetail&act=deleteCustomerSupportcase&id="+entryId+"&customId="+customId);

        }

  function deleteHosting(entryId,customId){
            if(confirm("$TXT_CRM_ARE_YOU_SURE_DELETE_ENTRIES"))

       window.location.replace("index.php?cmd=Crm&act=customers&tpl=showcustdetail&action=addHosting&act=deleteCustomerHosting&id="+entryId+"&customId="+customId);

        }
         function deleteServicePlan(entryId,customerId){
            if(confirm("$TXT_CRM_ARE_YOU_SURE_DELETE_ENTRIES"))
  window.location.replace("index.php?cmd=$MODULE_NAME&act=customers&tpl=showcustdetail&act=deleteCustomerServiceplan&seriveId="+entryId+"&customerId="+customerId);

        }


function selectTab(displayTab) {
    \$j('.Entries').css('display','none');
    \$j('#'+displayTab).css('display','block');
    \$j('#tabmenu a').removeClass('active');
    \$j('#'+displayTab+'Tab').addClass('active');
}







\$j = jQuery.noConflict();


  function isSpecialChars(s)
        {   var i;
        var iChars = "_!@#$%^&*()+=-[]\\\';,./{}|\":<>?";
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
        {   var i;
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
var iChars = "_!@#$%^&*()+=-[]\\\';,./{}|\":<>?";
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

 function checkServiceCases() {

    \$j("input, select").css("border","1px solid #0A50A1");
    \$j("input").keyup(function () {
                \$j(this).css("border","1px solid #0A50A1");
          });
    var errors = new Array();
    var errChk;


        var ticket = document.getElementById('support_ticket').value;
        var title= document.getElementById('support_title').value;


        if((trim(ticket) != "") && (ticket.search(/^[0-9]+$/))) {
        errors.push("$TXT_SUPPORTTICKET");
        document.add_support_case_form.support_ticket.style.border = "1px solid red";
        document.add_support_case_form.support_ticket.value="";
    }

       if((trim(title) != "") && (isSpecialChars(title) == true || isInteger(title) == true || isSpecialInteger(title) == true)) {
      errors.push("$TXT_SUPPORT_TITLE");
          document.add_support_case_form.support_title.style.border = "1px solid red";
      document.add_support_case_form.support_title.value="";
   }


        if ( errors.length >= 1 || errChk == 1 ) {

            if ( errors.length >= 1 && errChk == 1 ) {
                errString = errors.join('<br />');


            } else if (  errors.length >= 1) {
                    errString = errors.join('<br />');
                    \$j('#supportcaseerr').html(errString);
            }
              \$j('#supportcaseerr').css("display","block");
              return false;
        } else {
           \$j('#supportcaseerr').html('');
            \$j('#supportcaseerr').css("display","none");
            document.getElementById('add_support_case').style.display='none';
            document.getElementById('fade').style.display='none';
            return true;
        }

    }

  function hostingValidation() {

    \$j("input, select").css("border","1px solid #0A50A1");
    \$j("input").keyup(function () {
                \$j(this).css("border","1px solid #0A50A1");
          });
    var errors = new Array();
    var errChk;

        var hostingType = document.getElementById('hosting_type').value;
        var domain = document.getElementById('domain').value;
        var password = document.getElementById('hosting_password').value;
        var registrationdate= document.getElementById('registrationdate').value;
        var nextinvoice = document.getElementById('nextinvoice').value;
       var price = document.getElementById('hosting_price').value;
       var additionalinformation = document.getElementById('additionalinformation').value;
 if ((trim(domain) != "") && (isSpecialChars(domain) == true || isInteger(domain) == true || isSpecialInteger(domain) == true)) {
      errors.push("$TXT_ENTER_DOMAINNAME");
          document.hosting_case_form.domain.style.border = "1px solid red";
      document.hosting_case_form.domain.value="";
   }

if((trim(price) != "") && (price.search(/^[0-9]+$/))) {
        errors.push("$TXT_ENTER_PRICE");
        document.hosting_case_form.hosting_price.style.border = "1px solid red";
        document.hosting_case_form.hosting_price.value="";
    }

    if ( errors.length >= 1 || errChk == 1 ) {

            if ( errors.length >= 1 && errChk == 1 ) {
                errString = errors.join('<br />');


            } else if (  errors.length >= 1) {
                    errString = errors.join('<br />');
                    \$j('#hostingcaseerr').html(errString);
            }
              \$j('#hostingcaseerr').css("display","block");
              return false;
        } else {
           \$j('#hostingcaseerr').html('');
            \$j('#hostingcaseerr').css("display","none");
            document.getElementById('add_hosting').style.display='none';
            document.getElementById('fade').style.display='none';
            return true;
        }

    }

function showList(id) {
    document.getElementById(id).style.display="block";
    return false;
}
//]]>
</script>

END;
        return $javascript;
    }

    /**
     * Get customer type script
     *
     * @return String $javascript
     */
    function getCustomerTypeJavascript()
    {
        global $_CORELANG, $_ARRAYLANG, $objDatabase;

        $TXT_CRM_SURE_TO_DELETE_SELECTED_ENTRIES  = $_ARRAYLANG['TXT_CRM_SURE_TO_DELETE_SELECTED_ENTRIES'];
        $TXT_CRM_ENTER_LABEL_FIELD                = $_ARRAYLANG['TXT_CRM_ENTER_LABEL_FIELD'];
        $TXT_CRM_ENTER_LABEL_FIELD_WITHOUT_SPECIAL_CHARACTERS = $_ARRAYLANG['TXT_CRM_ENTER_LABEL_FIELD_WITHOUT_SPECIAL_CHARACTERS'];
        $TXT_CRM_ENTER_DISCOUNT_PERCENT           = $_ARRAYLANG['TXT_CRM_ENTER_DISCOUNT_PERCENT'];
        $TXT_CRM_PLEASE_ENTER_DISCOUNT_PERCENT_IN_NUMBER = $_ARRAYLANG['TXT_CRM_PLEASE_ENTER_DISCOUNT_PERCENT_IN_NUMBER'];
        $TXT_CRM_ARE_YOU_SURE_TO_DELETE_THE_ENTRY = $_ARRAYLANG['TXT_CRM_ARE_YOU_SURE_TO_DELETE_THE_ENTRY'];
        $TXT_CRM_NOTHING_SELECTED                 = $_ARRAYLANG['TXT_CRM_NOTHING_SELECTED'];
        $TXT_CRM_SAME_SORTVALUE                   = $_ARRAYLANG['TXT_CRM_SAME_SORTVALUE'];
        $TXT_ENTER_SORTING                    = $_ARRAYLANG['TXT_ENTER_SORTING'];
        $TXT_SORTING_NUMERIC                  = $_ARRAYLANG['TXT_SORTING_NUMERIC'];
        $TXT_MANDATORY_ERROR                  = $_ARRAYLANG['TXT_CRM_MANDATORY_FIELDS_NOT_FILLED_OUT'];
        $MODULE_NAME                          = $this->moduleName;
        $CSRFPARAM                            = \Cx\Core\Csrf\Controller\Csrf::param();


        $javascript = <<<END
        <script type="text/javascript" src="../lib/javascript/jquery.js"></script>
        <script language="JavaScript" type="text/javascript">
          var \$j  = jQuery.noConflict();
          \$j(document).ready(function () {
          \$j("input").keyup(function () {
                \$j(this).css("border","1px solid #0A50A1");
          });
          \$j("select").change(function () {
                \$j(this).css("border","1px solid #0A50A1");
          });
          });

        //<![CDATA[
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

function ltrim(s)
{
    var l=0;
    while(l < s.length && s[l] == ' ')
    {    l++; }
    return s.substring(l, s.length);
}

function rtrim(s)
{
    var r=s.length -1;
    while(r > 0 && s[r] == ' ')
    {    r-=1;    }
    return s.substring(0, r+1);
}

function IsNumeric(strString)
   //  check for valid numeric strings
   {
   var strValidChars = "0123456789";
   var strChar;
   var blnResult = true;

   if (strString.length == 0) return false;

   //  test strString consists of valid characters listed above
   for (i = 0; i < strString.length && blnResult == true; i++)
      {
      strChar = strString.charAt(i);
      if (strValidChars.indexOf(strChar) == -1)
         {
         blnResult = false;
         }
      }
   return blnResult;
   }


function selectMultiAction() {


    with (document.frmShowCustomerEntries) {
                             var chks = document.getElementsByName('selectedEntriesId[]');
                             var hasChecked = false;
                             // Get the checkbox array length and iterate it to see if any of them is selected
                             for (var i = 0; i < chks.length; i++){
                                if (chks[i].checked){
                                      hasChecked = true;
                                      break;
                                }
                             }
                               if (!hasChecked) {
                                      alert("$TXT_CRM_NOTHING_SELECTED");
                                      document.frmShowCustomerEntries.frmShowEntries_MultiAction.value=0;
                                      document.frmShowCustomerEntries.frmShowEntries_MultiAction.focus();
                                      return false;
                               }
        switch (frmShowEntries_MultiAction.value) {
            case 'delete':
                if (confirm("$TXT_CRM_SURE_TO_DELETE_SELECTED_ENTRIES")) {
                    action='index.php?cmd=$MODULE_NAME&act=deleteCustomerTypes&$CSRFPARAM';
                    submit();
                }
                else{
                frmShowEntries_MultiAction.value=0;
                }
            break;
            default: //do nothing
        }
                if(frmShowEntries_MultiAction.value == "activate"){
        action='index.php?cmd=$MODULE_NAME&act=settings&tpl=customerTypeChangeStatus&type=activate';
                    submit();
                }
                if(frmShowEntries_MultiAction.value == "deactivate"){
                action='index.php?cmd=$MODULE_NAME&act=settings&tpl=customerTypeChangeStatus&type=deactivate';
                    submit();
                }
                if(frmShowEntries_MultiAction.value == "sort"){
                             var sortText = document.getElementsByName('form_pos[]');
                 var SortArray = new Array();
                             var cond=0;
                             for (var i = 0; i < sortText.length; i++){

                    if(sortText[i].value==""){
                                            alert("$TXT_ENTER_SORTING");
                                      document.frmShowCustomerEntries.frmShowEntries_MultiAction.value=0;
                                      document.frmShowCustomerEntries.frmShowEntries_MultiAction.focus();
                                            cond=1;
                                            return false;
                                            break;
                                       }
                                      else if(IsNumeric(sortText[i].value) == false){
                            alert("$TXT_SORTING_NUMERIC");
                     document.frmShowCustomerEntries.frmShowEntries_MultiAction.value=0;
                                         document.frmShowCustomerEntries.frmShowEntries_MultiAction.focus();
                                            cond=1;
                                            return false;
                                            break;
                                       }

                                      if(cond == 1){
                                         break;
                                      }
                             }
                    action='index.php?cmd=$MODULE_NAME&act=settings&tpl=customertypes&chg=1';
                    submit();
                  }
    }
}

function showList(id) {

    document.getElementById(id).style.display="block";
    return false;
}

function deleteEntry(entryId){
            if(confirm("$TXT_CRM_ARE_YOU_SURE_TO_DELETE_THE_ENTRY"))
        //alert("index.php?cmd=$MODULE_NAME&act=deleteCustomerTypes&$CSRFPARAM&id="+entryId);
                 window.location.replace("index.php?cmd=$MODULE_NAME&act=deleteCustomerTypes&$CSRFPARAM&id="+entryId);
        }

function checkValidations() {

          \$j("input, select").css("border","1px solid #0A50A1");
           var errors =new  Array();
           var errChk;
           var label           = document.getElementById("label").value;
           //var discountPercent = document.getElementById("discount").value;
           var sorting         = document.getElementById("sortingNumber").value;

           if(trim(label) == "") {
            errChk = 1;
           document.getElementById("label").style.border = "1px solid red";
           document.getElementById("label").value="";
        }
        if((trim(sorting) != "") && (IsNumeric(sorting) == false)) {
                errors.push("$TXT_SORTING_NUMERIC");
                errChk = 1;
            document.getElementById("sortingNumber").style.border = "1px solid red";
                document.getElementById("sortingNumber").value = "";
        }
       /*if(trim(discountPercent) == "") {
        errChk = 1;
        document.getElementById("discount").style.border = "1px solid red";
        document.getElementById("discount").value="";

    } else if(discountPercent.search(/^[0-9.]+$/)) {
        errors.push("$TXT_CRM_PLEASE_ENTER_DISCOUNT_PERCENT_IN_NUMBER");
        document.getElementById("discount").style.border = "1px solid red";
        document.getElementById("discount").value="";
    }*/
    if ( errors.length >= 1 || errChk == 1) {
                if (errChk == 1 && errors.length >= 1) {
                    errString = errors.join('<br />');
                    \$j('#formerr').html("$TXT_MANDATORY_ERROR<br />"+errString);
                } else if (  errors.length >= 1) {
                    errString = errors.join('<br />');
                    \$j('#formerr').html(errString);
                } else {
                    \$j('#formerr').html("$TXT_MANDATORY_ERROR");
                }
       \$j('#formerr').css('display','block');
        return false;
    } else {
        \$j('#formerr').html('');
        \$j('#formerr').css('display', 'none');
        return true;
    }

}

//]]>
</script>
END;
        return $javascript;
    }

    /**
     * Get edit customer type script
     *
     * @return String $javascript
     */
    function editCustomerTypeJavascript()
    {
        global $_ARRAYLANG;
        $TXT_CRM_ENTER_LABEL_FIELD   = $_ARRAYLANG['TXT_CRM_ENTER_LABEL_FIELD'];
        $TXT_CRM_ENTER_LABEL_FIELD_WITHOUT_SPECIAL_CHARACTERS = $_ARRAYLANG['TXT_CRM_ENTER_LABEL_FIELD_WITHOUT_SPECIAL_CHARACTERS'];
        $TXT_CRM_ENTER_DISCOUNT_PERCENT = $_ARRAYLANG['TXT_CRM_ENTER_DISCOUNT_PERCENT'];
        $TXT_CRM_PLEASE_ENTER_DISCOUNT_PERCENT_IN_NUMBER = $_ARRAYLANG['TXT_CRM_PLEASE_ENTER_DISCOUNT_PERCENT_IN_NUMBER'];

        $TXT_CRM_NOTHING_SELECTED                 =$_ARRAYLANG['TXT_CRM_NOTHING_SELECTED'];
        $TXT_CRM_SAME_SORTVALUE                   = $_ARRAYLANG['TXT_CRM_SAME_SORTVALUE'];
        $TXT_ENTER_SORTING                   = $_ARRAYLANG['TXT_ENTER_SORTING'];
        $TXT_SORTING_NUMERIC                 = $_ARRAYLANG['TXT_SORTING_NUMERIC'];

        $TXT_CRM_SURE_TO_DELETE_SELECTED_ENTRIES = $_ARRAYLANG['TXT_CRM_SURE_TO_DELETE_SELECTED_ENTRIES'];
        $TXT_CRM_ARE_YOU_SURE_TO_DELETE_THE_ENTRY =$_ARRAYLANG['TXT_CRM_ARE_YOU_SURE_TO_DELETE_THE_ENTRY'];
        $TXT_MANDATORY_ERROR                  = $_ARRAYLANG['TXT_CRM_MANDATORY_FIELDS_NOT_FILLED_OUT'];
        $MODULE_NAME                          = $this->moduleName;
        $javascript = <<<END
<script type="text/javascript" src="../lib/javascript/jquery.js"></script>
            <script>
          var \$j  = jQuery.noConflict();
          \$j(document).ready(function () {
          \$j("input").keyup(function () {
                \$j(this).css("border","1px solid #0A50A1");
          });
          \$j("select").change(function () {
                \$j(this).css("border","1px solid #0A50A1");
          });
          });
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

function ltrim(s)
{
    var l=0;
    while(l < s.length && s[l] == ' ')
    {    l++; }
    return s.substring(l, s.length);
}

function rtrim(s)
{
    var r=s.length -1;
    while(r > 0 && s[r] == ' ')
    {    r-=1;    }
    return s.substring(0, r+1);
}

function selectMultiAction() {


    with (document.frmShowCustomerEntries) {
                             var chks = document.getElementsByName('selectedEntriesId[]');
                             var hasChecked = false;
                             // Get the checkbox array length and iterate it to see if any of them is selected
                             for (var i = 0; i < chks.length; i++){
                                if (chks[i].checked){
                                      hasChecked = true;
                                      break;
                                }
                             }
                               if (!hasChecked) {
                                      alert("$TXT_CRM_NOTHING_SELECTED");
                                      document.frmShowCustomerEntries.frmShowEntries_MultiAction.value=0;
                                      document.frmShowCustomerEntries.frmShowEntries_MultiAction.focus();
                                      return false;
                               }
        switch (frmShowEntries_MultiAction.value) {
            case 'delete':
                if (confirm("$TXT_CRM_SURE_TO_DELETE_SELECTED_ENTRIES")) {
                    action='index.php?cmd=$MODULE_NAME&act=deleteCustomerTypes';
                    submit();
                }
                else{
                frmShowEntries_MultiAction.value=0;
                }
            break;
            default: //do nothing
        }
if(frmShowEntries_MultiAction.value == "activate"){
                    action='index.php?cmd=$MODULE_NAME&act=customerTypeChangeStatus&type=activate';
                    submit();
                }
                if(frmShowEntries_MultiAction.value == "deactivate"){
                    action='index.php?cmd=$MODULE_NAME&act=customerTypeChangeStatus&type=deactivate';
                    submit();
                }
                if(frmShowEntries_MultiAction.value == "sort"){
                             var sortText = document.getElementsByName('form_pos[]');
                             var SortArray = new Array();
                             var cond=0;
                             for (var i = 0; i < sortText.length; i++){
                                  for (var j = i+1; j < sortText.length; j++){
                                       if(sortText[i].value==""){
                                            alert("$TXT_ENTER_SORTING");
                                            document.frmShowCustomerEntries.frmShowEntries_MultiAction.value=0;
                                            document.frmShowCustomerEntries.frmShowEntries_MultiAction.focus();
                                            cond=1;
                                            return false;
                                            break;
                                       }
                       else if(IsNumeric(sortText[i].value) == false){
                                            alert("$TXT_SORTING_NUMERIC");
                                            document.frmShowCustomerEntries.frmShowEntries_MultiAction.value=0;
                                            document.frmShowCustomerEntries.frmShowEntries_MultiAction.focus();
                                            cond=1;
                                            return false;
                                            break;
                                       }


                                  }
                                      if(cond == 1){
                                         break;
                                      }
                             }
                    action='index.php?cmd=$MODULE_NAME&act=settings&chg=1';
                    submit();
                  }
    }
}

function showList(id) {

    document.getElementById(id).style.display="block";
    return false;
}

function deleteEntry(entryId){
            if(confirm("$TXT_CRM_ARE_YOU_SURE_TO_DELETE_THE_ENTRY"))
                 window.location.replace("index.php?cmd=$MODULE_NAME&act=deleteCustomerTypes&id="+entryId);
        }


function checkValidations() {

     \$j("input, select").css("border","1px solid #0A50A1");
     var errors =new  Array();
     var errChk;

     var label           = document.customerTypes.label.value;
     //var discountPercent = document.customerTypes.discount_percent.value;
     if(trim(label)=="") {
        errChk = 1;
        document.customerTypes.label.style.border = "1px solid red";
        document.customerTypes.label.value="";
    }
       /* if(trim(discountPercent) == "") {
        errChk = 1;
        document.customerTypes.discount_percent.style.border = "1px solid red";
        document.customerTypes.discount_percent.value="";
    } else if(discountPercent.search(/^[0-9.]+$/)) {
        errors.push("$TXT_CRM_PLEASE_ENTER_DISCOUNT_PERCENT_IN_NUMBER");
        document.customerTypes.discount_percent.style.border = "1px solid red";
        document.customerTypes.discount_percent.value="";
    }*/

    if ( errors.length >= 1 || errChk == 1) {
                if (errChk == 1 && errors.length >= 1) {
                    errString = errors.join('<br />');
                    \$j('#formerr').html("$TXT_MANDATORY_ERROR<br />"+errString);
                } else if (  errors.length >= 1) {
                    errString = errors.join('<br />');
                    \$j('#formerr').html(errString);
                } else {
                    \$j('#formerr').html("$TXT_MANDATORY_ERROR");
                }
       \$j('#formerr').css('display','block');
        return false;
    } else {
        \$j('#formerr').html('');
        \$j('#formerr').css('display', 'none');
        return true;
    }
}
</script>
END;
        return $javascript;
    }

    /**
     * Get script for popup
     *
     * @return String $javascript
     */
    function showPopup()
    {
        $javascript = <<<END
        <script>
        var \$j  = jQuery.noConflict();
            \$j(document).ready(function () {
                document.getElementById('add_hostingtype').style.display='block';
                document.getElementById('fade').style.display='block';
            });
        </script>
END;
        return $javascript;
    }

    /**
     * Get script for service popup
     *
     * @return String $javascript
     */
    function showServicePopup()
    {
        $javascript = <<<END
        <script>
        var \$j  = jQuery.noConflict();
            \$j(document).ready(function () {
                document.getElementById('add_service_plan_type').style.display='block';
                document.getElementById('fade').style.display='block';
            });
        </script>
END;
        return $javascript;
    }

    /**
     * Get script for service plan popup
     *
     * @return String $javascript
     */
    function showServicePlanPopup()
    {

        $javascript = <<<END
        <script>
        var \$j  = jQuery.noConflict();
            \$j(document).ready(function () {
                document.getElementById('add_support_plan').style.display='block';
                document.getElementById('fade').style.display='block';
            });
        </script>
END;
        return $javascript;
    }

    /**
     * Get script for hosting popup
     *
     * @return String $javascript
     */
    function showHostingPopup()
    {
        $javascript = <<<END
        <script>
        var \$j  = jQuery.noConflict();
            \$j(document).ready(function () {
                document.getElementById('add_hosting').style.display='block';
                document.getElementById('fade').style.display='block';
            });
        </script>
END;
        return $javascript;
    }

    /**
     * Get script for support case
     *
     * @return String $javascript
     */
    function showSupportcases()
    {
        $javascript = <<<END
        <script>
        var \$j  = jQuery.noConflict();
            \$j(document).ready(function () {
                document.getElementById('add_support_case').style.display='block';
                document.getElementById('fade').style.display='block';
            });
        </script>
END;
        return $javascript;
    }

    /**
     * Get script for crm shadowbox
     *
     * @return String $javascript
     */
    function crmAjaxformSubmitForShadowbox()
    {
        global $_ARRAYLANG;
        $CRM_COMPANY_NAME_ALREADY_PRESENT   = $_ARRAYLANG['TXT_CRM_COMPANYNAME_ALREADY_EXISTS'];
        $javascript = <<<END
<!--  Script refered from
        http://jquery.malsup.com/form/
        -->
<script type="text/javascript" src="../modules/pm/lib/jquery.form.js"></script>
<script type="text/javascript">
          \$j(document).ready(function () {
                var options = {
                    dataType:  'json',
                    success: function(data) {
                                          //  alert(data.errChk);
                                          if (data.errChk == 1) {
                                              \$j('#formerr').append('$CRM_COMPANY_NAME_ALREADY_PRESENT');
                                              \$j('#formerr').show();
                                          } else {
                                              window.parent.changeCustomer(data.customerId, data.customerName);
                                              window.parent.Shadowbox.close();
                                          }
                                      },  // post-submit callback
                };

                \$j('#access').bind('submit', function(e) {
                    e.preventDefault(); // <-- important
                    \$j(this).ajaxSubmit(options);

                    // !!! Important !!!
                    // always return false to prevent standard browser submit and page navigation
                    return false;
                });
          });
        \$j(document).ajaxStart(function() {
            \$j('#loading').show();
        }).ajaxStop(function() {
            \$j('#loading').hide();
        });
</script>
END;
        return $javascript;

    }

    /**
     * Get script for pm shadowbox
     *
     * @param String $tpl
     *
     * @return String $javascript
     */
    function pmAjaxformSubmitForShadowbox($tpl)
    {
        global $_ARRAYLANG;
        $CRM_COMPANY_NAME_ALREADY_PRESENT   = $_ARRAYLANG['TXT_CRM_COMPANYNAME_ALREADY_EXISTS'];
        $javascript = <<<END
<!--  Script refered from
        http://jquery.malsup.com/form/
        -->
<script type="text/javascript" src="../modules/pm/lib/jquery.form.js"></script>
<script type="text/javascript">
          \$J(document).ready(function () {
                var options = {
                    dataType:  'json',
                    success: function(data) {
                {$tpl}(data);
                                      },  // post-submit callback
                };
                function customerDetail(data) {
                    if(data.errChk == 0){
                        if(data.contactId != 0){
                            window.parent.location = "./index.php?cmd=Crm&act=customers&tpl=showcustdetail&id="+data.contactId+"&mes="+data.msg;
                            window.parent.Shadowbox.close();
                            }else{
                            window.parent.location.reload();
                            window.parent.Shadowbox.close();
                            }
                    }
                }
                function addCustomer(data){
                    if(data.errChk == 0){
                        window.parent.changeCustomer(data.customerId, data.customerName);
                        window.parent.Shadowbox.close();
                    }
                }
                \$J('#access').bind('submit', function(e) {
                    e.preventDefault(); // <-- important
                    \$J(this).ajaxSubmit(options);

                    // !!! Important !!!
                    // always return false to prevent standard browser submit and page navigation
                    return false;
                });
          });
        \$J(document).ajaxStart(function() {
            \$J('#loading').show();
        }).ajaxStop(function() {
            \$J('#loading').hide();
        });
</script>
END;
        return $javascript;

    }

    /**
     * Get script for pm contact form
     *
     * @return String $javascript
     */
    function pmAjaxformContactFormSubmit()
    {
        global $_ARRAYLANG;
        $CRM_COMPANY_NAME_ALREADY_PRESENT   = $_ARRAYLANG['TXT_CRM_COMPANYNAME_ALREADY_EXISTS'];
        $javascript = <<<END
<!--  Script refered from
        http://jquery.malsup.com/form/
        -->
<script type="text/javascript" src="../modules/pm/lib/jquery.form.js"></script>
<script type="text/javascript">
          \$J(document).ready(function () {
                var options = {
                    dataType:  'json',
                    success: function(data) {
                                            //alert(data.errChk);
                                          if (data.errChk == 1) {
                                              \$J('#formerr').append('$CRM_COMPANY_NAME_ALREADY_PRESENT');
                                              \$J('#formerr').show();
                                          } else {
                                              //window.parent.changeCustomer(data.customerId, data.customerName);
                                              window.parent.location = './index.php?cmd=Crm&act=customers&tpl=showcustdetail&id='+data.customerId+'&mes='+data.msg;
                                          }
                                      },  // post-submit callback
                };

                \$J('#access').bind('submit', function(e) {
                    e.preventDefault(); // <-- important
                    \$J(this).ajaxSubmit(options);

                    // !!! Important !!!
                    // always return false to prevent standard browser submit and page navigation
                    return false;
                });
          });
        \$J(document).ajaxStart(function() {
            \$J('#loading').show();
        }).ajaxStop(function() {
            \$J('#loading').hide();
        });
</script>
END;
        return $javascript;

    }

    /**
     * Get script for get import analysis
     *
     * @return String $javascript
     */
    function getImportAnalysisJavascript()
    {
        global $_CORELANG, $_ARRAYLANG, $objDatabase;

        $javascript = <<<END
        <script language="JavaScript" type="text/javascript">
        //<![CDATA[
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

function ltrim(s)
{
    var l=0;
    while(l < s.length && s[l] == ' ')
    {    l++; }
    return s.substring(l, s.length);
}

function rtrim(s)
{
    var r=s.length -1;
    while(r > 0 && s[r] == ' ')
    {    r-=1;    }
    return s.substring(0, r+1);
}

function IsNumeric(strString)
   //  check for valid numeric strings
   {
   var strValidChars = "0123456789";
   var strChar;
   var blnResult = true;

   if (strString.length == 0) return false;

   //  test strString consists of valid characters listed above
   for (i = 0; i < strString.length && blnResult == true; i++)
      {
      strChar = strString.charAt(i);
      if (strValidChars.indexOf(strChar) == -1)
         {
         blnResult = false;
         }
      }
   return blnResult;
   }

function addPair()
{

  var selectGiven = document.getElementById("given_field");
  var selectFile = document.getElementById("file_field");
  var selectLeft = document.getElementById("pairs_left");
  var selectRight = document.getElementById("pairs_right");
  if (selectGiven.selectedIndex >= 0 && selectFile.selectedIndex >= 0) {
    selectLeft[selectLeft.length] = new Option(selectFile[selectFile.selectedIndex].text,selectFile[selectFile.selectedIndex].value);
    selectRight[selectRight.length] = new Option(selectGiven[selectGiven.selectedIndex].text, selectGiven[selectGiven.selectedIndex].value);
     selectGiven[selectGiven.selectedIndex] = null;
     selectFile[selectFile.selectedIndex] = null;
     selectFile.selectedIndex = 0;
     selectGiven.selectedIndex = 0;
  }
  resetHidden();
}


function resetHidden()
{
  var selectLeft = document.getElementById("pairs_left");
  var selectRight = document.getElementById("pairs_right");
  var leftKeys = document.getElementById("pairs_left_keys");
  var rightKeys = document.getElementById("pairs_right_keys");
  var tmp = '';
  for (var i = 0; i < selectLeft.length; i++) {
    tmp +=
      (tmp == "" ? "" : ";")+
      selectLeft[i].value;
  }
  leftKeys.value = tmp;

  tmp = '';
  for (var i = 0; i < selectRight.length; i++) {
    tmp +=
      (tmp == "" ? "" : ";")+
      selectRight[i].value;
  }
  rightKeys.value = tmp;
}


function removePair()
{
  var selectGiven = document.getElementById("given_field");
  var selectFile = document.getElementById("file_field");
  var selectLeft = document.getElementById("pairs_left");
  var selectRight = document.getElementById("pairs_right");
  if (selectLeft.selectedIndex >= 0 || selectRight.selectedIndex >= 0) {
    selectFile[selectFile.length] = new Option(selectLeft[selectLeft.selectedIndex].text, selectLeft[selectLeft.selectedIndex].value);
    selectGiven[selectGiven.length] = new Option(selectRight[selectRight.selectedIndex].text, selectRight[selectRight.selectedIndex].value);
    selectLeft[selectLeft.selectedIndex] = null;
    selectRight[selectRight.selectedIndex] = null;
    selectLeft.selectedIndex = 0;
    selectRight.selectedIndex = 0;
  }
  resetHidden();
}
function formValidation(){

    var pairsLeft = document.getElementById('pairs_left_keys').value;

    if(pairsLeft == "") {
        alert("Please Add Pair Fields");
        document.getElementById('file_field').focus();
        return false;
    }

    var custType = document.getElementById('cust_type').value;

    if(custType == "") {
        alert("Please Select Customer Type Field");
        document.getElementById('cust_type').focus();
        return false;
    }

    var parentId = document.getElementById('parent_id').value;

    if(parentId == "") {
        alert("Please Select Parent Id Field");
        document.getElementById('parent_id').focus();
        return false;
    }

    var lang = document.getElementById('lang').value;

    if(lang == "") {
        alert("Please Select Language Field");
        document.getElementById('lang').focus();
        return false;
    }

    var currency = document.getElementById('currency').value;

    if(currency == "") {
        alert("Please Select Currency Field");
        document.getElementById('currency').focus();
        return false;
    }

    return true;
}



//]]>
</script>
END;
        return $javascript;
    }

    /**
     * Get script for get interface
     *
     * @return String $javascript
     */
    function getInterfaceJavascript()
    {
        global $_CORELANG, $_ARRAYLANG, $objDatabase;

        $javascript = <<<END
        <script language="JavaScript" type="text/javascript">
        //<![CDATA[
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

function ltrim(s)
{
    var l=0;
    while(l < s.length && s[l] == ' ')
    {    l++; }
    return s.substring(l, s.length);
}

function rtrim(s)
{
    var r=s.length -1;
    while(r > 0 && s[r] == ' ')
    {    r-=1;    }
    return s.substring(0, r+1);
}

function IsNumeric(strString)
   //  check for valid numeric strings
   {
   var strValidChars = "0123456789";
   var strChar;
   var blnResult = true;

   if (strString.length == 0) return false;

   //  test strString consists of valid characters listed above
   for (i = 0; i < strString.length && blnResult == true; i++)
      {
      strChar = strString.charAt(i);
      if (strValidChars.indexOf(strChar) == -1)
         {
         blnResult = false;
         }
      }
   return blnResult;
   }



function formValidation(){

    var table = document.getElementById('table').value;

    if(table == 0) {
        alert("Please Select the Data");
        document.getElementById('table').focus();
        return false;
    }

    var separator = document.getElementById('import_options_csv_separator').value;

    if(trim(separator) == "") {
        alert("Please Enter the Separator");
        document.getElementById('import_options_csv_separator').focus();
        return false;
    }

    var enclosure = document.getElementById('import_options_csv_enclosure').value;

    if(trim(enclosure) == "") {
        alert("Please Enter the Enclosure");
        document.getElementById('import_options_csv_enclosure').focus();
        return false;
    }

    return true;
}




//]]>
</script>
END;
        return $javascript;
    }

    /**
     * Get script for get final import
     *
     * @return String $javascript
     */
    function getFinalImportJavascript()
    {
        global $_CORELANG, $_ARRAYLANG, $objDatabase;

        $javascript = <<<END
        <script language="JavaScript" type="text/javascript">
        //<![CDATA[
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

function ltrim(s)
{
    var l=0;
    while(l < s.length && s[l] == ' ')
    {    l++; }
    return s.substring(l, s.length);
}

function rtrim(s)
{
    var r=s.length -1;
    while(r > 0 && s[r] == ' ')
    {    r-=1;    }
    return s.substring(0, r+1);
}

function IsNumeric(strString)
   //  check for valid numeric strings
   {
   var strValidChars = "0123456789";
   var strChar;
   var blnResult = true;

   if (strString.length == 0) return false;

   //  test strString consists of valid characters listed above
   for (i = 0; i < strString.length && blnResult == true; i++)
      {
      strChar = strString.charAt(i);
      if (strValidChars.indexOf(strChar) == -1)
         {
         blnResult = false;
         }
      }
   return blnResult;
   }



function addPair()
{

  var selectGiven = document.getElementById("given_field");
  var selectFile = document.getElementById("file_field");
  var selectLeft = document.getElementById("pairs_left");
  var selectRight = document.getElementById("pairs_right");
  if (selectGiven.selectedIndex >= 0 && selectFile.selectedIndex >= 0) {
    selectLeft[selectLeft.length] = new Option(selectFile[selectFile.selectedIndex].text,selectFile[selectFile.selectedIndex].value);
    selectRight[selectRight.length] = new Option(selectGiven[selectGiven.selectedIndex].text, selectGiven[selectGiven.selectedIndex].value);
  }
  resetHidden();
}

function addPair2()
{

  var selectGiven = document.getElementById("given_field2");
  var selectFile = document.getElementById("file_field2");
  var selectLeft = document.getElementById("pairs_left2");
  var selectRight = document.getElementById("pairs_right2");
  if (selectGiven.selectedIndex >= 0 && selectFile.selectedIndex >= 0) {
    selectLeft[selectLeft.length] = new Option(selectFile[selectFile.selectedIndex].text,selectFile[selectFile.selectedIndex].value);
    selectRight[selectRight.length] = new Option(selectGiven[selectGiven.selectedIndex].text, selectGiven[selectGiven.selectedIndex].value);
  }
  resetHidden2();
}

function addPair3()
{

  var selectGiven = document.getElementById("given_field3");
  var selectFile = document.getElementById("file_field3");
  var selectLeft = document.getElementById("pairs_left3");
  var selectRight = document.getElementById("pairs_right3");
  if (selectGiven.selectedIndex >= 0 && selectFile.selectedIndex >= 0) {
    selectLeft[selectLeft.length] = new Option(selectFile[selectFile.selectedIndex].text,selectFile[selectFile.selectedIndex].value);
    selectRight[selectRight.length] = new Option(selectGiven[selectGiven.selectedIndex].text, selectGiven[selectGiven.selectedIndex].value);
  }
  resetHidden3();
}

function addPair4()
{

  var selectGiven = document.getElementById("given_field4");
  var selectFile = document.getElementById("file_field4");
  var selectLeft = document.getElementById("pairs_left4");
  var selectRight = document.getElementById("pairs_right4");
  if (selectGiven.selectedIndex >= 0 && selectFile.selectedIndex >= 0) {
    selectLeft[selectLeft.length] = new Option(selectFile[selectFile.selectedIndex].text,selectFile[selectFile.selectedIndex].value);
    selectRight[selectRight.length] = new Option(selectGiven[selectGiven.selectedIndex].text, selectGiven[selectGiven.selectedIndex].value);
  }
  resetHidden4();
}


function resetHidden()
{
  var selectLeft = document.getElementById("pairs_left");
  var selectRight = document.getElementById("pairs_right");
  var leftKeys = document.getElementById("pairs_left_keys");
  var rightKeys = document.getElementById("pairs_right_keys");
  var tmp = '';
  for (var i = 0; i < selectLeft.length; i++) {
    tmp +=
      (tmp == "" ? "" : ";")+
      selectLeft[i].value;
  }
  leftKeys.value = tmp;

  tmp = '';
  for (var i = 0; i < selectRight.length; i++) {
    tmp +=
      (tmp == "" ? "" : ";")+
      selectRight[i].value;
  }
  rightKeys.value = tmp;
}


function resetHidden2()
{
  var selectLeft = document.getElementById("pairs_left2");
  var selectRight = document.getElementById("pairs_right2");
  var leftKeys = document.getElementById("pairs_left_keys2");
  var rightKeys = document.getElementById("pairs_right_keys2");
  var tmp = '';
  for (var i = 0; i < selectLeft.length; i++) {
    tmp +=
      (tmp == "" ? "" : ";")+
      selectLeft[i].value;
  }
  leftKeys.value = tmp;

  tmp = '';
  for (var i = 0; i < selectRight.length; i++) {
    tmp +=
      (tmp == "" ? "" : ";")+
      selectRight[i].value;
  }
  rightKeys.value = tmp;
}



function resetHidden3()
{
  var selectLeft = document.getElementById("pairs_left3");
  var selectRight = document.getElementById("pairs_right3");
  var leftKeys = document.getElementById("pairs_left_keys3");
  var rightKeys = document.getElementById("pairs_right_keys3");
  var tmp = '';
  for (var i = 0; i < selectLeft.length; i++) {
    tmp +=
      (tmp == "" ? "" : ";")+
      selectLeft[i].value;
  }
  leftKeys.value = tmp;

  tmp = '';
  for (var i = 0; i < selectRight.length; i++) {
    tmp +=
      (tmp == "" ? "" : ";")+
      selectRight[i].value;
  }
  rightKeys.value = tmp;
}



function resetHidden4()
{
  var selectLeft = document.getElementById("pairs_left4");
  var selectRight = document.getElementById("pairs_right4");
  var leftKeys = document.getElementById("pairs_left_keys4");
  var rightKeys = document.getElementById("pairs_right_keys4");
  var tmp = '';
  for (var i = 0; i < selectLeft.length; i++) {
    tmp +=
      (tmp == "" ? "" : ";")+
      selectLeft[i].value;
  }
  leftKeys.value = tmp;

  tmp = '';
  for (var i = 0; i < selectRight.length; i++) {
    tmp +=
      (tmp == "" ? "" : ";")+
      selectRight[i].value;
  }
  rightKeys.value = tmp;
}


//This is the function for remove the Pair
function removePair()
{
  var selectGiven = document.getElementById("given_field");
  var selectFile = document.getElementById("file_field");
  var selectLeft = document.getElementById("pairs_left");
  var selectRight = document.getElementById("pairs_right");
  if (selectLeft.selectedIndex >= 0 || selectRight.selectedIndex >= 0) {
    selectLeft[selectLeft.selectedIndex] = null;
    selectRight[selectRight.selectedIndex] = null;
    selectLeft.selectedIndex = 0;
    selectRight.selectedIndex = 0;
  }
  resetHidden();
}


//This is the function for remove the Pair
function removePair2()
{
  var selectGiven = document.getElementById("given_field2");
  var selectFile = document.getElementById("file_field2");
  var selectLeft = document.getElementById("pairs_left2");
  var selectRight = document.getElementById("pairs_right2");
  if (selectLeft.selectedIndex >= 0 || selectRight.selectedIndex >= 0) {
    selectLeft[selectLeft.selectedIndex] = null;
    selectRight[selectRight.selectedIndex] = null;
    selectLeft.selectedIndex = 0;
    selectRight.selectedIndex = 0;
  }
  resetHidden2();
}


//This is the function for remove the Pair
function removePair3()
{
  var selectGiven = document.getElementById("given_field3");
  var selectFile = document.getElementById("file_field3");
  var selectLeft = document.getElementById("pairs_left3");
  var selectRight = document.getElementById("pairs_right3");
  if (selectLeft.selectedIndex >= 0 || selectRight.selectedIndex >= 0) {
    selectLeft[selectLeft.selectedIndex] = null;
    selectRight[selectRight.selectedIndex] = null;
    selectLeft.selectedIndex = 0;
    selectRight.selectedIndex = 0;
  }
  resetHidden3();
}

//This is the function for remove the Pair
function removePair4()
{
  var selectGiven = document.getElementById("given_field4");
  var selectFile = document.getElementById("file_field4");
  var selectLeft = document.getElementById("pairs_left4");
  var selectRight = document.getElementById("pairs_right4");
  if (selectLeft.selectedIndex >= 0 || selectRight.selectedIndex >= 0) {
    selectLeft[selectLeft.selectedIndex] = null;
    selectRight[selectRight.selectedIndex] = null;
    selectLeft.selectedIndex = 0;
    selectRight.selectedIndex = 0;
  }
  resetHidden4();
}



//]]>
</script>
END;
        return $javascript;
    }

    /**
     * Get script for dropdown toggle
     *
     * @return String $javascript
     */
    function dropdownToggle()
    {
        $javascript = <<<endl
(function() {
    var
      dropdownToggleHash = {};

    cx.jQuery.extend({
        dropdownToggle: function(options) {
            // default options
            options = cx.jQuery.extend({
                //switcherSelector: "#id" or ".class",          - button
                //dropdownID: "id",                             - drop panel
                //anchorSelector: "#id" or ".class",            - near field
                //noActiveSwitcherSelector: "#id" or ".class",  - dont hide
                addTop: 0,
                addLeft: 0,
                position: "absolute",
                fixWinSize: true,
                enableAutoHide: true,
                showFunction: null,
                hideFunction: null,
                alwaysUp: false
            }, options);

            var _toggle = function(switcherObj, dropdownID, addTop, addLeft, fixWinSize, position, anchorSelector, showFunction, alwaysUp) {
                fixWinSize = fixWinSize === true;
                addTop = addTop || 0;
                addLeft = addLeft || 0;
                position = position || "absolute";

                var targetPos = cx.jQuery(anchorSelector || switcherObj).offset();
                var dropdownItem = cx.jQuery("#" + dropdownID);

                var elemPosLeft = targetPos.left;
                var elemPosTop = targetPos.top + cx.jQuery(anchorSelector || switcherObj).outerHeight();

                var w = cx.jQuery(window);
                var topPadding = w.scrollTop();
                var leftPadding = w.scrollLeft();

                if (position == "fixed") {
                    addTop -= topPadding;
                    addLeft -= leftPadding;
                }

                var scrWidth = w.width();
                var scrHeight = w.height();

                if (fixWinSize
                    && (targetPos.left + addLeft + dropdownItem.outerWidth()) > (leftPadding + scrWidth))
                    elemPosLeft = Math.max(0, leftPadding + scrWidth - dropdownItem.outerWidth()) - addLeft;

                if (fixWinSize
                    && (elemPosTop + dropdownItem.outerHeight()) > (topPadding + scrHeight)
                        && (targetPos.top - dropdownItem.outerHeight()) > topPadding
                         || alwaysUp)
                    elemPosTop = targetPos.top - dropdownItem.outerHeight();

                dropdownItem.css(
                    {
                        "position": position,
                        "top": elemPosTop + addTop,
                        "left": elemPosLeft + addLeft
                    });
                if (typeof showFunction === "function")
                    showFunction(switcherObj, dropdownItem);

                dropdownItem.toggle();
            };

            var _registerAutoHide = function(event, switcherSelector, dropdownSelector, hideFunction) {
                if (cx.jQuery(dropdownSelector).is(":visible")) {
                    var \$targetElement = cx.jQuery((event.target) ? event.target : event.srcElement);
                    if (!\$targetElement.parents().andSelf().is(switcherSelector + ", " + dropdownSelector)) {
                        if (typeof hideFunction === "function")
                            hideFunction(\$targetElement);
                        cx.jQuery(dropdownSelector).hide();
                    }
                }
            };

            if (options.switcherSelector && options.dropdownID) {
                var toggleFunc = function(e) {
                    _toggle(cx.jQuery(this), options.dropdownID, options.addTop, options.addLeft, options.fixWinSize, options.position, options.anchorSelector, options.showFunction, options.alwaysUp);
                };
                if (!dropdownToggleHash.hasOwnProperty(options.switcherSelector + options.dropdownID)) {
                    cx.jQuery(options.switcherSelector).live("click", toggleFunc);
                    dropdownToggleHash[options.switcherSelector + options.dropdownID] = true;
                }
            }

            if (options.enableAutoHide && options.dropdownID) {
                var hideFunc = function(e) {
                    var allSwitcherSelectors = options.noActiveSwitcherSelector ?
                        options.switcherSelector + ", " + options.noActiveSwitcherSelector : options.switcherSelector;
                    _registerAutoHide(e, allSwitcherSelectors, "#" + options.dropdownID, options.hideFunction);

                };
                cx.jQuery(document).unbind("click", hideFunc);
                cx.jQuery(document).bind("click", hideFunc);
            }

            return {
                toggle: _toggle,
                registerAutoHide: _registerAutoHide
            };
        }
    });
})();
endl;
        return $javascript;
    }
}
