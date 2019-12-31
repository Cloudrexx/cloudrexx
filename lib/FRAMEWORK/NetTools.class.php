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
 * Net Tools Library
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Thomas Däppen <thomas.daeppen@comvation.com>
 * @version     1.0
 * @access      public
 * @package     cloudrexx
 * @subpackage  lib_framework
 * @todo        Edit PHP DocBlocks!
 */
namespace Cx\Lib;
/**
 * Net Tools Library
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Thomas Däppen <thomas.daeppen@comvation.com>
 * @version     1.0
 * @access      public
 * @package     cloudrexx
 * @subpackage  lib_framework
 */
class NetTools {

    // The timeout of the server
    var $ServerTimeout = 7;

    var $arrWhoisIps = array(
        array(
            'server'    => 'whois.ripe.net',
            'port'      => '43',
            'prepend'   => '',
            'append'    => '',
            'error'     => '%ERROR:101: no entries found',
        ),
        array(
            'server'    => 'whois.arin.net',
            'port'      => '43',
            'prepend'   => '',
            'append'    => '',
            'error'     => 'No match found for',
        ),
        array(
            'server'    => 'whois.apnic.net',
            'port'      => '43',
            'prepend'   => '',
            'append'    => '',
            'error'     => '%ERROR:101: no entries found',
        ),
        array(
            'server'    => 'whois.aunic.net',
            'port'      => '43',
            'prepend'   => '',
            'append'    => '',
            'error'     => 'No Data Found',
        )
    );


