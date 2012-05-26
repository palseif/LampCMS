<?php
/**
 *
 * License, TERMS and CONDITIONS
 *
 * This software is lisensed under the GNU LESSER GENERAL PUBLIC LICENSE (LGPL) version 3
 * Please read the license here : http://www.gnu.org/licenses/lgpl-3.0.txt
 *
 *  Redistribution and use in source and binary forms, with or without
 *  modification, are permitted provided that the following conditions are met:
 * 1. Redistributions of source code must retain the above copyright
 *    notice, this list of conditions and the following disclaimer.
 * 2. Redistributions in binary form must reproduce the above copyright
 *    notice, this list of conditions and the following disclaimer in the
 *    documentation and/or other materials provided with the distribution.
 * 3. The name of the author may not be used to endorse or promote products
 *    derived from this software without specific prior written permission.
 *
 * ATTRIBUTION REQUIRED
 * 4. All web pages generated by the use of this software, or at least
 *       the page that lists the recent questions (usually home page) must include
 *    a link to the http://www.lampcms.com and text of the link must indicate that
 *    the website\'s Questions/Answers functionality is powered by lampcms.com
 *    An example of acceptable link would be "Powered by <a href="http://www.lampcms.com">LampCMS</a>"
 *    The location of the link is not important, it can be in the footer of the page
 *    but it must not be hidden by style attibutes
 *
 * THIS SOFTWARE IS PROVIDED BY THE AUTHOR "AS IS" AND ANY EXPRESS OR IMPLIED
 * WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF
 * MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.
 * IN NO EVENT SHALL THE FREEBSD PROJECT OR CONTRIBUTORS BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF
 * THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This product includes GeoLite data created by MaxMind,
 *  available from http://www.maxmind.com/
 *
 *
 * @author     Dmitri Snytkine <cms@lampcms.com>
 * @copyright  2005-2012 (or current year) Dmitri Snytkine
 * @license    http://www.gnu.org/licenses/lgpl-3.0.txt GNU LESSER GENERAL PUBLIC LICENSE (LGPL) version 3
 * @link       http://www.lampcms.com   Lampcms.com project
 * @version    Release: @package_version@
 *
 *
 */
namespace Lampcms\Geo;

class Ip
{

    protected $MongoDB;


    /**
     *
     * Constructor
     * @param \Mongo $Mongo Instance of php's Mongo object
     */
    public function __construct(\MongoDB $M)
    {
        $this->MongoDB = $M;
    }


    /**
     * Get Location object for the ip address
     *
     * @param string $ip ip address
     * @return object of type Location
     */
    public function getLocation($ip = null)
    {
        $ip = (null !== $ip) ? $ip : \Lampcms\Request::getIP();

        if (false === $l = $this->isPublic($ip)) {
            d('ip not public');

            return new Location();
        }

        if (4 === PHP_INT_SIZE) {
            $l = sprintf("%u", $l);
        }

        $i = (double)$l;
        $a = $this->MongoDB->GEO_BLOCKS->findOne(array('s' => array('$lte' => $i), 'e' => array('$gte' => $i)), array('l'));

        if (is_array($a) && !empty($a['l'])) {
            /**
             * Important: must exclude _id from returned data, otherwise
             * it may override another _id when doing array_merge
             *
             */
            $loc = $this->MongoDB->GEO_LOCATION->findOne(array('_id' => $a['l']), array('_id' => 0));

            return new Location($loc);
        }

        return new Location();
    }


    /**
     * Magic method to allow getting
     * Location object as property.
     * For example to get array of Geo Data:
     * $Ip->Location->toArray();
     *
     * Enter description here ...
     * @param string $name
     * @throws \RuntimeException if requesting any
     * property other than 'Location'
     */
    public function __get($name)
    {
        if ('Location' === $name) {
            return $this->getLocation();
        }

        throw new \RuntimeException('Unknown property ' . $name);
    }


    /**
     * Test if ip address is public
     * Takes into consideration
     * different results of shift on
     * 64-bit and 32-bit system
     *
     * @param string $ip ipv4 ip address
     * @return mixed int result of ip2long or false if Ip
     * is private
     */
    public function isPublic($ip)
    {
        /**
         * Allowing to pass the value of already
         * resolved ip2long converted to double
         */
        if (is_double($ip)) {
            $long = $ip;
        } elseif (is_string($ip)) {
            if (false === $long = ip2long($ip)) {

                return false;
            }
        } else {
            throw new \InvalidArgumentException('Param $ip must be double or string. Was: ' . gettype($ip));
        }

        if (8 === PHP_INT_SIZE) {
            if (
                ($long >> 24) === 127
                || ($long >> 24) === 10
                // 169.254.0.0/16
                || ($long >> 16) === 43518
                // 192.168.0.0/16
                || ($long >> 16) === 49320
                // 172.16.0.0/20
                || ($long >> 20) === 2753
            ) {
                return false;
            }
        } else {
            if (
                ($long >> 24) === 127
                || ($long >> 24) === 10
                // 169.254.0.0/16
                || ($long >> 16) === -22018
                // 192.168.0.0/16
                || ($long >> 16) === -16216
                // 172.16.0.0/20
                || ($long >> 20) === -1343
            ) {
                return false;
            }
        }

        return $long;
    }

}
