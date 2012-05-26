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


namespace Lampcms\Modules\Blogger;

use \Lampcms\Interfaces\Post;
use \Lampcms\Interfaces\Question;
use \Lampcms\Interfaces\Answer;
use \Lampcms\String\HTMLString;

/**
 * Adapter class that takes Post
 * (instance of Question or Answer)
 * as input and returns instance of Entry
 * as output
 *
 *
 * @author Dmitri Snytkine
 *
 */
class BloggerPostAdapter
{
    /**
     * Registry object
     *
     * @var object
     */
    protected $Registry;


    /**
     * Object Entry
     *
     * @var object
     */
    protected $oEntry;


    /**
     * Constructor
     *
     * @param \Lampcms\Registry $o
     */
    public function __construct(\Lampcms\Registry $o)
    {
        $this->Registry = $o;
    }


    /**
     * Getter for $this->oEntry
     *
     * @return object of type Entry
     */
    public function getEntry()
    {
        return $this->oEntry;
    }


    /**
     * Make object Entry
     * from object Post (which can be Answer or Question)
     *
     * @param Post $post object of type Post which
     * can be Answer or Question
     */
    public function makeEntry(Post $o)
    {
        $this->oEntry = new Entry();
        if ($o instanceof Question) {
            $this->makeQuestionPost($o);
        } elseif ($o instanceof Answer) {
            $this->makeAnswerPost($o);
        }
        d('cp');

        return $this->getEntry();
    }


    /**
     *
     * Setup values in $this->oTumblrPost using
     * values of Question
     *
     * @param Question $o
     */
    protected function makeQuestionPost(Question $o)
    {
        d('cp');

        /**
         * @todo Translate strings
         *
         * @var string
         */
        $qUrl = $o->getUrl();
        $tpl1 = '<p><a href="%s"><strong>My Question</strong></a> on %s</p>';
        $tpl2 = '<p><a href="%s">Click here</a> to post your reply</p><br>';
        $body = sprintf($tpl1, $qUrl, $this->Registry->Ini->SITE_NAME);
        $body .= $o->getBody();
        $body .= sprintf($tpl2, $qUrl);
        $body = HtmlString::factory($body);

        $this->oEntry->setBody($body)->setTitle($o->getTitle());

        $tags = $o['a_tags'];
        d('$tags: ' . print_r($tags, 1));
        if (!empty($tags)) {
            $this->oEntry->addTags($tags);
        }

        return $this;
    }


    /**
     *
     * Setup values in $this->oTumblrPost using
     * values of Answer
     *
     * @param Answer $o
     */
    protected function makeAnswerPost(Answer $o)
    {
        d('cp');
        $qlink = $this->Registry->Ini->SITE_URL . '/q' . $o->getQuestionId() . '/';

        /**
         * @todo Translate string
         *
         * @var string
         */
        $tpl = '<p>This is my answer to a <a href="%s"><strong>Question</strong></a> on %s</p><br>';
        $body = sprintf($tpl, $qlink, $this->Registry->Ini->SITE_NAME);

        $body .= $o->getBody();
        d('body: ' . $body);

        $body = HtmlString::factory($body);
        $title = 'My answer to "' . $o->getTitle() . '"';

        $this->oEntry->setBody($body)->setTitle($title);

        return $this;
    }
}