    var $arrWhoisDomains = array(
        array(
            'server'    => "whois.networksolutions.com",
            'port'      => 43,
            'types'     => array("com","net","edu"),
            'prepend'   => "",
            'append'    => "",
            'error'     => "No match for \""
        ),
        array(
            'server'    => "whois.aunic.net",
            'port'      => 43,
            'types'     => array("au"),
            'prepend'   => "",
            'append'    => "",
            'error'     => "No Data Found"
        ),
        array(
            'server'    => "whois.afilias.net",
            'port'      => 43,
            'types'     => array("info"),
            'prepend'   => "",
            'append'    => "",
            'error'     => "NOT FOUND"
        ),
        array(
            'server'    => "whois.publicinterestregistry.net",
            'port'      => 43,
            'types'     => array("org"),
            'prepend'   => "",
            'append'    => "",
            'error'     => "NOT FOUND"
        ),
        array(
            'server'    => "whois.corenic.net",
            'port'      => 43,
            'types'     => array("org"),
            'prepend'   => "",
            'append'    => "",
            'error'     => "    The requested domain "
        ),
        array(
            'server'    => "whois.eu",
            'port'      => 43,
            'types'     => array("eu"),
            'prepend'   => "",
            'append'    => "",
            'error'     => "NOT FOUND"
        ),
        array(
            'server'    => "whois.nic.name",
            'port'      => 43,
            'types'     => array("name"),
            'prepend'   => "",
            'append'    => "",
            'error'     => "No match."
        ),
        array(
            'server'    => "whois.nic.biz",
            'port'      => 43,
            'types'     => array("biz"),
            'prepend'   => "",
            'append'    => "",
            'error'     => "Not found: "
        ),
        array(
            'server'    => "whois.enicregistrar.com",
            'port'      => 43,
            'types'     => array("cc"),
            'prepend'   => "",
            'append'    => "",
            'error'     => "No match for \""
        ),
        array(
            'server'    => "whois.nic.ws",
            'port'      => 43,
            'types'     => array("ws"),
            'prepend'   => "",
            'append'    => "",
            'error'     => "No match for \""
        ),
        array(
            'server'    => "whois.domain-registry.nl",
            'port'      => 43,
            'types'     => array("nl"),
            'prepend'   => "",
            'append'    => "",
            'error'     => "{DOMAIN} is free"
        ),
        array(
            'server'    => "whois.nic.ac",
            'port'      => 43,
            'types'     => array("ac"),
            'prepend'   => "",
            'append'    => "",
            'error'     => "No match for \""
        ),
        array(
            'server'    => "whois.nic.as",
            'port'      => 43,
            'types'     => array("as"),
            'prepend'   => "",
            'append'    => "",
            'error'     => "Domain Not Found"
        ),
        array(
            'server'    => "whois.dns.be",
            'port'      => 43,
            'types'     => array("be"),
            'prepend'   => "",
            'append'    => "",
            'error'     => "% No such domain"
        ),
        array(
            'server'    => "whois.nic.br",
            'port'      => 43,
            'types'     => array("br"),
            'prepend'   => "",
            'append'    => "",
            'error'     => "% No match for domain \""
        ),
        array(
            'server'    => "whois.nic.cc",
            'port'      => 43,
            'types'     => array("cc"),
            'prepend'   => "",
            'append'    => "",
            'error'     => "No match for \""
        ),
        array(
            'server'    => "whois.nic.ch",
            'port'      => 43,
            'types'     => array("ch"),
            'prepend'   => "",
            'append'    => "",
            'error'     => "We do not have an entry in our database matching your query."
        ),
        array(
            'server'    => "whois.ck-nic.org.ck",
            'port'      => 43,
            'types'     => array("ck"),
            'prepend'   => "",
            'append'    => "",
            'error'     => "% No entries found for the selected source(s)."
        ),
        array(
            'server'    => "whois.cnnic.net.cn",
            'port'      => 43,
            'types'     => array("cn"),
            'prepend'   => "",
            'append'    => "",
            'error'     => "no matching record"
        ),
        array(
            'server'    => "whois.nic.fr",
            'port'      => 43,
            'types'     => array("fr"),
            'prepend'   => "",
            'append'    => "",
            'error'     => "%% No entries found in the AFNIC Database."
        ),
        array(
            'server'    => "whois.nic.gov",
            'port'      => 43,
            'types'     => array("gov"),
            'prepend'   => "",
            'append'    => "",
            'error'     => ""
        ),
        array(
            'server'    => "whois.idnic.net.id",
            'port'      => 43,
            'types'     => array("id"),
            'prepend'   => "",
            'append'    => "",
            'error'     => "%error 230 No Objects Found"
        ),
        array(
            'server'    => "whois.ja.net",
            'port'      => 43,
            'types'     => array("ja"),
            'prepend'   => "",
            'append'    => "",
            'error'     => "No such domain "
        ),
        array(
            'server'    => "whois.nic.ad.jp",
            'port'      => 43,
            'types'     => array("jp"),
            'prepend'   => "",
            'append'    => "",
            'error'     => "No match!!"
        ),
        array(
            'server'    => "whois.nic.or.kr",
            'port'      => 43,
            'types'     => array("kr"),
            'prepend'   => "",
            'append'    => "",
            'error'     => "Above domain name is not registered to KRNIC."
        ),
        array(
            'server'    => "whois.krnic.net",
            'port'      => 43,
            'types'     => array("kr"),
            'prepend'   => "",
            'append'    => "",
            'error'     => ""
        ),
        array(
            'server'    => "whois.nic.li",
            'port'      => 43,
            'types'     => array("li"),
            'prepend'   => "",
            'append'    => "",
            'error'     => "We do not have an entry in our database matching your query."
        ),
        array(
            'server'    => "whois.nic.mil",
            'port'      => 43,
            'types'     => array("mil"),
            'prepend'   => "",
            'append'    => "",
            'error'     => ""
        ),
        array(
            'server'    => "whois.nic.mx",
            'port'      => 43,
            'types'     => array("mx"),
            'prepend'   => "",
            'append'    => "",
            'error'     => ""
        ),
        array(
            'server'    => "whois.norid.no",
            'port'      => 43,
            'types'     => array("no"),
            'prepend'   => "",
            'append'    => "",
            'error'     => "% no matches"
        ),
        array(
            'server'    => "whois.nic.nu",
            'port'      => 43,
            'types'     => array("nu"),
            'prepend'   => "",
            'append'    => "",
            'error'     => "NO MATCH for domain "
        ),
        array(
            'server'    => "whois.nic-se.se",
            'port'      => 43,
            'types'     => array("se"),
            'prepend'   => "",
            'append'    => "",
            'error'     => "# No data found."
        ),
        array(
            'server'    => "whois.nic.net.sg",
            'port'      => 43,
            'types'     => array("sg"),
            'prepend'   => "",
            'append'    => "",
            'error'     => "NOMATCH"
        ),
        array(
            'server'    => "whois.nic.sh",
            'port'      => 43,
            'types'     => array("sh"),
            'prepend'   => "",
            'append'    => "",
            'error'     => ""
        ),
        array(
            'server'    => "whois.thnic.net",
            'port'      => 43,
            'types'     => array("th"),
            'prepend'   => "",
            'append'    => "",
            'error'     => "% No entries found for the selected source(s)."
        ),
        array(
            'server'    => "whois.tonic.to",
            'port'      => 43,
            'types'     => array("to"),
            'prepend'   => "",
            'append'    => "",
            'error'     => "No match for "
        ),
        array(
            'server'    => "whois.twnic.net",
            'port'      => 43,
            'types'     => array("tw"),
            'prepend'   => "",
            'append'    => "",
            'error'     => "No such Domain Name"
        ),
        array(
            'server'    => "whois.twnic.net.tw",
            'port'      => 43,
            'types'     => array("tw"),
            'prepend'   => "",
            'append'    => "",
            'error'     => "No such Domain Name"
        ),
        array(
            'server'    => "whois.nic.uk",
            'port'      => 43,
            'types'     => array("uk"),
            'prepend'   => "",
            'append'    => "",
            'error'     => "   No match for \""
        ),
        array(
            'server'    => "whois.adamsnames.tc",
            'port'      => 43,
            'types'     => array("tc"),
            'prepend'   => "",
            'append'    => "",
            'error'     => "No\n"
        ),
        array(
            'server'    => "whois.opensrs.net",
            'port'      => 43,
            'types'     => array("com","edu","net"),
            'prepend'   => "",
            'append'    => "",
            'error'     => "Can't get information on non-local domain"
        ),
        array(
            'server'    => "whois.alldomains.com",
            'port'      => 43,
            'types'     => array(""),
            'prepend'   => "",
            'append'    => "",
            'error'     => "No entries found."
        )
    );

