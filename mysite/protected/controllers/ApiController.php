<?php

class ApiController extends Controller
{
	public function init()
	{
		header('Content-Type: application/json');
	}
	/**
	 * This is the action to handle external exceptions.
	 */
	public function actionError()
	{
		if($error=Yii::app()->errorHandler->error)
		{
			if(Yii::app()->request->isAjaxRequest)
				echo $error['message'];
				else
					$this->render('error', $error);
		}
	}
	public function actionTest()
	{
		if (!Yii::app()->user->isGuest)
			echo json_encode(Yii::app()->user->user_name);
	}
	
	/**
	 * For registering User through API
	 */
	public function actionRegister()
	{
		if (Yii::app()->user->isGuest == false)
		{
			echo json_encode(array('status' =>'error','error_code'=>6,'message'=>'Need to logout before registering a new account'));
			Yii::app()->end();
		}
		
		$deviceToken = Yii::app()->request->getParam('device_token',null);
		$deviceType 	= Yii::app()->request->getParam('device_type',null);
	
		if ($deviceToken == null)
		{
			echo json_encode(array('status' =>'error','error_code'=>1,'message'=>'device_token not provided.'));
			Yii::app()->end();
		}
		else if ($deviceType == null)
		{
			echo json_encode(array('status' =>'error','error_code'=>2,'message'=>'device_type not provided.'));
			Yii::app()->end();
		}
	
		$criteria = new CDbCriteria();
		$criteria->addCondition('device_token = :device_token');
		$criteria->addCondition('device_type = :device_type');
		$criteria->params = array(':device_token'=>$deviceToken,':device_type'=>$deviceType);
	
		$user = new User();
		$user = User::model()->find($criteria);
	
		if ($user && $user->user_name != null)
		{
			echo json_encode(array('status' =>'user_exist','user_name'=>$user->user_name));
			Yii::app()->end();
		}
	
		$user = new User();
		$user->user_name = substr( md5(rand()), 0, 15);
		$user->device_type = $deviceType;
		$user->device_token = $deviceToken;
	
		try
		{
			$user->save();
		}
		catch (CDbException $e)
		{
			echo json_encode(array('status' =>'error','error_code'=>3,'error'=>$e));
			Yii::app()->end();
		}
	
		if($user->hasErrors())
		{
			echo json_encode(array('status' =>'error','error_code'=>4,'error'=>$user->errors));
			Yii::app()->end();
		}
		
		$identity=new UserIdentity($user->user_name,$deviceToken);
		
		if($identity->authenticate())
		{
			Yii::app()->user->login($identity);
			echo json_encode(array('status' =>'success','user_id'=>Yii::app()->user->id,'user_name'=>Yii::app()->user->user_name));
		}
		else
			echo json_encode(array('status' =>'error','error_code'=>5,'message'=>'Authentication Error.'));
	}
	
	/**
	 * For logging in the User.
	 */
	public function actionLogin()
	{
		if (Yii::app()->user->isGuest == false)
		{
			echo json_encode(array('status' =>'logged_in','message'=>'User is already logged in'));
			Yii::app()->end();
		}
		$deviceToken 	= Yii::app()->request->getPost('device_token',null);
		$userName		= Yii::app()->request->getPost('user_name',null);
	
		if ($userName == null)
		{
			echo json_encode(array('status' =>'error','error_code'=>2,'message'=>'user_name not provided.'));
			Yii::app()->end();
		}
		else if ($deviceToken == null)
		{
			echo json_encode(array('status' =>'error','error_code'=>1,'message'=>'device_token not provided.'));
			Yii::app()->end();
		}
	
		$identity=new UserIdentity($userName,$deviceToken);
		if($identity->authenticate())
		{
			Yii::app()->user->login($identity);
			echo json_encode(array('status' =>'success','id'=>Yii::app()->user->id,'user_name'=>Yii::app()->user->user_name));
		}
		else
			echo json_encode(array('status' =>'error','error_code'=>3,'message'=>'Authentication Error.'));
	}
	

