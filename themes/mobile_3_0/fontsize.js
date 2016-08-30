// JavaScript-Funktionen zur Steuerung der Schriftgroesse in einem HTML-Dokument
// Copyright (C) 2005 Alexander Mueller
// Autor: Alexander Mueller
// Web:   http://www.EvoComp.de/
// Datei: fontsize.js
// The copyright notice must stay intact for use!
// You can obtain this and other scripts at http://www.EvoComp.de/scripts/skripte.html
//
// This program is distributed in the hope that it will be useful,
// but without any warranty, expressed or implied.
// !!! Benutzerdefinierte Variablen !!!
// Folgende Variablen koennen vom Benutzer definiert werden
// (die Werte werden durch entsprechend uebergebene Parameter der Funktion
//  init_FontSize ueberschrieben):
// Standardschriftgroesse
var initial_font_size = 1;
// Einheit, in der die Schriftgroesse angegeben ist
var font_unity = 'em';
// Schrittweite, mit der die Schrift erhoeht bzw. erniedrigt werden soll
var delta = 0.1;
// !!! Ende Benutzerdefinierte Variablen !!!
// zur Speicherung der aktuellen Schriftgroesse
var fsize = initial_font_size;
// Initialisierung der Schriftgroesse auf in der URL uebergebene, die der Funktion
// uebergebene bzw. oben angegebene Standardgroesse (falls keine Parameter uebergeben
// wurden)
function init_FontSize (ifs, fu, del)
{
    if (!isNaN(ifs))
        initial_font_size = parseFloat(ifs);
    if ("pt,pc,in,mm,cm,px,em,ex,%".indexOf (fu) != -1)
        font_unity = fu;
    if (!isNaN(del))
        delta = parseFloat(del);
    if (isNaN(parseFloat(document.getElementsByTagName('body')[0].style.fontSize)) || parseFloat(document.getElementsByTagName('body')[0].style.fontSize) == 0)
        fsize = getFontSize ();
    else
        fsize = parseFloat(document.getElementsByTagName('body')[0].style.fontSize);
    if (fsize != initial_font_size)
        addFontSizeToLinks ();
    document.getElementsByTagName('body')[0].style.fontSize = fsize + font_unity;
}
// Schriftgroesse um delta erhoehen
function incFontSize ()
{
    if (!isNaN(delta))
        setFontSize (Math.round((parseFloat(fsize) + parseFloat(delta)) * 100) / 100);
}
// Schriftgroesse um delta verkleinern
function decFontSize ()
{
    if (!isNaN(delta))
        setFontSize (Math.round((parseFloat(fsize) - parseFloat(delta)) * 100) / 100);
}
// Schriftgroesse zurÃ¼cksetzten
function resetFontSize ()
{
    setFontSize (0.75);
}
// Schriftgroesse um delta veraendern
function setFontSize (newsize)
{
    if (!isNaN(newsize))
    {
        fsize = Math.round((parseFloat(newsize)) * 100) / 100;
        document.getElementsByTagName('body')[0].style.fontSize = fsize + font_unity;
        addFontSizeToLinks ();
    }
}
// Eingestellte Schriftgroesse aus der URL auslesen bzw. Standardwert fuer die
// Schriftgroesse liefern
function getFontSize ()
{
    var fs;
    if (document.location.search != "" && (document.location.search).match (/fsize=[0-9]+\.?[0-9]*/i))
        fs = ("" + (document.location.search).match (/fsize=[0-9]+\.?[0-9]*/i)).replace (/fsize=/i, '');
    else
        fs = initial_font_size;
    return fs;
}
// Liefert die Domaenen-URL zu einer uebergebenen URL.
// Bei ungueltiger URL wird ein leeres String zurueckgegeben
function getDomainURL (URL)
{
    if (URL.match (/^((http:\/\/)?(www\.)?((([0-9a-z][0-9a-z-]+\.)+)([a-z]{2,3}))).*/))
        return URL.match (/^((http:\/\/)?(www\.)?((([0-9a-z][0-9a-z-]+\.)+)([a-z]{2,3}))).*/)[1];
    else
        return "";
}
// Schriftgroesse an interne URLs anhaengen, damit die eingestellte Schriftgroesse
// auch in Folgeseiten beibehalten wird
function addFontSizeToLinks ()
{
    // Alle Links im aktuellen HTML-Dokument bearbeiten
    for (i = 0; i < document.links.length; i++)
    {
        // Links, die dem Verschicken von E-Mails dienen und URLs, die
        // mit dem Domainnamen enden (Probleme bei IE, der die Parameter
        // in den Links anzeigt) sollen nicht parametrisiert werden.
        if (!(document.links[i].href).match (/^mailto:/)
            && !(document.links[i].href).match (/^(http:\/\/)?(www\.)?((([0-9a-z][0-9a-z-]+\.)+)([a-z]{2,3}))[\/]+$/)
            // nur URLs, die auf die eigene Domain zeigen sollen
            // Parameter erhalten (keine externen Links)
            && (document.links[i].href).indexOf (getDomainURL (self.location.href)) != -1
            // Parameter sollen zusaetzlich noch die in 'pardomains'
            // enthaltenen Domains erhalten.
            //&& !(document.links[i].href).match (/ausnahmedomains/)
            )
            {
            // gewaehlte Schriftgroesse an den Link anhaengen bzw.
            // bisher gespeicherte Werte durch die aktuellen ersetzen
            if ((document.links[i].href).match (/fsize=[0-9]+\.?[0-9]{0,2}/i))
                document.links[i].href = (document.links[i].href).replace (/fsize=[0-9]+\.?[0-9]{0,2}/i, ("fsize=" + fsize));
            else
            if ((document.links[i].href).indexOf ('?') != -1)
                document.links[i].href = document.links[i].href + "&fsize=" + fsize;
            else
                document.links[i].href = document.links[i].href + "?fsize=" + fsize;
        }
    }
}