    function NetTools() {
        $this->__construct();
    }

    function __construct() {
    }



    function IsIP($host)
    {
        // Make sure it's at least #.#.#.#
        if(preg_match("/^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$/", $host))
        {
            // If so, make sure none of the octets are higher than 255!
            $octets = explode(".", $host);
            return ($octets[0] < 256 &&
                $octets[1] < 256 &&
                $octets[2] < 256 &&
                $octets[3] < 256);
        }

        // It's not an IP address
        return false;
    }

    /*
    * LookupIP($ip, &$hostname);
    * Gets the hostname from IP and returns whether it succeeded
    * Parameters:
    *   1. $ip: The IP to convert to the hostname
    *   2. &$hostname: Will return the converted hostname (or the IP if it failed)
    * Returns: Whether or not it was successful
    */
    function LookupIP($ip, &$hostname)
    {
        return (($hostname = gethostbyaddr($ip)) != $ip);
    }

    /*
    * LookupDomain($hostname, &$ip);
    * Gets the IP from hostname and returns whether it succeeded
    * Parameters:
    *   1. $hostname: The domain name to convert to the hostname
    *   2. &$ip: Will return the found IP (or the domain name if it failed)
    * Returns: Whether or not it was successful
    */
    function LookupDomain($hostname, &$ip)
    {
        return (($ip = gethostbyname($hostname)) != $hostname);
    }

    /*
    * PingHost($targethost, &$err);
    * Pings a host and return the output
    * Parameters:
    *   1. $targethost: The host to ping
    *   2. &$err: Will return the occured error (or 0 on success)
    * Returns: The output of the ping command (returns: \n, return as last character)
    * Errors:
    *   #1: Insecure host to ping to
    */
    function PingHost($targethost, &$err) {
        $err = 0;

        // Make sure the host is valid
        if(!(preg_match("/^[a-zA-Z0-9_\\.-]+$/", $targethost) && substr($targethost, 0, 1) != "-")) {
            // Invalid host; it's insecure to run ping on this
            $err = -1;
        } else {
            // Ping the host
            if (substr(PHP_OS,0,3) == "WIN") {
                exec("ping ".escapeshellcmd($targethost)." 2>&1", $output);
            } else {
                exec("ping -c 4 ".escapeshellcmd($targethost)." 2>&1", $output);
            }
            $output = implode("\n", $output);
        }

        return $output;
    }

    /*
    * ProbePort($targethost, $destport, &$banner, &$errdesc);
    * Tests whether a port is opened on the server
    * Parameters:
    *   1. $targethost: The host to probe the port from
    *   2. $destport: The port to probe on the server
    *   3. &$banner: The banner of the port, with linefeeds
    *   4. &$errdesc: Will return the error description of fsockopen
    * Returns: The occured error (or 0 on success)
    * Errors:
    *   #-1: Invalid port passed
    *   Rest: Returned from fsockopen
    */
    function ProbePort($targethost, $destport, &$errdesc) {
        $errdesc = "";

        // test whether the port is valid
        if(!is_numeric($destport) || $destport <= 0 || $destport > 65535) {
            // Non existant port number
            return -1;
        } else {
            $sock = @fsockopen($targethost, $destport, $errno, $errdesc, 3);

            // Determine whether it was a success
            if(!$sock) {
                // Unable to connect to server
                return $errdesc;
            } else {
                // Port is opened!
                fclose($sock);

                return 0;
            }
        }
    }


