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


namespace Lampcms;
require_once 'bootstrap.php';

require_once 'Fixtures/MockQuestion.php';
require_once 'Fixtures/MockUser.php';
require_once 'Fixtures/MockAnswer.php';
require_once 'Fixtures/tplQtags.php';

use Lampcms\Question;

/**
 * Run after UserTest, AnswerTest, CommentTest
 *
 */
class QuestionTest extends LampcmsUnitTestCase
{
    protected $Question;

    public function setUp()
    {
        $this->Question = new \Lampcms\MockQuestion(new Registry());
    }


    public function testScore()
    {
        $s = $this->Question->getScore();

        $this->assertEquals(0, $s);
    }

    /**
     *
     * @depends testScore
     */
    public function testAddVote()
    {

        $this->Question->addDownVote();

        $this->assertEquals(-1, $this->Question->getScore());

        $this->Question->addUpVote()
            ->addUpVote()
            ->addUpVote();

        $this->assertEquals(2, $this->Question->getScore());

    }


    public function testAddFollower()
    {
        $this->Question->addFollower(999999999);

        $aFollowers = $this->Question['a_flwrs'];
        $this->assertTrue(in_array(999999999, $aFollowers));
    }

    /**
     *
     * @depends testAddFollower
     */
    public function testRemoveFollower()
    {
        $this->Question->removeFollower(999999999);

        $aFollowers = $this->Question['a_flwrs'];
        $this->assertFalse(in_array(999999999, $aFollowers));
    }


    public function testGetCommentsCount()
    {
        $this->assertTrue(2 === $this->Question->getCommentsCount());
    }

    public function testGetOwnerId()
    {
        $this->assertTrue(3 === $this->Question->getOwnerId());
    }

    public function testGetQuestionOwnerId()
    {
        $this->assertTrue(3 === $this->Question->getQuestionOwnerId());
    }

    public function testgetResourceTypeId()
    {
        $this->assertEquals('QUESTION', $this->Question->getResourceTypeId());
    }

    public function testGetQuestionId()
    {
        $this->assertEquals(510, $this->Question->getQuestionId());
    }

    public function testGetAnswerCount()
    {
        $this->assertTrue(1 === $this->Question->getAnswerCount());
    }

    /**
     *
     * @depends testGetAnswerCount
     */
    public function testIncreaseAnswerCountTwice()
    {
        $this->Question->updateAnswerCount()->updateAnswerCount();

        $this->assertTrue(3 === $this->Question->getAnswerCount());
    }

    /**
     *
     * @depends testGetAnswerCount
     */
    public function testDecreaseAnswerCountBelow1()
    {
        $this->Question->updateAnswerCount(-5);

        $this->assertTrue(0 === $this->Question->getAnswerCount());
        $this->assertTrue('unans' === $this->Question->offsetGet('status'));
    }


    public function testGetLastModified()
    {
        $this->assertEquals(1305401334, $this->Question->getLastModified());
    }

    /**
     *
     * @depends testGetLastModified
     */
    public function testGetEtag()
    {
        $this->assertEquals(1305401334, $this->Question->getEtag());
    }

    /**
     *
     * @depends testGetEtag
     */
    public function testTouch()
    {
        $this->Question->touch();
        $this->assertTrue((time() - $this->Question->getEtag()) < 2);
        $this->assertTrue((time() - $this->Question->getLastModified()) < 2);
    }


    /**
     *
     * @depends testGetCommentsCount
     */
    public function testGetComments()
    {
        $a = $this->Question->getComments();
        $this->assertTrue(511 === $a[0]['_id']);
        $this->assertTrue(512 === $a[1]['_id']);
    }


    public function testGetTitle()
    {
        $this->assertEquals('Mock Stub Post', $this->Question->getTitle());
    }

    public function testGetBody()
    {
        $this->assertEquals('<span>This is a simple <em class="wtag">mock</em> question</span>', $this->Question->getBody());
    }

