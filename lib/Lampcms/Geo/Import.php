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

class Import
{

    /**
     * Object of type MongoDB
     *
     * @see http://us3.php.net/mongodb
     *
     * @var object
     */
    protected $Mongo;

    /**
     * Location of "blocks" .csv file
     * File should be downloaded from Maxmind.com
     * website
     *
     * @var string
     */
    protected $blocksFile;

    protected $locationFile;

    protected $dir;

    /**
     *
     * Constructor
     * @param object $MongoDB Instance of php's MongoDB object
     * @param string $blocksFile full path to location of blocks .csv file
     * @param string $locationFile full path to location .csv File
     */
    public function __construct(\MongoDB $MongoDB, $blocksFile, $locationFile)
    {

        $this->Mongo = $MongoDB;
        $this->blocksFile = $blocksFile;
        $this->locationFile = $locationFile;
    }


    public function run()
    {
        $this->drop();
        $this->importBlocks();
        $this->importLocation();
    }


    protected function drop()
    {

        $this->Mongo->GEO_BLOCKS->drop();
        $this->Mongo->GEO_LOCATION->drop();

        return $this;
    }

    protected function importBlocks()
    {
        $handle = fopen($this->blocksFile, 'r');
        if (false === $handle) {
            throw new \Exception('Unable to read file: ' . $this->blocksFile);

        }

        $coll = $this->Mongo->GEO_BLOCKS;
        $coll->ensureIndex(array('s' => 1));
        $coll->ensureIndex(array('e' => 1));

        echo '<br>Starting the import process. Be patient, it may take about 5-10 minutes, depending on your server.';

        $row = 0;
        while (($data = fgetcsv($handle, 1000, ",")) !== false) {
            $num = count($data);
            if ($num > 2 && is_numeric($data[0])) {
                $a = array(
                    's' => (float)$data[0],
                    'e' => (float)$data[1],
                    'l' => (int)$data[2]
                );

                $coll->insert($a);
            }

            $row += 1;
        }

        echo '<br>Imported ' . number_format($row) . ' rows to GEO_BLOCKS collection';
    }


    /**
     *
     * Create GEO_LOCATION collection
     * If one already exists it will be dropped first
     * and then recreated
     * @throws \Exception
     */
    protected function importLocation()
    {
        $handle = fopen($this->locationFile, 'r');
        if (false === $handle) {
            throw new \Exception('Unable to read file: ' . $this->locationFile);

        }

        $coll = $this->Mongo->GEO_LOCATION;
        $row = 0;
        while (($data = fgetcsv($handle, 1000, ",")) !== false) {
            $num = count($data);
            if ($num > 6 && is_numeric($data[0])) {
                $a = array(
                    '_id' => (int)$data[0],
                    'cc' => \utf8_encode($data[1]),
                );

                /**
                 * Currently Region (state) only imported for US and Canada
                 */
                if (!empty($data[2]) && ('US' === $a['cc'] || 'CA' === $a['cc'])) {
                    $a['state'] = \utf8_encode($data[2]);
                }

                if (!empty($data[3])) {
                    $a['city'] = \utf8_encode($data[3]);
                }

                if (!empty($data[4])) {
                    $a['zip'] = \utf8_encode($data[4]);
                }

                if (!empty($data[5])) {
                    $a['lat'] = (double)$data[5];
                }

                if (!empty($data[6])) {
                    $a['lon'] = (double)$data[6];
                }

                $coll->insert($a);
                $row += 1;
            }
        }

        echo '<br>Imported ' . number_format($row) . ' rows to GEO_LOCATION collection';
    }
}