    /*
    * WhoisIP($ip);
    * Whois an IP and return the result (or "" on failure)
    * Parameters:
    *   1. $ip: The IP to whois
    * Returns: The whois information ("" on failure)
    */
    function WhoisIP($ip) {
        $whoisInfo = "";

        if (empty($ip)) {
            return $whoisInfo;
        }

        // For every defined server
        $bestresult = "";
        $greatestres = 0;
        $lastref = -1;
        for ($i = 0; $i < count($this->arrWhoisIps); $i++) {
            // Create the socket
            $sock = @fsockopen($this->arrWhoisIps[$i]['server'], $this->arrWhoisIps[$i]['port'], $errno, $errdesc, $this->ServerTimeout);

            if($sock) {
                // Send the requested IP
                fputs($sock, $this->arrWhoisIps[$i]['prepend'].$ip.$this->arrWhoisIps[$i]['append']."\r\n");

                // Get all the response
                $resp = "";
                while(($response = fgets($sock)))
                    $resp .= $response;

                // Close the socket
                fclose($sock);

                // Get the country: data
                $splresp = preg_split("/country:/i", $resp);

//              $hascountry = 0;
//              if (count($splresp)>1) {
//                  for($j = 1;$j < count($splresp) && $hascountry == 0; $j++) {
//                      // Get the country code only
//                      $countrycode = $splresp[$j];
//                      if (strpos($countrycode, "\n") !== false)
//                          $countrycode = substr($countrycode, 0, strpos($countrycode, "\n"));
//                      $countrycode = trim($countrycode);
//
//                      if (strpos($countrycode, ".") === false && strpos($countrycode, "/") === false && strpos($countrycode, "\\") === false) {
//                          $hascountry = 1;
//                          $arrWhoisInfo['country'] = strtolower($countrycode);
//                      } else {
//                          $arrWhoisInfo['country'] = "";
//                      }
//                  }
//              } else {
//                  $arrWhoisInfo['country'] = "";
//              }

                if (strstr($resp, "\n".$this->arrWhoisIps[$i]['error']) === false && substr($resp, 0, strlen($this->arrWhoisIps[$i]['error'])) != $this->arrWhoisIps[$i]['error'] && strstr($resp, "0.0.0.0 - 255.255.255.255") === false) {
                    // If this string contains a netname, get it
                    $netname = substr(strstr($resp, "netname:"), 8);
                    if (strpos($netname, "\n") !== false)
                        $netname = substr($netname, 0, strpos($netname, "\n"));

                    $netname = trim($netname);
                    //$arrWhoisInfo['netname'] = $netname;

                    // If the netname is in the following list, it's the best result but not
                    // quite good enough:
                    // ERX-NETBLOCK, NETBLK-RIPE, ARIN-CIDR-BLOCK, *-RIPE
                    if($netname == "ERX-NETBLOCK" || $netname == "NETBLK-RIPE" || $netname == "ARIN-CIDR-BLOCK" || strstr($netname, "-RIPE") !== false || stristr($resp, "ReferralServer: ")) {
                        // Store it as best so far, if there hasn't been a $greatestres yet
                        if($greatestres == 0)
                            $whoisInfo = $resp;
                    } else {
                        // If the netname contains BLOCK or NETBLK, try to find a better one
                        if (strstr($netname, "BLOCK") !== false || strstr($netname, "NETBLK")) {
                            // Store it as "possibly not perfect
                            if ($greatestres == 0) {
                                $whoisInfo = $resp;
                                $greatestres = 1;
                            }
                        } else {    // Perfect!
                            // Succeeded, bail out
                            $whoisInfo = $resp;

                            return $whoisInfo;
                        }
                    }
                }

                // If the result has "ReferralServer:"
                if (($referral = stristr($resp, "ReferralServer: ")) !== false) {
                    $referral = substr($referral, strlen("RefferalServer:"));
                    $referral = trim(substr($referral, 0, strpos($referral, "\n")));
                    if (substr($referral, 0, 8) == "whois://")
                        $referral = substr($referral, 8);
                    if(substr($referral, -1, 1) == "/")
                        $referral = substr($referral, 0, -1);

                    // Make sure this isn't a referral already
                    if ($lastref != $i) {
                        // Next will be a referral
                        $lastref = $i;

                        list($this->arrWhoisIps[$i]['server'], $this->arrWhoisIps[$i]['port']) = explode(":", $referral, 2);
                        $i--;
                    }
                }
            }
        }

        // Tried 'em all, all failed, return the best result
        return $whoisInfo;
    }