    public function testGetUsername()
    {
        $this->assertEquals('user1', $this->Question->getUsername());
    }


    public function testIsClosed()
    {
        $this->assertFalse($this->Question->isClosed());
    }

    public function testSeoUrl()
    {
        $this->assertEquals('Mock-Stub-Post', $this->Question->getSeoUrl());
    }

    public function testGetRegistry()
    {
        $this->assertInstanceOf('\Lampcms\Registry', $this->Question->getRegistry());
    }


    /**
     * @depends testGetRegistry
     *
     */
    public function testGetUrl()
    {

        $Registry = $this->Question->getRegistry();

        $siteUrl = $Registry->Ini->SITE_URL;
        $url = $this->Question->getUrl();
        $shortUrl = $this->Question->getUrl(true);

        $this->assertEquals('/q510/Mock-Stub-Post', \substr($url, strlen($siteUrl)));
        $this->assertEquals('/q510/', \substr($shortUrl, strlen($siteUrl)));
    }


    /**
     *
     * @depends testGetComments
     */
    public function testDeleteCommentComments()
    {
        $a = $this->Question->deleteComment(511);
        $this->assertTrue(1 === $this->Question->getCommentsCount());
    }


    public function testAddContributor()
    {
        $this->Question->addContributor(5);
        $a1 = $this->Question['a_uids'];
        $this->assertTrue(in_array(5, $a1));
        $this->assertFalse(in_array(26, $a1));
        $this->Question->addContributor(new MockUser($this->Question->getRegistry()));
        $a2 = $this->Question['a_uids'];
        $this->assertTrue(in_array(26, $a2));

    }


    /**
     * @depends testAddContributor
     *
     */
    public function testRemoveContributor()
    {
        $this->Question->addContributor(new MockUser($this->Question->getRegistry()));
        $this->Question->removeContributor(new MockUser($this->Question->getRegistry()));
        $a2 = $this->Question['a_uids'];
        $this->assertFalse(in_array(26, $a2));
    }


    public function testSetBestAnswer()
    {
        $Answer = new MockAnswer($this->Question->getRegistry());
        $this->Question->setBestAnswer($Answer);

        $this->assertEquals(513, $this->Question['i_sel_ans']);
        $this->assertEquals(3, $this->Question['i_sel_uid']);
        $this->assertEquals('accptd', $this->Question['status']);
        $this->assertTrue((time() - $this->Question['i_etag']) < 2);
        $this->assertTrue($Answer['accepted'] === true);
        $this->assertTrue((time() - $Answer->getLastModified()) < 2);
    }


    public function testSetDeleted()
    {
        $User = new MockUser($this->Question->getRegistry());
        $this->Question->setDeleted($User, 'test of deleting');

        $a = $this->Question['a_deleted'];

        $this->assertTrue(is_array($a));
        $this->assertEquals(count($a), 5);
        $this->assertEquals('John D Doe', $a['username']);
        $this->assertEquals('test of deleting', $a['reason']);
        $this->assertEquals(26, $a['i_uid']);
        $this->assertTrue((time() - $this->Question['i_del_ts']) < 2);
    }

    /**
     * @depends testSetDeleted
     *
     */
    public function testGetDeletedTime()
    {
        $this->assertEquals(0, $this->Question->getDeletedTime());
        $User = new MockUser($this->Question->getRegistry());
        $this->Question->setDeleted($User, 'test of deleting');
        $this->assertTrue((time() - $this->Question->getDeletedTime()) < 2);
    }


    public function testSetEdited()
    {
        $User = new MockUser($this->Question->getRegistry());
        $this->Question->setEdited($User, 'test of editing');
        $a = $this->Question['a_edited'];
        $this->assertTrue(is_array($a));
        $this->assertTrue(count($a) > 0);

        $aEdited = end($a);
        $this->assertTrue(is_array($aEdited));
        $this->assertEquals('John D Doe', $aEdited['username']);
        $this->assertEquals('test of editing', $aEdited['reason']);
        $this->assertEquals(26, $aEdited['i_uid']);
        $this->assertEquals(26, $aEdited['i_uid']);
    }


