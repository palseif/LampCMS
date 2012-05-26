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

class FollowManager extends LampcmsObject
{
    /**
     * @todo instead
     * should pass Mongo, Dispatcher objects
     * And not extend LampcmsObject
     *
     * @param Registry $Registry
     */
    public function __construct(Registry $Registry)
    {
        $this->Registry = $Registry;
    }


    /**
     *
     * Adds question ID to array of
     * a_f_q or User object
     *
     * The reason we do this using User object
     * and not in-place update in Mongo is because
     * this operation is always (at least for now) performed
     * by the Viewer and we need the changed to be made to
     * current Viewer object so that viewer will see that
     * he is not following the question anymore.
     *
     * @todo also update a_flwrs and i_flwrs count in Question?
     * Maybe not
     * maybe ONLY i_flwrs
     *
     * @todo do the addToSet via shutdown_function so it will
     * not delay the return
     *
     * @todo experiment later with getting followers data
     * from the USERS collection during Question display
     *
     * This will add one more find() query using indexed field
     * a_f_q in USERS but the upside is - no need to also store
     * the same data in QUESTIONS and most importantly
     * the data will be live - not stale avatars but live
     * updated values of avatars and user names from USERS
     * IMPORTANT: must still $inc number of followers in QUESTION
     * ONLY if addToSet succeeds in USERS (put inside the else{})
     *
     *
     * @param User $User
     *
     *
     * @param mixed $Question int | Question object
     *
     * @param $addUserTQuestion bool if false then will Not append
     * array of user data to QUESTION collection's a_flwrs array
     * This option is here because in QuestionParser we already
     * adding this array when we creating the brand new question - we
     * add Viewer as the first follower automatically.
     *
     * @throws DevException if Question is not int and not Question object
     */
    public function followQuestion(User $User, $Question)
    {
        d('cp');
        $coll = $this->Registry->Mongo->QUESTIONS;
        $coll->ensureIndex(array('a_flwrs' => 1), array('safe' => true));

        if (!is_int($Question) && (!is_object($Question) || !($Question instanceof Question))) {
            throw new DevException('$Question can only be instance of Question class or an integer representing question id');
        }

        $qid = (is_int($Question)) ? $Question : $Question->getResourceId();
        if (!is_object($Question)) {
            $this->checkQuestionExists($qid);
        }

        $uid = $User->getUid();
        d('qid: ' . $qid . ' $uid: ' . $uid);
        $this->Registry->Dispatcher->post($User, 'onBeforeQuestionFollow', array('qid' => $qid));


        if (is_object($Question)) {
            $Question->addFollower($uid);
        } else {
            /**
             * If we don't have Question object
             * then add userID directly to nested array
             * of a_flwrs in QUESTIONS COLLECTION
             * using $addToSet Mongo operator, it
             * ensures that if will NOT add duplicate value
             */
            $coll->ensureIndex(array('a_flwrs', 1));
            $coll->update(array('_id' => $qid), array('$addToSet' => array('a_flwrs' => $uid)));
        }

        $this->Registry->Dispatcher->post($User, 'onQuestionFollow', array('qid' => $qid));

        return $this;
    }


    /**
     *
     * Remove question id from the a_f_q array
     * in User object, meaning User will no longer be
     * following the question
     *
     * @todo also update a_flwrs and i_flwrs count in Question? Maybe not
     * maybe ONLY i_flwrs
     *
     * @param User $User
     *
     * @param mixed $Question int | Question object
     *
     * @throws DevException if Question is not int and not Question object
     */
    public function unfollowQuestion(User $User, $Question)
    {
        $coll = $this->Registry->Mongo->QUESTIONS;
        $coll->ensureIndex(array('a_flwrs' => 1));

        if (!is_int($Question) && (!is_object($Question) || !($Question instanceof Question))) {
            throw new DevException('$Question can only be instance of Question class or an integer representing question id');
        }

        $qid = (is_int($Question)) ? $Question : $Question->getResourceId();
        d('qid: ' . $qid);
        if (!is_object($Question)) {
            $this->checkQuestionExists($qid);
        }

        $uid = $User->getUid();

        if (is_object($Question)) {
            $Question->removeFollower($uid);
        } else {
            /**
             * If we don't have Question object
             * then add userID directly to nested array
             * of a_flwrs in QUESTIONS COLLECTION
             * using $addToSet Mongo operator, it
             * ensures that if will NOT add duplicate value
             */
            $coll->update(array('_id' => $qid), array('$pull' => array('a_flwrs' => $uid)));
        }

        $this->Registry->Dispatcher->post($User, 'onQuestionUnfollow', array('qid' => $qid));

        return $this;
    }


    /**
     * Check that record exists in QUESTIONS
     * for a given question id
     *
     * @param int $id
     *
     * @return object $this
     *
     * @throws \Lampcms\Exception if question with this id
     * does not exist.
     */
    protected function checkQuestionExists($qid)
    {

        $a = $this->Registry->Mongo->QUESTIONS->findOne(array('_id' => (int)$qid), array('_id'));
        if (empty($a)) {
            throw new Exception('Question with id ' . $qid . ' not found');
        }

        return $this;
    }