    /*
    * WhoisDomain($domain);
    * Whois a domain and return the result (or "" on failure)
    * Parameters:
    *   1. $domain: The domain to whois
    * Returns: The whois information ("" on failure)
    */
    function WhoisDomain($domain)
    {
        if (empty($domain)) {
            return "";
        }

        // Only request the domain... so www.astalavista.net => astalavista.net
        while(substr_count($domain, ".") > 1)
            $domain = substr($domain, strpos($domain, ".") + 1);

        // For every defined server
        foreach ($this->arrWhoisDomains as $arrWhoisDomain) {
            // Search the domain type
            foreach ($arrWhoisDomain['types'] as $arrWhoisDomainType) {
                // If it's "" or it matches
                if ($arrWhoisDomainType == "" || substr($domain, -strlen($arrWhoisDomainType) - 1) == ".".$arrWhoisDomainType) {
                    // Create the socket
                    $sock = @fsockopen($arrWhoisDomain['server'], $arrWhoisDomain['port'], $errno, $errdesc, $this->ServerTimeout);
                    if ($sock) {
                        // Send the requested IP
                        fputs($sock, $arrWhoisDomain['prepend'].$domain.$arrWhoisDomain['append']."\r\n");

                        // Get all the response
                        $resp = "";
                        while(($response = fgets($sock)))
                            $resp .= $response;

                        // Close the socket
                        fclose($sock);

                        if (strstr($resp, "\n".str_replace("{DOMAIN}", $domain, $arrWhoisDomain['error'])) === false && substr($resp, 0, strlen($arrWhoisDomain['error'])) != $arrWhoisDomain['error'] && strstr($resp, "0.0.0.0 - 255.255.255.255") === false) {

                            // Succeeded, bail out
                            return $resp;
                        }
                    }
                    break;
                }
            }
        }

        // Tried 'em all, all failed, return ""
        return "";
    }
}


    /* This function requires PHP 5 and can therefore not be used here */
    //if($action == "getdnsrec")
    //{
    //  // If the requested host is an IP address, lookup the address
    //  $actaddr = $targethost;
    //  if($isip)
    //  {
    //      // Lookup the target host
    //      $actaddr = getaddrbyaddr($targethost);
    //      if($actaddr == $targethost)
    //      {
    //          // Failed to lookup address
    //          $actout .= "Could not get domainname of $targethost<br>";
    //      } else {
    //          // Notify the user of the usage of the domain name
    //          $actout .= "$targethost has domain name $actaddr<br>";
    //          $actout .= "Querying DNS record for $actaddr<br><br>";
    //      }
    //  }
    //
    //  // Get a DNS record
    //  if(!$isip || $actaddr != $targethost)
    //  {
    //      // Grab the DNS record
    //      $dnsinfo = dns_get_record($actaddr, DNS_ANY, $authns, $addtl);
    //
    //      for($cnt = 0; $cnt < 3; $cnt++)
    //      {
    //          switch($cnt)
    //          {
    //          case 0:
    //              $curdata = $dnsinfo;
    //              $actout .= "<font size=+1><b>DNS records:</b></font><br>";
    //
    //              break;
    //
    //          case 1:
    //              $curdata = $authns;
    //              $actout .= "<font size=+1><b>Authoritative Name Servers:</b></font><br>";
    //
    //              break;
    //
    //          case 2:
    //              $curdata = $addtl;
    //              $actout .= "<font size=+1><v>Additional Records:</b></font><br>";
    //
    //              break;
    //          }
    //
    //          // Show the info
    //          if(count($dnsinfo) == 0)
    //              $actout .= "None<br>";
    //          for($i = 0; $i < count($curdata); $i++)
    //          {
    //              // Fill in this information
    //              $actout .= "<b>record $i</b><br>";
    //              foreach($curdata[$i] as $recname => $recvalue)
    //              {
    //                  // Display this one
    //                  $actout .= "$recname: $recvalue<br>";
    //              }
    //              $actout .= "<br>";
    //          }
    //          $actout .= "<br>";
    //      }
    //  }
    //}