    /**
     * @depends testIsClosed
     *
     */
    public function testSetClosed()
    {
        $User = new MockUser($this->Question->getRegistry());
        $this->Question->setClosed($User, 'test of closed');
        $a = $this->Question['a_closed'];
        $this->assertTrue(is_array($a));

        $this->assertEquals(count($a), 5);
        $this->assertEquals('John D Doe', $a['username']);
        $this->assertEquals('test of closed', $a['reason']);
        $this->assertEquals(26, $a['i_uid']);
        $this->assertEquals($a['av'], $User->getAvatarSrc());
        $this->assertSame($a, ($this->Question->isClosed()));
    }


    /**
     * @depends testGetAnswerCount
     *
     */
    public function testUpdateAnswerCount()
    {
        $this->Question->updateAnswerCount(-1);
        $this->assertEquals(0, $this->Question->getAnswerCount());
        $this->assertEquals('unans', $this->Question['status']);
        $this->assertEquals('s', $this->Question['ans_s']);

        $this->Question->updateAnswerCount(-2);
        $this->assertEquals(0, $this->Question->getAnswerCount());
        $this->assertEquals('unans', $this->Question['status']);
        $this->assertEquals('s', $this->Question['ans_s']);

        $this->Question->updateAnswerCount();
        $this->assertEquals(1, $this->Question->getAnswerCount());
        $this->assertEquals('answrd', $this->Question['status']);
        $this->assertEquals('', $this->Question['ans_s']);

        $this->Question->updateAnswerCount(1);
        $this->assertEquals(2, $this->Question->getAnswerCount());
        $this->assertEquals('answrd', $this->Question['status']);
        $this->assertEquals('s', $this->Question['ans_s']);
    }


    public function testIncreaseViews()
    {
        $User = new MockUser($this->Question->getRegistry());
        $this->Question->increaseViews($User);
        $this->assertEquals(2, $this->Question['i_views']);
        $this->Question->increaseViews($User);
        $this->assertEquals(2, $this->Question['i_views']);

        $User['_id'] = 7;
        $this->Question->increaseViews($User);
        $this->assertEquals(3, $this->Question['i_views']);
        /**
         * Test when Viewer is owner of question
         * in which case view should not count
         */
        $User['_id'] = 3;
        $this->Question->increaseViews($User);
        $this->assertEquals(3, $this->Question['i_views']);

        /**
         * Test when Viewer is guest (_id is 0)
         * View should count
         * in which case view should not count
         */
        $User['_id'] = 0;
        $this->Question->increaseViews($User);
        $this->assertEquals(4, $this->Question['i_views']);
    }


    /**
     * @depends testSetBestAnswer
     *
     */
    public function testSetLatestAnswer()
    {

        $User = new MockUser($this->Question->getRegistry());
        $Answer = new MockAnswer($this->Question->getRegistry());

        $this->Question->setLatestAnswer($User, $Answer);
        $a = $this->Question['a_latest'];
        $this->assertTrue(is_array($a[0]));
        $this->assertEquals(1, count($a));
        $this->assertEquals('<a href="/users/26/ladada">John D Doe</a>', $a['0']['u']);
        $this->assertEquals(513, $a['0']['id']);

        $Answer['_id'] = 999;
        $User['username'] = 'Dude';
        $User['_id'] = 999999;
        $Answer->setSaved();
        $User->setSaved();

        $this->Question->setLatestAnswer($User, $Answer);
        $a = $this->Question['a_latest'];
        $this->assertTrue(is_array($a[0]));
        $this->assertEquals(2, count($a));
        $this->assertEquals('<a href="/users/999999/Dude">John D Doe</a>', $a['0']['u']);
        $this->assertEquals(999, $a['0']['id']);
        $this->assertEquals('<a href="/users/26/ladada">John D Doe</a>', $a['1']['u']);
        $this->assertEquals(513, $a['1']['id']);

        $this->Question->insert();

        $Question = new Question($this->Question->getRegistry());
        $Question->by_id(510);

        $a = $Question['a_latest'];
        $this->assertEquals(999, $a['0']['id']);
    }