    /**
     * Adds the tag name to the array of a_f_t
     * of User object and increases the i_f_t by one
     * if USER does not already follow this tag
     * also increases the i_flwrs in QUESTION_TAGS collection
     * by one for this tag
     *
     *
     * @param User $User
     * @param string $tag
     * @throws \InvalidArgumentException if $tag is not a string
     */
    public function followTag(User $User, $tag)
    {

        if (!is_string($tag)) {
            throw new \InvalidArgumentException('$tag must be a string');
        }

        $tag = Utf8String::factory($tag)->toLowerCase()->stripTags()->trim()->valueOf();

        $aFollowed = $User['a_f_t'];
        d('$aFollowed: ' . print_r($aFollowed, 1));
        if (in_array($tag, $aFollowed)) {
            e('User ' . $User->getUid() . ' is already following question tag ' . $tag);

            return $this;
        }

        $this->Registry->Dispatcher->post($User, 'onBeforeTagFollow', array('tag' => $tag));

        $aFollowed[] = $tag;
        $User['a_f_t'] = $aFollowed;
        $User['i_f_t'] = count($aFollowed);
        $User->save();
        $this->Registry->Dispatcher->post($User, 'onTagFollow', array('tag' => $tag));

        $this->Registry->Mongo->QUESTION_TAGS->update(array('tag' => $tag), array('$inc' => array('i_flwrs' => 1)));

        return $this;
    }


    /**
     * Removes the tag name from the array of a_f_t
     * of User object and increases the i_f_t by one
     * if USER already follow this tag
     * also decreases the i_flwrs in QUESTION_TAGS collection
     * by one for this tag
     *
     *
     * @param User $User
     * @param string $tag
     * @throws \InvalidArgumentException if $tag is not a string
     */
    public function unfollowTag(User $User, $tag)
    {

        if (!is_string($tag)) {
            throw new \InvalidArgumentException('$tag must be a string');
        }

        $tag = Utf8String::factory($tag)->toLowerCase()->stripTags()->trim()->valueOf();


        $aFollowed = $User['a_f_t'];
        d('$aFollowed: ' . print_r($aFollowed, 1));

        if (false !== $key = array_search($tag, $aFollowed)) {
            d('cp unsetting key: ' . $key);
            array_splice($aFollowed, $key, 1);
            $User['a_f_t'] = $aFollowed;
            $User->save();
            $this->Registry->Mongo->QUESTION_TAGS->update(array('tag' => $tag), array('$inc' => array('i_flwrs' => -1)));
            $this->Registry->Dispatcher->post($User, 'onTagUnfollow', array('tag' => $tag));
        } else {
            d('tag ' . $tag . ' is not among the followed tags of this userID: ' . $User->getUid());
        }

        return $this;
    }


    /**
     * Process follow user request
     *
     * @param Object $User object of type User user who follows
     * @param int $userid id user being followed (followee)
     *
     * @return object $this
     */
    public function followUser(User $User, $userid)
    {

        if (!is_int($userid)) {
            throw new \InvalidArgumentException('$userid must be an integer');
        }

        $aFollowed = $User['a_f_u'];
        d('$aFollowed: ' . print_r($aFollowed, 1));

        if (in_array($userid, $aFollowed)) {
            e('User ' . $User->getUid() . ' is already following $userid ' . $userid);

            return $this;
        }

        $this->Registry->Dispatcher->post($User, 'onBeforeUserFollow', array('uid' => $userid));

        $aFollowed[] = $userid;
        $User['a_f_u'] = $aFollowed;
        $User['i_f_u'] = count($aFollowed);
        $User->save();
        $this->Registry->Dispatcher->post($User, 'onUserFollow', array('uid' => $userid));

        $this->Registry->Mongo->USERS->update(array('_id' => $userid), array('$inc' => array('i_flwrs' => 1)));

        return $this;

    }


    /**
     * Process unfollow user request
     *
     * @param User $User who is following
     * @param int $userid id user user being unfollowed
     * @throws \InvalidArgumentException
     *
     * @return object $this
     */
    public function unfollowUser(User $User, $userid)
    {

        if (!is_int($userid)) {
            throw new \InvalidArgumentException('$userid must be an integer');
        }

        $aFollowed = $User['a_f_u'];
        d('$aFollowed: ' . print_r($aFollowed, 1));

        if (false !== $key = array_search($userid, $aFollowed)) {
            d('cp unsetting key: ' . $key);
            array_splice($aFollowed, $key, 1);
            $User['a_f_u'] = $aFollowed;
            $User->save();
            $this->Registry->Mongo->USERS->update(array('_id' => $userid), array('$inc' => array('i_flwrs' => -1)));
            $this->Registry->Dispatcher->post($User, 'onUserUnfollow', array('uid' => $userid));
        } else {
            d('tag ' . $tag . ' is not among the followed tags of this userID: ' . $User->getUid());
        }

        return $this;
    }


    /**
     * Ensure indexes on nested arrays and
     * on email optins/optouts
     * Yes, USERS collection may seem like it has
     * too many indexes but it is still be efficient
     * than working without indexes and then filtering
     * out the optouts during the email notifications
     *
     * @return object $this
     */
    protected function ensureIndexes()
    {
        $coll = $this->Registry->Mongo->USERS;
        $coll->ensureIndex(array('a_f_t' => 1));
        $coll->ensureIndex(array('a_f_u' => 1));

        return $this;
    }

}