	/**
	 * For getting user interests
	 */
	public function actionUserinterests()
	{
		$userId 	= Yii::app()->request->getParam('user_id',null);
		if ($userId == null)
		{
			echo json_encode(array('status' =>'error','error_code'=>1,'message'=>'user_id not provided.'));
			Yii::app()->end();
		}
		$userInterests = UserInterest::model()->findAllByAttributes(array('user_id'=>$userId));
	
		$interests = array();
		foreach ($userInterests as $userInterest)
		{
			$interests[] = $userInterest->interest;
		}
		echo json_encode(array('status' =>'success','interests'=>$interests));
		Yii::app()->end();
	}

	/**
	 * For adding User interests
	 */
	public function actionInterestedwith()
	{
		if (Yii::app()->user->isGuest == true)
		{
			echo json_encode(array('status' =>'error','error_code'=>1,'message'=>'Not logged in, please log in first'));
			Yii::app()->end();
		}
	
		$interestList 	= Yii::app()->request->getPost('interests',array());
		if (count($interestList) > 50)
		{
			echo json_encode(array('status' =>'error','error_code'=>2,'message'=>'You can\'t define more than 50 interests'));
			Yii::app()->end();
		}
		else if (count($interestList) == 0)
		{
			echo json_encode(array('status' =>'error','error_code'=>3,'message'=>'didnt supply an array of interests'));
			Yii::app()->end();
		}
	
		UserInterest::model()->deleteAllByAttributes(array('user_id'=>Yii::app()->user->id)); //Deleting all the interests so that we can reset them
	
		$userInterests = array();
	
		foreach ($interestList as $interest)
		{
			$userInterest = new UserInterest();
			$userInterest->user_id = Yii::app()->user->id;
			$userInterest->interest = $interest;
			try
			{
				$userInterest->save();
			}
			catch (CDbException $e)
			{
				echo json_encode(array('status' =>'error','error_code'=>4,'error'=>$e));
				Yii::app()->end();
			}
			$userInterests[] = $interest;
		}
	
		echo json_encode(array('status' =>'success','interests'=>$userInterests));
	}
	
	/**
	 * For adding a user story
	 */
	public function actionAddstory()
	{
		if (Yii::app()->user->isGuest == true)
		{
			echo json_encode(array('status' =>'error','error_code'=>1,'message'=>'Not logged in, please log in first'));
			Yii::app()->end();
		}
	
		$type = Yii::app()->request->getPost('type',null);
		$title = Yii::app()->request->getPost('title',null);
		$hashtag = Yii::app()->request->getPost('hashtag',null);
		$content = Yii::app()->request->getPost('content',null);
	
		if (empty($type))
		{
			echo json_encode(array('status' =>'error','error_code'=>2,'message'=>'type was not provided'));
			Yii::app()->end();
		}
		else if (empty($title))
		{
			echo json_encode(array('status' =>'error','error_code'=>3,'message'=>'title was not provided'));
			Yii::app()->end();
		}
		else if (empty($content))
		{
			echo json_encode(array('status' =>'error','error_code'=>4,'message'=>'content was not provided'));
			Yii::app()->end();
		}
		else if (!in_array($type,[1,2,3,4,5,6]))
		{
			echo json_encode(array('status' =>'error','error_code'=>5,'message'=>'type doesnt exist, need to insert one of these (1,2,3,4,5,6)'));
			Yii::app()->end();
		}
	
		$story = new UserStory();
		$story->user_id = Yii::app()->user->id;
		$story->title = $title;
		$story->type = $type;
		$story->hashtags = '#'.implode(' #',explode(',',$hashtag));
		$story->content = $content;
		try
		{
			$story->save();
		}
		catch (CDbException $e)
		{
			echo json_encode(array('status' =>'error','error_code'=>4,'error'=>$e));
			Yii::app()->end();
		}
		echo json_encode(array('status' =>'success','story'=>$story->getAttributes()));
	}
	
	/**
	 * For getting user stories
	 */
	public function actionUserstories()
	{
		$userId 	= Yii::app()->request->getParam('user_id',null);
		$offset = Yii::app()->request->getParam('offset',0);
		$storiesLimit = 50;
		
		$criteria = new CDbCriteria();
		if($userId !== null)
		{
			$criteria->addInCondition('user_id',array($userId));
		}
		$criteria->limit = $storiesLimit + 1;
		$criteria->offset = $offset;
		$criteria->order = 'posted_date DESC';
		
		$userStories = UserStory::model()->findAll($criteria);
		
		$moreStoriesExist = false;
		if (count($userStories) > $storiesLimit)
		{
			$moreStoriesExist = true;
			array_pop($userStories);
		}
		
		$count = count($userStories);
		
		$nextOffset = $offset + $count;
		
		$stories = array();
		foreach ($userStories as $userStory)
		{
			$stories[] = $userStory->getAttributes();
		}
		echo json_encode(array('status' =>'success','next_offset'=>$nextOffset,'more_stories_exist'=>$moreStoriesExist,'count'=>$count,'stories'=>$stories));
	}