    /**
     * @depends testSetLatestAnswer
     *
     */
    public function testRemoveAnswer()
    {

        /**
         * Mock question has i_ans set to 1, we need to reset it
         * to 0 for this test
         */
        $this->Question->updateAnswerCount(-1);

        $User = new MockUser($this->Question->getRegistry());
        $Answer = new MockAnswer($this->Question->getRegistry());
        $Answer2 = new MockAnswer($this->Question->getRegistry());
        $Answer2['_id'] = 999;

        $this->Question->setLatestAnswer($User, $Answer)->updateAnswerCount();
        $this->Question->setBestAnswer($Answer);

        $this->Question->setLatestAnswer($User, $Answer2)->updateAnswerCount();
        $a = $this->Question['a_latest'];
        $this->assertEquals(999, $a['0']['id']);
        $this->assertEquals('accptd', $this->Question['status']);
        $this->assertEquals(3, $this->Question['i_sel_uid']);
        $this->assertEquals(513, $this->Question['i_sel_ans']);

        /**
         * Remove answer that was set as best
         * answer should remove accptd status
         * and change it to answrd
         * it should also unset keys i_sel_uid and i_sel_ans
         */
        $this->Question->removeAnswer($Answer);
        $a = $this->Question['a_latest'];
        $this->assertEquals(999, $a['0']['id']);
        $this->assertEquals('answrd', $this->Question['status']);
        $this->assertFalse($this->Question->offsetExists('i_sel_uid'));
        $this->assertFalse($this->Question->offsetExists('i_sel_ans'));

        /**
         * Removing second answer should
         * completely remove the a_latest key
         * and reset status to unans since there
         * are no answeres now
         */
        $this->Question->removeAnswer($Answer2);
        $this->assertEquals('unans', $this->Question['status']);
        $this->assertFalse($this->Question->offsetExists('a_latest'));

    }


    /**
     * @depends testSetEdited
     *
     */
    public function testRetag()
    {
        $User = new MockUser($this->Question->getRegistry());
        $this->Question->retag($User, array('brown', 'fox'));

        $tags = $this->Question['a_tags'];
        $tagsHtml = $this->Question['tags_html'];
        $body = $this->Question['b'];

        $this->assertEquals(array('brown', 'fox'), $tags);
        $this->assertContains('<a href="/tagged/brown/" title="Questions tagged brown">brown</a> <a href="/tagged/fox/" title="Questions tagged fox">fox</a>', $tagsHtml);
        $this->assertEquals('<span>This is a simple mock question</span>', $body);

        $this->Question->retag($User, array('mock', 'simple'));

        $tags = $this->Question['a_tags'];
        $tagsHtml = $this->Question['tags_html'];
        $body = $this->Question['b'];

        $this->assertEquals(array('mock', 'simple'), $tags);
        $this->assertContains('<a href="/tagged/mock/" title="Questions tagged mock">mock</a> <a href="/tagged/simple/" title="Questions tagged simple">simple</a>', $tagsHtml);
        $this->assertEquals('<span>This is a <em class="wtag">simple</em> <em class="wtag">mock</em> question</span>', $body);

        $a = $this->Question['a_edited'];
        $this->assertTrue(is_array($a));
        $this->assertTrue(count($a) > 0);

        $aEdited = end($a);
        $this->assertTrue(is_array($aEdited));
        $this->assertEquals('John D Doe', $aEdited['username']);
        $this->assertEquals('Retagged', $aEdited['reason']);

    }
}
