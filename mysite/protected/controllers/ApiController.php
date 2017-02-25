<?php

class ApiController extends Controller
{
	public function init()
	{
		//header('Content-Type: application/json');
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
	 * For logging in the User
	 */
	public function actionLogin()
	{
		if (Yii::app()->user->isGuest == false)
		{
			echo json_encode(array('status' =>'logged_in','message'=>'This user is already logged in'));
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
			echo json_encode(array('status' =>'error','error_code'=>1,'message'=>'type was not provided'));
			Yii::app()->end();
		}
		else if (empty($title))
		{
			echo json_encode(array('status' =>'error','error_code'=>1,'message'=>'title was not provided'));
			Yii::app()->end();
		}
		else if (empty($content))
		{
			echo json_encode(array('status' =>'error','error_code'=>1,'message'=>'content was not provided'));
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
	 * For getting user interests
	 */
	public function actionUserstories()
	{
		$userId 	= Yii::app()->request->getParam('user_id',null);
		
		$criteria = new CDbCriteria();
		if($userId !== null)
		{
			$criteria->addInCondition('user_id',array($userId));
		}
		$criteria->order = 'posted_date DESC';
		
		$userStories = UserStory::model()->findAll($criteria);
		
		$stories = array();
		foreach ($userStories as $userStory)
		{
			$stories[] = $userStory->getAttributes();
		}
		echo json_encode(array('status' =>'success','stories'=>$stories));
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