	/**
	 * Add Comment to a story
	 */
	public function actionAddComment()
	{
		if (Yii::app()->user->isGuest == true)
		{
			echo json_encode(array('status' =>'error','error_code'=>1,'message'=>'Not logged in, please log in first'));
			Yii::app()->end();
		}
		
		$storyId = Yii::app()->request->getPost('story_id',null);
		$comment = Yii::app()->request->getPost('text',null);
		
		if (empty($storyId))
		{
			echo json_encode(array('status' =>'error','error_code'=>2,'message'=>'story_id was not provided'));
			Yii::app()->end();
		}
		else if (empty($comment))
		{
			echo json_encode(array('status' =>'error','error_code'=>3,'message'=>'text was not provided'));
			Yii::app()->end();
		}
		
		if(UserStory::model()->findByPk($storyId) == null)
		{
			echo json_encode(array('status' =>'error','error_code'=>4,'message'=>'Story doesn\'t exist'));
			Yii::app()->end();
		}
		
		$storyComment = new StoryComment();
		$storyComment->user_id = Yii::app()->user->id;
		$storyComment->comment = $comment;
		$storyComment->story_id = $storyId;
		try
		{
			$storyComment->save();
		}
		catch (CDbException $e)
		{
			echo json_encode(array('status' =>'error','error_code'=>4,'error'=>$e));
			Yii::app()->end();
		}
		
		$story = UserStory::model()->findByPk($storyComment->story_id);
		$story->comments_count++;
		$story->save();
		
		echo json_encode(array('status' =>'success','comments_count'=>$story->comments_count,'storyComment'=>$storyComment->getAttributes()));
	}
	
	/**
	 * For getting user interests
	 */
	public function actionStoryComments()
	{
		$storyId 	= Yii::app()->request->getParam('story_id',null);
		
		if (empty($storyId))
		{
			echo json_encode(array('status' =>'error','error_code'=>2,'message'=>'story_id was not provided'));
			Yii::app()->end();
		}
		else if(UserStory::model()->findByPk($storyId) == null)
		{
			echo json_encode(array('status' =>'error','error_code'=>4,'message'=>'Story doesn\'t exist'));
			Yii::app()->end();
		}
		
		$offset = Yii::app()->request->getParam('offset',0);
		$commentsLimit = 50;
	
		$criteria = new CDbCriteria();

		$criteria->addInCondition('story_id',array($storyId));
		$criteria->limit = $commentsLimit + 1;
		$criteria->offset = $offset;
		$criteria->order = 'timestamp DESC';
	
		$storyComments = StoryComment::model()->findAll($criteria);
	
		$moreStoriesExist = false;
		if (count($storyComments) > $commentsLimit)
		{
			$moreStoriesExist = true;
			array_pop($storyComments);
		}
	
		$count = count($storyComments);
	
		$nextOffset = $offset + $count;
	
		$comments = array();
		foreach ($storyComments as $storyComment)
		{
			$comments[] = $storyComment->getAttributes();
		}
		echo json_encode(array('status' =>'success','next_offset'=>$nextOffset,'more_stories_exist'=>$moreStoriesExist,'count'=>$count,'comments'=>$comments));
	}
	
	/**
	 * Delete Comment
	 */
	public function actionDeleteComment()
	{
		if (Yii::app()->user->isGuest == true)
		{
			echo json_encode(array('status' =>'error','error_code'=>1,'message'=>'Not logged in, please log in first'));
			Yii::app()->end();
		}
		
		$commentId 	= Yii::app()->request->getPost('comment_id',null);
		
		if ($commentId == null)
		{
			echo json_encode(array('status' =>'error','error_code'=>2,'message'=>'comment_id not provided'));
			Yii::app()->end();
		}
		
		$storyComment = StoryComment::model()->findByPk($commentId);
		
		if ($storyComment == null)
		{
			echo json_encode(array('status' =>'error','error_code'=>3,'message'=>'Comment doesn\'t exist'));
			Yii::app()->end();
		}
		
		if ($storyComment->user_id != Yii::app()->user->id)
		{
			echo json_encode(array('status' =>'error','error_code'=>4,'message'=>'Comment doesn\'t belong to user'));
			Yii::app()->end();
		}
		
		$story = UserStory::model()->findByPk($storyComment->story_id);
		$story->comments_count--;
		$story->save();
		
		$storyComment->delete();
		echo json_encode(array('status' =>'success','comments_count'=>$story->comments_count,'message'=>'Comment deleted successfully'));
	}
	
	/**
	 * Like story
	 */
	public function actionLikeStory()
	{
		if (Yii::app()->user->isGuest == true)
		{
			echo json_encode(array('status' =>'error','error_code'=>1,'message'=>'Not logged in, please log in first'));
			Yii::app()->end();
		}
	
		$storyId 	= Yii::app()->request->getPost('story_id',null);
	
		if ($storyId == null)
		{
			echo json_encode(array('status' =>'error','error_code'=>2,'message'=>'story_id not provided'));
			Yii::app()->end();
		}
	
		$story = UserStory::model()->findByPk($storyId);
	
		if ($story == null)
		{
			echo json_encode(array('status' =>'error','error_code'=>3,'message'=>'Story doesn\'t exist'));
			Yii::app()->end();
		}

		$story->likes_count++;
		$story->save();

		echo json_encode(array('status' =>'success','likes_count'=>$story->likes_count,'message'=>'Liked story successfully'));
	}
	
	/**
	 * Delete Comment
	 */
	public function actionRepostStory()
	{
		if (Yii::app()->user->isGuest == true)
		{
			echo json_encode(array('status' =>'error','error_code'=>1,'message'=>'Not logged in, please log in first'));
			Yii::app()->end();
		}
	
		$storyId 	= Yii::app()->request->getPost('story_id',null);
	
		if ($storyId == null)
		{
			echo json_encode(array('status' =>'error','error_code'=>2,'message'=>'story_id not provided'));
			Yii::app()->end();
		}
	
		$story = UserStory::model()->findByPk($storyId);
		$story->id = null;
		$story->user_id = Yii::app()->user->id;
		$story->likes_count = 0;
		$story->posted_date = date("Y-m-d h:i:s");
		$story->comments_count = 0;
		$story->setIsNewRecord(true);
		
		$story->save();
		
		echo json_encode(array('status' =>'success','story'=>$story->getAttributes(),'message'=>'Story '.$storyId.' got reposted for user '.Yii::app()->user->id));
	}
	
	/**
	 * For getting logged in user stories
	 */
	public function actionMystories()
	{
		if (Yii::app()->user->isGuest == true)
		{
			echo json_encode(array('status' =>'error','error_code'=>1,'message'=>'Not logged in, please log in first'));
			Yii::app()->end();
		}
		
		$userId = Yii::app()->user->id;
		$offset = Yii::app()->request->getParam('offset',0);
		$storiesLimit = 50;
	
		$criteria = new CDbCriteria();
		$criteria->addInCondition('user_id',array($userId));
		$criteria->limit = $storiesLimit + 1;
		$criteria->offset = $offset;
		$criteria->order = 'posted_date DESC';
	
		$userStories = UserStory::model()->findAll($criteria);
	
		$moreStoriesExist = false;
		if (count($userStories) > $storiesLimit)
		{
			$moreStoriesExist = true;
			array_pop($userStories);
		}
	
		$count = count($userStories);
	
		$nextOffset = $offset + $count;
	
		$stories = array();
		foreach ($userStories as $userStory)
		{
			$stories[] = $userStory->getAttributes();
		}
		echo json_encode(array('status' =>'success','next_offset'=>$nextOffset,'more_stories_exist'=>$moreStoriesExist,'count'=>$count,'stories'=>$stories));
	}
	
	/**
	 * For Logging out the User
	 */
	public function actionLogout()
	{
		Yii::app()->user->logout();
		echo json_encode(array('status'=>'success'));
	}
}